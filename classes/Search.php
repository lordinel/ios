<?php


// class definition for search functionality
class Search extends Layout
{
	private $table;
	private $target;
	private $key;
	private $field;
	private $header;
	private $meta;
	private $metaExtension;



	public function __construct( $table, $target, $key, array $field, array $header, array $meta, $metaExtension = null )
	{
		$this->table = $table;
		$this->target = $target;
		$this->key = $key;
		$this->field = $field;
		$this->header = $header;
		$this->meta = $meta;
		$this->metaExtension = $metaExtension;
	}


	public function search( $searchText, $isExactSearch = false )
	{
		if ( self::$database == null ) {
			self::$database = new Database();
		}

		$resultArray = array();
		$searchText  = mysql_real_escape_string( trim( $searchText ) );
		$length 	 = sizeof( $this->field );


		// construct query
		if ( $this->table == "`order`" && $this->key == "id" ) {
			// limit search results by branch assignments
			$sqlQuery = "SELECT `order`." . $this->key;
		} else {
			$sqlQuery = "SELECT " . $this->key;
		}
		$clause   = "";

		// special handing for amounts
		$isAmount = false;

		for ( $i = 0; $i < $length; $i++ ) {
			if ( $this->key != $this->field[$i] ) {			
				if ( $this->table == "`order`" && $this->field[$i] == "id" ) {
					// limit search results by branch assignments
					$sqlQuery = $sqlQuery . ", `order`." . $this->field[$i];
				} else {
					$sqlQuery = $sqlQuery . ", " . $this->field[$i];
				}
			}

			// special handing for amounts
			if ( $this->field[$i] == 'amount' ) {
				$searchText = str_replace( ',', '', $searchText );
				$isAmount = true;
			}

			if ( $isExactSearch ) {
				if ( $this->table == "`order`" && $this->field[$i] == "id" ) {
					// limit search results by branch assignments
					$clause = $clause . "`order`." . $this->field[$i] . " = '" . $searchText . "'";
				} else {
					$clause = $clause . $this->field[$i] . " = '" . $searchText . "'";
				}
			} else {
				if ( $this->table == "`order`" && $this->field[$i] == "id" ) {
					// limit search results by branch assignments
					$clause = $clause . "`order`." . $this->field[$i] . " LIKE '%" . $searchText . "%'";
				} else {
					$clause = $clause . $this->field[$i] . " LIKE '%" . $searchText . "%'";
				}
			}

			if ( ( $i + 1 ) != $length ) {
				$clause = $clause." OR ";
			}
		}

		
		if ( $this->table == "inventory" ) {
			// special handling for inventory
			$sqlQuery = $sqlQuery . ", CONCAT(name,'</span> <span>(Remaining Stocks: <b>',stock_count,'</b>)') AS model_meta FROM " .
				$this->table . " INNER JOIN inventory_brand ON inventory_brand.id = inventory.brand_id WHERE " . $clause;
		} elseif ( $this->table == "`order`" ) {
			// special handling for orders
			// limit search results by branch assignments
			$sqlQuery = $sqlQuery . " FROM " . $this->table .
						" INNER JOIN customer ON `order`.customer_id = customer.id WHERE (" .
						$clause . ") AND " . User::getQueryForBranch(self::$database);
		} elseif ( $this->table == "order_payment" ) {
			$sqlQuery = $sqlQuery . " FROM " . $this->table .
						" INNER JOIN `order` ON `order`.id = order_payment.order_id" .
						" INNER JOIN customer ON `order`.customer_id = customer.id WHERE (" .
						$clause . ") AND " . User::getQueryForBranch(self::$database);
		} elseif ( $this->table == "customer" ) {
			$sqlQuery = $sqlQuery . " FROM " . $this->table . " WHERE (" . $clause . ") AND " . User::getQueryForBranch(self::$database);
		} else {
			$sqlQuery = $sqlQuery . " FROM " . $this->table . " WHERE " . $clause;
		}
		
		// sort orders and purchases by date
		if ( $this->table == "`order`" ) {
			$sqlQuery = $sqlQuery . " ORDER BY order_date DESC";
		} elseif ( $this->table == "purchase" ) {
			$sqlQuery = $sqlQuery . " ORDER BY purchase_date DESC";
		}
		//echo $sqlQuery . '<br />';
		

		// execute query
		$resultSet = self::$database->query( $sqlQuery );
		

		if ( self::$database->getResultCount( $resultSet ) > 0 )
		{
			// process result
			while ( $result = self::$database->getResultRow( $resultSet ) ) {
				for ( $i = 0; $i < $length; $i++ ) {
					// check if search text is found in record
					$patternFound = false;
					if ( ( $this->field[$i] == "id" || $this->field[$i] == "n.id" ) && (int) $result[$this->field[$i]] == (int) $searchText ) {
						$patternFound = true;
					} else {
						$patternFound = strpos( strtolower( $result[$this->field[$i]] ), $searchText );
					}


					if ( $patternFound !== false ) {
						$fieldText 		  = html_entity_decode( capitalizeWords( htmlentities( stripslashes( $result[$this->field[$i]] ) ) ) );
						$searchText       = stripslashes( $searchText );
						
						// special handing for amounts
						if ( $isAmount ) {
							$fieldText = numberFormat( $fieldText, 'float', 3, '.', ',', true );
							if ( strpos( $searchText, '.' ) ) {
								$searchText = numberFormat( $searchText, 'float', 3, '.', ',', true );
							} else {
								$searchText = numberFormat( $searchText, 'int', 0, '.', ',', true );
							}
						}
						
						$position		  = stripos( $fieldText, $searchText );
						$searchTextLength = strlen( $searchText );
						$pattern		  = '/' . quotemeta( $searchText ) . '/i';
						$fieldText 		  = preg_replace( $pattern,
														  '<span class="search_hit">' .
														      htmlspecialchars( substr( $fieldText, $position, $searchTextLength ) ) .
														      '</span>',
														  $fieldText, 1 );

						if ( substr( $this->meta[$i], -1 ) == "*" ) {
							$metaText = substr( $this->meta[$i], 0, strlen( $this->meta[$i] ) -1 )  .
												'<span class="search_meta_hit">' .
												capitalizeWords( Filter::output( $result[$this->metaExtension] ) ) .
												'</span>';
							$metaText = html_entity_decode( $metaText );
						} else {
							$metaText = $this->meta[$i];
						}

						array_push( $resultArray,
									array( 'value' => $this->header[$i] . " " . $fieldText,
										   'link' => $this->target . Filter::output( $result[$this->key] ),
										   'meta' => $metaText
									)
						);
					}
				}
			}
		}

		if ( sizeof( $resultArray ) > 0 ) {
			return $resultArray;
		} else {
			return null;
		}
	}
}
?>

<?php
	require_once( "controls/autoload.php" );
	$PAGE_NAME = "Search";

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->paint();


	if ( !isset( $_GET['q'] ) )	{				// check if search string is passed
		redirectToHomePage();
	} elseif ( trim( $_GET['q'] ) == "" ) {		// check if search string is not empty
		redirectToHomePage();
	}


	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'search.png', true );


?>	<fieldset><legend>Search Results</legend>
		<section id="search_results">
<?php
		$searchText = strtolower( $_GET['q'] );

		$isKeyWordSearch = true;


		// predefined search based on keywords
		if ( substr( $searchText, 0, 3 ) == "si=" )	{					// sales invoice
			$fields = array(
				new Search( "`order`", "view_order_details.php?id=", "id",
						array( "sales_invoice" ),
						array( "Sales Invoice No." ),
						array( "from Order No. *" ), "id" ),
				new Search( "purchase", "view_purchase_details.php?id=", "id",
						array( "sales_invoice" ),
						array( "Sales Invoice No." ),
						array( "from Purchase No. *" ), "id" )
			);
			
			if ( strlen( $searchText ) > 3 ) {
				$searchText = substr( $searchText, 3 );
			} else {
				$searchText = "*";
			}
		} elseif ( substr( $searchText, 0, 3 ) == "dr=" ) {				// delivery receipt
			$fields = array(
				new Search( "`order`", "view_order_details.php?id=", "id",
						array( "delivery_receipt" ),
						array( "Delivery Receipt No." ),
						array( "from Order No. *" ), "id" ),
				new Search( "purchase", "view_purchase_details.php?id=", "id",
						array( "delivery_receipt" ),
						array( "Delivery Receipt No." ),
						array( "from Purchase No. *" ), "id" )
			);

			if ( strlen( $searchText ) > 3 ) {
				$searchText = substr( $searchText, 3 );
			} else {
				$searchText = "*";
			}
		} elseif ( substr( $searchText, 0, 8 ) == "receipt=" ) {				// official receipt
			$fields = array(
				new Search( "order_payment", "view_order_details.php?id=", "order_id",
						array( "receipt_number" ),
						array( "O.R. No." ),
						array( "from Order No. *" ), "order_id" ),
				new Search( "purchase_payment", "view_purchase_details.php?id=", "purchase_id",
						array( "receipt_number" ),
						array( "O.R. No." ),
						array( "from Purchase No. *" ), "purchase_id" )
			);

			if ( strlen( $searchText ) > 8 ) {
				$searchText = substr( $searchText, 8 );
			} else {
				$searchText = "*";
			}
		} elseif ( substr( $searchText, 0, 6 ) == "order=" ) {			// order
			$fields = array(
				new Search( "`order`", "view_order_details.php?id=", "id",
						array( "id" ),
						array( "Order No." ),
						array( "from Orders" ), "id" )
			);

			if ( strlen( $searchText ) > 6 ) {
				$searchText = substr( $searchText, 6 );
			} else {
				$searchText = "*";
			}
		} elseif ( substr( $searchText, 0, 9 ) == "purchase=" ) {		// purchase
			$fields = array(
				new Search( "purchase", "view_purchase_details.php?id=", "id",
						array( "id" ),
						array( "Purchase No." ),
						array( "from Purchases" ), "id" )
			);

			if ( strlen( $searchText ) > 9 ) {
				$searchText = substr( $searchText, 9 );
			} else {
				$searchText = "*";
			}
		} elseif ( substr( $searchText, 0, 4 ) == "spo=" )	{			// supplier's P.O. number
			$fields = array(
				new Search( "purchase", "view_purchase_details.php?id=", "id",
					array( "purchase_number" ),
					array( "Supplier's P.O. No." ),
					array( "from Purchase No. *" ), "id" )
			);

			if ( strlen( $searchText ) > 4 ) {
				$searchText = substr( $searchText, 4 );
			} else {
				$searchText = "*";
			}
		} elseif ( substr( $searchText, 0, 6 ) == "check=" ) {			// check number
			$fields = array(
				new Search( "order_payment", "view_order_details.php?id=", "order_id",
					array( "check_number" ),
					array( "Check No." ),
					array( "from Order No. *" ), "order_id" ),
				new Search( "purchase_payment", "view_purchase_details.php?id=", "purchase_id",
					array( "check_number" ),
					array( "Check No." ),
					array( "from Purchase No. *" ), "purchase_id" )
			);

			if ( strlen( $searchText ) > 6 ) {
				$searchText = substr( $searchText, 6 );
			} else {
				$searchText = "*";
			}
		} elseif ( substr( $searchText, 0, 4 ) == "inv=" )	{			// inventory
			$fields = array(
				new Search( "inventory_brand", "list_inventory_models.php?brandID=", "id",
				            array( "name" ),
				            array( "" ),
				            array( "from Inventory Brands" ), "name" ),
				new Search( "inventory", "list_inventory_models.php?brandID=", "brand_id",
				            array( "model" ),
				            array( "Model:" ),
				            array( "from *" ), "model_meta" )
			);

			if ( strlen( $searchText ) > 4 ) {
				$searchText = substr( $searchText, 4 );
			} else {
				$searchText = "*";
			}
		} elseif ( substr( $searchText, 0, 4 ) == "amt=" )	{			// amount
			$fields = array(
				new Search( "order_payment", "view_order_details.php?id=", "order_id",
				            array( "amount" ),
				            array( CURRENCY ),
				            array( "from Order No. *" ), "order_id" ),
				new Search( "purchase_payment", "view_purchase_details.php?id=", "purchase_id",
				            array( "amount" ),
				            array( CURRENCY ),
				            array( "from Purchase No. *" ), "purchase_id" )
			);

			if ( strlen( $searchText ) > 4 ) {
				$searchText = substr( $searchText, 4 );
			} else {
				$searchText = "*";
			}
		} else {
			$isKeyWordSearch = false;

			$fields = array(
				new Search( "`order`", "view_order_details.php?id=", "id",
						array( "id", "sales_invoice", "delivery_receipt" ),
						array( "Order No.", "Sales Invoice No.", "Delivery Receipt No." ),
						array( "from Orders", "from Order No. *", "from Order No. *" ), "id" ),
				new Search( "purchase", "view_purchase_details.php?id=", "id",
						array( "id", "sales_invoice", "delivery_receipt" ),
						array( "Purchase No.", "Sales Invoice No.", "Delivery Receipt No." ),
						array( "from Purchases", "from Purchase No. *", "from Purchase No. *" ), "id" ),
				new Search( "order_payment", "view_order_details.php?id=", "order_id",
						array( "receipt_number" ),
						array( "O.R. No." ),
						array( "from Order No. *" ), "order_id" ),
				new Search( "purchase_payment", "view_purchase_details.php?id=", "purchase_id",
						array( "receipt_number" ),
						array( "O.R. No." ),
						array( "from Purchase No. *" ), "purchase_id" ),
				new Search( "order_payment", "view_order_details.php?id=", "order_id",
						array( "check_number" ),
						array( "Check No." ),
						array( "from Order No. *" ), "order_id" ),
				new Search( "purchase_payment", "view_purchase_details.php?id=", "purchase_id",
						array( "check_number" ),
						array( "Check No." ),
						array( "from Purchase No. *" ), "purchase_id" ),
				new Search( "purchase", "view_purchase_details.php?id=", "id",
						array( "purchase_number" ),
						array( "Supplier's P.O. No." ),
						array( "from Purchase No. *" ), "id" ),
				new Search( "customer", "view_customer_details.php?id=", "id",
						array( "name", "contact_person" ),
						array( "", "" ),
						array( "from Customers", "Contact person of customer *" ), "name" ),
				new Search( "supplier", "view_supplier_details.php?id=", "id",
						array( "name", "contact_person" ),
						array( "", "" ),
						array( "from Suppliers", "Contact person of supplier *" ), "name" ),
				new Search( "agent", "view_agent_details.php?id=", "id",
						array( "name" ),
						array( "", "" ),
						array( "from Agents" ), "name" ),
				new Search( "inventory_brand", "list_inventory_models.php?brandID=", "id",
						array( "name" ),
						array( "" ),
						array( "from Inventory Brands" ), "name" ),
				new Search( "inventory", "list_inventory_models.php?brandID=", "brand_id",
						array( "model" ),
						array( "Model:" ),
						array( "from *" ), "model_meta" ),
				new Search( "order_payment", "view_order_details.php?id=", "order_id",
				        array( "amount" ),
				        array( CURRENCY ),
				        array( "from Order No. *" ), "order_id" ),
				new Search( "purchase_payment", "view_purchase_details.php?id=", "purchase_id",
			            array( "amount" ),
			            array( CURRENCY ),
			            array( "from Purchase No. *" ), "purchase_id" )
			);
		}


		//$searchText = str_replace( array( '/', '\\' ), '*', $searchText );
		if ($searchText == '\/' || $searchText == '/') {
			$searchText = '';
		} 
		if ( strlen( $searchText ) == 0 ) {
			$searchText = '*';
		}


		echo '<div id="search_hint"><b>Hint:</b><br />' .
			 'You can use keywords to do exact search.' .
			 '<ul>' .
			 '<li><span class="search_hint_keyword">si=</span><span class="search_hint_syntax">&lt;sales invoice number&gt;</span></li>' .
			 '<li><span class="search_hint_keyword">dr=</span><span class="search_hint_syntax">&lt;delivery receipt number&gt;</span></li>' .
			 '<li><span class="search_hint_keyword">order=</span><span class="search_hint_syntax">&lt;order number&gt;</span></li>' .
			 '<li><span class="search_hint_keyword">purchase=</span><span class="search_hint_syntax">&lt;purchase number&gt;</span></li>' .
			 '<li><span class="search_hint_keyword">receipt=</span><span class="search_hint_syntax">&lt;official receipt number&gt;</span></li>' .
			 '<li><span class="search_hint_keyword">check=</span><span class="search_hint_syntax">&lt;check number&gt;</span></li>' .
			 '<li><span class="search_hint_keyword">spo=</span><span class="search_hint_syntax">&lt;supplier\'s P.O. number&gt;</span></li>' .
			 '<li><span class="search_hint_keyword">inv=</span><span class="search_hint_syntax">&lt;inventory brand/model name&gt;</span></li>' .
			 '<li><span class="search_hint_keyword">amt=</span><span class="search_hint_syntax">&lt;amount&gt;</span></li>' .
			 '</ul>' .
			 '</div>';


		function performSearch( $searchText, $fields, $isKeyWordSearch = false )
		{
			$isResultFound = false;
			$isEnableNextPageLink = false;
			//$numberOfResultsPerPage = ITEMS_PER_PAGE;
			$numberOfResultsPerPage = 10;

			// get page number
			if ( isset( $_GET['pg'] ) ) {
				$page = $_GET['pg'];
			} else {
				$page = 1;
			}

			// determine paging parameters
			$offset 	= ( ( $page - 1 ) * $numberOfResultsPerPage ) + 1;
			$limit		= $offset + $numberOfResultsPerPage  - 1;
			$resultCtr  = 0;

			$length = sizeof( $fields );

			// iterate search fields
			for ( $i = 0; $i < $length; $i++ ) {
				$result = $fields[$i]->search( $searchText, $isKeyWordSearch );

				if ( $result != null ) {
					if ( $isResultFound == false ) {
						echo '<ol start="' . $offset . '">';
						$isResultFound = true;
					}

					$resultLength = sizeof( $result );

					for ( $j = 0; $j < $resultLength; $j++ ) {
						$resultCtr++;

						if ( $resultCtr > $limit ) {
							$isEnableNextPageLink = true;
							break;
						}

						if ( $resultCtr >= $offset && $resultCtr <= $limit ) {
							echo '<li><a href="' . $result[$j]['link'] . '">' . $result[$j]['value'] . '</a><br />';
							echo $result[$j]['meta'] . '<br /><br /></li>';
						}
					}
				}

				if ( $isEnableNextPageLink == true ) {
					break;
				}
			}


			if ( $isResultFound == true ) {
				echo '</ol>';

				if ( $page > 1 || $isEnableNextPageLink ) {
					echo '<div id="search_pagination">';
					echo 'Page ' . $page;
					echo '<br />';

					if ( $page == 1 ) {
						echo "&laquo; prev";
					} else {
						echo "<a href=\"search.php?q=" . rawurlencode( $_GET['q'] ) . "&pg=" . ( $page - 1 ) . "\">&laquo; prev</a>";
					}

					echo " | ";

					if ( !$isEnableNextPageLink ) {
						echo "next &raquo;";
					} else {
						echo "<a href=\"search.php?q=" . rawurlencode( $_GET['q'] ) . "&pg=" . ( $page + 1 ) . "\">next &raquo;</a>";
					}

					echo '</div>';
				}
			}


			return $isResultFound;
		}


		// first level search
		$isResultFound = performSearch( $searchText, $fields, $isKeyWordSearch );


		if ( !$isResultFound ) {
			if ( $isKeyWordSearch ) {
				echo '<div id="no_search_result_notification">No exact result found.</div>';
				echo '<div id="secondary_search_prompt"><br />Are you trying to search for any of the following?</div>';

				// second level search if using keywords and no exact result found
				$isResultFound = performSearch( $searchText, $fields, false );
				if ( !$isResultFound ) {
					?><script type="text/javascript">
					<!--
						$('#secondary_search_prompt').html( '' );
					// -->
					</script><?php
				}
			} else {
				echo '<div id="no_search_result_notification">No result found.</div>';
			}
		}
?>
		</section>
	</fieldset>
<?php
?>

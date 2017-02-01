<?php
	require_once( "controls/autoload.php" );

	$PAGE_NAME = "Migration Tool";

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( null, true );

	Diagnostics::toggleAbortProcessing( false );
	

	// construct INSERT query
	function insertToDatabase( Database &$database, $table, array $fields )
	{
		$sqlQuery   = "INSERT INTO `" . $table . "` VALUES (";
		$length     = sizeof( $fields );
		$keys       = array_keys( $fields );
		$rowIsEmpty = true;
		$testMode   = false;
		
		for ( $i = 0; $i < $length; $i++ ) {
			if ( $i > 0 ) {
				$sqlQuery = $sqlQuery . ",";
			}

			$trimmed = trim( $fields[$keys[$i]] );
			
			if ( $trimmed === '' || $trimmed === null ) {
				$sqlQuery = $sqlQuery . "NULL";
			} else {
				$sqlQuery = $sqlQuery . "'" . Filter::input( $fields[$keys[$i]] ) . "'";
				$rowIsEmpty = false;
			}
		}
		
		$sqlQuery = $sqlQuery . ")";

		if ( !$rowIsEmpty ) {
			if ( !$testMode ) {
				$isSuccess = $database->query( $sqlQuery );
				if ( $isSuccess ) {
					echo '<span class="good">' . $sqlQuery . '</span><br /><br />';
				} else {
					echo '<span class="bad">' . $sqlQuery . '</span><br /><br />';
					abort( $database, $table );
				}
			} else {
				echo '<span class="good">' . $sqlQuery . '</span><br /><br />';
			}
		} else {
			echo '<br />';
		}
	}

	

	// insert order/purchase item to table
	function insertItem( Database $database, $table, $orderID, array $row, $orderStatus )
	{
		$fields['id'] 				= null;
		if ( $table == 'order_item' ) {
			$fields['order_id']		= $orderID;
		} else {
			$fields['purchase_id'] 	= $orderID;
		}

		$resultSet = $database->query(
			"SELECT id FROM inventory WHERE model = '" . Filter::input( $row[17] ) . "'"
		);

		// get inventory id
		if ( $database->getResultCount( $resultSet ) == 0 )	{
			echo '<span class="bad">Error: Cannot find inventory model "' . $row[17] . '"</span><br /><br />';
			return;
		}
		$inventory = $database->getResultRow( $resultSet );
		$fields['inventory_id'] = $inventory['id'];

		$fields['price'] = $row[18];
		if ( empty( $fields['price'] ) ) {
			$fields['price'] = 0.000;
		}
		if ( $fields['price'] < 0 ) {			// check if price is negative
			echo '<span class="bad">Error: Invalid selling price "' . $fields['price'] . '"</span><br /><br />';
			abort( $database, $table );
		}
		$fields['price'] = numberFormat( $fields['price'], 'float', '3', '.', '', true );

		$fields['sidr_price'] = $row[21];
		if ( empty( $fields['sidr_price'] ) ) {
			$fields['sidr_price'] = $fields['price'];
		}
		if ( $fields['sidr_price'] < 0 ) {			// check if SI/DR price is negative
			echo '<span class="bad">Error: Invalid SI/DR price "' . $fields['sidr_price'] . '"</span><br /><br />';
			abort( $database, $table );
		}
		$fields['sidr_price'] = numberFormat( $fields['sidr_price'], 'float', '3', '.', '', true );

		$fields['net_price'] = $row[23];
		if ( empty( $fields['net_price'] ) ) {
			$fields['net_price'] = $fields['sidr_price'];
		}
		if ( $fields['net_price'] < 0 ) {			// check if net price is negative
			echo '<span class="bad">Error: Invalid net price "' . $fields['net_price'] . '"</span><br /><br />';
			abort( $database, $table );
		}
		$fields['net_price'] = numberFormat( $fields['net_price'], 'float', '3', '.', '', true );

		$fields['quantity'] = $row[19];
		if ( empty( $fields['quantity'] ) ) {
			$fields['quantity'] = '0';
		}
		if ( $fields['quantity'] < 0 ) {			// check if quantity is negative
			echo '<span class="bad">Error: Invalid quantity "' . $fields['quantity'] . '"</span><br /><br />';
			abort( $database, $table );
		}

		if ( empty( $row[20] ) ) {
			$row[20] = 0;
		}
		$fields['undelivered_quantity'] = $fields['quantity'] - $row[20];
		if ( $fields['undelivered_quantity'] < 0 ) {			// check if undelivered quantity is negative
			echo '<span class="bad">Error: Invalid delivered quantity "' . $row[20] .
				 '" (Delivered quantity: ' . $fields['quantity'] . ')</span><br /><br />';
			return;
		}

		insertToDatabase( $database, $table, $fields );

		// update reserved stock
		if ( $table == "order_item" && $orderStatus != "cleared" && $orderStatus != "canceled" && $fields['undelivered_quantity'] > 0 ) {
			$sqlQuery = "UPDATE inventory SET reserved_stock = reserved_stock + " . $fields['undelivered_quantity'] . " WHERE id = " . $fields['inventory_id'];
			$database->query( $sqlQuery );
			echo $sqlQuery . "<br /><br />";
		}
	}



	// insert payment to table
	function insertPayment( Database $database, $table, $orderID, $paymentSequence, array $row )
	{
		if ( $table == 'order_payment' ) {
			$fields['order_id']		= $orderID;
		} else {
			$fields['purchase_id'] 	= $orderID;
		}

		$fields['payment_sequence'] 	= $paymentSequence;
		$fields['payment_schedule_id']  = null;

		$fields['amount'] 				= $row[25];
		if ( empty( $fields['amount'] ) ) {
			$fields['amount'] = 0.000;
		}
		if ( $fields['amount'] < 0 ) {			// check if negative amount
			echo '<span class="bad">Error: Invalid amount "' . $fields['amount'] . '"</span><br /><br />';
			abort( $database, $table );
		}
		$fields['amount'] 				= numberFormat( $fields['amount'], 'float', '3', '.', '', true );

		if ( empty( $row[26] ) ) {
			echo '<span class="bad">Error: No payment date specified</span><br /><br />';
			return;
		}
		$fields['payment_date'] 		= dateFormatInput( $row[26], 'Y-m-d', 'm-d-y' );
		$fields['receipt_number'] 		= $row[27];

		$fields['payment_type'] 		= $row[28];
		if ( $fields['payment_type'] != 'cash' && $fields['payment_type'] != 'check' ) {		// invalid payment type
			if ( $row[29] != null || $row[30] != null || $row[31] != null || $row[30] != null ) {
				$fields['payment_type'] = 'check';
			} else {
				$fields['payment_type'] = 'cash';
			}
		}

		if ( $fields['payment_type'] == 'check' ) {
			$fields['bank_name'] 		= $row[29];
			$fields['bank_branch'] 		= $row[30];
			$fields['check_number'] 	= $row[31];
			if ( !empty( $row[32] ) ) {
				$fields['check_date']	= dateFormatInput( $row[32], 'Y-m-d', 'm-d-y' );
			} else {
				$fields['check_date']	= $fields['payment_date'];
			}
		} else {
			$fields['bank_name'] 			= null;
			$fields['bank_branch'] 			= null;
			$fields['check_number'] 		= null;
			$fields['check_date']			= null;
		}

		if ( !empty( $row[33] ) ) {
			$fields['clearing_target_date'] = dateFormatInput( $row[33], 'Y-m-d', 'm-d-y' );
		} else {
			$fields['clearing_target_date'] = $fields['payment_date'];
		}

		if ( $row[34] == 'cleared' ) {
			$fields['clearing_actual_date'] = $fields['clearing_target_date'];
		} else {
			$fields['clearing_actual_date'] = null;
		}

		insertToDatabase( $database, $table, $fields );

		// update balance
		if ( $table == "order_payment" ) {
			$sqlQuery = "UPDATE `order` SET balance = balance - " . $fields['amount'] . " WHERE id = " . $fields['order_id'];
		} else {
			$sqlQuery = "UPDATE `purchase` SET balance = balance - " . $fields['amount'] . " WHERE id = " . $fields['purchase_id'];
		}
		$database->query( $sqlQuery );
		echo '<span class="good">' . $sqlQuery . "</span><br /><br />";
	}
	
	

	// abort the operation
	function abort( &$database = null, $table = null )
	{
		if ( $database != null && $table != null ) {
			// truncate existing tables
			if ( $table == 'customer' ) {
				$database->query( "DELETE FROM `customer`" );
				$database->query( "ALTER TABLE `customer` AUTO_INCREMENT=1" );
			} elseif ( $table == 'supplier' ) {
				$database->query( "DELETE FROM `supplier`" );
				$database->query( "ALTER TABLE `supplier` AUTO_INCREMENT=1" );
			} elseif ( $table == 'agent' ) {
				$database->query( "DELETE FROM `agent`" );
				$database->query( "ALTER TABLE `agent` AUTO_INCREMENT=1" );
			} elseif ( $table == 'inventory_brand' ) {
				$database->query( "DELETE FROM `inventory_brand`" );
				$database->query( "ALTER TABLE `inventory_brand` AUTO_INCREMENT=1" );
			} elseif ( $table == 'inventory' ) {
				$database->query( "DELETE FROM `inventory`" );
				$database->query( "ALTER TABLE `inventory` AUTO_INCREMENT=1" );
			} elseif ( $table == 'order' || $table == 'order_item' || $table == 'order_payment' )	{
				$database->query( "DELETE FROM `order_payment`" );
				$database->query( "DELETE FROM `order_payment_schedule`" );
				$database->query( "ALTER TABLE `order_payment_schedule` AUTO_INCREMENT=1" );
				$database->query( "DELETE FROM `order_item`" );
				$database->query( "ALTER TABLE `order_item` AUTO_INCREMENT=1" );
				$database->query( "DELETE FROM `order`" );
				$database->query( "ALTER TABLE `order` AUTO_INCREMENT=1" );
			} elseif ( $table == 'purchase' || $table == 'purchase_item' || $table == 'purchase_payment' ) {
				$database->query( "DELETE FROM `purchase_payment`" );
				$database->query( "DELETE FROM `purchase_payment_schedule`" );
				$database->query( "DELETE FROM `purchase_payment_schedule`" );
				$database->query( "DELETE FROM `purchase_item`" );
				$database->query( "ALTER TABLE `purchase_item` AUTO_INCREMENT=1" );
				$database->query( "DELETE FROM `purchase`" );
				$database->query( "ALTER TABLE `purchase` AUTO_INCREMENT=1" );
			} else {
				echo $table;
			}
		}
		
		echo '<section><div><a href="migration.php">Upload another file</a></div></section>';
		
		die();
	}



	function checkOrderConsistency( Database $database, $table, $orderID )
	{
		$resultSet = $database->query(
			"SELECT sales_invoice, delivery_receipt, receipt_amount, balance, canceled_date, cleared_date FROM `" . $table . "` WHERE id=" . $orderID
		);
		$tableDesc = ucfirst( $table );
		$orderInfo = $database->getResultRow( $resultSet );
		if ( $orderInfo['sales_invoice'] != null ) {
			$invoiceNo = ' (SI ' . $orderInfo['sales_invoice'] . ')';
		} else {
			$invoiceNo = ' (DR ' . $orderInfo['delivery_receipt'] . ')';
		}

		if ( $orderInfo['canceled_date'] != null ) {
			echo '<span style="color:orange">Warning: ' . $tableDesc . ' No. ' . $orderID . $invoiceNo . ' is canceled</span><br /><br />';
		} elseif ( $orderInfo['balance'] < 0 ) {
			echo '<span style="color:orange">Warning: ' . $tableDesc . ' No. ' . $orderID . $invoiceNo .
				 ' has excess payment of ' . $orderInfo['balance'] . ' (for rebate)</span><br /><br />';
		} elseif ( $orderInfo['balance'] > 0  && $orderInfo['cleared_date'] != null ) {
			echo '<span style="color:orange">Warning: ' . $tableDesc . ' No. ' . $orderID . $invoiceNo .
				' still has unpaid amount of ' . $orderInfo['balance'] . '</span><br /><br />';
		} else {
			echo '<span class="good">Balance of ' . $tableDesc . ' No. ' . $orderID . $invoiceNo . ' is ' .
				 $orderInfo['balance'] . '</span><br /><br />';
		}

		$resultSet = $database->query(
			"SELECT SUM(net_price*quantity) AS item_amount FROM `" . $table . "_item` WHERE " . $table . "_id=" . $orderID . " GROUP BY " . $table . "_id"
		);
		$itemInfo = $database->getResultRow( $resultSet );
		if ( $itemInfo['item_amount'] != $orderInfo['receipt_amount'] && $orderInfo['receipt_amount'] > 0.000 ) {
			echo '<span class="bad">Error: Receipt amount of ' . $tableDesc . ' No. ' . $orderID . $invoiceNo . ' is inconsistent (Item Amount: ' .
				$itemInfo['item_amount'] . ' != Receipt Amount: ' . $orderInfo['receipt_amount'] . ')</span><br /><br />';
			$database->query( "DELETE FROM `" . $table . "_payment` WHERE " . $table . "_id=" . $orderID );
			$database->query( "DELETE FROM `" . $table . "_payment_schedule` WHERE " . $table . "_id=" . $orderID );
			$database->query( "DELETE FROM `" . $table . "_item` WHERE " . $table . "_id=" . $orderID );
			$database->query( "DELETE FROM `" . $table . "` WHERE id=" . $orderID );
			$database->query( "ALTER TABLE `" . $table . "` AUTO_INCREMENT=" . $orderID );
			echo $tableDesc . ' No. ' . $orderID . $invoiceNo . ' deleted due to inconsistencies<br /><br />';
		} else {
			echo '<span class="good">Receipt amount of ' . $tableDesc . ' No. ' . $orderID . $invoiceNo . ' is consistent (Item Amount: ' .
				$itemInfo['item_amount'] . ' == Receipt Amount: ' . $orderInfo['receipt_amount'] . ')</span><br /><br />';
		}
	}


	// to skip uploading repeatedly
	if ( false ) {
		if ( file_exists( 'upload/order2.xlsx' ) ) {
			$sheetFilename = "order2.xlsx";
			goto process_excel;
		}
	}

	if ( !isset( $_POST['submit_form'] ) ) {
?>
	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
    	<fieldset><legend>File Upload</legend>
            <section>
                <div>
                    <label for="file">Excel migration file:</label>
                    <input type="file" name="file" required="required" size="100" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" />
                </div>
            </section>
            <div id="form_buttons">
            	<input type="submit" name="submit_form" id="submit_form" value="Submit" />
				<input type="reset" name="reset_form" id="reset_form" value="Reset" />
				<input type="button" name="cancel_form" id="cancel_form" value="Cancel" onclick="javascript:history.back()" />
            </div>
		</fieldset>
    </form>
<?php
	} else {
		echo '<fieldset><legend>Parsing File</legend>';


		// check if there's error in uploading file
		if ( $_FILES["file"]["error"] > 0 ) {
			echo 'Return Code: ' . $_FILES['file']['error'] . '<br />';
			echo 'Error: Problem in uploading file';
			abort();
		}


		// check if valid Excel (.xlsx) file
		if ( $_FILES['file']['type'] != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ) {
			echo 'Error: Invalid Excel file entered';
			abort();
		}


		// display basic file info
		echo '<section>';
		echo '<b>Uploaded File:</b><br />';
		echo 'Filename : ' . $_FILES['file']['name'] . '<br />';
		echo 'Type : ' . $_FILES['file']['type'] . '<br />';
		echo 'Size : ' . numberFormat( ( $_FILES['file']['size'] / 1024 ), 'float' ) . ' Kb<br />';
		echo 'Temp file: ' . $_FILES['file']['tmp_name'] . '<br />';


		// check if file already exists
		// delete if existing
		if ( file_exists( 'upload/' . $_FILES['file']['name'] ) ) {
			unlink( 'upload/' . $_FILES['file']['name'] );
			echo '<br />Deleted existing file ' . $_FILES['file']['name'] . ' in upload directory<br />';
		}


		// move uploaded file from tmp directory to upload directory
		move_uploaded_file( $_FILES["file"]['tmp_name'], 'upload/' . $_FILES['file']['name'] );
		echo 'Stored in: ' . 'upload/' . $_FILES['file']['name'];
		echo '</section>';

		$sheetFilename = $_FILES['file']['name'];

		process_excel:
		echo '<section>';
		echo '<b>Reading File...</b><br />';


		// create database object
		$database = new Database();


		// import PHPExcel library
		require_once( 'libraries/phpexcel/PHPExcel.php' );

		// create new PHPExcel object
		$objPHPExcel = PHPExcel_IOFactory::load( 'upload/' . $sheetFilename );


		// read sheet
		$objPHPExcel->setActiveSheetIndex( 0 );
		$activeSheet = $objPHPExcel->getActiveSheet();
		$table = $activeSheet->getTitle();
		echo 'Sheet Name : ' . $table . '<br />';

		// determine boundaries of excel
		$maxColumn = $activeSheet->getHighestColumn();
		$maxRow    = $activeSheet->getHighestRow();

		echo 'Max Column : ' . $maxColumn . '<br />';
		echo 'Max Row : ' . $maxRow . '<br /><br />';

		// to get cell value
		// $activeSheet->getCell( 'B2' )->getValue();
		// $activeSheet->getCell( 'B2' )->getCalculatedValue();

		// get sheet contents
		$activeSheetContents = $activeSheet->toArray();


		// disable timeout of processing
		set_time_limit ( 0 );


		// control settings
		$rowPtr    = 1;			// row pointer, default to 1
		$rowLimit  = null;		// set limit for number of lines to process, set to null if no limit
		$rowOffset = 0;			// offset in the sheet is divided into multiple sheets, set to zero if no offset
		$skipRow   = null;		// skip certain rows, set to null if no rows to be skipped


		$fields = array();

		if ( $table == 'order' || $table == 'purchase' ) {
			$orderID = null;
			$orderStatus = null;
		}


		foreach ( $activeSheetContents as $row ) {
			if ( $skipRow != null ) {		// skip rows
				if ( $rowPtr < $skipRow ) {
					$rowPtr++;
					continue;
				}
			}

			if ( $rowLimit != null ) {		// stop processing if limit is reached
				if ( $rowPtr > $rowLimit ) break;
			}

			// display line number
			if ( $rowPtr != 1 ) {
				echo '</div>';
			}
			if ( $rowOffset != 0 ) {
				echo '<b>Row ' . ( $rowPtr + $rowOffset ) . ' (' . $rowPtr . ')</b><br /><div style="margin-left: 30px">';
			} else {
				echo '<b>Row ' . $rowPtr . '</b><br /><div style="margin-left: 30px">';
			}

			switch ( $table ) {
				case 'agent': {
					// skip heading
					if ( $rowPtr == 1 ) {
						echo '<br />';
						break;
					}

					// get fields
					$fields['id'] 			  = null;
					$fields['name'] 		  = $row[1];
					if ( empty( $fields['name'] ) ) {
						echo '<br />';
						break;
					}
					$fields['address'] 		  = $row[2];
					$fields['telephone'] 	  = $row[3];
					$fields['mobile']		  = $row[4];
					$fields['fax'] 			  = $row[5];
					$fields['email'] 		  = $row[6];
					$fields['branch']   	  = $row[7];
					$fields['department']     = $row[8];
					$fields['position']   	  = $row[9];

					insertToDatabase( $database, $table, $fields );

					break;
				}

				case 'customer': {
					// skip heading
					if ( $rowPtr == 1 ) {
						echo '<br />';
						break;
					}

					// get fields
					$fields['id'] 			  = null;
					$fields['name'] 		  = $row[1];
					if ( empty( $fields['name'] ) ) {
						echo '<br />';
						break;
					}
					$fields['contact_person'] = $row[2];
					$fields['address'] 		  = $row[3];
					$fields['telephone'] 	  = $row[4];
					$fields['mobile']		  = $row[5];
					$fields['fax'] 			  = $row[6];
					$fields['email'] 		  = $row[7];
					$fields['credit_limit']   = $row[8];
					$fields['credit_terms']   = $row[9];

					insertToDatabase( $database, $table, $fields );

					break;
				}

				case 'supplier': {
					// skip heading
					if ( $rowPtr == 1 ) {
						echo '<br />';
						break;
					}

					// get fields
					$fields['id'] 			  = null;
					$fields['name'] 		  = $row[1];
					if ( empty( $fields['name'] ) ) {
						echo '<br />';
						break;
					}
					$fields['contact_person'] = $row[2];
					$fields['address'] 		  = $row[3];
					$fields['telephone'] 	  = $row[4];
					$fields['mobile']		  = $row[5];
					$fields['fax'] 			  = $row[6];
					$fields['email'] 		  = $row[7];

					insertToDatabase( $database, $table, $fields );

					break;
				}

				case 'inventory_brand': {
					// skip heading
					if ( $rowPtr == 1 ) {
						echo '<br />';
						break;
					}

					// get fields
					$fields['id'] 			  = null;
					$fields['name'] 		  = $row[0] . ' - ' . $row[1];

					// skip row with empty name
					if ( empty( $row[1] ) ) {
						echo '<br />';
					} else {
						insertToDatabase( $database, $table, $fields );
					}

					break;
				}

				case 'inventory': {
					// skip heading
					if ( $rowPtr == 1 ) {
						echo '<br />';
						break;
					}

					// determine brand id
					$brandName = $row[0] . ' - ' . $row[1];
					if ( empty( $row[0] ) || empty( $row[1] ) ) {
						echo '<span class="bad">Error: Invalid brand name "' . $brandName . '"</span><br /><br />';
						abort( $database, $table );
					}
					if ( $brandName == 'OTHERS - Others' ) {
						$brandName = "OTHERS";
					}

					$resultSet = $database->query(
						"SELECT id FROM inventory_brand WHERE name='" . $brandName . "'"
					);
					if ( $database->getResultCount( $resultSet ) == 0 ) {
						echo '<span class="bad">Error: Cannot find inventory brand "' . $brandName . '"</span><br />';
						abort( $database, $table );
					}
					$brandID = $database->getResultRow( $resultSet );

					// get fields
					$fields['id'] 			  	= null;
					$fields['brand_id']		  	= $brandID['id'];
					$fields['model'] 			= ucwords( $row[2] );
					if ( empty( $fields['model'] ) ) {
						echo '<span class="bad">Error: Model name is not specified</span><br /><br />';
						break;
					}

					// check if model name is existing for brand id
					$resultSet = $database->query(
						"SELECT id FROM inventory WHERE brand_id=" . $fields['brand_id'] . " AND model='" . Filter::input( $fields['model'] ) . "'"
					);
					if ( $database->getResultCount( $resultSet ) != 0 ) {
						echo '<span class="bad">Error: Inventory model "' . $fields['model'] .
							 '" for ' . $brandName . ' already exist</span><br />';
						abort( $database, $table );
					}

					$fields['description'] 		= $row[3];

					if ( $row[0] != 'SERVICES' ) {
						$fields['purchase_price'] 	= $row[5];		// skipped purchaser
						if ( empty( $fields['purchase_price'] ) ) {
							$fields['purchase_price']  = '0.000';
						}
						$fields['selling_price']	= $row[6];
						if ( empty( $fields['selling_price'] ) ) {
							$fields['selling_price']  = '0.000';
						}
						$fields['stock_count'] 		= $row[7];
						if ( empty( $fields['stock_count'] ) ) {
							$fields['stock_count']  = 0;
						}
						$fields['reserved_stock'] 	= 0;
					} else {
						$fields['purchase_price'] = null;
						$fields['selling_price']	= $row[6];
						if ( empty( $fields['selling_price'] ) ) {
							$fields['selling_price']  = '0.000';
						}
						$fields['stock_count'] 	  = null;
						$fields['reserved_stock'] = null;
					}

					$fields['parent_id'] 		= null;

					insertToDatabase( $database, $table, $fields );

					break;

				}

				case 'order':
				case 'purchase': {
					// skip heading
					if ( $table == 'order' ) {
						if ( $rowPtr <= 4 ) {
							echo '<br />';
							break;
						}
					} else {
						if ( $rowPtr <= 2 ) {
							echo '<br />';
							break;
						}
					}

					//abort( $database, $table );

					// get fields
					$fields['id'] 			  		= null;
					$fields['sales_invoice']		= $row[1];
					$fields['delivery_receipt'] 	= $row[2];

					$row[0] = trim( $row[0] );
					if ( $row[0] == 'canceled' || $row[0] == 'cancelled' ||			// order_id
						 $row[15] == 'canceled' || $row[15] == 'cancelled' ) {		// remarks
						$row[16] = 'canceled';
					}

					if ( !empty( $fields['sales_invoice'] ) || !empty( $fields['delivery_receipt'] ) ) {
						// order info

						// new order, check if balance in previous order is correct
						if ( $orderID != null ) {
							checkOrderConsistency( $database, $table, $orderID );
						}

						// check if sales invoice or delivery receipt is already existing
						if ( !empty( $fields['sales_invoice'] ) ) {
							$resultSet = $database->query(
								"SELECT id FROM `" . $table . "` WHERE sales_invoice='" . $fields['sales_invoice'] . "'"
							);
							if ( $database->getResultCount( $resultSet ) > 0 ) {
								echo '<span class="bad">Error: sales invoice "' . $fields['sales_invoice'] . '" already exist</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							}
						} else {
							$resultSet = $database->query(
								"SELECT id FROM `" . $table . "` WHERE delivery_receipt='" . $fields['delivery_receipt'] . "'"
							);
							if ( $database->getResultCount( $resultSet ) > 0 ) {
								echo '<span class="bad">Error: delivery receipt "' . $fields['delivery_receipt'] . '" already exist</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							}
						}

						if ( $table == 'purchase' ) {
							$fields['purchase_number'] = null;
						}

						// determine customer/supplier id
						if ( $table == 'order' ) {
							$customerName = $row[3];
							if ( empty( $customerName ) ) {
								echo '<span class="bad">Error: No customer specified</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							}
							$resultSet = $database->query(
								"SELECT id FROM customer WHERE name='" . Filter::input( $customerName ) . "'"
							);
							if ( $database->getResultCount( $resultSet ) == 0 ) {
								echo '<span class="bad">Error: Cannot find customer "' . $customerName . '"</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							}
							$customerID = $database->getResultRow( $resultSet );
							$fields['customer_id']		= $customerID['id'];
						} else {
							$supplierName = $row[3];
							if ( empty( $supplierName ) ) {
								echo '<span class="bad">Error: No supplier specified</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							}
							$resultSet = $database->query(
								"SELECT id FROM supplier WHERE name='" . Filter::input( $supplierName ) . "'"
							);
							if ( $database->getResultCount( $resultSet ) == 0 ) {
								echo '<span class="bad">Error: Cannot find supplier "' . $supplierName . '"</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							}
							$supplierID = $database->getResultRow( $resultSet );
							$fields['supplier_id']		= $supplierID['id'];
						}

						if ( empty( $row[4] ) ) {
							if ( empty( $row[6] ) ) {
								echo '<span class="bad">Error: No order date and delivery date specified</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							} else {
								$row[4] = $row[6];
							}
						}
						$fields[$table.'_date'] 				   = dateFormatInput( $row[4], 'Y-m-d', 'm-d-y' );
						$fields['business_unit'] 			   = null;
						$fields['transaction_type'] 		   = $row[5];
						if ( $fields['transaction_type'] != 'pick-up' && $fields['transaction_type'] != 'delivery' ) {
							$fields['transaction_type']		   = 'pick-up';
						}
						if ( empty( $row[6] ) ) {
							echo '<span class="bad">Error: No delivery date specified</span><br /><br />';
							$orderID = null;
							$orderStatus = null;
							break;
						}
						$fields['delivery_pickup_target_date'] = dateFormatInput( $row[6], 'Y-m-d', 'm-d-y' );
						$fields['delivery_pickup_actual_date'] = $fields['delivery_pickup_target_date'];
						$fields['payment_term'] 			   = $row[7];
						if ( $fields['payment_term'] != 'full' && $fields['payment_term'] != 'installment' ) {
							$fields['payment_term']			   = 'full';
						}
						$fields[$table.'_discount'] 			   = $row[12];
						if ( empty( $fields[$table.'_discount'] ) ) {
							$fields[$table.'_discount'] 		   = '0.000';
						}
						$fields['total_sales_amount'] 		   = $row[8];
						if ( empty( $fields['total_sales_amount'] ) ) {
							if ( !empty( $fields['delivery_receipt'] ) ) {
								$fields['total_sales_amount']  = $row[13];
							} else {
								$fields['total_sales_amount']  = '0.000';
							}
						}
						$fields['value_added_tax'] 			   = $row[9];
						if ( empty( $fields['value_added_tax'] ) ) {
							$fields['value_added_tax'] 		   = '0.000';
						}
						$fields['withholding_tax'] 			   = $row[10];
						if ( empty( $fields['withholding_tax'] ) ) {
							$fields['withholding_tax'] 		   = '0.000';
						}
						$fields['interest'] 				   = $row[11];
						if ( empty( $fields['interest'] ) ) {
							$fields['interest'] 			   = '0.000';
						}
						$fields['receipt_amount'] = numberFormat( ( $fields['total_sales_amount'] +
													floatval( $fields['value_added_tax'] ) -
													floatval( $fields['withholding_tax'] ) +
													floatval( $fields['interest'] ) -
													floatval( $fields[$table.'_discount'] ) ), 'float', 3, '.', '', true );

						if ( $table == 'order' ) {
							if ( $fields['receipt_amount'] != $row[13] ) {
								echo '<span class="bad">Error: Computed total amount receivable (' . $fields['receipt_amount'] .
									 ') is not equal to total amount receivable in excel (' . $row[13] .
									 ')</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							}

							$fields['amount_receivable'] 		   = $fields['receipt_amount'];
						} else {
							if ( $fields['receipt_amount'] != $row[13] ) {
								echo '<span class="bad">Error: Computed total amount payable (' . $fields['receipt_amount'] .
									') is not equal to total amount payable in excel (' . $row[13] .
									')</span><br /><br />';
								$orderID = null;
								$orderStatus = null;
								break;
							}

							$fields['amount_payable'] 		   = $fields['receipt_amount'];
						}

						$fields['balance'] 					   = $fields['receipt_amount'];
						$fields['waived_balance'] 			   = '0.000';
						$fields['agent_id'] 				   = null;
						$fields['remarks'] 					   = $row[15];

						$orderStatus = $row[16];
						if ( $orderStatus == 'canceled' ) {
							$fields['canceled_date'] 		   = $fields['order_date'];
						} else {
							$fields['canceled_date'] 		   = null;
						}
						if ( $orderStatus == 'cleared' ) {
							$fields['cleared_date'] 		   = $fields['delivery_pickup_actual_date'];
						} else {
							$fields['cleared_date']			   = null;
						}

						insertToDatabase( $database, $table, $fields );
						$orderID = $database->getLastInsertID();

						if ( !empty( $row[17] ) ) {
							insertItem( $database, $table.'_item', $orderID, $row, $orderStatus );
						}

						$paymentSequence = 1;
						if ( !empty( $row[25] ) ) {
							insertPayment( $database, $table.'_payment', $orderID, $paymentSequence, $row );
						}
					} else {
						if ( $orderID != null ) {
							if ( !empty( $row[17] ) ) {
								insertItem( $database, $table.'_item', $orderID, $row, $orderStatus );
							}

							$paymentSequence++;
							if ( !empty( $row[25] ) ) {
								insertPayment( $database, $table.'_payment', $orderID, $paymentSequence, $row );
							}
						} else {
							echo '<br /><br />';
						}
					}

					break;
				}
			}

			$rowPtr++;
		}

		if ( $table == 'order' || $table == 'purchase' ) {
			if ( $orderID != null ) {
				checkOrderConsistency( $database, $table, $orderID );
			}
		}

		// display link to upload new file
		echo '</section><section><div><a href="migration.php">Upload another file</a></div></section></fieldset>';
	}
?>

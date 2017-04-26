<?php
// note: this class requires scripts/order.js



// class definition for business transactions
abstract class Transaction extends Layout
{
	// abstract methods
	//abstract public static function showInputForm();
	//abstract protected static function showInputFieldset();
	//abstract public function save( $customerID );
	//abstract public function view();
	//abstract public static function showListTasks();
	//abstract public static function showList();


	protected $payment;							// payment object
	
	
	// attributes
	protected $salesInvoice;
	protected $deliveryReceipt;
	protected $transactionDate;
	protected $businessUnit;
	protected $branch;
	protected $transactionType;
	protected $deliveryPickupTargetDate;
	protected $deliveryPickupActualDate;
	protected $paymentTerm;
	protected $transactionDiscount;
	protected $totalSales;
	protected $valueAddedTax;
	protected $withholdingTax;
	protected $interest;
	protected $receiptAmount;
	protected $amountReceivable;
	protected $agentID;
	protected $remarks;



	// display item list row
	protected static function showItemListRow( $id, $rowNumber, $visibleItems, array $inventoryBrandID, array $inventoryBrandName, $itemCount, array $itemValue = null, $disablePrice = true )
	{		
?>		<tr id="item_row_<?php echo $rowNumber ?>"<?php if ( $rowNumber > $visibleItems ) echo " style=\"display:none\"" ?>>
			<td><span class="table_row_counter"><?php echo $rowNumber ?>.</span></td>
			<td>
				<select name="item_brand_<?php echo $rowNumber ?>" id="item_brand_<?php echo $rowNumber ?>" class="item_brand">
					<option value="0">-- select brand --</option>
<?php
					// load inventory brands
					for ( $i = 0; $i < sizeof( $inventoryBrandID ); $i++ )
					{
						echo '<option value="' . $inventoryBrandID[$i] . '"';

						if ( $id != null && $rowNumber <= $itemCount )
						{
							if ( $itemValue['brand_id'] == $inventoryBrandID[$i] )
								echo ' selected="selected"';

							//$selectedBrandID = $itemValue[$j]['brand_id'];
						}

						echo '>' . $inventoryBrandName[$i] . '</option>';
					}
								
?>				</select>
			</td>
			<td>
				<select name="item_model_<?php echo $rowNumber ?>" id="item_model_<?php echo $rowNumber ?>" class="item_model" disabled="disabled">
<?php
				// load inventory models if existing order
				if ( $id != null && $rowNumber <= $itemCount )
				{
					$resultSet = self::$database->query( "SELECT id, model FROM inventory WHERE brand_id = '" . $itemValue['brand_id'] . "' ORDER BY model ASC"  );
					if ( self::$database->getResultCount( $resultSet ) > 0 )
					{
						echo '<option value="0">-- select model --</option>';

						while( $model = self::$database->getResultRow( $resultSet ) )
						{
							echo '<option value="' . $model['id'] . '"';

							if ( $itemValue['inventory_id'] == $model['id'] )
								echo ' selected="selected"';

							echo '>' . capitalizeWords( Filter::output( $model['model'] ) ) . '</option>';
						}
					}
					else
						echo '<option value="null" selected="selected">-- no models available --</option>';
				}
				else
					echo '<option value="null" selected="selected">-- select brand first --</option>';
							
?>				</select>
<?php			echo '<img src="images/search.png" id="item_model_search_' . $rowNumber . '" class="search_icon_inactive" title="Search Model" onclick="showAutoSuggestModelDialog(' . $rowNumber . ')" />';
?>
			</td>
			<td>
               	<input type="text" name="item_price_<?php echo $rowNumber ?>" id="item_price_<?php echo $rowNumber ?>" class="number item_price<?php echo ( $disablePrice == true ? " output_field" : "" ) ?>" value="<?php echo ( $id != null && $rowNumber <= $itemCount ) ? numberFormat( Filter::reinput( $itemValue['price'] ), 'float', 3, '.', '', true ) : "0.000" ?>" disabled="disabled" />
				<input type="hidden" name="item_price_orig_<?php echo $rowNumber ?>" id="item_price_orig_<?php echo $rowNumber ?>" value="<?php echo ( $id != null && $rowNumber <= $itemCount ) ? numberFormat( $itemValue['price'], 'float', 3, '.', '', true ) : "0.000" ?>" />
			</td>
            <td>
				<input type="text" name="item_quantity_<?php echo $rowNumber ?>" id="item_quantity_<?php echo $rowNumber ?>" class="number item_quantity" value="<?php echo ( $id != null && $rowNumber <= $itemCount ) ? Filter::reinput( $itemValue['quantity'] ) : "0" ?>" disabled="disabled" />
				<input type="hidden" name="item_max_quantity_<?php echo $rowNumber ?>" id="item_max_quantity_<?php echo $rowNumber ?>" value="0" />
			</td>
			<td>
				<input type="text" name="item_sidr_price_<?php echo $rowNumber ?>" id="item_sidr_price_<?php echo $rowNumber ?>" class="number item_sidr_price" value="<?php echo ( $id != null && $rowNumber <= $itemCount ) ? numberFormat( Filter::reinput( $itemValue['sidr_price'] ), 'float', 3, '.', '', true ) : "0.000" ?>" disabled="disabled" />
			</td>
            <td>
               	<input type="text" name="item_sidr_subtotal_<?php echo $rowNumber ?>" id="item_sidr_subtotal_<?php echo $rowNumber ?>" class="number item_total output_field" value="0.000" disabled="disabled" />
			</td>
			<td>
				<input type="text" name="item_net_price_<?php echo $rowNumber ?>" id="item_net_price_<?php echo $rowNumber ?>" class="number item_net_price" value="<?php echo ( $id != null && $rowNumber <= $itemCount ) ? numberFormat( Filter::reinput( $itemValue['net_price'] ), 'float', 3, '.', '', true ) : "0.000" ?>" 
					   disabled="disabled" />
			</td>
            <td>
               	<input type="text" name="item_net_subtotal_<?php echo $rowNumber ?>" id="item_net_subtotal_<?php echo $rowNumber ?>" class="number item_total output_field" value="0.000" disabled="disabled" />
			</td>
		</tr>
<?php
	}


	// display remarks field set
	protected static function showRemarksInputFieldSet( $agentID = null, $remarks = null )
	{
		$isAgentEnabled = Registry::get( 'transaction.agent.enabled' );
		if ( $isAgentEnabled ) {
			$resultSet = self::$database->query(
				"SELECT id, name FROM agent ORDER BY name ASC"
			);
		}

		?><fieldset><legend>Remarks</legend>
			<?php
			if ( $isAgentEnabled ) {
				echo '<section><div><label for="agent_id">Agent:</label>';

				if ( self::$database->getResultCount( $resultSet ) > 0 ) {
					echo '<select name="agent_id" id="agent_id">';
					echo '<option value="0">-- select agent --</option>';
					while ( $agent = self::$database->getResultRow( $resultSet ) ) {
						echo '<option value="' . $agent['id'] . '"';
						if ( $agentID != null && $agentID == $agent['id'] ) {
							echo ' selected="selected"';
						}
						echo '>' . capitalizeWords( $agent['name'] ) . '</option>';
					}
					echo '</select>';
				} else {
					// no agent found, redirect to register agent first
					?><script type="text/javascript">
					<!--
						document.location = "add_agent.php";
					// -->
					</script><?php
				}

				echo '</div></section>';
			}

		?><section>
			<div>
				<label for="delivery_address">Notes/Comments:</label>
				<textarea name="comments" id="comments" rows="2"><?php echo ( $remarks != null ? Filter::reinput( $remarks ) : "" ) ?></textarea>
			</div>
		</section>
		</fieldset><?php
	}



	// check if tracking number entered already exist, ajax function
	public static function checkTrackingNumber()
	{
		// check required parameters
		if ( !isset( $_POST['trackingNumber'] ) || !isset( $_POST['invoiceType'] ) )
			return;
		
		$_POST['trackingNumber'] = Filter::input( $_POST['trackingNumber'] );
		if ( $_POST['invoiceType'] == "SI" )
			$field = "sales_invoice";
		else
			$field = "delivery_receipt";

		self::$database = new Database;

		$sqlQuery = "SELECT COUNT(*) AS count FROM `order` WHERE " . $field . " = '" . $_POST['trackingNumber'] . "' UNION SELECT COUNT(*) AS count FROM purchase WHERE " . $field . " = '" . $_POST['trackingNumber'] . "'";
		$resultSet = self::$database->query( $sqlQuery );

		$count = 0;
		
		while( $row = self::$database->getResultRow( $resultSet ) ) {
			$count = $count + $row['count'];
		}
		
		echo $count;
	}



	// prepare common transaction data before saving to database
	public function prepareTransactionData( $isItemEditable = true )
	{
		// sales invoice/delivery receipt
		if ( $_POST['invoice_type'] == 'SI' ) {
			if ( !empty( $_POST['tracking_number'] ) ) {
				$this->salesInvoice = "'" . Filter::input( $_POST['tracking_number'] ) . "'";
			} else {
				$this->salesInvoice = 'NULL';
			}
			$this->deliveryReceipt = 'NULL';
			$this->totalSales = $_POST['total_sales'];
			$this->valueAddedTax = $_POST['value_added_tax'];
			$this->withholdingTax = $_POST['withholding_tax'];
		} else {			// DR
			$this->salesInvoice = 'NULL';
			if ( !empty( $_POST['tracking_number'] ) ) {
				$this->deliveryReceipt = "'" . Filter::input( $_POST['tracking_number'] ) . "'";
			} else {
				$this->deliveryReceipt = 'NULL';
			}
			$this->totalSales = '0.000';
			$this->valueAddedTax = '0.000';
			$this->withholdingTax = '0.000';
		}

		// transaction date
		if ( $isItemEditable ) {
			$this->transactionDate = date('Y-m-d H:i:s');
		}

		// business unit
		if ( isset( $_POST['business_unit'] ) ) {
			$this->businessUnit = $_POST['business_unit'];
		} else {
			$this->businessUnit = null;
		}

		// branch
		if ( isset( $_POST['branch'] ) ) {
			$this->branch = $_POST['branch'];
		} else {
			$this->branch = null;
		}

		
		if ( $isItemEditable ) {
			// transaction type
			$this->transactionType = $_POST['transaction_type'];

			// delivery/pick-up target date
			if (isset($_POST['delivery_pickup_time'])) {
				$deliveryPickupTargetTime = $_POST['delivery_pickup_time'];
			} else {
				$deliveryPickupTargetTime = '00:00:00';
			}
			$this->deliveryPickupTargetDate = dateFormatInput(Filter::input($_POST['delivery_pickup_date'])." ".$deliveryPickupTargetTime);

			// delivery/pickup actual date
			if (isset($_POST['delivery_pickup_actual_date'])) {
				$this->deliveryPickupActualDate = $_POST['delivery_pickup_actual_date'];
			} else {
				$this->deliveryPickupActualDate = null;
			}

			// payment
			if (isset($_POST['payment_term'])) {
				$this->paymentTerm = $_POST['payment_term'];
				if ($this->paymentTerm == "installment" && isset($_POST['interest'])) {
					$this->interest = Filter::input($_POST['interest']);
					if (empty($this->interest)) {
						$this->interest = '0.000';
					}
				} else {
					$this->interest = '0.000';
				}
			} else {
				$this->paymentTerm = 'full';
				$this->interest    = '0.000';
			}

			// discount
			if (isset($_POST['payment_discount'])) {
				$this->transactionDiscount = Filter::input($_POST['payment_discount']);
				if (empty($this->transactionDiscount)) {
					$this->transactionDiscount = '0.000';
				}
			} else {
				$this->transactionDiscount = '0.000';
			}

			// receivable
			$this->receiptAmount    = $_POST['sidr_amount'] + (double)$this->interest;
			$this->amountReceivable = $_POST['net_amount'] + (double)$this->interest;
		}

		// agent
		if ( isset( $_POST['agent_id'] ) ) {
			$this->agentID = $_POST['agent_id'];
		} else {
			$this->agentID = null;
		}

		// comments
		$this->remarks = Filter::input( $_POST['comments'] );
		if ( empty( $this->remarks ) ) {
			$this->remarks = null;
		}
	}



	// save transaction items
	public function saveItems( $maxItems )
	{
		$class = $this->getInstanceClassName( $this );


		if ( $_POST[$class.'_query_mode'] == "edit" ) {
			// update reserved_stock
			if ($class == "order") {
				$sqlQuery = "SELECT inventory_id, SUM(quantity) AS quantity FROM ".$class."_item ".
							"WHERE ".$class."_id = ".$this->id." GROUP BY inventory_id";
				$resultSet = self::$database->query($sqlQuery);

				$oldInventoryID = array();
				$oldInventoryQuantity = array();
				while ($item = self::$database->getResultRow($resultSet)) {
					$oldInventoryID[] = $item['inventory_id'];
					$oldInventoryQuantity[] = $item['quantity'];
				}

				for ($i = 0, $oldInventoryCount = count($oldInventoryID); $i < $oldInventoryCount; $i++) {
					// return back the reserved stock prior to updating
					$sqlQuery = "UPDATE inventory SET reserved_stock = ".
								"IF(".$oldInventoryQuantity[$i]." <= reserved_stock,reserved_stock - ".$oldInventoryQuantity[$i].",0) ".
								"WHERE id = ".$oldInventoryID[$i];
					self::$database->query($sqlQuery);
				}
			}

			// delete previous records
			self::$database->query( "DELETE FROM " . $class . "_item WHERE " . $class . "_id = " . $this->id );
			
			// reset autoincrement
			self::$database->query( "ALTER TABLE " . $class . "_item AUTO_INCREMENT = 1" );
		}
		
		
		// save order items
		for ( $i = 1; $i <= $maxItems; $i++ )
		{
			if ( isset( $_POST['item_quantity_'.$i] ) )
			{
				$quantity = (int) Filter::input( $_POST['item_quantity_'.$i] );

				if ( $quantity > 0 )
				{
					// insert into order_item table
					$sqlQuery = "INSERT INTO " . $class . "_item VALUES (";
					$sqlQuery = $sqlQuery . "NULL,";													// id
					$sqlQuery = $sqlQuery . $this->id . ",";											// order_id/purchases_id
					$sqlQuery = $sqlQuery . $_POST['item_model_'.$i] . ",";								// inventory_id
					$sqlQuery = $sqlQuery . Filter::input( $_POST['item_price_'.$i] ) . ",";			// price
					$sqlQuery = $sqlQuery . Filter::input( $_POST['item_sidr_price_'.$i] ) . ",";		// sidr_price
					$sqlQuery = $sqlQuery . Filter::input( $_POST['item_net_price_'.$i] ) . ",";		// net_price
					$sqlQuery = $sqlQuery . $quantity . ",";											// quantity
					$sqlQuery = $sqlQuery . $quantity . ")";											// undelivered_quantity, set the same count as quantity

					self::$database->query( $sqlQuery );

					if ( $class == "order" )		// for order items only, update reserved stock
					{						
						$sqlQuery = "UPDATE inventory SET reserved_stock = ( reserved_stock + " . $quantity . "), " .
									"selling_price = " . Filter::input( $_POST['item_price_'.$i] ) . " " .
									"WHERE id = " . $_POST['item_model_'.$i];
						self::$database->query( $sqlQuery );
					}
				}
			}
		}
	}
	
	
	
	// display Item Delivery Dialog, ajax function
	public static function showItemDeliveryDialog()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) ||
			 !isset( $_POST['itemID'] ) || !isset( $_POST['index'] ) )
			return;

		self::$database = new Database;
		
		
		$sqlQuery = "SELECT transaction_type FROM `" . $_POST['class'] . "` WHERE id = " . $_POST['transactionID'];
		$resultSet = self::$database->query( $sqlQuery );
		$transaction = self::$database->getResultRow( $resultSet );
		
		
		$sqlQuery = "SELECT " . $_POST['class'] . "_item.*, inventory_brand.name AS brand, inventory.model AS model, stock_count" . 
					" FROM " . $_POST['class'] . "_item" .
					" INNER JOIN inventory ON inventory.id = " . $_POST['class'] . "_item.inventory_id" .
					" INNER JOIN inventory_brand ON inventory_brand.id = inventory.brand_id" .
					" WHERE " . $_POST['class'] . "_item.id = " . $_POST['itemID'];
		$resultSet = self::$database->query( $sqlQuery );
		$item = self::$database->getResultRow( $resultSet );
		
		if ( (int) $item['undelivered_quantity'] <= 0 )
		{
			echo '<form name="mark_as_delivered_form" method="post" autocomplete="off" action="javascript:markItemAsDelivered()">' .
				 "<div>" .
				 "<span class=\"record_label\">Brand:</span>" .
				 "<span class=\"record_data\">" . capitalizeWords( $item['brand'] ) . "</span>" .
				 "</div><div>" .
				 "<span class=\"record_label\">Model:</span>" .
				 "<span class=\"record_data\">" . capitalizeWords( $item['model'] ) . "</span>" .
				 "</div>" .
				 "<div><br />" .
				 "<span class=\"record_label\">No. of pending items:</span>" .
				 "<span class=\"good record_data\">All items already ";
			
			if ( $transaction['transaction_type'] == "delivery" )
			{
				if ( $_POST['class'] == "order" )
					echo "delivered";
				else
					echo "received";
			}
			else
			{
				if ( $_POST['class'] == "order" )
					echo "picked-up";
				else
					echo "received";
			}
			
			echo "</span></div>";
	
			// Yes and No buttons
			echo "<div id=\"dialog_buttons\"><br />" .
				 "<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />" .
				 "<input type=\"button\" value=\"Return Item\" style=\"margin-left: 238px\" onclick=\"showDialog('Return Item','Getting data...','warning'), ajax(null,'dialog_message','innerHTML','Transaction::showItemReturnDialog','class=" . $_POST['class'] . "&transactionID=" . $_POST['transactionID'] . "&itemID=" . $_POST['itemID'] . "&index=" . $_POST['index'] . "')\" >" .
				 "</div>" .
				 "</form>";
		}
		elseif ( $_POST['class'] == "purchase" || ( $_POST['class'] == "order" && (int) $item['undelivered_quantity'] <= (int) $item['stock_count'] ) )
		{
			echo '<form name="mark_as_delivered_form" method="post" autocomplete="off" action="javascript:markItemAsDelivered()">' .
				 '<input type="hidden" name="class_name" id="class_name" value="' . $_POST['class'] . '" />' .
				 '<input type="hidden" name="transaction_id" id="transaction_id" value="' . $_POST['transactionID'] . '" />' .
				 "<input type=\"hidden\" name=\"itemID\" id=\"itemID\" value=\"" . $_POST['itemID'] . "\" />" .
				 "<input type=\"hidden\" name=\"index\" id=\"index\" value=\"" . $_POST['index'] . "\" />" .
				 "<div>" .
				 "<span class=\"record_label\">Brand:</span>" .
				 "<span class=\"record_data\">" . capitalizeWords( $item['brand'] ) . "</span><br />" .
				 "<span class=\"record_label\">Model:</span>" .
				 "<span class=\"record_data\">" . capitalizeWords( $item['model'] ) . "</span>" .
				 "</div>" .
				 "<div><br />" .
				 "<span class=\"record_label\">No. of pending items:</span>" .
				 "<span class=\"record_data\"><input type=\"text\" name=\"quantity_undelivered\" id=\"quantity_undelivered\" class=\"number output_field\" value=\"" . $item['undelivered_quantity'] . "\" required=\"required\" disabled=\"disabled\" /></span>" .
				 "</div><div>" .
				 "<span class=\"record_label\">No. of items ";
			
			if ( $transaction['transaction_type'] == "delivery" )
			{
				if ( $_POST['class'] == "order" )
					echo "delivered";
				else
					echo "received";
			}
			else
			{
				if ( $_POST['class'] == "order" )
					echo "picked-up";
				else
					echo "received";
			}
			
			//$defaultDeliveryDate    = new DateTime();
			//$defaultDeliveryDateStr = $defaultDeliveryDate->format( "F j, Y, D" );
			
			echo ":</span>" .
				 "<span class=\"record_data\">" . 
				 "<input type=\"text\" name=\"quantity_delivered\" id=\"quantity_delivered\" class=\"number\" value=\"" . $item['undelivered_quantity'] . "\" autofocus=\"autofocus\" required=\"required\" onfocus=\"data.selectField( $(this), 'int' )\" onblur=\"data.validateField( $(this), 'int' )\" />" .
				 "</span>" .
				 "</div>" .
				 "<div><br />" .
				 "<span class=\"record_label\">Delivery Date:</span>" .
				 "<span class=\"record_data\">" .
				 "<input type=\"text\" name=\"delivery_date\" id=\"delivery_date\" class=\"datepicker_no_future_date\" maxlength=\"30\" size=\"30\" required=\"required\" onfocus=\"data.selectField( $(this) )\" />" .
				 "</span>" .
				 "</div>";
	
			// Yes and No buttons
			echo "<div id=\"dialog_buttons\"><br />" .
				 "<input type=\"submit\" value=\"Save\" />" .
				 "<input type=\"reset\" value=\"Reset\" />" .
				 "<input type=\"button\" value=\"Cancel\" onclick=\"hideDialog()\" />";
			
			if ( (int) $item['undelivered_quantity'] < (int) $item['quantity'] )
				 echo "<input type=\"button\" value=\"Return Item\" style=\"margin-left: 43px\" onclick=\"showDialog('Return Item','Getting data...','warning'), ajax(null,'dialog_message','innerHTML','Transaction::showItemReturnDialog','class=" . $_POST['class'] . "&transactionID=" . $_POST['transactionID'] . "&itemID=" . $_POST['itemID'] . "&index=" . $_POST['index'] . "')\" >";
			
			echo "</div></form>";
		}
		else
		{
			echo "<b>Notice:</b> You cannot mark this item as ";
			
			if ( $transaction['transaction_type'] == "delivery" )
			{
				if ( $_POST['class'] == "order" )
					echo "delivered";
				else
					echo "received";
			}
			else
			{
				if ( $_POST['class'] == "order" )
					echo "picked-up";
				else
					echo "received";
			}
			
			echo " because you are running out of supplies.<br /><br />".
				 "<div>" .
				 "<span class=\"record_label\">Brand:</span>" .
				 "<span class=\"record_data\">" . capitalizeWords( $item['brand'] ) . "</span><br />" .
				 "<span class=\"record_label\">Model:</span>" .
				 "<span class=\"record_data\">" . capitalizeWords( $item['model'] ) . "</span>" .
				 "</div><div>" .
				 "<span class=\"record_label\">No. of stocks:</span>" .
				 "<span class=\"record_data\" style=\"width: 50px\">" . $item['stock_count'] . "</span>" .
				 "</div><div>" .
				 "<span class=\"record_label\">Needed quantity:</span>" .
				 "<span class=\"record_data\" style=\"width: 50px\">" . $item['undelivered_quantity'] . "</span>" .
				 "</div>";
				 
			echo "<form>" .
				 "<div id=\"dialog_buttons\"><br />" .
				 "<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />" .
				 "<input type=\"button\" value=\"Purchase Supplies\" onclick=\"document.location='purchase_supplies.php'\" />" .
				 "</div>" .
				 "</form>";
		}
	}
	
	
	
	// mark an item as delivered, ajax function
	public static function markItemAsDelivered( $itemID = null, $deliveredQuantity = null ) {
		// check required parameters
		if ( !isset( $_POST['class'] )  || !isset( $_POST['transactionID'] ) || !isset( $_POST['deliveryDate'] ) ) {
			return;
		}
		
		if ( !isset( $_POST['itemID'] ) ) {
			if ( $itemID == null ) {
				return;
			}
		} else {
			$itemID = $_POST['itemID'];
		}
		
		if ( !isset( $_POST['deliveredQuantity'] ) ) {
			if ( $deliveredQuantity == null ) {
				return;
			}
		} else {
			$deliveredQuantity = $_POST['deliveredQuantity'];
		}
		
		
		if ( self::$database == null ) {
			self::$database = new Database;
		}
		
		
		// update undelivered_quantity
		$sqlQuery = "UPDATE " . $_POST['class'] . "_item " .
					"SET undelivered_quantity = ( undelivered_quantity - " . $deliveredQuantity . " ) " .
					"WHERE id = " . $itemID;
		
		if ( self::$database->query( $sqlQuery ) )	{
			$isInventoryModified = false;
			$childInventoryName = null;
			
			$sqlQuery = "SELECT inventory_id, net_price FROM " . $_POST['class'] . "_item WHERE id = " . $itemID;
			$resultSet = self::$database->query( $sqlQuery );
			$item = self::$database->getResultRow( $resultSet );
			
			if ( $_POST['class'] == "order" ) {
				// order
				
				// update stocks
				$sqlQuery = "UPDATE inventory " .
							"SET stock_count = (stock_count - " . $deliveredQuantity . "), " .
							"reserved_stock = IF(reserved_stock >= " . $deliveredQuantity . ", " .
												"reserved_stock - " . $deliveredQuantity . ", 0) " .
							"WHERE id = " . $item['inventory_id'];
				self::$database->query( $sqlQuery );
			} else {
				// purchase
				
				$itemInventoryID = $item['inventory_id'];
				
				$sqlQuery = "SELECT * FROM inventory WHERE id = " . $item['inventory_id'];
				$resultSet = self::$database->query( $sqlQuery );
				$inventory = self::$database->getResultRow( $resultSet );
				
				$parentID = $inventory['parent_id'];
				if ( $parentID != null ) {
					// selected inventory is a child inventory
					// get parent info of the selected inventory
					$sqlQuery = "SELECT * FROM inventory WHERE id = " . $parentID;
					$resultSet = self::$database->query( $sqlQuery );
					$inventory = self::$database->getResultRow( $resultSet );	// override inventory array
					
					$itemInventoryID = $parentID;
				}
					
				if ( $item['net_price'] != $inventory['purchase_price'] ) {
					// new purchase price
					
					if ( $inventory['stock_count'] > 0 ) {
						// parent inventory still have remaining stock
						
						// proceed to create child inventory
						$childInventoryName = self::createChildInventory( $inventory, $itemInventoryID, $_POST['transactionID'], $itemID );
						
						// update parent inventory to reflect latest price
						$sqlQuery = "UPDATE inventory " .
									"SET purchase_price = " . $item['net_price'] . ", " .
									"selling_price = 0, " .
									"stock_count = " . $deliveredQuantity . ", " .
									"reserved_stock = 0 " .
									"WHERE id = " . $inventory['id'];
						self::$database->query( $sqlQuery );
					} else {
						// no more stock in parent inventory
						
						// just update purchase price and stock and reuse parent inventory
						$sqlQuery = "UPDATE inventory " .
									"SET purchase_price = " . $item['net_price'] . ", " .
									"selling_price = 0, " .
									"stock_count = " . $deliveredQuantity . " " .
									"WHERE id = " . $item['inventory_id'];
						self::$database->query( $sqlQuery );
					}
					
					$isInventoryModified = true;
				} else {
					// no change in purchase price
					
					// simply update and reuse parent inventory
					$sqlQuery = "UPDATE inventory " .
								"SET stock_count = (stock_count + " . $deliveredQuantity . ") " .
								"WHERE id = " . $item['inventory_id'];
					self::$database->query( $sqlQuery );
				}
			}
			
			
			if ( isset( $_POST['itemID'] ) && isset( $_POST['deliveredQuantity'] ) ) {
				// function is called through AJAX
				// the item is marked as delivered individually
				
				
				// log event
				if ( $_POST['class'] == "order" ) {
					$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id, transaction_type ' .
								'FROM `order` WHERE id=' . $_POST['transactionID'];
					$resultSet = self::$database->query( $sqlQuery );
					$order = self::$database->getResultRow( $resultSet );
					
					$sqlQuery = 'SELECT id, name FROM customer WHERE id=' . $order['customer_id'];
					$resultSet = self::$database->query( $sqlQuery );
					$customer = self::$database->getResultRow( $resultSet );
					
					if ( $order['sales_invoice'] != null ) {
						$invoiceNumber = 'SI ' . $order['sales_invoice'];
					} else {
						$invoiceNumber = 'DR ' . $order['delivery_receipt'];
					}
					
					EventLog::addEntry( self::$database, 'info', 'order', 'update', 'delivered',
										'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
										$deliveredQuantity .	($deliveredQuantity  > 1 ? ' items were' : ' item was') .
										( $order['transaction_type'] == 'delivery' ?
											' <span class="event_log_action">delivered</span> to ' :
											' <span class="event_log_action">picked-up</span> by ' ) .
										'<a href="view_customer_details.php?id=' . $customer['id'] . '">' .
										capitalizeWords( Filter::output( $customer['name'] ) ) . '</a> ' );
				} else {
					$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id, transaction_type ' .
								'FROM purchase WHERE id=' . $_POST['transactionID'];
					$resultSet = self::$database->query( $sqlQuery );
					$purchase = self::$database->getResultRow( $resultSet );
					
					$sqlQuery = 'SELECT id, name FROM supplier WHERE id=' . $purchase['supplier_id'];
					$resultSet = self::$database->query( $sqlQuery );
					$supplier = self::$database->getResultRow( $resultSet );
					
					if ( $purchase['sales_invoice'] != null ) {
						$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
					} elseif ( $purchase['delivery_receipt'] != null ) {
						$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
					} else {
						$invoiceNumber = '';
					}
					
					EventLog::addEntry( self::$database, 'info', 'purchase', 'update', 'delivered',
										'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
										$deliveredQuantity . ( $deliveredQuantity  > 1 ? ' items were' : ' item was') .
										( $purchase['transaction_type'] == 'delivery' ?
											' <span class="event_log_action">delivered</span> by ' :
											' <span class="event_log_action">picked-up</span> from ' ) .
										'<a href="view_supplier_details.php?id=' . $supplier['id'] . '">' .
										capitalizeWords( Filter::output( $supplier['name'] ) ) . '</a> ' );
				}
				
				
				
				// display success message
				
				// check if there are still undelivered items
				$sqlQuery = "SELECT id FROM " . $_POST['class'] . "_item " .
							"WHERE " . $_POST['class'] . "_id = " . $_POST['transactionID'] .
							" AND undelivered_quantity > 0";
				$resultSet = self::$database->query( $sqlQuery );
				
				if ( self::$database->getResultCount( $resultSet ) > 0 ) {
					// there are still undelivered items
					if ( $_POST['class'] == "order" ) {
						echo "Order No. ";
					} else {
						echo "Purchase No. ";
					}
					
					echo $_POST['transactionID'] . " successfully updated!<br /><br />";
				} else {
					// all items now delivered
					$deliveryPickupActualDate = new DateTime( $_POST['deliveryDate'] );
					$sqlQuery = "UPDATE `" . $_POST['class'] . "` SET " .
								"delivery_pickup_actual_date = '" . $deliveryPickupActualDate->format( 'Y-m-d H:i:s' ) . "' " .
								"WHERE id = " . $_POST['transactionID'];
					self::$database->query( $sqlQuery );
					
					echo "All items for ";
					if ( $_POST['class'] == "order" ) {
						echo "Order No. ";
					} else {
						echo "Purchase No. ";
					}
					echo $_POST['transactionID'] . " are now <b>Delivered</b>!<br /><br />";
				}
				
				if ( $_POST['class'] == "order" ) {
					echo "Delivered/Picked-up items have been deducted to inventory!<br />";
				} else {
					echo "Received items have been added to inventory.<br />";
					
					if ( $isInventoryModified ) {
						echo '<br />' .
							 '<b>Important:</b> Inventory has been updated with new purchase price.<br />' . 
							 'For safekeeping purposes, selling price was reset to <span 
class="bad">' . CURRENCY . ' 0.000</span>.<br />' .
							 'Do not forget to update the selling price of inventory items listed on this Purchase Order.';
						
						if ( $childInventoryName != null ) {
							echo '<br /><br />New inventory <b>' . $childInventoryName . '</b> was created as placeholder for the old stocks.';
						}
					}
				}
			} else {
				// function is called by other member function
				
				// do not display success message
				if ( $isInventoryModified ) {
					if ( $childInventoryName != null ) {
						return $childInventoryName;
					} else {
						return true;
					}
				} else {
					return false;
				}
			}
		} else {
			if ( $_POST['class'] == "order" ) {
				Diagnostics::error( 'dialog', ERROR, "Cannot update Order No. " . $_POST['transactionID'], "Please try again.", SYSTEM_ERROR );
			} else {
				Diagnostics::error( 'dialog', ERROR, "Cannot update Purchase No. " . $_POST['transactionID'], "Please try again.", SYSTEM_ERROR );
			}
		}
	}
	
	
	
	// display Item Return Dialog, ajax function
	public static function showItemReturnDialog()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) ||
			 !isset( $_POST['itemID'] ) || !isset( $_POST['index'] ) )
			return;

		self::$database = new Database;
		
		
		$sqlQuery = "SELECT transaction_type FROM `" . $_POST['class'] . "` WHERE id = " . $_POST['transactionID'];
		$resultSet = self::$database->query( $sqlQuery );
		$transaction = self::$database->getResultRow( $resultSet );
		
		
		$sqlQuery = "SELECT " . $_POST['class'] . "_item.*, inventory_brand.name AS brand, inventory.model AS model" . 
					" FROM " . $_POST['class'] . "_item" .
					" INNER JOIN inventory ON inventory.id = " . $_POST['class'] . "_item.inventory_id" .
					" INNER JOIN inventory_brand ON inventory_brand.id = inventory.brand_id" .
					" WHERE " . $_POST['class'] . "_item.id = " . $_POST['itemID'];
		$resultSet = self::$database->query( $sqlQuery );
		$item = self::$database->getResultRow( $resultSet );
		
		
		echo '<form name="return_item_form" method="post" autocomplete="off" action="javascript:returnDeliveredItems()" onsubmit="return confirmNoReturnToStocks()">' .
			 '<input type="hidden" name="class_name" id="class_name" value="' . $_POST['class'] . '" />' .
			 '<input type="hidden" name="transaction_id" id="transaction_id" value="' . $_POST['transactionID'] . '" />' .
			 "<input type=\"hidden\" name=\"itemID\" id=\"itemID\" value=\"" . $_POST['itemID'] . "\" />" .
			 "<input type=\"hidden\" name=\"index\" id=\"index\" value=\"" . $_POST['index'] . "\" />" .
			 "<div>" .
			 "<span class=\"record_label\">Brand:</span>" .
			 "<span class=\"record_data\">" . capitalizeWords( $item['brand'] ) . "</span><br />" .
			 "<span class=\"record_label\">Model:</span>" .
			 "<span class=\"record_data\">" . capitalizeWords( $item['model'] ) . "</span>" .
			 "</div>" .
			 "<div><br />" .
			 "<span class=\"record_label\">No. of ";
		
		if ( $transaction['transaction_type'] == "delivery" )
		{
			if ( $_POST['class'] == "order" )
				echo "delivered";
			else
				echo "received";
		}
		else
		{
			if ( $_POST['class'] == "order" )
				echo "picked-up";
			else
				echo "received";
		}
		
		echo " items:</span>" .
			 "<span class=\"record_data\"><input type=\"text\" name=\"quantity_delivered\" id=\"quantity_delivered\" class=\"number output_field\" value=\"" . ( $item['quantity'] - $item['undelivered_quantity'] ) . "\" required=\"required\" disabled=\"disabled\" /></span>" .
			 "</div><div>" .
			 "<span class=\"record_label\">No. of items returned:</span>" .
			 "<span class=\"record_data\">" . 
			 "<input type=\"text\" name=\"quantity_returned\" id=\"quantity_returned\" class=\"number\" value=\"0\" autofocus=\"autofocus\" required=\"required\" onfocus=\"data.selectField( $(this), 'int' )\" onblur=\"data.validateField( $(this), 'int' )\" />" .
			 "</span>" .
			 "</div><div><br />" .
			 "<span class=\"record_label\"><input type=\"checkbox\" name=\"return_to_inventory\" id=\"return_to_inventory\" checked=\"checked\"" .
			 ( $_POST['class'] == "purchase" ? " disabled=\"disabled\"" : "" ) .
			 " /></span>" .
			 "<span class=\"record_data\"><label for=\"return_to_inventory\">" . 
			 ( $_POST['class'] == "order" ? "Return to inventory stocks" : "Remove from inventory stocks" ) .
			 "</label></span>" .
			 "</div>";

		// Yes and No buttons
		echo "<div id=\"dialog_buttons\"><br />" .
			 "<input type=\"submit\" value=\"Save\" />" .
			 "<input type=\"reset\" value=\"Reset\" />" .
			 "<input type=\"button\" value=\"Cancel\" onclick=\"hideDialog()\" />" .
			 "</div></form>";
	}
	
	
	
	// return delivered items, ajax function
	public static function returnDeliveredItems() {
		// check required parameters
		if (    !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] )
			 || !isset( $_POST['itemID'] ) || !isset( $_POST['returnedQuantity'] )
			 || !isset( $_POST['returnToInventory'] ) ) {
			return;
		}
		
		
		self::$database = new Database;
		
		
		// get inventory ID of item
		$sqlQuery = "SELECT inventory_id FROM " . $_POST['class'] . "_item WHERE id = " . $_POST['itemID'];
		$resultSet = self::$database->query( $sqlQuery );
		$item = self::$database->getResultRow( $resultSet );
		
		
		// for purchase, do not allow return if stocks are already used in orders
		if ( $_POST['class'] == "purchase" ) {
			$sqlQuery = "SELECT stock_count, parent_id FROM inventory " .
						"WHERE id = " . $item['inventory_id'];
			$resultSet = self::$database->query( $sqlQuery );
			$inventory = self::$database->getResultRow( $resultSet );
			
			if ( $inventory['stock_count'] - $_POST['returnedQuantity'] < 0 ) {
				echo "<b>Error:</b> You cannot return this item because the stocks are already used in Orders.<br /><br />" . 
					 "Items should be returned from Orders first, and try again.<br /><br />";
				return;
			}
		}
		
		
		// update undelivered_quantity
		$sqlQuery = "UPDATE " . $_POST['class'] . "_item SET " .
					"undelivered_quantity = ( undelivered_quantity + " . $_POST['returnedQuantity'] . " ) " .
					"WHERE id = " . $_POST['itemID'];
		
		if ( self::$database->query( $sqlQuery ) ) {
			// clear delivery_pickup_actual_date
			$sqlQuery = "UPDATE `" . $_POST['class'] . "` SET " .
						"delivery_pickup_actual_date = NULL " .
						"WHERE id = " . $_POST['transactionID'];
			self::$database->query( $sqlQuery );
			
			
			// log event
			if ( $_POST['class'] == "order" ) {
				$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id ' .
							'FROM `order` WHERE id=' . $_POST['transactionID'];
				$resultSet = self::$database->query( $sqlQuery );
				$order = self::$database->getResultRow( $resultSet );
				
				$sqlQuery = 'SELECT id, name FROM customer WHERE id=' . $order['customer_id'];
				$resultSet = self::$database->query( $sqlQuery );
				$customer = self::$database->getResultRow( $resultSet );
				
				if ( $order['sales_invoice'] != null ) {
					$invoiceNumber = 'SI ' . $order['sales_invoice'];
				} else {
					$invoiceNumber = 'DR ' . $order['delivery_receipt'];
				}
				
				EventLog::addEntry( self::$database, 'warning', 'order', 'update', 'undelivered',
									'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
									$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
									$_POST['returnedQuantity'] . ( $_POST['returnedQuantity']  > 1 ? ' items were' : ' item was') .
									' <span class="event_log_action">returned</span> by <a href="view_customer_details.php?id=' . $customer['id'] . '">' .
									capitalizeWords( Filter::output( $customer['name'] ) ) . '</a> ' );
			} else {
				$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id ' .
							'FROM purchase WHERE id=' . $_POST['transactionID'];
				$resultSet = self::$database->query( $sqlQuery );
				$purchase = self::$database->getResultRow( $resultSet );
				
				$sqlQuery = 'SELECT id, name FROM supplier WHERE id=' . $purchase['supplier_id'];
				$resultSet = self::$database->query( $sqlQuery );
				$supplier = self::$database->getResultRow( $resultSet );
				
				if ( $purchase['sales_invoice'] != null ) {
					$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
				} elseif ( $purchase['delivery_receipt'] != null ) {
					$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
				} else {
					$invoiceNumber = '';
				}
				
				EventLog::addEntry( self::$database, 'warning', 'purchase', 'update', 'undelivered',
									'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
									$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
									$_POST['returnedQuantity'] . ( $_POST['returnedQuantity']  > 1 ? ' items were' : ' item was') .
									' <span class="event_log_action">returned</span> to <a href="view_supplier_details.php?id=' . $supplier['id'] . '">' .
									capitalizeWords( Filter::output( $supplier['name'] ) ) . '</a> ' );
			}
			
			
			if ( $_POST['returnToInventory'] == "true" ) {
				// update stocks
				
				if ( $_POST['class'] == "order" ) {
					// order
					
					$sqlQuery = "UPDATE inventory SET " .
								"stock_count = (stock_count + " . $_POST['returnedQuantity'] . "), " .
								"reserved_stock = (reserved_stock + " . $_POST['returnedQuantity'] . ") " .
								"WHERE id = " . $item['inventory_id'];
					self::$database->query( $sqlQuery );
				} else {
					// purchase
					
					$sqlQuery = "UPDATE inventory SET " .
								"stock_count = (stock_count - " . $_POST['returnedQuantity'] . ") " .
								"WHERE id = " . $item['inventory_id'];
					self::$database->query( $sqlQuery );
					
					// delete child inventory, if there is any
					self::deleteChildInventory( $item['inventory_id'] );
				}
				
				
				if ( $_POST['class'] == "order" ) {
					echo "Order No. ";
				} else {
					echo "Purchase No. ";
				}
				
				echo $_POST['transactionID'] . " successfully updated!<br /><br />";
				
				if ( $_POST['class'] == "order" ) {
					echo "Returned items have been added back to inventory.<br />";
				} else {
					echo "Returned items have been deducted to inventory.<br />";
				}
			} else {
				// do not return stocks
				// just display success dialog
				
				if ( $_POST['class'] == "order" ) {
					echo "Order No. ";
				} else {
					echo "Purchase No. ";
				}
				
				echo $_POST['transactionID'] . " successfully updated!<br /><br /><br />";
			}
		} else {
			if ( $_POST['class'] == "order" ) {
				Diagnostics::error( 'dialog', ERROR, "Cannot update Order No. " . $_POST['transactionID'], "Please try again.", SYSTEM_ERROR );
			} else {
				Diagnostics::error( 'dialog', ERROR, "Cannot update Purchase No. " . $_POST['transactionID'], "Please try again.", SYSTEM_ERROR );
			}
		}
	}
	
	
	
	// display All Items Delivery Dialog, ajax function
	public static function showAllItemsDeliveryDialog()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) || !isset( $_POST['maxIndex'] ) )
			return;

		self::$database = new Database;
		
		
		$sqlQuery = "SELECT transaction_type, delivery_pickup_actual_date FROM `" . $_POST['class'] . "` WHERE id = " . $_POST['transactionID'];
		$resultSet = self::$database->query( $sqlQuery );
		$transaction = self::$database->getResultRow( $resultSet );
		
		if ( $transaction['delivery_pickup_actual_date'] != null )
		{
			echo "All items for ";
			if ( $_POST['class'] == "order" )
				echo "Order No. ";
			else
				echo "Purchase No. ";
			echo $_POST['transactionID'] . " are now <span class=\"best\">Delivered</span>.<br /><br />" .
				 "If the items are returned, click on the individual item's quantity.<br /><br /><br />";
				
			echo "<form>" .
				 "<div id=\"dialog_buttons\">" .
				 "<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />" .
				 "</div>" .
				 "</form>";
		}
		else
		{
			$outOfStock = false;
			
			if ( $_POST['class'] == "order" )
			{
				// check for available stocks
				$inventoryName = array();
				$stockAvailable = array();
				$neededStock = array();
		
				$sqlQuery = "SELECT name, model, stock_count, SUM(undelivered_quantity) AS undelivered_quantity " .
							"FROM order_item " .
							"INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
							"INNER JOIN inventory_brand ON inventory_brand.id = inventory.brand_id " .
							"WHERE order_id = " . $_POST['transactionID'] . " GROUP BY order_item.inventory_id";
				$resultSet = self::$database->query( $sqlQuery );
		
		
				while( $inventory = self::$database->getResultRow( $resultSet ) )
				{
					if ( (int) $inventory['undelivered_quantity'] > (int) $inventory['stock_count'] )
					{
						array_push( $inventoryName, $inventory['name'] . " - " . $inventory['model'] );
						array_push( $stockAvailable, $inventory['stock_count'] );
						array_push( $neededStock, $inventory['undelivered_quantity'] );
						$outOfStock = true;
					}
				}
			}
	
			if ( $_POST['class'] == "order" && $outOfStock == true )
			{
				// display confirmation to mark order as delivered
				echo "<b>Notice:</b> You cannot mark ";
				if ( $_POST['class'] == "order" )
					echo "Order No. ";
				else
					echo "Purchase No. ";
				echo $_POST['transactionID'] . " as ";
				if ( $transaction['transaction_type'] == "delivery" )
				{
					if ( $_POST['class'] == "order" )
						echo "delivered";
					else
						echo "received";
				}
				else
				{
					if ( $_POST['class'] == "order" )
						echo "picked-up";
					else
						echo "received";
				}
				echo " because you are running out of supplies for the following items:<br /><br />";
	
	
				for ( $i = 0; $i < sizeof( $inventoryName ); $i++ )
				{
					echo "<div>" .
						 $inventoryName[$i] . "<br />" .
						 "Stocks Available: " . $stockAvailable[$i] . "<br />" .
						 "Stocks Needed for this Order: " . $neededStock[$i] . "<br /><br />";
				}
	
	
				echo "<form>" .
					 "<div id=\"dialog_buttons\">" .
					 "<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />" .
					 "<input type=\"button\" value=\"Purchase Supplies\" onclick=\"document.location='purchase_supplies.php'\" />" .
					 "</div>" .
					 "</form>";
			}
			else
			{
				// display confirmation to mark order as delivered
				echo "Proceed to mark all items for ";
				if ( $_POST['class'] == "order" )
					echo "Order No. ";
				else
					echo "Purchase No. ";
				echo $_POST['transactionID'] . " as <b>";
				if ( $transaction['transaction_type'] == "delivery" )
				{
					if ( $_POST['class'] == "order" )
						echo "Delivered";
					else
						echo "Received";
				}
				else
					echo "Picked-up";
				echo "</b>?<br /><br />Inventory will be updated automatically.<br /><br />";
	
				// Yes and No buttons
				echo '<form name="mark_as_delivered_form" method="post" autocomplete="off" action="javascript:markAllItemsAsDelivered()">' .
					 '<input type="hidden" name="class_name" id="class_name" value="' . $_POST['class'] . '" />' .
					 '<input type="hidden" name="transaction_id" id="transaction_id" value="' . $_POST['transactionID'] . '" />' .
					 '<input type="hidden" name="max_index" id="max_index" value="' . $_POST['maxIndex'] . '" />' .
					 "<div><br />" .
					 "<span class=\"record_label\">Delivery Date:</span>" .
					 "<span class=\"record_data\">" .
					 "<input type=\"text\" name=\"delivery_date\" id=\"delivery_date\" class=\"datepicker_no_future_date\" maxlength=\"30\" size=\"30\" required=\"required\" onfocus=\"data.selectField( $(this) )\" />" .
					 "</span>" .
					 "</div>" .
					 "<div id=\"dialog_buttons\"><br />" .
					 "<input type=\"submit\" value=\"Yes\" />" .
					 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
					 "</div>" .
					 "</form>";
			}
		}
	}
	
	
	
	// mark all items as delivered, ajax function
	public static function markAllItemsAsDelivered() {
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) || !isset( $_POST['deliveryDate'] ) ) {
			return;
		}


		self::$database = new Database;
		
		$itemIDArray			  = array();
		$undeliveredQuantityArray = array();

		// get item info
		$sqlQuery = "SELECT id, undelivered_quantity FROM " . $_POST['class'] . "_item " .
					"WHERE " . $_POST['class'] . "_id = " . $_POST['transactionID'] . " " .
					"AND undelivered_quantity > 0";
		$resultSet = self::$database->query( $sqlQuery );
		$resultCount = self::$database->getResultCount( $resultSet );
		
		$totalUndeliveredQuantity = 0;
		
		while( $item = self::$database->getResultRow( $resultSet ) ) {
			array_push( $itemIDArray, $item['id'] );
			array_push( $undeliveredQuantityArray, $item['undelivered_quantity'] );
			$totalUndeliveredQuantity = $totalUndeliveredQuantity + $item['undelivered_quantity'];
		}
		
		$childInventoryNameArray  = array();
		$isInventoryModified = false;
		
		for ( $i = 0; $i < $resultCount; $i++ ) {
			$childInventoryName = self::markItemAsDelivered( $itemIDArray[$i], $undeliveredQuantityArray[$i] );
			if ( $childInventoryName != false ) {
				if ( !is_bool( $childInventoryName ) ) {
					array_push( $childInventoryNameArray, $childInventoryName );
				}
				$isInventoryModified = true;
			}
		}
		
		
		// set delivery date
		$deliveryPickupActualDate = new DateTime( $_POST['deliveryDate'] );
		$sqlQuery = "UPDATE `" . $_POST['class'] . "` SET " .
					"delivery_pickup_actual_date = '" . $deliveryPickupActualDate->format( 'Y-m-d H:i:s' ) . "' " .
					"WHERE id = " . $_POST['transactionID'];
		self::$database->query( $sqlQuery );
		
		
		// log event
		if ( $_POST['class'] == "order" ) {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id, transaction_type ' .
						'FROM `order` WHERE id=' . $_POST['transactionID'];
			$resultSet = self::$database->query( $sqlQuery );
			$order = self::$database->getResultRow( $resultSet );
			
			$sqlQuery = 'SELECT id, name FROM customer WHERE id=' . $order['customer_id'];
			$resultSet = self::$database->query( $sqlQuery );
			$customer = self::$database->getResultRow( $resultSet );
			
			if ( $order['sales_invoice'] != null ) {
				$invoiceNumber = 'SI ' . $order['sales_invoice'];
			} else {
				$invoiceNumber = 'DR ' . $order['delivery_receipt'];
			}
			
			EventLog::addEntry( self::$database, 'info', 'order', 'update', 'delivered',
								'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
								$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
								$totalUndeliveredQuantity .	($totalUndeliveredQuantity  > 1 ? ' items were' : ' item was') .
								( $order['transaction_type'] == 'delivery' ?
									' <span class="event_log_action">delivered</span> to ' :
									' <span class="event_log_action">picked-up</span> by ' ) .
								'<a href="view_customer_details.php?id=' . $customer['id'] . '">' .
								capitalizeWords( Filter::output( $customer['name'] ) ) . '</a> ' );
		} else {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id, transaction_type ' .
						'FROM purchase WHERE id=' . $_POST['transactionID'];
			$resultSet = self::$database->query( $sqlQuery );
			$purchase = self::$database->getResultRow( $resultSet );
			
			$sqlQuery = 'SELECT id, name FROM supplier WHERE id=' . $purchase['supplier_id'];
			$resultSet = self::$database->query( $sqlQuery );
			$supplier = self::$database->getResultRow( $resultSet );
			
			if ( $purchase['sales_invoice'] != null ) {
				$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
			} elseif ( $purchase['delivery_receipt'] != null ) {
				$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
			} else {
				$invoiceNumber = '';
			}
			
			EventLog::addEntry( self::$database, 'info', 'purchase', 'update', 'delivered',
								'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
								$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
								$totalUndeliveredQuantity .	( $totalUndeliveredQuantity  > 1 ? ' items were' : ' item was') .
								( $purchase['transaction_type'] == 'delivery' ?
									' <span class="event_log_action">delivered</span> by ' :
									' <span class="event_log_action">picked-up</span> from ' ) .
								'<a href="view_supplier_details.php?id=' . $supplier['id'] . '">' .
								capitalizeWords( Filter::output( $supplier['name'] ) ) . '</a> ' );
		}
		
		
		// display success message
		echo "All items for ";
		if ( $_POST['class'] == "order" ) {
			echo "Order No. ";
		} else {
			echo "Purchase No. ";
		}
		echo $_POST['transactionID'] . " are now <b>Delivered</b>!<br /><br />";
		if ( $_POST['class'] == "order" ) {
			echo "Delivered/Picked-up items have been deducted to inventory.";
		} else {
			echo "Received items have been added to inventory.";
		}
		echo '<br />';
		
		
		// display changed items
		if ( $isInventoryModified ) {
			echo '<br />' .
				 '<b>Important:</b> Inventory has been updated with new purchase price.<br />' . 
				 'For safekeeping purposes, selling price was reset to <span 
class="bad">' . CURRENCY . ' 0.000</span>.<br />' .
				 'Do not forget to update the selling price of inventory items listed on this Purchase Order.';
				 
			if ( sizeof( $childInventoryNameArray ) > 0 ) {
				echo '<br /><br />The following item/s were created as placeholder for old stocks:<br /><ul>';
				for ( $i = 0; $i < sizeof( $childInventoryNameArray ); $i++ ) {
					echo '<li>' . $childInventoryNameArray[$i] . '</li>';
				}
				echo '</ul>';
			}
		}
	}
	
	
	
	// get delivery status
	public static function getTransactionDeliveryStatus()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) )
			return;
		
		self::$database = new Database();
		
		$sqlQuery = "SELECT delivery_pickup_actual_date FROM `" . $_POST['class'] . "` WHERE id = " . $_POST['transactionID'];
		$resultSet = self::$database->query( $sqlQuery );
		$transaction = self::$database->getResultRow( $resultSet );
		
		if ( $transaction['delivery_pickup_actual_date'] != null )
			$transaction['delivery_pickup_status'] = 'all-delivered';
		else
		{
			$sqlQuery = "SELECT id FROM " . $_POST['class'] . "_item WHERE " . $_POST['class'] . "_id = " . $_POST['transactionID'] . " AND quantity != undelivered_quantity";
			$resultSet = self::$database->query( $sqlQuery );
			if ( self::$database->getResultCount( $resultSet ) > 0 )
				$transaction['delivery_pickup_status'] = 'partially-delivered';
			else
				$transaction['delivery_pickup_status'] = 'not-delivered';
		}

		echo json_encode( $transaction );
	}
	
	
	
	// create child inventory
	private static function createChildInventory( array $parentInventory, $itemInventoryID, $purchaseID, $itemID ) {
		$date = new DateTime();
			
		// check if a child inventory already exist for the parent inventory
		$sqlQuery = "SELECT * FROM inventory " .
					"WHERE parent_id = " . $itemInventoryID . " " .
					"AND (SUBSTRING(model,-12) = '(" . $date->format( 'Y-m-d' ) . ")' " .
					"OR SUBSTRING(model,-16) LIKE '(" . $date->format( 'Y-m-d' ) . " [%])')";
		$resultSet = self::$database->query( $sqlQuery );
		$childEntryCount = self::$database->getResultCount( $resultSet );
		if ( $childEntryCount == 0 ) {
			$childEntryExtension = '(' . $date->format( 'Y-m-d' ) . ')';
		} else {
			$childEntryExtension = '(' . $date->format( 'Y-m-d' ) . ' [' . $childEntryCount . '])';
		}
		
		
		if ( $parentInventory['description'] == null ) {
			$parentInventory['description'] = "NULL";
		} else {
			$parentInventory['description'] = "'" . $parentInventory['description'] . "'";
		}
		
		
		// create child model and copy parent model details to child model, set parent_id
		$sqlQuery = "INSERT INTO inventory VALUES (" .
					"NULL," .														// id, auto-generate
					$parentInventory['brand_id'] . ",'" .							// brand_id
					$parentInventory['model'] . " " . $childEntryExtension . "'," .	// model
					$parentInventory['description'] . "," .							// description
					$parentInventory['purchase_price'] . "," .						// purchase_price
					$parentInventory['selling_price'] . "," .						// selling_price
					$parentInventory['stock_count'] . "," .							// stock_count
					$parentInventory['reserved_stock'] . "," .						// reserved_stock, set initially to zero
					$parentInventory['id'] . ")";									// parent_id
		self::$database->query( $sqlQuery );
		$childEntryID = self::$database->getLastInsertID();
		
		
		// update active orders to point to child model
		$sqlQuery = "UPDATE v_active_order_items SET inventory_id = " . $childEntryID .
					" WHERE inventory_id = " . $parentInventory['id'];
		self::$database->query( $sqlQuery );
		
		// update active purchases, except currently opened purchase order, to point to child model
		$sqlQuery = "UPDATE v_active_purchase_items SET inventory_id = " . $childEntryID .
					" WHERE inventory_id = " . $parentInventory['id'] . " AND purchase_id != " . $purchaseID;
		self::$database->query( $sqlQuery );
		
		// update currently opened purchase to point to parent model
		$sqlQuery = "UPDATE purchase_item SET inventory_id = " . $parentInventory['id'] .
					" WHERE purchase_id = " . $purchaseID . " AND id = " . $itemID;
		self::$database->query( $sqlQuery );
		
		
		// log event
		$sqlQuery = 'SELECT name FROM inventory_brand WHERE id=' . $parentInventory['brand_id'];
		$resultSet = self::$database->query( $sqlQuery );
		$inventoryBrand = self::$database->getResultRow( $resultSet );
		
		EventLog::addEntry( self::$database, 'info', 'inventory', 'insert', 'new',
							'Child inventory "' . capitalizeWords( Filter::output( $parentInventory['model'] ) ) . " " . $childEntryExtension . '" ' .
							'was automatically <span class="event_log_action">added</span> to ' .
							'<a href="list_inventory_models.php?brandID=' . $parentInventory['brand_id'] . '">' .
							capitalizeWords( Filter::output( $inventoryBrand['name'] ) ) . '</a> brand to hold old stocks' );
		
		
		return $parentInventory['model'] . " " . $childEntryExtension;
	}
	
	
	
	// delete child inventory
	protected static function deleteChildInventory( $itemID ) {
		// check if child inventory can be deleted or reverted back
		$sqlQuery = "SELECT brand_id, model, stock_count, reserved_stock, parent_id FROM inventory WHERE id = " . $itemID;
		$resultSet = self::$database->query( $sqlQuery );
		$inventory = self::$database->getResultRow( $resultSet );
		
		if ( $inventory['stock_count'] == 0 && $inventory['reserved_stock'] == 0 )	{
			if ( $inventory['parent_id'] == null ) {
				// selected inventory is the parent
				$parentID = $itemID;
				
				// get latest child model info
				$sqlQuery = "SELECT * FROM inventory WHERE parent_id = " . $parentID . " ORDER BY id DESC LIMIT 0,1";
				$resultSet = self::$database->query( $sqlQuery );
				if ( self::$database->getResultCount( $resultSet ) > 0 ) {
					$childInventory = self::$database->getResultRow( $resultSet );
					$childID = $childInventory['id'];
					$childName = $childInventory['model'];
					
					// copy child to parent model
					$sqlQuery = "UPDATE inventory SET " .
								"purchase_price = " . $childInventory['purchase_price'] . ", " .
								"selling_price = " . $childInventory['selling_price'] . ", " .
								"stock_count = " . $childInventory['stock_count'] . ", " .
								"reserved_stock = " . $childInventory['reserved_stock'] .
								" WHERE id = " . $parentID;
					self::$database->query( $sqlQuery );
				} else {
					// no child
					$parentID = null;
				}
			} else {
				// selected inventory is the child
				$parentID = $inventory['parent_id'];
				$childID = $itemID;
				$childName = $inventory['model'];
			}
			
			
			if ( $parentID != null ) {
				// update present orders and purchases pointing to child model to parent model
				$sqlQuery = "UPDATE order_item SET inventory_id = " . $parentID .
							" WHERE inventory_id = " . $childID;
				self::$database->query( $sqlQuery );
				
				$sqlQuery = "UPDATE purchase_item SET inventory_id = " . $parentID .
							" WHERE inventory_id = " . $childID;
				self::$database->query( $sqlQuery );
				
				// delete child model
				$sqlQuery = "DELETE FROM inventory WHERE id = " . $childID;
				self::$database->query( $sqlQuery );
				
				
				// log event
				$sqlQuery = 'SELECT name FROM inventory_brand WHERE id=' . $inventory['brand_id'];
				$resultSet = self::$database->query( $sqlQuery );
				$inventoryBrand = self::$database->getResultRow( $resultSet );
				
				EventLog::addEntry( self::$database, 'info', 'inventory', 'delete', 'removed',
									'Child inventory "' . capitalizeWords( Filter::output( $childName ) ) . '" ' .
									'was automatically <span class="event_log_action">deleted</span> from ' .
									'<a href="list_inventory_models.php?brandID=' . $inventory['brand_id'] . '">' .
									capitalizeWords( Filter::output( $inventoryBrand['name'] ) ) . '</a> brand because the stock is now zero' );
			}
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display missing invoice numbers; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function showMissingInvoice() {
		self::$database = new Database;
		
		// get parameters
		if (!isset($_POST['criteria'])) {
			$table = 'order';
		} elseif ($_POST['criteria'] != 'order' && $_POST['criteria'] != 'purchase' ) {
			$table = 'order';
		} else {
			$table = $_POST['criteria'];
		}
		
		if ($table == 'order') {
			$tableHeading = 'Order';
		} else {
			$tableHeading = 'Purchase';
		}
		
		if (!isset($_POST['invoice_type'])) {
			$invoiceType = 'DR';
		} else {
			$invoiceType = $_POST['invoice_type'];
		}
		
		if ($invoiceType == 'DR') {
			$field = 'delivery_receipt';
		} else {
			$field = 'sales_invoice';
		}
		
		// check unused invoice numbers
		$sqlQuery = "SELECT CONCAT(z.expected, IF(z.got-1>z.expected, CONCAT(' thru ',z.got-1), '')) AS missing " .
					"FROM (SELECT @rownum:=@rownum+1 AS expected, IF(@rownum=$field, 0, @rownum:=$field) AS got " .
					"FROM (SELECT @rownum:=0) AS a JOIN `$table` ORDER BY $field * 1) AS z " .
					"WHERE z.got!=0";
		$resultSet = self::$database->query($sqlQuery);
		
		echo '<table class="item_input_table" id="missing_invoice">' .
			 "<thead><tr><th>Unused $invoiceType Numbers in {$tableHeading}s</th></tr></thead><tbody>";
		if (self::$database->getResultCount($resultSet) == 0) {
			echo '<tr class="item_row"><td>No unused ' . $invoiceType . ' numbers found.</td></tr>';
		} else {
			while ($order = self::$database->getResultRow($resultSet)) {
				echo '<tr class="item_row"><td>' . $invoiceType . ' ' . $order['missing'] . '</td></tr>';
			}
		}
		echo '</tbody></table>';
		
		// check orders with invalid SI/DR numbers
		$sqlQuery = "SELECT id, $field FROM `$table` WHERE ($field * 1 = 0) AND $field IS NOT NULL";
		$resultSet = self::$database->query($sqlQuery);
		
		echo '<table class="item_input_table" id="invalid_invoice">' .
			 "<thead><tr><th>{$tableHeading}s with Invalid $invoiceType Numbers</th><th>$invoiceType Number</th></tr></thead><tbody>";
		if (self::$database->getResultCount($resultSet) == 0) {
			echo '<tr class="item_row"><td colspan="2">No ' . $table . 's with invalid ' . $invoiceType . ' numbers found.</td></tr>';
		} else {
			while ($order = self::$database->getResultRow($resultSet)) {
				echo '<tr class="item_row">' . 
					 '<td>' . $tableHeading . ' No. <a href="view_order_details.php?id=' . $order['id'] . '">' .$order['id'] . '</a></td><td>' .
					 $order[$field] . '</td></tr>';
			}
		}
		echo '</tbody></table>';
		
		echo '<br style="clear: both" /><br /><br />';
	}
}

?>

<?php
// note: this class requires scripts/order.js


// ---------------------------------------------------------------------------------------------------------------
// class definition for orders and order handling
// ---------------------------------------------------------------------------------------------------------------
class Order extends Transaction
{
	const MAX_ORDER_ITEMS     = 20;               // maximum number of order items
	const VISIBLE_ORDER_ITEMS = 5;                // number of visible order items, must be less than MAX_ORDER_ITEMS
	
	
	// -----------------------------------------------------------------------------------------------------------
	// display order form
	// -----------------------------------------------------------------------------------------------------------
	public static function showInputForm( $id = null, $isItemEditable = true ) {
		// instantiate JavaScript objects
		echo '<script type="text/javascript">' .
			 "<!--\n" .
			 "var order = new Order();\n" .
			 '// -->' .
			 '</script>';
		
		echo '<form name="' . ($id == null ? 'add' : 'edit') .
			 '_order" method="post" action="view_order_details.php" autocomplete="off" onsubmit="return order.validateInputForm(' .
			 ($isItemEditable ? 'true' : 'false') . ')" onreset="return confirmReset(\'order.resetInputForm\')">';
		
		if ($id != null) {
			// existing order ID, get order and customer info
			self::$database = new Database();
			$resultSet      = self::$database->query("SELECT * FROM `order` WHERE id = $id");
			if ($resultSet != null) {
				$order      = self::$database->getResultRow($resultSet);
				$customerID = $order['customer_id'];
			} else {                    // cannot determine order ID
				$customerID = null;
				$order      = null;
				$id         = null;
			}
		} else {
			// new order
			$customerID = null;
			$order      = null;
		}
		
		// display customer field set
		Customer::showInputFieldSet($customerID, $order);
		
		// display order field set
		self::showInputFieldSet($id, $order, $isItemEditable);
		
		if ($isItemEditable) {
			// display payment field set
			if ($id != null) {
				Payment::showInputFieldSet($id, self::$database, 'order', $order['payment_term'], $order['interest']);
			} else {
				Payment::showInputFieldSet();
			}
		}
		
		// display remarks field set
		self::showRemarksInputFieldSet($order['agent_id'], $order['remarks']);
		
		// display submit/reset/cancel buttons
		self::showButtons(ButtonSet::SUBMIT_RESET_CANCEL);
		
		echo '</form>';
		
		// call JavaScript functions for initialization
		echo '<script type="text/javascript">';
		echo "<!--\n";
		
		if ($customerID != null) {
			// fill and lock customer fieldset
			echo "origPersonID = $customerID;\n" .
				 "lockInputFields();\n" .
				"$('#customer_query_mode').val('locked');\n" .
				"$('#credit_limit').attr('disabled','disabled');\n" .
				"$('#credit_terms').attr('disabled','disabled');\n";
		}
		
		if ($isItemEditable) {
			echo "order.toggleDeliveryPickupDateLabel();\n" .
				 "order.toggleTaxFieldsDisplay( false );\n" .
				 "order.loadFormEvents();\n";
		}
		
		echo '// -->';
		echo '</script>';
	}
	
	
	// -----------------------------------------------------------------------------------------------------------
	// display order form field set
	// -----------------------------------------------------------------------------------------------------------
	protected static function showInputFieldSet( $id = null, array $order = null, $isItemEditable = true ) {
		if (self::$database == null) {            // no database instance yet, create database instance
			self::$database = new Database();
		}
		
		if ($id != null && $order == null) {      // order array not yet initialized, get order info
			$resultSet = self::$database->query("SELECT * FROM `order` WHERE id = $id");
			$order     = self::$database->getResultRow($resultSet);
		}
		
		echo '<fieldset><legend>Order Info</legend><section>';
		
		if ($id != null) {
			echo '<div>' .
				 '<label for="order_number">Order No:</label>' .
				 '<output name="order_number">' . $order['id'] . '</output>' .
				 '</div>';
		}
		
		// display order's basic info
		?>
		<div>
			<label for="order_date">Order Date:</label>
			<output name="order_date" id="order_date"><?php
			if ($id == null) {
				echo date("F j, Y l");
			} else {
				echo dateFormatOutput($order['order_date']);
			}
		?></output>
			<input type="hidden" name="order_query_mode" id="order_query_mode" value="<?php echo ($id != null ? 'edit' : 'new') ?>" />
			<input type="hidden" name="order_id" id="order_id" value="<?php echo ($id != null ? $order['id'] : '0') ?>" />
		</div>
				
		<div>
			<label for="invoice_type">Tracking No:</label>
			<select name="invoice_type" id="invoice_type" class="form_input_select">
				<option value="SI"<?php echo($id != null ? ($order['sales_invoice'] != null ? ' selected="selected"' : '')
														   : '') ?>>Sales Invoice (SI)</option>
				<option value="DR"<?php echo($id != null ? ($order['delivery_receipt'] != null ? ' selected="selected"' : '')
														   : '') ?>>Delivery Receipt (DR)</option>
				</select>
				<input type="hidden" name="invoice_type_orig" id="invoice_type_orig" value="<?php
					   echo ($id != null ? ($order['sales_invoice'] != null ? 'SI' : 'DR') : 'null') ?>" />
			</div>
			
			<div>
				<label for="tracking_number"></label>
				<input type="text" name="tracking_number" id="tracking_number" required="required"<?php
					   echo ($id != null ? ' value="' . ($order['sales_invoice'] != null ? Filter::reinput($order['sales_invoice']) :
							Filter::reinput($order['delivery_receipt'])) . '"' : '') ?> />
				<input type="hidden" name="tracking_number_orig" id="tracking_number_orig" value="<?php
					   echo ($id != null ? ($order['sales_invoice'] != null ? $order['sales_invoice'] : $order['delivery_receipt']) : 'null') ?>" />
			</div>
		</section>

        <?php
		if (sizeof($GLOBALS['BUSINESS_UNITS']) > 0 || sizeof($GLOBALS['BRANCHES']) > 0) {
			echo '<section>';
			
			if (sizeof($GLOBALS['BUSINESS_UNITS']) > 0) {
				echo '<div>' .
					 '<label for="business_unit">Business Unit:</label>' .
					 '<select name="business_unit" id="business_unit" class="form_input_select">';
				foreach ($GLOBALS['BUSINESS_UNITS'] as $businessUnit) {
					echo '<option value="' . $businessUnit . '"' .
						 ($id != null ? ($order['business_unit'] == $businessUnit ? ' selected="selected"' : '') : '') .
						 '>' . $businessUnit . '</option>';
				}
				echo '</select></div>';
			}
			
			if (sizeof($GLOBALS['BRANCHES']) > 0) {
				echo '<div>' .
					 '<label for="branch">Branch:</label>' .
					 '<select name="branch" id="branch" class="form_input_select">';
				foreach ($GLOBALS['BRANCHES'] as $branch) {
					echo '<option value="' . $branch . '"' .
						 ($id != null ? ($order['branch'] == $branch ? ' selected="selected"' : '') : '') .
						 '>' . $branch . '</option>';
				}
				echo '</select></div>';
			}
			
			echo '</section>';
		}
		
		// display order items
		if (!$isItemEditable) {
			echo '<section><div id="horizontal_message">Items are already delivered or payment is already made.<br />' .
				 'Return delivered items and cancel all payments to edit Order Items and Payment details.</div></section>';
			return;
		}
		
		echo '<section><div>' .
			 '<label for="transaction_type">Transaction Type:</label>';
		if ($order['delivery_pickup_actual_date'] == null) {
			echo '<select name="transaction_type" id="transaction_type" class="form_input_select">' .
				 '<option value="delivery"' . ($id != null ? ($order['transaction_type'] == 'delivery' ? ' selected="selected"' : '') : '') . 
				 '>Delivery</option>' .
				 '<option value="pick-up"' . ($id != null ? ($order['transaction_type'] == 'pick-up' ? ' selected="selected"' : '') : '') .
				 '>Pick-up</option>' .
				 '</select>';
		} else {
			echo '<output>' . ucfirst($order['transaction_type']) . '</output>' .
				 '<input type="hidden" name="transaction_type" id="transaction_type" value="' . $order['transaction_type'] . '" />';
		}
		echo '</div>';
		
		echo '<div>' .
			 '<label for="delivery_pickup_date" id="delivery_pickup_date_label" class="required_label">Target Delivery Date:</label>';
		if ($order['delivery_pickup_actual_date'] == null) {
			echo '<input type="text" name="delivery_pickup_date" id="delivery_pickup_date" class="datepicker" size="30" ' . 
						'maxlength="30" required="required"' .
						($id != null ? ' value="' . dateFormatOutput(Filter::reinput($order['delivery_pickup_target_date']), 'F j, Y, D') . '"' : '') .
						"/>" .
				 '<span class="span_label">Time:</span>' .
				 '<select name="delivery_pickup_time" id="delivery_pickup_time">' .
				 '<option value="0:00:00"' . ($id != null ? (dateFormatOutput($order['delivery_pickup_target_date'], 'G:i:s') == "0:00:00" ?
											  ' selected="selected"' : '') : '') . '>----------&nbsp;&nbsp;</option>';
			
			// construct time selection
			for ($i = WORKING_HOURS_START; $i <= WORKING_HOURS_END; $i++) {
				echo '<option value="' . $i . ':00:00"';
				if ($id != null) {
					if (dateFormatOutput($order['delivery_pickup_target_date'], 'G:i:s') == $i . ':00:00') {
						echo ' selected="selected"';
					}
				}
				echo '>';
				
				$hour = $i % 12;
				if ($hour == 0) {
					$hour = 12;
				}
				
				if (($i / 12) < 1) {
					echo $hour . ':00 AM';
				} else {
					echo $hour . ':00 PM';
				}
				
				echo '</option>';
			}
			
			echo '</select>';
		} else {
			echo '<output>' . dateFormatOutput($order['delivery_pickup_target_date']) .
				 ($order['transaction_type'] == 'delivery' ? ' (Delivered: ' : ' (Picked-up: ') .
				 dateFormatOutput($order['delivery_pickup_actual_date'], 'F j, Y, D') .
				 ')</output>' .
				 '<input type="hidden" name="delivery_pickup_date" id="delivery_pickup_date" value="' .
				 dateFormatOutput(Filter::reinput($order['delivery_pickup_target_date']), 'F j, Y, D') . '" />' .
				 '<input type="hidden" name="delivery_pickup_time" id="delivery_pickup_time" value="' .
				 dateFormatOutput($order['delivery_pickup_target_date'], 'G:i:s') . '" />' .
				 '<input type="hidden" name="delivery_pickup_actual_time" id="delivery_pickup_actual_time" value="' .
				 $order['delivery_pickup_actual_date'] . '" />';
		}
		echo '</div></section>';
	
		echo '<section><table class="item_input_table"><thead><tr>' .
			 '<th></th>' .
			 '<th>Brand:</th>' .
			 '<th>Model:</th>' .
			 '<th>Selling Price:</th>' .
			 '<th>Quantity:</th>' .
			 '<th class="item_discount_column">SI/DR Price:</th>' .
			 '<th class="item_subtotal_column">SI/DR Subtotal:</th>' .
			 '<th class="item_discount_column">Net Price:</th>' .
			 '<th>Net Subtotal:</th>' .
			 '</tr></thead><tbody>';
		
		// get inventory brands
		$inventoryBrandID   = array();
		$inventoryBrandName = array();
		$resultSet = self::$database->query("SELECT id, name FROM inventory_brand ORDER BY name ASC");
		if (self::$database->getResultCount($resultSet) > 0) {
			while ($inventoryBrand = self::$database->getResultRow($resultSet)) {
				array_push($inventoryBrandID, $inventoryBrand['id']);
				array_push($inventoryBrandName, capitalizeWords(Filter::output($inventoryBrand['name'])));
			}
		}
		
		// get item list if existing order
		$item = array();
		if ($id != null) {
			$resultSet = self::$database->query(
				"SELECT * FROM order_item INNER JOIN inventory ON order_item.inventory_id = inventory.id WHERE order_id = {$order['id']}");
			
			$itemCount    = self::$database->getResultCount($resultSet);
			$visibleItems = $itemCount;
			
			while ($itemList = self::$database->getResultRow($resultSet)) {
				array_push($item, $itemList);
			}
		} else {
			$itemCount    = 0;
			$visibleItems = self::VISIBLE_ORDER_ITEMS;
		}
		
		$totalSidrAmount = 0;
		$totalNetAmount  = 0;
		
		// display item table
		for ($i = 1; $i <= self::MAX_ORDER_ITEMS; $i++) {
			// display item row
			if ($id != null && $i <= $itemCount) {
				self::showItemListRow($id, $i, $visibleItems, $inventoryBrandID, $inventoryBrandName, $itemCount, $item[$i - 1], false);
			} else {
				self::showItemListRow(null, $i, $visibleItems, $inventoryBrandID, $inventoryBrandName, $itemCount, null, false);
			}
			
			// perform item computation if existing order
			if ($id != null && $i <= $itemCount) {
				echo '<script type="text/javascript">';
				echo "<!--\n";
				echo "order.inventory.loadSellingPriceAndStock($i, $('#item_model_$i').val(), '" . ($id != null ? $id : '0') . "');\n";
				
				$sidrSubtotal = numberFormat(((double) $item[$i - 1]['sidr_price'] * (int) $item[$i - 1]['quantity']), 'float', 3, '.', '', true);
				$netSubtotal  = numberFormat(((double) $item[$i - 1]['net_price'] * (int) $item[$i - 1]['quantity']), 'float', 3, '.', '', true);
				
				echo "order.validateItemBrand($i);\n" .
					 "order.validateItemModel($i, true);\n" .
					 "$('#item_price_'+$i).val($('#item_price_'+$i).attr('defaultValue'));\n" .
					 "$('#item_quantity_'+$i).val($('#item_quantity_'+$i).attr('defaultValue'));\n" .
					 "if (parseInt($('#item_quantity_'+$i).val()) > parseInt($('#item_max_quantity_'+$i).val())) {\n" .
					 "$('#item_quantity_'+$i).css('color', badInputStyle);\n" .
					 "}\n" .
					 "$('#item_sidr_price_'+$i).val($('#item_sidr_price_'+$i).attr('defaultValue'));\n" .
					 "$('#item_net_price_'+$i).val($('#item_net_price_'+$i).attr('defaultValue'));\n" .
					 "$('#item_sidr_subtotal_'+$i).val('$sidrSubtotal');\n" .
					 "$('#item_net_subtotal_'+$i).val('$netSubtotal');\n" .
					 "order.validateItemPrice($i);\n";
				
				if ((int) $item[$i - 1]['undelivered_quantity'] < (int) $item[$i - 1]['quantity']) {
					echo "$('#item_brand_'+$i).attr('disabled', 'disabled');\n" .
						 "$('#item_model_'+$i).attr('disabled', 'disabled');\n" .
						 "$('#item_price_'+$i).attr('disabled', 'disabled');\n" .
						 "$('#item_quantity_'+$i).attr('disabled', 'disabled');\n" .
						 "$('#item_sidr_price_'+$i).attr('disabled', 'disabled');\n" .
						 "$('#item_net_price_'+$i).attr('disabled', 'disabled');\n";
				}
				
				echo '// -->';
				echo '</script>';
				
				$totalSidrAmount = $totalSidrAmount + $sidrSubtotal;
				$totalNetAmount  = $totalNetAmount + $netSubtotal;
			}
		}
		
		// display totals
		?>
		<tr>
			<td></td>
			<td colspan="8">
				<div class="multi_row_links">
					<span id="add_item_row_link"><a href="javascript:void(0)" onclick="order.addItem()">[ add ]</a></span>
					<span id="item_row_link_separator" style="display:none"> | </span>
					<span id="remove_item_row_link" style="display:none"><a href="javascript:void(0)" onclick="order.removeItem()">[ remove ]</a></span>
				</div>
			</td>
		</tr>
				
		<tr>
			<td colspan="6"><label for="total_amount">Total: <?= CURRENCY ?></label></td>
			<td><input type="text" name="total_sidr_amount" id="total_sidr_amount" class="number output_field" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($totalSidrAmount), 'float', 3, '.', '', true) : '0.000') ?>" disabled="disabled" /></td>
			<td></td>
			<td><input type="text" name="total_net_amount" id="total_net_amount" class="number output_field" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($totalNetAmount), 'float', 3, '.', '', true) : '0.000') ?>" disabled="disabled" /></td>
		</tr>
				
		<tr>
			<td colspan="9"><br /></td>
		<tr>
				
		<tr class="tax_fields">
			<td colspan="8"><label for="total_sales">Total Sales: <?= CURRENCY ?></label></td>
			<td><input type="text" name="total_sales" id="total_sales" class="number order_item_total output_field" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($order['total_sales_amount']), 'float', 3, '.', '', true) : '0.000') 
				?>" disabled="disabled" /></td>
			<td></td>
		</tr>
		<tr class="tax_fields">
			<td colspan="8"><label for="value_added_tax">+ Value-Added Tax: <?= CURRENCY ?></label></td>
			<td><input type="text" name="value_added_tax" id="value_added_tax" class="number order_item_total output_field" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($order['value_added_tax']), 'float', 3, '.', '', true) : '0.000')
				?>" disabled="disabled" /></td>
		</tr>
		<tr class="tax_fields">
			<td colspan="9"><br /></td>
		</tr>
		
		<tr>
			<td colspan="8"><label for="sidr_amount" class="important_label">SI/DR Amount: <?= CURRENCY ?></label></td>
			<td><input type="text" name="sidr_amount" id="sidr_amount" class="number order_item_total output_field" value="<?php
				echo ($id != null ? numberFormat($totalSidrAmount, 'float', 3, '.', '', true) : '0.000') ?>" disabled="disabled" /></td>
		</tr>
		
		<tr class="tax_fields">
			<td colspan="9"><br /></td>
		</tr>
		<tr class="tax_fields">
			<td colspan="8"><label for="withholding_tax">- Withholding Tax: <?= CURRENCY ?></label></td>
			<td><input type="text" name="withholding_tax" id="withholding_tax" class="number order_item_total" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($order['withholding_tax']), 'float', 3, '.', '', true) : '0.000')
				?>" disabled="disabled" /></td>
		</tr>
		<tr class="tax_fields">
			<td colspan="9"><br /></td>
		</tr>
		<tr>
			<td colspan="8"><label for="net_amount" class="important_label">OFC Net Amount: <?= CURRENCY ?></label></td>
			<td><input type="text" name="net_amount" id="net_amount" class="number order_item_total output_field" value="<?php
				echo ($id != null ? numberFormat(($totalNetAmount - $order['withholding_tax']), 'float', 3, '.', '', true) : '0.000')
				?>" disabled="disabled" /></td>
		</tr>
		</tbody>
		</table>
		</section>

		<script type="text/javascript">
		<!--
			order.setMaxItems(<?= self::MAX_ORDER_ITEMS ?>);
			order.setInitialVisibleItems(<?= $visibleItems ?>);
			order.payment.setVAT('<?= VAT_PERCENT ?>');
			<?php
			if ($id != null) {
				echo "$('#total_sales').attr('defaultValue', $('#total_sales').val());\n" .
					 "$('#value_added_tax').attr('defaultValue', $('#value_added_tax').val());\n" .
					 "$('#sidr_amount').attr('defaultValue', $('#sidr_amount').val());\n" .
					 "$('#withholding_tax').attr('defaultValue', $('#withholding_tax').val());\n" .
					 "$('#withholding_tax').attr('disabled', '');\n" .
					 "$('#net_amount').attr('defaultValue', $('#net_amount').val());\n" .
					 "order.payment.totalSIDRamount = $totalSidrAmount;\n" .
					 "order.payment.totalNetAmount = $totalNetAmount;\n";
			}
			?>
		// -->
		</script>
		</fieldset>
		<?php
	}
	
	
	// -----------------------------------------------------------------------------------------------------------
	// save order info to database; returns order ID
	// -----------------------------------------------------------------------------------------------------------
	public function save( $customerID ) {
		if ($_POST['order_query_mode'] == "edit" && !isset($_POST['transaction_type'])) {
			$isItemEditable = false;
		} else {
			$isItemEditable = true;
		}
		
		// format input values
		$this->prepareTransactionData($isItemEditable);
		
		if ($_POST['order_query_mode'] == 'new') {
			// save new order
			$sqlQuery = "INSERT INTO `order` VALUES (" .
						"NULL," .                                                            	// id, auto-generate
						"$this->salesInvoice," .                                          		// sales_invoice
						"$this->deliveryReceipt," .                                       		// delivery_receipt
						"$customerID," .                                                 		// customer_id
						"'$this->transactionDate'," .                                      		// order_date
						($this->businessUnit == null ? "NULL," : "'$this->businessUnit',") .    // business unit
						($this->branch == null ? "NULL," : "'$this->branch',") .                // branch
						"'$this->transactionType'," .                                     		// transaction_type
						"'$this->deliveryPickupTargetDate'," .                             		// delivery_pickup_target_date
						"NULL," .                                                           	// delivery_pickup_actual_date
						"'$this->paymentTerm'," .                                          		// payment_term
						"$this->transactionDiscount," .                                   		// order_discount
						"$this->totalSales," .                                            		// total_sales_amount
						"$this->valueAddedTax," .                                         		// value_added_tax
						"$this->withholdingTax," .                                        		// withholding_tax
						"$this->interest," .                                              		// interest
						"$this->receiptAmount," .                                         		// receipt_amount
						"$this->amountReceivable," .                                      		// amount_receivable
						"$this->amountReceivable," .                                      		// balance, set initially to amount_receivable
						"0.000," .                                                           	// waived_balance, set to 0.00
						($this->agentID == null ? "NULL," : "$this->agentID,") .           		// agent_id
						($this->remarks == null ? "NULL," : "'$this->remarks',") .     			// remarks
						"NULL," .                                                           	// canceled_date
						"NULL)";                                                             	// cleared_date
			
			self::$database->query($sqlQuery);
			
			// get generated order ID
			$this->id = self::$database->getLastInsertID();
			
			// save order items
			$this->saveItems(self::MAX_ORDER_ITEMS);
			
			// save payment schedule
			$this->payment = new Payment();
			$this->payment->saveSchedule(self::$database, $this->getInstanceClassName($this), $this->id, $this->paymentTerm);
			
			// get customer name to log
			$resultSet = self::$database->query("SELECT name FROM customer WHERE id=$customerID");
			$customer  = self::$database->getResultRow($resultSet);
			$orderNumber = $this->id;
			if ($this->salesInvoice != "NULL") {
				$invoiceNumber = 'SI ' . Filter::input($_POST['tracking_number']);
			} else {
				$invoiceNumber = 'DR ' . Filter::input($_POST['tracking_number']);
			}
			
			// log event
			EventLog::addEntry(self::$database, 'info', 'order', 'insert', 'new',
							   '<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $orderNumber . '">' .
							   $orderNumber . '</a> (' . $invoiceNumber . '): </span>' .
							   'New order from <a href="view_customer_details.php?id=' . $customerID . '">' .
							   capitalizeWords(Filter::output($customer['name'])) . '</a>');
			
		} else {
			// existing order, update records
			$this->id = $_POST['order_id'];
			
			// update order
			if ($isItemEditable) {
				$sqlQuery = "UPDATE `order` SET " .
							"sales_invoice=$this->salesInvoice," .                                    				// sales_invoice
							"delivery_receipt=$this->deliveryReceipt," .                              				// delivery_receipt
							"customer_id=$customerID," .                                              				// customer_id
							"business_unit=" . ($this->businessUnit == null ? "NULL," : "'$this->businessUnit',") . // business unit
							"branch=" . ($this->branch == null ? "NULL," : "'$this->branch',") .  					// branch
							"transaction_type='$this->transactionType'," .                            				// transaction_type
							"delivery_pickup_target_date='$this->deliveryPickupTargetDate'," .        				// delivery_pickup_target_date
							"delivery_pickup_actual_date=" . ($this->deliveryPickupActualDate == null ?
															  "NULL," : "'$this->deliveryPickupActualDate',") .		// delivery_pickup_actual_date
							"payment_term='$this->paymentTerm'," .                                    				// payment_term
							"order_discount=$this->transactionDiscount," .                            				// order_discount
							"total_sales_amount=$this->totalSales," .                                 				// total_sales_amount
							"value_added_tax=$this->valueAddedTax," .                                 				// value_added_tax
							"withholding_tax=$this->withholdingTax," .                                				// withholding_tax
							"interest=$this->interest," .                                             				// interest
							"receipt_amount=$this->receiptAmount," .                                  				// receipt_amount
							"amount_receivable=$this->amountReceivable," .                            				// amount_receivable
							"balance=$this->amountReceivable," .                                      				// balance, reset to amount receivable
							"waived_balance=0.000," .                                                         		// waived_balance, reset to 0.00
							"agent_id=" . ($this->agentID == null ? "NULL," : "$this->agentID,") .          		// agent_id
							"remarks=" . ($this->remarks == null ? "NULL" : "'$this->remarks'") .     				// remarks
							" WHERE id=" . $this->id;
				
			} else {
				$sqlQuery = "UPDATE `order` SET " .
							"sales_invoice=$this->salesInvoice," .                                    				// sales_invoice
							"delivery_receipt=$this->deliveryReceipt," .                              				// delivery_receipt
							"customer_id=$customerID," .                                              				// customer_id
							"business_unit=" . ($this->businessUnit == null ? "NULL," : "'$this->businessUnit',") . // business unit
							"branch=" . ($this->branch == null ? "NULL," : "'$this->branch',") .  					// branch
							"agent_id=" . ($this->agentID == null ? "NULL," : "$this->agentID,") .          		// agent_id
							"remarks=" . ($this->remarks == null ? "NULL" : "'$this->remarks'") .     				// remarks
							" WHERE id=" . $this->id;
				
			}
			
			self::$database->query($sqlQuery);
			
			// save order items and payment schedule
			if ($isItemEditable) {
				$this->saveItems(self::MAX_ORDER_ITEMS);
				$this->payment = new Payment();
				$this->payment->saveSchedule(self::$database, $this->getInstanceClassName($this), $this->id, $this->paymentTerm);
			}
			
			// get customer name to log
			$resultSet = self::$database->query("SELECT name FROM customer WHERE id=$customerID");
			$customer  = self::$database->getResultRow($resultSet);
			if ($this->salesInvoice != "NULL") {
				$invoiceNumber = 'SI ' . Filter::input($_POST['tracking_number']);
			} else {
				$invoiceNumber = 'DR ' . Filter::input($_POST['tracking_number']);
			}
			
			// log event
			EventLog::addEntry(self::$database, 'info', 'order', 'update', 'modified',
							   '<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $this->id . '">' .
							   $this->id . '</a> (' . $invoiceNumber . '): </span>Order from ' .
							   '<a href="view_customer_details.php?id=' . $customerID . '">' . capitalizeWords(Filter::output($customer['name'])) . '</a> ' .
							   'was <span class="event_log_action">modified</span>');
		}
		
		return $this->id;
	}
	
	
	// -----------------------------------------------------------------------------------------------------------
	// display tasks for order list
	// -----------------------------------------------------------------------------------------------------------
	public static function showListTasks() {
		// get parameters
		if (!isset($_GET['criteria'])) {
			$criteria = "recent-orders";
		} else {
			$criteria = $_GET['criteria'];
		}
		
		?>
		<div id="tasks">
		<ul>
			<li id="task_add_order"><a href="add_order.php"><img src="images/task_buttons/add.png" />Add Order</a></li>
			<li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php
				$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br />' .
						 		 'This may take a few minutes. The system might not respond to other users while processing your request.<br /><br /><br />' .
						 		 '<div id="dialog_buttons">' .
						 		 '<input type="button" value="Yes" onclick="exportToExcelConfirm(' .
						 		 '\\\'data=order_list&criteria=' . $criteria . '\\\')" />' .
						 		 '<input type="button" value="No" onclick="hideDialog()" />' .
						 		 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
		?>','prompt')"><img src="images/task_buttons/export_to_excel.png" />Export to Excel...</a></li>
		</ul>
		</div>
		</div>
		<?php
	}
	
	
	// -----------------------------------------------------------------------------------------------------------
	// display list of orders; ajax function
	// -----------------------------------------------------------------------------------------------------------
	public static function showList() {
		self::$database = new Database;
		
		// get parameters
		if (!isset($_POST['criteria'])) {
			$criteria = 'recent-orders';
		} else {
			$criteria = $_POST['criteria'];
		}
		
		if (!isset($_POST['sortColumn'])) {
			$sortColumn = 'id';
		} else {
			$sortColumn = $_POST['sortColumn'];
		}
		
		if (!isset($_POST['sortMethod'])) {
			$sortMethod = 'DESC';
		} else {
			$sortMethod = $_POST['sortMethod'];
		}
		
		if (!isset($_POST['page'])) {
			$page = 1;
		} else {
			$page = $_POST['page'];
		}
		
		if (!isset($_POST['itemsPerPage'])) {
			$itemsPerPage = ITEMS_PER_PAGE;
		} else {
			$itemsPerPage = $_POST['itemsPerPage'];
		}
		
		if (!isset($_POST['filterName'])) {
			$filterName = null;
		} else {
			$filterName = $_POST['filterName'];
		}
		
		if (!isset($_POST['filterValue'])) {
			$filterValue = null;
		} else {
			$filterValue = $_POST['filterValue'];
		}
		
		$offset = ($page * $itemsPerPage) - $itemsPerPage;
		
		// set condition
		switch ($criteria) {
			case 'recent-orders':
				$condition = " WHERE canceled_date IS NULL AND cleared_date IS NULL";
				break;
			case 'orders-to-deliver':
				$condition = " WHERE transaction_type = 'delivery' AND delivery_pickup_actual_date IS NULL AND canceled_date IS NULL";
				break;
			case 'orders-awaiting-pickup':
				$condition = " WHERE transaction_type = 'pick-up' AND delivery_pickup_actual_date IS NULL AND canceled_date IS NULL";
				break;
			case 'payments-to-collect':
				$condition = " WHERE v_accounts_receivable.amount_receivable > 0 AND canceled_date IS NULL";
				break;
			case 'payments-to-clear':
				$condition = " WHERE v_accounts_pdc_receivable.pdc_receivable > 0 AND canceled_date IS NULL";
				break;
			case 'payments-to-collect-and-clear':
				$condition = " WHERE (v_accounts_receivable.amount_receivable > 0 OR v_accounts_pdc_receivable.pdc_receivable > 0) AND canceled_date IS NULL";
				break;
			case 'rebates-to-issue':
				$condition = " WHERE v_rebate_payable.rebate_payable > 0 AND canceled_date IS NULL";
				break;
			case 'rebates-to-clear':
				$condition = " WHERE v_rebate_pdc_payable.pdc_rebate_payable > 0 AND canceled_date IS NULL";
				break;
			case 'waived-balance':
				$condition = " WHERE waived_balance > 0 AND canceled_date IS NULL";
				break;
			case 'orders-to-clear':
				$condition = " WHERE delivery_pickup_actual_date IS NOT NULL AND v_accounts_receivable.amount_receivable = 0 AND " .
							 "v_accounts_pdc_receivable.pdc_receivable IS NULL AND canceled_date IS NULL AND cleared_date IS NULL";
				break;
			case 'cleared-orders':
				$condition = " WHERE cleared_date IS NOT NULL";
				break;
			case 'canceled-orders':
				$condition = " WHERE canceled_date IS NOT NULL";
				break;
			default:
				$condition = "";
				break;
		}
		
		if ($filterName != null && $filterValue != null) {
			if ($condition == '') {
				$condition = " WHERE ";
			} else {
				$condition = $condition . " AND ";
			}
			
			$condition = $condition . "`order`.$filterName = '$filterValue'";
		}
		
		// limit viewable orders by branch
		if ($condition == '') {
			$condition = " WHERE ";
		} else {
			$condition = $condition . " AND ";
		}
		$condition = $condition . User::getQueryForBranch(self::$database);
		
		// count results prior to main query
		$sqlQuery    = "SELECT COUNT(*) AS count FROM `order` " .
					   "INNER JOIN customer ON `order`.customer_id = customer.id " .
					   "LEFT JOIN v_accounts_receivable ON `order`.id = v_accounts_receivable.order_id " .
					   "LEFT JOIN v_accounts_pdc_receivable ON `order`.id = v_accounts_pdc_receivable.order_id " .
					   "LEFT JOIN v_rebate_payable ON `order`.id = v_rebate_payable.order_id " .
					   "LEFT JOIN v_rebate_pdc_payable ON `order`.id = v_rebate_pdc_payable.order_id $condition";
		$resultSet   = self::$database->query($sqlQuery);
		$resultCount = self::$database->getResultRow($resultSet);
		$resultCount = $resultCount['count'];
		
		// construct main query
		$sqlQuery = "SELECT `order`.*, " .
					"v_accounts_receivable.amount_receivable AS accounts_receivable, " .
					"IF(v_accounts_pdc_receivable.pdc_receivable IS NULL,0,v_accounts_pdc_receivable.pdc_receivable) AS pdc_receivable, " .
					"(v_accounts_receivable.amount_receivable + " .
					"IF(v_accounts_pdc_receivable.pdc_receivable IS NULL,0,v_accounts_pdc_receivable.pdc_receivable))" .
					"AS total_receivable, " .
					"v_rebate_payable.rebate_payable, " .
					"IF(v_rebate_pdc_payable.pdc_rebate_payable IS NULL,0,v_rebate_pdc_payable.pdc_rebate_payable) AS pdc_rebate_payable, " .
					"(v_rebate_payable.rebate_payable + " .
					"IF(v_rebate_pdc_payable.pdc_rebate_payable IS NULL,0,v_rebate_pdc_payable.pdc_rebate_payable))" .
					"AS total_rebate_payable, " .
					"`order`.amount_receivable - `order`.waived_balance AS remaining_balance, " .
					"waived_balance, " .
					"customer.name AS customer, " .
					"customer.credit_terms AS credit_terms, " .
					"IF(SUM(order_item.quantity) IS NULL,0,SUM(order_item.quantity)) AS quantity, " .
					"IF(sales_invoice IS NOT NULL, CONCAT('SI ',sales_invoice), CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
					"IF(cleared_date IS NULL AND canceled_date IS NULL,DATEDIFF(NOW(),order.order_date),NULL) AS order_duration, " .
					"IF(cleared_date IS NOT NULL,'0-cleared'," .
					"IF(canceled_date IS NOT NULL,'1-canceled','2-pending'))" .
					"AS status " .
					"FROM `order` " .
					"INNER JOIN customer ON `order`.customer_id = customer.id " .
					"LEFT JOIN order_item ON `order`.id = order_item.order_id " .
					"LEFT JOIN v_accounts_receivable ON `order`.id = v_accounts_receivable.order_id " .
					"LEFT JOIN v_accounts_pdc_receivable ON `order`.id = v_accounts_pdc_receivable.order_id " .
					"LEFT JOIN v_rebate_payable ON `order`.id = v_rebate_payable.order_id " .
					"LEFT JOIN v_rebate_pdc_payable ON `order`.id = v_rebate_pdc_payable.order_id " .
					"$condition GROUP BY `order`.id ORDER BY $sortColumn $sortMethod" .
					($sortColumn == 'total_receivable' ? ", status $sortMethod " : " ") .
					"LIMIT $offset,$itemsPerPage";
		
		$resultSet = self::$database->query($sqlQuery);
		if (self::$database->getResultCount($resultSet) == 0) {
			echo '<div>No orders found for this criteria.</div>';
			return;
		}
		
		if ($filterName == 'customer_id') {
			$columns = array(
				'id'                          => 'Order No.',
				'tracking_number'             => 'Invoice No.',
				//'order_date' => 'Order Date',
				'order_duration'              => 'Duration',
				'delivery_pickup_target_date' => 'Delivery/Pick-up Date',
				//'quantity' => 'No. of Items',
				'remaining_balance'           => 'Total Amount',
				'accounts_receivable'         => 'Amount Receivable',
				'pdc_receivable'              => 'PDC Receivable',
				'total_receivable'            => 'Total Receivable',
				'rebate_payable'              => 'Rebate'
			);
		} else {
			$columns = array(
				'id'                          => 'Order No.',
				'tracking_number'             => 'Invoice No.',
				'customer'                    => 'Customer',
				//'order_date' => 'Order Date',
				'order_duration'              => 'Duration',
				'delivery_pickup_target_date' => 'Delivery/Pick-up Date',
				//'quantity' => 'No. of Items',
				'accounts_receivable'         => 'Amount Receivable',
				'pdc_receivable'              => 'PDC Receivable',
				'total_receivable'            => 'Total Receivable',
				'rebate_payable'              => 'Rebate'
			);
		}
		
		self::showListHeader($columns, 'order_list_section', 'Order::showList', $criteria, $sortColumn, $sortMethod, $filterName, $filterValue);
		
		// display list
		while ($order = self::$database->getResultRow($resultSet)) {
			echo '<tr>';
			
			// order no.
			echo '<td><a href="view_order_details.php?id=' . $order['id'] . '&src=' . $criteria . '">' . $order['id'] . '</a></td>';
			
			// invoice number
			echo '<td>';
			if ($order['canceled_date'] != null) {
				echo '<span class="canceled">' . $order['tracking_number'] . '</span>';
			} else {
				echo $order['tracking_number'];
			}
			echo '</td>';
			
			// customer
			if ($filterName != 'customer_id') {
				echo '<td>' .
					 '<span class="long_text_clip">' .
					 '<a href="view_customer_details.php?id=' . $order['customer_id'] . '" title="' .
					 capitalizeWords(Filter::output($order['customer'])) . '">' .
					 capitalizeWords(Filter::output($order['customer'])) . '</a>' .
					 '</span>' .
					 '</td>';
			}
			
			// order date
			/*echo '<td>';
			if ( $order['canceled_date'] != NULL ) {
				echo '<span class="canceled">' . dateFormatOutput( $order['order_date'], "M j, Y" ) . '</span>';
			} else {
				echo dateFormatOutput( $order['order_date'], "M j, Y" );
			}
			echo '</td>';*/
			
			// duration
			echo '<td>';
			if ($order['order_duration'] != null) {
				$creditTerms = explode(' ', $order['credit_terms']);
				
				if ($order['order_duration'] > $creditTerms[0]) {
					echo '<span class="bad">' . numberFormat($order['order_duration'], 'int');
				} else {
					echo '<span>' . numberFormat($order['order_duration'], 'int');
				}
				
				if ($order['order_duration'] > 1) {
					echo " days old</span>";
				} else {
					echo " day old</span>";
				}
			}
			echo '</td>';
			
			// delivery/pick-up date
			echo '<td>';
			$deliveryPickupTargetDate = dateFormatOutput($order['delivery_pickup_target_date'], 'Y-m-d');
			$currentDate              = date('Y-m-d');
			if ($order['canceled_date'] != null) {
				echo '<span class="canceled">' . dateFormatOutput($order['delivery_pickup_target_date'], 'M j, Y') . '</span>';
			} elseif ($order['delivery_pickup_actual_date'] != null) {        // order is already delivered
				echo '<span class="good">' . dateFormatOutput($order['delivery_pickup_target_date'], 'M j, Y') . '</span>';
				$deliveryPickupActualDate = dateFormatOutput($order['delivery_pickup_actual_date'], 'Y-m-d');
				if ($deliveryPickupTargetDate < $deliveryPickupActualDate) {
					echo '<img src="images/warning.png" class="status_icon" title="Delayed. Actual Delivery/Pickup Date: ' .
						 dateFormatOutput($order['delivery_pickup_actual_date'], 'M j, Y') . '" />';
				} else {
					echo '<img src="images/success.png" class="status_icon" title="On-time. Actual Delivery/Pickup Date: ' .
						 dateFormatOutput($order['delivery_pickup_actual_date'], 'M j, Y') . '" />';
				}
			} elseif ($deliveryPickupTargetDate < $currentDate) {            // delivery/pick-up date had passed
				echo '<span class="bad">' . dateFormatOutput($order['delivery_pickup_target_date'], 'M j, Y') . '</span>';
			} else {
				echo dateFormatOutput($order['delivery_pickup_target_date'], 'M j, Y');
			}
			echo '</td>';
			
			// no. of items
			/*echo '<td class="number">';
			if ( $order['canceled_date'] != NULL ) {
				echo '<span class="canceled">' . numberFormat( $order['quantity'], "int" ) . '</span>';
			} elseif ( $order['quantity'] <= 0 ) {
				echo '<span class="bad">' . numberFormat( $order['quantity'], "int" ) . '</span>';
			} else {
				echo '<span>' . numberFormat( $order['quantity'], "int" ) . '</span>';
			}
			echo '</td>';*/
			
			// total amount
			// in customer details page only
			if ($filterName == 'customer_id') {
				echo '<td class="number">';
				if ($order['canceled_date'] != null) {
					echo '<span class="canceled">' . numberFormat($order['remaining_balance'], 'float') . '</span>';
				} else {
					echo '<span>' . numberFormat($order['remaining_balance'], 'float') . '</span>';
				}
				echo '</td>';
			}
			
			// amount receivable
			echo '<td class="number">';
			if ($order['canceled_date'] != null) {
				echo '<span class="canceled">' . numberFormat($order['accounts_receivable'], 'float') . '</span>';
			} elseif ($order['accounts_receivable'] == 0) {
				echo '<span class="good">' . numberFormat($order['accounts_receivable'], 'float') . '</span>';
			} else {
				echo '<span>' . numberFormat($order['accounts_receivable'], 'float') . '</span>';
			}
			if ($order['waived_balance'] > 0) {
				echo '<img src="images/warning.png" class="status_icon" title="Waived Balance: ' .
					 numberFormat($order['waived_balance'], 'currency', 3, '.', ',', true) . '" />';
			}
			echo '</td>';
			
			// pdc receivable
			echo '<td class="number">';
			if ($order['canceled_date'] != null) {
				echo '<span class="canceled">' . numberFormat($order['pdc_receivable'], 'float') . '</span>';
			} elseif ($order['pdc_receivable'] == 0 && $order['accounts_receivable'] == 0) {
				echo '<span class="good">' . numberFormat($order['pdc_receivable'], 'float') . '</span>';
			} else {
				echo '<span>' . numberFormat($order['pdc_receivable'], 'float') . '</span>';
			}
			echo '</td>';
			
			// total receivable
			echo '<td class="number">';
			if ($order['canceled_date'] != null) {
				echo '<span class="canceled">Canceled</span>';
			} else {
				if ($order['cleared_date'] != null) {
					echo '<span class="good">Cleared!</span>' .
						 '<img src="images/success.png" class="status_icon" title="Order is cleared" />';
				} elseif ($order['total_receivable'] == 0) {
					echo '<span class="good">' . numberFormat($order['total_receivable'], 'float') . '</span>';
				} else {
					echo '<span>' . numberFormat($order['total_receivable'], 'float') . '</span>';
				}
			}
			echo '</td>';
			
			// rebate
			echo '<td class="number">';
			if ($order['canceled_date'] != null) {
				echo '<span class="canceled">' . numberFormat($order['rebate_payable'], 'float') . '</span>';
			} elseif ($order['rebate_payable'] == 0 && $order['pdc_rebate_payable'] == 0) {
				echo '<span class="good">' . numberFormat($order['rebate_payable'], 'float') . '</span>';
			} else {
				if ($order['pdc_rebate_payable'] > 0) {
					echo '<span class="bad">' . numberFormat($order['rebate_payable'], 'float') . '</span>' .
						 '<img src="images/rebate.png" class="status_icon" title="Rebate to clear: ' .
						 numberFormat($order['pdc_rebate_payable'], 'currency', '3', '.', ',', true) . '" />';
				} else {
					echo '<span>' . numberFormat($order['rebate_payable'], 'float') . '</span>';
				}
			}
			echo '</td>';
			
			echo "</tr>";
		}
		
		echo '</tbody></table>';
		
		self::showPagination($page, $itemsPerPage, $resultCount, 'order_list_section', 'Order::showList',
							 $criteria, $sortColumn, $sortMethod, $filterName, $filterValue);
		
		// totals field set
		if ($filterName != 'customer_id' && $filterName != 'agent_id') {
			echo '<fieldset><legend>Totals</legend>';
			
			$sqlQuery = "SELECT SUM(v_accounts_receivable.amount_receivable) AS total_amount_receivable, " .
						"SUM(v_accounts_pdc_receivable.pdc_receivable) AS total_pdc_receivable, " .
						"SUM(v_rebate_payable.rebate_payable) AS total_rebate_payable, " .
						"SUM(v_rebate_pdc_payable.pdc_rebate_payable) AS total_pdc_rebate_payable " .
						"FROM `order` " .
						"INNER JOIN customer ON `order`.customer_id = customer.id " .
						"LEFT JOIN v_accounts_receivable ON `order`.id = v_accounts_receivable.order_id " .
						"LEFT JOIN v_accounts_pdc_receivable ON `order`.id = v_accounts_pdc_receivable.order_id " .
						"LEFT JOIN v_rebate_payable ON `order`.id = v_rebate_payable.order_id " .
						"LEFT JOIN v_rebate_pdc_payable ON `order`.id = v_rebate_pdc_payable.order_id $condition";
			
			$resultSet = self::$database->query($sqlQuery);
			$order     = self::$database->getResultRow($resultSet);
			
			if ($order['total_pdc_receivable'] == null) {
				$order['total_pdc_receivable'] = 0;
			}
			
			if ($order['total_pdc_rebate_payable'] == null) {
				$order['total_pdc_rebate_payable'] = 0;
			}
			
			echo '<div>' .
				 '<span class="record_label">Number of Orders:</span>' .
				 '<span class="record_data">' . numberFormat($resultCount, 'int') . '</span>' .
				 '</div>';
			
			echo '<br /><br />';
			
			echo '<div>' .
				 '<span class="record_label">Total Amount Receivable:</span>' .
				 '<span class="record_data">' . numberFormat($order['total_amount_receivable'], 'currency') . '</span>' .
				 '</div>';
			
			echo '<div>' .
				 '<span class="record_label">Total PDC Receivable:</span>' .
				 '<span class="record_data">' . numberFormat($order['total_pdc_receivable'], 'currency') . '</span>' .
				 '</div>';
			
			echo '<div>' .
				 '<span class="record_label">Total Receivable:</span>' .
				 '<span class="record_data">' . numberFormat($order['total_amount_receivable'] + $order['total_pdc_receivable'], 'currency') . '</span>' .
				 '</div>';
			
			echo '<br /><br />';
			
			echo '<div>' .
				 '<span class="record_label">Total Rebate Payable:</span>' .
				 '<span class="record_data">' . numberFormat($order['total_rebate_payable'], 'currency') . '</span>' .
				 '</div>';
			
			echo '<div>' .
				 '<span class="record_label">Total PDC Rebate Payable:</span>' .
				 '<span class="record_data">' . numberFormat($order['total_pdc_rebate_payable'], 'currency') . '</span>' .
				 '</div>';
			
			echo '<div>' .
				 '<span class="record_label">Total Rebate:</span>' .
				 '<span class="record_data">' . numberFormat($order['total_rebate_payable'] + $order['total_pdc_rebate_payable'], 'currency') . '</span>' .
				 '</div>';
			
			echo '</fieldset>';
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// save order list to excel file; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function exportListToExcel( $username, $paramArray ) {
		$fileTimeStampExtension = date(EXCEL_FILE_TIMESTAMP_FORMAT);
		$headingTimeStamp       = dateFormatOutput($fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT);
		
		require_once('classes/Filter.php');
		
		self::$database = new Database();
		
		// get parameters
		switch ($paramArray['criteria']) {
			case 'recent-orders':
				$fileName   = 'Recent Orders';
				$sheetTitle = 'Recent Orders';
				$condition  = "WHERE canceled_date IS NULL AND cleared_date IS NULL";
				break;
			case 'orders-to-deliver':
				$fileName   = 'Orders to Deliver';
				$sheetTitle = 'Orders to Deliver to Customers';
				$condition  = "WHERE transaction_type = 'delivery' AND delivery_pickup_actual_date IS NULL AND canceled_date IS NULL";
				break;
			case 'orders-awaiting-pickup':
				$fileName   = 'Orders awaiting Pick-up';
				$sheetTitle = 'Orders Awaiting Pick-up by Customers';
				$condition  = "WHERE transaction_type = 'pick-up' AND delivery_pickup_actual_date IS NULL AND canceled_date IS NULL";
				break;
			case 'payments-to-collect':
				$fileName   = 'Orders with Payments to Collect';
				$sheetTitle = 'Orders with Payments to Collect';
				$condition  = "WHERE v_accounts_receivable.amount_receivable > 0 AND canceled_date IS NULL";
				break;
			case 'payments-to-clear':
				$fileName   = 'Orders with Payments to Clear';
				$sheetTitle = 'Orders with Payments to Clear';
				$condition  = "WHERE v_accounts_pdc_receivable.pdc_receivable > 0 AND canceled_date IS NULL";
				break;
			case 'payments-to-collect-and-clear':
				$fileName   = 'Orders with Payments to Collect and Clear';
				$sheetTitle = 'Orders with Payments to Collect and Clear';
				$condition  = "WHERE (v_accounts_receivable.amount_receivable > 0 OR v_accounts_pdc_receivable.pdc_receivable > 0) AND canceled_date IS NULL";
				break;
			case 'rebates-to-issue':
				$fileName   = 'Orders with Rebates to Issue';
				$sheetTitle = 'Orders with Rebates to Issue';
				$condition  = "WHERE v_rebate_payable.rebate_payable > 0 AND canceled_date IS NULL";
				break;
			case 'rebates-to-clear':
				$fileName   = 'Orders with Rebates to Clear';
				$sheetTitle = 'Orders with Rebates to Clear';
				$condition  = "WHERE v_rebate_pdc_payable.pdc_rebate_payable > 0 AND canceled_date IS NULL";
				break;
			case 'waived-balance':
				$fileName   = 'Orders with Waived Balance';
				$sheetTitle = 'Orders with Waived Balance';
				$condition  = "WHERE waived_balance > 0 AND canceled_date IS NULL";
				break;
			case 'orders-to-clear':
				$fileName   = 'Orders to Clear';
				$sheetTitle = 'Orders to Clear';
				$condition  = "WHERE delivery_pickup_actual_date IS NOT NULL AND v_accounts_receivable.amount_receivable = 0 AND " .
							  "v_accounts_pdc_receivable.pdc_receivable IS NULL AND canceled_date IS NULL AND cleared_date IS NULL";
				break;
			case 'cleared-orders':
				$fileName   = 'Cleared Orders';
				$sheetTitle = 'Cleared Orders';
				$condition  = "WHERE cleared_date IS NOT NULL";
				break;
			case 'canceled-orders':
				$fileName   = 'Canceled Orders';
				$sheetTitle = 'Canceled Orders';
				$condition  = "WHERE canceled_date IS NOT NULL";
				break;
			default:
				$fileName   = 'All Orders';
				$sheetTitle = 'All Orders';
				$condition  = '';
				break;
		}
		
		if ($condition == "") {
			$condition = "WHERE " . User::getQueryForBranch(self::$database);
		} else {
			$condition = $condition . " AND " . User::getQueryForBranch(self::$database);
		}
		
		// construct query
		$sqlQuery = "SELECT `order`.*, " .
					"v_accounts_receivable.amount_receivable AS accounts_receivable, " .
					"IF(v_accounts_pdc_receivable.pdc_receivable IS NULL,0,v_accounts_pdc_receivable.pdc_receivable) AS pdc_receivable, " .
					"(v_accounts_receivable.amount_receivable + " .
					"IF(v_accounts_pdc_receivable.pdc_receivable IS NULL,0,v_accounts_pdc_receivable.pdc_receivable))" .
					"AS total_receivable, " .
					"v_rebate_payable.rebate_payable, " .
					"IF(v_rebate_pdc_payable.pdc_rebate_payable IS NULL,0,v_rebate_pdc_payable.pdc_rebate_payable) AS pdc_rebate_payable, " .
					"(v_rebate_payable.rebate_payable + " .
					"IF(v_rebate_pdc_payable.pdc_rebate_payable IS NULL,0,v_rebate_pdc_payable.pdc_rebate_payable))" .
					"AS total_rebate_payable, " .
					"`order`.amount_receivable - `order`.waived_balance AS remaining_balance, " .
					"waived_balance, " .
					"customer.name AS customer, " .
					"agent.name AS agent, " .
					"customer.credit_terms AS credit_terms, " .
					"IF(SUM(order_item.quantity) IS NULL,0,SUM(order_item.quantity)) AS quantity, " .
					"IF(sales_invoice IS NOT NULL, CONCAT('SI ',sales_invoice), CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
					"IF(cleared_date IS NULL AND canceled_date IS NULL,DATEDIFF(NOW(),order.order_date),NULL) AS order_duration, " .
					"IF(cleared_date IS NOT NULL,'0-cleared'," .
					"IF(canceled_date IS NOT NULL,'1-canceled','2-pending'))" .
					"AS status " .
					"FROM `order` " .
					"INNER JOIN customer ON `order`.customer_id = customer.id " .
					"INNER JOIN agent ON `order`.agent_id = agent.id " .
					"LEFT JOIN order_item ON `order`.id = order_item.order_id " .
					"LEFT JOIN v_accounts_receivable ON `order`.id = v_accounts_receivable.order_id " .
					"LEFT JOIN v_accounts_pdc_receivable ON `order`.id = v_accounts_pdc_receivable.order_id " .
					"LEFT JOIN v_rebate_payable ON `order`.id = v_rebate_payable.order_id " .
					"LEFT JOIN v_rebate_pdc_payable ON `order`.id = v_rebate_pdc_payable.order_id " .
					"$condition GROUP BY `order`.id ORDER BY `order`.id DESC";
		
		$resultSet = self::$database->query($sqlQuery);
		
		// import PHPExcel library
		require_once('libraries/phpexcel/PHPExcel.php');
		
		// instantiate formatting variables
		$backgroundColor = new PHPExcel_Style_Color();
		$fontColor       = new PHPExcel_Style_Color();
		
		// color-specific variables
		$fontColorRed = new PHPExcel_Style_Color();
		$fontColorRed->setRGB('FF0000');
		$fontColorDarkRed = new PHPExcel_Style_Color();
		$fontColorDarkRed->setRGB('CC0000');
		$fontColorGreen = new PHPExcel_Style_Color();
		$fontColorGreen->setRGB('00CC00');
		$fontColorGray = new PHPExcel_Style_Color();
		$fontColorGray->setRGB('999999');
		
		$altRowColor = new PHPExcel_Style_Color();
		$altRowColor->setRGB(EXCEL_ALT_ROW_BACKGROUND_COLOR);
		
		// set value binder
		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());
		
		// create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		
		// set file properties
		$objPHPExcel->getProperties()
					->setCreator($username)
					->setLastModifiedBy($username)
					->setTitle("$sheetTitle as of $headingTimeStamp")
					->setSubject(EXCEL_FILE_SUBJECT)
					->setDescription(EXCEL_FILE_DESCRIPTION)
					->setKeywords(EXCEL_FILE_KEYWORDS)
					->setCategory(EXCEL_FILE_CATEGORY);
		
		// create a first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		
		// rename present sheet
		$activeSheet->setTitle('Order List');
		
		// set default font
		$activeSheet->getDefaultStyle()->getFont()->setName(EXCEL_DEFAULT_FONT_NAME)
					->setSize(EXCEL_DEFAULT_FONT_SIZE);
		
		// write sheet headers
		$activeSheet->setCellValue('A1', CLIENT);
		$activeSheet->setCellValue('A2', $sheetTitle);
		$activeSheet->setCellValue('A3', "As of $headingTimeStamp");
		
		// define max column
		$MAX_COLUMN       = 'U';
		$FIELD_HEADER_ROW = '5';
		
		// format sheet headers
		$backgroundColor->setRGB(EXCEL_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . '4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . '4')->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A1:A2')->getFont()->setBold(true);
		$activeSheet->getStyle('A1:A3')->getFont()->setName(EXCEL_HEADER_FONT_NAME);
		$activeSheet->getStyle('A1')->getFont()->setColor($fontColorRed);
		$activeSheet->getStyle('A1')->getFont()->setSize(EXCEL_HEADER1_FONT_SIZE);
		$activeSheet->getStyle('A2')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		$activeSheet->getStyle('A3')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		
		// write column headers
		$activeSheet->setCellValue('A' . $FIELD_HEADER_ROW, 'Order No.')
					->setCellValue('B' . $FIELD_HEADER_ROW, 'Invoice No.')
					->setCellValue('C' . $FIELD_HEADER_ROW, 'SI/DR')
					->setCellValue('D' . $FIELD_HEADER_ROW, 'Customer')
					->setCellValue('E' . $FIELD_HEADER_ROW, 'Business Unit')
					->setCellValue('F' . $FIELD_HEADER_ROW, 'Order Date')
					->setCellValue('G' . $FIELD_HEADER_ROW, 'Duration (days)')
					->setCellValue('H' . $FIELD_HEADER_ROW, 'Target Delivery Date')
					->setCellValue('I' . $FIELD_HEADER_ROW, 'Date Delivered')
					->setCellValue('J' . $FIELD_HEADER_ROW, 'No. of Items')
					->setCellValue('K' . $FIELD_HEADER_ROW, 'Total Amount (' . CURRENCY . ')')
					->setCellValue('L' . $FIELD_HEADER_ROW, 'Amount Receivable (' . CURRENCY . ')')
					->setCellValue('M' . $FIELD_HEADER_ROW, 'PDC Receivable (' . CURRENCY . ')')
					->setCellValue('N' . $FIELD_HEADER_ROW, 'Total Receivable (' . CURRENCY . ')')
					->setCellValue('O' . $FIELD_HEADER_ROW, 'Status')
					->setCellValue('P' . $FIELD_HEADER_ROW, 'Rebate Payable (' . CURRENCY . ')')
					->setCellValue('Q' . $FIELD_HEADER_ROW, 'PDC Rebate (' . CURRENCY . ')')
					->setCellValue('R' . $FIELD_HEADER_ROW, 'Total Rebate (' . CURRENCY . ')')
					->setCellValue('S' . $FIELD_HEADER_ROW, 'Waived Balance (' . CURRENCY . ')')
					->setCellValue('T' . $FIELD_HEADER_ROW, 'Agent')
					->setCellValue('U' . $FIELD_HEADER_ROW, 'Notes/Comments');
		
		// set column widths
		$activeSheet->getColumnDimension('A')->setWidth(15);
		$activeSheet->getColumnDimension('B')->setWidth(12);
		$activeSheet->getColumnDimension('C')->setWidth(10);
		$activeSheet->getColumnDimension('D')->setWidth(50);
		$activeSheet->getColumnDimension('E')->setWidth(15);
		$activeSheet->getColumnDimension('F')->setWidth(15);
		$activeSheet->getColumnDimension('G')->setWidth(15);
		$activeSheet->getColumnDimension('H')->setWidth(20);
		$activeSheet->getColumnDimension('I')->setWidth(15);
		$activeSheet->getColumnDimension('J')->setWidth(15);
		$activeSheet->getColumnDimension('K')->setWidth(20);
		$activeSheet->getColumnDimension('L')->setWidth(23);
		$activeSheet->getColumnDimension('M')->setWidth(20);
		$activeSheet->getColumnDimension('N')->setWidth(21);
		$activeSheet->getColumnDimension('O')->setWidth(15);
		$activeSheet->getColumnDimension('P')->setWidth(20);
		$activeSheet->getColumnDimension('Q')->setWidth(20);
		$activeSheet->getColumnDimension('R')->setWidth(20);
		$activeSheet->getColumnDimension('S')->setWidth(20);
		$activeSheet->getColumnDimension('T')->setWidth(30);
		$activeSheet->getColumnDimension('U')->setWidth(50);
		
		// format column headers
		$fontColor->setRGB(EXCEL_COLUMN_HEADER_FONT_COLOR);
		$backgroundColor->setRGB(EXCEL_COLUMN_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFont()->setColor($fontColor);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFont()->setBold(true);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getAlignment()->setWrapText(true);
		
		// set autofilter
		$activeSheet->setAutoFilter('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW);
		
		// freeze pane
		$activeSheet->freezePane('E' . ($FIELD_HEADER_ROW + 1));
		
		// initialize counters
		$rowPtr    = $FIELD_HEADER_ROW + 1;
		$itemCount = 0;
		
		// write data
		if (self::$database->getResultCount($resultSet) > 0) {
			while ($order = self::$database->getResultRow($resultSet)) {
				// order no.
				//$activeSheet->getCell( 'A' . $rowPtr )->setValueExplicit( $order['id'], PHPExcel_Cell_DataType::TYPE_STRING );
				$activeSheet->setCellValue('A' . $rowPtr, $order['id']);
				
				// invoice number
				$activeSheet->setCellValue('B' . $rowPtr, $order['tracking_number']);
				
				// SI/DR
				if ($order['sales_invoice'] != null) {
					$activeSheet->setCellValue('C' . $rowPtr, 'SI');
				} else {
					$activeSheet->setCellValue('C' . $rowPtr, 'DR');
				}
				
				// customer, business unit, order date
				$activeSheet->setCellValue('D' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($order['customer']))))
							->setCellValue('E' . $rowPtr, $order['business_unit'])
							->setCellValue('F' . $rowPtr, dateFormatOutput($order['order_date'], EXCEL_DATE_FORMAT_INPUT));
				
				// duration (days)
				if ($order['order_duration'] != null) {
					$activeSheet->setCellValue('G' . $rowPtr, $order['order_duration']);
					
					$creditTerms = explode(' ', $order['credit_terms']);
					if ($order['order_duration'] > $creditTerms[0]) {
						$activeSheet->getStyle('G' . $rowPtr)->getFont()->setColor($fontColorDarkRed);
						$activeSheet->getComment('G' . $rowPtr)->getText()
									->createTextRun("Duration of order is greater than the credit terms of this customer ({$creditTerms[0]} days)")
									->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
					}
				}
				
				// target delivery date
				$deliveryPickupTargetDate = dateFormatOutput($order['delivery_pickup_target_date'], EXCEL_DATE_FORMAT_INPUT);
				$activeSheet->setCellValue('H' . $rowPtr, $deliveryPickupTargetDate);
				
				// highlight missed target delivery date
				$currentDate = date(EXCEL_DATE_FORMAT_INPUT);
				if ($deliveryPickupTargetDate < $currentDate && $order['delivery_pickup_actual_date'] == null) {
					$activeSheet->getStyle('H' . $rowPtr)->getFont()->setColor($fontColorDarkRed);
					$activeSheet->getComment('H' . $rowPtr)->getText()
								->createTextRun('Target ' . ($order['transaction_type'] == 'delivery' ? 'delivery' : 'pick-up') . ' date is already missed')
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// date delivered
				if ($order['delivery_pickup_actual_date'] != null) {
					$deliveryPickupActualDate = dateFormatOutput($order['delivery_pickup_actual_date'], EXCEL_DATE_FORMAT_INPUT);
					$activeSheet->setCellValue('I' . $rowPtr, $deliveryPickupActualDate);
					
					// highlight late delivery
					if ($deliveryPickupTargetDate < $deliveryPickupActualDate) {
						$activeSheet->getStyle('I' . $rowPtr)->getFont()->setColor($fontColorDarkRed);
						$activeSheet->getComment('I' . $rowPtr)->getText()
									->createTextRun('Target ' . ($order['transaction_type'] == 'delivery' ? 'delivery' : 'pick-up') . ' date has been missed')
									->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
					}
				} else {
					$activeSheet->getComment('I' . $rowPtr)->getText()
								->createTextRun('Order is not yet ' .
												($order['transaction_type'] == 'delivery' ? 'delivered to customer' : 'picked-up by customer'))
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// no. of items
				if ($order['quantity'] > 0) {
					$activeSheet->setCellValue('J' . $rowPtr, $order['quantity']);
				} else {
					$activeSheet->getComment('J' . $rowPtr)->getText()
								->createTextRun('Nothing was ordered')
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// total amount
				if ($order['amount_receivable'] > 0) {
					$activeSheet->setCellValue('K' . $rowPtr, $order['amount_receivable']);
				} else {
					$activeSheet->getComment('K' . $rowPtr)->getText()
								->createTextRun('This order has no amount')
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// amount receivable
				if ($order['accounts_receivable'] > 0) {
					$activeSheet->setCellValue('L' . $rowPtr, $order['accounts_receivable']);
				}
				
				// pdc receivable
				if ($order['pdc_receivable'] > 0) {
					$activeSheet->setCellValue('M' . $rowPtr, $order['pdc_receivable']);
				}
				
				// total receivable
				$activeSheet->setCellValue('N' . $rowPtr, '=L' . $rowPtr . '+M' . $rowPtr);
				if ($activeSheet->getCell('N' . $rowPtr)->getCalculatedValue() == 0.000) {
					$activeSheet->getStyle('N' . $rowPtr)->getFont()->setColor($fontColorGreen);
				}
				
				// status
				if ($order['cleared_date'] != null) {
					$activeSheet->setCellValue('O' . $rowPtr, 'Cleared');
				} elseif ($order['canceled_date'] != null) {
					$activeSheet->setCellValue('O' . $rowPtr, 'Canceled');
				}
				
				// rebate payable
				if ($order['rebate_payable'] > 0) {
					$activeSheet->setCellValue('P' . $rowPtr, $order['rebate_payable']);
				}
				
				// pdc rebate
				if ($order['pdc_rebate_payable'] > 0) {
					$activeSheet->setCellValue('Q' . $rowPtr, $order['pdc_rebate_payable']);
				}
				
				// total rebate
				$activeSheet->setCellValue('R' . $rowPtr, '=P' . $rowPtr . '+Q' . $rowPtr);
				if ($activeSheet->getCell('R' . $rowPtr)->getCalculatedValue() == 0.000) {
					$activeSheet->getStyle('R' . $rowPtr)->getFont()->setColor($fontColorGreen);
				}
				
				// waived balance
				if ($order['waived_balance'] > 0) {
					$activeSheet->setCellValue('S' . $rowPtr, $order['waived_balance']);
				}
				
				$activeSheet->setCellValue('T' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($order['agent']))));
				
				// notes/comments
				$activeSheet->getCell('U' . $rowPtr)->setValueExplicit(stripslashes($order['remarks']), PHPExcel_Cell_DataType::TYPE_STRING);
				
				// set alternating row color
				if (EXCEL_ALT_ROW > 0 && $rowPtr % EXCEL_ALT_ROW == 0) {
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setStartColor($altRowColor);
				} else {
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_NONE);
				}
				
				$rowPtr++;
				$itemCount++;
			}
			
			$rowPtr--;
		}
		
		// post formatting
		$activeSheet->getStyle('A6:A' . $rowPtr)->getFont()->setBold(true);                                   // set Order No. to bold
		$activeSheet->getStyle('D6:D' . $rowPtr)->getAlignment()->setWrapText(true);                          // wrap Customer
		$activeSheet->getStyle('F6:F' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_DATE_FORMAT);        // format Order Date
		$activeSheet->getStyle('G6:G' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);         // format Duration
		$activeSheet->getStyle('H6:I' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_DATE_FORMAT);        // format Target Delivery Date and Date Delivered
		$activeSheet->getStyle('J6:J' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);         // format No. of Items
		$activeSheet->getStyle('K6:N' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);    // format amounts
		$activeSheet->getStyle('P6:R' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);    // format rebate
		$activeSheet->getStyle('K6:K' . $rowPtr)->getFont()->setBold(true);                                   // set Total Amount to bold
		$activeSheet->getStyle('N6:N' . $rowPtr)->getFont()->setBold(true);                                   // set Total Receivable to bold
		$activeSheet->getStyle('R6:R' . $rowPtr)->getFont()->setBold(true);                                   // set Total Rebate to bold
		$activeSheet->getStyle('S6:S' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);    // format Waived Balance
		$activeSheet->getStyle('T6:U' . $rowPtr)->getAlignment()->setWrapText(true);                          // wrap Agent and Notes/Comments
		
		// set columns to left aligned
		$activeSheet->getStyle('A6:E' . $rowPtr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$activeSheet->getStyle('T6:U' . $rowPtr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		// conditional formatting
		// * set Cleared to green, Canceled to grey
		$conditionalStyles = $activeSheet->getStyle('O6:O' . $rowPtr)->getConditionalStyles();
		
		$objConditional1 = new PHPExcel_Style_Conditional();
		$objConditional1->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
		$objConditional1->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_EQUAL);
		$objConditional1->addCondition('"Cleared"');
		$objConditional1->getStyle()->getFont()->setColor($fontColorGreen);
		
		array_push($conditionalStyles, $objConditional1);
		
		$objConditional2 = new PHPExcel_Style_Conditional();
		$objConditional2->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
		$objConditional2->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_EQUAL);
		$objConditional2->addCondition('"Canceled"');
		$objConditional2->getStyle()->getFont()->setColor($fontColorGray);
		
		array_push($conditionalStyles, $objConditional2);
		
		$activeSheet->getStyle('O6:O' . $rowPtr)->setConditionalStyles($conditionalStyles);
		
		// write totals
		$totalsRow = $rowPtr + 3;
		$activeSheet->setCellValue('A' . $totalsRow, 'Total Number of Orders:')
					->setCellValue('C' . $totalsRow, $itemCount)
					->setCellValue('I' . $totalsRow, 'Totals:')
					->setCellValue('J' . $totalsRow, '=SUM(J6:J' . $rowPtr . ')')
					->setCellValue('K' . $totalsRow, '=SUM(K6:K' . $rowPtr . ')')
					->setCellValue('L' . $totalsRow, '=SUM(L6:L' . $rowPtr . ')')
					->setCellValue('M' . $totalsRow, '=SUM(M6:M' . $rowPtr . ')')
					->setCellValue('N' . $totalsRow, '=SUM(N6:N' . $rowPtr . ')')
					->setCellValue('P' . $totalsRow, '=SUM(P6:P' . $rowPtr . ')')
					->setCellValue('Q' . $totalsRow, '=SUM(Q6:Q' . $rowPtr . ')')
					->setCellValue('R' . $totalsRow, '=SUM(R6:R' . $rowPtr . ')')
					->setCellValue('S' . $totalsRow, '=SUM(S6:S' . $rowPtr . ')');
		
		// format totals
		$styleArray = array(
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THICK)
			)
		);
		$activeSheet->getStyle('A' . $totalsRow . ':' . $MAX_COLUMN . $totalsRow)->applyFromArray($styleArray);
		$activeSheet->getStyle('C' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);
		$activeSheet->getStyle('J' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);
		$activeSheet->getStyle('K' . $totalsRow . ':N' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$activeSheet->getStyle('P' . $totalsRow . ':S' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$activeSheet->getStyle('A' . $totalsRow . ':' . $MAX_COLUMN . $totalsRow)->getFont()->setBold(true);
		$activeSheet->getStyle('A' . $totalsRow . ':' . $MAX_COLUMN . $totalsRow)->getFont()->setColor($fontColorRed);
		
		// set vertical alignment to top
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . $totalsRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
		
		// set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		
		// redirect output to a client's web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . CLIENT . ' - ' . $fileName . ' - as of ' . $fileTimeStampExtension . '.xlsx"');
		header('Cache-Control: max-age=0');
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
	}
	
	
	// -----------------------------------------------------------------------------------------------------------
	// display tasks for order details
	// -----------------------------------------------------------------------------------------------------------
	public function showDetailsTasks() {
		$sqlQuery  = "SELECT id, transaction_type, balance, " .
					 "IF(canceled_date IS NOT NULL,'canceled',IF(cleared_date IS NOT NULL,'cleared'," .
					 "IF(delivery_pickup_actual_date IS NULL,'not-delivered','all-delivered'))) AS order_status, " .
					 "IF(balance <= 0,'fully-paid',IF(balance = amount_receivable,'no-payment','partially-paid')) AS payment_status " .
					 "FROM `order` WHERE `order`.id = $this->id";
		$resultSet = self::$database->query($sqlQuery);
		$order     = self::$database->getResultRow($resultSet);
		
		if ($order['order_status'] == 'not-delivered') {
			$sqlQuery  = "SELECT id FROM order_item WHERE order_id = $this->id AND quantity != undelivered_quantity";
			$resultSet = self::$database->query($sqlQuery);
			if (self::$database->getResultCount($resultSet) > 0) {
				$order['order_status'] = 'partially-delivered';
			}
		}
		
		if ($order['payment_status'] == 'fully-paid') {
			$sqlQuery  = "SELECT clearing_actual_date FROM order_payment WHERE order_id = $this->id AND clearing_actual_date IS NULL AND amount >= 0";
			$resultSet = self::$database->query($sqlQuery);
			if (self::$database->getResultCount($resultSet) > 0) {       // there is still unclear payment or rebate
				$order['payment_status'] = 'fully-paid-not-cleared';
			}
		}
		
		?>
		<div id="tasks">
		<ul>
			<li id="task_edit_order" style="display: none"><a href="edit_order.php?id=<?= $order['id'] ?>">
				<img src="images/task_buttons/edit.png" />Edit Order</a></li>
			<li id="task_cancel_order" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
				$dialogMessage = "<b>Cancel</b> Order No. {$order['id']}?<br /><br /><br /><br /><br />" .
								 '<div id="dialog_buttons">' .
								 '<input type="button" value="Yes" onclick="cancelOrderCommit(\\\'' . $order['id'] . '\\\')" />' .
								 '<input type="button" value="No" onclick="hideDialog()" />' .
								 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
				?>','warning')"><img src="images/task_buttons/cancel.png" />Cancel Order...</a></li>
			<li id="task_undo_cancel_order" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
				$dialogMessage = "Undo cancelling Order No. {$order['id']}?<br /><br /><br /><br /><br />" .
								 '<div id="dialog_buttons">' .
								 '<input type="button" value="Yes" onclick="undoCancelOrderCommit(\\\'' . $order['id'] . '\\\')" />' .
								 '<input type="button" value="No" onclick="hideDialog()" />' .
								 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
				?>','warning')"><img src="images/task_buttons/undo.png" />Undo Cancel...</a></li>
            <li id="task_mark_as_cleared_notice" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
				$dialogMessage = '<b>Notice:</b> Clear all payments first before marking this order as cleared.<br /><br /><br /><br /><br />' .
						 		 '<div id="dialog_buttons">' .
						 		 '<input type="button" value="OK" onclick="hideDialog()" />' .
						 		 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
				?>','warning')"><img src="images/task_buttons/clear.png" />Mark as Cleared...</a></li>
			<li id="task_mark_as_cleared" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
				$dialogMessage = "Mark Order No. {$order['id']} as <b>Cleared</b>?<br /><br />" .
								 'Once cleared, this transaction will be considered complete.<br /><br /><br />' .
								 '<div id="dialog_buttons">' .
								 '<input type="button" value="Yes" onclick="markAsClearedCommit(\\\'' . $order['id'] . '\\\')" />' .
								 '<input type="button" value="No" onclick="hideDialog()" />' .
								 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
				?>','prompt')"><img src="images/task_buttons/clear.png" />Mark as Cleared...</a></li>
			<li id="task_unclear_order" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
				$dialogMessage = "Unclear Order No. {$order['id']}?<br /><br /><br /><br /><br />" .
								 '<div id="dialog_buttons">' .
								 '<input type="button" value="Yes" onclick="unclearOrderCommit(\\\'' . $order['id'] . '\\\')" />' .
								 '<input type="button" value="No" onclick="hideDialog()" />' .
								 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
				?>','warning')"><img src="images/task_buttons/undo.png" />Unclear...</a></li>
			<li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php
				$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br /><br /><br /><br />' .
								 '<div id="dialog_buttons">' .
						 		 '<input type="button" value="Yes" onclick="exportToExcelConfirm(\\\'data=order_details&orderID=' . $this->id . '\\\')" />' .
						 		 '<input type="button" value="No" onclick="hideDialog()" />' .
						 		 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
				?>','prompt')"><img src="images/task_buttons/export_to_excel.png" />Export to Excel...</a></li>
			<li id="task_export" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Print','<?php
				$dialogMessage = 'Do you want to view this page in print-friendly format?<br /><br /><br /><br /><br />' .
								 '<div id="dialog_buttons">' .
						 		 '<input type="button" value="Yes" onclick="exportToExcelConfirm(\\\'data=order_details&orderID=' . $this->id . '\\\')" />' .
						 		 '<input type="button" value="No" onclick="hideDialog()" />' .
						 		 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
				?>','prompt')"><img src="images/task_buttons/print.png" />Print...</a></li>
			<li id="task_back_to_list"><a href="list_orders.php<?php echo (isset($_GET['src']) ? '?criteria=' . $_GET['src'] : '') ?>">
				<img src="images/task_buttons/back_to_list.png" />Back to List</a></li>
		</ul>
		</div>
		
		<script type="text/javascript">
		<!--
			// update global variable
			orderStatus = '<?= $order['order_status'] ?>';
			paymentStatus = '<?= $order['payment_status'] ?>';
			transactionType = '<?= $order['transaction_type'] ?>';
			$(document).ready( function () {
				reorganizeOrderDetailsTasks();
				showOrderStatusLabel();
				if (typeof hideTasks == 'function') {
					hideTasks();		// hide tasks if no permission to view the order
				}
			});
		// -->
		</script>
		</div><?php		// extra closing div
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display order details
	//------------------------------------------------------------------------------------------------------------
	public function view() {
		// get main order info
		$sqlQuery  = "SELECT `order`.*, " .
					 "IF(canceled_date IS NOT NULL,'canceled',IF(cleared_date IS NOT NULL,'cleared',IF(delivery_pickup_actual_date IS NULL,'pending'," .
					 "IF(balance = 0,'fully-paid',IF(balance = amount_receivable,'delivered','partially-paid'))))) AS status, " .
					 "customer.name AS customer_name, agent.name AS agent_name, " .
					 "IF(cleared_date IS NULL AND canceled_date IS NULL,DATEDIFF(NOW(),order.order_date),-1) AS order_duration " .
					 "FROM `order` " .
					 "INNER JOIN customer ON customer.id = `order`.customer_id " .
					 "LEFT JOIN agent ON agent.id = `order`.agent_id " .
					 "WHERE `order`.id = $this->id AND " . User::getQueryForBranch(self::$database);
		$resultSet = self::$database->query($sqlQuery);
		
		if (self::$database->getResultCount($resultSet) == 0) {
			?>
			<fieldset>
				<legend>Order Info</legend>
				<section>
					<div>
						<span class="now">Error:</span> You do not have permission to view Order No. <?= $this->id;?>.<br />
						Order may be assigned to another branch or you do not have View Order permission.<br />
						Please contact your Administrator.<br /><br /><br /><br />
					</div>
				</section>
			</fieldset>
			<script type="application/javascript">
				<!--
				function hideTasks() {
					$("#task_edit_order").hide();
					$("#task_cancel_order").hide();
					$("#task_undo_cancel_order").hide();
					$("#task_mark_as_cleared_notice").hide();
					$("#task_mark_as_cleared").hide();
					$("#task_unclear_order").hide();
					$("#task_export").hide();
					$("task_back_to_list").hide();
				}
				// -->
			</script>
			<?php
			die();
		}
		
		$order = self::$database->getResultRow($resultSet);
		
		?>
		<fieldset>
			<legend>Order Info</legend>
			<section class="main_record_label">
				<div>Order No. <?= $order['id'] ?></div>
			</section>
			
			<section>
				<div>
					<span class="record_label">Status:</span>
					<span id="order_status_span" class="record_data"></span>
				</div>
			</section>
			
			<section>
				<div>
					<span class="record_label"><?php
						if ($order['sales_invoice'] != null) {
							echo "Sales Invoice No:";
							$trackingNumber = $order['sales_invoice'];
						} else {
							echo "Delivery Receipt No:";
							$trackingNumber = $order['delivery_receipt'];
						}
						?></span>
					<span class="record_data"><b><?= $trackingNumber ?></b></span>
				</div>
				<div>
					<span class="record_label">Customer:</span>
					<span class="record_data"><a href="view_customer_details.php?id=<?= $order['customer_id'] ?>"><?php
						echo capitalizeWords(Filter::output($order['customer_name'])) ?></a></span>
				</div>
				<div>
					<span class="record_label">Order Date:</span>
					<span class="record_data"><?php
						echo dateFormatOutput($order['order_date']);
						if ($order['order_duration'] > -1) {
							echo ' (' . numberFormat($order['order_duration'], 'int');
							if ($order['order_duration'] > 1) {
								echo ' days old)';
							} else {
								echo ' day old)';
							}
						}
						?></span>
				</div>
			</section>
			
			<section>
				<div>
					<span class="record_label">Transaction Type:</span>
					<span class="record_data"><?= ucfirst($order['transaction_type']) ?></span>
				</div>
				<div>
					<span class="record_label"><?= ucfirst($order['transaction_type']) ?> Date:</span>
					<span class="record_data"><?php
						$hour = (int) dateFormatOutput($order['delivery_pickup_target_date'], 'G');
				
						if ($hour < WORKING_HOURS_START || $hour > WORKING_HOURS_END) {
							// hour is outside working hours, do not display time
							echo dateFormatOutput($order['delivery_pickup_target_date'], 'F j, Y, D');
						} else {
							echo dateFormatOutput($order['delivery_pickup_target_date']);
						}
						
						if ($order['delivery_pickup_actual_date'] != null) {
							if ($order['transaction_type'] == 'delivery') {
								echo ' (Delivered: ';
							} else {
								echo ' (Picked-Up: ';
							}
							echo dateFormatOutput($order['delivery_pickup_actual_date'], 'F j, Y, D') . ')';
						}
						?></span>
				</div>
			</section>
			
			<section>
				<?php
				if (sizeof($GLOBALS['BUSINESS_UNITS']) > 0) {
					echo '<div>' .
						 '<span class="record_label">Business Unit:</span>' .
						 '<span class="record_data">' . $order['business_unit'] . '</span>' .
						 '</div>';
				}
				
				if (sizeof($GLOBALS['BRANCHES']) > 0) {
					echo '<div>' .
						 '<span class="record_label">Branch:</span>' .
						 '<span class="record_data">' . $order['branch'] . '</span>' .
						 '</div>';
				}
				
				if (Registry::get('transaction.agent.enabled') == true) {
					echo '<div>' .
						 '<span class="record_label">Agent:</span>' .
						 '<span class="record_data"><a href="view_agent_details.php?id=' . $order['agent_id'] . '">' .
						 capitalizeWords(Filter::output($order['agent_name'])) . '</a></span>' .
						 '</div>';
				}
				
				if (!is_null($order['remarks'])) {
					echo '<div>' .
						 '<span class="record_label">Notes/Comments:</span>' .
						 '<span class="record_data">' . Filter::output($order['remarks']) . '</span>' .
						 '</div>';
				}
				?>
			</section>
			
			<section>
				<div></div>
			</section>
			
			<?php
			// get order items
			$sqlQuery  = "SELECT order_item.*, inventory.model, inventory_brand.name AS brand_name FROM order_item " .
						 "INNER JOIN inventory ON inventory.id = order_item.inventory_id " .
						 "INNER JOIN inventory_brand ON inventory_brand.id = inventory.brand_id " .
						 "WHERE order_item.order_id = $this->id";
			$resultSet = self::$database->query($sqlQuery);
			
			if (self::$database->getResultCount($resultSet) > 0) {
				?>
				<section>
					<table class="item_input_table">
						<thead>
						<tr>
							<th></th>
							<th id="item_brand">Brand:</th>
							<th id="item_model">Model:</th>
							<th id="item_price">Selling Price:</th>
							<th id="item_quantity">Quantity:</th>
							<th id="item_sidr_discount">SI/DR Price:</th>
							<th id="item_subtotal">SI/DR Subtotal:</th>
							<th id="item_net_discount">Net Price:</th>
							<th id="item_subtotal">Net Subtotal:</th>
						</tr>
						</thead>
						<tbody>
						<?php
						$i                   = 1;
						$totalDeliveredItems = 0;
						$totalItems          = 0;
						$totalSIDRamount     = 0.0;
						$totalNetAmount      = 0.0;
						
						while ($item = self::$database->getResultRow($resultSet)) {
							?>
							<tr class="item_row">
								<td><span class="table_row_counter"><?= $i ?>.</span></td>
								<td><?= capitalizeWords($item['brand_name']) ?></td>
								<td><?= capitalizeWords($item['model']) ?></td>
								<td class="number"><span><?= numberFormat($item['price'], 'float') ?></span></td>
								<td class="quantity_link"><?php
									if ($order['status'] != 'cleared') {
										?>
										<span class="item_delivery_link">
											<a href="javascript:void(0)" onclick="showDialog('Mark Item as <?php
												echo ($order['transaction_type'] == 'delivery' ? 'Delivered' : 'Picked-up');
											 	?>','Getting data...','prompt'); 
											 	ajax(null,'dialog_message','innerHTML','Order::showItemDeliveryDialog','class=order&transactionID=<?=
												$order['id'] ?>&itemID=<?= $item['id'] ?>&index=<?= $i ?>'); loadDatePicker()" >
											 	<span id="item_quantity_<?= $i ?>"><?=
											 	numberFormat($item['quantity'] - $item['undelivered_quantity'], 'int') ?></span></a>
										</span>
										<?php
									} else {
										echo '<span id="item_quantity_' . $i . '">' .
											 numberFormat($item['quantity'] - $item['undelivered_quantity'], 'int') . '</span>';
									}
									echo ' / <span id="item_max_quantity_' . $i . '">' . numberFormat($item['quantity'], 'int') . '</span>';
									
									$totalDeliveredItems = $totalDeliveredItems + ($item['quantity'] - $item['undelivered_quantity']);
									$totalItems          = $totalItems + $item['quantity'];
									?></td>
								<td class="number"><span><?php echo numberFormat($item['sidr_price'], 'float'); ?></span></td>
								<td class="number"><span><?php
									$subtotal = (double) $item['sidr_price'] * (int) $item['quantity'];
									echo numberFormat($subtotal, 'float');
									$totalSIDRamount = $totalSIDRamount + $subtotal;
									?></span></td>
								<td class="number"><span><?php echo numberFormat($item['net_price'], 'float'); ?></span></td>
								<td class="number"><span><?php
									$subtotal = (double) $item['net_price'] * (int) $item['quantity'];
									echo numberFormat($subtotal, 'float');
									$totalNetAmount = $totalNetAmount + $subtotal;
									?></span></td>
							</tr>
							<?php
							$i++;
						}
						
						?>
						<tr>
							<td colspan="9"><br /></td>
						</tr>
						
						<tr class="totals_top totals_bottom">
							<td colspan="4"><label for="total_amount"><span class="important_label">Totals:</span></label></td>
							<td class="quantity_link">
								<?php
								if ($order['status'] != 'cleared') {
									?>
									<span class="item_delivery_link">
										<a href="javascript:void(0)" onclick="showDialog('Mark All Items as <?php
											echo($order['transaction_type'] == 'delivery' ? 'Delivered' : 'Picked-up');
											?>','Getting data...','prompt'); 
											ajax(null,'dialog_message','innerHTML','Order::showAllItemsDeliveryDialog','class=order&transactionID=<?=
											$order['id'] ?>&maxIndex=<?= ($i - 1) ?>'); loadDatePicker()">
											<span id="item_quantity_total"><?= $totalDeliveredItems ?></span></a>
									</span>
									<?php
								} else {
									echo '<span id="item_quantity_total">' . $totalDeliveredItems . '</span>';
								}
								?> / <span id="item_max_quantity_total"><?php echo $totalItems ?></span>
							</td>
							<td></td>
							<td class="number"><span><?= numberFormat($totalSIDRamount, 'float') ?></span></td>
							<td></td>
							<td class="number"><span><?= numberFormat($totalNetAmount, 'float') ?></span></td>
						</tr>
						
						<tr>
							<td colspan="9"><br /></td>
						</tr>
						
						<?php
						if ($order['sales_invoice'] != null) {
							?>
							<tr>
								<td colspan="8" class="summary_label">Total Sales: <?= CURRENCY ?></td>
								<td class="number"><span><?= numberFormat($order['total_sales_amount'], 'float') ?></span></td>
							</tr>
							<tr>
								<td colspan="8" class="summary_label">+ Value-Added Tax: <?= CURRENCY ?></td>
								<td class="number"><span><?= numberFormat($order['value_added_tax'], 'float') ?></span></td>
							</tr>
							<tr>
								<td colspan="8" class="summary_label">- Withholding Tax: <?= CURRENCY ?></td>
								<td class="number"><span><?= numberFormat($order['withholding_tax'], 'float') ?></span></td>
							</tr>
							<?php
						}
						
						if ($order['payment_term'] == 'installment' && (double) $order['interest'] > 0) {
							?>
							<tr>
								<td colspan="9"><br /></td>
							</tr>
							<tr>
								<td colspan="8" class="summary_label">+ Interest: <?= CURRENCY ?></td>
								<td class="number"><span><?= numberFormat($order['interest'], 'float') ?></span></td>
							</tr>
							<?php
						}
						?>
						<tr>
							<td colspan="9"><br /></td>
						</tr>
						
						<tr class="totals_top">
							<td colspan="8" class="summary_label"><span class="important_label">SI/DR Amount: <?= CURRENCY ?></span></td>
							<td class="number"><span class="important_label"><?= numberFormat($order['receipt_amount'], 'float') ?></span></td>
						</tr>
						<tr class="totals_bottom">
							<td colspan="8" class="summary_label"><span class="important_label">OFC Net Amount: <?= CURRENCY ?></span></td>
							<td class="number"><span class="important_label"><?= numberFormat($order['amount_receivable'], 'float') ?></span></td>
						</tr>
						</tbody>
					</table>
				</section>
				<?php
			}
		echo '</fieldset>';
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// mark order as canceled; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function cancel() {
		// check required parameters
		if (!isset($_POST['orderID'])) {
			return;
		}
		
		self::$database = new Database;
		
		// remove reserved stocks from inventory
		$sqlQuery  = "SELECT inventory_id, SUM(quantity) AS quantity FROM order_item " .
					 "WHERE order_id = {$_POST['orderID']} GROUP BY inventory_id";
		$resultSet = self::$database->query($sqlQuery);
		while ($orderItem = self::$database->getResultRow($resultSet)) {
			$sqlQuery = "UPDATE inventory SET " .
						"reserved_stock = IF(reserved_stock >= {$orderItem['quantity']}, reserved_stock - {$orderItem['quantity']}, 0) " .
						"WHERE id = " . $orderItem['inventory_id'];
			self::$database->query($sqlQuery);
		}
		
		// update order status
		$canceledDate = date('Y-m-d H:i:s');
		$sqlQuery     = "UPDATE `order` SET canceled_date = '$canceledDate' WHERE id = {$_POST['orderID']}";
		if (!self::$database->query($sqlQuery)) {
			Diagnostics::error('dialog', ERROR, "Cannot update Order No. {$_POST['orderID']}", 'Please try again.', SYSTEM_ERROR);
		}
		
		// log event
		$sqlQuery  = "SELECT sales_invoice, delivery_receipt, customer_id, name FROM `order` " .
					 "INNER JOIN customer ON `order`.customer_id = customer.id " .
					 "WHERE `order`.id = {$_POST['orderID']}";
		$resultSet = self::$database->query($sqlQuery);
		$order     = self::$database->getResultRow($resultSet);
		
		if ($order['sales_invoice'] != null) {
			$invoiceNumber = "SI {$order['sales_invoice']}";
		} else {
			$invoiceNumber = "DR {$order['delivery_receipt']}";
		}
		
		EventLog::addEntry(self::$database, 'info', 'order', 'update', 'canceled',
						   '<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['orderID'] . '">' .
						   $_POST['orderID'] . "</a> ($invoiceNumber): </span>Order from " .
						   '<a href="view_customer_details.php?id=' . $order['customer_id'] . '">' . capitalizeWords(Filter::output($order['name'])) .
						   '</a> was <span class="event_log_action bad">canceled</span>');
		
		echo "Order No. {$_POST['orderID']} has been canceled<br /><br /><br />";
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// set canceled order as active; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function undoCancel() {
		// check required parameters
		if (!isset($_POST['orderID'])) {
			return;
		}
		
		self::$database = new Database;
		
		// update order status
		$sqlQuery = "UPDATE `order` SET canceled_date = NULL WHERE id = {$_POST['orderID']}";
		self::$database->query($sqlQuery);
		
		// update stocks
		$sqlQuery   = "SELECT inventory_id, SUM(quantity) AS quantity FROM order_item " .
					  "WHERE order_id = " . $_POST['orderID'] . " GROUP BY inventory_id";
		$resultSet  = self::$database->query($sqlQuery);
		$orderItems = array();
		
		while ($orderItem = self::$database->getResultRow($resultSet)) {
			array_push($orderItems, $orderItem);
		}
		
		foreach ($orderItems as $orderItem) {
			$sqlQuery = "UPDATE inventory SET reserved_stock = reserved_stock + {$orderItem['quantity']} " .
						"WHERE id = {$orderItem['inventory_id']}";
			self::$database->query($sqlQuery);
		}
		
		// log event
		$sqlQuery  = "SELECT sales_invoice, delivery_receipt, customer_id, name FROM `order` " .
					 "INNER JOIN customer ON `order`.customer_id = customer.id " .
					 "WHERE `order`.id = {$_POST['orderID']}";
		$resultSet = self::$database->query($sqlQuery);
		$order     = self::$database->getResultRow($resultSet);
		
		if ($order['sales_invoice'] != null) {
			$invoiceNumber = "SI {$order['sales_invoice']}";
		} else {
			$invoiceNumber = "DR {$order['delivery_receipt']}";
		}
		
		EventLog::addEntry(self::$database, 'info', 'order', 'update', 'uncanceled',
						   '<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['orderID'] . '">' .
						   $_POST['orderID'] . "</a> ($invoiceNumber): </span>Order from " .
						   '<a href="view_customer_details.php?id=' . $order['customer_id'] . '">' . capitalizeWords(Filter::output($order['name'])) .
						   '</a> was <span class="event_log_action">uncanceled</span>');
		
		echo "Order No. {$_POST['orderID']} has been uncanceled<br /><br />Page will now reload...<br />";
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// mark order as cleared; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function markAsCleared() {
		// check required parameters
		if (!isset($_POST['orderID'])) {
			return;
		}
		
		self::$database = new Database;
		
		// delete child inventories from database
		$sqlQuery  = "SELECT inventory_id, parent_id FROM order_item " .
					 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
					 "WHERE order_id = {$_POST['orderID']}";
		$resultSet = self::$database->query($sqlQuery);
		$orderItems = array();
		
		while ($orderItem = self::$database->getResultRow($resultSet)) {
			if ($orderItem['parent_id'] != null) {
				array_push($orderItems, $orderItem);
			}
		}
		
		foreach ($orderItems as $orderItem) {
			self::deleteChildInventory($orderItem['inventory_id']);
		}
		
		// update order status
		$clearedDate = date('Y-m-d H:i:s');
		$sqlQuery    = "UPDATE `order` SET cleared_date = '$clearedDate' WHERE id = {$_POST['orderID']}";
		if (!self::$database->query($sqlQuery)) {
			Diagnostics::error('dialog', ERROR, "Cannot update Order No. {$_POST['orderID']}", 'Please try again.', SYSTEM_ERROR);
		}
		
		// log event
		$sqlQuery  = "SELECT sales_invoice, delivery_receipt, customer_id, name FROM `order` " .
					 "INNER JOIN customer ON `order`.customer_id = customer.id " .
					 "WHERE `order`.id = {$_POST['orderID']}";
		$resultSet = self::$database->query($sqlQuery);
		$order     = self::$database->getResultRow($resultSet);
		
		if ($order['sales_invoice'] != null) {
			$invoiceNumber = "SI {$order['sales_invoice']}";
		} else {
			$invoiceNumber = "DR {$order['delivery_receipt']}";
		}
		
		EventLog::addEntry(self::$database, 'info', 'order', 'update', 'cleared',
						   '<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['orderID'] . '">' .
						   $_POST['orderID'] . "</a> ($invoiceNumber): </span>Order from " .
						   '<a href="view_customer_details.php?id=' . $order['customer_id'] . '">' . capitalizeWords(Filter::output($order['name'])) .
						   '</a> was <span class="event_log_action good">cleared</span>');
		
		echo "Order No. {$_POST['orderID']} is now <b>cleared</b>!<br /><br /><br />";
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// set cleared order as active; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function undoClear() {
		// check required parameters
		if (!isset($_POST['orderID'])) {
			return;
		}
		
		self::$database = new Database;
		
		// update order status
		$sqlQuery = "UPDATE `order` SET cleared_date = NULL WHERE id = {$_POST['orderID']}";
		if (!self::$database->query($sqlQuery)) {
			Diagnostics::error('dialog', ERROR, "Cannot update Order No. {$_POST['orderID']}", 'Please try again.', SYSTEM_ERROR);
		}
		
		// log event
		$sqlQuery  = "SELECT sales_invoice, delivery_receipt, customer_id, name FROM `order` " .
					 "INNER JOIN customer ON `order`.customer_id = customer.id " .
					 "WHERE `order`.id = {$_POST['orderID']}";
		$resultSet = self::$database->query($sqlQuery);
		$order     = self::$database->getResultRow($resultSet);
		
		if ($order['sales_invoice'] != null) {
			$invoiceNumber = "SI {$order['sales_invoice']}";
		} else {
			$invoiceNumber = "DR {$order['delivery_receipt']}";
		}
		
		EventLog::addEntry(self::$database, 'info', 'order', 'update', 'uncleared',
						   '<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['orderID'] . '">' .
						   $_POST['orderID'] . "</a> ($invoiceNumber): </span>Order from " .
						   '<a href="view_customer_details.php?id=' . $order['customer_id'] . '">' . capitalizeWords(Filter::output($order['name'])) .
						   '</a> was <span class="event_log_action">uncleared</span>');
		
		echo "Order No. {$_POST['orderID']} has been uncleared<br /><br />Page will now reload...<br />";
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// save order details to excel file; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function exportDetailsToExcel( $username, $orderID ) {
		$fileTimeStampExtension = date(EXCEL_FILE_TIMESTAMP_FORMAT);
		$headingTimeStamp       = dateFormatOutput($fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT);
		
		require_once('classes/Filter.php');
		
		self::$database = new Database();
		
		// get main order info
		$sqlQuery  = "SELECT `order`.*, " .
					 "IF(canceled_date IS NOT NULL,'canceled',IF(cleared_date IS NOT NULL,'cleared'," .
					 "IF(delivery_pickup_actual_date IS NULL,'pending',IF(balance = 0,'fully-paid'," .
					 "IF(balance = amount_receivable,'delivered','partially-paid'))))) AS status, " .
					 "customer.name AS customer_name, agent.name AS agent_name, " .
					 "customer.credit_terms, " .
					 "IF(cleared_date IS NULL AND canceled_date IS NULL,DATEDIFF(NOW(),order.order_date),-1) AS order_duration " .
					 "FROM `order` " .
					 "INNER JOIN customer ON customer.id = `order`.customer_id " .
					 "LEFT JOIN agent ON agent.id = `order`.agent_id " .
					 "WHERE `order`.id = $orderID";
		
		$resultSet = self::$database->query($sqlQuery);
		
		if (self::$database->getResultCount($resultSet) == 0) {
			redirectToHomePage();
			return;
		}
		
		$order      = self::$database->getResultRow($resultSet);
		$sheetTitle = "Order No. $orderID";
		
		if ($order['sales_invoice'] != null) {
			$trackingNumber = $order['sales_invoice'];
			$fileName       = "Order No. $orderID (SI $trackingNumber)";
		} else {
			$trackingNumber = $order['delivery_receipt'];
			$fileName       = "Order No. $orderID (DR $trackingNumber)";
		}
		
		// import PHPExcel library
		require_once('libraries/phpexcel/PHPExcel.php');
		
		// instantiate formatting variables
		$backgroundColor = new PHPExcel_Style_Color();
		$fontColor       = new PHPExcel_Style_Color();
		
		// color-specific variables
		$fontColorRed = new PHPExcel_Style_Color();
		$fontColorRed->setRGB('FF0000');
		$fontColorDarkRed = new PHPExcel_Style_Color();
		$fontColorDarkRed->setRGB('CC0000');
		$fontColorGreen = new PHPExcel_Style_Color();
		$fontColorGreen->setRGB('00CC00');
		$fontColorGray = new PHPExcel_Style_Color();
		$fontColorGray->setRGB('777777');
		
		$altRowColor = new PHPExcel_Style_Color();
		$altRowColor->setRGB(EXCEL_ALT_ROW_BACKGROUND_COLOR);
		
		// set value binder
		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());
		
		// create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		
		// set file properties
		$objPHPExcel->getProperties()
					->setCreator($username)
					->setLastModifiedBy($username)
					->setTitle("$sheetTitle as of $headingTimeStamp")
					->setSubject(EXCEL_FILE_SUBJECT)
					->setDescription(EXCEL_FILE_DESCRIPTION)
					->setKeywords(EXCEL_FILE_KEYWORDS)
					->setCategory(EXCEL_FILE_CATEGORY);
		
		// create a first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		
		// rename present sheet
		$activeSheet->setTitle('Order Info');
		
		// set default font
		$activeSheet->getDefaultStyle()->getFont()->setName(EXCEL_DEFAULT_FONT_NAME)
					->setSize(EXCEL_DEFAULT_FONT_SIZE);
		
		// write sheet headers
		$activeSheet->setCellValue('A1', CLIENT);
		$activeSheet->setCellValue('A2', $sheetTitle);
		$activeSheet->setCellValue('A3', 'As of ' . $headingTimeStamp);
		
		// define max column
		$MAX_COLUMN       = 'I';
		$FIELD_HEADER_ROW = '5';
		
		// format sheet headers
		$backgroundColor->setRGB(EXCEL_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . '4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . '4')->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A1:A2')->getFont()->setBold(true);
		$activeSheet->getStyle('A1:A3')->getFont()->setName(EXCEL_HEADER_FONT_NAME);
		$activeSheet->getStyle('A1')->getFont()->setColor($fontColorRed);
		$activeSheet->getStyle('A1')->getFont()->setSize(EXCEL_HEADER1_FONT_SIZE);
		$activeSheet->getStyle('A2')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		$activeSheet->getStyle('A3')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		
		// set column widths
		$activeSheet->getColumnDimension('A')->setWidth(3);
		$activeSheet->getColumnDimension('B')->setWidth(25);
		$activeSheet->getColumnDimension('C')->setWidth(30);
		$activeSheet->getColumnDimension('D')->setWidth(15);
		$activeSheet->getColumnDimension('E')->setWidth(12);
		$activeSheet->getColumnDimension('F')->setWidth(15);
		$activeSheet->getColumnDimension('G')->setWidth(15);
		$activeSheet->getColumnDimension('H')->setWidth(15);
		$activeSheet->getColumnDimension('I')->setWidth(15);
		
		$activeSheet->setCellValue('A' . $FIELD_HEADER_ROW, 'Order Info');
		
		// format column headers
		$fontColor->setRGB(EXCEL_COLUMN_HEADER_FONT_COLOR);
		$backgroundColor->setRGB(EXCEL_COLUMN_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFont()->setColor($fontColor);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFont()->setBold(true);
		$activeSheet->getStyle('B' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getAlignment()->setWrapText(true);
		
		// freeze pane
		$activeSheet->freezePane('A' . ($FIELD_HEADER_ROW + 1));
		
		// order info
		if ($order['sales_invoice'] != null) {
			$activeSheet->setCellValue('B6', 'Sales Invoice No:');
		} else {
			$activeSheet->setCellValue('B6', 'Delivery Receipt No:');
		}
		$activeSheet->setCellValue('C6', $trackingNumber);
		
		$activeSheet->setCellValue('B7', 'Customer:');
		$activeSheet->setCellValue('C7', html_entity_decode(capitalizeWords(Filter::reinput($order['customer_name']))));
		
		$activeSheet->setCellValue('B8', 'Order Date:');
		$activeSheet->setCellValue('C8', dateFormatOutput($order['order_date'], EXCEL_DATETIME_FORMAT_INPUT));
		
		$activeSheet->setCellValue('B9', 'Duration:');
		if ($order['order_duration'] > -1) {
			if ($order['order_duration'] > 1) {
				$activeSheet->setCellValue('C9', $order['order_duration'] . ' days old');
			} else {
				$activeSheet->setCellValue('C9', $order['order_duration'] . ' day old');
			}
			
			$creditTerms = explode(' ', $order['credit_terms']);
			if ($order['order_duration'] > $creditTerms[0]) {
				$activeSheet->getStyle('C9')->getFont()->setColor($fontColorDarkRed);
				$activeSheet->getComment('C9')->getText()
							->createTextRun("Duration of order is greater than the credit terms of this customer ({$order['credit_terms']})")
							->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
			}
		}
		
		$activeSheet->setCellValue('B11', 'Transaction Type:');
		$activeSheet->setCellValue('C11', ucfirst($order['transaction_type']));
		
		$activeSheet->setCellValue('B12', 'Target ' . ucfirst($order['transaction_type']) . ' Date:');
		$activeSheet->setCellValue('C12', dateFormatOutput($order['delivery_pickup_target_date'], EXCEL_DATE_FORMAT_INPUT));
		
		$activeSheet->setCellValue('B13', 'Actual ' . ucfirst($order['transaction_type']) . ' Date:');
		if ($order['delivery_pickup_actual_date'] != null) {
			$activeSheet->setCellValue('C13', dateFormatOutput($order['delivery_pickup_actual_date'], EXCEL_DATE_FORMAT_INPUT));
		} else {
			if ($order['transaction_type'] == 'delivery') {
				$activeSheet->setCellValue('C13', 'Not yet delivered');
			} else {
				$activeSheet->setCellValue('C13', 'Not yet picked-up');
			}
			$activeSheet->getStyle('C13')->getFont()->setColor($fontColorDarkRed);
		}
		
		$activeSheet->setCellValue('B15', 'Business Unit:');
		$activeSheet->setCellValue('C15', $order['business_unit']);
		
		$activeSheet->setCellValue('B16', 'Agent:');
		$activeSheet->setCellValue('C16', html_entity_decode(capitalizeWords(Filter::reinput($order['agent_name']))));
		
		$activeSheet->setCellValue('B18', 'Notes/Comments:');
		$activeSheet->getCell('C18')->setValueExplicit(stripslashes($order['remarks']), PHPExcel_Cell_DataType::TYPE_STRING);
		
		$FIELD_HEADER_ROW = 20;
		
		// post formatting
		$activeSheet->getStyle('B6:B19')->getFont()->setColor($fontColorGray);      								// format labels
		$activeSheet->getStyle('C6:C19')->getAlignment()->setWrapText(true);                        				// wrap info
		$activeSheet->getStyle('C6:C19')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT); // left aligned
		$activeSheet->getStyle('C6')->getFont()->setBold(true);                                    					// set invoice number to bold
		$activeSheet->getStyle('C8')->getNumberFormat()->setFormatCode(EXCEL_DATETIME_FORMAT);        				// format Order Date
		$activeSheet->getStyle('C12:C13')->getNumberFormat()->setFormatCode(EXCEL_DATE_FORMAT);        				// format Delivery Dates
		
		// order items
		$activeSheet->setCellValue('B' . $FIELD_HEADER_ROW, 'Brand:');
		$activeSheet->setCellValue('C' . $FIELD_HEADER_ROW, 'Model:');
		$activeSheet->setCellValue('D' . $FIELD_HEADER_ROW, 'Selling Price:');
		$activeSheet->setCellValue('E' . $FIELD_HEADER_ROW, 'Quantity:');
		$activeSheet->setCellValue('F' . $FIELD_HEADER_ROW, 'SI/DR Price:');
		$activeSheet->setCellValue('G' . $FIELD_HEADER_ROW, 'SI/DR Subtotal:');
		$activeSheet->setCellValue('H' . $FIELD_HEADER_ROW, 'Net Price:');
		$activeSheet->setCellValue('I' . $FIELD_HEADER_ROW, 'Net Subtotal:');
		
		// format column headers
		$fontColor->setRGB(EXCEL_COLUMN_HEADER_FONT_COLOR);
		$backgroundColor->setRGB(EXCEL_COLUMN_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFont()->setColor($fontColor);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFont()->setBold(true);
		$activeSheet->getStyle('B' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getAlignment()->setWrapText(true);
		
		// initialize counters
		$rowPtr                = $FIELD_HEADER_ROW + 1;
		$itemRow               = $rowPtr;
		$itemCount             = 0;
		$totalUndeliveredItems = 0;
		$totalItems            = 0;
		
		// get order items
		$sqlQuery  = "SELECT order_item.*, inventory.model, inventory_brand.name AS brand_name FROM order_item " .
					 "INNER JOIN inventory ON inventory.id = order_item.inventory_id " .
					 "INNER JOIN inventory_brand ON inventory_brand.id = inventory.brand_id " .
					 "WHERE order_item.order_id = $orderID";
		$resultSet = self::$database->query($sqlQuery);
		
		// write data
		if (self::$database->getResultCount($resultSet) > 0) {
			while ($item = self::$database->getResultRow($resultSet)) {
				$itemCount++;
				$activeSheet->setCellValue('A' . $rowPtr, $itemCount);
				
				// brand
				$activeSheet->setCellValue('B' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($item['brand_name']))));
				
				// model
				$activeSheet->setCellValue('C' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($item['model']))));
				
				// selling price
				$activeSheet->setCellValue('D' . $rowPtr, $item['price']);
				
				// quantity
				$activeSheet->setCellValue('E' . $rowPtr, $item['quantity']);
				$totalItems            = $totalItems + $item['quantity'];
				$totalUndeliveredItems = $totalUndeliveredItems + $item['undelivered_quantity'];
				if ($item['undelivered_quantity'] > 0) {
					$activeSheet->getComment('E' . $rowPtr)->getText()
								->createTextRun(($item['quantity'] - $item['undelivered_quantity']) . ' out of ' . $item['quantity'] . ' items delivered')
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// SI/DR price
				$activeSheet->setCellValue('F' . $rowPtr, $item['sidr_price']);
				
				// SI/DR subtotal
				$activeSheet->setCellValue('G' . $rowPtr, '=E' . $rowPtr . '*F' . $rowPtr);
				
				// net price
				$activeSheet->setCellValue('H' . $rowPtr, $item['net_price']);
				
				// net subtotal
				$activeSheet->setCellValue('I' . $rowPtr, '=E' . $rowPtr . '*H' . $rowPtr);
				
				// set alternating row color
				if (EXCEL_ALT_ROW > 0 && $rowPtr % EXCEL_ALT_ROW == 0) {
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setStartColor($altRowColor);
				} else {
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_NONE);
				}
				
				$rowPtr++;
			}
			
			$rowPtr--;
		}
		
		// post formatting
		$activeSheet->getStyle('A' . $itemRow . ':A' . $rowPtr)->getFont()->setColor($fontColorGray);                       // format numbering
		$activeSheet->getStyle('B' . $itemRow . ':C' . $rowPtr)->getAlignment()->setWrapText(true);                         // wrap Brand and Model
		$activeSheet->getStyle('D' . $itemRow . ':D' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);   // format Selling Price
		$activeSheet->getStyle('E' . $itemRow . ':E' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);        // format Quantity
		$activeSheet->getStyle('F' . $itemRow . ':I' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);   // format amounts
		$activeSheet->getStyle('G' . $itemRow . ':G' . $rowPtr)->getFont()->setBold(true);                                  // set SI/DR Subtotal to bold
		$activeSheet->getStyle('I' . $itemRow . ':I' . $rowPtr)->getFont()->setBold(true);                                  // set Net Subtotal to bold
		
		// set columns to left aligned
		$activeSheet->getStyle('B' . $itemRow . ':C' . $rowPtr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		// conditional formatting
		// @TODO set zero amounts to red
		$conditionalStyles = $activeSheet->getStyle('D' . $itemRow . ':I' . $rowPtr)->getConditionalStyles();
		$objConditional    = new PHPExcel_Style_Conditional();
		$objConditional->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
		$objConditional->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_EQUAL);
		$objConditional->addCondition(0);
		$objConditional->getStyle()->getFont()->setColor($fontColorRed);
		array_push($conditionalStyles, $objConditional);
		$activeSheet->getStyle('D' . $itemRow . ':I' . $rowPtr)->setConditionalStyles($conditionalStyles);
		
		// write totals
		$totalsRow = $rowPtr + 3;
		$activeSheet->setCellValue('D' . $totalsRow, 'Totals:')
					->setCellValue('E' . $totalsRow, '=SUM(E' . $itemRow . ':E' . $rowPtr . ')')
					->setCellValue('G' . $totalsRow, '=SUM(G' . $itemRow . ':G' . $rowPtr . ')')
					->setCellValue('I' . $totalsRow, '=SUM(I' . $itemRow . ':I' . $rowPtr . ')');
		if ($totalUndeliveredItems > 0) {
			$activeSheet->getComment('E' . $totalsRow)->getText()
						->createTextRun(($totalItems - $totalUndeliveredItems) . ' out of ' . $totalItems . ' items delivered')
						->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
		}
		
		// format totals
		$styleArray = array(
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THICK)
			)
		);
		$activeSheet->getStyle('A' . $totalsRow . ':I' . $totalsRow)->applyFromArray($styleArray);
		$activeSheet->getStyle('E' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);
		$activeSheet->getStyle('G' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$activeSheet->getStyle('I' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$activeSheet->getStyle('A' . $totalsRow . ':I' . $totalsRow)->getFont()->setBold(true);
		$activeSheet->getStyle('A' . $totalsRow . ':I' . $totalsRow)->getFont()->setColor($fontColorRed);
		
		// tax
		$netAmountRow = $totalsRow;
		$totalsRow    = $totalsRow + 2;
		
		if ($order['sales_invoice'] != null) {
			$totalSalesRow = $totalsRow;
			$activeSheet->setCellValue('G' . $totalSalesRow, 'Total Sales:');
			$activeSheet->setCellValue('I' . $totalSalesRow, $order['total_sales_amount']);
			
			$valueAddedTaxRow = $totalSalesRow + 1;
			$activeSheet->setCellValue('G' . $valueAddedTaxRow, '(add) Value-Added Tax:');
			$activeSheet->setCellValue('I' . $valueAddedTaxRow, $order['value_added_tax']);
			
			$withholdingTaxRow = $valueAddedTaxRow + 1;
			$activeSheet->setCellValue('G' . $withholdingTaxRow, '(less) Withholding Tax:');
			$activeSheet->setCellValue('I' . $withholdingTaxRow, $order['withholding_tax']);
			
			$totalsRow = $withholdingTaxRow + 2;
		}
		
		// interest
		if ($order['payment_term'] == 'installment' && (double) $order['interest'] > 0) {
			$activeSheet->setCellValue('G' . $totalsRow, '(add) Interest:');
			$activeSheet->setCellValue('I' . $totalsRow, $order['interest']);
			$totalsRow = $totalsRow + 2;
		}
		
		// grand totals
		if ($order['sales_invoice'] != null) {
			$activeSheet->setCellValue('G' . $totalsRow, 'SI/DR Amount:');
			$activeSheet->setCellValue('H' . $totalsRow, CURRENCY);
			$activeSheet->setCellValue('I' . $totalsRow, '=I' . $totalSalesRow
														 . '+I' . $valueAddedTaxRow);
			$totalsRow++;
			
			$activeSheet->setCellValue('G' . $totalsRow, 'OFC Net Amount:');
			$activeSheet->setCellValue('H' . $totalsRow, CURRENCY);
			$activeSheet->setCellValue('I' . $totalsRow, '=I' . $netAmountRow
														 . '-I' . $withholdingTaxRow);
		} else {
			$activeSheet->setCellValue('G' . $totalsRow, 'SI/DR Amount:');
			$activeSheet->setCellValue('H' . $totalsRow, CURRENCY);
			$activeSheet->setCellValue('I' . $totalsRow, '=G' . $netAmountRow);
			$totalsRow++;
			
			$activeSheet->setCellValue('G' . $totalsRow, 'OFC Net Amount:');
			$activeSheet->setCellValue('H' . $totalsRow, CURRENCY);
			$activeSheet->setCellValue('I' . $totalsRow, '=I' . $netAmountRow);
		}
		
		// post formatting
		$activeSheet->getStyle('G' . ($netAmountRow + 1) . ':G' . ($totalsRow - 2))->getFont()->setColor($fontColorGray);      			// format numbering
		$activeSheet->getStyle('I' . ($netAmountRow + 1) . ':I' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT); // format Selling Price
		$activeSheet->getStyle('G' . ($totalsRow - 1) . ':I' . $totalsRow)->getFont()->setBold(true);
		$activeSheet->getStyle('H' . ($totalsRow - 1) . ':H' . $totalsRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
		$activeSheet->getStyle('I' . ($totalsRow - 1) . ':I' . $totalsRow)->getFont()->setColor($fontColorRed);
		
		// set vertical alignment to top
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . $totalsRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
		
		// set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		
		// redirect output to a client's web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . CLIENT . ' - ' . $fileName . ' - as of ' . $fileTimeStampExtension . '.xlsx"');
		header('Cache-Control: max-age=0');
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display list of orders for a particular inventory item; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function showInventory() {
		self::$database = new Database;
		
		// get parameters
		if (!isset($_POST['sortColumn'])) {
			$sortColumn = 'id';
		} else {
			$sortColumn = $_POST['sortColumn'];
		}
		
		if (!isset($_POST['sortMethod'])) {
			$sortMethod = 'DESC';
		} else {
			$sortMethod = $_POST['sortMethod'];
		}
		
		if (!isset($_POST['page'])) {
			$page = 1;
		} else {
			$page = $_POST['page'];
		}
		
		if (!isset($_POST['itemsPerPage'])) {
			$itemsPerPage = ITEMS_PER_PAGE;
		} else {
			$itemsPerPage = $_POST['itemsPerPage'];
		}
		
		if (!isset($_POST['filterName'])) {
			return;
		} else {
			$filterName = $_POST['filterName'];     // category
			if ($filterName != 'pending' && $filterName != 'delivered') {
				return;
			}
		}
		
		if (!isset($_POST['filterValue'])) {
			return;
		} else {
			$filterValue = $_POST['filterValue'];   // inventory ID
		}
		
		$offset = ($page * $itemsPerPage) - $itemsPerPage;
		
		// specify condition
		if ($filterName == 'pending') {
			$condition = "order_item.inventory_id = $filterValue AND " .
						 "order_item.undelivered_quantity > 0 AND " .
						 "canceled_date IS NULL AND " . User::getQueryForBranch(self::$database);
		} else {
			$condition = "order_item.inventory_id = $filterValue AND " .
						 "order_item.undelivered_quantity = 0 AND " .
						 "canceled_date IS NULL AND " . User::getQueryForBranch(self::$database);
		}
		
		// count results prior to main query
		$sqlQuery    = "SELECT COUNT(*) AS count FROM `order` " .
					   "INNER JOIN customer ON `order`.customer_id = customer.id " .
					   "LEFT JOIN order_item ON `order`.id = order_item.order_id WHERE $condition";
		$resultSet   = self::$database->query($sqlQuery);
		$resultCount = self::$database->getResultRow($resultSet);
		$resultCount = $resultCount['count'];
		
		// construct query
		$sqlQuery = "SELECT `order`.*, " .
					"customer.id AS customer_id, " .
					"customer.name AS customer, " .
					"customer.credit_terms AS credit_terms, " .
					"order_item.quantity, " .
					"order_item.undelivered_quantity, " .
					"order_item.quantity-order_item.undelivered_quantity AS delivered_quantity, " .
					"IF(sales_invoice IS NOT NULL," .
					"CONCAT('SI ',sales_invoice)," .
					"CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
					"IF(cleared_date IS NULL AND canceled_date IS NULL," .
					"DATEDIFF(NOW(),order.order_date)," .
					"NULL) AS order_duration " .
					"FROM `order` " .
					"INNER JOIN customer ON `order`.customer_id = customer.id " .
					"LEFT JOIN order_item ON `order`.id = order_item.order_id WHERE " .
					"$condition ORDER BY $sortColumn $sortMethod";
		if ($sortColumn == 'balance') {
			$sqlQuery = $sqlQuery . ", status $sortMethod";
		}
		$sqlQuery  = $sqlQuery . " LIMIT $offset,$itemsPerPage";
		$resultSet = self::$database->query($sqlQuery);
		
		// display result
		if (self::$database->getResultCount($resultSet) == 0) {
			echo "<div>No orders found for this criteria.</div>";
			return;
		}
		
		// set columns to display
		if ($filterName == 'pending') {
			$columns = array(
				'id'                          => 'Order No.',
				'tracking_number'             => 'Invoice No.',
				'customer'                    => 'Customer',
				'order_duration'              => 'Duration',
				'delivery_pickup_target_date' => 'Delivery/Pick-up Date',
				'quantity'                    => 'Quantity',
				'undelivered_quantity'        => 'Pending for Delivery'
			);
			
			$sectionName = 'pending_order_list_section';
		} else {
			$columns = array(
				'id'                          => 'Order No.',
				'tracking_number'             => 'Invoice No.',
				'customer'                    => 'Customer',
				'order_duration'              => 'Duration',
				'delivery_pickup_target_date' => 'Delivery/Pick-up Date',
				'quantity'                    => 'Quantity',
				'delivered_quantity'          => 'Items Delivered'
			);
			
			$sectionName = 'delivered_order_list_section';
		}
		
		// display sortable columns
		self::showListHeader($columns, $sectionName, 'Order::showInventory', null, $sortColumn, $sortMethod, $filterName, $filterValue);
		
		// display content
		while ($order = self::$database->getResultRow($resultSet)) {
			echo '<tr>';
			
			// order ID
			echo '<td><a href="view_order_details.php?id=' . $order['id'] . '">' . $order['id'] . '</a></td>';
			
			// invoice number
			echo '<td>' . $order['tracking_number'] . '</td>';
			
			// customer
			echo '<td>' .
				 '<span class="long_text_clip">' .
				 '<a href="view_customer_details.php?id=' . $order['customer_id'] .
				 '" title="' . capitalizeWords(Filter::output($order['customer'])) . '">' .
				 capitalizeWords(Filter::output($order['customer'])) .
				 '</a>' .
				 '</span>' .
				 '</td>';
			
			// duration
			echo '<td>';
			if ($order['order_duration'] != null) {
				$creditTerms = explode(' ', $order['credit_terms']);
				
				if ($order['order_duration'] > $creditTerms[0]) {
					echo '<span class="bad">' . numberFormat($order['order_duration'], 'int');
				} else {
					echo '<span>' . numberFormat($order['order_duration'], 'int');
				}
				
				if ($order['order_duration'] > 1) {
					echo ' days old</span>';
				} else {
					echo ' day old</span>';
				}
			}
			echo '</td>';
			
			// delivery/pick-up date
			echo '<td>';
			$deliveryPickupTargetDate = dateFormatOutput($order['delivery_pickup_target_date'], 'Y-m-d');
			$currentDate              = date('Y-m-d');
			if ($order['canceled_date'] != null) {
				echo '<span class="canceled">' . dateFormatOutput($order['delivery_pickup_target_date'], 'M j, Y') . '</span>';
			} elseif ($order['delivery_pickup_actual_date'] != null) {
				// order is already delivered
				echo '<span class="good">' . dateFormatOutput($order['delivery_pickup_target_date'], 'M j, Y') . '</span>';
				$deliveryPickupActualDate = dateFormatOutput($order['delivery_pickup_actual_date'], 'Y-m-d');
				if ($deliveryPickupTargetDate < $deliveryPickupActualDate) {
					echo '<img src="images/warning.png" class="status_icon" title="Delayed. Actual Delivery/Pickup Date: ' .
						 dateFormatOutput($order['delivery_pickup_actual_date'], 'M j, Y') . '" />';
				} else {
					echo '<img src="images/success.png" class="status_icon" title="On-time. Actual Delivery/Pickup Date: ' .
						 dateFormatOutput($order['delivery_pickup_actual_date'], 'M j, Y') . '" />';
				}
			} elseif ($deliveryPickupTargetDate < $currentDate) {
				// delivery/pick-up date had passed
				echo '<span class="bad">' . dateFormatOutput($order['delivery_pickup_target_date'], 'M j, Y') . '</span>';
			} else {
				echo dateFormatOutput($order['delivery_pickup_target_date'], 'M j, Y');
			}
			echo '</td>';
			
			// no. of items
			echo '<td class="number">';
			if ($order['quantity'] <= 0) {
				echo '<span class="bad">' . numberFormat($order['quantity'], 'int') . '</span>';
			} else {
				echo '<span>' . numberFormat($order['quantity'], 'int') . '</span>';
			}
			echo '</td>';
			
			// pending for delivery / delivered items
			echo '<td class="number">';
			if ($filterName == 'pending') {
				if ($order['undelivered_quantity'] <= 0) {
					echo '<span class="bad">' . numberFormat($order['undelivered_quantity'], 'int') . '</span>';
				} else {
					echo '<span>' . numberFormat($order['undelivered_quantity'], "int") . '</span>';
				}
			} else {
				if ($order['delivered_quantity'] <= 0) {
					echo '<span class="bad">' . numberFormat($order['delivered_quantity'], 'int') . '</span>';
				} else {
					echo '<span>' . numberFormat($order['delivered_quantity'], 'int') . '</span>';
				}
			}
			echo '</td>';
			
			echo '</tr>';
		}
		
		echo '</tbody></table>';
		
		echo '<div class="pagination_class">';
		self::showPagination($page, $itemsPerPage, $resultCount, $sectionName, 'Order::showInventory',
							 null, $sortColumn, $sortMethod, $filterName, $filterValue);
		echo '</div>';
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display missing invoice numbers; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function showMissingInvoice() {
		self::$database = new Database;
		
		// get parameters
		if (!isset($_POST['criteria'])) {
			$criteria = "DR";
		} else {
			$criteria = $_POST['criteria'];
		}
		
		echo $criteria;
	}
}
?>

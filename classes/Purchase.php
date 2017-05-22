<?php
// note: this class requires scripts/purchases.js


// ---------------------------------------------------------------------------------------------------------------
// class definition for purchases and purchase handling
// ---------------------------------------------------------------------------------------------------------------
class Purchase extends Transaction
{
	const MAX_PURCHASE_ITEMS     = 20;		// maximum number of purchase items
	const VISIBLE_PURCHASE_ITEMS = 5;		// number of visible purchase items, must be less than MAX_PURCHASE_ITEM
	
	private $purchaseNumber;
	
	
	// -----------------------------------------------------------------------------------------------------------
	// display purchase form
	// -----------------------------------------------------------------------------------------------------------
	public static function showInputForm( $id = null, $isItemEditable = true ) {
		// instantiate JavaScript objects
		echo '<script type="text/javascript">' .
			 "<!--\n" .
			 "var order = new Purchase();\n" .
			 '// -->' .
			 '</script>';
		
		echo '<form name="purchase_supplies" method="post" action="view_purchase_details.php" autocomplete="off" ' .
			 'onsubmit="return order.validateInputForm(' . ($isItemEditable ? 'true' : 'false') .
			 ')" onreset="return confirmReset(\'order.resetInputForm\')">';
		
		if ($id != null) {
			// existing purchase ID, get purchase and supplier info
			self::$database = new Database();
			$resultSet      = self::$database->query("SELECT * FROM purchase WHERE id = $id");
			if ($resultSet != null) {
				$purchase   = self::$database->getResultRow($resultSet);
				$supplierID = $purchase['supplier_id'];
			} else {		// cannot determine order ID
				$supplierID = null;
				$purchase   = null;
				$id         = null;
			}
		} else {
			// new purchase
			$supplierID = null;
			$purchase   = null;
		}
		
		// display supplier field set
		Supplier::showInputFieldSet($supplierID);
		
		// display purchase field set
		self::showInputFieldSet($id, $purchase, $isItemEditable);
		
		if ($isItemEditable) {
			// display payment field set
			if ($id != null) {
				Payment::showInputFieldSet($id, self::$database, 'purchase', $purchase['payment_term'], $purchase['interest']);
			} else {
				Payment::showInputFieldSet();
			}
		}
		
		// display remarks field set
		self::showRemarksInputFieldSet($purchase['agent_id'], $purchase['remarks']);
		
		// display submit/reset/cancel buttons
		self::showButtons(ButtonSet::SUBMIT_RESET_CANCEL);
		echo '</form>';
		
		// call JavaScript functions for initialization
		echo "<script type=\"text/javascript\">\n" .
			 "<!--\n";
		
		if ($supplierID != null) {
			// fill and lock supplier field set
			echo "lockInputFields();\n" .
				 "$('#supplier_query_mode').val('locked');\n";
		}
		
		if ($isItemEditable) {
			echo "order.toggleDeliveryPickupDateLabel();\n" .
				 "order.toggleTaxFieldsDisplay( false );\n" .
				 "order.loadFormEvents();\n";
		}
		
		echo "// -->\n" .
			 '</script>';
	}
	
	
	// -----------------------------------------------------------------------------------------------------------
	// display purchase form field set
	// -----------------------------------------------------------------------------------------------------------
	public static function showInputFieldSet( $id = null, array $purchase = null, $isItemEditable = true ) {
		if (self::$database == null) {				// no database instance yet, create database instance
			self::$database = new Database();
		}
		
		if ($id != null && $purchase == null) {		// purchase array not yet initialized, get purchase info
			$resultSet = self::$database->query("SELECT * FROM purchase WHERE id = $id");
			$purchase  = self::$database->getResultRow($resultSet);
		}
		
		echo '<fieldset><legend>Purchase Info</legend><section>';
		
		if ($id != null) {
			echo '<div>' .
				 '<label for="purchase_number">Purchase No:</label>' .
				 '<output name="purchase_number">' . $purchase['id'] . '</output>' .
				 '</div>';
		}
		
		// display purchase's basic info
		?>
		<div>
			<label for="purchase_date" class="required_label">Purchase Date:</label>
			<?php
			if ($id == null) {
				echo '<input type="text" name="purchase_date" id="purchase_date" class="datepicker_no_future_date" ' .
					 'size="30" maxlength="30" required="required" />';
			} else {
				echo '<output name="purchase_date" id="purchase_date">' . dateFormatOutput($purchase['purchase_date'], 'F j, Y, D') . '</output>';
			}
			?>
			<input type="hidden" name="purchase_query_mode" id="purchase_query_mode" value="<?= ($id != null ? 'edit' : 'new') ?>" />
			<input type="hidden" name="purchase_id" id="purchase_id" value="<?= ($id != null ? $purchase['id'] : '0') ?>" />
		</div>
            
        <div>
			<label for="invoice_type">Tracking No:</label>
            <select name="invoice_type" id="invoice_type" class="form_input_select">
            	<option value="SI"<?= ($id != null ? ($purchase['sales_invoice'] != null ? ' selected="selected"' : '') : '') ?>>Sales Invoice (SI)</option>
            	<option value="DR"<?= ($id != null ? ($purchase['delivery_receipt'] != null ?
					' selected="selected"' : '') : '') ?>>Delivery Receipt (DR)</option>
            </select>
            <input type="hidden" name="invoice_type_orig" id="invoice_type_orig" value="<?php
				echo ($id != null) ? ($purchase['sales_invoice'] != null ? "SI" : 'DR') : 'null' ?>" />
        </div>
        
        <div>
           	<label for="tracking_number"></label>
			<input type="text" name="tracking_number" id="tracking_number"<?php
				echo ($id != null ? ' value="' . ($purchase['sales_invoice'] != null ?
					 Filter::reinput($purchase['sales_invoice']) : Filter::reinput($purchase['delivery_receipt'])) . '"' : '') ?> />
            <input type="hidden" name="tracking_number_orig" id="tracking_number_orig" value="<?php
				echo ($id != null ? ($purchase['sales_invoice'] != null ?
					 Filter::reinput($purchase['sales_invoice']) : Filter::reinput($purchase['delivery_receipt'])) : 'null') ?>" />
		</div>
		</section>

        <section>
        	<div>
            	<label for="supplier_po_number">Supplier's P.O. No.</label>
				<input type="text" name="supplier_po_number" id="supplier_po_number" required="required"<?php
					echo ($id != null) ? ' value="' . Filter::reinput($purchase['purchase_number']) . '"' : '' ?> />
			</div>

	        <?php
			if (sizeof($GLOBALS['BUSINESS_UNITS']) > 0) {
				echo '<div>' .
					 '<label for="business_unit">Business Unit:</label>' .
					 '<select name="business_unit" id="business_unit" class="form_input_select">';
				foreach ($GLOBALS['BUSINESS_UNITS'] as $businessUnit) {
					echo '<option value="' . $businessUnit . '"' .
						 ($id != null ? ($purchase['business_unit'] == $businessUnit ? ' selected="selected"' : '') : '') .
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
						 ($id != null ? ($purchase['branch'] == $branch ? ' selected="selected"' : '') : '') .
					 	 '>' . $branch . '</option>';
				}
				echo '</select></div>';
			}
			?>
		</section>
		
		<?php
		// display order items
		if (!$isItemEditable) {
			echo '<section><div id="horizontal_message">Items are already delivered or payment is already made.<br />' .
				 'Return delivered items and cancel all payments to edit Purchase Items and Payment details.</div></section>';
			return;
		}
		
		echo '<section><div>' .
			 '<label for="transaction_type">Transaction Type:</label>';
		if ($purchase['delivery_pickup_actual_date'] == null) {
			echo '<select name="transaction_type" id="transaction_type" class="form_input_select">' .
				 '<option value="delivery"' . ($id != null ? ($purchase['transaction_type'] == 'delivery' ? ' selected="selected"' : '') : '') .
				 '>Delivery</option>' .
				 '<option value="pick-up"' . ($id != null ? ($purchase['transaction_type'] == 'pick-up' ? ' selected="selected"' : '') : '') .
				 '>Pick-up</option>' .
				 '</select>';
		} else {
			echo '<output>' . ucfirst($purchase['transaction_type']) . '</output>' .
				 '<input type="hidden" name="transaction_type" id="transaction_type" value="' . $purchase['transaction_type']  . '" />';
		}
		echo '</div>';
		
		echo '<div>' .
			 '<label for="delivery_pickup_date" id="delivery_pickup_date_label" class="required_label">Delivery Date:</label>';
		if ($purchase['delivery_pickup_actual_date'] == null) {
			echo '<input type="text" name="delivery_pickup_date" id="delivery_pickup_date" class="datepicker" ' .
				 'size="30" maxlength="30" required="required"' .
				 ($id != null ? ' value="' . dateFormatOutput(Filter::reinput($purchase['delivery_pickup_target_date']), 'F j, Y, D') . '"' : '') . '/>';
		} else {
			echo '<output>' . dateFormatOutput($purchase['delivery_pickup_target_date']) .
				 ($purchase['transaction_type'] == 'delivery' ? ' (Delivered: ' : ' (Picked-up: ') .
				 dateFormatOutput($purchase['delivery_pickup_actual_date'], 'F j, Y, D') . ')</output>' .
				 '<input type="hidden" name="delivery_pickup_date" id="delivery_pickup_date" value="' .
				 dateFormatOutput(Filter::reinput($purchase['delivery_pickup_target_date']), 'F j, Y, D') . '" />' .
				 '<input type="hidden" name="delivery_pickup_actual_time" id="delivery_pickup_actual_time" value="' .
				 $purchase['delivery_pickup_actual_date'] . '" />';
		}
		echo '</div></section>';
		
		echo '<section><table class="item_input_table"><thead><tr>' .
			 '<th></th>' .
			 '<th>Brand:</th>' .
			 '<th>Model:</th>' .
			 '<th>Purchase Price:</th>' .
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
				array_push($inventoryBrandName, capitalizeWords($inventoryBrand['name']));
			}
		}
		
		// get item list if existing purchase
		$item = array();
		if ($id != null) {
			$resultSet = self::$database->query(
				"SELECT * FROM purchase_item INNER JOIN inventory ON purchase_item.inventory_id = inventory.id WHERE purchase_id = {$purchase['id']}");
			
			$itemCount    = self::$database->getResultCount($resultSet);
			$visibleItems = $itemCount;
			
			while ($itemList = self::$database->getResultRow($resultSet)) {
				array_push($item, $itemList);
			}
		} else {
			$itemCount    = 0;
			$visibleItems = self::VISIBLE_PURCHASE_ITEMS;
		}
		
		$totalSidrAmount = 0;
		$totalNetAmount  = 0;
		
		// display item table
		for ($i = 1; $i <= self::MAX_PURCHASE_ITEMS; $i++) {
			// display item row
			if ($id != null && $i <= $itemCount) {
				self::showItemListRow($id, $i, $visibleItems, $inventoryBrandID, $inventoryBrandName, $itemCount, $item[$i - 1], false);
				
				// perform item computation if existing order
				echo "<script type=\"text/javascript\">\n" .
					 "<!--\n";
				
				echo "order.inventory.loadPurchasePrice($i, $('item_model_$i').val());\n";
				
				$sidrSubtotal = numberFormat(((double) $item[$i - 1]['sidr_price'] * (int) $item[$i - 1]['quantity']), 'float', 3, '.', '', true);
				$netSubtotal  = numberFormat(((double) $item[$i - 1]['net_price'] * (int) $item[$i - 1]['quantity']), 'float', 3, '.', '', true);
				
				echo "order.validateItemBrand($i);\n" .
					 "order.validateItemModel($i, true);\n" .
					 "$('#item_price_'+$i).val($('#item_price_'+$i).attr('defaultValue'));\n" .
					 "$('#item_quantity_'+$i).val($('#item_quantity_'+$i).attr('defaultValue'));\n" .
					 "$('#item_sidr_price_'+$i).val($('#item_sidr_price_'+$i).attr('defaultValue'));\n" .
					 "$('#item_net_price_'+$i).val($('#item_net_price_'+$i).attr('defaultValue'));\n" .
					 "$('#item_sidr_subtotal_'+$i).val('$sidrSubtotal');\n" .
					 "$('#item_net_subtotal_'+$i).val('$netSubtotal');\n" .
					 "order.validateItemPrice($i);\n";
				
				if ((int) $item[$i - 1]['undelivered_quantity'] < (int) $item[$i - 1]['quantity']) {
					echo "$('#item_brand_'+$i).attr('disabled','disabled');\n" .
						 "$('#item_model_'+$i).attr('disabled','disabled');\n" .
						 "$('#item_price_'+$i).attr('disabled','disabled');\n" .
						 "$('#item_quantity_'+$i).attr('disabled','disabled');\n" .
						 "$('#item_sidr_price_'+$i).attr('disabled','disabled');\n" .
						 "$('#item_net_price_'+$i).attr('disabled','disabled');\n";
				}
				
				echo "// -->\n" .
					 '</script>';
				
				$totalSidrAmount = $totalSidrAmount + $sidrSubtotal;
				$totalNetAmount  = $totalNetAmount + $netSubtotal;
			} else {
				self::showItemListRow(null, $i, $visibleItems, $inventoryBrandID, $inventoryBrandName, $itemCount, null, false);
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
				echo ($id != null ? numberFormat(Filter::reinput($purchase['total_sales_amount']), 'float', 3, '.', '', true) : '0.000') 
				?>" disabled="disabled" /></td>
			<td></td>
		</tr>
		<tr class="tax_fields">
			<td colspan="8"><label for="value_added_tax">Value-Added Tax: <?= CURRENCY ?></label></td>
			<td><input type="text" name="value_added_tax" id="value_added_tax" class="number order_item_total output_field" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($purchase['value_added_tax']), 'float', 3, '.', '', true) : '0.000')
				?>" disabled="disabled" /></td>
		</tr>
		<tr class="tax_fields">
			<td colspan="8"><label for="withholding_tax">Withholding Tax: <?= CURRENCY ?></label></td>
			<td><input type="text" name="withholding_tax" id="withholding_tax" class="number order_item_total" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($purchase['withholding_tax']), 'float', 3, '.', '', true) : '0.000') 
				?>" disabled="disabled" /></td>
		</tr>
		<tr class="tax_fields">
			<td colspan="9"><br /></td>
		</tr>
		
		<tr>
			<td colspan="8"><label for="sidr_amount" class="important_label">SI/DR Amount: <?= CURRENCY ?></label></td>
			<td><input type="text" name="sidr_amount" id="sidr_amount" class="number order_item_total output_field" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($purchase['receipt_amount']), 'float', 3, '.', '', true) : '0.000')
				?>" disabled="disabled" /></td>
		</tr>
		<tr>
			<td colspan="8"><label for="net_amount" class="important_label">OFC Net Amount: <?= CURRENCY ?></label></td>
			<td><input type="text" name="net_amount" id="net_amount" class="number order_item_total output_field" value="<?php
				echo ($id != null ? numberFormat(Filter::reinput($purchase['amount_payable']), 'float', 3, '.', '', true) : '0.000') 
				?>" disabled="disabled" /></td>
		</tr>
		</tbody>
		</table>
		</section>

		<script type="text/javascript">
		<!--
			order.setMaxItems(<?= self::MAX_PURCHASE_ITEMS ?>);
			order.setInitialVisibleItems(<?= $visibleItems ?>);
			order.payment.setVAT('<?= VAT_PERCENT ?>');
			<?php
			if ($id != null) {
				echo "$('#total_sales').attr('defaultValue', $('#total_sales').val());\n" .
				"$('#value_added_tax').attr('defaultValue', $('#value_added_tax').val());\n" .
				"$('#withholding_tax').attr('defaultValue', $('#withholding_tax').val());\n" .
				"$('#withholding_tax').attr('disabled', '');\n" .
				"$('#sidr_amount').attr('defaultValue', $('#sidr_amount').val());\n" .
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
	// save purchase info to database; returns purchase ID
	// -----------------------------------------------------------------------------------------------------------
	public function save( $supplierID ) {
		if ($_POST['purchase_query_mode'] == 'edit' && !isset($_POST['transaction_type'])) {
			$isItemEditable = false;
		} else {
			$isItemEditable = true;
		}
		
		// format input values
		$this->prepareTransactionData($isItemEditable);
		$this->purchaseNumber = Filter::input($_POST['supplier_po_number']);
		if (empty($this->purchaseNumber)) {
			$this->purchaseNumber = "NULL";
		} else {
			$this->purchaseNumber = "'$this->purchaseNumber'";
		}
		
		if ($_POST['purchase_query_mode'] == 'new') {
			// save new purchase
			$sqlQuery = "INSERT INTO purchase VALUES (" .
						"NULL," .                                                            	// id, auto-generate
						"$this->salesInvoice," .                                            	// sales_invoice
						"$this->deliveryReceipt," .                                        		// delivery_receipt
						"$this->purchaseNumber," .                                        		// purchase_number
						"$supplierID," .                                                		// supplier_id
						"'$this->transactionDate'," .                                        	// purchase_date
						($this->businessUnit == null ? "NULL," : "'$this->businessUnit',") .	// business unit
						($this->branch == null ? "NULL," : "'$this->branch',") .                // branch
						"'$this->transactionType'," .                                    		// transaction_type
						"'$this->deliveryPickupTargetDate'," .                            		// delivery_pickup_target_date
						"NULL," .                                                            	// delivery_pickup_actual_date
						"'$this->paymentTerm'," .                                        		// payment_term
						"$this->transactionDiscount," .                                    		// purchase_discount
						"$this->totalSales," .                                            		// total_sales_amount
						"$this->valueAddedTax," .                                        		// value_added_tax
						"$this->withholdingTax," .                                        		// withholding_tax
						"$this->interest," .                                                	// interest
						"$this->receiptAmount," .                                        		// receipt_amount
						"$this->amountReceivable," .                                        	// amount_payable
						"$this->amountReceivable," .                                        	// balance, set initially to amount_payable
						"0.000," .                                                            	// waived_balance, set to 0.00
						($this->agentID == null ? "NULL," : "$this->agentID,") .        		// agent_id
						($this->remarks == null ? "NULL," : "'$this->remarks',") .    			// remarks
						"NULL," .                                                            	// canceled_date
						"NULL)";                                                            	// cleared_date
			
			self::$database->query($sqlQuery);
			
			// get generated transaction ID
			$this->id = self::$database->getLastInsertID();
			
			// save purchase items
			$this->saveItems(self::MAX_PURCHASE_ITEMS);
			
			// save payment schedule
			$this->payment = new Payment();
			$this->payment->saveSchedule(self::$database, $this->getInstanceClassName($this), $this->id, $this->paymentTerm);
			
			// get supplier name to log
			$resultSet      = self::$database->query("SELECT name FROM supplier WHERE id = $supplierID");
			$supplier       = self::$database->getResultRow($resultSet);
			$purchaseNumber = $this->id;
			if ($this->salesInvoice != 'NULL') {
				$invoiceNumber = ' (SI ' . Filter::input($_POST['tracking_number']) . ')';
			} elseif ($this->deliveryReceipt != "NULL") {
				$invoiceNumber = ' (DR ' . Filter::input($_POST['tracking_number']) . ')';
			} else {
				$invoiceNumber = '';
			}
			
			// log event
			EventLog::addEntry(self::$database, 'info', 'purchase', 'insert', 'new',
							   '<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $purchaseNumber . '">' .
							   $purchaseNumber . '</a>' . $invoiceNumber .
							   ': </span>New purchase order for <a href="view_supplier_details.php?id=' . $supplierID . '">' .
							   capitalizeWords(Filter::output($supplier['name'])) . '</a>');
		} else {
			// existing purchase; update records
			$this->id = $_POST['purchase_id'];
			
			// update purchase
			if ($isItemEditable) {
				$sqlQuery = "UPDATE purchase SET " .
							"sales_invoice=$this->salesInvoice," .                                    				// sales_invoice
							"delivery_receipt=$this->deliveryReceipt," .                                			// delivery_receipt
							"purchase_number=$this->purchaseNumber," .                                				// purchase_number
							"supplier_id=$supplierID," .                                                			// supplier_id
							"business_unit=" . ($this->businessUnit == null ? "NULL," : "'$this->businessUnit',") .	// business unit
							"branch=" . ($this->branch == null ? "NULL," : "'$this->branch',") .     				// branch
							"transaction_type='$this->transactionType'," .                            				// transaction_type
							"delivery_pickup_target_date='$this->deliveryPickupTargetDate'," .        				// delivery_pickup_target_date
							"delivery_pickup_actual_date=" . ($this->deliveryPickupActualDate == null ?
															  "NULL," : "'$this->deliveryPickupActualDate',") .     // delivery_pickup_actual_date
							"payment_term='$this->paymentTerm'," .                                    				// payment_term
							"purchase_discount=$this->transactionDiscount," .                        				// purchase_discount
							"total_sales_amount=$this->totalSales," .                                				// total_sales_amount
							"value_added_tax=$this->valueAddedTax," .                                				// value_added_tax
							"withholding_tax=$this->withholdingTax," .                                				// withholding_tax
							"interest=$this->interest," .                                            				// interest
							"receipt_amount=$this->receiptAmount," .                                    			// receipt_amount
							"amount_payable=$this->amountReceivable," .                             				// amount_payable
							"balance=$this->amountReceivable," .                                        			// balance, reset to amount receivable
							"waived_balance=0.000," .                                                    			// waived_balance, reset to 0.00
							"agent_id=" . ($this->agentID == null ? "NULL," : "$this->agentID,") .        			// agent_id
							"remarks=" . ($this->remarks == null ? "NULL" : "'$this->remarks'") .      				// remarks
							" WHERE id=$this->id";
			} else {
				$sqlQuery = "UPDATE purchase SET " .
							"sales_invoice=$this->salesInvoice," .                                    				// sales_invoice
							"delivery_receipt=$this->deliveryReceipt," .                               	 			// delivery_receipt
							"purchase_number=$this->purchaseNumber," .                                				// purchase_number
							"supplier_id=$supplierID," .                                                			// supplier_id
							"business_unit=" . ($this->businessUnit == null ? "NULL," : "'$this->businessUnit',") . // business unit
							"branch=" . ($this->branch == null ? "NULL," : "'$this->branch',") .     				// branch
							"agent_id=" . ($this->agentID == null ? "NULL," : "$this->agentID,") .        			// agent_id
							"remarks=" . ($this->remarks == null ? "NULL" : "'$this->remarks'") .      				// remarks
							" WHERE id=$this->id";
			}
			
			self::$database->query($sqlQuery);
			
			// save order items and payment schedule
			if ($isItemEditable) {
				$this->saveItems(self::MAX_PURCHASE_ITEMS);
				$this->payment = new Payment();
				$this->payment->saveSchedule(self::$database, $this->getInstanceClassName($this), $this->id, $this->paymentTerm);
			}
			
			// get supplier name to log
			$resultSet = self::$database->query("SELECT name FROM supplier WHERE id = $supplierID");
			$supplier  = self::$database->getResultRow($resultSet);
			
			if ($this->salesInvoice != 'NULL') {
				$invoiceNumber = ' (SI ' . Filter::input($_POST['tracking_number']) . ')';
			} elseif ($this->deliveryReceipt != "NULL") {
				$invoiceNumber = ' (DR ' . Filter::input($_POST['tracking_number']) . ')';
			} else {
				$invoiceNumber = '';
			}
			
			// log event
			EventLog::addEntry(self::$database, 'info', 'purchase', 'update', 'modified',
							   '<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $this->id . '">' .
							   $this->id . '</a>' . $invoiceNumber .
							   ': </span>Purchase Order for <a href="view_supplier_details.php?id=' . $supplierID . '">' .
							   capitalizeWords(Filter::output($supplier['name'])) . '</a> was <span class="event_log_action">modified</span>');
		}
		
		return $this->id;
	}
	
	
	// -----------------------------------------------------------------------------------------------------------
	// display tasks for purchase list
	// -----------------------------------------------------------------------------------------------------------
	public static function showListTasks() {
		// get parameters
		if (!isset($_GET['criteria'])) {
			$criteria = 'recent-purchases';
		} else {
			$criteria = $_GET['criteria'];
		}
		
		?>
		<div id="tasks">
			<ul>
				<li id="task_add_order"><a href="purchase_supplies.php"><img src="images/task_buttons/add.png" />Purchase Supplies</a></li>
                <li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php
					$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br />' .
						 			 'This may take a few minutes. The system might not respond to other users while processing your request.' .
									 '<br /><br /><br />' .
									 '<div id="dialog_buttons">' .
						 			 '<input type="button" value="Yes" onclick="exportToExcelConfirm(' .
						 			 '\\\'data=purchase_list&criteria=' . $criteria . '\\\')" />' .
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
	// display list of purchases; ajax function
	// -----------------------------------------------------------------------------------------------------------
	public static function showList() {
		// get parameters
		if (!isset($_POST['criteria'])) {
			$criteria = 'recent-purchases';
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
			case 'recent-purchases':
				$condition = " WHERE canceled_date IS NULL AND cleared_date IS NULL";
				break;
			case 'purchases-awaiting-delivery':
				$condition = " WHERE transaction_type = 'delivery' AND delivery_pickup_actual_date IS NULL AND canceled_date IS NULL";
				break;
			case 'purchases-to-pickup':
				$condition = " WHERE transaction_type = 'pick-up' AND delivery_pickup_actual_date IS NULL AND canceled_date IS NULL";
				break;
			case 'purchases-to-pay':
				$condition = " WHERE v_accounts_payable.amount_payable > 0 AND canceled_date IS NULL";
				break;
			case 'payments-to-clear':
				$condition = " WHERE v_accounts_pdc_payable.pdc_payable > 0 AND canceled_date IS NULL";
				break;
			case 'purchases-to-pay-and-clear':
				$condition = " WHERE (v_accounts_payable.amount_payable > 0 OR v_accounts_pdc_payable.pdc_payable > 0) AND canceled_date IS NULL";
				break;
			case 'rebates-to-collect':
				$condition = " WHERE v_rebate_receivable.rebate_receivable > 0 AND canceled_date IS NULL";
				break;
			case 'rebates-to-clear':
				$condition = " WHERE v_rebate_pdc_receivable.pdc_rebate_receivable > 0 AND canceled_date IS NULL";
				break;
			case 'purchases-to-clear':
				$condition = " WHERE delivery_pickup_actual_date IS NOT NULL AND v_accounts_payable.amount_payable = 0 AND " .
							 "v_accounts_pdc_payable.pdc_payable IS NULL AND canceled_date IS NULL AND cleared_date IS NULL";
				break;
			case 'cleared-purchases':
				$condition = " WHERE cleared_date IS NOT NULL";
				break;
			case 'canceled-purchases':
				$condition = " WHERE canceled_date IS NOT NULL";
				break;
			default:
				$condition = '';
				break;
		}
		
		if ($filterName != null && $filterValue != null) {
			if ($condition == '') {
				$condition = " WHERE";
			} else {
				$condition = "$condition AND";
			}
			
			$condition = "$condition purchase.$filterName = '$filterValue'";
		}
		
		// count results prior to main query
		$sqlQuery = "SELECT COUNT(*) AS count FROM purchase " .
					"LEFT JOIN v_accounts_payable ON purchase.id = v_accounts_payable.purchase_id " .
					"LEFT JOIN v_accounts_pdc_payable ON purchase.id = v_accounts_pdc_payable.purchase_id " .
					"LEFT JOIN v_rebate_receivable ON purchase.id = v_rebate_receivable.purchase_id " .
					"LEFT JOIN v_rebate_pdc_receivable ON purchase.id = v_rebate_pdc_receivable.purchase_id " .
					$condition;
		
		
		self::$database = new Database;
		$resultSet      = self::$database->query($sqlQuery);
		$resultCount    = self::$database->getResultRow($resultSet);
		$resultCount    = $resultCount['count'];
		
		
		// construct main query
		$sqlQuery = "SELECT purchase.*, " .
					"v_accounts_payable.amount_payable AS accounts_payable, " .
					"IF(v_accounts_pdc_payable.pdc_payable IS NULL,0,v_accounts_pdc_payable.pdc_payable) AS pdc_payable, " .
					"(v_accounts_payable.amount_payable + " .
					"IF(v_accounts_pdc_payable.pdc_payable IS NULL,0,v_accounts_pdc_payable.pdc_payable))" .
					"AS total_payable, " .
					"v_rebate_receivable.rebate_receivable, " .
					"IF(v_rebate_pdc_receivable.pdc_rebate_receivable IS NULL,0,v_rebate_pdc_receivable.pdc_rebate_receivable) AS pdc_rebate_receivable, " .
					"(v_rebate_receivable.rebate_receivable + " .
					"IF(v_rebate_pdc_receivable.pdc_rebate_receivable IS NULL,0,v_rebate_pdc_receivable.pdc_rebate_receivable))" .
					"AS total_rebate_receivable, " .
					"purchase.amount_payable - purchase.waived_balance AS remaining_balance, " .
					"supplier.name AS supplier, " .
					"IF(SUM(purchase_item.quantity) IS NULL,0,SUM(purchase_item.quantity)) AS quantity, " .
					"IF(sales_invoice IS NOT NULL, CONCAT('SI ',sales_invoice), CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
					"IF(cleared_date IS NULL AND canceled_date IS NULL,DATEDIFF(NOW(),purchase.purchase_date),NULL) AS purchase_duration, " .
					"IF(cleared_date IS NOT NULL,'0-cleared'," .
					"IF(canceled_date IS NOT NULL,'1-canceled','2-pending'))" .
					"AS status, " .
					"check_number " .
					"FROM purchase " .
					"INNER JOIN supplier ON purchase.supplier_id = supplier.id " .
					"LEFT JOIN purchase_item ON purchase.id = purchase_item.purchase_id " .
					"LEFT JOIN purchase_payment ON purchase.id = purchase_payment.purchase_id " .
					"LEFT JOIN v_accounts_payable ON purchase.id = v_accounts_payable.purchase_id " .
					"LEFT JOIN v_accounts_pdc_payable ON purchase.id = v_accounts_pdc_payable.purchase_id " .
					"LEFT JOIN v_rebate_receivable ON purchase.id = v_rebate_receivable.purchase_id " .
					"LEFT JOIN v_rebate_pdc_receivable ON purchase.id = v_rebate_pdc_receivable.purchase_id " .
					$condition . " " .
					"GROUP BY purchase.id ORDER BY " . $sortColumn . " " . $sortMethod .
					($sortColumn == "total_payable" ? ", status " . $sortMethod : "") . " " .
					"LIMIT " . $offset . "," . $itemsPerPage;
		
		
		$resultSet = self::$database->query($sqlQuery);
		
		if (self::$database->getResultCount($resultSet) > 0) {
			if ($filterName == "supplier_id") {
				$columns = array(
					'id'                          => 'Purchase No.',
					'tracking_number'             => 'Invoice No.',
					//'purchase_date' => 'Purchase Date',
					'purchase_duration'           => 'Duration',
					'delivery_pickup_target_date' => 'Delivery/Pick-up Date',
					//'quantity' => 'No. of Items',
					'remaining_balance'           => 'Total Amount',
					'balance'                     => 'Amount Payable',
					'pdc_payable'                 => 'PDC Payable',
					'total_payable'               => 'Total Payable',
					'check_number'                => 'Check No.'
				);
			} else {
				$columns = array(
					'id'                          => 'Purchase No.',
					'tracking_number'             => 'Tracking No.',
					'supplier'                    => 'Supplier',
					//'purchase_date' => 'Purchase Date',
					'purchase_duration'           => 'Duration',
					'delivery_pickup_target_date' => 'Delivery/Pick-up Date',
					//'quantity' => 'No. of Items',
					'balance'                     => 'Amount Payable',
					'pdc_payable'                 => 'PDC Payable',
					'total_payable'               => 'Total Payable',
					'check_number'                => 'Check No.'
				);
			}
			
			self::showListHeader($columns, 'purchase_list_section', 'Purchase::showList', $criteria, $sortColumn, $sortMethod, $filterName, $filterValue);
			
			// display content
			while ($purchase = self::$database->getResultRow($resultSet)) {
				echo "<tr>";
				
				
				// purchase no.
				echo '<td><a href="view_purchase_details.php?id=' . $purchase['id'] . '&src=' . $criteria . '">' . $purchase['id'] . '</a></td>';
				
				
				// invoice no.
				echo '<td>';
				if ($purchase['canceled_date'] != null) {
					echo '<span class="canceled">' . $purchase['tracking_number'] . '</span>';
				} else {
					echo $purchase['tracking_number'];
				}
				echo '</td>';
				
				
				// supplier
				if ($filterName != "supplier_id") {
					echo '<td>' .
						 '<span class="long_text_clip">' .
						 '<a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '" title="' .
						 capitalizeWords(Filter::output($purchase['supplier'])) . '">' .
						 capitalizeWords(Filter::output($purchase['supplier'])) .
						 '</a>' .
						 '</span>' .
						 '</td>';
				}
				
				
				// purchase date
				/*echo '<td>';
				if ( $purchase['canceled_date'] != NULL ) {
					echo '<span class="canceled">' . dateFormatOutput( $purchase['purchase_date'], "M j, Y" ) . '</span>';
				} else {
					echo dateFormatOutput( $purchase['purchase_date'], "M j, Y" );
				}
				echo '</td>';*/
				
				
				// duration
				echo '<td>';
				if ($purchase['purchase_duration'] != null) {
					echo numberFormat($purchase['purchase_duration'], "int");
					if ($purchase['purchase_duration'] > 1) {
						echo " days old";
					} else {
						echo " day old";
					}
				}
				echo '</td>';
				
				
				// delivery/pick-up date
				echo '<td>';
				$deliveryPickupTargetDate = dateFormatOutput($purchase['delivery_pickup_target_date'], "Y-m-d");
				$currentDate              = date("Y-m-d");
				if ($purchase['canceled_date'] != null) {
					echo '<span class="canceled">' . dateFormatOutput($purchase['delivery_pickup_target_date'], "M j, Y") . '</span>';
				} elseif ($purchase['delivery_pickup_actual_date'] != null) {        // order is already delivered
					echo '<span class="good">' . dateFormatOutput($purchase['delivery_pickup_target_date'], "M j, Y") . '</span>';
					$deliveryPickupActualDate = dateFormatOutput($purchase['delivery_pickup_actual_date'], "Y-m-d");
					if ($deliveryPickupTargetDate < $deliveryPickupActualDate) {
						echo '<img src="images/warning.png" class="status_icon" title="Delayed. Actual Delivery/Pickup Date: ' .
							 dateFormatOutput($purchase['delivery_pickup_actual_date'], "M j, Y") . '" />';
					} else {
						echo '<img src="images/success.png" class="status_icon" title="On-time. Actual Delivery/Pickup Date: ' .
							 dateFormatOutput($purchase['delivery_pickup_actual_date'], "M j, Y") . '" />';
					}
				} elseif ($deliveryPickupTargetDate < $currentDate) {            // delivery/pick-up date is today
					echo '<span class="bad">' . dateFormatOutput($purchase['delivery_pickup_target_date'], "M j, Y") . '</span>';
				} else {
					echo dateFormatOutput($purchase['delivery_pickup_target_date'], "M j, Y");
				}
				
				echo '</td>';
				
				
				// no. of items
				/*echo '<td class="number">';
				if ( $purchase['canceled_date'] != NULL ) {
					echo '<span class="canceled">' . numberFormat( $purchase['quantity'], "int" ) . '</span>';
				} elseif ( $purchase['quantity'] <= 0 ) {
					echo '<span class="bad">' . numberFormat( $purchase['quantity'], "int" ) . '</span>';
				} else {
					echo '<span>' . numberFormat( $purchase['quantity'], "int" ) . '</span>';
				}
				echo '</td>';*/
				
				
				// remaining balance
				// in supplier details page only
				if ($filterName == "supplier_id") {
					echo '<td class="number">';
					if ($purchase['canceled_date'] != null) {
						echo '<span class="canceled">' . numberFormat($purchase['remaining_balance'], "float") . '</span>';
					} else {
						echo '<span>' . numberFormat($purchase['remaining_balance'], "float") . '</span>';
					}
					echo '</td>';
				}
				
				
				// amount payable
				echo '<td class="number">';
				if ($purchase['canceled_date'] != null) {
					echo '<span class="canceled">' . numberFormat($purchase['accounts_payable'], "float") . '</span>';
				} elseif ($purchase['accounts_payable'] == 0) {
					echo '<span class="good">' . numberFormat($purchase['accounts_payable'], "float") . '</span>';
				} else {
					echo '<span>' . numberFormat($purchase['accounts_payable'], "float") . '</span>';
				}
				echo '</td>';
				
				
				// pdc payable
				echo '<td class="number">';
				if ($purchase['canceled_date'] != null) {
					echo '<span class="canceled">' . numberFormat($purchase['pdc_payable'], "float") . '</span>';
				} elseif ($purchase['pdc_payable'] == 0 && $purchase['accounts_payable'] == 0) {
					echo '<span class="good">' . numberFormat($purchase['pdc_payable'], "float") . '</span>';
				} else {
					echo '<span>' . numberFormat($purchase['pdc_payable'], "float") . '</span>';
				}
				echo '</td>';
				
				
				// total receivable
				echo '<td class="number">';
				if ($purchase['canceled_date'] != null) {
					echo '<span class="canceled">Canceled</span>';
				} else {
					if ($purchase['cleared_date'] != null) {
						echo '<span class="good">Cleared!</span>';
					} elseif ($purchase['total_payable'] == 0) {
						echo '<span class="good">' . numberFormat($purchase['total_payable'], "float") . '</span>';
					} else {
						echo '<span>' . numberFormat($purchase['total_payable'], "float") . '</span>';
					}
					
					if ($purchase['rebate_receivable'] > 0 || $purchase['pdc_rebate_receivable'] > 0) {
						echo '<img src="images/rebate.png" class="status_icon" title="Pending rebate: ' .
							 numberFormat($purchase['rebate_receivable'], "currency", 3, '.', ',', true) .
							 ' | Rebate to clear: ' .
							 numberFormat($purchase['pdc_rebate_receivable'], "currency", 3, '.', ',', true) . '" />';
					} elseif ($purchase['cleared_date'] != null) {
						echo '<img src="images/success.png" class="status_icon" title="Purchase is cleared" />';
					}
				}
				echo '</td>';
				
				
				// check no.
				echo '<td>';
				if ($purchase['canceled_date'] != null) {
					echo '<span class="canceled">' . $purchase['check_number'] . '</span>';
				} else {
					echo '<span>' . $purchase['check_number'] . '</span>';
				}
				echo '</td>';
				
				
				echo "</tr>";
			}
			
			echo '</tbody>';
			echo '</table>';
			
			
			self::showPagination($page, $itemsPerPage, $resultCount, 'purchase_list_section', 'Purchase::showList', $criteria, $sortColumn, $sortMethod,
								 $filterName, $filterValue);
			
			
			// totals fieldset
			if ($filterName != "supplier_id" && $filterName != "agent_id") {
				echo '<fieldset><legend>Totals</legend>';
				
				$sqlQuery = "SELECT SUM(v_accounts_payable.amount_payable) AS total_amount_payable, " .
							"SUM(v_accounts_pdc_payable.pdc_payable) AS total_pdc_payable, " .
							"SUM(v_rebate_receivable.rebate_receivable) AS total_rebate_receivable, " .
							"SUM(v_rebate_pdc_receivable.pdc_rebate_receivable) AS total_pdc_rebate_receivable " .
							"FROM purchase " .
							"LEFT JOIN v_accounts_payable ON purchase.id = v_accounts_payable.purchase_id " .
							"LEFT JOIN v_accounts_pdc_payable ON purchase.id = v_accounts_pdc_payable.purchase_id " .
							"LEFT JOIN v_rebate_receivable ON purchase.id = v_rebate_receivable.purchase_id " .
							"LEFT JOIN v_rebate_pdc_receivable ON purchase.id = v_rebate_pdc_receivable.purchase_id " .
							$condition;
				
				$resultSet = self::$database->query($sqlQuery);
				$purchase  = self::$database->getResultRow($resultSet);
				
				if ($purchase['total_pdc_payable'] == null) {
					$purchase['total_pdc_payable'] = 0;
				}
				
				if ($purchase['total_pdc_rebate_receivable'] == null) {
					$purchase['total_pdc_rebate_receivable'] = 0;
				}
				
				echo '<div>' .
					 '<span class="record_label">Number of Purchases:</span>' .
					 '<span class="record_data">' . numberFormat($resultCount, "int") . '</span>' .
					 '</div>';
				
				echo '<br /><br />';
				
				echo '<div>' .
					 '<span class="record_label">Total Amount Payable:</span>' .
					 '<span class="record_data">' . numberFormat($purchase['total_amount_payable'], "currency") . '</span>' .
					 '</div>';
				
				echo '<div>' .
					 '<span class="record_label">Total PDC Payable:</span>' .
					 '<span class="record_data">' . numberFormat($purchase['total_pdc_payable'], "currency") . '</span>' .
					 '</div>';
				
				echo '<div>' .
					 '<span class="record_label">Total Payable:</span>' .
					 '<span class="record_data">' . numberFormat($purchase['total_amount_payable'] + $purchase['total_pdc_payable'], "currency") . '</span>' .
					 '</div>';
				
				echo '<br /><br />';
				
				echo '<div>' .
					 '<span class="record_label">Total Rebate Receivable:</span>' .
					 '<span class="record_data">' . numberFormat($purchase['total_rebate_receivable'], "currency") . '</span>' .
					 '</div>';
				
				echo '<div>' .
					 '<span class="record_label">Total PDC Rebate:</span>' .
					 '<span class="record_data">' . numberFormat($purchase['total_pdc_rebate_receivable'], "currency") . '</span>' .
					 '</div>';
				
				echo '<div>' .
					 '<span class="record_label">Total Rebate:</span>' .
					 '<span class="record_data">' . numberFormat($purchase['total_rebate_receivable'] + $purchase['total_pdc_rebate_receivable'], "currency") .
					 '</span>' .
					 '</div>';
				
				echo '</fieldset>';
			}
		} else {
			echo "<div>No purchases found for this criteria.</div>";
		}
	}
	
	
	// export order list to Excel file, ajax function
	public static function exportListToExcel( $username, $paramArray ) {
		$fileTimeStampExtension = date(EXCEL_FILE_TIMESTAMP_FORMAT);
		$headingTimeStamp       = dateFormatOutput($fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT);
		
		require_once("classes/Filter.php");
		
		self::$database = new Database();
		
		
		// get parameters
		switch ($paramArray['criteria']) {
			case "recent-purchases":
				$fileName   = 'Recent Purchases';
				$sheetTitle = 'Recent Purchases';
				$condition  = " WHERE canceled_date IS NULL AND cleared_date IS NULL";
				break;
			case "purchases-awaiting-delivery":
				$fileName   = 'Purchases awaiting Delivery';
				$sheetTitle = 'Purchases Awaiting Delivery from Suppliers';
				$condition  = " WHERE transaction_type = 'delivery' AND delivery_pickup_actual_date IS NULL AND canceled_date IS NULL";
				break;
			case "purchases-to-pickup":
				$fileName   = 'Purchases to Pick-up';
				$sheetTitle = 'Purchases to Pick-up to Suppliers';
				$condition  = " WHERE transaction_type = 'pick-up' AND delivery_pickup_actual_date IS NULL AND canceled_date IS NULL";
				break;
			case "purchases-to-pay":
				$fileName   = 'Purchases to Pay';
				$sheetTitle = 'Purchases to Pay';
				$condition  = " WHERE v_accounts_payable.amount_payable > 0 AND canceled_date IS NULL";
				break;
			case "payments-to-clear":
				$fileName   = 'Purchases with Payments to Clear';
				$sheetTitle = 'Purchases with Payments to Clear';
				$condition  = " WHERE v_accounts_pdc_payable.pdc_payable > 0 AND canceled_date IS NULL";
				break;
			case "purchases-to-pay-and-clear":
				$fileName   = 'Purchases to Pay and Clear';
				$sheetTitle = 'Purchases to Pay and Clear';
				$condition  = " WHERE (v_accounts_payable.amount_payable > 0 OR v_accounts_pdc_payable.pdc_payable > 0) AND canceled_date IS NULL";
				break;
			case "rebates-to-collect":
				$fileName   = 'Purchases with Rebates to Collect';
				$sheetTitle = 'Purchases with Rebates to Collect';
				$condition  = " WHERE v_rebate_receivable.rebate_receivable > 0 AND canceled_date IS NULL";
				break;
			case "rebates-to-clear":
				$fileName   = 'Purchases with Rebates to Clear';
				$sheetTitle = 'Purchases with Rebates to Clear';
				$condition  = " WHERE v_rebate_pdc_receivable.pdc_rebate_receivable > 0 AND canceled_date IS NULL";
				break;
			case "purchases-to-clear":
				$fileName   = 'Purchases to Clear';
				$sheetTitle = 'Purchases to Clear';
				$condition  = " WHERE delivery_pickup_actual_date IS NOT NULL AND v_accounts_payable.amount_payable = 0 AND " .
							  "v_accounts_pdc_payable.pdc_payable IS NULL AND canceled_date IS NULL AND cleared_date IS NULL";
				break;
			case "cleared-purchases":
				$fileName   = 'Cleared Purchases';
				$sheetTitle = 'Cleared Purchases';
				$condition  = " WHERE cleared_date IS NOT NULL";
				break;
			case "canceled-purchases":
				$fileName   = 'Canceled Purchases';
				$sheetTitle = 'Canceled Purchases';
				$condition  = " WHERE canceled_date IS NOT NULL";
				break;
			default:
				$fileName   = 'All Purchases';
				$sheetTitle = 'All Purchases';
				$condition  = "";
				break;
		}
		
		// construct query
		$sqlQuery = "SELECT purchase.*, " .
					"v_accounts_payable.amount_payable AS accounts_payable, " .
					"IF(v_accounts_pdc_payable.pdc_payable IS NULL,0,v_accounts_pdc_payable.pdc_payable) AS pdc_payable, " .
					"(v_accounts_payable.amount_payable + " .
					"IF(v_accounts_pdc_payable.pdc_payable IS NULL,0,v_accounts_pdc_payable.pdc_payable))" .
					"AS total_payable, " .
					"v_rebate_receivable.rebate_receivable, " .
					"IF(v_rebate_pdc_receivable.pdc_rebate_receivable IS NULL,0,v_rebate_pdc_receivable.pdc_rebate_receivable) AS pdc_rebate_receivable, " .
					"(v_rebate_receivable.rebate_receivable + " .
					"IF(v_rebate_pdc_receivable.pdc_rebate_receivable IS NULL,0,v_rebate_pdc_receivable.pdc_rebate_receivable))" .
					"AS total_rebate_receivable, " .
					"purchase.amount_payable - purchase.waived_balance AS remaining_balance, " .
					"supplier.name AS supplier, " .
					"agent.name AS agent, " .
					"IF(SUM(purchase_item.quantity) IS NULL,0,SUM(purchase_item.quantity)) AS quantity, " .
					"IF(sales_invoice IS NOT NULL, CONCAT('SI ',sales_invoice), CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
					"IF(cleared_date IS NULL AND canceled_date IS NULL,DATEDIFF(NOW(),purchase.purchase_date),NULL) AS purchase_duration, " .
					"IF(cleared_date IS NOT NULL,'0-cleared'," .
					"IF(canceled_date IS NOT NULL,'1-canceled','2-pending'))" .
					"AS status " .
					"FROM purchase " .
					"INNER JOIN supplier ON purchase.supplier_id = supplier.id " .
					"INNER JOIN agent ON purchase.agent_id = agent.id " .
					"LEFT JOIN purchase_item ON purchase.id = purchase_item.purchase_id " .
					"LEFT JOIN v_accounts_payable ON purchase.id = v_accounts_payable.purchase_id " .
					"LEFT JOIN v_accounts_pdc_payable ON purchase.id = v_accounts_pdc_payable.purchase_id " .
					"LEFT JOIN v_rebate_receivable ON purchase.id = v_rebate_receivable.purchase_id " .
					"LEFT JOIN v_rebate_pdc_receivable ON purchase.id = v_rebate_pdc_receivable.purchase_id " .
					$condition . " " .
					"GROUP BY purchase.id ORDER BY purchase.id DESC";
		
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
					->setTitle($sheetTitle . ' as of ' . $headingTimeStamp)
					->setSubject(EXCEL_FILE_SUBJECT)
					->setDescription(EXCEL_FILE_DESCRIPTION)
					->setKeywords(EXCEL_FILE_KEYWORDS)
					->setCategory(EXCEL_FILE_CATEGORY);
		
		// create a first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		
		// rename present sheet
		$activeSheet->setTitle('Purchase List');
		
		// set default font
		$activeSheet->getDefaultStyle()->getFont()->setName(EXCEL_DEFAULT_FONT_NAME)
					->setSize(EXCEL_DEFAULT_FONT_SIZE);
		
		// write sheet headers
		$activeSheet->setCellValue('A1', CLIENT);
		$activeSheet->setCellValue('A2', $sheetTitle);
		$activeSheet->setCellValue('A3', 'As of ' . $headingTimeStamp);
		
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
		$activeSheet->setCellValue('A' . $FIELD_HEADER_ROW, 'Purchase No.')
					->setCellValue('B' . $FIELD_HEADER_ROW, 'Invoice No.')
					->setCellValue('C' . $FIELD_HEADER_ROW, 'SI/DR')
					->setCellValue('D' . $FIELD_HEADER_ROW, 'Supplier')
					->setCellValue('E' . $FIELD_HEADER_ROW, 'Business Unit')
					->setCellValue('F' . $FIELD_HEADER_ROW, 'Supplier\'s P.O. No.')
					->setCellValue('G' . $FIELD_HEADER_ROW, 'Purchase Date')
					->setCellValue('H' . $FIELD_HEADER_ROW, 'Duration (days)')
					->setCellValue('I' . $FIELD_HEADER_ROW, 'Target Delivery Date')
					->setCellValue('J' . $FIELD_HEADER_ROW, 'Date Delivered')
					->setCellValue('K' . $FIELD_HEADER_ROW, 'No. of Items')
					->setCellValue('L' . $FIELD_HEADER_ROW, 'Total Amount (' . CURRENCY . ')')
					->setCellValue('M' . $FIELD_HEADER_ROW, 'Amount Payable (' . CURRENCY . ')')
					->setCellValue('N' . $FIELD_HEADER_ROW, 'PDC Payable (' . CURRENCY . ')')
					->setCellValue('O' . $FIELD_HEADER_ROW, 'Total Payable (' . CURRENCY . ')')
					->setCellValue('P' . $FIELD_HEADER_ROW, 'Status')
					->setCellValue('Q' . $FIELD_HEADER_ROW, 'Rebate Receivable (' . CURRENCY . ')')
					->setCellValue('R' . $FIELD_HEADER_ROW, 'PDC Rebate (' . CURRENCY . ')')
					->setCellValue('S' . $FIELD_HEADER_ROW, 'Total Rebate (' . CURRENCY . ')')
					->setCellValue('T' . $FIELD_HEADER_ROW, 'Agent')
					->setCellValue('U' . $FIELD_HEADER_ROW, 'Notes/Comments');
		
		// set column widths
		$activeSheet->getColumnDimension('A')->setWidth(15);
		$activeSheet->getColumnDimension('B')->setWidth(12);
		$activeSheet->getColumnDimension('C')->setWidth(10);
		$activeSheet->getColumnDimension('D')->setWidth(50);
		$activeSheet->getColumnDimension('E')->setWidth(15);
		$activeSheet->getColumnDimension('F')->setWidth(20);
		$activeSheet->getColumnDimension('G')->setWidth(15);
		$activeSheet->getColumnDimension('H')->setWidth(15);
		$activeSheet->getColumnDimension('I')->setWidth(20);
		$activeSheet->getColumnDimension('J')->setWidth(15);
		$activeSheet->getColumnDimension('K')->setWidth(15);
		$activeSheet->getColumnDimension('L')->setWidth(20);
		$activeSheet->getColumnDimension('M')->setWidth(20);
		$activeSheet->getColumnDimension('N')->setWidth(20);
		$activeSheet->getColumnDimension('O')->setWidth(20);
		$activeSheet->getColumnDimension('P')->setWidth(15);
		$activeSheet->getColumnDimension('Q')->setWidth(22);
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
			while ($purchase = self::$database->getResultRow($resultSet)) {
				// purchase no.
				$activeSheet->setCellValue('A' . $rowPtr, $purchase['id']);
				
				// invoice number
				$activeSheet->setCellValue('B' . $rowPtr, $purchase['tracking_number']);
				
				// SI/DR
				if ($purchase['sales_invoice'] != null) {
					$activeSheet->setCellValue('C' . $rowPtr, 'SI');
				} else {
					$activeSheet->setCellValue('C' . $rowPtr, 'DR');
				}
				
				// supplier, business unit
				$supplierName = html_entity_decode(capitalizeWords(Filter::reinput($purchase['supplier'])));
				$activeSheet->setCellValue('D' . $rowPtr, $supplierName)
							->setCellValue('E' . $rowPtr, $purchase['business_unit']);
				
				// supplier's P.O. no
				//$activeSheet->getCell( 'F' . $rowPtr )->setValueExplicit( $purchase['purchase_number'], PHPExcel_Cell_DataType::TYPE_STRING );
				$activeSheet->setCellValue('F' . $rowPtr, stripslashes($purchase['purchase_number']));
				
				// purchase date
				$activeSheet->setCellValue('G' . $rowPtr, dateFormatOutput($purchase['purchase_date'], EXCEL_DATE_FORMAT_INPUT));
				
				// duration (days)
				if ($purchase['purchase_duration'] != null) {
					$activeSheet->setCellValue('H' . $rowPtr, $purchase['purchase_duration']);
				}
				
				// target delivery date
				$deliveryPickupTargetDate = dateFormatOutput($purchase['delivery_pickup_target_date'], EXCEL_DATE_FORMAT_INPUT);
				$activeSheet->setCellValue('I' . $rowPtr, $deliveryPickupTargetDate);
				
				// highlight missed target delivery date
				$currentDate = date(EXCEL_DATE_FORMAT_INPUT);
				if ($deliveryPickupTargetDate < $currentDate && $purchase['delivery_pickup_actual_date'] == null) {
					$activeSheet->getStyle('I' . $rowPtr)->getFont()->setColor($fontColorDarkRed);
					$activeSheet->getComment('I' . $rowPtr)->getText()
								->createTextRun('Target ' . ($purchase['transaction_type'] == 'delivery' ? 'delivery' : 'pick-up') . ' date is already missed')
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// date delivered
				if ($purchase['delivery_pickup_actual_date'] != null) {
					$deliveryPickupActualDate = dateFormatOutput($purchase['delivery_pickup_actual_date'], EXCEL_DATE_FORMAT_INPUT);
					$activeSheet->setCellValue('J' . $rowPtr, $deliveryPickupActualDate);
					
					// highlight late delivery
					if ($deliveryPickupTargetDate < $deliveryPickupActualDate) {
						$activeSheet->getStyle('J' . $rowPtr)->getFont()->setColor($fontColorDarkRed);
						$activeSheet->getComment('J' . $rowPtr)->getText()
									->createTextRun('Target ' . ($purchase['transaction_type'] == 'delivery' ? 'delivery' : 'pick-up') .
													' date has been missed')
									->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
					}
				} else {
					$activeSheet->getComment('J' . $rowPtr)->getText()
								->createTextRun('Purchased items are not yet ' .
												($purchase['transaction_type'] == 'delivery' ? 'delivered by supplier' : 'picked-up from supplier'))
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// no. of items
				if ($purchase['quantity'] > 0) {
					$activeSheet->setCellValue('K' . $rowPtr, $purchase['quantity']);
				} else {
					$activeSheet->getComment('K' . $rowPtr)->getText()
								->createTextRun('Nothing was ordered')
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// total amount
				if ($purchase['amount_payable'] > 0) {
					$activeSheet->setCellValue('L' . $rowPtr, $purchase['amount_payable']);
				} else {
					$activeSheet->getComment('L' . $rowPtr)->getText()
								->createTextRun('This purchase order has no amount')
								->getFont()->setSize(EXCEL_COMMENT_FONT_SIZE);
				}
				
				// amount payable
				if ($purchase['accounts_payable'] > 0) {
					$activeSheet->setCellValue('M' . $rowPtr, $purchase['accounts_payable']);
				}
				
				// pdc payable
				if ($purchase['pdc_payable'] > 0) {
					$activeSheet->setCellValue('N' . $rowPtr, $purchase['pdc_payable']);
				}
				
				// total payable
				$activeSheet->setCellValue('O' . $rowPtr, '=M' . $rowPtr . '+N' . $rowPtr);
				if ($activeSheet->getCell('O' . $rowPtr)->getCalculatedValue() == 0.000) {
					$activeSheet->getStyle('O' . $rowPtr)->getFont()->setColor($fontColorGreen);
				}
				
				// status
				if ($purchase['cleared_date'] != null) {
					$activeSheet->setCellValue('P' . $rowPtr, 'Cleared');
				} elseif ($purchase['canceled_date'] != null) {
					$activeSheet->setCellValue('P' . $rowPtr, 'Canceled');
				}
				
				// rebate receivable
				if ($purchase['rebate_receivable'] > 0) {
					$activeSheet->setCellValue('Q' . $rowPtr, $purchase['rebate_receivable']);
				}
				
				// pdc rebate
				if ($purchase['pdc_rebate_receivable'] > 0) {
					$activeSheet->setCellValue('R' . $rowPtr, $purchase['pdc_rebate_receivable']);
				}
				
				// total rebate
				$activeSheet->setCellValue('S' . $rowPtr, '=Q' . $rowPtr . '+R' . $rowPtr);
				if ($activeSheet->getCell('S' . $rowPtr)->getCalculatedValue() == 0.000) {
					$activeSheet->getStyle('S' . $rowPtr)->getFont()->setColor($fontColorGreen);
				}
				
				$activeSheet->setCellValue('T' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($purchase['agent']))));
				
				// notes/comments
				$activeSheet->getCell('U' . $rowPtr)->setValueExplicit(stripslashes($purchase['remarks']), PHPExcel_Cell_DataType::TYPE_STRING);
				
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
		$activeSheet->getStyle('A6:A' . $rowPtr)->getFont()->setBold(true);                                    // set Purchase No. to bold
		$activeSheet->getStyle('D6:D' . $rowPtr)->getAlignment()->setWrapText(true);                        // wrap Supplier
		$activeSheet->getStyle('G6:G' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_DATE_FORMAT);        // format Purchase Date
		$activeSheet->getStyle('H6:H' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);        // format Duration
		$activeSheet->getStyle('I6:J' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_DATE_FORMAT);        // format Target Delivery Date and Date Delivered
		$activeSheet->getStyle('K6:K' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);        // format No. of Items
		$activeSheet->getStyle('L6:O' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);    // format amounts
		$activeSheet->getStyle('Q6:S' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);    // format rebate
		$activeSheet->getStyle('L6:L' . $rowPtr)->getFont()->setBold(true);                                    // set Total Amount to bold
		$activeSheet->getStyle('O6:O' . $rowPtr)->getFont()->setBold(true);                                    // set Total Payable to bold
		$activeSheet->getStyle('S6:S' . $rowPtr)->getFont()->setBold(true);                                    // set Total Rebate to bold
		$activeSheet->getStyle('T6:U' . $rowPtr)->getAlignment()->setWrapText(true);                        // wrap Agent and Notes/Comments
		
		// set columns to left aligned
		$activeSheet->getStyle('A6:F' . $rowPtr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		$activeSheet->getStyle('T6:U' . $rowPtr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		// conditional formatting
		// * set Cleared to green, Canceled to grey
		$conditionalStyles = $activeSheet->getStyle('P6:P' . $rowPtr)->getConditionalStyles();
		
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
		
		$activeSheet->getStyle('P6:P' . $rowPtr)->setConditionalStyles($conditionalStyles);
		
		// write totals
		$totalsRow = $rowPtr + 3;
		$activeSheet->setCellValue('A' . $totalsRow, 'Total Number of Purchases:')
					->setCellValue('C' . $totalsRow, $itemCount)
					->setCellValue('J' . $totalsRow, 'Totals:')
					->setCellValue('K' . $totalsRow, '=SUM(K6:K' . $rowPtr . ')')
					->setCellValue('L' . $totalsRow, '=SUM(L6:L' . $rowPtr . ')')
					->setCellValue('M' . $totalsRow, '=SUM(M6:M' . $rowPtr . ')')
					->setCellValue('N' . $totalsRow, '=SUM(N6:N' . $rowPtr . ')')
					->setCellValue('O' . $totalsRow, '=SUM(O6:O' . $rowPtr . ')')
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
		$activeSheet->getStyle('K' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);
		$activeSheet->getStyle('L' . $totalsRow . ':O' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$activeSheet->getStyle('Q' . $totalsRow . ':S' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
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
	
	
	// tasks for purchase details
	public function showDetailsTasks() {
		$sqlQuery  =
			"SELECT id, purchase_number, transaction_type, amount_payable, balance, IF(canceled_date IS NOT NULL,'canceled',IF(cleared_date IS NOT NULL,'cleared',IF(delivery_pickup_actual_date IS NULL,'not-delivered','all-delivered'))) AS order_status, IF(balance <= 0,'fully-paid',IF(balance = amount_payable,'no-payment','partially-paid')) AS payment_status FROM purchase WHERE purchase.id = " .
			$this->id;
		$resultSet = self::$database->query($sqlQuery);
		$purchase  = self::$database->getResultRow($resultSet);
		
		if ($purchase['order_status'] == 'not-delivered') {
			$sqlQuery  = "SELECT id FROM purchase_item WHERE purchase_id = " . $this->id . " AND quantity != undelivered_quantity";
			$resultSet = self::$database->query($sqlQuery);
			if (self::$database->getResultCount($resultSet) > 0) {
				$purchase['order_status'] = 'partially-delivered';
			}
		}
		
		if ($purchase['payment_status'] == 'fully-paid') {
			$sqlQuery  =
				"SELECT clearing_actual_date FROM purchase_payment WHERE purchase_id = " . $this->id . " AND clearing_actual_date IS NULL AND amount >= 0";
			$resultSet = self::$database->query($sqlQuery);
			if (self::$database->getResultCount($resultSet) > 0)        // there is still unclear payment or rebate
			{
				$purchase['payment_status'] = 'fully-paid-not-cleared';
			}
		}
		
		
		?>		<div id="tasks">
			<ul>
				<li id="task_edit_order" style="display: none"><a href="edit_purchase.php?id=<?php echo $purchase['id'] ?>">
						<img src="images/task_buttons/edit.png" />Edit Order</a></li>
				<li id="task_cancel_order" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
		
		// display confirmation to mark order as canceled
		$dialogMessage = "<b>Cancel</b> Purchase No. " . $purchase['id'] . "?<br /><br /><br /><br /><br />";
		
		// Yes and No buttons
		$dialogMessage = $dialogMessage . "<div id=\"dialog_buttons\">";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"Yes\" onclick=\"cancelOrderCommit(\'" . $purchase['id'] . "\')\" />";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />";
		$dialogMessage = $dialogMessage . "</div>";
		
		$dialogMessage = htmlentities($dialogMessage);
		
		echo $dialogMessage;
		
		?>','warning')"><img src="images/task_buttons/cancel.png" />Cancel Purchase...</a></li>
				<li id="task_undo_cancel_order" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
		
		// display confirmation to undo cancelling of order
		$dialogMessage = "Undo cancelling Purchase No. " . $purchase['id'] . "?<br /><br /><br /><br /><br />";
		
		// Yes and No buttons
		$dialogMessage = $dialogMessage . "<div id=\"dialog_buttons\">";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"Yes\" onclick=\"undoCancelOrderCommit(\'" . $purchase['id'] . "\')\" />";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />";
		$dialogMessage = $dialogMessage . "</div>";
		
		$dialogMessage = htmlentities($dialogMessage);
		
		echo $dialogMessage;
		
		?>','warning')"><img src="images/task_buttons/undo.png" />Undo Cancel...</a></li>
                <li id="task_mark_as_cleared_notice" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
		
		// display confirmation to mark order as delivered
		$dialogMessage = "<b>Notice:</b> Clear all payments first before marking this order as cleared. " .
						 "If there is excess payment, issue rebate first.<br /><br /><br /><br />" .
						 "<div id=\"dialog_buttons\">" .
						 "<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />" .
						 "</div>";
		
		$dialogMessage = htmlentities($dialogMessage);
		
		echo $dialogMessage;
		
		?>','warning')"><img src="images/task_buttons/clear.png" />Mark as Cleared...</a></li>
				<li id="task_mark_as_cleared" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
		
		// display confirmation to mark order as delivered
		$dialogMessage = "Mark Purchase No. " . $purchase['id'] . " as Cleared?<br /><br />";
		$dialogMessage = $dialogMessage . "Once cleared, this transaction will be considered complete.<br /><br /><br />";
		
		// Yes and No buttons
		$dialogMessage = $dialogMessage . "<div id=\"dialog_buttons\">";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"Yes\" onclick=\"markAsClearedCommit(\'" . $purchase['id'] . "\')\" />";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />";
		$dialogMessage = $dialogMessage . "</div>";
		
		$dialogMessage = htmlentities($dialogMessage);
		
		echo $dialogMessage;
		
		?>','prompt')"><img src="images/task_buttons/clear.png" />Mark as Cleared...</a></li>
				<li id="task_unclear_order" style="display: none"><a href="javascript:void(0)" onclick="showDialog('Change Status','<?php
		
		// display confirmation to unclear order
		$dialogMessage = "Unclear Purchase No. " . $purchase['id'] . "?<br /><br /><br /><br /><br />";
		
		// Yes and No buttons
		$dialogMessage = $dialogMessage . "<div id=\"dialog_buttons\">";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"Yes\" onclick=\"unclearOrderCommit(\'" . $purchase['id'] . "\')\" />";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />";
		$dialogMessage = $dialogMessage . "</div>";
		
		$dialogMessage = htmlentities($dialogMessage);
		
		echo $dialogMessage;
		
		?>','warning')"><img src="images/task_buttons/undo.png" />Unclear...</a></li>
				<li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php
		
		// display confirmation to unclear order
		$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br /><br /><br /><br />';
		
		// Yes and No buttons
		$dialogMessage = $dialogMessage . '<div id="dialog_buttons">' .
						 '<input type="button" value="Yes" onclick="exportToExcelConfirm(' .
						 '\\\'data=purchase_details&purchaseID=' . $this->id . '\\\')" />' .
						 '<input type="button" value="No" onclick="hideDialog()" />' .
						 '</div>';
		
		$dialogMessage = htmlentities($dialogMessage);
		
		echo $dialogMessage;
		
		?>','prompt')"><img src="images/task_buttons/export_to_excel.png" />Export to Excel...</a></li>
				<li id="task_back_to_list"><a href="list_purchases.php<?php echo(isset($_GET['src']) ? '?criteria=' . $_GET['src'] : '') ?>">
						<img src="images/task_buttons/back_to_list.png" />Back to List</a></li>
			</ul>
		</div>

		<script type="text/javascript">
		<!--
			// update global variable
			orderStatus = '<?php echo $purchase['order_status'] ?>';
			paymentStatus = '<?php echo $purchase['payment_status'] ?>';
			transactionType = '<?php echo $purchase['transaction_type'] ?>';
			
			$(document).ready( function () {
				reorganizePurchaseDetailsTasks();
				showPurchaseStatusLabel();
			});
		// -->
		</script>
	</div>
<?php
	}
	
	
	// view order details
	public function view() {
		// get main order info
		$sqlQuery =
			"SELECT purchase.*, IF(canceled_date IS NOT NULL,'canceled',IF(cleared_date IS NOT NULL,'cleared',IF(delivery_pickup_actual_date IS NULL,'pending','delivered'))) AS status, " .
			"supplier.name AS supplier_name, " .
			"agent.name AS agent_name, " .
			"IF(cleared_date IS NULL AND canceled_date IS NULL,DATEDIFF(NOW(),purchase.purchase_date),-1) AS order_duration " .
			"FROM purchase " .
			"INNER JOIN supplier ON supplier.id = purchase.supplier_id " .
			"LEFT JOIN agent ON agent.id = purchase.agent_id " .
			"WHERE purchase.id = " . $this->id;
		
		$resultSet = self::$database->query($sqlQuery);
		
		if (self::$database->getResultCount($resultSet) > 0) {
			$purchase = self::$database->getResultRow($resultSet);
			
			// display
			?>
			<fieldset>
				<legend>Purchase Info</legend>
				<section class="main_record_label">
					<div><?php echo "Purchase No. " . $purchase['id'] ?></div>
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
					if ($purchase['sales_invoice'] != null) {
						echo "Sales Invoice No:";
						$trackingNumber = $purchase['sales_invoice'];
					} elseif ($purchase['delivery_receipt'] != null) {
						echo "Delivery Receipt No:";
						$trackingNumber = $purchase['delivery_receipt'];
					} else {
						echo "SI/DR:";
					}
					?></span>
				<span class="record_data"><?php
					if ($purchase['sales_invoice'] != null || $purchase['delivery_receipt'] != null) {
						echo '<b>' . $trackingNumber . '</b>';
					} else {
						echo "(none)";
					}
					?></span>
					</div>
					<div>
						<span class="record_label">Supplier:</span>
						<span class="record_data"><a
								href="view_supplier_details.php?id=<?php echo $purchase['supplier_id'] ?>"><?php echo capitalizeWords(Filter::output($purchase['supplier_name'])) ?></a></span>
					</div>
					<div>
						<span class="record_label">Purchase Date:</span>
				<span class="record_data"><?php
					echo dateFormatOutput($purchase['purchase_date']);
					if ($purchase['order_duration'] > -1) {
						echo " (" . numberFormat($purchase['order_duration'], "int");
						if ($purchase['order_duration'] > 1) {
							echo " days old)";
						} else {
							echo " day old)";
						}
					}
					?></span>
					</div>
				</section>
				
				<section>
					<div>
						<span class="record_label">Supplier's P.O. No:</span>
						<span class="record_data"><?php echo($purchase['purchase_number'] != null ? Filter::reinput($purchase['purchase_number']) :
								"(none)") ?></a></span>
					</div>
				</section>
				
				<section>
					<div>
						<span class="record_label">Transaction Type:</span>
						<span class="record_data"><?php echo ucfirst($purchase['transaction_type']) ?></span>
					</div>
					<div>
						<span class="record_label"><?php echo ucfirst($purchase['transaction_type']) ?> Date:</span>
				<span class="record_data"><?php
					echo dateFormatOutput($purchase['delivery_pickup_target_date'], "F j, Y, D");
					
					if ($purchase['delivery_pickup_actual_date'] != null) {
						if ($purchase['transaction_type'] == "delivery") {
							echo " (Delivered: ";
						} else {
							echo " (Picked-Up: ";
						}
						
						echo dateFormatOutput($purchase['delivery_pickup_actual_date'], "F j, Y, D") . ")";
					}
					?></span>
					</div>
				</section>
				
				<section>
					<?php
					if (sizeof($GLOBALS['BUSINESS_UNITS']) > 0) {
						echo '<div>' .
							 '<span class="record_label">Business Unit:</span>' .
							 '<span class="record_data">' . $purchase['business_unit'] . '</span>' .
							 '</div>';
					}
					
					if (sizeof($GLOBALS['BRANCHES']) > 0) {
						echo '<div>' .
							 '<span class="record_label">Branch:</span>' .
							 '<span class="record_data">' . $purchase['branch'] . '</span>' .
							 '</div>';
					}
					
					if (Registry::get('transaction.agent.enabled') == true) {
						echo '<div>' .
							 '<span class="record_label">Agent:</span>' .
							 '<span class="record_data"><a href="view_agent_details.php?id=' . $purchase['agent_id'] . '">' .
							 capitalizeWords(Filter::output($purchase['agent_name'])) . '</a></span>' .
							 '</div>';
					}
					
					if (!is_null($purchase['remarks'])) {
						echo '<div>' .
							 '<span class="record_label">Notes/Comments:</span>' .
							 '<span class="record_data">' . Filter::output($purchase['remarks']) . '</span>' .
							 '</div>';
					}
					?></section>
				
				<section>
					<div></div>
				</section>
				
				<?php
				// get order items
				$sqlQuery = "SELECT purchase_item.*, inventory.model, inventory_brand.name AS brand_name FROM purchase_item";
				$sqlQuery = $sqlQuery . " INNER JOIN inventory ON inventory.id = purchase_item.inventory_id";
				$sqlQuery = $sqlQuery . " INNER JOIN inventory_brand ON inventory_brand.id = inventory.brand_id";
				$sqlQuery = $sqlQuery . " WHERE purchase_item.purchase_id = " . $this->id;
				
				
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
								<th id="item_price">Purchase Price:</th>
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
									<td><span class="table_row_counter"><?php echo $i ?>.</span></td>
									<td><?php echo capitalizeWords($item['brand_name']) ?></td>
									<td><?php echo capitalizeWords($item['model']) ?></td>
									<td class="number"><span><?php echo numberFormat($item['price'], "float") ?></span></td>
									<td class="quantity_link"><?php
										if ($purchase['status'] != "cleared") {
											echo '<span class="item_delivery_link">' .
												 '<a href="javascript:void(0)" onclick="showDialog(\'Mark Item as ' .
												 ($purchase['transaction_type'] == "delivery" ? "Received" : "Picked-up") .
												 '\',\'Getting data...\',\'prompt\'), ajax(null,\'dialog_message\',\'innerHTML\',\'Purchase::showItemDeliveryDialog\',\'class=purchase&transactionID=' .
												 $purchase['id'] . '&itemID=' . $item['id'] . '&index=' . $i . '\'), loadDatePicker()" >' .
												 '<span id="item_quantity_' . $i . '">' .
												 numberFormat($item['quantity'] - $item['undelivered_quantity'], "int") . '</span>' .
												 '</a></span>';
										} else {
											echo '<span id="item_quantity_' . $i . '">' .
												 numberFormat($item['quantity'] - $item['undelivered_quantity'], "int") . '</span>';
										}
										
										echo ' / <span id="item_max_quantity_' . $i . '">' . numberFormat($item['quantity'], "int") . '</span>';
										
										$totalDeliveredItems = $totalDeliveredItems + ($item['quantity'] - $item['undelivered_quantity']);
										$totalItems          = $totalItems + $item['quantity'];
										?></td>
									<td class="number"><span><?php echo numberFormat($item['sidr_price'], "float"); ?></span></td>
									<td class="number"><span><?php
											$subtotal = (double) $item['sidr_price'] * (int) $item['quantity'];
											echo numberFormat($subtotal, "float");
											$totalSIDRamount = $totalSIDRamount + $subtotal;
											?></span></td>
									<td class="number"><span><?php echo numberFormat($item['net_price'], "float"); ?></span></td>
									<td class="number"><span><?php
											$subtotal = (double) $item['net_price'] * (int) $item['quantity'];
											echo numberFormat($subtotal, "float");
											$totalNetAmount = $totalNetAmount + $subtotal;
											?></span></td>
								</tr>
								<?php                $i++;
							}
							
							?>
							<tr>
								<td colspan="9"><br /></td>
							</tr>
							
							<tr class="totals_top totals_bottom">
								<td colspan="4"><label for="total_amount"><span class="important_label">Totals:</span></label></td>
								<td class="quantity_link">
									<?php
									if ($purchase['status'] != "cleared") {
										?><span class="item_delivery_link"><a href="javascript:void(0)"
																			  onclick="showDialog('Mark All Items as <?php echo($purchase['transaction_type'] ==
																																"delivery" ? "Received" :
																				  "Picked-up") ?>','Getting data...','prompt'), ajax(null,'dialog_message','innerHTML','Purchase::showAllItemsDeliveryDialog','class=purchase&transactionID=<?php echo $purchase['id'] ?>&maxIndex=<?php echo($i -
																																																																							  1) ?>'), loadDatePicker()"><span
												id="item_quantity_total"><?php echo $totalDeliveredItems ?></span></a></span><?php
									} else {
										echo '<span id="item_quantity_total">' . $totalDeliveredItems . '</span>';
									}
									
									?> / <span id="item_max_quantity_total"><?php echo $totalItems ?></span>
								</td>
								<td></td>
								<td class="number"><span><?php echo numberFormat($totalSIDRamount, "float") ?></span></td>
								<td></td>
								<td class="number"><span><?php echo numberFormat($totalNetAmount, "float") ?></span></td>
							</tr>
							
							<tr>
								<td colspan="9"><br /></td>
							</tr>
							
							<?php
							if ($purchase['sales_invoice'] != null) {
								?>
								<tr>
									<td colspan="8" class="summary_label">Total Sales: <?php echo CURRENCY ?></td>
									<td class="number"><span><?php echo numberFormat($purchase['total_sales_amount'], "float") ?></span></td>
								</tr>
								<tr>
									<td colspan="8" class="summary_label">+ Value-Added Tax: <?php echo CURRENCY ?></td>
									<td class="number"><span><?php echo numberFormat($purchase['value_added_tax'], "float") ?></span></td>
								</tr>
								<tr>
									<td colspan="8" class="summary_label">- Withholding Tax: <?php echo CURRENCY ?></td>
									<td class="number"><span><?php echo numberFormat($purchase['withholding_tax'], "float") ?></span></td>
								</tr>
							
							<?php
							}
							
							if ($purchase['payment_term'] == "installment" && (double) $purchase['interest'] > 0) {
								?>
								<tr>
									<td colspan="8" class="summary_label">Interest: <?php echo CURRENCY ?></td>
									<td class="number"><span><?php echo numberFormat($purchase['interest'], "float") ?></span></td>
								</tr>
							<?php
							}
							?>
							<tr>
								<td colspan="9"><br /></td>
							</tr>
							
							<tr class="totals_top">
								<td colspan="8" class="summary_label"><span class="important_label">SI/DR Amount: <?php echo CURRENCY ?></span></td>
								<td class="number"><span class="important_label"><?php echo numberFormat($purchase['receipt_amount'], "float") ?></span></td>
							</tr>
							<tr class="totals_bottom">
								<td colspan="8" class="summary_label"><span class="important_label">OFC Net Amount: <?php echo CURRENCY ?></span></td>
								<td class="number"><span class="important_label"><?php echo numberFormat($purchase['amount_payable'], "float") ?></span></td>
							</tr>
							</tbody>
						</table>
					</section>
				<?php
				}
				?>
			</fieldset>
		<?php
		} else                // order number is not exsiting, redirect to home page
		{
			?>
			<script type="text/javascript">
				<!--
				document.location = "index.php";
				// -->
			</script>
		<?php
		}
	}
	
	
	// get purchase info, ajax function
	public static function getPurchaseInfo() {
		// check required parameters
		if (!isset($_POST['purchaseID'])) {
			return;
		}
		
		self::$database = new Database();
		$sqlQuery       = "SELECT * FROM purchase WHERE id = " . $_POST['purchaseID'];
		$resultSet      = self::$database->query($sqlQuery);
		echo json_encode(self::$database->getResultRow($resultSet));
	}
	
	
	// mark purchase as cleared, ajax function
	public static function markAsCleared() {
		// check required parameters
		if (!isset($_POST['purchaseID'])) {
			return;
		}
		
		
		self::$database = new Database;
		
		// update record
		$clearedDate = date('Y-m-d H:i:s');
		$sqlQuery    = "UPDATE purchase SET cleared_date = '" . $clearedDate . "' WHERE id = " . $_POST['purchaseID'];
		if (self::$database->query($sqlQuery)) {
			// log event
			$sqlQuery  = 'SELECT sales_invoice, delivery_receipt, supplier_id, name FROM purchase ' .
						 'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
						 'WHERE purchase.id = ' . $_POST['purchaseID'];
			$resultSet = self::$database->query($sqlQuery);
			$purchase  = self::$database->getResultRow($resultSet);
			
			if ($purchase['sales_invoice'] != null) {
				$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
			} elseif ($purchase['delivery_receipt'] != null) {
				$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
			} else {
				$invoiceNumber = '';
			}
			
			EventLog::addEntry(self::$database, 'info', 'purchase', 'update', 'cleared',
							   '<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['purchaseID'] . '">' .
							   $_POST['purchaseID'] . '</a>' . $invoiceNumber .
							   ': </span>Purchase Order for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
							   capitalizeWords(Filter::output($purchase['name'])) . '</a> was <span class="event_log_action good">cleared</span>');
			
			
			echo "Purchase No. " . $_POST['purchaseID'] . " is now <b>cleared</b>!<br /><br /><br />";
		} else {
			Diagnostics::error('dialog', ERROR, "Cannot update Purchase No. " . $_POST['purchaseID'], "Please try again.", SYSTEM_ERROR);
		}
	}
	
	
	// unclear order, ajax function
	public static function undoClear() {
		// check required parameters
		if (!isset($_POST['purchaseID'])) {
			return;
		}
		
		
		self::$database = new Database;
		
		// update record
		$sqlQuery = "UPDATE purchase SET cleared_date = NULL WHERE id = " . $_POST['purchaseID'];
		if (self::$database->query($sqlQuery)) {
			// log event
			$sqlQuery  = 'SELECT sales_invoice, delivery_receipt, supplier_id, name FROM purchase ' .
						 'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
						 'WHERE purchase.id = ' . $_POST['purchaseID'];
			$resultSet = self::$database->query($sqlQuery);
			$purchase  = self::$database->getResultRow($resultSet);
			
			if ($purchase['sales_invoice'] != null) {
				$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
			} elseif ($purchase['delivery_receipt'] != null) {
				$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
			} else {
				$invoiceNumber = '';
			}
			
			EventLog::addEntry(self::$database, 'info', 'purchase', 'update', 'uncleared',
							   '<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['purchaseID'] . '">' .
							   $_POST['purchaseID'] . '</a>' . $invoiceNumber .
							   ': </span>Purchase Order for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
							   capitalizeWords(Filter::output($purchase['name'])) . '</a> was <span class="event_log_action">uncleared</span>');
			
			
			echo "Purchase No. " . $_POST['purchaseID'] . " has been uncleared<br /><br />Page will now reload...<br />";
		} else {
			Diagnostics::error('dialog', ERROR, "Cannot update Purchase No. " . $_POST['purchaseID'], "Please try again.", SYSTEM_ERROR);
		}
	}
	
	
	// mark purchase as canceled, ajax function
	public static function cancel() {
		// check required parameters
		if (!isset($_POST['purchaseID'])) {
			return;
		}
		
		
		self::$database = new Database;
		
		// update record
		$canceledDate = date('Y-m-d H:i:s');
		$sqlQuery     = "UPDATE purchase SET canceled_date = '" . $canceledDate . "' WHERE id = " . $_POST['purchaseID'];
		if (self::$database->query($sqlQuery)) {
			// log event
			$sqlQuery  = 'SELECT sales_invoice, delivery_receipt, supplier_id, name FROM purchase ' .
						 'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
						 'WHERE purchase.id = ' . $_POST['purchaseID'];
			$resultSet = self::$database->query($sqlQuery);
			$purchase  = self::$database->getResultRow($resultSet);
			
			if ($purchase['sales_invoice'] != null) {
				$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
			} elseif ($purchase['delivery_receipt'] != null) {
				$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
			} else {
				$invoiceNumber = '';
			}
			
			EventLog::addEntry(self::$database, 'info', 'purchase', 'update', 'canceled',
							   '<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['purchaseID'] . '">' .
							   $_POST['purchaseID'] . '</a>' . $invoiceNumber .
							   ': </span>Purchase Order for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
							   capitalizeWords(Filter::output($purchase['name'])) . '</a> was <span class="event_log_action bad">canceled</span>');
			
			
			echo "Purchase No. " . $_POST['purchaseID'] . " has been canceled<br /><br /><br />";
		} else {
			Diagnostics::error('dialog', ERROR, "Cannot update Purchase No. " . $_POST['purchaseID'], "Please try again.", SYSTEM_ERROR);
		}
	}
	
	
	// undo cancel purchase, ajax function
	public static function undoCancel() {
		// check required parameters
		if (!isset($_POST['purchaseID'])) {
			return;
		}
		
		
		self::$database = new Database;
		
		// update purchase status
		$sqlQuery = "UPDATE purchase SET canceled_date = NULL WHERE id = " . $_POST['purchaseID'];
		self::$database->query($sqlQuery);
		
		
		// log event
		$sqlQuery  = 'SELECT sales_invoice, delivery_receipt, supplier_id, name FROM purchase ' .
					 'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
					 'WHERE purchase.id = ' . $_POST['purchaseID'];
		$resultSet = self::$database->query($sqlQuery);
		$purchase  = self::$database->getResultRow($resultSet);
		
		if ($purchase['sales_invoice'] != null) {
			$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
		} elseif ($purchase['delivery_receipt'] != null) {
			$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
		} else {
			$invoiceNumber = '';
		}
		
		EventLog::addEntry(self::$database, 'info', 'purchase', 'update', 'uncanceled',
						   '<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['purchaseID'] . '">' .
						   $_POST['purchaseID'] . '</a>' . $invoiceNumber .
						   ': </span>Purchase Order for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
						   capitalizeWords(Filter::output($purchase['name'])) . '</a> was <span class="event_log_action">uncanceled</span>');
		
		
		echo "Purchase No. " . $_POST['purchaseID'] . " has been uncanceled<br /><br />Page will now reload...<br />";
	}
	
	
	// export order details to Excel file, ajax function
	public static function exportDetailsToExcel( $username, $purchaseID ) {
		$fileTimeStampExtension = date(EXCEL_FILE_TIMESTAMP_FORMAT);
		$headingTimeStamp       = dateFormatOutput($fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT);
		
		require_once("classes/Filter.php");
		
		self::$database = new Database();
		
		// get main purchase info
		$sqlQuery  = "SELECT purchase.*, " .
					 "IF(canceled_date IS NOT NULL,'canceled',IF(cleared_date IS NOT NULL,'cleared',IF(delivery_pickup_actual_date IS NULL,'pending',IF(balance = 0,'fully-paid',IF(balance = amount_payable,'delivered','partially-paid'))))) AS status, " .
					 "supplier.name AS supplier_name, agent.name AS agent_name, " .
					 "IF(cleared_date IS NULL AND canceled_date IS NULL,DATEDIFF(NOW(),purchase.purchase_date),-1) AS order_duration " .
					 "FROM purchase " .
					 "INNER JOIN supplier ON supplier.id = purchase.supplier_id " .
					 "LEFT JOIN agent ON agent.id = purchase.agent_id " .
					 "WHERE purchase.id = " . $purchaseID;
		$resultSet = self::$database->query($sqlQuery);
		
		if (self::$database->getResultCount($resultSet) == 0) {
			redirectToHomePage();
		} else {
			$purchase   = self::$database->getResultRow($resultSet);
			$sheetTitle = "Purchase No. $purchaseID";
			
			if ($purchase['sales_invoice'] != null) {
				$trackingNumber = $purchase['sales_invoice'];
				$fileName       = "Purchase No. $purchaseID (SI $trackingNumber)";
			} elseif ($purchase['delivery_receipt'] != null) {
				$trackingNumber = $purchase['delivery_receipt'];
				$fileName       = "Purchase No. $purchaseID (DR $trackingNumber)";
			} else {
				$trackingNumber = null;
				$fileName       = "Purchase No. $purchaseID";
			}
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
					->setTitle($sheetTitle . ' as of ' . $headingTimeStamp)
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
		
		$activeSheet->setCellValue('A' . $FIELD_HEADER_ROW, 'Purchase Info');
		
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
		
		// purchase info
		if ($purchase['sales_invoice'] != null) {
			$activeSheet->setCellValue('B6', 'Sales Invoice No:');
		} elseif ($purchase['delivery_receipt'] != null) {
			$activeSheet->setCellValue('B6', 'Delivery Receipt No:');
		} else {
			$activeSheet->setCellValue('B6', 'SI/DR:');
		}
		$activeSheet->setCellValue('C6', $trackingNumber);
		
		$activeSheet->setCellValue('B7', 'Supplier:');
		$activeSheet->setCellValue('C7', html_entity_decode(capitalizeWords(Filter::reinput($purchase['supplier_name']))));
		
		$activeSheet->setCellValue('B8', 'Purchase Date:');
		$activeSheet->setCellValue('C8', dateFormatOutput($purchase['purchase_date'], EXCEL_DATETIME_FORMAT_INPUT));
		
		$activeSheet->setCellValue('B9', 'Duration:');
		if ($purchase['order_duration'] > -1) {
			if ($purchase['order_duration'] > 1) {
				$activeSheet->setCellValue('C9', $purchase['order_duration'] . ' days old');
			} else {
				$activeSheet->setCellValue('C9', $purchase['order_duration'] . ' day old');
			}
		}
		
		$activeSheet->setCellValue('B11', "Supplier's P.O. No:");
		$activeSheet->getCell('C11')->setValueExplicit(stripslashes($purchase['purchase_number']), PHPExcel_Cell_DataType::TYPE_STRING);
		
		$activeSheet->setCellValue('B13', 'Transaction Type:');
		$activeSheet->setCellValue('C13', ucfirst($purchase['transaction_type']));
		
		$activeSheet->setCellValue('B14', 'Target ' . ucfirst($purchase['transaction_type']) . ' Date:');
		$activeSheet->setCellValue('C14', dateFormatOutput($purchase['delivery_pickup_target_date'], EXCEL_DATE_FORMAT_INPUT));
		
		$activeSheet->setCellValue('B15', 'Actual ' . ucfirst($purchase['transaction_type']) . ' Date:');
		if ($purchase['delivery_pickup_actual_date'] != null) {
			$activeSheet->setCellValue('C15', dateFormatOutput($purchase['delivery_pickup_actual_date'], EXCEL_DATE_FORMAT_INPUT));
		} else {
			if ($purchase['transaction_type'] == 'delivery') {
				$activeSheet->setCellValue('C15', 'Not yet delivered');
			} else {
				$activeSheet->setCellValue('C15', 'Not yet picked-up');
			}
			$activeSheet->getStyle('C15')->getFont()->setColor($fontColorDarkRed);
		}
		
		$activeSheet->setCellValue('B17', 'Business Unit:');
		$activeSheet->setCellValue('C17', $purchase['business_unit']);
		
		$activeSheet->setCellValue('B18', 'Agent:');
		$activeSheet->setCellValue('C18', html_entity_decode(capitalizeWords(Filter::reinput($purchase['agent_name']))));
		
		$activeSheet->setCellValue('B20', 'Notes/Comments:');
		$activeSheet->getCell('C20')->setValueExplicit(stripslashes($purchase['remarks']), PHPExcel_Cell_DataType::TYPE_STRING);
		
		$FIELD_HEADER_ROW = 22;
		
		// post formatting
		$activeSheet->getStyle('B6:B21')->getFont()->setColor($fontColorGray);      // format labels
		$activeSheet->getStyle('C6:C21')->getAlignment()->setWrapText(true);                        // wrap info
		$activeSheet->getStyle('C6:C21')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);         // left aligned
		$activeSheet->getStyle('C6')->getFont()->setBold(true);                                    // set invoice number to bold
		$activeSheet->getStyle('C8')->getNumberFormat()->setFormatCode(EXCEL_DATETIME_FORMAT);        // format Order Date
		$activeSheet->getStyle('C14:C15')->getNumberFormat()->setFormatCode(EXCEL_DATE_FORMAT);        // format Delivery Dates
		
		
		// purchase items
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
		
		// get purchase items
		$sqlQuery  = "SELECT purchase_item.*, inventory.model, inventory_brand.name AS brand_name FROM purchase_item"
					 . " INNER JOIN inventory ON inventory.id = purchase_item.inventory_id"
					 . " INNER JOIN inventory_brand ON inventory_brand.id = inventory.brand_id"
					 . " WHERE purchase_item.purchase_id = " . $purchaseID;
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
		$activeSheet->getStyle('A' . $itemRow . ':A' . $rowPtr)->getFont()->setColor($fontColorGray);      // format numbering
		$activeSheet->getStyle('B' . $itemRow . ':C' . $rowPtr)->getAlignment()->setWrapText(true);                        // wrap Brand and Model
		$activeSheet->getStyle('D' . $itemRow . ':D' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);    // format Selling Price
		$activeSheet->getStyle('E' . $itemRow . ':E' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);        // format Quantity
		$activeSheet->getStyle('F' . $itemRow . ':I' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);    // format amounts
		$activeSheet->getStyle('G' . $itemRow . ':G' . $rowPtr)->getFont()->setBold(true);                                    // set SI/DR Subtotal to bold
		$activeSheet->getStyle('I' . $itemRow . ':I' . $rowPtr)->getFont()->setBold(true);                                    // set Net Subtotal to bold
		
		// set columns to left aligned
		$activeSheet->getStyle('B' . $itemRow . ':C' . $rowPtr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		// conditional formatting
		// * set zero amounts to red
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
		
		if ($purchase['sales_invoice'] != null) {
			$totalSalesRow = $totalsRow;
			$activeSheet->setCellValue('G' . $totalSalesRow, 'Total Sales:');
			$activeSheet->setCellValue('I' . $totalSalesRow, $purchase['total_sales_amount']);
			
			$valueAddedTaxRow = $totalSalesRow + 1;
			$activeSheet->setCellValue('G' . $valueAddedTaxRow, '(add) Value-Added Tax:');
			$activeSheet->setCellValue('I' . $valueAddedTaxRow, $purchase['value_added_tax']);
			
			$withholdingTaxRow = $valueAddedTaxRow + 1;
			$activeSheet->setCellValue('G' . $withholdingTaxRow, '(less) Withholding Tax:');
			$activeSheet->setCellValue('I' . $withholdingTaxRow, $purchase['withholding_tax']);
			
			$totalsRow = $withholdingTaxRow + 2;
		}
		
		// interest
		if ($purchase['payment_term'] == "installment" && (double) $purchase['interest'] > 0) {
			$activeSheet->setCellValue('G' . $totalsRow, '(add) Interest:');
			$activeSheet->setCellValue('I' . $totalsRow, $purchase['interest']);
			$totalsRow = $totalsRow + 2;
		}
		
		// grand totals
		if ($purchase['sales_invoice'] != null) {
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
		$activeSheet->getStyle('G' . ($netAmountRow + 1) . ':G' . ($totalsRow - 2))->getFont()->setColor($fontColorGray);      // format numbering
		$activeSheet->getStyle('I' . ($netAmountRow + 1) . ':I' . $totalsRow)->getNumberFormat()
					->setFormatCode(EXCEL_CURRENCY_FORMAT);    // format Selling Price
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
	
	
	// view orders with reserved stock, ajax function
	public static function showInventory() {
		// get parameters	
		if (!isset($_POST['sortColumn'])) {
			$sortColumn = "id";
		} else {
			$sortColumn = $_POST['sortColumn'];
		}
		
		if (!isset($_POST['sortMethod'])) {
			$sortMethod = "DESC";
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
			$condition = 'WHERE purchase_item.inventory_id = ' . $filterValue . ' AND ' .
						 'purchase_item.undelivered_quantity > 0 AND ' .
						 'canceled_date IS NULL';
		} else {
			$condition = 'WHERE purchase_item.inventory_id = ' . $filterValue . ' AND ' .
						 'purchase_item.undelivered_quantity = 0 AND ' .
						 'canceled_date IS NULL';
		}
		
		
		// count results prior to main query
		self::$database = new Database;
		$sqlQuery       = "SELECT COUNT(*) AS count FROM `purchase` LEFT JOIN purchase_item ON `purchase`.id = purchase_item.purchase_id " . $condition;
		$resultSet      = self::$database->query($sqlQuery);
		$resultCount    = self::$database->getResultRow($resultSet);
		$resultCount    = $resultCount['count'];
		
		
		// construct query
		$sqlQuery = "SELECT `purchase`.*, " .
					"supplier.id AS supplier_id, " .
					"supplier.name AS supplier, " .
					"purchase_item.quantity, " .
					"purchase_item.undelivered_quantity, " .
					"purchase_item.quantity-purchase_item.undelivered_quantity AS delivered_quantity, " .
					"IF(sales_invoice IS NOT NULL," .
					"CONCAT('SI ',sales_invoice)," .
					"CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
					"IF(cleared_date IS NULL AND canceled_date IS NULL," .
					"DATEDIFF(NOW(),purchase.purchase_date)," .
					"NULL) AS purchase_duration " .
					"FROM `purchase` " .
					"INNER JOIN supplier ON `purchase`.supplier_id = supplier.id " .
					"LEFT JOIN purchase_item ON `purchase`.id = purchase_item.purchase_id " .
					$condition .
					" ORDER BY " . $sortColumn . " " . $sortMethod;
		if ($sortColumn == "balance") {
			$sqlQuery = $sqlQuery . ", status " . $sortMethod;
		}
		$sqlQuery  = $sqlQuery . " LIMIT " . $offset . "," . $itemsPerPage;
		$resultSet = self::$database->query($sqlQuery);
		
		
		// display result
		if (self::$database->getResultCount($resultSet) > 0) {
			// set columns to display
			if ($filterName == 'pending') {
				$columns = array(
					'id'                          => 'Purchase No.',
					'tracking_number'             => 'Tracking No.',
					'supplier'                    => 'Supplier',
					'purchase_duration'           => 'Duration',
					'delivery_pickup_target_date' => 'Delivery/Pick-up Date',
					'quantity'                    => 'Quantity',
					'undelivered_quantity'        => 'Pending for Delivery'
				);
				
				$sectionName = 'pending_purchase_list_section';
			} else {
				$columns = array(
					'id'                          => 'Purchase No.',
					'tracking_number'             => 'Tracking No.',
					'supplier'                    => 'Supplier',
					'purchase_duration'           => 'Duration',
					'delivery_pickup_target_date' => 'Delivery/Pick-up Date',
					'quantity'                    => 'Quantity',
					'delivered_quantity'          => 'Items Delivered'
				);
				
				$sectionName = 'delivered_purchase_list_section';
			}
			
			
			// display sortable columns
			self::showListHeader($columns, $sectionName, 'Purchase::showInventory', null, $sortColumn, $sortMethod, $filterName, $filterValue);
			
			
			// display content
			while ($purchase = self::$database->getResultRow($resultSet)) {
				echo '<tr>';
				
				// order ID
				echo '<td><a href="view_purchase_details.php?id=' . $purchase['id'] . '">' . $purchase['id'] . '</a></td>';
				
				// invoice number
				echo '<td>' . $purchase['tracking_number'] . '</td>';
				
				// customer
				echo '<td>' .
					 '<span class="long_text_clip">' .
					 '<a href="view_supplier_details.php?id=' . $purchase['supplier_id'] .
					 '" title="' . capitalizeWords(Filter::output($purchase['supplier'])) . '">' .
					 capitalizeWords(Filter::output($purchase['supplier'])) .
					 '</a>' .
					 '</span>' .
					 '</td>';
				
				// duration
				echo '<td>';
				if ($purchase['purchase_duration'] != null) {
					echo '<span>' . numberFormat($purchase['purchase_duration'], "int");
					
					if ($purchase['purchase_duration'] > 1) {
						echo " days old</span>";
					} else {
						echo " day old</span>";
					}
				}
				echo '</td>';
				
				// delivery/pick-up date
				echo '<td>';
				$deliveryPickupTargetDate = dateFormatOutput($purchase['delivery_pickup_target_date'], "Y-m-d");
				$currentDate              = date("Y-m-d");
				if ($purchase['canceled_date'] != null) {
					echo '<span class="canceled">' . dateFormatOutput($purchase['delivery_pickup_target_date'], "M j, Y") . '</span>';
				} elseif ($purchase['delivery_pickup_actual_date'] != null) {        // order is already delivered
					echo '<span class="good">' . dateFormatOutput($purchase['delivery_pickup_target_date'], "M j, Y") . '</span>';
					$deliveryPickupActualDate = dateFormatOutput($purchase['delivery_pickup_actual_date'], "Y-m-d");
					if ($deliveryPickupTargetDate < $deliveryPickupActualDate) {
						echo '<img src="images/warning.png" class="status_icon" title="Delayed. Actual Delivery/Pickup Date: ' .
							 dateFormatOutput($purchase['delivery_pickup_actual_date'], "M j, Y") . '" />';
					} else {
						echo '<img src="images/success.png" class="status_icon" title="On-time. Actual Delivery/Pickup Date: ' .
							 dateFormatOutput($purchase['delivery_pickup_actual_date'], "M j, Y") . '" />';
					}
				} elseif ($deliveryPickupTargetDate < $currentDate) {            // delivery/pick-up date had passed
					echo '<span class="bad">' . dateFormatOutput($purchase['delivery_pickup_target_date'], "M j, Y") . '</span>';
				} else {
					echo dateFormatOutput($purchase['delivery_pickup_target_date'], "M j, Y");
				}
				echo '</td>';
				
				// no. of items
				echo '<td class="number">';
				if ($purchase['quantity'] <= 0) {
					echo '<span class="bad">' . numberFormat($purchase['quantity'], "int") . '</span>';
				} else {
					echo '<span>' . numberFormat($purchase['quantity'], "int") . '</span>';
				}
				echo '</td>';
				
				// pending for delivery / delivered items
				echo '<td class="number">';
				if ($filterName == 'pending') {
					if ($purchase['undelivered_quantity'] <= 0) {
						echo '<span class="bad">' . numberFormat($purchase['undelivered_quantity'], "int") . '</span>';
					} else {
						echo '<span>' . numberFormat($purchase['undelivered_quantity'], "int") . '</span>';
					}
				} else {
					if ($order['delivered_quantity'] <= 0) {
						echo '<span class="bad">' . numberFormat($purchase['delivered_quantity'], "int") . '</span>';
					} else {
						echo '<span>' . numberFormat($purchase['delivered_quantity'], "int") . '</span>';
					}
				}
				echo '</td>';
				
				echo '</tr>';
			}
			
			echo "	</tbody>\n";
			echo "</table>\n";
			
			
			echo '<div class="pagination_class">';
			self::showPagination($page, $itemsPerPage, $resultCount, $sectionName, 'Purchase::showInventory',
								 null, $sortColumn, $sortMethod, $filterName, $filterValue);
			echo '</div>';
		} else {
			echo "<div>No purchases found for this criteria.</div>";
		}
	}
}

?>

<?php
	require_once( "controls/autoload.php" );

	$PAGE_NAME = "Consistency Check Tool";

	function inlineStyle()
	{
		?>	<style type="text/css">
		section {
			width: 1000px;
		}
	</style>
	<?php
	}
	
	
	function showCheckItems() {
		?><fieldset>
            <section><div id="about_content">
                <div>
                    <p>This tool helps you check any inconsistency in the database records. Please select any of the action items below to analyze.<br />In case you found any inconsistency, please report to the programmers immediately.<br /></p>
                </div>
                
                <div id="consistency_check_items">
                    <ul>
                        <li>Orders/Purchases
                            <ul>
                                <li><a href="<?php echo $_SERVER['PHP_SELF'] . "?check=op-2" ?>">Amount paid/received is inconsistent with remaining balance »</a></li>
								<li><a href="<?php echo $_SERVER['PHP_SELF'] . "?check=op-3" ?>">Cleared orders/purchases with remaining balance »</a></li>
                                <li><a href="<?php echo $_SERVER['PHP_SELF'] . "?check=op-4" ?>">Cleared orders/purchases with undelivered items »</a></li>
                            </ul>
                            <br />
                        </li>
                        <li>Inventory
                            <ul>
                                <li><a href="<?php echo $_SERVER['PHP_SELF'] . "?check=inv-1" ?>">Incorrect reserved stocks in inventory »</a></li>
                                <li><a href="<?php echo $_SERVER['PHP_SELF'] . "?check=inv-2" ?>">Old inventories that can be deleted »</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
	        </div></section>
        </fieldset><?php
	}
	
	
	function showCorrectiveCode( &$correctiveAction ) {
		if ( Registry::get( 'tool.consistency_check.show_corrective_code' ) == true ) {
			echo '<section><div>' .
				 '<p><span class="now">Correction Code:</span></p>' .
				 '<p>' . $correctiveAction . '</p>' .
				 '</div></section>';
		}
	}
	

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->paint( "inlineStyle" );
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'checklist.png', true );
	
	
	if ( !isset( $_GET['check'] ) ) {
		showCheckItems();
		return;
	}
	
	
	$database = new Database();
	$correctiveAction = '';
	
	
	switch ( $_GET['check'] ) {
		case 'op-2' : {
			echo '<fieldset><section>' .
		         '<div><h3>Amount paid/received is inconsistent with remaining balance</h3></div>' .
				 '<div>';
			
			$sqlQuery = "SELECT id, balance, balance-SUM(order_payment.amount) AS amount_received " .
						"FROM `order` " .
						"LEFT JOIN order_payment ON order.id = order_payment.order_id " .
						"GROUP BY order.id " .
						"HAVING balance != balance";
			$resultSet = $database->query( $sqlQuery );
			if ( $database->getResultCount( $resultSet ) == 0 ) {
				echo '<p><span class="good">Nothing found for Orders.</span></p>' .
					 '</div>';
			} else {
				echo '<table class="item_input_table consistency_check_result">' .
					 '<thead><tr>' .
					 '<th class="narrow_cell">Order ID</th>' .
					 '<th>Recorded Balance</th>' .
					 '<th>Computed Balance</th>' .
					 '<th class="recommended_action">Recommended Action</th>' .
					 '</tr></thead>' .
					 '<tbody>';
				
				while ( $transaction = $database->getResultRow( $resultSet ) ) {
					$correctionSQL = 'UPDATE `order` SET balance=' . $transaction['amount_received'] . ' WHERE id=' . $transaction['id'];
					
					echo '<tr class="item_row">' .
						 '<td>' . $transaction['id'] . '</td>' .
						 '<td class="number"><span>' . $transaction['balance'] . '</span></td>' .
						 '<td class="number"><span>' . $transaction['amount_received'] . '</span></td>' .
						 '<td>Set balance to ' . $transaction['amount_received'] . '</td>' .
						 '</tr>' ;
					
					$correctiveAction = $correctiveAction . $correctionSQL . ';<br />';
				}
				
				echo '</tbody></table>';
				
				echo '</div>';
				showCorrectiveCode( $correctiveAction );
			}
			
			$correctiveAction = '';
			echo '<div>';
			$sqlQuery = "SELECT id, balance, amount_payable-SUM(purchase_payment.amount) AS amount_paid " .
						"FROM purchase " .
						"LEFT JOIN purchase_payment ON purchase.id = purchase_payment.purchase_id " .
						"GROUP BY purchase.id " .
						"HAVING balance != amount_paid";
			$resultSet = $database->query( $sqlQuery );
			if ( $database->getResultCount( $resultSet ) == 0 ) {
				echo '<p><span class="good">Nothing found for Purchases.</span></p>' .
					 '</div>';
			} else {
				echo '<table class="item_input_table consistency_check_result">' .
					 '<thead><tr>' .
					 '<th class="narrow_cell">Purchase ID</th>' .
					 '<th>Recorded Balance</th>' .
					 '<th>Computed Balance</th>' .
					 '<th class="recommended_action">Recommended Action</th>' .
					 '</tr></thead>' .
					 '<tbody>';
				
				while ( $transaction = $database->getResultRow( $resultSet ) ) {
					$correctionSQL = 'UPDATE purchase SET balance=' . $transaction['amount_paid'] . ' WHERE id=' . $transaction['id'];
					
					echo '<tr class="item_row">' .
						 '<td>' . $transaction['id'] . '</td>' .
						 '<td class="number"><span>' . $transaction['balance'] . '</span></td>' .
						 '<td class="number"><span>' . $transaction['amount_paid'] . '</span></td>' .
						 '<td>Set balance to ' . $transaction['amount_paid'] . '</td>' .
						 '</tr>' ;
					
					$correctiveAction = $correctiveAction . $correctionSQL . ';<br />';
				}
				
				echo '</tbody></table>';
				
				echo '</div>';
				showCorrectiveCode( $correctiveAction );
			}
			
			echo '</section></fieldset>';
			
			break;
		}
		
		case 'op-3' : {
			echo '<fieldset><section>' .
		         '<div><h3>Cleared orders/purchases with remaining balance</h3></div>' .
				 '<div>';
			
			$sqlQuery = "SELECT id, balance, cleared_date " .
						"FROM `order` " .
						"WHERE balance > 0 AND cleared_date IS NOT NULL";
			$resultSet = $database->query( $sqlQuery );
			if ( $database->getResultCount( $resultSet ) == 0 ) {
				echo '<p><span class="good">Nothing found for Orders.</span></p>' .
					 '</div>';
			} else {
				echo '<table class="item_input_table consistency_check_result">' .
					 '<thead><tr>' .
					 '<th class="narrow_cell">Order ID</th>' .
					 '<th>Balance</th>' .
					 '<th>Clearing Date</th>' .
					 '<th class="recommended_action">Recommended Action</th>' .
					 '</tr></thead>' .
					 '<tbody>';
				
				while ( $transaction = $database->getResultRow( $resultSet ) ) {
					$correctionSQL = 'UPDATE `order` SET waived_balance=balance, balance=0.000 WHERE id=' . $transaction['id'];
					
					echo '<tr class="item_row">' .
						 '<td>' . $transaction['id'] . '</td>' .
						 '<td class="number"><span>' . $transaction['balance'] . '</span></td>' .
						 '<td>' . $transaction['cleared_date'] . '</td>' .
						 '<td>Set waived balance to ' . $transaction['balance'] . 
						 ', balance to 0.000</td>' .
						 '</tr>' ;
					
					$correctiveAction = $correctiveAction . $correctionSQL . ';<br />';
				}
				
				echo '</tbody></table>';
				
				echo '</div>';
				showCorrectiveCode( $correctiveAction );
			}
			
			$correctiveAction = '';
			echo '<div>';
			$sqlQuery = "SELECT id, balance, cleared_date " .
						"FROM purchase " .
						"WHERE balance > 0 AND cleared_date IS NOT NULL";
			$resultSet = $database->query( $sqlQuery );
			if ( $database->getResultCount( $resultSet ) == 0 ) {
				echo '<p><span class="good">Nothing found for Purchases.</span></p>' .
					 '</div>';
			} else {
				echo '<table class="item_input_table consistency_check_result">' .
					 '<thead><tr>' .
					 '<th class="narrow_cell">Purchase ID</th>' .
					 '<th>Balance</th>' .
					 '<th>Clearing Date</th>' .
					 '<th class="recommended_action">Recommended Action</th>' .
					 '</tr></thead>' .
					 '<tbody>';
				
				while ( $transaction = $database->getResultRow( $resultSet ) ) {
					$correctionSQL = 'UPDATE purchase SET waived_balance=balance, balance=0.000 WHERE id=' . $transaction['id'];
					
					echo '<tr class="item_row">' .
						 '<td>' . $transaction['id'] . '</td>' .
						 '<td class="number"><span>' . $transaction['balance'] . '</span></td>' .
						 '<td>' . $transaction['cleared_date'] . '</td>' .
						 '<td>Set waived balance to ' . $transaction['balance'] . 
						 ', balance to 0.000</td>' .
						 '</tr>' ;
					
					$correctiveAction = $correctiveAction . $correctionSQL . ';<br />';
				}
				
				echo '</tbody></table>';
				
				echo '</div>';
				showCorrectiveCode( $correctiveAction );
			}
			
			echo '</section></fieldset>';
			
			break;
		}
		
		case 'op-4' : {
			echo '<fieldset><section>' .
		         '<div><h3>Cleared orders/purchases with undelivered items</h3></div>' .
				 '<div>';
			
			$sqlQuery = "SELECT `order`.id, cleared_date, inventory_id, model, undelivered_quantity " .
						"FROM `order` " .
						"LEFT JOIN order_item ON `order`.id = order_item.order_id " .
						"INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
						"WHERE undelivered_quantity > 0 AND " .
						"cleared_date IS NOT NULL";
			$resultSet = $database->query( $sqlQuery );
			if ( $database->getResultCount( $resultSet ) == 0 ) {
				echo '<p><span class="good">Nothing found for Orders.</span></p>' .
					 '</div>';
			} else {
				echo '<table class="item_input_table consistency_check_result">' .
					 '<thead><tr>' .
					 '<th class="narrow_cell">Order ID</th>' .
					 '<th>Clearing Date</th>' .
					 '<th>Inventory ID</th>' .
					 '<th>Undelivered Items</th>' .
					 '<th class="recommended_action">Recommended Action</th>' .
					 '</tr></thead>' .
					 '<tbody>';
				
				while ( $transaction = $database->getResultRow( $resultSet ) ) {
					$correctionSQL = 'UPDATE order_item SET undelivered_quantity=0 WHERE order_id=' . $transaction['id'] . ' AND inventory_id=' . $transaction['inventory_id'];
					
					echo '<tr class="item_row">' .
						 '<td>' . $transaction['id'] . '</td>' .
						 '<td>' . $transaction['cleared_date'] . '</td>' .
						 '<td>' . $transaction['model'] . '</td>' .
						 '<td class="number"><span>' . $transaction['undelivered_quantity'] . '</span></td>' .
						 '<td>Set undelivered quantity to 0</td>' .
						 '</tr>' ;
					
					$correctiveAction = $correctiveAction . $correctionSQL . ';<br />';
				}
				
				echo '</tbody></table>';
				
				echo '</div>';
				showCorrectiveCode( $correctiveAction );
			}
			
			$correctiveAction = '';
			echo '<div>';
			$sqlQuery = "SELECT purchase.id, cleared_date, inventory_id, model, undelivered_quantity " .
						"FROM purchase " .
						"LEFT JOIN purchase_item ON purchase.id = purchase_item.purchase_id " .
						"INNER JOIN inventory ON purchase_item.inventory_id = inventory.id " .
						"WHERE undelivered_quantity > 0 AND " .
						"cleared_date IS NOT NULL";
			$resultSet = $database->query( $sqlQuery );
			if ( $database->getResultCount( $resultSet ) == 0 ) {
				echo '<p><span class="good">Nothing found for Purchases.</span></p>' .
					 '</div>';
			} else {
				echo '<table class="item_input_table consistency_check_result">' .
					 '<thead><tr>' .
					 '<th class="narrow_cell">Purchase ID</th>' .
					 '<th>Clearing Date</th>' .
					 '<th>Inventory ID</th>' .
					 '<th>Undelivered Items</th>' .
					 '<th class="recommended_action">Recommended Action</th>' .
					 '</tr></thead>' .
					 '<tbody>';
				
				while ( $transaction = $database->getResultRow( $resultSet ) ) {
					$correctionSQL = 'UPDATE purchase_item SET undelivered_quantity=0 WHERE order_id=' . $transaction['id'] . ' AND inventory_id=' . $transaction['inventory_id'];
					
					echo '<tr class="item_row">' .
						 '<td>' . $transaction['id'] . '</td>' .
						 '<td>' . $transaction['cleared_date'] . '</td>' .
						 '<td>' . $transaction['model'] . '</td>' .
						 '<td class="number"><span>' . $transaction['undelivered_quantity'] . '</span></td>' .
						 '<td>Set undelivered quantity to 0</td>' .
						 '</tr>' ;
					
					$correctiveAction = $correctiveAction . $correctionSQL . ';<br />';
				}
				
				echo '</tbody></table>';
				
				echo '</div>';
				showCorrectiveCode( $correctiveAction );
			}
			
			echo '</section></fieldset>';
			
			break;
		}
		
		
		case 'inv-1' : {
			echo '<fieldset><section>' .
		         '<div><h3>Incorrect reserved stocks in inventory</h3></div>' .
				 '<div>';
			
			$sqlQuery = "SELECT inventory_brand.name, inventory.*, " .
						"IF (SUM(undelivered_quantity) IS NOT NULL,SUM(undelivered_quantity),0) AS correct_reserved " .
						"FROM inventory " .
						"INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
						"LEFT JOIN v_active_order_items ON inventory.id = v_active_order_items.inventory_id " .
						"GROUP BY inventory.id " .
						"HAVING correct_reserved != reserved_stock";
			$resultSet = $database->query( $sqlQuery );
			if ( $database->getResultCount( $resultSet ) == 0 ) {
				echo '<p><span class="good">Nothing found.</span></p>' .
					 '</div></section></fieldset>';
				return;
			}
			
			echo '<table class="item_input_table consistency_check_result">' .
				 '<thead><tr>' .
				 '<th class="narrow_cell">Inventory ID</th>' .
				 '<th>Brand</th>' .
				 '<th>Model</th>' .
				 '<th class="narrow_cell">Stock Count</th>' .
				 '<th class="narrow_cell">Reserved Stock</th>' .
				 '<th class="narrow_cell">Pending for Delivery</th>' .
				 '<th class="recommended_action">Recommended Action</th>' .
				 '</tr></thead>' .
				 '<tbody>';
			
			while ( $inventory = $database->getResultRow( $resultSet ) ) {
				$correctionSQL = 'UPDATE inventory SET reserved_stock=' . $inventory['correct_reserved'] . ' WHERE id=' . $inventory['id'];
				
				echo '<tr class="item_row">' .
					 '<td>' . $inventory['id'] . '</td>' .
					 '<td>' . Filter::output( $inventory['name'] ) . '</td>' .
					 '<td>' . Filter::output( $inventory['model'] ) . '</td>' .
					 '<td class="number"><span>' . $inventory['stock_count'] . '</span></td>' .
					 '<td class="number"><span>' . $inventory['reserved_stock'] . '</span></td>' .
					 '<td class="number"><span>' . $inventory['correct_reserved'] . '</span></td>' .
					 '<td>Set reserved stock to ' . $inventory['correct_reserved'] . '</td>' .
					 '</tr>' ;
				
				$correctiveAction = $correctiveAction . $correctionSQL . ';<br />';
			}
			
			echo '</tbody></table>';
			
			echo '</div></section>';
			showCorrectiveCode( $correctiveAction );
			echo '</fieldset>';
			
			break;
		}
		
		case 'inv-2' : {
			echo '<fieldset><section>' .
		         '<div><h3>Old inventories that can be deleted</h3></div>' .
				 '<div>';
			
			$sqlQuery = "SELECT inventory_brand.name, inventory.*, SUM(order_item.quantity) AS order_quantity, SUM(purchase_item.quantity) AS purchase_quantity " .
						"FROM inventory " .
						"INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
						"LEFT JOIN order_item ON inventory.id = order_item.inventory_id " .
						"LEFT JOIN purchase_item ON inventory.id = purchase_item.inventory_id " .
						"WHERE parent_id IS NOT NULL AND stock_count <= 0 AND reserved_stock <= 0 " .
						"GROUP BY inventory.id ";
			$resultSet = $database->query( $sqlQuery );
			if ( $database->getResultCount( $resultSet ) == 0 ) {
				echo '<p><span class="good">Nothing found.</span></p>' .
					 '</div></section></fieldset>';
				return;
			}
			
			echo '<table class="item_input_table consistency_check_result">' .
				 '<thead><tr>' .
				 '<th class="narrow_cell">Inventory ID</th>' .
				 '<th>Brand</th>' .
				 '<th>Model</th>' .
				 '<th class="narrow_cell">Stock Count</th>' .
				 '<th class="narrow_cell">Reserved Stock</th>' .
				 '<th class="narrow_cell">Order Quantity</th>' .
				 '<th class="narrow_cell">Purchase Quantity</th>' .
				 '<th class="recommended_action">Recommended Action</th>' .
				 '</tr></thead>' .
				 '<tbody>';
			
			while ( $inventory = $database->getResultRow( $resultSet ) ) {
				if ( $inventory['order_quantity'] == null && $inventory['purchase_quantity'] == null ) {
					$correctionSQL = 'DELETE FROM inventory WHERE id=' . $inventory['id'];
					$correctionStr = 'Delete inventory';
				} elseif ( $inventory['order_quantity'] != null && $inventory['purchase_quantity'] == null ) {
					$correctionSQL = 'UPDATE order_item SET inventory_id=' . $inventory['parent_id'] . ' WHERE inventory_id=' . $inventory['id'] . '; ' .
									 'DELETE FROM inventory WHERE id=' . $inventory['id'];
					$correctionStr = 'Update order items and delete inventory';
				} elseif ( $inventory['order_quantity'] == null && $inventory['purchase_quantity'] != null ) {
					$correctionSQL = 'UPDATE purchase_item SET inventory_id=' . $inventory['parent_id'] . ' WHERE inventory_id=' . $inventory['id'] . '; ' .
									 'DELETE FROM inventory WHERE id=' . $inventory['id'];
					$correctionStr = 'Update purchase items and delete inventory';
				} else {
					$correctionSQL = 'UPDATE order_item SET inventory_id=' . $inventory['parent_id'] . ' WHERE inventory_id=' . $inventory['id'] . '; ' .
									 'UPDATE purchase_item SET inventory_id=' . $inventory['parent_id'] . ' WHERE inventory_id=' . $inventory['id'] . '; ' .
									 'DELETE FROM inventory WHERE id=' . $inventory['id'];
					$correctionStr = 'Update order and purchase items and delete inventory';
				}
				
				echo '<tr class="item_row">' .
					 '<td>' . $inventory['id'] . '</td>' .
					 '<td>' . $inventory['name'] . '</td>' .
					 '<td>' . $inventory['model'] . '</td>' .
					 '<td class="number"><span>' . $inventory['stock_count'] . '</span></td>' .
					 '<td class="number"><span>' . $inventory['reserved_stock'] . '</span></td>' .
					 '<td class="number"><span>' . ( $inventory['order_quantity'] == null ? '(none)' : $inventory['order_quantity'] ) . '</span></td>' .
					 '<td class="number"><span>' . ( $inventory['purchase_quantity'] == null ? '(none)' : $inventory['purchase_quantity'] ) . '</span></td>' .
					 '<td>' . $correctionStr . '</td>' .
					 '</tr>' ;
				
				$correctiveAction = $correctiveAction . $correctionSQL . ';<br />';
			}
			
			echo '</tbody></table>';
			
			echo '</div></section>';
			showCorrectiveCode( $correctiveAction );
			echo '</fieldset>';
			
			break;
		}
		
		default : showCheckItems();
	}
?>

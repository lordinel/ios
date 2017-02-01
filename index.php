<?php
	require_once( "controls/autoload.php" );

	$PAGE_NAME_LOGIN = "Login";
	$PAGE_NAME_HOME = "Home";

	$htmlLayout = new HtmlLayout( $PAGE_NAME_LOGIN );

	$showHomePage = false;


	if ( isset( $_GET['action'] ) ) {
		$htmlLayout->loadStyle( "login" );
		$htmlLayout->paint();

		if ( $_GET['action'] == "logout" ) {		    // user logs out
			if ( $htmlLayout->user->isLoggedIn() ) {
				$htmlLayout->user->logout();
				User::showLoginForm( "logout" );
			} else {
				redirect( 'index.php' );
			}
		} elseif ( $_GET['action'] == "nosession" ) {	// user attempts to access a page directly without logging in
			User::showLoginForm( "nosession" );
		} else {                                        // unrecognized action parameter, display login form
			redirect( 'index.php' );
		}
	} elseif ( isset( $_POST['submit_login'] ) ) {   	// user logs in, validate ID and password
		$database = new Database();
		$loginStatus = $htmlLayout->user->login( $_POST['username'], $_POST['password'], $database );

		if ( $loginStatus == 'login_ok' ) {             // valid username and password, user is logged in
			reloadPage();								// prevent resubmission of form when page is refreshed
		} elseif ( $loginStatus == 'first_login' || $loginStatus == 'password_expired' ) {
			redirect( 'account_settings.php?mode=' . $loginStatus );
		} else {
			$htmlLayout->loadStyle( "login" );
			$htmlLayout->paint();
			User::showLoginForm( $loginStatus );		// access denied
		}
	} elseif ( $htmlLayout->user->isLoggedIn() ) {		// user session is active
		$showHomePage = true;
	} else {											// no session active, display login form
		$htmlLayout->loadStyle( "login" );
		$htmlLayout->paint();
		User::showLoginForm();
	}


	if ( $showHomePage == true )
	{
		function inlineScript() {
			?><script type="text/javascript"><!--
				$(function() {
					$('#tabs').tabs();
				});
			// --></script><?php
		}
		
		$htmlLayout->setPageTitle( $PAGE_NAME_HOME );
		$htmlLayout->loadStyle( "homepage" );
		$htmlLayout->paint( null, 'inlineScript' );
		$htmlLayout->showMainMenu();
		
		if ( !isset( $database ) ) {
			$database = new Database;
		}
		
		$currentDate = new DateTime();

		$branchFilter = User::getQueryForBranch($database);
?>

	<!--<div id="heading">
		<img src="images/blocks.png" />
		<h2>Welcome to <?php echo PROG_NAME ?><br /><span><?php echo PROG_NAME_LONG ?></span></h2>
	</div>-->

	
	<div id="home_content">
		<div id="left_sidebar" class="sidebar panel">
			<span class="sidebar_header">Today at a Glance</span>
			<br />
			<br />
			<div id="tabs">
				<ul>
                	<li><a href="#tab_content_1">Orders</a></li>
                    <li><a href="#tab_content_2">Purchases</a></li>
                </ul>
                
				<div id="tab_content_1" class="tab_content">
                	<table>
						<thead>
							<tr>
								<th colspan="2">Orders to Deliver</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$sqlQuery = "SELECT `order`.id, SUM(undelivered_quantity) AS quantity FROM `order` " .
										"INNER JOIN customer ON `order`.customer_id = customer.id " .
										"INNER JOIN order_item ON `order`.id = order_item.order_id " .
										"WHERE DATE_FORMAT(delivery_pickup_target_date, '%Y-%m-%d') = '" . $currentDate->format( 'Y-m-d' ) . "' " .
										"AND delivery_pickup_actual_date IS NULL " .
										"AND cleared_date IS NULL " .
										"AND canceled_date IS NULL " .
										"AND " . $branchFilter . " " .
										"GROUP BY order_id " .
										"ORDER BY delivery_pickup_target_date ASC";
							$resultSet = $database->query( $sqlQuery );
							if ( $database->getResultCount( $resultSet ) == 0 ) {
								echo '<tr>' .
									 '<td colspan="2" class="glance_none">None for today</td>' .
									 '</tr>';
							} else {
								while ( $order = $database->getResultRow( $resultSet ) ) {
									echo '<tr>' .
										 '<td><a href="view_order_details.php?id=' . $order['id'] . '">' . $order['id'] . '</a></td>' .
										 '<td class="number"><span>' . numberFormat( $order['quantity'], 'int', 0 ) . ' item(s)</span></td>' .
										 '</tr>';
								}
							}
						?>
                        	<tr>
                            	<td colspan="2" class="glance_see_all_link"><a href="list_orders.php?criteria=orders-to-deliver">See all »</a></td>
                            </tr>
						</tbody>
					</table>
					
					<br />
					
					<table>
						<thead>
							<tr>
								<th colspan="2">Payments to Collect</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$sqlQuery = "(SELECT `order`.id AS order_id, balance AS amount_to_collect, delivery_pickup_target_date AS payment_date " .
										"FROM `order` " .
										"INNER JOIN customer ON `order`.customer_id = customer.id " .
										"WHERE DATE_FORMAT(delivery_pickup_target_date, '%Y-%m-%d') = '" . $currentDate->format( 'Y-m-d' ) . "' " .
										"AND payment_term = 'full' " .
										"AND cleared_date IS NULL " .
										"AND canceled_date IS NULL " .
										"AND " . $branchFilter . " " .
										"AND balance > 0) " .
										"UNION " .
										"(SELECT order_id, SUM(amount_due-amount_received) AS amount_to_collect, due_date AS payment_date " .
										"FROM v_order_installment_paid " .
										"INNER JOIN `order` ON v_order_installment_paid.order_id = `order`.id " .
										"WHERE DATE_FORMAT(due_date, '%Y-%m-%d') = '" . $currentDate->format( 'Y-m-d' ) . "' " .
										"AND payment_term = 'installment' " .
										"AND cleared_date IS NULL " .
										"AND canceled_date IS NULL " .
										"GROUP BY order_id " .
										"HAVING amount_to_collect > 0) " .
										"ORDER BY payment_date ASC";
							$resultSet = $database->query( $sqlQuery );
							if ( $database->getResultCount( $resultSet ) == 0 ) {
								echo '<tr>' .
									 '<td class="glance_none">None for today</td>' .
									 '</tr>';
							} else {
								while ( $order = $database->getResultRow( $resultSet ) ) {
									//$orderNumber = str_pad( $order['order_id'], 10, '0', STR_PAD_LEFT );
									
									echo '<tr>' .
										 '<td><a href="view_order_details.php?id=' . $order['order_id'] . '">' . $order['order_id'] . '</a></td>' .
										 '<td class="number"><span>' . numberFormat( $order['amount_to_collect'], 'currency' ) . '</span></td>' .
										 '</tr>';
								}
							}
						?>
                        	<tr>
                            	<td colspan="2" class="glance_see_all_link"><a href="list_orders.php?criteria=payments-to-collect">See all »</a></td>
                            </tr>
						</tbody>
					</table>
					
					<br />
					
					<table>
						<thead>
							<tr>
								<th colspan="2">Payments to Clear</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$sqlQuery = "SELECT * FROM order_payment " .
										"INNER JOIN `order` ON `order`.id = order_payment.order_id " .
										"INNER JOIN customer ON `order`.customer_id = customer.id " .
										"WHERE DATE_FORMAT(clearing_target_date, '%Y-%m-%d') = '" . $currentDate->format( 'Y-m-d' ) . "' " .
										"AND clearing_actual_date IS NULL " .
										"AND " . $branchFilter . " " .
										"ORDER BY clearing_target_date ASC";
							$resultSet = $database->query( $sqlQuery );
							if ( $database->getResultCount( $resultSet ) == 0 ) {
								echo '<tr>' .
									 '<td colspan="2" class="glance_none">None for today</td>' .
									 '</tr>';
							} else {
								while ( $order = $database->getResultRow( $resultSet ) ) {
									echo '<tr>' .
										 '<td><a href="view_order_details.php?id=' . $order['order_id'] . '">' . $order['order_id'] . '</a></td>' .
										 '<td class="number"><span>';
									if ( $order['amount'] < 0 ) {
										echo '(' . numberFormat( $order['amount'] * -1, 'currency' ) . ') rebate';
									} else {
										echo numberFormat( $order['amount'], 'currency' ) . ' ' . $order['payment_type'];
									}
									echo '</span></td>' .
										 '</tr>';
								}
							}
						?>
                        	<tr>
                            	<td colspan="2" class="glance_see_all_link"><a href="list_orders.php?criteria=payments-to-clear">See all »</a></td>
                            </tr>
						</tbody>
					</table>
                </div>
                
                <div id="tab_content_2" class="tab_content">
                	<table>
						<thead>
							<tr>
								<th colspan="2">Purchases to Deliver</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$sqlQuery = "SELECT purchase.id, SUM(undelivered_quantity) AS quantity FROM purchase " .
										"INNER JOIN purchase_item ON purchase.id = purchase_item.purchase_id " .
										"WHERE DATE_FORMAT(delivery_pickup_target_date, '%Y-%m-%d') = '" . $currentDate->format( 'Y-m-d' ) . "' " .
										"AND delivery_pickup_actual_date IS NULL " .
										"AND cleared_date IS NULL " .
										"AND canceled_date IS NULL " .
										"GROUP BY purchase_id " .
										"ORDER BY delivery_pickup_target_date ASC";
							$resultSet = $database->query( $sqlQuery );
							if ( $database->getResultCount( $resultSet ) == 0 ) {
								echo '<tr>' .
									 '<td colspan="2" class="glance_none">None for today</td>' .
									 '</tr>';
							} else {
								while ( $purchase = $database->getResultRow( $resultSet ) ) {
									echo '<tr>' .
										 '<td><a href="view_purchase_details.php?id=' . $purchase['id'] . '">' . $purchase['id'] . '</a></td>' .
										 '<td class="number"><span>' . numberFormat( $purchase['quantity'], 'int', 0 ) . ' item(s)</span></td>' .
										 '</tr>';
								}
							}
						?>
                        	<tr>
                            	<td colspan="2" class="glance_see_all_link"><a href="list_purchases.php?criteria=purchases-awaiting-delivery">See all »</a></td>
                            </tr>
						</tbody>
					</table>
					
					<br />
					
					<table>
						<thead>
							<tr>
								<th colspan="2">Purchases to Pay</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$sqlQuery = "(SELECT id AS purchase_id, balance AS amount_to_pay, delivery_pickup_target_date AS payment_date " .
										"FROM purchase " .
										"WHERE DATE_FORMAT(delivery_pickup_target_date, '%Y-%m-%d') = '" . $currentDate->format( 'Y-m-d' ) . "' " .
										"AND payment_term = 'full' " .
										"AND cleared_date IS NULL " .
										"AND canceled_date IS NULL " .
										"AND balance > 0) " .
										"UNION " .
										"(SELECT purchase_id, SUM(amount_due-amount_paid) AS amount_to_pay, due_date AS payment_date " .
										"FROM v_purchase_installment_paid " .
										"INNER JOIN purchase ON v_purchase_installment_paid.purchase_id = purchase.id " .
										"WHERE DATE_FORMAT(due_date, '%Y-%m-%d') = '" . $currentDate->format( 'Y-m-d' ) . "' " .
										"AND payment_term = 'installment' " .
										"AND cleared_date IS NULL " .
										"AND canceled_date IS NULL " .
										"GROUP BY purchase_id " .
										"HAVING amount_to_pay > 0) " .
										"ORDER BY payment_date ASC";
							$resultSet = $database->query( $sqlQuery );
							if ( $database->getResultCount( $resultSet ) == 0 ) {
								echo '<tr>' .
									 '<td class="glance_none">None for today</td>' .
									 '</tr>';
							} else {
								while ( $purchase = $database->getResultRow( $resultSet ) ) {
									//$purchaseNumber = str_pad( $purchase['purchase_id'], 10, '0', STR_PAD_LEFT );
									
									echo '<tr>' .
										 '<td><a href="view_purchase_details.php?id=' . $purchase['purchase_id'] . '">' . $purchase['purchase_id'] . '</a></td>' .
										 '<td class="number"><span>' . numberFormat( $purchase['amount_to_pay'], 'currency' ) . '</span></td>' .
										 '</tr>';
								}
							}
						?>
                        	<tr>
                            	<td colspan="2" class="glance_see_all_link"><a href="list_purchases.php?criteria=purchases-to-pay">See all »</a></td>
                            </tr>
						</tbody>
					</table>
					
					<br />
					
					<table>
						<thead>
							<tr>
								<th colspan="2">Payments to Clear</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$sqlQuery = "SELECT * FROM purchase_payment " .
										"WHERE DATE_FORMAT(clearing_target_date, '%Y-%m-%d') = '" . $currentDate->format( 'Y-m-d' ) . "' " .
										"AND clearing_actual_date IS NULL " .
										"ORDER BY clearing_target_date ASC";
							$resultSet = $database->query( $sqlQuery );
							if ( $database->getResultCount( $resultSet ) == 0 ) {
								echo '<tr>' .
									 '<td colspan="2" class="glance_none">None for today</td>' .
									 '</tr>';
							} else {
								while ( $purchase = $database->getResultRow( $resultSet ) ) {
									echo '<tr>' .
										 '<td><a href="view_purchase_details.php?id=' . $purchase['purchase_id'] . '">' . $purchase['purchase_id'] . '</a></td>' .
										 '<td class="number"><span>';
									if ( $purchase['amount'] < 0 ) {
										echo '(' . numberFormat( $purchase['amount'] * -1, 'currency' ) . ') rebate';
									} else {
										echo numberFormat( $purchase['amount'], 'currency' ) . ' ' . $purchase['payment_type'];
									}
									echo '</span></td>' .
										 '</tr>';
								}
							}
						?>
                        	<tr>
                            	<td colspan="2" class="glance_see_all_link"><a href="list_purchases.php?criteria=payments-to-clear">See all »</a></td>
                            </tr>
						</tbody>
					</table>
                </div>
			</div>
            
			<script type="text/javascript">
			<!--
				loadAccordion( 'zero' );
			// -->
			</script>
		</div>
		
		<div id="activity_stream" class="nolinkdecor">
			<header>Recent Activities</header>
            
            <div id="activity_stream_content">
            <?php
				$sqlQuery = "SELECT event_log.*, user.name FROM event_log " .
							"INNER JOIN user ON event_log.encoder = user.id " .
							"WHERE `table` != 'user' " .
							"AND `table` != 'agent' " .
							"AND `table` != 'inventory' " .
							"AND `table` != 'inventory_brand' ";
                if ( !$htmlLayout->user->checkPermission( 'orders_and_customers', $database ) ) {
	                $sqlQuery = $sqlQuery . "AND `table` != 'order' AND `table` != 'order_payment' AND `table` != 'customer' ";
                }
                if ( !$htmlLayout->user->checkPermission( 'purchases_and_suppliers', $database ) ) {
	                $sqlQuery = $sqlQuery . "AND `table` != 'purchase' AND `table` != 'purchase_payment' AND `table` != 'supplier' ";
                }
                $sqlQuery = $sqlQuery . "ORDER BY date DESC LIMIT 0,5";
				$resultSet = $database->query( $sqlQuery );
                
				if ( $database->getResultCount( $resultSet ) == 0 ) {
					echo '<div class="activity_stream_event" id="activity_stream_empty">';
					echo '<span>No recent activities</span>';
					echo '</div>';
				} else {
					while ( $event = $database->getResultRow( $resultSet ) ) {
						echo '<div class="activity_stream_event">';

						if ( $event['table'] == 'order' || $event['table'] == 'purchase'  ) {
							if ( $event['table'] == 'order' ) {
								echo '<div class="event_image"><img src="images/event/orders.png" />';
							} else {
								echo '<div class="event_image"><img src="images/event/purchases.png" />';
							}
							switch ( $event['action'] ) {
								case 'new' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/new.png" /></span>';
									break;
								}
								case 'delivered' : {
									if ( $event['table'] == 'order' ) {
										echo '<span class="event_image_overlay"><img src="images/event/overlay/delivered.png" /></span>';
									} else {
										echo '<span class="event_image_overlay"><img src="images/event/overlay/received.png" /></span>';
									}
									break;
								}
								case 'undelivered' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/returned.png" /></span>';
									break;
								}
								case 'cleared' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/cleared.png" /></span>';
									break;
								}
								case 'uncleared' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/uncleared.png" /></span>';
									break;
								}
								case 'canceled' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/canceled.png" /></span>';
									break;
								}
								case 'uncanceled' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/uncanceled.png" /></span>';
									break;
								}
								case 'modified' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/edited.png" /></span>';
									break;
								}
							}
							echo '</div>';
						} if ( $event['table'] == 'order_payment' || $event['table'] == 'purchase_payment'  ) {
							if ( $event['table'] == 'order_payment' ) {
								echo '<div class="event_image"><img src="images/event/orders.png" />';
							} else {
								echo '<div class="event_image"><img src="images/event/purchases.png" />';
							}
							switch ( $event['action'] ) {
								case 'cleared' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/payment-cleared.png" /></span>';
									break;
								}
								case 'uncleared' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/payment-uncleared.png" /></span>';
									break;
								}
								case 'rebate' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/payment-rebate.png" /></span>';
									break;
								}
								case 'removed' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/payment-removed.png" /></span>';
									break;
								}
								case 'waived' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/payment-waived.png" /></span>';
									break;
								}
								case 'unwaived' : {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/payment-unwaived.png" /></span>';
									break;
								}
								default: {
									echo '<span class="event_image_overlay"><img src="images/event/overlay/payment.png" /></span>';
								}
							}
							echo '</div>';
						} elseif ( $event['table'] == 'customer' ) {
							echo '<div class="event_image"><img src="images/event/customers.png" />';
							if ( $event['action'] == 'new' ) {
								echo '<span class="event_image_overlay"><img src="images/event/overlay/new.png" /></span>';
							} else {
								echo '<span class="event_image_overlay"><img src="images/event/overlay/edited.png" /></span>';
							}
							echo '</div>';
						} elseif ( $event['table'] == 'supplier' ) {
							echo '<div class="event_image"><img src="images/event/suppliers.png" />';
							if ( $event['action'] == 'new' ) {
								echo '<span class="event_image_overlay"><img src="images/event/overlay/new.png" /></span>';
							} else {
								echo '<span class="event_image_overlay"><img src="images/event/overlay/edited.png" /></span>';
							}
							echo '</div>';
						}
						
						echo '<div class="event_content">';
						
						echo stripslashes( $event['event'] ) . '<br />';
						
						// determine event age
						$eventDate = new DateTime( $event['date'] );
						$eventAge = $eventDate->diff( $currentDate );
						
						$year = $eventAge->format( '%y' );
						$month = $eventAge->format( '%m' );
						$day = $eventAge->format( '%d' );
						$hour = $eventAge->format( '%h' );
						$minute = $eventAge->format( '%i' );
						
						if ( $year > 0 ) {
							$eventAgeValue = $year;
							if ( $year == 1 ) {
								$eventAgeUnit = 'year';
							} else {
								$eventAgeUnit = 'years';
							}
						} elseif ( $month > 0 ) {
							$eventAgeValue = $month;
							if ( $month == 1 ) {
								$eventAgeUnit = 'month';
							} else {
								$eventAgeUnit = 'months';
							}
						} elseif ( $day > 0 ) {
							$eventAgeValue = $day;
							if ( $day == 1 ) {
								$eventAgeUnit = 'day';
							} else {
								$eventAgeUnit = 'days';
							}
						} elseif ( $hour > 0 ) {
							$eventAgeValue = $hour;
							if ( $hour == 1 ) {
								$eventAgeUnit = 'hour';
							} else {
								$eventAgeUnit = 'hours';
							}
						} else {
							if ( $minute == 0 ) {
								$eventAgeValue = 'Few';
								$eventAgeUnit = 'seconds';
							} elseif ( $minute == 1 ) {
								$eventAgeValue = $minute;
								$eventAgeUnit = 'minute';
							} else {
								$eventAgeValue = $minute;
								$eventAgeUnit = 'minutes';
							}
						}
						
						
						echo '<div class="activity_age">' .
							 '<span class="activity_age_value">' . $eventAgeValue . '</span> ' .
							 '<span class="activity_age_unit">' . $eventAgeUnit . ' ago</span>' .
							 '<span class="activity_dot">&#8226;</span>' .
							 '<span class="activity_encoder">' . capitalizeWords( Filter::output( $event['name'] ) ) . '</span>' .
							 '</div>';
						//echo '<p>' . $eventDate->format( 'M j, Y g:i:s A' ) . ' Year: ' . $year . ' Month: ' . $month . ' Day: ' . $day . ' Hour: ' .$hour . ' Minute: ' .$minute . '</p>';
						echo '</div>';
						
						echo '<br style="clear: both" />';
						
						echo '</div>';
					}
				}
			?>
            </div>
		</div>

		<?php
		echo '<div id="right_sidebar" class="sidebar"><div class="panel right_panel nolinkdecor">' .
			 '<span class="sidebar_header">Tasks</span><br />' .
			 '<ul>';

		if ( User::checkPermission( 'orders_and_customers', $database ) ) {
			echo '<li><img src="images/task_pane/view_orders.png" /><a href="list_orders.php">View Orders</a></li>' .
				 '<li><img src="images/task_pane/add_order.png" /><a href="add_order.php">Add Order</a></li>' .
				 '<li><img src="images/task_pane/collect_payments.png" /><a href="list_customers.php?criteria=with-receivable">Collect Payments</a></li>';
		}

		if ( User::checkPermission( 'purchases_and_suppliers', $database ) ) {
			echo '<li><img src="images/task_pane/view_purchases.png" /><a href="list_purchases.php">View Purchases</a></li>' .
				 '<li><img src="images/task_pane/purchase_supplies.png" /><a href="purchase_supplies.php">Purchase Supplies</a></li>' .
				 '<li><img src="images/task_pane/pay_suppliers.png" /><a href="list_suppliers.php?criteria=with-payable">Pay Suppliers</a></li>';
		}

		if ( User::checkPermission( 'inventory', $database ) ) {
			echo '<li><img src="images/task_pane/check_inventory.png" /><a href="list_inventory.php">Check Inventory</a></li>';
		}

		if ( User::checkPermission( 'daily_sales_report', $database ) ) {
			echo '<li><img src="images/task_pane/daily_sales_report.png" /><a href="report.php?type=daily-sales">Daily Sales Report</a></li>';
		}

		echo '<li><img src="images/task_pane/tutorials.png" /><a href="buglist.php">Tutorials</a></li>';

		echo '</ul></div>';


		echo '<div id="about_section" class="panel right_panel">';
		echo '<div id="about_section_img"><img src="images/heading/about.png" /></div>';
		echo '<div id="about_section_text"><span id="about_section_text_h1">Welcome to ' . PROG_NAME . '</span><br />' .
			 PROG_NAME_LONG . '<br />' .
			 'Licensed to: ' . CLIENT . '<br />' .
			 '<a href="about.php">About ' . PROG_NAME . ' »</a></div>';

		echo '</div></div></div>';
	}
?>

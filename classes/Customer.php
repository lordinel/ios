<?php
// note: this class requires scripts/customer.js


// class definition for customers
class Customer extends Person
{
	//const CLASS_NAME = "customer";
	const NAME_LABEL = "Customer Name";
	const SHOW_CONTACT_PERSON = true;
	const ADDRESS_LABEL = "Delivery Address";



	// additional attributes
	private $creditLimit;
	private $remainingCredits;
	private $creditTerms;
	private $branch;



	// display customer form
	public static function showInputForm( $id = null )
	{
		echo "<form name=\"" . ( $id == null ? "add" : "edit" ) . "_customer\" method=\"post\" action=\"view_customer_details.php\" autocomplete=\"off\" onreset=\"return confirmReset('resetCustomerForm')\">\n";
		self::showInputFieldset( $id );
		self::showButtons( ButtonSet::SUBMIT_RESET_CANCEL );
		echo "</form>\n";
	}



	// display customer form fiedset
	public static function showInputFieldset( $id = null, array $order = null )
	{
		echo "<fieldset><legend>Customer Info</legend>\n";
		
		if ( self::$database == null ) {
			self::$database = new Database();
		}
		
		$branchList = self::getBranches();

		if ( $id != null ) {
			$sqlQuery = "SELECT customer.*, " .
						"(customer.credit_limit - (" .
							"v_customer_payment_summary.amount_receivable " .
							"+ v_customer_payment_summary.pdc_receivable" .
						")) AS credit_remaining " .
						"FROM customer " .
						"LEFT JOIN v_customer_payment_summary ON customer.id = v_customer_payment_summary.customer_id " .
						"WHERE customer.id = " . $id;
			$resultSet = self::$database->query( $sqlQuery );
			$customerInfo = self::$database->getResultRow( $resultSet );
			
			if ( $order != null ) {
				$customerInfo['credit_remaining'] = $customerInfo['credit_remaining'] + ($order['amount_receivable'] - $order['interest']);
			}
			
			// check if user has permission to view the user
			if ( User::isBranchVisible( $customerInfo['branch_id'], self::$database ) == false ) {
				?><section><div><br />
						<span class="now">Error:</span> You do not have permission to view customer "<?php
						echo capitalizeWords( Filter::output( $customerInfo['name'] ) );
						?>".<br />Please contact your Administrator.
					<br /><br /><br /><br /></div></section></fieldset>
				<?php
				die();
			}
		} else {
			$customerInfo = null;
		}

		self::showBasicInputFields( $customerInfo, self::NAME_LABEL, self::SHOW_CONTACT_PERSON, self::ADDRESS_LABEL );
?>
		<section>
			<div>
				<label for="credit_limit">Credit Limit: <?php echo CURRENCY ?></label>
				<input type="text" name="credit_limit" id="credit_limit" class="number" maxlength="100" required="required" value="<?php echo ( $customerInfo != null ) ? numberFormat( Filter::reinput( $customerInfo['credit_limit'] ), 'float', 3, '.', '', true ) : "0.000" ?>" />
                <span id="remaining_credits_field"<?php echo ( $customerInfo == null ? ' style="display: none"' : "" ) ?>>
	                <span class="span_label">Remaining Credits: <?php echo CURRENCY ?></span>
    	            <input type="text" id="remaining_credits" class="number output_field" disabled="disabled" value="<?php echo ( $customerInfo != null ? numberFormat( $customerInfo['credit_remaining'], 'float', 3, '.', '', true ) : "0.000" ) ?>" />
                </span>
			</div>
			<div>
				<label for="credit_terms">Credit Terms:</label>
				<select name="credit_terms" id="credit_terms" class="form_input_select">
					<option value="30 days"<?php echo ( $customerInfo != null ? ( $customerInfo['credit_terms'] == "30 days" ? " selected=\"selected\"" : "" ) : " selected=\"selected\"" ) ?>>30 days</option>
					<option value="60 days"<?php echo ( $customerInfo != null ? ( $customerInfo['credit_terms'] == "60 days" ? " selected=\"selected\"" : "" ) : "" ) ?>>60 days</option>
					<option value="90 days"<?php echo ( $customerInfo != null ? ( $customerInfo['credit_terms'] == "90 days" ? " selected=\"selected\"" : "" ) : "" ) ?>>90 days</option>
					<option value="120 days"<?php echo ( $customerInfo != null ? ( $customerInfo['credit_terms'] == "120 days" ? " selected=\"selected\"" : "" ) : "" ) ?>>120 days</option>
					<option value="150 days"<?php echo ( $customerInfo != null ? ( $customerInfo['credit_terms'] == "150 days" ? " selected=\"selected\"" : "" ) : "" ) ?>>150 days</option>
				</select>
			</div>
		</section>
		<section>
			<div id="branch_assignments">
				<label for="branch_assignments">Branch Assignments:</label>
				<span id="branch_list">
				<?php
				if ( $branchList != null ) {
					$tempBranches = explode(',', $customerInfo['branch_id']);
					foreach ( $tempBranches as $tempBranchKey ) {
						$customerBranches[$tempBranchKey] = true;
					}
					$i = 1;
					foreach ( $branchList as $branchKey => $branchName ) {
						echo '<span><input type="checkbox" name="branch_assignments[]" id="branch_' . $branchKey . 
				 			 '" value="' . $branchKey . '"';
						if ( isset($customerBranches[$branchKey]) || $customerInfo == null ) {
							echo ' checked="checked"';
						}
						echo ' />';
						echo '<label for="branch_' .  $branchKey . '" class="branch_label">' . $branchName . '</label></span>';
						if ( $i % 4 == 0 ) {
							echo '<br />';
						}
						$i++;
					}
				} else {
					echo '<option value="0">-- No available branches --</option>';
				}
				?>
				</span>
			</div>
			<div>
				<label></label>
				<span class="form_hint">
					<span class="inline_msg">
					<span class="now">Note:</span> You can only view the customer and the corresponding orders if you and the customer belong to the same branch.</span>
				</span>
			</div>
		</section>
<?php
		echo "</fieldset>\n";
	}



	// save customer info
	public function save()
	{
		if ( $_POST['customer_query_mode'] == "new" ) {		// new customer
			$this->prepareBasicInputData();
			$this->creditLimit = Filter::input( $_POST['credit_limit'] );
			$this->creditTerms = "'" . $_POST['credit_terms'] . "'";
			if ( !empty( $_POST['branch_assignments'] ) ) {
				$branch = "'";
				for ( $i = 0; $i < sizeof( $_POST['branch_assignments'] ); $i++ ) {
					if ( $i > 0 ) {
						$branch = $branch . ",";
					}
					$branch = $branch . $_POST['branch_assignments'][$i];
				}
				$branch = $branch . "'";
			} else {
				$branch = "NULL";
			}

			$sqlQuery = "INSERT INTO customer VALUES (" .
						"NULL," .								// id, auto-generate
						$this->name . "," .						// name
						$this->contactPerson . "," .			// contact_person
						$this->address . "," .					// address
						$this->telephone . "," .				// telephone
						$this->mobile . "," .					// mobile
						$this->fax . "," .						// fax
						$this->email . "," .					// email
						$this->creditLimit . "," .				// credit_limit
						$this->creditTerms . "," .				// credit_terms
						$branch . ")";							// branch_id
			self::$database->query( $sqlQuery );

			// get generated customer ID
			$this->id = self::$database->getLastInsertID();
			
			// log event
			EventLog::addEntry( self::$database, 'info', 'customer', 'insert', 'new',
								'<span class="event_log_main_record_inline"><a href="view_customer_details.php?id=' . $this->id . '">' .
								capitalizeWords( htmlentities( $_POST['customer_name'] ) ) .
								'</a></span> was <span class="event_log_action">added</span> to <a href="list_customers.php">Customers</a>' );
			
		} elseif ( $_POST['customer_query_mode'] == "edit" ) {		// existing customer, update records
			$this->id = $_POST['customer_id'];

			$this->prepareBasicInputData();
			$this->creditLimit = Filter::input( $_POST['credit_limit'] );
			$this->creditTerms = "'" . $_POST['credit_terms'] . "'";
			if ( !empty( $_POST['branch_assignments'] ) ) {
				$branch = "'";
				for ( $i = 0; $i < sizeof( $_POST['branch_assignments'] ); $i++ ) {
					if ( $i > 0 ) {
						$branch = $branch . ",";
					}
					$branch = $branch . $_POST['branch_assignments'][$i];
				}
				$branch = $branch . "'";
			} else {
				$branch = "NULL";
			}

			$sqlQuery = "UPDATE customer SET " .
						"name=" . $this->name . "," .						// name
						"contact_person=" . $this->contactPerson . "," .	// contact_person
						"address=" . $this->address . "," .					// address
						"telephone=" . $this->telephone . "," .				// telephone
						"mobile=" . $this->mobile . "," .					// mobile
						"fax=" . $this->fax . "," .							// fax
						"email=" . $this->email . "," .						// email
						"credit_limit=" . $this->creditLimit . "," .		// credit_limit
						"credit_terms=" . $this->creditTerms . "," .		// credit_terms
						"branch_id=" . $branch .							// branch_id
						" WHERE id=" . $this->id;
			self::$database->query( $sqlQuery );
			
			// log event
			EventLog::addEntry( self::$database, 'info', 'customer', 'update', 'modified',
								'Customer <span class="event_log_main_record_inline"><a href="view_customer_details.php?id=' . $this->id . '">' .
								capitalizeWords( htmlentities( $_POST['customer_name'] ) ) .
								'</a></span> was <span class="event_log_action">modified</span>' );
			
		} else {                                                    // no further processing, just get the ID
			$this->id = $_POST['customer_id'];
		}

		return $this->id;
	}



	// tasks for customer details
	public function showDetailsTasks()
	{
?>		<div id="tasks">
			<ul>
				<li id="task_edit_customer"><a href="edit_customer.php?id=<?php echo $this->id ?>"><img src="images/task_buttons/edit.png" />Edit Customer</a></li>
				<li id="task_back_to_list"><a href="list_customers.php<?php echo (isset($_GET['src']) ? '?criteria='.$_GET['src'] : '') ?>">
						<img src="images/task_buttons/back_to_list.png" />Back to List</a></li>
			</ul>
		</div>
	</div>
<?php
	}



	// view customer info
	public function view()
	{
		$branchList = self::getBranches();
		
		// get main customer info
		$sqlQuery = "SELECT customer.*, " .
					"(customer.credit_limit - (" .
						"v_customer_payment_summary.amount_receivable " .
						"+ v_customer_payment_summary.pdc_receivable" .
					")) AS credit_remaining, " .
					"COUNT(v_active_orders.id) AS active_orders_count, " .
					"v_customer_payment_summary.amount_receivable AS amount_receivable, " .
					"v_customer_payment_summary.pdc_receivable AS pdc_receivable, " .
					"v_customer_payment_summary.amount_receivable + v_customer_payment_summary.pdc_receivable AS total_receivable, " .
					"v_customer_payment_summary.rebate_payable AS rebate_payable, " .
					"v_customer_payment_summary.pdc_rebate_payable AS pdc_rebate_payable, " .
					"v_customer_payment_summary.rebate_payable + v_customer_payment_summary.pdc_rebate_payable AS total_rebate " .
					"FROM customer " .
					"LEFT JOIN v_active_orders ON customer.id = v_active_orders.customer_id " .
					"LEFT JOIN v_customer_payment_summary ON customer.id = v_customer_payment_summary.customer_id " .
					"WHERE customer.id = " . $this->id;
		$resultSet = self::$database->query( $sqlQuery );
		$customer = self::$database->getResultRow( $resultSet );
		
		// check if user has permission to view the customer
		if ( User::isBranchVisible( $customer['branch_id'], self::$database ) == false ) {
			?><fieldset><legend>Customer Info</legend>
				<section><div><br />
					<span class="now">Error:</span> You do not have permission to view customer "<?php
					echo capitalizeWords( Filter::output( $customer['name'] ) );
					?>".<br />Please contact your Administrator.
				<br /><br /><br /><br /></div></section>
			</fieldset>
			<script type="application/javascript">
			<!--
				$("#task_edit_customer").hide();
			// -->
			</script>
			<?php
			die();
		}

		if ( $customer['id'] != null )		// customer ID is found in database
		{
			$this->name				= capitalizeWords( Filter::output( $customer['name'] ) );
			$this->contactPerson	= capitalizeWords( Filter::output( $customer['contact_person'] ) );
			$this->address			= capitalizeWords( Filter::output( $customer['address'] ) );
			$this->telephone		= Filter::output( $customer['telephone'] );
			$this->mobile			= Filter::output( $customer['mobile'] );
			$this->fax				= Filter::output( $customer['fax'] );
			$this->email			= strtolower( Filter::output( $customer['email'] ) );
			$this->creditLimit		= numberFormat( $customer['credit_limit'], "currency" );
			$this->creditTerms		= $customer['credit_terms'];
			$this->remainingCredits = numberFormat($customer['credit_remaining'], "currency" );
			$this->branch			= explode(',', $customer['branch_id']);


			HtmlLayout::setPageTitleStatic('Customers » '.addslashes( html_entity_decode( capitalizeWords( Filter::output( $customer['name'] ) ) ) ) );

			echo "<fieldset><legend>Customer Info</legend>\n";

			$this->showBasicInfo( self::SHOW_CONTACT_PERSON, self::ADDRESS_LABEL );

?>
		<section>
			<div>
				<span class="record_label">Credit Limit:</span>
				<span class="record_data"><?php echo $this->creditLimit ?></span>
			</div>
            <div>
				<span class="record_label">Remaining Credits:</span>
				<span class="record_data"><?php echo $this->remainingCredits ?></span>
			</div>
			<div>
				<span class="record_label">Credit Terms:</span>
				<span class="record_data"><?php echo $this->creditTerms ?></span>
			</div>
		</section>
		<section>
			<div>
				<span class="record_label">Branch Assignments:</span>
				<span class="record_data">
				<?php
					if ( $this->branch[0] != null ) {
						$i = 1;
						foreach ($this->branch as $branchKey) {
							if ($i > 1) {
								echo ", ";
							}
							echo capitalizeWords(Filter::output($branchList[$branchKey]));
							$i++;
						}
					} else {
						echo "-- None --";
					}
				?>
				</span>
			</div>
		</section>
	</fieldset>
    
<?php
			// get orders info
			$sqlQuery = "SELECT COUNT(id) AS count FROM `order` WHERE customer_id = " . $this->id;
			$resultSet = self::$database->query( $sqlQuery );
			$order = self::$database->getResultRow( $resultSet );
			if ( (int) $order['count'] > 0 )
			{
?>
	<fieldset><legend>Orders List</legend>
        <section id="order_list_section">
        </section>
    </fieldset>

	<script type="text/javascript">
	<!--
		ajax( null, 'order_list_section', 'innerHTML', 'Order::showList', 'criteria=all-orders&filterName=customer_id&filterValue=<?php echo $this->id ?>' );
	// -->
	</script>

	<fieldset><legend>Totals</legend>
        <section>
        	<div>
                <span class="record_label">Total No. of Orders:</span>
                <span class="record_data"><?php echo numberFormat( $order['count'], "int" ) ?></span>
            </div>
            <div>
                <span class="record_label">Pending Orders:</span>
                <span class="record_data"><?php echo numberFormat( $customer['active_orders_count'], "int" ) ?></span>
            </div>
		</section>
        
		<section>
            <div>
                <span class="record_label">Amount Receivable:</span>
                <span class="record_data"><?php	echo numberFormat( $customer['amount_receivable'], "currency" ) ?></span>
            </div>
            <div>
                <span class="record_label">PDC Receivable:</span>
                <span class="record_data"><?php	echo numberFormat( $customer['pdc_receivable'], "currency" ) ?></span>
            </div>
			<div>
                <span class="record_label">Total Receivable:</span>
                <span class="record_data"><?php	echo numberFormat( $customer['total_receivable'], "currency" ) ?></span>
            </div>
		</section>
        
		<section>
            <div>
                <span class="record_label">Rebate Payable:</span>
                <span class="record_data"><?php	echo numberFormat( $customer['rebate_payable'], "currency" ) ?></span>
            </div>
            <div>
                <span class="record_label">PDC Rebate:</span>
                <span class="record_data"><?php	echo numberFormat( $customer['pdc_rebate_payable'], "currency" ) ?></span>
            </div>
            <div>
                <span class="record_label">Total Rebate:</span>
                <span class="record_data"><?php	echo numberFormat( $customer['total_rebate'], "currency" ) ?></span>
            </div>
		</section>
	</fieldset>
    
    <?php
	// default date range
	$startDate 		= new DateTime( date('Y') . '-01-01' );	// January 1 of present year
	$startDateStr 	= $startDate->format( "F j, Y, D" );
	
	$endDate 		= new DateTime();						// present date
	$endDateStr 	= $endDate->format( "F j, Y, D" );
	?>
	
	<fieldset><legend>History and Statistics</legend>
    	<section>
            <div class="report_data">
                <form name="report_date_form">
                    <label for="startdate">Start Date:</label>
                    <input type="text" name="startdate" id="startdate" class="datepicker" size="30" maxlength="30" required="required" value="<?php echo $startDateStr ?>" />
                    <label for="enddate">End Date:</label>
                    <input type="text" name="enddate" id="enddate" class="datepicker" size="30" maxlength="30" required="required" value="<?php echo $endDateStr ?>" />
                    <input type="button" name="submit_form" value="Go" onclick="javascript:ajax( null, 'customer_statistics_section', 'innerHTML', 'Customer::showHistoryAndStatistics', 'customerID=<?php echo $this->id ?>&startDate=' + $('#startdate').val() + '&endDate=' + $('#enddate').val() )" />
                </form>
            </div>
        </section>
    	<section id="customer_statistics_section">
        </section>
	</fieldset>
    
    <script type="text/javascript">
	<!--		
		ajax( null, 'customer_statistics_section', 'innerHTML', 'Customer::showHistoryAndStatistics', 'customerID=<?php echo $this->id ?>&startDate=<?php echo $startDateStr ?>&endDate=<?php echo $endDateStr ?>' );
	// -->
	</script>
<?php
			}
		}
		else				// customer ID is not existing, redirect to home page
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
	
	
	// view customer history and statistics, ajax function
	public static function showHistoryAndStatistics()
	{
		// check for required parameters
		if ( !isset( $_POST['customerID'] ) || !isset( $_POST['startDate'] ) || !isset( $_POST['endDate'] ) ) {
			return;
		}
		
		
		// get date range
		$startDate 		= new DateTime( $_POST['startDate'] );
		$startDateParam = $startDate->format( 'Y-m-d' );
	
		$endDate 		= new DateTime( $_POST['endDate'] );
		$endDateParam 	= $endDate->format( 'Y-m-d' );
		
		
		// check date for validity
		if ( $startDate > $endDate ) {
			echo '<p><span class="bad">Error:</span> The start date is greater than end date. Please select a different set of dates.</p>';
			return;
		}
		
		
		$database = new Database();
		
		
		
		?>
		<section>
			<div>
            	<?php
				$sqlQuery   = "SELECT COUNT(id) AS count " .
					 		  "FROM `order` " .
					 		  "WHERE customer_id = " . $_POST['customerID'] . " " .
							  "AND (order_date BETWEEN '" . $startDateParam . " 00:00:00' " . "AND '" . $endDateParam . " 23:59:59')";
				$resultSet  = $database->query( $sqlQuery );
				$order      = $database->getResultRow( $resultSet );
				$orderCount = $order['count'];
				?>
                <span class="record_label">Number of placed orders:</span>
                <span class="record_data"><?php echo numberFormat( $orderCount, "int" ); ?></span>
            </div>
			<div>
            	<?php
				$sqlQuery  		  = "SELECT COUNT(id) AS active_orders_count " .
					 		  	    "FROM v_active_orders " .
					 			    "WHERE customer_id = " . $_POST['customerID'] . " " .
									"AND (order_date BETWEEN '" . $startDateParam . " 00:00:00' " . "AND '" . $endDateParam . " 23:59:59')";
				$resultSet 		  = $database->query( $sqlQuery );
				$order     		  = $database->getResultRow( $resultSet );
				?>
                <span class="record_label">Pending orders:</span>
                <span class="record_data"><?php echo numberFormat( $order['active_orders_count'], "int" ); ?></span>
            </div>
			<div>
            	<?php
				$sqlQuery   = "SELECT COUNT(id) AS cleared_orders_count " .
					 		  "FROM `order` " .
					 		  "WHERE customer_id = " . $_POST['customerID'] . " " .
							  "AND cleared_date IS NOT NULL " .
							  "AND (order_date BETWEEN '" . $startDateParam . " 00:00:00' " . "AND '" . $endDateParam . " 23:59:59')";
				$resultSet  = $database->query( $sqlQuery );
				$order      = $database->getResultRow( $resultSet );
				?>
                <span class="record_label">Cleared orders:</span>
                <span class="record_data"><?php echo numberFormat( $order['cleared_orders_count'], "int" ); ?></span>
            </div>
			<div>
            	<?php
				$sqlQuery   = "SELECT COUNT(id) AS canceled_orders_count " .
					 		  "FROM `order` " .
					 		  "WHERE customer_id = " . $_POST['customerID'] . " " .
							  "AND canceled_date IS NOT NULL " .
							  "AND (order_date BETWEEN '" . $startDateParam . " 00:00:00' " . "AND '" . $endDateParam . " 23:59:59')";
				$resultSet  = $database->query( $sqlQuery );
				$order      = $database->getResultRow( $resultSet );
				?>
                <span class="record_label">Canceled orders:</span>
                <span class="record_data"><?php echo numberFormat( $order['canceled_orders_count'], "int" ); ?></span>
            </div>
		</section>
        
        <section>
			<div>
            	<?php
				$sqlQuery  = "SELECT SUM(amount) AS total_amount_paid " .
							 "FROM order_payment " .
							 "INNER JOIN `order` ON order_payment.order_id = `order`.id " .
							 "WHERE `order`.customer_id = " . $_POST['customerID'] . " " .
							 "AND (payment_date BETWEEN '" . $startDateParam . " 00:00:00' " . "AND '" . $endDateParam . " 23:59:59')";
				$resultSet = $database->query( $sqlQuery );
				$order	   = $database->getResultRow( $resultSet );
				?>
                <span class="record_label">Total payments:</span>
                <span class="record_data"><?php echo numberFormat( $order['total_amount_paid'], "currency" ); ?></span>
            </div>
			<div>
            	<?php
				$sqlQuery  = "SELECT SUM(amount) AS total_amount_paid " .
							 "FROM order_payment " .
							 "INNER JOIN `order` ON order_payment.order_id = `order`.id " .
							 "WHERE `order`.customer_id = " . $_POST['customerID'] . " "  .
							 "AND (payment_date BETWEEN '" . $startDateParam . " 00:00:00' " . "AND '" . $endDateParam . " 23:59:59') " .
							 "AND clearing_actual_date IS NOT NULL";
				$resultSet = $database->query( $sqlQuery );
				$order	   = $database->getResultRow( $resultSet );
				?>
                <span class="record_label">Cleared payments:</span>
                <span class="record_data"><?php echo numberFormat( $order['total_amount_paid'], "currency" ); ?></span>
            </div>
            <div>
            	<?php
				$sqlQuery  = "SELECT SUM(amount) AS total_amount_paid " .
							 "FROM order_payment " .
							 "INNER JOIN `order` ON order_payment.order_id = `order`.id " .
							 "WHERE `order`.customer_id = " . $_POST['customerID'] . " " . 
							 "AND (payment_date BETWEEN '" . $startDateParam . " 00:00:00' " . "AND '" . $endDateParam . " 23:59:59') " .
							 "AND clearing_actual_date IS NULL";
				$resultSet = $database->query( $sqlQuery );
				$order	   = $database->getResultRow( $resultSet );
				?>
                <span class="record_label">Unclear payments:</span>
                <span class="record_data"><?php echo numberFormat( $order['total_amount_paid'], "currency" ); ?></span>
            </div>
        </section>
		<?php
	}



	// tasks for customer list
	public static function showListTasks()
	{
		// get parameters
		if ( !isset( $_GET['criteria'] ) )	{
			$criteria = "all-customers";
		} else {
			$criteria = $_GET['criteria'];
		}
		
?>		<div id="tasks">
			<ul>
				<li id="task_add_customer"><a href="add_customer.php"><img src="images/task_buttons/add.png" />Add Customer</a></li>
                <li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php

					// display confirmation to unclear order
					$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br />' .
									 'This may take a few minutes. The system might not respond to other users while processing your request.<br /><br /><br />';

					// Yes and No buttons
					$dialogMessage = $dialogMessage . '<div id="dialog_buttons">' .
													  '<input type="button" value="Yes" onclick="exportToExcelConfirm(' .
													  '\\\'data=customer_list&criteria=' . $criteria . '\\\')" />' .
													  '<input type="button" value="No" onclick="hideDialog()" />' .
													  '</div>';

					$dialogMessage = htmlentities( $dialogMessage );

					echo $dialogMessage;

				?>','prompt')"><img src="images/task_buttons/export_to_excel.png" />Export to Excel...</a></li>
			</ul>
		</div>
	</div>
<?php
	}



	// view customer list, ajax function
	public static function showList()
	{
		self::$database = new Database;
		
		
		// get parameters
		if ( !isset( $_POST['criteria'] ) )	{
			$criteria = "all-customers";
		} else {
			$criteria = $_POST['criteria'];
		}
		
		if ( !isset( $_POST['sortColumn'] ) ) {
			$sortColumn = "name";
		} else {
			$sortColumn = $_POST['sortColumn'];
		}

		if ( !isset( $_POST['sortMethod'] ) ) {
			$sortMethod = "ASC";
		} else {
			$sortMethod = $_POST['sortMethod'];
		}
		
		if ( !isset( $_POST['page'] ) || !isset( $_POST['itemsPerPage'] ) ) {
			$page = 1;
			$itemsPerPage = ITEMS_PER_PAGE;
		} else {
			$page = $_POST['page'];
			$itemsPerPage = $_POST['itemsPerPage'];
		}
		
		if ( !isset( $_POST['filterName'] ) ) {
			$filterName = null;
		} else {
			$filterName = $_POST['filterName'];
		}
		
		if ( !isset( $_POST['filterValue'] ) ) {
			$filterValue = null;
		} else {
			$filterValue = $_POST['filterValue'];
		}
		
		$offset = ( $page * $itemsPerPage ) - $itemsPerPage;
		
		
		// first letter filter
		if ( $filterName == 'alpha' && $filterValue != null ) {
			if ( $filterValue == '#' ) {
				$condition = "WHERE name NOT RLIKE '^[A-Z]' ";
			} else {
				$condition = "WHERE name LIKE '" . $filterValue . "%' ";
			}
		} else {
			$condition = "";
		}
		
		
		// limit viewable customers by branch
		if ( $condition == "" ) {
			$condition = "WHERE ";
		} else {
			$condition = $condition . " AND ";
		}
		$condition = $condition . User::getQueryForBranch(self::$database);
		
		
		// set condition
		switch ( $criteria ) {
			case "all-customers" :
				$sqlQuery = "SELECT COUNT(*) AS count FROM customer " . $condition;
				break;
			
			default:
				if ( $condition == "" ) {
					$condition = "WHERE ";
				} else {
					$condition = $condition . " AND ";
				}
				
				switch ( $criteria ) {
					case "with-receivable" :
						$condition = $condition . "(v_customer_payment_summary.amount_receivable > 0 OR v_customer_payment_summary.pdc_receivable > 0) ";
						break;
					
					case "without-receivable" :
						$condition = $condition . "(v_customer_payment_summary.amount_receivable = 0 AND v_customer_payment_summary.pdc_receivable = 0) ";
						break;
					
					case "with-rebate" :
						$condition = $condition . "(v_customer_payment_summary.rebate_payable > 0 OR v_customer_payment_summary.pdc_rebate_payable > 0) ";
						break;
					
					case "without-rebate" :
						$condition = $condition . "(v_customer_payment_summary.rebate_payable = 0 AND v_customer_payment_summary.pdc_rebate_payable = 0) ";
						break;
				}
				
				// count results prior to main query
				$sqlQuery = "SELECT COUNT(*) AS count " .
							"FROM customer " .
							"LEFT JOIN v_customer_payment_summary ON customer.id = v_customer_payment_summary.customer_id " .
							$condition;
		}
		
		
		$resultSet = self::$database->query( $sqlQuery );
		$resultCount = self::$database->getResultRow( $resultSet );
		$resultCount = $resultCount['count'];
		
		
		// construct main query
		$sqlQuery = "SELECT customer.*, " .
					"(customer.credit_limit - (" .
						"v_customer_payment_summary.amount_receivable " .
						"+ v_customer_payment_summary.pdc_receivable" .
					")) AS credit_remaining, " .
					"COUNT(v_active_orders.id) AS order_count, " .
					"v_customer_payment_summary.amount_receivable AS amount_receivable, " .
					"v_customer_payment_summary.pdc_receivable AS pdc_receivable, " .
					"v_customer_payment_summary.amount_receivable + v_customer_payment_summary.pdc_receivable AS total_receivable, " .
					"v_customer_payment_summary.rebate_payable AS rebate_payable, " .
					"v_customer_payment_summary.pdc_rebate_payable AS pdc_rebate_payable, " .
					"v_customer_payment_summary.rebate_payable + v_customer_payment_summary.pdc_rebate_payable AS total_rebate " .
					"FROM customer " .
					"LEFT JOIN v_active_orders ON customer.id = v_active_orders.customer_id " .
					"LEFT JOIN v_customer_payment_summary ON customer.id = v_customer_payment_summary.customer_id " .
					$condition .
					" GROUP BY customer.id " .
					"ORDER BY " . $sortColumn . " " . $sortMethod . " " .
					"LIMIT " . $offset . "," . $itemsPerPage;
		
		$resultSet = self::$database->query( $sqlQuery );

		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			$columns = array(
				'name' => 'Customer',
				'credit_limit' => 'Credit Limit',
				'credit_remaining' => 'Remaining Credits',
				'credit_terms' => 'Credit Terms',
				'order_count' => 'Pending Orders',
				'amount_receivable' => 'Amount Receivable',
				'pdc_receivable' => 'PDC Receivable',
				'total_receivable' => 'Total Receivable',
				'rebate_payable' => 'Rebate'
			);


			self::showListHeader( $columns, 'customer_list_section', 'Customer::showList', $criteria, $sortColumn, $sortMethod, $filterName, $filterValue );


			// display content
			while ( $customer = self::$database->getResultRow( $resultSet ) )
			{
				echo '<tr>';
				
				
				// customer
				echo '<td>' .
					 '<span class="long_text_clip">' .
					 '<a href="view_customer_details.php?id=' . $customer['id'] . '&src=' . $criteria . '" title="' .
					 capitalizeWords( Filter::output( $customer['name'] ) ) . '">' .
					 capitalizeWords( Filter::output( $customer['name'] ) ) .
					 '</a>' .
					 '</span>' .
					 '</td>';
				
				
				// credit limit
				echo '<td class="number">';
				if ( $customer['credit_limit'] <= 0 ) {
					echo '<span class="bad">' . numberFormat( $customer['credit_limit'], "float" ) . '</span>';
				} else {
	            	echo '<span>' . numberFormat( $customer['credit_limit'], "float" ) . '</span>';
				}
				echo '</td>';
				
				
				// remaining credit
				echo '<td class="number">';
				if ( $customer['credit_remaining'] <= 0 ) {
					echo '<span class="bad">' . numberFormat( Filter::output( $customer['credit_remaining'] ), "float" ) . '</span>';
				} elseif ( $customer['credit_remaining'] >= $customer['credit_limit'] ) {
					echo '<span class="good">' . numberFormat( Filter::output( $customer['credit_remaining'] ), "float" ) . '</span>';
				} else {
	            	echo '<span>' . numberFormat( Filter::output( $customer['credit_remaining'] ), "float" ) . '</span>';
				}
				echo '</td>';
				
				
				// credit terms
				echo '<td>' . $customer['credit_terms'] . '</td>';
				
				
				// pending orders
				echo '<td class="number">';
				if ( $customer['order_count'] == 0 ) {
					echo '<span class="bad">' . numberFormat( $customer['order_count'], "int" ) . '</span>';
				} else {
	            	echo '<span>' . numberFormat( $customer['order_count'], "int" ) . '</span>';
				}
				echo '</td>';
				
				
				// amount receivable
				echo '<td class="number">';
				if ( $customer['amount_receivable'] == 0 ) {
					echo '<span class="good">' . numberFormat( $customer['amount_receivable'], "float" ) . '</span>';
				} else {
	            	echo '<span>' . numberFormat( $customer['amount_receivable'], "float" ) . '</span>';
				}
				echo '</td>';
				
				
				// pdc receivable
				echo '<td class="number">';
				if ( $customer['pdc_receivable'] == 0 ) {
					echo '<span class="good">' . numberFormat( $customer['pdc_receivable'], "float" ) . '</span>';
				} else {
		            echo '<span>' . numberFormat( $customer['pdc_receivable'], "float" ) . '</span>';
				}
				echo '</td>';
				
				
				// total receivable
				echo '<td class="number">';
				if ( $customer['total_receivable'] == 0 ) {
					echo '<span class="good">' . numberFormat( $customer['total_receivable'], "float" ) . '</span>';
				} else {
		            echo '<span>' . numberFormat( $customer['total_receivable'], "float" ) . '</span>';
				}
				echo '</td>';
				
				
				// rebate
				echo '<td class="number">';
				if ( $customer['rebate_payable'] == 0 ) {
					echo '<span class="good">' . numberFormat( $customer['rebate_payable'], "float" ) . '</span>';
				} else {
					echo '<span>' . numberFormat( $customer['rebate_payable'], "float" ) . '</span>';
				}
				if ( $customer['pdc_rebate_payable'] > 0 ) {
					echo '<img src="images/rebate.png" class="status_icon" title="Rebate to clear: ' . numberFormat( $customer['pdc_rebate_payable'], "currency", 3, '.', ',', true ) . '" />';
				}
				echo '</td>';
				
				
				echo "</tr>";
			}

			echo "</tbody>";
			echo "</table>";
			

			self::showPagination( $page, $itemsPerPage, $resultCount, 'customer_list_section', 'Customer::showList', $criteria, $sortColumn, $sortMethod, $filterName, $filterValue );
		}
		else
		{
			echo "<div>No customers found.</div>";
		}
	}
	
	
	// export order list to Excel file, ajax function
	public static function exportListToExcel( $username, $paramArray )
	{
		$fileTimeStampExtension = date( EXCEL_FILE_TIMESTAMP_FORMAT );
		$headingTimeStamp = dateFormatOutput( $fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT );
		
		require_once( "classes/Filter.php" );
		
		self::$database = new Database();
		
		
		// get parameters
		switch( $paramArray['criteria'] ) {
			case "with-receivable" :
				$fileName = 'Customers with Receivables';
				$sheetTitle = 'Customers with Receivables';
				$condition = "WHERE (v_customer_payment_summary.amount_receivable > 0 OR v_customer_payment_summary.pdc_receivable > 0)";
				break;
			case "without-receivable" :
				$fileName = 'Customers without Receivables';
				$sheetTitle = 'Customers without Receivables';
				$condition = "WHERE (v_customer_payment_summary.amount_receivable = 0 AND v_customer_payment_summary.pdc_receivable = 0)";
				break;
			case "with-rebate" :
				$fileName = 'Customers with Rebate';
				$sheetTitle = 'Customers with Rebate';
				$condition = "WHERE (v_customer_payment_summary.rebate_payable > 0 OR v_customer_payment_summary.pdc_rebate_payable > 0)";
				break;
			case "without-rebate" :
				$fileName = 'Customers without Rebate';
				$sheetTitle = 'Customers without Rebate';
				$condition = "WHERE (v_customer_payment_summary.rebate_payable = 0 AND v_customer_payment_summary.pdc_rebate_payable = 0)";
				break;
			default:
				$fileName = 'All Customers';
				$sheetTitle = 'All Customers';
				$condition = "";
				break;
		}
		
		if ($condition == "") {
			$condition = "WHERE " . User::getQueryForBranch(self::$database);
		} else {
			$condition = $condition . " AND " . User::getQueryForBranch(self::$database);
		}
		
		
		
		// construct main query
		$sqlQuery = "SELECT customer.*, " .
					"(customer.credit_limit - (" .
						"v_customer_payment_summary.amount_receivable " .
						"+ v_customer_payment_summary.pdc_receivable" .
					")) AS credit_remaining, " .
					"COUNT(v_active_orders.id) AS order_count, " .
					"v_customer_payment_summary.amount_receivable AS amount_receivable, " .
					"v_customer_payment_summary.pdc_receivable AS pdc_receivable, " .
					"v_customer_payment_summary.amount_receivable + v_customer_payment_summary.pdc_receivable AS total_receivable, " .
					"v_customer_payment_summary.rebate_payable AS rebate_payable, " .
					"v_customer_payment_summary.pdc_rebate_payable AS pdc_rebate_payable, " .
					"v_customer_payment_summary.rebate_payable + v_customer_payment_summary.pdc_rebate_payable AS total_rebate " .
					"FROM customer " .
					"LEFT JOIN v_active_orders ON customer.id = v_active_orders.customer_id " .
					"LEFT JOIN v_customer_payment_summary ON customer.id = v_customer_payment_summary.customer_id " .
					$condition .
					" GROUP BY customer.id ORDER BY customer.name ASC";
		
		$resultSet = self::$database->query( $sqlQuery );

		
		// import PHPExcel library
		require_once( 'libraries/phpexcel/PHPExcel.php' );
		
		// instantiate formatting variables
		$backgroundColor = new PHPExcel_Style_Color();
		$fontColor 		 = new PHPExcel_Style_Color();
		
		// color-specific variables
		$fontColorRed	 	= new PHPExcel_Style_Color();
		$fontColorRed->setRGB( 'FF0000' );
		$fontColorDarkRed	= new PHPExcel_Style_Color();
		$fontColorDarkRed->setRGB( 'CC0000' );
		$fontColorGreen	 	= new PHPExcel_Style_Color();
		$fontColorGreen->setRGB( '00CC00' );
		$fontColorGray	 	= new PHPExcel_Style_Color();
		$fontColorGray->setRGB( '999999' );

		$altRowColor  	    = new PHPExcel_Style_Color();
		$altRowColor->setRGB( EXCEL_ALT_ROW_BACKGROUND_COLOR );
		
		// set value binder
		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );
		
		// create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// set file properties
		$objPHPExcel->getProperties()
					->setCreator( $username )
					->setLastModifiedBy( $username )
					->setTitle( $sheetTitle . ' as of ' . $headingTimeStamp )
					->setSubject( EXCEL_FILE_SUBJECT )
					->setDescription( EXCEL_FILE_DESCRIPTION )
					->setKeywords( EXCEL_FILE_KEYWORDS )
					->setCategory( EXCEL_FILE_CATEGORY );

		// create a first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		
		// rename present sheet
		$activeSheet->setTitle( 'Customer List' );
		
		// set default font
		$activeSheet->getDefaultStyle()->getFont()->setName( EXCEL_DEFAULT_FONT_NAME )
												  ->setSize( EXCEL_DEFAULT_FONT_SIZE );
		
		// write sheet headers
		$activeSheet->setCellValue( 'A1', CLIENT );
		$activeSheet->setCellValue( 'A2', $sheetTitle );
		$activeSheet->setCellValue( 'A3', 'As of ' . $headingTimeStamp );

		// define max column
		$MAX_COLUMN = 'Q';
		$FIELD_HEADER_ROW = '5';
		
		// format sheet headers
		$backgroundColor->setRGB( EXCEL_HEADER_BACKGROUND_COLOR );
		$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.'4' )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID );
		$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.'4' )->getFill()->setStartColor( $backgroundColor );
		$activeSheet->getStyle( 'A1:A2' )->getFont()->setBold( true );
		$activeSheet->getStyle( 'A1:A3' )->getFont()->setName( EXCEL_HEADER_FONT_NAME );
		$activeSheet->getStyle( 'A1' )->getFont()->setColor( $fontColorRed );
		$activeSheet->getStyle( 'A1' )->getFont()->setSize( EXCEL_HEADER1_FONT_SIZE );
		$activeSheet->getStyle( 'A2' )->getFont()->setSize( EXCEL_HEADER2_FONT_SIZE );
		$activeSheet->getStyle( 'A3' )->getFont()->setSize( EXCEL_HEADER2_FONT_SIZE );
		
		// write column headers
		$activeSheet->setCellValue( 'A'.$FIELD_HEADER_ROW, 'Customer' )
					->setCellValue( 'B'.$FIELD_HEADER_ROW, 'Contact Person' )
					->setCellValue( 'C'.$FIELD_HEADER_ROW, 'Delivery Address' )
					->setCellValue( 'D'.$FIELD_HEADER_ROW, 'Telephone' )
					->setCellValue( 'E'.$FIELD_HEADER_ROW, 'Mobile' )
					->setCellValue( 'F'.$FIELD_HEADER_ROW, 'Fax' )
					->setCellValue( 'G'.$FIELD_HEADER_ROW, 'E-mail' )
					->setCellValue( 'H'.$FIELD_HEADER_ROW, 'Credit Limit (' . CURRENCY . ')' )
					->setCellValue( 'I'.$FIELD_HEADER_ROW, 'Remaining Credits (' . CURRENCY . ')' )
					->setCellValue( 'J'.$FIELD_HEADER_ROW, 'Credit Terms' )
					->setCellValue( 'K'.$FIELD_HEADER_ROW, 'Pending Orders' )
					->setCellValue( 'L'.$FIELD_HEADER_ROW, 'Amount Receivable (' . CURRENCY . ')' )
					->setCellValue( 'M'.$FIELD_HEADER_ROW, 'PDC Receivable (' . CURRENCY . ')' )
					->setCellValue( 'N'.$FIELD_HEADER_ROW, 'Total Receivable (' . CURRENCY . ')' )
					->setCellValue( 'O'.$FIELD_HEADER_ROW, 'Rebate Payable (' . CURRENCY . ')' )
					->setCellValue( 'P'.$FIELD_HEADER_ROW, 'PDC Rebate (' . CURRENCY . ')' )
					->setCellValue( 'Q'.$FIELD_HEADER_ROW, 'Total Rebate (' . CURRENCY . ')' );
		
		// set column widths
		$activeSheet->getColumnDimension( 'A' )->setWidth( 50 );
		$activeSheet->getColumnDimension( 'B' )->setWidth( 25 );
		$activeSheet->getColumnDimension( 'C' )->setWidth( 80 );
		$activeSheet->getColumnDimension( 'D' )->setWidth( 20 );
		$activeSheet->getColumnDimension( 'E' )->setWidth( 20 );
		$activeSheet->getColumnDimension( 'F' )->setWidth( 20 );
		$activeSheet->getColumnDimension( 'G' )->setWidth( 25 );
		$activeSheet->getColumnDimension( 'H' )->setWidth( 18 );
		$activeSheet->getColumnDimension( 'I' )->setWidth( 23 );
		$activeSheet->getColumnDimension( 'J' )->setWidth( 15 );
		$activeSheet->getColumnDimension( 'K' )->setWidth( 17 );
		$activeSheet->getColumnDimension( 'L' )->setWidth( 24 );
		$activeSheet->getColumnDimension( 'M' )->setWidth( 20 );
		$activeSheet->getColumnDimension( 'N' )->setWidth( 22 );
		$activeSheet->getColumnDimension( 'O' )->setWidth( 20 );
		$activeSheet->getColumnDimension( 'P' )->setWidth( 20 );
		$activeSheet->getColumnDimension( 'Q' )->setWidth( 20 );
		
		// format column headers
		$fontColor->setRGB( EXCEL_COLUMN_HEADER_FONT_COLOR );
		$backgroundColor->setRGB( EXCEL_COLUMN_HEADER_BACKGROUND_COLOR );
		$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID );
		$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFill()->setStartColor( $backgroundColor );
		$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFont()->setColor( $fontColor );
		$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFont()->setBold( true );
		$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getAlignment()->setWrapText( true );
		
		// set autofilter
		$activeSheet->setAutoFilter( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW );
		
		// freeze pane
		$activeSheet->freezePane( 'B'.($FIELD_HEADER_ROW+1) );
		
		// initialize counters
		$rowPtr = $FIELD_HEADER_ROW+1;
		$itemCount = 0;
		
		// write data
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			while ( $customer = self::$database->getResultRow( $resultSet ) ) {
				// customer details
				$activeSheet->setCellValue( 'A' . $rowPtr, html_entity_decode( capitalizeWords( Filter::reinput( $customer['name'] ) ) ) )
							->setCellValue( 'B' . $rowPtr, html_entity_decode( capitalizeWords( Filter::reinput( $customer['contact_person'] ) ) ) )
							->setCellValue( 'C' . $rowPtr, html_entity_decode( capitalizeWords( Filter::reinput( $customer['address'] ) ) ) )
							->setCellValue( 'D' . $rowPtr, stripslashes( $customer['telephone'] ) )
							->setCellValue( 'E' . $rowPtr, stripslashes( $customer['mobile'] ) )
							->setCellValue( 'F' . $rowPtr, stripslashes( $customer['fax'] ) )
							->setCellValue( 'G' . $rowPtr, stripslashes( $customer['email'] ) )
							->setCellValue( 'H' . $rowPtr, $customer['credit_limit'] );

				// credit limit
				if ( $customer['credit_limit'] <= 0 ) {
					$activeSheet->getStyle( 'H' . $rowPtr )->getFont()->setColor( $fontColorDarkRed );
					$activeSheet->getComment( 'H' . $rowPtr )->getText()
						->createTextRun( 'No available credits for the customer' )
						->getFont()->setSize( EXCEL_COMMENT_FONT_SIZE );
				}

				// remaining credits
				$activeSheet->setCellValue( 'I' . $rowPtr, $customer['credit_remaining'] );
				if ( $customer['credit_remaining'] >= $customer['credit_limit'] ) {
					$activeSheet->getStyle( 'I' . $rowPtr )->getFont()->setColor( $fontColorGreen );
					$activeSheet->getComment( 'I' . $rowPtr )->getText()
						->createTextRun( 'Remaining credit is equal to credit limit' )
						->getFont()->setSize( EXCEL_COMMENT_FONT_SIZE );
				} elseif ( $customer['credit_remaining'] <= 0 ) {
					$activeSheet->getStyle( 'I' . $rowPtr )->getFont()->setColor( $fontColorDarkRed );
					$activeSheet->getComment( 'I' . $rowPtr )->getText()
						->createTextRun( 'No more remaining credits' )
						->getFont()->setSize( EXCEL_COMMENT_FONT_SIZE );
				}
				
				// credit terms
				$activeSheet->getCell( 'J' . $rowPtr )->setValueExplicit( $customer['credit_terms'], PHPExcel_Cell_DataType::TYPE_STRING );
				
				// pending orders
				if ( $customer['order_count'] > 0 ) {
					$activeSheet->setCellValue( 'K' . $rowPtr, $customer['order_count'] );
				}
				
				// amount receivable
				if ( $customer['amount_receivable'] > 0 ) {
					$activeSheet->setCellValue( 'L' . $rowPtr, $customer['amount_receivable'] );
				}
				
				// pdc receivable
				if ( $customer['pdc_receivable'] > 0 ) {
					$activeSheet->setCellValue( 'M' . $rowPtr, $customer['pdc_receivable'] );
				}
				
				// total receivable
				$activeSheet->setCellValue( 'N' . $rowPtr, '=L' . $rowPtr . '+M' . $rowPtr );
				if ( $activeSheet->getCell( 'N' . $rowPtr )->getCalculatedValue() == 0.000 ) {
					$activeSheet->getStyle( 'N' . $rowPtr )->getFont()->setColor( $fontColorGreen );
				}
				
				// rebate payable
				if ( $customer['rebate_payable'] > 0 ) {
					$activeSheet->setCellValue( 'O' . $rowPtr, $customer['rebate_payable'] );
				}
				
				// pdc rebate
				if ( $customer['pdc_rebate_payable'] > 0 ) {
					$activeSheet->setCellValue( 'P' . $rowPtr, $customer['pdc_rebate_payable'] );
				}
				
				// total rebate
				$activeSheet->setCellValue( 'Q' . $rowPtr, '=O' . $rowPtr . '+P' . $rowPtr );
				if ( $activeSheet->getCell( 'Q' . $rowPtr )->getCalculatedValue() == 0.000 ) {
					$activeSheet->getStyle( 'Q' . $rowPtr )->getFont()->setColor( $fontColorGreen );
				}

				// set alternating row color
				if ( EXCEL_ALT_ROW > 0 && $rowPtr % EXCEL_ALT_ROW == 0 ) {
					$activeSheet->getStyle( 'A'.$rowPtr.':'.$MAX_COLUMN.$rowPtr )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID );
					$activeSheet->getStyle( 'A'.$rowPtr.':'.$MAX_COLUMN.$rowPtr )->getFill()->setStartColor( $altRowColor );
				} else {
					$activeSheet->getStyle( 'A'.$rowPtr.':'.$MAX_COLUMN.$rowPtr )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_NONE );
				}
				
				$rowPtr++;
				$itemCount++;
			}
			
			$rowPtr--;
		}
		
		
		// post formatting
		$activeSheet->getStyle( 'A6:G' . $rowPtr )->getAlignment()->setWrapText( true );						// wrap columns
		$activeSheet->getStyle( 'H6:I' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );	// format Credit Limit, Credit Remaining
		$activeSheet->getStyle( 'J6:J' . $rowPtr )->getAlignment()->setWrapText( true );						// wrap Credit Terms
		$activeSheet->getStyle( 'K6:K' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );		// format Pending Orders
		$activeSheet->getStyle( 'L6:Q' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );	// format amounts
		$activeSheet->getStyle( 'N6:N' . $rowPtr )->getFont()->setBold( true );									// set Total Receivable to bold
		$activeSheet->getStyle( 'Q6:Q' . $rowPtr )->getFont()->setBold( true );									// set Total Rebate to bold

		// set columns to left aligned
		$activeSheet->getStyle( 'A6:G' . $rowPtr )->getAlignment()->setHorizontal( PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
		
		// write totals
		$totalsRow = $rowPtr + 3;
		$activeSheet->setCellValue( 'A' . $totalsRow, 'Total Number of Customers: ' . numberFormat( $itemCount, "int" ) )
					->setCellValue( 'J' . $totalsRow, 'Totals:' )
					->setCellValue( 'K' . $totalsRow, '=SUM(K6:K' . $rowPtr . ')' )
					->setCellValue( 'L' . $totalsRow, '=SUM(L6:L' . $rowPtr . ')' )
					->setCellValue( 'M' . $totalsRow, '=SUM(M6:M' . $rowPtr . ')' )
					->setCellValue( 'N' . $totalsRow, '=SUM(N6:N' . $rowPtr . ')' )
					->setCellValue( 'O' . $totalsRow, '=SUM(O6:O' . $rowPtr . ')' )
					->setCellValue( 'P' . $totalsRow, '=SUM(P6:P' . $rowPtr . ')' )
					->setCellValue( 'Q' . $totalsRow, '=SUM(Q6:Q' . $rowPtr . ')' );
					
		// format totals
		$styleArray = array(
			'borders' => array(
				'top' => array( 'style' => PHPExcel_Style_Border::BORDER_THICK )
		  	)
		);
		$activeSheet->getStyle( 'A' . $totalsRow . ':Q' . $totalsRow )->applyFromArray( $styleArray );
		$activeSheet->getStyle( 'K' . $totalsRow )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );
		$activeSheet->getStyle( 'L' . $totalsRow . ':Q' . $totalsRow )->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );
		$activeSheet->getStyle( 'A' . $totalsRow . ':Q' . $totalsRow )->getFont()->setBold( true );
		$activeSheet->getStyle( 'A' . $totalsRow . ':Q' . $totalsRow )->getFont()->setColor( $fontColorRed );

		// set vertical alignment to top
		$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.$totalsRow )->getAlignment()->setVertical( PHPExcel_Style_Alignment::VERTICAL_TOP );

		// set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex( 0 );

		// redirect output to a client's web browser (Excel2007)
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="'.CLIENT.' - '.$fileName.' - as of '.$fileTimeStampExtension.'.xlsx"' );
		header( 'Cache-Control: max-age=0' );
		
		$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( 'php://output' );
	}



	// show customer list for auto-suggest, ajax function
	public static function showAutoSuggest()
	{
		// check for required parameters
		if ( isset( $_POST['searchString'] ) )
			self::showAutoSuggestResult( "customer", "name", $_POST['searchString'] );

		return;
	}



	// show customer list for auto-suggest, ajax function
	public static function autoFill()
	{
		// check for required parameters
		if ( !isset( $_POST['customerName'] ) ) {
			return;
		}

		$customer = self::getAutoFillData( "customer", "name", $_POST['customerName'] );

		if ( $customer == null ) {
			$customer['id'] = 0;
		} else {
			// determine credits remaining
			if ( !isset( self::$database ) )
				self::$database = new Database();
			
			$sqlQuery = "SELECT (customer.credit_limit - (" .
							"v_customer_payment_summary.amount_receivable " .
							"+ v_customer_payment_summary.pdc_receivable" .
						")) AS credit_remaining " .
						"FROM customer " .
						"LEFT JOIN v_customer_payment_summary ON customer.id = v_customer_payment_summary.customer_id " .
						"WHERE customer.id = " . $customer['id'];
			$resultSet = self::$database->query( $sqlQuery );
			$credit = self::$database->getResultRow( $resultSet );
			$customer['credit_remaining'] = $credit['credit_remaining'];

			$customer['contact_person'] = html_entity_decode( capitalizeWords( Filter::output( $customer['contact_person'] ) ) );
			$customer['address'] = html_entity_decode( capitalizeWords( Filter::output( $customer['address'] ) ) );
			$customer['email'] = strtolower( $customer['email'] );
		}

		echo json_encode( $customer );
	}
	
	
	// get branch details
	private static function getBranches() {
		$resultSet = self::$database->query( "SELECT id, name FROM branch" );
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			while ( $branchInfo = self::$database->getResultRow( $resultSet ) ) {
				$branch[$branchInfo['id']] = $branchInfo['name']; 
			}
		} else {
			$branch = null;
		}
		return $branch;
	}
}
?>

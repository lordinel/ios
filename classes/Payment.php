<?php
// note: this class requires scripts/payment.js




// class definition for payment and payment handling
class Payment
{
	const MAX_INSTALLMENT_PERIOD = 12;				// maximum number of installment parts
	const VISIBLE_INSTALLMENT_PERIOD = 1;			// number of installment period initially visible

	private static $database;
	private $paymentTerm;
	private $interest;



	// display payment form field set
	public static function showInputFieldSet( $id = null, Database &$database = null, $class = null, $paymentTerm = "full", $interest = 0.0 )
	{
?>		<fieldset><legend>Payment Info</legend>
			<section>
				<div>
					<label for="payment_due">SI/DR Amount:</label>
					<input type="text" name="sidr_payment_due" id="sidr_payment_due" class="number output_field" value="0.000" disabled="disabled" />
				</div>
				<div>
					<label for="payment_due">OFC Net Amount:</label>
					<input type="text" name="payment_due" id="payment_due" class="number output_field" value="0.000" disabled="disabled" />
				</div>
				<div>
					<label for="payment_term">Payment Terms:</label>
					<select name="payment_term" id="payment_term" class="form_input_select">
						<option value="full"<?php echo ( $paymentTerm == "full" ? " selected=\"selected\"" : "" ) ?>>Full</option>
						<option value="installment"<?php echo ( $paymentTerm == "installment" ? " selected=\"selected\"" : "" ) ?>>Installment</option>
					</select>
				</div>
			</section>

			<section id="interest_section" style="display: none">
				<div>
					<label for="interest">Interest:</label>
					<input type="text" name="interest" id="interest" class="number" maxlength="255" value="<?php echo ( $id != null ? numberFormat( Filter::reinput( $interest ), "float", 3, '.', '', true ) : "0.000" ) ?>" />
				</div>
				<div>
					<label for="sidr_amount_plus_interest" class="important_label">SI/DR Amt + Int.:</label>
					<input type="text" name="sidr_amount_plus_interest" id="sidr_amount_plus_interest" class="number output_field" value="0.000" disabled="disabled" />
				</div>
                <div>
					<label for="net_amount_plus_interest" class="important_label">OFC Net Amt + Int.:</label>
					<input type="text" name="net_amount_plus_interest" id="net_amount_plus_interest" class="number output_field" value="0.000" disabled="disabled" />
				</div>
			</section>

			<section id="installment_plan_section" style="display: none">
				<div>
					<label for="installment_part">Installment Plan:</label>
					<table id="payment_schedule_input">
						<tbody>
							<tr>
								<td>
<?php
								if ( $id != null && $paymentTerm == "installment" )
								{
									$installmentAmount = array();
									$installmentDate = array();

									$resultSet = $database->query( "SELECT * FROM " . $class . "_payment_schedule WHERE " . $class . "_id = " . $id );

									$visibleInstallment = $database->getResultCount( $resultSet );

									while( $installmentList = $database->getResultRow( $resultSet ) )
									{
										array_push( $installmentAmount, $installmentList['amount_due'] );
										array_push( $installmentDate, $installmentList['due_date'] );
									}
								}
								else
									$visibleInstallment = self::VISIBLE_INSTALLMENT_PERIOD;


								for ( $i = 1; $i <= self::MAX_INSTALLMENT_PERIOD; $i++ )
								{
?>									<div id="installment_row_<?php echo $i ?>" <?php if ( $i > $visibleInstallment ) echo " style=\"display:none\"" ?>>
										<span class="installment_pay_label">Pay</span>
										<input type="text" name="installment_amount_<?php echo $i ?>" id="installment_amount_<?php echo $i ?>" class="number installment_amount" maxlength="255" value="<?php echo ( $id != null && $paymentTerm == "installment" && $i <= $visibleInstallment ? numberFormat( Filter::reinput( $installmentAmount[$i-1] ), "float", 3, '.', '', true ) : "0.000" ) ?>" disabled="disabled" />
										<span class="installment_on_label">on</span>
										<input type="text" name="installment_date_<?php echo $i ?>" id="installment_date_<?php echo $i ?>" class="installment_date datepicker_no_past_date" size="30" maxlength="30" value="<?php echo ( $id != null && $paymentTerm == "installment" && $i <= $visibleInstallment ? dateFormatOutput( Filter::reinput( $installmentDate[$i-1] ), "F j, Y, D", "Y-m-d" ) : "" ) ?>" disabled="disabled" required="required" />
									</div>
<?php							}
?>

									<div id="add_remove_installment_links" class="multi_row_links">
										<span id="add_installment_row_link"><a href="javascript:void(0)" onclick="order.payment.addInstallmentRow()">[ add ]</a></span>
										<span id="installment_row_link_separator" style="display:none"> | </span>
										<span id="remove_installment_row_link" style="display:none"><a href="javascript:void(0)" onclick="order.payment.removeInstallmentRow(), order.payment.calculateAmount(null)">[ remove ]</a></span>
									</div>

									<script type="text/javascript">
									<!--
										order.payment.setMaxInstallmentPeriod( <?php echo self::MAX_INSTALLMENT_PERIOD ?> );
<?php
										if ( $id != null && $paymentTerm == "installment" )
										{
?>										for ( i = 1; i < <?php echo $visibleInstallment ?>; i++ )
											order.payment.addInstallmentRow();

										$('#installment_remaining').css( 'color', goodInputStyle );
										$('#clear_label').show();
										$('#add_installment_row_link').hide();
										$('#installment_row_link_separator').hide();
<?php
										}
?>
									// -->
									</script>
								</td>
							</tr>
							<tr>
								<td>
									<span id="installment_remaining_label">Remaining: <?php echo CURRENCY ?></span>
									<input type="text" name="installment_remaining" id="installment_remaining" class="number output_field" value="0.000" disabled="disabled" />
									<span id="clear_label" style="display: none">Cleared!</span>
								</td>
							</tr>

<?php
							if ( $id != null && $paymentTerm == "installment" )
							{
?>	                        <script type="text/javascript">
							<!--
								$('#installment_remaining').css( 'color', goodInputStyle );
								$('#clear_label').show();
							// -->
							</script>
<?php						}
?>
						</tbody>
					</table>
				</div>
			</section>
		</fieldset>

<?php
		if ( $id != null )
		{
?>
		<script type="text/javascript">
		<!--
			$('#sidr_payment_due').val( $('#sidr_amount').val() );
			$('#sidr_payment_due').attr( 'defaultValue', $('#sidr_payment_due').val() );
			$('#payment_due').val( $('#net_amount').val() );
			$('#payment_due').attr( 'defaultValue', $('#payment_due').val() );

			var sidrAmountPlusInterest = parseFloat( $('#sidr_amount').val() ) + parseFloat( $('#interest').val() );
			var netAmountPlusInterest = parseFloat( $('#payment_due').val() ) + parseFloat( $('#interest').val() );

			$('#sidr_amount_plus_interest').val( sidrAmountPlusInterest.toFixed( 3 ) );
			$('#sidr_amount_plus_interest').attr( 'defaultValue', $('#sidr_amount_plus_interest').val() );
			$('#net_amount_plus_interest').val( netAmountPlusInterest.toFixed( 3 ) );
			$('#net_amount_plus_interest').attr( 'defaultValue', $('#net_amount_plus_interest').val() );
			order.payment.toggleInstallmentSection();
		// -->
        </script>
<?php
		}
	}


	// save payment schedule for installment
	public function saveSchedule( Database &$database, $class, $transactionID, $paymentTerm )
	{
		if ( $_POST[$class.'_query_mode'] == "edit" )
		{
			// delete previous records
			$database->query( "DELETE FROM " . $class . "_payment_schedule WHERE " . $class . "_id = " . $transactionID );

			// reset autoincrement
			$database->query( "ALTER TABLE " . $class . "_payment_schedule AUTO_INCREMENT = 1" );
		}


		// save payment schedule
		if ( $paymentTerm == "installment" )
		{
			for ( $i = 1; $i <= self::MAX_INSTALLMENT_PERIOD; $i++ )
			{
				if ( isset( $_POST['installment_amount_'.$i] ) )
				{
					$amount = (double) Filter::input( $_POST['installment_amount_'.$i] );

					if ( $amount > 0 )
					{
						// format installment date
						$installmentDate = dateFormatInput( Filter::input( $_POST['installment_date_'.$i] ), "Y-m-d", "F j, Y, D" );

						$sqlQuery = "INSERT INTO " .  $class . "_payment_schedule VALUES (";
						$sqlQuery = $sqlQuery . "NULL,";												// id, auto-generate
						$sqlQuery = $sqlQuery . $transactionID . ",";									// order_id or purchases_id
						$sqlQuery = $sqlQuery . $amount . ",'";											// amount_due
						$sqlQuery = $sqlQuery . $installmentDate . "')";								// due_date

						$database->query( $sqlQuery );
					}
				}
			}
		}
	}



	// display payment schedule for order, ajax function
	public static function showSchedule()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) )
			return;

		$class = $_POST['class'];

		self::$database = new Database();
		

		// get info about payment terms, delivery/pickup date, and amount to pay
		$sqlQuery = "SELECT DATE_FORMAT(" . $class . "_date,'%Y-%m-%d') AS transaction_date, payment_term, delivery_pickup_target_date, ";
		if ( $_POST['class'] == "order" )
			$sqlQuery = $sqlQuery . "amount_receivable AS total_amount, ";
		else
			$sqlQuery = $sqlQuery . "amount_payable AS total_amount, ";
		$sqlQuery = $sqlQuery . "balance, waived_balance, canceled_date, SUM(" . $class ."_payment.amount) AS amount_received, cleared_date FROM `" . $class ."`";
		$sqlQuery = $sqlQuery . " LEFT JOIN " . $class ."_payment ON `" . $class ."`.id = " . $class ."_payment." . $class ."_id";
		$sqlQuery = $sqlQuery . " WHERE id = " . $_POST['transactionID'];
		$sqlQuery = $sqlQuery . " GROUP BY `" . $class ."`.id";

		$resultSet = self::$database->query( $sqlQuery );
		$transaction = self::$database->getResultRow( $resultSet );
		
		
		$transactionDate = new DateTime( $transaction['transaction_date'] );


		if ( $transaction['amount_received'] == NULL )
			$transaction['amount_received'] = 0.000;
		
		
		// check how many payments are uncleared
		$sqlQuery = "SELECT clearing_actual_date FROM " . $_POST['class'] . "_payment WHERE " . $_POST['class'] . "_id = " . $_POST['transactionID'] . " AND clearing_actual_date IS NULL AND amount >= 0";
		$resultSet = self::$database->query( $sqlQuery );
		$pendingClearanceCount = self::$database->getResultCount( $resultSet );


?>	<fieldset><legend>Payment Info</legend>

		<section>
        	<div>
                <span class="record_label"><?php echo ( $_POST['class'] == "order" ? "Amount Receivable:" : "Amount Payable:" ) ?></span>
                <span class="record_data"><?php echo numberFormat( $transaction['total_amount'], "currency" ) ?></span>
            </div>
            <div>
                <span class="record_label"><?php echo ( $_POST['class'] == "order" ? "Amount Received:" : "Amount Paid:" ) ?></span>
                <span class="record_data"><?php echo numberFormat( $transaction['amount_received'], "currency" ) ?><?php
				if ( $transaction['waived_balance'] > 0 ) {
					echo " (Waived: " . numberFormat( $transaction['waived_balance'], "currency" );
					
					if ( $transaction['cleared_date'] == null )	{
						echo ' <a href="javascript:void(0)" onclick="showDialog(\'Undo Waiving of Balance\',\'';
						$dialogMessage = "Do you want to undo waiving the balance for this order?<br /><br />" .
										 "Waived Balance: " . numberFormat( $transaction['waived_balance'], "currency" ) . "<br /><br /><br />" .
										 "<div id=\"dialog_buttons\">" .
										 "<input type=\"button\" value=\"Yes\" onclick=\"undoWaiveBalanceCommit(\'" . $_POST['transactionID'] . "\'," . $transaction['total_amount'] . "," . $transaction['waived_balance'] . ")\" />" .
										 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
										 "</div>";
						$dialogMessage = htmlentities( $dialogMessage );
						echo $dialogMessage;
						echo '\',\'prompt\')">[ Undo... ]</a> ';
					}
					
					echo ')';
				}
				?></span>
            </div>
            <div>
                <span class="record_label">Balance:</span>
                <span class="record_data"><?php
                    if ( $transaction['balance'] == 0 )
                        echo "<span class=\"good\">" . numberFormat( $transaction['balance'], "currency" ) . "</span>";
                    else
					{
						if ( $transaction['balance'] >= 0 )
	                        echo numberFormat( $transaction['balance'], "currency" );
						else
							echo "<span class=\"good\">" . numberFormat( 0, "currency" ) . "</span>";
					}


					if ( $transaction['balance'] > 0 && $transaction['canceled_date'] == null )
					{
?>
						<span id="enter_payment_link"><a href="javascript:void(0)" onclick="showDialog('Enter payment for <?php echo ucfirst( $_POST['class'] ) ?> No. <?php echo $_POST['transactionID'] ?>','Getting data...','prompt'), ajax(null,'dialog_message','innerHTML','Payment::showInputDialog','class=<?php echo $_POST['class'] ?>&transactionID=<?php echo $_POST['transactionID'] ?>'), loadDatePicker()">[ Enter Payment... ]</a></span>
<?php
						if ( $class == "order" ) {
							echo '<img src="images/cancel.png" class="canceled_icon" title="Waive Remaining Balance" onclick="showDialog(\'Waive Remaining Balance\',\'';
							$dialogMessage = "<b>Warning:</b> Are you sure you want to waive all remaining balance for this order?<br /><br />" .
											 "Balance: " . numberFormat( $transaction['balance'], "currency" ) . "<br /><br /><br />" .
											 "<div id=\"dialog_buttons\">" .
											 "<input type=\"button\" value=\"Yes\" onclick=\"waiveBalanceCommit(\'" . $_POST['transactionID'] . "\'," . $pendingClearanceCount . ")\" />" .
											 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
											 "</div>";
							$dialogMessage = htmlentities( $dialogMessage );
							echo $dialogMessage;
							echo '\',\'warning\')" />';
						}
					}
					elseif ( $transaction['amount_received'] > $transaction['total_amount'] )
					{
						echo " (Rebate: <span class=\"bad\">" . numberFormat( ( $transaction['amount_received'] - $transaction['total_amount'] ), "currency" ) . "</span>)";
?>
						<span id="enter_payment_link"><a href="javascript:void(0)" onclick="showDialog('Enter rebate for <?php echo ucfirst( $_POST['class'] ) ?> No. <?php echo $_POST['transactionID'] ?>','Getting data...','prompt'), ajax(null,'dialog_message','innerHTML','Payment::showRebateDialog','class=<?php echo $_POST['class'] ?>&transactionID=<?php echo $_POST['transactionID'] ?>'), loadDatePicker()">[ Issue Rebate... ]</a></span>
<?php
					}
?>
                </span>
            </div>
        </section>

		<section>
			<table class="payment_info_table item_input_table">
            	<caption>Payment Schedule</caption>
				<thead>
					<tr>
						<th class="payment_schedule_date">Due Date:</th>
						<th class="payment_schedule_amount">Amount to Pay (<?php echo CURRENCY ?>):</th>
						<th class="payment_schedule_amount">Amount <?php echo ( $class == 'order' ? 'Received' : 'Paid' ) . ' (' . CURRENCY . '):' ?></th>
					</tr>
				</thead>
				<tbody>
<?php
		if ( $transaction['payment_term'] == "installment" )		// installment, get payment schedule
		{
			$sqlQuery = "SELECT " . $class . "_payment_schedule.*, SUM(" . $class . "_payment.amount) AS amount_received FROM " . $class . "_payment_schedule";
			$sqlQuery = $sqlQuery . " LEFT JOIN " . $class . "_payment ON " . $class . "_payment_schedule.id = " . $class . "_payment.payment_schedule_id";
			$sqlQuery = $sqlQuery . " WHERE " . $class . "_payment_schedule." . $class . "_id = " . $_POST['transactionID'];
			$sqlQuery = $sqlQuery . " GROUP BY " . $class . "_payment_schedule.id ORDER BY due_date ASC";

			$resultSet = self::$database->query( $sqlQuery );

			if ( self::$database->getResultCount( $resultSet ) > 0 )		// schedule was found, display
			{
				while( $payment = self::$database->getResultRow( $resultSet ) )
				{
					if ( $payment['amount_received'] == NULL )
						$payment['amount_received'] = 0.000;

?>					<tr class="item_row">
						<td><?php echo dateFormatOutput( $payment['due_date'], "F j, Y, D", "Y-m-d" ) ?></td>
						<td class="number"><span class="indent_more"><?php echo numberFormat( $payment['amount_due'], "float" ) ?></span></td>
						<td class="number"><?php
							if (  $transaction['canceled_date'] == null &&
								  numberFormat( $payment['amount_received'], "float", 3, '.', '', true ) ==
								  numberFormat( $payment['amount_due'], "float", 3, '.', '', true ) ) {
								echo "<span class=\"good indent_more\">".numberFormat($payment['amount_received'], "float")."</span>";
							} elseif ( $transaction['canceled_date'] == null && $payment['amount_received'] == 0 ) {
								echo "<span class=\"bad indent_more\">".numberFormat($payment['amount_received'], "float")."</span>";
							} else {
								echo '<span class="indent_more">' . numberFormat( $payment['amount_received'], "float" ) . '</span>';
							}
						?></td>
					</tr>
<?php			}
			}
			else													// no schedule, display delivery/pickup date instead
			{
?>					<tr class="item_row">
						<td><?php echo dateFormatOutput( $transaction['delivery_pickup_target_date'], "F j, Y, D" ) ?></td>
						<td class="number"><span class="indent_more"><?php echo numberFormat( $transaction['total_amount'], "float" ) ?></span></td>
						<td class="number"><?php
							if ( $transaction['canceled_date'] == null &&
								 numberFormat( $transaction['amount_received'], "float", 3, '.', '', true ) ==
								 numberFormat( $transaction['total_amount'], "float" , 3, '.', '', true ) ) {
								echo "<span class=\"good indent_more\">".numberFormat($transaction['amount_received'], "float")."</span>";
							} elseif ( $transaction['canceled_date'] == null && $transaction['amount_received'] == 0 ) {
								echo "<span class=\"bad indent_more\">".numberFormat($transaction['amount_received'], "float")."</span>";
							} else {
								echo '<span class="indent_more">'.numberFormat($transaction['amount_received'], "float").'</span>';
							}
						?></td>
					</tr>
<?php		}
		}
		else												// full, display delivery/pickup date
		{
?>					<tr class="item_row">
						<td><?php echo dateFormatOutput( $transaction['delivery_pickup_target_date'], "F j, Y, D" ) ?></td>
						<td class="number"><span class="indent_more"><?php echo numberFormat( $transaction['total_amount'], "float" ) ?></span></td>
						<td class="number"><?php
							if ( $transaction['canceled_date'] == null &&
								 numberFormat( $transaction['amount_received'], "float", 3, '.', '', true ) ==
								 numberFormat( $transaction['total_amount'], "float", 3, '.', '', true ) ) {
								echo "<span class=\"good indent_more\">".numberFormat($transaction['amount_received'], "float")."</span>";
							} elseif ( $transaction['canceled_date'] == null && $transaction['amount_received'] == 0 ) {
								echo "<span class=\"bad indent_more\">".numberFormat($transaction['amount_received'], "float")."</span>";
							} else {
								echo '<span class="indent_more">'.numberFormat($transaction['amount_received'], "float").'</span>';
							}
						?></td>
					</tr>
<?php	}
?>				</tbody>
			</table>
		</section>

<?php
		// payment breakdown
		$sqlQuery = "SELECT *, SUM(amount) AS amount_paid FROM " . $class . "_payment " .
					"WHERE " . $class . "_id = " . $_POST['transactionID'] . " " .
					"GROUP BY payment_sequence HAVING amount_paid >= 0 ORDER BY payment_date ASC";

		$resultSet = self::$database->query( $sqlQuery );

		if ( self::$database->getResultCount( $resultSet ) > 0 )		// payment is already made
		{
?>
        <section>
        	<table class="payment_info_table item_input_table">
            	<caption>Payment Breakdown</caption>
            	<thead>
					<tr>
						<th class="payment_schedule_date">Date:</th>
						<th class="payment_schedule_amount">Amount <?php echo ( $class == 'order' ? 'Received' : 'Paid' ) . ' (' . CURRENCY . '):' ?></th>
						<th class="payment_schedule_receipt">O.R. No.:</th>
                        <th class="payment_schedule_check">Check No.:</th>
						<th class="payment_schedule_remarks">Remarks:</th>
                        <th class="payment_schedule_target_clearing">Target Clearing Date:</th>
                        <th class="payment_schedule_actual_clearing">Actual Clearing Date:</th>
					</tr>
				</thead>
				<tbody>
<?php
				while( $payment = self::$database->getResultRow( $resultSet ) )
				{
					$paymentDate = new DateTime( $payment['payment_date'] );
					//$runningDays = date_diff( $paymentDate, $transactionDate );
					//$runningDaysFormatted = $runningDays->format( '%a' );
?>
					<tr class="item_row">
                    	<td><?php
                        	echo dateFormatOutput( $payment['payment_date'], "F j, Y, D", "Y-m-d" );
							//$paymentDate = new DateTime( $payment['payment_date'] );
							//$runningDays = date_diff( $paymentDate, $transactionDate );
							/*echo ' (after ' . $runningDaysFormatted;
							if ( (int) $runningDaysFormatted > 1 ) {
								echo ' days)';
							} else {
								echo ' day)';
							}*/
						?></td>
                        <td class="number"><span class="indent_more"><?php echo numberFormat( $payment['amount_paid'], "float" ) ?></span></td>
                        <td><?php
							if ( $payment['receipt_number'] != null )
	                        	echo $payment['receipt_number'];
							else
								echo "(none)";
						?></td>
                        <td><?php
							if ( $payment['payment_type'] == "check" )
							{
								echo '<a href="javascript:void(0)" onclick="showDialog(\'Check Info\',\'';
								$dialogMessage = '<div>' .
												 '<span class=\"record_label\">Check Number:</span>' .
												 '<span class=\"record_data\">' .  ( Filter::output( $payment['check_number'] ) != null ? $payment['check_number'] : "(none specified)" ) . '</span><br /><br />' .
												 '<span class=\"record_label\">Date Issued:</span>' .
												 '<span class=\"record_data\">' .
												 ( $payment['check_date'] != null ? dateFormatOutput( $payment['check_date'], "F j, Y, D", "Y-m-d" ) : '(none specified)' ) . '</span><br /><br />' .
												 '<span class=\"record_label\">Bank:</span>' .
												 '<span class=\"record_data\">' .
												 ( $payment['bank_name'] != null ? capitalizeWords( Filter::output( $payment['bank_name'] ) ) : '(none specified)' ) . '</span><br /><br />' .
												 '<span class=\"record_label\">Branch:</span>' .
												 '<span class=\"record_data\">' .
												 ( $payment['bank_branch'] != null ? capitalizeWords( Filter::output( $payment['bank_branch'] ) ) : '(none specified)' ) . '</span>' .
												 '</div><br /><br />' .
												 '<div id=\"dialog_buttons\">' .
												 '<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />' .
												 "</div>";

								$dialogMessage = htmlentities( $dialogMessage );
								echo $dialogMessage;

								echo '\',\'prompt\')">' . ( $payment['check_number'] != null ? $payment['check_number'] : "(check)" ) . '</a>';
							}
							else
								echo "(cash)";
						?></td>
						<td><?php
							echo Filter::output( $payment['remarks'] );
						?></td>
                        <td><?php
                            echo dateFormatOutput( $payment['clearing_target_date'], "F j, Y, D", "Y-m-d" );
						?></td>
                        <td><?php
							if ( $transaction['cleared_date'] != null )		// transaction is already cleared, disable clearing of payments
							{
								if ( $payment['clearing_actual_date'] == null )
									echo ' <span class="bad">(Not yet cleared)</span>';
								else
									echo dateFormatOutput( $payment['clearing_actual_date'], "F j, Y, D", "Y-m-d" );
							}
							else
							{
								if ( $payment['clearing_actual_date'] == null )
								{
									echo ' <a href="javascript:void(0)" onclick="showDialog(\'Clear Payment\',\'';
									$dialogMessage = "Mark this payment as <b>Cleared</b>?<br /><br /><br /><br /><br />" .
													 "<div id=\"dialog_buttons\">" .
													 "<input type=\"button\" value=\"Yes\" onclick=\"clearPaymentCommit(\'" . $class . "\',\'" . $_POST['transactionID'] . "\',\'" . $payment['payment_sequence'] . "\'," . $transaction['balance'] . "," . $pendingClearanceCount . ")\" />" .
													 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
													 "</div>";
									$dialogMessage = htmlentities( $dialogMessage );
									echo $dialogMessage;
									echo '\',\'prompt\')">[ Mark payment as Cleared.. ]</a>';
									
									
									echo '<img src="images/cancel.png" class="canceled_icon cancel_payment_icon" title="Delete Payment" onclick="showDialog(\'Delete Payment\',\'';
									$dialogMessage = "Are you sure you want to <b>delete</b> this payment?<br /><br />" .
													 "Proceeding will mean that:<ul>" .
													 "<li>the cash payment is returned; or</li>" .
													 "<li>the check payment had bounced</li>" .
													 "</ul><br />" .
													 "<div id=\"dialog_buttons\">" .
													 "<input type=\"button\" value=\"Yes\" onclick=\"deletePaymentCommit(\'" . $class . "\',\'" . $_POST['transactionID'] . "\',\'" . $payment['payment_sequence'] . "\'," . $transaction['total_amount'] . "," . $transaction['balance'] . "," . $payment['amount_paid'] . "," . $pendingClearanceCount . ")\" />" .
													 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
													 "</div>";
									$dialogMessage = htmlentities( $dialogMessage );
									echo $dialogMessage;
									echo '\',\'prompt\')" />';
								}
								else
								{
									echo dateFormatOutput( $payment['clearing_actual_date'], "F j, Y, D", "Y-m-d" );
									echo ' <a href="javascript:void(0)" onclick="showDialog(\'Clear Payment\',\'';
									$dialogMessage = "Undo clearing of this payment?<br /><br /><br /><br /><br />" .
													 "<div id=\"dialog_buttons\">" .
													 "<input type=\"button\" value=\"Yes\" onclick=\"undoClearPaymentCommit(\'" . $class . "\',\'" . $_POST['transactionID'] . "\',\'" . $payment['payment_sequence'] . "\',\'" . $transaction['balance'] . "\'," . $pendingClearanceCount . ")\" />" .
													 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
													 "</div>";

									$dialogMessage = htmlentities( $dialogMessage );
									echo $dialogMessage;

									echo '\',\'warning\')">[Unclear..]</a>';
								}
							}
						?>
                        </td>
                    </tr>
<?php
				}
?>
                </tbody>
            </table>
        </section> 
<?php
		}
		
		
        // rebate breakdown
		$sqlQuery = "SELECT *, SUM(amount) AS amount_paid FROM " . $class . "_payment " .
					"WHERE " . $class . "_id = " . $_POST['transactionID'] . " " .
					"GROUP BY payment_sequence HAVING amount_paid < 0 ORDER BY payment_sequence ASC";

		$resultSet = self::$database->query( $sqlQuery );

		if ( self::$database->getResultCount( $resultSet ) > 0 )		// payment is already made
		{
?>
		<style type="text/css">
			img.cancel_payment_icon {
				display: none;
			}
        </style>
        
        <section>
        	<table class="payment_info_table item_input_table">
            	<caption>Rebate Breakdown</caption>
            	<thead>
					<tr>
						<th class="payment_schedule_date">Date:</th>
						<th class="payment_schedule_amount">Amount Returned (<?php echo CURRENCY ?>):</th>
                        <th class="payment_schedule_check">Check No.:</th>
                        <th class="payment_schedule_target_clearing">Target Clearing Date:</th>
                        <th class="payment_schedule_actual_clearing_rebate">Actual Clearing Date:</th>
					</tr>
				</thead>
				<tbody>
<?php
				while( $payment = self::$database->getResultRow( $resultSet ) )
				{
?>
					<tr class="item_row">
                    	<td><?php echo dateFormatOutput( $payment['payment_date'], "F j, Y, D", "Y-m-d" ) ?></td>
                        <td class="number"><span class="indent_more"><?php echo numberFormat( $payment['amount_paid'] * -1, "float" ) ?></span></td>
                        <td><?php
							if ( $payment['payment_type'] == "check" )
							{
								echo '<a href="javascript:void(0)" onclick="showDialog(\'Check Info\',\'';
								$dialogMessage = '<div>' .
												 '<span class=\"record_label\">Check Number:</span>' .
												 '<span class=\"record_data\">' .  ( $payment['check_number'] != null ? $payment['check_number'] : "(none specified)" ) . '</span><br /><br />' .
												 '<span class=\"record_label\">Date Issued:</span>' .
												 '<span class=\"record_data\">' .
												 ( $payment['check_date'] != null ? dateFormatOutput( $payment['check_date'], "F j, Y, D", "Y-m-d" ) : '(none specified)' ) . '</span><br /><br />' .
												 '<span class=\"record_label\">Bank:</span>' .
												 '<span class=\"record_data\">' .
												 ( $payment['bank_name'] != null ? $payment['bank_name'] : '(none specified)' ) . '</span><br /><br />' .
												 '<span class=\"record_label\">Branch:</span>' .
												 '<span class=\"record_data\">' .
												 ( $payment['bank_branch'] != null ? $payment['bank_branch'] : '(none specified)' ) . '</span>' .
												 '</div><br /><br />' .
												 '<div id=\"dialog_buttons\">' .
												 '<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />' .
												 "</div>";

								$dialogMessage = htmlentities( $dialogMessage );
								echo $dialogMessage;

								echo '\',\'prompt\')">' . ( $payment['check_number'] != null ? $payment['check_number'] : "(check)" ) . '</a>';
							}
							else
								echo "(cash)";
						?></td>
                        <td><?php
                            echo dateFormatOutput( $payment['clearing_target_date'], "F j, Y, D", "Y-m-d" );
						?></td>
                        <td><?php
							if ( $payment['clearing_actual_date'] == null )
							{
								echo ' <a href="javascript:void(0)" onclick="showDialog(\'Clear Payment\',\'';
								$dialogMessage = "Mark this payment as <b>Cleared</b>?<br /><br /><br /><br /><br />" .
												 "<div id=\"dialog_buttons\">" .
												 "<input type=\"button\" value=\"Yes\" onclick=\"clearPaymentCommit(\'" . $class . "\',\'" . $_POST['transactionID'] . "\',\'" . $payment['payment_sequence'] . "\',\'" . $transaction['balance'] . "\',\'" . $pendingClearanceCount . "\',true)\" />" .
												 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
												 "</div>";
								$dialogMessage = htmlentities( $dialogMessage );
								echo $dialogMessage;
								echo '\',\'prompt\')">(Not yet cleared)</a>';
								
								
								echo '<img src="images/cancel.png" class="canceled_icon cancel_rebate_icon" title="Delete Payment" onclick="showDialog(\'Delete Payment\',\'';
								$dialogMessage = "Are you sure you want to <b>delete</b> this payment?<br /><br />" .
												 "Proceeding will mean that:<ul>" .
												 "<li>the cash payment is returned; or</li>" .
												 "<li>the check payment had bounced</li>" .
												 "</ul><br />" .
												 "<div id=\"dialog_buttons\">" .
												 "<input type=\"button\" value=\"Yes\" onclick=\"deletePaymentCommit(\'" . $class . "\',\'" . $_POST['transactionID'] . "\',\'" . $payment['payment_sequence'] . "\'," . $transaction['total_amount'] . "," . $transaction['balance'] . "," . $payment['amount_paid'] . "," . $pendingClearanceCount . ",true)\" />" .
												 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
												 "</div>";
								$dialogMessage = htmlentities( $dialogMessage );
								echo $dialogMessage;
								echo '\',\'prompt\')" />';
							}
							else
							{
								echo dateFormatOutput( $payment['clearing_actual_date'], "F j, Y, D", "Y-m-d" );
								echo ' <a href="javascript:void(0)" onclick="showDialog(\'Clear Payment\',\'';
								$dialogMessage = "Undo clearing of this payment?<br /><br /><br /><br /><br />" .
												 "<div id=\"dialog_buttons\">" .
												 "<input type=\"button\" value=\"Yes\" onclick=\"undoClearPaymentCommit(\'" . $class . "\',\'" . $_POST['transactionID'] . "\',\'" . $payment['payment_sequence'] . "\',\'" . $transaction['balance'] . "\'," . $pendingClearanceCount . ",true)\" />" .
												 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
												 "</div>";

								$dialogMessage = htmlentities( $dialogMessage );
								echo $dialogMessage;

								echo '\',\'warning\')">(Cleared)</a>';
							}
						?>
                        </td>
                    </tr>
<?php
				}
?>
                </tbody>
            </table>
        </section>
<?php
		}
?>
	</fieldset>
<?php
	}



	// display Enter Payment Dialog, ajax function
	public static function showInputDialog()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) )
			return;

		$class = $_POST['class'];

		if ( $class == "order" )
		{
			$cashFlow = "inbound";
			$totalField = "amount_receivable";
		}
		else
		{
			$cashFlow = "outbound";
			$totalField = "amount_payable";
		}


		self::$database = new Database;


		$sqlQuery = "SELECT id, transaction_type, delivery_pickup_target_date, payment_term, ";

		if ( $cashFlow == "inbound" )
			$sqlQuery = $sqlQuery . "IF(canceled_date IS NOT NULL,'canceled',IF(cleared_date IS NOT NULL,'cleared',IF(delivery_pickup_actual_date IS NULL,'pending',IF(balance = 0,'fully-paid',IF(balance = " . $totalField . ",'delivered','partially-paid'))))) AS status, ";
		else
			$sqlQuery = $sqlQuery . "IF(canceled_date IS NOT NULL,'canceled',IF(cleared_date IS NOT NULL,'cleared',IF(delivery_pickup_actual_date IS NULL,'pending','delivered'))) AS status, ";

		$sqlQuery = $sqlQuery . $totalField . " AS total_amount, SUM(" . $class . "_payment.amount) AS amount_received FROM `" . $class . "`" .
					" LEFT JOIN " . $class . "_payment ON `" . $class . "`.id = " . $class . "_payment." . $class . "_id" .
					" WHERE `" . $class . "`.id = " . $_POST['transactionID'] .
					" GROUP BY " . $class . ".id";

		$resultSet = self::$database->query( $sqlQuery );

		$transaction = self::$database->getResultRow( $resultSet );


		if ( $transaction['payment_term'] == "installment" )	// installment payment term
		{
			$sqlQuery = "SELECT " . $class . "_payment_schedule.*, SUM(" . $class . "_payment.amount) AS amount_received FROM " . $class . "_payment_schedule";
			$sqlQuery = $sqlQuery . " LEFT JOIN " . $class . "_payment ON " . $class . "_payment_schedule.id = " . $class . "_payment.payment_schedule_id";
			$sqlQuery = $sqlQuery . " WHERE " . $class . "_payment_schedule." . $class . "_id = " . $transaction['id'];
			$sqlQuery = $sqlQuery . " GROUP BY " . $class . "_payment_schedule.id ORDER BY " . $class . "_payment_schedule.due_date ASC";
			$resultSet = self::$database->query( $sqlQuery );

			if ( self::$database->getResultCount( $resultSet ) > 0 )
			{
				$amountReceivedToDate = 0.000;		// total amount received to date
				$toPay = NULL;						// to pay
				$paymentScheduleID = NULL;			// id of payment schedule
				$paymentDueDate = NULL;				// payment date

				while( $payment = self::$database->getResultRow( $resultSet ) )
				{
					// compute the amount received until today
					if ( $payment['amount_received'] != NULL )
						$amountReceivedToDate = $amountReceivedToDate + $payment['amount_received'];

					// determine last payment
					if ( $payment['amount_received'] < $payment['amount_due'] && $toPay == NULL  )
					{
						if ( $payment['amount_received'] == NULL )	// zero payment
							$toPay = $payment['amount_due'];
						else										// get balance
							$toPay = $payment['amount_due'] - $payment['amount_received'];

						$paymentScheduleID = $payment['id'];
						$paymentDueDate = $payment['due_date'];
					}
				}
			}
			else			// no payment schedule, consider as full payment
			{
				if ( $transaction['amount_received'] != NULL )
					$amountReceivedToDate = $transaction['amount_received'];
				else
					$amountReceivedToDate = 0.000;
				$toPay = $transaction['total_amount'] - $amountReceivedToDate;
				$paymentScheduleID = NULL;
				$paymentDueDate = dateFormatOutput( $transaction['delivery_pickup_target_date'], "Y-m-d" );
			}
		}
		else			// full payment term
		{
			if ( $transaction['amount_received'] != NULL )
				$amountReceivedToDate = $transaction['amount_received'];
			else
				$amountReceivedToDate = 0.000;
			$toPay = $transaction['total_amount'] - $amountReceivedToDate;
			$paymentScheduleID = NULL;
			$paymentDueDate = dateFormatOutput( $transaction['delivery_pickup_target_date'], "Y-m-d" );
		}


		// passing values to hidden fields
		$dialogMessage = "<form name=\"add_payment\" method=\"post\" action=\"javascript:enterPayment()\" autocomplete=\"off\">";
		$dialogMessage = $dialogMessage . "<input type=\"hidden\" name=\"" . $class . "_id\" id=\"" . $class . "_id\" value=\"" . $transaction['id']. "\" />";
		$dialogMessage = $dialogMessage . "<input type=\"hidden\" name=\"transaction_type\" id=\"transaction_type\" value=\"" . $transaction['transaction_type']. "\" />";
		$dialogMessage = $dialogMessage . "<input type=\"hidden\" name=\"total_amount\" id=\"total_amount\" value=\"" . $transaction['total_amount'] . "\" />";
		$dialogMessage = $dialogMessage . "<input type=\"hidden\" name=\"total_amount_pending\" id=\"total_amount_pending\" value=\"" . ( $transaction['total_amount'] - $amountReceivedToDate ). "\" />";
		$dialogMessage = $dialogMessage . "<input type=\"hidden\" name=\"amount_pending\" id=\"amount_pending\" value=\"" . $toPay . "\" />";
		$dialogMessage = $dialogMessage . "<input type=\"hidden\" name=\"payment_schedule_id\" id=\"payment_schedule_id\" value=\"" . $paymentScheduleID . "\" />";
		$dialogMessage = $dialogMessage . "<input type=\"hidden\" name=\"status\" id=\"status\" value=\"" . $transaction['status'] . "\" />";

		// total amount
		$dialogMessage = $dialogMessage . "<div>";
		if ( $cashFlow == "inbound" )
			$dialogMessage = $dialogMessage . "<span class=\"record_label\">Total Amount Receivable:</span>";
		else
			$dialogMessage = $dialogMessage . "<span class=\"record_label\">Total Amount to Pay:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\">" . numberFormat( $transaction['total_amount'], "currency" ) ."</span></div><div>";
		if ( $cashFlow == "inbound" )
			$dialogMessage = $dialogMessage . "<span class=\"record_label\">Today Amount<br />Received until Today:</span>";
		else		// purchase
			$dialogMessage = $dialogMessage . "<span class=\"record_label\">Today Amount<br />Paid until Today:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><br />" . numberFormat( $amountReceivedToDate, "currency" ) ."</span>";
		$dialogMessage = $dialogMessage . "<br /><br /><br /></div>";

		$dialogMessage = $dialogMessage . "<hr />";

		// amount to pay
		$dialogMessage = $dialogMessage . "<div>";
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">";
		if ( $cashFlow == "inbound" )
			$dialogMessage = $dialogMessage . "Amount Receivable on ";
		else
			$dialogMessage = $dialogMessage . "Amount to Pay by ";
		$dialogMessage = $dialogMessage . "<br />" . dateFormatOutput( $paymentDueDate, "M j, Y, D", "Y-m-d" ) . ":" ."</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><br />" . numberFormat( $toPay, "currency" ) ."</span>";
		$dialogMessage = $dialogMessage . "<br /><br /><br /></div>";

		// input payment
		$dialogMessage = $dialogMessage . "<div>";
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">";
		if ( $cashFlow == "inbound" )
			$dialogMessage = $dialogMessage . "Amount Received: ";
		else
			$dialogMessage = $dialogMessage . "Amount Paid: ";
		$dialogMessage = $dialogMessage . CURRENCY . "</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"amount_received\" id=\"amount_received\" class=\"number\" value=\"0.000\" required=\"required\" onfocus=\"data.selectField( $(this), 'float' )\" onblur=\"data.validateField( $(this), 'float' )\" style=\"margin-right: 8px\" />";
		$dialogMessage = $dialogMessage . "<select name=\"payment_type\" id=\"payment_type\" onchange=\"toggleCheckInfoSection()\">";
		$dialogMessage = $dialogMessage . "<option value=\"check\" selected=\"selected\">Check</option>";
		$dialogMessage = $dialogMessage . "<option value=\"cash\">Cash</option>";
		$dialogMessage = $dialogMessage . "</select></span>";

		// payment date
		if ( $cashFlow == "inbound" )
			$dialogMessage = $dialogMessage . "<span class=\"record_label\">Date of Collection:</span>";
		else
			$dialogMessage = $dialogMessage . "<span class=\"record_label\">Date of Payment:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"payment_date\" id=\"payment_date\" class=\"datepicker\" required=\"required\" size=\"30\" maxlength=\"30\" onfocus=\"data.selectField( $(this) )\" /></span>";

		// receipt number
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">O.R. No. Issued:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"receipt_number\" id=\"receipt_number\" size=\"30\" /></span>";
		$dialogMessage = $dialogMessage . "<br /><br /><br /><br /><br /></div>";

		// details of check payment
		//$dialogMessage = $dialogMessage . "<div id=\"check_info\" style=\"display: none\"><br />";
		$dialogMessage = $dialogMessage . "<div id=\"check_info\"><br />";
		
		// bank name
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">Bank:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"bank_name\" id=\"bank_name\" /></span>";

		// bank branch name
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">Branch:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"branch_name\" id=\"branch_name\" /></span>";

		// check number
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">Check Number:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"check_number\" id=\"check_number\" size=\"30\" /></span>";

		// date of check
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">Check Date:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"check_date\" id=\"check_date\" class=\"datepicker\" size=\"30\" maxlength=\"30\" onfocus=\"data.selectField( $(this) )\" /></span>";

		$dialogMessage = $dialogMessage . "<br /><br /><br /><br /><br /><br /></div>";

		// target clearing date
		$dialogMessage = $dialogMessage . "<div><br />";
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">Target Clearing Date:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"clearing_date\" id=\"clearing_date\" class=\"datepicker\" size=\"30\" maxlength=\"30\" onfocus=\"data.selectField( $(this) )\" /></span><br />";
		$dialogMessage = $dialogMessage . "<br /></div>";

		// remarks
		$dialogMessage = $dialogMessage . "<div><br />";
		$dialogMessage = $dialogMessage . "<span class=\"record_label\">Remarks:</span>";
		$dialogMessage = $dialogMessage . "<span class=\"record_data\"><input type=\"text\" name=\"remarks\" id=\"remarks\" size=\"30\" maxlength=\"100\" /></span><br />";
		$dialogMessage = $dialogMessage . "<br /><br /></div>";

		// Yes and No buttons
		$dialogMessage = $dialogMessage . "<div id=\"dialog_buttons\">";
		$dialogMessage = $dialogMessage . "<input type=\"submit\" value=\"Save\" />";
		$dialogMessage = $dialogMessage . "<input type=\"reset\" value=\"Reset\" />";
		$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"Cancel\" onclick=\"hideDialog()\" />";
		$dialogMessage = $dialogMessage . "</div>";

		$dialogMessage = $dialogMessage . "</form>";

		echo $dialogMessage;
	}



	// save payment, ajax function
	public static function save()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) || !isset( $_POST['totalToPay'] ) ||
			 !isset( $_POST['toPay'] ) || !isset( $_POST['paymentScheduleID'] ) ||
			 !isset( $_POST['amountReceived'] ) || !isset( $_POST['paymentDate'] ) ||
			 !isset( $_POST['paymentType'] ) || !isset( $_POST['receiptNumber'] ) )
			return;

		$class = $_POST['class'];


		// filter input
		$_POST['amountReceived'] = Filter::input( $_POST['amountReceived'] );
		if ( $_POST['amountReceived'] <= 0 )
		{
			echo "Error: Amount entered is zero<br /><br />Please try again.<br /><br /><br />";
			return;
		}


		self::$database = new Database();

		// get payment sequence
		$sqlQuery = "SELECT DISTINCT payment_sequence FROM " . $class . "_payment WHERE " . $class . "_id = " . $_POST['transactionID'] . " ORDER BY payment_sequence DESC LIMIT 0,1";
		$resultSet = self::$database->query( $sqlQuery );
		if ( self::$database->getResultCount( $resultSet ) > 0 )
		{
			$payment = self::$database->getResultRow( $resultSet );
			$sequence = (int) $payment['payment_sequence'] + 1;
		}
		else
			$sequence = 1;


		// enter payment to table
		if ( $_POST['amountReceived'] <= $_POST['toPay'] )
		{
			if ( empty( $_POST['paymentScheduleID'] ) )		// full payment, hence no payment schedule
				$_POST['paymentScheduleID'] = "NULL";

			self::savePayment( $class, $_POST['amountReceived'], $_POST['paymentScheduleID'], $sequence );
		}
		else		// amount received is greater than amount to pay for due date
		{
			$sqlQuery = "SELECT id, amount_due, SUM(" . $class . "_payment.amount) AS amount_received " .
						"FROM " . $class . "_payment_schedule " .
						"LEFT JOIN " . $class . "_payment ON " . $class . "_payment_schedule.id = " . $class . "_payment.payment_schedule_id " .
						"WHERE " . $class . "_payment_schedule." . $class . "_id = " . $_POST['transactionID'] . " " .
						"GROUP BY " . $class . "_payment_schedule.id " .
						"HAVING amount_received < amount_due OR amount_received IS NULL ";
						"ORDER BY due_date";
			$resultSet = self::$database->query( $sqlQuery );
			if ( self::$database->getResultCount( $resultSet ) > 0 )		// with payment schedule
			{
				$amountReceived = (double) $_POST['amountReceived'];
				$paymentScheduleID = "NULL";

				while ( $amountReceived > 0 )
				{
					$paymentSchedule = self::$database->getResultRow( $resultSet );
					if ( $paymentSchedule != null )
					{
						$paymentScheduleID = $paymentSchedule['id'];

						if ( $paymentSchedule['amount_received'] != NULL )
							$toPay = $paymentSchedule['amount_due'] - $paymentSchedule['amount_received'];
						else
							$toPay = $paymentSchedule['amount_due'];

						if ( $amountReceived > $toPay )
							self::savePayment( $class, $toPay, $paymentScheduleID, $sequence );
						else
							self::savePayment( $class, $amountReceived, $paymentScheduleID, $sequence );

						$amountReceived = $amountReceived - $toPay;
					}
					else
					{
						self::savePayment( $class, $amountReceived, $paymentScheduleID, $sequence );
						$amountReceived = 0;
					}
				}
			}
			else
			{
				if ( empty( $_POST['paymentScheduleID'] ) )		// full payment, hence no payment schedule
					$_POST['paymentScheduleID'] = "NULL";

				self::savePayment( $class, $_POST['amountReceived'], $_POST['paymentScheduleID'], $sequence );
			}
		}


		// reestablish database connection
		self::$database = new Database;

		//if ( $_POST['amountReceived'] > $_POST['totalToPay'] )		// amount entered is greater than total amount to pay
		//	$_POST['amountReceived'] = $_POST['totalToPay'];

		// update transaction status and recorded balance
		$sqlQuery = "UPDATE `" . $class . "` SET balance = ( balance - " . $_POST['amountReceived'] . " )";
		$sqlQuery = $sqlQuery . " WHERE id = " . $_POST['transactionID'];


		if ( self::$database->query( $sqlQuery ) )
		{
			// log event
			if ( $_POST['class'] == "order" ) {
				$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id, name FROM `order` ' .
							'INNER JOIN customer ON `order`.customer_id = customer.id ' .
							'WHERE `order`.id=' . $_POST['transactionID'];
				$resultSet = self::$database->query( $sqlQuery );
				$order = self::$database->getResultRow( $resultSet );
				
				if ( $order['sales_invoice'] != null ) {
					$invoiceNumber = 'SI ' . $order['sales_invoice'];
				} else {
					$invoiceNumber = 'DR ' . $order['delivery_receipt'];
				}
				
				EventLog::addEntry( self::$database, 'info', 'order_payment', 'insert', 'new',
									'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
									$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
									numberFormat( $_POST['amountReceived'], 'currency' ) . ' ' . $_POST['paymentType'] .
									' payment from <a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
									capitalizeWords( Filter::output( $order['name'] ) ) . '</a> was <span class="event_log_action">received</span>' );
			} else {
				$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id, name FROM purchase ' .
							'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
							'WHERE purchase.id=' . $_POST['transactionID'];
				$resultSet = self::$database->query( $sqlQuery );
				$purchase = self::$database->getResultRow( $resultSet );
				
				if ( $purchase['sales_invoice'] != null ) {
					$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
				} elseif ( $purchase['delivery_receipt'] != null ) {
					$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
				} else {
					$invoiceNumber = '';
				}
				
				EventLog::addEntry( self::$database, 'info', 'purchase_payment', 'insert', 'new',
									'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
									$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
									numberFormat( $_POST['amountReceived'], 'currency' ) . ' ' . $_POST['paymentType'] .
									' payment for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
									capitalizeWords( Filter::output( $purchase['name'] ) ) . '</a> was <span class="event_log_action">issued</span>' );
			}
			
			
			// display success message
			if ( $_POST['amountReceived'] >= $_POST['totalToPay'] ) {
				if ( $class == "order" ) {
					echo "Order No. ";
				} else {
					echo "Purchase No. ";
				}
				echo $_POST['transactionID'] . " is now <b>fully paid</b>!<br /><br /><br />";
			} else {
				echo "Payment for ";
				if ( $class == "order" ) {
					echo "Order No. ";
				} else {
					echo "Purchase No. ";
				}
				echo $_POST['transactionID'] . " is entered successfully!<br /><br /><br />";
			}
		}
		else
		{
			if ( $class == "order" )
				Diagnostics::error( 'dialog', ERROR, "Cannot update Order No. " . $_POST['transactionID'], "Please try again.", SYSTEM_ERROR );
			else
				Diagnostics::error( 'dialog', ERROR, "Cannot update Purchase No. " . $_POST['transactionID'], "Please try again.", SYSTEM_ERROR );
		}
	}



	// save payment received
	private static function savePayment( $class, $amountReceived, $paymentScheduleID, $sequence )
	{
		$db = new Database();

		if ( isset( $_POST['receiptNumber'] ) ) {
			$receipt = Filter::input( $_POST['receiptNumber'] );
			if ( empty( $receipt ) ) {
				$receipt = "NULL";
			} else {
				$receipt = "'" . $receipt . "'";
			}
		} else {
			$receipt = "NULL";
		}

		$sqlQuery = "INSERT INTO " . $class . "_payment VALUES (" .
					$_POST['transactionID'] . "," .																// order_id/purchase_id
					$sequence . "," .																			// payment_sequence
					$paymentScheduleID . "," .																	// payment_schedule_id
					$amountReceived . ",'" .																	// amount
					dateFormatInput( Filter::input( $_POST['paymentDate'] ), "Y-m-d", "F j, Y, D" ) . "'," .	// payment_date
					$receipt . ",'" .																			// receipt_number
					$_POST['paymentType'] . "',";																// payment_type


		if ( $_POST['paymentType'] == "check" )		// check payment, enter check info
		{
			// bank_name
			if ( !empty( $_POST['bankName'] ) )
				$sqlQuery = $sqlQuery . "'" . Filter::input( $_POST['bankName'] ) . "',";
			else
				$sqlQuery = $sqlQuery . "NULL,";

			// branch_name
			if ( !empty( $_POST['branchName'] ) )
				$sqlQuery = $sqlQuery . "'" . Filter::input( $_POST['branchName'] ) . "',";
			else
				$sqlQuery = $sqlQuery . "NULL,";

			// check_number
			if ( !empty( $_POST['checkNumber'] ) )
				$sqlQuery = $sqlQuery . "'" . Filter::input( $_POST['checkNumber'] ) . "',";
			else
				$sqlQuery = $sqlQuery . "NULL,";

			// check_date
			if ( !empty( $_POST['checkDate'] ) )
				$sqlQuery = $sqlQuery . "'" . dateFormatInput( Filter::input( $_POST['checkDate'] ), "Y-m-d", "F j, Y, D" ) . "',";
			else
				$sqlQuery = $sqlQuery . "NULL,";
		}
		else										// cash payment
			$sqlQuery = $sqlQuery . "NULL,NULL,NULL,NULL,";


		// clearing_target_date
		if ( !empty( $_POST['clearingDate'] ) )
			$sqlQuery = $sqlQuery . "'" . dateFormatInput( Filter::input( $_POST['clearingDate'] ), "Y-m-d", "F j, Y, D" ) . "'";
		else		// set to same date as payment_date
			$sqlQuery = $sqlQuery . "'" . dateFormatInput( Filter::input( $_POST['paymentDate'] ), "Y-m-d", "F j, Y, D" ) . "'";


		// clearing_actual_date
		$sqlQuery = $sqlQuery . ",NULL";

		// remarks
		if ( !empty( $_POST['remarks'] ) )
			$sqlQuery = $sqlQuery . ",'" . Filter::input( $_POST['remarks'] ) . "')";
		else
			$sqlQuery = $sqlQuery . ",NULL)";


		$db->query( $sqlQuery );
	}



	// mark a payment as cleared, ajax function
	public static function clearPayment()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) ||
			 !isset( $_POST['paymentSequence'] ) || !isset( $_POST['balance'] ) )
			return;

		self::$database = new Database();

		$clearingActualDate = date( 'Y-m-d' );
		$sqlQuery = "UPDATE " . $_POST['class'] . "_payment SET clearing_actual_date = '" . $clearingActualDate . "' WHERE " . $_POST['class'] . "_id = " . $_POST['transactionID'] . " AND payment_sequence = " . $_POST['paymentSequence'];
		if ( self::$database->query( $sqlQuery ) ) {
			// log event
			$sqlQuery = 'SELECT SUM(amount) AS amount, payment_type FROM ' . $_POST['class'] . "_payment " .
						'WHERE ' . $_POST['class'] . '_id=' . $_POST['transactionID'] . ' ' .
						'AND payment_sequence=' . $_POST['paymentSequence'];
			$resultSet = self::$database->query( $sqlQuery );
			$payment = self::$database->getResultRow( $resultSet );
			
			if ( $_POST['class'] == "order" ) {
				$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id, name FROM `order` ' .
							'INNER JOIN customer ON `order`.customer_id = customer.id ' .
							'WHERE `order`.id=' . $_POST['transactionID'];
				$resultSet = self::$database->query( $sqlQuery );
				$order = self::$database->getResultRow( $resultSet );
				
				if ( $order['sales_invoice'] != null ) {
					$invoiceNumber = 'SI ' . $order['sales_invoice'];
				} else {
					$invoiceNumber = 'DR ' . $order['delivery_receipt'];
				}
				
				if ( $payment['amount'] >= 0 ) {
					EventLog::addEntry( self::$database, 'info', 'order_payment', 'update', 'cleared',
										'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
										numberFormat( $payment['amount'], 'currency' ) . ' ' . $payment['payment_type'] .
										' payment from <a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
										capitalizeWords( Filter::output( $order['name'] ) ) . '</a> was <span class="event_log_action good">cleared</span>' );
				} else {
					EventLog::addEntry( self::$database, 'info', 'order_payment', 'update', 'cleared',
										'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
										numberFormat( $payment['amount'] * -1, 'currency' ) . ' ' . $payment['payment_type'] .
										' rebate for <a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
										capitalizeWords( Filter::output( $order['name'] ) ) . '</a> was <span class="event_log_action good">cleared</span>' );
				}
			} else {
				$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id, name FROM purchase ' .
							'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
							'WHERE purchase.id=' . $_POST['transactionID'];
				$resultSet = self::$database->query( $sqlQuery );
				$purchase = self::$database->getResultRow( $resultSet );
				
				if ( $purchase['sales_invoice'] != null ) {
					$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
				} elseif ( $purchase['delivery_receipt'] != null ) {
					$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
				} else {
					$invoiceNumber = '';
				}
				
				if ( $payment['amount'] >= 0 ) {
					EventLog::addEntry( self::$database, 'info', 'purchase_payment', 'update', 'cleared',
										'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
										numberFormat( $payment['amount'], 'currency' ) . ' ' . $payment['payment_type'] .
										' payment for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
										capitalizeWords( Filter::output( $purchase['name'] ) ) . '</a> was <span class="event_log_action good">cleared</span>' );
				} else {
					EventLog::addEntry( self::$database, 'info', 'purchase_payment', 'update', 'cleared',
										'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
										numberFormat( $payment['amount'] * -1, 'currency' ) . ' ' . $payment['payment_type'] .
										' rebate from <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
										capitalizeWords( Filter::output( $purchase['name'] ) ) . '</a> was <span class="event_log_action good">cleared</span>' );
				}
			}
			
			echo "Payment successfully cleared!<br /><br /><br />";
		} else {
			Diagnostics::error( 'dialog', ERROR, "Cannot update " . ucfirst( $_POST['class'] ) ." No. " . $_POST['transactionID'], "Please try again.", SYSTEM_ERROR );
		}
	}


	// mark a payment as cleared, ajax function
	public static function undoClearPayment()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) ||
			 !isset( $_POST['paymentSequence'] ) || !isset( $_POST['balance'] ) )
			return;
		self::$database = new Database();

		$sqlQuery = "UPDATE " . $_POST['class'] . "_payment SET clearing_actual_date = NULL WHERE " . $_POST['class'] . "_id = " . $_POST['transactionID'] . " AND payment_sequence = " . $_POST['paymentSequence'];
		if ( self::$database->query( $sqlQuery ) ) {
			// log event
			$sqlQuery = 'SELECT SUM(amount) AS amount, payment_type FROM ' . $_POST['class'] . "_payment " .
						'WHERE ' . $_POST['class'] . '_id=' . $_POST['transactionID'] . ' ' .
						'AND payment_sequence=' . $_POST['paymentSequence'];
			$resultSet = self::$database->query( $sqlQuery );
			$payment = self::$database->getResultRow( $resultSet );
			
			if ( $_POST['class'] == "order" ) {
				$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id, name FROM `order` ' .
							'INNER JOIN customer ON `order`.customer_id = customer.id ' .
							'WHERE `order`.id=' . $_POST['transactionID'];
				$resultSet = self::$database->query( $sqlQuery );
				$order = self::$database->getResultRow( $resultSet );
				
				if ( $order['sales_invoice'] != null ) {
					$invoiceNumber = 'SI ' . $order['sales_invoice'];
				} else {
					$invoiceNumber = 'DR ' . $order['delivery_receipt'];
				}
				
				if ( $payment['amount'] >= 0 ) {
					EventLog::addEntry( self::$database, 'warning', 'order_payment', 'update', 'uncleared',
										'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
										numberFormat( $payment['amount'], 'currency' ) . ' ' . $payment['payment_type'] .
										' payment from <a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
										capitalizeWords( Filter::output( $order['name'] ) ) . '</a> was <span class="event_log_action">uncleared</span>' );
				} else {
					EventLog::addEntry( self::$database, 'warning', 'order_payment', 'update', 'uncleared',
										'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
										numberFormat( $payment['amount'] * -1, 'currency' ) . ' ' . $payment['payment_type'] .
										' rebate for <a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
										capitalizeWords( Filter::output( $order['name'] ) ) . '</a> was <span class="event_log_action">uncleared</span>' );
				}
			} else {
				$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id, name FROM purchase ' .
							'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
							'WHERE purchase.id=' . $_POST['transactionID'];
				$resultSet = self::$database->query( $sqlQuery );
				$purchase = self::$database->getResultRow( $resultSet );
				
				if ( $purchase['sales_invoice'] != null ) {
					$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
				} elseif ( $purchase['delivery_receipt'] != null ) {
					$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
				} else {
					$invoiceNumber = '';
				}
				
				if ( $payment['amount'] >= 0 ) {
					EventLog::addEntry( self::$database, 'warning', 'purchase_payment', 'update', 'uncleared',
										'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
										numberFormat( $payment['amount'], 'currency' ) . ' ' . $payment['payment_type'] .
										' payment for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
										capitalizeWords( Filter::output( $purchase['name'] ) ) . '</a> was <span class="event_log_action">uncleared</span>' );
				} else {
					EventLog::addEntry( self::$database, 'warning', 'purchase_payment', 'update', 'uncleared',
										'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
										$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
										numberFormat( $payment['amount'] * -1, 'currency' ) . ' ' . $payment['payment_type'] .
										' rebate from <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
										capitalizeWords( Filter::output( $purchase['name'] ) ) . '</a> was <span class="event_log_action">uncleared</span>' );
				}
			}
			
			echo "Payment is now uncleared!<br /><br /><br />";
		} else {
			Diagnostics::error( 'dialog', ERROR, "Cannot update " . ucfirst( $_POST['class'] ) ." No. " . $_POST['transactionID'], "Please try again.", SYSTEM_ERROR );
		}
	}



	// display Enter Rebate Dialog, ajax function
	public static function showRebateDialog()
	{
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) )
			return;


		$class = $_POST['class'];
		$transactionID = $_POST['transactionID'];


		if ( $class == "order" ) {
			$cashFlow = "inbound";
		} else {
			$cashFlow = "outbound";
		}


		self::$database = new Database;


		$sqlQuery = 'SELECT ( balance * -1 ) AS rebate_amount FROM `' . $_POST['class'] . '` WHERE id = ' . $_POST['transactionID'];
		$resultSet = self::$database->query( $sqlQuery );
		$transaction = self::$database->getResultRow( $resultSet );
		$rebateAmount = number_format( $transaction['rebate_amount'], 2, '.', '' );
		$rebateAmountStr = numberFormat( $rebateAmount, "currency" );


		echo <<<END
			<form name="rebate_payment" method="post" action="javascript:enterRebate()" autocomplete="off">
				<input type="hidden" name="transaction_id" id="transaction_id" value="$transactionID" />
				<input type="hidden" name="rebate_amount" id="rebate_amount" value="$rebateAmount" />

				<div>
					<span class="record_label">Amount to Rebate:</span>
					<span class="record_data">$rebateAmountStr</span>
				</div>

				<hr /><br />

				<div>
					<span class="record_label">Amount Returned:</span>
					<span class="record_data">
						<input type="text" name="amount_returned" id="amount_returned" class="number" value="0.000" required="required" onfocus="data.selectField( $(this), 'float' )" onblur="data.validateField( $(this), 'float' )" style="margin-right: 8px" />
						<select name="payment_type" id="payment_type" onchange="toggleCheckInfoSection()">
							<option value="cash" selected="selected">Cash</option>
							<option value="check">Check</option>
						</select>
					</span>

					<span class="record_label">Rebate Date:</span>
					<span class="record_data"><input type="text" name="payment_date" id="payment_date" class="datepicker_no_future_date" required="required" size="30" maxlength="30" onfocus="data.selectField( $(this) )" /></span>
					<br /><br /><br /><br />
				</div>

				<div id="check_info" style="display: none">
					<br />
					<span class="record_label">Bank:</span>
					<span class="record_data"><input type="text" name="bank_name" id="bank_name" /></span>

					<span class="record_label">Branch:</span>
					<span class="record_data"><input type="text" name="branch_name" id="branch_name" /></span>

					<span class="record_label">Check Number:</span>
					<span class="record_data"><input type="text" name="check_number" id="check_number" size="30" /></span>

					<span class="record_label">Check Date:</span>
					<span class="record_data"><input type="text" name="check_date" id="check_date" class="datepicker_no_future_date" size="30" maxlength="30" onfocus="data.selectField( $(this) )" /></span>
					<br /><br /><br /><br /><br /><br />
				</div>

				<div>
					<br />
					<span class="record_label">Target Clearing Date:</span>
					<span class="record_data"><input type="text" name="clearing_date" id="clearing_date" class="datepicker_no_past_date" size="30" maxlength="30" onfocus="data.selectField( $(this) )" /></span><br />
					<br />
				</div>

				<div id="dialog_buttons">
					<input type="submit" value="Save" />
					<input type="reset" value="Reset" />
					<input type="button" value="Cancel" onclick="hideDialog()" />
				</div>
			</form>

END;
	}



	// issue rebate, ajax function
	public static function issueRebate()
	{
		// check required parameters
		if ( 	!isset( $_POST['class'] ) || !isset( $_POST['transactionID'] )
			 || !isset( $_POST['rebateAmount'] ) || !isset( $_POST['amountReturned'] )
			 || !isset( $_POST['paymentType'] ) || !isset( $_POST['paymentDate'] ) )
			return;


		self::$database = new Database();


		// get payment sequence
		$sqlQuery = "SELECT DISTINCT payment_sequence FROM " . $_POST['class'] . "_payment " .
					"WHERE " . $_POST['class'] . "_id = " . $_POST['transactionID'] . " " .
					"ORDER BY payment_sequence DESC LIMIT 0,1";
		$resultSet = self::$database->query( $sqlQuery );
		$payment = self::$database->getResultRow( $resultSet );
		$sequence = (int) $payment['payment_sequence'] + 1;


		// enter payment to table
		$_POST['amountReturned'] = Filter::input( $_POST['amountReturned'] );
		$amountReturnedNegative = $_POST['amountReturned'] * -1;		// convert to negative amount to denote rebate
		self::savePayment( $_POST['class'], $amountReturnedNegative, "NULL", $sequence );


		// reestablish database connection
		self::$database = new Database;

		
		// update transaction status and recorded balance
		$sqlQuery = "UPDATE `" . $_POST['class'] . "` SET balance = ( balance + " . $_POST['amountReturned'] . " ) " .
					"WHERE id = " . $_POST['transactionID'];
		self::$database->query( $sqlQuery );
		
		
		// log event
		if ( $_POST['class'] == "order" ) {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id, name FROM `order` ' .
						'INNER JOIN customer ON `order`.customer_id = customer.id ' .
						'WHERE `order`.id=' . $_POST['transactionID'];
			$resultSet = self::$database->query( $sqlQuery );
			$order = self::$database->getResultRow( $resultSet );
			
			if ( $order['sales_invoice'] != null ) {
				$invoiceNumber = 'SI ' . $order['sales_invoice'];
			} else {
				$invoiceNumber = 'DR ' . $order['delivery_receipt'];
			}
			
			EventLog::addEntry( self::$database, 'info', 'order_payment', 'update', 'rebate',
								'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
								$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
								numberFormat( $_POST['amountReturned'], 'currency' ) . ' ' . $_POST['paymentType'] .
								' rebate for <a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
								capitalizeWords( Filter::output( $order['name'] ) ) . '</a> was <span class="event_log_action">issued</span>' );
		} else {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id, name FROM purchase ' .
						'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
						'WHERE purchase.id=' . $_POST['transactionID'];
			$resultSet = self::$database->query( $sqlQuery );
			$purchase = self::$database->getResultRow( $resultSet );
			
			if ( $purchase['sales_invoice'] != null ) {
				$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
			} elseif ( $purchase['delivery_receipt'] != null ) {
				$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
			} else {
				$invoiceNumber = '';
			}
			
			EventLog::addEntry( self::$database, 'info', 'purchase_payment', 'update', 'rebate',
								'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
								$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
								numberFormat( $_POST['amountReturned'], 'currency' ) . ' ' . $_POST['paymentType'] .
								' rebate from <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
								capitalizeWords( Filter::output( $purchase['name'] ) ) . '</a> was <span class="event_log_action">received</span>' );
		}
				

		if ( $_POST['class'] == "order" ) {
			echo "Order No. ";
		} else {
			echo "Purchase No. ";
		}
		echo $_POST['transactionID'] . " successfully updated!<br /><br /><br />";
	}
	
	
	
	// delete payment, ajax function
	public static function deletePayment()
	{
		// check required parameters
		if (    !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] )
			 || !isset( $_POST['paymentSequence'] ) || !isset( $_POST['amountReturned'] ) )
			return;

		self::$database = new Database();
		
		
		// get details before deleting
		if ( $_POST['class'] == 'order' ) {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id AS person_id, name FROM `order` ' .
						'INNER JOIN customer ON `order`.customer_id = customer.id ' .
						'WHERE `order`.id=' . $_POST['transactionID'];
		} else {		// purchase
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id AS person_id, name FROM purchase ' .
						'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
						'WHERE purchase.id=' . $_POST['transactionID'];
		}
		$resultSet = self::$database->query( $sqlQuery );
		$transaction = self::$database->getResultRow( $resultSet );
		
		if ( $transaction['sales_invoice'] != null ) {
			$invoiceNumber = ' (SI ' . $transaction['sales_invoice'] . ')';
		} elseif ( $transaction['delivery_receipt'] != null ) {
			$invoiceNumber = ' (DR ' . $transaction['delivery_receipt'] . ')';
		} else {
			$invoiceNumber = '';
		}
		
		$sqlQuery = 'SELECT SUM(amount) AS amount, payment_type FROM ' . $_POST['class'] . "_payment " .
					'WHERE ' . $_POST['class'] . '_id=' . $_POST['transactionID'] . ' ' .
					'AND payment_sequence=' . $_POST['paymentSequence'];
		$resultSet = self::$database->query( $sqlQuery );
		$payment = self::$database->getResultRow( $resultSet );
		
		
		// delete payment
		$sqlQuery = "DELETE FROM " . $_POST['class'] . "_payment " .
					"WHERE " . $_POST['class'] . "_id = " .  $_POST['transactionID'] . " " .
					"AND payment_sequence = " . $_POST['paymentSequence'];
		self::$database->query( $sqlQuery );
		
		
		// update balance
		$sqlQuery = "UPDATE `" . $_POST['class'] . "` " .
					"SET balance = balance + " . $_POST['amountReturned'] . " " .
					"WHERE id = " . $_POST['transactionID'];
		self::$database->query( $sqlQuery );
		
		
		// log event
		if ( $_POST['class'] == "order" ) {
			if ( $payment['amount'] >= 0 ) {
				EventLog::addEntry( self::$database, 'warning', 'order_payment', 'delete', 'removed',
									'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
									$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
									numberFormat( $payment['amount'], 'currency' ) . ' ' . $payment['payment_type'] .
									' payment from <a href="view_customer_details.php?id=' . $transaction['person_id'] . '">' .
									capitalizeWords( Filter::output( $transaction['name'] ) ) . '</a> was <span class="event_log_action bad">deleted</span>' );
			} else {
				EventLog::addEntry( self::$database, 'warning', 'order_payment', 'delete', 'removed',
									'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
									$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
									numberFormat( $payment['amount'] * -1, 'currency' ) . ' ' . $payment['payment_type'] .
									' rebate for <a href="view_customer_details.php?id=' . $transaction['person_id'] . '">' .
									capitalizeWords( Filter::output( $transaction['name'] ) ) . '</a> was <span class="event_log_action bad">deleted</span>' );
			}
		} else {		// purchase
			if ( $payment['amount'] >= 0 ) {
				EventLog::addEntry( self::$database, 'warning', 'purchase_payment', 'delete', 'removed',
									'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
									$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
									numberFormat( $payment['amount'], 'currency' ) . ' ' . $payment['payment_type'] .
									' payment for <a href="view_supplier_details.php?id=' . $transaction['person_id'] . '">' .
									capitalizeWords( Filter::output( $transaction['name'] ) ) . '</a> was <span class="event_log_action bad">deleted</span>' );
			} else {
				EventLog::addEntry( self::$database, 'warning', 'purchase_payment', 'delete', 'removed',
									'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
									$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
									numberFormat( $payment['amount'] * -1, 'currency' ) . ' ' . $payment['payment_type'] .
									' rebate from <a href="view_supplier_details.php?id=' . $transaction['person_id'] . '">' .
									capitalizeWords( Filter::output( $transaction['name'] ) ) . '</a> was <span class="event_log_action bad">deleted</span>' );
			}
		}

		
		echo "Payment is now deleted!<br /><br /><br />";
	}
	
	
	
	// waive remaining balance, ajax function
	public static function waiveBalance() {
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) ) {
			return;
		}
		
		self::$database = new Database();
		
		$sqlQuery = "UPDATE `" . $_POST['class'] . "` SET " .
					"waived_balance = balance, " .
					"balance = 0.000" .
					"WHERE id = " . $_POST['transactionID'];
		self::$database->query( $sqlQuery );
		
		
		// log event
		if ( $_POST['class'] == "order" ) {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id, name, waived_balance FROM `order` ' .
				'INNER JOIN customer ON `order`.customer_id = customer.id ' .
				'WHERE `order`.id=' . $_POST['transactionID'];
			$resultSet = self::$database->query( $sqlQuery );
			$order = self::$database->getResultRow( $resultSet );

			if ( $order['sales_invoice'] != null ) {
				$invoiceNumber = 'SI ' . $order['sales_invoice'];
			} else {
				$invoiceNumber = 'DR ' . $order['delivery_receipt'];
			}

			EventLog::addEntry( self::$database, 'warning', 'order_payment', 'update', 'waived',
								'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
								$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
								numberFormat( $order['waived_balance'], 'currency' ) .
								' balance of <a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
								capitalizeWords( Filter::output( $order['name'] ) ) . '</a> was <span class="event_log_action bad">waived</span>' );
		} else {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id, name, waived_balance FROM purchase ' .
				'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
				'WHERE purchase.id=' . $_POST['transactionID'];
			$resultSet = self::$database->query( $sqlQuery );
			$purchase = self::$database->getResultRow( $resultSet );

			if ( $purchase['sales_invoice'] != null ) {
				$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
			} elseif ( $purchase['delivery_receipt'] != null ) {
				$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
			} else {
				$invoiceNumber = '';
			}

			EventLog::addEntry( self::$database, 'warning', 'purchase_payment', 'update', 'waived',
								'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
								$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
								numberFormat( $purchase['waived_balance'], 'currency' ) .
								' balance for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
								capitalizeWords( Filter::output( $purchase['name'] ) ) . '</a> was <span class="event_log_action bad">waived</span>' );
		}
		
		
		echo "All remaining balance for ";
		if ( $_POST['class'] == "order" ) {
			echo "Order No. ";
		} else {
			echo "Purchase No. ";
		}
		echo $_POST['transactionID'] . " is now waived!<br /><br /><br />";
	}
	
	
	// waive remaining balance, ajax function
	public static function undoWaiveBalance() {
		// check required parameters
		if ( !isset( $_POST['class'] ) || !isset( $_POST['transactionID'] ) ) {
			return;
		}
		
		self::$database = new Database();
		
		$sqlQuery = "UPDATE `" . $_POST['class'] . "` SET " .
					"balance = waived_balance," .
					"waived_balance = 0.000 " .
					"WHERE id = " . $_POST['transactionID'];
		self::$database->query( $sqlQuery );
		
		
		// log event
		if ( $_POST['class'] == "order" ) {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, customer_id, name, balance FROM `order` ' .
						'INNER JOIN customer ON `order`.customer_id = customer.id ' .
						'WHERE `order`.id=' . $_POST['transactionID'];
			$resultSet = self::$database->query( $sqlQuery );
			$order = self::$database->getResultRow( $resultSet );

			if ( $order['sales_invoice'] != null ) {
				$invoiceNumber = 'SI ' . $order['sales_invoice'];
			} else {
				$invoiceNumber = 'DR ' . $order['delivery_receipt'];
			}

			EventLog::addEntry( self::$database, 'info', 'order_payment', 'update', 'unwaived',
								'<span class="event_log_main_record">Order No. <a href="view_order_details.php?id=' . $_POST['transactionID'] . '">' .
								$_POST['transactionID'] . '</a> (' . $invoiceNumber . '): </span>' .
								numberFormat( $order['balance'], 'currency' ) .
								' waived balance of <a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
								capitalizeWords( Filter::output( $order['name'] ) ) . '</a> was <span class="event_log_action">returned</span>' );
		} else {
			$sqlQuery = 'SELECT sales_invoice, delivery_receipt, supplier_id, name, balance FROM purchase ' .
				'INNER JOIN supplier ON purchase.supplier_id = supplier.id ' .
				'WHERE purchase.id=' . $_POST['transactionID'];
			$resultSet = self::$database->query( $sqlQuery );
			$purchase = self::$database->getResultRow( $resultSet );

			if ( $purchase['sales_invoice'] != null ) {
				$invoiceNumber = ' (SI ' . $purchase['sales_invoice'] . ')';
			} elseif ( $purchase['delivery_receipt'] != null ) {
				$invoiceNumber = ' (DR ' . $purchase['delivery_receipt'] . ')';
			} else {
				$invoiceNumber = '';
			}

			EventLog::addEntry( self::$database, 'info', 'purchase_payment', 'update', 'unwaived',
								'<span class="event_log_main_record">Purchase No. <a href="view_purchase_details.php?id=' . $_POST['transactionID'] . '">' .
								$_POST['transactionID'] . '</a>' . $invoiceNumber . ': </span>' .
								numberFormat( $purchase['balance'], 'currency' ) .
								' waived balance for <a href="view_supplier_details.php?id=' . $purchase['supplier_id'] . '">' .
								capitalizeWords( Filter::output( $purchase['name'] ) ) . '</a> was <span class="event_log_action">returned</span>' );
		}
		
		
		echo "Waived balance for ";
		if ( $_POST['class'] == "order" ) {
			echo "Order No. ";
		} else {
			echo "Purchase No. ";
		}
		echo $_POST['transactionID'] . " is now removed!<br /><br /><br />";
	}
}
?>

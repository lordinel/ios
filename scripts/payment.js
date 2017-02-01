// payment.js
// Complementary script for Payment class (Payment.php)



// ---------------------------------------------------------------------------------------------------------------
// class Payment
// ---------------------------------------------------------------------------------------------------------------
function Payment()					// constructor
{
	this.totalSIDRamount = 0;		// total SI/DR amount
	this.totalNetAmount = 0;		// total net amount
	this.vat = 0;					// value added tax
	
	this.installmentRowCount = 1;	// count for installment periods
	this.maxInstallmentPeriod = 0;	// count for maximum installment period
}



// ---------------------------------------------------------------------------------------------------------------
// set percentage of value added tax
// ---------------------------------------------------------------------------------------------------------------
Payment.prototype.setVAT = function( vat )
{
	var vatStr = vat + '';
	var length = vatStr.length;
	
	if ( vatStr.charAt( length - 1 ) == '%' )
		this.vat = parseFloat( vatStr.substring( 0, length - 1 ) ) / 100;
	else
		this.vat = parseFloat( vat ) / 100;
}



// ---------------------------------------------------------------------------------------------------------------
// set maximum number of installment rows
// ---------------------------------------------------------------------------------------------------------------
Payment.prototype.setMaxInstallmentPeriod = function( maxInstallment )
{
	this.maxInstallmentPeriod = maxInstallment;
}



// ---------------------------------------------------------------------------------------------------------------
// calculate total amount to pay in Add Order page
// ---------------------------------------------------------------------------------------------------------------
Payment.prototype.calculateAmount = function( row, resetDependentFields )
{
	if ( resetDependentFields == null ) {
		resetDependentFields = false;
	}
	
	
	if ( row != null ) {
		// get input values
		var quantity = parseInt( $('#item_quantity_'+row).val() );
		if ( isNaN( quantity ) || quantity < 0 ) {
			quantity = 0;
		}

		var sidrPrice = parseFloat( $('#item_sidr_price_'+row).val() );
		if ( isNaN( sidrPrice ) || sidrPrice < 0 ) {
			sidrPrice = 0;
		}

		var netPrice = parseFloat( $('#item_net_price_'+row).val() );
		if ( isNaN( netPrice ) || netPrice < 0 ) {
			netPrice = 0;
		}

		
		// get previous values
		var previousSIDRsubtotal = parseFloat( $('#item_sidr_subtotal_'+row).val() );
		var previousNetSubtotal = parseFloat( $('#item_net_subtotal_'+row).val() );
		
		
		// calculate subtotal
		var newSIDRsubtotal = sidrPrice * quantity;
		$('#item_sidr_subtotal_'+row).val( newSIDRsubtotal.toFixed( 3 ) );
		
		var newNetSubtotal = netPrice * quantity;
		$('#item_net_subtotal_'+row).val( newNetSubtotal.toFixed( 3 ) );


		// calculate total amounts
		if ( newSIDRsubtotal > previousSIDRsubtotal ) {
			this.totalSIDRamount = this.totalSIDRamount + ( newSIDRsubtotal - previousSIDRsubtotal );		// add to total amount
		} else {
			this.totalSIDRamount = this.totalSIDRamount - ( previousSIDRsubtotal - newSIDRsubtotal );		// deduct to total amount
		}

		$("#total_sidr_amount" ).val( this.totalSIDRamount.toFixed( 3 ) );
		
		if ( newNetSubtotal > previousNetSubtotal ) {
			this.totalNetAmount = this.totalNetAmount + ( newNetSubtotal - previousNetSubtotal );		// add to total amount
		} else {
			this.totalNetAmount = this.totalNetAmount - ( previousNetSubtotal - newNetSubtotal );		// deduct to total amount
		}

		$("#total_net_amount" ).val( this.totalNetAmount.toFixed( 3 ) );

	} else {
		var queryMode;
		
		if ( $('#order_query_mode') != null ) {
			queryMode = $('#order_query_mode').val();
		} else if ( $('#purchase_query_mode') != null ) {
			queryMode = $('#purchase_query_mode').val();
		}

		/*if ( queryMode == "new" )
		{
			totalAmount = parseFloat( $("#total_amount" ).val() );
			if ( isNaN( totalAmount ) )
				totalAmount = 0;
		}*/
	}


	// fill up tax fields
	var totalSales = this.totalSIDRamount / ( 1 + this.vat );
	$('#total_sales').val( totalSales.toFixed( 3 ) );

	valueAddedTax = this.totalSIDRamount - totalSales;
	$('#value_added_tax').val( valueAddedTax.toFixed( 3 ) );
	

	// enable/disable withholding tax
	if ( parseFloat( $('#total_sales').val() ) > 0.000 ) {
		$('#withholding_tax').attr( 'disabled', '' );
	} else {
		$('#withholding_tax').attr( 'disabled', 'disabled' );
		
		if ( resetDependentFields == true )
			$('#withholding_tax').val( '0.000' );
	}
	
	var withholdingTax = parseFloat( $('#withholding_tax').val() );
	if ( isNaN( withholdingTax ) ) {
		withholdingTax = 0;
	}
	

	var totalNetAmount = this.totalNetAmount - withholdingTax;
	if ( totalNetAmount < 0 ) {
		totalNetAmount = 0;
	}


	$('#sidr_amount').val( this.totalSIDRamount.toFixed( 3 ) );
	$('#net_amount').val( totalNetAmount.toFixed( 3 ) );
	

	// check credit limit for customer
	if ( $( "#order_id" ).val() != null ) {     // for orders only
		checkCreditLimit();
	}



	// installment calculation
	if ( $('#payment_due') != null ) {			// payment fieldset is existing
		// copy to payment info fieldset
		$('#sidr_payment_due').val( $('#sidr_amount').val() );
		$('#payment_due').val( $('#net_amount').val() );


		// calculate installment
		if ( $('#payment_term').val() == "installment" )
		{
			if ( parseFloat( $('#payment_due').val() ) <= 0.000 ) {
				if ( resetDependentFields == true )
					$('#interest').val( '0.000' );
			}

			// compute interest			
			var interest = parseFloat( $('#interest').val() );
			if ( isNaN( interest ) ) {
				interest = 0;
			}

			var sidrAmountPlusInterest = this.totalSIDRamount + interest;
			$('#sidr_amount_plus_interest').val( sidrAmountPlusInterest.toFixed( 3 ) );

			var toPayPlusInterest = totalNetAmount + interest;
			var toPayPlusInterestOrig = toPayPlusInterest;
			$('#net_amount_plus_interest').val( toPayPlusInterest.toFixed( 3 ) );


			// compute installment parts
			var installmentAmountTotal = 0.000;
			var installmentAmount;

			for ( var i = 1; i <= this.installmentRowCount; i++ ) {
				installmentAmount = parseFloat( $('#installment_amount_'+i).val() );

				if ( isNaN( installmentAmount ) ) {
					installmentAmount = 0;
				}

				installmentAmountTotal = installmentAmountTotal + parseFloat( installmentAmount.toFixed( 3 ) );
			}

			installmentAmountTotal = parseFloat( installmentAmountTotal.toFixed( 3 ) );

			if ( row == null && resetDependentFields == true && installmentAmountTotal > toPayPlusInterest ) {
				var newValue = parseFloat( $('#installment_amount_'+this.installmentRowCount).val() ) - ( installmentAmountTotal - toPayPlusInterest );
				$('#installment_amount_'+this.installmentRowCount).val( newValue.toFixed( 3 ) );
			}

			toPayPlusInterest = toPayPlusInterest - installmentAmountTotal;

			if ( toPayPlusInterest < 0 ) {
				if ( row != null ) {
					// clear installment amount
					if ( resetDependentFields == true ) {
						for ( var i = 1; i <= this.installmentRowCount; i++ ) {
							$('#installment_amount_'+i).val( '0.000' );
						}

						while ( this.installmentRowCount > 1 ) {
							this.removeInstallmentRow();
						}

						$('#installment_remaining').val( toPayPlusInterestOrig.toFixed( 3 ) );
					}
				} else {
					//var newValue = parseFloat( $('#net_amount_plus_interest').val() ) - installmentAmountTotal;
					//$('#installment_amount_'+this.installmentRowCount).val( newValue.toFixed( 2 ) );

					toPayPlusInterest = 0;
					$('#installment_remaining').val( toPayPlusInterest.toFixed( 3 ) );
				}
			} else if ( toPayPlusInterest == 0 && toPayPlusInterestOrig == 0 ) {
				if ( resetDependentFields == true ) {
					while ( this.installmentRowCount > 1 ) {
						this.removeInstallmentRow();
					}

					$('#installment_amount_1').val( '0.000' );
					$('#installment_remaining').val( '0.000' );
				}
			} else {
				$('#installment_remaining').val( toPayPlusInterest.toFixed( 3 ) );	// remaining amount after deducting the installment parts
			}


			// show cleared flag
			if ( parseFloat( $('#installment_remaining').val() ) == 0.000 )
			{
				$('#installment_remaining').css( 'color', goodInputStyle );
				$('#clear_label').show();
				$('#add_installment_row_link').hide();
				$('#installment_row_link_separator').hide();
			}
			else
			{
				$('#installment_remaining').css( 'color', badInputStyle );
				$('#clear_label').hide();
				$('#add_installment_row_link').show();
				if ( this.installmentRowCount > 1 )
					$('#installment_row_link_separator').show();
			}
		}
		else
			$('#installment_remaining').val( '0.000' );
	}
	//}
}



// ---------------------------------------------------------------------------------------------------------------
// toggle display of installment section
// ---------------------------------------------------------------------------------------------------------------
Payment.prototype.toggleInstallmentSection = function()
{
	if ( $('#payment_term').val() == "full" )		// full payment, hide section
	{
		$('#interest_section').hide('slow');
		$('#installment_plan_section').hide('slow');
		$('#interest').val( '0.000' );
		
		for ( var i = 1; i <= this.installmentRowCount; i++ )
		{
			$('#installment_amount_'+i).val( '0.000' );
			$('#installment_date_'+i).val( '' );
			
			if ( i > 1 )
				$('#installment_row_'+i).hide();
			
			$('#installment_amount_'+i).attr( 'disabled', 'disabled' );
			$('#installment_date_'+i).attr( 'disabled', 'disabled' );
		}
		
		$('#installment_row_link_separator').hide();
		$('#remove_installment_row_link').hide();
		
		this.installmentRowCount = 1;
	}
	else											// installment, display section
	{
		$('#interest_section').show('slow');
		$('#installment_plan_section').show('slow');
		$('#installment_amount_1').attr( 'disabled', '' );
		$('#installment_date_1').attr( 'disabled', '' );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// add row for installment period
// ---------------------------------------------------------------------------------------------------------------
Payment.prototype.addInstallmentRow = function()
{
	this.installmentRowCount++;

	// enable field
	$('#installment_amount_'+this.installmentRowCount).attr( 'disabled', '' );
	$('#installment_date_'+this.installmentRowCount).attr( 'disabled', '' );
	
	// display hidden row
	$('#installment_row_'+this.installmentRowCount).show( 'fast' );

	// toggle link and separator
	if ( this.installmentRowCount == 2 )
	{
		$('#installment_row_link_separator').show();
		$('#remove_installment_row_link').show();
	}
	else if ( this.installmentRowCount == this.maxInstallmentPeriod )
	{
		$('#add_installment_row_link').hide();
		$('#installment_row_link_separator').hide();
	}
}



// ---------------------------------------------------------------------------------------------------------------
// remove row for installment period
// ---------------------------------------------------------------------------------------------------------------
Payment.prototype.removeInstallmentRow = function()
{
	// hide last row
	$('#installment_row_'+this.installmentRowCount).hide( 'fast' );

	// disable field
	$('#installment_amount_'+this.installmentRowCount).attr( 'disabled', 'disabled' );
	$('#installment_date_'+this.installmentRowCount).attr( 'disabled', 'disabled' );

	// reset values
	$('#installment_amount_'+this.installmentRowCount).val( '0.000' );
	$('#installment_date_'+this.installmentRowCount).val( '' );

	// toggle link and separator
	if ( this.installmentRowCount == this.maxInstallmentPeriod )
	{
		$('#add_installment_row_link').show();
		$('#installment_row_link_separator').show();
	}
	else if ( this.installmentRowCount == 2 )
	{
		$('#installment_row_link_separator').hide();
		$('#remove_installment_row_link').hide();
	}

	this.installmentRowCount--;
}



// ---------------------------------------------------------------------------------------------------------------
// check installment amount if greater than remaining balance
// ---------------------------------------------------------------------------------------------------------------
Payment.prototype.validateInstallmentAmount = function( row )
{
	if ( parseFloat( $('#installment_amount_'+row).val() ) > parseFloat( $('#installment_remaining').val() ) )
	{
		var sum = 0;
		for ( var i = 1; i <= this.installmentRowCount; i++ )
		{
			if ( i != row  )
				sum = sum + parseFloat( $('#installment_amount_'+i).val() );
		}


		if ( parseFloat( $('#installment_amount_'+row).val() ) > ( parseFloat( $('#net_amount_plus_interest').val() ) - sum ) )
		{
			var newValue = parseFloat( $('#net_amount_plus_interest').val() ) - sum;
			$('#installment_amount_'+row).val( newValue.toFixed( 3 ) );
		}
	}
}




// toggle display of check info section in Enter Payment dialog
function toggleCheckInfoSection()
{
	if ( document.getElementById( "payment_type" ).value == "check" )
		$('#check_info').slideDown('slow');
	else
		$('#check_info').slideUp('slow');
}



// mark a payment as cleared
function clearPaymentCommit( className, transactionID, paymentSequence, balance, pendingClearanceCount, isRebatePayment )
{
	if ( isRebatePayment == null ) {
		isRebatePayment = false;
	}
	
	
	if ( ajaxFromDialog( "Clear Payment", "Payment::clearPayment", "class=" + className + "&transactionID=" + transactionID + "&paymentSequence=" + paymentSequence + '&balance=' + balance ) )
	{
		if ( !isRebatePayment ) {
			if ( balance <= 0 ) {
				if ( pendingClearanceCount > 1 ) {
					paymentStatus = "fully-paid-not-cleared";
				} else {
					paymentStatus = "fully-paid";
				}
			} else {
				paymentStatus = "partially-paid";
			}
		} else {
			if ( pendingClearanceCount == 0 ) {
				paymentStatus = "fully-paid";
			} else {
				paymentStatus = "fully-paid-not-cleared";
			}
		}
		
		if ( className == "order" )
		{
			showOrderStatusLabel();
			reorganizeOrderDetailsTasks();
		}
		else
		{
			showPurchaseStatusLabel();
			reorganizePurchaseDetailsTasks();
		}
	}
	
	// redispay payment info section
	ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=' + className + '&transactionID=' + transactionID );
}



// undo clearing of payment
function undoClearPaymentCommit( className, transactionID, paymentSequence, balance, pendingClearanceCount, isRebatePayment )
{
	if ( isRebatePayment == null ) {
		isRebatePayment = false;
	}
	
	
	if ( ajaxFromDialog( "Clear Payment", "Payment::undoClearPayment", "class=" + className + "&transactionID=" + transactionID + "&paymentSequence=" + paymentSequence + '&balance=' + balance ) )
	{
		if ( !isRebatePayment ) {
			if ( balance <= 0 ) {
				paymentStatus = "fully-paid-not-cleared";
			} else {
				paymentStatus = "partially-paid";
			}
		} else {
			if ( pendingClearanceCount == 0 ) {
				paymentStatus = "fully-paid";
			} else {
				paymentStatus = "fully-paid-not-cleared";
			}
		}
		
		
		if ( className == "order" )
		{
			showOrderStatusLabel();
			reorganizeOrderDetailsTasks();
		}
		else
		{
			showPurchaseStatusLabel();
			reorganizePurchaseDetailsTasks();
		}
	}
	
	// redispay payment info section
	ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=' + className + '&transactionID=' + transactionID );
}



// delete payment
function deletePaymentCommit( className, transactionID, paymentSequence, amountReceivable, balance, amountReturned, pendingClearanceCount, isRebatePayment )
{
	if ( isRebatePayment == null ) {
		isRebatePayment = false;
	}
	
	
	if ( ajaxFromDialog( "Delete Payment", "Payment::deletePayment", "class=" + className + "&transactionID=" + transactionID + "&paymentSequence=" + paymentSequence + "&amountReturned=" + amountReturned ) )
	{
		if ( !isRebatePayment ) {
			if ( balance + amountReturned <= 0 ) {
				if ( pendingClearanceCount == 1 ) {
					paymentStatus = "fully-paid";
				} else {
					paymentStatus = "fully-paid-not-cleared";
				}
			} else if ( balance + amountReturned >= amountReceivable ) {
				paymentStatus = "no-payment";
			} else {
				paymentStatus = "partially-paid";
			}
		} else {
			paymentStatus = "fully-paid";
		}
		
		if ( className == "order" )
		{
			showOrderStatusLabel();
			reorganizeOrderDetailsTasks();
		}
		else
		{
			showPurchaseStatusLabel();
			reorganizePurchaseDetailsTasks();
		}
		
		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=' + className + '&transactionID=' + transactionID );
	}
}

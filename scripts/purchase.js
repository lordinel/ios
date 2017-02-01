// purchase.js
// Complementary script for Purchase class (Order.php)




// ---------------------------------------------------------------------------------------------------------------
// class Order
// ---------------------------------------------------------------------------------------------------------------
function Purchase() {}
Purchase.prototype = new Transaction();		// inherit Transaction class



// ---------------------------------------------------------------------------------------------------------------
// load events on Add/Edit Purchase form
// ---------------------------------------------------------------------------------------------------------------
Purchase.prototype.loadFormEvents = function()
{
	var obj = this;				// this handler
	
	Transaction.prototype.loadFormEvents.call(this);		// call parent method
	
	
	// load additional events
	
	$('#invoice_type').change( function() {
		obj.toggleTaxFieldsDisplay();
		checkTrackingNumber( 'Purchase' );
	});
	
	
	$('#tracking_number').bind({
		focus: function() {
			obj.data.selectField( $(this) );
		},
	    keyup: function() {
		    checkTrackingNumber( 'Purchase' );
	    },
		blur: function() {
			obj.data.validateField( $(this) );
			showTrackingNumberDialog( 'Purchase' );
		}
	});
	
	
	$('#purchase_date').click( function() {
		obj.data.selectField( $(this) );
	});
	
	
	$('#supplier_po_number').focus( function() {
		obj.data.selectField( $(this) );
	});
	
	
	$('.item_brand').each( function( row ) {
		row = row + 1;
		$(this).change( function() {
			ajax( $(this).type, 'item_model_'+row, 'innerHTML', 'Inventory::loadModelList', 'brandID='+$(this).val()+'&class=purchase' );
			obj.validateItemBrand( row );
			obj.validateItemModel( row );
			obj.validateItemPrice( row );
			obj.payment.calculateAmount( row, true );
		});
	});
	
	
	$('.item_model').each( function( row ) {
		row = row + 1;
		$(this).change( function() {
			obj.inventory.loadPurchasePrice( row, $(this).val() );
			obj.validateItemModel( row, true );
			obj.validateItemPrice( row );
			obj.payment.calculateAmount( row, true );
		});
	});
	
	
	$('.item_quantity').each( function( row ) {
		row = row + 1;
		$(this).bind({
			focus: function() {
				obj.data.selectField( $(this), 'int' );
			},
			keyup: function() {
				obj.payment.calculateAmount( row );
			},
			blur: function() {
				obj.data.validateField( $(this), 'int' );
				obj.payment.calculateAmount( row, true );
			}
		});
	});
}




// ----------------------------------------------------------------
// Purchase Processing Group
// ----------------------------------------------------------------

var orderStatus;		  // pending, delivered, canceled, cleared
var paymentStatus;		  // no-payment, partially-paid, fully-paid-not-cleared, fully-paid
var transactionType;	  // delivery, pick-up
var editOrderExtraParam;  // GET parameter to show/hide order items and payment sections in Edit Order Page 


// set purchase status label
function showPurchaseStatusLabel()
{
	var spanLabel = document.getElementById( "order_status_span" );


	if ( orderStatus == "canceled" )
		spanLabel.innerHTML = "Canceled";
	else
	{
		// delivery status
		if ( orderStatus == "not-delivered" )
		{
			if ( transactionType == "delivery" )
				spanLabel.innerHTML = "<span class=\"bad\">Not yet delivered</span>";
			else
				spanLabel.innerHTML = "<span class=\"bad\">Not yet picked-up</span>";
		}
		else if ( orderStatus == "partially-delivered" )
		{
			if ( transactionType == "delivery" )
				spanLabel.innerHTML = "Partially delivered";
			else
				spanLabel.innerHTML = "Partially picked-up";
		}
		else
		{
			if ( transactionType == "delivery" )
				spanLabel.innerHTML = "<span class=\"good\">Delivered</span>";
			else
				spanLabel.innerHTML = "<span class=\"good\">Picked-up</span>";
		}


		spanLabel.innerHTML = spanLabel.innerHTML + " | ";


		switch( paymentStatus )
		{
			case "no-payment":
				spanLabel.innerHTML = spanLabel.innerHTML + "<span class=\"bad\">Not yet paid</span>";
				break;
			case "partially-paid":
				spanLabel.innerHTML = spanLabel.innerHTML + "Partially paid";
				break;
			case "fully-paid-not-cleared":
				spanLabel.innerHTML = spanLabel.innerHTML + "<span class=\"good\">Fully paid</span>";
				
				if ( orderStatus == "all-delivered" )
					spanLabel.innerHTML = spanLabel.innerHTML + " | Pending payment clearance";
				break;
			case "fully-paid":
				spanLabel.innerHTML = spanLabel.innerHTML + "<span class=\"good\">Fully paid</span>";
				
				if ( orderStatus == "cleared" )
					spanLabel.innerHTML = spanLabel.innerHTML + " | <span class=\"best\">Cleared</span>";
				else if ( orderStatus == "all-delivered" )
					spanLabel.innerHTML = spanLabel.innerHTML + " | Pending order clearance";
				break;
		}
	}
}



// reorganize task list
function reorganizePurchaseDetailsTasks()
{
	if ( editOrderExtraParam == null ) {
		editOrderExtraParam = $('#task_edit_order > a').attr('href');
	}

	if ( orderStatus != 'not-delivered' || paymentStatus != 'no-payment' ) {
		$('#task_edit_order > a').attr('href',editOrderExtraParam+'&item_editable=false');
		$('#task_cancel_order').hide();
	}
	else {
		$('#task_edit_order > a').attr('href',editOrderExtraParam);
		$('#task_cancel_order').show();
	}

	if ( orderStatus != 'canceled' && orderStatus != 'cleared' ) {
		$('#task_edit_order').show();
	} else {
		$('#task_edit_order').hide();
	}


	if ( orderStatus != 'canceled' && ( paymentStatus == 'no-payment' || paymentStatus == 'partially-paid' ) )
		$('#task_enter_payment').show();
	else
		$('#task_enter_payment').hide();


	if ( orderStatus == 'all-delivered' )
	{
		if ( paymentStatus == 'fully-paid-not-cleared' )
		{
			$('#task_mark_as_cleared_notice').show();
			$('#task_mark_as_cleared').hide();
		}
		else if ( paymentStatus == 'fully-paid' )
		{
			$('#task_mark_as_cleared_notice').hide();
			$('#task_mark_as_cleared').show();
		}
		else
		{
			$('#task_mark_as_cleared_notice').hide();
			$('#task_mark_as_cleared').hide();
		}
	}
	else
	{
		$('#task_mark_as_cleared_notice').hide();
		$('#task_mark_as_cleared').hide();
	}
	
	
	if ( orderStatus == "canceled" ) {
		$('#task_undo_cancel_order').show();
	} else {
		$('#task_undo_cancel_order').hide();
	}
	
	
	if ( orderStatus == "cleared" ) {
		$('#task_unclear_order').show();
	} else {
		$('#task_unclear_order').hide();
	}


	if ( orderStatus == "canceled" || orderStatus == "cleared" )
	{
		$('.item_delivery_link').each( function( row ) {
			$(this).html( $(this).children().html() );
		});
	}
	
	
	if ( orderStatus == "cleared" ) {
		$('#content').toggleClass( 'cleared_order' );
	} else if ( orderStatus == "canceled" ) {
		$('#content').toggleClass( 'canceled_order' );
	}
}



// mark order as cleared
function markAsClearedCommit( purchaseID )
{
	if ( ajaxFromDialog( "Change Status", "Purchase::markAsCleared", "purchaseID=" + purchaseID ) )
	{
		ajax( null, null, 'inline', "Purchase::getPurchaseInfo", 'purchaseID=' + purchaseID );
		var purchase = JSON.parse( ajaxResponseText );
		
		orderStatus = "cleared";
		showPurchaseStatusLabel( );
		reorganizePurchaseDetailsTasks();
		
		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=purchase&transactionID=' + purchaseID );
	}
}



// unclear order
function unclearOrderCommit( purchaseID )
{
	if ( ajaxFromDialog( "Change Status", "Purchase::undoClear", "purchaseID=" + purchaseID ) )
	{
		orderStatus = "all-delivered";
		showPurchaseStatusLabel( );
		reorganizePurchaseDetailsTasks();
		
		// reload page
		window.setTimeout( function() {
			window.location.reload();
		}, 2500 );
	}
}



// cancel order
function cancelOrderCommit( purchaseID )
{
	if ( ajaxFromDialog( "Change Status", "Purchase::cancel", "purchaseID=" + purchaseID ) )
	{
		ajax( null, null, 'inline', "Purchase::getPurchaseInfo", 'purchaseID=' + purchaseID );
		var purchase = JSON.parse( ajaxResponseText );

		orderStatus = "canceled";
		showPurchaseStatusLabel();
		reorganizePurchaseDetailsTasks();
		
		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=purchase&transactionID=' + purchaseID );
	}
}



// undo cancel order
function undoCancelOrderCommit( purchaseID )
{
	if ( ajaxFromDialog( "Change Status", "Purchase::undoCancel", "purchaseID=" + purchaseID ) )
	{
		orderStatus = "not-delivered";
		showPurchaseStatusLabel();
		reorganizePurchaseDetailsTasks();
		
		// reload page
		window.setTimeout( function() {
			window.location.reload();
		}, 2500 );
	}
}



// enter payment
function enterPayment()
{
	// required parameters
	transactionID	= document.getElementById( "purchase_id" ).value;
	transactionType	= document.getElementById( "transaction_type" ).value;
	totalToPay		= document.getElementById( "total_amount_pending" ).value;

	// purchase-specific parameters
	totalAmount			= parseFloat( document.getElementById( "total_amount" ).value );
	status				= document.getElementById( "status" ).value;


	// input
	amountReceived	= parseFloat( document.getElementById( "amount_received" ).value );
	
	if ( amountReceived <= 0 ) {
		alert( 'Input Error:\nThe amount you entered is invalid.\n\nPlease correct the amount paid.' );
		$('#amount_received').select();
		return;
	} else if ( amountReceived > totalToPay ) {
		alert( 'Input Error:\nThe amount you entered is greater than the amount to pay.\n\nPlease correct the amount paid.' );
		$('#amount_received').select();
		return;
	}


	if ( ajaxFromDialog( "Enter payment for Purchase No. " + transactionID, "Payment::save",
						 "class=purchase" +
						 "&transactionID=" +		transactionID +
						 "&totalToPay=" +			totalToPay +
						 "&toPay=" +				document.getElementById( "amount_pending" ).value +
						 "&paymentScheduleID=" +	document.getElementById( "payment_schedule_id" ).value +
						 "&amountReceived=" +		amountReceived +
						 "&paymentDate=" +			document.getElementById( "payment_date" ).value +
						 "&paymentType=" +			document.getElementById( "payment_type" ).value +
						 "&receiptNumber=" +		document.getElementById( "receipt_number" ).value +
						 "&bankName=" +				document.getElementById( "bank_name" ).value +
						 "&branchName=" +			document.getElementById( "branch_name" ).value +
						 "&checkNumber=" +			document.getElementById( "check_number" ).value +
						 "&checkDate=" +			document.getElementById( "check_date" ).value +
						 "&clearingDate=" +			document.getElementById( "clearing_date" ).value +
						 "&remarks=" +				document.getElementById( "remarks" ).value ) )
	{
		// update status label and task list
		if ( amountReceived >= totalToPay )
		{
			// mark order as fully paid
			paymentStatus = "fully-paid-not-cleared";
			showPurchaseStatusLabel();
			reorganizePurchaseDetailsTasks();
		}
		else if ( amountReceived > 0 )
		{
			paymentStatus = "partially-paid";
			showPurchaseStatusLabel();
			reorganizePurchaseDetailsTasks();
		}

		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=purchase&transactionID=' + transactionID );
	}
}



// enter rebate
function enterRebate()
{
	// required parameters
	transactionID  = $('#transaction_id').val();
	rebateAmount   = $('#rebate_amount').val();
	
	// input
	amountReturned = parseFloat( $('#amount_returned').val() );
	
	if ( amountReturned <= 0 ) {
		alert( 'Input Error:\nThe amount you entered is invalid.\n\nPlease correct the amount returned.' );
		$('#amount_returned').select();
		return;
	} else if ( amountReturned > rebateAmount ) {
		alert( 'Input Error:\nThe amount you entered is greater than the amount to rebate.\n\nPlease correct the amount returned.' );
		$('#amount_returned').select();
		return;
	}

	if ( ajaxFromDialog( "Enter rebate for Purchase No. " + transactionID, "Payment::issueRebate",
						 "class=purchase" +
						 "&transactionID=" +		transactionID +
						 "&rebateAmount=" +			rebateAmount +
						 "&amountReturned=" +		amountReturned +
						 "&paymentType=" +			$('#payment_type').val() +
						 "&paymentDate=" +			$('#payment_date').val() +
						 "&bankName=" +				$('#bank_name').val() +
						 "&branchName=" +			$('#branch_name').val() +
						 "&checkNumber=" +			$('#check_number').val() +
						 "&checkDate=" +			$('#check_date').val() +
						 "&clearingDate=" +			$('#clearing_date').val() ) )
	{
		// update status label and task list
		/*if ( amountReturned < rebateAmount ) {
			paymentStatus = "fully-paid-not-cleared";
			reorganizeOrderDetailsTasks();
		}*/

		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=purchase&transactionID=' + transactionID );
	}
}

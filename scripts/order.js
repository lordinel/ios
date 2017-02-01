// order.js
// Complementary script for Order class (Order.php)



// ---------------------------------------------------------------------------------------------------------------
// class Order
// ---------------------------------------------------------------------------------------------------------------
function Order() {}
Order.prototype = new Transaction();		// inherit Transaction class



// ---------------------------------------------------------------------------------------------------------------
// load events on Add/Edit Order form
// ---------------------------------------------------------------------------------------------------------------
Order.prototype.loadFormEvents = function()
{
	var obj = this;				// this handler
	
	Transaction.prototype.loadFormEvents.call(this);		// call parent method
	
	
	// load additional events
	
	$('#invoice_type').change( function() {
		obj.toggleTaxFieldsDisplay();
		checkTrackingNumber( 'Order' );
	});
	
	
	$('#tracking_number').bind({
		focus: function() {
			obj.data.selectField( $(this) );
		},
		keyup: function() {
			checkTrackingNumber( 'Order' );
		},
		blur: function() {
			obj.data.validateField( $(this) );
			showTrackingNumberDialog( 'Order' );
		}
	});
	
	
	$('.item_brand').each( function( row ) {
		row = row + 1;
		$(this).change( function() {
			ajax( $(this).type, 'item_model_'+row, 'innerHTML', 'Inventory::loadModelList', 'brandID='+$(this).val()+'&class=order' );
			obj.validateItemBrand( row );
			obj.validateItemModel( row );
			obj.validateItemPrice( row );
			obj.payment.calculateAmount( row, true );
		});
	});
	
	
	$('.item_model').each( function( row ) {
		row = row + 1;
		$(this).change( function() {
			obj.inventory.loadSellingPriceAndStock( row, $(this).val(), ( this.orderID != null ? this.orderID : 0 ) );
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
				obj.checkStock( row );
			},
			blur: function() {
				obj.data.validateField( $(this), 'int' );
				obj.payment.calculateAmount( row, true );
				//obj.showStockDialog( row );
			}
		});
	});
}



// ---------------------------------------------------------------------------------------------------------------
// check if quantity entered is greater than stock count
// ---------------------------------------------------------------------------------------------------------------
Order.prototype.checkStock = function( row )
{
	var quantity = parseInt( $('#item_quantity_'+row).val() );
	var	maxQuantity = parseInt( $('#item_max_quantity_'+row).val() );
	
	if ( quantity > maxQuantity )
	{
		$('#item_quantity_'+row).css( 'color', badInputStyle );
		return false;
	}
	else
	{
		$('#item_quantity_'+row).css( 'color', goodInputStyle );
		return true;
	}
}



// ---------------------------------------------------------------------------------------------------------------
// display a warning if the quantity entered is greater than stock count
// ---------------------------------------------------------------------------------------------------------------
Order.prototype.showStockDialog = function( row )
{
	if ( this.checkStock( row ) == false )
	{
		var	maxQuantity = parseInt( $('#item_max_quantity_'+row).val() );
		
		showDialog('Out of Stock',
				   '<b>Notice:</b> The quantity you entered exceeds the available, unreserved supplies for this item.<br /><br />' +
				   'The number of stocks currently unreserved is <b>' + maxQuantity + '</b><br /><br />' +
				   '<b>If you want to proceed, be sure to purchase supplies after saving this order.</b><br /><br />' +
				   'Do you want to continue?<br /><br />' +
				   '<div id=\"dialog_buttons\">' +
				   '<input type=\"button\" value=\"Yes\" onclick=\"hideDialog()\" />' +
				   '<input type=\"button\" value=\"No\" onclick=\"resetQuantity(\'' + row + '\'), hideDialog()\" />' +
				   '</div>', 'warning');
	}
}



// ---------------------------------------------------------------------------------------------------------------
// enable required but disabled fields before submitting form, and perform some checks on required fields
// ---------------------------------------------------------------------------------------------------------------
Order.prototype.validateInputForm = function( isItemEditable )
{
	// check credit limit
	if ( $('#order_id').val() != null )
	{
		if ( $( "#customer_query_mode" ).val() != "new" )
		{
			if ( parseFloat( $('#net_amount').val() ) > parseFloat( $('#remaining_credits').val() ) )
			{
				showDialog('Credits Limit Exceeded',
						   '<b>Notice:</b> This customer already exceeded the remaining credits.<br /><br />' +
						   'Remaining credits: Php ' + $('#remaining_credits').val() + '<br />' +
						   'Amount to pay for this order: Php ' + $('#net_amount').val() + '<br /><br />' +
						   '<div id=\"dialog_buttons\">' +
						   '<input type=\"button\" value=\"Close\" onclick=\"hideDialog()\" />' +
						   '</div>', 'error');
				return false;
			}
		}
		else
		{
			if ( parseFloat( $('#net_amount').val() ) > parseFloat( $('#credit_limit').val() ) )
			{
				showDialog('Credits Limit Exceeded',
						   '<b>Notice:</b> This customer already exceeded the credit limit.<br /><br />' +
						   'Credit limit: Php ' + $('#credit_limit').val() + '<br />' +
						   'Amount to pay for this order: Php ' + $('#net_amount').val() + '<br /><br />' +
						   '<div id=\"dialog_buttons\">' +
						   '<input type=\"button\" value=\"Close\" onclick=\"hideDialog()\" />' +
						   '</div>', 'error');
				return false;
			}
		}
	}
	
	
	return Transaction.prototype.validateInputForm.call(this, isItemEditable);
}





/***********************************
 Non-member functions
***********************************/

// ---------------------------------------------------------------------------------------------------------------
// reset quantity field to zero
// ---------------------------------------------------------------------------------------------------------------
function resetQuantity( row )
{
	$('#item_quantity_'+row).val( '0' );
	$('#item_quantity_'+row).css( 'color', goodInputStyle );
	$('#item_quantity_'+row).focus();
}




// ----------------------------------------------------------------
// Order Details Processing Group
// ----------------------------------------------------------------

var orderStatus;		  // not-delivered, partially-delivered, all-delivered, canceled, cleared
var paymentStatus;		  // no-payment, partially-paid, fully-paid-not-cleared, fully-paid
var transactionType;	  // delivery, pick-up
var editOrderExtraParam;  // GET parameter to show/hide order items and payment sections in Edit Order Page 


// set purchase status label
function showOrderStatusLabel()
{
	if ( document.getElementById( "order_status_span" ) == null ) {
		return;
	}
	
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
function reorganizeOrderDetailsTasks()
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
function markAsClearedCommit( orderID )
{
	if ( ajaxFromDialog( "Change Status", "Order::markAsCleared", "orderID=" + orderID ) )
	{
		orderStatus = "cleared";
		showOrderStatusLabel();
		reorganizeOrderDetailsTasks();
		
		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=order&transactionID=' + orderID );
	}
}



// unclear order
function unclearOrderCommit( orderID )
{
	if ( ajaxFromDialog( "Change Status", "Order::undoClear", "orderID=" + orderID ) )
	{
		orderStatus = "all-delivered";
		showOrderStatusLabel();
		reorganizeOrderDetailsTasks();
		
		// reload page
		window.setTimeout( function() {
			window.location.reload();
		}, 2500 );
	}
}



// cancel order
function cancelOrderCommit( orderID )
{
	if ( ajaxFromDialog( "Change Status", "Order::cancel", "orderID=" + orderID ) )
	{
		orderStatus = "canceled";
		showOrderStatusLabel();
		reorganizeOrderDetailsTasks();
		
		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=order&transactionID=' + orderID );
	}
}


// undo cancel order
function undoCancelOrderCommit( orderID )
{
	if ( ajaxFromDialog( "Change Status", "Order::undoCancel", "orderID=" + orderID ) )
	{
		orderStatus = "not-delivered";
		showOrderStatusLabel();
		reorganizeOrderDetailsTasks();
		
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
	transactionID	= document.getElementById( "order_id" ).value;
	transactionType	= document.getElementById( "transaction_type" ).value;
	totalToPay		= document.getElementById( "total_amount_pending" ).value;


	// input
	amountReceived	= parseFloat( document.getElementById( "amount_received" ).value );
	
	if ( amountReceived <= 0 ) {
		alert( 'Input Error:\nThe amount you entered is invalid.\n\nPlease correct the amount received.' );
		$('#amount_received').select();
		return;
	}


	if ( ajaxFromDialog( "Enter payment for Order No. " + transactionID, "Payment::save",
						 "class=order" +
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
			showOrderStatusLabel();
			reorganizeOrderDetailsTasks();
		}
		else if ( amountReceived > 0 )
		{
			paymentStatus = "partially-paid";
			showOrderStatusLabel();
			reorganizeOrderDetailsTasks();
		}

		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=order&transactionID=' + transactionID );
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
	

	if ( ajaxFromDialog( "Enter rebate for Order No. " + transactionID, "Payment::issueRebate",
						 "class=order" +
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
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=order&transactionID=' + transactionID );
	}
}



// waive remaining balance
function waiveBalanceCommit( orderID, pendingClearanceCount ) {
	if ( ajaxFromDialog( "Waive Remaining Balance", "Payment::waiveBalance",
						 "class=order" +
						 "&transactionID=" + orderID ) ) {
		if ( pendingClearanceCount == 0 ) {
			paymentStatus = "fully-paid";
		} else {
			paymentStatus = "fully-paid-not-cleared";
		}
		
		showOrderStatusLabel();
		reorganizeOrderDetailsTasks();
		
		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=order&transactionID=' + orderID );
	}
}



// undo waiving of balance
function undoWaiveBalanceCommit( orderID, amountReceivable, waivedBalance ) {
	if ( ajaxFromDialog( "Undo Waiving of Balance", "Payment::undoWaiveBalance",
						 "class=order" +
						 "&transactionID=" + orderID ) ) {
		if ( waivedBalance == amountReceivable ) {
			paymentStatus = "no-payment";
		} else {
			paymentStatus = "partially-paid";
		}
		
		showOrderStatusLabel();
		reorganizeOrderDetailsTasks();
		
		// redispay payment info section
		ajax( null, 'payment_info', 'innerHTML', 'Payment::showSchedule', 'class=order&transactionID=' + orderID );
	}
}

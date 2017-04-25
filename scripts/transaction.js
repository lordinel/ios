// order.js
// Complementary script for Order class (Order.php)



// ---------------------------------------------------------------------------------------------------------------
// class Transaction
// ---------------------------------------------------------------------------------------------------------------
function Transaction()		// constructor
{
	this.maxItems = 20;						// maximum number of order item rows
	this.initialVisibleItems = 5;			// count of visible order item rows
	
	this.itemRowCount = 0;					// counter for order item rows
	
	this.inventory = new Inventory();		// Inventory object
	this.data = new Data();					// Data object
	this.payment = new Payment();			// Payment object
}



// ---------------------------------------------------------------------------------------------------------------
// set maximum number of order items
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.setMaxItems = function( maxItems )
{
	this.maxItems = maxItems;
}



// ---------------------------------------------------------------------------------------------------------------
// set initial visible items
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.setInitialVisibleItems = function( initialVisibleItems )
{
	this.initialVisibleItems = initialVisibleItems;
}



// ---------------------------------------------------------------------------------------------------------------
// load events on Add/Edit Order form
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.loadFormEvents = function()
{
	var obj = this;				// this handler
	
	
	// trap invalid max items and initial visible items
	if ( this.maxItems == null || isNaN( this.maxItems ) || this.maxItems < 1 )
	{
		alert( 'Program Error:\nInvalid MAX_ITEMS (' + this.maxItems + ').\n\nPlease contact the programmers.' );
		return;
	}
	if ( this.initialVisibleItems == null || isNaN( this.initialVisibleItems ) || this.initialVisibleItems > this.maxItems )
	{
		alert( 'Program Error:\nInvalid INITIAL_VISIBLE_ITEMS (' + this.initialVisibleItems + ').\n\nPlease contact the programmers.' );
		return;
	}
	
	
	// load events
	
	$('#transaction_type').change( function() {
		obj.toggleDeliveryPickupDateLabel();
	});
	
	
	$('#delivery_pickup_date').click( function() {
		obj.data.selectField( $(this) );
	});
	
	
	$('.item_price').each( function( row ) {
		row = row + 1;
		$(this).bind({
			focus: function() {
				obj.data.selectField( $(this), 'float' );
			},
			keyup: function() {
				obj.validateItemPrice( row );
				obj.payment.calculateAmount( row );
			},
			blur: function() {
				obj.data.validateField( $(this), 'float' );
				obj.validateItemPrice( row );
				obj.payment.calculateAmount( row, true );
			}
		});
	});
	
	
	$('.item_sidr_price').each( function( row ) {
		row = row + 1;
		$(this).bind({
			focus: function() {
				obj.data.selectField( $(this), 'float' );
			},
			keyup: function() {
				obj.payment.calculateAmount( row );
			},
			blur: function() {
				obj.data.computePercentageWrapper( 'item_price_'+row, 'item_sidr_price_'+row );
				obj.data.validateField( $(this), 'float' );
				obj.warnIfSIDRPriceLessThanSellingPrice( row );
				obj.payment.calculateAmount( row, true );
			}
		});
	});
	
	
	$('.item_net_price').each( function( row ) {
		row = row + 1;
		$(this).bind({
			focus: function() {
				obj.data.selectField( $(this), 'float' );
			},
			keyup: function() {
				obj.payment.calculateAmount( row );
			},
			blur: function() {
				obj.data.computePercentageWrapper( 'item_price_'+row, 'item_net_price_'+row );
				obj.data.validateField( $(this), 'float' );
				obj.warnIfNetPriceLessThanSellingPrice( row );
				obj.payment.calculateAmount( row, true );
			}
		});
	});
	
	
	$('#withholding_tax').bind({
		focus: function() {
			obj.data.selectField( $(this), 'float' );
		},
		keyup: function() {
			obj.payment.calculateAmount( null );
		},
		blur: function() {
			obj.data.computePercentageWrapper( 'total_sales', 'withholding_tax' );
			obj.data.validateField( $(this), 'float' );
			obj.payment.calculateAmount( null, true );
		}
	});
	
	
	if ( $('#payment_due') != null )		// payment field is existing
	{
		$('#payment_term').change( function() {
			obj.payment.toggleInstallmentSection();
			obj.payment.calculateAmount( null );
		});


		$('#interest').bind({
			focus: function() {
				obj.data.selectField( $(this), 'float' );
			},
			keyup: function() {
				obj.payment.calculateAmount( null );
			},
			blur: function() {
				obj.data.computePercentageWrapper( 'payment_due', 'interest' );
				obj.data.validateField( $(this), 'float' );
				obj.payment.calculateAmount( null, true );
			}
		});

		
		$('.installment_amount').each( function( row ) {
			row = row + 1;
			$(this).bind({
				focus: function() {
					obj.data.selectField( $(this), 'float' );
				},
				keyup: function() {
					obj.payment.calculateAmount( null );
				},
				blur: function() {
					obj.data.computePercentageWrapper( 'net_amount_plus_interest', 'installment_amount_'+row );
					obj.data.validateField( $(this), 'float' );
					obj.payment.validateInstallmentAmount( row );
					obj.payment.calculateAmount( row, true );
				}
			});
		});
		
		
		$('.installment_date').each( function( row ) {
			$(this).click( function() {
				obj.data.selectField( $(this) );
			});
		});
	}
}



// ---------------------------------------------------------------------------------------------------------------
// add row for order item
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.addItem = function()
{
	if ( this.itemRowCount == 0 )
		this.itemRowCount = this.initialVisibleItems;

	this.itemRowCount++;

	// display hidden row
	$('#item_row_'+this.itemRowCount).fadeIn( 800 ).css( 'display', 'table-row' );

	// toggle link and separator
	if ( this.itemRowCount == this.maxItems )
	{
		$('#add_item_row_link').hide();
		$('#item_row_link_separator').hide();
	}
	else if ( this.itemRowCount == this.initialVisibleItems + 1 )
	{
		$('#item_row_link_separator').show();
		$('#remove_item_row_link').show();
	}
}



// ---------------------------------------------------------------------------------------------------------------
// remove row for order item
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.removeItem = function()
{
	// hide last row
	$('#item_row_'+this.itemRowCount).fadeOut( 'fast' );

	// reset and disable fields
	$('#item_brand_'+this.itemRowCount).attr( 'selectedIndex', 0 );
	$('#item_model_'+this.itemRowCount).html( '<option value="null" selected="selected">-- select brand first --</option>' );
	
	this.toggleItemModel( this.itemRowCount, false );
	this.toggleItemPrice( this.itemRowCount, false, true );
	this.toggleItemQuantity( this.itemRowCount, false, true );
	this.toggleItemSIDRprice( this.itemRowCount, false, true );
	this.toggleItemNetPrice( this.itemRowCount, false, true );
	
	this.payment.calculateAmount( this.itemRowCount );

	// toggle link and separator
	if ( this.itemRowCount == this.maxItems )
	{
		$('#add_item_row_link').show();
		$('#item_row_link_separator').show();
	}
	else if ( this.itemRowCount == Number( this.initialVisibleItems ) + 1 )
	{
		$('#item_row_link_separator').hide();
		$('#remove_item_row_link').hide();
	}

	this.itemRowCount--;
}



// ---------------------------------------------------------------------------------------------------------------
// toggle display of Total Sales and VAT fields
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.toggleTaxFieldsDisplay = function( resetValues )
{
	if ( resetValues == null )
		resetValues = true;
	
	if ( $('#invoice_type').val() == "SI" )		// Sales Invoice
		$('.tax_fields').fadeIn( 800 );
	else										// Delivery Receipt, no need to VAT
		$('.tax_fields').fadeOut('fast');
	
	if ( resetValues == true )
	{
		// reset withholding tax
		$('#withholding_tax').val( '0.000' );
		this.payment.calculateAmount( null );
		
		$('#tracking_number').focus();				// autofocus to tracking number field
	}
}



// ---------------------------------------------------------------------------------------------------------------
// toggle label of delivery date/pick-up date
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.toggleDeliveryPickupDateLabel = function()
{
	if ( $('#transaction_type').val() == "delivery" )
		$('#delivery_pickup_date_label').html( "Target Delivery Date:" );
	else								// pick-up
		$('#delivery_pickup_date_label').html( "Target Pick-up Date:" );
}



// ---------------------------------------------------------------------------------------------------------------
// enable or disable item model field
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.toggleItemModel = function( row, enabled )
{
	if ( enabled == true ) {
		$('#item_model_'+row).attr( 'disabled', '' );
		if ( $('#item_model_search_'+row).hasClass( 'search_icon_inactive' ) ) {
			$('#item_model_search_'+row).toggleClass( 'search_icon' );
			$('#item_model_search_'+row).removeClass( 'search_icon_inactive' );
		}
	} else {
		$('#item_model_'+row).attr( 'disabled', 'disabled' );
		if ( $('#item_model_search_'+row).hasClass( 'search_icon' ) ) {
			$('#item_model_search_'+row).toggleClass( 'search_icon_inactive' );
			$('#item_model_search_'+row).removeClass( 'search_icon' );
		}
	}
}



// ---------------------------------------------------------------------------------------------------------------
// enable/disable or reset item price field
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.toggleItemPrice = function( row, enabled, resetValue )
{
	if ( enabled == true )
		$('#item_price_'+row).attr( 'disabled', '' );
	else
		$('#item_price_'+row).attr( 'disabled', 'disabled' );
	
	if ( resetValue == true )
		$('#item_price_'+row).val( '0.000' );
}



// ---------------------------------------------------------------------------------------------------------------
// enable/disable or reset item quantity field
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.toggleItemQuantity = function( row, enabled, resetValue )
{
	if ( enabled == true )
		$('#item_quantity_'+row).attr( 'disabled', '' );
	else
		$('#item_quantity_'+row).attr( 'disabled', 'disabled' );
	
	if ( resetValue == true )
	{
		$('#item_quantity_'+row).val( '0' );
		$('#item_quantity_'+row).css( 'color', goodInputStyle );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// enable/disable or reset item SI/DR discount field
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.toggleItemSIDRprice = function( row, enabled, resetValue )
{
	if ( enabled == true )
		$('#item_sidr_price_'+row).attr( 'disabled', '' );
	else
		$('#item_sidr_price_'+row).attr( 'disabled', 'disabled' );
	
	if ( resetValue == true )
		$('#item_sidr_price_'+row).val( '0.000' );
}



// ---------------------------------------------------------------------------------------------------------------
// enable/disable or reset item Net discount field
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.toggleItemNetPrice = function( row, enabled, resetValue )
{
	if ( enabled == true )
		$('#item_net_price_'+row).attr( 'disabled', '' );
	else
		$('#item_net_price_'+row).attr( 'disabled', 'disabled' );
	
	if ( resetValue == true )
		$('#item_net_price_'+row).val( '0.000' );
}



// ---------------------------------------------------------------------------------------------------------------
// check if item brand selected is valid, toggle item model field
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.validateItemBrand = function( row )
{
	if ( $('#item_brand_'+row).val() == "0" || $('#item_model_'+row).val() == "null" )
		this.toggleItemModel( row, false );
	else
		this.toggleItemModel( row, true );
}



// ---------------------------------------------------------------------------------------------------------------
// check if item model selected is valid, reset price and quantity
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.validateItemModel = function( row, enablePrice )
{
	if ( $('#item_model_'+row).val() == "0" || $('#item_model_'+row).val() == "null" )
		this.toggleItemPrice( row, false, true );
	else
	{
		if ( enablePrice == null )
			enablePrice = false;
		
		this.toggleItemPrice( row, enablePrice, false );
	}
	
	$('#item_quantity_'+row).val( '0' );		// always reset quantity
	$('#item_quantity_'+row).css( 'color', goodInputStyle );
}



// ---------------------------------------------------------------------------------------------------------------
// check if item price is above zero, toggle quantity, SI/DR price and Net price fields
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.validateItemPrice = function( row )
{
	var price = parseFloat( $('#item_price_'+row).val() );
	if ( isNaN( price ) )
		price = 0;
	
	if ( price <= 0 )
	{
		this.toggleItemQuantity( row, false, true );
		this.toggleItemSIDRprice( row, false, true );
		this.toggleItemNetPrice( row, false, true );
	}
	else
	{
		this.toggleItemQuantity( row, true, false );
		this.toggleItemSIDRprice( row, true, false );
		this.toggleItemNetPrice( row, true, false );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// check if SI/DR price is below Selling Price
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.warnIfSIDRPriceLessThanSellingPrice = function( row )
{
	var price = parseFloat( $('#item_price_'+row).val() );
	var sidrPrice = parseFloat( $('#item_sidr_price_'+row).val() );
	
	if ( sidrPrice < price ) {
		showDialog('SI/DR Price Warning',
				   '<b>Warning:</b> SI/DR Price is less than Selling Price<br /><br />' +
				   'Selling Price : Php ' + $('#item_price_'+row).val() + '<br />' +
				   'SI/DR Price&nbsp; : Php ' + $('#item_sidr_price_'+row).val() + '<br /><br />' +
				   '<div id=\"dialog_buttons\">' +
				   '<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />' +
				   '</div>', 'warning');
	}
}



// ---------------------------------------------------------------------------------------------------------------
// check if Net price is below Selling Price
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.warnIfNetPriceLessThanSellingPrice = function( row )
{
	var price = parseFloat( $('#item_price_'+row).val() );
	var netPrice = parseFloat( $('#item_net_price_'+row).val() );

	if ( netPrice < price ) {
		showDialog('Net Price Warning',
				   '<b>Warning:</b> Net Price is less than Selling Price<br /><br />' +
				   'Selling Price : Php ' + $('#item_price_'+row).val() + '<br />' +
				   'Net Price &nbsp; &nbsp;&nbsp; : Php ' + $('#item_net_price_'+row).val() + '<br /><br />' +
				   '<div id=\"dialog_buttons\">' +
				   '<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />' +
				   '</div>', 'warning');
	}
}




// ---------------------------------------------------------------------------------------------------------------
// reset form
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.resetInputForm = function()
{
	/*totalAmount = 0.0;
	for ( var i = 1; i <= orderRowCount; i++ )
	{
		document.getElementById( "order_subtotal_" + i ).value = 0;
		calculateTotal( i );
	}*/
}



// ---------------------------------------------------------------------------------------------------------------
// enable required but disabled fields before submitting form, and perform some checks on required fields
// ---------------------------------------------------------------------------------------------------------------
Transaction.prototype.validateInputForm = function( isItemEditable )
{
	if ( isItemEditable ) {
		// check quantity
		var totalQuantity = 0;

		for (var row = 1; row <= this.maxItems; row++) {
			if (parseInt($('#item_quantity_' + row).val()) > 0)
				totalQuantity = totalQuantity + parseInt($('#item_quantity_' + row).val());
		}

		if (totalQuantity == 0)	// quantity is empty
		{
			showDialog('Error on Quantity',
					   '<b>Error:</b> No quantity entered.<br /><br />' +
					   'Please enter the quantity first then try again.<br /><br /><br />' +
					   '<div id=\"dialog_buttons\">' +
					   '<input type=\"button\" value=\"Close\" onclick=\"hideDialog()\" />' +
					   '</div>', 'error');
			return false;
		}


		// check if there is still unallocated amount in installment plan
		if ($('#payment_due') != null)		// payment fieldset is existing
		{
			if ($('#payment_term').val() == "installment" && parseFloat($('#installment_remaining').val()) > 0) {
				showDialog('Error on Installment Plan',
						   '<b>Error:</b> There is still unallocated amount in installment plan.<br /><br />' +
						   'Clear the remaining amount first then try again.<br /><br /><br />' +
						   '<div id=\"dialog_buttons\">' +
						   '<input type=\"button\" value=\"Close\" onclick=\"hideDialog()\" />' +
						   '</div>', 'error');
				return false;
			}
		}
	}

	
	// check if an agent is selected
	if ( $('#agent_id').val() == "0" )
	{
		showDialog('Error on Agent',
			   '<b>Error:</b> No agent selected.<br /><br />' +
			   'Please select an agent first then try again.<br /><br /><br />' +
			   '<div id=\"dialog_buttons\">' +
			   '<input type=\"button\" value=\"Close\" onclick=\"hideDialog()\" />' +
			   '</div>', 'error');
		return false;
	}
	
	
	if ( isItemEditable ) {
		// enable amount to pay
		$('#total_sales').attr('disabled', '');
		$('#value_added_tax').attr('disabled', '');
		$('#sidr_amount').attr('disabled', '');
		$('#net_amount').attr('disabled', '');
	}

	
	return true;
}




/***********************************
 Non-member functions
***********************************/

// ---------------------------------------------------------------------------------------------------------------
// check if the tracking number entered already exists, wrapper method for handling timeout
// ---------------------------------------------------------------------------------------------------------------
function checkTrackingNumber( transactionClass )
{
	var trackingNumber = escape( $('#tracking_number').val() );
	
	if ( trackingNumber != "" )
	{
		// @TODO previousValue is a global variable
		if ( trackingNumber != previousValue )		// check if input had changed, do not query to database if not
		{
			if ( waitTimeStarted == false )
			{
				waitTimeStarted = true;
				timeoutID = window.setTimeout( "validateTrackingNumber('" + transactionClass + "','" + trackingNumber + "')", TIMEOUT * 1000 );
			}
			else
			{
				window.clearTimeout(timeoutID);		// reset timer
				timeoutID = window.setTimeout( "validateTrackingNumber('" + transactionClass + "','" + trackingNumber + "')", TIMEOUT * 1000 );
			}
		}
	}
	else
	{
		waitTimeStarted = false;
		previousValue = "";
		window.clearTimeout( timeoutID );
		$('#tracking_number').css( 'color', goodInputStyle );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// check if the tracking number entered already exists
// ---------------------------------------------------------------------------------------------------------------
function validateTrackingNumber( transactionClass, trackingNumber )
{
	var invoiceType = $('#invoice_type').val();
	var origInvoiceType = $('#invoice_type_orig').val();
	var origTrackingNumber = $('#tracking_number_orig').val();
	
	if ( invoiceType == origInvoiceType && trackingNumber == origTrackingNumber ) {
		$('#tracking_number').css( 'color', goodInputStyle );
		return true;
	}
	
	ajax( null, null, "inline", transactionClass+"::checkTrackingNumber", "trackingNumber=" + trackingNumber + "&invoiceType=" + invoiceType );
	count = parseInt( ajaxResponseText );
	
	if ( count > 0 ) {
		$('#tracking_number').css( 'color', badInputStyle );
		return false;
	}
	else {
		$('#tracking_number').css( 'color', goodInputStyle );
		return true;
	}
}


/*
original validateTrackingNumber function
for int type tracking number

// ---------------------------------------------------------------------------------------------------------------
// check if the tracking number entered already exists
// ---------------------------------------------------------------------------------------------------------------
function validateTrackingNumber( transactionClass, trackingNumber )
{
	trackingNumber = parseInt( trackingNumber );
	
	
	if ( !isNaN( trackingNumber ) && trackingNumber > 0 )
	{
		var invoiceType = $('#invoice_type').val();
		var origInvoiceType = $('#invoice_type_orig').val();
		var origTrackingNumber = parseInt( $('#tracking_number_orig').val() );
		
		if ( invoiceType == origInvoiceType && trackingNumber == origTrackingNumber )
		{
			$('#tracking_number').css( 'color', goodInputStyle );
			return true;
		}
		
		ajax( null, null, "inline", transactionClass+"::checkTrackingNumber", "trackingNumber=" + trackingNumber + "&invoiceType=" + invoiceType );
		count = parseInt( ajaxResponseText );
		
		if ( count > 0 )
		{
			$('#tracking_number').css( 'color', badInputStyle );
			return false;
		}
		else
		{
			$('#tracking_number').css( 'color', goodInputStyle );
			return true;
		}
	}
	else
	{
		$('#tracking_number').css( 'color', badInputStyle );
		return false;
	}
}

 */


// ---------------------------------------------------------------------------------------------------------------
// display error message if tracking number entered already exists
// ---------------------------------------------------------------------------------------------------------------
function showTrackingNumberDialog( transactionClass )
{
	var trackingNumber = escape( $('#tracking_number').val() );
	
	if ( this.validateTrackingNumber( transactionClass, trackingNumber ) == false )
	{
		var invoiceType = $('#invoice_type').val();
		
		showDialog('Duplicate Tracking Number',
				   '<b>Error:</b> The ' + ( invoiceType == "SI" ? 'Sales Invoice' : 'Delivery Receipt' ) +
				   ' number you entered is already assigned to another order or purchase.<br /><br />' +
				   'Kindly correct the ' + ( invoiceType == "SI" ? 'Sales Invoice' : 'Delivery Receipt' ) +
				   ' number.<br /><br />' +
				   '<div id=\"dialog_buttons\">' +
				   '<input type=\"button\" value=\"OK\" onclick=\"hideTrackingNumberDialog()\" />' +
				   '</div>', 'error');
	}
}


/*
original showTrackingNumberDialog function
for int type tracking number

// ---------------------------------------------------------------------------------------------------------------
// display error message if tracking number entered already exists
// ---------------------------------------------------------------------------------------------------------------
function showTrackingNumberDialog( transactionClass )
{
	var trackingNumber = parseInt( escape( $('#tracking_number').val() ) );
	
	if ( !isNaN( trackingNumber ) && trackingNumber > 0 )
	{
		if ( this.validateTrackingNumber( transactionClass, trackingNumber ) == false )
		{
			var invoiceType = $('#invoice_type').val();
			
			showDialog('Duplicate Tracking Number',
					   '<b>Error:</b> The ' + ( invoiceType == "SI" ? 'Sales Invoice' : 'Delivery Receipt' ) +
					   ' number you entered is already assigned to another order or purchase.<br /><br />' +
					   'Kindly correct the ' + ( invoiceType == "SI" ? 'Sales Invoice' : 'Delivery Receipt' ) +
					   ' number.<br /><br />' +
					   '<div id=\"dialog_buttons\">' +
					   '<input type=\"button\" value=\"OK\" onclick=\"hideTrackingNumberDialog()\" />' +
					   '</div>', 'error');
		}
	}
	else
	{
		$('#tracking_number').val( '' );
		$('#tracking_number').css( 'color', goodInputStyle );
		$('#tracking_number').focus();
	}
}

 */


// ---------------------------------------------------------------------------------------------------------------
// hide error message shown if tracking number entered already exists
// ---------------------------------------------------------------------------------------------------------------
function hideTrackingNumberDialog()
{
	hideDialog();
	$('#tracking_number').val( '' );
	$('#tracking_number').css( 'color', goodInputStyle );
	$('#tracking_number').focus();
}




/*****/
function reloadTransactionStatus( className, transactionID, status )
{
	if ( status == null )
	{
		ajax( null, null, "inline", "Transaction::getTransactionDeliveryStatus", "class=" + className + "&transactionID=" + transactionID );
		var transaction = JSON.parse( ajaxResponseText );
		status = transaction['delivery_pickup_status'];
	}
	
	if ( className == "order" )
	{
		orderStatus = status;
		showOrderStatusLabel();
		reorganizeOrderDetailsTasks();
	}
	else
	{
		orderStatus = status;
		showPurchaseStatusLabel();
		reorganizePurchaseDetailsTasks();
	}
}


// ---------------------------------------------------------------------------------------------------------------
// mark selected item as delivered
// ---------------------------------------------------------------------------------------------------------------
function markItemAsDelivered()
{
	var className = $('#class_name').val();
	var transactionID = $('#transaction_id').val();
	var itemID = parseInt( $('#itemID').val() );
	var index = parseInt( $('#index').val() );
	
	var undeliveredQuantity = parseInt( $('#quantity_undelivered').val() );
	var deliveredQuantity = parseInt( $('#quantity_delivered').val() );
	var deliveryDate = $('#delivery_date').val();
	
	var dialogTitle = $('#dialog_title').html();
	
	
	if ( deliveredQuantity <= 0 ) 	{
		alert( 'Input Error:\nThe number of delivered items you entered is zero.\n\nPlease correct the number of items delivered.' );
		$('#quantity_delivered').select();
		return;
	} else if ( deliveredQuantity > undeliveredQuantity ) {
		alert( 'Input Error:\nThe number of delivered items you entered is greater than undelivered quantity.\n\nPlease correct the number of items delivered.' );
		$('#quantity_delivered').select();
		return;
	}
	
	
	if ( ajaxFromDialog( dialogTitle, "Transaction::markItemAsDelivered",
						 "class=" + className +
						 "&transactionID=" + transactionID +
						 "&itemID=" + itemID +
						 "&deliveredQuantity=" + deliveredQuantity +
						 "&deliveryDate=" + deliveryDate ) )
	{
		$('#item_quantity_'+index).html( parseFloat( $('#item_quantity_'+index).html() ) + deliveredQuantity );
		$('#item_quantity_total').html( parseInt( $('#item_quantity_total').html() ) + deliveredQuantity );
		reloadTransactionStatus( className, transactionID );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// return delivered items
// ---------------------------------------------------------------------------------------------------------------
function confirmNoReturnToStocks()
{
	if ( !$('#return_to_inventory').attr( 'checked' ) ) {
		response = confirm( "Are you sure you don't want to return the items to inventory stocks?" );
		return response;
	} else {
		return true;
	}
}


function returnDeliveredItems()
{
	var className = $('#class_name').val();
	var transactionID = $('#transaction_id').val();
	var itemID = parseInt( $('#itemID').val() );
	var index = parseInt( $('#index').val() );
	
	var deliveredQuantity = parseInt( $('#quantity_delivered').val() );
	var returnedQuantity = parseInt( $('#quantity_returned').val() );
	
	var dialogTitle = $('#dialog_title').html();
	
	
	if ( returnedQuantity <= 0 ) {
		alert( 'Input Error:\nThe number of returned items you entered is zero.\n\nPlease correct the number of items returned.' );
		$('#quantity_returned').select();
		return;
	} else if ( returnedQuantity > deliveredQuantity ) {
		alert( 'Input Error:\nThe number of returned items you entered is greater than number of delivered items.\n\nPlease correct the number of items returned.');
		$('#quantity_returned').select();
		return;
	}
	
	
	if ( ajaxFromDialog( dialogTitle, "Transaction::returnDeliveredItems",
						 "class=" + className +
						 "&transactionID=" + transactionID +
						 "&itemID=" + itemID +
						 "&returnedQuantity=" + returnedQuantity +
						 "&returnToInventory=" + $('#return_to_inventory').attr( 'checked' ) ) )
	{
		$('#item_quantity_'+index).html( parseFloat( $('#item_quantity_'+index).html() ) - returnedQuantity );
		$('#item_quantity_total').html( parseInt( $('#item_quantity_total').html() ) - returnedQuantity );
		reloadTransactionStatus( className, transactionID );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// mark all items as delivered
// ---------------------------------------------------------------------------------------------------------------
function markAllItemsAsDelivered()
{
	var className = $('#class_name').val();
	var transactionID = $('#transaction_id').val();
	var maxIndex = parseInt( $('#max_index').val() );
	
	var deliveryDate = $('#delivery_date').val();
	
	var dialogTitle = $('#dialog_title').html();
	
	
	if ( ajaxFromDialog( dialogTitle, "Transaction::markAllItemsAsDelivered",
						 "class=" + className +
						 "&transactionID=" + transactionID +
						 "&deliveryDate=" + deliveryDate ) )
	{
		for ( var i = 1; i <= maxIndex; i++ )
			$('#item_quantity_'+i).html( $('#item_max_quantity_'+i).html() );
		
		$('#item_quantity_total').html( $('#item_max_quantity_total').html() );
		reloadTransactionStatus( className, transactionID, "all-delivered" );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// display auto-suggest dialog for Item Model
// ---------------------------------------------------------------------------------------------------------------
function showAutoSuggestModelDialog( row )
{
	if ( $('#item_model_'+row).attr( 'disabled' ) == '' ) {
		brandID = $('#item_brand_'+row).val();
		
		if ( $('#order_id').val() != null ) {
			className = 'order';
		} else {
			className = 'purchase';
		}		
		
		showDialog( 'Search Model','Getting data...', 'prompt' );
		if ( $('#item_model_'+row).val() != 0 ) {
			defaultTextValue = encodeURIComponent( $('#item_model_'+row+' option:selected').html() );
		} else {
			defaultTextValue = "";
		}
		
		ajax( null,'dialog_message','innerHTML','Inventory::loadModelListSuggestion',
			  'brandID=' + brandID + '&class=' + className + '&row=' + row + '&defaultTextValue=' + defaultTextValue );
		$('#model_suggestion').focus();
	}
}


function submitAutoSuggestModelDialog( brandID, className, row )
{
	ajax( null, null, 'inline', 'Inventory::getModelID', 'brandID=' + brandID + '&class=' + className + '&modelName=' + filterAjaxInput( $('#model_suggestion').val() ) );
	var transaction = JSON.parse( ajaxResponseText );
	var modelID = transaction['model_id'];
	
	if ( modelID > 0 ) {
		$('#item_model_'+row).val( modelID );
		$('#item_model_'+row).change();
		return true;
	} else {
		alert( 'Input Error:\nInvalid model name entered.\n\nPlease type a valid model name.' );
		return false;
	}
}


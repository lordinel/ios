// payment.js
// Complementary script for Payment class (Payment.php)



// ---------------------------------------------------------------------------------------------------------------
// class Inventory
// ---------------------------------------------------------------------------------------------------------------
function Inventory() {}



// ---------------------------------------------------------------------------------------------------------------
// load item price and stock count in order item table
// ---------------------------------------------------------------------------------------------------------------
Inventory.prototype.loadSellingPriceAndStock = function( row, inventoryID, orderID )
{
	ajax( null, null, "inline", "Inventory::loadSellingPriceAndStock", "inventoryID=" + inventoryID + "&orderID=" + orderID );

	var inventoryInfo = JSON.parse( ajaxResponseText );

	$('#item_price_'+row).val( inventoryInfo['selling_price'] );
	$('#item_price_orig_'+row).val( inventoryInfo['selling_price'] );
	$("#item_max_quantity_"+row).val( inventoryInfo['available_stock'] );
	$('#item_sidr_price_'+row).val( inventoryInfo['selling_price'] );
	$('#item_net_price_'+row).val( inventoryInfo['selling_price'] );
}



// ---------------------------------------------------------------------------------------------------------------
// load item purchase price
// ---------------------------------------------------------------------------------------------------------------
Inventory.prototype.loadPurchasePrice = function( row, inventoryID )
{
	ajax( null, null, "inline", "Inventory::loadPurchasePrice", "inventoryID=" + inventoryID );

	var inventoryInfo = JSON.parse( ajaxResponseText );

	$('#item_price_'+row).val( inventoryInfo['purchase_price'] );
	$('#item_price_orig_'+row).val( inventoryInfo['purchase_price'] );
	$('#item_sidr_price_'+row).val( inventoryInfo['purchase_price'] );
	$('#item_net_price_'+row).val( inventoryInfo['purchase_price'] );
}



// ----------------------------------------------------------------
// Inventory Processing Group
// ----------------------------------------------------------------

// add brand
function addBrandCommit()
{
	var newBrandName = filterAjaxInput( document.getElementById( "new_brand_name" ).value );

	if ( ajaxFromDialog( "Add Brand", "Inventory::addBrand", "newBrandName=" + newBrandName ) )
	{
		var brandID = $('#brand_id').val();
		window.setTimeout( function() {
			document.location = "list_inventory_models.php?brandID=" + brandID;
		}, 2500 );
	}
}



// edit brand
function editBrandCommit()
{
	var brandID      = filterAjaxInput( document.getElementById( "brand_id" ).value );
	var oldBrandName = filterAjaxInput( document.getElementById( "old_brand_name" ).value );
	var newBrandName = filterAjaxInput( document.getElementById( "new_brand_name" ).value );

	if ( ajaxFromDialog( "Edit Brand", "Inventory::editBrand", "brandID=" + brandID + "&oldBrandName=" + oldBrandName + "&newBrandName=" + newBrandName ) ) {
		ajax( null, 'model_tasks', 'innerHTML', 'Inventory::showModelListTasks','brandID='+brandID,'brandName='+newBrandName );
		newBrandName = $('#formatted_new_brand_name').html();
		$('.main_record_label > div').html( decodeURIComponent( newBrandName ) );
		$('#old_brand_name').val( decodeURIComponent( newBrandName ) );
	}
}



// delete brand
function deleteBrandCommit( brandID, brandName )
{
	brandName = filterAjaxInput( brandName );
	if ( ajaxFromDialog( "Delete Brand", "Inventory::deleteBrand",
						 "brandID=" + brandID + "&brandName=" + brandName ) )
	{
		window.setTimeout( function()
		{
			document.location = "list_inventory.php";
		}, 2500 );
	}
}



// add model for selected brand
function addModelCommit()
{
	var brandID   = filterAjaxInput( document.getElementById( "brand_id" ).value );
	var dialogTitle = $('#dialog_title').html();

	if ( ajaxFromDialog( dialogTitle, "Inventory::addModel",
						 "brandID=" + brandID +
						 "&newModelName=" 			+ filterAjaxInput( document.getElementById( "new_model_name" ).value ) +
						 "&newModelDescription=" 	+ filterAjaxInput( document.getElementById( "new_model_description" ).value ) +
						 "&newModelPurchasePrice=" 	+ filterAjaxInput( document.getElementById( "new_model_purchase_price" ).value ) +
						 "&newModelSellingPrice=" 	+ filterAjaxInput( document.getElementById( "new_model_selling_price" ).value ) +
						 "&newModelStockCount=" 	+ filterAjaxInput( document.getElementById( "new_model_stock_count" ).value ) ) )
	{
		// redispay inventory model list
		ajax( null, 'model_list_' + brandID, 'innerHTML', 'Inventory::showModelList', "criteria=" + brandID );
	}
}



// edit model info
function editModelCommit()
{
	var brandID = filterAjaxInput( document.getElementById( "brand_id" ).value );

	if ( ajaxFromDialog( "Edit Model", "Inventory::editModel",
						 "modelID=" 				+ filterAjaxInput( document.getElementById( "model_id" ).value ) +
						 "&newModelName=" 			+ filterAjaxInput( document.getElementById( "new_model_name" ).value ) +
						 "&newModelDescription=" 	+ filterAjaxInput( document.getElementById( "new_model_description" ).value ) +
						 "&newModelPurchasePrice=" 	+ filterAjaxInput( document.getElementById( "new_model_purchase_price" ).value ) +
						 "&newModelSellingPrice=" 	+ filterAjaxInput( document.getElementById( "new_model_selling_price" ).value ) +
	                     "&newModelStockCount=" 	+ filterAjaxInput( document.getElementById( "new_model_stock_count" ).value ) ) )
	{
		// redispay inventory model list
		ajax( null, 'model_list_' + brandID, 'innerHTML', 'Inventory::showModelList', "criteria=" + brandID );
	}
}



// display confirmation to delete model
function deleteModelConfirm( brandID, modelID, modelName )
{
	showDialog( 'Delete Model','Getting data...', 'warning' );
	ajax( null,'dialog_message','innerHTML','Inventory::showDeleteModelDialog',
		  'brandID=' + brandID + '&modelID=' + modelID + '&modelName=' + encodeURIComponent( modelName ) );
}


// toggle duplicate model input in delete model dialog
function toggleDuplicateModelInput()
{
	if ( $('#mark_as_duplicate').attr( 'checked' ) ) {
		$('#duplicate_inventory').attr( 'disabled', '' );
	} else {
		$('#duplicate_inventory').attr( 'disabled', 'disabled' );
		$('#duplicate_inventory').val( '' );
	}
}



// delete model
function deleteModelCommit( resultCount, brandID, modelID, modelName )
{
	duplicateModelID = 0;
	
	if ( resultCount > 0 ) {			// check if duplicate model form is present
		if ( $('#mark_as_duplicate').attr( 'checked' ) ) {			// check if mark as duplicate is checked
			// check if entered duplicate model is valid
			ajax( null, null, 'inline', 'Inventory::getModelIdExcept',
				  'brandID=' + brandID +
				  '&modelName=' + filterAjaxInput( $('#duplicate_inventory').val() ) +
				  '&modelIdException=' + modelID );
			var transaction = JSON.parse( ajaxResponseText );
			var duplicateModelID = transaction['model_id'];
			
			if ( duplicateModelID == 0 ) {
				alert( 'Input Error:\nInvalid model name entered.\n\nPlease select a valid model name.' );
				return false;
			} else {
				response = confirm( 'Are you sure you want to delete\n\n"' + modelName + '"\n\nand change it to\n\n"' + $('#duplicate_inventory').val() + '"?');
				if ( !response ) {
					return false;
				}
			}
		}
	}
	
	if ( ajaxFromDialog( "Delete Model", "Inventory::deleteModel",
						 "modelID=" + modelID + "&modelName=" + filterAjaxInput( modelName ) + "&duplicateModelID=" + duplicateModelID ) )
	{
		// redispay inventory model list
		ajax( null, 'model_list_' + brandID, 'innerHTML', 'Inventory::showModelList', "criteria=" + brandID );
	}
}

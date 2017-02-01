// supplier.js
// Complementary script for Supplier class (Supplier.php)

$( function() {
	$( "#supplier_name" ).keyup( function() {
		autoSuggest('Supplier::showAutoSuggest','supplier_name','autosuggest_supplier_name');
	});


	$( "#supplier_name" ).keydown( function() {
		enableInputFields( "supplier" );
	} );


	$( "#supplier_name" ).blur( function() {
		fillInputFields( "supplier", "Supplier::autoFill", 'supplierName=' + encodeURIComponent( $('#supplier_name').val() ) )
	} );
});



function resetSupplierForm()
{
	enableInputFields( "supplier" );
	editLocked = false;
}

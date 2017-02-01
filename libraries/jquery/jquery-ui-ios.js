// ----------------------------------------------------------------
// Generic jQuery UI Functions Group
// ----------------------------------------------------------------

// generic function for loading datepicker
function loadDatePicker()
{
	$( function()
	{
		$( ".datepicker" ).datepicker(
		{
			showOn: "both",
			buttonImage: "libraries/jquery/images/calendar.gif",
			buttonImageOnly: true,
			dateFormat: "MM d, yy, D",
			changeMonth: true,
			changeYear: true
		} );

		$( ".datepicker" ).keydown( function(){
			$(this).blur();
		} );
	} );

	$( function()
	{
		$( ".datepicker_no_past_date" ).datepicker(
		{
			showOn: "both",
			buttonImage: "libraries/jquery/images/calendar.gif",
			buttonImageOnly: true,
			dateFormat: "MM d, yy, D",
			changeMonth: true,
			changeYear: true,
			minDate: 0
		} );

		$( ".datepicker_no_past_date" ).keydown( function(){
			$(this).blur();
		} );
	} );

	$( function()
	{
		$( ".datepicker_no_future_date" ).datepicker(
		{
			showOn: "both",
			buttonImage: "libraries/jquery/images/calendar.gif",
			buttonImageOnly: true,
			dateFormat: "MM d, yy, D",
			changeMonth: true,
			changeYear: true,
			maxDate: 0
		} );

		$( ".datepicker_no_future_date" ).keydown( function(){
			$(this).blur();
		} );
	} );
}


// generic function for loading accordion
function loadAccordion( activeID, isCollapsible )
{
	if ( !activeID )
		activeID = false;
	else if ( activeID == "zero" )
		activeID = 0;
	
	if ( isCollapsible == null )
		isCollapsible = true;

	$( function()
	{
		$( "#accordion" ).accordion(
		{
			active: activeID,
			collapsible: isCollapsible,
			autoHeight: false
		} );
	} );
}


// make dialog box draggable
function makeDialogDraggable()
{
	$( function()
	{
		$( "div#dialog" ).draggable( {
			containment: "body",
			handle: "div#dialog_header"
		} );
	} );
}




// load generic jQuery UI functions automatically
loadDatePicker();
loadAccordion();


// ----------------------------------------------------------------
// Specific jQuery UI Functions Group
// ----------------------------------------------------------------


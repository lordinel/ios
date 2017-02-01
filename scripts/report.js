// report.js
// Complementary script for Sales Report



// ---------------------------------------------------------------------------------------------------------------
// class Report
// ---------------------------------------------------------------------------------------------------------------
function Report()
{
	this.data = new Data();		// instantiate Data class
	
	// parameters
	this.totalBankAmount = 0;
	this.amountReceivable = 0;
	this.pdcReceivable = 0;
	this.inventoryAmount = 0;
	this.rebatePayable = 0;
	this.pdcRebate = 0;
	this.amountPayable = 0;
	this.pdcPayable = 0;
	this.otherExpenses = 0;
	this.capital = 0;
	this.profit = 0;
	
	this.vieType = null;
}



// ---------------------------------------------------------------------------------------------------------------
// set initial view type
// ---------------------------------------------------------------------------------------------------------------
Report.prototype.setViewType = function( viewType )
{
	this.viewType = viewType;
}



// ---------------------------------------------------------------------------------------------------------------
// determine the difference of start date and end date
// ---------------------------------------------------------------------------------------------------------------
Report.prototype.getDateDifference = function( startDate, endDate, unit )
{
	if ( unit == null )
		unit = 'day';		// default unit
	
	
	// constants
	var MILLISECONDS_PER_SECOND = 1000;
	var SECONDS_PER_MINUTE = 60;
	var MINUTES_PER_HOUR = 60;
	var HOURS_PER_DAY = 24;
	//var DAYS_PER_WEEK = 7;


	var unitDivisor;

	switch ( unit.toLowerCase() )
	{
		case 'day':
			unitDivisor = MILLISECONDS_PER_SECOND * SECONDS_PER_MINUTE * MINUTES_PER_HOUR * HOURS_PER_DAY;
	}


	return Math.ceil( ( endDate.getTime() - startDate.getTime() ) / unitDivisor );
}



// ---------------------------------------------------------------------------------------------------------------
// set View selection
// ---------------------------------------------------------------------------------------------------------------
Report.prototype.setChartViewOptions = function()
{
	var startDate = new Date( $('#startdate').val() );
	var endDate = new Date( $('#enddate').val() );


	var viewTypeOptions = "";


	var dayDifference = this.getDateDifference( startDate, endDate );

	// limit day view to 1 month (31 days max)
	if ( dayDifference <= 30 )
		viewTypeOptions = viewTypeOptions + '<option value="day"' + ( this.viewType == 'day' ? " selected=\"selected\"" : "" ) + '>Day</option>';


	var weekDifference = Math.ceil( dayDifference / 7 );

	// limit week view to 1 year (53 weeks max)
	if ( weekDifference <= 30 )
		viewTypeOptions = viewTypeOptions + '<option value="week"' + ( this.viewType == 'week' ? " selected=\"selected\"" : "" ) + '>Week</option>';

	// limit month view to 3 years (1096 days max)
	if ( dayDifference <= 731 )
		viewTypeOptions = viewTypeOptions + '<option value="month"' + ( this.viewType == 'month' ? " selected=\"selected\"" : "" ) + '>Month</option>';

	// limit quarter view to 5 years (1827 days max)
	if ( dayDifference <= 1827 )
		viewTypeOptions = viewTypeOptions + '<option value="quarter"' + ( this.viewType == 'quarter' ? " selected=\"selected\"" : "" ) + '>Quarter</option>';

	// year view is always present
	viewTypeOptions = viewTypeOptions + '<option value="year"' + ( this.viewType == 'year' ? " selected=\"selected\"" : "" ) + '>Year</option>';


	$('#viewtype').html( viewTypeOptions );
}



// ---------------------------------------------------------------------------------------------------------------
// load events on Add/Edit Order form
// ---------------------------------------------------------------------------------------------------------------
Report.prototype.loadFormEvents = function()
{
	var obj = this;				// this handler
	
	
	// trap invalid viewtype
	if ( this.viewType != 'day' && this.viewType != 'week' && this.viewType != 'month' &&
		 this.viewType != 'quarter' && this.viewType != 'year' )
	{
		alert( 'Program Error:\nInvalid viewType (' + this.viewType + ')\n\nPlease contact the programmers.' );
		return;
	}
	
	
	// load events
	
	$('#startdate, #enddate').bind({
		focus: function() {
			obj.data.selectField( $(this) );
		},
		change: function() {
			obj.setChartViewOptions();
		}
	});
	
	
	$('.bank_amount').each( function( row ) {
		row = row + 1;
		$(this).bind({
			focus: function() {
				obj.data.selectField( $(this), 'float' );
			},
			keyup: function() {
				obj.calculateProfit();
			},
			blur: function() {
				obj.data.validateField( $(this), 'float' );
				obj.calculateProfit();
			}
		});
	});
	
	
	$('#other_expenses, #capital').bind({
		focus: function() {
			obj.data.selectField( $(this), 'float' );
		},
		keyup: function() {
			obj.calculateProfit();
		},
		blur: function() {
			obj.data.validateField( $(this), 'float' );
			obj.calculateProfit();
		}
	});
}



// ---------------------------------------------------------------------------------------------------------------
// calculate profit
// ---------------------------------------------------------------------------------------------------------------
Report.prototype.calculateProfit = function() {
	this.totalBankAmount = 0;
	this.profit = 0;
	
	for ( var i = 1; i <= 5; i++ )
	{
		bankAmount = parseFloat( stripNonNumeric( $('#bank_amount_'+i).val() ) );
		if ( isNaN( bankAmount ) )
			bankAmount = 0;
		this.totalBankAmount = this.totalBankAmount + bankAmount;
	}
	
	$('#bank_amount_total').val( this.totalBankAmount.toFixed( 3 ) );
	
	
	this.otherExpenses = parseFloat( $('#other_expenses').val() );
	if ( isNaN( this.otherExpenses ) )
		this.otherExpenses = 0;
	
	this.capital = parseFloat( $('#capital').val() );
	if ( isNaN( this.capital ) )
		this.capital = 0;
	
	
	this.profit = ( this.totalBankAmount + this.amountReceivable + this.pdcReceivable + this.inventoryAmount ) -
				  ( this.amountPayable + this.pdcPayable + this.rebatePayable + this.pdcRebate + this.otherExpenses + this.capital );
	if ( isNaN( this.profit ) )
		this.profit = 0;
	
	$('#profit').val( numberFormat( this.profit.toFixed( 3 ) ) );
	if ( $('#profit').val() == '0.000' ) {
		$('#profit').css( 'color', goodInputStyle );
	} else if ( this.profit > 0 ) {
		$('#profit').css( 'color', bestInputStyle );
	} else {
		$('#profit').css( 'color', badInputStyle );
	}
}



// load report
Report.prototype.loadReport = function( reportType, category, startDate, endDate, viewType, divID )
{
	if ( reportType == 'periodic-sales' || reportType == 'projected-collections' ) {
		var startDateCalc = new Date( startDate );
		var endDateCalc   = new Date( endDate );
		var ONE_DAY       = 1000 * 60 * 60 * 24;
		
		var dateDifference = Math.ceil( ( endDateCalc.getTime() - startDateCalc.getTime() ) / ONE_DAY );
		if ( dateDifference > 365 ) {   // if more than 1 year, show loading page
			$('#tab_content').html( 'Loading.. Please wait.' );
		}
	}

	if ( $('#report_category') != null ) {
		$('#report_category').val( category );
	}
	
	if ( divID == null )
		divID = 'tab_content';
	
	ajax( null, divID, 'innerHTML', 'Report::loadReport',
		  'type='+reportType+'&category='+category
		  +'&startDate='+startDate+'&endDate='+endDate
		  +'&viewType='+viewType );
}



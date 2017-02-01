// data.js
// Data manipulation class



// ---------------------------------------------------------------------------------------------------------------
// class Data
// ---------------------------------------------------------------------------------------------------------------
function Data() {}



// ---------------------------------------------------------------------------------------------------------------
// clear or select the value of a text input field upon focus
// ---------------------------------------------------------------------------------------------------------------
Data.prototype.selectField = function( element, dataType )
{
	if ( dataType == null )
		element.select();
	else
	{
		dataType = dataType.toLowerCase();
		
		if ( ( dataType == 'float' && element.val() == "0.000" ) ||
			 ( dataType == 'int' && element.val() == "0" ) )
			element.val( "" );
		else
			element.select();
	}
}



// ---------------------------------------------------------------------------------------------------------------
// restore the default value of text field if blank or invalid value, or format the input text
// ---------------------------------------------------------------------------------------------------------------
Data.prototype.validateField = function( element, dataType, noNegative )
{
	value = element.val();
	
	if ( dataType != null )
		dataType = dataType.toLowerCase();
	else
		dataType = 'string';
	
	if ( dataType == 'float' || dataType == 'int' )
	{
		if ( noNegative == null )
			noNegative = true;
	}

	if ( value == "" || isNaN( Number( value ) ) || RegExp(/^\s+$/).test( value ) ||
		 ( ( dataType == 'float' || dataType == 'int' ) && element.val() < 0 && noNegative == true )  )
	{
		if ( dataType == 'float' ) {
			element.val("0.000");
		} else if ( dataType == 'int' )
			element.val( "0" );
		else if ( dataType != 'string' )
			alert( "Program Bug:\nInvalid data type parameter (" + dataType + ").\n\nPlease contact the programmers." );
	}
	else
	{
		if ( dataType == 'float' )
		{
			value = parseFloat( value );
			element.val( value.toFixed( 3 ) );
		}
		else if ( dataType == 'int' )
			element.val( parseInt( value, 10 ) );
		else if ( dataType != 'string' )
			alert( "Program Bug:\nInvalid data type parameter (" + dataType + ").\n\nPlease contact the programmers." );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// wrapper method for converting percentage input to number
// ---------------------------------------------------------------------------------------------------------------
Data.prototype.computePercentageWrapper = function( sourceID, percentID, targetID )
{
	var percentStr = $('#'+percentID).val() + '';
	var length = percentStr.length;
	
	if ( percentStr.charAt( length - 1 ) == '%' )
	{
		var number;
	
		if ( $('#'+sourceID) != null )			// source entered is an element ID
			number = $('#'+sourceID).val();
		else									// source entered is a number
			number = sourceID;
		
		var percentageValue = this.computePercentage( number, $('#'+percentID).val() );
	
		if ( targetID != null && $('#'+targetID) != null )			// target element is specified
			$('#'+targetID).val( percentageValue.toFixed( 3 ) );
		else														// no target element, display back on percent element
			$('#'+percentID).val( percentageValue.toFixed( 3 ) );
	}
}



// ---------------------------------------------------------------------------------------------------------------
// convert percentage input to number
// ---------------------------------------------------------------------------------------------------------------
Data.prototype.computePercentage = function( number, percent )
{
	var percentStr = percent + '';
	var length = percentStr.length;
	
	var multiplier;
	
	if ( percentStr.charAt( length - 1 ) == '%' )				// percent string contains % symbol
		multiplier = parseFloat( percentStr.substring( 0, length - 1 ) ) / 100;
	else														// percent string is a number
		multiplier = parseFloat( percentStr ) / 100;
	
	if ( multiplier < 0 )					// negative percent, get reverse percentage
		multiplier = 1 - ( multiplier * -1 );
	
	return number * multiplier;
}

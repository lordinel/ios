// ajax.js
// Complementary script for ajax.php

var ajaxResponseText = "";


// execute AJAX query
function ajax( callerType,			// type of element that called this ajax function (e.g. text, select-one, dialog, autosuggest), set to this.type for generic call
			   elementID,			// id of element to which result should be displayed
			   displayType,			// how result will be displayed (e.g. innerHTML, value, or inline)
			   phpFunction,			// PHP function or class method to call
			   param /*...*/ )		// parameters (can be as many as possible, or an array), in format "name=value", set to null if no parameter
{
	// trap invalid parameters
	if ( displayType != "innerHTML" && displayType != "value" &&  displayType != "inline" )
	{
		alert( "Program Bug:\nInvalid display type parameter (" + displayType + ").\n\nPlease contact the programmers." );
		return false;
	}
	else
	{
		if ( !param || ( param != "" && ( param != "null" || ( param == "null" && callerType == "text" ) ) ) )
		{
			// prepare parameters
			var parameters = "phpFunction=" + phpFunction;
			if ( param != null )
			{
				var i;

				if ( param.constructor == Array )		// param is an array
				{
					for ( i = 0; i < param.length; i++ )
						parameters = parameters + "&" + param[i];
				}
				else									// multiple param arguments
				{
					for ( i = 4; i < arguments.length; i++ )
						parameters = parameters + "&" + arguments[i];
				}
			}


			// set timeout to display AJAX connection status message
			showAJAXConnectionMessage();


			// set timeout to detect network connection problem
			// produce timeout in 1 milliseconds after browser returns timeout
			var connectionTimeout = setTimeout( 'ajaxConnectionLost()', 1 );


			// prepare AJAX connection
			var xmlhttp = new XMLHttpRequest();


			// set function for receiving response data
			xmlhttp.onreadystatechange = function()
			{
				if ( xmlhttp.readyState == 4 && xmlhttp.status == 200 )
				{
					// response received, disable timeout
					clearTimeout( connectionTimeout );


					// hide AJAX connection status message
					hideAJAXConnectionMessage();


					// get response data
					if ( xmlhttp.responseText.length > 0 )
					{
						if ( displayType == "innerHTML" )
						{
							document.getElementById( elementID ).innerHTML = xmlhttp.responseText;
							return true;
						}
						else if ( displayType == "value" )
						{
							document.getElementById( elementID ).value = xmlhttp.responseText;
							return true;
						}
						else		// inline
						{
							ajaxResponseText = xmlhttp.responseText;		// pass to global variable
							return true;
						}
					}
					else		// empty reponse, most likely due to missing required parameters
					{
						if ( callerType != 'dialog' && callerType != 'autosuggest' )		// not called from ajaxFromDialog()
							ajaxParamMissing();

						return false;
					}


					// call user-defined JS function after returning result (experimental)
					/* func = "calculateSubtotal";
					window[func]('1'); */
				}
				else
					return false;
			}


			// open AJAX connection
			xmlhttp.open( "POST", "controls/ajax.php", false );


			// send parameter for database query
			xmlhttp.setRequestHeader( "Content-type","application/x-www-form-urlencoded" );
			xmlhttp.send( parameters );
		}
		else
		{
			if ( displayType == "innerHTML" )
				document.getElementById( elementID ).innerHTML = "";
			else
				document.getElementById( elementID ).value = document.getElementById( elementID ).defaultValue;

			return true;
		}

		return true;
	}
}



// AJAX query from dialog box
function ajaxFromDialog( dialogTitle,			// title of dialog box
						 phpFunction,			// PHP function or class method to call
						 param /*...*/ )		// parameters (can be as many as possible), in format "name=value", set to null if no parameter
{
	var processingString = "Saving...";

	// display temporary message
	document.getElementById( "dialog_message" ).innerHTML = processingString;


	if ( param )		// parameters are specified
	{
		// construct parameters as array
		var parameters = new Array();

		for ( var i = 2; i < arguments.length; i++ )
			parameters.push(arguments[i]);


		// call ajax function with parameters
		ajax( 'dialog', 'dialog_message', 'innerHTML', phpFunction, parameters );
	}
	else				// no parameters
	{
		// call ajax function without parameters
		ajax( 'dialog', 'dialog_message', 'innerHTML', phpFunction );
	}


	if ( document.getElementById( "dialog_message" ).innerHTML != processingString )
	{
		var returnMessage = document.getElementById( "dialog_message" ).innerHTML.toLowerCase();
		var returnMessageType;


		// determine dialog type based on keyword
		if ( returnMessage.search("error") >= 0 || returnMessage.search("exception") >= 0 )
			returnMessageType = "error";
		else if ( returnMessage.search("warning") >= 0 || returnMessage.search("notice") >= 0 )
			returnMessageType = "warning";
		else
			returnMessageType = "success";


		// display notification
		if ( returnMessageType == "success" )
		{
			var message = document.getElementById( "dialog_message" ).innerHTML;
			message = message + "<br /><br /><div id=\"dialog_buttons\">";
			message = message + "<input type=\"button\" value=\"OK\" onclick=\"hideDialog()\" />";
			message = message + "</div>";
			
			showDialog( dialogTitle, message, 'success' );
			return true;
		}
		else
		{
			var message = document.getElementById( "dialog_message" ).innerHTML;
			message = message + "<br /><br /><div id=\"dialog_buttons\">";
			message = message + "<input type=\"button\" value=\"Close\" onclick=\"hideDialog()\" />";
			message = message + "</div>";

			showDialog( dialogTitle, message, returnMessageType );
			return false;
		}
	}
	else
	{
		ajaxParamMissing();
		return false;
	}
}



// display error message if the required parameter is missing
function ajaxParamMissing()
{
	var message = "<b>Warning:</b> AJAX function returned empty response.<br />";
	message = message + "This might be due to some required parameters missing.<br /><br />";
	message = message + "If this error continues to appear, please contact the programmers.<br /><br />";
	message = message + "<div id=\"dialog_buttons\">";
	message = message + "<input type=\"button\" value=\"Close\" onclick=\"hideDialog()\" />";
	message = message + "</div>";


	if ( typeof showDialog == 'function' )
		showDialog( "Program Error", message, "warning" );
	else
		alert( 'Warning:\nAJAX function returned empty response.\n' +
			   'This might be due to some required parameters missing.\n\n' +
			   'If this error continues to appear, please contact the programmers.' );
}



// display error message when AJAX connectivity is lost
function ajaxConnectionLost()
{
	var message = "Oops! Seems like you lost connectivity to the server.<br />";
	message = message + "Please try again in a few minutes.<br /><br />";
	message = message + "If the error continues to appear, please contact your Administrator.<br /><br />";
	message = message + "<div id=\"dialog_buttons\">";
	message = message + "<input type=\"button\" value=\"Close\" onclick=\"hideDialog()\" />";
	message = message + "</div>";


	if ( typeof showDialog == 'function' )
		showDialog( "Error", message, 'network_error' );
	else
		alert( 'Network Error:\nOops! Seems like you lost connectivity to the server.\n' +
			   'In addition, an exception was generated.\n' +
			   'Exception: dialog.js missing\n\n' +
			   'Please contact your administrator and programmers.' );
}



// filter for input via AJAX
function filterAjaxInput( inputStr )
{
	return escape( inputStr );
}



// display AJAX connection status message
function showAJAXConnectionMessage()
{
	// prepare AJAX connection status message
	if ( $('#ajaxStatusText').length == 0 ) {
		var ajaxStatusText       = document.createElement( "div" );
		ajaxStatusText.id        = 'ajaxStatusText';
		ajaxStatusText.innerHTML = "Connecting to server....";
		$('#content').append( ajaxStatusText )
	} else {
		$('#ajaxStatusText').show();
	}
}



// hide AJAX connection status message
function hideAJAXConnectionMessage()
{
	if ( $('#ajaxStatusText').length > 0 ) {
		$('#ajaxStatusText').delay(700).fadeOut(300);
	}
}


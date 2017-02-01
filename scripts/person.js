// person.js
// Complementary script for Person class (Person.php)



// ----------------------------------------------------------------
// Auto-suggest Script for Person and Person-based objects
// ----------------------------------------------------------------

var editLocked = false;
var fillFields = false;
var lockFields = false;


// check if name exists
function fillInputFields( personType, callback, parameter )
{
	ajax( null, null, 'inline', callback, parameter );
	var personInfo = JSON.parse( ajaxResponseText );

	if ( parseInt( personInfo["id"] ) > 0 )
	{
		// name exists, fill and disable input fields
		
		var queryMode = document.getElementById( personType+"_query_mode" ).value;
		
		
		if ( queryMode == "new" )
		{
			document.getElementById( personType+"_id" ).value = personInfo["id"];
			document.getElementById( personType+"_query_mode" ).value = "locked";
			fillFields = true;
			lockFields = true;
		}
		else if ( queryMode == "edit" )
		{
			if ( personInfo["id"] == document.getElementById( personType+"_id" ).value )
			{
				if ( editLocked == true )
				{
					fillFields = true;
					
					if ( document.getElementById( "tracking_number" ) == null )			// page is not Add Order
						lockFields = false;
					else
						lockFields = true;
					
					editLocked = false;
				}
				else
				{
					fillFields = false;
					lockFields = false;
				}				
			}
			else
			{
				fillFields = true;
				lockFields = true;
				editLocked = true;
			}
		}


		if ( fillFields == true )
		{
			if ( document.getElementById( "contact_person" ) != null )
			{
				if ( personInfo["contact_person"] != null )
					document.getElementById( "contact_person" ).value = personInfo["contact_person"];
				else
					document.getElementById( "contact_person" ).value = "";
			}


			if ( document.getElementById( "address" ) != null )
				document.getElementById( "address" ).value = personInfo["address"];


			if ( personInfo["telephone"] != null )
				document.getElementById( "telephone" ).value = personInfo["telephone"];
			else
				document.getElementById( "telephone" ).value = "";


			if ( personInfo["mobile"] != null )
				document.getElementById( "mobile" ).value = personInfo["mobile"];
			else
				document.getElementById( "mobile" ).value = "";


			if ( personInfo["fax"] != null )
				document.getElementById( "fax" ).value = personInfo["fax"];
			else
				document.getElementById( "fax" ).value = "";


			if ( personInfo["email"] != null )
				document.getElementById( "email" ).value = personInfo["email"];
			else
				document.getElementById( "email" ).value = "";


			if ( lockFields == true )
				lockInputFields();
			

			return personInfo;		// return result for further processing of child classes
		}
		else
			return null;
	}
	else
		return null;			// no match found
}



// fill and disable input fields
function lockInputFields( disabledStatus )
{
	if ( disabledStatus == null )
		disabledStatus = true;
	
	if ( document.getElementById( "contact_person" ) != null )
		document.getElementById( "contact_person" ).disabled = disabledStatus;

	if ( document.getElementById( "address" ) != null )
		document.getElementById( "address" ).disabled = disabledStatus;
	
	document.getElementById( "telephone" ).disabled = disabledStatus;
	document.getElementById( "mobile" ).disabled = disabledStatus;
	document.getElementById( "fax" ).disabled = disabledStatus;
	document.getElementById( "email" ).disabled = disabledStatus;

	if ( document.getElementById( "agent_id" ) == null )		// page is not Add Order/Purchase, proceed to disable submit button
	{
		if ( disabledStatus == true )
			disableSubmitButton();
		else
			enableSubmitButton();
	}
}



// clear and enable input fields
function enableInputFields( personType )
{
	if ( document.getElementById( personType+"_query_mode" ).value == "locked" || editLocked == true )		// fields are disabled
	{
		// enable fields and reset value
		if ( document.getElementById( personType+"_query_mode" ).value == "locked" )
		{
			document.getElementById( personType+"_id" ).value = "null";
			document.getElementById( personType+"_query_mode" ).value = "new";
		}

		if ( document.getElementById( "contact_person" ) != null )
			document.getElementById( "contact_person" ).value = "";

		if ( document.getElementById( "address" ) != null )
			document.getElementById( "address" ).value = "";

		document.getElementById( "telephone" ).value = "";
		document.getElementById( "mobile" ).value = "";
		document.getElementById( "fax" ).value = "";
		document.getElementById( "email" ).value = "";

		lockInputFields( false );

		return true;
	}
	else				// fields are already enabled, no further processing needed
		return false;
}



/*function getKeyPressed( event )
{
	// character codes
	var BACKSPACE = 8;
	var TAB = 9;
	var ENTER = 13;
	var SHIFT = 16;
	var ESC = 27;
	var KEYUP = 38;
	var KEYDN = 40;
	var DELETE = 46;

	if ( event.keyCode == ENTER || event.keyCode == ESC )
	{
		fillCustomerInputFields();
		if ( event.keyCode == ENTER )
			return false;		// prevent autosubmission
	}
	else
	{
		if ( fieldsLock )
		{
			enableCustomerInputFields();
		}
	}
}*/

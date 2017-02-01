// form.js
// Generic script for form validation



function enableSubmitButton()
{
	document.getElementById( "submit_form" ).disabled = false;
	document.getElementById( "submit_form" ).style.color = "#000000";
}



function disableSubmitButton()
{
	document.getElementById( "submit_form" ).disabled = true;
	document.getElementById( "submit_form" ).style.color = "#CCCCCC";
}





// confirm if user wants to reset the form
function confirmReset( callback, parameter ) {
	var response = confirm('Are you sure you want to reset the form?\nAll fields will be cleared and reverted back to their default values.');

	if ( response == true && callback != null ) {
		if ( parameter == null ) {
			window[callback]();
		} else {
			window[callback](parameter);
		}
	}

	return response;
}

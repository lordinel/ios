// user.js
// Complementary script for User class (User.php)



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

	if ( document.getElementById( "tracking_number" ) == null )		// page is not Add Order/Purchase, proceed to disable submit button
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



// load form events
$(document).ready( function() {
	if ( $('#user_query_mode').val() == "new" ) {
		// initially hide error messages
		$('.inline_error_msg').hide();

		// check username on blur
		$('#username').blur( function()	{ validateUsername() } );

		$('#username').focus( function()
		{
			$('#username').css( 'color', 'black' );
		} );

		$('#initial_password').blur( function() { validateInitialPassword() } );
		$('#retype_password').blur( function() { validateInitialPassword() } );
	}
} );



function validateUsername()
{
	var usernameInput = $('#username').val();

	// check if username is empty
	if ( usernameInput.length == 0 ) {
		$('.inline_msg:eq(0)').fadeOut('fast');
		$('.inline_msg:eq(0)').css( 'margin-bottom', '0' );
		return;
	}

	$('.inline_msg:eq(0)').fadeIn('fast');
	$('.inline_msg:eq(0)').css( 'margin-bottom', '5px' );

	// check username for valid characters
	var pattern = /^[a-zA-Z_]([A-Za-z0-9_]{7,20})$/;
	if ( !pattern.test( usernameInput ) ) {
		$('#username').css( 'color', 'red' );
		$('#valid_username_flag').val( 'false' );
		$('.inline_msg:eq(0)').html( '<span class="bad">Error: </span> Invalid username. Please follow the rules.' );
		disableSubmitButton();
		return;
	}

	// check if username exist
	$.post( 'controls/ajax.php', {
		phpFunction : "User::isUserIDExist",
		userID      : usernameInput
	}, function( result ) {
		if ( result.user_exist ) {
			$('#username').css( 'color', 'red' );
			$('#valid_username_flag').val( 'false' );
			$('.inline_msg:eq(0)').html( '<span class="bad">Error: </span> Username already exists.' );
			disableSubmitButton();
		} else {
			$('#username').css( 'color', 'black' );
			$('#valid_username_flag').val( 'true' );
			$('.inline_msg:eq(0)').html( '<span class="good">Valid username &#10003;</span>' );
			if ( $('#valid_password_flag').val() == 'true' ) {
				enableSubmitButton();
			}
		}
	}, 'json' );
}


function validateInitialPassword()
{
	var initialPassword = $('#initial_password').val();
	var retypePassword  = $('#retype_password').val();

	// check only if fields are not empty
	if ( initialPassword.length > 0 && retypePassword.length > 0 ) {
		$('.inline_msg:eq(1)').fadeIn('fast');
		$('.inline_msg:eq(1)').css( 'margin-bottom', '5px' );

		// check if passwords match
		if ( initialPassword != retypePassword ) {
			$('#valid_password_flag').val( 'false' );
			$('.inline_msg:eq(1)').html( '<span class="bad">Error: </span> Passwords don\'t match. Please retype passwords.' );
			disableSubmitButton();
			return;
		}

		// check password validity based on password rules
		if ( $('#username').val().length > 0 ) {
			var validPassword = validatePassword( initialPassword, {
				length:   [8, Infinity],
				lower:    1,
				upper:    1,
				numeric:  1,
				special:  1,
				badWords: ["password", $('#username').val()]
			});
		} else {
			var validPassword = validatePassword( initialPassword, {
				length:   [8, Infinity],
				lower:    1,
				upper:    1,
				numeric:  1,
				special:  1,
				badWords: ["password"]
			});
		}


		if ( !validPassword ) {
			$('#valid_password_flag').val( 'false' );
			$('.inline_msg:eq(1)').html( '<span class="bad">Error:</span> Invalid new password entered. Please follow the rules in creating passwords.' );
			disableSubmitButton();
		} else {
			$('#valid_password_flag').val( 'true' );
			$('.inline_msg:eq(1)').html( '<span class="good">Valid password &#10003;</span>' );
			if ( $('#valid_username_flag').val() == 'true' ) {
				enableSubmitButton();
			}
		}
	}
}



// show Change Password dialog
function showChangePasswordDialog( userID )
{
	var dialogMessage;

	dialogMessage = '<form name="change_password" method="post" action="javascript:changePassword(\'' + userID + '\')">' +
					'<div id="password_message" style="display: none;"></div>' +
					'<input type="hidden" name="password_saved_flag" id="password_saved_flag" value="false" />' +
					'<div><span class="record_label">Current Password:</span>' +
					'<span class="record_data">' +
					'<input type="password" name="old_password" id="old_password" required="required" autofocus="autofocus" />' +
					'</span></div><br /><br />' +
					'<div><span class="record_label">New Password:</span>' +
					'<span class="record_data">' +
					'<input type="password" name="new_password" id="new_password" required="required" />' +
					'</span></div>' +
					'<div><span class="record_label">Retype Password:</span>' +
					'<span class="record_data">' +
					'<input type="password" name="retype_password" id="retype_password" required="required" />' +
					'</span></div>' +
					'<div id="invalid_password_msg" style="display: none;"></div>' +
					'<div id="password_rules">' +
					'Your password must be:<br />' +
					'- not the same as your username<br />' +
					'- not &quotpassword&quot<br />' +
					'- not the same as your old password<br />' +
					'- at least 8 characters in length<br />' +
					'- with at least 1 uppercase letter<br />' +
					'- with at least 1 lowercase letter<br />' +
					'- with at least 1 number<br />' +
					'- with at least 1 special character' +
					'</div><br /><br />' +
					'<div id="dialog_buttons">' +
					'<input type="submit" value="OK" />' +
					'<input type="button" value="Cancel" onclick="hideDialog()" id="cancel_button" />' +
					'</div></form>';

	showDialog('Change Password', dialogMessage, 'prompt');
	$('#old_password').focus();
}


// change password
function changePassword( userID )
{
	var oldPassword    = $('#old_password').val();
	var newPassword    = $('#new_password').val();
	var retypePassword = $('#retype_password').val();


	// check if passwords match
	if ( newPassword != retypePassword ) {
		$('#invalid_password_msg').hide();
		$('#invalid_password_msg').html( '<span class="bad">Error:</span> Passwords don\'t match. Please retype passwords.' );
		$('#invalid_password_msg').fadeIn( 800 );
		return;
	}


	// check if new password is the same as old password
	if ( newPassword == oldPassword ) {
		$('#invalid_password_msg').hide();
		$('#invalid_password_msg').html( '<span class="bad">Error:</span> You should not use your old password as new password.' );
		$('#invalid_password_msg').fadeIn( 800 );
		return;
	}


	// check password validity based on password rules
	var validPassword = validatePassword( newPassword, {
		length:   [8, Infinity],
		lower:    1,
		upper:    1,
		numeric:  1,
		special:  1,
		badWords: ["password", userID, oldPassword]
	});

	if ( !validPassword ) {
		$('#invalid_password_msg').hide();
		$('#invalid_password_msg').html( '<span class="bad">Error:</span> Invalid new password entered. Please follow the rules in creating passwords.' );
		$('#invalid_password_msg').fadeIn( 800 );
		return;
	}


	// perform changing of password
	$.post( 'controls/ajax.php',
		{
			phpFunction : "User::changePassword",
			userID      : userID,
			oldPassword : oldPassword,
			newPassword : newPassword
		},
		function( passwordChange ) {
			if ( passwordChange.is_success ) {
				// password change is successful
				var dialogMessage;
				dialogMessage = '<div>Password was changed successfully!</div><br /><br /><br /><br /><br /><br />' +
					'<input type="hidden" name="password_saved_flag" id="password_saved_flag" value="true" />' +
					'<div id="dialog_buttons">' +
					'<input type="button" value="OK" onclick="hideDialog()" />' +
					'</div></form>';
				$('#dialog_message').hide();
				$('#dialog_header').attr( 'class', 'success_header' );
				$('#dialog_content').attr( 'class', 'success' );
				$('#dialog_message').fadeIn( 'fast' );
				$('#dialog_message').html( dialogMessage );
				$('#password_expiry_date').html( passwordChange.password_expiry_date );
				$('#last_password_change').html( passwordChange.last_password_change_date + ' on Terminal: ' +
												 passwordChange.last_password_change_terminal + ' (IP Address: ' +
												 passwordChange.last_password_change_ip_address + ')' );


			} else {
				// old password is incorrect
				$('#invalid_password_msg').hide();
				$('#invalid_password_msg').html( '<span class="bad">Error:</span> Old password is incorrect' );
				$('#invalid_password_msg').fadeIn( 800 );
				$('#old_password').val( '' );
				$('#old_password').focus();
			}
		}, 'json'
	);
}



// show Reset Password dialog
function showResetPasswordDialog( userID )
{
	var dialogMessage;

	dialogMessage = '<form name="reset_password" method="post" action="javascript:resetPassword(\'' + userID + '\')">' +
		'<div><span class="record_label">Initial Password:</span>' +
		'<span class="record_data">' +
		'<input type="password" name="new_password" id="new_password" required="required" />' +
		'</span></div>' +
		'<div><span class="record_label">Retype Password:</span>' +
		'<span class="record_data">' +
		'<input type="password" name="retype_password" id="retype_password" required="required" />' +
		'</span></div>' +
		'<div id="invalid_password_msg" style="display: none;"></div>' +
		'<div id="password_rules">' +
		'The user\'s initial password must be:<br />' +
		'- not the same as his/her username<br />' +
		'- not &quotpassword&quot<br />' +
		'- not the same as user\'s old password<br />' +
		'- at least 8 characters in length<br />' +
		'- with at least 1 uppercase letter<br />' +
		'- with at least 1 lowercase letter<br />' +
		'- with at least 1 number<br />' +
		'- with at least 1 special character' +
		'</div><br /><br />' +
		'<div id="dialog_buttons">' +
		'<input type="submit" value="OK" />' +
		'<input type="button" value="Cancel" onclick="hideDialog()" />' +
		'</div></form>';

	showDialog('Reset Password', dialogMessage, 'prompt');
	$('#new_password').focus();
}


// reset password
function resetPassword( userID )
{
	var newPassword    = $('#new_password').val();
	var retypePassword = $('#retype_password').val();


	// check if passwords match
	if ( newPassword != retypePassword ) {
		$('#invalid_password_msg').hide();
		$('#invalid_password_msg').html( '<span class="bad">Error:</span> Passwords don\'t match. Please retype passwords.' );
		$('#invalid_password_msg').fadeIn( 800 );
		return;
	}


	// check password validity based on password rules
	var validPassword = validatePassword( newPassword, {
		length:   [8, Infinity],
		lower:    1,
		upper:    1,
		numeric:  1,
		special:  1,
		badWords: ["password", userID]
	});

	if ( !validPassword ) {
		$('#invalid_password_msg').hide();
		$('#invalid_password_msg').html( '<span class="bad">Error:</span> Invalid new password entered. Please follow the rules in creating passwords.' );
		$('#invalid_password_msg').fadeIn( 800 );
		return;
	}


	// perform changing of password
	$.post( 'controls/ajax.php',
		{
			phpFunction : "User::changePassword",
			userID      : userID,
			newPassword : newPassword
		},
		function( passwordChange ) {
			if ( passwordChange.is_success ) {
				// password change is successful
				var dialogMessage;
				dialogMessage = '<div>Password was reset successfully!</div><br /><br /><br /><br /><br /><br />' +
					'<div id="dialog_buttons">' +
					'<input type="button" value="OK" onclick="hideDialog()" />' +
					'</div></form>';
				$('#dialog_message').hide();
				$('#dialog_header').attr( 'class', 'success_header' );
				$('#dialog_content').attr( 'class', 'success' );
				$('#dialog_message').fadeIn( 'fast' );
				$('#dialog_message').html( dialogMessage );
				$('#password_expiry_date').html( passwordChange.password_expiry_date );
				$('#last_password_change').html( passwordChange.last_password_change_date + ' on Terminal: ' +
												 passwordChange.last_password_change_terminal + ' (IP Address: ' +
												 passwordChange.last_password_change_ip_address + ')' );
			} else {
				var dialogMessage;
				dialogMessage = '<div><span class="bad">Error:</span> Operation failed</div><br /><br /><br /><br /><br /><br />' +
					'<div id="dialog_buttons">' +
					'<input type="button" value="Close" onclick="hideDialog()" />' +
					'</div></form>';
				$('#dialog_message').hide();
				$('#dialog_header').attr( 'class', 'error_header' );
				$('#dialog_content').attr( 'class', 'error' );
				$('#dialog_message').fadeIn( 'fast' );
				$('#dialog_message').html( dialogMessage );
			}
		}, 'json'
	);
}



// show Unlock User dialog
function showLockUnlockUserDialog( userID, action )
{
	if ( action != 'unlock' && action != 'lock' ) {
		alert( 'Programming Error: Invalid action received' );
	}

	var dialogMessage;

	if ( action == 'unlock' ) {
		dialogMessage = '<div>Are you sure you want to <b>unlock</b> '+ userID + '?</div><br /><br /><br /><br /><br /><br />' +
						'<div id="dialog_buttons">' +
						'<input type="button" value="OK" onclick="javascript:unlockUser(\'' + userID + '\')" />' +
						'<input type="button" value="Cancel" onclick="hideDialog()" />' +
						'</div></form>';

		showDialog('Unlock User', dialogMessage, 'prompt');
	} else {
		dialogMessage = '<div>Are you sure you want to <b>lock</b> '+ userID + '?</div><br /><br /><br /><br /><br /><br />' +
			'<div id="dialog_buttons">' +
			'<input type="button" value="OK" onclick="javascript:lockUser(\'' + userID + '\')" />' +
			'<input type="button" value="Cancel" onclick="hideDialog()" />' +
			'</div></form>';

		showDialog('Lock User', dialogMessage, 'prompt');
	}
}


// unlock user
function unlockUser( userID )
{
	// perform ch
	$.post( 'controls/ajax.php',
		{
			phpFunction : "User::unlockUser",
			userID      : userID
		},
		function( userUnlocked ) {
			if ( userUnlocked.isSuccess ) {
				// unlocking user is successful
				var dialogMessage;
				dialogMessage = '<div>' + userID + ' was successfully <b>unlocked</b>!</div><br /><br /><br /><br /><br /><br />' +
					'<div id="dialog_buttons">' +
					'<input type="button" value="OK" onclick="hideDialog()" />' +
					'</div></form>';
				$('#dialog_message').hide();
				$('#dialog_header').attr( 'class', 'success_header' );
				$('#dialog_content').attr( 'class', 'success' );
				$('#dialog_message').fadeIn( 'fast' );
				$('#dialog_message').html( dialogMessage );
				$('#account_status').html( 'Not Locked' );
				$('#account_status').switchClass( "bad", "good", 1000 );
				$('#task_unlock_user').css( 'display', 'none' );
				$('#task_lock_user').css( 'display', 'inline' );
			} else {
				var dialogMessage;
				dialogMessage = '<div><span class="bad">Error:</span> Operation failed</div><br /><br /><br /><br /><br /><br />' +
					'<div id="dialog_buttons">' +
					'<input type="button" value="Close" onclick="hideDialog()" />' +
					'</div></form>';
				$('#dialog_message').hide();
				$('#dialog_header').attr( 'class', 'error_header' );
				$('#dialog_content').attr( 'class', 'error' );
				$('#dialog_message').fadeIn( 'fast' );
				$('#dialog_message').html( dialogMessage );
			}
		}, 'json'
	);
}


// lock user
function lockUser( userID )
{
	// perform ch
	$.post( 'controls/ajax.php',
		{
			phpFunction : "User::lockUser",
			userID      : userID
		},
		function( userLocked ) {
			if ( userLocked.isSuccess ) {
				// unlocking user is successful
				var dialogMessage;
				dialogMessage = '<div>' + userID + ' was successfully <b>locked</b>!</div><br /><br /><br /><br /><br /><br />' +
					'<div id="dialog_buttons">' +
					'<input type="button" value="OK" onclick="hideDialog()" />' +
					'</div></form>';
				$('#dialog_message').hide();
				$('#dialog_header').attr( 'class', 'success_header' );
				$('#dialog_content').attr( 'class', 'success' );
				$('#dialog_message').fadeIn( 'fast' );
				$('#dialog_message').html( dialogMessage );
				$('#account_status').html( 'Locked' );
				$('#account_status').switchClass( "good", "bad", 1000 );
				$('#task_unlock_user').css( 'display', 'inline' );
				$('#task_lock_user').css( 'display', 'none' );
			} else {
				var dialogMessage;
				dialogMessage = '<div><span class="bad">Error:</span> Operation failed</div><br /><br /><br /><br /><br /><br />' +
					'<div id="dialog_buttons">' +
					'<input type="button" value="Close" onclick="hideDialog()" />' +
					'</div></form>';
				$('#dialog_message').hide();
				$('#dialog_header').attr( 'class', 'error_header' );
				$('#dialog_content').attr( 'class', 'error' );
				$('#dialog_message').fadeIn( 'fast' );
				$('#dialog_message').html( dialogMessage );
			}
		}, 'json'
	);
}


function validatePassword ( pw, options )
{
	// default options (allows any password)
	var o = {
		lower:    0,
		upper:    0,
		alpha:    0, /* lower + upper */
		numeric:  0,
		special:  0,
		length:   [0, Infinity],
		custom:   [ /* regexes and/or functions */ ],
		badWords: [],
		badSequenceLength: 0,
		noQwertySequences: false,
		noSequential:      false
	};

	for (var property in options)
		o[property] = options[property];

	var	re = {
			lower:   /[a-z]/g,
			upper:   /[A-Z]/g,
			alpha:   /[A-Z]/gi,
			numeric: /[0-9]/g,
			special: /[\W_]/g
		},
		rule, i;

	// enforce min/max length
	if (pw.length < o.length[0] || pw.length > o.length[1])
		return false;

	// enforce lower/upper/alpha/numeric/special rules
	for (rule in re) {
		if ((pw.match(re[rule]) || []).length < o[rule])
			return false;
	}

	// enforce word ban (case insensitive)
	for (i = 0; i < o.badWords.length; i++) {
		if (pw.toLowerCase().indexOf(o.badWords[i].toLowerCase()) > -1)
			return false;
	}

	// enforce the no sequential, identical characters rule
	if (o.noSequential && /([\S\s])\1/.test(pw))
		return false;

	// enforce alphanumeric/qwerty sequence ban rules
	if (o.badSequenceLength) {
		var	lower   = "abcdefghijklmnopqrstuvwxyz",
			upper   = lower.toUpperCase(),
			numbers = "0123456789",
			qwerty  = "qwertyuiopasdfghjklzxcvbnm",
			start   = o.badSequenceLength - 1,
			seq     = "_" + pw.slice(0, start);
		for (i = start; i < pw.length; i++) {
			seq = seq.slice(1) + pw.charAt(i);
			if (
				lower.indexOf(seq)   > -1 ||
					upper.indexOf(seq)   > -1 ||
					numbers.indexOf(seq) > -1 ||
					(o.noQwertySequences && qwerty.indexOf(seq) > -1)
				) {
				return false;
			}
		}
	}

	// enforce custom regex/function rules
	for (i = 0; i < o.custom.length; i++) {
		rule = o.custom[i];
		if (rule instanceof RegExp) {
			if (!rule.test(pw))
				return false;
		} else if (rule instanceof Function) {
			if (!rule(pw))
				return false;
		}
	}

	// great success!
	return true;
}

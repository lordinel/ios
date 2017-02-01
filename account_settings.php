<?php
	require_once( "controls/autoload.php" );
	$PAGE_NAME = "Account Settings";

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	$htmlLayout->loadScript( "user" );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );


	if ( isset( $_POST['submit_form'] ) ) {		// form is submitted, save user details
		$userIsSaved = $htmlLayout->user->save();
	}


	$htmlLayout->paint();
	$htmlLayout->showMainMenu();


	if ( isset( $_GET['mode'] ) ) {
		if ( $_GET['mode'] == 'edit' ) {
			// show edit form
			$htmlLayout->showPageHeading( 'account.png', true );
			$htmlLayout->user->showSettingsForm();
		} elseif ( $_GET['mode'] == 'first_login' || $_GET['mode'] == 'password_expired' ) {
			// display password change prompt
			$htmlLayout->showPageHeading( 'account.png', true );
			$htmlLayout->user->showSettingsForm();

			if ( $_GET['mode'] == 'first_login' ) {
				$dialogTitle = 'First Login';
				$passwordMessage = '<b>Welcome to ' . PROG_NAME . ", " . $htmlLayout->user->getUserName() . '!</b><br /><br />' .
								   'Before you can use ' . PROG_NAME . ', you must change your initial password. ' .
								   'Kindly change you password below:<br /><br />';
			} else {
				$dialogTitle = 'Password Expired';
				$passwordMessage = 'Your password had already expired. Please change your password.<br /><br />';
			}

			?>
			<script type="text/javascript">
				<!--
				$(document).ready( function() {
					showChangePasswordDialog('<?php echo $htmlLayout->user->getUserID() ?>');
					$('#dialog_title').html( '<?php echo $dialogTitle ?>' );
					$('#dialog_close').removeAttr( 'onclick' );
					$('#dialog_close').hide();

					$('#cancel_button').removeAttr( 'onclick' );
					$('#cancel_button').hide();

					$('#password_message').html( '<?php echo $passwordMessage ?>' );
					$('#password_message').show();
				});

				$(window).unload( function() {
					if ( $('#password_saved_flag').val() == 'false' ) {
						document.location = "index.php?action=logout";
					}
				});
				// -->
			</script>
			<?php
		} else {		// invalid mode
			?>
			<script type="text/javascript">
				<!--
				document.location = "account_settings.php";
				// -->
			</script>
			<?php
		}
	} else {
		// display user
		$htmlLayout->showPageHeading( 'account.png' );
		$htmlLayout->user->showDetailsTasks();
		$htmlLayout->user->view();
	}

?>

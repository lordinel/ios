<?php

	$PAGE_NAME = "Edit User";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	$htmlLayout->paint();

	if ( !isset( $_GET['id'] ) || ( $htmlLayout->user->getUserID() != 'administrator' && !$htmlLayout->user->checkPermission('manage_users') ) ) {
		// user ID is not specified or the user is not authorized to edit
?>
		<script type="text/javascript">
		<!--
			document.location = "index.php";
		// -->
		</script>
<?php
	}

	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'users.png', true );
	User::showSettingsForm( 'edit', $_GET['id'] );
?>

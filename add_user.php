<?php

	$PAGE_NAME = "Add User";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	$htmlLayout->loadScript( "user" );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'users.png', true );

	User::showSettingsForm( "new" );
?>

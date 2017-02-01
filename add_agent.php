<?php

	$PAGE_NAME = "Add Agent";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	
	$htmlLayout->loadScript( "person" );
	$htmlLayout->loadScript( "agent" );
	
	$htmlLayout->loadScript( "ajax" );
	
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'agents.png' );
	Agent::showDefaultTasks('list_agents.php');

	Agent::showInputForm();

?>

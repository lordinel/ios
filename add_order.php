<?php
	$PAGE_NAME = "Add Order";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	
	$htmlLayout->loadScript( "person" );
	$htmlLayout->loadScript( "customer" );
	
	$htmlLayout->loadScript( "data" );
	$htmlLayout->loadScript( "payment" );
	$htmlLayout->loadScript( "inventory" );
	$htmlLayout->loadScript( "transaction" );
	$htmlLayout->loadScript( "order" );
	
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'orders.png' );
	Order::showDefaultTasks('list_orders.php');

	Order::showInputForm();
?>

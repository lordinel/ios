<?php

	$PAGE_NAME = "Edit Supplier";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	
	$htmlLayout->loadScript( "person" );
	$htmlLayout->loadScript( "supplier" );
	
	$htmlLayout->loadScript( "ajax" );
	
	$htmlLayout->paint();

	if ( !isset( $_GET['id'] ) )			// customer ID is not specified
	{
?>
		<script type="text/javascript">
		<!--
			document.location = "index.php";
		// -->
		</script>
<?php
	}

	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'suppliers.png', true );

	Supplier::showInputForm( Filter::input( $_GET['id'] ) );
?>

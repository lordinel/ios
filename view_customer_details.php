<?php

	$PAGE_NAME = "Customer Details";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'customers.png' );


	$database = new Database();

	if ( isset( $_POST['submit_form'] ) )		// new customer to be saved to database
	{
		$customer = new Customer( $database );
		$customer->save();

		// @todo show notification of successful save

		$customer->showDetailsTasks();
		$customer->view();

		unset( $_POST );
	}
	elseif ( isset( $_GET['id'] ) )				// view customer records
	{
		$customer = new Customer( $database, $_GET['id'] );
		$customer->showDetailsTasks();
		$customer->view();
	}
	else										// required parameters missing, redirect page
	{
?>
		<script type="text/javascript">
		<!--
			document.location = "index.php";
		// -->
		</script>
<?php
	}

?>

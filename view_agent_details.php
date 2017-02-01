<?php

	$PAGE_NAME = "Agent Details";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'agents.png' );


	$database = new Database();

	if ( isset( $_POST['submit_form'] ) )		// new customer to be saved to database
	{
		$agent = new Agent( $database );
		$agent->save();

		// @todo show notification of successful save

		$agent->showDetailsTasks();
		$agent->view();

		unset( $_POST );
	}
	elseif ( isset( $_GET['id'] ) )				// view customer records
	{
		$agent = new Agent( $database, $_GET['id'] );
		$agent->showDetailsTasks();
		$agent->view();
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

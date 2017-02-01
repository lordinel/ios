<?php
	$PAGE_NAME = "Supplier Details";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'suppliers.png' );


	$database = new Database();

	if ( isset( $_POST['submit_form'] ) )		// new customer to be saved to database
	{
		$supplier = new Supplier( $database );
		$supplier->save();

		// @todo show notification of successful save

		$supplier->showDetailsTasks();
		$supplier->view();

		unset( $_POST );
	}
	elseif ( isset( $_GET['id'] ) )				// view customer records
	{
		$supplier = new Supplier( $database, $_GET['id'] );
		$supplier->showDetailsTasks();
		$supplier->view();
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

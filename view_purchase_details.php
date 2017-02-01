<?php
	$PAGE_NAME = "Purchase Details";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	
	$htmlLayout->loadScript( "data" );
	$htmlLayout->loadScript( "payment" );
	$htmlLayout->loadScript( "inventory" );
	$htmlLayout->loadScript( "transaction" );
	$htmlLayout->loadScript( "purchase" );
	
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();

	$database = new Database();



	if ( isset( $_POST['submit_form'] ) )		// new order to be saved to database
	{
		// save customer info
		$supplier = new Supplier( $database );
		$supplierID = $supplier->save();
		
		
		// log audit
		//Audit::logEvent( $database, username, "insert", "customer", $customerID );
		
		
		// save purchase info
		$purchase = new Purchase( $database );
		$purchaseID = $purchase->save( $supplierID );


		// log audit
		//Audit::logEvent( $database, username, "insert", "order", $orderID );


		// show notification of successful save
		// ***

		unset( $_POST );
	}
	elseif ( isset( $_GET['id'] ) )					// view order records
	{
		$purchase = new Purchase( $database, $_GET['id'] );
		$purchaseID = $_GET['id'];
	}
	else											// required parameters missing, redirect page
	{
?>
		<script type="text/javascript">
		<!--
			document.location = "index.php";
		// -->
		</script>
<?php
	}


	$htmlLayout->showPageHeading( 'purchases.png' );
	$htmlLayout->setPageTitle( 'Purchase No. '.$purchaseID, true );
	$purchase->showDetailsTasks();

	$purchase->view();

?>
		<div id="payment_info">
		</div>
		<script type="text/javascript">
		<!--
			var data = new Data();		// Data object
			ajax(null,'payment_info','innerHTML','Payment::showSchedule','class=purchase&transactionID=<?php echo $purchaseID ?>');
		// -->
		</script>
<?php
?>

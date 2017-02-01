<?php
	$PAGE_NAME = "Order Details";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	
	$htmlLayout->loadScript( "data" );
	$htmlLayout->loadScript( "payment" );
	$htmlLayout->loadScript( "inventory" );
	$htmlLayout->loadScript( "transaction" );
	$htmlLayout->loadScript( "order" );
	
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();

	$database = new Database();



	if ( isset( $_POST['submit_form'] ) )		// new order to be saved to database
	{
		// save customer info
		$customer = new Customer( $database );
		$customerID = $customer->save();


		// save order info
		$order = new Order( $database );
		$orderID = $order->save( $customerID );

		unset( $_POST );
	}
	elseif ( isset( $_GET['id'] ) )					// view order records
	{
		$order = new Order( $database, $_GET['id'] );
		$orderID = $_GET['id'];
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


	$htmlLayout->showPageHeading( 'orders.png' );
	$htmlLayout->setPageTitle( 'Order No. '.$orderID, true );
	$order->showDetailsTasks();
	$order->view();

?>
		<div id="payment_info">
		</div>
		<script type="text/javascript">
		<!--
			var data = new Data();		// Data object
			ajax(null,'payment_info','innerHTML','Payment::showSchedule','class=order&transactionID=<?php echo $orderID ?>');
		// -->
		</script>
<?php
?>

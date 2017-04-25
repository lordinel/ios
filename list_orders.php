<?php
$PAGE_NAME = "Orders";

require_once("controls/autoload.php");

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadScript("ajax");
$htmlLayout->loadScript("dialog");
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('orders.png');
Order::showListTasks();

?>
<fieldset>
	<legend><?php
		if (isset($_GET['criteria'])) {
			switch ($_GET['criteria']) {
				case 'orders-to-deliver'			: echo 'Orders to Deliver to Customers'; 			break;
				case 'orders-awaiting-pickup'		: echo 'Orders Awaiting Pick-up by Customers'; 		break;
				case 'payments-to-collect'			: echo 'Orders with Payments to Collect'; 			break;
				case 'payments-to-clear'			: echo 'Orders with Payments to Clear'; 			break;
				case 'payments-to-collect-and-clear': echo 'Orders with Payments to Collect and Clear'; break;
				case 'rebates-to-issue'				: echo 'Orders with Rebates to Issue'; 				break;
				case 'rebates-to-clear'				: echo 'Orders with Rebates to Clear'; 				break;
				case 'waived-balance'				: echo 'Orders with Waived Balance'; 				break;
				case 'orders-to-clear'				: echo 'Orders to Clear'; 							break;
				case 'cleared-orders'				: echo 'Cleared Orders'; 							break;
				case 'canceled-orders'				: echo 'Canceled Orders'; 							break;
				case 'all-orders'					: echo 'All Orders'; 								break;
				default								: echo 'Recent Orders';
			}
		} else {
			echo 'Recent Orders';
		}
		?></legend>
	<section id="order_list_section">
	</section>
</fieldset>

<script type="text/javascript">
<!--
	ajax(null, 'order_list_section', 'innerHTML', 'Order::showList'<?php
		if (isset($_GET['criteria'])) {
			echo ", 'criteria=" . Filter::input( $_GET['criteria'] ) . "'";	  	
		}
		
		if (isset($_GET['sortColumn'])) {
			echo ", 'sortColumn=" . Filter::input( $_GET['sortColumn'] ) . "'";	  	
		}
		
		if (isset($_GET['sortMethod'])) {
			echo ", 'sortMethod=" . Filter::input( $_GET['sortMethod'] ) . "'";	  	
		}
		
		if (isset($_GET['page'])) {
			echo ", 'page=" . Filter::input( $_GET['page'] ) . "'";	  	
		}
		
		if (isset($_GET['itemsPerPage'])) {
			echo ", 'itemsPerPage=" . Filter::input( $_GET['itemsPerPage'] ) . "'";	  	
		}
		
		if (isset($_GET['filterName'])) {
			echo ", 'filterName=" . Filter::input( $_GET['filterName'] ) . "'";	  	
		}
		
		if (isset($_GET['filterValue'])) {
			echo ", 'filterValue=" . Filter::input( $_GET['filterValue'] ) . "'";	  	
		}
		?>);
// -->
</script>
<?php
?>

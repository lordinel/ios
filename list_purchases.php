<?php
	$PAGE_NAME = "Purchases";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'purchases.png' );
	Purchase::showListTasks();

?>	<fieldset><legend><?php
			if ( isset( $_GET['criteria'] ) )
			{
				switch ( $_GET['criteria'] )
				{
					case "purchases-awaiting-delivery":	echo "Purchases Awaiting Delivery from Suppliers";	break;
					case "purchases-to-pickup":			echo "Purchases to Pick-up to Suppliers";			break;
					case "purchases-to-pay":			echo "Purchases to Pay";							break;
					case "payments-to-clear":			echo "Purchases with Payments to Clear";			break;
					case "purchases-to-pay-and-clear":	echo "Purchases to Pay and Clear";					break;
					case "rebates-to-collect":			echo "Purchases with Rebates to Collect";			break;
					case "rebates-to-clear":			echo "Purchases with Rebates to Clear";				break;
					case "purchases-to-clear":			echo "Purchases to Clear";							break;
					case "cleared-purchases":			echo "Cleared Purchases";							break;
					case "canceled-purchases":			echo "Canceled Purchases";							break;
					case "all-purchases":				echo "All Purchases";								break;
					default:							echo "Recent Purchases";
				}
			}
			else
				echo "Recent Purchases";
		?></legend>
		<section id="purchase_list_section">
		</section>
	</fieldset>

	<script type="text/javascript">
		ajax( null, 'purchase_list_section', 'innerHTML', 'Purchase::showList'<?php
			if ( isset( $_GET['criteria'] ) )
				echo ", 'criteria=" . $_GET['criteria'] . "'";
		?> );
	</script>
<?php
?>

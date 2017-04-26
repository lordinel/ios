<?php
$PAGE_NAME = 'Missing Invoices';

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
	<legend>
		<?php
		if (isset($_GET['criteria'])) {
			switch ($_GET['criteria']) {
				case 'DR': echo 'Missing DRs'; break;
				case 'SI': echo 'Missing SIs'; break;
				default  : redirectToHomePage();
			}
		} else {
			redirectToHomePage();
		}
		?>
	</legend>
	<section id="missing_invoice_section">
	</section>
</fieldset>

<script type="text/javascript">
<!--
	ajax(null, 'missing_invoice_section', 'innerHTML', 'Order::showMissingInvoice', 'criteria=<?= $_GET['criteria'] ?>');
// -->
</script>
<?php
?>

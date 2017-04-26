<?php
$PAGE_NAME = 'Missing Invoices';

require_once("controls/autoload.php");

if (!isset($_GET['criteria']) || !isset($_GET['invoice_type'])) {
	redirectToHomePage();
}

switch ($_GET['criteria']) {
	case 'order'   : $headingIcon = 'orders.png';    break;
	case 'purchase': $headingIcon = 'purchases.png'; break;
	default        : redirectToHomePage();
}

if ($_GET['invoice_type'] != 'SI' && $_GET['invoice_type'] != 'DR') {
	redirectToHomePage();
}

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadScript("ajax");
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading($headingIcon, true);

?>
<fieldset>
	<legend>Missing <?= $_GET['invoice_type'] ?>s</legend>
	<section id="missing_invoice_section">
	</section>
</fieldset>

<script type="text/javascript">
<!--
	ajax(null, 'missing_invoice_section', 'innerHTML', 'Transaction::showMissingInvoice',
		 'criteria=<?= $_GET['criteria'] ?>&invoice_type=<?= $_GET['invoice_type'] ?>');
// -->
</script>
<?php
?>

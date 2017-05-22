<?php
$PAGE_NAME = 'Edit Purchase Order';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadStyle('form');
$htmlLayout->loadScript('form');
$htmlLayout->loadScript('person');
$htmlLayout->loadScript('supplier');
$htmlLayout->loadScript('data');
$htmlLayout->loadScript('payment');
$htmlLayout->loadScript('inventory');
$htmlLayout->loadScript('transaction');
$htmlLayout->loadScript('purchase');
$htmlLayout->loadScript('ajax');
$htmlLayout->loadScript('dialog');
$htmlLayout->paint();

if (!isset($_GET['id'])) {
	// purchase ID is not specified
	redirectToHomePage();
}

$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('purchases.png', true);

if (!isset($_GET['item_editable'])) {
	Purchase::showInputForm(Filter::input($_GET['id']));
} else {
	Purchase::showInputForm(Filter::input($_GET['id']), false);
}
?>

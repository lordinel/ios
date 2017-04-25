<?php
$PAGE_NAME = 'Edit Order';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadStyle('form');
$htmlLayout->loadScript('form');
$htmlLayout->loadScript('person');
$htmlLayout->loadScript('customer');
$htmlLayout->loadScript('data');
$htmlLayout->loadScript('payment');
$htmlLayout->loadScript('inventory');
$htmlLayout->loadScript('transaction');
$htmlLayout->loadScript('order');
$htmlLayout->loadScript('ajax');
$htmlLayout->loadScript('dialog');
$htmlLayout->paint();

if (!isset($_GET['id'])) {
	// order ID is not specified
	redirectToHomePage();
}

$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('orders.png', true);

if (!isset($_GET['item_editable'])) {
	Order::showInputForm(Filter::input($_GET['id']));
} else {
	Order::showInputForm(Filter::input($_GET['id']), false);
}
?>

<?php
$PAGE_NAME = 'Purchase Supplies';

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
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('purchases.png');

Purchase::showDefaultTasks('list_purchases.php');
Purchase::showInputForm();
?>

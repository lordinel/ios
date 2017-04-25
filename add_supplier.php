<?php

$PAGE_NAME = 'Add Supplier';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadStyle('form');
$htmlLayout->loadScript('form');
$htmlLayout->loadScript('person');
$htmlLayout->loadScript('supplier');
$htmlLayout->loadScript('ajax');
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('suppliers.png');

Supplier::showDefaultTasks('list_suppliers.php');
Supplier::showInputForm();
?>

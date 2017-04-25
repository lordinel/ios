<?php

$PAGE_NAME = 'Add Customer';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadStyle('form');
$htmlLayout->loadScript('form');
$htmlLayout->loadScript('person');
$htmlLayout->loadScript('customer');
$htmlLayout->loadScript('data');
$htmlLayout->loadScript('ajax');
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('customers.png');

Customer::showDefaultTasks('list_customers.php');
Customer::showInputForm();
?>

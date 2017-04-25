<?php
$PAGE_NAME = 'Supplier Details';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadScript('ajax');
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('suppliers.png');

$database = new Database();

if (isset($_POST['submit_form'])) {
	// new supplier to be saved to database
	$supplier = new Supplier($database);
	$supplier->save();
	
	// @TODO show notification of successful save
	
	$supplier->showDetailsTasks();
	$supplier->view();
	
	unset($_POST);			// to prevent post resubmission
} elseif (isset($_GET['id'])) {
	// view customer records
	$supplier = new Supplier($database, Filter::input($_GET['id']));
	$supplier->showDetailsTasks();
	$supplier->view();
} else {
	// required parameters missing; redirect page
	redirectToHomePage();
}
?>

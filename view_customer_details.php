<?php
$PAGE_NAME = 'Customer Details';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadScript("ajax");
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('customers.png');

$database = new Database();

if (isset($_POST['submit_form'])) {
	// new customer to be saved to database
	$customer = new Customer($database);
	$customer->save();
	
	// @TODO show notification of successful save
	
	$customer->showDetailsTasks();
	$customer->view();
	
	unset($_POST);			// to prevent post resubmission
} elseif (isset($_GET['id'])) {
	// view customer records
	$customer = new Customer($database, Filter::input($_GET['id']));
	$customer->showDetailsTasks();
	$customer->view();
} else {
	// required parameters missing; redirect page
	redirectToHomePage();
}
?>

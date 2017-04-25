<?php

$PAGE_NAME = 'Agent Details';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadScript('ajax');
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('agents.png');

$database = new Database();

if (isset($_POST['submit_form'])) {
	// new agent to be saved to database
	$agent = new Agent($database);
	$agent->save();
	
	// @TODO show notification of successful save
	
	$agent->showDetailsTasks();
	$agent->view();
	
	unset($_POST);		// to prevent post resubmission
} elseif (isset($_GET['id'])) {
	// view agent records
	$agent = new Agent($database, Filter::input($_GET['id']));
	$agent->showDetailsTasks();
	$agent->view();
} else {
	// required parameters missing, redirect page
	redirectToHomePage();
}

?>

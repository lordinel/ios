<?php

$PAGE_NAME = 'Edit Agent';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadStyle('form');
$htmlLayout->loadScript('form');
$htmlLayout->loadScript('person');
$htmlLayout->loadScript('agent');
$htmlLayout->loadScript('ajax');
$htmlLayout->paint();

if (!isset($_GET['id'])) {
	// agent ID is not specified
	redirectToHomePage();
}

$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('agents.png', true);

Agent::showInputForm(Filter::input($_GET['id']));
?>

<?php
require_once("controls/autoload.php");
$PAGE_NAME = "User Manual";

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('help.png', true);

echo '<fieldset><section><div id="about_content">';

echo "<div>User Manual coming soon (Bug 000279)<br /><br /><br /><br /><br /></div>";

echo '</div></section></fieldset>';
?>

<?php
$PAGE_NAME = "Inventory Details";

require_once("controls/autoload.php");

$htmlLayout = new HtmlLayout($PAGE_NAME);

$htmlLayout->loadStyle("form");
$htmlLayout->loadScript("form");

$htmlLayout->loadScript("data");
$htmlLayout->loadScript("inventory");

$htmlLayout->loadScript("ajax");
$htmlLayout->loadScript("dialog");

$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('inventory.png');

if (!isset($_GET['brandID'])) {
	redirectToHomePage();
}

$brandName = Inventory::getBrandName(Filter::input($_GET['brandID']));
if ($brandName == null) {
	redirectToHomePage();
}
echo '<div id="model_tasks">';
Inventory::showModelListTasks(Filter::input($_GET['brandID']), $brandName);
echo '</div></div>';

?>
<fieldset>
	<legend>Model List</legend>
	<section class="main_record_label">
		<div><?php
			echo capitalizeWords(Filter::output($brandName));
			?></div>
		<?php HtmlLayout::setPageTitleStatic('Inventory » ' . addslashes(html_entity_decode(capitalizeWords(Filter::output($brandName))))); ?>
	</section>
	<section id="model_list_<?php echo Filter::input($_GET['brandID']) ?>">
	</section>
</fieldset>

<script type="text/javascript">
	<!--
	var data = new Data();
	ajax(null, 'model_list_<?php echo Filter::input($_GET['brandID']) ?>', 'innerHTML', 'Inventory::showModelList', 'criteria=<?php echo Filter::input( $_GET['brandID'] ) ?>');
	// -->
</script><?php

Inventory::showModelListTotals(Filter::input($_GET['brandID']));
?>

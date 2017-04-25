<?php
$PAGE_NAME = 'Inventory » Orders';

require_once('controls/autoload.php');

// get and check URL parameter
$inventoryID = '';
if (!isset($_GET['inv-id'])) {
	redirectToHomePage();
} else {
	$inventoryID = Filter::input($_GET['inv-id']);
}

// get inventory brand name and model 
$database  = new Database();
$sqlQuery  = "SELECT name AS brand, inventory.* FROM inventory " .
			 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
			 "WHERE inventory.id = $inventoryID";
$resultSet = $database->query($sqlQuery);
if ($database->getResultCount($resultSet) == 0) {
	redirectToHomePage();
}
$inventory          = $database->getResultRow($resultSet);
$inventory['brand'] = capitalizeWords(Filter::output($inventory['brand']));
$inventory['model'] = capitalizeWords(Filter::output($inventory['model']));
$legend             = $inventory['brand'] . ' » ' . $inventory['model'];

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadScript('ajax');
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('inventory.png', true);

?>
<fieldset>
	<legend><?php echo $legend ?></legend>
	<section>
		<div>
			<span class="record_label">Brand Name:</span>
			<span class="record_data"><?= $inventory['brand'] ?></span>
		</div>
		<div>
			<span class="record_label">Model Name:</span>
			<span class="record_data"><?= $inventory['model'] ?></span>
		</div>
		<?php
		if ($inventory['description'] != null) {
			?>
			<div>
			<span class="record_label">Description:</span>
			<span class="record_data"><?= Filter::output($inventory['description']) ?></span>
			</div>
			<?php
		}
		?>
	</section>
	<section>
		<div>
			<span class="record_label">Purchase Price:</span>
			<span class="record_data"><?= numberFormat($inventory['purchase_price'], 'currency', 3, '.', ',') ?></span>
		</div>
		<div>
			<span class="record_label">Selling Price:</span>
			<span class="record_data"><?= numberFormat($inventory['selling_price'], 'currency', 3, '.', ',') ?></span>
		</div>
	</section>
	<section>
		<div>
			<span class="record_label">Total Available Stocks:</span>
			<span class="record_data"><?= $inventory['stock_count'] ?></span>
		</div>
		<div>
			<span class="record_label">Total Demand:</span>
			<span class="record_data"><?= $inventory['reserved_stock'] ?></span>
		</div>
	</section>
</fieldset>

<fieldset>
	<legend>Pending Delivery</legend>
	<section id="pending_order_list_section">
	</section>
</fieldset>

<fieldset>
	<legend>Delivered</legend>
	<section id="delivered_order_list_section">
	</section>
</fieldset>

<script type="text/javascript">
<!--
	ajax(null, 'pending_order_list_section', 'innerHTML', 'Order::showInventory', 'filterName=pending', 'filterValue=<?= $inventoryID ?>');
	ajax(null, 'delivered_order_list_section', 'innerHTML', 'Order::showInventory', 'filterName=delivered', 'filterValue=<?= $inventoryID ?>');
// -->
</script>
<?php
?>

<?php
	$PAGE_NAME = "Orders";

	require_once( "controls/autoload.php" );
	
	
	// get and check URL parameter
	if ( !isset( $_GET['inv-id'] ) ) {
		redirectToHomePage();
	} else {
		$inventoryID = Filter::input( $_GET['inv-id'] );
	}
	
	$database = new Database();
	
	$sqlQuery = "SELECT name AS brand, model, stock_count, reserved_stock FROM inventory " .
				"INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
				"WHERE inventory.id = " . $inventoryID;
	$resultSet = $database->query( $sqlQuery );
	if ( $database->getResultCount( $resultSet ) == 0 ) {
		redirectToHomePage();
	}
	
	$inventory = $database->getResultRow( $resultSet );
	$legend = 'Orders with Demand for ' . $inventory['brand'] . ' > ' . $inventory['model'];
	

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'orders.png', true );

?>	<fieldset><legend><?php echo $legend ?></legend>
		<section>
        	<div>
                <span class="record_label">Total Available Stocks:</span>
                <span class="record_data"><?php echo $inventory['stock_count'] ?></span>
            </div>
            <div>
                <span class="record_label">Total Demand:</span>
                <span class="record_data"><?php echo $inventory['reserved_stock'] ?></span>
            </div>
        </section>
		<section id="order_list_section">
		</section>
	</fieldset>

	<script type="text/javascript">
		ajax( null, 'order_list_section', 'innerHTML', 'Order::showReserved', 'filterName=inventoryID', 'filterValue=<?php echo $inventoryID ?>' );
	</script>
<?php
?>

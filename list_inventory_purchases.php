<?php
    $PAGE_NAME = "Inventory » Purchases";

	require_once( "controls/autoload.php" );
	
	
	// get and check URL parameter
	if ( !isset( $_GET['inv-id'] ) ) {
		redirectToHomePage();
	} else {
		$inventoryID = Filter::input( $_GET['inv-id'] );
	}
	

    // get inventory brand name and model 
	$database = new Database();
	$sqlQuery = "SELECT name AS brand, inventory.* FROM inventory " .
				"INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
				"WHERE inventory.id = " . $inventoryID;
	$resultSet = $database->query( $sqlQuery );
	if ( $database->getResultCount( $resultSet ) == 0 ) {
		redirectToHomePage();
	}
	$inventory = $database->getResultRow( $resultSet );
    $inventory['brand'] = capitalizeWords(Filter::output($inventory['brand']));
    $inventory['model'] = capitalizeWords(Filter::output($inventory['model']));
	$legend = $inventory['brand'] . ' » ' . $inventory['model'];
	

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
    $htmlLayout->showPageHeading( 'inventory.png', true );

?>	<fieldset><legend><?php echo $legend ?></legend>
		<section>
            <div>
                <span class="record_label">Brand Name:</span>
                <span class="record_data"><?php echo $inventory['brand'] ?></span>
            </div>
            <div>
                <span class="record_label">Model Name:</span>
                <span class="record_data"><?php echo $inventory['model'] ?></span>
            </div>
            <?php
            if ( $inventory['description'] != null ) {
                ?><div>
                    <span class="record_label">Description:</span>
                    <span class="record_data"><?php echo Filter::output($inventory['description']) ?></span>
                </div><?php
            }
            ?>
        </section>
        <section>
            <div>
                <span class="record_label">Purchase Price:</span>
                <span class="record_data"><?php echo numberFormat( $inventory['purchase_price'], "currency", 3, '.', ',' ) ?></span>
            </div>
            <div>
                <span class="record_label">Selling Price:</span>
                <span class="record_data"><?php echo numberFormat( $inventory['selling_price'], "currency", 3, '.', ',' ) ?></span>
            </div>
        </section>
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
	</fieldset>

    <fieldset><legend>Pending Delivery</legend>
        <section id="pending_purchase_list_section">
        </section>
    </fieldset>

	<script type="text/javascript">
		ajax( null, 'pending_purchase_list_section', 'innerHTML', 'Purchase::showInventory',
              'filterName=pending', 'filterValue=<?php echo $inventoryID ?>' );
	</script>

    <fieldset><legend>Delivered</legend>
        <section id="delivered_purchase_list_section">
        </section>
    </fieldset>
    
    <script type="text/javascript">
        ajax( null, 'delivered_purchase_list_section', 'innerHTML', 'Purchase::showInventory',
              'filterName=delivered', 'filterValue=<?php echo $inventoryID ?>' );
    </script>
<?php
?>

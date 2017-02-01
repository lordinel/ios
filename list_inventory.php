<?php
	$PAGE_NAME = "Inventory";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	
	$htmlLayout->loadScript( "data" );
	$htmlLayout->loadScript( "inventory" );
	
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'inventory.png' );
	Inventory::showBrandListTasks();


?>	<fieldset><legend>Brand List</legend>
		<div id="list_filter">
			<span class="filter_link selected_filter"><a href="#" onclick="ajax( null, 'brand_list_section', 'innerHTML', 'Inventory::showBrandList', null ); showFilterIndicator(this)">All</a></span><span class="filter_separator">|</span><span class="filter_link"><a href="#" onclick="ajax( null, 'brand_list_section', 'innerHTML', 'Inventory::showBrandList', 'filterName=alpha', 'filterValue=#' ); showFilterIndicator(this)">#</a></span><?php
			foreach ( range('A','Z') as $i ) {
				echo '<span class="filter_separator">|</span><span class="filter_link"><a href="#" onclick="ajax( null, \'brand_list_section\', \'innerHTML\', \'Inventory::showBrandList\', \'filterName=alpha\', \'filterValue=' . $i . '\' ); showFilterIndicator(this)">' . $i . '</a></span>';
			}
			?>
		</div>
		<section id="brand_list_section">
		</section>
	</fieldset>


	<script type="text/javascript">
	<!--
		ajax( null, 'brand_list_section', 'innerHTML', 'Inventory::showBrandList' );
	// -->
	</script>
<?php
?>

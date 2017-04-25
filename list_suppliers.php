<?php
$PAGE_NAME = 'Suppliers';

require_once('controls/autoload.php');

$htmlLayout = new HtmlLayout($PAGE_NAME);
$htmlLayout->loadScript('ajax');
$htmlLayout->loadScript('dialog');
$htmlLayout->paint();
$htmlLayout->showMainMenu();
$htmlLayout->showPageHeading('suppliers.png');
Supplier::showListTasks();

if (isset($_GET['criteria'])) {
	$criteria = Filter::input( $_GET['criteria'] );
} else {
	$criteria = 'all-suppliers';
}

?>
<fieldset>
	<legend><?php
		switch ($criteria) {
			case 'with-payable'    : echo 'Suppliers with Payables'; 	break;
			case 'without-payable' : echo 'Suppliers without Payables'; break;
			case 'with-rebate' 	   : echo 'Suppliers with Rebate'; 		break;
			case 'without-rebate'  : echo 'Suppliers without Rebate'; 	break;
			default 			   : echo 'All Suppliers';
		}
		?>
	</legend>
	<div id="list_filter">
		<span class="filter_link selected_filter"><a href="#" onclick="ajax( null, 'supplier_list_section', 'innerHTML', 'Supplier::showList', <?php
		echo "'criteria=$criteria'" ?>, null, null ); showFilterIndicator(this)">All</a></span><span class="filter_separator">|</span><span
		class="filter_link"><a href="#" onclick="ajax( null, 'supplier_list_section', 'innerHTML', 'Supplier::showList', <?php
		echo "'criteria=$criteria'" ?>, 'filterName=alpha', 'filterValue=#' ); showFilterIndicator(this)">#</a></span><?php
		foreach (range('A', 'Z') as $i) {
			echo '<span class="filter_separator">|</span><span class="filter_link"><a href="#" onclick="' .
				 "ajax( null, 'supplier_list_section', 'innerHTML', 'Supplier::showList', 'criteria=$criteria', 'filterName=alpha', 'filterValue=$i');" .
				 'showFilterIndicator(this)">' . $i . '</a></span>';
		}
		?>
	</div>
	<section id="supplier_list_section">
	</section>
</fieldset>

<script type="text/javascript">
<!--
	ajax(null, 'supplier_list_section', 'innerHTML', 'Supplier::showList', <?php
			if ( isset( $_GET['criteria'] ) ) {
				echo "'criteria=$criteria', ";
			} else {
				echo "null, ";
			}
		?>null, null);
// -->
</script>
<?php
?>

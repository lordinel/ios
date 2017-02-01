<?php
	$PAGE_NAME = "Customers";

	require_once( "controls/autoload.php" );

	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadScript( "ajax" );
	$htmlLayout->loadScript( "dialog" );
	$htmlLayout->paint();
	$htmlLayout->showMainMenu();
	$htmlLayout->showPageHeading( 'customers.png' );
	Customer::showListTasks();
	
	if ( isset( $_GET['criteria'] ) ) {
		$criteria = Filter::input( $_GET['criteria'] );
	} else {
		$criteria = "all-customers";
	}
	

?>	<fieldset><legend><?php
		switch ( $criteria ) {
			case "with-receivable" :	echo "Customers with Receivables";		break;
			case "without-receivable" :	echo "Customers without Receivables";	break;
			case "with-rebate" :		echo "Customers with Rebate";			break;
			case "without-rebate" :		echo "Customers without Rebate";		break;
			default :					echo "All Customers";
		}
		?></legend>
		<div id="list_filter">
        <span class="filter_link selected_filter"><a href="#" onclick="ajax( null, 'customer_list_section', 'innerHTML', 'Customer::showList', <?php echo "'criteria=" . $criteria . "'" ?>, null, null ); showFilterIndicator(this)">All</a></span><span class="filter_separator">|</span><span class="filter_link"><a href="#" onclick="ajax( null, 'customer_list_section', 'innerHTML', 'Customer::showList', <?php echo "'criteria=" . $criteria . "'" ?>, 'filterName=alpha', 'filterValue=#' ); showFilterIndicator(this)">#</a></span><?php
		foreach ( range('A','Z') as $i ) {
			echo '<span class="filter_separator">|</span><span class="filter_link"><a href="#" onclick="ajax( null, \'customer_list_section\', \'innerHTML\', \'Customer::showList\', \'criteria=' . $criteria . '\', \'filterName=alpha\', \'filterValue=' . $i . '\' ); showFilterIndicator(this)">' . $i . '</a></span>';
		}
		?>
        </div>
		<section id="customer_list_section">
		</section>
	</fieldset>

	<script type="text/javascript">
		ajax( null, 'customer_list_section', 'innerHTML', 'Customer::showList', <?php
			if ( isset( $_GET['criteria'] ) ) {
				echo "'criteria=" . $criteria . "', ";
			} else {
				echo "null, ";
			}
		?>null, null );
	</script>
<?php
?>

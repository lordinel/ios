<?php
// set document type to HTML5
define( "DOCTYPE", "<!DOCTYPE HTML>" );

// set document type to XHTML 1.1; add xmlns="http://www.w3.org/1999/xhtml" attribute to <html>
// define( "DOCTYPE", "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">" );

define( "LIBRARIES_PATH", "libraries/" );		// default path for libraries and third-party codes
define( "STYLES_PATH", "styles/" );				// default path for external CSS files
define( "SCRIPTS_PATH", "scripts/" );			// default path for external JavaScript files
define( "IMAGES_PATH", "images/" );				// default path for CSS files

define( "SITE_ICON", "favicon.png" );			// site icon
define( "GLOBAL_LIBRARY", "jquery" );			// default library to load for all web pages
define( "GLOBAL_STYLE", "master.css" );			// default CSS file to load for all web pages
define( "GLOBAL_SCRIPT", "global.js" );			// default JavaScript file to load for all web page


// class definition for HTML layout elements across all web pages
class HtmlLayout
{
	private $pageName;					// name of Web page
	private $libraries = array();		// libraries to load (in form of folder name)
	private $styles = array();			// external styles to load (in form of CSS file)
	private $scripts = array();			// external scripts to load (in form of JavaScript file)
	public $user;


	// constructor
	public function __construct( $pageName )
	{
		$this->pageName = $pageName;
		$this->user = new User();
		require_once( "Database.php" );
	}


	// dynamically change page title
	public function setPageTitle( $pageTitle, $isDynamic = false )
	{
		$this->pageName = $pageTitle;

		if ( $isDynamic ) {
			?><script type="text/javascript">
			<!--
				document.title = '<?php echo $this->pageName . " | " . PROG_NAME . " " . VERSION ?>';
			// -->
			</script><?php
		}
	}


	// dynamically change page title (for Person classes)
	public static function setPageTitleStatic( $pageTitle )
	{
		?><script type="text/javascript">
		<!--
			document.title = '<?php echo $pageTitle . " | " . PROG_NAME . " " . VERSION ?>';
		// -->
		</script><?php
	}


	// load libraries
	public function loadLibrary( /*...*/ )
	{
		for ( $i = 0; $i < func_num_args(); $i++ )
		{
			array_push( $this->libraries, LIBRARIES_PATH . func_get_arg( $i ) );
		}
	}


	// load external CSS file
	public function loadStyle( /*...*/ )
	{
		for ( $i = 0; $i < func_num_args(); $i++ )
		{
			array_push( $this->styles, STYLES_PATH . func_get_arg( $i ) . ".css" );
		}
	}


	// load external JavaScript file
	public function loadScript( /*...*/ )
	{
		for ( $i = 0; $i < func_num_args(); $i++ )
		{
			array_push( $this->scripts, SCRIPTS_PATH . func_get_arg( $i ) . ".js" );
		}
	}



	// paint HTML page
	public function paint( $inlineStyleFunction = NULL, $inlineScriptFunction = NULL )
	{
		// open HTML document and header
		echo DOCTYPE . "\n";
?><html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $this->pageName . " | " . PROG_NAME . " " . VERSION ?></title>
	<link rel="icon" type="image/png" href="<?php echo IMAGES_PATH . SITE_ICON ?>" />
<?php

		// load default library
		array_push( $this->libraries, LIBRARIES_PATH . GLOBAL_LIBRARY );

		// load libraries
		//$this->libraries = array_reverse( $this->libraries );		// reverse array to keep the sequence of libraries as they are called

		if ( sizeof( $this->libraries ) > 0 )
		{
			foreach ( $this->libraries as $library )
			{
				$directoryList = scandir( $library );

				foreach ( $directoryList as $directoryEntry )
				{
					if ( !is_dir( $directoryEntry ) )
					{
						$file = pathinfo( $directoryEntry );

						if ( isset( $file['extension'] ) )
						{
							if ( $file['extension'] == "php" || $file['extension'] == "inc" )	// PHP include file
								require_once( LIBRARIES_PATH . $library );
							elseif ( $file['extension'] == "js" )								// JavaScript file
								array_unshift( $this->scripts, $library . "/" . $directoryEntry );
							elseif ( $file['extension'] == "css" )								// CSS file
								array_unshift( $this->styles,  $library . "/" . $directoryEntry );
						}
					}
				}
			}
		}


		// load global CSS file
		echo "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . STYLES_PATH . GLOBAL_STYLE . "\" />\n";


		// load external CSS files
		if ( sizeof( $this->styles ) > 0 )
		{
			foreach ( $this->styles as $cssFile )
			{
				echo "\t<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $cssFile . "\" />\n";
			}
		}


		// load inline CSS
		if ( $inlineStyleFunction != NULL )
			call_user_func( $inlineStyleFunction );


		// load external JavaScript files
		if ( sizeof( $this->scripts ) > 0 )
		{
			foreach ( $this->scripts as $jsFile )
			{
				echo "\t<script type=\"text/javascript\" src=\"" . $jsFile . "\"></script>\n";
			}
		}
		
		
		// load global JavaScript file
		echo "\t<script type=\"text/javascript\" src=\"" . SCRIPTS_PATH . GLOBAL_SCRIPT . "\"></script>\n";


		// load inline JavaScript
		if ( $inlineScriptFunction != NULL )
			call_user_func( $inlineScriptFunction );


		// close header
?></head>
<?php
		if ( strtolower( $this->pageName ) == "login" )
			echo "<body id=\"login_page\">\n";
		else
		{
			echo "<body>\n";
			if ( !$this->user->isLoggedIn() )
			{
?>
				<script type="text/javascript">
				<!--
					document.location = "index.php?action=nosession";
				//-->
				</script>
<?php
			}
		}
	}



	// display main menu and navigation
	public function showMainMenu()
	{
		$database = new Database();

		// display error message if Javascript is disabled
		echo '<noscript><div class="notification"><b>Oops!</b> We have detected that JavaScript is disabled in your browser. ' .
			 'Please enable Javascript and reload the page.</div></noscript>';


		// display header
		echo '<div id="header"><div id="header_content">';

		// display title
		echo '<h1><a href="index.php">' . CLIENT . ' ' . PROG_NAME . ' ' . VERSION . '</a></h1>';

		// display user info
		echo '<div id="user_info"><span id="user_info_name">' . $this->user->getUserNameStatic() . '</span><br />' .
			 '<span id="user_info_action">[ <a class="nolinkdecor" href="account_settings.php">Account Settings</a> ] [ <a class="nolinkdecor" href="index.php?action=logout">Logout</a> ]</span>' .
			 '</div>';

		echo '</div></div>';


		// display navigation strap
		echo '<div id="nav"><div id="nav_content">';

		// display main menu
		echo '<ul id="treemenu1">';

		// home menu
		echo '<li><span id="homelink"><a href="index.php"><img src="images/home.png" /> Home</a></span></li>';

		if ( $this->user->checkPermission( 'orders_and_customers', $database ) ) {
			echo '<li><a href="#">Orders</a>';
			echo '<ul>';
			echo '<li><a href="list_orders.php">Recent orders</a></li>' .
				 '<li class="menu_separator"><a href="list_orders.php?criteria=orders-to-deliver">Orders to deliver</a></li>' .
				 '<li><a href="list_orders.php?criteria=orders-awaiting-pickup">Orders awaiting pick-up</a></li>' .
				 '<li class="menu_separator"><a href="list_orders.php?criteria=payments-to-collect">With payments to collect</a></li>' .
				 '<li><a href="list_orders.php?criteria=payments-to-clear">With payments to clear</a></li>' .
				 '<li><a href="list_orders.php?criteria=payments-to-collect-and-clear">With payments to collect &amp; clear</a></li>' .
				 '<li class="menu_separator"><a href="list_orders.php?criteria=rebates-to-issue">With rebates to issue</a></li>' .
				 '<li><a href="list_orders.php?criteria=rebates-to-clear">With rebates to clear</a></li>' .
				 '<li><a href="list_orders.php?criteria=waived-balance">With waived balance</a></li>' .
				 '<li class="menu_separator"><a href="list_orders.php?criteria=orders-to-clear">Orders to clear</a></li>' .
				 '<li class="menu_separator"><a href="list_orders.php?criteria=cleared-orders">Cleared orders</a></li>' .
				 '<li><a href="list_orders.php?criteria=canceled-orders">Canceled orders</a></li>' .
				 '<li class="menu_separator"><a href="list_orders.php?criteria=all-orders">All orders</a></li>' .
				 '<li class="menu_separator"><a href="#">Actions</a>' .
				 '<ul>' .
				 '<li class="menu_separator"><a href="add_order.php">Add new order</a></li>' .
				 '<li class="menu_separator"><a href="list_orders_sidr.php?criteria=DR">Missing DRs</a></li>'. 
				 '</ul></li>';
			echo '</ul>';
			echo '</li>';
			echo '<li><a href="#">Customers</a>';
			echo '<ul>';
			echo '<li><a href="list_customers.php">All customers</a></li>' .
				 '<li class="menu_separator"><a href="list_customers.php?criteria=with-receivable">With receivable</a></li>' .
				 '<li><a href="list_customers.php?criteria=without-receivable">Without receivable</a></li>' .
				 '<li class="menu_separator"><a href="list_customers.php?criteria=with-rebate">With rebate</a></li>' .
				 '<li><a href="list_customers.php?criteria=without-rebate">Without rebate</a></li>' .
				 '<li class="menu_separator"><a href="add_customer.php">Add new customer...</a></li>';
			echo '</ul>';
			echo '</li>';
		}

		if ( $this->user->checkPermission( 'purchases_and_suppliers', $database ) ) {
			echo '<li><a href="#">Purchases</a>';
			echo '<ul>';
			echo '<li><a href="list_purchases.php">Recent purchases</a></li>' .
				 '<li class="menu_separator"><a href="list_purchases.php?criteria=purchases-awaiting-delivery">Purchases awaiting delivery</a></li>' .
				 '<li><a href="list_purchases.php?criteria=purchases-to-pickup">Purchases to pick-up</a></li>' .
				 '<li class="menu_separator"><a href="list_purchases.php?criteria=purchases-to-pay">Purchases to pay</a></li>' .
				 '<li><a href="list_purchases.php?criteria=payments-to-clear">With payments to clear</a></li>' .
				 '<li><a href="list_purchases.php?criteria=purchases-to-pay-and-clear">Purchases to pay &amp; clear</a></li>' .
				 '<li class="menu_separator"><a href="list_purchases.php?criteria=rebates-to-collect">With rebates to collect</a></li>' .
				 '<li><a href="list_purchases.php?criteria=rebates-to-clear">With rebates to clear</a></li>' .
				 '<li class="menu_separator"><a href="list_purchases.php?criteria=purchases-to-clear">Purchases to clear</a></li>' .
				 '<li class="menu_separator"><a href="list_purchases.php?criteria=cleared-purchases">Cleared purchases</a></li>' .
				 '<li><a href="list_purchases.php?criteria=canceled-purchases">Canceled purchases</a></li>' .
				 '<li class="menu_separator"><a href="list_purchases.php?criteria=all-purchases">All purchases</a></li>' .
				 '<li class="menu_separator"><a href="purchase_supplies.php">Purchase supplies...</a></li>';
			echo '</ul>';
			echo '</li>';
			echo '<li><a href="#">Suppliers</a>';
			echo '<ul>';
			echo '<li><a href="list_suppliers.php">All suppliers</a></li>' .
				 '<li class="menu_separator"><a href="list_suppliers.php?criteria=with-payable">With payable</a></li>' .
				 '<li><a href="list_suppliers.php?criteria=without-payable">Without payable</a></li>' .
				 '<li class="menu_separator"><a href="list_suppliers.php?criteria=with-rebate">With rebate</a></li>' .
				 '<li><a href="list_suppliers.php?criteria=without-rebate">Without rebate</a></li>' .
				 '<li class="menu_separator"><a href="add_supplier.php">Add new supplier...</a></li>';
			echo '</ul>';
			echo '</li>';
		}

		// inventory menu
		if ( $this->user->checkPermission( array( 'inventory','agents' ), $database ) ) {
        	echo '<li><a href="#">Office</a>';
        	echo '<ul>';

			if ( $this->user->checkPermission( 'inventory', $database ) ) {
        		echo '<li><a href="list_inventory.php">Inventory</a></li>';
			}

			if ( Registry::get( 'transaction.agent.enabled' ) == true &&
				 $this->user->checkPermission( 'agents', $database ) ) {
				echo '<li class="menu_separator"><a href="list_agents.php">Agents</a></li>';
			}

        	echo '</ul>';
        	echo '</li>';
		}

        // reports menu
        // @TODO Report menu is only specific to Express Dymans
		if ( CLIENT == 'Express Dymans' && $this->user->checkPermission( array(
			'daily_sales_report',
			'periodic_sales_report',
			'projected_collections_report',
			'inventory_report',
			'revenue_and_expense_report',
			'profit_calculator'
			 ), $database ) ) {
			echo '<li><a href="#">Reports</a>';
			echo '<ul>';

			if ( $this->user->checkPermission( 'daily_sales_report', $database ) ) {
				echo '<li><a href="report.php?type=daily-sales">Daily Sales</a>';
				echo '<ul>';
				echo '<li><a href="report.php?type=daily-sales&category=summary">Sales Summary</a></li>' .
					 '<li class="menu_separator"><a href="report.php?type=daily-sales&category=edic-yokohama">EDIC Yokohama</a></li>' .
					 '<li><a href="report.php?type=daily-sales&category=edic-others">EDIC Others</a></li>' .
					 '<li><a href="report.php?type=daily-sales&category=mdj-yokohama">MDJ Yokohama</a></li>' .
					 '<li><a href="report.php?type=daily-sales&category=mdj-others">MDJ Others</a></li>';
				echo '</ul>';
				echo '</li>';
			}

			if ( $this->user->checkPermission( 'periodic_sales_report', $database ) ) {
				echo '<li><a href="report.php?type=periodic-sales">Periodic Sales</a>';
				echo '<ul>';
				echo '<li><a href="report.php?type=periodic-sales&category=summary">Sales Summary</a></li>' .
					 '<li class="menu_separator"><a href="report.php?type=periodic-sales&category=edic-yokohama">EDIC Yokohama</a></li>' .
					 '<li><a href="report.php?type=periodic-sales&category=edic-others">EDIC Others</a></li>' .
					 '<li><a href="report.php?type=periodic-sales&category=mdj-yokohama">MDJ Yokohama</a></li>' .
					 '<li><a href="report.php?type=periodic-sales&category=mdj-others">MDJ Others</a></li>';
				echo '</ul>';
				echo '</li>';
			}

			if ( $this->user->checkPermission( 'projected_collections_report', $database ) ) {
				echo '<li><a href="report.php?type=projected-collections">Projected Collections</a>';
				echo '<ul>';
				echo '<li><a href="report.php?type=projected-collections&category=incoming">Incoming Cash</a></li>' .
					 '<li><a href="report.php?type=projected-collections&category=outgoing">Outgoing Cash</a></li>';
				echo '</ul>';
				echo '</li>';
			}

			if ( $this->user->checkPermission( 'inventory_report', $database ) ) {
				echo '<li><a href="report.php?type=inventory">Inventory Report</a>';
				echo '<ul>';
				echo '<li><a href="report.php?type=inventory&category=remaining">Remaining Stocks</a></li>' .
					 '<li><a href="report.php?type=inventory&category=to-buy-items">To Buy</a></li>' .
					 '<li><a href="report.php?type=inventory&category=sold-items">Sold Items</a></li>' .
					 '<li><a href="report.php?type=inventory&category=purchased-items">Purchased Items</a></li>';
				echo '</ul>';
				echo '</li>';
			}

			if ( $this->user->checkPermission( 'revenue_and_expense_report', $database ) ) {
				echo '<li><a href="report.php?type=rev-exp">Revenue and Expense</a>';
				echo '<ul>';
				echo '<li><a href="report.php?type=rev-exp&category=orders-trend">Orders</a></li>' .
					 '<li><a href="report.php?type=rev-exp&category=revenues-trend">Revenues</a></a></li>' .
					 '<li class="menu_separator"><a href="report.php?type=rev-exp&category=purchases-trend">Purchases</a></li>' .
					 '<li><a href="report.php?type=rev-exp&category=expenses-trend">Expenses</a></li>';
				echo '</ul>';
				echo '</li>';
			}

			if ( $this->user->checkPermission( 'profit_calculator', $database ) ) {
				echo '<li class="menu_separator"><a href="report.php?type=profit-calc">Profit Calculator</a></li>';
			}

			echo '</ul>';
			echo '</li>';
		}

		// tools menu
		if ( $this->user->checkPermission( array( 'manage_users','event_log','consistency_check' ), $database ) ||
             $this->user->getUserID() == 'administrator' || $this->user->getUserID() == 'ios_support' ) {
			echo '<li><a href="#">Tools</a>';
				echo '<ul>';

				if ( $this->user->checkPermission( 'manage_users', $database ) ) {
					echo '<li><a href="account_manager.php">Manage Users</a></li>';
				}

				if ( $this->user->checkPermission( 'event_log', $database ) ) {
					 echo '<li class="menu_separator"><a href="event_log.php">Event Log</a></li>';
				}

				if ( $this->user->checkPermission( 'consistency_check', $database ) ) {
					 echo '<li><a href="consistency_check.php">Consistency Check</a></li>';
				}
            
                /* if ( $this->user->getUserID() == 'administrator' || $this->user->getUserID() == 'ios_support' ) {
                    echo '<li class="menu_separator"><a href="system_settings.php">System Settings</a></li>';
                }*/

				echo '</ul>';
			echo '</li>';
		}

		// help menu
		echo '<li><a href="#">Help</a>';
			echo '<ul>';
			//echo '<li><a href="manual.php">User Manual & Tutorials</a></li>';
			//echo '<li class="menu_separator"><a href="issues.php">Known Issues</a></li>';
			echo '<li><a href="buglist.php">Bug Tracker</a></li>';
			echo '<li><a href="feedback.php">Feedback</a></li>';
			echo '<li class="menu_separator"><a href="about.php">About</a></li>';
			echo '</ul>';
		echo '</li>';

		echo '</ul>';

		// display search
		echo '<div id="search">';
		echo '<form action="search.php" method="get" id="search" onsubmit="return checkSearchText()"><div>' .
			 '<input type="text" name="q" id="search_text" size="20" maxlength="255" placeholder="type here to search..."' .
			 ( isset( $_GET['q'] ) ? ' value="' . htmlentities( $_GET['q'] ) . '"' : '' ) . ' />' .
			 '<input type="submit" id="submit_search" value="Search" title="Search entire site" />' .
			 '</div></form>';
		echo '</div>';

		echo '</div></div>';


		// display content wrapper
		echo '<div id="wrapper">';
		echo '<div id="content">';


		// preload images for modal dialog
		$dialogImageList = scandir( IMAGES_PATH . "dialog" );

		foreach ( $dialogImageList as $dialogImage )
		{
			if ( !is_dir( $dialogImage ) )
			{
				echo "	<img src=\"" . IMAGES_PATH . "dialog/" . $dialogImage . "\" class=\"preload_image\" />\n";
			}
		}
	}


	// display page title
	public function showPageHeading( $icon, $noPageTasks = false )
	{
		echo '<div id="page_header">';

		echo '<img src="' . IMAGES_PATH . 'heading/' . $icon . '" />';
		echo '<h2>' . $this->pageName . '</h2>';

		if ( $noPageTasks == true ) {
			echo '</div>';	// close div
		}
	}


	// destructor
	public function __destruct()
	{
		$database = new Database();

		if ( strtolower( $this->pageName ) != "login" )
		{
			?>
			<div id="footer">
				<div id="footer_content">
					<?php
					echo '<a class="nolinkdecor" href="index.php">Home</a>';
					
					if ( $this->user->checkPermission( 'orders_and_customers', $database ) ) {
						echo ' | <a class="nolinkdecor" href="list_orders.php">Orders</a> | <a class="nolinkdecor" href="list_customers.php">Customers</a>';
					}

					if ( $this->user->checkPermission( 'purchases_and_suppliers', $database ) ) {
						echo ' | <a class="nolinkdecor" href="list_purchases.php">Purchases</a> | <a class="nolinkdecor" href="list_suppliers.php">Suppliers</a>';
					}

					if ( $this->user->checkPermission( 'inventory', $database ) ) {
						echo ' | <a class="nolinkdecor" href="list_inventory.php">Inventory</a>';
					}
					?>
					<div><?php echo PROG_NAME_LONG . " " . VERSION ?></div>
					<div>Copyright &copy; <?php echo YEAR ?> | All Rights Reserved</div>
				</div>
			</div>
<?php
		}
?>
</div>
</body>
</html>
<?php
	}
}
?>

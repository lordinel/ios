<?php
	require_once( "controls/autoload.php" );

	
	// get report type
	if ( isset( $_GET['type'] ) ) {
		$reportType = strtolower( $_GET['type'] );
	} else {
		$reportType = 'daily-sales';
	}
	
	
	// set page name, get category and date parameters
	switch ( $reportType ) {
		case 'daily-sales': {
			$PAGE_NAME = "Daily Sales Report";
			
			
			// get category
			if ( isset( $_GET['category'] ) ) {
				$reportCategory = strtolower( $_GET['category'] );
			} else {
				$reportCategory = 'summary';
			}
			
			switch ( $reportCategory ) {
				case 'summary'		:	$selectedTab = 0;	break;
				case 'edic-yokohama':	$selectedTab = 1;	break;
				case 'edic-others'	:	$selectedTab = 2;	break;
				case 'mdj-yokohama' :	$selectedTab = 3;	break;
				case 'mdj-others'	:	$selectedTab = 4;	break;
				default:				redirectToHomePage();
			}
			
			
			// get date
			if ( isset( $_GET['date'] ) ) {
				try {
					$reportDate = new DateTime( $_GET['date'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				$reportDate = new DateTime();	// present date
				//$reportDate->sub( new DateInterval('P1D') );	// default to yesterday
			}
			
			break;
		}
		
		case 'periodic-sales': {
			$PAGE_NAME = "Periodic Sales Report";
			
			
			// get category
			if ( isset( $_GET['category'] ) ) {
				$reportCategory = strtolower( $_GET['category'] );
			} else {
				$reportCategory = 'summary';
			}
			
			switch ( $reportCategory ) {
				case 'summary'		:	$selectedTab = 0;	break;
				case 'edic-yokohama':	$selectedTab = 1;	break;
				case 'edic-others'	:	$selectedTab = 2;	break;
				case 'mdj-yokohama' :	$selectedTab = 3;	break;
				case 'mdj-others'	:	$selectedTab = 4;	break;
				default:				redirectToHomePage();
			}
			
			
			// get date
			if ( isset( $_GET['startdate'] ) ) {
				try {
					$startDate = new DateTime( $_GET['startdate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				$startDate = new DateTime();	// present date
				
				// set to Sunday
				if ( ( $interval = $startDate->format( 'w' ) ) > 0 ) {
					$startDate->sub( new DateInterval('P' . $interval . 'D') );
				}
			}
			
			// get end date
			if ( isset( $_GET['enddate'] ) ) {
				try {
					$endDate = new DateTime( $_GET['enddate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				// default end date, present day
				$endDate = new DateTime();
			}
			
			break;
		}

		case 'projected-collections': {
			$PAGE_NAME = "Projected Collections Report";


			// get category
			if ( isset( $_GET['category'] ) ) {
				$reportCategory = strtolower( $_GET['category'] );
			} else {
				$reportCategory = 'incoming';
			}

			switch ( $reportCategory ) {
				case 'incoming'		:	$selectedTab = 0;	break;
				case 'outgoing'     :	$selectedTab = 1;	break;
				default:				redirectToHomePage();
			}


			// get date
			if ( isset( $_GET['startdate'] ) ) {
				try {
					$startDate = new DateTime( $_GET['startdate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				$startDate = new DateTime();	// present date
			}

			// get end date
			if ( isset( $_GET['enddate'] ) ) {
				try {
					$endDate = new DateTime( $_GET['enddate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				// default end date, next 7 days
				$endDate = new DateTime();
				$endDate->add( new DateInterval('P7D') );
			}

			break;
		}
		
		case 'inventory': {
			$PAGE_NAME = "Inventory Report";
			
			// get category
			if ( isset( $_GET['category'] ) ) {
				$reportCategory = strtolower( $_GET['category'] );
			} else {
				$reportCategory = 'remaining';
			}
			
			switch ( $reportCategory ) {
				case 'remaining'		:	$selectedTab = 0;	break;
				case 'to-buy-items'		:	$selectedTab = 1;	break;
				case 'sold-items'		:	$selectedTab = 2;	break;
				case 'purchased-items'	:	$selectedTab = 3;	break;
				default:					redirectToHomePage();
			}
				
			
			// get start date
			if ( isset( $_GET['startdate'] ) ) {
				try {
					$startDate = new DateTime( $_GET['startdate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				$startDate = new DateTime();	// present date
				
				// set to Sunday
				if ( ( $interval = $startDate->format( 'w' ) ) > 0 ) {
					$startDate->sub( new DateInterval('P' . $interval . 'D') );
				}
			}
			
			
			// get end date
			if ( isset( $_GET['enddate'] ) ) {
				try {
					$endDate = new DateTime( $_GET['enddate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				// default end date, present day
				$endDate = new DateTime();
			}
			
			break;
		}
		
		case 'rev-exp': {
			$PAGE_NAME = "Revenue and Expense Report";
			
			
			// get category
			if ( isset( $_GET['category'] ) ) {
				$reportCategory = strtolower( $_GET['category'] );
			} else {
				$reportCategory = 'orders-trend';
			}
			
			switch ( $reportCategory ) {
				case 'orders-trend'		:	$selectedTab = 0;	break;
				case 'revenues-trend'	:	$selectedTab = 1;	break;
				case 'purchases-trend' 	:	$selectedTab = 2;	break;
				case 'expenses-trend'	:	$selectedTab = 3;	break;
				default:					redirectToHomePage();
			}
			
			
			// get view type
			if ( isset( $_GET['viewtype'] ) ) {
				$viewType = strtolower( $_GET['viewtype'] );
				if (    $viewType != 'day' && $viewType != 'week'
					 && $viewType != 'month' && $viewType != 'quarter'
					 && $viewType != 'year' ) {
					redirectToHomePage();
				}
			} else {
				$viewType = 'day';
			}
				
			
			// get start date
			if ( isset( $_GET['startdate'] ) ) {
				try {
					$startDate = new DateTime( $_GET['startdate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				// default start date, past 2 years
				$startDate = new DateTime();
				
				// set to 1st day of the month
				$interval = $startDate->format( 'd' );
				if ( $interval == 1 ) {
					// if 1st day of the month, show past month
					$startDate->sub( new DateInterval('P1M') );
				} else {
					$startDate->sub( new DateInterval('P' . ( $interval - 1 ) . 'D') );
				}
			}
			
			
			// get end date
			if ( isset( $_GET['enddate'] ) ) {
				try {
					$endDate = new DateTime( $_GET['enddate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				// default end date, present day
				$endDate = new DateTime();
			}
			
			
			break;
		}
		
		case 'profit-calc': {
			$PAGE_NAME = "Profit Calculator";
			
			
			// get start date
			if ( isset( $_GET['startdate'] ) ) {
				try {
					$startDate = new DateTime( $_GET['startdate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				// default start date, past 1 month
				$startDate = new DateTime();
				$startDate->sub( new DateInterval('P6D') );
			}
			
			
			// get end date
			if ( isset( $_GET['enddate'] ) ) {
				try {
					$endDate = new DateTime( $_GET['enddate'] );
				} catch ( Exception $e ) {
					redirectToHomePage();
				}
			} else {
				// default end date, present day
				$endDate = new DateTime();
			}
			
			break;
		}
		
		default: redirectToHomePage();
	}
	
	
	function inlineScript() {
		global $reportType;
		
		if ( $reportType != 'profit-calc' ) {
			global $selectedTab;
			
			?><script type="text/javascript"><!--
				$(function() {
					$('#tabs').tabs({
						selected: <?php echo $selectedTab ?>
					});
				});
				
				var report = new Report();
			// --></script><?php
		} else {
			?><script type="text/javascript"><!--
				var report = new Report();
			// --></script><?php
		}
	}
	
	
	$htmlLayout = new HtmlLayout( $PAGE_NAME );
	$htmlLayout->loadLibrary( "charting" );
	$htmlLayout->loadStyle( "form" );
	$htmlLayout->loadScript( "form" );
	$htmlLayout->loadScript( "data" );
	$htmlLayout->loadScript( "ajax" );
    $htmlLayout->loadScript( "dialog" );
	$htmlLayout->loadScript( "report" );
	$htmlLayout->paint( null, 'inlineScript' );
	$htmlLayout->showMainMenu();
	if ( $reportType == 'profit-calc' ) {
		$htmlLayout->showPageHeading( 'profit.png' );
		Report::showListTasks();
	} else {
		$htmlLayout->showPageHeading( 'report.png', true );
	}
	
	
	echo '<fieldset>';
	$formTarget = $_SERVER['PHP_SELF'];
	
	
	// display report
	switch ( $reportType ) {
		case 'daily-sales': {
			$reportDateStr   = $reportDate->format( "F j, Y, D" );
			$reportDateParam = $reportDate->format( 'Y-m-d' );
            
			echo <<<END
				<section>
			        <div class="report_data">
						<form name="report_date_form" method="get" action="$formTarget" autocomplete="off">
							<input type="hidden" name="type" id="report_type" value="$reportType" />
							<input type="hidden" name="category" id="report_category" value="$reportCategory" />
							<label for="date">Date:</label>
							<input type="text" name="date" id="date" class="datepicker_no_future_date" size="30" maxlength="30" required="required" value="$reportDateStr" />
							<input type="submit" name="submit_form" value="Go" />
						</form>
					</div>
				</section>
				
				<div id="tabs">
					<ul>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','summary','$reportDateParam')">Sales Summary</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','edic-yokohama','$reportDateParam')">EDIC Yokohama</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','edic-others','$reportDateParam')">EDIC Others</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','mdj-yokohama','$reportDateParam')">MDJ Yokohama</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','mdj-others','$reportDateParam')">MDJ Others</a></li>
					</ul>
					<div id="tab_content"></div>
				</div>
				
END;
			// load default report
			?><script type="text/javascript"><!--
				report.loadReport( '<?php echo $reportType ?>', '<?php echo $reportCategory ?>', '<?php echo $reportDateParam ?>' );
				$('#date').focus(function(){
					report.data.selectField( $(this) );
				});
			// --></script>
			<?php
			
			break;
		}
		
		case 'periodic-sales': {
			$startDateStr = $startDate->format( "F j, Y, D" );
			$startDateParam = $startDate->format( 'Y-m-d' );
			
			$endDateStr = $endDate->format( "F j, Y, D" );
			$endDateParam = $endDate->format( 'Y-m-d' );
            
			
			echo <<<END
				<section>
			        <div class="report_data">
						<form name="report_date_form" method="get" action="$formTarget" autocomplete="off">
							<input type="hidden" name="type" id="report_type" value="$reportType" />
							<input type="hidden" name="category" id="report_category" value="$reportCategory" />
							<label for="startdate">Start Date:</label>
							<input type="text" name="startdate" id="startdate" class="datepicker_no_future_date" size="30" maxlength="30" required="required" value="$startDateStr" />
							<label for="enddate">End Date:</label>
							<input type="text" name="enddate" id="enddate" class="datepicker_no_future_date" size="30" maxlength="30" required="required" value="$endDateStr" />
							<input type="submit" name="submit_form" value="Go" />
						</form>
					</div>
				</section>
				
				<div id="tabs">
					<ul>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','summary','$startDateParam','$endDateParam')">Sales Summary</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','edic-yokohama','$startDateParam','$endDateParam')">EDIC Yokohama</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','edic-others','$startDateParam','$endDateParam')">EDIC Others</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','mdj-yokohama','$startDateParam','$endDateParam')">MDJ Yokohama</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','mdj-others','$startDateParam','$endDateParam')">MDJ Others</a></li>
					</ul>
					<div id="tab_content"></div>
				</div>
				
END;
			// load default report
			?><script type="text/javascript"><!--
				report.loadReport( '<?php echo $reportType ?>', '<?php echo $reportCategory ?>', '<?php echo $startDateParam ?>', '<?php echo $endDateParam ?>' );
				$('#startdate').focus(function(){
					report.data.selectField( $(this) );
				});
				$('#enddate').focus(function(){
					report.data.selectField( $(this) );
				});
			// --></script>
			<?php
			
			break;
		}

		case 'projected-collections': {
			$startDateStr = $startDate->format( "F j, Y, D" );
			$startDateParam = $startDate->format( 'Y-m-d' );

			$endDateStr = $endDate->format( "F j, Y, D" );
			$endDateParam = $endDate->format( 'Y-m-d' );

			echo <<<END
					<section>
				        <div class="report_data">
							<form name="report_date_form" method="get" action="$formTarget" autocomplete="off">
								<input type="hidden" name="type" id="report_type" value="$reportType" />
								<input type="hidden" name="category" id="report_category" value="$reportCategory" />
								<label for="startdate">Start Date:</label>
								<input type="text" name="startdate" id="startdate" class="datepicker_no_past_date" size="30" maxlength="30" required="required" value="$startDateStr" />
								<label for="enddate">End Date:</label>
								<input type="text" name="enddate" id="enddate" class="datepicker_no_past_date" size="30" maxlength="30" required="required" value="$endDateStr" />
								<input type="submit" name="submit_form" value="Go" />
							</form>
						</div>
					</section>
					<div id="tabs">
						<ul>
							<li><a href="#tab_content" onclick="report.loadReport('$reportType','incoming','$startDateParam','$endDateParam')">Incoming Cash</a></li>
							<li><a href="#tab_content" onclick="report.loadReport('$reportType','outgoing','$startDateParam','$endDateParam')">Outgoing Cash</a></li>
						</ul>
						<div id="tab_content"></div>
					</div>

END;
			// load default report
			?><script type="text/javascript"><!--
				report.loadReport( '<?php echo $reportType ?>', '<?php echo $reportCategory ?>', '<?php echo $startDateParam ?>', '<?php echo $endDateParam ?>' );
				$('#startdate').focus(function(){
					report.data.selectField( $(this) );
				});
				$('#enddate').focus(function(){
					report.data.selectField( $(this) );
				});
			// --></script>
			<?php

			break;
		}
		
		case 'inventory': {
			$startDateParam = $startDate->format( 'Y-m-d' );
			$endDateParam   = $endDate->format( 'Y-m-d' );
            
			echo <<<END
				<br />
				<div id="tabs">
					<ul>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','remaining','$startDateParam','$endDateParam'), report.loadFormEvents(), loadDatePicker()">Remaining Stocks</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','to-buy-items','$startDateParam','$endDateParam'), report.loadFormEvents(), loadDatePicker()">To Buy</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','sold-items','$startDateParam','$endDateParam'), report.loadFormEvents(), loadDatePicker()">Sold Items</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','purchased-items','$startDateParam','$endDateParam'), report.loadFormEvents(), loadDatePicker()">Purchased Items</a></li>
					</ul>
					<div id="tab_content"></div>
				</div>
				
END;

			?><script type="text/javascript"><!--
				report.loadReport( '<?php echo $reportType ?>', '<?php echo $reportCategory ?>',
								   '<?php echo $startDateParam ?>', '<?php echo $endDateParam ?>' );
				report.setViewType( 'day' );	// no bearing
				report.loadFormEvents();
			// --></script><?php
			break;
		}
		
		case 'rev-exp': {
			$startDateStr   = $startDate->format( "F j, Y, D" );
			$startDateParam = $startDate->format( 'Y-m-d' );
			$endDateStr     = $endDate->format( "F j, Y, D" );
			$endDateParam   = $endDate->format( 'Y-m-d' );
            
			echo <<<END
				<section>
			        <div class="report_data">
						<form name="report_date_form" method="get" action="$formTarget" autocomplete="off">
							<input type="hidden" name="type" id="report_type" value="$reportType" />
							<input type="hidden" name="category" id="report_category" value="$reportCategory" />
							<label for="startdate">Start Date:</label>
							<input type="text" name="startdate" id="startdate" class="datepicker_no_future_date" size="30" maxlength="30" required="required" value="$startDateStr" />
							<label for="enddate">End Date:</label>
							<input type="text" name="enddate" id="enddate" class="datepicker_no_future_date" size="30" maxlength="30" required="required" value="$endDateStr" />
							<label for="viewtype">View by</label>
							<select name="viewtype" id="viewtype">
								<option value="day">Day</option>
								<option value="week">Week</option>
								<option value="month">Month</option>
								<option value="quarter">Quarter</option>
								<option value="year">Year</option>
							</select>
							<input type="submit" name="submit_form" value="Go" />
						</form>
					</div>
				</section>
				
				<div id="tabs">
					<ul>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','orders-trend','$startDateParam','$endDateParam','$viewType'), reloadChart(0)">Orders</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','revenues-trend','$startDateParam','$endDateParam','$viewType'), reloadChart(1)">Revenues</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','purchases-trend','$startDateParam','$endDateParam','$viewType'), reloadChart(2)">Purchases</a></li>
						<li><a href="#tab_content" onclick="report.loadReport('$reportType','expenses-trend','$startDateParam','$endDateParam','$viewType'), reloadChart(3)">Expenses</a></li>
					</ul>
					<div id="tab_content"></div>
				</div>
				
END;
			// load default report
			?><script type="text/javascript"><!--
				report.loadReport( '<?php echo $reportType ?>', '<?php echo $reportCategory ?>',
								   '<?php echo $startDateParam ?>', '<?php echo $endDateParam ?>', '<?php echo $viewType ?>' );
				report.setViewType( '<?php echo $viewType ?>' );
				report.setChartViewOptions();
				report.loadFormEvents();
				
				function reloadChart( selectedTab ) {
					switch ( selectedTab ) {
						case 0: {
							$(function(){
								$('table#order_chart').visualize({type: 'area', colors: ['#92d5ea']});
							});
							
							break;
						}
						
						case 1: {
							$(function(){
								$('table#revenue_chart').visualize({type: 'area', colors: ['#26a4ed']});
							});
							
							break;
						}
						
						case 2: {
							$(function(){
								$('table#purchase_chart').visualize({type: 'area', colors: ['#ee8310']});
							});
							
							break;
						}
						
						case 3: {
							$(function(){
								$('table#expense_chart').visualize({type: 'area', colors: ['#be1e2d']});
							});
							
							break;
						}
					}
				}
				
				reloadChart(<?php echo $selectedTab ?>);	// initial call on page load
			// --></script>
			<?php
			
			break;
		}
		
		case 'profit-calc': {
			$startDateStr   = $startDate->format( "F j, Y, D" );
			$startDateParam = $startDate->format( 'Y-m-d' );
			$endDateStr     = $endDate->format( "F j, Y, D" );
			$endDateParam   = $endDate->format( 'Y-m-d' );
			
			
			echo '<section><br /><br />' .
				 '<div id="profit_calc_div">' .
				 '<div id="report_content"></div>';
			?><script type="text/javascript"><!--
				report.loadReport('profit-calc', null, <?php echo $startDateParam ?>, <?php echo $endDateParam ?>, null, 'report_content');
				report.setViewType( 'day' ); 	// no bearing
				report.loadFormEvents();
				report.amountReceivable = parseFloat( stripNonNumeric( $('#amount_receivable').val() ) );
				report.pdcReceivable 	= parseFloat( stripNonNumeric( $('#pdc_receivable').val() ) );
				report.inventoryAmount 	= parseFloat( stripNonNumeric( $('#inventory_amount').val() ) );
				report.rebatePayable	= parseFloat( stripNonNumeric( $('#rebate_payable').val() ) );
				report.pdcRebate		= parseFloat( stripNonNumeric( $('#pdc_rebate').val() ) );
				report.amountPayable 	= parseFloat( stripNonNumeric( $('#amount_payable').val() ) );
				report.pdcPayable		= parseFloat( stripNonNumeric( $('#pdc_payable').val() ) );
				report.calculateProfit();
			// --></script><?php
			echo '</div></section>';
			break;
		}
	}


	echo '</fieldset>';
	
?>

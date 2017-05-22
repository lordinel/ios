<?php


//----------------------------------------------------------------------------------------------------------------
// class definition for report generation
//----------------------------------------------------------------------------------------------------------------
class Report extends Layout
{
	//------------------------------------------------------------------------------------------------------------
	// choose which report to display
	//------------------------------------------------------------------------------------------------------------
	public static function loadReport() {
		// check required parameters
		if (!isset($_POST['type'])) {
			return;
		}
		
		// determine report type
		switch ($_POST['type']) {
			case 'daily-sales':
				// check required parameters
				if (!isset($_POST['category']) && !isset($_POST['startDate'])) {
					return;
				}
				self::showDailySalesReport($_POST['category'], $_POST['startDate']);
				break;
			
			case 'periodic-sales':
				// check required parameters
				if (!isset($_POST['category']) && !isset($_POST['startDate']) && !isset($_POST['endDate'])) {
					return;
				}
				
				$startDate = new DateTime($_POST['startDate']);
				$endDate   = new DateTime($_POST['endDate']);
				
				if ($startDate > $endDate) {
					echo '<p><span class="bad">Error:</span> The start date is greater than end date. Please select a different set of dates.</p>';
					return;
				}
				
				self::showPeriodicSalesReport($_POST['category'], $_POST['startDate'], $_POST['endDate']);
				break;
			
			case 'projected-collections':
				// check required parameters
				if (!isset($_POST['category']) && !isset($_POST['startDate']) && !isset($_POST['endDate'])) {
					return;
				}
				
				$startDate = new DateTime($_POST['startDate']);
				$endDate   = new DateTime($_POST['endDate']);
				
				if ($startDate > $endDate) {
					echo '<p><span class="bad">Error:</span> The start date is greater than end date. Please select a different set of dates.</p>';
					return;
				}
				
				self::showProjectedCollectionsReport($_POST['category'], $_POST['startDate'], $_POST['endDate']);
				break;
			
			case 'inventory':
				// check required parameters
				if (!isset($_POST['category']) && !isset($_POST['startDate']) && !isset($_POST['endDate'])) {
					return;
				}
				
				$startDate = new DateTime($_POST['startDate']);
				$endDate   = new DateTime($_POST['endDate']);
				
				self::showInventoryReport($_POST['category'], $startDate, $endDate);
				break;
			
			case 'rev-exp':
				// check required parameters
				if (!isset($_POST['category']) && !isset($_POST['viewType'])
					&& !isset($_POST['startDate']) && !isset($_POST['endDate'])) {
					return;
				}
				
				$startDate = new DateTime($_POST['startDate']);
				$endDate   = new DateTime($_POST['endDate']);
				
				if ($startDate > $endDate) {
					echo '<p><span class="bad">Error:</span> The start date is greater than end date. Please select a different set of dates.</p>';
					return;
				}
				
				self::showRevenueExpenseReport($_POST['category'], $_POST['viewType'], $startDate, $endDate);
				break;
			
			case 'profit-calc':
				self::showProfitCalculatorForm();
				break;
			
			default:
				return;
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display Daily Sales Report
	//------------------------------------------------------------------------------------------------------------
	private static function showDailySalesReport( $category, $date ) {
		self::$database = new Database();
		
		// get id of Yokohama for special handling
		$resultSet = self::$database->query("SELECT id FROM inventory_brand WHERE name='yokohama'");
		if (self::$database->getResultCount($resultSet) == 0) {
			echo '<span class="bad">Error:</span> Cannot find Yokohama in Inventory Brand list.';
			return;
		}
		$inventory  = self::$database->getResultRow($resultSet);
		$yokohamaID = $inventory['id'];
		
		$reportDate = new DateTime($date);
		$reportDate = $reportDate->format('F j, Y');
		echo '<div id="date_header">As of <span class="date">' . $reportDate . '</span></div>';
		
		if ($category == 'summary') {
			$dailySales = array();
			
			// EDIC Yokohama
			$sqlQuery  = "SELECT SUM(quantity) AS total_quantity, SUM(net_price*quantity) AS total_amount " .
					     "FROM order_item " .
					     "INNER JOIN `order` ON order_item.order_id = `order`.id " .
					     "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
					     "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
					     "WHERE DATE_FORMAT(`order`.delivery_pickup_actual_date,'%Y-%m-%d') = '$date' AND " .
					     "inventory_brand.id = $yokohamaID AND `order`.business_unit = 'EDIC'";
			$resultSet = self::$database->query($sqlQuery);
			$orderItem = self::$database->getResultRow($resultSet);
			
			$dailySales['edic-yokohama'] = array('quantity' => $orderItem['total_quantity'], 'amount' => $orderItem['total_amount']);
			
			// EDIC Others
			$sqlQuery  = "SELECT SUM(quantity) AS total_quantity, SUM(net_price*quantity) AS total_amount " .
						 "FROM order_item " .
						 "INNER JOIN `order` ON order_item.order_id = `order`.id " .
						 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
						 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
						 "WHERE DATE_FORMAT(`order`.delivery_pickup_actual_date,'%Y-%m-%d') = '$date' AND " .
						 "inventory_brand.id != $yokohamaID AND `order`.business_unit = 'EDIC'";
			$resultSet = self::$database->query($sqlQuery);
			$orderItem = self::$database->getResultRow($resultSet);
			
			$dailySales['edic-others'] = array('quantity' => $orderItem['total_quantity'], 'amount' => $orderItem['total_amount']);
			
			// MDJ Yokohama
			$sqlQuery  = "SELECT SUM(quantity) AS total_quantity, SUM(net_price*quantity) AS total_amount " .
					     "FROM order_item " .
					     "INNER JOIN `order` ON order_item.order_id = `order`.id " .
					     "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
					     "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
					     "WHERE DATE_FORMAT(`order`.delivery_pickup_actual_date,'%Y-%m-%d') = '$date' AND " .
					     "inventory_brand.id = $yokohamaID AND `order`.business_unit = 'MDJ'";
			$resultSet = self::$database->query($sqlQuery);
			$orderItem = self::$database->getResultRow($resultSet);
			
			$dailySales['mdj-yokohama'] = array('quantity' => $orderItem['total_quantity'], 'amount' => $orderItem['total_amount']);
			
			// MDJ Others
			$sqlQuery  = "SELECT SUM(quantity) AS total_quantity, SUM(net_price*quantity) AS total_amount " .
						 "FROM order_item " .
						 "INNER JOIN `order` ON order_item.order_id = `order`.id " .
						 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
						 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
						 "WHERE DATE_FORMAT(`order`.delivery_pickup_actual_date,'%Y-%m-%d') = '$date' AND " .
						 "inventory_brand.id != $yokohamaID AND `order`.business_unit = 'MDJ'";
			$resultSet = self::$database->query($sqlQuery);
			$orderItem = self::$database->getResultRow($resultSet);
			
			$dailySales['mdj-others'] = array('quantity' => $orderItem['total_quantity'], 'amount' => $orderItem['total_amount']);
			
			echo '<br /><table id="daily_sales_table" class="item_input_table report_table"><thead><tr>' .
				 '<th id="summary_category">Catergory</th><th id="summary_quantity">Total Quantity</th><th id="summary_amount">Total Amount</th>' .
				 '</tr></thead><tbody>';
			
			echo '<tr class="item_row">' .
				 '<td>EDIC Yokohama</td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['edic-yokohama']['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['edic-yokohama']['amount'], 'float') . '</span></td>' .
				 '</tr>';
			echo '<tr class="item_row">' .
				 '<td>EDIC Others</td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['edic-others']['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['edic-others']['amount'], 'float') . '</span></td>' .
				 '</tr>';
			echo '<tr class="item_row">' .
				 '<td>MDJ Yokohama</td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['mdj-yokohama']['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['mdj-yokohama']['amount'], 'float') . '</span></td>' .
				 '</tr>';
			echo '<tr class="item_row">' .
				 '<td>MDJ Others</td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['mdj-others']['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['mdj-others']['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			echo '<tr class="totals_bottom"><td colspan="3"></td></tr>';
			
			$totalYokohama = array('quantity' => $dailySales['edic-yokohama']['quantity'] + $dailySales['mdj-yokohama']['quantity'],
								   'amount'   => $dailySales['edic-yokohama']['amount'] + $dailySales['mdj-yokohama']['amount']);
			
			echo '<tr class="item_row">' .
				 '<td>Total of Yokohama</td>' .
				 '<td class="number"><span>' . numberFormat($totalYokohama['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($totalYokohama['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			$totalOthers = array('quantity' => $dailySales['edic-others']['quantity'] + $dailySales['mdj-others']['quantity'],
								 'amount'   => $dailySales['edic-others']['amount'] + $dailySales['mdj-others']['amount']);
			
			echo '<tr class="item_row">' .
				 '<td>Total of Others</td>' .
				 '<td class="number"><span>' . numberFormat($totalOthers['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($totalOthers['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			echo '<tr class="totals_bottom"><td colspan="3"></td></tr>';
			
			$totalEdic = array('quantity' => $dailySales['edic-yokohama']['quantity'] + $dailySales['edic-others']['quantity'],
							   'amount'   => $dailySales['edic-yokohama']['amount'] + $dailySales['edic-others']['amount']);
			
			echo '<tr class="item_row">' .
				 '<td>Total of EDIC</td>' .
				 '<td class="number"><span>' . numberFormat($totalEdic['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($totalEdic['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			$totalMdj = array('quantity' => $dailySales['mdj-yokohama']['quantity'] + $dailySales['mdj-others']['quantity'],
							  'amount'   => $dailySales['mdj-yokohama']['amount'] + $dailySales['mdj-others']['amount']);
			
			echo '<tr class="item_row">' .
				 '<td>Total of MDJ</td>' .
				 '<td class="number"><span>' . numberFormat($totalMdj['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($totalMdj['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			echo '<tr class="totals_bottom"><td colspan="3"></td></tr>';
			
			$grandTotal = array('quantity' => $totalEdic['quantity'] + $totalMdj['quantity'],
								'amount'   => $totalEdic['amount'] + $totalMdj['amount']);
			
			echo '<tr class="item_row">' .
				 '<td><span class="important_label">Grand Total</span></td>' .
				 '<td class="number"><span class="important_label">' . numberFormat($grandTotal['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span class="important_label">' . numberFormat($grandTotal['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			echo '</tbody></table>';
		} else {
			// determine category
			switch ($category) {
				case 'edic-yokohama': $condition = "inventory_brand.id = $yokohamaID AND `order`.business_unit = 'EDIC'";  break;
				case 'edic-others'  : $condition = "inventory_brand.id != $yokohamaID AND `order`.business_unit = 'EDIC'"; break;
				case 'mdj-yokohama' : $condition = "inventory_brand.id = $yokohamaID AND `order`.business_unit = 'MDJ'";   break;
				case 'mdj-others'   : $condition = "inventory_brand.id != $yokohamaID AND `order`.business_unit = 'MDJ'";  break;
				default				: return;
			}
			
			// perform query
			$sqlQuery  = "SELECT `order`.id, " .
						 "IF(sales_invoice IS NOT NULL,CONCAT('SI ',sales_invoice),CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
						 "customer.id AS customer_id, customer.name AS customer, " .
						 "order_item.quantity, order_item.sidr_price, order_item.net_price, " .
						 "inventory_brand.name AS brand, inventory.model " .
						 "FROM `order` " .
						 "INNER JOIN customer ON `order`.customer_id = customer.id " .
						 "INNER JOIN order_item ON `order`.id = order_item.order_id " .
						 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
						 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
						 "WHERE DATE_FORMAT(`order`.delivery_pickup_actual_date,'%Y-%m-%d') = '$date' AND $condition " .
						 "ORDER BY `order`.id ASC";
			$resultSet = self::$database->query($sqlQuery);
			if (self::$database->getResultCount($resultSet) == 0) {
				echo '<p>No orders found.</p>';
				return;
			}
			
			echo '<br /><table id="daily_sales_table" class="item_input_table report_table"><thead><tr>' .
				 '<th id="order_number">Order No.</th><th id="tracking_number">Invoice No.</th><th id="customer">Customer</th>' .
				 '<th id="quantity">Qty</th><th id="brand">Brand</th><th id="model">Model</th>' .
				 '<th id="sidr_price">SI/DR Price</th><th id="net_price">Net Price</th>' .
				 '<th id="net_amount">Net Amount<br /><span class="subheader">(Net Price x Qty)</span></th>' .
				 '</tr></thead><tbody>';
			
			$totalQuantity  = 0;
			$totalNetAmount = 0;
			$prevOrderId    = null;
			
			while ($order = self::$database->getResultRow($resultSet)) {
				echo '<tr class="item_row">';
				
				if ($order['id'] != $prevOrderId) {
					echo '<td><a href="view_order_details.php?id=' . $order['id'] . '">' . $order['id'] . '</a></td>' .
						 '<td>' . $order['tracking_number'] . '</td>' .
					     '<td><a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
						 capitalizeWords(Filter::output($order['customer'])) . '</a></td>';
				} else {
					echo '<td></td><td></td><td></td>';
				}
				
				$totalQuantity = $totalQuantity + $order['quantity'];
				
				echo '<td class="number"><span>' . numberFormat($order['quantity'], 'int') . '</span></td>' .
					 '<td>' . capitalizeWords(Filter::output($order['brand'])) . '</td>' .
					 '<td>' . capitalizeWords(Filter::output($order['model'])) . '</td>' .
					 '<td class="number"><span>' . numberFormat($order['sidr_price'], "float") . '</span></td>' .
					 '<td class="number"><span>' . numberFormat($order['net_price'], "float") . '</span></td>';
				
				$netAmount      = $order['net_price'] * $order['quantity'];
				$totalNetAmount = $totalNetAmount + $netAmount;
				
				echo '<td class="number"><span>' . numberFormat($netAmount, "float") . '</span></td>';
				echo '</tr>';
				
				$prevOrderId = $order['id'];
			}
			
			$totalNetAmount = numberFormat($totalNetAmount, 'float');
			
			echo '<tr><td colspan="9"></td></tr><tr>' .
				 '<td colspan="3" class="summary_label"><span class="important_label">Totals:</span></td>' .
				 '<td class="number"><span class="important_label">' . $totalQuantity . '</span></td>' .
				 '<td colspan="4" class="summary_label"><span class="important_label">' . CURRENCY . '</span></td>' .
				 '<td class="number"><span class="important_label">' . $totalNetAmount. '</span></td>' .
				 '</tr></tbody></table>';
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// save daily sales report to excel file
	//------------------------------------------------------------------------------------------------------------
	public static function exportDailySalesReportToExcel( $username, $reportDateParam ) {
		$sheetTitle = 'Daily Sales Report';
		$reportDateTmp = new DateTime($reportDateParam);
		$reportDate = $reportDateTmp->format('Y-m-d');
		$fileTimeStampExtension = $reportDate;
		$headingTimeStamp = dateFormatOutput($fileTimeStampExtension, 'F d, Y', 'Y-m-d');
		
		require_once('classes/Filter.php');
		
		self::$database = new Database();
		
		// import PHPExcel library
		require_once( 'libraries/phpexcel/PHPExcel.php' );
		
		// instantiate formatting variables
		$backgroundColor = new PHPExcel_Style_Color();
		$fontColor 		 = new PHPExcel_Style_Color();
		
		// color-specific variables
		$fontColorRed	  = new PHPExcel_Style_Color(); $fontColorRed->setRGB('FF0000');
		$fontColorDarkRed = new PHPExcel_Style_Color(); $fontColorDarkRed->setRGB('CC0000');
		$fontColorGreen	  = new PHPExcel_Style_Color(); $fontColorGreen->setRGB('00CC00');
		$fontColorGray	  = new PHPExcel_Style_Color(); $fontColorGray->setRGB('999999');

		// set value binder
		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());
		
		// create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		
		// set file properties
		$objPHPExcel->getProperties()
					->setCreator($username)
					->setLastModifiedBy($username)
					->setTitle("$sheetTitle - $headingTimeStamp")
					->setSubject(EXCEL_FILE_SUBJECT)
					->setDescription(EXCEL_FILE_DESCRIPTION)
					->setKeywords(EXCEL_FILE_KEYWORDS)
					->setCategory(EXCEL_FILE_CATEGORY);
		
		
		// create a first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		
		// rename present sheet
		$activeSheet->setTitle('OFFICE');
		
		// set default font
		$activeSheet->getDefaultStyle()->getFont()->setName(EXCEL_DEFAULT_FONT_NAME)
												  ->setSize(EXCEL_DEFAULT_FONT_SIZE);
		
		
		// write sheet headers
		$activeSheet->setCellValue('A1', CLIENT);
		$activeSheet->setCellValue('A2', $sheetTitle);
		
		// format sheet headers
		$backgroundColor->setRGB(EXCEL_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A1:O3')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A1:O3')->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A1:O2')->getFont()->setBold(true);
		$activeSheet->getStyle('A1:O3')->getFont()->setName(EXCEL_HEADER_FONT_NAME);
		$activeSheet->getStyle('A1')->getFont()->setColor($fontColorRed);
		$activeSheet->getStyle('A1')->getFont()->setSize(EXCEL_HEADER1_FONT_SIZE);
		$activeSheet->getStyle('A2')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		
		// write column headers
		$activeSheet->setCellValue('A4', 'Date');
		$activeSheet->setCellValue('B4', 'Customer');
		$activeSheet->setCellValue('C4', 'D.R./S.I.');
		$activeSheet->setCellValue('D4', 'Gross');
		$activeSheet->setCellValue('E4', 'Cost');
		$activeSheet->setCellValue('F4', 'Rebate');
		$activeSheet->setCellValue('G4', 'Quantity');
		$activeSheet->setCellValue('H4', 'Unit');
		$activeSheet->setCellValue('I4', 'Particulars');
		$activeSheet->setCellValue('J4', 'Supplier');
		$activeSheet->setCellValue('K4', 'Discount');
		$activeSheet->setCellValue('L4', 'Selling');
		$activeSheet->setCellValue('M4', 'Total');
		$activeSheet->setCellValue('N4', 'Agent');
		$activeSheet->setCellValue('O4', 'Remarks');
		
		// set column widths
		$activeSheet->getColumnDimension('A')->setWidth(10);
		$activeSheet->getColumnDimension('B')->setWidth(50);
		$activeSheet->getColumnDimension('C')->setWidth(15);
		$activeSheet->getColumnDimension('D')->setWidth(12);
		$activeSheet->getColumnDimension('E')->setWidth(12);
		$activeSheet->getColumnDimension('F')->setWidth(12);
		$activeSheet->getColumnDimension('G')->setWidth(8);
		$activeSheet->getColumnDimension('H')->setWidth(5);
		$activeSheet->getColumnDimension('I')->setWidth(50);
		$activeSheet->getColumnDimension('J')->setWidth(30);
		$activeSheet->getColumnDimension('K')->setWidth(10);
		$activeSheet->getColumnDimension('L')->setWidth(12);
		$activeSheet->getColumnDimension('M')->setWidth(15);
		$activeSheet->getColumnDimension('N')->setWidth(20);
		$activeSheet->getColumnDimension('O')->setWidth(20);
		
		// format column headers
		$fontColor->setRGB(EXCEL_COLUMN_HEADER_FONT_COLOR);
		$backgroundColor->setRGB(EXCEL_COLUMN_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A4:O4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A4:O4')->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A4:O4')->getFont()->setColor($fontColor);
		$activeSheet->getStyle('A4:O4')->getFont()->setBold(true);
		$activeSheet->getStyle('A4:O4')->getAlignment()->setWrapText(true);
		
		// freeze pane
		$activeSheet->freezePane('A5');
		$row = 5;
		
		$sqlQuery  = "SELECT `order`.id, " .
					 "IF(sales_invoice IS NOT NULL,CONCAT('SI ',sales_invoice),CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
					 "customer.id AS customer_id, customer.name AS customer, " .
					 "agent.name AS agent, " .
					 "order_item.quantity, order_item.sidr_price, order_item.net_price, " .
					 "inventory_brand.name AS brand, inventory.model, inventory.selling_price, inventory.purchase_price " .
					 "FROM `order` " .
					 "INNER JOIN customer ON `order`.customer_id = customer.id " .
					 "INNER JOIN agent ON `order`.agent_id = agent.id " .
					 "INNER JOIN order_item ON `order`.id = order_item.order_id " .
					 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
					 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
					 "WHERE DATE_FORMAT(`order`.delivery_pickup_actual_date,'%Y-%m-%d') = '$reportDate' " . 
					 "ORDER BY `order`.id ASC";
		$resultSet = self::$database->query($sqlQuery);
		if (self::$database->getResultCount($resultSet) > 0) {
			$prevOrderId = null;
			
			$activeSheet->setCellValue('A5',$reportDate);
			
			while ($order = self::$database->getResultRow($resultSet)) {
				if ($order['id'] != $prevOrderId) {
					$activeSheet->setCellValue('B'.$row, html_entity_decode(capitalizeWords(Filter::reinput($order['customer']))));
					$activeSheet->setCellValue('C'.$row, $order['tracking_number']);
					if ($order['balance'] < 0) {
						$activeSheet->setCellValue('F'.$row, abs($order['balance']));
					}
					$activeSheet->setCellValue('N'.$row, html_entity_decode(capitalizeWords(Filter::reinput($order['agent']))));
					$activeSheet->setCellValue('O'.$row, html_entity_decode(capitalizeWords(Filter::reinput($order['remarks']))));
					
					$backgroundColor->setRGB('FFFF00');
					$activeSheet->getStyle('A'.$row.':O'.$row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$activeSheet->getStyle('A'.$row.':O'.$row)->getFill()->setStartColor($backgroundColor);
				}
				
				$activeSheet->setCellValue('D'.$row, $order['selling_price']);
				$activeSheet->setCellValue('E'.$row, $order['purchase_price']);
				$activeSheet->setCellValue('G'.$row, $order['quantity']);
				$activeSheet->setCellValue('H'.$row, 'pcs');
				$activeSheet->setCellValue('I'.$row, html_entity_decode(capitalizeWords(Filter::reinput($order['brand']))) . ' -- ' .
													 html_entity_decode(capitalizeWords(Filter::reinput($order['model']))));
				$activeSheet->setCellValue('J'.$row, 'STOCK');
				$activeSheet->setCellValue('K'.$row, 'FIX');
				$activeSheet->setCellValue('L'.$row, $order['net_price']);
				$activeSheet->setCellValue('M'.$row, '=L'.$row.'*G'.$row);
				
				$row++;
				$prevOrderId = $order['id'];
			}
		}
		
		$row++;
		$activeSheet->setCellValue('L'.$row, 'TOTAL:');
		$activeSheet->setCellValue('M'.$row, '=SUM(M5:M'.($row-2).')');
		
		
		// post formatting
		$activeSheet->getStyle('A5:A'.$row)->getNumberFormat()->setFormatCode(EXCEL_DATE_FORMAT);
		$activeSheet->getStyle('B5:B'.$row)->getAlignment()->setWrapText(true);
		$activeSheet->getStyle('C5:F'.$row)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$activeSheet->getStyle('G5:G'.$row)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);
		$activeSheet->getStyle('I5:J'.$row)->getAlignment()->setWrapText(true);
		$activeSheet->getStyle('L5:M'.$row)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$activeSheet->getStyle('M5:M'.$row)->getFont()->setBold(true);
		$activeSheet->getStyle('N5:N'.$row)->getAlignment()->setWrapText(true);
		
		// format totals
		$activeSheet->getStyle('L'.$row)->getFont()->setBold(true);
		$styleArray = array(
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THICK)
			)
		);
		$activeSheet->getStyle('A'.$row.':O'.$row)->applyFromArray($styleArray);
		$activeSheet->getStyle('A'.$row.':O'.$row)->getFont()->setColor($fontColorRed);
		
		
		// set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex( 0 );

		// redirect output to a client�s web browser (Excel2007)
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="'.CLIENT.' - Daily Sales Report - '.$fileTimeStampExtension.'.xlsx"' );
		header( 'Cache-Control: max-age=0' );

		$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( 'php://output' );
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display Periodic Sales Report
	//------------------------------------------------------------------------------------------------------------
	private static function showPeriodicSalesReport( $category, $startDate, $endDate ) {
		self::$database = new Database();
		
		$startDateStr = date_format(new DateTime($startDate), "F j, Y");
		$endDateStr   = date_format(new DateTime($endDate), "F j, Y");
		
		echo '<div id="date_header">From <span class="date">' . $startDateStr . '</span> to <span class="date">' . $endDateStr . '</span></div>';
		
		// get id of Yokohama for special handling
		$resultSet = self::$database->query("SELECT id FROM inventory_brand WHERE name='yokohama'");
		if (self::$database->getResultCount($resultSet) == 0) {
			echo '<span class="bad">Error:</span> Cannot find Yokohama in Inventory Brand list.';
			return;
		}
		$inventory  = self::$database->getResultRow($resultSet);
		$yokohamaID = $inventory['id'];
		
		if ($category == 'summary') {
			$dailySales = array();
			
			// EDIC Yokohama
			$sqlQuery  = "SELECT SUM(quantity) AS total_quantity, SUM(net_price*quantity) AS total_amount " .
					     "FROM order_item " .
					     "INNER JOIN `order` ON order_item.order_id = `order`.id " .
					     "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
					     "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
					     "WHERE (`order`.delivery_pickup_actual_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') AND " .
					     "inventory_brand.id = $yokohamaID AND `order`.business_unit = 'EDIC'";
			$resultSet = self::$database->query($sqlQuery);
			$orderItem = self::$database->getResultRow($resultSet);
			
			$dailySales['edic-yokohama'] = array('quantity' => $orderItem['total_quantity'], 'amount' => $orderItem['total_amount']);
			
			// EDIC Others
			$sqlQuery  = "SELECT SUM(quantity) AS total_quantity, SUM(net_price*quantity) AS total_amount " .
						 "FROM order_item " .
						 "INNER JOIN `order` ON order_item.order_id = `order`.id " .
						 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
						 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
						 "WHERE (`order`.delivery_pickup_actual_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') AND " .
						 "inventory_brand.id != $yokohamaID AND `order`.business_unit = 'EDIC'";
			$resultSet = self::$database->query($sqlQuery);
			$orderItem = self::$database->getResultRow($resultSet);
			
			$dailySales['edic-others'] = array('quantity' => $orderItem['total_quantity'], 'amount' => $orderItem['total_amount']);
			
			// MDJ Yokohama
			$sqlQuery  = "SELECT SUM(quantity) AS total_quantity, SUM(net_price*quantity) AS total_amount " .
					     "FROM order_item " .
					     "INNER JOIN `order` ON order_item.order_id = `order`.id " .
					     "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
					     "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
					     "WHERE (`order`.delivery_pickup_actual_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') AND " .
					     "inventory_brand.id = $yokohamaID AND `order`.business_unit = 'MDJ'";
			$resultSet = self::$database->query($sqlQuery);
			$orderItem = self::$database->getResultRow($resultSet);
			
			$dailySales['mdj-yokohama'] = array('quantity' => $orderItem['total_quantity'], 'amount' => $orderItem['total_amount']);
			
			// MDJ Others
			$sqlQuery  = "SELECT SUM(quantity) AS total_quantity, SUM(net_price*quantity) AS total_amount " .
						 "FROM order_item " .
						 "INNER JOIN `order` ON order_item.order_id = `order`.id " .
						 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
						 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
						 "WHERE (`order`.delivery_pickup_actual_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') AND " .
						 "inventory_brand.id != $yokohamaID AND `order`.business_unit = 'MDJ'";
			$resultSet = self::$database->query($sqlQuery);
			$orderItem = self::$database->getResultRow($resultSet);
			
			$dailySales['mdj-others'] = array('quantity' => $orderItem['total_quantity'], 'amount' => $orderItem['total_amount']);
			
			echo '<br /><table id="daily_sales_table" class="item_input_table report_table"><thead>' .
				 '<tr><th id="summary_category">Catergory</th><th id="summary_quantity">Total Quantity</th><th id="summary_amount">Total Amount</th>' .
				 '</tr></thead><tbody>';
			
			echo '<tr class="item_row">' .
				 '<td>EDIC Yokohama</td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['edic-yokohama']['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['edic-yokohama']['amount'], 'float') . '</span></td>' .
				 '</tr>';
			echo '<tr class="item_row">' .
				 '<td>EDIC Others</td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['edic-others']['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['edic-others']['amount'], 'float') . '</span></td>' .
				 '</tr>';
			echo '<tr class="item_row">' .
				 '<td>MDJ Yokohama</td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['mdj-yokohama']['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['mdj-yokohama']['amount'], 'float') . '</span></td>' .
				 '</tr>';
			echo '<tr class="item_row">' .
				 '<td>MDJ Others</td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['mdj-others']['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($dailySales['mdj-others']['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			echo '<tr class="totals_bottom"><td colspan="3"></td></tr>';
			
			$totalYokohama = array('quantity' => $dailySales['edic-yokohama']['quantity'] + $dailySales['mdj-yokohama']['quantity'],
								   'amount'   => $dailySales['edic-yokohama']['amount'] + $dailySales['mdj-yokohama']['amount']);
			echo '<tr class="item_row">' .
				 '<td>Total of Yokohama</td>' .
				 '<td class="number"><span>' . numberFormat($totalYokohama['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($totalYokohama['amount'], 'float') . '</span></td>' .
				 '</tr>';
			$totalOthers = array('quantity' => $dailySales['edic-others']['quantity'] + $dailySales['mdj-others']['quantity'],
								 'amount'   => $dailySales['edic-others']['amount'] + $dailySales['mdj-others']['amount']);
			echo '<tr class="item_row">' .
				 '<td>Total of Others</td>' .
				 '<td class="number"><span>' . numberFormat($totalOthers['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($totalOthers['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			echo '<tr class="totals_bottom"><td colspan="3"></td></tr>';
			
			$totalEdic = array('quantity' => $dailySales['edic-yokohama']['quantity'] + $dailySales['edic-others']['quantity'],
							   'amount'   => $dailySales['edic-yokohama']['amount'] + $dailySales['edic-others']['amount']);
			echo '<tr class="item_row">' .
				 '<td>Total of EDIC</td>' .
				 '<td class="number"><span>' . numberFormat($totalEdic['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($totalEdic['amount'], 'float') . '</span></td>' .
				 '</tr>';
			$totalMdj = array('quantity' => $dailySales['mdj-yokohama']['quantity'] + $dailySales['mdj-others']['quantity'],
							  'amount'   => $dailySales['mdj-yokohama']['amount'] + $dailySales['mdj-others']['amount']);
			echo '<tr class="item_row">' .
				 '<td>Total of MDJ</td>' .
				 '<td class="number"><span>' . numberFormat($totalMdj['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span>' . numberFormat($totalMdj['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			echo '<tr class="totals_bottom"><td colspan="3"></td></tr>';
			
			$grandTotal = array('quantity' => $totalEdic['quantity'] + $totalMdj['quantity'],
								'amount'   => $totalEdic['amount'] + $totalMdj['amount']);
			echo '<tr class="item_row">' .
				 '<td><span class="important_label">Grand Total</span></td>' .
				 '<td class="number"><span class="important_label">' . numberFormat($grandTotal['quantity'], 'int') . '</span></td>' .
				 '<td class="number"><span class="important_label">' . numberFormat($grandTotal['amount'], 'float') . '</span></td>' .
				 '</tr>';
			
			echo '</tbody></table>';
		} else {
			// determine category
			switch ($category) {
				case 'edic-yokohama': $condition = "inventory_brand.id = $yokohamaID AND `order`.business_unit = 'EDIC'";  break;
				case 'edic-others'  : $condition = "inventory_brand.id != $yokohamaID AND `order`.business_unit = 'EDIC'"; break;
				case 'mdj-yokohama' : $condition = "inventory_brand.id = $yokohamaID AND `order`.business_unit = 'MDJ'";   break;
				case 'mdj-others'   : $condition = "inventory_brand.id != $yokohamaID AND `order`.business_unit = 'MDJ'";  break;
				default				: return;
			}
			
			// perform query
			$sqlQuery  = "SELECT DATE_FORMAT(delivery_pickup_actual_date,'%M %e, %Y, %a') AS delivery_pickup_actual_date, `order`.id, " .
						 "IF(sales_invoice IS NOT NULL,CONCAT('SI ',sales_invoice),CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
						 "customer.id AS customer_id, customer.name AS customer " .
						 "FROM `order` " .
						 "INNER JOIN customer ON `order`.customer_id = customer.id " .
						 "INNER JOIN order_item ON `order`.id = order_item.order_id " .
						 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
						 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
						 "WHERE (`order`.delivery_pickup_actual_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') AND " .
						 "$condition GROUP BY `order`.id " .
						 "ORDER BY `order`.delivery_pickup_actual_date ASC";
			$resultSet = self::$database->query($sqlQuery);
			if (self::$database->getResultCount($resultSet) == 0) {
				echo '<p>No orders found.</p>';
				return;
			}
			
			$orderList = array();
			while ($order = self::$database->getResultRow($resultSet)) {
				array_push($orderList, $order);
			}
			
			$currency          = CURRENCY;
			$subtotalQuantity  = 0;
			$subtotalNetAmount = 0;
			$totalQuantity     = 0;
			$totalNetAmount    = 0;
			$previousDate      = null;
			
			foreach ($orderList as $order) {
				if ($order['delivery_pickup_actual_date'] != $previousDate) {     // same date division
					if ($previousDate != null) {
						$subtotalNetAmount = numberFormat($subtotalNetAmount, 'float');
						echo '<tr><td colspan="9"></td></tr><tr>' .
							 '<td colspan="3" class="summary_label"><span class="important_label">Subtotal:</span></td>' .
							 '<td class="number"><span class="important_label">' . $subtotalQuantity . '</span></td>' .
							 '<td colspan="4" class="summary_label"><span class="important_label">' . $currency . '</span></td>' .
							 '<td class="number"><span class="important_label">' . $subtotalNetAmount . '</span></td>' .
							 '</tr></tbody></table>';
					}
					
					$subtotalQuantity  = 0;
					$subtotalNetAmount = 0;
					
					echo '<br /><div class="report_date_header"><a href="report.php?type=daily-sales&category=' . $category . '&date=' .
						 urldecode($order['delivery_pickup_actual_date']) . '&src=periodic-sales" title="View Daily Sales">' .
						 $order['delivery_pickup_actual_date'] . '</a></div>';
					
					$previousDate = $order['delivery_pickup_actual_date'];
					
					echo '<br /><table id="daily_sales_table" class="item_input_table report_table"><thead><tr>' .
						 '<th id="order_number">Order No.</th>' .
						 '<th id="tracking_number">Invoice No.</th>' .
						 '<th id="customer">Customer</th>' .
						 '<th id="quantity">Qty</th>' .
						 '<th id="brand">Brand</th>' .
						 '<th id="model">Model</th>' .
						 '<th id="sidr_price">SI/DR Price</th>' .
						 '<th id="net_price">Net Price</th>' .
						 '<th id="net_amount">Net Amount<br /><span class="subheader">(Net Price x Qty)</span></th>' .
						 '</tr></thead><tbody>';
				}
				
				
				$sqlQuery  = "SELECT quantity, inventory_brand.name AS brand, inventory.model, " .
							 "sidr_price, net_price " .
							 "FROM order_item " .
							 "INNER JOIN `order` ON order_item.order_id = `order`.id " .
							 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
							 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
							 "WHERE order_item.order_id = {$order['id']} AND $condition";
				$resultSet = self::$database->query($sqlQuery);
				if (!$resultSet) {
					continue;
				}
				
				$firstRow = true;
				while ($orderItem = self::$database->getResultRow($resultSet)) {
					echo '<tr class="item_row">';
					
					if ($firstRow) {
						echo '<td><a href="view_order_details.php?id=' . $order['id'] . '">' . $order['id'] . '</a></td>';
						echo '<td>' . $order['tracking_number'] . '</td>';
						echo '<td><a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
							 capitalizeWords(Filter::output($order['customer'])) . '</a></td>';
						$firstRow = false;
					} else {
						echo '<td></td><td></td><td></td>';
					}
					
					$subtotalQuantity = $subtotalQuantity + $orderItem['quantity'];
					$totalQuantity    = $totalQuantity + $orderItem['quantity'];
					
					echo '<td class="number"><span>' . numberFormat($orderItem['quantity'], 'int') . '</span></td>';
					echo '<td>' . capitalizeWords(Filter::output($orderItem['brand'])) . '</td>';
					echo '<td>' . capitalizeWords(Filter::output($orderItem['model'])) . '</td>';
					echo '<td class="number"><span>' . numberFormat($orderItem['sidr_price'], 'float') . '</span></td>';
					echo '<td class="number"><span>' . numberFormat($orderItem['net_price'], 'float') . '</span></td>';
					
					$netAmount = $orderItem['net_price'] * $orderItem['quantity'];
					
					echo '<td class="number"><span>' . numberFormat($netAmount, 'float') . '</span></td>';
					
					$subtotalNetAmount = $subtotalNetAmount + $netAmount;
					$totalNetAmount    = $totalNetAmount + $netAmount;
					
					echo '</tr>';
				}
			}
			
			
			$subtotalNetAmount = numberFormat($subtotalNetAmount, 'float');
			$totalNetAmount    = numberFormat($totalNetAmount, 'float');
			
			echo '<tr><td colspan="9"></td></tr><tr>' .
				 '<td colspan="3" class="summary_label"><span class="important_label">Subtotal:</span></td>' .
				 '<td class="number"><span class="important_label">' . $subtotalQuantity . '</span></td>' .
				 '<td colspan="4" class="summary_label"><span class="important_label">' . $currency . '</span></td>' .
				 '<td class="number"><span class="important_label">' . $subtotalNetAmount . '</span></td>' .
				 '</tr><tr><td colspan="9"></td></tr><tr style="border-top: 1px solid #000000">' .
				 '<td colspan="3" class="summary_label"><span class="important_label">Grand Total:</span></td>' .
				 '<td class="number"><span class="important_label">' . $totalQuantity . '</span></td>' .
				 '<td colspan="4" class="summary_label"><span class="important_label">' . $currency . '</span></td>' .
				 '<td class="number"><span class="important_label">' . $totalNetAmount . '</span></td>' .
				 '</tr></tbody></table>';
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display Projected Collections Report
	//------------------------------------------------------------------------------------------------------------
	private static function showProjectedCollectionsReport( $category, $startDate, $endDate ) {
		self::$database = new Database();
		
		$startDateStr = date_format(new DateTime($startDate), 'F j, Y');
		$endDateStr   = date_format(new DateTime($endDate), 'F j, Y');
		
		echo '<div id="date_header">From <span class="date">' . $startDateStr . '</span> to <span class="date">' . $endDateStr . '</span></div>';
		
		// perform query
		if ($category == 'incoming') {
			$sqlQuery = "(SELECT `order`.id AS order_id, " .
						"IF(sales_invoice IS NOT NULL, CONCAT('SI ',sales_invoice), CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
						"customer_id, " .
						"customer.name AS customer_name, " .
						"balance AS amount_to_collect, " .
						"DATE_FORMAT(delivery_pickup_target_date,'%M %e, %Y, %a') AS payment_date " .
						"FROM `order` " .
						"INNER JOIN customer ON `order`.customer_id = customer.id " .
						"WHERE (`order`.delivery_pickup_target_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') " .
						"AND payment_term = 'full' " .
						"AND cleared_date IS NULL " .
						"AND canceled_date IS NULL " .
						"AND balance > 0) " .
						"UNION " .
						"(SELECT order_id, " .
						"IF(sales_invoice IS NOT NULL, CONCAT('SI ',sales_invoice), CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
						"customer_id, " .
						"customer.name AS customer_name, " .
						"SUM(amount_due-amount_received) AS amount_to_collect, " .
						"DATE_FORMAT(due_date,'%M %e, %Y, %a') AS payment_date " .
						"FROM v_order_installment_paid " .
						"INNER JOIN `order` ON v_order_installment_paid.order_id = `order`.id " .
						"INNER JOIN customer ON `order`.customer_id = customer.id " .
						"WHERE (due_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') " .
						"AND payment_term = 'installment' " .
						"AND cleared_date IS NULL " .
						"AND canceled_date IS NULL " .
						"GROUP BY order_id, due_date " .
						"HAVING amount_to_collect > 0) " .
						"ORDER BY payment_date ASC";
			
		} elseif ($category == 'outgoing') {
			$sqlQuery = "(SELECT purchase.id AS purchase_id, " .
						"IF(sales_invoice IS NOT NULL, CONCAT('SI ',sales_invoice), CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
						"supplier_id, " .
						"supplier.name AS supplier_name, " .
						"balance AS amount_to_pay, " .
						"DATE_FORMAT(delivery_pickup_target_date,'%M %e, %Y, %a') AS payment_date " .
						"FROM purchase " .
						"INNER JOIN supplier ON purchase.supplier_id = supplier.id " .
						"WHERE (purchase.delivery_pickup_target_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') " .
						"AND payment_term = 'full' " .
						"AND cleared_date IS NULL " .
						"AND canceled_date IS NULL " .
						"AND balance > 0) " .
						"UNION " .
						"(SELECT purchase_id, " .
						"IF(sales_invoice IS NOT NULL, CONCAT('SI ',sales_invoice), CONCAT('DR ',delivery_receipt)) AS tracking_number, " .
						"supplier_id, " .
						"supplier.name AS supplier_name, " .
						"SUM(amount_due-amount_paid) AS amount_to_pay, " .
						"DATE_FORMAT(due_date,'%M %e, %Y, %a') AS payment_date " .
						"FROM v_purchase_installment_paid " .
						"INNER JOIN purchase ON v_purchase_installment_paid.purchase_id = purchase.id " .
						"INNER JOIN supplier ON purchase.supplier_id = supplier.id " .
						"WHERE (due_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59') " .
						"AND payment_term = 'installment' " .
						"AND cleared_date IS NULL " .
						"AND canceled_date IS NULL " .
						"GROUP BY purchase_id, due_date " .
						"HAVING amount_to_pay > 0) " .
						"ORDER BY payment_date ASC";
			
		} else {
			return;
		}
		
		$resultSet = self::$database->query($sqlQuery);
		if (self::$database->getResultCount($resultSet) == 0) {
			echo '<p>No orders found.</p>';
			return;
		}
		
		$currency       = CURRENCY;
		$subtotalAmount = 0;
		$totalAmount    = 0;
		$previousDate   = null;
		
		while ($order = self::$database->getResultRow($resultSet)) {
			if ($order['payment_date'] != $previousDate) {     // same date division
				if ($previousDate != null) {
					$totalAmount    = $totalAmount + $subtotalAmount;
					$subtotalAmount = numberFormat($subtotalAmount, 'float');
					
					echo '<tr><td colspan="5"></td></tr><tr>' .
						 '<td colspan="3" class="summary_label"><span class="important_label">Subtotal:</span></td>' .
						 '<td class="summary_label"><span class="important_label">' . $currency . '</span></td>' .
						 '<td class="number"><span class="important_label">' . $subtotalAmount . '</span></td>' .
						 '</tr></tbody></table>';
				}
				
				$subtotalAmount = 0;
				
				echo '<br /><div class="report_date_header"><span class="payment_date_header">' . $order['payment_date'] . '</span></div>';
				
				$previousDate = $order['payment_date'];
				
				if ($category == 'incoming') {
					echo '<br /><table id="projected_collections_table" class="item_input_table report_table"><thead><tr>' .
						 '<th id="order_number">Order No.</th>' .
						 '<th id="tracking_number">Invoice No.</th>' .
						 '<th id="customer">Customer</th>' .
						 '<th></th>' .
						 '<th id="amount">Amount to Collect (' . $currency . ')</th>' .
						 '</tr></thead><tbody>';
				} else {
					echo '<br /><table id="projected_collections_table" class="item_input_table report_table"><thead><tr>' .
						 '<th id="order_number">Purchase No.</th>' .
						 '<th id="tracking_number">Invoice No.</th>' .
						 '<th id="customer">Supplier</th>' .
						 '<th></th>' .
						 '<th id="amount">Amount to Pay (' . $currency . ')</th>' .
						 '</tr></thead><tbody>';
				}
			}
			
			
			echo '<tr class="item_row">';
			
			if ($category == 'incoming') {
				echo '<td><a href="view_order_details.php?id=' . $order['order_id'] . '">' . $order['order_id'] . '</a></td>' .
					 '<td>' . $order['tracking_number'] . '</td>' .
					 '<td><a href="view_customer_details.php?id=' . $order['customer_id'] . '">' .
					 capitalizeWords(Filter::output($order['customer_name'])) . '</a></td>' .
					 '<td></td>' . 		// filler cell
					 '<td class="number"><span>' . numberFormat($order['amount_to_collect'], 'float') . '</span></td>';
				
				$subtotalAmount = $subtotalAmount + $order['amount_to_collect'];
			} else {
				echo '<td><a href="view_purchase_details.php?id=' . $order['purchase_id'] . '">' . $order['purchase_id'] . '</a></td>' .
					 '<td>' . $order['tracking_number'] . '</td>' .
					 '<td><a href="view_supplier_details.php?id=' . $order['supplier_id'] . '">' .
					 capitalizeWords(Filter::output($order['supplier_name'])) . '</a></td>' .
					 '<td></td>' . 		// filler cell
					 '<td class="number"><span>' . numberFormat($order['amount_to_pay'], 'float') . '</span></td>';
				
				$subtotalAmount = $subtotalAmount + $order['amount_to_pay'];
			}
			
			echo '</tr>';
		}
		
		
		$totalAmount    = $totalAmount + $subtotalAmount;
		$subtotalAmount = numberFormat($subtotalAmount, 'currency');
		$totalAmount    = numberFormat($totalAmount, 'currency');
		
		echo '<tr><td colspan="5"></td></tr><tr>' .
			 '<td colspan="3" class="summary_label"><span class="important_label">Subtotal:</span></td>' .
			 '<td class="summary_label"><span class="important_label">' . $currency . '</span></td>' .
			 '<td class="number"><span class="important_label">' . $subtotalAmount . '</span></td>' .
			 '</tr><tr><td colspan="5"></td></tr><tr style="border-top: 1px solid #000000">' .
			 '<td colspan="3" class="summary_label"><span class="important_label">Grand Total:</span></td>' .
			 '<td class="summary_label"><span class="important_label">' . $currency . '</span></td>' .
			 '<td class="number"><span class="important_label">' . $totalAmount . '</span></td>' .
			 '</tr></tbody></table>';
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display Inventory Report
	//------------------------------------------------------------------------------------------------------------
	private static function showInventoryReport( $category, DateTime $startDate, DateTime $endDate ) {
		self::$database = new Database();
		$currency       = CURRENCY;
		
		$startDateStr = $startDate->format('F j, Y, D');
		$endDateStr   = $endDate->format('F j, Y, D');
		
		if ($category == 'sold-items' || $category == 'purchased-items') {
			// display date selection
			echo '<section><div style="margin-left: auto; margin-right: auto; width: 780px"><br />' .
				 '<form name="report_date_form" method="get" action="report.php" autocomplete="off">' .
				 '<input type="hidden" name="type" value="inventory" />' .
				 '<input type="hidden" name="category" value="' . $category . '" />' .
				 '<label for="startdate">Start Date:</label>' .
				 '<input type="text" name="startdate" id="startdate" class="datepicker_no_future_date" size="30" 
				 		 maxlength="30" required="required" value="' . $startDateStr . '" />' .
				 '<label for="enddate">End Date:</label>' .
				 '<input type="text" name="enddate" id="enddate" class="datepicker_no_future_date" size="30" 
				 		 maxlength="30" required="required" value="' . $endDateStr . '" />' .
				 '<input type="submit" name="submit_form" value="Go" />' .
				 '</form></div></section>';
			
			if ($startDate > $endDate) {
				echo '<p><span class="bad">Error:</span> The start date is greater than end date. Please select a different set of dates.</p>';
				return;
			}
		}
		
		switch ($category) {
			case 'remaining':
				$sqlQuery  = "SELECT inventory_brand.*, " .
							 "SUM(stock_count) AS stock_count, " .
							 "SUM(purchase_price*stock_count) AS stock_amount, " .
							 "SUM(selling_price*stock_count) AS expected_sales, " .
							 "SUM(selling_price*stock_count)-SUM(purchase_price*stock_count) AS expected_income, " .
							 "SUM(reserved_stock) AS reserved_stock " .
							 "FROM inventory " .
							 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
							 "GROUP BY brand_id " .
							 "ORDER BY name ASC";
				$resultSet = self::$database->query($sqlQuery);
				
				$currentDate = new DateTime;
				$currentDate = $currentDate->format('F j, Y');
				
				echo '<div id="date_header">As of Today, <span class="date">' . $currentDate . '</span></div>';
				
				if (self::$database->getResultCount($resultSet) == 0) {
					echo '<p>Inventory is empty.</p>';
					return;
				}
				
				$currency = CURRENCY;
				
				echo '<br /><table id="inventory_remaining_table" class="item_input_table report_table"><thead><tr>' .
					 '<th id="brand">Brand</th>' .
					 '<th id="stock_count">Available Stocks</th>' .
					 '<th class="net_amount">Stock Amount (' . $currency . ')<br /><span class="subheader">(Purchase Price x Available Stocks)</span></th>' .
					 '<th class="net_amount">Expected Sales (' . $currency . ')<br /><span class="subheader">(Selling Price x Available Stocks)</span></th>' .
					 '<th class="net_amount">Expected Profit (' . $currency . ')<br /><span class="subheader">(Expected Sales - Stock Amount)</span></th>' .
					 '<th id="demand_count">Demand</th>' .
					 '</tr></thead><tbody>';
				
				$totalQuantity         = 0;
				$totalStockAmount      = 0;
				$totalExpectedRevenues = 0;
				$totalExpectedIncome   = 0;
				$totalReservation      = 0;
				
				while ($report = self::$database->getResultRow($resultSet)) {
					echo '<tr class="item_row">';
					
					// brand
					echo '<td><a href="list_inventory_models.php?brandID=' . $report['id'] . '" Title="Show models" target="top">' .
						 capitalizeWords(Filter::output($report['name'])) . '</a></td>';
					
					// available stocks
					if ($report['stock_count'] <= 0) {
						echo '<td class="number"><span class="bad">' . numberFormat($report['stock_count'], 'int', 0) . '</span></td>';
					} else {
						echo '<td class="number"><span>' . numberFormat($report['stock_count'], 'int', 0) . '</span></td>';
					}
					$totalQuantity = $totalQuantity + $report['stock_count'];
					
					// stock amount
					if ($report['stock_amount'] <= 0) {
						echo '<td class="number"><span class="bad">' . numberFormat($report['stock_amount'], 'float') . '</span></td>';
					} else {
						echo '<td class="number"><span>' . numberFormat($report['stock_amount'], 'float') . '</span></td>';
					}
					$totalStockAmount = $totalStockAmount + $report['stock_amount'];
					
					// expected revenues
					if ($report['expected_sales'] <= 0) {
						echo '<td class="number"><span class="bad">' . numberFormat($report['expected_sales'], 'float') . '</span></td>';
					} else {
						echo '<td class="number"><span>' . numberFormat($report['expected_sales'], 'float') . '</span></td>';
					}
					$totalExpectedRevenues = $totalExpectedRevenues + $report['expected_sales'];
					
					// expected income
					if ($report['expected_income'] <= 0) {
						echo '<td class="number"><span class="bad">' . numberFormat($report['expected_income'], 'float') . '</span></td>';
					} else {
						echo '<td class="number"><span>' . numberFormat($report['expected_income'], 'float') . '</span></td>';
					}
					$totalExpectedIncome = $totalExpectedIncome + $report['expected_income'];
					
					// demand
					if ($report['reserved_stock'] <= 0) {
						echo '<td class="number"><span class="bad">' . numberFormat($report['reserved_stock'], 'int', 0) . '</span></td>';
					} else {
						echo '<td class="number"><span>' . numberFormat($report['reserved_stock'], 'int', 0) . '</span></td>';
					}
					$totalReservation = $totalReservation + $report['reserved_stock'];
					
					echo '</tr>';
				}
				
				$totalQuantity         = numberFormat($totalQuantity, 'int');
				$totalStockAmount      = numberFormat($totalStockAmount, 'currency');
				$totalExpectedRevenues = numberFormat($totalExpectedRevenues, 'currency');
				$totalExpectedIncome   = numberFormat($totalExpectedIncome, 'currency');
				
				echo '<tr><td colspan="4"></td></tr><tr>' .
					 '<td class="summary_label"><span class="important_label">Totals:</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalQuantity . '</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalStockAmount . '</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalExpectedRevenues . '</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalExpectedIncome . '</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalReservation . '</span></td>' .
					 '</tr></tbody></table>';			
				
				break;
			
			
			case 'to-buy-items':
				$sqlQuery  = "SELECT inventory.id, inventory_brand.name, " .
							 "model, stock_count, reserved_stock, (reserved_stock-stock_count) AS missing_stock, " .
							 "((reserved_stock-stock_count)*purchase_price) AS amount " .
							 "FROM inventory " .
							 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
							 "WHERE stock_count < reserved_stock AND " .
							 "parent_id IS NULL " .
							 "ORDER BY missing_stock DESC";
				$resultSet = self::$database->query($sqlQuery);
				
				$currentDate = new DateTime;
				$currentDate = $currentDate->format('F j, Y');
				
				echo '<div id="date_header">As of Today <span class="date">' . $currentDate . '</span></div>';
				
				if (self::$database->getResultCount($resultSet) == 0) {
					echo '<p>No items needed to be purchased.</p>';
					return;
				}
				
				echo '<br /><table id="inventory_to_buy_table" class="item_input_table report_table"><thead><tr>' .
					 '<th id="brand">Brand</th>' . 
					 '<th id="model">Model</th>' .
					 '<th class="stock_count">Available Stocks</th>' .
					 '<th class="stock_count">Demand</th>' .
					 '<th class="stock_count">Missing Stocks<br /><span class="subheader">(Demand - Available Stocks)</span></th>' .
					 '<th id="net_amount">Approx. Amount (' . $currency . ')<br /><span class="subheader">(Demand x Purchase Price)</span></th>' .
					 '</tr></thead><tbody>';
				
				$totalQuantity = 0;
				$totalAmount   = 0;
				
				while ($report = self::$database->getResultRow($resultSet)) {
					echo '<tr class="item_row">' .
						 '<td>' . capitalizeWords(Filter::output($report['name'])) . '</td>' .
						 '<td><a href="list_inventory_orders.php?inv-id=' . $report['id'] . '" target="top">' .
						 capitalizeWords(Filter::output($report['model'])) . '</a></td>';
					
					if ($report['stock_count'] == 0) {
						echo '<td class="number"><span class="bad">' . numberFormat($report['stock_count'], 'int', 0) . '</span></td>';
					} else {
						echo '<td class="number"><span>' . numberFormat($report['stock_count'], 'int', 0) . '</span></td>';
					}
					
					echo '<td class="number"><span>' . numberFormat($report['reserved_stock'], 'int', 0) . '</span></td>' .
						 '<td class="number"><span class="bad">' . numberFormat($report['missing_stock'], 'int', 0) . '</span></td>' .
						 '<td class="number"><span>' . numberFormat($report['amount'], 'float') . '</span></td>' .
						 '</tr>';
					
					$totalQuantity = $totalQuantity + $report['missing_stock'];
					$totalAmount   = $totalAmount + $report['amount'];
				}
				
				$totalQuantity = numberFormat($totalQuantity, 'int', 0);
				$totalAmount   = numberFormat($totalAmount, 'currency');
				
				echo '<tr><td colspan="6"></td></tr><tr>' .
					 '<td colspan="4" class="summary_label"><span class="important_label">Totals:</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalQuantity . '</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalAmount . '</span></td>' .
					 '</tr></tbody></table>';
				
				break;
			
			
			case 'sold-items':
				$sqlQuery  = "SELECT inventory_brand.name, " .
							 "inventory.model, " .
							 "SUM(quantity-undelivered_quantity) AS delivered_quantity, " .
							 "SUM((quantity-undelivered_quantity)*net_price) AS sold_amount " .
							 "FROM order_item " .
							 "INNER JOIN `order` ON order_item.order_id = `order`.id " .
							 "INNER JOIN inventory ON order_item.inventory_id = inventory.id " .
							 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
							 "WHERE (order_date BETWEEN '" . $startDate->format("Y-m-d") . " 00:00:00' AND '" . $endDate->format("Y-m-d") . " 23:59:59') AND " .
							 "canceled_date IS NULL " .
							 "GROUP BY order_item.inventory_id " .
							 "HAVING delivered_quantity > 0 " .
							 "ORDER BY quantity DESC";
				$resultSet = self::$database->query($sqlQuery);
				
				$startDateStr = $startDate->format('F j, Y');
				$endDateStr   = $endDate->format('F j, Y');
				
				echo '<div id="date_header">From <span class="date">' . $startDateStr . '</span> to <span class="date">' . $endDateStr . '</span></div>';
				
				if (self::$database->getResultCount($resultSet) == 0) {
					echo '<p>No items sold within this period.</p>';
					return;
				}
				
				echo '<table id="inventory_sold_table" class="item_input_table report_table"><thead><tr>' .
					 '<th id="brand">Brand</th>' .
					 '<th id="model">Model</th>' .
					 '<th id="stock_count">No. of Stocks Sold</th>' .
					 '<th id="net_amount">Sold Amount (' . $currency . ')</th>' .
					 '</tr></thead><tbody>';
				
				$totalQuantity = 0;
				$totalAmount   = 0;
				
				while ($report = self::$database->getResultRow($resultSet)) {
					echo '<tr class="item_row">' .
						 '<td>' . capitalizeWords(Filter::output($report['name'])) . '</td>' .
						 '<td>' . capitalizeWords(Filter::output($report['model'])) . '</td>' .
						 '<td class="number"><span>' . numberFormat($report['delivered_quantity'], 'int', 0) . '</span></td>' .
						 '<td class="number"><span>' . numberFormat($report['sold_amount'], 'float') . '</span></td>' .
						 '</tr>';
					
					$totalQuantity = $totalQuantity + $report['delivered_quantity'];
					$totalAmount   = $totalAmount + $report['sold_amount'];
				}
				
				$totalQuantity = numberFormat($totalQuantity, 'int', 0);
				$totalAmount   = numberFormat($totalAmount, 'currency');
				
				echo '<tr><td colspan="4"></td></tr><tr>' .
					 '<td colspan="2" class="summary_label"><span class="important_label">Totals:</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalQuantity . '</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalAmount . '</span></td>' .
					 '</tr></tbody></table>';
				
				break;
			
			
			case 'purchased-items':
				$sqlQuery  = "SELECT inventory_brand.name, " .
							 "inventory.model, " .
							 "SUM(quantity-undelivered_quantity) AS purchased_quantity, " .
							 "SUM((quantity-undelivered_quantity)*net_price) AS purchased_amount " .
							 "FROM purchase_item " .
							 "INNER JOIN purchase ON purchase_item.purchase_id = purchase.id " .
							 "INNER JOIN inventory ON purchase_item.inventory_id = inventory.id " .
							 "INNER JOIN inventory_brand ON inventory.brand_id = inventory_brand.id " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format("Y-m-d") . " 00:00:00' AND '" . $endDate->format("Y-m-d") .
							 " 23:59:59') AND " .
							 "canceled_date IS NULL " .
							 "GROUP BY purchase_item.inventory_id " .
							 "HAVING purchased_quantity > 0 " .
							 "ORDER BY quantity DESC";
				$resultSet = self::$database->query($sqlQuery);
				
				$startDateStr = $startDate->format('F j, Y');
				$endDateStr   = $endDate->format('F j, Y');
				
				echo '<div id="date_header">From <span class="date">' . $startDateStr . '</span> to <span class="date">' . $endDateStr . '</span></div>';
				
				if (self::$database->getResultCount($resultSet) == 0) {
					echo '<p>No items purchased within this period.</p>';
					return;
				}
				
				echo '<table id="inventory_sold_table" class="item_input_table report_table"><thead><tr>' .
					 '<th id="brand">Brand</th>' .
					 '<th id="model">Model</th>' .
					 '<th id="stock_count">No. of Stocks Purchased</th>' .
					 '<th id="net_amount">Purchase Amount (' . $currency . ')</th>' .
					 '</tr></thead><tbody>';
				
				$totalQuantity = 0;
				$totalAmount   = 0;
				
				while ($report = self::$database->getResultRow($resultSet)) {
					echo '<tr class="item_row">' .
						 '<td>' . capitalizeWords(Filter::output($report['name'])) . '</td>' .
						 '<td>' . capitalizeWords(Filter::output($report['model'])) . '</td>' .
						 '<td class="number"><span>' . numberFormat($report['purchased_quantity'], 'int', 0) . '</span></td>' .
						 '<td class="number"><span>' . numberFormat($report['purchased_amount'], 'float') . '</span></td>' .
						 '</tr>';
					
					$totalQuantity = $totalQuantity + $report['purchased_quantity'];
					$totalAmount = $totalAmount + $report['purchased_amount'];
				}
				
				$totalQuantity = numberFormat($totalQuantity, 'int', 0);
				$totalAmount   = numberFormat($totalAmount, 'currency');
				
				echo '<tr><td colspan="4"></td></tr><tr>' .
					 '<td colspan="2" class="summary_label"><span class="important_label">Totals:</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalQuantity . '</span></td>' .
					 '<td class="number"><span class="important_label">' . $totalAmount . '</span></td>' .
					 '</tr></tbody></table>';
				
				break;
			
			
			default:
				return;
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display Revenue and Expense Report
	//------------------------------------------------------------------------------------------------------------
	private static function showRevenueExpenseReport( $category, $viewType, DateTime $startDate, DateTime $endDate ) {
		self::$database = new Database();
		
		$startDateStr = $startDate->format('F j, Y');
		$endDateStr   = $endDate->format('F j, Y');
		
		echo '<div id="date_header">From <span class="date">' . $startDateStr . '</span> to <span class="date">' . $endDateStr . '</span></div>';
		
		// category constants
		$CAT1 = 'orders-trend';
		$CAT2 = 'revenues-trend';
		$CAT3 = 'purchases-trend';
		$CAT4 = 'expenses-trend';
		
		// determine query parameters for selected view type
		switch ($viewType) {
			case 'day':
				$interval = new DateInterval('P1D');
				
				$unit[$CAT1]    = "DATE_FORMAT(order_date,'%Y-%m-%d') AS unit";
				$sorting[$CAT1] = "YEAR(order_date) ASC, MONTH(order_date) ASC, DAY(order_date) ASC";
				
				$unit[$CAT2]    = "DATE_FORMAT(clearing_actual_date,'%Y-%m-%d') AS unit";
				$sorting[$CAT2] = "YEAR(clearing_actual_date) ASC, MONTH(clearing_actual_date) ASC, DAY(clearing_actual_date) ASC";
				
				$unit[$CAT3]    = "DATE_FORMAT(purchase_date,'%Y-%m-%d') AS unit";
				$sorting[$CAT3] = "YEAR(purchase_date) ASC, MONTH(purchase_date) ASC, DAY(purchase_date) ASC";
				
				$unit[$CAT4]    = "DATE_FORMAT(clearing_actual_date,'%Y-%m-%d') AS unit";
				$sorting[$CAT4] = "YEAR(clearing_actual_date) ASC, MONTH(clearing_actual_date) ASC, DAY(clearing_actual_date) ASC";
				
				if ($startDate == $endDate) {
					echo '<p><span class="bad">Error:</span> The start and end dates the same. Please select a different set of dates.</p>';
					return;
				}
				
				break;
			
			
			case 'week':
				$interval = new DateInterval('P1W');
				
				$unit[$CAT1]    = "WEEK(order_date) AS unit";
				$sorting[$CAT1] = "YEAR(order_date) ASC, MONTH(order_date) ASC, WEEK(order_date) ASC";
				
				$unit[$CAT2]    = "WEEK(clearing_actual_date) AS unit";
				$sorting[$CAT2] = "YEAR(clearing_actual_date) ASC, MONTH(clearing_actual_date) ASC, WEEK(clearing_actual_date) ASC";
				
				$unit[$CAT3]    = "WEEK(purchase_date) AS unit";
				$sorting[$CAT3] = "YEAR(purchase_date) ASC, MONTH(purchase_date) ASC, WEEK(purchase_date) ASC";
				
				$unit[$CAT4]    = "WEEK(clearing_actual_date) AS unit";
				$sorting[$CAT4] = "YEAR(clearing_actual_date) ASC, MONTH(clearing_actual_date) ASC, WEEK(clearing_actual_date) ASC";
				
				if ($startDate->format('Y W') == $endDate->format('Y W')) {
					echo '<p><span class="bad">Error:</span> The start and end dates fall in the same week. Please select a different set of dates.</p>';
					return;
				}
				
				break;
			
			
			case 'month':
				$interval = new DateInterval('P1M');
				
				$unit[$CAT1]    = "DATE_FORMAT(order_date,'%Y-%m') AS unit";
				$sorting[$CAT1] = "YEAR(order_date) ASC, MONTH(order_date) ASC";
				
				$unit[$CAT2]    = "DATE_FORMAT(clearing_actual_date,'%Y-%m') AS unit";
				$sorting[$CAT2] = "YEAR(clearing_actual_date) ASC, MONTH(clearing_actual_date) ASC";
				
				$unit[$CAT3]    = "DATE_FORMAT(purchase_date,'%Y-%m') AS unit";
				$sorting[$CAT3] = "YEAR(purchase_date) ASC, MONTH(purchase_date) ASC";
				
				$unit[$CAT4]    = "DATE_FORMAT(clearing_actual_date,'%Y-%m') AS unit";
				$sorting[$CAT4] = "YEAR(clearing_actual_date) ASC, MONTH(clearing_actual_date) ASC";
				
				if ($startDate->format('Y-m') == $endDate->format('Y-m')) {
					echo '<p><span class="bad">Error:</span> The start and end dates fall in the same month. Please select a different set of dates.</p>';
					return;
				}
				
				break;
			
			
			case 'quarter':
				$interval = new DateInterval('P3M');
				
				$unit[$CAT1]    = "CONCAT(YEAR(order_date),' Q',QUARTER(order_date)) AS unit";
				$sorting[$CAT1] = "YEAR(order_date) ASC, QUARTER(order_date) ASC";
				
				$unit[$CAT2]    = "CONCAT(YEAR(clearing_actual_date),' Q',QUARTER(clearing_actual_date)) AS unit";
				$sorting[$CAT2] = "YEAR(clearing_actual_date) ASC, QUARTER(clearing_actual_date) ASC";
				
				$unit[$CAT3]    = "CONCAT(YEAR(purchase_date),' Q',QUARTER(purchase_date)) AS unit";
				$sorting[$CAT3] = "YEAR(purchase_date) ASC, QUARTER(purchase_date) ASC";
				
				$unit[$CAT4]    = "CONCAT(YEAR(clearing_actual_date),' Q',QUARTER(payment_date)) AS unit";
				$sorting[$CAT4] = "YEAR(clearing_actual_date) ASC, QUARTER(clearing_actual_date) ASC";
				
				switch ((int) $startDate->format('m')) {
					case  1:
					case  2:
					case  3: $startQuarter = 1; break;
					case  4:
					case  5:
					case  6: $startQuarter = 2; break;
					case  7:
					case  8:
					case  9: $startQuarter = 3; break;
					case 10:
					case 11:
					case 12: $startQuarter = 4; break;
				}
				
				switch ((int) $endDate->format('m')) {
					case  1:
					case  2:
					case  3: $endQuarter = 1; break;
					case  4:
					case  5:
					case  6: $endQuarter = 2; break;
					case  7:
					case  8:
					case  9: $endQuarter = 3; break;
					case 10:
					case 11:
					case 12: $endQuarter = 4; break;
				}
				
				if ($startDate->format('Y') == $endDate->format('Y') && $startQuarter == $endQuarter) {
					echo '<p><span class="bad">Error:</span> The start and end dates fall in the same quarter. Please select a different set of dates.</p>';
					return;
				}
				
				break;
			
			
			case 'year':
				$interval = new DateInterval('P1Y');
				
				$unit[$CAT1]    = "YEAR(order_date) AS unit";
				$sorting[$CAT1] = "YEAR(order_date) ASC";
				
				$unit[$CAT2]    = "YEAR(clearing_actual_date) AS unit";
				$sorting[$CAT2] = "YEAR(clearing_actual_date) ASC";
				
				$unit[$CAT3]    = "YEAR(purchase_date) AS unit";
				$sorting[$CAT3] = "YEAR(purchase_date) ASC";
				
				$unit[$CAT4]    = "YEAR(payment_date) AS unit";
				$sorting[$CAT4] = "YEAR(payment_date) ASC";
				
				if ($startDate->format("Y") == $endDate->format('Y')) {
					echo '<p><span class="bad">Error:</span> The start and end dates fall in the same year. Please select a different set of dates.</p>';
					return;
				}
				
				break;
		}
		
		
		switch ($category) {
			case 'orders-trend':
				echo '<div class="chart">' .
					 '<table id="order_chart" class="chart_table">' .
					 '<caption>Orders Trend</caption>' .
					 '<thead><tr><td></td>';
				
				// display table header
				$sqlQuery  = "SELECT " . $unit[$category] . ", COUNT(id) AS data FROM `order` " .
							 "WHERE (order_date BETWEEN '" . $startDate->format("Y-m-d") . " 00:00:00' " .
							 "AND '" . $endDate->format("Y-m-d") . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "GROUP BY unit ORDER BY " . $sorting[$category];
				$chartData = self::iterateChartData($sqlQuery, $viewType, $startDate, $endDate, $interval);
				
				echo '</tr></thead>' .
					 '<tbody><tr>' .
					 '<th scope="row">Number of orders created</th>';
				
				// display table data
				for ($i = 0, $chartSize = sizeof($chartData); $i < $chartSize; $i++) {
					echo "<td>{$chartData[$i]}</td>";
				}
				
				echo '</tr></tbody></table></div>';
				
				// display bullet info
				echo '<div class="report_data"><section>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM `order` " .
							 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label"><b>Number of Orders Created:</b></span>' .
					 '<span class="data"><b>' . numberFormat($report['count'], 'int') . '</b></span>' .
					 '</div>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM `order` " .
							 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "AND delivery_pickup_actual_date IS NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Orders to Deliver/Pick-up:</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM `order` " .
							 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "AND delivery_pickup_actual_date IS NOT NULL " .
							 "AND balance > 0 AND cleared_date IS NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Orders Delivered and Awaiting Payment:</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM `order` " .
							 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "AND delivery_pickup_actual_date IS NOT NULL " .
							 "AND balance <= 0 AND cleared_date IS NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Orders to Clear:</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM `order` " .
							 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND cleared_date IS NOT NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Cleared Orders:</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				echo '</section><section>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM `order` " .
							 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "AND balance < 0";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label"><b>Orders with Rebate:</b></span>' .
					 '<span class="data"><b>' . numberFormat($report['count'], 'int') . '</b></span>' .
					 '</div>';
				
				echo '</section>' .
					 '<section>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM `order` " .
							 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NOT NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label"><b>Canceled Orders:</b></span>' .
					 '<span class="data"><b>(' . numberFormat($report['count'], 'int') . ')</b></span>' .
					 '</div>';
				
				echo '</section></div>';
				
				break;
			
			
			case 'revenues-trend':
				echo '<div class="chart">' .
					 '<table id="revenue_chart" class="chart_table">' .
					 '<caption>Revenues Trend</caption>' .
					 '<thead><tr><td></td>';
				
				// display table header
				$sqlQuery  = "SELECT " . $unit[$category] . ", ROUND(SUM(amount)/1000,0) AS data FROM order_payment " .
							 "WHERE (clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "GROUP BY unit ORDER BY " . $sorting[$category];
				$chartData = self::iterateChartData($sqlQuery, $viewType, $startDate, $endDate, $interval);
				
				echo '</tr></thead>' .
					 '<tbody><tr>' .
					 '<th scope="row">Cleared payments for orders [x 1000]</th>';
				
				// display table data
				for ($i = 0, $chartSize = sizeof($chartData); $i < $chartSize; $i++) {
					if ($chartData[$i] < 0) {
						// do not display negative revenues
						echo '<td>0.000</td>';
					} else {
						echo "<td>{$chartData[$i]}</td>";
					}
				}
				
				echo '</tr></tbody></table></div>';
				
				// display bullet info
				echo '<div class="report_data"><section>';
				
				// cleared payments
				$sqlQuery        = "SELECT SUM(amount) AS amount FROM order_payment " .
								   "WHERE clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
								   "AND '" . $endDate->format('Y-m-d') . " 23:59:59'" .
								   "AND amount > 0";
				$resultSet       = self::$database->query($sqlQuery);
				$report          = self::$database->getResultRow($resultSet);
				$clearedPayments = $report['amount'];
				echo '<div>' .
					 '<span class="label"><b>Cleared Payments:</b></span>' .
					 '<span class="data_pad"><b>' . numberFormat($clearedPayments, 'float') . '</b></span>' .
					 '</div>';
				
				// cash payments
				$sqlQuery  = "SELECT SUM(amount) AS amount FROM order_payment " .
							 "WHERE (clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND payment_type = 'cash'" .
							 "AND amount > 0";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Payments Received in Cash:</span>' .
					 '<span class="data">' . numberFormat($report['amount'], 'float') . '</span>' .
					 '</div>';
				
				// check payments
				$sqlQuery  = "SELECT SUM(amount) AS amount FROM order_payment " .
							 "WHERE (clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND payment_type = 'check'" .
							 "AND amount > 0";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Payments Received in Check:</span>' .
					 '<span class="data">' . numberFormat($report['amount'], 'float') . '</span>' .
					 '</div>';
				
				echo '</section><section>';
				
				// amount receivable
				$sqlQuery         = "SELECT SUM(v_accounts_receivable.amount_receivable) AS accounts_receivable " .
									"FROM v_accounts_receivable " .
									"INNER JOIN `order` ON v_accounts_receivable.order_id = `order`.id " .
									"WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
									"AND '" . $endDate->format('Y-m-d') . " 23:59:59') ";
				$resultSet        = self::$database->query($sqlQuery);
				$report           = self::$database->getResultRow($resultSet);
				$amountReceivable = $report['accounts_receivable'];
				
				// pdc receivable
				$sqlQuery      = "SELECT SUM(v_accounts_pdc_receivable.pdc_receivable) AS pdc_receivable " .
								 "FROM v_accounts_pdc_receivable " .
								 "INNER JOIN `order` ON v_accounts_pdc_receivable.order_id = `order`.id " .
								 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
								 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') ";
				$resultSet     = self::$database->query($sqlQuery);
				$report        = self::$database->getResultRow($resultSet);
				$pdcReceivable = $report['pdc_receivable'];
				
				echo '<div>' .
					 '<span class="label"><b>Total Receivable:</b></span>' .
					 '<span class="data_pad"><b>' . numberFormat($amountReceivable + $pdcReceivable, 'float') . '</b></span>' .
					 '</div><div>' .
					 '<span class="label">Total Amount Receivable:</span>' .
					 '<span class="data">' . numberFormat($amountReceivable, 'float') . '</span>' .
					 '</div><div>' .
					 '<span class="label">Total PDC Receivable:</span>' .
					 '<span class="data">' . numberFormat($pdcReceivable, 'float') . '</span>' .
					 '</div>';
				
				// total revenues
				echo '</section><section><div>' .
					 '<span class="label"><b>Total Revenues:</b></span>' .
					 '<span class="data_pad"><b><u>' .
					 numberFormat(($clearedPayments + $amountReceivable + $pdcReceivable), 'currency') .
					 '</u></b></span>' .
					 '</div></section>' .
					 '<br /><br />' .
					 '<section>';
				
				// cleared rebates
				$sqlQuery       = "SELECT SUM(amount)*-1 AS amount FROM order_payment " .
								  "WHERE (clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
								  "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
								  "AND amount < 0";
				$resultSet      = self::$database->query($sqlQuery);
				$report         = self::$database->getResultRow($resultSet);
				$clearedRebates = $report['amount'];
				echo '<div>' .
					 '<span class="label"><b>Cleared Rebates:</b></span>' .
					 '<span class="data_pad"><b>(' . numberFormat($report['amount'], 'float') . ')</b></span>' .
					 '</div>';
				
				// rebate payable
				$sqlQuery      = "SELECT SUM(v_rebate_payable.rebate_payable) AS rebate_payable " .
								 "FROM v_rebate_payable " .
								 "INNER JOIN `order` ON v_rebate_payable.order_id = `order`.id " .
								 "WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
								 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') ";
				$resultSet     = self::$database->query($sqlQuery);
				$report        = self::$database->getResultRow($resultSet);
				$rebatePayable = $report['rebate_payable'];
				
				// pdc rebate
				$sqlQuery         = "SELECT SUM(v_rebate_pdc_payable.pdc_rebate_payable) AS pdc_rebate_payable " .
									"FROM v_rebate_pdc_payable " .
									"INNER JOIN `order` ON v_rebate_pdc_payable.order_id = `order`.id " .
									"WHERE (order_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
									"AND '" . $endDate->format('Y-m-d') . " 23:59:59') ";
				$resultSet        = self::$database->query($sqlQuery);
				$report           = self::$database->getResultRow($resultSet);
				$pdcRebatePayable = $report['pdc_rebate_payable'];
				
				echo '<div>' .
					 '<span class="label"><b>Pending Rebate:</b></span>' .
					 '<span class="data_pad"><b>(' . numberFormat($rebatePayable + $pdcRebatePayable, 'float') . ')</b></span>' .
					 '</div><div>' .
					 '<span class="label">Total Rebate Payable:</span>' .
					 '<span class="data">(' . numberFormat($rebatePayable, 'float') . ')</span>' .
					 '</div><div>' .
					 '<span class="label">Total PDC Rebate:</span>' .
					 '<span class="data">(' . numberFormat($pdcRebatePayable, 'float') . ')</span>' .
					 '</div>';
				
				// total rebates
				echo '</section><section><div>' .
					 '<span class="label"><b>Total Rebates:</b></span>' .
					 '<span class="data_pad"><b><u>(' .
					 numberFormat(($clearedRebates + $rebatePayable + $pdcRebatePayable), 'currency') .
					 ')</u></b></span>' .
					 '</div></section>' .
					 '<br /><br /><br />' .
					 '<section>';
				
				// gross revenue
				echo '<div>' .
					 '<span class="label"><b>Gross Revenues:</b></span>' .
					 '<span class="data_pad double_underline"><b>' .
					 numberFormat((($clearedPayments + $amountReceivable + $pdcReceivable) - ($clearedRebates + $rebatePayable + $pdcRebatePayable)),
						 'currency') .
					 '</b></span>' .
					 '</div>';
				
				echo '</section></div>';
				
				break;
			
			
			case 'purchases-trend':
				echo '<div class="chart">' .
					 '<table id="purchase_chart" class="chart_table">' .
					 '<caption>Purchases Trend</caption>' .
					 '<thead><tr><td></td>';
				
				// display table header
				$sqlQuery  = "SELECT " . $unit[$category] . ", COUNT(id) AS data FROM purchase " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "GROUP BY unit ORDER BY " . $sorting[$category];
				$chartData = self::iterateChartData($sqlQuery, $viewType, $startDate, $endDate, $interval);
				
				echo '</tr></thead>' .
					 '<tbody><tr>' .
					 '<th scope="row">Number of purchases created</th>';
				
				// display table data
				for ($i = 0, $chartSize = sizeof($chartData); $i < $chartSize; $i++) {
					echo "<td>{$chartData[$i]}</td>";
				}
				
				echo '</tr></tbody></table></div>';
				
				// display bullet info
				echo '<div class="report_data"><section>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label"><b>Number of Purchases Created:</b></span>' .
					 '<span class="data"><b>' . numberFormat($report['count'], 'int') . '</b></span>' .
					 '</div>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "AND delivery_pickup_actual_date IS NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Purchases to Deliver/Pick-up:</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "AND delivery_pickup_actual_date IS NOT NULL " .
							 "AND balance > 0 AND cleared_date IS NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Delivered Purchases to Pay:</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "AND delivery_pickup_actual_date IS NOT NULL " .
							 "AND balance <= 0 AND cleared_date IS NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Purchases to Clear:</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND cleared_date IS NOT NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Cleared Purchases:</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				echo '</section>' .
					 '<section>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NULL " .
							 "AND balance < 0";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Purchases with Rebate</span>' .
					 '<span class="data">' . numberFormat($report['count'], 'int') . '</span>' .
					 '</div>';
				
				echo '</section>' .
					 '<section>';
				
				$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase " .
							 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND canceled_date IS NOT NULL";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label"><b>Canceled Purchases:</b></span>' .
					 '<span class="data"><b>(' . numberFormat($report['count'], 'int') . ')</b></span>' .
					 '</div>';
				
				echo '</section></div>';
				
				break;
			
			
			case 'expenses-trend':
				echo '<div class="chart">' .
					 '<table id="expense_chart" class="chart_table">' .
					 '<caption>Capital Expenditures Trend</caption>' .
					 '<thead><tr><td></td>';
				
				// display table header
				$sqlQuery  = "SELECT " . $unit[$category] . ", ROUND(SUM(amount)/1000,0) AS data FROM purchase_payment " .
							 "WHERE (clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "GROUP BY unit ORDER BY " . $sorting[$category];
				$chartData = self::iterateChartData($sqlQuery, $viewType, $startDate, $endDate, $interval);
				
				echo '</tr></thead>' .
					 '<tbody><tr>' .
					 '<th scope="row">Cleared payments for purchases [x 1000]</th>';
				
				// display table data
				for ($i = 0, $chartSize = sizeof($chartData); $i < $chartSize; $i++) {
					if ($chartData[$i] < 0) {
						// do not display negative revenues
						echo '<td>0.000</td>';
					} else {
						echo "<td>{$chartData[$i]}</td>";
					}
				}
				
				echo '</tr></tbody></table></div>';
				
				// display bullet info
				echo '<div class="report_data"><section>';
				
				// cleared payments
				$sqlQuery        = "SELECT SUM(amount) AS amount FROM purchase_payment " .
								   "WHERE clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
								   "AND '" . $endDate->format('Y-m-d') . " 23:59:59'";
				$resultSet       = self::$database->query($sqlQuery);
				$report          = self::$database->getResultRow($resultSet);
				$clearedPayments = $report['amount'];
				echo '<div>' .
					 '<span class="label"><b>Cleared Payments:</b></span>' .
					 '<span class="data_pad"><b>' . numberFormat($report['amount'], 'float') . '</b></span>' .
					 '</div>';
				
				// cash payments
				$sqlQuery  = "SELECT SUM(amount) AS amount FROM purchase_payment " .
							 "WHERE (clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND payment_type = 'cash'" .
							 "AND amount > 0";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Amount Paid in Cash:</span>' .
					 '<span class="data">' . numberFormat($report['amount'], 'float') . '</span>' .
					 '</div>';
				
				// check payments
				$sqlQuery  = "SELECT SUM(amount) AS amount FROM purchase_payment " .
							 "WHERE (clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND payment_type = 'Check'" .
							 "AND amount > 0";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Amount Paid in Check:</span>' .
					 '<span class="data">' . numberFormat($report['amount'], 'float') . '</span>' .
					 '</div>';
				
				// cleared rebates
				$sqlQuery  = "SELECT SUM(amount)*-1 AS amount FROM purchase_payment " .
							 "WHERE (clearing_actual_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') " .
							 "AND amount < 0";
				$resultSet = self::$database->query($sqlQuery);
				$report    = self::$database->getResultRow($resultSet);
				echo '<div>' .
					 '<span class="label">Cleared Rebates:</span>' .
					 '<span class="data">(' . numberFormat($report['amount'], 'float') . ')</span>' .
					 '</div>';
				
				echo '</section>' .
					 '<section>';
				
				// amount payable
				$sqlQuery      = "SELECT SUM(v_accounts_payable.amount_payable) AS accounts_payable " .
								 "FROM v_accounts_payable " .
								 "INNER JOIN purchase ON v_accounts_payable.purchase_id = purchase.id " .
								 "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
								 "AND '" . $endDate->format('Y-m-d') . " 23:59:59') ";
				$resultSet     = self::$database->query($sqlQuery);
				$report        = self::$database->getResultRow($resultSet);
				$amountPayable = $report['accounts_payable'];
				
				// pdc payable
				$sqlQuery   = "SELECT SUM(v_accounts_pdc_payable.pdc_payable) AS pdc_payable " .
							  "FROM v_accounts_pdc_payable " .
							  "INNER JOIN purchase ON v_accounts_pdc_payable.purchase_id = purchase.id " .
							  "WHERE (purchase_date BETWEEN '" . $startDate->format('Y-m-d') . " 00:00:00' " .
							  "AND '" . $endDate->format('Y-m-d') . " 23:59:59') ";
				$resultSet  = self::$database->query($sqlQuery);
				$report     = self::$database->getResultRow($resultSet);
				$pdcPayable = $report['pdc_payable'];
				
				echo '<div>' .
					 '<span class="label"><b>Total Payable:</b></span>' .
					 '<span class="data_pad"><b>' . numberFormat($amountPayable + $pdcPayable, 'float') . '</b></span>' .
					 '</div><div>' .
					 '<span class="label">Total Amount Payable:</span>' .
					 '<span class="data">' . numberFormat($amountPayable, 'float') . '</span>' .
					 '</div><div>' .
					 '<span class="label">Total PDC Payable:</span>' .
					 '<span class="data">' . numberFormat($pdcPayable, 'float') . '</span>' .
					 '</div>';
				
				// gross revenue
				echo '</section><br /><br /><section><div>' .
					 '<span class="label"><b>Total Expenses:</b></span>' .
					 '<span class="data_pad double_underline"><b>' .
					 numberFormat(($clearedPayments + $amountPayable + $pdcPayable), 'currency') .
					 '</b></span></div></section></div>';
				
				break;
			
			
			default:
				return;
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// populate chart data array
	//------------------------------------------------------------------------------------------------------------
	private static function iterateChartData( $sqlQuery, $viewType, DateTime $startDate, DateTime $endDate, DateInterval $interval ) {
		// modify date counter start
		$dateCtr = new DateTime($startDate->format('Y-m-d'));
		
		switch ($viewType) {
			case 'week':
				// set start date to Monday
				if ($dateCtr->format('w') > 1) {
					$dateCtr->sub(new DateInterval('P' . ($dateCtr->format('w') - 1) . 'D'));
				} elseif ($dateCtr->format('w') < 1) {
					$dateCtr->sub(new DateInterval('P6D'));
				}
				break;
			
			
			case 'month':
				// set start date to 1st day of the month
				if ($dateCtr->format('d') > 1) {
					$dateCtr = new DateTime($startDate->format('Y-m-1'));
				}
				break;
			
			
			case 'quarter':
				switch ((int) $startDate->format('m')) {
					// set start date to 1st day of the quarter
					case  1:
					case  2:
					case  3: $quarterCtr = 1;
							 $dateCtr    = new DateTime($startDate->format('Y-01-01'));
							 break;
					case  4:
					case  5:
					case  6: $quarterCtr = 2;
							 $dateCtr    = new DateTime($startDate->format('Y-04-01'));
							 break;
					case  7:
					case  8:
					case  9: $quarterCtr = 3;
							 $dateCtr    = new DateTime($startDate->format('Y-07-01'));
							 break;
					case 10:
					case 11:
					case 12: $quarterCtr = 4;
							 $dateCtr    = new DateTime($startDate->format('Y-10-01'));
							 break;
				}
				break;
			
			
			case 'year':
				// set start date to January 1
				$dateCtr = new DateTime($startDate->format('Y-1-1'));
				break;
		}
		
		// flags
		$getNextRow = true;
		
		// placeholder for chart data
		$chartData = array();
		
		// execute query
		$resultSet = self::$database->query($sqlQuery);
		
		// construct chart table
		while ($dateCtr <= $endDate) {
			// fetch data
			if ($getNextRow) {
				$report     = self::$database->getResultRow($resultSet);
				$getNextRow = false;
			}
			
			// display chart x-axis labels
			switch ($viewType) {
				case 'day':
					echo '<th scope="col">' . $dateCtr->format('M j') . '</th>';
					if ($dateCtr->format('Y-m-d') == $report['unit']) {
						array_push($chartData, $report['data']);
						$getNextRow = true;
					} else {
						array_push($chartData, 0);
					}
					break;
				
				case 'week':
					echo '<th scope="col">Wk ' . $dateCtr->format('W') . '</th>';
					if ($dateCtr->format('W') == $report['unit']) {
						array_push($chartData, $report['data']);
						$getNextRow = true;
					} else {
						array_push($chartData, 0);
					}
					break;
				
				case 'month':
					echo '<th scope="col">' . $dateCtr->format('Y M') . '</th>';
					if ($dateCtr->format('Y-m') == $report['unit']) {
						array_push($chartData, $report['data']);
						$getNextRow = true;
					} else {
						array_push($chartData, 0);
					}
					break;
				
				case 'quarter':
					echo '<th scope="col">' . $dateCtr->format('Y') . ' Q' . $quarterCtr . '</th>';
					if (($dateCtr->format('Y') . ' Q' . $quarterCtr) == $report['unit']) {
						array_push($chartData, $report['data']);
						$getNextRow = true;
					} else {
						array_push($chartData, 0);
					}
					$quarterCtr++;
					if ($quarterCtr > 4) {
						$quarterCtr = 1;
					}
					break;
				
				case 'year':
					echo '<th scope="col">' . $dateCtr->format('Y') . '</th>';
					if ($dateCtr->format('Y') == $report['unit']) {
						array_push($chartData, $report['data']);
						$getNextRow = true;
					} else {
						array_push($chartData, 0);
					}
					break;
			}
			
			$dateCtr->add($interval);
		}
		
		return $chartData;
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display tasks for profit calculator page
	//------------------------------------------------------------------------------------------------------------
	public static function showListTasks( $reportType ) {
		?>
		<script type="text/javascript">
		<!--
			function getDailySalesData() {
				exportToExcelConfirm('data=daily_sales&report_date=' + $('#date').val() );
			}
			
			function getProfitCalcData() {
				exportToExcelConfirm('data=profit_calc&'  +
									 'bank_name_1='       + $('#bank_name_1').val()       + '&' +
									 'bank_name_2='       + $('#bank_name_2').val()       + '&' +
									 'bank_name_3='       + $('#bank_name_3').val()       + '&' +
									 'bank_name_4='       + $('#bank_name_4').val()       + '&' +
									 'bank_name_5='       + $('#bank_name_5').val()       + '&' +
									 'bank_amount_1='     + $('#bank_amount_1').val()     + '&' +
									 'bank_amount_2='     + $('#bank_amount_2').val()     + '&' +
									 'bank_amount_3='     + $('#bank_amount_3').val()     + '&' +
									 'bank_amount_4='     + $('#bank_amount_4').val()     + '&' +
									 'bank_amount_5='     + $('#bank_amount_5').val()     + '&' +
									 'amount_receivable=' + $('#amount_receivable').val() + '&' +
									 'pdc_receivable='    + $('#pdc_receivable').val()    + '&' +
									 'inventory_amount='  + $('#inventory_amount').val()  + '&' +
									 'amount_payable='    + $('#amount_payable').val()    + '&' +
									 'pdc_payable='       + $('#pdc_payable').val()       + '&' +
									 'rebate_payable='    + $('#rebate_payable').val()    + '&' +
									 'pdc_rebate='        + $('#pdc_rebate').val()        + '&' +
									 'other_expenses='    + $('#other_expenses').val()    + '&' +
									 'capital='           + $('#capital').val()           );
				}
		//-->
		</script>

		<div id="tasks">
		<ul>
			<li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php
			$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br /><br /><br /><br />' .
							 '<div id="dialog_buttons">' .
						 	 '<input type="button" value="Yes" onclick="';
			
			switch( $reportType ) {
				case 'daily-sales': $dialogMessage = $dialogMessage . 'getDailySalesData()'; break;
				case 'profit-calc': $dialogMessage = $dialogMessage . 'getProfitCalcData()'; break;
			}
			
			$dialogMessage = $dialogMessage . '" />' .
						 	 '<input type="button" value="No" onclick="hideDialog()" />' .
						 	 '</div>';
			$dialogMessage = htmlentities($dialogMessage);
			echo $dialogMessage;
			?>','prompt')"><img src="images/task_buttons/export_to_excel.png" />Export to Excel...</a></li>
			</ul>
		</div>
		</div><?php		// extra closing div
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display form for profit calculator
	//------------------------------------------------------------------------------------------------------------
	private static function showProfitCalculatorForm() {
		self::$database = new Database();
		
		$currentDate = new DateTime();
		$currentDate = $currentDate->format('F j, Y');;
		
		echo '<div id="date_header">As of Today, <span class="date">' . $currentDate . '</span></div>';
		
		$sqlQuery         = "SELECT SUM(amount_receivable) AS amount_receivable FROM v_accounts_receivable";
		$resultSet        = self::$database->query($sqlQuery);
		$report           = self::$database->getResultRow($resultSet);
		$amountReceivable = numberFormat($report['amount_receivable'], 'float', 3, '.', '', true);
		
		$sqlQuery      = "SELECT SUM(pdc_receivable) AS pdc_receivable FROM v_accounts_pdc_receivable";
		$resultSet     = self::$database->query($sqlQuery);
		$report        = self::$database->getResultRow($resultSet);
		$pdcReceivable = numberFormat($report['pdc_receivable'], 'float', 3, '.', '', true);
		
		$sqlQuery        = "SELECT SUM(stock_count*purchase_price) AS inventory_amount FROM inventory";
		$resultSet       = self::$database->query($sqlQuery);
		$report          = self::$database->getResultRow($resultSet);
		$inventoryAmount = numberFormat($report['inventory_amount'], 'float', 3, '.', '', true);
		
		$sqlQuery      = "SELECT SUM(rebate_payable) AS rebate_payable FROM v_rebate_payable";
		$resultSet     = self::$database->query($sqlQuery);
		$report        = self::$database->getResultRow($resultSet);
		$rebatePayable = numberFormat($report['rebate_payable'], 'float', 3, '.', '', true);
		
		$sqlQuery  = "SELECT SUM(pdc_rebate_payable) AS pdc_rebate_payable FROM v_rebate_pdc_payable";
		$resultSet = self::$database->query($sqlQuery);
		$report    = self::$database->getResultRow($resultSet);
		$pdcRebate = numberFormat($report['pdc_rebate_payable'], 'float', 3, '.', '', true);
		
		$sqlQuery      = "SELECT SUM(amount_payable) AS amount_payable FROM v_accounts_payable";
		$resultSet     = self::$database->query($sqlQuery);
		$report        = self::$database->getResultRow($resultSet);
		$amountPayable = numberFormat($report['amount_payable'], 'float', 3, '.', '', true);
		
		$sqlQuery   = "SELECT SUM(pdc_payable) AS pdc_payable FROM v_accounts_pdc_payable";
		$resultSet  = self::$database->query($sqlQuery);
		$report     = self::$database->getResultRow($resultSet);
		$pdcPayable = numberFormat($report['pdc_payable'], 'float', 3, '.', '', true);
		
		echo '<section><div class="report_data">' .
			 '<table class="item_input_table"><thead><tr>' .
			 '<th id="profit_bank_counter"></th>' .
			 '<th id="profit_bank_name">Bank:</th>' .
			 '<th id="profit_bank_amount">Amount:</th>' .
			 '</tr></thead><tbody>';
		
		for ($i = 1; $i <= 5; $i++) {
			echo '<tr>' .
				 "<td>$i</td>" .
				 '<td><input type="text" name="bank_name_' . $i . '" id="bank_name_' . $i . '" /></td>' .
				 '<td><input type="text" name="bank_amount_' . $i . '" id="bank_amount_' . $i . '" value="0.000" class="number bank_amount" /></td>' .
				 '</tr>';
		}
		
		$currency = CURRENCY;
		
		echo '<tr><td colspan="3"></td></tr><tr>' .
			 '<td colspan="2"><label for="bank_amount_total">Bank Total: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="bank_amount_total" id="bank_amount_total" class="number output_field" value="0.000" disabled="disabled" /></td>' .
			 '</tr><tr>' .
			 '<td>+</td>' .
			 '<td><label for="amount_receivable"><a href="list_orders.php?criteria=all-orders" 
			 										target="_blank">Amount Receivable</a>: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="amount_receivable" id="amount_receivable" class="number output_field" 
			 			 value="' . $amountReceivable . '" disabled="disabled" /></td>' .
			 '</tr><tr>' .
			 '<td>+</td>' .
			 '<td><label for="pdc_receivable"><a href="list_orders.php?criteria=all-orders" 
			 									 target="_blank">PDC Receivable</a>: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="pdc_receivable" id="pdc_receivable" class="number output_field" 
			 			 value="' . $pdcReceivable . '" disabled="disabled" /></td>' .
			 '</tr><tr>' .
			 '<td>+</td>' .
			 '<td><label for="stock_amount"><a href="report.php?type=inventory&category=remaining" 
			 								   target="_blank">Inventory Amount</a>: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="inventory_amount" id="inventory_amount" class="number output_field" 
			 			 value="' . $inventoryAmount . '" disabled="disabled" /></td>' .
			 '</tr><tr><td colspan="3"></td></tr><tr>' .
			 '<td>-</td>' .
			 '<td><label for="amount_payable"><a href="list_purchases.php?criteria=all-purchases" 
			 									 target="_blank">Amount Payable</a>: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="amount_payable" id="amount_payable" class="number output_field" 
			 			 value="' . $amountPayable . '" disabled="disabled" /></td>' .
			 '</tr><tr>' .
			 '<td>-</td>' .
			 '<td><label for="pdc_payable"><a href="list_purchases.php?criteria=all-purchases" 
			 								  target="_blank">PDC Payable</a>: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="pdc_payable" id="pdc_payable" class="number output_field" 
			 			 value="' . $pdcPayable . '" disabled="disabled" /></td>' .
			 '</tr><tr>' .
			 '<td>-</td>' .
			 '<td><label for="rebate_payable"><a href="list_orders.php?criteria=all-orders" 
			 									 target="_blank">Rebate Payable</a>: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="rebate_payable" id="rebate_payable" class="number output_field" 
			 			 value="' . $rebatePayable . '" disabled="disabled" /></td>' .
			 '</tr><tr>' .
			 '<td>-</td>' .
			 '<td><label for="pdc_rebate"><a href="list_orders.php?criteria=all-orders" 
			 								 target="_blank">PDC Rebate Payable</a>: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="pdc_rebate" id="pdc_rebate" class="number output_field" 
			 			 value="' . $pdcRebate . '" disabled="disabled" /></td>' .
			 '</tr><tr>' .
			 '<td>-</td>' .
			 '<td><label for="other_expenses">Other Expenses: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="other_expenses" id="other_expenses" class="number" value="0.000" /></td>' .
			 '</tr><tr>' .
			 '<td>-</td>' .
			 '<td><label for="capital">Capital: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="capital" id="capital" class="number" value="0.000" /></td>' .
			 '</tr><tr><td colspan="3"></td></tr><tr>' .
			 '<td colspan="2"><label for="profit" class="important_label">Profit: ' . $currency . '</label></td>' .
			 '<td><input type="text" name="profit" id="profit" class="number output_field" value="0.000" disabled="disabled" /></td>' .
			 '</tr></tbody></table></div></section>';
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// save profit report to excel file
	//------------------------------------------------------------------------------------------------------------
	public static function exportProfitReportToExcel( $username, $paramArray ) {
		$fileTimeStampExtension = date(EXCEL_FILE_TIMESTAMP_FORMAT);
		$headingTimeStamp       = dateFormatOutput($fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT);
		
		require_once('classes/Filter.php');
		
		self::$database = new Database();
		
		$sheetTitle = 'Profit Report';
		
		// import PHPExcel library
		require_once('libraries/phpexcel/PHPExcel.php');
		
		// instantiate formatting variables
		$backgroundColor = new PHPExcel_Style_Color();
		$fontColor       = new PHPExcel_Style_Color();
		
		// color-specific variables
		$fontColorRed = new PHPExcel_Style_Color();
		$fontColorRed->setRGB('FF0000');
		$fontColorDarkRed = new PHPExcel_Style_Color();
		$fontColorDarkRed->setRGB('CC0000');
		$fontColorGreen = new PHPExcel_Style_Color();
		$fontColorGreen->setRGB('00CC00');
		$fontColorGray = new PHPExcel_Style_Color();
		$fontColorGray->setRGB('999999');
		
		// set value binder
		PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());
		
		// create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		
		// set file properties
		$objPHPExcel->getProperties()
					->setCreator($username)
					->setLastModifiedBy($username)
					->setTitle("$sheetTitle as of $headingTimeStamp")
					->setSubject(EXCEL_FILE_SUBJECT)
					->setDescription(EXCEL_FILE_DESCRIPTION)
					->setKeywords(EXCEL_FILE_KEYWORDS)
					->setCategory(EXCEL_FILE_CATEGORY);
		
		// create a first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		$activeSheet = $objPHPExcel->getActiveSheet();
		
		// rename present sheet
		$activeSheet->setTitle($sheetTitle);
		
		// set default font
		$activeSheet->getDefaultStyle()->getFont()->setName(EXCEL_DEFAULT_FONT_NAME)
					->setSize(EXCEL_DEFAULT_FONT_SIZE);
		
		// write sheet headers
		$activeSheet->setCellValue('A1', CLIENT);
		$activeSheet->setCellValue('A2', $sheetTitle);
		$activeSheet->setCellValue('A3', "As of $headingTimeStamp");
		
		// format sheet headers
		$backgroundColor->setRGB(EXCEL_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A1:C4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A1:C4')->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A1:C2')->getFont()->setBold(true);
		$activeSheet->getStyle('A1:C3')->getFont()->setName(EXCEL_HEADER_FONT_NAME);
		$activeSheet->getStyle('A1')->getFont()->setColor($fontColorRed);
		$activeSheet->getStyle('A1')->getFont()->setSize(EXCEL_HEADER1_FONT_SIZE);
		$activeSheet->getStyle('A2')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		$activeSheet->getStyle('A3')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		
		// write column headers
		$activeSheet->setCellValue('B5', 'Amounts in (' . CURRENCY . ')');
		
		// set column widths
		$activeSheet->getColumnDimension('A')->setWidth(30);
		$activeSheet->getColumnDimension('B')->setWidth(20);
		$activeSheet->getColumnDimension('C')->setWidth(20);
		
		// format column headers
		$fontColor->setRGB(EXCEL_COLUMN_HEADER_FONT_COLOR);
		$backgroundColor->setRGB(EXCEL_COLUMN_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A5:C5')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A5:C5')->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A5:C5')->getFont()->setColor($fontColor);
		$activeSheet->getStyle('A5:C5')->getFont()->setBold(true);
		$activeSheet->getStyle('A5:C5')->getAlignment()->setWrapText(true);
		
		// freeze pane
		$activeSheet->freezePane('A6');
		
		// write data
		$activeSheet->setCellValue('A6', 'Banks');
		
		$activeSheet->setCellValue('A7', $paramArray['bank_name_1']);
		$activeSheet->setCellValue('A8', $paramArray['bank_name_2']);
		$activeSheet->setCellValue('A9', $paramArray['bank_name_3']);
		$activeSheet->setCellValue('A10', $paramArray['bank_name_4']);
		$activeSheet->setCellValue('A11', $paramArray['bank_name_5']);
		
		$activeSheet->setCellValue('B7', $paramArray['bank_amount_1']);
		$activeSheet->setCellValue('B8', $paramArray['bank_amount_2']);
		$activeSheet->setCellValue('B9', $paramArray['bank_amount_3']);
		$activeSheet->setCellValue('B10', $paramArray['bank_amount_4']);
		$activeSheet->setCellValue('B11', $paramArray['bank_amount_5']);
		
		$activeSheet->setCellValue('A13', 'Bank Total');
		$activeSheet->setCellValue('B13', '=SUM(B7:B11)');
		
		$activeSheet->setCellValue('A14', 'Amount Receivable');
		$activeSheet->setCellValue('B14', str_replace(",", "", $paramArray['amount_receivable']));
		
		$activeSheet->setCellValue('A15', 'PDC Receivable');
		$activeSheet->setCellValue('B15', str_replace(",", "", $paramArray['pdc_receivable']));
		
		$activeSheet->setCellValue('A16', 'Inventory Amount');
		$activeSheet->setCellValue('B16', str_replace(",", "", $paramArray['inventory_amount']));
		
		$activeSheet->setCellValue('C17', '=SUM(B13:B16)');
		
		$activeSheet->setCellValue('A19', 'Amount Payable');
		$activeSheet->setCellValue('B19', str_replace(",", "", $paramArray['amount_payable']));
		
		$activeSheet->setCellValue('A20', 'PDC Payable');
		$activeSheet->setCellValue('B20', str_replace(",", "", $paramArray['pdc_payable']));
		
		$activeSheet->setCellValue('A21', 'Rebate Payable');
		$activeSheet->setCellValue('B21', str_replace(",", "", $paramArray['rebate_payable']));
		
		$activeSheet->setCellValue('A22', 'PDC Rebate Payable');
		$activeSheet->setCellValue('B22', str_replace(",", "", $paramArray['pdc_rebate']));
		
		$activeSheet->setCellValue('A23', 'Other Expenses');
		$activeSheet->setCellValue('B23', str_replace(",", "", $paramArray['other_expenses']));
		
		$activeSheet->setCellValue('A24', 'Capital');
		$activeSheet->setCellValue('B24', str_replace(",", "", $paramArray['capital']));
		
		$activeSheet->setCellValue('C25', '=SUM(B19:B24)');
		
		$activeSheet->setCellValue('A27', 'Profit');
		$activeSheet->setCellValue('C27', '=C17-C25');
		
		// post formatting
		$activeSheet->getStyle('A6:A27')->getAlignment()->setWrapText(true);
		$activeSheet->getStyle('A6')->getFont()->setBold(true);
		$activeSheet->getStyle('A13:A27')->getFont()->setBold(true);
		$activeSheet->getStyle('C6:C27')->getFont()->setBold(true);
		$activeSheet->getStyle('B6:C26')->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		
		// conditional formatting
		// * set green for net profit, red for net loss
		$conditionalStyles = $activeSheet->getStyle('C27')->getConditionalStyles();
		
		$objConditional1 = new PHPExcel_Style_Conditional();
		$objConditional1->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
		$objConditional1->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_GREATERTHAN);
		$objConditional1->addCondition('0');
		$objConditional1->getStyle()->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$objConditional1->getStyle()->getFont()->setColor($fontColorGreen);
		
		array_push($conditionalStyles, $objConditional1);
		
		$objConditional2 = new PHPExcel_Style_Conditional();
		$objConditional2->setConditionType(PHPExcel_Style_Conditional::CONDITION_CELLIS);
		$objConditional2->setOperatorType(PHPExcel_Style_Conditional::OPERATOR_LESSTHANOREQUAL);
		$objConditional2->addCondition('0');
		$objConditional2->getStyle()->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$objConditional2->getStyle()->getFont()->setColor($fontColorRed);
		
		array_push($conditionalStyles, $objConditional2);
		
		$activeSheet->getStyle('C27')->setConditionalStyles($conditionalStyles);
		
		// format totals
		$styleArray = array(
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THICK)
			)
		);
		$activeSheet->getStyle('A27:C27')->applyFromArray($styleArray);
		$activeSheet->getStyle('A27')->getFont()->setColor($fontColorRed);
		
		// set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		
		// redirect output to a client�s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . CLIENT . ' - Profit Report - as of ' . $fileTimeStampExtension . '.xlsx"');
		header('Cache-Control: max-age=0');
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
	}
}

?>

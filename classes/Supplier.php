<?php
// note: this class requires scripts/supplier.js


//----------------------------------------------------------------------------------------------------------------
// class definition for supplier
//----------------------------------------------------------------------------------------------------------------
class Supplier extends Person
{
	const NAME_LABEL          = "Supplier Name";
	const SHOW_CONTACT_PERSON = true;
	const ADDRESS_LABEL       = "Office Address";
	
	
	//------------------------------------------------------------------------------------------------------------
	// display supplier form
	//------------------------------------------------------------------------------------------------------------
	public static function showInputForm( $id = null ) {
		echo '<form name="' . ($id == null ? "add" : "edit") .
			 '_supplier" method="post" action="view_supplier_details.php" autocomplete="off" onreset="return confirmReset(\'resetSupplierForm\')">';
		self::showInputFieldset($id);
		self::showButtons(ButtonSet::SUBMIT_RESET_CANCEL);
		echo '</form>';
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display supplier form fied set
	//------------------------------------------------------------------------------------------------------------
	public static function showInputFieldset( $id = null ) {
		echo '<fieldset><legend>Supplier Info</legend>';
		
		if ($id != null) {
			$id = Filter::reinput($id);
			
			if (self::$database == null) {
				self::$database = new Database();
			}
			$resultSet    = self::$database->query("SELECT * FROM supplier WHERE id = $id");
			$supplierInfo = self::$database->getResultRow($resultSet);
		} else {
			$supplierInfo = null;
		}
		
		// display basic fields
		self::showBasicInputFields($supplierInfo, self::NAME_LABEL, self::SHOW_CONTACT_PERSON, self::ADDRESS_LABEL);
		echo '</fieldset>';
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// auto-suggest supplier name; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function showAutoSuggest() {
		// check required parameters
		if (isset($_POST['searchString'])) {
			self::showAutoSuggestResult('supplier', 'name', $_POST['searchString']);
		}
		return;
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// auto-fill supplier form based on auto-suggest selection; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function autoFill() {
		// check required parameters
		if (!isset($_POST['supplierName'])) {
			return;
		}
		
		$supplier = self::getAutoFillData('supplier', 'name', $_POST['supplierName']);
		
		if ($supplier != null) {
			$supplier['contact_person'] = html_entity_decode(capitalizeWords(Filter::reinput($supplier['contact_person'])));
			$supplier['address']        = html_entity_decode(capitalizeWords(Filter::reinput($supplier['address'])));
			$supplier['email']          = strtolower($supplier['email']);
		} else {
			$supplier['id'] = 0;
		}
		
		echo json_encode($supplier);
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// save supplier info to database
	//------------------------------------------------------------------------------------------------------------
	public function save() {
		if ($_POST['supplier_query_mode'] == 'new') {
			// new supplier
			
			// process basic fields
			$this->prepareBasicInputData();
			
			// save new supplier to database
			$sqlQuery = "INSERT INTO supplier VALUES (" .
						"NULL," .                               // id, auto-generate
						"$this->name," .                        // name
						"$this->contactPerson," .               // contact_person
						"$this->address," .                     // address
						"$this->telephone," .                   // telephone
						"$this->mobile," .                      // mobile
						"$this->fax," .                         // fax
						"$this->email)";                        // email
			self::$database->query($sqlQuery);
			
			// get generated supplier ID
			$this->id = self::$database->getLastInsertID();
			
			// log event
			EventLog::addEntry(self::$database, 'info', 'supplier', 'insert', 'new',
							   '<span class="event_log_main_record_inline">' .
							   '<a href="view_supplier_details.php?id=' . $this->id . '">' . capitalizeWords(htmlentities($_POST['supplier_name'])) . '</a>' .
							   '</span> was <span class="event_log_action">added</span> to <a href="list_suppliers.php">Suppliers</a>');
			
		} elseif ($_POST['supplier_query_mode'] == 'edit') {
			// existing supplier, update records
			
			// get supplier ID to update
			$this->id = $_POST['supplier_id'];
			
			// process basic fields
			$this->prepareBasicInputData();
			
			$sqlQuery = "UPDATE supplier SET " .
						"name=$this->name," .                        // name
						"contact_person=$this->contactPerson," .     // contact_person
						"address=$this->address," .                  // address
						"telephone=$this->telephone," .              // telephone
						"mobile=$this->mobile," .                    // mobile
						"fax=$this->fax," .                          // fax
						"email=$this->email " .                      // email
						"WHERE id=$this->id";
			self::$database->query($sqlQuery);
			
			// log event
			EventLog::addEntry(self::$database, 'info', 'supplier', 'update', 'modified',
							   'Supplier <span class="event_log_main_record_inline">' .
							   '<a href="view_supplier_details.php?id=' . $this->id . '">' . capitalizeWords(htmlentities($_POST['supplier_name'])) . '</a>' .
							   '</span> was <span class="event_log_action">modified</span>');
			
		} else {
			// no further processing, just get the ID
			$this->id = $_POST['supplier_id'];
		}
		
		return $this->id;
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display tasks for supplier list
	//------------------------------------------------------------------------------------------------------------
	public static function showListTasks() {
		// get parameters
		if (!isset($_GET['criteria'])) {
			$criteria = "all-suppliers";
		} else {
			$criteria = $_GET['criteria'];
		}
		
		?>
		<div id="tasks">
			<ul>
				<li id="task_add_supplier"><a href="add_supplier.php"><img src="images/task_buttons/add.png" />Add Supplier</a></li>
                <li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php
				// display confirmation to unclear order
				$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br />' .
								 'This may take a few minutes. The system might not respond to other users while processing your request.<br /><br /><br />' .
								 '<div id="dialog_buttons">' .
								 '<input type="button" value="Yes" onclick="exportToExcelConfirm(' .
								 '\\\'data=supplier_list&criteria=' . $criteria . '\\\')" />' .
								 '<input type="button" value="No" onclick="hideDialog()" />' .
								 '</div>';
				$dialogMessage = htmlentities($dialogMessage);
				echo $dialogMessage;
				?>','prompt')"><img src="images/task_buttons/export_to_excel.png" />Export to Excel...</a></li>
			</ul>
		</div>
		</div><?php			// extra closing div
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display list of suppliers; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function showList() {
		// get parameters
		if (!isset($_POST['criteria'])) {
			$criteria = 'all-suppliers';
		} else {
			$criteria = $_POST['criteria'];
		}
		
		if (!isset($_POST['sortColumn'])) {
			$sortColumn = 'name';
		} else {
			$sortColumn = $_POST['sortColumn'];
		}
		
		if (!isset($_POST['sortMethod'])) {
			$sortMethod = 'ASC';
		} else {
			$sortMethod = $_POST['sortMethod'];
		}
		
		if (!isset($_POST['page']) || !isset($_POST['itemsPerPage'])) {
			$page         = 1;
			$itemsPerPage = ITEMS_PER_PAGE;
		} else {
			$page         = $_POST['page'];
			$itemsPerPage = $_POST['itemsPerPage'];
		}
		
		if (!isset($_POST['filterName'])) {
			$filterName = null;
		} else {
			$filterName = $_POST['filterName'];
		}
		
		if (!isset($_POST['filterValue'])) {
			$filterValue = null;
		} else {
			$filterValue = $_POST['filterValue'];
		}
		
		$offset = ($page * $itemsPerPage) - $itemsPerPage;
		
		// first letter filter
		if ($filterName == 'alpha' && $filterValue != null) {
			if ($filterValue == '#') {
				$condition = "WHERE name NOT RLIKE '^[A-Z]' ";
			} else {
				$condition = "WHERE name LIKE '$filterValue%' ";
			}
		} else {
			$condition = "";
		}
		
		// set condition
		switch ($criteria) {
			case "all-suppliers" :
				$sqlQuery = "SELECT COUNT(*) AS count FROM supplier $condition";
				break;
			
			default:
				if ($condition == "") {
					$condition = "WHERE ";
				} else {
					$condition = $condition . "AND ";
				}
				
				switch ($criteria) {
					case "with-payable" :
						$condition = $condition . "(v_supplier_payment_summary.amount_payable > 0 OR v_supplier_payment_summary.pdc_payable > 0) ";
						break;
					
					case "without-payable" :
						$condition = $condition . "(v_supplier_payment_summary.amount_payable = 0 AND v_supplier_payment_summary.pdc_payable = 0) ";
						break;
					
					case "with-rebate" :
						$condition = $condition . "(v_supplier_payment_summary.rebate_receivable > 0 OR v_supplier_payment_summary.pdc_rebate_receivable > 0) ";
						break;
					
					case "without-rebate" :
						$condition =
							$condition . "(v_supplier_payment_summary.rebate_receivable = 0 AND v_supplier_payment_summary.pdc_rebate_receivable = 0) ";
						break;
				}
				
				// count results prior to main query
				$sqlQuery = "SELECT COUNT(*) AS count " .
							"FROM supplier " .
							"LEFT JOIN v_supplier_payment_summary ON supplier.id = v_supplier_payment_summary.supplier_id " .
							$condition;
		}
		
		self::$database = new Database;
		$resultSet      = self::$database->query($sqlQuery);
		$resultCount    = self::$database->getResultRow($resultSet);
		$resultCount    = $resultCount['count'];
		
		// construct main query
		$sqlQuery = "SELECT supplier.*, " .
					"COUNT(v_active_purchases.id) AS purchase_count, " .
					"v_supplier_payment_summary.amount_payable AS amount_payable, " .
					"v_supplier_payment_summary.pdc_payable AS pdc_payable, " .
					"v_supplier_payment_summary.amount_payable + v_supplier_payment_summary.pdc_payable AS total_payable, " .
					"v_supplier_payment_summary.rebate_receivable AS rebate_receivable, " .
					"v_supplier_payment_summary.pdc_rebate_receivable AS pdc_rebate_receivable, " .
					"v_supplier_payment_summary.rebate_receivable + v_supplier_payment_summary.pdc_rebate_receivable AS total_rebate " .
					"FROM supplier " .
					"LEFT JOIN v_active_purchases ON supplier.id = v_active_purchases.supplier_id " .
					"LEFT JOIN v_supplier_payment_summary ON supplier.id = v_supplier_payment_summary.supplier_id " .
					"$condition GROUP BY supplier.id " .
					"ORDER BY $sortColumn $sortMethod " .
					"LIMIT $offset,$itemsPerPage";
		$resultSet = self::$database->query($sqlQuery);
		
		if (self::$database->getResultCount($resultSet) == 0) {
			echo '<div>No suppliers found.</div>';
			return;
		}
		
		$columns = array(
			'name'              => 'Supplier Name',
			'purchase_count'    => 'Pending Purchases',
			'amount_payable'    => 'Amount Payable',
			'pdc_payable'       => 'PDC Payable',
			'total_payable'     => 'Total Payable',
			'rebate_receivable' => 'Rebate'
		);
		
		self::showListHeader($columns, 'supplier_list_section', 'Supplier::showList', $criteria, $sortColumn, $sortMethod, $filterName, $filterValue);
		
		// display list
		while ($supplier = self::$database->getResultRow($resultSet)) {
			echo '<tr>';
			
			// supplier name
			echo '<td>' .
				 '<span class="extra_long_text_clip">' .
				 '<a href="view_supplier_details.php?id=' . $supplier['id'] . '&src=' . $criteria . '" title="' .
				 capitalizeWords(Filter::output($supplier['name'])) . '">' .
				 capitalizeWords(Filter::output($supplier['name'])) .
				 '</a>' .
				 '</span>' .
				 '</td>';
			
			// pending purchases
			echo '<td class="number">';
			if ($supplier['purchase_count'] == 0) {
				echo '<span class="bad">' . numberFormat($supplier['purchase_count'], 'int') . '</span>';
			} else {
				echo '<span>' . numberFormat($supplier['purchase_count'], 'int') . '</span>';
			}
			echo '</td>';
			
			// amount payable
			echo '<td class="number">';
			if ($supplier['amount_payable'] == 0) {
				echo '<span class="good">' . numberFormat($supplier['amount_payable'], 'float') . '</span>';
			} else {
				echo '<span>' . numberFormat($supplier['amount_payable'], 'float') . '</span>';
			}
			echo '</td>';
			
			// pdc payable
			echo '<td class="number">';
			if ($supplier['pdc_payable'] == 0) {
				echo '<span class="good">' . numberFormat($supplier['pdc_payable'], 'float') . '</span>';
			} else {
				echo '<span>' . numberFormat($supplier['pdc_payable'], 'float') . '</span>';
			}
			echo '</td>';
			
			// total payable
			echo '<td class="number">';
			if ($supplier['total_payable'] == 0) {
				echo '<span class="good">' . numberFormat($supplier['total_payable'], 'float') . '</span>';
			} else {
				echo '<span>' . numberFormat($supplier['total_payable'], 'float') . '</span>';
			}
			echo '</td>';
			
			// rebate
			echo '<td class="number">';
			if ($supplier['rebate_receivable'] == 0) {
				echo '<span class="good">' . numberFormat($supplier['rebate_receivable'], 'float') . '</span>';
			} else {
				echo '<span>' . numberFormat($supplier['rebate_receivable'], 'float') . '</span>';
			}
			if ($supplier['pdc_rebate_receivable'] > 0) {
				echo '<img src="images/rebate.png" class="status_icon" title="Rebate to clear: ' .
					 numberFormat($supplier['pdc_rebate_receivable'], 'currency', 3, '.', ',', true) . '" />';
			}
			echo '</td>';
			
			echo '</tr>';
		}
		
		echo '</tbody>';
		echo '</table>';
		
		self::showPagination($page, $itemsPerPage, $resultCount, 'supplier_list_section', 'Supplier::showList',
							 $criteria, $sortColumn, $sortMethod, $filterName, $filterValue);
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// save supplier list to excel file; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function exportListToExcel( $username, $paramArray ) {
		$fileTimeStampExtension = date(EXCEL_FILE_TIMESTAMP_FORMAT);
		$headingTimeStamp       = dateFormatOutput($fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT);
		
		require_once('classes/Filter.php');
		
		self::$database = new Database();
		
		// get parameters
		switch ($paramArray['criteria']) {
			case 'with-payable' :
				$fileName   = 'Suppliers with Payables';
				$sheetTitle = 'Suppliers with Payables';
				$condition  = " WHERE (v_supplier_payment_summary.amount_payable > 0 OR v_supplier_payment_summary.pdc_payable > 0) ";
				break;
			case 'without-payable' :
				$fileName   = 'Suppliers without Payables';
				$sheetTitle = 'Suppliers without Payables';
				$condition  = " WHERE (v_supplier_payment_summary.amount_payable = 0 AND v_supplier_payment_summary.pdc_payable = 0) ";
				break;
			case 'with-rebate' :
				$fileName   = 'Suppliers with Rebate';
				$sheetTitle = 'Suppliers with Rebate';
				$condition  = " WHERE (v_supplier_payment_summary.rebate_receivable > 0 OR v_supplier_payment_summary.pdc_rebate_receivable > 0) ";
				break;
			case 'without-rebate' :
				$fileName   = 'Suppliers without Rebate';
				$sheetTitle = 'Suppliers without Rebate';
				$condition  = " WHERE (v_supplier_payment_summary.rebate_receivable = 0 AND v_supplier_payment_summary.pdc_rebate_receivable = 0) ";
				break;
			default:
				$fileName   = 'All Suppliers';
				$sheetTitle = 'All Suppliers';
				$condition  = "";
				break;
		}
		
		// construct main query
		$sqlQuery = "SELECT supplier.*, " .
					"COUNT(v_active_purchases.id) AS purchase_count, " .
					"v_supplier_payment_summary.amount_payable AS amount_payable, " .
					"v_supplier_payment_summary.pdc_payable AS pdc_payable, " .
					"v_supplier_payment_summary.amount_payable + v_supplier_payment_summary.pdc_payable AS total_payable, " .
					"v_supplier_payment_summary.rebate_receivable AS rebate_receivable, " .
					"v_supplier_payment_summary.pdc_rebate_receivable AS pdc_rebate_receivable, " .
					"v_supplier_payment_summary.rebate_receivable + v_supplier_payment_summary.pdc_rebate_receivable AS total_rebate " .
					"FROM supplier " .
					"LEFT JOIN v_active_purchases ON supplier.id = v_active_purchases.supplier_id " .
					"LEFT JOIN v_supplier_payment_summary ON supplier.id = v_supplier_payment_summary.supplier_id " .
					"$condition GROUP BY supplier.id ORDER BY supplier.name ASC";
		$resultSet = self::$database->query($sqlQuery);
		
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
		
		$altRowColor = new PHPExcel_Style_Color();
		$altRowColor->setRGB(EXCEL_ALT_ROW_BACKGROUND_COLOR);
		
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
		$activeSheet->setTitle('Supplier List');
		
		// set default font
		$activeSheet->getDefaultStyle()->getFont()->setName(EXCEL_DEFAULT_FONT_NAME)
					->setSize(EXCEL_DEFAULT_FONT_SIZE);
		
		// write sheet headers
		$activeSheet->setCellValue('A1', CLIENT);
		$activeSheet->setCellValue('A2', $sheetTitle);
		$activeSheet->setCellValue('A3', "As of $headingTimeStamp");
		
		// define max column
		$MAX_COLUMN       = 'N';
		$FIELD_HEADER_ROW = '5';
		
		// format sheet headers
		$backgroundColor->setRGB(EXCEL_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . '4')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . '4')->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A1:A2')->getFont()->setBold(true);
		$activeSheet->getStyle('A1:A3')->getFont()->setName(EXCEL_HEADER_FONT_NAME);
		$activeSheet->getStyle('A1')->getFont()->setColor($fontColorRed);
		$activeSheet->getStyle('A1')->getFont()->setSize(EXCEL_HEADER1_FONT_SIZE);
		$activeSheet->getStyle('A2')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		$activeSheet->getStyle('A3')->getFont()->setSize(EXCEL_HEADER2_FONT_SIZE);
		
		// write column headers
		$activeSheet->setCellValue('A' . $FIELD_HEADER_ROW, 'Supplier')
					->setCellValue('B' . $FIELD_HEADER_ROW, 'Contact Person')
					->setCellValue('C' . $FIELD_HEADER_ROW, 'Office Address')
					->setCellValue('D' . $FIELD_HEADER_ROW, 'Telephone')
					->setCellValue('E' . $FIELD_HEADER_ROW, 'Mobile')
					->setCellValue('F' . $FIELD_HEADER_ROW, 'Fax')
					->setCellValue('G' . $FIELD_HEADER_ROW, 'E-mail')
					->setCellValue('H' . $FIELD_HEADER_ROW, 'Pending Purchases')
					->setCellValue('I' . $FIELD_HEADER_ROW, 'Amount Payable (' . CURRENCY . ')')
					->setCellValue('J' . $FIELD_HEADER_ROW, 'PDC Payable (' . CURRENCY . ')')
					->setCellValue('K' . $FIELD_HEADER_ROW, 'Total Payable (' . CURRENCY . ')')
					->setCellValue('L' . $FIELD_HEADER_ROW, 'Rebate Receivable (' . CURRENCY . ')')
					->setCellValue('M' . $FIELD_HEADER_ROW, 'PDC Rebate (' . CURRENCY . ')')
					->setCellValue('N' . $FIELD_HEADER_ROW, 'Total Rebate (' . CURRENCY . ')');
		
		// set column widths
		$activeSheet->getColumnDimension('A')->setWidth(50);
		$activeSheet->getColumnDimension('B')->setWidth(25);
		$activeSheet->getColumnDimension('C')->setWidth(80);
		$activeSheet->getColumnDimension('D')->setWidth(20);
		$activeSheet->getColumnDimension('E')->setWidth(20);
		$activeSheet->getColumnDimension('F')->setWidth(20);
		$activeSheet->getColumnDimension('G')->setWidth(25);
		$activeSheet->getColumnDimension('H')->setWidth(20);
		$activeSheet->getColumnDimension('I')->setWidth(22);
		$activeSheet->getColumnDimension('J')->setWidth(20);
		$activeSheet->getColumnDimension('K')->setWidth(20);
		$activeSheet->getColumnDimension('L')->setWidth(23);
		$activeSheet->getColumnDimension('M')->setWidth(20);
		$activeSheet->getColumnDimension('N')->setWidth(20);
		
		// format column headers
		$fontColor->setRGB(EXCEL_COLUMN_HEADER_FONT_COLOR);
		$backgroundColor->setRGB(EXCEL_COLUMN_HEADER_BACKGROUND_COLOR);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFill()->setStartColor($backgroundColor);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFont()->setColor($fontColor);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getFont()->setBold(true);
		$activeSheet->getStyle('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW)->getAlignment()->setWrapText(true);
		
		// set autofilter
		$activeSheet->setAutoFilter('A' . $FIELD_HEADER_ROW . ':' . $MAX_COLUMN . $FIELD_HEADER_ROW);
		
		// freeze pane
		$activeSheet->freezePane('B' . ($FIELD_HEADER_ROW + 1));
		
		// initialize counters
		$rowPtr    = $FIELD_HEADER_ROW + 1;
		$itemCount = 0;
		
		// write data
		if (self::$database->getResultCount($resultSet) > 0) {
			while ($supplier = self::$database->getResultRow($resultSet)) {
				// customer details
				$activeSheet->setCellValue('A' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($supplier['name']))))
							->setCellValue('B' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($supplier['contact_person']))))
							->setCellValue('C' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($supplier['address']))))
							->setCellValue('D' . $rowPtr, stripslashes($supplier['telephone']))
							->setCellValue('E' . $rowPtr, stripslashes($supplier['mobile']))
							->setCellValue('F' . $rowPtr, stripslashes($supplier['fax']))
							->setCellValue('G' . $rowPtr, stripslashes($supplier['email']));
				
				// pending orders
				if ($supplier['purchase_count'] > 0) {
					$activeSheet->setCellValue('H' . $rowPtr, $supplier['purchase_count']);
				}
				
				// amount payable
				if ($supplier['amount_payable'] > 0) {
					$activeSheet->setCellValue('I' . $rowPtr, $supplier['amount_payable']);
				}
				
				// pdc payable
				if ($supplier['pdc_payable'] > 0) {
					$activeSheet->setCellValue('J' . $rowPtr, $supplier['pdc_payable']);
				}
				
				// total payable
				$activeSheet->setCellValue('K' . $rowPtr, '=I' . $rowPtr . '+J' . $rowPtr);
				if ($activeSheet->getCell('K' . $rowPtr)->getCalculatedValue() == 0.000) {
					$activeSheet->getStyle('K' . $rowPtr)->getFont()->setColor($fontColorGreen);
				}
				
				// rebate receivable
				if ($supplier['rebate_receivable'] > 0) {
					$activeSheet->setCellValue('L' . $rowPtr, $supplier['rebate_receivable']);
				}
				
				// pdc rebate
				if ($supplier['pdc_rebate_receivable'] > 0) {
					$activeSheet->setCellValue('M' . $rowPtr, $supplier['pdc_rebate_receivable']);
				}
				
				// total rebate
				$activeSheet->setCellValue('N' . $rowPtr, '=L' . $rowPtr . '+M' . $rowPtr);
				if ($activeSheet->getCell('N' . $rowPtr)->getCalculatedValue() == 0.000) {
					$activeSheet->getStyle('N' . $rowPtr)->getFont()->setColor($fontColorGreen);
				}
				
				// set alternating row color
				if (EXCEL_ALT_ROW > 0 && $rowPtr % EXCEL_ALT_ROW == 0) {
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setStartColor($altRowColor);
				} else {
					$activeSheet->getStyle('A' . $rowPtr . ':' . $MAX_COLUMN . $rowPtr)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_NONE);
				}
				
				$rowPtr++;
				$itemCount++;
			}
			
			$rowPtr--;
		}
		
		// post formatting
		$activeSheet->getStyle('A6:G' . $rowPtr)->getAlignment()->setWrapText(true);                        // wrap columns
		$activeSheet->getStyle('H6:H' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);       // format Pending Purchases
		$activeSheet->getStyle('I6:N' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);  // format amounts
		$activeSheet->getStyle('K6:K' . $rowPtr)->getFont()->setBold(true);                                 // set Total Receivable to bold
		$activeSheet->getStyle('N6:N' . $rowPtr)->getFont()->setBold(true);                                 // set Total Rebate to bold
		
		// set columns to left aligned
		$activeSheet->getStyle('A6:G' . $rowPtr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		// write totals
		$totalsRow = $rowPtr + 3;
		$activeSheet->setCellValue('A' . $totalsRow, 'Total Number of Suppliers: ' . numberFormat($itemCount, 'int'))
					->setCellValue('G' . $totalsRow, 'Totals:')
					->setCellValue('H' . $totalsRow, '=SUM(H6:H' . $rowPtr . ')')
					->setCellValue('I' . $totalsRow, '=SUM(I6:I' . $rowPtr . ')')
					->setCellValue('J' . $totalsRow, '=SUM(J6:J' . $rowPtr . ')')
					->setCellValue('K' . $totalsRow, '=SUM(K6:K' . $rowPtr . ')')
					->setCellValue('L' . $totalsRow, '=SUM(L6:L' . $rowPtr . ')')
					->setCellValue('M' . $totalsRow, '=SUM(M6:M' . $rowPtr . ')')
					->setCellValue('N' . $totalsRow, '=SUM(N6:N' . $rowPtr . ')');
		
		// format totals
		$styleArray = array(
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THICK)
			)
		);
		$activeSheet->getStyle('A' . $totalsRow . ':N' . $totalsRow)->applyFromArray($styleArray);
		$activeSheet->getStyle('H' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);
		$activeSheet->getStyle('I' . $totalsRow . ':N' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_CURRENCY_FORMAT);
		$activeSheet->getStyle('A' . $totalsRow . ':N' . $totalsRow)->getFont()->setBold(true);
		$activeSheet->getStyle('A' . $totalsRow . ':N' . $totalsRow)->getFont()->setColor($fontColorRed);
		
		// set vertical alignment to top
		$activeSheet->getStyle('A1:' . $MAX_COLUMN . $totalsRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
		
		// set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);
		
		// redirect output to a client's web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . CLIENT . ' - ' . $fileName . ' - as of ' . $fileTimeStampExtension . '.xlsx"');
		header('Cache-Control: max-age=0');
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display tasks for supplier details
	//------------------------------------------------------------------------------------------------------------
	public function showDetailsTasks() {
		?>
		<div id="tasks">
			<ul>
				<li id="task_edit_customer"><a href="edit_supplier.php?id=<?php echo $this->id ?>">
					<img src="images/task_buttons/edit.png" />Edit Supplier</a></li>
				<li id="task_back_to_list"><a href="list_suppliers.php<?php echo(isset($_GET['src']) ? '?criteria=' . $_GET['src'] : '') ?>">
					<img src="images/task_buttons/back_to_list.png" />Back to List</a></li>
			</ul>
		</div>
		</div><?php			// extra closing div
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display supplier details
	//------------------------------------------------------------------------------------------------------------
	public function view() {
		// get supplier info
		$sqlQuery  = "SELECT supplier.*, " .
					 "COUNT(v_active_purchases.id) AS active_purchases_count, " .
					 "v_supplier_payment_summary.amount_payable AS amount_payable, " .
					 "v_supplier_payment_summary.pdc_payable AS pdc_payable, " .
					 "v_supplier_payment_summary.amount_payable + v_supplier_payment_summary.pdc_payable AS total_payable, " .
					 "v_supplier_payment_summary.rebate_receivable AS rebate_receivable, " .
					 "v_supplier_payment_summary.pdc_rebate_receivable AS pdc_rebate_receivable, " .
					 "v_supplier_payment_summary.rebate_receivable + v_supplier_payment_summary.pdc_rebate_receivable AS total_rebate " .
					 "FROM supplier " .
					 "LEFT JOIN v_active_purchases ON supplier.id = v_active_purchases.supplier_id " .
					 "LEFT JOIN v_supplier_payment_summary ON supplier.id = v_supplier_payment_summary.supplier_id " .
					 "WHERE supplier.id = $this->id";
		$resultSet = self::$database->query($sqlQuery);
		$supplier  = self::$database->getResultRow($resultSet);
		
		if ($supplier['id'] == null) {
			// supplier ID is not found in database
			redirectToHomePage();
		}
		
		$this->name          = capitalizeWords(Filter::output($supplier['name']));
		$this->contactPerson = capitalizeWords(Filter::output($supplier['contact_person']));
		$this->address       = capitalizeWords(Filter::output($supplier['address']));
		$this->telephone     = Filter::output($supplier['telephone']);
		$this->mobile        = Filter::output($supplier['mobile']);
		$this->fax           = Filter::output($supplier['fax']);
		$this->email         = strtolower(Filter::output($supplier['email']));
		
		HtmlLayout::setPageTitleStatic('Suppliers » ' . addslashes(html_entity_decode(capitalizeWords(Filter::output($supplier['name'])))));
		
		echo '<fieldset><legend>Supplier Info</legend>';
		
		// display basic info
		$this->showBasicInfo(self::SHOW_CONTACT_PERSON, self::ADDRESS_LABEL);
		
		echo '</fieldset>';
		
		// get purchases to this supplier
		$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase WHERE supplier_id = $this->id";
		$resultSet = self::$database->query($sqlQuery);
		$purchase  = self::$database->getResultRow($resultSet);
		if ((int) $purchase['count'] > 0) {
			?>
			<fieldset>
				<legend>Purchases List</legend>
				<section id="purchase_list_section">
				</section>
			</fieldset>
			
			<script type="text/javascript">
				<!--
				ajax(null, 'purchase_list_section', 'innerHTML', 'Purchase::showList',
					 'criteria=all-purchases&filterName=supplier_id&filterValue=<?php echo $this->id ?>');
				// -->
			</script>
			
			<fieldset>
				<legend>Totals</legend>
				<section>
					<div>
						<span class="record_label">Total No. of Purchases:</span>
						<span class="record_data"><?php echo numberFormat($purchase['count'], 'int') ?></span>
					</div>
					<div>
						<span class="record_label">Pending Purchases:</span>
						<span class="record_data"><?php echo numberFormat($supplier['active_purchases_count'], 'int') ?></span>
					</div>
				</section>
				
				<section>
					<div>
						<span class="record_label">Amount Payable:</span>
						<span class="record_data"><?php echo numberFormat($supplier['amount_payable'], 'currency') ?></span>
					</div>
					<div>
						<span class="record_label">PDC Payable:</span>
						<span class="record_data"><?php echo numberFormat($supplier['pdc_payable'], 'currency') ?></span>
					</div>
					<div>
						<span class="record_label">Total Payable:</span>
						<span class="record_data"><?php echo numberFormat($supplier['total_payable'], 'currency') ?></span>
					</div>
				</section>
				
				<section>
					<div>
						<span class="record_label">Rebate Receivable:</span>
						<span class="record_data"><?php echo numberFormat($supplier['rebate_receivable'], 'currency') ?></span>
					</div>
					<div>
						<span class="record_label">PDC Rebate:</span>
						<span class="record_data"><?php echo numberFormat($supplier['pdc_rebate_receivable'], 'currency') ?></span>
					</div>
					<div>
						<span class="record_label">Total Rebate:</span>
						<span class="record_data"><?php echo numberFormat($supplier['total_rebate'], 'currency') ?></span>
					</div>
				</section>
			</fieldset>
			
			<?php
			// default date range
			$startDate    = new DateTime(date('Y') . '-01-01');    // January 1 of present year
			$startDateStr = $startDate->format("F j, Y, D");
			$endDate      = new DateTime();                        // present date
			$endDateStr   = $endDate->format("F j, Y, D");
			?>
			
			<fieldset>
				<legend>History and Statistics</legend>
				<section>
					<div class="report_data">
						<form name="report_date_form">
							<label for="startdate">Start Date:</label>
							<input type="text" name="startdate" id="startdate" class="datepicker_no_future_date" size="30" maxlength="30"
								   required="required" value="<?php echo $startDateStr ?>" />
							<label for="enddate">End Date:</label>
							<input type="text" name="enddate" id="enddate" class="datepicker_no_future_date" size="30" maxlength="30" required="required"
								   value="<?php echo $endDateStr ?>" />
							<input type="button" name="submit_form" value="Go"
								   onclick="javascript:ajax(null, 'supplier_statistics_section', 'innerHTML', 'Supplier::showHistoryAndStatistics',
									   						'supplierID=<?php echo $this->id ?>&startDate=' +
									   						$('#startdate').val() + '&endDate=' + $('#enddate').val())" />
						</form>
					</div>
				</section>
				<section id="supplier_statistics_section">
				</section>
			</fieldset>
			
			<script type="text/javascript">
				<!--
				ajax(null, 'supplier_statistics_section', 'innerHTML', 'Supplier::showHistoryAndStatistics',
					 'supplierID=<?php echo $this->id ?>&startDate=<?php echo $startDateStr ?>&endDate=<?php echo $endDateStr ?>');
				// -->
			</script><?php
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display purchase history and statistics for supplier; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function showHistoryAndStatistics() {
		// check required parameters
		if (!isset($_POST['supplierID']) || !isset($_POST['startDate']) || !isset($_POST['endDate'])) {
			return;
		}
		
		// get date range
		$startDate      = new DateTime($_POST['startDate']);
		$startDateParam = $startDate->format('Y-m-d');
		$endDate        = new DateTime($_POST['endDate']);
		$endDateParam   = $endDate->format('Y-m-d');
		
		// check date for validity
		if ($startDate > $endDate) {
			echo '<p><span class="bad">Error:</span> The start date is greater than end date. Please select a different set of dates.</p>';
			return;
		}
		
		$database = new Database();
		
		?>
		<section>
			<div>
				<?php
				$sqlQuery      = "SELECT COUNT(id) AS count " .
								 "FROM purchase " .
								 "WHERE supplier_id = {$_POST['supplierID']} " .
								 "AND (purchase_date BETWEEN '$startDateParam 00:00:00' AND '$endDateParam 23:59:59')";
				$resultSet     = $database->query($sqlQuery);
				$purchase      = $database->getResultRow($resultSet);
				$purchaseCount = $purchase['count'];
				?>
				<span class="record_label">Number of placed purchases:</span>
				<span class="record_data"><?php echo numberFormat($purchaseCount, 'int'); ?></span>
			</div>
			<div>
				<?php
				$sqlQuery  = "SELECT COUNT(id) AS active_purchases_count " .
							 "FROM v_active_purchases " .
							 "WHERE supplier_id = {$_POST['supplierID']} " .
							 "AND (purchase_date BETWEEN '$startDateParam 00:00:00' AND '$endDateParam 23:59:59')";
				$resultSet = $database->query($sqlQuery);
				$purchase  = $database->getResultRow($resultSet);
				?>
				<span class="record_label">Pending purchases:</span>
				<span class="record_data"><?php echo numberFormat($purchase['active_purchases_count'], 'int'); ?></span>
			</div>
			<div>
				<?php
				$sqlQuery  = "SELECT COUNT(id) AS cleared_purchases_count " .
							 "FROM purchase " .
							 "WHERE supplier_id = {$_POST['supplierID']} " .
							 "AND cleared_date IS NOT NULL " .
							 "AND (purchase_date BETWEEN '$startDateParam 00:00:00' AND '$endDateParam 23:59:59')";
				$resultSet = $database->query($sqlQuery);
				$purchase  = $database->getResultRow($resultSet);
				?>
				<span class="record_label">Cleared purchases:</span>
				<span class="record_data"><?php echo numberFormat($purchase['cleared_purchases_count'], 'int'); ?></span>
			</div>
			<div>
				<?php
				$sqlQuery  = "SELECT COUNT(id) AS canceled_purchases_count " .
							 "FROM purchase " .
							 "WHERE supplier_id = {$_POST['supplierID']} " .
							 "AND canceled_date IS NOT NULL " .
							 "AND (purchase_date BETWEEN '$startDateParam 00:00:00' AND '$endDateParam 23:59:59')";
				$resultSet = $database->query($sqlQuery);
				$purchase  = $database->getResultRow($resultSet);
				?>
				<span class="record_label">Canceled purchases:</span>
				<span class="record_data"><?php echo numberFormat($purchase['canceled_purchases_count'], 'int'); ?></span>
			</div>
		</section>
		
		<section>
			<div>
				<?php
				$sqlQuery  = "SELECT SUM(amount) AS total_amount_paid " .
							 "FROM purchase_payment " .
							 "INNER JOIN purchase ON purchase_payment.purchase_id = purchase.id " .
							 "INNER JOIN supplier ON purchase.supplier_id = supplier.id " .
							 "WHERE supplier.id = {$_POST['supplierID']} " .
							 "AND (payment_date BETWEEN '$startDateParam 00:00:00' AND '$endDateParam 23:59:59')";
				$resultSet = $database->query($sqlQuery);
				$purchase  = $database->getResultRow($resultSet);
				?>
				<span class="record_label">Total payments:</span>
				<span class="record_data"><?php echo numberFormat($purchase['total_amount_paid'], 'currency'); ?></span>
			</div>
			<div>
				<?php
				$sqlQuery  = "SELECT SUM(amount) AS total_amount_paid " .
							 "FROM purchase_payment " .
							 "INNER JOIN purchase ON purchase_payment.purchase_id = purchase.id " .
							 "INNER JOIN supplier ON purchase.supplier_id = supplier.id " .
							 "WHERE supplier.id = {$_POST['supplierID']} " .
							 "AND (payment_date BETWEEN '$startDateParam 00:00:00' AND '$endDateParam 23:59:59') " .
							 "AND clearing_actual_date IS NOT NULL";
				$resultSet = $database->query($sqlQuery);
				$purchase  = $database->getResultRow($resultSet);
				?>
				<span class="record_label">Cleared payments:</span>
				<span class="record_data"><?php echo numberFormat($purchase['total_amount_paid'], 'currency'); ?></span>
			</div>
			<div>
				<?php
				$sqlQuery  = "SELECT SUM(amount) AS total_amount_paid " .
							 "FROM purchase_payment " .
							 "INNER JOIN purchase ON purchase_payment.purchase_id = purchase.id " .
							 "INNER JOIN supplier ON purchase.supplier_id = supplier.id " .
							 "WHERE supplier.id = {$_POST['supplierID']} " .
							 "AND (payment_date BETWEEN '$startDateParam 00:00:00' AND '$endDateParam 23:59:59') " .
							 "AND clearing_actual_date IS NULL";
				$resultSet = $database->query($sqlQuery);
				$purchase  = $database->getResultRow($resultSet);
				?>
				<span class="record_label">Unclear payments:</span>
				<span class="record_data"><?php echo numberFormat($purchase['total_amount_paid'], 'currency'); ?></span>
			</div>
		</section>
		<?php
	}
}

?>

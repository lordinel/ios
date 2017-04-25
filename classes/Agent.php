<?php
// note: this class requires scripts/agents.js


//----------------------------------------------------------------------------------------------------------------
// class definition for sales agents
//----------------------------------------------------------------------------------------------------------------
class Agent extends Person
{
	const NAME_LABEL = 'Agent Name';
	
	
	private $branch;
	private $department;
	private $position;
	
	
	//------------------------------------------------------------------------------------------------------------
	// display agent form
	//------------------------------------------------------------------------------------------------------------
	public static function showInputForm( $id = null ) {
		echo '<form name="' . ($id == null ? 'add' : 'edit') .
			 '_agent" method="post" action="view_agent_details.php" autocomplete="off" onreset="return confirmReset(\'resetAgentForm\')">';
		self::showInputFieldset($id);
		self::showButtons(ButtonSet::SUBMIT_RESET_CANCEL);
		echo '</form>';
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display agent form field set
	//------------------------------------------------------------------------------------------------------------
	public static function showInputFieldset( $id = null ) {
		echo '<fieldset><legend>Agent Info</legend>';
		
		if ($id != null) {
			$id = Filter::reinput($id);
			self::$database = new Database();
			$resultSet      = self::$database->query("SELECT * FROM agent WHERE id = $id");
			$agentInfo      = self::$database->getResultRow($resultSet);
		} else {
			$agentInfo      = null;
		}
		
		// display basic fields
		self::showBasicInputFields($agentInfo, self::NAME_LABEL, false, 'Address', false);
		
		// display additional fields
		?>
		<section>
			<div>
				<label for="branch">Branch:</label>
				<input type="text" name="branch" id="branch" class="form_input_text" maxlength="100"<?php
					echo ($agentInfo != null) ? ' value="' . capitalizeWords(Filter::reinput($agentInfo['branch'])) . '"' : '' ?> />
			</div>
			<div>
				<label for="department">Department:</label>
				<input type="text" name="department" id="department" class="form_input_text" maxlength="100"<?php
					echo ($agentInfo != null) ? ' value="' . capitalizeWords(Filter::reinput($agentInfo['department'])) . '"' : '' ?> />
			</div>
			<div>
				<label for="position">Position:</label>
				<input type="text" name="position" id="position" class="form_input_text" maxlength="100"<?php
					echo ($agentInfo != null) ? ' value="' . capitalizeWords(Filter::reinput($agentInfo['position'])) . '"' : '' ?> />
			</div>
		</section>
		<?php
		echo '</fieldset>';
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// auto-suggest agent name; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function showAutoSuggest() {
		// check required parameters
		if (isset($_POST['searchString'])) {
			self::showAutoSuggestResult('agent', 'name', $_POST['searchString']);
		}
		return;
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// auto-fill agent form based on auto-suggest selection; ajax function
	//------------------------------------------------------------------------------------------------------------
	public static function autoFill() {
		// check required parameters
		if (!isset($_POST['agentName'])) {
			return;
		}
		
		$agent = self::getAutoFillData('agent', 'name', $_POST['agentName']);
		
		// send back auto-fill data
		if ($agent != null) {
			$agent['address']    = html_entity_decode(capitalizeWords(Filter::output($agent['address'])));
			$agent['email']      = strtolower($agent['email']);
			$agent['branch']     = html_entity_decode(capitalizeWords(Filter::output($agent['branch'])));
			$agent['department'] = html_entity_decode(capitalizeWords(Filter::output($agent['department'])));
			$agent['position']   = html_entity_decode(capitalizeWords(Filter::output($agent['position'])));
			echo json_encode($agent);
		} else {
			$agent['id'] = 0;
			echo json_encode($agent);
		}
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// save agent info to database
	//------------------------------------------------------------------------------------------------------------
	public function save() {
		if ($_POST['agent_query_mode'] == 'new') {
			// new customer
			
			// process basic fields
			$this->prepareBasicInputData();
			
			// process additional fields
			if (empty($_POST['branch'])) {
				$this->branch = 'NULL';
			} else {
				$this->branch = "'" . Filter::input($_POST['branch']) . "'";
			}
			
			if (empty($_POST['department'])) {
				$this->department = 'NULL';
			} else {
				$this->department = "'" . Filter::input($_POST['department']) . "'";
			}
			
			if (empty($_POST['position'])) {
				$this->position = 'NULL';
			} else {
				$this->position = "'" . Filter::input($_POST['position']) . "'";
			}
			
			// save new agent to database
			$sqlQuery = "INSERT INTO agent VALUES (" .
						"NULL," .                             // id, auto-generate
						"$this->name," .                      // name
						"$this->address," .                   // address
						"$this->telephone," .                 // telephone
						"$this->mobile," .                    // mobile
						"$this->fax," .                       // fax
						"$this->email," .                     // email
						"$this->branch," .                    // branch
						"$this->department," .                // department
						"$this->position)";                   // position
			self::$database->query($sqlQuery);
			
			// get generated agent ID
			$this->id = self::$database->getLastInsertID();
			
			// log event
			EventLog::addEntry(self::$database, "info", 'agent', 'insert', 'new',
							   '<span class="event_log_main_record_inline">' .
							   '<a href="view_agent_details.php?id=' . $this->id . '">' . capitalizeWords(htmlentities($_POST['agent_name'])) . '</a>' .
							   '</span> was <span class="event_log_action">added</span> to <a href="list_agents.php">Agents</a>');
			
		} elseif ($_POST['agent_query_mode'] == 'edit') {
			// existing customer; update records
			
			// get agent ID to update
			$this->id = $_POST['agent_id'];
			
			// process basic fields
			$this->prepareBasicInputData();
			
			// process additional fields
			if (empty($_POST['branch'])) {
				$this->branch = 'NULL';
			} else {
				$this->branch = "'" . Filter::input($_POST['branch']) . "'";
			}
			
			if (empty($_POST['department'])) {
				$this->department = 'NULL';
			} else {
				$this->department = "'" . Filter::input($_POST['department']) . "'";
			}
			
			if (empty($_POST['position'])) {
				$this->position = 'NULL';
			} else {
				$this->position = "'" . Filter::input($_POST['position']) . "'";
			}
			
			// update agent in database
			$sqlQuery = "UPDATE agent SET " .
						"name=$this->name," .                        // name
						"address=$this->address," .                  // address
						"telephone=$this->telephone," .              // telephone
						"mobile=$this->mobile," .                    // mobile
						"fax=$this->fax," .                          // fax
						"email=$this->email," .                      // email
						"branch=$this->branch," .                    // branch
						"department=$this->department," .            // department
						"position=$this->position " .                // position
						"WHERE id=$this->id";
			self::$database->query($sqlQuery);
			
			// log event
			EventLog::addEntry(self::$database, 'info', 'agent', 'update', 'modified',
							   'Agent <span class="event_log_main_record_inline">' .
							   '<a href="view_agent_details.php?id=' . $this->id . '">' . capitalizeWords(htmlentities($_POST['agent_name'])) . '</a>' .
							   '</span> was <span class="event_log_action">modified</span>');
			
		} else {
			// no further processing, just get the ID
			$this->id = $_POST['agent_id'];
		}
		
		return $this->id;
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display tasks for agent list
	//------------------------------------------------------------------------------------------------------------
	public static function showListTasks() {
		?>
		<div id="tasks">
			<ul>
				<li id="task_add_customer"><a href="add_agent.php"><img src="images/task_buttons/add.png" />Add Agent</a></li>
				<li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php
				$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br />' .
								 'This may take a few minutes. The system might not respond to other users while processing your request.<br /><br /><br />' .
								 '<div id="dialog_buttons">' .
								 '<input type="button" value="Yes" onclick="exportToExcelConfirm(' .
								 '\\\'data=agent_list\\\')" />' .
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
	// display list of agents
	//------------------------------------------------------------------------------------------------------------
	public static function showList() {
		// get parameters
		if (!isset($_POST['criteria']) || !isset($_POST['sortColumn']) || !isset($_POST['sortMethod'])) {
			// default sorting
			$criteria   = 'all';
			$sortColumn = 'name';
			$sortMethod = 'ASC';
		} else {
			// get parameter values for sorting
			$criteria   = $_POST['criteria'];
			$sortColumn = $_POST['sortColumn'];
			$sortMethod = $_POST['sortMethod'];
		}
		
		if (!isset($_POST['page']) || !isset($_POST['itemsPerPage'])) {
			$page         = 1;
			$itemsPerPage = ITEMS_PER_PAGE;
		} else {
			$page         = $_POST['page'];
			$itemsPerPage = $_POST['itemsPerPage'];
		}
		
		$offset = ($page * $itemsPerPage) - $itemsPerPage;
		
		self::$database = new Database;
		
		// count agents
		$sqlQuery    = "SELECT COUNT(*) AS count FROM agent";
		$resultSet   = self::$database->query($sqlQuery);
		$resultCount = self::$database->getResultRow($resultSet);
		$resultCount = $resultCount['count'];
		
		// @TODO MySQL bug: cannot run count and two left join
		
		// get agent list
		$sqlQuery = "SELECT agent.*, COUNT(`order`.id) AS order_count FROM agent " .
					"LEFT JOIN `order` ON agent.id = `order`.agent_id " .
					"GROUP BY agent.id";
		
		/*switch( $criteria )
		{
			case "all":

		}*/
		
		$sqlQuery = $sqlQuery . " ORDER BY $sortColumn $sortMethod LIMIT $offset, $itemsPerPage";
		
		$resultSet = self::$database->query($sqlQuery);
		
		if (self::$database->getResultCount($resultSet) == 0) {
			echo '<div>No agents found.</div>';
			return;
		}
		
		$columns = array(
			'name'        => 'Agent Name',
			'branch'      => 'Branch',
			'department'  => 'Department',
			'position'    => 'Position',
			'order_count' => 'No. of Referred Orders'
		);
		
		self::showListHeader($columns, 'agent_list_section', 'Agent::showList', $criteria, $sortColumn, $sortMethod);
		
		while ($agent = self::$database->getResultRow($resultSet)) {
			?>
			<tr>
			<td>
				<span class="long_text_clip">
				<a href="view_agent_details.php?id=<?php echo $agent['id'] ?>"
				   title="<?php echo capitalizeWords(Filter::output($agent['name'])) ?>"><?php echo capitalizeWords(Filter::output($agent['name'])) ?></a>
				</span>
			</td>
			<td><span class="long_text_clip"><?php echo capitalizeWords(Filter::output($agent['branch'])) ?></span></td>
			<td><span class="long_text_clip"><?php echo capitalizeWords(Filter::output($agent['department'])) ?></span></td>
			<td><span class="long_text_clip"><?php echo capitalizeWords(Filter::output($agent['position'])) ?></span></td>
			<td class="number">
			<?php
				if ($agent['order_count'] <= 0) {
					echo '<span class="bad">' . numberFormat($agent['order_count'], 'int') . '</span>';
				} else {
					echo '<span>' . numberFormat($agent['order_count'], 'int') . '</span>';
				}
			?>
			</td>
			</tr>
			<?php
		}
		
		echo '</tbody></table>';
		
		self::showPagination($page, $itemsPerPage, $resultCount, 'agent_list_section', 'Agent::showList', $criteria, $sortColumn, $sortMethod);
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// save agent list to excel file
	//------------------------------------------------------------------------------------------------------------
	public static function exportListToExcel( $username ) {
		$fileTimeStampExtension = date(EXCEL_FILE_TIMESTAMP_FORMAT);
		$headingTimeStamp       = dateFormatOutput($fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT);
		
		require_once('classes/Filter.php');
		
		self::$database = new Database();
		
		$fileName   = 'Agents';
		$sheetTitle = 'Agents';
		
		
		// @TODO MySQL bug: cannot run count and two left join
		
		// get agent count
		$sqlQuery  = "SELECT agent.id AS id, " .
					 "IF(COUNT(DISTINCT purchase.id) IS NULL,0,COUNT(DISTINCT purchase.id)) AS purchase_count FROM agent " .
					 "LEFT JOIN purchase ON agent.id = purchase.agent_id " .
					 "GROUP BY agent.id ORDER BY agent.name ASC";
		$resultSet = self::$database->query($sqlQuery);
		
		// save to array
		if (self::$database->getResultCount($resultSet) > 0) {
			while ($agent = self::$database->getResultRow($resultSet)) {
				$agentPurchaseCount[$agent['id']] = $agent['purchase_count'];
			}
		}
		
		// get agent list and order count
		$sqlQuery = "SELECT agent.*, " .
					"IF(COUNT(DISTINCT `order`.id) IS NULL,0,COUNT(DISTINCT `order`.id)) AS order_count FROM agent " .
					"LEFT JOIN `order` ON agent.id = `order`.agent_id " .
					"GROUP BY agent.id ORDER BY agent.name ASC";
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
		$activeSheet->setTitle('Customer List');
		
		// set default font
		$activeSheet->getDefaultStyle()->getFont()->setName(EXCEL_DEFAULT_FONT_NAME)
					->setSize(EXCEL_DEFAULT_FONT_SIZE);
		
		// write sheet headers
		$activeSheet->setCellValue('A1', CLIENT);
		$activeSheet->setCellValue('A2', $sheetTitle);
		$activeSheet->setCellValue('A3', "As of $headingTimeStamp");
		
		// define max column
		$MAX_COLUMN       = 'K';
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
		$activeSheet->setCellValue('A' . $FIELD_HEADER_ROW, 'Agent Name')
					->setCellValue('B' . $FIELD_HEADER_ROW, 'Address')
					->setCellValue('C' . $FIELD_HEADER_ROW, 'Telephone')
					->setCellValue('D' . $FIELD_HEADER_ROW, 'Mobile')
					->setCellValue('E' . $FIELD_HEADER_ROW, 'Fax')
					->setCellValue('F' . $FIELD_HEADER_ROW, 'E-mail')
					->setCellValue('G' . $FIELD_HEADER_ROW, 'Branch')
					->setCellValue('H' . $FIELD_HEADER_ROW, 'Department')
					->setCellValue('I' . $FIELD_HEADER_ROW, 'Position')
					->setCellValue('J' . $FIELD_HEADER_ROW, 'No. of Referred Orders')
					->setCellValue('K' . $FIELD_HEADER_ROW, 'No. of Referred Purchases');
		
		// set column widths
		$activeSheet->getColumnDimension('A')->setWidth(50);
		$activeSheet->getColumnDimension('B')->setWidth(80);
		$activeSheet->getColumnDimension('C')->setWidth(20);
		$activeSheet->getColumnDimension('D')->setWidth(20);
		$activeSheet->getColumnDimension('E')->setWidth(20);
		$activeSheet->getColumnDimension('F')->setWidth(25);
		$activeSheet->getColumnDimension('G')->setWidth(25);
		$activeSheet->getColumnDimension('H')->setWidth(25);
		$activeSheet->getColumnDimension('I')->setWidth(25);
		$activeSheet->getColumnDimension('J')->setWidth(20);
		$activeSheet->getColumnDimension('K')->setWidth(20);
		
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
			while ($agent = self::$database->getResultRow($resultSet)) {
				// agent details
				$activeSheet->setCellValue('A' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($agent['name']))))
							->setCellValue('B' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($agent['address']))))
							->setCellValue('C' . $rowPtr, stripslashes($agent['telephone']))
							->setCellValue('D' . $rowPtr, stripslashes($agent['mobile']))
							->setCellValue('E' . $rowPtr, stripslashes($agent['fax']))
							->setCellValue('F' . $rowPtr, strtolower(stripslashes($agent['email'])))
							->setCellValue('G' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($agent['branch']))))
							->setCellValue('H' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($agent['department']))))
							->setCellValue('I' . $rowPtr, html_entity_decode(capitalizeWords(Filter::reinput($agent['position']))));
				
				// no. of referred orders
				if ($agent['order_count'] > 0) {
					$activeSheet->setCellValue('J' . $rowPtr, $agent['order_count']);
				}
				
				// no. of referred purchases
				if ($agentPurchaseCount[$agent['id']] > 0) {
					$activeSheet->setCellValue('K' . $rowPtr, $agentPurchaseCount[$agent['id']]);
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
		$activeSheet->getStyle('A6:I' . $rowPtr)->getAlignment()->setWrapText(true);                        // wrap columns
		$activeSheet->getStyle('J6:K' . $rowPtr)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);       // format numbers
		
		// set columns to left aligned
		$activeSheet->getStyle('A6:I' . $rowPtr)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		
		// write totals
		$totalsRow = $rowPtr + 3;
		$activeSheet->setCellValue('A' . $totalsRow, 'Total Number of Agents: ' . numberFormat($itemCount, "int"))
					->setCellValue('I' . $totalsRow, 'Totals:')
					->setCellValue('J' . $totalsRow, '=SUM(J6:J' . $rowPtr . ')')
					->setCellValue('K' . $totalsRow, '=SUM(K6:K' . $rowPtr . ')');
		
		// format totals
		$styleArray = array(
			'borders' => array(
				'top' => array('style' => PHPExcel_Style_Border::BORDER_THICK)
			)
		);
		$activeSheet->getStyle('A' . $totalsRow . ':K' . $totalsRow)->applyFromArray($styleArray);
		$activeSheet->getStyle('A' . $totalsRow . ':K' . $totalsRow)->getFont()->setBold(true);
		$activeSheet->getStyle('A' . $totalsRow . ':K' . $totalsRow)->getFont()->setColor($fontColorRed);
		$activeSheet->getStyle('J' . $totalsRow . ':K' . $totalsRow)->getNumberFormat()->setFormatCode(EXCEL_INT_FORMAT);
		
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
	// display tasks for agent details
	//------------------------------------------------------------------------------------------------------------
	public function showDetailsTasks() {
		?>
		<div id="tasks">
			<ul>
				<li id="task_edit_customer"><a href="edit_agent.php?id=<?php echo $this->id ?>"><img src="images/task_buttons/edit.png" />Edit Agent</a></li>
				<li id="task_back_to_list"><a href="list_agents.php"><img src="images/task_buttons/back_to_list.png" />Back to List</a></li>
			</ul>
		</div>
		</div><?php		// extra closing div
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// display agent details
	//------------------------------------------------------------------------------------------------------------
	public function view() {
		// get agent info
		$sqlQuery  = "SELECT * FROM agent WHERE id = $this->id";
		$resultSet = self::$database->query($sqlQuery);
		
		if (self::$database->getResultCount($resultSet) == 0) {
			// agent ID is not found in database
			redirectToHomePage();
		}
		
		$agent = self::$database->getResultRow($resultSet);
		
		$this->name       = capitalizeWords(Filter::output($agent['name']));
		$this->address    = capitalizeWords(Filter::output($agent['address']));
		$this->telephone  = Filter::output($agent['telephone']);
		$this->mobile     = Filter::output($agent['mobile']);
		$this->fax        = Filter::output($agent['fax']);
		$this->email      = strtolower(Filter::output($agent['email']));
		$this->branch     = capitalizeWords(Filter::output($agent['branch']));
		$this->department = capitalizeWords(Filter::output($agent['department']));
		$this->position   = capitalizeWords(Filter::output($agent['position']));
		
		HtmlLayout::setPageTitleStatic('Agents » ' . addslashes(html_entity_decode(capitalizeWords(Filter::output($agent['name'])))));
		
		echo '<fieldset><legend>Agent Info</legend>';
		
		// display basic info
		$this->showBasicInfo(false, 'Address');
		
		// display additional info 
		if ($this->branch != null || $this->department != null || $this->position != null) {
			echo '<section>';
			
			if ($this->branch != null) {
				?>
				<div>
					<span class="record_label">Branch:</span>
					<span class="record_data"><?php echo $this->branch ?></span>
				</div>
				<?php
			}
				
			if ($this->department != null) {
				?>
				<div>
					<span class="record_label">Department:</span>
					<span class="record_data"><?php echo $this->department ?></span>
				</div>
				<?php
			}
				
			if ($this->position != null) {
				?>
				<div>
					<span class="record_label">Position:</span>
					<span class="record_data"><?php echo $this->position ?></span>
				</div>
				<?php
			}
			
			echo '</section>';
		}
		
		echo '</fieldset>';
		
		// get list of orders handled by the agent
		$sqlQuery  = "SELECT COUNT(id) AS count FROM `order` WHERE agent_id = $this->id";
		$resultSet = self::$database->query($sqlQuery);
		$order     = self::$database->getResultRow($resultSet);
		
		if ((int) $order['count'] > 0) {
			?>
			<fieldset>
				<legend>Orders List</legend>
				<section id="order_list_section">
				</section>
			</fieldset>
			
			<script type="text/javascript">
			<!--
				ajax(null, 'order_list_section', 'innerHTML', 'Order::showList',
					 'criteria=all-orders&filterName=agent_id&filterValue=<?php echo $this->id ?>');
			// -->
			</script>
			<?php
		}
		
		// get list of purchases handled by the agent
		$sqlQuery  = "SELECT COUNT(id) AS count FROM purchase WHERE agent_id = $this->id";
		$resultSet = self::$database->query($sqlQuery);
		$order     = self::$database->getResultRow($resultSet);
		
		if ((int) $order['count'] > 0) {
			?>
			<fieldset>
				<legend>Purchases List</legend>
				<section id="purchase_list_section">
				</section>
			</fieldset>
			
			<script type="text/javascript">
			<!--
				ajax(null, 'purchase_list_section', 'innerHTML', 'Purchase::showList',
					 'criteria=all-purchases&filterName=agent_id&filterValue=<?php echo $this->id ?>');
			// -->
			</script><?php
		}
	}
}

?>

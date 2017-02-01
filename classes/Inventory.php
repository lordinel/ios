<?php
// note: this class requires scripts/inventory.js


// class definition for inventory
class Inventory extends Layout
{
	// load model list of selected brand, ajax function
	public static function loadModelList()
	{
		// check required parameters
		if ( !isset( $_POST['brandID'] ) || !isset( $_POST['class'] ) )
			return;


		if ( $_POST['brandID'] != 0 )
		{
			self::$database = new Database();
			if ( $_POST['class'] == "order" )
				$sqlQuery = "SELECT id, model, parent_id FROM inventory WHERE brand_id = '" . $_POST['brandID'] . "' ORDER BY model ASC";
			else					// purchase
				$sqlQuery = "SELECT id, model, parent_id FROM inventory WHERE brand_id = '" . $_POST['brandID'] . "' AND parent_id IS NULL ORDER BY model ASC";
			$resultSet = self::$database->query( $sqlQuery );
			
			if ( self::$database->getResultCount( $resultSet ) > 0 )
			{
				echo '<option value="0" selected="selected">-- select model --</option>';

				while( $model = self::$database->getResultRow( $resultSet ) )
				{
					if ( $_POST['class'] == "order" && $model['parent_id'] != null ) {
						echo '<option value="'.$model['id'].'" style="color:#AAAAAA">'.capitalizeWords($model['model']).'</option>';
					} else {
						echo '<option value="'.$model['id'].'">'.capitalizeWords($model['model']).'</option>';
					}
				}
			}
			else
				echo '<option value="null" selected="selected">-- no models available --</option>';
		}
		else
			echo '<option value="null" selected="selected">-- select brand first --</option>';
	}
	
	
	
	// load model list suggestion of selected brand, ajax function
	public static function loadModelListSuggestion()
	{
		// check required parameters
		if ( !isset( $_POST['brandID'] ) || !isset( $_POST['class'] ) || !isset( $_POST['row'] ) ) {
			return;
		}
		

		if ( $_POST['brandID'] != 0 )
		{
			self::$database = new Database();
			
			$sqlQuery = "SELECT name FROM inventory_brand WHERE id = " . $_POST['brandID'];
			$resultSet = self::$database->query( $sqlQuery );
			$brand = self::$database->getResultRow( $resultSet );
			
			echo '<form name="model_suggestion_form" id="model_suggestion_form" ' .
				 'onsubmit="return submitAutoSuggestModelDialog(' . $_POST['brandID'] . ',\'' . $_POST['class'] . '\',' . $_POST['row'] . ')" ' .
				 'action="javascript:hideDialog()" autocomplete="off">' . 
				 '<div><span class="record_label">Brand:</span>' .
				 '<span class="record_data">' . $brand['name'] . '</span>' .
				 '</div><br /><br />' .
				 '<div><span class="record_label"><label for="model_suggestion">Model:</label></span>' .
				 '<span class="record_data"><input type="text" name="model_suggestion" id="model_suggestion" required="required" autofocus="autofocus" size="45" maxlength="100" list="autosuggest_model" value="' . $_POST['defaultTextValue'] . '" /></span>' .
				 '</div><br /><br /><br />' .
				 '<div id="dialog_buttons">' .
				 '<input type="submit" value="OK" />' .
				 '<input type="button" value="Cancel" onclick="hideDialog()" />' .
				 '</div>' .
				 '</form>';
			
			
			if ( $_POST['class'] == "order" )
				$sqlQuery = "SELECT id, model FROM inventory WHERE brand_id = '" . $_POST['brandID'] . "' ORDER BY model ASC";
			else					// purchase
				$sqlQuery = "SELECT id, model FROM inventory WHERE brand_id = '" . $_POST['brandID'] . "' AND parent_id IS NULL ORDER BY model ASC";
			$resultSet = self::$database->query( $sqlQuery );
			
			echo '<datalist id="autosuggest_model">';
			
			if ( self::$database->getResultCount( $resultSet ) > 0 ) {
				while( $model = self::$database->getResultRow( $resultSet ) ) {
					echo '<option value="' . capitalizeWords( $model['model'] ) . '"></option>';
				}
			}
			
			echo '</datalist>';
		}
	}
	
	
	
	// get model ID for a given model name, ajax function
	public static function getModelID()
	{
		// check required parameters
		if ( !isset( $_POST['brandID'] ) || !isset( $_POST['class'] ) || !isset( $_POST['modelName'] ) ) {
			return;
		}
		

		if ( $_POST['brandID'] != 0 )
		{
			self::$database = new Database();
			
			if ( $_POST['class'] == "order" ) {
				$sqlQuery = "SELECT id AS model_id FROM inventory WHERE brand_id = '" . $_POST['brandID'] . "' AND model = '" . Filter::input( $_POST['modelName'] ) . "'";
			} else {				// purchase
				$sqlQuery = "SELECT id AS model_id FROM inventory WHERE brand_id = '" . $_POST['brandID'] .
							"' AND model = '" . Filter::input( $_POST['modelName'] ) .
							"' AND parent_id IS NULL";
			}
			$resultSet = self::$database->query( $sqlQuery );
			
			
			if ( self::$database->getResultCount( $resultSet ) != 1 ) {
				$inventory['model_id'] = 0;
			} else {
				$inventory = self::$database->getResultRow( $resultSet );
			}
			
			echo json_encode( $inventory );
		}
	}
	
	
	
	// get model ID for a given model name with exception, ajax function
	public static function getModelIdExcept()
	{
		// check required parameters
		if ( !isset( $_POST['brandID'] ) || !isset( $_POST['modelName'] ) || !isset( $_POST['modelIdException'] ) ) {
			return;
		}
		

		if ( $_POST['brandID'] != 0 )
		{
			self::$database = new Database();
			
			$sqlQuery = "SELECT id AS model_id FROM inventory WHERE brand_id = '" . $_POST['brandID'] .
						"' AND model = '" . Filter::input( $_POST['modelName'] ) .
						"' AND id != '" . $_POST['modelIdException'] .
						"' AND parent_id IS NULL";
			$resultSet = self::$database->query( $sqlQuery );
			
			
			if ( self::$database->getResultCount( $resultSet ) != 1 ) {
				$inventory['model_id'] = 0;
			} else {
				$inventory = self::$database->getResultRow( $resultSet );
			}
			
			echo json_encode( $inventory );
		}
	}



	// load selling price and available stock of selected item, ajax function
	public static function loadSellingPriceAndStock()
	{
		// check required parameters
		if ( !isset( $_POST['inventoryID'] ) || !isset( $_POST['orderID'] ) )
			return;


		if ( $_POST['inventoryID'] != 0 )
		{
			self::$database = new Database();
			//$resultSet = self::$database->query( "SELECT ROUND(selling_price,3) AS selling_price, IF(stock_count >= reserved_stock,stock_count - reserved_stock,0) AS available_stock, stock_count, reserved_stock FROM inventory WHERE id = " . $_POST['inventoryID'] );
			$resultSet = self::$database->query( "SELECT ROUND(selling_price,3) AS selling_price, stock_count AS available_stock, reserved_stock FROM inventory WHERE id = " . $_POST['inventoryID'] );
			$item = self::$database->getResultRow( $resultSet );
			
			/*if ( $_POST['orderID'] != 0 )
			{
				$resultSet = self::$database->query( "SELECT SUM(quantity) AS item_quantity FROM order_item WHERE order_id = " . $_POST['orderID'] . " AND inventory_id = " . $_POST['inventoryID'] . " GROUP BY inventory_id" );
				$quantity = self::$database->getResultRow( $resultSet );
				
				if ( $item['available_stock'] > 0 )
					$item['available_stock'] = $item['available_stock'] + $quantity['item_quantity'];
				elseif( $item['reserved_stock'] == $quantity['item_quantity'] )
					$item['available_stock'] = $item['stock_count'];
				elseif ( ( $item['reserved_stock'] - $quantity['item_quantity'] ) < $item['stock_count'] )
					$item['available_stock'] = $item['stock_count'] - ( $item['reserved_stock'] - $quantity['item_quantity'] );
			}*/
			
			echo json_encode( $item );
		}
		else
		{
			$item['selling_price'] = "0.000";
			$item['available_stock'] = "0";
			echo json_encode( $item );
		}
	}



	// load purchase price of selected item, ajax function
	public static function loadPurchasePrice()
	{
		// check required parameters
		if ( !isset( $_POST['inventoryID'] ) )
			return;


		if ( $_POST['inventoryID'] != 0 )
		{
			self::$database = new Database();
			$resultSet = self::$database->query( "SELECT ROUND(purchase_price,3) AS purchase_price FROM inventory WHERE id = " . $_POST['inventoryID'] );
			$item = self::$database->getResultRow( $resultSet );
			echo json_encode( $item );
		}
		else
		{
			$item['purchase_price'] = "0.000";
			echo json_encode( $item );
		}
	}



	// tasks for inventory brand list
	public static function showBrandListTasks()
	{
?>		<div id="tasks">
			<ul>
				<li id="task_add_brand"><a href="javascript:void(0)" onclick="showDialog('Add Brand','<?php self::showAddEditBrandDialog() ?>',
					'prompt'); $('#new_brand_name').focus()"><img src="images/task_buttons/add.png" />Add Brand...</a></li>
                <li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php

					// display confirmation to unclear order
					$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br />' .
									 'This may take a few minutes. The system might not respond to other users while processing your request.<br /><br /><br />';

					// Yes and No buttons
					$dialogMessage = $dialogMessage . '<div id="dialog_buttons">' .
													  '<input type="button" value="Yes" onclick="exportToExcelConfirm(' .
													  '\\\'data=inventory_list\\\')" />' .
													  '<input type="button" value="No" onclick="hideDialog()" />' .
													  '</div>';

					$dialogMessage = htmlentities( $dialogMessage );

					echo $dialogMessage;

				?>','prompt')"><img src="images/task_buttons/export_to_excel.png" />Export to Excel...</a></li>
			</ul>
		</div>
	</div>
<?php
	}



	// view brand list, ajax function
	public static function showBrandList()
	{
		// get parameters
		if ( !isset( $_POST['sortColumn'] ) ) {
			$sortColumn = "brand_name";
		} else {
			$sortColumn = $_POST['sortColumn'];
		}

		if ( !isset( $_POST['sortMethod'] ) ) {
			$sortMethod = "ASC";
		} else {
			$sortMethod = $_POST['sortMethod'];
		}

		if ( !isset( $_POST['page'] ) ) {
			$page = 1;
		} else {
			$page = $_POST['page'];
		}

		if ( !isset( $_POST['itemsPerPage'] ) ) {
			$itemsPerPage = ITEMS_PER_PAGE;
		} else {
			$itemsPerPage = $_POST['itemsPerPage'];
		}

		if ( !isset( $_POST['filterName'] ) ) {
			$filterName = null;
		} else {
			$filterName = $_POST['filterName'];
		}

		if ( !isset( $_POST['filterValue'] ) ) {
			$filterValue = null;
		} else {
			$filterValue = $_POST['filterValue'];
		}

		$offset = ( $page * $itemsPerPage ) - $itemsPerPage;


		// first letter filter
		if ( $filterName == 'alpha' && $filterValue != null ) {
			if ( $filterValue == '#' ) {
				$condition = "WHERE name NOT RLIKE '^[A-Z]' ";
			} else {
				$condition = "WHERE name LIKE '" . $filterValue . "%' ";
			}
		} else {
			$condition = "";
		}


		// count results prior to main query
		$sqlQuery 		= "SELECT COUNT(*) AS count FROM inventory_brand " . $condition;
		self::$database = new Database;
		$resultSet 		= self::$database->query( $sqlQuery );
		$resultCount 	= self::$database->getResultRow( $resultSet );
		$resultCount 	= $resultCount['count'];


		// select inventory brands
		$sqlQuery = "SELECT inventory_brand.id AS brand_id, " .
					"inventory_brand.name AS brand_name, " .
					"COUNT(inventory.id) AS model_count, " .
					"SUM(IF(stock_count IS NOT NULL,stock_count,0)) AS stock_count, " .
					"SUM(purchase_price*stock_count) AS stock_amount, " .
					"SUM(selling_price*stock_count) AS expected_sales, " .
					"SUM(selling_price*stock_count)-SUM(purchase_price*stock_count) AS expected_income, " .
					"SUM(IF(reserved_stock IS NOT NULL,reserved_stock,0)) AS reserved_stock " .
					"FROM inventory_brand " .
					"LEFT JOIN inventory ON inventory_brand.id = inventory.brand_id " .
					$condition .
					"GROUP BY inventory_brand.id " .
					"ORDER BY " . $sortColumn . " " . $sortMethod . " " .
					"LIMIT " . $offset . "," . $itemsPerPage;
		$resultSet = self::$database->query( $sqlQuery );
		if ( !self::$database->getResultCount( $resultSet ) ) {
			echo '<div>No brands found.</div>';
			return;
		}

		$columns = array(
			'brand_name' 		 => 'Brand Name',
			'model_count'		 => 'No. of Models',
			'stock_count' 		 => 'Total Available Stocks',
			'stock_amount' 		 => 'Stock Amount ('.CURRENCY.')<br /><span class="subheader">(Purchase Price x Available Stocks)</span>',
			'expected_sales' 	 => 'Expected Sales ('.CURRENCY.')<br /><span class="subheader">(Selling Price x Available Stocks)</span>',
			'expected_income' 	 => 'Expected Profit ('.CURRENCY.')<br /><span class="subheader">(Expected Sales - Stock Amount)</span>',
			'reserved_stock' 	 => 'Demand'
		);

		self::showListHeader( $columns, 'brand_list_section', 'Inventory::showBrandList', null, $sortColumn, $sortMethod, $filterName, $filterValue );

		// display content
		while ( $brand = self::$database->getResultRow( $resultSet ) ) {
			echo '<tr>';

			// brand name
			echo '<td><span class="long_text_clip"><a href="list_inventory_models.php?brandID=' . $brand['brand_id'] .
				 '" title="' . capitalizeWords( Filter::output( $brand['brand_name'] ) ) . '">' .
				 capitalizeWords( Filter::output( $brand['brand_name'] ) ) . '</a></span></td>';

			// no. of models
			echo '<td class="number">';
			if ( $brand['model_count'] == 0 ) {
				echo '<span class="bad">';
			} else {
				echo '<span>';
			}
			echo $brand['model_count'] . '</span></td>';

			// available stock
			echo '<td class="number">';
			if ( $brand['stock_count'] == 0 ) {
				echo '<span class="bad">';
			} else {
				echo '<span>';
			}
			echo $brand['stock_count'] . '</span></td>';

			// stock amount
			echo '<td class="number">';
			if ( $brand['stock_amount'] == 0 ) {
				echo '<span class="bad">';
			} else {
				echo '<span>';
			}
			echo numberFormat( $brand['stock_amount'], 'float' ) . '</span></td>';

			// expected sales
			echo '<td class="number">';
			if ( $brand['expected_sales'] == 0 ) {
				echo '<span class="bad">';
			} else {
				echo '<span>';
			}
			echo numberFormat( $brand['expected_sales'], 'float' ) . '</span></td>';

			// expected profit
			echo '<td class="number">';
			if ( $brand['expected_income'] <= 0 ) {
				echo '<span class="bad">';
			} else {
				echo '<span>';
			}
			echo numberFormat( $brand['expected_income'], 'float' ) . '</span></td>';

			// reserved stock
			echo '<td class="number">';
			if ( $brand['reserved_stock'] == 0 ) {
				echo '<span class="bad">';
			} else {
				echo '<span>';
			}
			echo $brand['reserved_stock'] . '</span></td>';

			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';


		self::showPagination( $page, $itemsPerPage, $resultCount, 'brand_list_section', 'Inventory::showBrandList', null, $sortColumn, $sortMethod, $filterName, $filterValue );
	}



	// tasks for inventory model list
	public static function showModelListTasks( $brandID = null, $brandName = null )
	{
		if ( $brandID == null && $brandName == null ) {
			if ( isset( $_POST['brandID'] ) ) {
				$brandID = $_POST['brandID'];
			} else {
				return;
			}
			if ( isset( $_POST['brandName'] ) ) {
				$brandName = $_POST['brandName'];
			} else {
				return;
			}
		}

		?><div id="tasks">
		<ul>
			<li id="task_add_brand"><a href="javascript:void(0)" onclick="showDialog('Add Model for <?php echo capitalizeWords( Filter::reinputToJS( $brandName ) ) ?>',
				'<?php self::showAddEditModelDialog( $brandID ) ?>','prompt'); $('#new_model_name').focus()"><img src="images/task_buttons/add.png" />Add Model...</a></li>
			<li id="task_edit_brand"><a href="javascript:void(0)" onclick="showDialog('Edit Brand','<?php self::showAddEditBrandDialog( $brandID, $brandName ) ?>',
				'prompt'); $('#new_brand_name').select()"><img src="images/task_buttons/edit.png" />Change Brand Name...</a></li>
			<li id="task_delete_brand"><a href="javascript:void(0)" onclick="showDialog('Delete Brand','<?php
				// display confirmation to delete brand
				$dialogMessage = "Delete <b>" . capitalizeWords( Filter::reinputToJS( $brandName ) ) . "</b> and all its associated models?<br /><br />" .
								 "Note: this will succeed only if no order is having this brand.<br /><br /><br />" .
								 "<div id=\"dialog_buttons\">" .
								 "<input type=\"button\" value=\"Yes\" onclick=\"deleteBrandCommit(\'" . $brandID . "\', \'" . addslashes( capitalizeWords( Filter::reinputToJS( $brandName ) ) ) . "\')\" />" .
								 "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />" .
								 "</div>";

				$dialogMessage = htmlentities( $dialogMessage );

				echo $dialogMessage;

				?>','warning')"><img src="images/task_buttons/delete.png" />Delete Brand...</a></li>
			<li id="task_export"><a href="javascript:void(0)" onclick="showDialog('Export to Excel','<?php

				// display confirmation to unclear order
				$dialogMessage = 'Do you want to export this page to an Excel file?<br /><br />' .
					'This may take a few minutes. The system might not respond to other users while processing your request.<br /><br /><br />';

				// Yes and No buttons
				$dialogMessage = $dialogMessage . '<div id="dialog_buttons">' .
					'<input type="button" value="Yes" onclick="exportToExcelConfirm(' .
					'\\\'data=inventory_list&brandID=' . $brandID . '\\\')" />' .
					'<input type="button" value="No" onclick="hideDialog()" />' .
					'</div>';

				$dialogMessage = htmlentities( $dialogMessage );

				echo $dialogMessage;

				?>','prompt')"><img src="images/task_buttons/export_to_excel.png" />Export to Excel...</a></li>
			<li id="task_back_to_list"><a href="list_inventory.php"><img src="images/task_buttons/back_to_list.png" />Back to List</a></li>
		</ul>
	</div>
	<span style="visibility: hidden" id="formatted_new_brand_name"><?php echo capitalizeWords( Filter::output( $brandName ) ); ?></php></span>
	<?php
	}


	// view model list for selected brand
	public static function showModelList()
	{
		// check required parameters
		if ( !isset( $_POST['criteria'] ) ) {		// criteria is the brand ID
			return;
		}

		$brandID = $_POST['criteria'];

		// get parameters
		if ( !isset( $_POST['sortColumn'] ) || !isset( $_POST['sortMethod'] ) ) {
			// default sorting
			$sortColumn = "model";
			$sortMethod = "ASC";
		} else {
			// get parameter values for sorting
			$sortColumn = $_POST['sortColumn'];
			$sortMethod = $_POST['sortMethod'];
		}
		
		if ( !isset( $_POST['page'] ) || !isset( $_POST['itemsPerPage'] ) ) {
			$page = 1;
			$itemsPerPage = ITEMS_PER_PAGE;
		} else {
			$page = $_POST['page'];
			$itemsPerPage = $_POST['itemsPerPage'];
		}
		
		$offset = ( $page * $itemsPerPage ) - $itemsPerPage;
		
		self::$database = new Database();
		$brandName = capitalizeWords( self::getBrandName( $brandID ) );


		// count number of models
		$sqlQuery = 'SELECT COUNT(id) AS count FROM inventory WHERE inventory.brand_id = ' . $brandID;
		$resultSet = self::$database->query( $sqlQuery );
		$inventoryBrand = self::$database->getResultRow( $resultSet );
		$resultCount = $inventoryBrand['count'];


		// select inventory models
		$sqlQuery = "SELECT *, selling_price-purchase_price AS profit_margin FROM inventory " .
					"WHERE inventory.brand_id = " . $brandID . " " .
					"ORDER BY " . $sortColumn . " " . $sortMethod . " " .
					"LIMIT " . $offset . "," . $itemsPerPage;
		

		$resultSet = self::$database->query( $sqlQuery );

		if ( self::$database->getResultCount( $resultSet ) == 0 ) {
			echo "<div>No models found for this brand.</div>";
			return;
		}

		$columns = array(
			'model' => 'Model Name',
			'description' => 'Description',
			'purchase_price' => 'Purchase Price (' . CURRENCY . ')',
			'selling_price' => 'Selling Price (' . CURRENCY . ')',
			'profit_margin' => 'Profit Margin (' . CURRENCY . ')',
			'stock_count' => 'Available Stocks',
			'reserved_stock' => 'Demand'
		);


		/*if ( $itemsPerPage > 20 )
			self::showModelButtonSet( $brandID, $brandName, $page, $itemsPerPage, $resultCount, $sortColumn, $sortMethod );
		else
			echo '<div><br /></div>';*/


		self::showListHeader( $columns, 'model_list_' . $brandID, 'Inventory::showModelList', $brandID, $sortColumn, $sortMethod );


		while ( $model = self::$database->getResultRow( $resultSet ) )
		{
			echo "<tr>";
			?><td><span class="long_text_clip">
			<a href="javascript:void(0)" onclick="showDialog('Edit Model','<?php self::showAddEditModelDialog( $brandID, $model['id'],
				capitalizeWords( Filter::reinputToJS( $model['model'] ) ),
				Filter::reinputToJS( $model['description'] ),
				numberFormat( $model['purchase_price'], "float", 3, '.', '', true ),
				numberFormat( $model['selling_price'], "float", 3, '.', '', true ),
				$model['stock_count'],
				$model['parent_id'] ) ?>','prompt')" title="<?php echo capitalizeWords( Filter::output( $model['model'] ) ) ?>">
				<?php echo capitalizeWords( Filter::output( $model['model'] ) ) ?>
			</a></span></td>
			<td><span class="long_text_clip"><?php echo Filter::output( $model['description'] ) ?></span></td><?php

			if ( $model['purchase_price'] <= 0 ) {
				echo '<td class="number"><span class="bad">' . numberFormat( $model['purchase_price'], "float" ) . '</span></td>';
			} else {
				echo '<td class="number"><span>' . numberFormat( $model['purchase_price'], "float" ) . '</span></td>';
			}

			if ( $model['selling_price'] <= 0 ) {
				echo '<td class="number"><span class="bad">' . numberFormat( $model['selling_price'], "float" ) . '</span></td>';
			} else {
				echo '<td class="number"><span>' . numberFormat( $model['selling_price'], "float" ) . '</span></td>';
			}

			if ( $model['profit_margin'] <= 0 ) {
				echo '<td class="number"><span class="bad">' . numberFormat( $model['profit_margin'], "float" ) . '</span></td>';
			} else {
				echo '<td class="number"><span>' . numberFormat( $model['profit_margin'], "float" ) . '</span></td>';
			}

			if ( $model['stock_count'] <= 0 ) {
				echo '<td class="number"><span class="bad">' . numberFormat( $model['stock_count'], "int" ) . '</span></td>';
			} else {
				echo '<td class="number"><span>' . numberFormat( $model['stock_count'], "int" ) . '</span></td>';
			}

			if ( $model['reserved_stock'] <= 0 ) {
				echo '<td class="number"><span class="bad">' . numberFormat( $model['reserved_stock'], "int" ) . '</span></td>';
			} else {
				echo '<td class="number"><span>' . numberFormat( $model['reserved_stock'], "int" ) . '</span></td>';
			}

			echo "		</tr>\n";
		}

		echo "	</tbody>\n";
		echo "</table>\n";

		//self::showModelButtonSet( $brandID, $brandName, $page, $itemsPerPage, $resultCount, $sortColumn, $sortMethod );

		self::showPagination( $page, $itemsPerPage, $resultCount, 'model_list_' . $brandID, 'Inventory::showModelList', $brandID, $sortColumn, $sortMethod );
	}


	// view totals for selected brand
	public static function showModelListTotals( $brandID )
	{
		if ( !self::$database ) {
			self::$database = new Database();
		}

		// statistics
		$sqlQuery = "SELECT SUM(stock_count) AS stock_count, " .
					"SUM(purchase_price*stock_count) AS stock_amount, " .
					"SUM(selling_price*stock_count) AS expected_sales, " .
					"SUM(selling_price*stock_count)-SUM(purchase_price*stock_count) AS expected_income, " .
					"SUM(reserved_stock) AS reserved_stock " .
					"FROM inventory " .
					"WHERE inventory.brand_id = " . $brandID;
		$resultSet = self::$database->query( $sqlQuery );
		$inventoryBrand = self::$database->getResultRow( $resultSet );

		echo '<fieldset><legend>Totals</legend><section><div>' .
			 '<span class="record_label">Total Available Stocks:</span>' .
			 '<span class="record_data">' . numberFormat( $inventoryBrand['stock_count'], "int" ) . '</span>' .
			 '</div><div>' .
			 '<span class="record_label">Total Stock Amount:<br><span style="font-size: 75%">(Purchase Price x Available Stocks)</span></span>' .
			 '<span class="record_data">' . numberFormat( $inventoryBrand['stock_amount'], "currency" ) . '</span>' .
			 '</div><div>' .
			 '<span class="record_label">Expected Sales:<br><span style="font-size: 75%">(Selling Price x Available Stocks)</span></span>' .
			 '<span class="record_data">' . numberFormat( $inventoryBrand['expected_sales'], "currency" ) . '</span>' .
			 '</div><div>' .
			 '<span class="record_label">Expected Profit:<br><span style="font-size: 75%">(Expected Sales - Stock Amount)</span></span>' .
			 '<span class="record_data">' . numberFormat( $inventoryBrand['expected_income'], "currency" ) . '</span>' .
			 '</div><div>' .
			 '<span class="record_label">Total Demand:</span>' .
			 '<span class="record_data">' . numberFormat( $inventoryBrand['reserved_stock'], "int" ) . '</span><br/><br/>' .
			 '</div></section></fieldset>';
	}
	
	
	// display inventory model button set
	private static function showModelButtonSet( $brandID, $brandName, $page = null, $itemsPerPage = null, $resultCount = null, $sortColumn = null, $sortMethod = null )
	{
?>		<div id="dialog_buttons" class="inventory_buttons">
			<input type="button" value="Add Model" onclick="showDialog('Add Model for <?php echo Filter::reinputToJS( $brandName ) ?>','<?php self::showAddEditModelDialog( $brandID ) ?>','prompt')" />
			<input type="button" value="Edit Brand" onclick="showDialog('Edit Brand','<?php self::showAddEditBrandDialog( $brandID, Filter::reinputToJS( $brandName ) ) ?>','prompt')" />
			<input type="button" value="Delete Brand" onclick="showDialog('Delete Brand','<?php

					// display confirmation to mark order as delivered
					$dialogMessage = "Delete <b>" . Filter::reinputToJS( $brandName ) . "</b> and all its associated models?<br /><br />";
					$dialogMessage = $dialogMessage . "Note: this will succeed only if no order is having this brand.<br /><br /><br />";

					// Yes and No buttons
					$dialogMessage = $dialogMessage . "<div id=\"dialog_buttons\">";
					$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"Yes\" onclick=\"deleteBrandCommit(\'" . $brandID . "\', \'" . $brandName . "\')\" />";
					$dialogMessage = $dialogMessage . "<input type=\"button\" value=\"No\" onclick=\"hideDialog()\" />";
					$dialogMessage = $dialogMessage . "</div>";

					$dialogMessage = htmlentities( $dialogMessage );

					echo $dialogMessage;

			?>','warning')" />
		</div>
<?php
	}


	// display add/edit brand dialog
	private static function showAddEditBrandDialog( $brandID=NULL, $brandName=NULL )
	{
		$dialogMessage = "<form name=\"add_edit_brand\" method=\"post\" action=\"javascript:" .
					 	 ( $brandID == NULL ? "addBrandCommit()" : "editBrandCommit()" ) . "\" autocomplete=\"off\">" .
						 ( $brandID != NULL ? "<input type=\"hidden\" name=\"brand_id\" id=\"brand_id\" value=\"" . $brandID . "\" />" .
							"<input type=\"hidden\" name=\"old_brand_name\" id=\"old_brand_name\" value=\"" .
							capitalizeWords( Filter::reinputToJS( $brandName ) ) . "\" />" : "" ) .
						 "<label for=\"new_brand_name\">Brand Name: </label>" .
						 "<input type=\"text\" name=\"new_brand_name\" id=\"new_brand_name\" size=\"40\" maxlength=\"100\" autofocus=\"autofocus\" required=\"required\"" .
						 	( $brandName != NULL ? " value=\"" . capitalizeWords( Filter::reinputToJS( $brandName ) ) . "\"" : "" ) . " />" .
						 "<br /><br /><br /><br /><br />";

		// Yes and No buttons
		$dialogMessage = $dialogMessage . "<div id=\"dialog_buttons\">" .
										  "<input type=\"submit\" value=\"Save\" />" .
										  "<input type=\"reset\" value=\"Reset\" />" .
										  "<input type=\"button\" value=\"Cancel\" onclick=\"hideDialog()\" />" .
										  "</div></form>";

		$dialogMessage = htmlentities( $dialogMessage );

		echo $dialogMessage;
	}



	// display add/edit model dialog
	private static function showAddEditModelDialog( $brandID, $modelID=NULL, $modelName=NULL, $modelDescription=NULL,
												$rawPrice="0.000", $sellingPrice="0.000", $stockCount=0, $parentID = null )
	{
		if ( $parentID == null ) {
            // form
			$dialogMessage = '<form name="add_edit_model" method="post" action="javascript:'.
                             ($modelID == null ? 'addModelCommit()' : 'editModelCommit()').'" autocomplete="off">'.
                             
                             // brand ID (hidden)
							 '<div><input type="hidden" name="brand_id" id="brand_id" value="'.$brandID.'" />'.
                             
                             // model ID (hidden)
							 ($modelID != null ? '<input type="hidden" name="model_id" id="model_id" value="'.$modelID.'" />' : '').
                             
                             // model name
							 '<span class="record_label">Model Name:</span><span class="record_data">'.
                             '<input type="text" name="new_model_name" id="new_model_name" size="30" maxlength="100" autofocus="autofocus" '.
                                'required="required"'.($modelName != null ? ' value="'.$modelName.'"' : '').' /></span>'.
                             
                             // description
							 '<span class="record_label">Description:</span><span class="record_data">'.
							 '<input type="text" name="new_model_description" id="new_model_description" size="30" maxlength="255"'.
                                ($modelDescription != null ? ' value="'.$modelDescription.'"' : '').' /></span>'.

                             // purchase price
                             '<span class="record_label">Purchase Price ('.CURRENCY.'):</span><span class="record_data">'.
                             '<input type="text" name="new_model_purchase_price" id="new_model_purchase_price" class="number" value="'.$rawPrice.'" '.
                             'required="required" onclick="data.selectField($(this),\'float\')" onblur="data.validateField($(this),\'float\')" /></span>'.

                             // selling price
                             '<span class="record_label">Selling Price ('.CURRENCY.'):</span><span class="record_data">'.
                             '<input type="text" name="new_model_selling_price" id="new_model_selling_price" class="number" value="'.$sellingPrice.'" '.
                             'required="required" onclick="data.selectField($(this),\'float\')" '.
                             'onblur="data.validateField($(this),\'float\')" /></span>'.

                             // no. of stocks
                             '<span class="record_label">No. of Stocks:</span><span class="record_data">'.
                             '<input type="text" name="new_model_stock_count" id="new_model_stock_count" class="number" value="'.$stockCount.'" '.
                             'required="required" onclick="data.selectField($(this),\'int\')" onblur="data.validateField($(this),\'int\')" />'.

                             // links
                             ($modelID != null ? '<br /><br /><a href="javascript:void(0)" onclick="redirect(\'purchase_supplies.php\',\'id\',\''.
                                                 $modelID . '\')">Purchase Supplies</a>'.
                                                 '<br /><a href="list_inventory_orders.php?inv-id='.$modelID . '" target="top")">View Orders »</a> '.
                                                 '<br /><a href="list_inventory_purchases.php?inv-id='.$modelID . '" target="top")">View Purchases »</a> '
                                                 : '').'</span>'.

                             '<br /><br /><br /><br /><br /><br /><br /><br /><br /></div>'.
	
			                 // Yes and No buttons
			                 '<div id="dialog_buttons">'.
			                 '<input type="submit" value="Save" />'.
			                 '<input type="reset" value="Reset" />'.
			                 '<input type="button" value="Cancel" onclick="hideDialog()" />'.
                             
                             // Delete Model button
			                ($modelID != null ? '<input type="button" value="Delete Model..." '.
                                'onclick="deleteModelConfirm(\''.$brandID.'\',\''.$modelID.'\',\''.addslashes($modelName).'\')" style="margin-left: 50px" />'
                                : '');
		} else {
			$dialogMessage = '<b>Notice:</b> You cannot edit the info of an inventory for old stocks.<br /><br />'.

							 ($modelID != null ?
								 '<br /><a href="list_inventory_orders.php?inv-id='.$modelID . '" target="top")">View Orders »</a>'.
								 '<br /><a href="list_inventory_purchases.php?inv-id='.$modelID . '" target="top")">View Purchases »</a><br /><br />'
								 : '').'</span>'.
							 
			                 '<div id="dialog_buttons"><input type="button" value="OK" onclick="hideDialog()" />'.
							 
			                 ($modelID != null ? '<input type="button" value="Delete Model..." '.
                                'onclick="deleteModelConfirm(\''.$brandID.'\',\''.$modelID.'\',\''.addslashes($modelName).'\')" style="margin-left: 220px" />'
                                 : '');
		}

		$dialogMessage = $dialogMessage.'</div></form>';
		$dialogMessage = addslashes( htmlentities($dialogMessage) );
		echo $dialogMessage;
	}

	
	
	// display delete model dialog, ajax function
	public static function showDeleteModelDialog()
	{
		// check required parameters
		if ( !isset( $_POST['brandID'] ) || !isset( $_POST['modelID'] ) || !isset( $_POST['modelName'] ) ) {
			return;
		}
		
		self::$database = new Database();
		
		// determine number of stocks remaining
		$sqlQuery = "SELECT stock_count FROM inventory WHERE id = " . $_POST['modelID'];
		$resultSet = self::$database->query( $sqlQuery );
		$model = self::$database->getResultRow( $resultSet );
		if ( $model['stock_count'] > 0 ) {
			echo '<b>Notice:</b> You cannot delete an inventory with remaining stocks.<br /><br />' .
				 'Clear all remaining stocks first then try again.<br /><br /><br />' .
				 '<div id="dialog_buttons"><form>' .
				 '<input type="button" value="OK" onclick="hideDialog()" />' .
				 '</form></div>';
			return;
		}
		
		$sqlQuery = "SELECT id, model FROM inventory " .
					"WHERE brand_id = '" . $_POST['brandID'] . "' AND id != '" . $_POST['modelID'] . "' AND parent_id IS NULL ORDER BY model ASC";
		$resultSet = self::$database->query( $sqlQuery );
		
		
		echo 'Delete <b>' . Filter::output( $_POST['modelName'] ) . '</b>?<br /><br />' .
			 'Note: this will succeed only if no order is having this model.<br /><br />';
		
		
		$resultCount = self::$database->getResultCount( $resultSet );
		if ( $resultCount > 0 ) {
			echo '<hr /><br />' .
				 '<form name="model_suggestion_form" id="model_suggestion_form" autocomplete="off">' . 
				 '<input type="checkbox" name="mark_as_duplicate" id="mark_as_duplicate" onchange="toggleDuplicateModelInput()" /> ' .
				 '<label for="mark_as_duplicate">Mark as duplicate of:</label><br /><br />' .
				 '<div><span class="record_label"><label for="duplicate_inventory">Model:</label></span>' .
				 '<span class="record_data"><input type="text" name="duplicate_inventory" id="duplicate_inventory" size="45" maxlength="100" list="autosuggest_model" disabled="disabled" /></span>' .
				 '</div><br />';
			
			echo '<datalist id="autosuggest_model">';
			while( $model = self::$database->getResultRow( $resultSet ) ) {
				echo '<option value="' . capitalizeWords( Filter::output( $model['model'] ) ) . '"></option>';
			}
			echo '</datalist>';
		}
		
		echo '<div id="dialog_buttons"><br />' .
			 '<input type="button" value="Yes" onclick="deleteModelCommit(' . $resultCount . ',\'' . $_POST['brandID'] . '\',\'' . $_POST['modelID'] . '\',\'' . addslashes( Filter::output( $_POST['modelName'] ) ) . '\')" />' .
			 '<input type="button" value="No" onclick="hideDialog()" />' .
			 '</div></form>';
	}


	// add new brand, ajax function
	public static function addBrand()
	{
		// check required parameters
		if ( !isset( $_POST['newBrandName'] ) ) {
			return;
		}

		self::$database = new Database();
		
		
		// check if brand name already exists
		$sqlQuery = "SELECT * FROM inventory_brand WHERE name = '" . Filter::input( $_POST['newBrandName'] ) . "'";
		$resultSet = self::$database->query( $sqlQuery );
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			echo "<b>Error:</b> Cannot add <b>" . capitalizeWords( $_POST['newBrandName'] )  . "</b><br /><br />";
			echo "Ensure that the new brand name is not the same as the existing brands.<br /><br />";
			echo "Please verify and try again.";
			return;
		}


		// select inventory models
		$sqlQuery = "INSERT INTO inventory_brand VALUES (" .
					"NULL,'" .											// id, auto-generate
					Filter::input( $_POST['newBrandName'] ) . "')";		// name

		if ( self::$database->query( $sqlQuery ) ) {
			$brandID = self::$database->getLastInsertID();
			
			// log event
			EventLog::addEntry( self::$database, 'info', 'inventory_brand', 'insert', 'new',
								'<span class="event_log_main_record_inline"><a href="list_inventory_models.php?brandID=' . $brandID . '">' .
								capitalizeWords( htmlentities( $_POST['newBrandName'] ) ) .
								'</a></span> was <span class="event_log_action">added</span> to ' .
								'<a href="list_inventory.php">Inventory Brands</a>' );
			
			echo "<b>" . capitalizeWords( htmlentities( $_POST['newBrandName'] ) ) . "</b> successfully added to Brand list!<br /><br />Redirecting to model list...<br />";
			?><form action="javascript:void()">
				<input type="hidden" name="brand_id" id="brand_id" value="<?php echo $brandID; ?>" />
			</form><?php
		}
	}



	// edit brand, ajax function
	public static function editBrand()
	{
		// check required parameters
		if ( !isset( $_POST['brandID'] ) || !isset( $_POST['oldBrandName'] ) || !isset( $_POST['newBrandName'] ) )
			return;

		self::$database = new Database();
		
		
		// check if brand name already exists
		$sqlQuery = "SELECT * FROM inventory_brand WHERE name = '" . Filter::input( $_POST['newBrandName'] ) . "'";
		$resultSet = self::$database->query( $sqlQuery );
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			echo "<b>Error:</b> Cannot set <b>" . capitalizeWords( htmlentities( $_POST['newBrandName'] ) )  . "</b> as new brand name for <b>" . htmlentities( $_POST['oldBrandName'] )  . "</b><br /><br />";
			echo "Ensure that the new brand name is not the same as the existing brands<br /><br />";
			echo "Please verify and try again.";
			return;
		}


		// save brand info
		$sqlQuery = "UPDATE inventory_brand SET " .
					"name = '" . Filter::input( $_POST['newBrandName'] ) . "' " .		// name
					"WHERE id = " . $_POST['brandID'];

		if ( self::$database->query( $sqlQuery ) ) {
			// log event
			EventLog::addEntry( self::$database, 'info', 'inventory_brand', 'update', 'modified',
								capitalizeWords( htmlentities( $_POST['oldBrandName'] ) ) . ' brand was <span class="event_log_action">renamed</span> to ' .
								'<span class="event_log_main_record_inline"><a href="list_inventory_models.php?brandID=' . $_POST['brandID'] . '">' .
								capitalizeWords( htmlentities( $_POST['newBrandName'] ) ) . '</a></span>' );
			
			echo capitalizeWords( htmlentities( $_POST['oldBrandName'] ) ) . " brand was renamed to <b>" . capitalizeWords( htmlentities( $_POST['newBrandName'] ) ) . "</b> successfully!<br /><br /><br />";
		}
	}



	// delete brand, ajax function
	public static function deleteBrand()
	{
		// check required parameters
		if ( !isset( $_POST['brandID'] ) || !isset( $_POST['brandName'] ) )
			return;

		self::$database = new Database();


		// select inventory models
		$sqlQuery = "DELETE FROM inventory_brand WHERE id =" . $_POST['brandID'];

		if ( self::$database->query( $sqlQuery ) ) {
			// log event
			EventLog::addEntry( self::$database, 'warning', 'inventory_brand', 'delete', 'removed',
								'<span class="event_log_main_record_inline">' . capitalizeWords( htmlentities( $_POST['brandName'] ) ) .
								'</span> was <span class="event_log_action bad">deleted</span> from ' .
								'<a href="list_inventory.php">Inventory Brands</a>' );
			
			echo "<b>" . capitalizeWords( htmlentities( $_POST['brandName'] ) ) . "</b> has been deleted from brand list!<br /><br />Redirecting to brand 
			list...<br />";
		} else {
			echo "<b>Error:</b> Cannot delete " . capitalizeWords( htmlentities( $_POST['brandName'] ) )  . " to brand list<br /><br />";
			echo "It is possible that this brand is currently in use in some orders.<br />";
			echo "Please verify and try again.";
		}
	}



	// add new model, ajax function
	public static function addModel()
	{
		// check required parameters
		if ( !isset( $_POST['brandID'] ) || !isset( $_POST['newModelName'] ) ||
			 !isset( $_POST['newModelDescription'] ) || !isset( $_POST['newModelPurchasePrice'] ) ||
			 !isset( $_POST['newModelSellingPrice'] ) || !isset( $_POST['newModelStockCount'] ) )
			return;
		
		
		self::$database = new Database();
		
		
		// check if model name already exists under the same brand
		$sqlQuery = "SELECT * FROM inventory WHERE model = '" . Filter::input( $_POST['newModelName'] ) . "' AND brand_id = " . $_POST['brandID'];
		$resultSet = self::$database->query( $sqlQuery );
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			echo "<b>Error:</b> Cannot add <b>" . capitalizeWords( htmlentities( $_POST['newModelName'] ) )  . "</b><br /><br />";
			echo "Ensure that the new model name is not the same as any of the existing model names within the same brand.<br /><br />";
			echo "Please verify and try again.";
			return;
		}
		
		
		// filter input
		if ( empty( $_POST['newModelDescription'] ) )
			$_POST['newModelDescription'] = "NULL";
		else
			$_POST['newModelDescription'] = "'" . Filter::input( $_POST['newModelDescription'] ) . "'";
		

		// save new model
		$sqlQuery = "INSERT INTO inventory VALUES (" .
					"NULL," .														// id, auto-generate
					$_POST['brandID'] . ",'" .										// brand_id
					Filter::input( $_POST['newModelName'] ) . "'," .				// model
					$_POST['newModelDescription'] . "," .							// description
					Filter::input( $_POST['newModelPurchasePrice'] ) . "," .		// purchase_price
					Filter::input( $_POST['newModelSellingPrice'] ) . "," .			// selling_price
					Filter::input( $_POST['newModelStockCount'] ) . "," .			// stock_count
					"0," .															// reserved_stock, set initially to zero
					"NULL)";														// parent_id

		if ( self::$database->query( $sqlQuery ) ) {
			$modelID = self::$database->getLastInsertID();
			
			$sqlQuery = 'SELECT name FROM inventory_brand WHERE id=' . $_POST['brandID'];
			$resultSet = self::$database->query( $sqlQuery );
			$inventory = self::$database->getResultRow( $resultSet );
			
			// log event
			EventLog::addEntry( self::$database, 'info', 'inventory', 'insert', 'new',
								'<span class="event_log_main_record_inline">' . capitalizeWords( htmlentities( $_POST['newModelName'] ) ) .
								'</span> model was <span class="event_log_action">added</span> to ' .
								'<a href="list_inventory_models.php?brandID=' . $_POST['brandID'] . '">' .
								capitalizeWords( Filter::output( $inventory['name'] ) ) . '</a> brand' );
			
			echo "<b>" . capitalizeWords( htmlentities( $_POST['newModelName'] ) ) . "</b> successfully added!<br /><br /><br />";
		}
	}



	// add new model, ajax function
	public static function editModel()
	{
		// check required parameters
		if ( !isset( $_POST['modelID'] ) || !isset( $_POST['newModelName'] ) ||
			 !isset( $_POST['newModelDescription'] ) || !isset( $_POST['newModelPurchasePrice'] ) ||
			 !isset( $_POST['newModelSellingPrice'] ) || !isset( $_POST['newModelStockCount'] ) ) {
			return;
		}


		self::$database = new Database();
		
		
		// check if model name already exists under the same brand
		$sqlQuery = "SELECT * FROM inventory WHERE " .
					"id != " . $_POST['modelID'] . " AND " .
					"model = '" . Filter::input( $_POST['newModelName'] ) . "' AND " .
					"brand_id = (SELECT brand_id FROM inventory WHERE id = " . $_POST['modelID'] . ")";
		$resultSet = self::$database->query( $sqlQuery );
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			echo "<b>Error:</b> Cannot update <b>" . capitalizeWords( htmlentities( $_POST['newModelName'] ) )  . "</b><br /><br />";
			echo "Ensure that the new model name is not the same as any of the existing model names within the same brand.<br /><br />";
			echo "Please verify and try again.";
			return;
		}
		
		
		// filter input
		if ( empty( $_POST['newModelDescription'] ) )
			$_POST['newModelDescription'] = "NULL";
		else
			$_POST['newModelDescription'] = "'" . Filter::input( $_POST['newModelDescription'] ) . "'";


		// save new model
		$sqlQuery = "UPDATE inventory SET " .
					"model = '" . Filter::input( $_POST['newModelName'] ) . "', " .		                // model
					"description = " . $_POST['newModelDescription'] . ", " .			                // description
					"purchase_price = " . Filter::input( $_POST['newModelPurchasePrice'] ) . ", " .		// purchase_price
					"selling_price = " . Filter::input( $_POST['newModelSellingPrice'] ) . ", " .		// selling_price
					"stock_count = " . Filter::input( $_POST['newModelStockCount'] ) . 				    // stock_count
					" WHERE id = " . $_POST['modelID'];

		if ( self::$database->query( $sqlQuery ) ) {
			// log event
			$sqlQuery = 'SELECT id AS brand_id, name FROM inventory_brand WHERE id=' .
						'(SELECT brand_id FROM inventory WHERE id = ' . $_POST['modelID'] . ')';
			$resultSet = self::$database->query( $sqlQuery );
			$inventory = self::$database->getResultRow( $resultSet );
			
			// log event
			EventLog::addEntry( self::$database, 'info', 'inventory', 'update', 'modified',
								'<span class="event_log_main_record_inline">' . capitalizeWords( htmlentities( $_POST['newModelName'] ) ) .
								'</span> model in ' .
								'<a href="list_inventory_models.php?brandID=' . $inventory['brand_id'] . '">' .
								capitalizeWords( Filter::output( $inventory['name'] ) ) . '</a>' .
								' brand was <span class="event_log_action">modified</span>' );
			
			echo "<b>" . capitalizeWords( htmlentities( $_POST['newModelName'] ) ) . "</b> successfully updated!<br /><br /><br />";
		}
	}



	// delete model, ajax function
	public static function deleteModel()
	{
		// check required parameters
		if ( !isset( $_POST['modelID'] ) || !isset( $_POST['modelName'] ) || !isset( $_POST['duplicateModelID'] ) )
			return;

		self::$database = new Database();
		
		
		// get brand name
		$sqlQuery = 'SELECT id, name FROM inventory_brand WHERE id=' .
					'(SELECT brand_id FROM inventory WHERE id = ' . $_POST['modelID'] . ')';
		$resultSet = self::$database->query( $sqlQuery );
		$inventory = self::$database->getResultRow( $resultSet );

		
		// update orders and purchases
		if ( $_POST['duplicateModelID'] != 0 ) {
			$sqlQuery = 'UPDATE order_item SET inventory_id=' . $_POST['duplicateModelID'] . ' WHERE inventory_id=' . $_POST['modelID'];
			self::$database->query( $sqlQuery );
			
			$sqlQuery = 'UPDATE purchase_item SET inventory_id=' . $_POST['duplicateModelID'] . ' WHERE inventory_id=' . $_POST['modelID'];
			self::$database->query( $sqlQuery );
		}
		
		
		$sqlQuery = "DELETE FROM inventory WHERE id =" . $_POST['modelID'];

		if ( self::$database->query( $sqlQuery ) ) {
			// log event
			EventLog::addEntry( self::$database, 'warning', 'inventory', 'delete', 'removed',
								'<span class="event_log_main_record_inline">' . capitalizeWords( htmlentities( $_POST['modelName'] )  ) . '</span> model in ' .
								'<a href="list_inventory_models.php?brandID=' . $inventory['id'] . '">' .
								capitalizeWords( Filter::output( $inventory['name'] ) ) . '</a> ' .
								'brand was <span class="event_log_action bad">deleted</span>' );
			
			echo "<b>" . capitalizeWords( htmlentities( $_POST['modelName'] ) ) . "</b> has been deleted from model list!<br /><br /><br />";
		} else {
			echo "<b>Error:</b> Cannot delete " . capitalizeWords( htmlentities( $_POST['modelName'] ) )  . " to model list<br /><br />";
			echo "It is possible that this model is currently in use in some orders.<br />";
			echo "Please verify and try again.";
		}
	}



	// get brand name for given brand ID
	public static function getBrandName( $brandID )
	{
		if ( !self::$database ) {
			self::$database = new Database();
		}

		$sqlQuery = "SELECT name FROM inventory_brand" .
					" WHERE inventory_brand.id = " . $brandID;;

		$resultSet = self::$database->query( $sqlQuery );
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			$brandName = self::$database->getResultRow( $resultSet );
			return $brandName['name'];
		} else {
			return null;
		}
	}
	
	
	// export order list to Excel file, ajax function
	public static function exportListToExcel( $username, $paramArray = null )
	{
		if ( $paramArray != null ) {
			if ( isset( $paramArray['brandID'] ) ) {
				$brandID = $paramArray['brandID'];
			} else {
				$brandID = null;
			}
		} else {
			$brandID = null;
		}
		
		$fileTimeStampExtension = date( EXCEL_FILE_TIMESTAMP_FORMAT );
		$headingTimeStamp       = dateFormatOutput( $fileTimeStampExtension, EXCEL_HEADING_TIMESTAMP_FORMAT, EXCEL_FILE_TIMESTAMP_FORMAT );
		
		require_once( "classes/Filter.php" );
		
		self::$database = new Database();
		
		
		// import PHPExcel library
		require_once( 'libraries/phpexcel/PHPExcel.php' );
		
		// instantiate formatting variables
		$backgroundColor = new PHPExcel_Style_Color();
		$fontColor 		 = new PHPExcel_Style_Color();
		
		// color-specific variables
		$fontColorRed	  = new PHPExcel_Style_Color();
		$fontColorRed->setRGB( 'FF0000' );
		$fontColorDarkRed = new PHPExcel_Style_Color();
		$fontColorDarkRed->setRGB( 'CC0000' );
		$fontColorGreen	  = new PHPExcel_Style_Color();
		$fontColorGreen->setRGB( '00CC00' );
		$fontColorGray	  = new PHPExcel_Style_Color();
		$fontColorGray->setRGB( '999999' );

		$altRowColor  	  = new PHPExcel_Style_Color();
		$altRowColor->setRGB( EXCEL_ALT_ROW_BACKGROUND_COLOR );
		
		// set value binder
		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );
		
		// create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		
		$sheetTitle = 'Inventory';
		$fileName   = 'Inventory';

		// set file properties
		$objPHPExcel->getProperties()
					->setCreator($username)
					->setLastModifiedBy($username)
					->setTitle($sheetTitle.' as of '.$headingTimeStamp)
					->setSubject(EXCEL_FILE_SUBJECT)
					->setDescription(EXCEL_FILE_DESCRIPTION)
					->setKeywords(EXCEL_FILE_KEYWORDS)
					->setCategory(EXCEL_FILE_CATEGORY);
		
		
		// get inventory brands
		if ( $brandID == null ) {
			$sqlQuery = "SELECT * FROM inventory_brand ORDER BY name ASC";
		} else {
			$sqlQuery = "SELECT * FROM inventory_brand WHERE id = $brandID";
		}
		$resultSet = self::$database->query( $sqlQuery );
		if ( self::$database->getResultCount( $resultSet ) > 0 ) {
			$inventoryBrands = array();
			$inventoryBrandCount = 0;
			
			// save result set to temporary variable
			while ( $brand = self::$database->getResultRow( $resultSet ) ) {
				$inventoryBrands[$inventoryBrandCount] 		   = array();
				$inventoryBrands[$inventoryBrandCount]['id']   = $brand['id'];
				$inventoryBrands[$inventoryBrandCount]['name'] = html_entity_decode(capitalizeWords(Filter::reinput( $brand['name'])));
				$inventoryBrandCount++;
			}
			
			$inventoryBrandCount--;
			
			for ( $i = 0; $i <= $inventoryBrandCount; $i++ ) {
				if ( $i > 0 ) {
					// create additional sheet if $i is greater than 0
					// if 0, use current sheet
					$objPHPExcel->createSheet();
				}
				
				// set new created sheet as active sheet
				$objPHPExcel->setActiveSheetIndex( $i );
				$activeSheet = $objPHPExcel->getActiveSheet();
				
				// use brand name as sheet name
				$inventoryBrands[$i]['sheetTitle'] = str_replace(array('[', ']', '*', '/', '\\', '?', ':', "'" ), '_', 
				                                                 $inventoryBrands[$i]['name'] );
				$activeSheet->setTitle( $inventoryBrands[$i]['sheetTitle'] );
				
				
				// set default font
				$activeSheet->getDefaultStyle()->getFont()->setName( EXCEL_DEFAULT_FONT_NAME )
														  ->setSize( EXCEL_DEFAULT_FONT_SIZE );
				
				// write sheet headers
				if ( $brandID == null ) {
					$activeSheet->setCellValue( 'A1', $inventoryBrands[$i]['name'] );
					$activeSheet->setCellValue( 'A2', 'As of '.$headingTimeStamp );
					$FIELD_HEADER_ROW = '4';
				} else {
					// overwrite sheetTitle and fileName
					$sheetTitle = $sheetTitle.' » '.$inventoryBrands[$i]['name'];
					$fileName   = $fileName.' - '.$inventoryBrands[$i]['sheetTitle'];
					
					$activeSheet->setCellValue( 'A1', CLIENT );
					$activeSheet->setCellValue( 'A2', $sheetTitle );
					$activeSheet->setCellValue( 'A3', 'As of '.$headingTimeStamp );
					$FIELD_HEADER_ROW = '5';
				}

				// define max column
				$MAX_COLUMN = 'J';
				$BLANK_HEADER_ROW = $FIELD_HEADER_ROW - 1;
				
				// format sheet headers
				$backgroundColor->setRGB( EXCEL_HEADER_BACKGROUND_COLOR );
				$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.$BLANK_HEADER_ROW )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID );
				$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.$BLANK_HEADER_ROW )->getFill()->setStartColor( $backgroundColor );
				if ( $brandID == null ) {
					$activeSheet->getStyle( 'A1' )->getFont()->setBold( true );
					$activeSheet->getStyle( 'A1:A2' )->getFont()->setName( EXCEL_HEADER_FONT_NAME );
					$activeSheet->getStyle( 'A1' )->getFont()->setSize( EXCEL_HEADER2_FONT_SIZE );
					$activeSheet->getStyle( 'A2' )->getFont()->setSize( EXCEL_HEADER2_FONT_SIZE );
				} else {
					$activeSheet->getStyle( 'A1:A2' )->getFont()->setBold( true );
					$activeSheet->getStyle( 'A1:A3' )->getFont()->setName( EXCEL_HEADER_FONT_NAME );
					$activeSheet->getStyle( 'A1' )->getFont()->setColor( $fontColorRed );
					$activeSheet->getStyle( 'A1' )->getFont()->setSize( EXCEL_HEADER1_FONT_SIZE );
					$activeSheet->getStyle( 'A2' )->getFont()->setSize( EXCEL_HEADER2_FONT_SIZE );
					$activeSheet->getStyle( 'A3' )->getFont()->setSize( EXCEL_HEADER2_FONT_SIZE );
				}
				
				// write column headers
				$activeSheet->setCellValue( 'A'.$FIELD_HEADER_ROW, 'Model' )
							->setCellValue( 'B'.$FIELD_HEADER_ROW, 'Description' )
							->setCellValue( 'C'.$FIELD_HEADER_ROW, 'Purchase Price (' . CURRENCY . ')' )
							->setCellValue( 'D'.$FIELD_HEADER_ROW, 'Selling Price (' . CURRENCY . ')' )
							->setCellValue( 'E'.$FIELD_HEADER_ROW, 'Available Stocks' )
							->setCellValue( 'F'.$FIELD_HEADER_ROW, 'Stock Amount (' . CURRENCY . ')' )
							->setCellValue( 'G'.$FIELD_HEADER_ROW, 'Expected Sales (' . CURRENCY . ')' )
							->setCellValue( 'H'.$FIELD_HEADER_ROW, 'Expected Profit (' . CURRENCY . ')' )
							->setCellValue( 'I'.$FIELD_HEADER_ROW, 'Demand' )
							->setCellValue( 'J'.$FIELD_HEADER_ROW, 'Remarks' );
				
				// set column widths
				$activeSheet->getColumnDimension( 'A' )->setWidth( 40 );
				$activeSheet->getColumnDimension( 'B' )->setWidth( 40 );
				$activeSheet->getColumnDimension( 'C' )->setWidth( 20 );
				$activeSheet->getColumnDimension( 'D' )->setWidth( 20 );
				$activeSheet->getColumnDimension( 'E' )->setWidth( 17 );
				$activeSheet->getColumnDimension( 'F' )->setWidth( 20 );
				$activeSheet->getColumnDimension( 'G' )->setWidth( 24 );
				$activeSheet->getColumnDimension( 'H' )->setWidth( 23 );
				$activeSheet->getColumnDimension( 'I' )->setWidth( 20 );
				$activeSheet->getColumnDimension( 'J' )->setWidth( 30 );
								
				// format column headers
				$fontColor->setRGB( EXCEL_COLUMN_HEADER_FONT_COLOR );
				$backgroundColor->setRGB( EXCEL_COLUMN_HEADER_BACKGROUND_COLOR );
				$activeSheet->getStyle('A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID );
				$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFill()->setStartColor( $backgroundColor );
				$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFont()->setColor( $fontColor );
				$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFont()->setBold( true );
				$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getAlignment()->setWrapText( true );
				
				// set autofilter
				$activeSheet->setAutoFilter( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW );
				
				// freeze pane
				$activeSheet->freezePane( 'C'.($FIELD_HEADER_ROW + 1) );
				
				// initialize counters
				$rowPtr = $FIELD_HEADER_ROW + 1;
				$itemCount = 0;
				$parentRow = 0;
				$childRow  = null;
				
				
				// get inventory models
				$sqlQuery = 'SELECT * FROM inventory WHERE brand_id = ' . $inventoryBrands[$i]['id'] . ' ORDER BY model ASC';
				$resultSet = self::$database->query( $sqlQuery );
				
				
				// write data
				if ( self::$database->getResultCount( $resultSet ) > 0 ) {
					while ( $inventoryModel = self::$database->getResultRow( $resultSet ) ) {
						if ( $inventoryModel['parent_id'] == NULL ) {
							// model
							$activeSheet->getCell( 'A' . $rowPtr )
										->setValueExplicit( html_entity_decode( capitalizeWords( Filter::reinput( $inventoryModel['model'] ) ) ),
										                    PHPExcel_Cell_DataType::TYPE_STRING );
							
							// description
							$activeSheet->getCell( 'B' . $rowPtr )->setValueExplicit( stripslashes( $inventoryModel['description'] ),
							                                                          PHPExcel_Cell_DataType::TYPE_STRING );
							
							if ( $childRow != null ) {
								$activeSheet->mergeCells('A'.$parentRow.':A'.$childRow);
								$childRow = null;
							}

							$parentRow = $rowPtr;
						} else {
							// put old name in description
							$activeSheet->getCell( 'B' . $rowPtr )
										->setValueExplicit( html_entity_decode( capitalizeWords( Filter::reinput( $inventoryModel['model'] ) ) ),
										                    PHPExcel_Cell_DataType::TYPE_STRING );
							$activeSheet->getStyle( 'B' . $rowPtr )->getFont()->setColor( $fontColorGray );
							
							$childRow  = $rowPtr;
						}
						
						// purchase price
						$activeSheet->setCellValue( 'C' . $rowPtr, $inventoryModel['purchase_price'] );
						if ( $inventoryModel['purchase_price'] <= 0 ) {
							$activeSheet->getStyle( 'C' . $rowPtr )->getFont()->setColor( $fontColorDarkRed );
						}
						
						// selling price
						$activeSheet->setCellValue( 'D' . $rowPtr, $inventoryModel['selling_price'] );
						if ( $inventoryModel['selling_price'] <= 0 ) {
							$activeSheet->getStyle( 'D' . $rowPtr )->getFont()->setColor( $fontColorDarkRed );
						}
						
						// available stocks
						if ( $inventoryModel['stock_count'] > 0 ) {
							$activeSheet->setCellValue( 'E' . $rowPtr, $inventoryModel['stock_count'] );
						}
						
						// stock amount
						$activeSheet->setCellValue( 'F' . $rowPtr, '=C' . $rowPtr . '*E' . $rowPtr );
						
						// expected sales
						$activeSheet->setCellValue( 'G' . $rowPtr, '=D' . $rowPtr . '*E' . $rowPtr );
						
						// expected profit
						$activeSheet->setCellValue( 'H' . $rowPtr, '=G' . $rowPtr . '-F' . $rowPtr );
						
						// demand
						if ( $inventoryModel['reserved_stock'] > 0 ) {
							$activeSheet->setCellValue( 'I' . $rowPtr, $inventoryModel['reserved_stock'] );
						}
						
						// remarks
						if ( $inventoryModel['parent_id'] != NULL ) {
							// note as old stock
							$childInventoryDate = new DateTime( substr( html_entity_decode( capitalizeWords( Filter::reinput( $inventoryModel['model'] ) ) ),
							                                            -11, 10 ) );
							$activeSheet->setCellValue( 'J' . $rowPtr, 'Old stock as of '.$childInventoryDate->format( 'n/j/Y' ) );
						}

						// set alternating row color
						if ( EXCEL_ALT_ROW > 0 && $rowPtr % EXCEL_ALT_ROW == 0 ) {
							$activeSheet->getStyle( 'A'.$rowPtr.':'.$MAX_COLUMN.$rowPtr )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID );
							$activeSheet->getStyle( 'A'.$rowPtr.':'.$MAX_COLUMN.$rowPtr )->getFill()->setStartColor( $altRowColor );
						} else {
							$activeSheet->getStyle( 'A'.$rowPtr.':'.$MAX_COLUMN.$rowPtr )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_NONE );
						}
						
						$rowPtr++;
						
						if ( $inventoryModel['parent_id'] == NULL ) {
							$itemCount++;
						}
					}
					
					$rowPtr--;
				}
				
				$ROW_START_OF_DATA = $FIELD_HEADER_ROW + 1;
				
				// post formatting
				$activeSheet->getStyle( 'A'.$ROW_START_OF_DATA.':B' . $rowPtr )->getAlignment()->setWrapText( true );						// wrap Model and Description
				$activeSheet->getStyle( 'C'.$ROW_START_OF_DATA.':D' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );	// format Purchase Price and Selling Price
				$activeSheet->getStyle( 'E'.$ROW_START_OF_DATA.':E' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );		// format Available Stocks
				$activeSheet->getStyle( 'F'.$ROW_START_OF_DATA.':H' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );	// format amounts
				$activeSheet->getStyle( 'F'.$ROW_START_OF_DATA.':H' . $rowPtr )->getFont()->setBold( true );								// set amounts to bold
				$activeSheet->getStyle( 'I'.$ROW_START_OF_DATA.':I' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );		// format Demand

				// set columns to left aligned
				$activeSheet->getStyle( 'A'.$ROW_START_OF_DATA.':B' . $rowPtr )->getAlignment()->setHorizontal( PHPExcel_Style_Alignment::HORIZONTAL_LEFT );
				$activeSheet->getStyle( 'J'.$ROW_START_OF_DATA.':T' . $rowPtr )->getAlignment()->setHorizontal( PHPExcel_Style_Alignment::HORIZONTAL_LEFT );	// Remarks

				// conditional formatting
				// * set 0 or negative values to dark red
				$conditionalStyles = $activeSheet->getStyle( 'F'.$ROW_START_OF_DATA.':H' . $rowPtr )->getConditionalStyles();

				$objConditional = new PHPExcel_Style_Conditional();
				$objConditional->setConditionType( PHPExcel_Style_Conditional::CONDITION_CELLIS );
				$objConditional->setOperatorType( PHPExcel_Style_Conditional::OPERATOR_LESSTHANOREQUAL );
				$objConditional->addCondition( 0 );
				$objConditional->getStyle()->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );
				$objConditional->getStyle()->getFont()->setColor( $fontColorDarkRed );

				array_push( $conditionalStyles, $objConditional );

				$activeSheet->getStyle( 'F'.$ROW_START_OF_DATA.':H' . $rowPtr )->setConditionalStyles( $conditionalStyles );
				
				// write totals
				$totalsRow = $rowPtr + 3;
				$activeSheet->setCellValue( 'A' . $totalsRow, 'Total Number of Models: ' . numberFormat( $itemCount, "int" ) )
							->setCellValue( 'D' . $totalsRow, 'Totals:' )
							->setCellValue( 'E' . $totalsRow, '=SUM(E'.$ROW_START_OF_DATA.':E' . $rowPtr . ')' )
							->setCellValue( 'F' . $totalsRow, '=SUM(F'.$ROW_START_OF_DATA.':F' . $rowPtr . ')' )
							->setCellValue( 'G' . $totalsRow, '=SUM(G'.$ROW_START_OF_DATA.':G' . $rowPtr . ')' )
							->setCellValue( 'H' . $totalsRow, '=SUM(H'.$ROW_START_OF_DATA.':H' . $rowPtr . ')' )
							->setCellValue( 'I' . $totalsRow, '=SUM(I'.$ROW_START_OF_DATA.':I' . $rowPtr . ')' );
				
				$inventoryBrands[$i]['model_count'] = $itemCount;
				$inventoryBrands[$i]['totals_row']  = $totalsRow;
				
				// format totals
				$styleArray = array(
					'borders' => array(
						'top' => array( 'style' => PHPExcel_Style_Border::BORDER_THICK )
					)
				);
				$activeSheet->getStyle( 'A' . $totalsRow . ':J' . $totalsRow )->applyFromArray( $styleArray );
				$activeSheet->getStyle( 'E' . $totalsRow )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );
				$activeSheet->getStyle( 'F' . $totalsRow . ':H' . $totalsRow )->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );
				$activeSheet->getStyle( 'I' . $totalsRow )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );
				$activeSheet->getStyle( 'A' . $totalsRow . ':J' . $totalsRow )->getFont()->setBold( true );
				$activeSheet->getStyle( 'A' . $totalsRow . ':J' . $totalsRow )->getFont()->setColor( $fontColorRed );


				// set vertical alignment to top
				$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.$totalsRow )->getAlignment()->setVertical( PHPExcel_Style_Alignment::VERTICAL_TOP );
			}
		}
		
		if ( $brandID == null ) {
			// create summary sheet
			$objPHPExcel->createSheet( 0 );
			$objPHPExcel->setActiveSheetIndex( 0 );
			$activeSheet = $objPHPExcel->getActiveSheet();
			$activeSheet->setTitle( '- SUMMARY -' );
			
			// set default font
			$activeSheet->getDefaultStyle()->getFont()->setName( EXCEL_DEFAULT_FONT_NAME )
													  ->setSize( EXCEL_DEFAULT_FONT_SIZE );
		
			// write sheet headers
			$activeSheet->setCellValue( 'A1', CLIENT );
			$activeSheet->setCellValue( 'A2', 'Inventory' );
			$activeSheet->setCellValue( 'A3', 'As of '.$headingTimeStamp );

			// define max column
			$MAX_COLUMN = 'G';
			$FIELD_HEADER_ROW = '5';
		
			// format sheet headers
			$backgroundColor->setRGB( EXCEL_HEADER_BACKGROUND_COLOR );
			$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.'4' )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID );
			$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.'4' )->getFill()->setStartColor( $backgroundColor );
			$activeSheet->getStyle( 'A1:A2' )->getFont()->setBold( true );
			$activeSheet->getStyle( 'A1:A3' )->getFont()->setName( EXCEL_HEADER_FONT_NAME );
			$activeSheet->getStyle( 'A1' )->getFont()->setColor( $fontColorRed );
			$activeSheet->getStyle( 'A1' )->getFont()->setSize( EXCEL_HEADER1_FONT_SIZE );
			$activeSheet->getStyle( 'A2' )->getFont()->setSize( EXCEL_HEADER2_FONT_SIZE );
			$activeSheet->getStyle( 'A3' )->getFont()->setSize( EXCEL_HEADER2_FONT_SIZE );
		
			// write column headers
			$activeSheet->setCellValue( 'A'.$FIELD_HEADER_ROW, 'Brand' )
						->setCellValue( 'B'.$FIELD_HEADER_ROW, 'No. of Models' )
						->setCellValue( 'C'.$FIELD_HEADER_ROW, 'Total Available Stocks' )
						->setCellValue( 'D'.$FIELD_HEADER_ROW, 'Total Stock Amount (' . CURRENCY . ')' )
						->setCellValue( 'E'.$FIELD_HEADER_ROW, 'Total Expected Sales (' . CURRENCY . ')' )
						->setCellValue( 'F'.$FIELD_HEADER_ROW, 'Total Expected Profit (' . CURRENCY . ')' )
						->setCellValue( 'G'.$FIELD_HEADER_ROW, 'Total Demand' );
		
			// set column widths
			$activeSheet->getColumnDimension( 'A' )->setWidth( 30 );
			$activeSheet->getColumnDimension( 'B' )->setWidth( 20 );
			$activeSheet->getColumnDimension( 'C' )->setWidth( 25 );
			$activeSheet->getColumnDimension( 'D' )->setWidth( 25 );
			$activeSheet->getColumnDimension( 'E' )->setWidth( 28 );
			$activeSheet->getColumnDimension( 'F' )->setWidth( 26 );
			$activeSheet->getColumnDimension( 'G' )->setWidth( 25 );
		
			// format column headers
			$fontColor->setRGB( EXCEL_COLUMN_HEADER_FONT_COLOR );
			$backgroundColor->setRGB( EXCEL_COLUMN_HEADER_BACKGROUND_COLOR );
			$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFill()->setFillType( PHPExcel_Style_Fill::FILL_SOLID );
			$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFill()->setStartColor( $backgroundColor );
			$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFont()->setColor( $fontColor );
			$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getFont()->setBold( true );
			$activeSheet->getStyle( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW )->getAlignment()->setWrapText( true );
			
			// set autofilter
			$activeSheet->setAutoFilter( 'A'.$FIELD_HEADER_ROW.':'.$MAX_COLUMN.$FIELD_HEADER_ROW );
			
			// freeze pane
			$activeSheet->freezePane( 'B'.($FIELD_HEADER_ROW+1) );
		
			// initialize counters
			$rowPtr = $FIELD_HEADER_ROW + 1;
			$itemCount = 0;
			
			// write data
			for ( $i = 0; $i <= $inventoryBrandCount; $i++ ) {
				// brand
				$activeSheet->getCell( 'A' . $rowPtr )
							->setValueExplicit( $inventoryBrands[$i]['name'], PHPExcel_Cell_DataType::TYPE_STRING );
				
				// no. of models
				$activeSheet->setCellValue( 'B' . $rowPtr, $inventoryBrands[$i]['model_count'] );
				if ( $inventoryBrands[$i]['model_count'] <= 0 ) {
					$activeSheet->getStyle( 'B' . $rowPtr )->getFont()->setColor( $fontColorDarkRed );
				}
				
				// total available stocks
				$activeSheet->setCellValue( 'C' . $rowPtr, "='" . $inventoryBrands[$i]['sheetTitle'] . "'!E" . $inventoryBrands[$i]['totals_row'] );
				
				// total stock amount
				$activeSheet->setCellValue( 'D' . $rowPtr, "='" . $inventoryBrands[$i]['sheetTitle'] . "'!F" . $inventoryBrands[$i]['totals_row'] );
				
				// total expected sales
				$activeSheet->setCellValue( 'E' . $rowPtr, "='" . $inventoryBrands[$i]['sheetTitle'] . "'!G" . $inventoryBrands[$i]['totals_row'] );
				
				// total expected profit
				$activeSheet->setCellValue( 'F' . $rowPtr, "='" . $inventoryBrands[$i]['sheetTitle'] . "'!H" . $inventoryBrands[$i]['totals_row'] );
				
				// total demand
				$activeSheet->setCellValue( 'G' . $rowPtr, "='" . $inventoryBrands[$i]['sheetTitle'] . "'!I" . $inventoryBrands[$i]['totals_row'] );
				
				$rowPtr++;
				$itemCount++;
			}
			$rowPtr--;
		
			// post formatting
			$activeSheet->getStyle( 'A6:A' . $rowPtr )->getAlignment()->setWrapText( true );						// wrap Brand
			$activeSheet->getStyle( 'B6:C' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );		// format No. of Models and Total Available Stocks
			$activeSheet->getStyle( 'D6:F' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );	// format amounts
			$activeSheet->getStyle( 'D6:F' . $rowPtr )->getFont()->setBold( true );									// set totals to bold
			$activeSheet->getStyle( 'G6:G' . $rowPtr )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );		// format Total Demand

			// set columns to left aligned
			$activeSheet->getStyle( 'A6:A' . $rowPtr )->getAlignment()->setHorizontal( PHPExcel_Style_Alignment::HORIZONTAL_LEFT );

			// conditional formatting
			// * set 0 or negative values to dark red
			$conditionalStyles = $activeSheet->getStyle( 'B6:G' . $rowPtr )->getConditionalStyles();
			$objConditional = new PHPExcel_Style_Conditional();
			$objConditional->setConditionType( PHPExcel_Style_Conditional::CONDITION_CELLIS );
			$objConditional->setOperatorType( PHPExcel_Style_Conditional::OPERATOR_LESSTHANOREQUAL );
			$objConditional->addCondition( 0 );
			$objConditional->getStyle()->getFont()->setColor( $fontColorDarkRed );
			array_push( $conditionalStyles, $objConditional );
			$activeSheet->getStyle( 'B6:G' . $rowPtr )->setConditionalStyles( $conditionalStyles );
		
			// write totals
			$totalsRow = $rowPtr + 3;
			$activeSheet->setCellValue( 'A' . $totalsRow, 'Total Number of Brands: ' . numberFormat( $itemCount, "int" ) )
						->setCellValue( 'B' . $totalsRow, '=SUM(B6:B' . $rowPtr . ')' )
						->setCellValue( 'C' . $totalsRow, '=SUM(C6:C' . $rowPtr . ')' )
						->setCellValue( 'D' . $totalsRow, '=SUM(D6:D' . $rowPtr . ')' )
						->setCellValue( 'E' . $totalsRow, '=SUM(E6:E' . $rowPtr . ')' )
						->setCellValue( 'F' . $totalsRow, '=SUM(F6:F' . $rowPtr . ')' )
						->setCellValue( 'G' . $totalsRow, '=SUM(G6:G' . $rowPtr . ')' );
		
			// format totals
			$styleArray = array(
				'borders' => array(
					'top' => array( 'style' => PHPExcel_Style_Border::BORDER_THICK )
				)
			);
			$activeSheet->getStyle( 'A' . $totalsRow . ':G' . $totalsRow )->applyFromArray( $styleArray );
			$activeSheet->getStyle( 'B' . $totalsRow . ':C' . $totalsRow )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );
			$activeSheet->getStyle( 'D' . $totalsRow . ':F' . $totalsRow )->getNumberFormat()->setFormatCode( EXCEL_CURRENCY_FORMAT );
			$activeSheet->getStyle( 'G' . $totalsRow )->getNumberFormat()->setFormatCode( EXCEL_INT_FORMAT );
			$activeSheet->getStyle( 'A' . $totalsRow . ':G' . $totalsRow )->getFont()->setBold( true );
			$activeSheet->getStyle( 'A' . $totalsRow . ':G' . $totalsRow )->getFont()->setColor( $fontColorRed );


			// set vertical alignment to top
			$activeSheet->getStyle( 'A1:'.$MAX_COLUMN.$totalsRow )->getAlignment()->setVertical( PHPExcel_Style_Alignment::VERTICAL_TOP );
		}
		
		
		// set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex( 0 );

		// redirect output to a client's web browser (Excel2007)
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment;filename="'.CLIENT.' - '.$fileName.' - as of '.$fileTimeStampExtension.'.xlsx"' );
		header( 'Cache-Control: max-age=0' );
		
		$objWriter = PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( 'php://output' );
	}

}
?>

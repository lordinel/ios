<?php

// class definition for page layout engine
abstract class Layout
{
	protected $id;
	protected static $database;						// database object handler



	// constuctor
	public function __construct( Database &$database = null, $id = null )
	{
		self::$database = $database;
		$this->id = $id;
	}



	// return the value of id
	public function getUserID()
	{
		return $this->id;
	}



	// get class name for instance
	protected static function getInstanceClassName( $thisInstance = null )
	{
		if ( $thisInstance == null )			// static method, no object
			return strtolower ( get_called_class() );
		else
			return strtolower ( get_class( $thisInstance ) );
	}



	// display list table
	protected static function showListHeader( array $columns,		// array of columns for the table, in format $array['database_column'] = "Column Name"
											  $sectionID,			// ID of section where to display the table
											  $callback,			// AJAX PHP function or method to call
											  $criteria,			// criteria for filtering results
											  $sortColumn,			// column presently sorted
											  $sortMethod,			// sort method (e.g. ASC or DESC)
											  $filterName = null,	// filter name (optional)
											  $filterValue = null )	// filter value (optional)
	{
		// preload images used in header
		echo "\n		<img src=\"images/blue-glass-hover.png\" class=\"preload_image\" />\n";
		echo "		<img src=\"images/blue-glass-active.png\" class=\"preload_image\" />\n";

		echo "	<table class=\"list_table\">\n";
		echo "		<thead>\n";
		echo "			<tr>\n";

		// display header
		while( $columnName = current( $columns ) )
		{
			echo "<th onclick=\"" .
				 "ajax(null,'" . $sectionID . "','innerHTML','" . $callback . "'," .
				 self::prepareSortTableParameters( $criteria, key( $columns ), $sortColumn, $sortMethod, $filterName, $filterValue ) . "), " .
				 "modifyBrowserHistory(" .
				 self::prepareSortTableParameters( $criteria, key( $columns ), $sortColumn, $sortMethod, $filterName, $filterValue ) . ")" .
				 "\" title=\"Click to sort\">";

			self::showSortIndicator( key( $columns ), $sortColumn, $sortMethod );
			echo $columnName . "</th>\n";

			next( $columns );
		}

		echo "			</tr>\n";
		echo "		</thead>\n\n";
		echo "		<tbody>\n";
	}



	// table sorting
	private static function prepareSortTableParameters( $criteria, $newSortColumn, $prevSortColumn, $sortMethod, $filterName, $filterValue )
	{
		$string = "'criteria=" . $criteria . "'," .
				  "'sortColumn=" . $newSortColumn . "',";
		
		if ( $newSortColumn == $prevSortColumn ) {
			if ( $sortMethod == "ASC" ) {
				$string = $string . "'sortMethod=DESC',";
			} else {
				$string = $string . "'sortMethod=ASC',";
			}
		} else {
			$string = $string . "'sortMethod=ASC',";
		}
		
		$string = $string . "'filterName=" . $filterName . "'," .
							"'filterValue=" . $filterValue . "'";

		return $string;
	}



	// table sorting indicator
	private static function showSortIndicator( $columnName, $sortColumn, $sortMethod )
	{
		if ( $columnName == $sortColumn )
		{
			if ( $sortMethod == "ASC" )
				echo "▲ ";
			else
				echo "▼ ";
		}
	}
	
	
	
	// display pagination
	protected static function showPagination( $currentPage,			// current page number
											  $itemsPerPage,		// number of items per page
											  $resultCount,			// maximum number of items
											  $sectionID,			// ID of section where to display the table
											  $callback,			// AJAX PHP function or method to call
											  $criteria,			// criteria for filtering results
											  $sortColumn,			// column presently sorted
											  $sortMethod,			// sort method (e.g. ASC or DESC)
											  $filterName = null,	// filter name (optional)
											  $filterValue = null )	// filter value (optional)
	{
		$maxPage = ceil( $resultCount / $itemsPerPage );
		echo '<div class="pagination">';

		echo '<span class="page_items_info">';
		echo ( ( ( $currentPage * $itemsPerPage ) - $itemsPerPage ) + 1 ) . '-';
		if ( ( $currentPage * $itemsPerPage ) > $resultCount ) {
			echo $resultCount;
		} else {
			echo ( $currentPage * $itemsPerPage );
		}
		echo ' of ' . $resultCount . ' Records';
		echo '</span>';

		echo '<span class="page_info">Page ' . $currentPage . ' / ' . $maxPage . '</span>';

		echo '<span class="page_selector">';
		if ( $currentPage == 1 )
			echo "&laquo; prev";
		else
			echo "<a href=\"javascript:void(0)\" onclick=\"" .
				 "ajax(null,'" . $sectionID . "','innerHTML','" . $callback . "'," .
				 self::prepareSortPaginationParameters( $criteria, $sortColumn, $sortMethod, ( $currentPage - 1 ), $itemsPerPage, $filterName, $filterValue ) . "), " .
				 "modifyBrowserHistory(" .
				 self::prepareSortPaginationParameters( $criteria, $sortColumn, $sortMethod, ( $currentPage - 1 ), $itemsPerPage, $filterName, $filterValue ) . ")" .
				 "\">&laquo; prev</a>";
		
		
		echo ' | <select name="page_dropdown" id="page_dropdown" onchange="' .
			 "ajax(null,'" . $sectionID . "','innerHTML','" . $callback . "'," .
			 self::prepareSortPaginationParameters( $criteria, $sortColumn, $sortMethod, 'PAGE_DROPDOWN', $itemsPerPage, $filterName, $filterValue ) . "), " .
			 "modifyBrowserHistory(" .
			 self::prepareSortPaginationParameters( $criteria, $sortColumn, $sortMethod, 'PAGE_DROPDOWN', $itemsPerPage, $filterName, $filterValue ) . ")" .
			 '">';
		for ( $i = 1; $i <= $maxPage; $i++ ) {
			echo '<option value="' . $i . '"' . ( $i == $currentPage ? ' selected="selected"' : '' ) . '>' . $i . '&nbsp;</option>';
		}
		echo '</select> | ';
		
		
		if ( $currentPage == $maxPage )
			echo "next &raquo;";
		else
			echo "<a href=\"javascript:void(0)\" onclick=\"" .
				 "ajax(null,'" . $sectionID . "','innerHTML','" . $callback . "'," .
				 self::prepareSortPaginationParameters( $criteria, $sortColumn, $sortMethod, ( $currentPage + 1 ), $itemsPerPage, $filterName, $filterValue ) . "); " .
				 "modifyBrowserHistory(" .
				 self::prepareSortPaginationParameters( $criteria, $sortColumn, $sortMethod, ( $currentPage + 1 ), $itemsPerPage, $filterName, $filterValue ) . ")" .
				 "\">next &raquo;</a>";

		echo '</span>';
		echo '</div>';
	}
	
	
	private static function prepareSortPaginationParameters( $criteria, $sortColumn, $sortMethod, $newPage, $itemsPerPage, $filterName, $filterValue )
	{
		if ( $newPage == 'PAGE_DROPDOWN' ) {
			$string = "'criteria=" . $criteria . "'," .
				  "'sortColumn=" . $sortColumn . "'," .
				  "'sortMethod=" . $sortMethod . "'," .
				  "'page='+$(this).val()," .
				  "'itemsPerPage=" . $itemsPerPage . "'," .
				  "'filterName=" . $filterName . "'," .
				  "'filterValue=" . $filterValue . "'";
		} else {
			$string = "'criteria=" . $criteria . "'," .
				  "'sortColumn=" . $sortColumn . "'," .
				  "'sortMethod=" . $sortMethod . "'," .
				  "'page=" . $newPage . "'," .
				  "'itemsPerPage=" . $itemsPerPage . "'," .
				  "'filterName=" . $filterName . "'," .
				  "'filterValue=" . $filterValue . "'";
		}

		return $string;
	}



	// default tasks for pages
	public static function showDefaultTasks( $page = null )
	{
		echo '<div id="tasks"><ul>';
		if ( $page == null ) {
			echo '<li id="task_back_to_list"><a href="javascript:void(0)" onclick="javascript:history.back()">' .
				 '<img src="images/task_buttons/back_to_list.png" />Back to List</a></li>';
		} else {
			echo '<li id="task_back_to_list"><a href="' .  $page . '">' .
				 '<img src="images/task_buttons/back_to_list.png" />Back to List</a></li>';
		}
		echo '</ul></div>';
		echo '</div>';		// to close header div
	}



	// default tasks for details pages
	protected function showCommonDetailsTasks()
	{
		echo "			<li id=\"task_back_to_list\"><a href=\"javascript:void(0)\" onclick=\"javascript:history.back()\">Back to List</a></li>\n";
	}



	// show list for auto-suggest, ajax function
	protected static function showAutoSuggestResult( $table, $column, $searchString )
	{
		self::$database = new Database();

		// select customers
		$sqlQuery = "SELECT " . $column ." FROM " . $table . " WHERE " . $column ." LIKE '%" . Filter::input( $searchString ) . "%'";
		//if ($table == 'customer') {
		//	$sqlQuery = $sqlQuery . " AND " . User::getQueryForBranch(self::$database);
		//}
		$sqlQuery = $sqlQuery . " ORDER BY name LIMIT 0,20";

		$resultSet = self::$database->query( $sqlQuery );

		if ( self::$database->getResultCount( $resultSet ) > 0 )
		{
			// display list
			while ( $item = self::$database->getResultRow( $resultSet ) )
			{
				echo "<option value=\"" . capitalizeWords( Filter::output( $item[$column] ) ) . "\" />";
			}
		}
	}



	// show list for auto-suggest, ajax function
	protected static function getAutoFillData( $table, $column, $searchString )
	{
		self::$database = new Database();

		// select customer
		$sqlQuery = "SELECT * FROM " . $table . " WHERE " . $column . " = '" . capitalizeWords( Filter::input( $searchString ) ) . "'";

		$resultSet = self::$database->query( $sqlQuery );
		return self::$database->getResultRow( $resultSet );
	}



	// display set of buttons
	protected static function showButtons( $buttonSet = ButtonSet::SUBMIT_RESET_CANCEL,
										   $divID = "form_buttons",
										   $display = true )
	{
		$buttonStr = '<div id="' . $divID . '">';

		switch ( $buttonSet )
		{
			case ButtonSet::SUBMIT_RESET_CANCEL:
				$buttonStr = $buttonStr . '<input type="submit" name="submit_form" id="submit_form" value="Save" />' .
										  '<input type="reset" name="reset_form" id="reset_form" value="Reset" />' .
										  '<input type="button" name="cancel_form" id="cancel_form" value="Cancel" onclick="javascript:history.back()" />';
				break;
			case ButtonSet::LOGIN:
				$buttonStr = $buttonStr . '<input type="submit" name="submit_login" value="Log In" />';
				break;
		}

		$buttonStr = $buttonStr . '</div>';

		if ( $display == true )
		{
			echo $buttonStr;
			return null;
		}
		else
			return $buttonStr;
	}
}


?>

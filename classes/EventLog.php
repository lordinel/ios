<?php
// includes
require_once( "classes/Database.php" );


// class definition for auditing and event logging functionality
class EventLog
{
	// save audit info
	public static function addEntry( Database &$database = null, $category, $table, $transaction, $action, $event, $encoder = null, $date = null )
	{
		if ( $database == null ) {
			$database = new Database();
		}
		
		if ( $encoder == null ) {
			$encoder = User::getUserIDStatic();
		}
		
		if ( $date == null ) {
			$date = date( 'Y-m-d H:i:s' );
		}
		

		$sqlQuery = "INSERT INTO event_log VALUES (" .
					"'" . $date . "'," .					// date
					"'" . $category . "'," .				// category
					"'" . $encoder . "'," .					// encoder
					"'" . $table . "'," .					// table
					"'" . $transaction . "'," .				// transaction
					"'" . $action . "'," .					// action
					"'" . Filter::input( $event ) . "')";	// remarks
		

		$database->query( $sqlQuery );

		return true;
	}
}
?>

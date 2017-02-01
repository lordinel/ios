<?php
// includes
require_once( "classes/Database.php" );
require_once( "classes/Filter.php" );


// class definition for registry
class Registry
{
	private static $database;
	
	
	const CONFIG_TABLE = "config";				            // table that contains the registry settings
	const CONFIG_NAME_FIELD = "name";			            // table field that identifies the setting name
	const CONFIG_TYPE_FIELD = "type";                       // table field that identifies the setting type
	const CONFIG_DEFAULT_VALUE_FIELD = "default_value";		// table field that identifies the setting default value
	const CONFIG_VALUE_FIELD = "value";			            // table field that identifies the setting value
	
	
	public static function get( $configName ) {
		if ( self::$database == null ) {
			self::$database = new Database();
		}
		
		$sqlQuery = "SELECT * FROM " . self::CONFIG_TABLE .
					" WHERE " . self::CONFIG_NAME_FIELD . " = '" . Filter::input( $configName ) . "'";
		
		$resultSet = self::$database->query( $sqlQuery );
		$setting = self::$database->getResultRow( $resultSet );
		
		if ( $setting[self::CONFIG_TYPE_FIELD] == 'boolean' ) {
			if ( strtolower( $setting[self::CONFIG_VALUE_FIELD] ) == "true" ) {
				return true;
			} else {
				return false;
			}
		} elseif ( $setting[self::CONFIG_TYPE_FIELD] == 'integer' ) {
			return (int) $setting[self::CONFIG_VALUE_FIELD];
		} else {
			return $setting[self::CONFIG_VALUE_FIELD];
		}
	}
}
?>

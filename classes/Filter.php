<?php
// class definition for input/output filtering
class Filter
{
	// filter input string
	public static function input( $inputString )
	{
		//return mysql_real_escape_string( addslashes( strip_tags( trim( $inputString ) ) ) );
		return mysql_real_escape_string( addslashes( trim( $inputString ) ) );
	}


	// filter output string
	public static function output( $outputString )
	{
		return nl2br( htmlentities( stripslashes( $outputString ) ) );
	}
	
	
	// filter string for reinput (e.g. read from database and put to input fields)
	public static function reinput( $inputString )
	{
		return htmlentities( stripslashes( $inputString ) );
	}
	
	
	// filter string for reinput (e.g. read from database and pass to Javascript)
	public static function reinputToJS( $inputString )
	{
		return addslashes( self::reinput( $inputString ) );
	}


	// functions to develop
	public static function formToDB( $inputString )
	{
	}

	public static function stringToDB( $inputString )
	{
	}

	public static function dbToHTML( $inputString )
	{
	}

	public static function dbToJS( $inputString )
	{
	}

	public static function dbToForm( $inputString )
	{
	}
}
?>

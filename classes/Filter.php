<?php

//----------------------------------------------------------------------------------------------------------------
// class definition for input/output filtering
//----------------------------------------------------------------------------------------------------------------
class Filter
{
	//------------------------------------------------------------------------------------------------------------
	// filter input string (i.e. from input form or URL parameter to database)
	//------------------------------------------------------------------------------------------------------------
	public static function input( $inputString ) {
		//return mysql_real_escape_string(addslashes(strip_tags(trim($inputString))));
		//return mysql_real_escape_string(addslashes(trim($inputString)));
		return addslashes(trim($inputString));
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// filter output string (i.e. from database to screen)
	//------------------------------------------------------------------------------------------------------------
	public static function output( $outputString ) {
		return nl2br(htmlentities(stripslashes($outputString)));
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// filter string for re-input (i.e. from database to input form)
	//------------------------------------------------------------------------------------------------------------
	public static function reinput( $inputString ) {
		return htmlentities(stripslashes($inputString));
	}
	
	
	//------------------------------------------------------------------------------------------------------------
	// filter string for re-input (i.e. from database to Javascript)
	//------------------------------------------------------------------------------------------------------------
	public static function reinputToJS( $inputString ) {
		return addslashes(self::reinput($inputString));
	}
	
	
	/*
	// methods to develop
	public static function formToDB( $inputString ) {
	}
	
	
	public static function stringToDB( $inputString ) {
	}
	
	
	public static function dbToHTML( $inputString ) {
	}
	
	
	public static function dbToJS( $inputString ) {
	}
	
	
	public static function dbToForm( $inputString ) {
	}
	*/
}

?>

<?php
// automatic loading of classes and required files


// always load application constants (constants.php)
require_once( "controls/constants.php" );

// always load client-specific settings (client_settings.php)
require_once( "controls/client_settings.php" );

// always load global functions (global.php)
require_once( "controls/global.php" );


// specify system timezone
date_default_timezone_set( TIMEZONE );


// function for automatically loading classes as they are called
function __autoload( $className )
{
	if ( file_exists( "classes/" . $className . ".php" ) ) {
		require_once( "classes/" . $className . ".php" );
	} elseif ( file_exists( "libraries/" . $className . "/" . $className . ".php" ) ) {
		require_once( "libraries/" . $className . "/" . $className . ".php" );
	}
}

?>

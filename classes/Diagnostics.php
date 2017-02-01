<?php
// error types
define( "ERROR", "Error:" );
define( "FATAL_ERROR", "Fatal Error:" );
define( "WARNING", "Warning:" );
define( "EXCEPTION", "Exception:" );

// predefined resolution messages
define( "PROGRAM_ERROR", "If this error continues to appear, please contact the programmers." );
define( "SYSTEM_ERROR", "If this error continues to appear, please contact your Administrator." );


// class definition for error handling
class Diagnostics
{
	private static $abortProcessing = true;


	public static function toggleAbortProcessing( $setting )
	{
		self::$abortProcessing = $setting;
	}


	public static function error( $displayType,		// 'dialog' or null (page)
						   $errorType,			// ERROR, FATAL-ERROR, WARNING, or EXCEPTION
						   $errorMessage,		// main error message
						   $errorDetails,		// error details
						   $resolution )		// possible resolution or action, pass PROGRAM or ADMIN
	{
		// display error message (requires dialog.js)
		if ( $displayType == "dialog" )
		{
			echo "<span id=\"error_type\">" . $errorType . "</span> " . $errorMessage . "<br />\n";
			echo $errorDetails . "<br /><br />\n";
			echo $resolution;
		}
		else
		{
?>		<div id="error_dialog">
			<div id="error_line">
				<span id="error_type"><?php echo $errorType ?></span>
				<span id="error_message"><?php echo $errorMessage ?></span>
			</div>
			<div id="error_details"><?php echo $errorDetails ?></div>
			<div id="error_resolution"><?php echo $resolution ?></div>
		</div>
<?php

			// abort
			if ( self::$abortProcessing ) {
				echo "</body>\n";
				echo "</html>";
				exit();
			}
		}
	}
}
?>

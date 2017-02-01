<?php
	// Asynchronous JavaScript and XML (AJAX) functionality


	// set base directory to the location of index.php
	chdir( "../" );


	// includes
	require( "controls/autoload.php" );
	
	
	// recreate user object, used for event logging
	if ( !isset( $_SESSION ) ) {
		$user = new User();
	}
	
	
	// check if required parameter is passed
	if ( isset( $_POST['phpFunction'] ) )
	{
		// call appropriate function or class method
		call_user_func( $_POST['phpFunction'] );
	}


	// check if required parameter is passed
	/*if ( isset( $_POST['phpFunction'] ) )
	{
		// prepare parameter
		$parameter = array();
		$i = 1;


		// construct parameters as array
		while( isset( $_GET['p'.$i] ) )
		{
			array_push( $parameter, $_GET['p'.$i] );
			$i++;
		}


		// call appropriate function or class method
		if ( sizeof( $parameter ) > 0 )
			call_user_func( $_GET['f'], $parameter );
		else
			call_user_func( $_GET['f'] );
	}*/
?>

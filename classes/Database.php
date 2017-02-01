<?php
// includes
require_once( "classes/Diagnostics.php" );


// class definition for Database operations
class Database
{
	private $connection;				// handle for database connection
	private $resultSet;					// handle for result set
	private $testMode;					// flag for test mode, no actual query


	// constructor
	public function __construct()
	{
		// open connection to database
		$this->connection = mysql_connect( DATABASE_SERVER, "root", "" );
		if ( !$this->connection )
			Diagnostics::error( NULL, FATAL_ERROR, "Unable to connect to database server <b>" . DATABASE_SERVER . "</b>",
								mysql_error(),
								"Please verify if database application is running on server.<br />" . SYSTEM_ERROR );

		// select database to use
		if ( !mysql_select_db( DATABASE_NAME ) )
			Diagnostics::error( NULL, FATAL_ERROR, "Unable to select database <b>" . DATABASE_NAME . "</b>",
								mysql_error() . "\nDatabase might be deleted or corrupted.",
								"You might need to recreate or restore database from backup.<br />" . SYSTEM_ERROR );

		$this->testMode = FALSE;		// disable test mode initially
	}


	// execute general SQL queries
	public function query( $sqlQuery )
	{
		if ( !$this->testMode )
		{
			// free memory from previous query
			if ( is_resource( $this->resultSet ) )
				mysql_free_result( $this->resultSet );

			// execute query
			$this->resultSet = mysql_query( $sqlQuery );
			if ( !$this->resultSet && substr( $sqlQuery, 0, 6 ) != "DELETE" )
				Diagnostics::error( NULL, ERROR, "Unable to perform your requested operation",
									"Query: " . $sqlQuery . "<br />" . mysql_error(),
									PROGRAM_ERROR );

			// for SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset, a resource is returned
			// for INSERT, UPDATE, DELETE, CREATE, and DROP, boolean true (success) or false (failed) is returned
			return $this->resultSet;
		}
		else		// test mode, display only the query
		{
			echo "<p>" . $sqlQuery . "</p>";
			return NULL;
		}
	}


	// enable or disable test mode
	public function setTestMode( $testMode = TRUE )
	{
		$this->testMode = $testMode;
	}



	public function getLastInsertID()
	{
		return mysql_insert_id();
	}


	// return result count from the passed result set parameter
	public function getResultCount( $resultSet )
	{
		if ( is_resource( $resultSet ) )
			return mysql_num_rows( $resultSet );
		else
			return 0;
	}


	// return a row of result from the passed result set parameter
	// the record pointer in result set will increment per call
	public function getResultRow( $resultSet )
	{
		if ( is_resource( $resultSet ) )
			return mysql_fetch_assoc( $resultSet );
		else
			return NULL;
	}


	// destructor
	public function __destruct()
	{
		// free result set
		if ( is_resource( $this->resultSet ) )
			mysql_free_result( $this->resultSet );

		// close connection to database
		if ( is_resource( $this->connection ) )
			mysql_close( $this->connection );
	}
}
?>

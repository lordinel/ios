<?php
chdir( $_SERVER['DOCUMENT_ROOT'] . '/ios' );
require_once( 'controls/autoload.php' );


// check if user is logged in
$user = new User();
if ( !$user->isLoggedIn() ) {
	redirectToHomePage( 'action=nosession' );
}


// check if data parameter is set
if ( !isset( $_GET['data'] ) ) {
	redirectToHomePage();
}


// disable timeout of processing
set_time_limit ( 0 );


// contants for Excel worksheet
define( "EXCEL_FILE_TIMESTAMP_FORMAT", 		'Y-m-d hiA' );
define( "EXCEL_HEADING_TIMESTAMP_FORMAT", 	'F d, Y h:i A (T)' );

define( "EXCEL_FILE_SUBJECT",		PROG_NAME . " Report File" );
define( "EXCEL_FILE_DESCRIPTION",	PROG_NAME . " Auto-Generated File (Do not edit)" );
define( "EXCEL_FILE_KEYWORDS",		PROG_NAME );
define( "EXCEL_FILE_CATEGORY",		PROG_NAME . " Auto-Generated File" );

define( "EXCEL_DEFAULT_FONT_NAME",	"Calibri" );
define( "EXCEL_DEFAULT_FONT_SIZE",	"10" );

define( "EXCEL_HEADER_FONT_NAME",		 "Cambria" );
define( "EXCEL_HEADER1_FONT_SIZE",		 "16" );
define( "EXCEL_HEADER2_FONT_SIZE",		 "12" );
define( "EXCEL_HEADER3_FONT_SIZE",		 "11" );
define( "EXCEL_HEADER_BACKGROUND_COLOR", "CCFFCC" );

define( "EXCEL_COLUMN_HEADER_FONT_COLOR",		 "FFFFFF" );
define( "EXCEL_COLUMN_HEADER_BACKGROUND_COLOR",  "00CCFF" );

define( "EXCEL_COMMENT_FONT_SIZE",	"10" );

define( "EXCEL_ALT_ROW",  		 		  "0" );	// set to 0 to disable alternating color
define( "EXCEL_ALT_ROW_BACKGROUND_COLOR", "EEEEEE" );

define( "EXCEL_DATE_FORMAT_INPUT", 	"Y-m-d" );
define( "EXCEL_DATETIME_FORMAT_INPUT", 	"n/j/Y g:i A" );
//define( "EXCEL_DATE_FORMAT", 		"MMM DD, YYYY" );
define( "EXCEL_DATE_FORMAT", 		"m/d/yyyy" );
define( "EXCEL_DATETIME_FORMAT", 	"m/d/yyyy h:mm AM/PM" );
define( "EXCEL_INT_FORMAT", 		"#,##0" );
define( "EXCEL_CURRENCY_FORMAT", 	"#,##0.000" );

// set cookie to indicate start of download
setcookie( "excelDownloadProgress", 100, time()+10, "/");     // expire after 10 sec

// determine data parameter value and corresponding class method to call
switch ( $_GET['data'] )
{
	case 'order_list'		:	Order::exportListToExcel( $user->getUserID(), $_GET );                  break;
	case 'order_details'    :   Order::exportDetailsToExcel( $user->getUserID(), $_GET['orderID'] );    break;
	case 'purchase_list'	:	Purchase::exportListToExcel( $user->getUserID(), $_GET );               break;
	case 'purchase_details' :   Purchase::exportDetailsToExcel( $user->getUserID(), $_GET['purchaseID'] );    break;
	case 'customer_list'	:	Customer::exportListToExcel( $user->getUserID(), $_GET );               break;
	case 'supplier_list'	:	Supplier::exportListToExcel( $user->getUserID(), $_GET );               break;
	case 'inventory_list'	:	Inventory::exportListToExcel( $user->getUserID(), $_GET );              break;
	case 'agent_list'	    :	Agent::exportListToExcel( $user->getUserID() );                         break;
	case 'profit_calc'      :   Report::exportProfitReportToExcel( $user->getUserID(), $_GET );         break;
	default					:	redirectToHomePage();
}


// end with exit to prevent unrecoverable data prompt in Excel
exit;
?>

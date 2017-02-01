// dialog.js
// Functions for dialog box


// global variables
var TIMER = 10;
var FADEIN_SPEED = 5;
var FADEOUT_SPEED = 3;
var WRAPPER = "body_id";		// wrapper div ID


// calculate the current window width
function getPageWidth()
{
	if ( window.innerWidth != null )
		return window.innerWidth;
	else if ( document.documentElement && document.documentElement.clientWidth )
		return document.documentElement.clientWidth;
	else if ( document.body != null )
		return document.body.clientWidth;
	else
		return null;
}


// calculate the current window height
function getPageHeight()
{
	if ( window.innerHeight != null )
		return window.innerHeight;
	else if ( document.documentElement && document.documentElement.clientHeight )
		return document.documentElement.clientHeight;
	else if ( document.body != null )
		return document.body.clientHeight;
	else
		return null;
}


// calculate the current window vertical offset
function getTopPosition()
{
	if ( typeof window.pageYOffset != "undefined" )
		return window.pageYOffset;
	else if ( document.documentElement && document.documentElement.scrollTop )
		return document.documentElement.scrollTop;
	else if ( document.body.scrollTop )
		return document.body.scrollTop;
	else
		return 0;
}


// calculate the position starting at the left of the window
function getLeftPosition()
{
	if ( typeof window.pageXOffset != "undefined" )
		return window.pageXOffset;
	else if ( document.documentElement && document.documentElement.scrollLeft )
		return document.documentElement.scrollLeft;
	else if ( document.body.scrollLeft )
		return document.body.scrollLeft;
	else
		return 0;
}


// build/show the dialog box, populate the data and call the fadeDialog function
function showDialog( title, message, type, autohide )
{
	if ( !type )
		type = 'error';

	var dialog;
	var dialogHeader;
	var dialogClose;
	var dialogTitle;
	var dialogContent;
	var dialogMessage;
	var dialogMask;

	if ( !document.getElementById( "dialog" ) )		// div#dialog is not existing, create
	{
		// create dialog window
		dialog = document.createElement( "div" );
		dialog.id = 'dialog';

		// create header
		dialogHeader = document.createElement( "div");
		dialogHeader.id = 'dialog_header';

		// create header title
		dialogTitle = document.createElement( "div" );
		dialogTitle.id = 'dialog_title';

		// create header close button
		dialogClose = document.createElement( "div" );
		dialogClose.id = 'dialog_close'

		// create content area
		dialogContent = document.createElement( "div" );
		dialogContent.id = 'dialog_content';

		// create message area
		dialogMessage = document.createElement( "div" );
		dialogMessage.id = 'dialog_message';

		// create overlay masking
		dialogMask = document.createElement( "div" );
		dialogMask.id = 'dialog_mask';

		// add dialog and mask to body
		document.body.appendChild( dialogMask );
		document.body.appendChild( dialog );

		// add header and content to dialog
		dialog.appendChild(dialogHeader);
		dialog.appendChild(dialogContent);

		// add title and close button to header
		dialogHeader.appendChild(dialogTitle);
		dialogHeader.appendChild(dialogClose);

		// add message area to content
		dialogContent.appendChild(dialogMessage);

		// add close functionality
		dialogClose.setAttribute('onclick','hideDialog()');
		dialogClose.onclick = hideDialog;
	}
	else
	{
		dialog = document.getElementById( "dialog" );
		dialogHeader = document.getElementById( "dialog_header" );
		dialogTitle = document.getElementById( "dialog_title" );
		dialogClose = document.getElementById( "dialog_close" );
		dialogContent = document.getElementById( "dialog_content" );
		dialogMessage = document.getElementById( "dialog_message" );
		dialogMask = document.getElementById( "dialog_mask" );

		// display dialog
		dialogMask.style.visibility = "visible";
		dialog.style.visibility = "visible";
	}

	// make dialog opaque
	dialog.style.opacity = .00;
	dialog.style.filter = "alpha(opacity=0)";
	dialog.alpha = 0;


	// position dialog
	var width = getPageWidth();
	var height = getPageHeight();
	var left = getLeftPosition();
	var top = getTopPosition();

	var dialogWidth = dialog.offsetWidth;
	var dialogHeight = dialog.offsetHeight;
	var topPosition = top + ( height / 3 ) - ( dialogHeight / 2 );
	// fix for long dialog
	if ( title.substr( 0, 17 ) == "Enter payment for" && type == "prompt" ) {
		topPosition = top + ( height / 5 ) - ( dialogHeight / 2 );
	}
	var leftPosition = left + ( width / 2 ) - ( dialogWidth / 2 );

	dialog.style.top = topPosition + "px";
	dialog.style.left = leftPosition + "px";


	// set message type
	dialogHeader.className = type + "_header";
	dialogTitle.innerHTML = title;
	dialogContent.className = type;
	dialogMessage.innerHTML = message;


	// set height of mask equal to wrapper div
	var content = document.getElementsByTagName( "body" )[0];
	dialogMask.style.height = content.offsetHeight + 'px';


	// set autoclose
	dialog.timer = setInterval( "fadeDialog(1)", TIMER );
	if ( autohide )
	{
		dialogClose.style.visibility = "hidden";
		window.setTimeout( "hideDialog()", ( autohide * 1000 ) );
	}
	else
		dialogClose.style.visibility = "visible";

	makeDialogDraggable();		// requires jQuery library
}


// hide the dialog box
function hideDialog()
{
	var dialog = document.getElementById( "dialog" );
	clearInterval( dialog.timer );
	dialog.timer = setInterval( "fadeDialog(0)", TIMER );
}


// fade-in the dialog box
function fadeDialog( flag )		// set flag to 1 for fade-in, 0 for fade out
{
	if ( flag == null )
		flag = 1;

	var dialog = document.getElementById( "dialog" );
	var dialogMask = document.getElementById( "dialog_mask" );
	var value;

	if ( flag == 1 )
		value = dialog.alpha + FADEIN_SPEED;
	else
		value = dialog.alpha - FADEOUT_SPEED;

	dialog.alpha = value;
	dialog.style.opacity = ( value / 100 );
	dialog.style.filter = "alpha(opacity=" + value + ")";

	if( value >= 99 )
	{
		clearInterval( dialog.timer );
		dialog.timer = null;
	}
	else if ( value <= 1 )
	{
		dialog.style.visibility = "hidden";
		document.getElementById( "dialog_mask" ).style.visibility = "hidden";
		clearInterval( dialog.timer );

		document.body.removeChild( dialog );
		document.body.removeChild( dialogMask );
	}
}


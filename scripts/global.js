// global.js
// Global JavaScript functions


// load global events
// bug: problem on event sequence. this is always the last to execute
/*$(document).ready( function() {
	var data = new Data();
	
	
	$('.input_float').each( function( index ) {
		$(this).bind({
			focus: function() {
				data.selectField( $(this), 'float' );
			},
			blur: function() {
				data.validateField( $(this), 'float' );
			}
		});
	});
	
});*/


var bestInputStyle = "#00CC66";
var goodInputStyle = "#000000";
var badInputStyle  = "#FF0000";



function getElementIndex( element )
{
	elementID = element.attr( 'id' );
	return parseInt( elementID.substr( elementID.lastIndexOf( '_' ) + 1 ) );
}



// navigation bar script
var menuIDs = ["treemenu1"]

function buildsubmenus_horizontal()
{
	for ( var i = 0; i < menuIDs.length; i++ )
	{
		if ( document.getElementById( menuIDs[i] ) != null )
		{
			var ulTags = document.getElementById( menuIDs[i] ).getElementsByTagName( "ul" );
	
			for ( var t = 0; t < ulTags.length; t++ )
			{
				if ( ulTags[t].parentNode.parentNode.id == menuIDs[i] )
				{
					ulTags[t].style.top = ulTags[t].parentNode.offsetHeight + "px";
					ulTags[t].parentNode.getElementsByTagName( "a" )[0].className = "mainfoldericon";
				}
				else
				{ // else if this is a sub level menu (ul)
					ulTags[t].style.left = ulTags[t-1].getElementsByTagName( "a" )[0].offsetWidth + "px";
					// position menu to the right of menu item that activated it
					ulTags[t].parentNode.getElementsByTagName( "a" )[0].className = "subfoldericon";
				}
	
				ulTags[t].parentNode.onmouseover = function()
				{
					this.getElementsByTagName( "ul" )[0].style.visibility = "visible";
				}
	
				ulTags[t].parentNode.onmouseout = function()
				{
					this.getElementsByTagName( "ul" )[0].style.visibility = "hidden";
				}
			}
		}
	}
}


if ( window.addEventListener )
	window.addEventListener( "load", buildsubmenus_horizontal, false )
else if ( window.attachEvent )
	window.attachEvent( "onload", buildsubmenus_horizontal )



// redirect page
function redirect( page, parameter, paramValue )
{
	redirectTo = page;
	if ( parameter != null && paramValue != null ) {
		redirectTo = redirectTo + "?" + parameter + "=" + paramValue;
	}
	
	document.location = redirectTo;
}


// get URL variables/parameters
function getUrlParameters()
{
	var parameters = [], hash;
	var hashes = window.location.href.slice( window.location.href.indexOf( '?' ) + 1 ).split( '&' );

	for(var i = 0; i < hashes.length; i++)
	{
		hash = hashes[i].split( '=' );
		parameters.push( hash[0] );
		parameters[ hash[0] ] = decodeURIComponent( hash[ 1 ] );
	}

	return parameters;
}



var TIMEOUT = 0.5;					// key-up timeout, in seconds
var waitTimeStarted = false;
var previousValue = "";
var timeoutID;

function autoSuggest( phpFunction, sourceID, targetID )
{
	var sourceContent = encodeURIComponent( escape( document.getElementById( sourceID ).value ) );

	if ( sourceContent == "" )
	{
		//document.getElementById( "autosuggest_customer" ).style.display = "none";
		document.getElementById( targetID ).innerHTML = "";			// clear suggestions if no input
		waitTimeStarted = false
		previousValue = "";
		window.clearTimeout( timeoutID );
	}
	else
	{
		if ( sourceContent != previousValue )		// check if input had changed, do not query to database if not
		{
			if ( waitTimeStarted == false )
			{
				waitTimeStarted = true;
				timeoutID = window.setTimeout( "displaySuggestions('" + phpFunction + "','" + sourceContent + "','" +
											   targetID + "')", TIMEOUT * 1000 );
			}
			else
			{
				window.clearTimeout(timeoutID);		// reset timer
				timeoutID = window.setTimeout( "displaySuggestions('" + phpFunction + "','" + sourceContent + "','" +
											   targetID + "')", TIMEOUT * 1000 );
			}

			previousValue = sourceContent;
		}
	}
}


function displaySuggestions( phpFunction, sourceContent, targetID )
{
	//document.getElementById( "autosuggest_customer" ).style.display = "block"
	ajax( 'autosuggest', targetID, 'innerHTML', phpFunction, 'searchString=' + unescape( sourceContent ) );
	waitTimeStarted = false;
}



function checkSearchText()
{
	if ( escape( document.getElementById( "search_text" ).value ) != "" )
		return true
	else
	{
		document.getElementById( "search_text" ).focus();
		return false;
	}
}



function numberFormat(nStr)
{
  nStr += '';
  x = nStr.split('.');
  x1 = x[0];
  x2 = x.length > 1 ? '.' + x[1] : '';
  var rgx = /(\d+)(\d{3})/;
  while (rgx.test(x1))
    x1 = x1.replace(rgx, '$1' + ',' + '$2');
  return x1 + x2;
}


function stripNonNumeric( str )
{
  str += '';
  var rgx = /^\d|\.|-$/;
  var out = '';
  for( var i = 0; i < str.length; i++ ){
    if( rgx.test( str.charAt(i) ) ){
      if( !( ( str.charAt(i) == '.' && out.indexOf( '.' ) != -1 ) ||
             ( str.charAt(i) == '-' && out.length != 0 ) ) ){
        out += str.charAt(i);
      }
    }
  }
  return out;
}


// dynamically load JS and CSS file
function loadjscssfile(filename, filetype){
	if (filetype=="js"){ //if filename is a external JavaScript file
		var fileref=document.createElement('script')
		fileref.setAttribute("type","text/javascript")
		fileref.setAttribute("src", filename)
	}
	else if (filetype=="css"){ //if filename is an external CSS file
		var fileref=document.createElement("link")
		fileref.setAttribute("rel", "stylesheet")
		fileref.setAttribute("type", "text/css")
		fileref.setAttribute("href", filename)
	}
	if (typeof fileref!="undefined")
		document.getElementsByTagName("head")[0].appendChild(fileref)
}


// jQuery extension for downloading a file
jQuery.download = function(url, data, method){
	//url and data options required
	if( url && data ){
		//data can be string of parameters or array/object
		data = typeof data == 'string' ? data : jQuery.param(data);
		
		//split params into form inputs
		var inputs = '';
		jQuery.each(data.split('&'), function(){
			var pair = this.split('=');
			inputs+='<input type="hidden" name="'+ pair[0] +'" value="'+ pair[1] +'" />';
		});
		
		//send request
		jQuery('<form action="'+ url +'" method="'+ (method||'post') +'">'+inputs+'</form>')
			.appendTo('body').submit().remove();
	}
};


function exportToExcelConfirm( parameters )
{
	$('#dialog_close').hide();
	$('#dialog_message').html( 'Please wait while processing your request' );

	document.location = 'services/export_to_excel.php?' + parameters;
	//$.download( 'services/export_to_excel.php', parameters, 'get' );
	
	var counter       = 0;
	var INTERVAL      = 100;     // in milliseconds
	var PROGRESS_STEP = 500;     // in milliseconds
	var RESET_COUNTER = 5;

	var excelDownloadCheck = window.setInterval(function() {
		// show progress indicator
		if (counter % PROGRESS_STEP == 0) {
			var numberOfDots = counter / PROGRESS_STEP;
			var progressIndicator = '';
			for (var i = 0; i < numberOfDots; i++ ) {
				progressIndicator = progressIndicator + '.';
			}
			$('#dialog_message').html('Please wait while processing your request' + progressIndicator);
			if (numberOfDots == RESET_COUNTER) {
				counter = 0;       // to make counter zero
			}
		}
		counter = counter + INTERVAL;
		
		// check if download cookie is already set
		if ($.cookie('excelDownloadProgress')) {
			excelDownloadProgress($.cookie('excelDownloadProgress'));
		}
	}, INTERVAL);

	function excelDownloadProgress(percentComplete) {
		if ( percentComplete == 100 ) {
			window.clearInterval(excelDownloadCheck);
			$.removeCookie('excelDownloadProgress', { path: '/' });
			hideDialog();
		}
	}
}



function modifyBrowserHistory( parameters )
{
	/*page = document.location.pathname;
	urlParameter = "";
	
	idParam = getUrlParameters()['id'];
	
	if ( idParam != null ) {
		urlParameter = "?id=" + idParam;
	}
	
	if ( parameters != null ) {
		if ( urlParameter == "" ) {
			urlParameter = "?" + arguments[0];
		} else {
			urlParameter = urlParameter + "&" + arguments[0];
		}
		
		for ( var i = 1; i < arguments.length; i++ ) {
			urlParameter = urlParameter + "&" + arguments[i];
		}
	}
	
	/*var date = new Date();
	var unixEpoch = '' + date.getTime();
	var securityHash = hex_md5( unixEpoch.substring( 0, 10 ) );
	urlParameter = urlParameter + "&hash=" + securityHash;
	
	var stateObj;
	window.history.replaceState( stateObj, "IOS", page + urlParameter );*/
}



function showFilterIndicator( obj )
{
	$('span.selected_filter').removeClass( "selected_filter" );
	$(obj).parent('span.filter_link').addClass( "selected_filter" );
}


var HEADER_OFFSET = 70;

window.onscroll = function()
{
	if ( $(window).scrollTop() > HEADER_OFFSET ) {
		$('#nav').css({
			'position':'fixed',
			'top':'0px',
			'width':'100%'
		});
	} else if ( $(window).scrollTop() <= HEADER_OFFSET ) {
		$('#nav').css({
			'position':'',
			'top':'',
			'width':''
		});
	}
}

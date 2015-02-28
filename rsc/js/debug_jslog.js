/**
 * This javascript gets included in debug jslog mode.
 * b2evolution - http://b2evolution.net/
 * @version $Id: debug_jslog.js 6934 2014-06-19 12:16:47Z yura $
 */


/**** AJAX Debug functions ****/
var debug_ajax_request_number = 1;

jQuery( document ).ready( function()
{
	jQuery( 'a.jslog_switcher' ).click( function()
	{	// Click event of link "JS Log" in the eveobar (Hide & Show log)
		jQuery( 'div#debug_ajax_info' ).toggle();
		jQuery.cookie( 'jslog_style', jQuery( 'div#debug_ajax_info' ).attr( 'style' ), { path: '/' } );
		if( jQuery( 'div#debug_ajax_info' ).is( ':hidden' ) )
		{	// Delete cookie with jslog password
			jQuery.cookie( 'jslog', null, { path: '/' } );
		}
		return false;
	} );

	jQuery( 'a.jslog_clear' ).click( function()
	{	// Clear the log
		jQuery( 'div#jslog_container' ).html( '' );
		return false;
	} );

	var jslog_style_top;
	jQuery( 'div#debug_ajax_info' ).resizable(
	{	// Plugin to resize
		resize: function( event, ui )
		{
			var new_height = jQuery( this ).height() - 40;
			jQuery( 'div#debug_ajax_info #jslog_container' ).css( { 'height': new_height, 'max-height': new_height } );
		},
		stop: function( event, ui )
		{	// Write the jslog block styles into the cookies
			jQuery( this ).css( { 'position': 'fixed', top: jQuery( this ).offset().top - jQuery( window ).scrollTop() } );
			jQuery.cookie( 'jslog_style', jQuery( this ).attr( 'style' ), { path: '/' } );
		}
	} );
	jQuery( 'div#debug_ajax_info' ).draggable(
	{	// Plugin to drag
		snap: '#evo_toolbar',
		handle: 'div.jslog_titlebar',
		stop: function( event, ui )
		{	// Write the jslog block styles into the cookies
			jQuery.cookie( 'jslog_style', jQuery( this ).attr( 'style' ), { path: '/' } );
		}
	} );

	var debug_ajax_date_start = new Date();
	jQuery( document ).ajaxStart( function()
	{ // Save a start time of execution
		debug_ajax_date_start = new Date();
	} );

	jQuery( document ).ajaxSuccess( function( event, request, settings )
	{ // AJAX request is success, Add debug info into the list
		var debug_ajax_date_end = new Date();

		var log = '<h4>Request #' + debug_ajax_request_number + ':</h4>';
		log += '<b>request time</b>: ' + get_formated_date( debug_ajax_date_start ) + '<br />';
		log += '<b>response time</b>: ' + get_formated_date( debug_ajax_date_end ) + '<br />';
		log += '<b>roundtrip time</b>: ' + get_interval_time( debug_ajax_date_start, debug_ajax_date_end ) + '<br />';
		log += '<b>url</b>: ' + settings.url + '<br />';
		log += '<b>' + settings.type + ' data</b>: ' + settings.data + '<br />';
		if( settings.dataType != undefined )
		{
			log += '<b>response data type</b>: ' + settings.dataType + '<br />';
		}
		log += ajax_debug_extract_log( request.responseText, 'div#debug_ajax_info' );

		ajax_debug_info_add( log );
	} );

	jQuery( document ).ajaxError( function( event, request, settings, thrownError )
	{ // AJAX request is failed, Add debug info into the list
		jQuery( 'div#debug_ajax_info' ).show();

		var log = '<h4 class="error">Request ERROR #' + debug_ajax_request_number + ':</h4>';
		log += '<b>url</b>: ' + settings.url + '<br />';
		log += '<b>' + settings.type + ' data</b>: ' + settings.data + '<br />';
		log += '<b>error</b>: <b class="red">' + thrownError + '</b><br />';
		log += ajax_debug_extract_log( request.responseText, 'div#debug_ajax_info' );

		ajax_debug_info_add( log );
	} );
} );

/**
 * Add info data to the ajax debug info
 *
 * @param string Info
 */
function ajax_debug_info_add( log )
{
	var debug_info_div = jQuery( 'div#debug_ajax_info' );

	// Insert a new log info
	jQuery( '#jslog_container' ).append( log );
	// Scroll a list to bottom
	debug_info_div.scrollTop( debug_info_div[0].scrollHeight );

	debug_ajax_request_number++;
}


/**
 * Extract JS log from response text
 *
 * @param string Response text
 * @param object Object of div with JS log
 * @return string JS log text
 */
function ajax_debug_extract_log( text, div_obj )
{
	var log = '';
	var data = jQuery( '<div/>' );
	data.html( text.replace( /(<script[^>]+><\/script>|<link[^>]+\/>)/gim, '' ) );
	var jslog = data.find( 'div.jslog' );

	if( jslog.length > 0 )
	{ // AJAX Debug exist in the response text
		log += '<p>Remote debug info:</p>';
		log += jslog.html();

		if( jslog.find( 'ul.jslog_error' ).length > 0 )
		{ // AJAX Response has the errors
			jQuery( div_obj ).show();
		}
	}

	return log;
}


/**
 * Get date in format Y-m-d H:i:s.ms
 *
 * @param object Date
 * @return string Date
 */
function get_formated_date( Date )
{
	return Date.getFullYear() + '-' + ( Date.getMonth() + 1 )+ '-' + Date.getDate() + ' ' + 
			Date.getHours() + ':' + Date.getMinutes() + ':' + Date.getSeconds() + '.' + Date.getMilliseconds();
}


/**
 * Get time beetween two dates in format #minutes #seconds #miliseconds
 *
 * @param object Date start
 * @param object Date end
 * @return string Time
 */
function get_interval_time( Date_start, Date_end )
{
	var time = Date_end.getTime() - Date_start.getTime();

	var ms = time % 1000;
	var seconds = ( time % 60000 - ms ) / 1000;
	var minutes = ( time - seconds * 1000 - ms ) / 60000;

	var result = ms + ' ms';
	if( seconds > 0 )
	{	// Display seconds
		result = seconds + 'sec ' + result;
	}

	if( minutes > 0 )
	{	// Display minutes
		result = minutes + 'min ' + result;
	}

	return result;
}
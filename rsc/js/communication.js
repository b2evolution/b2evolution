/**
 * Server communication functions - Ajax without the pain
 * b2evolution - http://b2evolution.net/
 * @author yabs {@link http://innervisions.org.uk/ }
 * @version $Id: communication.js 1329 2012-05-02 17:37:41Z yura $
 */


/**
 * Init : adds required elements to the document tree
 *
 */
jQuery(document).ready(function()
{
	// placeholder for error/success messages:
	jQuery( '<div id="server_messages"></div>' ).prependTo( '.pblock' );
	// used for POST requests:
	jQuery( '<iframe id="server_postback" name="server_postback"></iframe>' ).appendTo( 'body' ).css( { position:'absolute',left:"-1000em",top:"-1000em" } );
});


/**
 * Sends a javascript request to admin
 *
 * @param string ctrl Admin control to send request to
 * @param string action Action to take
 * @param string query_string Any extra data
 * @param boolean Whether to prevent caching of the result. Default: true.
 */
function SendAdminRequest( ctrl, action, query_string, nocache )
{
	if( nocache === undefined || nocache )
	{
		var datetime = new Date();
		query_string += ( query_string !== '' ? '&' : '' ) + 'nocache_dummy=' + datetime.getTime();
	}

	SendServerRequest( b2evo_dispatcher_url + '?ctrl='+ctrl+'&action='+action+( query_string ? '&'+query_string : '' ) );
}


/**
 * Sends a javascript request to the server
 *
 * @param string the url to request
 */
function SendServerRequest( url )
{
	// add a & to the URL if we already have a query string, otherwise add a ?
	url += ( url.indexOf( '?' ) != -1 ) ? '&' : '?';
	url += 'display_mode=js'; // add flag for js display mode
	var data = url.split( '?' );
	url = data[0];
	data = data[1];

	jQuery.ajax(
	{	// Load JavaScript via AJAX request
		// It gives the adding a log into "JS log"
		// The script is executed if AJAX request is completed
		type: 'POST',
		url: url,
		data: data,
		dataType: 'script'
	} );
}


/**
 * Send a forms request as javascript request
 *
 * @param string DOM ID of form to attach to
 */
function AttachServerRequest( whichForm )
{
	jQuery( '<input type="hidden" name="display_mode" value="js" /><input type="hidden" name="js_target" value="window.parent." />' ).appendTo( '#' + whichForm );	// add our inputs
	jQuery( '#'+whichForm ).attr( 'target', 'server_postback' ); // redirect form via hidden iframe
}


/**
 * Displays Messages ( @see Log::display() )
 *
 * @param string message The html to display
 */
function DisplayServerMessages( messages )
{	// display any server messages and highlight them
	jQuery( '#server_messages' ).html( messages );

	// highlight success message
	jQuery( '#server_messages .log_success' ).animate({
			backgroundColor: "#88ff88"
		},"fast" ).animate({
			backgroundColor: "#ffffff"
		},"fast", "", function(){jQuery( this ).removeAttr( "style" );
	});

	// highlight error message
	jQuery( '#server_messages > .log_error' ).animate({
			backgroundColor: "#ff8888"
		},"fast" ).animate({
			backgroundColor: "#ffffff"
		},"fast", "", function(){jQuery( this ).removeAttr( "style" );
	});
}


/**
 * Potential replacement code ( once finished )
 */
var _b2evoCommunications = function()
{
	var me; // reference to self

	var _delay_default = 2500; // default buffer delay in milli seconds
	var _interval_default = 250; // default delay buffer ticker interval
	var _dispatcher; // admin url

	return {
		/**
		 * Initialise the helper object
		 * Adds any translation strings found in html
		 *
		 * @param delay (int) buffered server call delay in milliseconds
		 * @param interval (int) buffered server call ticker interval in milliseconds
		 */
		Init:function()
		{
			// set available params to defaults
			var params = jQuery.fn.extend({
				// no comma after final entry or IE barfs
				delay:_delay_default,
				interval:_interval_default,
				dispatcher:_dispatcher
				}, ( arguments.length ? arguments[0] : '' ) );

			_delay_default = params.delay; // change default delay if required
			_interval_default = params.interval; // change interval if required
			_dispatcher = params.dispatcher; // store dispatcher

			me = this; // set reference to self

			b2evoHelper.info( 'Communications object ready' );
		},


		/**
		 * Enables server calls to be buffered
		 *
		 * @param ticker_callback (function) Called each time ticker occurs
		 * @param send_callback (function) Called when send event occurs
		 * @param delay (int) initial delay for buffer : defaults to _delay_default
		 * @param interval (int) initial ticker interval : defaults to _interval_default
		 * @param buffer_name (string) name for the buffer
		 */
		BufferedServerCall:function()
		{
			// set available params to defaults
			var params = jQuery.fn.extend({
					// no comma after final entry or IE barfs
					ticker_callback: function(){ return true; }, // callback for ticker checks
					send_callback: function(){}, // callback for sending
					delay: _delay_default, // time to buffer call for
					interval: _interval_default, // interval between polls
					buffer_name:'' // name for the buffer
					}, ( arguments.length ? arguments[0] : '' ) );

			if( ticker_status = params.ticker_callback( params.delay ) )
			{
				if( ticker_status !== true )
				{
					b2evoHelper.log( 'Ticker status : '+ticker_status );
				}
				switch( ticker_status )
				{
					case 'cancel' : // cancel the server call
						b2evoHelper.DisplayMessage( '<div class="log_message">'+T_( 'Update cancelled' )+'</div>' );
						return;

					case 'pause' : // pause the server call
						b2evoHelper.DisplayMessage( '<div class="log_error">'+T_( 'Update Paused' )+' : '+b2evoHelper.str_repeat( '.', params.delay / params.interval )+'</div>' );
						me.BufferedServerLoop( params );
						return;

					case 'ignore' : // don't change current message, ask again on next ticker
						me.BufferedServerLoop(params);
						return;

					case 'immediate' : // send call without delay
						break;

					default :
						params.delay -= params.interval;
						if( params.delay > 0 )
						{ // still buffered
							b2evoHelper.DisplayMessage( '<div class="log_error">'+T_( 'Changes pending' )+' : '+b2evoHelper.str_repeat( '.', params.delay / params.interval )+'</div>' );
							me.BufferedServerLoop(params);
							return;
						}
						// send the call;
						b2evoHelper.DisplayMessage( '<div class="log_message">'+T_( 'Saving changes' )+'</div>' );
						params.send_callback();
				}
			}
		},


		/**
		 * Callback to add params to relevant buffer so we can use timeout
		 *
		 * @param params (mixed) parameters from @func BufferedServerCall
		 */
		BufferedServerLoop:function( params )
		{
			var current_buffers = jQuery( me ).data( 'buffers' );
			if( typeof( current_buffers ) == 'undefined' )
			{
				current_buffers = Array();
			}
			current_buffers[ params.buffer_name ] = params; // store params
			jQuery( me ).data( 'buffers', current_buffers );
			window.setTimeout( 'b2evoCommunications.BufferedServerCallback( "'+params.buffer_name+'" )', params.interval );
		},


		/**
		 * Callback for timeout, calls @func BufferedServerCall with relevant params
		 *
		 * @param buffer_key (string) name of the buffer
		 */
		BufferedServerCallback:function( buffer_key )
		{
			var current_buffers = jQuery( me ).data( 'buffers' );
			me.BufferedServerCall( current_buffers[ buffer_key ] );
		},


		/**
		 * Send a request to admin
		 */
		SendAdminRequest:function()
		{
			// set available params to defaults
			var params = jQuery.fn.extend({
					// no comma after final entry or IE barfs
					ctrl: '', // destination admin control
					action: '', // action to be taken
					data: '', // associated data for the call
					key: '', // communications key for the request
					error:function(){ return false; }, // trigger for onerror
					ok:function(){ return false; } // trigger for 200
					}, ( arguments.length ? arguments[0] : '' ) );
			var data = "ctrl="+params.ctrl+"&key="+params.key+"&action="+params.action+"&"+params.data;
			me.SendServerRequest({
				url:_dispatcher,
				data:data,
				error:params.error,
				ok:params.ok
			});
		}, // SendAdminRequest


		/**
		 * Send a request to the server
		 */
		SendServerRequest:function(){
			// set available params to defaults
			var params = jQuery.fn.extend({
					// no comma after final entry or IE barfs
					url: '', // destination url
					data: '', // associated data for the call
					error:function(){ return false; }, // trigger for onerror
					ok:function(){ return false; } // trigger for 200
					}, ( arguments.length ? arguments[0] : '' ) );
			if( params.url )
			{ // we have a url
				params.url += ( params.url.indexOf( '?' ) === true ? '&' : '?' )+'mode=js';
				if( params.data )
				{ // we have some data
					params.url += '&'+params.data;
				}
				var script = jQuery( '<script type="text/javascript"></script>' );
				script.attr( 'src', params.url );
				script.load( params.ok() );
				script.error( params.error() );
				script.appendTo( 'body' );
				b2evoHelper.log( 'Sending request : '+params.url );
			}
		} // SendServerRequest
	}
} // _b2evoCommunications

// create instance of the communications object
var b2evoCommunications = new _b2evoCommunications();

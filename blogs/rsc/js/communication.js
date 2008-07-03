/**
 *	Server communication functions
 *
 * Ajax without the pain
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package main
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author yabs {@link http://innervisions.org.uk/ }
 */


/**
 * Init : adds required elements to the document tree
 *
 */
jQuery(document).ready(function()
{
	jQuery( '<div id="server_messages"></div>' ).prependTo( '.pblock' );// placeholder for error/success messages
	jQuery( '<iframe id="server_postback" name="server_postback"></iframe>' ).appendTo( 'body' ); // used for POST requests
	jQuery( '#server_postback' ).css( { position:'absolute',left:"-1000em",top:"-1000em" } );
});


/**
 * Sends a javascript request to admin
 *
 * @param string ctrl Admin control to send request to
 * @param string action Action to take
 * @param string query_string Any extra data
 */
function SendAdminRequest( ctrl, action, query_string )
{
	SendServerRequest( b2evo_dispatcher_url + '?ctrl='+ctrl+'&action='+action+( query_string ? '&'+query_string : '' ) );
}


/**
 * Sends a javascript request to the server
 *
 * @param string the url to request
 */
function SendServerRequest( url )
{
	if( url.indexOf( '?' ) )
	{	// we already have a query string
		url = url + '&';
	}
	var the_call = document.createElement( 'script' ); // create script element
	the_call.src = url+'display_mode=js'; // add flag for js display mode
	the_call.type = 'text/javascript'; // to be sure to be sure
	document.body.appendChild( the_call ); // add script to body and let browser do the rest
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

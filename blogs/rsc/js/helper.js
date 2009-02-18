/**
 * This file implements the b2evoHelper object
 *
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
 *
 * @version $Id$
 */


var _b2evoHelper = function()
{
	var me; // reference to self

	var _debug = false;

	var _t = Array(); // translation strings

	return {
		/**
		 * Initialise the helper object
		 * Adds any translation strings found in html
		 *
		 * @param debug (boolean) enable debug mode
		 */
		Init:function()
		{
			// set available params to defaults
			var params = jQuery.fn.extend({
				// no comma after final entry or IE barfs
				debug:false, // are we in debug mode?
				}, ( arguments.length ? arguments[0] : '' ) );

			_debug = params.debug; // set debug mode

			me = this; // set reference to self

			jQuery( '<div id="b2evoMessages" class="log_container"></div>' ).prependTo( '.pblock' );// placeholder for error/success messages

			jQuery( '#b2evo_translations div' ).each( function(){ // grab all translation strings
				var untranslated = jQuery( this ).find( '.b2evo_t_string' ).html();
				var translated = jQuery( this ).find( '.b2evo_translation' ).html();
				_t[ untranslated ] = translated;
			});

			me.info( 'Helper object ready' );
		},


		/**
		 * Displays messages
		 *
		 * @param message (string) message to display
		 */
		DisplayMessage:function( message )
		{
			jQuery( '#b2evoMessages' ).html( message );
		},


		/**
		 * replicates PHP's str_repeat() function
		 *
		 * @param data (string) string to repeat
		 * @param multiplier (int) number of repeats required
		 *
		 * @return the multiplied string
		 */
		str_repeat:function( data, multiplier )
		{
			if( multiplier = Math.floor( multiplier ) )
			{
				return new Array( multiplier + 1 ).join( data );
			}
			return false;
		},


		/**
		 * Translates a string
		 *
		 * @param untranslated (string) the string to be translated
		 *
		 * @return string translated string if available or original string
		 */
		T_:function( untranslated )
		{
			if( typeof( _t[ untranslated ] ) == 'string' )
			{ // we have a translation
				return _t[ untranslated ];
			}
			return untranslated;
		},


		/**
		 * Add Log message to console if enabled and in debug mode
		 *
		 * @param message (string) message to be added to the console
		 */
		log:function(message)
		{
			if( _debug && typeof( console ) == 'object' )
			{
				console.log( message );
			}
		},


		/**
		 * Add Error message to console if enabled and in debug mode
		 *
		 * @param message (string) message to be added to the console
		 */
		error:function(message)
		{
			if( _debug && typeof( console ) == 'object' )
			{
				console.error( message );
			}
		},


		/**
		 * Add Info message to console if enabled and in debug mode
		 *
		 * @param message (string) message to be added to the console
		 */
		info:function(message)
		{
			if( _debug && typeof( console ) == 'object' )
			{
				console.info( message );
			}
		}
	}
} // _b2evoHelper

// create instance of the helper
var b2evoHelper = new _b2evoHelper();


/*
 * $Log$
 * Revision 1.1  2009/02/18 10:48:25  yabs
 * Start of helper object.
 *
 */

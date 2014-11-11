/**
 * This file implements the b2evoHelper object
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @author yabs {@link http://innervisions.org.uk/ }
 * @version $Id: helper.js 9 2011-10-24 22:32:00Z fplanque $
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

			jQuery( '<div id="b2evoMessages"></div>' ).prependTo( '.pblock' );// placeholder for error/success messages

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
			jQuery( '#b2evoMessages' ).addClass( "log_container" ).html( message );
		},


		/**
		 * Displays Server messages
		 *
		 * @param message (string) message to display
		 */
		DisplayServerMessage:function( message )
		{
			jQuery( '#b2evoMessages' ).removeClass( "log_container" ).html( message );
		},


		/**
		 * Fades out the selected element(s)
		 * For available effects see @link http://http://docs.jquery.com/Effects/animate#paramsoptions
		 */
		FadeOut:function()
		{
			// set available params to defaults
			var params = jQuery.fn.extend({
				// no comma after final entry or IE barfs
				selector:'', // jQuery selector for objects to fade
				start:{}, // params for start see @link http://http://docs.jquery.com/Effects/animate#paramsoptions
				end:{}, // params for end see @link http://http://docs.jquery.com/Effects/animate#paramsoptions
				callback:function(obj){}, // callback function when finished, will be passed the faded object
				remove_style: true // remove style attribute when finished
				}, ( arguments.length ? arguments[0] : '' ) );
			jQuery( params.selector ).animate( params.start, {
					duration:"fast",
					complete:function(){
						jQuery( this ).animate( params.end,{
							duration:"fast",
							complete:function(){
								if( params.remove_style )
								{
									jQuery( this ).removeAttr( 'style' );
								}
								params.callback( this ); // trigger callback
							}
						});
					}
			});
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

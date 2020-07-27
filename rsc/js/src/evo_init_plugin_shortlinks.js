/**
 * This file initialize plugin "Shortlinks"
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on jQuery
 */
jQuery( document ).ready( function()
{
	window.evo_init_shortlinks_toolbar = function( config )
		{
			window.shortlinks_toolbar = function shortlinks_toolbar( title )
				{
					var r = config.toolbar_title_before + title + config.toolbar_title_after
							+ config.toolbar_group_before
							+ '<input type="button" title="' + config.button_title + '"'
							+ ' class="' + config.button_class + '"'
							+ ' data-func="shortlinks_load_window|' + config.js_prefix + '" value="' + config.button_value + '" />'
							+ config.toolbar_group_after;

						jQuery( '.' + config.js_prefix + config.plugin_code + '_toolbar' ).html( r );
				};
			
			window.shortlinks_toolbar( config.toolbar_title );
		};

	if( typeof( evo_init_shortlinks_toolbar_config ) != 'undefined' )
	{
		// Initialize each Shortlinks toolbar instance:
		var evo_temp_config = Object.values( evo_init_shortlinks_toolbar_config );
		for( var i = 0; i < evo_temp_config.length; i++ )
		{
			( function() {
				window.evo_init_shortlinks_toolbar( evo_temp_config[i] );
			} )();
		}
		delete evo_temp_config;
	}
} );

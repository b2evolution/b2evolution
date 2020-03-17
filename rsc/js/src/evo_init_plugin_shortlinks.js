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
	if( typeof( evo_init_shortlinks_toolbar_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var config = evo_init_shortlinks_toolbar_config;
	
	window.shortlinks_toolbar = function shortlinks_toolbar( title, prefix )
	{
		var r = config['toolbar_title_before'] + title + config['toolbar_title_after']
				+ config['toolbar_group_before']
				+ '<input type="button" title="' + config['button_title'] + '"'
				+ ' class="' + config['button_class'] + '"'
				+ ' data-func="shortlinks_load_window|' + prefix + '" value="' + config['button_value'] + '" />'
				+ config['toolbar_group_after'];

			jQuery( '.' + prefix + config['plugin_code'] + '_toolbar' ).html( r );
	}

	if( typeof( evo_init_shortlinks_toolbar ) != 'undefined' )
	{	// Init individual shortlinks toolbar:
		var toolbars = Object.values( evo_init_shortlinks_toolbar );
		for( var i = 0; i < toolbars.length; i++ )
		{
			window.shortlinks_toolbar( toolbars[i]['title'], toolbars[i]['prefix'] );
		}
	}
} );

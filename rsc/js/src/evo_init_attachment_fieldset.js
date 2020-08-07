/**
 * This file initializes the attachment fieldset JS to set proper height and handler to resize it
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery, jQueryUI
 */
jQuery( document ).ready( function()
{
	if( typeof( evo_link_initialize_fieldset_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var evo_link_initialize_fieldset_configs = Object.values( evo_link_initialize_fieldset_config );
	for( var i = 0; i < evo_link_initialize_fieldset_configs.length; i++ )
	{
		window.evo_link_initialize_fieldset( evo_link_initialize_fieldset_configs[i].fieldset_prefix );
	}
} );
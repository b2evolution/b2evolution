/**
 * This file initialize marketing popup container.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * 
 * Depends on: jQuery
 */
jQuery( document ).ready( function()
{
	if( typeof( evo_ddexitpop_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	ddexitpop.init(
	{
		contentsource: [ 'id', 'evo_container__' + evo_ddexitpop_config.container_code ],
		fxclass: evo_ddexitpop_config.animation,
		hideaftershow: evo_ddexitpop_config.show_repeat,
		displayfreq: evo_ddexitpop_config.show_frequency,
	} );

	jQuery( '.ddexitpop button.close' ).click( function()
	{	// Hide popup on click top-right close icon button:
		ddexitpop.hidepopup();
	} );
} );
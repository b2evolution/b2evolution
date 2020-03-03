/**
 * This file has generic functions that are initialized on jQuery ready function
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

	// Datepicker
	if( typeof( evo_init_datepicker ) != 'undefined' )
	{
		jQuery( evo_init_datepicker['selector'] ).datepicker( evo_init_datepicker['config'] );
	}

	// Change Link Position JS
	if( typeof( evo_link_position_config ) != 'undefined' )
	{
		var config = evo_link_position_config['config'];
		var displayInlineReminder = config['display_inline_reminder'];
		var deferInlineReminder = config['defer_inline_reminder'];

		jQuery( document ).on( 'change', evo_link_position_config['selector'], {
				url: config['url'],
				crumb: config['crumb'],
			},
			function( event )
			{
				if( this.value == 'inline' && displayInlineReminder && !deferInlineReminder )
				{ // Display inline position reminder
					alert( config['alert_msg'] );
					displayInlineReminder = false;
				}
				evo_link_change_position( this, event.data.url, event.data.crumb );
			} );
	}

	// Item Text Renderers
	if( typeof( evo_itemform_renderers__click ) != 'undefined' )
	{
		jQuery( "#itemform_renderers .dropdown-menu" ).on( "click", function( e ) { e.stopPropagation() } );
	}

	// Comment Text Renderers
	if( typeof( evo_commentform_renderers__click ) != 'undefined' )
	{
		jQuery( "#commentform_renderers .dropdown-menu" ).on( "click", function( e ) { e.stopPropagation() } );
	}

	// Initialize attachments fieldset
	if( typeof( 'evo_link_initialize_fieldset_config' ) != 'undefined' )
	{
		evo_link_initialize_fieldset( evo_link_initialize_fieldset_config['fieldset_prefix'] );
	}

} );

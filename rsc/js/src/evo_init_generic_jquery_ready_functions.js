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

	// Sortable
	if( typeof( 'evo_link_sortable_config' ) != 'undefined' )
	{
		var config = evo_link_sortable_config;
		jQuery( '#' + config['fieldset_prefix'] + 'attachments_fieldset_table table' ).sortable(
			{
				containerSelector: 'table',
				itemPath: '> tbody',
				itemSelector: 'tr',
				placeholder: jQuery.parseHTML( '<tr class="placeholder"><td colspan="5"></td></tr>' ),
				onMousedown: function( $item, _super, event )
				{
					if( ! event.target.nodeName.match( /^(a|img|select|span)$/i ) )
					{	// Ignore a sort action when mouse is clicked on the tags <a>, <img>, <select> or <span>
						event.preventDefault();
						return true;
					}
				},
				onDrop: function( $item, container, _super )
				{
					jQuery( '#' + config['fieldset_prefix'] + 'attachments_fieldset_table table tr' ).removeClass( 'odd even' );
					jQuery( '#' + config['fieldset_prefix'] + 'attachments_fieldset_table table tr:odd' ).addClass( 'even' );
					jQuery( '#' + config['fieldset_prefix'] + 'attachments_fieldset_table table tr:even' ).addClass( 'odd' );
		
					var link_IDs = '';
					jQuery( '#' + config['fieldset_prefix'] + 'attachments_fieldset_table table tr' ).each( function()
					{
						var link_ID_cell = jQuery( this ).find( '.link_id_cell > span[data-order]' );
						if( link_ID_cell.length > 0 )
						{
							link_IDs += link_ID_cell.html() + ',';
						}
					} );
					link_IDs = link_IDs.slice( 0, -1 );
		
					jQuery.ajax(
					{
						url: config['htsrv_url'] + 'anon_async.php',
						type: 'POST',
						data:
						{
							'action': 'update_links_order',
							'links': link_IDs,
							'crumb_link': config['crumb'],
						},
						success: function( data )
						{
							link_data = JSON.parse( ajax_debug_clear( data ) );
							// Update data-order attributes
							jQuery( '#attachments_fieldset_table table tr' ).each( function()
							{
								var link_ID_cell = jQuery( this ).find( '.link_id_cell > span[data-order]' );
								if( link_ID_cell.length > 0 )
								{
									link_ID_cell.attr( 'data-order', link_data[link_ID_cell.html()] );
								}
							} );
							evoFadeSuccess( $item );
						}
					} );
		
					$item.removeClass(container.group.options.draggedClass).removeAttr("style");
				}
			} );
	}
} );

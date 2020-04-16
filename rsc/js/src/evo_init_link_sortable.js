/**
 * This file initializes the sortable links JS
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery, jQuery.sortable
 */
window.init_link_sortable = function init_link_sortable( config )
	{
		jQuery( '#' + config.fieldset_prefix + 'attachments_fieldset_table table' ).sortable(
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
						jQuery( '#' + config.fieldset_prefix + 'attachments_fieldset_table table tr' ).removeClass( 'odd even' );
						jQuery( '#' + config.fieldset_prefix + 'attachments_fieldset_table table tr:odd' ).addClass( 'even' );
						jQuery( '#' + config.fieldset_prefix + 'attachments_fieldset_table table tr:even' ).addClass( 'odd' );
			
						var link_IDs = '';
						jQuery( '#' + config.fieldset_prefix + 'attachments_fieldset_table table tr' ).each( function()
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
							url: htsrv_url + 'anon_async.php',
							type: 'POST',
							data:
								{
									'action': 'update_links_order',
									'links': link_IDs,
									'crumb_link': config.crumb_link,
								},
							success: function( data )
								{
									link_data = JSON.parse( ajax_debug_clear( data ) );
									// Update data-order attributes
									jQuery( '#' + config.fieldset_prefix + 'attachments_fieldset_table table tr' ).each( function()
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
			
						$item.removeClass( container.group.options.draggedClass ).removeAttr("style");
					}
			} );
	};

jQuery( document ).ready( function()
{
	if( typeof( evo_link_sortable_js_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var evo_link_sortable_js_config_keys = Object.keys( evo_link_sortable_js_config );
	for( var i = 0; i < evo_link_sortable_js_config_keys.length; i++ )
	{
		init_link_sortable( evo_link_sortable_js_config[evo_link_sortable_js_config_keys[i]] );
	}
} );

/**
 * This file has generic functions
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */


/**
 * Prevent submit a form by Enter Key, e.g. when user is editing the owner fields
 *
 * @param string jQuery selector
 */
function evo_prevent_key_enter( selector )
{
	jQuery( selector ).keypress( function( e )
	{
		if( e.keyCode == 13 )
		{
			return false;
		}
	} );
}


/**
 * Render comment ratings to star buttons
 */
function evo_render_star_rating()
{
	jQuery( '#comment_rating' ).each( function( index ) {
		var raty_params = jQuery( 'span.raty_params', this );
		if( raty_params )
		{
			jQuery( this ).html( '' ).raty( raty_params );
		}
	} );
}


/**
 * Open link attachment modal window
 * @param string link_owner_type 
 * @param integer link_owner_ID 
 * @param string root 
 * @param string path 
 * @param string fm_highlight 
 * @param string prefix 
 */
function link_attachment_window( link_owner_type, link_owner_ID, root, path, fm_highlight, prefix )
{
	openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_link_attachment_window_config.loader_title + '"></span>',
		'90%', '80%', true, evo_link_attachment_window_config.window_title, '', true );

	var data = {
			'action': 'link_attachment',
			'link_owner_type': link_owner_type,
			'link_owner_ID': link_owner_ID,
			'crumb_link': evo_link_attachment_window_config.crumb_link,
			'root': typeof( root ) == 'undefined' ? '' : root,
			'path': typeof( path ) == 'undefined' ? '' : path,
			'fm_highlight': typeof( fm_highlight ) == 'undefined' ? '' : fm_highlight,
			'prefix': typeof( prefix ) == 'undefined' ? '' : prefix,
		};

	jQuery.ajax(
		{
			type: 'POST',
			url: htsrv_url + 'async.php',
			data: data,
			success: function(result)
			{
				openModalWindow( result, '90%', '80%', true, evo_link_attachment_window_config.window_title, '' );
			}
		} );
	return false;
};


/**
 * Initialize sortable links
 * @param object config 
 */
function init_link_sortable( config )
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
}

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
 * Display Link Attachment Modal window
 */
if( typeof( evo_link_attachment_window_config ) != 'undefined' )
{
	window.link_attachment_window = function link_attachment_window( link_owner_type, link_owner_ID, root, path, fm_highlight, prefix )
		{
			openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_link_attachment_window_config.loader_title + '"></span>',
				'90%', '80%', true, evo_link_attachment_window_config.window_title, '', true );
			jQuery.ajax(
			{
				type: 'POST',
				url: htsrv_url + 'async.php',
				data:
				{
					'action': 'link_attachment',
					'link_owner_type': link_owner_type,
					'link_owner_ID': link_owner_ID,
					'crumb_link': evo_link_attachment_window_config.crumb_link,
					'root': typeof( root ) == 'undefined' ? '' : root,
					'path': typeof( path ) == 'undefined' ? '' : path,
					'fm_highlight': typeof( fm_highlight ) == 'undefined' ? '' : fm_highlight,
					'prefix': typeof( prefix ) == 'undefined' ? '' : prefix,
				},
				success: function(result)
				{
					openModalWindow( result, '90%', '80%', true, evo_link_attachment_window_config.window_title, '' );
				}
			} );
			return false;
		};
}

/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: results.js 674 2012-08-15 07:08:29Z yura $
 */

jQuery( document ).ready( function()
{
	jQuery( document ).on( 'click', 'div.navigation.ajax a', function ()
	{
		var params = 'action=results&callback_function=items_list_block_by_page';
		var this_obj = jQuery( this );
		this_obj.addClass( 'loading' );

		var link_href = jQuery( this ).attr( 'href' ).split( '?' );
		link_href = link_href[1];

		if( typeof blog_id != 'undefined' && blog_id > 0 )
		{	// Add "blog" param
			params += '&blog=' + blog_id;
		}

		jQuery.ajax(
		{	// Send ajax request with the given params
			type: 'POST',
			url: htsrv_url + 'anon_async.php',
			data: params + '&' + link_href,
			success: function( result )
			{
				jQuery( 'div#content' ).append( ajax_debug_clear( result ) );
				this_obj.parent().fadeOut( 500, function()
				{	// Hide & remove current page link
					jQuery( this ).remove();
				} );
			}
		} );

		return false;
	} );
} );
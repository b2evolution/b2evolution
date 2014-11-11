/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: results.js 674 2012-08-15 07:08:29Z yura $
 */

jQuery( document ).ready(function()
{
	jQuery( document ).on( 'click',
		'.results_nav a, ' + // page navigation links
		'.table_scroll a.basic_sort_link, ' + // sort links
		'.table_scroll a.basic_current', // current sort link
		function()
		{	// Action to load ajax content by changing a page or an order
			return results_ajax_load( jQuery( this ), jQuery( this ).attr( 'href' ) );
		}
	);

	jQuery( document ).on( 'focus', 'select[name$=_per_page]', function()
	{	// Fix attributes of <select> elements
		if( !jQuery( this ).attr( 'onchange' ) )
		{
			return false;
		}

		var onchange = jQuery( this ).attr( 'onchange' );
		onchange = onchange.replace( "location.href='", '' ).replace( "'+this.value", '' );
		jQuery( this ).attr( 'href', onchange );

		jQuery( this ).removeAttr( 'onchange' );
	} );

	jQuery( document ).on( 'change', 'select[name$=_per_page]', function()
	{	// Action to load ajax content by changing a page size

		var link_href = jQuery( this ).attr( 'href' );

		// Add a selected value of page size
		link_href += jQuery( this ).val();

		if( results_ajax_load( jQuery( this ), link_href ) )
		{	// No ajax request, Use a simple url to refresh the page
			location.href = link_href;
			return true;
		}
		else
		{	// New page view is loading by AJAX request
			return false;
		}
	} );
} );


/**
 * Send AJAX request to load a content for results table
 *
 * @param object This object ( jQuery( this ) )
 * @param string Url
 * @return boolean TRUE - if callback function is not available
 */
function results_ajax_load( this_obj, link_href )
{
	var params = 'action=results';
	var layout = this_obj.parents( 'div[id$=_ajax_content]' );
	if( layout.length == 0 )
	{ // callback_funcion can't be set, handle with normal request
		return true;
	}

	var param_prefix = layout.attr( 'id' ).replace( 'ajax_content', '' );
	var link_href = link_href.split( '?' );
	link_href = link_href[1];

	if( ( param_prefix != '' ) && ( jQuery( '#' + param_prefix + 'ajax_callback' ).length > 0 ) )
	{	// Set "callback_function" param
		params += '&callback_function=' + jQuery( '#' + param_prefix + 'ajax_callback' ).html();
	}
	else
	{	// callback_funcion can't be set, handle with normal request
		return true;
	}

	if( typeof is_backoffice != 'undefined' && is_backoffice )
	{	// Add param to detect the requests from backoffice
		params += '&is_backoffice=1';
	}
	else if( typeof blog_id != 'undefined' && blog_id > 0)
	{	// Add "blog" param for frontoffice
		params += '&blog=' + blog_id;
	}

	if( layout.find( '.results_ajax_loading' ).length == 0 )
	{	// Set temporary content during ajax is loading
		var $ajax_loading = jQuery( '<div class="results_ajax_loading"><div>&nbsp;</div></div>' );
		$ajax_loading.css( {
				'width':  layout.width(),
				'height': layout.height(),
				'top':    layout.offset().top,
				'left':   layout.offset().left,
			} );
		layout.append( $ajax_loading );
	}

	jQuery.ajax(
	{	// Send ajax request with the given params
		type: 'POST',
		url: htsrv_url + 'anon_async.php',
		data: params + '&' + link_href,
		success: function( result )
		{
			var $div_result = jQuery( document.createElement( 'div' ) );
			$div_result.html( ajax_debug_clear( result ) );
			var ajax_content_layout = $div_result.find( 'div#' + layout.attr( 'id' ) );
			if( ajax_content_layout.length == 0 )
			{	// Content is unavailable by some reason
				layout.html( '<div class="results_unavailable">Content is unavailable</div>' );
			}
			else
			{	// Display content
				layout.html( ajax_content_layout.html() );
			}
		},
		error: function()
		{
			layout.html( '<div class="results_unavailable">Content is unavailable</div>' );
		}
	} );

	return false;
}
/**
 * This file is used to open modal window with tool to crop profile pictures
 */


/**
 * Open modal window to crop profile picture
 *
 * @param integer User ID
 * @param integer File ID
 * @param string Tab of backoffice profile page
 * @return boolean FALSE to prevent onclick event of the link
 */
function user_crop_avatar( user_ID, file_ID, user_tab_from )
{
	if( typeof( user_tab_from ) == 'undefined' )
	{
		user_tab_from = 'avatar';
	}

	var max_size = 750;
	var min_size = 320;

	var viewport_width = jQuery( window ).width();
	var viewport_height = jQuery( window ).height();
	//console.log( 'viewport', viewport_width, viewport_height );

	// Set sizes for modal window:
	var window_width = viewport_width;
	var window_height = viewport_height;
	var aspect_ratio = window_height / window_width;

	// Limit window with max & min sizes:
	window_height = ( window_height > max_size ) ? max_size : ( ( window_height < min_size ) ? min_size : window_height );
	window_width = ( window_width > max_size ) ? max_size : ( ( window_width < min_size ) ? min_size : window_width );
	//console.log( 'window', window_width, window_height );

	// Set margins for normal view of wide screens:
	var margin_size_width = 10;
	var margin_size_height = 10;

	margin_size_width = ( window_width - ( margin_size_width * 2 ) ) > min_size ? 10 : 0;
	margin_size_height = ( window_height - ( margin_size_height * 2 ) ) > min_size ? 10: 0;

	// Set modal size:
	var modal_width = ( window_width > max_size ? max_size : window_width );
	var modal_height = ( window_height > max_size ? max_size : window_height );


	// Open modal window with loading animation while ajax request is executing below:
	openModalWindow(
			'<span id="spinner" class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_loading + '"></span>',
			modal_width + 'px',
			modal_height + 'px',
			true,
			evo_js_lang_crop_profile_pic,
			[ evo_js_lang_crop, 'btn-primary' ],
			true );

	// Get content size
	var modal_body_padding = {
		top: parseInt( jQuery( 'div.modal-dialog div.modal-body' ).css( 'paddingTop' ) ),
		right: parseInt( jQuery( 'div.modal-dialog div.modal-body' ).css( 'paddingRight' ) ),
		bottom: parseInt( jQuery( 'div.modal-dialog div.modal-body' ).css( 'paddingBottom' ) ),
		left: parseInt( jQuery( 'div.modal-dialog div.modal-body' ).css( 'paddingLeft' ) )
	};
	var content_height = parseInt( jQuery( 'div.modal-dialog div.modal-body' ).css('min-height') ) - ( modal_body_padding.top + modal_body_padding.bottom );
	var content_width = modal_width - ( modal_body_padding.left + modal_body_padding.right );

	// Initialize params for ajax request:
	var ajax_data = {
		'user_ID': user_ID,
		'file_ID': file_ID,
		'aspect_ratio' : aspect_ratio,
		'content_width' : content_width,
		'content_height' : content_height,
		'display_mode': 'js',
		'crumb_user': evo_js_crumb_user,
	};

	if( evo_js_is_backoffice )
	{ // Ajax params for back-office:
		ajax_data.ctrl = 'user';
		ajax_data.user_tab = 'crop';
		ajax_data.user_tab_from = user_tab_from;
	}
	else
	{ // Ajax params for front-office:
		ajax_data.blog = evo_js_blog;
		ajax_data.disp = 'avatar';
		ajax_data.action = 'crop';
	}

	// Execute ajax request to load a crop tool:
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_user_crop_ajax_url,
		data: ajax_data,
		success: function( result )
		{
			openModalWindow(
				result,
				modal_width+'px',
				modal_height+'px',
				true,
				evo_js_lang_crop_profile_pic,
				[ evo_js_lang_crop, 'btn-primary' ] );
		}
	} );

	return false;
}
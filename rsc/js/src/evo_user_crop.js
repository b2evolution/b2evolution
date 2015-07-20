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
	// Limit window with max & min sizes:
	window_height = ( window_height > max_size ) ? max_size : ( ( window_height < min_size ) ? min_size : window_height );
	window_width = ( window_width > max_size ) ? max_size : ( ( window_width < min_size ) ? min_size : window_width );
	//console.log( 'window', window_width, window_height );

	// Set margins for normal view of wide screens:
	var margin_size_width = 100;
	var margin_size_height = viewport_height > max_size ? 170 : 205;
	if( viewport_width <= 900 )
	{ // When width is less than 900px then preview thumbnails are located under big picture, so height margin should be more
		margin_size_width = 35;
		margin_size_height = 325;
	}
	//console.log( 'margins', margin_size_width, margin_size_height );

	// Set image sizes:
	var image_width = window_width - margin_size_width;
	var image_height = window_height - margin_size_height;
	var image_min_size = 130;
	// Limit image with min size:
	image_width = ( image_width < image_min_size ) ? image_min_size : image_width;
	image_height = ( image_height < image_min_size ) ? image_min_size : image_height;
	//console.log( 'image', image_width, image_height );

	// Open modal window with loading animation while ajax request is executing below:
	openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_loading + '"></span>',
		window_width+'px', window_height+'px', true,
		evo_js_lang_crop_profile_pic, [ evo_js_lang_crop, 'btn-primary hide' ], true );

	// Initialize params for ajax request:
	var ajax_data = {
		'user_ID': user_ID,
		'file_ID': file_ID,
		'image_width'  : image_width,
		'image_height' : image_height,
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
			openModalWindow( result, window_width+'px', window_height+'px', true,
			evo_js_lang_crop_profile_pic, [ evo_js_lang_crop, 'btn-primary hide' ] );
		}
	} );

	return false;
}
/**
 * This file is used to open modal window to report on user
 */


/**
 * Open modal window to report on user
 *
 * @param integer User ID
 * @param string Tab of backoffice page
 * @return boolean FALSE to prevent onclick event of the link
 */
function user_report( user_ID, user_tab )
{
	openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'auto', '', true,
		evo_js_lang_report_user, [ evo_js_lang_report_this_user_now, 'btn-danger' ], true );

	var ajax_data = {
		'action': 'get_user_report_form',
		'user_ID': user_ID,
		'crumb_user': evo_js_crumb_user,
	};

	if( evo_js_is_backoffice )
	{ // Ajax params for backoffice:
		ajax_data.is_backoffice = 1;
		ajax_data.user_tab = user_tab;
	}
	else
	{ // Ajax params for frontoffice:
		ajax_data.blog = evo_js_blog;
	}

	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_user_report_ajax_url,
		data: ajax_data,
		success: function( result )
		{
			openModalWindow( result, 'auto', '',true,
				evo_js_lang_report_user, [ evo_js_lang_report_this_user_now, 'btn-danger' ] );
		}
	} );

	return false;
}
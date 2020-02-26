/**
 * This file is used to open modal window to change default filters on users list
 */


/**
 * Open modal window to change default filters on users list
 *
 * @param integer User ID
 * @param string Tab of backoffice page
 * @return boolean FALSE to prevent onclick event of the link
 */
function evo_users_list_default_filters()
{
	openModalWindow( '<span class="loader_img loader_user_filters absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'auto', '', true,
		evo_js_lang_change_default_users_filters, evo_js_lang_save_defaults, true );

	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_userlist_filters_ajax_url,
		data: {
			'action': 'get_user_default_filters_form',
		},
		success: function( result )
		{
			openModalWindow( result, 'auto', '',true,
				evo_js_lang_change_default_users_filters, evo_js_lang_save_defaults );
		}
	} );

	return false;
}
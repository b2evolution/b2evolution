/**
 * This file is used to work with user account status
 * (Used only in back-office)
 */


/**
 * Open modal window to set user account status
 *
 * @return boolean FALSE to prevent onclick event of the link
 */
function set_account_status()
{
	openModalWindow( '<span class="loader_img loader_set_user_account_status absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'500px', '', true,
		evo_js_lang_set_user_account_status, evo_js_lang_make_changes_now, true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_userlist_set_account_status_ajax_url,
		data:
		{
			'ctrl': 'users',
			'action': 'set_status',
			'display_mode': 'js',
		},
		success: function( result )
		{
			openModalWindow( result, '500px', '', true,
				evo_js_lang_set_user_account_status, evo_js_lang_make_changes_now );
		}
	} );

	return false;
}
/**
 * This file is used to open modal window to delete user data
 * (Used only in back-office)
 */


/**
 * Open modal window to edit contact groups
 *
 * @param integer User ID
 * @param string Tab of backoffice profile page
 * @return boolean FALSE to prevent onclick event of the link
 */
function user_deldata( user_ID, user_tab_from )
{
	openModalWindow( '<span class="loader_img loader_user_deldata absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'auto', '', true,
		evo_js_lang_delete_user_data, [ evo_js_lang_delete_selected_data, 'btn-danger' ], true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_user_deldata_ajax_url,
		data:
		{
			'ctrl': 'user',
			'user_tab': 'deldata',
			'user_tab_from': user_tab_from,
			'user_ID': user_ID,
			'display_mode': 'js',
			'crumb_user': evo_js_crumb_user,
		},
		success: function( result )
		{
			openModalWindow( result, 'auto', '', true,
			evo_js_lang_delete_user_data, [ evo_js_lang_delete_selected_data, 'btn-danger' ] );
		}
	} );

	return false;
}
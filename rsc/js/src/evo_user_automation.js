/**
 * This file is used to open modal window to add user to automation
 * (Used only in back-office)
 */


/**
 * Open modal window to add user to automation
 *
 * @param integer User ID
 * @return boolean FALSE to prevent onclick event of the link
 */
function user_automation( user_ID )
{
	openModalWindow( '<span class="loader_img loader_user_deldata absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'auto', '', true,
		evo_js_lang_add_user_to_automation, evo_js_lang_add, true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_user_automation_ajax_url,
		data:
		{
			'ctrl': 'user',
			'user_tab': 'automation',
			'user_ID': user_ID,
			'display_mode': 'js',
			'crumb_user': evo_js_crumb_user,
		},
		success: function( result )
		{
			openModalWindow( result, 'auto', '', true,
			evo_js_lang_add_user_to_automation, evo_js_lang_add );
		}
	} );

	return false;
}
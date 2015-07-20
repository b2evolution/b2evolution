/**
 * This file is used to open modal window to edit contact groups of the user
 * (Used only in front-office)
 */


/**
 * Open modal window to edit contact groups
 *
 * @param integer User ID
 * @return boolean FALSE to prevent onclick event of the link
 */
function user_contact_groups( user_ID )
{
	openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'auto', '', true,
		evo_js_lang_contact_groups, evo_js_lang_save, true );

	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_user_contact_groups_ajax_url,
		data:
		{
			'action': 'get_user_contact_form',
			'blog': evo_js_blog,
			'user_ID': user_ID,
			'crumb_user': evo_js_crumb_user,
		},
		success: function( result )
		{
			openModalWindow( result, 'auto', '', true,
				evo_js_lang_contact_groups, evo_js_lang_save, true );
		}
	} );

	return false;
}
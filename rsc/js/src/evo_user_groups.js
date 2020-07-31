/**
 * This file is used to work with user groups
 * (Used only in back-office)
 */


/**
 * Open modal window to change user group membership
 *
 * @return boolean FALSE to prevent onclick event of the link
 */
function change_groups()
{
	openModalWindow( '<span class="loader_img loader_change_groups absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'500px', '', true,
		evo_js_lang_change_groups, evo_js_lang_make_changes_now, true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_userlist_change_groups_ajax_url,
		data:
		{
			'ctrl': 'users',
			'action': 'change_groups',
			'display_mode': 'js',
		},
		success: function( result )
		{
			openModalWindow( result, '500px', '', true,
				evo_js_lang_change_groups, evo_js_lang_make_changes_now );
		}
	} );

	return false;
}
/**
 * This file is used to work with user tags
 * (Used only in back-office)
 */


/**
 * Open modal window to add users list to automation
 *
 * @return boolean FALSE to prevent onclick event of the link
 */
function add_remove_userlist_tags()
{
	openModalWindow( '<span class="loader_img loader_add_userlist_automation absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'auto', '', true,
		evo_js_lang_add_remove_tags_to_users, evo_js_lang_make_changes_now, true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_userlist_tags_ajax_url,
		data:
		{
			'ctrl': 'users',
			'action': 'edit_tags',
			'display_mode': 'js',
		},
		success: function( result )
		{
			openModalWindow( result, 'auto', '', true,
				evo_js_lang_add_remove_tags_to_users, evo_js_lang_make_changes_now );
		}
	} );

	return false;
}
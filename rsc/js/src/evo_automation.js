/**
 * This file is used to work with automations
 * (Used only in back-office)
 */


/**
 * Open modal window to add user to automation
 *
 * @param integer User ID
 * @return boolean FALSE to prevent onclick event of the link
 */
function add_user_automation( user_ID )
{
	openModalWindow( '<span class="loader_img loader_add_user_automation absolute_center" title="' + evo_js_lang_loading + '"></span>',
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


/**
 * Open modal window to add users list to automation
 *
 * @return boolean FALSE to prevent onclick event of the link
 */
function add_userlist_automation()
{
	openModalWindow( '<span class="loader_img loader_add_userlist_automation absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'auto', '', true,
		evo_js_lang_add_current_selection_to_automation, evo_js_lang_add_selected_users_to_automation, true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_userlist_automation_ajax_url,
		data:
		{
			'ctrl': 'users',
			'action': 'automation',
			'display_mode': 'js',
		},
		success: function( result )
		{
			openModalWindow( result, 'auto', '', true,
			evo_js_lang_add_current_selection_to_automation, evo_js_lang_add_selected_users_to_automation );
		}
	} );

	return false;
}


/**
 * Open modal window to add user to automation
 *
 * @param integer User ID
 * @return boolean FALSE to prevent onclick event of the link
 */
function requeue_automation( autm_ID )
{
	openModalWindow( '<span class="loader_img loader_requeue_automation absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'auto', '', true,
		evo_js_lang_requeue_automation_for_finished_steps, evo_js_lang_requeue, true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_requeue_automation_ajax_url,
		data:
		{
			'ctrl': 'automations',
			'action': 'requeue_form',
			'autm_ID': autm_ID,
			'display_mode': 'js',
		},
		success: function( result )
		{
			openModalWindow( result, 'auto', '', true,
			evo_js_lang_requeue_automation_for_finished_steps, evo_js_lang_requeue );
		}
	} );

	return false;
}
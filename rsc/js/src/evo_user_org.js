/**
 * This file is used to open modal window to add user to organization
 * (Used only in back-office)
 */


/**
 * Open modal window to add user to organization
 *
 * @param integer Organization ID
 * @return boolean FALSE to prevent onclick event of the link
 */
function user_add_org( org_ID )
{
	openModalWindow( '<span class="loader_img loader_user_deldata absolute_center" title="' + evo_js_lang_loading + '"></span>',
		'450px', '', true,
		evo_js_lang_add_user_to_organization, evo_js_lang_add, true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_user_org_ajax_url,
		data:
		{
			'ctrl': 'organizations',
			'action': 'add_user',
			'org_ID': org_ID,
			'display_mode': 'js',
			'crumb_user': evo_js_crumb_organization,
		},
		success: function( result )
		{
			openModalWindow( result, '450px', '', true,
			evo_js_lang_add_user_to_organization, evo_js_lang_add );

			jQuery( "input.autocomplete_login" ).trigger( "added" );
		}
	} );

	return false;
}

/**
 * Open modal window to edit user in organization
 *
 * @param integer Organization ID
 * @param integer user ID
 */
function user_edit( org_ID, user_ID )
{
	openModalWindow( '<span class="loader_img loader_user_deldata absolute_center" title="' + evo_js_lang_loading + '"></span>',
			'450px', '', true,
			evo_js_lang_edit_membership, evo_js_lang_edit, true );
	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_user_org_ajax_url,
		data:
		{
			'ctrl': 'organizations',
			'action': 'edit_user',
			'org_ID': org_ID,
			'user_ID': user_ID,
			'display_mode': 'js',
			'crumb_user': evo_js_crumb_organization,
		},
		success: function( result )
		{
			openModalWindow( result, '450px', '', true, evo_js_lang_edit_membership, evo_js_lang_edit );
		}
	});

	return false;
}

function user_remove( org_ID, user_ID )
{
	openModalWindow( '<span class="loader_img loader_user_deldata absolute_center" title="' + evo_js_lang_loading + '"></span>',
			'450px', '', true,
			'<span class="text-danger">' + evo_js_lang_remove_user_membership + '</span>', evo_js_lang_remove, true );

	jQuery.ajax(
	{
		type: 'POST',
		url: evo_js_user_org_ajax_url,
		data:
		{
			'ctrl': 'organizations',
			'action': 'remove_user',
			'org_ID': org_ID,
			'user_ID': user_ID,
			'display_mode': 'js',
			'crumb_user': evo_js_crumb_organization,
		},
		success: function( result )
		{
			openModalWindow( result, '450px', '', true, '<span class="text-danger">' + evo_js_lang_remove_user_membership + '</span>', evo_js_lang_remove );
		}
	});

	return false;
}
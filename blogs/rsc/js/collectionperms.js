/**
 * Javascript for Blog permission forms (backoffice).
 * b2evolution - http://b2evolution.net/
 * @version $Id$
 */


/**
 * Toggles all checkboxes of the wide layout
 *
 * @param form the form
 * @param integer the checkbox group id
 * @param integer optional force set/unset
 */
function toggleall_wide( the_form, id, set )
{
	if( typeof(set) != 'undefined' )
	{
		allchecked[id] = Boolean(set);
	}
	else
	{
		allchecked[id] = allchecked[id] ? false : true;
	}

	// Trigger click() on all checkboxes that need to change.
	// This also triggers the bozo validator, if activated!
	var options = new Array(
			"blog_ismember_", "blog_perm_published_", "blog_perm_protected_", "blog_perm_private_", "blog_perm_draft_", "blog_perm_deprecated_", "blog_perm_redirected_", "blog_perm_page_", "blog_perm_intro_", "blog_perm_podcast_", "blog_perm_sidebar_", "blog_perm_delpost_", "blog_perm_edit_ts_", "blog_perm_draft_cmts_", "blog_perm_publ_cmts_", "blog_perm_depr_cmts_", "blog_perm_media_upload_", "blog_perm_media_browse_", "blog_perm_media_change_", "blog_perm_cats_", "blog_perm_properties_", "blog_perm_admin_"
		);
	for( var i = 0; i < options.length; i++ )
	{
		var option = options[i]+String(id);
		if( the_form.elements[option].checked != allchecked[id] )
		{
			the_form.elements[option].click();
		}
	}

	// Select
	the_form.elements['blog_perm_edit_'+String(id)].value = allchecked[id] ? 'all' : 'no';
}


/**
 * Switches UI layouts by applying CSS style.display
 */
function blogperms_switch_layout( layout )
{
	if( layout == 'all' )
	{
		jQuery("#userlist_default").show();
		jQuery("#userlist_wide").show();
	}
	else if( layout == 'wide' )
	{
		jQuery('#userlist_default').hide();
		jQuery('#userlist_wide').show();
	}
	else
	{
		jQuery('#userlist_default').show();
		jQuery('#userlist_wide').hide();
	}

	// Update form hidden field:
	jQuery('#blogperm_checkchanges').attr('layout', layout);

	// Update $UserSettings through async JS request:
	jQuery.get( htsrv_url+'async.php', {
			action: 'admin_blogperms_set_layout',
			layout: layout
	});
}


/**
 * Updates other UI layouts when "easy UI" changes
 */
function merge_from_easy( source, userid )
{
	if( source.name.indexOf( 'blog_perm_easy_' ) != 0 )
	{
		return;
	}

	if( source.value == 'custom' )
	{ // don't change anything
		return;
	}

	// reset all checkboxes / selects
	toggleall_wide( source.form, userid, 0 );

	// Select option
	switch( source.value )
	{
		case 'admin':
		case 'owner':
			source.form.elements['blog_perm_edit_'+String(userid)].value = 'all';
			break;

		case 'moderator':
			source.form.elements['blog_perm_edit_'+String(userid)].value = 'lt';
			break;

		case 'editor':
		case 'contrib':
			source.form.elements['blog_perm_edit_'+String(userid)].value = 'own';
			break;

		case 'member':
		default:
			source.form.elements['blog_perm_edit_'+String(userid)].value = 'no';
			break;
	}

	switch( source.value )
	{
		case 'admin':
			source.form.elements['blog_perm_admin_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_edit_ts_'+String(userid)].checked = 1;
		case 'owner':
			source.form.elements['blog_perm_properties_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_cats_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_delpost_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_intro_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_sidebar_'+String(userid)].checked = 1;
		case 'moderator':
			source.form.elements['blog_perm_draft_cmts_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_publ_cmts_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_depr_cmts_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_redirected_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_media_change_'+String(userid)].checked = 1;
		case 'editor':	// publisher
			source.form.elements['blog_perm_published_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_protected_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_deprecated_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_podcast_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_page_'+String(userid)].checked = 1;
		case 'contrib':
			source.form.elements['blog_perm_private_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_draft_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_media_browse_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_media_upload_'+String(userid)].checked = 1;
		case 'member':
			source.form.elements['blog_ismember_'+String(userid)].click();
	}
}


function merge_from_wide( source, userid )
{
	if( typeof(source.checked) != 'undefined' )
	{ // source is checkbox
		f = source.form;

		if( source.id.indexOf( idprefix+'_state_'+String(userid) ) == 0 )
		{ // state-checkbox
			if( !source.checked ){ toggleall_wide( f, userid, 0 ) }
			setcheckallspan(userid, source.checked);
		}
		else if( source.checked && !f.elements[idprefix+'_state_'+String(userid)].checked )
		{
			f.elements['checkallspan_state_'+String(userid)].click();
		}
	}
	else if( source.nodeName.toLowerCase() == 'select' )
	{
		f = source.form;
	}
	else
	{
		f = source;
	}

	var toeasy = '';
	if( ! f.elements['blog_ismember_'+String(userid)].checked )
	{
		toeasy = 'nomember';
	}
	else
	{
		var perms_contrib = Number(f.elements['blog_perm_draft_'+String(userid)].checked)
										+Number(f.elements['blog_perm_private_'+String(userid)].checked)
										+Number(f.elements['blog_perm_media_upload_'+String(userid)].checked)
										+Number(f.elements['blog_perm_media_browse_'+String(userid)].checked);

		var perms_editor = Number(f.elements['blog_perm_deprecated_'+String(userid)].checked)
										+Number(f.elements['blog_perm_protected_'+String(userid)].checked)
										+Number(f.elements['blog_perm_published_'+String(userid)].checked)
										+Number(f.elements['blog_perm_page_'+String(userid)].checked)
										+Number(f.elements['blog_perm_podcast_'+String(userid)].checked);

		var perm_moderator = Number(f.elements['blog_perm_redirected_'+String(userid)].checked)
										+Number(f.elements['blog_perm_draft_cmts_'+String(userid)].checked)
										+Number(f.elements['blog_perm_publ_cmts_'+String(userid)].checked)
										+Number(f.elements['blog_perm_depr_cmts_'+String(userid)].checked)
										+Number(f.elements['blog_perm_media_change_'+String(userid)].checked);

		var perms_owner = Number(f.elements['blog_perm_properties_'+String(userid)].checked)
										+Number(f.elements['blog_perm_cats_'+String(userid)].checked)
										+Number(f.elements['blog_perm_delpost_'+String(userid)].checked)
										+Number(f.elements['blog_perm_intro_'+String(userid)].checked)
										+Number(f.elements['blog_perm_sidebar_'+String(userid)].checked);

		var perms_admin = Number(f.elements['blog_perm_admin_'+String(userid)].checked)
										+Number(f.elements['blog_perm_edit_ts_'+String(userid)].checked);

		var perm_edit = f.elements['blog_perm_edit_'+String(userid)].value;

		// alert( perms_contrib+' '+perms_editor+' '+perm_moderator+' '+perms_admin+' '+perm_edit );

		if( perms_contrib == 4 && perms_editor == 5 && perm_moderator == 5 && perms_owner == 5 && perms_admin == 2 && perm_edit == 'all' )
		{ // has full admin rights
			toeasy = 'admin';
		}
		else if( perms_contrib == 4 && perms_editor == 5 && perm_moderator == 5 && perms_owner == 5 && perms_admin == 0 && perm_edit == 'all' )
		{ // has full editor rights
			toeasy = 'owner';
		}
		else if( perms_contrib == 4 && perms_editor == 5 && perm_moderator == 5 && perms_owner == 0 && perms_admin == 0 && perm_edit == 'lt' )
		{ // moderator
			toeasy = 'moderator';
		}
		else if( perms_contrib == 4 && perms_editor == 5 && perm_moderator == 0 && perms_owner == 0 && perms_admin == 0 && perm_edit == 'own' )
		{ // publisher
			toeasy = 'editor';
		}
		else if( perms_contrib == 4 && perms_editor == 0 && perm_moderator == 0 && perms_owner == 0 && perms_admin == 0 && perm_edit == 'own' )
		{ // contributor
			toeasy = 'contrib';
		}
		else if( perms_contrib == 0 && perms_editor == 0 && perm_moderator == 0 && perms_owner == 0 && perms_admin == 0 && perm_edit == 'no' )
		{
			toeasy = 'member';
		}
		else
		{
			toeasy = 'custom';
		}
	}

	for( i = 0; i < f.elements['blog_perm_easy_'+String(userid)].length; i++ )
	{
		if( f.elements['blog_perm_easy_'+String(userid)][i].value == toeasy )
		{
			f.elements['blog_perm_easy_'+String(userid)][i].checked = 1;
			break;
		};
	}
}


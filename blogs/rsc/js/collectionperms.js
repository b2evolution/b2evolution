/**
 * Javascript for Blog permission forms (backoffice).
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
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

	the_form.elements['blog_ismember_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_published_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_protected_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_private_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_draft_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_deprecated_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_delpost_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_comments_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_media_upload_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_media_browse_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_media_change_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_cats_'+String(id)].checked = allchecked[id];
	the_form.elements['blog_perm_properties_'+String(id)].checked = allchecked[id];
}


/**
 * Switches UI layouts by applying CSS style.display
 */
function blogperms_switch_layout( layout )
{
	if( layout == 'all' )
	{
		$("#userlist_default").show();
		$("#userlist_wide").show();
	}
	else if( layout == 'wide' )
	{
		$('#userlist_default').hide();
		$('#userlist_wide').show();
	}
	else
	{
		$('#userlist_default').show();
		$('#userlist_wide').hide();
	}

	// Update form hidden field:
	$('#blogperm_checkchanges').attr('layout', layout);

	// Update $UserSettings through async JS request:
	$.get( htsrv_url+'async.php', {
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

	// reset all checkboxes
	toggleall_wide( source.form, userid, 0 );

	switch( source.value )
	{
		case 'admin':
			source.form.elements['blog_perm_cats_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_properties_'+String(userid)].checked = 1;
		case 'editor':
			source.form.elements['blog_perm_published_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_protected_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_private_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_draft_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_deprecated_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_delpost_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_comments_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_media_browse_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_media_upload_'+String(userid)].checked = 1;
			source.form.elements['blog_perm_media_change_'+String(userid)].checked = 1;
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
	else
	{
		f = source;
	}

	var toeasy = '';
	if( !f.elements['blog_ismember_'+String(userid)].checked )
	{
		toeasy = 'nomember';
	}
	else
	{
		var perms_editor = Number(f.elements['blog_perm_deprecated_'+String(userid)].checked)
										+Number(f.elements['blog_perm_draft_'+String(userid)].checked)
										+Number(f.elements['blog_perm_private_'+String(userid)].checked)
										+Number(f.elements['blog_perm_protected_'+String(userid)].checked)
										+Number(f.elements['blog_perm_published_'+String(userid)].checked)
										+Number(f.elements['blog_perm_delpost_'+String(userid)].checked)
										+Number(f.elements['blog_perm_comments_'+String(userid)].checked)
										+Number(f.elements['blog_perm_media_upload_'+String(userid)].checked)
										+Number(f.elements['blog_perm_media_browse_'+String(userid)].checked)
										+Number(f.elements['blog_perm_media_change_'+String(userid)].checked);

		var perms_admin = Number(f.elements['blog_perm_properties_'+String(userid)].checked)
										+Number(f.elements['blog_perm_cats_'+String(userid)].checked);

		if( perms_editor == 10 )
		{ // has full editor rights
			switch( perms_admin )
			{
				case 0: toeasy = 'editor'; break;
				case 1: toeasy = 'custom'; break;
				case 2: toeasy = 'admin'; break;
			}
		}
		else if( perms_editor == 0 )
		{
			if( perms_admin )
			{
				toeasy = 'custom';
			}
			else
			{
				toeasy = 'member';
			}
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


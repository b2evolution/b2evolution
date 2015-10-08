/**
 * Javascript for Blog permission forms (backoffice).
 * b2evolution - http://b2evolution.net/
 * @version $Id: collectionperms.js 5577 2014-01-07 07:05:41Z yura $
 */


/**
 * Toggles all checkboxes of the wide layout
 *
 * @param form the form
 * @param integer the checkbox group id
 * @param integer optional force set/unset
 */
function toggleall_perm( the_form, id, set )
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
			"blog_ismember_", "blog_can_be_assignee_", "blog_perm_published_", "blog_perm_community_", "blog_perm_protected_", "blog_perm_private_", "blog_perm_review_", "blog_perm_draft_", "blog_perm_deprecated_", "blog_perm_redirected_", "blog_perm_page_", "blog_perm_intro_", "blog_perm_podcast_", "blog_perm_sidebar_", "blog_perm_delpost_", "blog_perm_edit_ts_",
			"blog_perm_delcmts_", "blog_perm_recycle_owncmts_", "blog_perm_vote_spam_cmts_", "blog_perm_published_cmt_", "blog_perm_community_cmt_", "blog_perm_protected_cmt_", "blog_perm_private_cmt_", "blog_perm_review_cmt_", "blog_perm_draft_cmt_", "blog_perm_deprecated_cmt_",
			"blog_perm_media_upload_", "blog_perm_media_browse_", "blog_perm_media_change_", "blog_perm_cats_", "blog_perm_properties_", "blog_perm_admin_"
		);
	for( var i = 0; i < options.length; i++ )
	{
		var option = options[i]+String(id);
		if( the_form.elements[option] && the_form.elements[option].checked != allchecked[id] )
		{
			the_form.elements[option].click();
		}
	}

	// Selects
	if( ! the_form.elements['blog_perm_item_type_'+String(id)].disabled )
	{	// Toggle only enabled select element:
		the_form.elements['blog_perm_item_type_'+String(id)].value = allchecked[id] ? 'admin' : 'standard';
	}
	if( ! the_form.elements['blog_perm_edit_'+String(id)].disabled )
	{	// Toggle only enabled select element:
		the_form.elements['blog_perm_edit_'+String(id)].value = allchecked[id] ? 'all' : 'no';
	}
	
	if( ! the_form.elements['blog_perm_edit_cmt_'+String(id)].disabled )
	{	// Toggle only enabled select element:
		the_form.elements['blog_perm_edit_cmt_'+String(id)].value = allchecked[id] ? 'all' : 'no';
	}
}
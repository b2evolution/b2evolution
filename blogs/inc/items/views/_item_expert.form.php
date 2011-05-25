<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author fsaya: Fabrice SAYA-GASNIER / PROGIDISTRI
 * @author blueyed: Daniel HAHLER
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 *
 * @todo blueyed>> IMHO it's not good to use CSS class .line here (mainly white-space:nowrap),
 *                 because on a smaller screen you'll cut things off! (and not every browser
 *                 allows "marking and moving" of text then).
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var Item
 */
global $edited_Item;
/**
 * @var Blog
 */
global $Blog;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var GeneralSettings
 */
global $Settings;

global $pagenow;

global $Session;

global $mode, $admin_url, $rsc_url;
global $post_comment_status, $trackback_url, $item_tags;
global $bozo_start_modified, $creating;
global $item_title, $item_content;
global $redirect_to;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'item_checkchanges', 'post' );
$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";


// ================================ START OF EDIT FORM ================================

$params = array();
if( !empty( $bozo_start_modified ) )
{
	$params['bozo_start_modified'] = true;
}

$Form->begin_form( '', '', $params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	if( isset( $mode ) )   $Form->hidden( 'mode', $mode );	// used by bookmarklet
	if( isset( $edited_Item ) )
	{
		$Form->hidden( 'post_ID', $edited_Item->ID );

		// Here we add js code for attaching file popup window: (Yury)
		if( !empty( $edited_Item->ID ) && ( $Session->get('create_edit_attachment') === true ) )
		{	// item also created => we have $edited_Item->ID for popup window
			echo_attaching_files_button_js();
			// clear session variable
			$Session->delete('create_edit_attachment');
		}
	}
	$Form->hidden( 'redirect_to', $redirect_to );

	// In case we send this to the blog for a preview :
	$Form->hidden( 'preview', 1 );
	$Form->hidden( 'more', 1 );
	$Form->hidden( 'preview_userid', $current_User->ID );

?>
<div class="left_col">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->begin_fieldset( T_('Post contents').get_manual_link('post_contents_fieldset') );

	$Form->switch_layout( 'none' );

	echo '<table cellspacing="0" class="compose_layout"><tr>';
	$display_title_field = $Blog->get_setting('require_title') != 'none';
	if( $display_title_field )
	{
		echo '<td width="1%"><strong>'.T_('Title').':</strong></td>';
		echo '<td width="97%" class="input">';
		$Form->text_input( 'post_title', $item_title, 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;', 'required' => $Blog->get_setting('require_title') == 'required') );
		echo '</td>';
	}

	// -- Language chooser BEGIN --
	$locale_options = locale_options( $edited_Item->get( 'locale' ), false, true );

	if ( is_array( $locale_options ) )
	{	// We've only one enabled locale.
		// Tblue> The locale name is not really needed here, but maybe we
		//        want to display the name of the only locale?
		$Form->hidden( 'post_locale', $locale_options[0] );
		//pre_dump( $locale_options );
	}
	else
	{	// More than one locale => select field.
		echo '<td width="1%">';
		if( $display_title_field )
		{
			echo '&nbsp;&nbsp;';
		}
		echo '<strong>'.T_('Language').':</strong></td>';
		echo '<td width="1%" class="select">';
		$Form->select_options( 'post_locale', $locale_options, '' );
		echo '</td>';
	}
	// -- Language chooser END --
	echo '</tr></table>';

	echo '<table cellspacing="0" class="compose_layout"><tr>';
	echo '<td width="1%"><strong>'.T_('Link to url').':</strong></td>';
	echo '<td class="input">';
	$Form->text_input( 'post_url', $edited_Item->get( 'url' ), 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td>';
	echo '<td width="1%">&nbsp;&nbsp;<strong>'.T_('Type').':</strong></td>';
	echo '<td width="1%" class="select">';
	$ItemTypeCache = & get_ItemTypeCache();
	$Form->select_object( 'item_typ_ID', $edited_Item->ptyp_ID, $ItemTypeCache,
								'', '', false, '', 'get_option_list_usable_only' );
	echo '</td>';

	echo '</tr></table>';

 	$Form->switch_layout( NULL );

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );
	echo '</div>';

	// ---------------------------- TEXTAREA -------------------------------------
	$Form->fieldstart = '<div class="edit_area">';
	$Form->fieldend = "</div>\n";
	$Form->textarea_input( 'content', $item_content, 16, '', array( 'cols' => 40 , 'id' => 'itemform_post_content' ) );
	$Form->fieldstart = '<div class="tile">';
	$Form->fieldend = '</div>';
	?>
	<script type="text/javascript" language="JavaScript">
		<!--
		// This is for toolbar plugins
		var b2evoCanvas = document.getElementById('itemform_post_content');
		// -->
	</script>

	<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );

	echo_publish_buttons( $Form, $creating, $edited_Item );

	echo '</div>';

	$Form->end_fieldset();


	// ####################### ATTACHMENTS/LINKS #########################
	if( isset($GLOBALS['files_Module']) )
	{
		attachment_iframe( $Form, $creating, $edited_Item, $Blog );
	}
	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Advanced properties').get_manual_link('post_advanced_properties_fieldset'), array( 'id' => 'itemform_adv_props' ) );

	// CUSTOM FIELDS varchar
	echo '<table cellspacing="0" class="compose_layout">';
	for( $i = 1 ; $i <= 3; $i++ )
	{	// For each custom double field:
		if( $field_name = $Blog->get_setting('custom_varchar'.$i) )
		{	// Field has a name: display it:
			echo '<tr><td class="label"><label for="item_varchar'.$i.'"><strong>'.$field_name.':</strong></label></td>';
			echo '<td class="input" width="97%">';
			$Form->text_input( 'item_varchar'.$i, $edited_Item->{'varchar'.$i}, 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
			echo '</td><td width="1"><!-- for IE7 --></td></tr>';
		}
	}

	//add slug_changed field - needed for slug trim, if this field = 0 slug will trimmed
	$Form->hidden( 'slug_changed', 0 );

	echo '<tr><td class="label"><label for="post_urltitle" title="'.T_('&quot;slug&quot; to be used in permalinks').'"><strong>'.T_('URL title "slug"').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'post_urltitle', $edited_Item->get('urltitle'), 40, '', '', array('maxlength'=>210, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="titletag"><strong>'.T_('&lt;title&gt; tag').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'titletag', $edited_Item->get('titletag'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="metadesc" title="&lt;meta name=&quot;description&quot;&gt;"><strong>'.T_('&lt;meta&gt; desc').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'metadesc', $edited_Item->get('metadesc'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="metakeywords" title="&lt;meta name=&quot;keywords&quot;&gt;"><strong>'.T_('&lt;meta&gt; keywds').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'metakeywords', $edited_Item->get('metakeywords'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="item_tags"><strong>'.T_('Tags').':</strong> <span class="notes">'.T_('sep by ,').'</span></label></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'item_tags', $item_tags, 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '</table>';

 	$Form->switch_layout( 'linespan' );
	echo '<div id="itemform_urltitle" class="edit_fieldgroup">';

	echo '</div>';
	$Form->switch_layout( NULL );

	$edited_Item_excerpt = $edited_Item->get('excerpt');
	?>

	<div id="itemform_post_excerpt" class="edit_fieldgroup">
		<label for="post_excerpt"><strong><?php echo T_('Excerpt') ?>:</strong>
		<span class="notes"><?php echo T_('(for XML feeds)') ?></span></label><br />
		<textarea name="post_excerpt" rows="2" cols="25" class="large" id="post_excerpt"><?php echo htmlspecialchars($edited_Item_excerpt) ?></textarea>
	</div>

	<?php

	if( $edited_Item->get('excerpt_autogenerated') )
	{ // store hash of current post_excerpt to detect if it was changed.
		$Form->hidden('post_excerpt_previous_md5', md5($edited_Item_excerpt));
	}
	$Form->end_fieldset();


	// ############################ WORKFLOW #############################

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$Form->begin_fieldset( T_('Workflow properties'), array( 'id' => 'itemform_workflow_props' ) );

			echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
			$Form->switch_layout( 'linespan' );

			$Form->select_object( 'item_priority', NULL, $edited_Item, T_('Priority'), '', true, '', 'priority_options' );

			echo ' '; // allow wrapping!

			$Form->select_object( 'item_assigned_user_ID', NULL, $edited_Item, T_('Assigned to'),
														'', true, '', 'get_assigned_user_options' );

			echo ' '; // allow wrapping!

			$ItemStatusCache = & get_ItemStatusCache();
			$Form->select_options( 'item_st_ID', $ItemStatusCache->get_option_list( $edited_Item->pst_ID, true ), T_('Task status') );

			echo ' '; // allow wrapping!

			$Form->date( 'item_deadline', $edited_Item->get('datedeadline'), T_('Deadline') );

			$Form->switch_layout( NULL );
			echo '</div>';

		$Form->end_fieldset();
	}

	// ####################### ADDITIONAL ACTIONS #########################

	if( isset( $Blog ) && $Blog->get('allowtrackbacks') )
	{
		$Form->begin_fieldset( T_('Additional actions'), array( 'id' => 'itemform_additional_actions' ) );

		// --------------------------- TRACKBACK --------------------------------------
		?>
		<div id="itemform_trackbacks">
			<label for="trackback_url"><strong><?php echo T_('Trackback URLs') ?>:</strong>
			<span class="notes"><?php echo T_('(Separate by space)') ?></span></label><br />
			<input type="text" name="trackback_url" class="large form_text_input" id="trackback_url" value="<?php echo format_to_output( $trackback_url, 'formvalue' ); ?>" />
		</div>
		<?php

		$Form->end_fieldset();
	}


	// ####################### PLUGIN FIELDSETS #########################

	$Plugins->trigger_event( 'AdminDisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item, 'edit_layout' => 'expert' ) );

	?>

</div>

<div class="right_col">

	<?php
	// ################### CATEGORIES ###################

	cat_select( $Form );


	// ################### PROPERTIES ###################

	$Form->begin_fieldset( T_('Properties'), array( 'id' => 'itemform_extra' ) );

	$Form->checkbox_basic_input( 'item_featured', $edited_Item->featured, '<strong>'.T_('Featured post').'</strong>' );

	if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
		issue_date_control( $Form, true );
		echo '</div>';
	}

	echo '<table>';

	echo '<tr><td><strong>'.T_('Order').':</strong></td><td>';
	$Form->text( 'item_order', $edited_Item->order, 10, '', T_('can be decimal') );
	echo '</td></tr>';

	if( $current_User->check_perm( 'users', 'edit' ) )
	{
		echo '<tr><td><strong>'.T_('Owner').':</strong></td><td>';
		$Form->username( 'item_owner_login', $edited_Item->get_creator_User(), '', T_( 'login of this post\'s owner.').'<br/>' );
		$Form->hidden( 'item_owner_login_displayed', 1 );
		echo '</td></tr>';
	}

	// CUSTOM FIELDS double
	for( $i = 1 ; $i <= 5; $i++ )
	{	// For each custom double field:
		if( $field_name = $Blog->get_setting('custom_double'.$i) )
		{	// Field has a name: display it:
			echo '<tr><td><strong>'.$field_name.':</strong></td><td>';
			$Form->text( 'item_double'.$i, $edited_Item->{'double'.$i}, 10, '', T_('can be decimal') );
			echo '</td></tr>';
		}
	}

	echo '</table>';

	$Form->end_fieldset();


	// ################### VISIBILITY / SHARING ###################

	$Form->begin_fieldset( T_('Visibility / Sharing'), array( 'id' => 'itemform_visibility' ) );

	$Form->switch_layout( 'linespan' );
	visibility_select( $Form, $edited_Item->status );
	$Form->switch_layout( NULL );

	$Form->end_fieldset();


	// ################### TEXT RENDERERS ###################

	$Form->begin_fieldset( T_('Text Renderers'), array( 'id' => 'itemform_renderers' ) );

	// fp> TODO: there should be no param call here (shld be in controller)
	$edited_Item->renderer_checkboxes( param('renderers', 'array', NULL) );

	$Form->end_fieldset();


	// ################### COMMENT STATUS ###################

	if( ( $Blog->get_setting( 'allow_comments' ) != 'never' ) && ( $Blog->get_setting( 'disable_comments_bypost' ) ) )
	{
		$Form->begin_fieldset( T_('Comments'), array( 'id' => 'itemform_comments' ) );

		?>
			<label title="<?php echo T_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="open" class="checkbox" <?php if( $post_comment_status == 'open' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Open') ?></label><br />

			<label title="<?php echo T_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="closed" class="checkbox" <?php if( $post_comment_status == 'closed' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Closed') ?></label><br />

			<label title="<?php echo T_('Visitors cannot see nor leave comments on this post.') ?>"><input type="radio" name="post_comment_status" value="disabled" class="checkbox" <?php if( $post_comment_status == 'disabled' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Disabled') ?></label><br />
		<?php

		$Form->end_fieldset();
	}

	// ################### ATTENDING ###################

	if( $Blog->get_setting( 'allow_attending' ) == 'enable_bypost' )
	{
		$Form->begin_fieldset( T_( 'Attending events' ) );
		$Form->checkbox_basic_input( 'post_attend_status', $edited_Item->get( 'attend_status' ), T_('Allow users to attend this event') );
		$Form->end_fieldset();
	}

	?>

</div>

<div class="clear"></div>

<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();

// ####################### JS BEHAVIORS #########################
echo_publishnowbutton_js();
echo_set_is_attachments();
echo_link_files_js();
echo_autocomplete_tags();
if( empty( $edited_Item->ID ) )
{ // if we creating new post - we add slug autofiller JS
	echo_slug_filler();
}
else
{	// if we are editing the post
	echo_set_slug_changed();
}
// New category input box:
echo_onchange_newcat();

// require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

/*
 * $Log$
 * Revision 1.75  2011/05/25 14:59:34  efy-asimo
 * Post attending
 *
 * Revision 1.74  2011/03/02 09:45:59  efy-asimo
 * Update collection features allow_comments, disable_comments_bypost, allow_attachments, allow_rating
 *
 * Revision 1.73  2011/01/06 14:31:47  efy-asimo
 * advanced blog permissions:
 *  - add blog_edit_ts permission
 *  - make the display more compact
 *
 * Revision 1.72  2010/11/03 19:44:15  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.71  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.70  2010/06/19 01:09:31  blueyed
 * Improve jQuery hintbox integration.
 *
 *  - Load js/css in form class method
 *  - Use JS to load the CSS, since LINK is not valid in HTML BODY
 *  - Remove disableEnterKey onkeypress: handled properly by
 *    hintbox (patch sent upstream). This allows form submission from
 * 	 the input field now again.
 *  - Add proper CSS class to input field. This makes the "loading"
 *    background image not appear anymore, but that depends on the
 * 	 admin skin.
 *
 * Revision 1.69  2010/06/18 23:57:46  blueyed
 * Move hintbox to jquery subdir.
 *
 * Revision 1.68  2010/06/15 20:12:51  blueyed
 * Autocompletion for tags in item edit forms, via echo_autocomplete_tags
 *
 * Revision 1.67  2010/05/14 21:42:47  blueyed
 * Fix handling of autogenerated excerpts.
 *
 * Revision 1.66  2010/03/15 20:12:38  efy-yury
 * slug fix
 *
 * Revision 1.65  2010/03/04 19:36:04  fplanque
 * minor/doc
 *
 * Revision 1.64  2010/03/04 16:40:34  fplanque
 * minor
 *
 * Revision 1.63  2010/02/13 16:22:30  efy-yury
 * slug field autofill
 *
 * Revision 1.62  2010/02/08 17:53:16  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.61  2010/02/05 09:51:40  efy-asimo
 * create categories on the fly
 *
 * Revision 1.60  2010/02/04 16:41:19  efy-yury
 * add "Add/Link files" link
 *
 * Revision 1.59  2010/02/02 21:21:27  efy-yury
 * update expert form: attachments popup now opens when pushed the button 'Save and start attaching files'
 *
 * Revision 1.58  2010/01/30 18:55:31  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.57  2010/01/03 13:45:36  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.56  2009/12/08 20:16:12  fplanque
 * Better handling of the publish! button on post forms
 *
 * Revision 1.55  2009/11/27 12:29:06  efy-maxim
 * drop down
 *
 * Revision 1.54  2009/11/23 21:50:37  efy-maxim
 * ajax dropdown
 *
 * Revision 1.53  2009/11/23 11:58:04  efy-maxim
 * owner fix
 *
 * Revision 1.52  2009/11/22 18:52:21  efy-maxim
 * change owner; is login
 *
 * Revision 1.51  2009/11/20 09:06:09  efy-maxim
 * change owner
 *
 * Revision 1.50  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.49  2009/08/22 20:31:01  tblue246
 * New feature: Post type permissions
 *
 * Revision 1.48  2009/07/06 22:49:11  fplanque
 * made some small changes on "publish now" handling.
 * Basically only display it for drafts everywhere.
 *
 * Revision 1.47  2009/07/06 13:37:16  tblue246
 * - Backoffice, write screen:
 * 	- Hide the "Publish NOW !" button using JavaScript if the post types "Protected" or "Private" are selected.
 * 	- Do not publish draft posts whose post status has been set to either "Protected" or "Private" and inform the user (note).
 * - Backoffice, post lists:
 * 	- Only display the "Publish NOW!" button for draft posts.
 *
 * Revision 1.46  2009/07/05 01:29:02  tblue246
 * Ask for confirmation when "1 click-publishing" a post and the post status is set to "protected" (as discussed in: http://forums.b2evolution.net/viewtopic.php?p=93750 )
 *
 * Revision 1.45  2009/06/28 23:55:32  fplanque
 * Item specific description has priority.
 * If none provided, fall back to excerpt.
 * Never include duplicate general description.
 * Also added TODO for keywords to have a fallback to tags.
 *
 * Revision 1.44  2009/06/20 17:19:31  leeturner2701
 * meta desc and meta keywords per blog post
 *
 * Revision 1.43  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.42  2009/03/08 21:45:58  fplanque
 * removed global $use_preview setting for now
 *
 * Revision 1.41  2009/02/27 23:17:02  afwas
 * Add class 'PreviewButton' to Preview Button.
 *
 * Revision 1.40  2009/01/29 18:16:35  tblue246
 * Hide language chooser on post form in expert mode if there's only one locale.
 *
 * Revision 1.39  2009/01/24 04:19:49  afwas
 * Removed now obsolete call to /inc/items/viws/inc/_item_form_behaviors.inc.php
 *
 * Revision 1.38  2009/01/23 22:08:12  tblue246
 * - Filter reserved post types from dropdown box on the post form (expert tab).
 * - Indent/doc fixes
 * - Do not check whether a post title is required when only e. g. switching tabs.
 *
 * Revision 1.37  2009/01/19 21:40:59  fplanque
 * Featured post proof of concept
 *
 * Revision 1.36  2008/09/23 05:26:38  fplanque
 * Handle attaching files when multiple posts are edited simultaneously
 *
 * Revision 1.35  2008/06/30 23:47:04  blueyed
 * require_title setting for Blogs, defaulting to 'required'. This makes the title field now a requirement (by default), since it often gets forgotten when posting first (and then the urltitle is ugly already)
 *
 * Revision 1.34  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.33  2008/04/14 16:24:39  fplanque
 * use ActionArray[] to make action handlign more robust
 *
 * Revision 1.32  2008/04/13 20:40:07  fplanque
 * enhanced handlign of files attached to items
 *
 * Revision 1.31  2008/04/04 17:02:23  fplanque
 * cleanup of global settings
 *
 * Revision 1.30  2008/04/03 22:03:09  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.29  2008/04/03 19:33:27  fplanque
 * category selector will be smaller if less than 11 cats
 *
 * Revision 1.28  2008/04/03 15:54:19  fplanque
 * enhanced edit layout
 *
 * Revision 1.27  2008/03/22 19:39:29  fplanque
 * <title> tag support
 *
 * Revision 1.26  2008/03/22 15:20:19  fplanque
 * better issue time control
 *
 * Revision 1.25  2008/03/21 16:07:03  fplanque
 * longer post slugs
 *
 * Revision 1.24  2008/02/10 00:58:00  fplanque
 * minor
 *
 * Revision 1.23  2008/02/09 20:14:14  fplanque
 * custom fields management
 *
 * Revision 1.22  2008/02/09 17:36:15  fplanque
 * better handling of order, including approximative comparisons
 *
 * Revision 1.21  2008/02/09 02:56:00  fplanque
 * explicit order by field
 *
 * Revision 1.20  2008/01/28 20:23:19  fplanque
 * better display of image file linking while in 'upload' mode
 *
 * Revision 1.19  2008/01/28 20:17:45  fplanque
 * better display of image file linking while in 'upload' mode
 *
 * Revision 1.18  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.17  2008/01/14 23:41:47  fplanque
 * cleanup load_funcs( urls ) in main because it is ubiquitously used
 *
 * Revision 1.16  2007/12/23 16:16:17  fplanque
 * Wording improvements
 *
 * Revision 1.15  2007/11/30 01:45:52  fplanque
 * minor
 *
 * Revision 1.14  2007/11/29 22:47:13  fplanque
 * tags everywhere + debug
 *
 * Revision 1.13  2007/10/09 15:03:43  waltercruz
 * Minor css fix
 *
 * Revision 1.12  2007/10/08 08:32:00  fplanque
 * nicer forms
 *
 * Revision 1.11  2007/09/29 16:17:50  fplanque
 * minor
 *
 * Revision 1.10  2007/09/29 09:50:54  yabs
 * validation
 *
 * Revision 1.9  2007/09/22 19:23:56  fplanque
 * various fixes & enhancements
 *
 * Revision 1.8  2007/09/17 20:11:43  fplanque
 * UI improvements
 *
 * Revision 1.7  2007/09/17 20:04:40  fplanque
 * UI improvements
 *
 * Revision 1.6  2007/09/12 21:00:31  fplanque
 * UI improvements
 *
 * Revision 1.5  2007/09/04 22:16:33  fplanque
 * in context editing of posts
 *
 * Revision 1.4  2007/09/03 16:44:28  fplanque
 * chicago admin skin
 *
 * Revision 1.3  2007/07/09 19:07:44  fplanque
 * minor
 *
 * Revision 1.2  2007/06/30 21:23:19  fplanque
 * moved excerpt
 *
 * Revision 1.1  2007/06/25 11:00:29  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.45  2007/05/14 02:47:23  fplanque
 * (not so) basic Tags framework
 *
 * Revision 1.44  2007/05/13 22:03:21  fplanque
 * basic excerpt support
 *
 * Revision 1.43  2007/04/26 00:11:07  fplanque
 * (c) 2007
 *
 * Revision 1.42  2007/04/05 22:57:33  fplanque
 * Added hook: UnfilterItemContents
 *
 * Revision 1.41  2007/03/25 13:19:17  fplanque
 * temporarily disabled dynamic and static urls.
 * may become permanent in favor of a caching mechanism.
 *
 * Revision 1.40  2007/03/21 02:21:37  fplanque
 * item controller: highlight current (step 2)
 *
 * Revision 1.39  2007/03/21 01:44:51  fplanque
 * item controller: better return to current filterset - step 1
 *
 * Revision 1.38  2007/03/11 23:56:02  fplanque
 * fixed some post editing oddities / variable cleanup (more could be done)
 *
 * Revision 1.37  2007/01/26 02:12:09  fplanque
 * cleaner popup windows
 *
 * Revision 1.36  2006/12/14 00:01:49  fplanque
 * land in correct collection when opening FM from an Item
 *
 * Revision 1.35  2006/12/12 23:23:30  fplanque
 * finished post editing v2.0
 *
 * Revision 1.34  2006/12/12 21:19:31  fplanque
 * UI fixes
 *
 * Revision 1.33  2006/12/12 02:53:57  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.32  2006/12/10 23:56:26  fplanque
 * Worfklow stuff is now hidden by default and can be enabled on a per blog basis.
 *
 * Revision 1.31  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.30  2006/12/06 23:55:53  fplanque
 * hidden the dead body of the sidebar plugin + doc
 *
 * Revision 1.29  2006/11/29 20:48:46  blueyed
 * Moved url_rel_to_same_host() from _misc.funcs.php to _url.funcs.php
 *
 * Revision 1.28  2006/11/19 03:50:29  fplanque
 * cleaned up CSS
 *
 * Revision 1.26  2006/11/16 23:48:56  blueyed
 * Use div.line instead of span.line as element wrapper for XHTML validity
 *
 * Revision 1.25  2006/10/01 22:21:54  blueyed
 * edit_layout param fixes/doc
 */
?>

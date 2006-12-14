<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
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


global $mode, $use_post_url;
global $use_preview, $post_status, $post_comment_status, $trackback_url;
global $edit_date, $bozo_start_modified, $creating;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = & new Form( NULL, 'item_checkchanges', 'post', 'none' );
$Form->fieldstart = '<div class="tile">';
$Form->fieldend = '</div>';
$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";


// ================================ START OF EDIT FORM ================================

$params = array();
if( !empty( $bozo_start_modified ) )
{
	$params['bozo_start_modified'] = true;
}

$Form->begin_form( '', '', $params );

$Form->hidden( 'ctrl', 'items' );
$Form->hidden( 'action', $creating ? 'create' : 'update' );
$Form->hidden( 'blog', $Blog->ID );
if( isset( $mode ) )   $Form->hidden( 'mode', $mode );	// used by bookmarklet
if( isset( $edited_Item ) )   $Form->hidden( 'post_ID', $edited_Item->ID );

// In case we send this to the blog for a preview :
$Form->hidden( 'preview', 1 );
$Form->hidden( 'more', 1 );
$Form->hidden( 'preview_userid', $current_User->ID );

?>
<div class="left_col">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->begin_fieldset( T_('Post contents') );

	$Form->text_input( 'post_title', $edited_Item->title, 48, T_('Title'), '', array('maxlength'=>255) );

	echo ' <span id="itemform_post_locale">'; // allow wrapping here! (and below)
	                                          // blueyed>> (Opera would additionally need text/&nbsp; here, but that wraps ugly)
	$Form->select( 'post_locale', $edited_Item->get( 'locale' ), 'locale_options_return', T_('Language') );
	echo '</span>';

	echo ' <span id="itemform_typ_ID">';
	$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
	$Form->select_object( 'item_typ_ID', $edited_Item->typ_ID, $ItemTypeCache, T_('Type') );
	echo '</span>';

	if( $use_post_url )
	{
		echo ' <span id="itemform_post_url">';
		$Form->text( 'post_url', $edited_Item->get( 'url' ), 40, T_('Link to url'), '', 255 );
		echo '</span>';
	}
	else
	{
		$Form->hidden( 'post_url', '' );
	}

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );
	echo '</div>';

	// ---------------------------- TEXTAREA -------------------------------------
	$Form->fieldstart = '<div class="edit_area">';
	$Form->fieldend = "</div>\n";
	$Form->textarea_input( 'content', $edited_Item->get( 'content' ), 16, '', array( 'cols' => 40 , 'id' => 'itemform_post_content' ) );
	$Form->fieldstart = '<div class="tile">';
	$Form->fieldend = '</div>';
	?>
	<script type="text/javascript" language="JavaScript">
		<!--
		// This is for toolbar plugins
		var b2evoCanvas = document.getElementById('itemform_post_content');
		//-->
	</script>

	<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	if( $use_preview )
	{ // ---------- PREVIEW ----------
		load_funcs( '_misc/_url.funcs.php' );
		$url = url_same_protocol( $Blog->get( 'dynurl' ) );

		$Form->button( array( 'button', '', T_('Preview'), '', 'b2edit_open_preview(this.form, \''.$url.'\');' ) );
	}

	// ---------- SAVE ----------
	$Form->submit( array( '', /* TRANS: This is the value of an input submit button */ T_('Save !'), 'SaveButton' ) );

	// ---------- DELETE ----------
	if( ! $creating )
	{ // Editing post
		// Display delete button if current user has the rights:
		$edited_Item->delete_link( ' ', ' ', '#', '#', 'DeleteButton', true );
	}

	if( $Settings->get( 'fm_enabled' ) )
	{ // ---------- UPLOAD ----------
		// Note: we try to land in the Blog media folder if possible
		// fp> TODO: check what happens if blog folders are disabled
		if( $current_User->check_perm( 'files', 'view' ) )
		{
			echo '<input id="itemform_button_files" type="button" value="'.format_to_output(T_('Files'), 'formvalue').'" class="ActionButton"
			       onclick="pop_up_window( \''.url_add_param( $Blog->get_filemanager_link(), 'mode=upload' ).'\', \'fileman_upload\' );" /> ';
		}

		// fp> TODO: perms. It would be nice if we could upload files without viewing them, but I don't think that works.
		if( $Settings->get('upload_enabled') && $current_User->check_perm( 'files', 'add' ) )
		{
			echo '<input id="itemform_button_upload" type="button" value="'.format_to_output(T_('Upload'), 'formvalue').'" class="ActionButton"
			       onclick="pop_up_window( \''.url_add_param( $Blog->get_filemanager_link(), 'mode=upload&amp;fm_mode=file_upload' ).'\', \'fileman_upload\' );" /> ';
		}
	}

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );

	echo '</div>';

	$Form->end_fieldset();


	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Advanced properties'), array( 'id' => 'itemform_adv_props' ) );

	if( $current_User->check_perm( 'edit_timestamp' ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		?>
		<div id="itemform_edit_timestamp">
		<?php
		$Form->date( 'item_issue_date', $edited_Item->get('issue_date'), T_('Issue date') );
		echo ' '; // allow wrapping!
		$Form->time( 'item_issue_time', $edited_Item->get('issue_date'), '' );
		echo ' '; // allow wrapping!
		if( $creating )
		{ // If not checked, create time will be used...
			$Form->checkbox( 'edit_date', $edit_date, '', T_('Edit') );
		}
		?>
		</div>
		<?php
	}

	$Form->text( 'post_urltitle', $edited_Item->get( 'urltitle' ), 40, T_('URL Title'),
	             T_('(to be used in permalinks)'), $field_maxlength = 50 ) ;

	$Form->end_fieldset();


	// ############################ WORKFLOW #############################

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$Form->begin_fieldset( T_('Workflow properties'), array( 'id' => 'itemform_workflow_props' ) );

			$Form->select_object( 'item_priority', NULL, $edited_Item, T_('Priority'), '', true, '', 'priority_options' );

			echo ' '; // allow wrapping!

			$Form->select_object( 'item_assigned_user_ID', NULL, $edited_Item, T_('Assigned to'),
														'', true, '', 'get_assigned_user_options' );

			echo ' '; // allow wrapping!

			$ItemStatusCache = & get_Cache( 'ItemStatusCache' );
			$Form->select_options( 'item_st_ID', $ItemStatusCache->get_option_list( $edited_Item->st_ID, true ), 	T_('Task status') );

			echo ' '; // allow wrapping!

			$Form->date( 'item_deadline', $edited_Item->get('deadline'), T_('Deadline') );

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
			<input type="text" name="trackback_url" class="large" id="trackback_url" value="<?php echo format_to_output( $trackback_url, 'formvalue' ); ?>" />
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

	$Form->begin_fieldset( T_('Categories'), array( 'class'=>'extracats', 'id' => 'itemform_categories' ) );

	echo cat_select();

	$Form->end_fieldset();


	// ################### VISIBILITY / SHARING ###################

	$Form->begin_fieldset( T_('Visibility / Sharing'), array( 'id' => 'itemform_visibility' ) );

	$sharing_options = array();
	if( $current_User->check_perm( 'blog_post_statuses', 'published', false, $Blog->ID ) )
		$sharing_options[] = array( 'published', T_('Published (Public)') );
	if( $current_User->check_perm( 'blog_post_statuses', 'protected', false, $Blog->ID ) )
		$sharing_options[] = array( 'protected', T_('Protected (Members only)') );
	if( $current_User->check_perm( 'blog_post_statuses', 'private', false, $Blog->ID ) )
		$sharing_options[] = array( 'private', T_('Private (You only)') );
	if( $current_User->check_perm( 'blog_post_statuses', 'draft', false, $Blog->ID ) )
		$sharing_options[] = array( 'draft', T_('Draft (Not published!)') );
	if( $current_User->check_perm( 'blog_post_statuses', 'deprecated', false, $Blog->ID ) )
		$sharing_options[] = array( 'deprecated', T_('Deprecated (Not published!)') );

	$Form->radio( 'post_status', $post_status, $sharing_options, '', true );

	$Form->end_fieldset();


	// ################### COMMENT STATUS ###################

	if( $Blog->allowcomments == 'post_by_post' )
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


	// ################### TEXT RENDERERS ###################

	$Form->begin_fieldset( T_('Text Renderers'), array( 'id' => 'itemform_renderers' ) );

	// fp> TODO: there should be no param call here (shld be in controller)
	$edited_Item->renderer_checkboxes( param('renderers', 'array', NULL) );

	$Form->end_fieldset();

	?>

</div>

<div class="clear"></div>

<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();



// ####################### LINKS #########################

if( ! $creating )
{ // Editing post

	require dirname(__FILE__).'/inc/_item_links.inc.php';

}


// ####################### JS BEHAVIORS #########################

require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';


/*
 * $Log$
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
 *
 * Revision 1.24  2006/09/11 22:06:08  blueyed
 * Cleaned up option_list callback handling
 *
 * Revision 1.23  2006/09/10 14:21:35  blueyed
 * Added url_same_protocol() (+tests) and use it for preview URL
 *
 * Revision 1.22  2006/08/20 22:25:22  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.21  2006/08/19 08:50:26  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.20  2006/08/19 02:15:08  fplanque
 * Half kille dthe pingbacks
 * Still supported in DB in case someone wants to write a plugin.
 *
 * Revision 1.19  2006/08/18 20:37:15  fplanque
 * Improved bozo validation on post editing
 *
 * Revision 1.18  2006/07/28 15:34:47  blueyed
 * Translate "Upload" and "Files" buttons
 *
 * Revision 1.17  2006/07/27 23:38:30  blueyed
 * More fixes to tabs/blogs switching while editing, mainly by using $edited_Item->load_from_Request()
 *
 * Revision 1.16  2006/07/26 20:25:49  blueyed
 * Refactored/centralized document.title JS update for item forms; additionally init it on page load (when switching tabs/blogs).
 *
 * Revision 1.15  2006/07/23 23:53:33  blueyed
 * item forms: update document title, if title gets changed (very useful in usability means)
 *
 * Revision 1.14  2006/07/08 22:33:43  blueyed
 * Integrated "simple edit form".
 *
 * Revision 1.13  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.12  2006/06/26 23:10:24  fplanque
 * minor / doc
 *
 * Revision 1.11  2006/06/24 05:19:39  smpdawg
 * Fixed various javascript warnings and errors.
 * Spelling corrections.
 * Fixed PHP warnings.
 *
 * Revision 1.10  2006/05/19 18:15:05  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.9.2.1  2006/05/19 15:06:24  fplanque
 * dirty sync
 *
 * Revision 1.9  2006/05/05 19:36:23  blueyed
 * New events
 *
 * Revision 1.8  2006/04/19 15:56:02  blueyed
 * Renamed T_posts.post_comments to T_posts.post_comment_status (DB column rename!);
 * and Item::comments to Item::comment_status (Item API change)
 *
 * Revision 1.7  2006/04/18 19:29:52  fplanque
 * basic comment status implementation
 *
 * Revision 1.6  2006/03/16 18:41:45  blueyed
 * Do not display "Files" button if no permission.
 *
 * Revision 1.5  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.4  2006/03/10 19:04:58  fplanque
 * minor
 *
 * Revision 1.3  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.2  2006/02/24 23:02:43  blueyed
 * Added missing global $pagenow
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.48  2006/01/25 18:24:21  fplanque
 * hooked bozo validator in several different places
 *
 * Revision 1.47  2006/01/20 16:45:11  blueyed
 * Add html IDs to input objects/blocks
 *
 * Revision 1.45  2006/01/05 23:44:21  blueyed
 * Use new event names.
 *
 * Revision 1.42  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.41  2005/11/27 08:54:28  blueyed
 * b2edit_open_preview(): catch double-clicks on Preview button
 *
 * Revision 1.40  2005/11/25 22:45:37  fplanque
 * no message
 *
 * Revision 1.39  2005/11/25 13:14:47  blueyed
 * Javascript b2edit_open_preview(): Fixed submitting item instead of preview for Safari!
 *
 * Revision 1.38  2005/11/24 01:07:11  blueyed
 * simply using span.line again.
 *
 * Revision 1.37  2005/11/23 17:29:19  fplanque
 * no message
 *
 * Revision 1.36  2005/11/23 05:29:49  blueyed
 * Sorry, didn't meant to change input field sizes
 *
 * Revision 1.35  2005/11/23 04:01:08  blueyed
 * Using div.line with whitespace between elements that are allowed to wrap fixes the issues with Konqueror/Safari in b2edit. It also makes it xhtml valid.
 * Still, using "white-space:nowrap" is not good IMHO. It's better to have a note wrap around than not being able to read it..
 *
 * Revision 1.32  2005/11/20 03:55:39  blueyed
 * Closing input tags
 *
 * Revision 1.31  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.30  2005/10/24 23:20:32  blueyed
 * Removed &nbsp; in submit button value.
 *
 * Revision : 1.64  2004/10/6 9:36:55  gorgeb
 * Added allowcomments, a per blog setting taking three values : always, post_by_post, never.
 */
?>
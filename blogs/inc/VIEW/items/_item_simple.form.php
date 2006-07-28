<?php
/**
 * This file implements the SIMPLE Post form.
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
 * @author blueyed: Daniel HAHLER
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
 * @var DataObjectCache
 */
global $ItemTypeCache;
/**
 * @var DataObjectCache
 */
global $ItemStatusCache;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var GeneralSettings
 */
global $Settings;

global $pagenow;


// Begin payload block:
$this->disp_payload_begin();


echo '<strong>EXPERIMENTAL</strong>';


global $form_action, $next_action, $mode, $post_title, $post_locale, $post_title, $use_post_url, $post_url, $content;
global $use_preview, $post_urltitle, $post_status, $post_comment_status, $post_trackbacks;
global $edit_date;

$Form = & new Form( $form_action, 'item_checkchanges', 'post', 'none' );
$Form->fieldstart = '<span class="line">';
$Form->fieldend = '</span>';
$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";


// ================================ START OF EDIT FORM ================================

$Form->begin_form( '' );

$Form->hidden( 'action', $next_action );
$Form->hidden( 'blog', $Blog->ID );
if( isset( $mode ) )   $Form->hidden( 'mode', $mode );
if( isset( $edited_Item ) )   $Form->hidden( 'post_ID', $edited_Item->ID );

// In case we send this to the blog for a preview :
$Form->hidden( 'preview', 1 );
$Form->hidden( 'more', 1 );
$Form->hidden( 'preview_userid', $current_User->ID );


// Fields used in "advanced" form, but not here:
$Form->hidden( 'post_locale', $Request->param('post_locale', 'string', '') );
$Form->hidden( 'item_typ_ID', $Request->param('item_typ_ID', 'integer', NULL) );
$Form->hidden( 'post_url', $Request->param('post_url', 'string', '') );
$Form->hidden( 'post_urltitle', $Request->param('post_urltitle', 'string', '') );
$Form->hidden( 'item_priority', $Request->param('item_priority', 'integer', NULL) );
$Form->hidden( 'item_assigned_user_ID', $Request->param('item_assigned_user_ID', 'integer', NULL) );
$Form->hidden( 'item_st_ID', $Request->param('item_st_ID', 'integer', NULL) );
$Form->hidden( 'item_deadline', $Request->param('item_deadline', 'string', NULL) );
$Form->hidden( 'post_pingback', $Request->param('post_pingback', 'integer', NULL) );
$Form->hidden( 'trackback_url', $Request->param('trackback_url', 'string', NULL) );
$Form->hidden( 'renderers', $Request->param('renderers', 'array', NULL) );


// TODO: Form::hidden() do not add, if NULL?!

?>

<div class="left_col">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->begin_fieldset( T_('Post contents') );

	$Form->text_input( 'post_title', $post_title, 48, T_('Title'), array('maxlength'=>255) );

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array( 'target_type' => 'Item', 'edit_layout' => 'simple' ) );
	echo '</div>';

	// ---------------------------- TEXTAREA -------------------------------------
	$Form->fieldstart = '<div class="edit_area">';
	$Form->fieldend = "</div>\n";
	$Form->textarea_input( 'content', $content, 16, '', array( 'cols' => 40 , 'id' => 'itemform_post_content' ) );
	$Form->fieldstart = '<span class="line">';
	$Form->fieldend = '</span>';
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
		$Form->button( array( 'button', '', T_('Preview'), '', 'b2edit_open_preview(this.form, \''.$Blog->get('dynurl').'\');' ) );
	}

	// ---------- SAVE ----------
	$Form->submit( array( '', /* TRANS: This is the value of an input submit button */ T_('Save !'), 'SaveButton' ) );

	// ---------- DELETE ----------
	if( $next_action == 'update' )
	{ // Editing post
		// Display delete button if current user has the rights:
		$edited_Item->delete_link( ' ', ' ', '#', '#', 'DeleteButton', true );
	}

	if( $Settings->get( 'fm_enabled' ) )
	{ // ---------- UPLOAD ----------
		if( $current_User->check_perm( 'files', 'view' ) )
		{
			echo '<input id="itemform_button_files" type="button" value="'.format_to_output(T_('Files'), 'formvalue').'" class="ActionButton"
			       onclick="pop_up_window( \'admin.php?ctrl=files&amp;mode=upload\', \'fileman_upload\' );" /> ';
		}

		if( $Settings->get('upload_enabled') && $current_User->check_perm( 'files', 'add' ) )
		{
			echo '<input id="itemform_button_upload" type="button" value="'.format_to_output(T_('Upload'), 'formvalue').'" class="ActionButton"
			       onclick="pop_up_window( \'admin.php?ctrl=files&amp;mode=upload&amp;fm_mode=file_upload\', \'fileman_upload\' );" /> ';
		}
	}

	// CALL PLUGINS NOW:
	// TODO: make sure plugin authors are strongly encouraged not to display their stuff on simple edit screen by default. Add returns if 'simple' to all existing plugins on the planet, tehn enable this :P
	// $Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'simple' ) );

	echo '</div>';


	if( $current_User->check_perm( 'edit_timestamp' ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		?>
		<br />
		<div id="itemform_edit_timestamp">
		<?php
		$Form->date( 'item_issue_date', $edited_Item->get('issue_date'), T_('Issue date') );
		echo ' '; // allow wrapping!
		$Form->time( 'item_issue_time', $edited_Item->get('issue_date'), '' );
		echo ' '; // allow wrapping!
		if( $next_action == 'create' )
		{ // If not checked, create time will be used...
			$Form->checkbox( 'edit_date', $edit_date, '', T_('Edit') );
		}
		?>
		</div>
		<?php
	}

	$Form->end_fieldset();


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

	?>

</div>

<div class="clear"></div>

<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();


// End payload block:
$this->disp_payload_end();


// ####################### LINKS #########################

if( $next_action == 'update' )
{ // Editing post
	// End payload block:
	$this->disp_payload_begin();

	// Consider that if we are here, we're allowed to edit.
	$edit_allowed = true;

	require dirname(__FILE__).'/_item_links.inc.php';

	// End payload block:
	$this->disp_payload_end();
}


/*
 * $Log$
 * Revision 1.7  2006/07/28 15:34:47  blueyed
 * Translate "Upload" and "Files" buttons
 *
 * Revision 1.6  2006/07/27 23:38:30  blueyed
 * More fixes to tabs/blogs switching while editing, mainly by using $edited_Item->load_from_Request()
 *
 * Revision 1.5  2006/07/26 20:25:49  blueyed
 * Refactored/centralized document.title JS update for item forms; additionally init it on page load (when switching tabs/blogs).
 *
 * Revision 1.4  2006/07/23 23:53:33  blueyed
 * item forms: update document title, if title gets changed (very useful in usability means)
 *
 * Revision 1.3  2006/07/16 23:04:05  fplanque
 * Most plugins should keep as quiet as possible in simple mode.
 * Smilies are about the only thing simple enough for simple mode.
 *
 * Revision 1.2  2006/07/08 22:33:43  blueyed
 * Integrated "simple edit form".
 *
 * Revision 1.1  2006/07/08 15:48:14  fplanque
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
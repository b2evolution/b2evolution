<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
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


if( isset($Blog) )
{
?>
<script type="text/javascript" language="javascript">
	<!--
	/*
	 * Open the item in a preview window (a new window with target 'b2evo_preview'), by changing
	 * the form's action attribute and target temporarily.
	 *
	 * fplanque: created
	 */
	function open_preview(form)
	{
		if( form.target == 'b2evo_preview' )
		{ // A double-click on the Preview button
			return false;
		}
		// Stupid thing: having a field called action !
		var saved_action = form.attributes.getNamedItem('action').value;
		form.attributes.getNamedItem('action').value = '<?php $Blog->disp( 'dynurl', 'raw' ) ?>';

		// FIX for Safari (2.0.2, OS X 10.4.3), to not submit the item on "Preview"! - (Konqueror does not fail here)
		if( form.attributes.getNamedItem('action').value == saved_action )
		{ // Still old value: Setting form.action failed! (This is the case for Safari)
			// NOTE: checking "form.action == saved_action" (or through document.getElementById()) does not work - Safari uses the input element then
			{ // _Setting_ form.action however sets the form's action attribute (not the input element) on Safari
				form.action = '<?php $Blog->disp( 'dynurl', 'raw' ) ?>';
			}

			if( form.attributes.getNamedItem('action').value == saved_action )
			{ // Still old value, did not work.
				alert( "Preview not supported. Sorry. (Could not set form.action for preview)" );
				return false;
			}
		}
		// END FIX for Safari

		form.target = 'b2evo_preview';
		preview_window = window.open( '', 'b2evo_preview' );
		preview_window.focus();
		// submit after target window is created.
		form.submit();
		form.attributes.getNamedItem('action').value = saved_action;
		form.target = '_self';
	}
	/*
	 * edit_reload()
	 * fplanque: created
	 */
	function edit_reload( form, blog )
	{
		form.attributes.getNamedItem('action').value = '<?php echo $pagenow ?>';
		form.blog.value = blog;
		// form.action.value = 'reload';
		// form.post_title.value = 'demo';
		// alert( form.action.value + ' ' + form.post_title.value );
		form.submit();
		return false;
	}
	// End -->
</script>
<?php
}


// Begin payload block:
$this->disp_payload_begin();

global $form_action, $next_action, $mode, $post_title, $post_locale, $post_title, $use_post_url, $post_url, $content;
global $use_preview, $post_urltitle, $post_status, $post_comments, $post_trackbacks;

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

?>

<div class="left_col">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->begin_fieldset( T_('Post contents') );

	$Form->text( 'post_title', $post_title, 48, T_('Title'), '', 255 );

	echo ' <span id="itemform_post_locale">'; // allow wrapping here! (and below)
	                                          // blueyed>> (Opera would additionally need text/&nbsp; here, but that wraps ugly)
	$Form->select( 'post_locale', $post_locale, 'locale_options_return', T_('Language') );
	echo '</span>';

	echo ' <span id="itemform_typ_ID">';
	$Form->select_object( 'item_typ_ID', $edited_Item->typ_ID, $ItemTypeCache, T_('Type') );
	echo '</span>';

	if( $use_post_url )
	{
		echo ' <span id="itemform_post_url">';
		$Form->text( 'post_url', $post_url, 40, T_('Link to url'), '', 255 );
		echo '</span>';
	}
	else
	{
		$Form->hidden( 'post_url', '' );
	}

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array( 'target_type' => 'Item' ) );
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
		b2evoCanvas = document.getElementById('itemform_post_content');
		//-->
	</script>

	<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	if( $use_preview )
	{ // ---------- PREVIEW ----------
		$Form->button( array( 'button', '', T_('Preview'), '', 'open_preview(this.form);' ) );
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
		echo '<input id="itemform_button_files" type="button" value="Files" class="ActionButton"
						onclick="pop_up_window( \'admin.php?ctrl=files&amp;mode=upload\', \'fileman_upload\' );" /> ';

		if( $Settings->get('upload_enabled') && $current_User->check_perm( 'files', 'add' ) )
		{
			echo '<input id="itemform_button_upload" type="button" value="Upload" class="ActionButton"
							onclick="pop_up_window( \'admin.php?ctrl=files&amp;mode=upload&amp;fm_mode=file_upload\', \'fileman_upload\' );" /> ';
		}
	}

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item' ) );

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
		if( $next_action == 'create' )
		{ // If not checked, create time will be used...
			$Form->checkbox( 'edit_date', 0, '', T_('Edit') );
		}
		?>
		</div>
		<?php
	}

	$Form->text( 'post_urltitle', $post_urltitle, 40, T_('URL Title'),
	             T_('(to be used in permalinks)'), $field_maxlength = 50 ) ;

	$Form->end_fieldset();


	// ############################ WORKFLOW #############################

	$Form->begin_fieldset( T_('Workflow properties'), array( 'id' => 'itemform_workflow_props' ) );

	$Form->select_object( 'item_priority', NULL, $edited_Item, T_('Priority'), '', true, '', 'priority_options' );

	echo ' '; // allow wrapping!

	$Form->select_object( 'item_assigned_user_ID', NULL, $edited_Item, T_('Assigned to'),
												'', true, '', 'get_assigned_user_options' );

	echo ' '; // allow wrapping!

	$Form->select_options( 'item_st_ID',
												$ItemStatusCache->option_list_return( $edited_Item->st_ID, ! $edited_Item->st_required ),
												 T_('Task status') );

	echo ' '; // allow wrapping!

	$Form->date( 'item_deadline', $edited_Item->get('deadline'), T_('Deadline') );

	$Form->end_fieldset();


	// ####################### ADDITIONAL ACTIONS #########################

	if( isset( $Blog ) && ( $Blog->get('allowpingbacks') || $Blog->get('allowtrackbacks') ) )
	{
		$Form->begin_fieldset( T_('Additional actions'), array( 'id' => 'itemform_additional_actions' ) );

		if( $Blog->get('allowpingbacks') )
		{ // --------------------------- PINGBACK --------------------------------------
			global $post_pingback;
		?>
		<div id="itemform_pingbacks">
			<input type="checkbox" class="checkbox" name="post_pingback" value="1" id="post_pingback"
				<?php	if ($post_pingback) { echo ' checked="checked"'; } ?> />
			<label for="post_pingback"><strong><?php echo T_('Pingback') ?></strong> <span class="notes"><?php echo T_('(Send a pingback to all URLs in this post)') ?></span></label>
		</div>
		<?php
		}

		if( $Blog->get('allowtrackbacks') )
		{ // --------------------------- TRACKBACK --------------------------------------
		?>
		<div id="itemform_trackbacks">
			<label for="trackback_url"><strong><?php echo T_('Trackback URLs') ?>:</strong> <span class="notes"><?php echo T_('(Separate by space)') ?></span></label><br /><input type="text" name="trackback_url" class="large" id="trackback_url" value="<?php echo format_to_output( $post_trackbacks, 'formvalue' ); ?>" />
		</div>
		<?php
		}

		$Form->end_fieldset();
	}
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
			<label title="<?php echo T_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comments" value="open" class="checkbox" <?php if( $post_comments == 'open' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Open') ?></label><br />

			<label title="<?php echo T_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comments" value="closed" class="checkbox" <?php if( $post_comments == 'closed' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Closed') ?></label><br />

			<label title="<?php echo T_('Visitors cannot see nor leave comments on this post.') ?>"><input type="radio" name="post_comments" value="disabled" class="checkbox" <?php if( $post_comments == 'disabled' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Disabled') ?></label><br />
		<?php

		$Form->end_fieldset();
	}


	// ################### TEXT RENDERERS ###################

	$Form->begin_fieldset( T_('Text Renderers'), array( 'id' => 'itemform_renderers' ) );

	$edited_Item->renderer_checkboxes();

	$Form->end_fieldset();

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
 * open_preview(): catch double-clicks on Preview button
 *
 * Revision 1.40  2005/11/25 22:45:37  fplanque
 * no message
 *
 * Revision 1.39  2005/11/25 13:14:47  blueyed
 * Javascript open_preview(): Fixed submitting item instead of preview for Safari!
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
 * Revision 1.29  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.28  2005/08/24 10:38:53  blueyed
 * typo
 *
 * Revision 1.27  2005/08/22 18:42:25  fplanque
 * minor
 *
 * Revision 1.26  2005/07/26 18:50:48  fplanque
 * enhanced attached file handling
 *
 * Revision 1.25  2005/05/16 15:17:12  fplanque
 * minor
 *
 * Revision 1.24  2005/05/13 18:41:28  fplanque
 * made file links clickable... finally ! :P
 *
 * Revision 1.23  2005/05/11 15:58:30  fplanque
 * cleanup
 *
 * Revision 1.22  2005/05/09 16:09:38  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.21  2005/04/21 18:01:28  fplanque
 * CSS styles refactoring
 *
 * Revision 1.20  2005/04/15 18:02:58  fplanque
 * finished implementation of properties/meta data editor
 * started implementation of files to items linking
 *
 * Revision 1.19  2005/03/16 19:58:13  fplanque
 * small AdminUI cleanup tasks
 *
 * Revision 1.18  2005/03/04 18:40:26  fplanque
 * added Payload display wrappers to admin skin object
 *
 * Revision 1.17  2005/02/28 09:06:37  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.16  2005/02/27 20:34:48  blueyed
 * Admin UI refactoring
 *
 * Revision 1.15  2005/02/20 22:48:47  blueyed
 * use $edited_Item->renderer_checkboxes()
 *
 * Revision 1.14  2005/02/15 22:05:11  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.13  2005/02/08 20:17:29  blueyed
 * removed obsolete $User_ID global
 *
 * Revision 1.12  2005/01/25 15:07:18  fplanque
 * cleanup
 *
 * Revision 1.11  2005/01/20 20:38:58  fplanque
 * refactoring
 *
 * Revision 1.10  2005/01/13 19:53:48  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.9  2005/01/10 02:08:37  blueyed
 * Use $Settings to check if upload allowed
 *
 * Revision 1.8  2005/01/09 05:36:39  blueyed
 * fileupload
 *
 * Revision 1.5  2004/12/21 21:18:37  fplanque
 * Finished handling of assigning posts/items to users
 *
 * Revision 1.4  2004/12/20 19:49:23  fplanque
 * cleanup & factoring
 *
 * Revision 1.3  2004/12/17 20:38:51  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.2  2004/12/15 20:50:31  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.1  2004/12/14 20:27:11  fplanque
 * splited post/comment edit forms
 *
 * Revision : 1.64  2004/10/6 9:36:55  gorgeb
 * Added allowcomments, a per blog setting taking three values : always, post_by_post, never.
 */
?>
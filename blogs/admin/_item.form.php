<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author fplanque: François PLANQUE
 * @author fsaya: Fabrice SAYA-GASNIER / PROGIDISTRI
 * @author blueyed: Daniel HAHLER
 * @author gorgeb
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

if( isset($Blog) )
{
?>
<script type="text/javascript" language="javascript">
	<!--
	/*
	 * open_preview()
	 * fplanque: created
	 */
	function open_preview(form)
	{
		// Stupid thing: having a field called action !
		var saved_action =  form.attributes.getNamedItem('action').value;
		form.attributes.getNamedItem('action').value = '<?php $Blog->disp( 'dynurl', 'raw' ) ?>';
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


// Display submenu:
require dirname(__FILE__).'/_submenu.inc.php';

$Form = & new Form( $form_action, 'post', 'post', 'none' );
$Form->fieldstart = '<span class="line">';
$Form->fieldend = '</span>';
$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";


// ================================ START OF EDIT FORM ================================

$Form->begin_form( '' );

$Form->hidden( 'action', $next_action );
$Form->hidden( 'blog', $blog );
if( isset( $mode ) )   $Form->hidden( 'mode', $mode );
if( isset( $post ) )   $Form->hidden( 'post_ID', $post );
if( isset( $tsk_ID ) ) $Form->hidden( 'tsk_ID', $tsk_ID );

// In case we send this to the blog for a preview :
$Form->hidden( 'preview', 1 );
$Form->hidden( 'more', 1 );
$Form->hidden( 'preview_userid', $current_User->ID );

?>

<div class="left_col">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->fieldset( T_('Post contents') );

	$Form->text( 'post_title', $post_title, 48, T_('Title'), '', 255 );

	$Form->select( 'post_locale', $post_locale, 'locale_options_return', T_('Language') );

	$Form->select_object( 'item_typ_ID', $edited_Item->typ_ID, $itemTypeCache, T_('Type') );

	if( $use_post_url )
	{
		$Form->text( 'post_url', $post_url, 40, T_('Link to url'), '', 255 );
	}
	else
	{
		$Form->hidden( 'post_url', '' );
	}

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayToolbar', array( 'target_type' => 'Item' ) );
	echo '</div>';

	// ---------------------------- TEXTAREA -------------------------------------
	$Form->fieldstart = '<div class="edit_area">';
	$Form->fieldend = "</div>\n";
	$Form->textarea( 'content', $content, 16, '', '', 40 , '' );
	$Form->fieldstart = '<span class="line">';
	$Form->fieldend = '</span>';
	?>
	<script type="text/javascript" language="JavaScript">
		<!--
		// This is for toolbar plugins
		b2evoCanvas = document.getElementById('content');
		//-->
	</script>

	<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	if( $use_preview )
	{ // ---------- PREVIEW ----------
		$Form->button( array( 'button', '', T_('Preview'), '', 'open_preview(this.form);' ) );
	}

	// ---------- SAVE ----------
	$Form->submit( array( '',/* TRANS: the &nbsp; are just here to make the button larger. If your translation is a longer word, don't keep the &nbsp; */ T_('&nbsp; Save ! &nbsp;'), 'SaveButton' ) );

	// ---------- DELETE ----------
	if( $next_action == 'update' )
	{ // Editing post
		// Display delete button if current user has the rights:
		$edited_Item->delete_link( ' ', ' ', '#', '#', 'DeleteButton', true );
	}

	if( $Settings->get( 'fm_enabled' ) && $Settings->get( 'upload_enabled' ) )
	{ // ---------- UPLOAD ----------
		require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_filemanager.class.php' );
		$Fileman = new Filemanager( $current_User, 'files.php', 'user' );
		$Fileman->dispButtonUploadPopup( T_('Files') );
	}

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayEditorButton', array( 'target_type' => 'Item' ) );

	echo '</div>';

	$Form->fieldset_end();


	// ############################ ADVANCED #############################

	$Form->fieldset( T_('Advanced properties') );

	if( $current_User->check_perm( 'edit_timestamp' ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		$Form->date( 'item_issue_date', $edited_Item->get('issue_date'), T_('Issue date') );
		$Form->time( 'item_issue_time', $edited_Item->get('issue_date'), '' );
		if( $next_action == 'create' )
		{ // If not checked, create time will be used...
			$Form->checkbox( 'edit_date', 0, '', T_('Edit') );
		}
	}

	$Form->text( 'post_urltitle', $post_urltitle, 40, T_('URL Title'),
							 T_('(to be used in permalinks)'), $field_maxlength = 50 ) ;

	$Form->fieldset_end();


	// ############################ WORKFLOW #############################

	$Form->fieldset( T_('Workflow properties') );

	$Form->select_options( 'item_st_ID',
												$itemStatusCache->option_list_return( $edited_Item->st_ID, ! $edited_Item->st_required ),
												 T_('Task status') );

	$Form->select_object( 'item_assigned_user_ID', NULL, $edited_Item, T_('Assigned to'),
												'', false, '', 'get_assigned_user_options' );

	$Form->select_object( 'item_priority', NULL, $edited_Item, T_('Priority'),
												'', false, '', 'priority_options' );

	$Form->date( 'item_deadline', $edited_Item->get('deadline'), T_('Deadline') );

	$Form->fieldset_end();


	// ####################### ADDITIONAL ACTIONS #########################

	if( isset( $Blog ) && ( $Blog->get('allowpingbacks') || $Blog->get('allowtrackbacks') ) )
	{
		$Form->fieldset( T_('Additional actions') );

		if( $Blog->get('allowpingbacks') )
		{ // --------------------------- PINGBACK --------------------------------------

		?>
		<div>
			<input type="checkbox" class="checkbox" name="post_pingback" value="1" id="post_pingback"
				<?php	if ($post_pingback) { echo ' checked="checked"'; } ?> />
			<label for="post_pingback"><strong><?php echo T_('Pingback') ?></strong> <span class="notes"><?php echo T_('(Send a pingback to all URLs in this post)') ?></span></label>
		</div>
		<?php
		}

		if( $Blog->get('allowtrackbacks') )
		{ // --------------------------- TRACKBACK --------------------------------------
		?>
		<div>
			<label for="trackback_url"><strong><?php echo T_('Trackback URLs') ?>:</strong> <span class="notes"><?php echo T_('(Separate by space)') ?></span></label><br /><input type="text" name="trackback_url" class="large" id="trackback_url" value="<?php echo format_to_output( $post_trackbacks, 'formvalue' ); ?>" />
		</div>
		<?php
		}

		$Form->fieldset_end();

	}
	?>

</div>

<div class="right_col">

	<?php
	// ################### CATEGORIES ###################

	$Form->fieldset( T_('Categories'), 'extracats' );

	echo cat_select();

	$Form->fieldset_end();


	// ################### VISIBILITY / SHARING ###################

	$Form->fieldset( T_('Visibility / Sharing') );

	$sharing_options = array();
	if( $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog ) )
		$sharing_options[] = array( 'published', T_('Published (Public)') );
	if( $current_User->check_perm( 'blog_post_statuses', 'protected', false, $blog ) )
		$sharing_options[] = array( 'protected', T_('Protected (Members only)') );
	if( $current_User->check_perm( 'blog_post_statuses', 'private', false, $blog ) )
		$sharing_options[] = array( 'private', T_('Private (You only)') );
	if( $current_User->check_perm( 'blog_post_statuses', 'draft', false, $blog ) )
		$sharing_options[] = array( 'draft', T_('Draft (Not published!)') );
	if( $current_User->check_perm( 'blog_post_statuses', 'deprecated', false, $blog ) )
		$sharing_options[] = array( 'deprecated', T_('Deprecated (Not published!)') );

	$Form->radio( 'post_status', $post_status, $sharing_options, '', true );

	$Form->fieldset_end();


	// ################### COMMENT STATUS ###################

	if( $Blog->allowcomments == 'post_by_post' )
	{
		$Form->fieldset( T_('Comments') );

		?>
			<label title="<?php echo T_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comments" value="open" class="checkbox" <?php if( $post_comments == 'open' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Open') ?></label><br />

			<label title="<?php echo T_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comments" value="closed" class="checkbox" <?php if( $post_comments == 'closed' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Closed') ?></label><br />

			<label title="<?php echo T_('Visitors cannot see nor leave comments on this post.') ?>"><input type="radio" name="post_comments" value="disabled" class="checkbox" <?php if( $post_comments == 'disabled' ) echo 'checked="checked"'; ?> />
			<?php echo T_('Disabled') ?></label><br />
		<?php

		$Form->fieldset_end();
	}


	// ################### TEXT RENDERERS ###################

	$Form->fieldset( T_('Text Renderers') );

	$edited_Item->renderer_checkboxes();

	$Form->fieldset_end();

	?>

</div>

<div class="clear"></div>

<?php

$Form->end_form();

// ================================== END OF EDIT FORM ==================================

// End block:
require dirname(__FILE__).'/_sub_end.inc.php';

/*
 * $Log$
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
 * Revision 1.7  2005/01/03 15:17:51  fplanque
 * no message
 *
 * Revision 1.6  2004/12/23 21:19:40  fplanque
 * no message
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
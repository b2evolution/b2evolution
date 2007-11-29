<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
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
global $use_preview, $post_comment_status, $trackback_url, $item_tags;
global $edit_date, $bozo_start_modified, $creating;
global $item_title, $item_content;
global $redirect_to;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = & new Form( NULL, 'item_checkchanges', 'post' );
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
	echo '<td width="1%"><strong>'.T_('Title').':</strong></td>';
	echo '<td width="97%" class="input">';
	$Form->text_input( 'post_title', $item_title, 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td>';
	echo '<td width="1%">&nbsp;&nbsp;<strong>'.T_('Language').':</strong></td>';
	echo '<td width="1%" class="select">';
	$Form->select( 'post_locale', $edited_Item->get( 'locale' ), 'locale_options_return', '' );
	echo '</td></tr></table>';

	echo '<table cellspacing="0" class="compose_layout"><tr>';
	if( $use_post_url )
	{
		echo '<td width="1%"><strong>'.T_('Link to url').':</strong></td>';
		echo '<td class="input">';
		$Form->text_input( 'post_url', $edited_Item->get( 'url' ), 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
		echo '</td>';
	}
	else
	{
		echo '<td>';
		$Form->hidden( 'post_url', '' );
		echo '</td>';
	}

	echo '<td width="1%">&nbsp;&nbsp;<strong>'.T_('Type').':</strong></td>';
	echo '<td width="1%" class="select">';
	$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
	$Form->select_object( 'item_typ_ID', $edited_Item->ptyp_ID, $ItemTypeCache, '' );
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
		//-->
	</script>

	<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	if( $use_preview )
	{ // ---------- PREVIEW ----------
		load_funcs( '_core/_url.funcs.php' );
		$url = url_same_protocol( $Blog->get( 'url' ) ); // was dynurl

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
			// TODO: image integration into posts after upload is broken...
			echo '<input id="itemform_button_files" type="button" value="'.format_to_output(T_('Files'), 'formvalue').'" class="ActionButton"
			       onclick="pop_up_window( \''.url_add_param( $Blog->get_filemanager_link(), 'mode=upload' ).'\', \'fileman_upload\', 1000 )" /> ';
		}
	}

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );

	echo '</div>';

	$Form->end_fieldset();


	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Advanced properties'), array( 'id' => 'itemform_adv_props' ) );

 	$Form->switch_layout( 'linespan' );

	if( $current_User->check_perm( 'edit_timestamp' ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------

		echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';

		$Form->date( 'item_issue_date', $edited_Item->get('issue_date'), T_('Issue date') );
		echo ' '; // allow wrapping!
		$Form->time( 'item_issue_time', $edited_Item->get('issue_date'), '' );
		echo ' '; // allow wrapping!
		if( $creating )
		{ // If not checked, create time will be used...
			$Form->checkbox( 'edit_date', $edit_date, '', T_('Edit') );
		}

		echo '</div>';
	}


	echo '<div id="itemform_urltitle" class="edit_fieldgroup">';
	$Form->text( 'post_urltitle', $edited_Item->get( 'urltitle' ), 40, T_('URL Title'),
	             T_('(to be used in permalinks)'), $field_maxlength = 50 ) ;
	echo '</div>';
	?>

	<div id="itemform_tags" class="edit_fieldgroup">
		<label for="item_tags"><strong><?php echo T_('Tags') ?>:</strong>
		<span class="notes"><?php echo T_('(Separate by coma (,))') ?></span></label><br />
		<input type="text" name="item_tags" class="large form_text_input" id="item_tags" value="<?php echo format_to_output( $item_tags, 'formvalue' ); ?>" />
	</div>

	<div id="itemform_post_excerpt" class="edit_fieldgroup">
		<label for="post_excerpt"><strong><?php echo T_('Excerpt') ?>:</strong>
		<span class="notes"><?php echo T_('(for XML feeds)') ?></span></label><br />
		<textarea name="post_excerpt" rows="2" cols="25" class="large" id="post_excerpt"><?php $edited_Item->disp( 'excerpt', 'formvalue' ) ?></textarea>
	</div>

	<?php
	$Form->switch_layout( NULL );

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

			$ItemStatusCache = & get_Cache( 'ItemStatusCache' );
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

	$Form->begin_fieldset( T_('Categories'), array( 'class'=>'extracats', 'id' => 'itemform_categories' ) );

	echo cat_select();

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



// ####################### LINKS #########################

if( ! $creating )
{ // Editing post

	require dirname(__FILE__).'/inc/_item_links.inc.php';

}


// ####################### JS BEHAVIORS #########################

require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';


/*
 * $Log$
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
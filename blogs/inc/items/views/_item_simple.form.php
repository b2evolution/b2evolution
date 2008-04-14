<?php
/**
 * This file implements the SIMPLE Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @var Plugins
 */
global $Plugins;
/**
 * @var GeneralSettings
 */
global $Settings;

global $pagenow;

global $mode;
global $use_preview, $post_comment_status, $trackback_url, $item_tags;
global $bozo_start_modified, $creating;
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
$Form->hidden( 'blog', $Blog->ID );
if( isset( $mode ) )   $Form->hidden( 'mode', $mode ); // used by bookmarklet
if( isset( $edited_Item ) )   $Form->hidden( 'post_ID', $edited_Item->ID );
$Form->hidden( 'redirect_to', $redirect_to );

// In case we send this to the blog for a preview :
$Form->hidden( 'preview', 1 );
$Form->hidden( 'more', 1 );
$Form->hidden( 'preview_userid', $current_User->ID );


// Fields used in "advanced" form, but not here:
$Form->hidden( 'post_locale', $edited_Item->get( 'locale' ) );
$Form->hidden( 'item_typ_ID', $edited_Item->ptyp_ID );
$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
$Form->hidden( 'post_excerpt', $edited_Item->get( 'excerpt' ) );
$Form->hidden( 'post_urltitle', $edited_Item->get( 'urltitle' ) );
$Form->hidden( 'titletag', $edited_Item->get( 'titletag' ) );
if( $Blog->get_setting( 'use_workflow' ) )
{	// We want to use workflow properties for this blog:
	$Form->hidden( 'item_priority', $edited_Item->priority );
	$Form->hidden( 'item_assigned_user_ID', $edited_Item->assigned_user_ID );
	$Form->hidden( 'item_st_ID', $edited_Item->pst_ID );
	$Form->hidden( 'item_deadline', $edited_Item->datedeadline );
}
$Form->hidden( 'trackback_url', $trackback_url );
$Form->hidden( 'renderers_displayed', 1 );
$Form->hidden( 'renderers', $edited_Item->get_renderers_validated() );
$Form->hidden( 'item_featured', $edited_Item->featured );
$Form->hidden( 'item_order', $edited_Item->order );
// CUSTOM FIELDS double
for( $i = 1 ; $i <= 5; $i++ )
{	// For each custom double field:
	$Form->hidden( 'item_double'.$i, $edited_Item->{'double'.$i} );
}
// CUSTOM FIELDS varchar
for( $i = 1 ; $i <= 3; $i++ )
{	// For each custom varchar field:
	$Form->hidden( 'item_varchar'.$i, $edited_Item->{'varchar'.$i} );
}

// TODO: Form::hidden() do not add, if NULL?!

?>

<div class="left_col">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->begin_fieldset( T_('Post contents').get_manual_link('post_contents_fieldset') );

	$Form->switch_layout( 'none' );

	echo '<table cellspacing="0" class="compose_layout"><tr>';
	echo '<td class"label"><strong>'.T_('Title').':</strong></td>';
	echo '<td class="input">';
	$Form->text_input( 'post_title', $item_title, 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr></table>';

	$Form->switch_layout( NULL );

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array( 'target_type' => 'Item', 'edit_layout' => 'simple' ) );
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

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'simple' ) );

	// ---------- FILES ----------
	/* Deprecated by "Attach files (popup)"

	if( $Settings->get( 'fm_enabled' ) )
	{ // Note: we try to land in the Blog media folder if possible
		// fp> TODO: check what happens if blog folders are disabled
		if( $current_User->check_perm( 'files', 'view' ) )
		{
			$fm_url_params = 'mode=upload';
			if( !empty($edited_Item->ID) )
			{
				$fm_url_params .= '&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID;
			}
			echo '<input id="itemform_button_files" type="button" value="'.format_to_output(T_('Files...'), 'formvalue')
						.'" class="ActionButton" onclick="pop_up_window( \''
						.url_add_param( $Blog->get_filemanager_link(), $fm_url_params ).'\', \'fileman_upload\', 1000 )" /> ';
		}
	}
	*/

	if( $use_preview )
	{ // ---------- PREVIEW ----------
		$url = url_same_protocol( $Blog->get( 'url' ) ); // was dynurl

		$Form->button( array( 'button', '', T_('Preview'), '', 'b2edit_open_preview(this.form, \''.$url.'\');' ) );
	}

	// ---------- SAVE ----------
	$next_action = ($creating ? 'create' : 'update');
	$Form->submit( array( 'actionArray['.$next_action.'_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & edit'), 'SaveEditButton' ) );
	$Form->submit( array( 'actionArray['.$next_action.']', /* TRANS: This is the value of an input submit button */ T_('Save'), 'SaveButton' ) );
	if( $edited_Item->status == 'draft'
			&& $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID )	// TODO: if we actually set the primary cat to another blog, we may still get an ugly perm die
			&& $current_User->check_perm( 'edit_timestamp', 'edit', false ) )
	{	// Only allow publishing if in draft mode. Other modes are too special to run the risk of 1 click publication.
		$Form->submit( array( 'actionArray['.$next_action.'_publish]', /* TRANS: This is the value of an input submit button */ T_('Publish NOW !'), 'SaveButton' ) );
	}


	echo '</div>';

	$Form->end_fieldset();


	// ####################### ATTACHMENTS/LINKS #########################

	attachment_iframe( $Form, $creating, $edited_Item, $Blog );


	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Meta info').get_manual_link('post_simple_meta_fieldset'), array( 'id' => 'itemform_adv_props' ) );

	if( $current_User->check_perm( 'edit_timestamp' ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
		$Form->switch_layout( 'linespan' );
		issue_date_control( $Form, false );
		$Form->switch_layout( NULL );
		echo '</div>';
	}

	echo '<table cellspacing="0" class="compose_layout">';
	echo '<tr><td class="label"><label for="item_tags">'.T_('Tags').':</strong> <span class="notes">'.T_('sep by ,').'</span></label></label></td>';
	echo '<td class="input">';
	$Form->text_input( 'item_tags', $item_tags, 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';
	echo '</table>';

	$Form->end_fieldset();


	// ####################### PLUGIN FIELDSETS #########################

	$Plugins->trigger_event( 'AdminDisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item, 'edit_layout' => 'simple' ) );
	?>

</div>

<div class="right_col">

	<?php
	// ################### CATEGORIES ###################

	cat_select( $Form );


	// ################### VISIBILITY / SHARING ###################

	$Form->begin_fieldset( T_('Visibility / Sharing'), array( 'id' => 'itemform_visibility' ) );

	$Form->switch_layout( 'linespan' );
	visibility_select( $Form, $edited_Item->status );
	$Form->switch_layout( NULL );

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

// ####################### JS BEHAVIORS #########################

require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

/*
 * $Log$
 * Revision 1.19  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.18  2008/04/14 16:24:39  fplanque
 * use ActionArray[] to make action handlign more robust
 *
 * Revision 1.17  2008/04/13 20:40:07  fplanque
 * enhanced handlign of files attached to items
 *
 * Revision 1.16  2008/04/04 17:02:23  fplanque
 * cleanup of global settings
 *
 * Revision 1.15  2008/04/03 22:03:09  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.14  2008/04/03 19:33:27  fplanque
 * category selector will be smaller if less than 11 cats
 *
 * Revision 1.13  2008/04/03 15:54:19  fplanque
 * enhanced edit layout
 *
 * Revision 1.12  2008/04/03 13:39:15  fplanque
 * fix
 *
 * Revision 1.11  2008/03/22 15:20:19  fplanque
 * better issue time control
 *
 * Revision 1.10  2008/02/09 20:14:14  fplanque
 * custom fields management
 *
 * Revision 1.9  2008/02/09 02:56:00  fplanque
 * explicit order by field
 *
 * Revision 1.8  2008/01/28 20:17:45  fplanque
 * better display of image file linking while in 'upload' mode
 *
 * Revision 1.7  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.6  2008/01/14 23:41:47  fplanque
 * cleanup load_funcs( urls ) in main because it is ubiquitously used
 *
 * Revision 1.5  2007/09/17 20:04:40  fplanque
 * UI improvements
 *
 * Revision 1.4  2007/09/12 21:00:31  fplanque
 * UI improvements
 *
 * Revision 1.3  2007/09/04 22:16:33  fplanque
 * in context editing of posts
 *
 * Revision 1.2  2007/09/03 16:44:28  fplanque
 * chicago admin skin
 *
 * Revision 1.1  2007/06/25 11:00:32  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.37  2007/05/14 02:47:23  fplanque
 * (not so) basic Tags framework
 *
 * Revision 1.36  2007/05/13 22:03:21  fplanque
 * basic excerpt support
 *
 * Revision 1.35  2007/04/26 00:11:07  fplanque
 * (c) 2007
 *
 * Revision 1.34  2007/04/05 22:57:33  fplanque
 * Added hook: UnfilterItemContents
 *
 * Revision 1.33  2007/03/25 13:19:17  fplanque
 * temporarily disabled dynamic and static urls.
 * may become permanent in favor of a caching mechanism.
 *
 * Revision 1.32  2007/03/21 02:21:37  fplanque
 * item controller: highlight current (step 2)
 *
 * Revision 1.31  2007/03/21 01:44:51  fplanque
 * item controller: better return to current filterset - step 1
 *
 * Revision 1.30  2007/03/11 23:56:02  fplanque
 * fixed some post editing oddities / variable cleanup (more could be done)
 *
 * Revision 1.29  2007/01/26 02:12:09  fplanque
 * cleaner popup windows
 *
 * Revision 1.28  2006/12/14 00:01:49  fplanque
 * land in correct collection when opening FM from an Item
 *
 * Revision 1.27  2006/12/12 23:23:30  fplanque
 * finished post editing v2.0
 *
 * Revision 1.26  2006/12/12 21:19:31  fplanque
 * UI fixes
 *
 * Revision 1.25  2006/12/12 02:53:57  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.24  2006/12/11 00:02:25  fplanque
 * Worfklow stuff is now hidden by default and can be enabled on a per blog basis.
 *
 * Revision 1.23  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.22  2006/12/06 23:55:53  fplanque
 * hidden the dead body of the sidebar plugin + doc
 *
 * Revision 1.21  2006/11/29 20:48:46  blueyed
 * Moved url_rel_to_same_host() from _misc.funcs.php to _url.funcs.php
 *
 * Revision 1.20  2006/11/19 16:07:31  blueyed
 * Fixed saving empty renderers list. This should also fix the saving of "default" instead of the explicit renderer list
 *
 * Revision 1.19  2006/11/19 03:50:29  fplanque
 * cleaned up CSS
 *
 * Revision 1.17  2006/11/16 23:48:56  blueyed
 * Use div.line instead of span.line as element wrapper for XHTML validity
 *
 * Revision 1.16  2006/11/16 23:10:35  blueyed
 * Added AdminDisplayItemFormFieldset hook also to simple form
 */
?>
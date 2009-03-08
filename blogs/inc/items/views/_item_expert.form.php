<?php
/**
 * This file implements the Post form.
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


global $mode, $admin_url;
global $post_comment_status, $trackback_url, $item_tags;
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
	$ItemTypeCache = & get_Cache( 'ItemTypeCache' );
	$Form->select_object( 'item_typ_ID', $edited_Item->ptyp_ID, $ItemTypeCache,
								'', '', false, '', 'get_option_list_unreserved_only' );
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

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'expert' ) );

	// ---------- PREVIEW ----------
	$url = url_same_protocol( $Blog->get( 'url' ) ); // was dynurl
	$Form->button( array( 'button', '', T_('Preview'), 'PreviewButton', 'b2edit_open_preview(this.form, \''.$url.'\');' ) );

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

	echo '<tr><td class="label"><label for="post_urltitle" title="'.T_('&quot;slug&quot; to be used in permalinks').'"><strong>'.T_('URL "slug"').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'post_urltitle', $edited_Item->get('urltitle'), 40, '', '', array('maxlength'=>210, 'style'=>'width: 100%;') );
	echo '</td><td width="1"><!-- for IE7 --></td></tr>';

	echo '<tr><td class="label"><label for="titletag"><strong>'.T_('&lt;title&gt; tag').':</strong></label></td>';
	echo '<td class="input" width="97%">';
	$Form->text_input( 'titletag', $edited_Item->get('titletag'), 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
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
	?>

	<div id="itemform_post_excerpt" class="edit_fieldgroup">
		<label for="post_excerpt"><strong><?php echo T_('Excerpt') ?>:</strong>
		<span class="notes"><?php echo T_('(for XML feeds)') ?></span></label><br />
		<textarea name="post_excerpt" rows="2" cols="25" class="large" id="post_excerpt"><?php $edited_Item->disp( 'excerpt', 'formvalue' ) ?></textarea>
	</div>

	<?php

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

	cat_select( $Form );


	// ################### PROPERTIES ###################

	$Form->begin_fieldset( T_('Properties'), array( 'id' => 'itemform_extra' ) );

	$Form->checkbox_basic_input( 'item_featured', $edited_Item->featured, '<strong>'.T_('Featured post').'</strong>' );

	if( $current_User->check_perm( 'edit_timestamp' ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
		issue_date_control( $Form, true );
		echo '</div>';
	}

	echo '<table>';

	echo '<tr><td><strong>'.T_('Order').':</strong></td><td>';
	$Form->text( 'item_order', $edited_Item->order, 10, '', T_('can be decimal') );
	echo '</td></tr>';

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



/*
 * $Log$
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
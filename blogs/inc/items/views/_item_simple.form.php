<?php
/**
 * This file implements the SIMPLE Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _item_simple.form.php 7740 2014-12-03 12:12:05Z yura $
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

global $mode;
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

$iframe_name = NULL;
$params = array();
if( !empty( $bozo_start_modified ) )
{
	$params['bozo_start_modified'] = true;
}
$Form->begin_form( '', '', $params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	if( isset( $mode ) )   $Form->hidden( 'mode', $mode ); // used by bookmarklet
	if( isset( $edited_Item ) )
	{
		// Item ID
		$Form->hidden( 'post_ID', $edited_Item->ID );

		// Here we add js code for attaching file popup window: (Yury)
		if( !empty( $edited_Item->ID ) && ( $Session->get('create_edit_attachment') === true ) )
		{ // item also created => we have $edited_Item->ID for popup window:
			echo_attaching_files_button_js( $iframe_name );
			// clear session variable:
			$Session->delete('create_edit_attachment');
		}
	}
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
	$Form->hidden( 'metadesc', $edited_Item->get_setting( 'post_metadesc' ) );
	$Form->hidden( 'custom_headers', $edited_Item->get_setting( 'post_custom_headers' ) );


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
	$Form->hidden( 'item_hideteaser', $edited_Item->get_setting( 'hide_teaser' ) );
	$Form->hidden( 'expiry_delay', $edited_Item->get_setting( 'post_expiry_delay' ) );
	$Form->hidden( 'item_order', $edited_Item->order );

	$creator_User = $edited_Item->get_creator_User();
	$Form->hidden( 'item_owner_login', $creator_User->login );
	$Form->hidden( 'item_owner_login_displayed', 1 );

	if( $Blog->get_setting( 'show_location_coordinates' ) )
	{
		$Form->hidden( 'item_latitude', $edited_Item->get_setting( 'latitude' ) );
		$Form->hidden( 'item_longitude', $edited_Item->get_setting( 'longitude' ) );
		$Form->hidden( 'google_map_zoom', $edited_Item->get_setting( 'map_zoom' ) );
		$Form->hidden( 'google_map_type', $edited_Item->get_setting( 'map_type' ) );
	}

	// CUSTOM FIELDS
	display_hidden_custom_fields( $Form, $edited_Item );

	// Location
	$Form->hidden( 'item_ctry_ID', $edited_Item->ctry_ID );
	$Form->hidden( 'item_rgn_ID', $edited_Item->rgn_ID );
	$Form->hidden( 'item_subrg_ID', $edited_Item->subrg_ID );
	$Form->hidden( 'item_city_ID', $edited_Item->city_ID );

	// TODO: Form::hidden() do not add, if NULL?!

?>

<div class="row">

<div class="left_col col-md-9">

	<?php
	// ############################ POST CONTENTS #############################

	$Form->begin_fieldset( T_('Post contents').get_manual_link('post_contents_fieldset') );

	// Title input:
	$require_title = $Blog->get_setting('require_title');
	if( $require_title != 'none' )
	{
		$Form->switch_layout( 'none' );

		echo '<table cellspacing="0" class="compose_layout"><tr>';
		echo '<td class"label"><strong>'.T_('Title').':</strong></td>';
		echo '<td class="input">';
		$Form->text_input( 'post_title', $item_title, 20, '', '', array('maxlength'=>255, 'style'=>'width: 100%;', 'required'=>($require_title=='required')) );
		echo '</td></tr></table>';

		$Form->switch_layout( NULL );
	}

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array(
			'target_type' => 'Item',
			'edit_layout' => 'simple',
			'Item' => $edited_Item,
		) );
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

	echo_publish_buttons( $Form, $creating, $edited_Item );

	echo '</div>';

	$Form->end_fieldset();


	// ####################### ATTACHMENTS/LINKS #########################
	if( isset($GLOBALS['files_Module']) && ( !$creating ||
		( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item )
		&& $current_User->check_perm( 'files', 'view', false ) ) ) )
	{ // Files module is enabled, but in case of creating new posts we should show file attachments block only if user has all required permissions to attach files
		load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
		$LinkOwner = new LinkItem( $edited_Item );
		attachment_iframe( $Form, $LinkOwner, $iframe_name, $creating );
	}
	// ############################ ADVANCED #############################

	$Form->begin_fieldset( T_('Meta info').get_manual_link('post_simple_meta_fieldset'), array( 'id' => 'itemform_adv_props' ) );

	$Form->switch_layout( 'linespan' );

	if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{ // ------------------------------------ TIME STAMP -------------------------------------
		echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
		issue_date_control( $Form, false );
		echo '</div>';
	}

	echo '<table cellspacing="0" class="compose_layout">';
	echo '<tr><td class="label"><label for="item_tags">'.T_('Tags').':</strong></label></td>';
	echo '<td class="input">';
	$Form->text_input( 'item_tags', $item_tags, 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td></tr>';
	echo '</table>';

	$Form->switch_layout( NULL );

	$Form->end_fieldset();


	// ####################### PLUGIN FIELDSETS #########################

	$Plugins->trigger_event( 'AdminDisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item, 'edit_layout' => 'simple' ) );
	?>

</div>

<div class="right_col col-md-3 form-inline">

	<?php
	// ################### MODULES SPECIFIC ITEM SETTINGS ###################

	modules_call_method( 'display_item_settings', array( 'Form' => & $Form, 'Blog' => & $Blog, 'edited_Item' => & $edited_Item ) );

	// ################### CATEGORIES ###################

	cat_select( $Form );


	// ################### VISIBILITY / SHARING ###################

	$Form->begin_fieldset( T_('Visibility / Sharing'), array( 'id' => 'itemform_visibility' ) );

	$Form->switch_layout( 'linespan' );
	visibility_select( $Form, $edited_Item->status );
	$Form->switch_layout( NULL );

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

	?>

</div>

<div class="clear"></div>

</div>

<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();


// ####################### JS BEHAVIORS #########################
echo_publishnowbutton_js();
echo_set_is_attachments();
echo_link_files_js();
// New category input box:
echo_onchange_newcat();
echo_autocomplete_tags( $edited_Item->get_tags() );

// require dirname(__FILE__).'/inc/_item_form_behaviors.inc.php';

?>
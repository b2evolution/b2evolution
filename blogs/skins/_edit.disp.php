<?php
/**
 * This is the template that displays the edit item form. It gets POSTed to /htsrv/item_edit.php.
 *
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evoskins
 *
 * @version $Id: _edit.disp.php 6479 2014-04-16 07:18:54Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $Session, $inc_path;
global $action, $form_action;

/**
 * @var User
 */
global $current_User;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var GeneralSettings
 */
global $Settings;

global $pagenow;

global $trackback_url;
global $bozo_start_modified, $creating;
global $edited_Item, $item_tags, $item_title, $item_content;
global $post_category, $post_extracats;
global $admin_url, $redirect_to, $form_action;


// Default params:
$params = array_merge( array(
		'disp_edit_categories' => true,
		'edit_form_params' => array(),
	), $params );

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( $form_action, 'item_checkchanges', 'post' );

$Form->switch_template_parts( $params['edit_form_params'] );

// ================================ START OF EDIT FORM ================================

$form_params = array();
$iframe_name = NULL;
if( !empty( $bozo_start_modified ) )
{
	$form_params['bozo_start_modified'] = true;
}

$Form->begin_form( 'inskin', '', $form_params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	if( isset( $edited_Item ) )
	{
		$copy_post_ID = param( 'cp', 'integer', 0 );
		if( $copy_post_ID > 0 )
		{	// Copy post
			$Form->hidden( 'post_ID', 0 );
		}
		else
		{	// Edit post
			$Form->hidden( 'post_ID', $edited_Item->ID );
		}

		// Here we add js code for attaching file popup window: (Yury)
		if( !empty( $edited_Item->ID ) && ( $Session->get('create_edit_attachment') === true ) )
		{	// item also created => we have $edited_Item->ID for popup window:
			echo_attaching_files_button_js( $iframe_name );
			// clear session variable:
			$Session->delete('create_edit_attachment');
		}
	}
	$Form->hidden( 'redirect_to', $redirect_to );

	// In case we send this to the blog for a preview :
	$Form->hidden( 'preview', 0 );
	$Form->hidden( 'more', 1 );
	$Form->hidden( 'preview_userid', $current_User->ID );

	// Add hidden required fields or fields that were set in the init_inskin_editing() function
	$Form->hidden( 'item_typ_ID', $edited_Item->ptyp_ID );

	if( $edited_Item->get( 'urltitle' ) != '' )
	{	// post_urltitle can be defined from request param
		$Form->hidden( 'post_urltitle', $edited_Item->get( 'urltitle' ) );
	}

	if( $action != 'new' )
	{ // DO NOT ADD HIDDEN FIELDS IF THEY ARE NOT SET
		// These fields will be set only in case when switch tab from admin editing to in-skin editing
		// Fields used in "advanced" form, but not here:
		$Form->hidden( 'post_comment_status', $edited_Item->get( 'comment_status' ) );
		$Form->hidden( 'post_locale', $edited_Item->get( 'locale' ) );
		$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );
		$Form->hidden( 'post_excerpt', $edited_Item->get( 'excerpt' ) );
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
		$Form->hidden( 'item_featured', $edited_Item->featured );
		$Form->hidden( 'item_hideteaser', $edited_Item->get_setting( 'hide_teaser' ) );
		$Form->hidden( 'expiry_delay', $edited_Item->get_setting( 'post_expiry_delay' ) );
		$Form->hidden( 'item_order', $edited_Item->order );

		$creator_User = $edited_Item->get_creator_User();
		$Form->hidden( 'item_owner_login', $creator_User->login );
		$Form->hidden( 'item_owner_login_displayed', 1 );
	}
	elseif( !isset( $edited_Item->status ) )
	{
		$highest_publish_status = get_highest_publish_status( 'post', $Blog->ID, false );
		$edited_Item->set( 'status', $highest_publish_status );
	}

	$disp_edit_categories = true;
	if( ! $params['disp_edit_categories'] )
	{	// When categories are hidden, we store a cat_ID in the hidden input
		if( $edited_Item->ID > 0 )
		{	// Get cat_ID from existing Item
			$main_Chapter = $edited_Item->get_main_Chapter();
			$cat = $main_Chapter->ID;
		}
		else
		{	// Forums skin get cat_ID from $_GET
			$cat = param( 'cat', 'integer', 0 );
		}

		if( $cat > 0 )
		{	// Store a cat_ID
			$Form->hidden( 'post_category', $cat );
			$Form->hidden( 'cat', $cat );
			$disp_edit_categories = false;
		}
	}

?>


	<?php
	// ############################ POST CONTENTS #############################
	// Title input:
	$require_title = $Blog->get_setting('require_title');
	if( $require_title != 'none' )
	{
		$Form->switch_layout( 'none' );
		echo '<table width="100%"><tr>';
		$Form->labelstart = '<th width="10%">';
		$Form->labelend = '</th>';
		$Form->inputstart = '<td>';
		$Form->inputend = '</td>';
		$Form->text_input( 'post_title', $item_title, 20, T_('Title'), '', array('maxlength'=>255, 'style'=>'width: 100%;', 'required'=>($require_title=='required')) );
		echo '</tr></table>';
		$Form->switch_layout( NULL );
	}

	// --------------------------- TOOLBARS ------------------------------------
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array(
			'target_type' => 'Item',
			'edit_layout' => 'expert',
			'Item' => $edited_Item,
		) );
	echo '</div>';

	// ---------------------------- TEXTAREA -------------------------------------
	$Form->switch_layout( 'none' );
	$Form->fieldstart = '<div class="edit_area">';
	$Form->fieldend = "</div>\n";
	$Form->textarea_input( 'content', $item_content, 16, NULL, array( 'cols' => 50 , 'id' => 'itemform_post_content' ) );
	$Form->switch_layout( NULL );
	?>
	<script type="text/javascript" language="JavaScript">
		<!--
		// This is for toolbar plugins
		var b2evoCanvas = document.getElementById('itemform_post_content');
		//-->
	</script>

	<?php
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'inskin' ) );

	global $display_item_settings_is_defined;
	$display_item_settings_is_defined = false;
	modules_call_method( 'display_item_settings', array( 'Form' => & $Form, 'Blog' => & $Blog, 'edited_Item' => & $edited_Item ) );

	if( ! $display_item_settings_is_defined )
	{
		if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
		{ // ------------------------------------ TIME STAMP -------------------------------------
			$Form->begin_fieldset();

			$Form->switch_layout( 'none' );
			echo '<div id="itemform_edit_timestamp" class="edit_fieldgroup">';
			issue_date_control( $Form, false, '<strong>'.T_('Issue date').'</strong>' );
			echo '</div>';
			$Form->switch_layout( NULL );

			$Form->end_fieldset();
		}

		// ################### VISIBILITY / SHARING ###################
		// Get those statuses which are not allowed for the current User to create posts in this blog
		$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_post!', 'create' ), array( 'trash' ) );
		// Get allowed visibility statuses
		$sharing_options = get_visibility_statuses( 'radio-options', $exclude_statuses );
		if( count( $sharing_options ) == 1 )
		{ // Only one visibility status is available, don't show radio but set hidden field
			$Form->hidden( 'post_status', $sharing_options[0][0] );
		}
		else
		{ // Display visibiliy options
			$Form->begin_fieldset( T_('Visibility / Sharing'), array( 'id' => 'itemform_visibility' ) );
			$Form->switch_layout( 'linespan' );
			visibility_select( $Form, $edited_Item->status );
			$Form->switch_layout( NULL );
			$Form->end_fieldset();
		}
	}

	if( $disp_edit_categories )
	{	// Display categories
		cat_select( $Form, true, false );
		echo '<br />';
	}

	// ################### TEXT RENDERERS ###################
	$item_renderer_checkboxes = $edited_Item->get_renderer_checkboxes();
	if( !empty( $item_renderer_checkboxes ) )
	{
		$Form->begin_fieldset( T_('Text Renderers'), array( 'id' => 'itemform_renderers' ) );
		echo $item_renderer_checkboxes;
		$Form->end_fieldset();
	}

	$Form->switch_layout( 'none' );
	echo '<table cellspacing="0" width="100%" class="item_tags">';
	echo '<tr><td class="label shrinkwrap"><label for="item_tags"><strong>'.T_('Tags').':</strong></label></td>';
	echo '<td class="input">';
	$Form->text_input( 'item_tags', $item_tags, 40, '', '', array('maxlength'=>255, 'style'=>'width: 100%;') );
	echo '</td></tr>';
	echo '</table>';
	$Form->switch_layout( NULL );
?>

<div class="clear"></div>

<?php
// ################### LOCATIONS ###################
echo_item_location_form( $Form, $edited_Item );

if( $Blog->get_setting( 'show_location_coordinates' ) )
{
	$Form->hidden( 'item_latitude', $edited_Item->get_setting( 'latitude' ) );
	$Form->hidden( 'item_longitude', $edited_Item->get_setting( 'longitude' ) );
	$Form->hidden( 'google_map_zoom', $edited_Item->get_setting( 'map_zoom' ) );
	$Form->hidden( 'google_map_type', $edited_Item->get_setting( 'map_type' ) );
}

// ################### PROPERTIES ###################
$custom_fields = get_item_custom_fields();

if( count( $custom_fields ) > 0 )
{
	$Form->begin_fieldset( T_('Properties') );

	$Form->switch_layout( 'table' );
	$Form->labelstart = '<td class="right"><strong>';
	$Form->labelend = '</strong></td>';

	echo $Form->formstart;

	foreach( $custom_fields as $field )
	{	// Display each custom field
		if( $field['type'] == 'varchar' )
		{
			$field_note = '';
			$field_params = array( 'maxlength' => 255, 'style' => 'width:100%' );
		}
		else
		{	// type == double
			$field_note = T_('can be decimal');
			$field_params = array();
		}
		$Form->text_input( 'item_'.$field['name'], $edited_Item->get_setting( 'custom_'.$field['name'] ), 10, $field['title'], $field_note, $field_params );
	}

	echo $Form->formend;

	$Form->switch_layout( NULL );

	$Form->end_fieldset();
}

// ####################### ATTACHMENTS FIELDSETS #########################
$LinkOwner = new LinkItem( $edited_Item );
if( $LinkOwner->count_links() )
{
	$Form->begin_fieldset( T_('Attachments') );
	if( $current_User->check_perm( 'files', 'view' ) && $current_User->check_perm( 'admin', 'restricted' ) )
	{
		display_attachments( $LinkOwner );
	}
	else
	{
		$Form->info( '', T_('You do not have permission to edit file attachments for this post') );
	}
	$Form->end_fieldset();
}

// ####################### PLUGIN FIELDSETS #########################
$Plugins->trigger_event( 'DisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item) );
?>

<div class="clear"></div>

<div class="center margin2ex">
<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';
	echo_publish_buttons( $Form, $creating, $edited_Item, true );
	echo '</div>';
?>
</div>
<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();


// ####################### JS BEHAVIORS #########################
echo_publishnowbutton_js();
// New category input box:
echo_onchange_newcat();
echo_autocomplete_tags( $edited_Item->get_tags() );

$edited_Item->load_Blog();
// Location
echo_regional_js( 'item', $edited_Item->Blog->region_visible() );
?>
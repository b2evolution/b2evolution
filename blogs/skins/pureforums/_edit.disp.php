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
 * @subpackage pureforums
 *
 * @version $Id: _edit.disp.php 6539 2014-04-23 14:32:54Z yura $
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
global $admin_url, $redirect_to, $form_action, $advanced_edit_link;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

// Default params:
$disp_params = array_merge( array(
		'disp_edit_categories' => true,
		'edit_form_params' => array(
			'formstart'      => '<table class="forums_table topics_table" cellspacing="0" cellpadding="0"><tr class="table_title"><th colspan="2"><div class="form_title">'.( $creating ? T_('Post a new topic') : T_('Edit post') ).'</div></th></tr>',
			'formend'        => '</table>',
			'fieldset_begin' => '<tr><td colspan="2">',
			'fieldset_end'   => '</td></tr>',
			'fieldstart'     => '<tr>',
			'fieldend'       => '</tr>',
			'labelstart'     => '<td class="form_label"><strong>',
			'labelend'       => '</strong></td>',
			'inputstart'     => '<td class="form_input">',
			'inputend'       => '</td>',
			'infostart'      => '<td class="form_info">',
			'infoend'        => '</td>',
			'buttonsstart'   => '<tr><td colspan="2">',
			'buttonsend'     => '</td></tr>',
			'output'         => true
		),
		'categories_name'  => T_('Appears in'),
		'category_name'    => T_('Forum'),
		'category_main_title'  => T_('Main forum'),
		'category_extra_title' => T_('Additional forum'),
	), $params );

// BREADCRUMBS
echo '<div class="post_panel">';

$chapter_ID = 0;
if( !empty( $edited_Item->ID ) )
{	// Get ID of main chapter
	$main_Chapter = & $edited_Item->get_main_Chapter();
	$chapter_ID = $main_Chapter->ID;
}
elseif( param( 'cat', 'integer', 0 ) > 0 )
{	// Get chapter ID from request when we create a new topic
	$chapter_ID = get_param( 'cat' );
}
$Skin->display_breadcrumbs( $chapter_ID );
echo '</div><div class="clear"></div>';

$Form = new Form( $form_action, 'item_checkchanges', 'post' );

$Form->switch_template_parts( $disp_params['edit_form_params'] );

// ================================ START OF EDIT FORM ================================

$iframe_name = NULL;
if( !empty( $bozo_start_modified ) )
{
	$params['bozo_start_modified'] = true;
}

$Form->begin_form( 'inskin', '', $params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	if( isset( $edited_Item ) )
	{
		$Form->hidden( 'post_ID', $edited_Item->ID );

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
	$Form->hidden( 'preview', 1 );
	$Form->hidden( 'more', 1 );
	$Form->hidden( 'preview_userid', $current_User->ID );

	// Add hidden required fields or fields that were set in the init_inskin_editing() function
	$Form->hidden( 'item_typ_ID', $edited_Item->ptyp_ID );

	// These fields are required on preview mode
	$Form->hidden( 'titletag', $edited_Item->get( 'titletag' ) );
	$Form->hidden( 'post_excerpt', $edited_Item->get( 'excerpt' ) );
	$Form->hidden( 'metadesc', $edited_Item->get_setting( 'post_metadesc' ) );
	$Form->hidden( 'custom_headers', $edited_Item->get_setting( 'post_custom_headers' ) );

	if( $edited_Item->get( 'urltitle' ) != '' )
	{	// post_urltitle can be defined from request param
		$Form->hidden( 'post_urltitle', $edited_Item->get( 'urltitle' ) );
	}

	if( $action != 'new' )
	{	// DO NOT ADD HIDDEN FIELDS IF THEY ARE NOT SET
		// These fields will be set only in case when switch tab from admin editing to in-skin editing
		// Fields used in "advanced" form, but not here:
		$Form->hidden( 'post_locale', $edited_Item->get( 'locale' ) );
		$Form->hidden( 'post_url', $edited_Item->get( 'url' ) );

		if( $Blog->get_setting( 'use_workflow' ) )
		{	// We want to use workflow properties for this blog:
			$Form->hidden( 'item_priority', $edited_Item->priority );
			$Form->hidden( 'item_assigned_user_ID', $edited_Item->assigned_user_ID );
			$Form->hidden( 'item_st_ID', $edited_Item->pst_ID );
			$Form->hidden( 'item_deadline', $edited_Item->datedeadline );
		}
		$Form->hidden( 'trackback_url', $trackback_url );
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

		display_hidden_custom_fields( $Form, $edited_Item );
	}
	else if( $edited_Item->ID == 0 )
	{	// If new item - add these hidden fields (on the edit mode the checkbox and radio buttons are used)
		$Form->hidden( 'item_featured', $edited_Item->featured );
		$Form->hidden( 'post_comment_status', $edited_Item->get( 'comment_status' ) );
		if( !isset( $edited_Item->status ) )
		{
			$highest_publish_status = get_highest_publish_status( 'post', $Blog->ID, false );
			$edited_Item->set( 'status', $highest_publish_status );
		}
	}

	$disp_edit_categories = true;
	if( ! $disp_params['disp_edit_categories'] )
	{	// When categories are hidden, we store a cat_ID in the hidden input
		if( $edited_Item->ID > 0 )
		{	// Get cat_ID from existing Item
			$cat = $edited_Item->get_main_Chapter()->ID;
		}
		else
		{	// Forums skin get cat_ID from $_GET
			$cat = param( 'cat', 'integer', 0 );
		}

		if( $cat > 0 && $edited_Item->ID == 0 )
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
		$Form->text_input( 'post_title', $item_title, 20, T_('Subject'), '', array('maxlength'=>255, 'style'=>'width: 98%;', 'required'=>($require_title=='required')) );
	}

	// --------------------------- TOOLBARS ------------------------------------
	ob_start();
	echo '<div class="edit_toolbars">';
	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminDisplayToolbar', array(
			'target_type' => 'Item',
			'edit_layout' => 'expert',
			'Item' => $edited_Item,
		) );
	echo '</div>';
	$plugins_toolbar = ob_get_clean();
	
	// CALL PLUGINS NOW:
	ob_start();
	$Plugins->trigger_event( 'DisplayEditorButton', array( 'target_type' => 'Item', 'edit_layout' => 'inskin' ) );
	$plugins_editor_button = ob_get_clean();

	$Form->switch_template_parts( array(
			'inputstart' => '<td class="form_input">'.$plugins_toolbar,
			'inputend' => $plugins_editor_button.'</td>',
		) );
	// ---------------------------- TEXTAREA -------------------------------------
	$Form->textarea_input( 'content', $item_content, 16, T_('Message body'), array( 'cols' => 60 , 'id' => 'itemform_post_content', 'style' => 'width:100%' ) );
	?>
	<script type="text/javascript" language="JavaScript">
		<!--
		// This is for toolbar plugins
		var b2evoCanvas = document.getElementById('itemform_post_content');
		//-->
	</script>

	<?php
	$Form->switch_template_parts( $disp_params['edit_form_params'] );

$Form->end_fieldset();

	if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{	// ------------------------------------ TIME STAMP -------------------------------------
		echo $Form->fieldstart;

		$Form->switch_template_parts( array(
			'fieldstart' => '',
			'fieldend'   => '',
			'labelstart' => '',
			'labelend'   => '',
			'labelempty' => '',
			'inputstart' => '',
			'inputend'   => '',
			'output'     => false
		) );
		$note = $Form->date( 'item_issue_date', $edited_Item->get('issue_date'), '' ).' ';
		$note .= $Form->time( 'item_issue_time', $edited_Item->get('issue_date'), '', 'hh:mm:ss', '' );

		$Form->switch_template_parts( $disp_params['edit_form_params'] );
		$values = array(
			array( 'value' => '0', 'label' => T_('Update to NOW') ),
			array( 'value' => '1', 'label' => T_('Set to') ),
		);
		$Form->radio_input( 'item_dateset', $edited_Item->dateset, $values, T_('Issue date'), array( 'note' => $note ) );
		echo $Form->fieldend;

		// Autoselect "change date" is the date is changed.
		?>
		<script>
		jQuery( function()
				{
					jQuery('#item_issue_date, #item_issue_time').change(function()
					{
						jQuery('#item_dateset_radio_2').attr("checked", "checked")
					})
				}
			)
		</script>
		<?php
	}

	modules_call_method( 'display_item_settings', array( 'Form' => & $Form, 'Blog' => & $Blog, 'edited_Item' => & $edited_Item ) );

	if( $disp_edit_categories )
	{	// Display categories
		$Form->switch_template_parts( array(
				'fieldset_begin' => '<tr><td class="left" valign="top"><strong>$fieldset_title$:</strong></td><td>',
			) );
		cat_select( $Form, true, false, $disp_params );
		$Form->switch_template_parts( $disp_params['edit_form_params'] );
	}

	if( $edited_Item->ID > 0 )
	{	// If item is editing
		$Form->checkbox_input( 'item_featured', $edited_Item->featured, T_('Sticky'), array( 'note' => T_('Make this topic sticky (featured at the top of the list)') ) );

		if( ( $Blog->get_setting( 'allow_comments' ) != 'never' ) && ( $Blog->get_setting( 'disable_comments_bypost' ) ) )
		{	// Display radio inputs to change the comments status
			$Form->radio_input( 'post_comment_status', $edited_Item->get( 'comment_status' ), array(
					array( 'value' => 'open', 'label' => T_('Open'), 'note' => T_('Visitors can leave comments on this post.') ),
					array( 'value' => 'closed', 'label' => T_('Closed'), 'note' => T_('Visitors can NOT leave comments on this post.') ),
					array( 'value' => 'disabled', 'label' => T_('Disabled'), 'note' => T_('Visitors cannot see nor leave comments on this post.') ),
				), T_('Allow replies'), array( 'lines' => true ) );
		}
		else
		{
			$Form->hidden( 'post_comment_status', $edited_Item->get( 'comment_status' ) );
		}
	}

	// ################### VISIBILITY / SHARING ###################
	$Form->switch_template_parts( array(
			'inputstart'     => '<td class="form_input" id="itemform_visibility">',
		) );
	visibility_select( $Form, $edited_Item->status, false, array(), T_('Visibility / Sharing') );
	$Form->switch_template_parts( $disp_params['edit_form_params'] );

	// ################### TEXT RENDERERS ###################
	$item_renderer_checkboxes = $edited_Item->get_renderer_checkboxes();
	if( !empty( $item_renderer_checkboxes ) )
	{
		$Form->switch_template_parts( array(
				'fieldset_begin' => '<tr><td class="left" valign="top"><strong>$fieldset_title$:</strong></td><td class="form_input">',
			) );
		$Form->begin_fieldset( T_('Text Renderers'), array( 'id' => 'itemform_renderers' ) );
		echo $item_renderer_checkboxes;
		$Form->end_fieldset();
		$Form->switch_template_parts( $disp_params['edit_form_params'] );
	}

	// ################### TAGS ###################
	$Form->text_input( 'item_tags', $item_tags, 40, T_('Tags'), '', array('maxlength'=>255, 'style'=>'width:100%;') );

// ####################### ATTACHMENTS FIELDSETS #########################
$LinkOwner = new LinkItem( $edited_Item );
if( $LinkOwner->count_links() )
{
	$Form->switch_template_parts( array(
			'fieldset_begin' => '<tr><td class="form_label" valign="top"><strong>$fieldset_title$:</strong></td><td class="form_input">',
		) );
	$Form->begin_fieldset( T_('Attachments') );
	if( $current_User->check_perm( 'files', 'view' ) && $current_User->check_perm( 'admin', 'restricted' ) )
	{
		display_attachments( $LinkOwner );
	}
	else
	{
		echo T_('You do not have permission to edit file attachments for this post');
	}
	$Form->end_fieldset();
	$Form->switch_template_parts( $disp_params['edit_form_params'] );
}

// ####################### PLUGIN FIELDSETS #########################
$Form->switch_template_parts( array(
	'fieldset_begin' => '<tr><td colspan="2" class="left row2">',
	'fieldend'       => '</tr><tr><td colspan="2">',
	) );
$Plugins->trigger_event( 'DisplayItemFormFieldset', array( 'Form' => & $Form, 'Item' => & $edited_Item ) );
$Form->switch_template_parts( $disp_params['edit_form_params'] );

$Form->begin_fieldset();
	// ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions center">';
	echo_publish_buttons( $Form, $creating, $edited_Item, true, true );
	echo '</div>';
$Form->end_fieldset();
// ================================== END OF EDIT FORM ==================================
$Form->end_form();

// ####################### JS BEHAVIORS #########################
echo_publishnowbutton_js();
// New category input box:
echo_onchange_newcat();
echo_autocomplete_tags( $edited_Item->get_tags() );
?>
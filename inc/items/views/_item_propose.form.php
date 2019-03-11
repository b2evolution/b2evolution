<?php
/**
 * This file implements the Post form to propose a change.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Item
 */
global $edited_Item;
/**
 * @var Blog
 */
global $Collection, $Blog;
/**
 * @var Plugins
 */
global $Plugins;

global $admin_url, $bozo_start_modified, $item_title, $item_content, $redirect_to;

// Used to mark the required fields (in non-standard template)
$required_star = '<span class="label_field_required">*</span>';

$Form = new Form( NULL, 'item_checkchanges', 'post' );
$Form->labelstart = '<strong>';
$Form->labelend = "</strong>\n";


// ================================ START OF PROPOSE CHANGE FORM ================================

$params = array();
if( !empty( $bozo_start_modified ) )
{
	$params['bozo_start_modified'] = true;
}

$Form->begin_form( '', '', $params );

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $Blog->ID );
	$Form->hidden( 'post_ID', $edited_Item->ID );

	// Try to get the original item ID (For example, on copy action):
	$original_item_ID = get_param( 'p' );
	if( ! empty( $original_item_ID ) )
	{
		$Form->hidden( 'p', $original_item_ID );
	}

	$Form->hidden( 'redirect_to', $redirect_to );
?>
<div class="row">

<div class="col-lg-12">
	<?php
	// ############################ POST CONTENTS #############################

	if( $edited_Item->ID > 0 )
	{	// Set form title for editing the item:
		$form_title_item_ID = T_('Propose change for Item').' <a href="'.$admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$edited_Item->ID.'" class="post_type_link">#'.$edited_Item->ID.'</a>';
	}
	$Form->begin_fieldset( $form_title_item_ID.get_manual_link( 'post-contents-panel' ), array( 'id' => 'itemform_content' ) );

	$Form->switch_layout( 'fields_table' );

	$Form->begin_fieldset( '', array( 'class' => 'evo_fields_table__single_row' ) );
	if( $edited_Item->get_type_setting( 'use_title' ) != 'never' )
	{	// Display a post title field:
		$Form->text_input( 'post_title', $item_title, 20, T_('Title'), '', array( 'maxlength' => 255, 'required' => ( $edited_Item->get_type_setting( 'use_title' ) == 'required' ) ) );
	}
	else
	{	// Hide a post title field:
		$Form->hidden( 'post_title', $item_title );
	}
	$Form->end_fieldset();

	$Form->switch_layout( NULL );

	if( $edited_Item->get_type_setting( 'use_text' ) != 'never' )
	{ // Display text
		// --------------------------- TOOLBARS ------------------------------------
		echo '<div class="edit_toolbars">';
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'AdminDisplayToolbar', array(
				'edit_layout' => 'expert',
				'Item' => $edited_Item,
			) );
		echo '</div>';

		// ---------------------------- TEXTAREA -------------------------------------
		$Form->fieldstart = '<div class="edit_area">';
		$Form->fieldend = "</div>\n";
		$Form->textarea_input( 'content', $item_content, 16, '', array( 'cols' => 40 , 'id' => 'itemform_post_content', 'class' => 'autocomplete_usernames' ) );
		?>
		<script type="text/javascript" language="JavaScript">
			<!--
			// This is for toolbar plugins
			var b2evoCanvas = document.getElementById('itemform_post_content');
			// -->
		</script>

	<?php
	}
	else
	{ // Hide text
		$Form->hidden( 'content', $item_content );
	}

	// ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';

	echo '<div class="pull-left">';
	// CALL PLUGINS NOW:
	ob_start();
	$Plugins->trigger_event( 'AdminDisplayEditorButton', array(
			'target_type'   => 'Item',
			'target_object' => $edited_Item,
			'content_id'    => 'itemform_post_content',
			'edit_layout'   => 'expert',
		) );
	$plugin_button = ob_get_flush();
	if( empty( $plugin_button ) )
	{	// If button is not displayed by any plugin
		// Display a current status of HTML allowing for the edited item:
		echo '<span class="html_status">';
		if( $edited_Item->get_type_setting( 'allow_html' ) )
		{
			echo T_('HTML is allowed');
		}
		else
		{
			echo T_('HTML is not allowed');
		}
		// Display manual link for more info:
		echo get_manual_link( 'post-allow-html' );
		echo '</span>';
	}
	echo '</div>';

	echo '<div class="pull-right">';
	echo_publish_buttons( $Form, false, $edited_Item, false, false, $action );
	echo '</div>';

	echo '<div class="clearfix"></div>';

	echo '</div>';

	$Form->end_fieldset();

	// ############################ CUSTOM FIELDS #############################
	$custom_fields = $edited_Item->get_type_custom_fields();
	if( count( $custom_fields ) )
	{	// Display fieldset with custom fields only if at least one exists:
		$custom_fields_title = T_('Custom fields').get_manual_link( 'post-custom-fields-panel' );
		if( $current_User->check_perm( 'options', 'edit' ) )
		{	// Display an icon to edit post type if current user has a permission:
			$custom_fields_title .= '<span class="floatright panel_heading_action_icons">'
					.action_icon( T_('Edit fields...'), 'edit',
						$admin_url.'?ctrl=itemtypes&amp;action=edit&amp;ityp_ID='.$edited_Item->get( 'ityp_ID' ).'#fieldset_wrapper_custom_fields',
						T_('Edit fields...'), 3, 4, array( 'class' => 'action_icon btn btn-default btn-sm' ) )
				.'</span>';
		}

		$Form->begin_fieldset( $custom_fields_title, array( 'id' => 'itemform_custom_fields', 'fold' => true ) );

		$Form->switch_layout( 'fields_table' );
		$Form->begin_fieldset();

		// Display inputs to edit custom fields:
		display_editable_custom_fields( $Form, $edited_Item, true );

		$Form->end_fieldset();
		$Form->switch_layout( NULL );

		$Form->end_fieldset();
	}
	?>

</div>

</div>

<?php
// ================================== END OF PROPOSE CHANGE FORM ==================================
$Form->end_form();

// JS code for status dropdown select button
echo_status_dropdown_button_js( 'post' );
// Save and restore item content field height and scroll position:
echo_item_content_position_js( get_param( 'content_height' ), get_param( 'content_scroll' ) );
// Fieldset folding
echo_fieldset_folding_js();
?>
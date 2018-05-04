<?php
/**
 * This file implements the Post form to propose a change.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
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

	$Form->switch_layout( 'none' );

	echo '<table cellspacing="0" class="compose_layout" align="center"><tr>';
	$display_title_field = $edited_Item->get_type_setting( 'use_title' ) != 'never';
	if( $display_title_field )
	{ // Display title
		$field_required = ( $edited_Item->get_type_setting( 'use_title' ) == 'required' ) ? $required_star : '';
		echo '<td class="label">'.$field_required.'<strong>'.T_('Title').':</strong></td>';
		echo '<td width="100%" class="input">';
		$Form->text_input( 'post_title', $item_title, 20, '', '', array( 'maxlength' => 255, 'style' => 'width: 100%;' ) );
		echo '</td>';
	}
	else
	{ // Hide title
		$Form->hidden( 'post_title', $item_title );
	}
	echo '</tr></table>';

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

	if( ! $edited_Item->get_type_setting( 'use_custom_fields' ) )
	{	// All CUSTOM FIELDS are hidden by post type:
		display_hidden_custom_fields( $Form, $edited_Item );
	}
	else
	{	// CUSTOM FIELDS:
		$custom_fields = $edited_Item->get_type_custom_fields( 'all', true );

		if( count( $custom_fields ) )
		{	// Display fieldset with custom fields only if at least one exists:
			$Form->begin_fieldset( T_('Custom fields').get_manual_link( 'post-custom-fields-panel' ), array( 'id' => 'itemform_custom_fields', 'fold' => true ) );

			echo '<table cellspacing="0" class="compose_layout">';

			foreach( $custom_fields as $custom_field )
			{	// Loop through custom fields:
				echo '<tr><td class="label"><label for="item_'.$custom_field['type'].'_'.$custom_field['ID'].'"><strong>'.$custom_field['label'].':</strong></label></td>';
				echo '<td class="input" width="97%">';
				switch( $custom_field['type'] )
				{
					case 'double':
						$Form->text( 'item_double_'.$custom_field['ID'], $edited_Item->get_custom_field_value( $custom_field['name'], false, false ), 10, '', $custom_field['note'].' <code>'.$custom_field['name'].'</code>' );
						break;
					case 'varchar':
						$Form->text_input( 'item_varchar_'.$custom_field['ID'], $edited_Item->get_custom_field_value( $custom_field['name'], false, false ), 20, '', '<br />'.$custom_field['note'].' <code>'.$custom_field['name'].'</code>', array( 'maxlength' => 255, 'style' => 'width: 100%;' ) );
						break;
					case 'text':
						$Form->textarea_input( 'item_text_'.$custom_field['ID'], $edited_Item->get_custom_field_value( $custom_field['name'], false, false ), 5, '', array( 'note' => $custom_field['note'].' <code>'.$custom_field['name'].'</code>' ) );
						break;
					case 'html':
						$Form->textarea_input( 'item_html_'.$custom_field['ID'], $edited_Item->get_custom_field_value( $custom_field['name'], false, false ), 5, '', array( 'note' => $custom_field['note'].' <code>'.$custom_field['name'].'</code>' ) );
						break;
					case 'url':
						$Form->text_input( 'item_url_'.$custom_field['ID'], $edited_Item->get_custom_field_value( $custom_field['name'], false, false ), 20, '', '<br />'.$custom_field['note'].' <code>'.$custom_field['name'].'</code>', array( 'maxlength' => 255, 'style' => 'width: 100%;' ) );
						break;
				}
				echo '</td></tr>';
			}

			echo '</table>';

			$Form->end_fieldset();
		}
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
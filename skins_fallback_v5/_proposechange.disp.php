<?php
/**
 * This file implements the Post form to propose a change.
 *
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Collection, $Blog, $Plugins;
global $edited_Item, $item_title, $item_content;
global $bozo_start_modified, $form_action, $redirect_to;

// Default params:
$params = array_merge( array(
		'edit_form_params' => array(),
	), $params );

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
	$Form->hidden( 'post_ID', $edited_Item->ID );
	$Form->hidden( 'redirect_to', $redirect_to );

	$Form->begin_fieldset( get_request_title( $params ) );

	// ############################ POST CONTENTS #############################
	// Title input:
	$use_title = $edited_Item->get_type_setting( 'use_title' );
	if( $use_title != 'never' )
	{
		$Form->switch_layout( 'fields_table' );
		$Form->begin_fieldset();
		$Form->text_input( 'post_title', $item_title, 20, T_('Title'), '', array( 'maxlength' => 255, 'style' => 'width: 100%;', 'required' => ( $use_title == 'required' ) ) );
		$Form->end_fieldset();
		$Form->switch_layout( NULL );
	}

	if( $edited_Item->get_type_setting( 'use_text' ) != 'never' )
	{ // Display text
		// --------------------------- TOOLBARS ------------------------------------
		echo '<div class="edit_toolbars">';
		// CALL PLUGINS NOW:
		$admin_toolbar_params = array(
				'edit_layout' => 'expert',
				'Item' => $edited_Item,
			);
		$Plugins->trigger_event( 'AdminDisplayToolbar', $admin_toolbar_params );
		echo '</div>';

		// ---------------------------- TEXTAREA -------------------------------------
		$Form->switch_layout( 'none' );
		$Form->fieldstart = '<div class="edit_area">';
		$Form->fieldend = "</div>\n";
		$Form->textarea_input( 'content', $item_content, 16, NULL, array(
				'cols' => 50 ,
				'id' => 'itemform_post_content',
				'class' => 'autocomplete_usernames link_attachment_dropzone'
			) );
		$Form->switch_layout( NULL );
		?>
		<script>
			<!--
			// This is for toolbar plugins
			var b2evoCanvas = document.getElementById('itemform_post_content');
			//-->
		</script>

		<?php
		echo '<div class="edit_plugin_actions">';
		// CALL PLUGINS NOW:
		$display_editor_params = array(
				'target_type'   => 'Item',
				'target_object' => $edited_Item,
				'content_id'    => 'itemform_post_content',
				'edit_layout'   => 'inskin',
			);
		$Plugins->trigger_event( 'DisplayEditorButton', $display_editor_params );
		echo '</div>';
	}
	else
	{ // Hide text
		$Form->hidden( 'content', $item_content );
	}

	$Form->end_fieldset();

	// ################### CUSTOM FIELDS ###################
	$custom_fields = $edited_Item->get_type_custom_fields();
	if( count( $custom_fields ) > 0 )
	{
		$Form->begin_fieldset( T_('Additional fields'), array( 'id' => 'itemform_custom_fields' ) );

		// Display inputs to edit custom fields:
		display_editable_custom_fields( $Form, $edited_Item );

		$Form->end_fieldset();
	}
?>

<div class="clear"></div>

<div class="center margin2ex">
<?php // ------------------------------- ACTIONS ----------------------------------
	echo '<div class="edit_actions">';
	$Form->submit( array( 'actionArray[save_propose]', T_('Propose change'), 'btn-primary evo_propose_change_btn' ) );
	echo '</div>';
?>
</div>
<?php
// ================================== END OF EDIT FORM ==================================
$Form->end_form();
?>
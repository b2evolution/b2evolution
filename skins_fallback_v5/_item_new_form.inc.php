<?php
/**
 * This is the template that displays the item/post form for anonymous user
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Blog, $Settings, $dummy_fields;

if( is_logged_in() )
{	// This form is allowed only for anonymous users, Exit here:
	return;
}

$params = array_merge( array(
		'item_new_warning_msg'  => T_( 'In order to start a new post' ),
		'form_params'           => array(), // Use to change a structre of form, i.e. fieldstart, fieldend and etc.
		'item_new_form_start'   => '<h3>'.sprintf( T_('New [%s]'), $Blog->get_default_item_type_name() ).'</h3>',
		'item_new_form_end'     => '',
		'item_new_submit_text'  => T_('Create post'),
	), $params );


if( $Blog->get_setting( 'post_anonymous' ) )
{	// Display a form if it is allowed by collection setting:

	$edited_Item = get_session_Item( 0, true );

	echo $params['item_new_form_start'];

	$Form = new Form( get_htsrv_url().'action.php' );

	$Form->switch_template_parts( $params['form_params'] );

	$Form->begin_form();

	$Form->hidden( 'mname', 'collections' );
	$Form->add_crumb( 'collections_create_post' );
	$Form->hidden( 'cat', get_param( 'cat' ) );

	$Form->text_input( $dummy_fields['name'], ( isset( $edited_Item->temp_user_name ) ? $edited_Item->temp_user_name : '' ), 40, T_('Name'), '', array( 'maxlength' => 100, 'required' => true ) );

	$Form->text_input( $dummy_fields['email'], ( isset( $edited_Item->temp_user_email ) ? $edited_Item->temp_user_email : '' ), 40, T_('Email'), '<br />'.T_('Your email address will <strong>not</strong> be revealed on this site.'), array( 'maxlength' => 255, 'required' => true ) );

	// Title input:
	$use_title = $edited_Item->get_type_setting( 'use_title' );
	if( $use_title != 'never' )
	{
		$Form->switch_layout( 'none' );
		echo '<table width="100%" class="compose_layout"><tr>';
		$Form->labelstart = '<th width="1%" class="label">';
		$Form->labelend = '</th>';
		$Form->inputstart = '<td>';
		$Form->inputend = '</td>';
		$Form->text_input( 'post_title', $edited_Item->get( 'title' ), 20, T_('Title'), '', array( 'maxlength' => 255, 'style' => 'width: 100%;', 'required' => ( $use_title == 'required' ) ) );
		echo '</tr></table>';
		$Form->switch_layout( NULL );
	}

	if( $edited_Item->get_type_setting( 'use_text' ) != 'never' )
	{	// Display textarea for a post text:
		// --------------------------- TOOLBARS ------------------------------------
		echo '<div class="edit_toolbars">';
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'AdminDisplayToolbar', array(
				'edit_layout' => 'expert',
				'Item' => $edited_Item,
			) );
		echo '</div>';

		// ---------------------------- TEXTAREA -------------------------------------
		$Form->switch_layout( 'none' );
		$Form->fieldstart = '<div class="edit_area">';
		$Form->fieldend = "</div>\n";
		$Form->textarea_input( 'content', $edited_Item->get( 'content' ), 16, NULL, array(
				'cols'  => 50 ,
				'id'    => 'itemform_post_content',
				'class' => 'autocomplete_usernames'
			) );
		$Form->switch_layout( NULL );
		?>
		<script type="text/javascript" language="JavaScript">
			<!--
			// This is for toolbar plugins
			var b2evoCanvas = document.getElementById('itemform_post_content');
			//-->
		</script>

		<?php
		echo '<div class="edit_plugin_actions">';
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'DisplayEditorButton', array(
				'target_type'   => 'Item',
				'target_object' => $edited_Item,
				'content_id'    => 'itemform_post_content',
				'edit_layout'   => 'inskin'
			) );
		echo '</div>';

		// set b2evoCanvas for plugins
		echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "'.$dummy_fields['content'].'" );</script>';

		// Display renderers:
		$item_renderer_checkboxes = ( $Blog->get_setting( 'in_skin_editing_renderers' ) ? $edited_Item->get_renderer_checkboxes() : false );
		if( ! empty( $item_renderer_checkboxes ) )
		{
			$Form->info( T_('Text Renderers'), $item_renderer_checkboxes );
		}
	}

	// Display additional fieldsets from active plugins:
	$Plugins->trigger_event( 'DisplayItemFormFieldset', array(
			'Form'              => & $Form,
			'Item'              => & $edited_Item,
			'form_use_fieldset' => false,
		) );

	$Form->end_form( array(
			array( 'name' => 'actionArray[create_post]', 'class' => 'submit SaveButton', 'value' => $params['item_new_submit_text'] ),
		) );

	echo $params['item_new_form_end'];
}
else
{	// Display a warning to log in or register before new post creating:
	$register_link = '';
	$login_link = '<a class="btn btn-primary btn-sm" href="'.get_login_url( 'cannot post' ).'">'.T_( 'Log in now!' ).'</a>';
	if( ( $Settings->get( 'newusers_canregister' ) == 'yes' ) && ( $Settings->get( 'registration_is_public' ) ) )
	{
		$register_link = '<a class="btn btn-primary btn-sm" href="'.get_user_register_url( NULL, 'reg to post' ).'">'.T_( 'Register now!' ).'</a>';
	}
	echo '<p class="alert alert-warning alert-item-new">';
	echo $params['item_new_warning_msg'].' '.$login_link.( ! empty( $register_link ) ? ' '.T_('or').' '.$register_link : '' );
	echo '</p>';
}
?>
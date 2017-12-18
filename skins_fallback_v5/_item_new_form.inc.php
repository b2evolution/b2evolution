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


if( $Blog->get_setting( 'post_anonymous' ) )
{	// Display a form if it is allowed by collection setting:

	$edited_Item = new Item();

	$Form = new Form( get_htsrv_url().'item_edit.php' );

	$Form->begin_form();

	$Form->text( $dummy_fields['name'], '', 40, T_('Name'), '', 100 );

	$Form->text( $dummy_fields['email'], '', 40, T_('Email'), '<br />'.T_('Your email address will <strong>not</strong> be revealed on this site.'), 255 );

	if( $edited_Item->get_type_setting( 'use_text' ) != 'never' )
	{	// Display text
		// ---------------------------- TEXTAREA -------------------------------------
		$Form->switch_layout( 'none' );
		$Form->fieldstart = '<div class="edit_area">';
		$Form->fieldend = "</div>\n";
		$Form->textarea_input( 'content', '', 16, NULL, array(
				'cols' => 50 ,
				'id' => 'itemform_post_content',
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
	}
	else
	{ // Hide text
		$Form->hidden( 'content', $item_content );
	}

	// set b2evoCanvas for plugins
	echo '<script type="text/javascript">var b2evoCanvas = document.getElementById( "'.$dummy_fields[ 'content' ].'" );</script>';

	$Form->end_form();
}
else
{	// Display a warning to log in or register before new post creating:
	$register_link = '';
	$login_link = '<a class="btn btn-primary btn-sm" href="'.get_login_url( 'cannot post' ).'">'.T_( 'Log in now!' ).'</a>';
	if( ( $Settings->get( 'newusers_canregister' ) == 'yes' ) && ( $Settings->get( 'registration_is_public' ) ) )
	{
		$register_link = '<a class="btn btn-primary btn-sm" href="'.get_user_register_url( NULL, 'reg to post' ).'">'.T_( 'Register now!' ).'</a>';
	}
	echo '<p class="alert alert-warning">';
	echo T_( 'In order to start a new topic' ).' '.$login_link.( ! empty( $register_link ) ? ' '.T_('or').' '.$register_link : '' );
	echo '</p>';
}

?>
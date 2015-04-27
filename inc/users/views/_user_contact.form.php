<?php
/**
 * This file implements the UI view for the user contact form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $display_mode, $Blog;

/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;

$Form = new Form( $form_action, 'user_checkchanges' );

$form_class = 'fform user_contact_form';
$Form->title_fmt = '<span style="float:right">$global_icons$</span><div>$title$</div>'."\n";

$Form->begin_form( $form_class );
$Form->hidden( 'blog', $Blog->ID );
$Form->add_crumb( 'user' );
$Form->hidden( 'user_ID', $edited_User->ID );

$close_icon = '';
if( $display_mode == 'js' )
{ // Display a close link for popup window
	$close_icon = action_icon( T_('Close this window'), 'close', '', '', 0, 0, array( 'id' => 'close_button', 'class' => 'floatright' ) );
}
$Form->begin_fieldset( T_('Groups').$close_icon, array( 'class' => 'fieldset clear' ) );

$current_user_groups = get_contacts_groups_array();
$active_groups = get_contacts_groups_by_user_ID( $edited_User->ID );
$is_contact = check_contact( $edited_User->ID );

$group_options = array();
foreach( $current_user_groups as $group_ID => $group_title )
{
	$group_options[] = array( 'contact_groups[]', $group_ID, $group_title, in_array( $group_ID, $active_groups ) );
}
$group_options[] = array( 'contact_groups[]', 'new', T_('new').': <input type="text" name="contact_group_new" class="form-control" />', false, false, '', 'contact_group_new' );

$Form->checklist( $group_options, 'contact_groups', '', false, false );

$Form->checkbox( 'contact_blocked', $is_contact === false, T_('Block this contact'), T_('He/she won\'t be able to contact you.') );

$Form->end_fieldset();

$Form->end_form( array( array( 'value' => T_('Save'), 'name' => 'actionArray[contact_group_save]' ) ) );

?>
<?php
/**
 * This file implements the UI view for the invitation code properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var Invitation
 */
global $edited_Invitation;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'invitation_checkchanges', 'post', 'compact' );

if( ! $creating )
{
	$Form->global_icon( T_('Delete this invitation code!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('invitation') ) );
}
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ? T_('New invitation code') : T_('Invitation code') ).get_manual_link( 'invitation-code-form' ) );

	$Form->add_crumb( 'invitation' );

	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$GroupCache = & get_GroupCache();
	$Form->select_input_object( 'ivc_grp_ID', $edited_Invitation->grp_ID, $GroupCache, T_('Group'), array( 'required' => true ) );

	$Form->text_input( 'ivc_code', $edited_Invitation->code, 32, T_('Code'), T_('Must be from 3 to 32 letters, digits or signs "-", "_".'), array( 'required' => true ) );

	$Form->date_input( 'ivc_expire_date', date2mysql( $edited_Invitation->expire_ts ), T_('Expire date'), array( 'required' => true ) );

	$Form->time_input( 'ivc_expire_time', date2mysql( $edited_Invitation->expire_ts ), T_('Expire time'), array( 'required' => true ) );

	$Form->text_input( 'ivc_source', $edited_Invitation->source, 32, T_('Source'), '', array( 'maxlength' => 30 ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}
?>
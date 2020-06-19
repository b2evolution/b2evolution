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
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
	$Form->global_icon( TB_('Delete this invitation code!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('invitation') ) );
}
$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ? TB_('New invitation code') : TB_('Invitation code') ).get_manual_link( 'invitation-code-form' ) );

	$Form->add_crumb( 'invitation' );

	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'ivc_code', $edited_Invitation->code, 32, TB_('Invitation code'), TB_('Must be from 3 to 32 letters, digits or signs "-", "_".'), array( 'required' => true ) );

	$Form->begin_line( TB_('Expires'), 'ivc_expire_date', '', array( 'required' => true ) );

		$Form->date_input( 'ivc_expire_date', $edited_Invitation->expire_ts, '' );

		$Form->time_input( 'ivc_expire_time', $edited_Invitation->expire_ts, TB_('at') );

	$Form->end_line();

	$GroupCache = & get_GroupCache( true, '('.TB_('Default group').')' );
	$Form->select_input_object( 'ivc_grp_ID', $edited_Invitation->grp_ID, $GroupCache, TB_('Assign to Group'), array( 'allow_none' => true ) );

	$Form->text_input( 'ivc_level', $edited_Invitation->level, 32, TB_('Assign level'), '', array( 'maxlength' => 2 ) );

	$Form->text_input( 'ivc_source', $edited_Invitation->source, 32, TB_('Assign source'), '', array( 'maxlength' => 30 ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', TB_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', TB_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', TB_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', TB_('Save Changes!'), 'SaveButton' ) ) );
}
?>
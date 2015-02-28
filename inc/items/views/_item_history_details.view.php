<?php
/**
 * This file implements the Item history details view
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_Item, $Revision;

$post_statuses = get_visibility_statuses();

$Form = new Form( NULL, 'history', 'post', 'compact' );

$Form->global_icon( T_('Cancel viewing!'), 'close', regenerate_url( 'action', 'action=history' ) );

$Form->begin_form( 'fform', sprintf( T_('Revision #%s for: %s'), $Revision->iver_ID == 0 ? '('.T_('Current version').')' : $Revision->iver_ID, $edited_Item->get_title() ) );

$Form->info( T_('Date'), mysql2localedatetime( $Revision->iver_edit_datetime, 'Y-m-d', 'H:i:s' ) );

$iver_editor_user_link = get_user_identity_link( NULL, $Revision->iver_edit_user_ID );
$Form->info( T_('User'), ( empty( $iver_editor_user_link ) ? T_( '(deleted user)' ) : $iver_editor_user_link ) );

$Form->info( T_('Status'), $post_statuses[ $Revision->iver_status ] );

$Form->info( T_('Note'), $Revision->iver_ID > 0 ? T_('Archived version') : T_('Current version') );

$Form->info( T_('Title'), $Revision->iver_title );

$Form->info( T_('Content'), $Revision->iver_content );

$Form->end_form();

?>
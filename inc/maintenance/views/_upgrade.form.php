<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package maintenance
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var action
 */
global $action;

global $updates;

$Form = new Form( NULL, 'upgrade_form', 'post', 'compact' );

$Form->begin_form( 'fform', T_( 'Check for updates' ) );

if( empty( $updates ) )
{ // No new updates
	?><div class="action_messages">
		<div class="log_error" style="text-align:center;font-weight:bold"><?php echo T_( 'There are no new updates.' ); ?></div>
	</div><?php

	$Form->end_form();
}
else
{ // Display a form to download new update
	$update = $updates[0];

	$Form->info( T_( 'Update' ), $update['name'] );
	$Form->info( T_( 'Description' ), $update['description'] );
	$Form->info( T_( 'Version' ), $update['version'] );

	$Form->text_input( 'upd_url', ( get_param( 'upd_url' ) != '' ? get_param( 'upd_url' ) : $update['url'] ), 80,
		T_('URL'), '<br/><span class="note">'.T_( 'You <i>might</i> replace this with a different URL in case you want to upgrade to a custom version.' ).'</span>', array( 'maxlength' => 300, 'required' => true ) );

	$Form->add_crumb( 'upgrade_started' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );

	$Form->end_form( array( array( 'submit', 'actionArray[download]', T_( 'Continue' ), 'SaveButton' ) ) );
}

?>
<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of Updater class
 */
global $current_Updater;

/**
 * @var action
 */
global $action;

$Form = & new Form( NULL, 'backup_settings', 'post', 'compact' );

$Form->begin_form( 'fform', T_('Check for updates') );

$Form->hiddens_by_key( get_memorized( 'action' ) );

if( empty( $current_Updater->updates ) )
{
	$Form->info( T_( 'Updates' ), T_( 'There are no any new updates.' ) );

	$Form->end_form();
}
else
{
	$update = $current_Updater->updates[0];

	$Form->info( T_( 'Updates' ), T_( 'There is a new update!' ), '<br/><br/><b>Name:</b> '.$update['name'].
																'<br/><b>Description:</b> '.$update['description'].
																'<br><b>Version:</b> '.$update['version'] );

	$Form->text_input( 'upd_url', $update['url'], 80, T_('URL'), '<br/><span style="color:red">This is a test implementation. Please enter the URL of the ZIP file to download and install !</span>', array( 'maxlength'=> 100, 'required'=>true ) );

	$Form->end_form( array( array( 'submit', 'actionArray[upgrade]', T_('Upgrade'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

/*
 * $Log$
 * Revision 1.3  2009/10/18 08:16:55  efy-maxim
 * log
 *
 */

?>
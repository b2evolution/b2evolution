<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package maintenance
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _upgrade.form.php 8020 2015-01-19 08:18:22Z yura $
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
		T_('URL'), '<br/><span class="red">'.T_( 'This is a test implementation. Please enter the URL of the ZIP file to download and install!' ).'</span>', array( 'maxlength' => 300, 'required' => true ) );

	$Form->add_crumb( 'upgrade_started' );
	$Form->hiddens_by_key( get_memorized( 'action' ) );

	$Form->end_form( array( array( 'submit', 'actionArray[download]', T_( 'Continue' ), 'SaveButton' ) ) );
}

?>
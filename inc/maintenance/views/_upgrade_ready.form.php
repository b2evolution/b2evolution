<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @package maintenance
 *
 * @version $Id: _upgrade_ready.form.php 7341 2014-09-30 09:47:23Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $block_item_Widget, $action, $new_version_status;

if( isset( $block_item_Widget ) )
{
	$block_item_Widget->disp_template_replaced( 'block_end' );
}

$Form = new Form( NULL, 'upgrade_form', 'post' );

$Form->add_crumb( 'upgrade_is_launched' ); // In case we want to continue
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_form( 'fform' );

// Display the backup options to select what should be backuped
require( '_backup_options.form.php' );

// Display the form buttons
$Form->begin_fieldset( T_( 'Actions' ) );

$action_backup_value = ( $action == 'ready_svn' ) ? 'backup_and_overwrite_svn' : 'backup_and_overwrite';
$action_backup_title = ( empty( $new_version_status ) ) ? T_( 'Backup & Upgrade' ) : T_( 'Force Backup & Upgrade' );

$Form->end_form( array( array( 'submit', 'actionArray['.$action_backup_value.']', $action_backup_title, 'SaveButton' ) ) );

?>
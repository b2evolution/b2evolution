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
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _upgrade_continue.form.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $block_item_Widget, $upgrade_buttons;

if( isset( $block_item_Widget ) )
{
	$block_item_Widget->disp_template_replaced( 'block_end' );
}

if( empty( $upgrade_buttons ) )
{ // No button to continue the upgrade process
	debug_die('Unhandled upgrade action!');
}

$Form = new Form( NULL, 'upgrade_form', 'post' );

$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_form( 'fform' );

if( strpos( $action, 'ready' ) !== false )
{ // Display the backup options to select what should be backuped
	require( '_backup_options.form.php' );
}

// Display the form buttons
$Form->begin_fieldset( T_( 'Actions' ) );

$form_buttons = array();
foreach( $upgrade_buttons as $btn_action => $btn_title )
{
	$form_buttons[] = array( 'submit', 'actionArray['.$btn_action.']', $btn_title, 'SaveButton' );
}

$Form->end_form( $form_buttons );

?>
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
 * @version $Id: _upgrade_export.form.php 7341 2014-09-30 09:47:23Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $block_item_Widget, $revision_is_exported;

if( isset( $block_item_Widget ) )
{
	$block_item_Widget->disp_template_replaced( 'block_end' );
}

$Form = new Form( NULL, 'upgrade_form', 'post' );

$Form->add_crumb( 'upgrade_export' ); // In case we want to "Force Export" again
$Form->add_crumb( 'upgrade_is_ready' ); // In case we want to continue
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_form( 'fform' );

// Display the form buttons
$Form->begin_fieldset( T_( 'Actions' ) );

$form_buttons = array();
if( empty( $revision_is_exported ) )
{ // Init the buttons to continue
	$form_buttons[] = array( 'submit', 'actionArray[ready_svn]', T_( 'Continue' ), 'SaveButton' );
}
else
{ // Init the buttons to select next action
	$form_buttons[] = array( 'submit', 'actionArray[ready_svn]', T_( 'Skip Export' ), 'SaveButton' );
	$form_buttons[] = array( 'submit', 'actionArray[force_export_svn]', T_( 'Force New Export' ), 'SaveButton' );
}

$Form->end_form( $form_buttons );

?>
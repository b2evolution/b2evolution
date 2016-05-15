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
 * @package messaging
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Settings;

$Form = new Form( NULL, 'msg_settings_general' );

$Form->begin_form( 'fform', '' );

	$Form->add_crumb( 'msgsettings' );
	$Form->hidden( 'ctrl', 'msgsettings' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'tab', get_param( 'tab' ) );

$Form->begin_fieldset( T_( 'General settings' ).get_manual_link( 'messages-general-settings' ) );

	$Form->checkbox( 'allow_html_message', $Settings->get( 'allow_html_message' ),
						T_( 'Allow HTML' ), T_( 'Check to allow HTML in messages.' ).' ('.T_('HTML code will pass several sanitization filters.').')' );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>
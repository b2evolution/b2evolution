<?php
/**
 * This file implements the UI view to test the returned emails tool.
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


global $repath_test_output, $action;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );

$Form->begin_fieldset( T_('Test saved settings').get_manual_link( 'return-path-configuration' ) );

	$url = '?ctrl=email&amp;tab=return&amp;tab3=test&amp;'.url_crumb('emailsettings').'&amp;action=';
	$Form->info_field( T_('Perform tests'),
				'<a href="'.$url.'test_1">['.T_('server connection').']</a>&nbsp;&nbsp;'.
				'<a href="'.$url.'test_2">['.T_('get one returned email').']</a>&nbsp;&nbsp;'.
				'<a href="'.$url.'test_3">['.T_('Paste an error message/returned email').']</a>' );

	if( $action == 'test_3' )
	{ // Display a textarea to fill a sample error message
		$Form->textarea( 'test_error_message', param( 'test_error_message', 'raw', '' ), 15, T_('Test error message'), '', 50 );
		$Form->buttons( array( array( 'submit', 'actionArray[test_3]', T_('Test'), 'SaveButton' ) ) );
	}

	if( !empty( $repath_test_output ) )
	{
		echo '<div style="margin-top:25px"></div>';
		// Display scrollable div
		echo '<div style="padding: 6px; margin:5px; border: 1px solid #CCC; min-height: 350px">'.$repath_test_output.'</div>';
	}

$Form->end_fieldset();

$Form->end_form();

?>
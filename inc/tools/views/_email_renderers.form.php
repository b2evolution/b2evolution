<?php
/**
 * This file implements the UI view for the email renderers settings.
 *
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


/**
 * @var Plugins
 */
global $Plugins;

$Form = new Form( NULL, 'email_settings_renderers' );

$Form->begin_form( 'fform', '' );

	$Form->add_crumb( 'emailsettings' );
	$Form->hidden( 'ctrl', 'email' );
	$Form->hidden( 'action', 'settings' );
	$Form->hidden( 'tab', 'settings' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

load_funcs('plugins/_plugin.funcs.php');

$plugins_settings_content = '';
$Plugins->restart();
while( $loop_Plugin = & $Plugins->get_next() )
{
	// We use output buffers here to display the fieldset only if there's content in there
	ob_start();

	$tmp_params = array( 'for_editing' => true );
	$plugin_settings = $loop_Plugin->get_email_setting_definitions( $tmp_params );
	if( is_array( $plugin_settings ) && count( $plugin_settings ) )
	{	// Print the settings of each plugin in separate fieldset:
		$priority_link = '<a href="'.$loop_Plugin->get_edit_settings_url().'#ffield_edited_plugin_code">'.$loop_Plugin->priority.'</a>';
		$Form->begin_fieldset( $loop_Plugin->name.' '.$loop_Plugin->get_help_link('$help_url').' ('.T_('Priority').': '.$priority_link.')' );

		foreach( $plugin_settings as $l_name => $l_meta )
		{
			// Display form field for this setting:
			autoform_display_field( $l_name, $l_meta, $Form, 'EmailSettings', $loop_Plugin );
		}

		$Form->end_fieldset();
	}

	$plugins_settings_content .= ob_get_contents();

	ob_end_clean();
}

if( !empty( $plugins_settings_content ) )
{	// Display fieldset only when at least one renderer plugin exists:
	//$Form->begin_fieldset( T_( 'Renderer plugins settings' ).get_manual_link( 'messaging-plugin-settings' ), array( 'id' => 'msgplugins' ) );

	echo $plugins_settings_content;

	//$Form->end_fieldset();
}

$Form->buttons( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>
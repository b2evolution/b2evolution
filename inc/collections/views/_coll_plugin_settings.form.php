<?php
/**
 * This file implements the PLugin settings form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;

/**
 * @var Plugins
 */
global $Plugins;

global $current_User, $admin_url;

$Form = new Form( NULL, 'plugin_settings_checkchanges' );

// PluginUserSettings
load_funcs('plugins/_plugin.funcs.php');

if( $current_User->check_perm( 'options', 'edit', false ) )
{	// Display this message only if current user has permission to manage the plugins
	echo '<p class="alert alert-info">'
			.sprintf( T_('Here you can configure some plugins individually for each blog. To manage your installed plugins go <a %s>here</a>.'),
					      'href="'.$admin_url.'?ctrl=plugins"' )
		.'</p>';
}

$have_plugins = false;
$Plugins->restart();
while( $loop_Plugin = & $Plugins->get_next() )
{
	$Form->begin_form( 'fform' );

		$Form->add_crumb( 'collection' );
		$Form->hidden_ctrl();
		$Form->hidden( 'tab', 'plugin_settings' );
		$Form->hidden( 'action', 'update' );
		$Form->hidden( 'blog', $Blog->ID );

	// We use output buffers here to display the fieldset only if there's content in there
	ob_start();

	$priority_link = '<a href="'.$loop_Plugin->get_edit_settings_url().'#ffield_edited_plugin_code">'.$loop_Plugin->priority.'</a>';
	$Form->begin_fieldset( $loop_Plugin->name.' '.$loop_Plugin->get_help_link('$help_url').' ('.T_('Priority').': '.$priority_link.')' );

	ob_start();

	$plugin_settings = $loop_Plugin->get_coll_setting_definitions( $tmp_params = array( 'for_editing' => true, 'blog_ID' => $Blog->ID ) );
	if( is_array($plugin_settings) )
	{
		foreach( $plugin_settings as $l_name => $l_meta )
		{
			// Display form field for this setting:
			autoform_display_field( $l_name, $l_meta, $Form, 'CollSettings', $loop_Plugin, $Blog );
		}
	}

	$has_contents = strlen( ob_get_contents() );

	$Form->end_fieldset();

	if( $has_contents )
	{
		ob_end_flush();
		ob_end_flush();

		$have_plugins = true;
	}
	else
	{ // No content, discard output buffers:
		ob_end_clean();
		ob_end_clean();
	}
}

if( $have_plugins )
{	// End form:
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}
else
{	// Display a message:
	echo '<p>', T_( 'There are no plugins providing blog-specific settings.' ), '</p>';
	$Form->end_form();
}

?>
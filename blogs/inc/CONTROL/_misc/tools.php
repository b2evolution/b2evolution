<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 * @author blueyed: Daniel HAHLER
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


require_once $inc_path.'_misc/_plugin.funcs.php';


$AdminUI->set_path( 'tools' );

if( $AdminUI->get_menu_entries('tools') )
{ // Prepend a "default" tab
	$AdminUI->unshift_menu_entries( 'tools', array( '' => array('text' => T_('Main tab') ) ) );
}

$tab_Plugin = NULL;
param( 'tab', 'string', '', true );

$tab_plugin_ID = false;

if( ! empty($tab) )
{
	if( preg_match( '~^plug_ID_(\d+)$~', $tab, $match ) )
	{
		$tab_plugin_ID = $match[1];
		$tab_Plugin = & $Plugins->get_by_ID( $match[1] );
		if( ! $tab_Plugin )
		{ // Plugin does not exist
			$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $tab_plugin_ID ), 'error' );
			$tab_plugin_ID = false;
			$tab_Plugin = false;
			$tab = '';
		}
		else
		{
			$Plugins->call_method_if_active( $tab_plugin_ID, 'AdminTabAction', $params = array() );
		}
	}
	else
	{
		$tab = '';
		$Messages->add( 'Invalid sub-menu!' ); // Should need no translation, prevented by GUI
	}
}


$AdminUI->append_path_level( $tab );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();


if( empty($tab) )
{ // Event AdminToolPayload for each Plugin:
	$tool_plugins = $Plugins->get_list_by_event( 'AdminToolPayload' );
	foreach( $tool_plugins as $loop_Plugin )
	{
		echo '<div class="panelblock">';
		echo '<h2>';
		$loop_Plugin->name();
		echo '</h2>';
		$Plugins->call_method_if_active( $loop_Plugin->ID, 'AdminToolPayload', $params = array() );
		echo '</div>';
	}
	?>

	<div class="panelblock">
		<h2><?php echo T_('Movable Type Import') ?></h2>
		<ol>
			<li><?php echo T_('Use MT\'s export functionnality to create a .TXT file containing your posts;') ?></li>
			<li><?php echo T_('Place that file into the /admin folder on your server;') ?></li>
			<li><?php printf( T_('Follow the insctructions in the <a %s>MT migration utility</a>.'), ' href="?ctrl=mtimport"' ) ?></li>
		</ol>
	</div>

	<?php
}
elseif( $tab_Plugin )
{ // Plugin tab

	// Icons:
	?>

	<div class="right_icons">

	<?php
	echo action_icon( T_('Edit plugin settings!'), 'edit', '?ctrl=plugins&amp;action=edit_settings&amp;plugin_ID='.$tab_Plugin->ID )
		.' '.$tab_Plugin->get_help_link()
		.' '.$tab_Plugin->get_README_link();
	?>

	</div>

	<?php
	$Plugins->call_method_if_active( $tab_plugin_ID, 'AdminTabPayload', $params = array() );
}


// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>
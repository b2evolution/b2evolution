<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 * @author This file built upon code from original b2 - http://cafelog.com/
 */

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/_header.php' ); // this will actually load blog params for req blog
$AdminUI->set_path( 'tools' );

if( $AdminUI->get_menu_entries('tools') )
{ // Prepend a "default" tab
	$AdminUI->unshift_menu_entries( 'tools', array( '' => array('text' => T_('Main tab') ) ) );
}

$Plugin = NULL;
param( 'tab', 'string', '', true );
$AdminUI->set_path_by_nr( 1, $tab );

$tab_plugin_ID = false;

if( ! empty($tab) )
{
	if( preg_match( '~^plug_ID_(\d+)$~', $tab, $match ) )
	{
		$tab_plugin_ID = $match[1];
		$Plugins->call_method( $tab_plugin_ID, 'AdminTabAction' );
	}
	else
	{
		$tab = '';
		$Messages->add( 'Invalid sub-menu!' ); // Should need no translation, prevented by GUI
	}
}


require( dirname(__FILE__).'/_menutop.php' );

$AdminUI->disp_payload_begin();


if( empty($tab) )
{
	// Event AdminToolPayload:
	$tool_plugins = $Plugins->get_list_by_event( 'AdminToolPayload' );
	foreach( $tool_plugins as $loop_Plugin )
	{
		echo '<div class="panelblock">';
		echo '<h2>';
		$loop_Plugin->name();
		echo '</h2>';
		$Plugins->call_method( $loop_Plugin->ID, 'AdminToolPayload' );
		echo '</div>';
	}
	?>

	<div class="panelblock">
		<h2><?php echo T_('Movable Type Import') ?></h2>
		<ol>
			<li><?php echo T_('Use MT\'s export functionnality to create a .TXT file containing your posts;') ?></li>
			<li><?php echo T_('Place that file into the /admin folder on your server;') ?></li>
			<li><?php printf( T_('Follow the insctructions in the <a %s>MT migration utility</a>.'), ' href="import-mt.php"' ) ?></li>
		</ol>
	</div>

	<?php
}
else
{
	if( $tab_plugin_ID )
	{
		$Plugins->call_method( $tab_plugin_ID, 'AdminTabPayload' );
	}
}


$AdminUI->disp_payload_end();

require( dirname(__FILE__). '/_footer.php' );
?>
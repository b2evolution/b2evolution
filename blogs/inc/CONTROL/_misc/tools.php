<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 * @author blueyed: Daniel HAHLER
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Import plugin funcs
 */
require_once $inc_path.'_misc/_plugin.funcs.php';


param( 'tab', 'string', '', true );

$tab_Plugin = NULL;
$tab_plugin_ID = false;

if( ! empty($tab) )
{	// We have requested a tab which is handled by a plugin:
	if( preg_match( '~^plug_ID_(\d+)$~', $tab, $match ) )
	{ // Instanciate the invoked plugin:
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

// Highlight the requested tab (if valid):
$AdminUI->set_path( 'tools', $tab );


if( empty($tab) )
{ // "Main tab" actions:
	param( 'action', 'string', '' );

	switch( $action )
	{
		case 'del_itemprecache':
			// TODO: dh> this should really be a separate permission.. ("tools", "exec") or similar!
			$current_User->check_perm('options', 'edit', true);

			$DB->query('DELETE FROM T_item__prerendering WHERE 1');

			$Messages->add( sprintf( T_('Removed %d cached entries.'), $DB->rows_affected ), 'success' );

			break;
	}
}


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
		echo format_to_output($loop_Plugin->name);
		echo '</h2>';
		$Plugins->call_method_if_active( $loop_Plugin->ID, 'AdminToolPayload', $params = array() );
		echo '</div>';
	}


	// TODO: dh> this should really be a separate permission.. ("tools", "exec") or similar!
	if( $current_User->check_perm('options', 'edit') )
	{ // default admin actions:
		?>

		<div class="panelblock">
			<h2><?php echo T_('Contents cached in the database') ?></h2>
			<ul>
				<li><a href="<?php echo regenerate_url('action', 'action=del_itemprecache') ?>"><?php echo T_('Delete pre-renderered item cache.') ?></a></li>
			</ul>
		</div>

		<?php
	}


	// fp> TODO: pluginize MT! :P
	?>

	<div class="panelblock">
		<h2><?php echo T_('Movable Type Import') ?></h2>
		<ol>
			<li><?php echo T_('Use MT\'s export functionnality to create a .TXT file containing your posts;') ?></li>
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
	echo $tab_Plugin->get_edit_settings_link()
		.' '.$tab_Plugin->get_help_link('$help_url')
		.' '.$tab_Plugin->get_help_link('$readme');
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

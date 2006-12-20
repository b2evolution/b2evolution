<?php
/**
 * This file implements the UI controller for plugins management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$AdminUI->set_path( 'options', 'plugins' );

$action = param_action( 'list' );

$UserSettings->param_Request( 'plugins_disp_avail', 'plugins_disp_avail', 'integer', 0 );

// Check permission to display:
$current_User->check_perm( 'options', 'view', true );

load_class('_misc/_plugins_admin.class.php');
$admin_Plugins = new Plugins_admin();
$admin_Plugins->restart();

// Pre-walk list of plugins
while( $loop_Plugin = & $admin_Plugins->get_next() )
{
	if( $loop_Plugin->status == 'broken' && ! isset( $admin_Plugins->plugin_errors[$loop_Plugin->ID] ) )
	{ // The plugin is not "broken" anymore (either the problem got fixed or it was "broken" from a canceled "install_db_schema" action)
		// TODO: set this to the previous status (dh)
		$Plugins->set_Plugin_status( $loop_Plugin, 'disabled' );
	}
}


/**
 * Helper function to do the action part of DB schema upgrades for "enable" and "install"
 * actions.
 *
 * @param Plugin
 * @return boolean True, if no changes needed or done; false if we should break out to display "install_db_schema" action payload.
 */
function install_plugin_db_schema_action( & $Plugin )
{
	global $action, $inc_path, $install_db_deltas, $DB, $Messages;

	$action = 'list';
	// Prepare vars for DB layout changes
	$install_db_deltas_confirm_md5 = param( 'install_db_deltas_confirm_md5' );

	$db_layout = $Plugin->GetDbLayout();
	$install_db_deltas = array(); // This holds changes to make, if any (just all queries)

	if( ! empty($db_layout) )
	{ // The plugin has a DB layout attached
		require_once $inc_path.'_misc/_upgrade.funcs.php';

		// Get the queries to make:
		foreach( db_delta($db_layout) as $table => $queries )
		{
			foreach( $queries as $query_info )
			{
				foreach( $query_info['queries'] as $query )
				{ // subqueries for this query (usually one, but may include required other queries)
					$install_db_deltas[] = $query;
				}
			}
		}

		if( ! empty($install_db_deltas) )
		{ // delta queries to make
			if( empty($install_db_deltas_confirm_md5) )
			{ // delta queries have to be confirmed in payload
				$action = 'install_db_schema';
				return false;
			}
			elseif( $install_db_deltas_confirm_md5 == md5( implode('', $install_db_deltas) ) )
			{ // Confirmed in first step:
				foreach( $install_db_deltas as $query )
				{
					$DB->query( $query );
				}

				$Messages->add( T_('The database has been updated.'), 'success' );
			}
			else
			{ // should not happen
				$Messages->add( T_('The DB schema has been changed since confirmation.'), 'error' );

				// delta queries have to be confirmed (again) in payload
				$action = 'install_db_schema';
				return false;
			}
		}
	}
	return true;
}




// Actions that delegate to other actions (other than list):
switch( $action )
{
	case 'del_settings_set':
		param( 'plugin_ID', 'integer', true );
		param( 'set_path' );

		$edit_Plugin = & $admin_Plugins->get_by_ID($plugin_ID);

		require_once $inc_path.'_misc/_plugin.funcs.php';
		_set_setting_by_path( $edit_Plugin, 'Settings', $set_path, NULL );

		#$edit_Plugin->Settings->dbupdate();

		$action = 'edit_settings';

		break;

	case 'add_settings_set': // delegates to edit_settings
		// Add a new set to an array type setting:
		param( 'plugin_ID', 'integer', true );
		param( 'set_path', 'string', '' );

		$edit_Plugin = & $admin_Plugins->get_by_ID($plugin_ID);

		require_once $inc_path.'_misc/_plugin.funcs.php';
		_set_setting_by_path( $edit_Plugin, 'Settings', $set_path, array() );

		#$edit_Plugin->Settings->dbupdate();

		$action = 'edit_settings';

		break;

}


switch( $action )
{
	// Disable a plugin, only if it is "enabled"
	case 'disable_plugin':
		$current_User->check_perm( 'options', 'edit', true );

		param( 'plugin_ID', 'integer', true );

		$action = 'list';

		$edit_Plugin = & $admin_Plugins->get_by_ID( $plugin_ID );

		if( empty($edit_Plugin) )
		{
			$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $plugin_ID ), 'error' );
			break;
		}
		if( $edit_Plugin->status != 'enabled' )
		{
			$Messages->add( sprintf( T_( 'The plugin with ID %d is already disabled.' ), $plugin_ID ), 'note' );
			break;
		}

		// Check dependencies
		$msgs = $admin_Plugins->validate_dependencies( $edit_Plugin, 'disable' );
		if( ! empty( $msgs['error'] ) )
		{
			$Messages->add( T_( 'The plugin cannot be disabled because of the following dependencies:' ).' <ul><li>'.implode('</li><li>', $msgs['error']).'</li></ul>', 'error' );
			break;
		}

		// we call $Plugins(!) here: the Plugin gets disabled on the current page already and it should not get (un)registered on $admin_Plugins!
		$Plugins->set_Plugin_status( $edit_Plugin, 'disabled' ); // sets $edit_Plugin->status

		$Messages->add( /* TRANS: plugin name, class name and ID */ sprintf( T_('Disabled "%s" plugin (%s, #%d).'), $edit_Plugin->name, $edit_Plugin->classname, $edit_Plugin->ID ), 'success' );

		break;


	// Try to enable a plugin, only if it is in state "disabled" or "needs_config"
	case 'enable_plugin':
		$current_User->check_perm( 'options', 'edit', true );

		param( 'plugin_ID', 'integer', true );

		$action = 'list';

		$edit_Plugin = & $admin_Plugins->get_by_ID( $plugin_ID );

		if( empty($edit_Plugin) )
		{
			$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $plugin_ID ), 'error' );
			break;
		}
		if( $edit_Plugin->status == 'enabled' )
		{
			$Messages->add( /* TRANS: plugin name, class name and ID */ sprintf( T_( 'The "%s" plugin (%s, #%d) is already enabled.' ), $edit_Plugin->name, $edit_Plugin->classname, $plugin_ID ), 'note' );
			break;
		}
		if( $edit_Plugin->status == 'broken' )
		{
			$Messages->add( sprintf( T_( 'The plugin is in a broken state. It cannot get enabled.' ), $plugin_ID ), 'error' );
			break;
		}

		// Check dependencies
		$msgs = $admin_Plugins->validate_dependencies( $edit_Plugin, 'enable' );
		if( ! empty( $msgs['error'] ) )
		{
			$Messages->add( T_( 'The plugin cannot be enabled because of the following dependencies:' ).' <ul><li>'.implode('</li><li>', $msgs['error']).'</li></ul>' );
			break;
		}

		if( ! install_plugin_db_schema_action( $edit_Plugin ) )
		{
			$next_action = 'enable_plugin';
			break;
		}

		// Try to enable plugin:
		$enable_return = $edit_Plugin->BeforeEnable();
		if( $enable_return === true )
		{
			// NOTE: we don't need to handle plug_version here, because it gets handled in Plugins::register() already.

			// Detect new events:
			$admin_Plugins->save_events( $edit_Plugin, array() );

			// we call $Plugins(!) here: the Plugin gets active on the current page already and it should not get (un)registered on $admin_Plugins!
			$Plugins->set_Plugin_status( $edit_Plugin, 'enabled' ); // sets $edit_Plugin->status

			$Messages->add( /* TRANS: plugin name, class name and ID */ sprintf( T_('Enabled "%s" plugin (%s, #%d).'), $edit_Plugin->name, $edit_Plugin->classname, $edit_Plugin->ID ), 'success' );
		}
		else
		{
			$Messages->add( T_('The plugin has not been enabled.').( empty($enable_return) ? '' : '<br />'.$enable_return ), 'error' );
		}

		break;


	case 'reload_plugins':
		// Register new events
		// Unregister obsolete events
		// Detect plugins with no code and try to have at least one plugin with the default code
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$admin_Plugins->restart();
		$admin_Plugins->load_events();
		$changed = false;
		while( $loop_Plugin = & $admin_Plugins->get_next() )
		{
			// NOTE: we don't need to handle plug_version here, because it gets handled in Plugins::register() already.

			// Discover new events:
			if( $admin_Plugins->save_events( $loop_Plugin, array() ) )
			{
				$changed = true;
			}

			// Detect plugins with no code and try to have at least one plugin with the default code:
			if( empty($loop_Plugin->code) )
			{ // Instantiated Plugin has no code
				$default_Plugin = & $admin_Plugins->register($loop_Plugin->classname);

				if( ! empty($default_Plugin->code) // Plugin has default code
				    && ! $admin_Plugins->get_by_code( $default_Plugin->code ) ) // Default code is not in use (anymore)
				{ // Set the Plugin's code to the default one
					if( $admin_Plugins->set_code( $loop_Plugin->ID, $default_Plugin->code ) )
					{
						$changed = true;
					}
				}

				$admin_Plugins->unregister($default_Plugin);
			}
		}

		if( $changed )
		{
			$Messages->add( T_('Plugins have been reloaded.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Plugins have not changed.'), 'note' );
		}
		$action = 'list';
		break;


	case 'install': // Install a plugin. This may be a two-step action, when DB changes have to be confirmed
		$action = 'list';
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// First step:
		param( 'plugin', 'string', true );

		$edit_Plugin = & $admin_Plugins->install( $plugin, 'broken' ); // "broken" by default, gets adjusted later

		if( is_string($edit_Plugin) )
		{
			$Messages->add( $edit_Plugin, 'error' );
			break;
		}


	case 'install_db_schema': // we come here from the first step ("install")
		param( 'plugin_ID', 'integer', 0 );

		if( $plugin_ID )
		{ // second step:
			$edit_Plugin = & $admin_Plugins->get_by_ID( $plugin_ID );

			if( ! is_a($edit_Plugin, 'Plugin') )
			{
				$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $plugin_ID ), 'error' );
				$action = 'list';
				break;
			}
		}
		if( ! install_plugin_db_schema_action($edit_Plugin) )
		{
			$next_action = 'install_db_schema';
			break;
		}

		$msg = sprintf( T_('Installed plugin &laquo;%s&raquo;.'), $edit_Plugin->classname );
		if( ($edit_settings_icon = $edit_Plugin->get_edit_settings_link()) )
		{
			$msg .= ' '.$edit_settings_icon;
		}
		$Messages->add( $msg, 'success' );

		// Install completed:
		$r = $admin_Plugins->call_method( $edit_Plugin->ID, 'AfterInstall', $params = array() );

		// Try to enable plugin:
		$enable_return = $edit_Plugin->BeforeEnable();
		if( $enable_return === true )
		{
			$Plugins->set_Plugin_status( $edit_Plugin, 'enabled' );
		}
		else
		{
			$Messages->add( T_('The plugin has not been enabled.').( empty($enable_return) ? '' : '<br />'.$enable_return ), 'error' );
			$Plugins->set_Plugin_status( $edit_Plugin, 'disabled' ); // does not unregister it
		}

		if( ! empty( $edit_Plugin->install_dep_notes ) )
		{ // Add notes from dependencies
			$Messages->add_messages( array( 'note' => $edit_Plugin->install_dep_notes ) );
		}

		// }}}
		break;


	case 'uninstall':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		// Uninstall plugin:
		param( 'plugin_ID', 'integer', true );
		param( 'uninstall_confirmed_drop', 'integer', 0 );

		$action = 'list'; // leave 'uninstall' by default

		$edit_Plugin = & $admin_Plugins->get_by_ID( $plugin_ID );

		if( empty($edit_Plugin) )
		{
			$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $plugin_ID ), 'error' );
			break;
		}

		// Check dependencies:
		$msgs = $admin_Plugins->validate_dependencies( $edit_Plugin, 'disable' );
		if( ! empty( $msgs['error'] ) )
		{
			$Messages->add( T_( 'The plugin cannot be uninstalled because of the following dependencies:' ).' <ul><li>'.implode('</li><li>', $msgs['error']).'</li></ul>', 'error' );
			break;
		}
		if( ! empty( $msgs['note'] ) )
		{ // just notes:
			$Messages->add_messages( array( 'note' => $msgs['note'] ) );
		}

		// Ask plugin:
		$uninstall_ok = $admin_Plugins->call_method( $edit_Plugin->ID, 'BeforeUninstall', $params = array( 'unattended' => false ) );

		if( $uninstall_ok === false )
		{ // Plugin said "NO":
			$Messages->add( sprintf( T_('Could not uninstall plugin #%d.'), $edit_Plugin->ID ), 'error' );
			break;
		}

		// See if we have (canonical) tables to drop:
		$uninstall_tables_to_drop = $DB->get_col( 'SHOW TABLES LIKE "'.$edit_Plugin->get_sql_table('%').'"' );

		if( $uninstall_ok === true )
		{ // Plugin said "YES":

			if( $uninstall_tables_to_drop )
			{ // There are tables with the prefix for this plugin:
				if( $uninstall_confirmed_drop )
				{ // Drop tables:
					$sql = 'DROP TABLE IF EXISTS '.implode( ', ', $uninstall_tables_to_drop );
					$DB->query( $sql );
					$Messages->add( T_('Dropped the table(s) of the plugin.'), 'success' );
				}
				else
				{
					$uninstall_ok = false;
				}
			}

			if( $uninstall_ok )
			{ // We either have no tables to drop or it has been confirmed:
				$admin_Plugins->uninstall( $edit_Plugin->ID );

				$Messages->add( /* %s = plugin's classname, %d = plugin's ID */
					sprintf( T_('The &laquo;%s&raquo; plugin (#%d) has been uninstalled.'), $edit_Plugin->classname, $edit_Plugin->ID ), 'success' );
				break;
			}
		}

		// $ok === NULL (or other): execute plugin event BeforeUninstallPayload() below
		// $ok === false: let the admin confirm DB table dropping below
		$action = 'uninstall';

		break;


	case 'update_settings':
		// Update plugin settings:

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'plugin_ID', 'integer', true );

		// Next default action:
		if( isset($actionArray['update_settings']) && is_array($actionArray['update_settings']) && isset($actionArray['update_settings']['review']) )
		{ // "Save (and review)"
			$action = 'edit_settings';
		}
		else
		{
			$action = 'list';
		}

		$edit_Plugin = & $admin_Plugins->get_by_ID( $plugin_ID );
		if( empty($edit_Plugin) )
		{
			$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $plugin_ID ), 'error' );
			$action = 'list';
			break;
		}

		// Params from/for form:
		param( 'edited_plugin_name' );
		param( 'edited_plugin_shortdesc' );
		param( 'edited_plugin_code' );
		param( 'edited_plugin_priority' );
		param( 'edited_plugin_apply_rendering' );
		param( 'edited_plugin_displayed_events', 'array', array() );
		param( 'edited_plugin_events', 'array', array() );

		$default_Plugin = & $admin_Plugins->register($edit_Plugin->classname);

		// Update plugin name and shortdesc:
		// (Only if changed to preserve initial localization feature and therefor also priorize NULL)
		if( $edit_Plugin->name != $edited_plugin_name )
		{
			$set_to = $edited_plugin_name == $default_Plugin->name ? NULL : $edited_plugin_name;
			$edit_Plugin->name = $edited_plugin_name;
			if( $DB->query( '
				UPDATE T_plugins
					 SET plug_name = '.$DB->quote($set_to).'
				 WHERE plug_ID = '.$plugin_ID ) )
			{
				$Messages->add( T_('Plugin name updated.'), 'success' );
			}
		}
		if( $edit_Plugin->short_desc != $edited_plugin_shortdesc )
		{
			$set_to = $edited_plugin_shortdesc == $default_Plugin->short_desc ? NULL : $edited_plugin_shortdesc;
			$edit_Plugin->short_desc = $edited_plugin_shortdesc;
			if( $DB->query( '
				UPDATE T_plugins
					 SET plug_shortdesc = '.$DB->quote($set_to).'
				 WHERE plug_ID = '.$plugin_ID ) )
			{
				$Messages->add( T_('Plugin description updated.'), 'success' );
			}
		}

		$updated = $admin_Plugins->set_code( $edit_Plugin->ID, $edited_plugin_code );
		if( is_string( $updated ) )
		{
			param_error( 'edited_plugin_code', $updated );
			$action = 'edit_settings';
		}
		elseif( $updated === 1 )
		{
			$Messages->add( T_('Plugin code updated.'), 'success' );
		}

		if( param_check_range( 'edited_plugin_priority', 0, 100, T_('Plugin priority must be numeric (0-100).'), true ) )
		{
			$updated = $admin_Plugins->set_priority( $edit_Plugin->ID, $edited_plugin_priority );
			if( $updated === 1 )
			{
				$Messages->add( T_('Plugin priority updated.'), 'success' );
			}
		}
		else
		{
			$action = 'edit_settings';
		}

		// apply_rendering:
		if( $admin_Plugins->set_apply_rendering( $edit_Plugin->ID, $edited_plugin_apply_rendering ) )
		{
			$Messages->add( T_('Plugin rendering updated.'), 'success' );
		}

		// Settings:
		if( $edit_Plugin->Settings )
		{
			require_once $inc_path.'_misc/_plugin.funcs.php';
			set_Settings_for_Plugin_from_Request( $edit_Plugin, $admin_Plugins, 'Settings' );

			// Let the plugin handle custom fields:
			$ok_to_update = $admin_Plugins->call_method( $edit_Plugin->ID, 'PluginSettingsUpdateAction', $tmp_params = array() );

			if( $ok_to_update === false )
			{
				$edit_Plugin->Settings->reset();
			}
			elseif( $edit_Plugin->Settings->dbupdate() )
			{
				$Messages->add( T_('Plugin settings have been updated.'), 'success' );
			}
		}

		// Events:
		$registered_events = $admin_Plugins->get_registered_events( $edit_Plugin );

		$enable_events = array();
		$disable_events = array();
		foreach( $edited_plugin_displayed_events as $l_event )
		{
			if( ! in_array( $l_event, $registered_events ) )
			{ // unsupported event
				continue;
			}
			if( isset($edited_plugin_events[$l_event]) && $edited_plugin_events[$l_event] )
			{
				$enable_events[] = $l_event; // may be already there
			}
			else
			{ // unset:
				$disable_events[] = $l_event;
			}
		}
		if( $admin_Plugins->save_events( $edit_Plugin, $enable_events, $disable_events ) )
		{
			$Messages->add( T_('Plugin events have been updated.'), 'success' );
		}

		// Check if it can stay enabled, if it is
		if( $edit_Plugin->status == 'enabled' )
		{
			$enable_return = $edit_Plugin->BeforeEnable();
			if( $enable_return !== true )
			{
				$Plugins->set_Plugin_status( $edit_Plugin, 'needs_config' );
				$Messages->add( T_('The plugin has been disabled.').( empty($enable_return) ? '' : '<br />'.$enable_return ), 'error' );
			}
		}

		if( $Messages->count('error') )
		{ // there were errors
			$action = 'edit_settings';
		}
		break;


	case 'edit_settings':
		// Check permission:
		$current_User->check_perm( 'options', 'view', true );

		// Edit plugin settings:
		param( 'plugin_ID', 'integer', true );

		$edit_Plugin = & $admin_Plugins->get_by_ID( $plugin_ID );

		if( ! $edit_Plugin )
		{
			$Debuglog->add( 'The plugin with ID '.$plugin_ID.' was not found.', array('plugins', 'error') );
			$action = 'list';
			break;
		}

		// Detect new events, so they get displayed correctly in the "Edit events" fieldset:
		$admin_Plugins->save_events( $edit_Plugin, array() );

		// Inform Plugin that it gets edited:
		$admin_Plugins->call_method( $edit_Plugin->ID, 'PluginSettingsEditAction', $tmp_params = array() );

		// Params for form:
		$edited_plugin_name = $edit_Plugin->name;
		$edited_plugin_shortdesc = $edit_Plugin->short_desc;
		$edited_plugin_code = $edit_Plugin->code;
		$edited_plugin_priority = $edit_Plugin->priority;
		$edited_plugin_apply_rendering = $edit_Plugin->apply_rendering;

		break;


	case 'default_settings': // Restore default settings
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'plugin_ID', 'integer', true );

		$edit_Plugin = & $admin_Plugins->get_by_ID( $plugin_ID );
		if( !$edit_Plugin )
		{
			$Debuglog->add( 'The plugin with ID '.$plugin_ID.' was not found.', array('plugins', 'error') );
			$action = 'list';
			break;
		}

		$default_Plugin = & $admin_Plugins->register($edit_Plugin->classname);

		// Params for/"from" form:
		$edited_plugin_name = $default_Plugin->name;
		$edited_plugin_shortdesc = $default_Plugin->short_desc;
		$edited_plugin_code = $default_Plugin->code;
		$edited_plugin_priority = $default_Plugin->priority;
		$edited_plugin_apply_rendering = $default_Plugin->apply_rendering;

		// Name and short desc:
		$DB->query( '
				UPDATE T_plugins
				   SET plug_name = NULL,
				       plug_shortdesc = NULL
				 WHERE plug_ID = '.$plugin_ID );

		// Code:
		$updated = $admin_Plugins->set_code( $edit_Plugin->ID, $edited_plugin_code );
		if( is_string( $updated ) )
		{ // error message
			param_error( 'edited_plugin_code', $updated );
			$action = 'edit_settings';
		}
		elseif( $updated === 1 )
		{
			$Messages->add( T_('Plugin code updated.'), 'success' );
		}

		// Priority:
		if( ! preg_match( '~^1?\d?\d$~', $edited_plugin_priority ) )
		{
			param_error( 'edited_plugin_priority', T_('Plugin priority must be numeric (0-100).') );
		}
		else
		{
			$updated = $admin_Plugins->set_priority( $edit_Plugin->ID, $edited_plugin_priority );
			if( $updated === 1 )
			{
				$Messages->add( T_('Plugin priority updated.'), 'success' );
			}
		}

		// apply_rendering:
		if( $admin_Plugins->set_apply_rendering( $edit_Plugin->ID, $edited_plugin_apply_rendering ) )
		{
			$Messages->add( T_('Plugin rendering updated.'), 'success' );
		}

		// PluginSettings:
		if( $edit_Plugin->Settings )
		{
			if( $edit_Plugin->Settings->restore_defaults() )
			{
				$Messages->add( T_('Restored default values.'), 'success' );
			}
			else
			{
				$Messages->add( T_('Settings have not changed.'), 'note' );
			}
		}

		// Enable all events:
		if( $admin_Plugins->save_events( $edit_Plugin ) )
		{
			$Messages->add( T_('Plugin events have been updated.'), 'success' );
		}

		// Check if it can stay enabled, if it is
		if( $edit_Plugin->status == 'enabled' )
		{
			$enable_return = $edit_Plugin->BeforeEnable();
			if( $enable_return !== true )
			{
				$Plugins->set_Plugin_status( $edit_Plugin, 'needs_config' );
				$Messages->add( T_('The plugin has been disabled.').( empty($enable_return) ? '' : '<br />'.$enable_return ), 'error' );
			}
		}

		// blueyed>> IMHO it's good to see the new settings again. Perhaps we could use $action = 'list' for "Settings have not changed"?
		$action = 'edit_settings';

		break;


	case 'info':
		param( 'plugin_ID', 'integer', true );

		// Discover available plugins:
		load_class('_misc/_plugins_admin_no_db.class.php');
		$AvailablePlugins = & new Plugins_admin_no_DB(); // do not load registered plugins/events from DB
		$AvailablePlugins->discover();

		if( ! ($edit_Plugin = & $AvailablePlugins->get_by_ID( $plugin_ID )) )
		{
			$edit_Plugin = & $admin_Plugins->get_by_ID($plugin_ID);
		}
		if( ! $edit_Plugin )
		{
			$action = 'list';
		}
		break;


	case 'disp_help_plain': // just the help, without any payload
	case 'disp_help':
		param( 'plugin_ID', 'integer', 0 );

		// Discover available plugins:
		load_class('_misc/_plugins_admin_no_db.class.php');
		$AvailablePlugins = & new Plugins_admin_no_DB(); // do not load registered plugins/events from DB
		$AvailablePlugins->discover();

		if( ! ($edit_Plugin = & $AvailablePlugins->get_by_ID( $plugin_ID )) )
		{
			$edit_Plugin = & $admin_Plugins->get_by_ID($plugin_ID);
		}
		if( ! $edit_Plugin || ! ($help_file = $edit_Plugin->get_help_file()) )
		{
			$action = 'list';
			break;
		}

		if( $action == 'disp_help' )
		{ // display it later
			break;
		}

		readfile($help_file);
		debug_info();
		exit();

		break;

}

/*
if( 1 || $Settings->get( 'plugins_disp_log_in_admin' ) )
{
	$Messages->add_messages( $Debuglog->get_messages('plugins') );
}
*/


// Extend titlearea for some actions and add JS:
switch( $action )
{
	case 'edit_settings':
		$AdminUI->append_to_titlearea( '<a href="'.regenerate_url('', 'action=edit_settings&amp;plugin_ID='.$edit_Plugin->ID).'">'
			.sprintf( T_('Edit plugin &laquo;%s&raquo; (ID %d)'), $edit_Plugin->name, $edit_Plugin->ID ).'</a>' );
		break;

	case 'disp_help':
		$title = sprintf( T_('Help for plugin &laquo;%s&raquo;'), '<a href="admin.php?ctrl=plugins&amp;action=edit_settings&amp;plugin_ID='.$edit_Plugin->ID.'">'.$edit_Plugin->name.'</a>' );
		if( ! empty($edit_Plugin->help_url) )
		{
			$title .= ' '.action_icon( T_('External help page'), 'www', $edit_Plugin->help_url );
		}
		$AdminUI->append_to_titlearea( $title );
		break;
}


// Display load error from Plugins::register() (if any):
if( isset($edit_Plugin) && is_object($edit_Plugin) && isset( $admin_Plugins->plugin_errors[$edit_Plugin->ID] )
		&& ! empty($admin_Plugins->plugin_errors[$edit_Plugin->ID]['register']) )
{
	$Messages->add( get_icon('warning').' '.$admin_Plugins->plugin_errors[$edit_Plugin->ID]['register'], 'error' );
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

switch( $action )
{
	case 'disp_help':
		// Display plugin help:
		$help_file_body = implode( '', file($help_file) );

		// Try to extract the BODY part:
		if( preg_match( '~<body.*?>(.*)</body>~is', $help_file_body, $match ) )
		{
			$help_file_body = $match[1];
		}

		echo $help_file_body;
		unset($help_file_body);
		break;


	case 'install_db_schema':
		// Payload for 'install_db_schema' action if DB layout changes have to be confirmed:
		?>

		<div class="panelinfo">

			<?php
			$Form = & new Form( NULL, 'install_db_deltas', 'get' );
			$Form->hidden_ctrl();
			$Form->hidden( 'action', $next_action );
			$Form->hidden( 'plugin_ID', $edit_Plugin->ID );

			$Form->global_icon( T_('Cancel installation!'), 'close', regenerate_url() );

			$Form->begin_form( 'fform', sprintf( /* TRANS: %d is ID, %d name */ T_('Finish setup for plugin #%d (%s)'), $edit_Plugin->ID, $edit_Plugin->name ) );

			echo '<p>'.T_('The plugin needs the following database changes.').'</p>';

			if( ! empty($install_db_deltas) )
			{
				echo '<p>'.T_('The following database changes will be carried out. If you are not sure what this means, it will probably be alright.').'</p>';

				echo '<ul>';
				foreach( $install_db_deltas as $l_delta )
				{
					#echo '<li><code>'.nl2br($l_delta).'</code></li>';
					echo '<li><pre>'.str_replace( "\t", '  ', $l_delta ).'</pre></li>';
				}
				echo '</ul>';

				$Form->hidden( 'install_db_deltas_confirm_md5', md5(implode( '', $install_db_deltas )) );
			}

			$Form->submit( array( '', T_('Install!'), 'ActionButton' ) );
			$Form->end_form();
			?>

		</div>

		<?php
		break;


	case 'uninstall': // We come here either if the plugin requested a call to BeforeUninstallPayload() or if there are tables to be dropped {{{
		?>

		<div class="panelinfo">

			<?php
			$Form = & new Form( '', 'uninstall_plugin', 'post' );
			// We may need to use memorized params in the next page
			$Form->hiddens_by_key( get_memorized( 'action,plugin_ID') );
			$Form->hidden( 'action', 'uninstall' );
			$Form->hidden( 'plugin_ID', $edit_Plugin->ID );
			$Form->hidden( 'uninstall_confirmed_drop', 1 );
			$Form->global_icon( T_('Cancel uninstall!'), 'close', regenerate_url() );

			$Form->begin_form( 'fform', sprintf( /* TRANS: %d is ID, %d name */ T_('Uninstall plugin #%d (%s)'), $edit_Plugin->ID, $edit_Plugin->name ) );

			if( $uninstall_tables_to_drop )
			{
				echo '<p>'.T_('Uninstalling this plugin will also delete its database tables:').'</p>'
					.'<ul>'
					.'<li>'
					.implode( '</li><li>', $uninstall_tables_to_drop )
					.'</li>'
					.'</ul>';
			}

			if( $uninstall_ok === NULL )
			{ // Plugin requested this:
				$admin_Plugins->call_method( $edit_Plugin->ID, 'BeforeUninstallPayload', $params = array( 'Form' => & $Form ) );
			}

			echo '<p>'.T_('THIS CANNOT BE UNDONE!').'</p>';

			$Form->submit( array( '', T_('I am sure!'), 'DeleteButton' ) );
			$Form->end_form();
			?>

		</div>

		<?php // }}}
		break;


	case 'edit_settings':
		$AdminUI->disp_view( 'settings/_set_plugins_editsettings.form.php' );
		break;


	case 'info':
		// Display plugin info:
		require_once $inc_path.'_misc/_plugin.funcs.php';

		$Form = & new Form( $pagenow );

		if( $edit_Plugin->ID > 0 )
		{ // Edit settings button (if installed):
			$Form->global_icon( T_('Edit plugin settings!'), 'edit', $admin_url.'?ctrl=plugins&amp;action=edit_settings&amp;plugin_ID='.$edit_Plugin->ID );
		}

		// Close button:
		$Form->global_icon( T_('Close info!'), 'close', regenerate_url() );

		$Form->begin_form('fform');
		$Form->hidden( 'ctrl', 'plugins' );
		$Form->begin_fieldset('Plugin info', array('class' => 'fieldset clear')); // "clear" to fix Konqueror (http://bugs.kde.org/show_bug.cgi?id=117509)
		$Form->info_field( T_('Name'), $edit_Plugin->name );
		$Form->info_field( T_('Code'),
				( empty($edit_Plugin->code) ? ' - ' : $edit_Plugin->code ),
				array( 'note' => T_('This 32 character code uniquely identifies the functionality of this plugin. It is only necessary to call the plugin by code (SkinTag) or when using it as a Renderer.') ) );
		$Form->info_field( T_('Short desc'), $edit_Plugin->short_desc );
		$Form->info_field( T_('Long desc'), $edit_Plugin->long_desc );
		if( $edit_Plugin->ID > 0 )
		{ // do not display ID for non registered Plugins
			$Form->info_field( T_('ID'), $edit_Plugin->ID );
		}
		$Form->info_field( T_('Version'), $edit_Plugin->version );
		$Form->info_field( T_('Classname'), $edit_Plugin->classname );
		$Form->info_field( T_('Class file'), rel_path_to_base($edit_Plugin->classfile_path ) );

		// Help icons (to homepage and README.html), if available:
		$help_icons = array();
		if( $help_www = $edit_Plugin->get_help_link('$help_url') )
		{
			$help_icons[] = $help_www;
		}
		if( $help_README = $edit_Plugin->get_help_link('$readme') )
		{
			$help_icons[] = $help_README;
		}
		if( ! empty($help_icons) )
		{
			$Form->info_field( T_('Help'), implode( ' ', $help_icons ) );
		}

		$Form->end_fieldset();

		if( $edit_Plugin->ID < 1 )
		{ // add "Install NOW" submit button (if not already installed)
			$registrations = $admin_Plugins->count_regs($edit_Plugin->classname);

			if( ! isset( $edit_Plugin->number_of_installs )
					|| ( $admin_Plugins->count_regs($edit_Plugin->classname) < $edit_Plugin->number_of_installs ) )
			{ // number of installations are not limited or not reached yet
				$Form->hidden( 'action', 'install' );
				$Form->hidden( 'plugin', $edit_Plugin->classname );

				$Form->begin_fieldset( '', array( 'class'=>'fieldset center' ) );
				$Form->submit( array( '', T_('Install NOW!'), 'ActionButton' ) );
				$Form->end_fieldset();
			}
		}

		$Form->end_form();
		$action = '';
		break;

}


switch( $action )
{
	case 'list':
		// Display VIEW:
		$AdminUI->disp_view( 'settings/_set_plugins.form.php' );
		break;

	case 'list_available':
		// Display VIEW:
		$AdminUI->disp_view( 'settings/_set_plugins_available.form.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.72  2006/12/20 23:07:23  blueyed
 * Moved list of available plugins to separate sub-screen/form
 *
 * Revision 1.71  2006/12/04 22:34:30  blueyed
 * Use Plugins_admin::register() to instantiate $default_Plugin
 *
 * Revision 1.70  2006/12/01 20:41:37  blueyed
 * Moved Plugins::uninstall() to Plugins_admin class
 *
 * Revision 1.69  2006/12/01 20:01:38  blueyed
 * Moved Plugins::validate_dependencies() to Plugins_admin class
 *
 * Revision 1.68  2006/11/30 05:43:39  blueyed
 * Moved Plugins::discover() to Plugins_admin::discover(); Renamed Plugins_no_DB to Plugins_admin_no_DB (and deriving from Plugins_admin)
 *
 * Revision 1.67  2006/11/30 00:30:33  blueyed
 * Some minor memory optimizations regarding "Plugins" screen
 *
 * Revision 1.66  2006/11/26 23:40:34  blueyed
 * trans
 *
 * Revision 1.65  2006/11/26 01:42:09  fplanque
 * doc
 *
 */
?>

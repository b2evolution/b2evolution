<?php
/**
 * This file implements the UI controller for plugins management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
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

/**
 * Includes:
 */
require( dirname(__FILE__). '/_header.php' );
$AdminUI->set_path( 'options', 'plugins' );

$action = $Request->param_action( 'list' );

// Check permission to display:
$current_User->check_perm( 'options', 'view', true );


// Discover available plugins:
$AvailablePlugins = & new Plugins_no_DB(); // do not load registered plugins/events from DB
$AvailablePlugins->discover();
$AvailablePlugins->sort('name');


switch( $action )
{
	case 'reload_plugins':
		// Register new events
		// Unregister obsolete events
		// Detect plugins with no code and try to have at least one plugin with the default code 
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$Plugins->restart();
		$Plugins->load_events();
		$changed = false;
		while( $loop_Plugin = & $Plugins->get_next() )
		{
			// Discover new events:
			if( $Plugins->save_events( $loop_Plugin, array() ) )
			{
				$changed = true;
			}

			// Detect plugins with no code and try to have at least one plugin with the default code:
			if( $Plugins->set_empty_code_to_default( $loop_Plugin ) )
			{
				$changed = true;
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


	case 'install':
		$action = 'list';
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		// Install plugin:
		param( 'plugin', 'string', true );
		$installed_Plugin = & $Plugins->install( $plugin );

		if( is_string( $installed_Plugin ) )
		{ // error
			$Messages->add( sprintf( T_('Could not install plugin &laquo;%s&raquo;!'), $plugin ), 'error' );
			$Messages->add( $installed_Plugin, 'error' );
		}
		else
		{
			$Messages->add( sprintf( T_('Installed plugin &laquo;%s&raquo;.'), $plugin ), 'success' );
		}
		break;


	case 'uninstall':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
		// Uninstall plugin:
		param( 'plugin_ID', 'int', true );

		$success = $Plugins->call_method( $plugin_ID, 'BeforeUninstall', $params = array( 'unattended' => false ) );
		if( $success === false )
		{
			if( $params['handles_display'] )
			{ // The plugin handles display
				$action = 'list';
				break;
			}
		}
		else
		{ // try un-installing
			$success = $Plugins->uninstall( $plugin_ID );
		}

		if( $success === true )
		{
			$Messages->add( sprintf( T_('Uninstalled plugin #%d.'), $plugin_ID ), 'success' );
		}
		else
		{
			$Messages->add( sprintf( T_('Could not uninstall plugin #%d.'), $plugin_ID ), 'error' );
			if( ! empty($success) )
			{
				$Messages->add( $success, 'error' );
			}
		}
		$action = 'list';
		break;


	case 'update_settings':
		// Update plugin settings:
		$action = 'list'; // next action by default

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'plugin_ID', 'integer', true );

		$edit_Plugin = & $Plugins->get_by_ID( $plugin_ID );
		if( !$edit_Plugin )
		{
			$Messages->add( sprintf( T_( 'The plugin with ID %d could not get instantiated.' ), $plugin_ID ), 'error' );
			break;
		}

		// Params from/for form:
		$Request->param( 'edited_plugin_code' );
		$Request->param( 'edited_plugin_priority' );
		$Request->param( 'edited_plugin_apply_rendering' );

		$updated = $Plugins->set_code( $edit_Plugin->ID, $edited_plugin_code );
		if( is_string( $updated ) )
		{
			$Request->param_error( 'edited_plugin_code', $updated );
			$action = 'edit_settings';
		}
		elseif( $updated === 1 )
		{
			$Messages->add( T_('Plugin code updated.'), 'success' );
		}

		if( $Request->param_check_number( 'edited_plugin_priority', T_('Plugin priority must be numeric.'), true ) )
		{
			$updated = $Plugins->set_priority( $edit_Plugin->ID, $edited_plugin_priority );
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
		if( $Plugins->set_apply_rendering( $edit_Plugin->ID, $edited_plugin_apply_rendering ) )
		{
			$Messages->add( T_('Plugin rendering appliance updated.'), 'success' );
		}

		// Settings:
		if( $edit_Plugin->Settings )
		{
			foreach( $edit_Plugin->GetDefaultSettings() as $l_name => $l_meta )
			{
				$l_param_type = (isset($l_meta['type']) && $l_meta['type'] == 'array') ? 'array' : 'string';
				$l_value = param( 'edited_plugin_set_'.$l_name, $l_param_type );
				if( false === $Plugins->call_method( $edit_Plugin->ID, 'PluginSettingsBeforeSet', $params = array( 'name' => $l_name, 'value' => & $l_value, 'meta' => $l_meta ) ) )
				{ // skip this
					$action = 'edit_settings';
					continue;
				}
				if( isset($l_meta['valid_pattern']) )
				{
					$param_pattern = is_array($l_meta['valid_pattern']) ? $l_meta['valid_pattern']['pattern'] : $l_meta['valid_pattern'];
					if( ! preg_match( $param_pattern, $l_value ) )
					{
						$param_error = is_array($l_meta['valid_pattern']) ? $l_meta['valid_pattern']['error'] : sprintf(T_('The value is invalid. It must match the regular expression &laquo;%s&raquo;.'), $param_pattern);
						$Request->param_error( 'edited_plugin_set_'.$l_name, $param_error );
						$action = 'edit_settings';
						continue;
					}
				}
				if( $l_param_type == 'array' )
				{
					$l_value = serialize($l_value);
				}
				$edit_Plugin->Settings->set( $l_name, $l_value );
			}

			if( $edit_Plugin->Settings->dbupdate() )
			{
				$Messages->add( T_('Plugin settings have been updated.'), 'success' );
			}
			else
			{
				$Messages->add( T_('Plugin settings have not changed.'), 'note' );
			}
		}

		// Events:
		param( 'edited_plugin_displayed_events', 'array', array() );
		param( 'edited_plugin_events', 'array', array() );
		$registered_events = $Plugins->get_registered_events( $edit_Plugin );

		$enable_events = $disable_events = array();
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
		if( $Plugins->save_events( $edit_Plugin, $enable_events, $disable_events ) )
		{
			$Messages->add( T_('Plugin events have been updated.'), 'success' );
		}

		break;


	case 'edit_settings':
		// Check permission:
		$current_User->check_perm( 'options', 'view', true );

		// Edit plugin settings:
		param( 'plugin_ID', 'integer', true );

		$edit_Plugin = & $Plugins->get_by_ID( $plugin_ID );

		if( ! $edit_Plugin )
		{
			$Debuglog->add( 'The plugin with ID '.$plugin_ID.' was not found.', array('plugins', 'error') );
			$action = 'list';
			break;
		}

		// Params for form:
		$edited_plugin_code = $edit_Plugin->code;
		$edited_plugin_priority = $edit_Plugin->priority;
		$edited_plugin_apply_rendering = $edit_Plugin->apply_rendering;

		break;


	case 'default_settings': // Restore default settings
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		param( 'plugin_ID', 'integer', true );

		$edit_Plugin = & $Plugins->get_by_ID( $plugin_ID );
		if( !$edit_Plugin )
		{
			$Debuglog->add( 'The plugin with ID '.$plugin_ID.' was not found.', array('plugins', 'error') );
			$action = 'list';
			break;
		}

		$default_Plugin = & new $edit_Plugin->classname; // instantiate it to access default member values

		// Params for/"from" form:
		$edited_plugin_code = $default_Plugin->code;
		$edited_plugin_priority = $default_Plugin->priority;
		$edited_plugin_apply_rendering = $default_Plugin->apply_rendering;

		// Code:
		$updated = $Plugins->set_code( $edit_Plugin->ID, $edited_plugin_code );
		if( is_string( $updated ) )
		{ // error message
			$Request->param_error( 'edited_plugin_code', $updated );
			$action = 'edit_settings';
		}
		elseif( $updated === 1 )
		{
			$Messages->add( T_('Plugin code updated.'), 'success' );
		}

		// Priority:
		if( !is_numeric( $edited_plugin_priority ) )
		{
			$Request->param_error( 'edited_plugin_priority', T_('Plugin priority must be numeric.') );
		}
		else
		{
			$updated = $Plugins->set_priority( $edit_Plugin->ID, $edited_plugin_priority );
			if( $updated === 1 )
			{
				$Messages->add( T_('Plugin priority updated.'), 'success' );
			}
		}

		// apply_rendering:
		if( $Plugins->set_apply_rendering( $edit_Plugin->ID, $edited_plugin_apply_rendering ) )
		{
			$Messages->add( T_('Plugin rendering appliance updated.'), 'success' );
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
		if( $Plugins->save_events( $edit_Plugin ) )
		{
			$Messages->add( T_('Plugin events have been updated.'), 'success' );
		}

		// blueyed>> IMHO it's good to see the new settings again. Perhaps we could use $action = 'list' for "Settings have not changed"?
		$action = 'edit_settings';

		break;

	case 'info':
		param( 'plugin_ID', 'integer', true );
		if( ! ($info_Plugin = & $AvailablePlugins->get_by_ID( $plugin_ID )) )
		{
			$info_Plugin = & $Plugins->get_by_ID($plugin_ID);
		}
		if( ! $info_Plugin )
		{
			$action = 'list';
		}
		break;

}

/*
if( 1 || $Settings->get( 'plugins_disp_log_in_admin' ) )
{
	$Messages->add_messages( $Debuglog->getmessages('plugins') );
}
*/


if( $action == 'edit_settings' )
{
	$AdminUI->append_to_titlearea( sprintf( T_('Edit plugin &laquo;%s&raquo; (ID %d)'), $edit_Plugin->name, $edit_Plugin->ID ) );
	$Plugins->call_method( $edit_Plugin->ID, 'PluginSettingsEditAction', $params = array() );
}


require( dirname(__FILE__). '/_menutop.php' );

// Begin payload block:
$AdminUI->disp_payload_begin();

switch( $action )
{
	case 'info':
		// Display plugin info:
		$Form = & new Form( $pagenow );
		$Form->begin_form('fform');
		$Form->begin_fieldset('Plugin info', array('class' => 'fieldset clear')); // "clear" to fix Konqueror (http://bugs.kde.org/show_bug.cgi?id=117509)
		$Form->info_field( T_('Name'), $info_Plugin->name( 'raw', false ) );
		$Form->info_field( T_('Code'), $info_Plugin->code, array( 'note' => T_('This 32 character code uniquely identifies the functionality of this plugin.') ) );
		$Form->info_field( T_('Short desc'), $info_Plugin->short_desc( 'raw', false ) );
		$Form->info_field( T_('Long desc'), $info_Plugin->long_desc( 'raw', false ) );
		// TODO: help url
		$Form->end_fieldset();
		$Form->end_form();
		$action = 'list';
		break;


	case 'edit_settings':
		$Form = & new Form( $pagenow, 'pluginsettings_checkchanges' );
		$Form->begin_form( 'fform') ;
		$Form->hidden( 'plugin_ID', $plugin_ID );

		// PluginSettings
		$Plugins->display_settings_fieldset( $edit_Plugin, $Form );

		// Plugin variables
		$Form->begin_fieldset( T_('Plugin variables').' ('.T_('Advanced').')', array( 'class' => 'clear' ) );
		$Form->text_input( 'edited_plugin_code', $edited_plugin_code, 15, T_('Code'), array('maxlength'=>32, 'note'=>'The code to call the plugin by code. This is also used to link renderer plugins to items.') );
		$Form->text_input( 'edited_plugin_priority', $edited_plugin_priority, 4, T_('Priority'), array( 'maxlength' => 4 ) );
		$Form->select_input_array( 'edited_plugin_apply_rendering', $Plugins->get_apply_rendering_values(), T_('Apply rendering'), array(
			'value' => $edited_plugin_apply_rendering,
			'note' => empty( $edited_plugin_code )
				? T_('Note: The plugin code is empty, so this plugin will not work as an "opt-out", "opt-in" or "lazy" renderer.')
				: NULL )
			);
		$Form->end_fieldset();


		// (De-)Activate Events (Advanced)
		$Form->begin_fieldset( T_('Plugin events').' ('.T_('Advanced')
			.') <img src="'.get_icon('expand', 'url').'" id="clickimg_pluginevents" />', array('legend_params' => array( 'onclick' => 'toggle_clickopen(\'pluginevents\')') ) );
		?>
		<div id="clickdiv_pluginevents">
		<?php
		/**
		 * We do not use {@link Plugins::get_enabled_events()}, because it may have been
		 * dynamically disabled by {@link Plugin::remove_events_for_this_request()}.
		 */
		$enabled_events = $DB->get_col( '
			SELECT pevt_event FROM T_pluginevents
			 WHERE pevt_plug_ID = '.$edit_Plugin->ID.'
			   AND pevt_enabled > 0' );
		$supported_events = $Plugins->get_supported_events();
		$registered_events = $Plugins->get_registered_events( $edit_Plugin );
		$count = 0;
		foreach( array_keys($supported_events) as $l_event )
		{
			if( ! in_array( $l_event, $registered_events ) )
			{
				continue;
			}
			if( in_array( $l_event, $Plugins->_supported_private_events ) )
			{
				continue;
			}
			$Form->hidden( 'edited_plugin_displayed_events[]', $l_event ); // to consider only displayed ones on update
			$Form->checkbox_input( 'edited_plugin_events['.$l_event.']', in_array( $l_event, $enabled_events ), $l_event, array( 'note' => $supported_events[$l_event] ) );
			$count++;
		}
		if( ! $count )
		{
			echo T_( 'This plugin has no registered events.' );
		}
		?>
		</div>
		<?php
		$Form->end_fieldset();
		?>

		<script type="text/javascript">
			<!--
			toggle_clickopen('pluginevents');
			// -->
		</script>

		<?php
		if( $current_User->check_perm( 'options', 'edit', false ) )
		{
			$Form->buttons_input( array(
				array( 'type' => 'submit', 'name' => 'actionArray[update_settings]', 'value' => T_('Save !'), 'class' => 'SaveButton' ),
				array( 'type' => 'reset', 'value' => T_('Reset'), 'class' => 'ResetButton' ),
				array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'SaveButton' ),
				) );
		}
		$Form->end_fieldset();


		// Display info - might be handy to not edit a wrong Plugin
		$Form->begin_fieldset('Plugin info');
		$Form->info_field( T_('Name'), $edit_Plugin->name( 'raw', false ) );
		$Form->info_field( T_('Short desc'), $edit_Plugin->short_desc( 'raw', false ) );
		$Form->info_field( T_('Long desc'), $edit_Plugin->long_desc( 'raw', false ) );
		$Form->info_field( T_('ID'), $edit_Plugin->ID );
		// TODO: help URL
		$Form->end_fieldset();

		$Form->end_form();
		break;
}


if( $action == 'list' )
{
	require dirname(__FILE__).'/_set_plugins.form.php';
}

// End payload block:
$AdminUI->disp_payload_end();

require dirname(__FILE__).'/_footer.php';
?>
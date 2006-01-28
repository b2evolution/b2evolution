<?php
/**
 * This file implements the PluginS class.
 *
 * This is where you can plug-in some plugins :D
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_plugin.class.php';

/**
 * Plugins Class
 *
 * This is where you can plug-in some plugins :D
 *
 * @todo Handle dependencies on other plugins or events. The {@link dnsbl_antispam_plugin} does this itself now.
 * @todo A plugin might want to register allowed events that it triggers itself on installation..
 * @package evocore
 */
class Plugins
{
	/**#@+
	 * @access private
	 */

	/**
	 * Array of loaded plug-ins.
	 */
	var $Plugins = array();


	/**
	 * Index: plugin_code => Plugin
	 */
	var $index_code_Plugins = array();

	/**
	 * Index: plugin_ID => Plugin
	 * @var array
	 */
	var $index_ID_Plugins = array();

	/**
	 * Cache Plugin IDs by event. IDs are sorted by priority.
	 * @var array
	 */
	var $index_event_IDs = array();

	/**
	 * plug_ID => DB row from T_plugins to lazy-instantiate a Plugin.
	 * @var array
	 */
	var $index_ID_rows = array();

	/**
	 * plug_code => plug_ID map to lazy-instantiate by code.
	 * @var array
	 */
	var $index_code_ID = array();

	/**
	 * Cache Plugin IDs by apply_rendering setting.
	 * @var array
	 */
	var $index_apply_rendering_codes = array();

	/**
	 * Path to plug-ins.
	 */
	var $plugins_path;

	/**
	 * Have we loaded the plugins table (T_plugins)?
	 * @var boolean
	 */
	var $loaded_plugins_table = false;

	/**
	 * Current object idx in {@link $sorted_IDs} array.
	 * @var integer
	 */
	var $current_idx = 0;

	/**
	 * List of IDs, sorted. This gets used to lazy-instantiate a Plugin.
	 *
	 * @var array
	 */
	var $sorted_IDs = array();

	/**
	 * The smallest internal/auto-generated Plugin ID.
	 * @var integer
	 */
	var $smallest_internal_ID = 0;


	/**
	 * The list of supported events/hooks.
	 *
	 * Gets lazy-filled in {@link get_supported_events()}.
	 *
	 * @access protected
	 * @var array
	 */
	var $_supported_events;

	/**
	 * A list of events that should not be allowed to be disabled in the backoffice.
	 * @var array
	 */
	var $_supported_private_events = array( 'GetDefaultSettings', 'PluginSettingsInstantiated', 'AfterInstall', 'BeforeInstall', 'BeforeUninstall' );

	/**#@-*/


	/**
	 * Constructor. Sets {@link $plugins_path} and load events.
	 */
	function Plugins()
	{
		global $core_dirout, $plugins_subdir;
		global $DB, $Debuglog, $Timer;

		// Set plugin path:
		$this->plugins_path = dirname(__FILE__).'/'.$core_dirout.$plugins_subdir;

		$Timer->resume( 'plugin_init' );

		$this->load_events();

		$Timer->pause( 'plugin_init' );
	}


	/**
	 * Get the list of supported/available events/hooks.
	 *
	 * Those listed in {@link $_supported_private_events} are supported, but
	 * cannot be disabled in backoffice.
	 *
	 * @todo Finish descriptions
	 *
	 * @return array Name of event (key) => description (value)
	 */
	function get_supported_events()
	{
		if( empty( $this->_supported_events ) )
		{
			$this->_supported_events = array(
				'AdminAfterPageFooter' => '',
				'AdminDisplayEditorButton' => '',
				'AdminDisplayToolbar' => '',
				'AdminEndHtmlHead' => '',
				'AdminAfterMenuInit' => '',
				'AdminTabAction' => '',
				'AdminTabPayload' => '',
				'AdminToolAction' => '',
				'AdminToolPayload' => '',

				'AdminBeginPayload' => '',

				'CacheObjects' => T_('Cache data objects.'),
				'CachePageContent' => T_('Cache page content.'),
				'CacheIsCollectingContent' => T_('Gets asked for if we are generating cached content.'),

				'AfterDataObjectDelete' => '',
				'AfterDataObjectInsert' => '',
				'AfterDataObjectUpdate' => '',

				'PluginSettingsInstantiated' => '', /* private / needs no description */
				'PluginSettingsBeforeSet' => T_("Called before setting a plugin's setting in the backoffice."),
				'PluginSettingsEditAction' => T_("Called as action before editing the plugin's settings."),

				'RenderItemAsHtml' => T_('Renders content when generated as HTML.'),
				'RenderItemAsXml' => T_('Renders content when generated as XML.'),
				'RenderItem' => T_('Renders content when not generated as HTML or XML.'),

				/*
				not used yet..
				'DisplayItemAsHtml' => T_('Called on an item when it gets displayed as HTML.'),
				'DisplayItemAsXml' => T_('Called on an item when it gets displayed as XML.'),
				'DisplayItem' => T_('Called on an item when it gets not displayed as HTML or XML.'),
				*/
				'DisplayItemAllFormats' => T_('Called on an item when it gets displayed.'),

				'ItemViewed' => T_('Called when the view counter of an item got increased.'),

				'SkinTag' => '',

				'DisplayCommentFormButton' => '',
				'DisplayCommentFormFieldset' => '',

				'CommentFormSent' => T_('Called when a comment form got submitted.'),

				'GetKarmaForComment' => '',

				'CaptchaValidated' => T_('Validate the test from CaptchaPayload to detect humans.'),
				'CaptchaPayload' => T_('Provide a turing test to detect humans.'),
				'CaptchaFormPayload' => '',
				'CaptchaFormGetKarma' => '',

				'AppendUserRegistrTransact' => T_('Gets appended to the transaction that creates a new user on registration.'),
				'GetDefaultSettings' => '',  /* private / needs not description */ // used to instantiate $Settings.
				'AfterInstall' => '', /* private / needs not description */
				'AfterUninstall' => '', /* private / needs not description */
				'BeforeInstall' => '', /* private / needs not description */
				'LoginAttempt' => '',
				'SessionLoaded' => '', // gets called after $Session is initialized, quite early.
			);
		}

		return $this->_supported_events;
	}


	/**
	 * Get the list of values for when a rendering Plugin can apply (apply_rendering).
	 *
	 * @todo Add descriptions.
	 *
	 * @param boolean Return an associative array with description for the values?
	 * @return array
	 */
	function get_apply_rendering_values( $with_desc = false )
	{
		if( empty( $this->_apply_rendering_values ) )
		{
			$this->_apply_rendering_values = array(
					'stealth' => '',
					'always' => '',
					'opt-out' => '',
					'opt-in' => '',
					'lazy' => '',
					'never' => '',
				);
		}
		if( ! $with_desc )
		{
			return array_keys( $this->_apply_rendering_values );
		}

		return $this->_apply_rendering_values;
	}


	/**
	 * Discover and register all available plugins.
	 */
	function discover()
	{
		global $Debuglog;

		$Debuglog->add( 'Discovering plugins...', 'plugins' );

		// Go through directory:
		$this_dir = dir( $this->plugins_path );
		while( $this_file = $this_dir->read() )
		{
			if( preg_match( '/^_(.+)\.plugin\.php$/', $this_file, $matches ) && is_file( $this->plugins_path. '/'. $this_file ) )
			{ // Valid plugin file name, Register the plugin:
				$classname = $matches[1].'_plugin';

				$this->register( $classname, 0 ); // auto-generate negative ID; will return string on error.
			}
		}
	}


	/**
	 * Sort the list of {@link $Plugins}.
	 *
	 * WARNING: do NOT sort by anything else than priority unless you're handling a list of NOT-YET-INSTALLED plugins!
	 *
	 * @param string Order: 'priority' (default), 'name'
	 */
	function sort( $order = 'priority' )
	{
		$this->load_plugins_table();

		foreach( $this->sorted_IDs as $plugin_ID )
		{ // Instantiate every plugin, so invalid ones do not get unregistered during sorting (crashes PHP, because $sorted_IDs gets changed etc)
			$this->get_by_ID( $plugin_ID );
		}

		switch( $order )
		{
			case 'name':
				usort( $this->sorted_IDs, array( & $this, 'sort_Plugin_name') );
				break;

			default:
				// Sort array by priority:
				usort( $this->sorted_IDs, array( & $this, 'sort_Plugin_priority') );
		}
	}

	/**
	 * Callback function to sort plugins by priority.
	 */
	function sort_Plugin_priority( & $a_ID, & $b_ID )
	{
		$a_Plugin = & $this->get_by_ID( $a_ID );
		$b_Plugin = & $this->get_by_ID( $b_ID );

		return $a_Plugin->priority - $b_Plugin->priority;
	}

	/**
	 * Callback function to sort plugins by name.
	 *
	 * WARNING: do NOT sort by anything else than priority unless you're handling a list of NOT-YET-INSTALLED plugins
	 */
	function sort_Plugin_name( & $a_ID, & $b_ID )
	{
		$a_Plugin = & $this->get_by_ID( $a_ID );
		$b_Plugin = & $this->get_by_ID( $b_ID );

		return strcasecmp( $a_Plugin->name, $b_Plugin->name );
	}


	/**
	 * Install a plugin.
	 *
	 * Records it and its Events in the database.
	 *
	 * @return Plugin|string The installed plugin on success, string with error on failure.
	 */
	function & install( $plugin_classname )
	{
		global $DB, $Debuglog, $Messages;

		$this->load_plugins_table();

		// Register the plugin:
		$Plugin = & $this->register( $plugin_classname, 0 ); // Auto-generates negative ID; New ID will be set a few lines below

		if( is_string($Plugin) )
		{ // return error message from register()
			return $Plugin;
		}

		if( isset($Plugin->nr_of_installs)
		    && ( $this->count_regs( $Plugin->classname ) >= $Plugin->nr_of_installs ) )
		{
			$this->unregister( $Plugin );
			$r = T_('The plugin cannot be installed again.');
			return $r;
		}

		if( ! $Plugin->BeforeInstall() )
		{
			$this->unregister( $Plugin );
			$r = T_('The installation of the plugin failed.');
			return $r;
		}

		$this->sort( 'priority' );

		if( empty($Plugin->code) )
		{
			$Plugin->code = NULL;
		}

		// Record into DB
		$DB->begin();

		$DB->query( '
				INSERT INTO T_plugins( plug_classname, plug_priority, plug_code, plug_apply_rendering )
				VALUES( "'.$plugin_classname.'", '.$Plugin->priority.', '.$DB->quote($Plugin->code).', '.$DB->quote($Plugin->apply_rendering).' ) ' );

		// Unset auto-generated ID info
		unset( $this->index_ID_Plugins[ $Plugin->ID ] );
		$key = array_search( $Plugin->ID, $this->sorted_IDs );

		// New ID:
		$Plugin->ID = $DB->insert_id;
		$this->index_ID_Plugins[ $Plugin->ID ] = & $Plugin;
		$this->sorted_IDs[$key] = $Plugin->ID;

		$this->save_events( $Plugin );

		$DB->commit();

		// "GetDefaultSettings" was just discovered by save_events()
		$this->instantiate_Settings( $Plugin );

		$Debuglog->add( 'New plugin: '.$Plugin->name.' ID: '.$Plugin->ID, 'plugins' );

		$this->call_method( $Plugin->ID, 'AfterInstall', $params = array() );

		return $Plugin;
	}


	/**
	 * Uninstall a plugin.
	 *
	 * Removes the Plugin, its Settings and Events from the database.
	 *
	 * @return true|string True on success, string on failure (might be empty, as a lot of cases are checked that should never happen).
	 */
	function uninstall( $plugin_ID )
	{
		global $DB, $Debuglog;

		$Debuglog->add( 'Uninstalling plugin (ID '.$plugin_ID.')...', 'plugins' );

		$Plugin = & $this->get_by_ID( $plugin_ID ); // get the Plugin before any not loaded data might get deleted below

		if( ! $Plugin )
		{
			$Debuglog->add( 'Plugin could not be instantiated!', 'plugins' );
			return '';
		}

		$DB->begin();

		// Delete Plugin settings (constraints)
		$DB->query( "DELETE FROM T_pluginsettings
		              WHERE pset_plug_ID = $plugin_ID" );

		// Delete Plugin events (constraints)
		$DB->query( "DELETE FROM T_pluginevents
		              WHERE pevt_plug_ID = $plugin_ID" );

		// Delete from DB
		if( ! $DB->query( "DELETE FROM T_plugins
		                    WHERE plug_ID = $plugin_ID" ) )
		{ // Nothing removed!?
			$DB->commit();

			return ''; // should be prevented by UI
		}

		$DB->commit();

		$this->unregister( $Plugin );

		$Debuglog->add( 'Uninstalled plugin (ID '.$plugin_ID.').', 'plugins' );
		return true;
	}


	/**
	 * Register a plugin.
	 *
	 * This handles the indexes, dynamically unregisters a Plugin that does not exist (anymore)
	 * and instantiates the Plugin's Settings.
	 *
	 * @todo When a Plugin does not exist anymore we might want to provide a link in
	 *       "Tools / Plugins" to un-install it completely or handle it otherwise.. (deactivate)
	 * @access private
	 * @param string name of plugin class to instantiate & register
	 * @param int ID in database (0 if not installed)
	 * @param int Priority in database (-1 to keep default)
	 * @param array When should rendering apply? (NULL to keep default)
	 * @param boolean Must the plugin exist (filename and classname)?
	 *                This is used internally to be able to unregister an non-existing plugin.
	 * @return Plugin|string Plugin ref to newly created plugin; string in case of error
	 */
	function & register( $classname, $ID = 0, $priority = -1, $apply_rendering = NULL, $must_exists = true )
	{
		global $Debuglog, $Messages, $pagenow, $Timer;

		if( $ID && isset($this->index_ID_Plugins[$ID]) )
		{
			debug_die( 'Tried to register already registered Plugin (ID '.$ID.')' ); // should never happen!
		}

		$Timer->resume( 'plugins_register' );

		$filename = $this->plugins_path.'_'.str_replace( '_plugin', '.plugin', $classname ).'.php';

		$Debuglog->add( 'register(): '.$classname.', ID: '.$ID.', priority: '.$priority.', filename: ['.$filename.']', 'plugins' );

		if( ! is_readable( $filename ) )
		{ // Plugin file not found!
			if( $must_exists )
			{
				$r = 'Plugin filename ['.rel_path_to_base($filename).'] not readable!'; // no translation, should not happen!
				$Debuglog->add( $r, array( 'plugins', 'error' ) );

				// unregister:
				$Plugin = & $this->register( $classname, $ID, $priority, $apply_rendering, false ); // must not exist
				$this->unregister( $Plugin );
				$Debuglog->add( 'Unregistered plugin ['.$classname.']!', array( 'plugins', 'error' ) );

				$Timer->pause( 'plugins_register' );
				return $r;
			}
		}
		else
		{
			$Debuglog->add( 'Loading plugin class file: '.$classname, 'plugins' );
			require_once $filename;
		}

		if( ! class_exists( $classname ) )
		{ // the given class does not exist
			if( $must_exists )
			{
				$r = sprintf( /* TRANS: First %s is the (class)name */ T_('Plugin class for &laquo;%s&raquo; in file &laquo;%s&raquo; not defined - it must match the filename.'), $classname, rel_path_to_base($filename) );
				$Debuglog->add( $r, array( 'plugins', 'error' ) );

				// unregister:
				$Plugin = & $this->register( $classname, $ID, $priority, $apply_rendering, false ); // must not exist
				$this->unregister( $Plugin );
				$Debuglog->add( 'Unregistered plugin ['.$classname.']!', array( 'plugins', 'error' ) );

				$Timer->pause( 'plugins_register' );
				return $r;
			}
			else
			{
				$Plugin = new stdClass;	// COPY !
				$Plugin->code = NULL;
				$Plugin->apply_rendering = 'never';
			}
		}
		else
		{
			$Plugin = new $classname;	// COPY !
		}

		// Tell him his ID :)
		if( $ID == 0 )
		{
			$Plugin->ID = --$this->smallest_internal_ID;
		}
		else
		{
			$Plugin->ID = $ID;
		}
		// Tell him his name :)
		$Plugin->classname = $classname;
		// Tell him his priority:
		if( $priority > -1 ) { $Plugin->priority = $priority; }
		if( isset( $this->index_ID_rows[$Plugin->ID] ) )
		{
			$Plugin->code = $this->index_ID_rows[$Plugin->ID]['plug_code'];
		}

		if( isset($apply_rendering) )
		{
			$Plugin->apply_rendering = $apply_rendering;
		}

		// Memorizes Plugin in sequential array:
		$this->Plugins[] = & $Plugin;
		// Memorizes Plugin in code hash array:
		if( isset($this->index_code_ID[ $Plugin->code ]) && $this->index_code_ID[ $Plugin->code ] != $Plugin->ID )
		{ // This code is already in use!
			$Plugin->code = NULL;
		}
		else
		{
			$this->index_code_Plugins[ $Plugin->code ] = & $Plugin;
			$this->index_code_ID[ $Plugin->code ] = & $Plugin->ID;
		}
		$this->index_ID_Plugins[ $Plugin->ID ] = & $Plugin;

		if( ! in_array( $Plugin->ID, $this->sorted_IDs ) )
		{ // not in our sort index yet
			$this->sorted_IDs[] = & $Plugin->ID;
		}

		// Instantiate the Plugins Settings class
		$this->instantiate_Settings( $Plugin );

		$Timer->pause( 'plugins_register' );

		return $Plugin;
	}


	/**
	 * Un-register a plugin.
	 *
	 * This does not un-install it from DB, just from the internal indexes.
	 */
	function unregister( & $Plugin )
	{
		global $Debuglog;

		// Forget events:
		foreach( array_keys($this->index_event_IDs) as $l_event )
		{
			while( ($key = array_search( $Plugin->ID, $this->index_event_IDs[$l_event] )) !== false )
			{
				unset( $this->index_event_IDs[$l_event][$key] );
			}
		}

		// Unset apply-rendering index:
		if( isset( $this->index_apply_rendering_codes[ $Plugin->apply_rendering ] ) )
		{
			while( ( $key = array_search( $Plugin->code, $this->index_apply_rendering_codes[$Plugin->apply_rendering] ) ) !== false )
			{
				unset( $this->index_apply_rendering_codes[$Plugin->apply_rendering][$key] );
			}
		}

		unset( $this->index_code_Plugins[ $Plugin->code ] );
		unset( $this->index_ID_Plugins[ $Plugin->ID ] );

		if( isset($this->index_ID_rows[ $Plugin->ID ]) )
		{ // It has an associated DB row (load_plugins_table() was called)
			unset($this->index_ID_rows[ $Plugin->ID ]);
		}

		$sort_key = array_search( $Plugin->ID, $this->sorted_IDs );
		if( $sort_key === false )
		{ // this may happen if a Plugin has unregistered itself
			$Debuglog->add( 'Tried to unregister not-installed plugin (not in $sorted_IDs)!', 'plugins' );
			return false;
		}
		unset( $this->sorted_IDs[$sort_key] );
		$this->sorted_IDs = array_values( $this->sorted_IDs );

		// Unset from $Plugins array.. this should not be necessary really, but keeps things clean
		foreach( $this->Plugins as $l_key => $l_Plugin )
		{
			if( $l_Plugin->ID == $Plugin->ID )
			{
				unset( $this->Plugins[$l_key] );
				// Note: No need to re-arrange Plugins array..
				break;
			}
		}

		if( $this->current_idx >= $sort_key )
		{ // We have removed a file before or at the $sort_key'th position
			$this->current_idx--;
		}
	}


	/**
	 * Set the code for a given Plugin ID.
	 *
	 * It makes sure that the index is handled and writes it to DB.
	 *
	 * @param string Plugin ID
	 * @param string Code to set the plugin to
	 * @return boolean|integer|string
	 *   true, if already set to same value.
	 *   string: error message (already in use, wrong format)
	 *   1 in case of setting it into DB (number of affected rows).
	 *   false, if invalid Plugin.
	 */
	function set_code( $plugin_ID, $code )
	{
		global $DB;

		if( strlen( $code ) > 32 )
		{
			return T_( 'The maximum length of a plugin code is 32 characters.' );
		}

		// TODO: more strict check?! Just "[\w_-]+" as regexp pattern?
		if( strpos( $code, '.' ) !== false )
		{
			return T_( 'The plugin code cannot include a dot!' );
		}

		if( ! empty($code) && isset( $this->index_code_ID[$code] ) )
		{
			if( $this->index_code_ID[$code] == $plugin_ID )
			{ // Already set to same value
				return true;
			}
			else
			{
				return T_( 'The plugin code is already in use by another plugin.' );
			}
		}

		$Plugin = & $this->get_by_ID( $plugin_ID );
		if( ! $Plugin )
		{
			return false;
		}

		if( empty($code) )
		{
			$code = NULL;
		}
		else
		{ // update indexes
			$this->index_code_ID[$code] = & $Plugin->ID;
			$this->index_code_Plugins[$code] = & $Plugin;
		}
		$Plugin->code = $code;

		return $DB->query( '
			UPDATE T_plugins
			  SET plug_code = '.$DB->quote($code).'
			WHERE plug_ID = '.$plugin_ID );
	}


	/**
	 * Set the priority for a given Plugin ID.
	 *
	 * It makes sure that the index is handled and writes it to DB.
	 *
	 * @return boolean|integer
	 *   true, if already set to same value.
	 *   false if another Plugin uses that code already.
	 *   1 in case of setting it into DB.
	 */
	function set_priority( $plugin_ID, $priority )
	{
		global $DB;

		if( ! is_numeric($priority) )
		{
			debug_die( 'Plugin priority must be numeric.' );
		}

		$Plugin = & $this->get_by_ID($plugin_ID);
		if( ! $Plugin )
		{
			return false;
		}

		if( $Plugin->priority == $priority )
		{ // Already set to same value
			return true;
		}

		$r = $DB->query( '
			UPDATE T_plugins
			  SET plug_priority = '.$DB->quote($priority).'
			WHERE plug_ID = '.$plugin_ID );

		$Plugin->priority = $priority;
		$this->sort();
		return $r;
	}


	/**
	 * Set the apply_rendering value for a given Plugin ID.
	 *
	 * It makes sure that the index is handled and writes it to DB.
	 *
	 * @return boolean true if set to new value, false in case of error or if already set to same value
	 */
	function set_apply_rendering( $plugin_ID, $apply_rendering )
	{
		global $DB;

		if( ! in_array( $apply_rendering, $this->get_apply_rendering_values() ) )
		{
			debug_die( 'Plugin apply_rendering not in allowed list.' );
		}

		$Plugin = & $this->get_by_ID($plugin_ID);
		if( ! $Plugin )
		{
			return false;
		}

		if( $Plugin->apply_rendering == $apply_rendering )
		{ // Already set to same value
			return false;
		}

		$r = $DB->query( '
			UPDATE T_plugins
			  SET plug_apply_rendering = '.$DB->quote($apply_rendering).'
			WHERE plug_ID = '.$plugin_ID );

		$Plugin->apply_rendering = $apply_rendering;

		return true;
	}


	/**
	 * Instantiate Settings member of class {@link PluginSettings} for the given
	 * plugin, if it provides default settings.
	 *
	 * @param Plugin
	 */
	function instantiate_Settings( & $Plugin )
	{
		global $Debuglog, $Timer;

		$Timer->resume( 'plugins_settings' );

		$defaults = $this->call_method_if_active( $Plugin->ID, 'GetDefaultSettings', $params = array() );

		if( empty($defaults) )
		{
			return false;
		}

		if( !is_array($defaults) )
		{
			$Debuglog->add( $Plugin->classname.'::GetDefaultSettings() did not return array!', array('plugins', 'error') );
		}
		else
		{
			require_once dirname(__FILE__).'/_pluginsettings.class.php';
			$Plugin->Settings = & new PluginSettings( $Plugin->ID );

			foreach( $defaults as $l_name => $l_meta )
			{
				$Plugin->Settings->_defaults[$l_name] = isset($l_meta['defaultvalue']) ? $l_meta['defaultvalue'] : '';
			}

			$this->call_method( $Plugin->ID, 'PluginSettingsInstantiated', $params = array() );
		}

		$Timer->pause( 'plugins_settings' );
	}


	/**
	 * Count # of registrations of same plugin.
	 *
	 * Plugins with negative ID (auto-generated; not installed (yet)) will not get considered.
	 *
	 * @param string class name
	 * @return int # of regs
	 */
	function count_regs( $classname )
	{
		$count = 0;

		foreach( $this->sorted_IDs as $plugin_ID )
		{
			$Plugin = & $this->get_by_ID( $plugin_ID );
			if( $Plugin && $Plugin->classname == $classname && $Plugin->ID > 0 )
			{
				$count++;
			}
		}
		return $count;
	}


	/**
	 * Get next plugin in the list.
	 *
	 * NOTE: You'll have to call {@link restart()} or {@link load_plugins_table()}
	 * before using it.
	 *
	 * @return Plugin|false (false if no more plugin).
	 */
	function & get_next()
	{
		global $Debuglog;

		$Debuglog->add( 'get_next() ('.$this->current_idx.')..', 'plugins' );

		if( isset($this->sorted_IDs[$this->current_idx]) )
		{
			$Plugin = & $this->get_by_ID( $this->sorted_IDs[$this->current_idx] );

			$this->current_idx++;

			if( ! $Plugin )
			{ // recurse until we've been through whole $sorted_IDs!
				return $this->get_next();
			}

			$Debuglog->add( 'return: '.$Plugin->classname.' ('.$Plugin->ID.')', 'plugins' );
			return $Plugin;
		}
		else
		{
			$Debuglog->add( 'return: false', 'plugins' );
			$r = false;
			return $r;
		}
	}


	/**
	 * Load plugins table and rewind iterator used by {@link get_next()}.
	 */
	function restart()
	{
		$this->load_plugins_table();

		$this->current_idx = 0;
	}


	/**
	 * Stop propagation of events to next plugins in {@link trigger_event()}.
	 */
	function stop_propagation()
	{
		$this->_stop_propagation = true;
	}


	/**
	 * Trigger appropriate events before displaying content.
	 *
	 * This is different from {@link render()}:
	 *  - It applies on every display (rendering will get cached later)
	 *  - It calls all Plugins that register these events, not just associated ones.
	 *
	 * @param string Content to render
	 * @param mixed The affected object
	 * @param string Output format, see {@link format_to_output()}
	 * @param string Type of data to display ('ItemContent').
	 * @return string rendered content
	 */
	function trigger_display( & $content, & $Object, $format, $type = 'ItemContent' )
	{
		$params = array(
				'data'   => & $content,
				'format' => $format,
			);

		if( $type == 'ItemContent' )
		{
			$params['Item'] = & $Object;
		}

		/*
		Not used yet:
		// TODO: support $type different than 'ItemContent'
		if( $format == 'htmlbody' || $format == 'entityencoded' )
		{
			$event = 'DisplayItemAsHtml';
		}
		elseif( $format == 'xml' )
		{
			$event = 'DisplayItemAsXml';
		}
		else
		{
			$event = 'DisplayItem';
		}
		$this->trigger_event( $event, $params );
		*/

		$this->trigger_event( 'DisplayItemAllFormats', $params );

		return $content;
	}


	/**
	 * Call all plugins for a given event.
	 *
	 * @param string event name, see {@link Plugin}
	 * @param array Associative array of parameters for the Plugin
	 */
	function trigger_event( $event, $params = NULL )
	{
		global $Debuglog;

		$Debuglog->add( 'Trigger event '.$event, 'plugins' );

		if( empty($this->index_event_IDs[$event]) )
		{ // No events registered
			$Debuglog->add( 'No registered plugins.', 'plugins' );
			return false;
		}

		$Debuglog->add( 'Registered plugin IDs: '.implode( ', ', $this->index_event_IDs[$event]), 'plugins' );
		foreach( $this->index_event_IDs[$event] as $l_plugin_ID )
		{
			$this->call_method( $l_plugin_ID, $event, $params );
			if( ! empty($this->_stop_propagation) )
			{
				$this->_stop_propagation = false;
				break;
			}
		}
	}


	/**
	 * Call all plugins for a given event, until the first one returns true.
	 *
	 * @param string event name, see {@link Plugin}
	 * @param array Associative array of parameters for the Plugin
	 * @return array The (modified) params array with key "plugin_ID" set to the last called plugin;
	 *               Empty array if no Plugin returned true or no Plugin has this event registered.
	 */
	function trigger_event_first_true( $event, $params = NULL )
	{
		global $Debuglog;

		$Debuglog->add( 'Trigger event '.$event.' (first true)', 'plugins' );

		if( empty($this->index_event_IDs[$event]) )
		{ // No events registered
			$Debuglog->add( 'No registered plugins.', 'plugins' );
			return array();
		}

		$Debuglog->add( 'Registered plugin IDs: '.implode( ', ', $this->index_event_IDs[$event]), 'plugins' );
		foreach( $this->index_event_IDs[$event] as $l_plugin_ID )
		{
			$r = $this->call_method( $l_plugin_ID, $event, $params );
			if( $r === true )
			{
				$Debuglog->add( 'Plugin ID '.$l_plugin_ID.' returned true!', 'plugins' );
				$params['plugin_ID'] = & $l_plugin_ID;
				return $params;
			}
		}
		return array();
	}


	/**
	 * Trigger a karma collecting event.
	 *
	 * @param string Event
	 * @param array Params to the event
	 * @param integer Maximum karma to start with
	 * @param integer Absolute karma to start with
	 * @return integer Karma percentage (rounded)
	 */
	function trigger_karma_collect( $event, $params, $karma_max = 1, $karma_absolute = 1 )
	{
		$params['karma_max'] = & $karma_max;
		$params['karma_absolute'] = & $karma_absolute;

		$this->trigger_event( $event, $params );

		$percentage = $karma_max ? ( $karma_absolute * 100 ) / $karma_max : 0;

		return round($percentage);
	}


	/**
	 * Call a method on a Plugin.
	 *
	 * This makes sure that the Timer for the Plugin gets resumed.
	 *
	 * @param integer Plugin ID
	 * @param string Method name.
	 * @param array Params (by reference).
	 * @return NULL|mixed Return value of the plugin's method call or NULL if no such method.
	 */
	function call_method( $plugin_ID, $method, & $params )
	{
		global $Timer, $debug, $Debuglog;

		$Plugin = & $this->get_by_ID( $plugin_ID );

		if( ! method_exists( $Plugin, $method ) )
		{
			return NULL;
		}

		if( $debug )
		{
			// Hide passwords from Debuglog!
			$debug_params = $params;
			if( isset($debug_params['pass']) )
			{
				$debug_params['pass'] = '-hidden-';
			}
			if( isset($debug_params['pass_md5']) )
			{
				$debug_params['pass_md5'] = '-hidden-';
			}
			$Debuglog->add( 'Calling '.$Plugin->classname.'(#'.$Plugin->ID.')->'.$method.'( '.htmlspecialchars(var_export( $debug_params, true )).' )', 'plugins' );
		}

		$Timer->resume( $Plugin->classname.'_(#'.$Plugin->ID.')' );
		$r = $Plugin->$method( $params );
		$Timer->pause( $Plugin->classname.'_(#'.$Plugin->ID.')' );

		return $r;
	}


	/**
	 * Call a method on a Plugin if it is not deactivated.
	 *
	 * This is a wrapper around {@link call_method()}.
	 *
	 * @param integer Plugin ID
	 * @param string Method name.
	 * @param array Params (by reference).
	 * @return NULL|mixed Return value of the plugin's method call or NULL if no such method (or inactive).
	 */
	function call_method_if_active( $plugin_ID, $method, & $params )
	{
		if( ! isset($this->index_event_IDs[$method])
		    || ! in_array( $plugin_ID, $this->index_event_IDs[$method] ) )
		{
			return NULL;
		}

		return $this->call_method( $plugin_ID, $method, $params );
	}


	/**
	 * Validate renderer list.
	 *
	 * @param array renderer codes ('default' will include all "opt-out"-ones)
	 * @return array validated array
	 */
	function validate_list( $renderers = array('default') )
	{
		$this->load_plugins_table();

		$validated_renderers = array();

		$index = & $this->index_apply_rendering_codes;

		if( isset( $index['stealth'] ) )
		{
			// pre_dump( 'stealth:', $index['stealth'] );
			$validated_renderers = array_merge( $validated_renderers, $index['stealth'] );
		}
		if( isset( $index['always'] ) )
		{
			// pre_dump( 'always:', $index['always'] );
			$validated_renderers = array_merge( $validated_renderers, $index['always'] );
		}

		if( isset( $index['opt-out'] ) )
		{
			foreach( $index['opt-out'] as $l_code )
			{
				if( in_array( $l_code, $renderers ) // Option is activated
					|| in_array( 'default', $renderers ) ) // OR we're asking for default renderer set
				{
					// pre_dump( 'opt-out:', $l_code );
					$validated_renderers[] = $l_code;
				}
			}
		}

		if( isset( $index['opt-in'] ) )
		{
			foreach( $index['opt-in'] as $l_code )
			{
				if( in_array( $l_code, $renderers ) ) // Option is activated
				{
					// pre_dump( 'opt-in:', $l_code );
					$validated_renderers[] = $l_code;
				}
			}
		}
		if( isset( $index['lazy'] ) )
		{
			foreach( $index['lazy'] as $l_code )
			{
				if( in_array( $l_code, $renderers ) ) // Option is activated
				{
					// pre_dump( 'lazy:', $l_code );
					$validated_renderers[] = $l_code;
				}
			}
		}

		// echo 'validated Renderers: '.count( $validated_renderers );
		return $validated_renderers;
	}


	/**
	 * Render the content
	 *
	 * @param string content to render
	 * @param array renderer codes to use for opt-out, opt-in and lazy.
	 * @param string Output format, see {@link format_to_output()}
	 * @param string Type of data to render ('ItemContent').
	 * @return string rendered content
	 */
	function render( & $content, & $renderers, $format, $type = 'ItemContent' )
	{
		// echo implode(',',$renderers);

		$params = array(
				'data'   => & $content,
				'format' => $format
			);

		// TODO: support $type different than 'ItemContent'
		if( $format == 'htmlbody' || $format == 'entityencoded' )
		{
			$event = 'RenderItemAsHtml';
		}
		elseif( $format == 'xml' )
		{
			$event = 'RenderItemAsXml';
		}
		else
		{
			$event = 'RenderItem';
		}
		$renderer_Plugins = $this->get_list_by_event( $event );

		foreach( $renderer_Plugins as $loop_RendererPlugin )
		{ // Go through whole list of renders
			// echo ' ',$loop_RendererPlugin->code, ':';

			switch( $loop_RendererPlugin->apply_rendering )
			{
				case 'stealth':
				case 'always':
					// echo 'FORCED ';
					$this->call_method( $loop_RendererPlugin->ID, $event, $params );
					break;

				case 'opt-out':
				case 'opt-in':
				case 'lazy':
					if( in_array( $loop_RendererPlugin->code, $renderers ) )
					{ // Option is activated
						// echo 'OPT ';
						$this->call_method( $loop_RendererPlugin->ID, $event, $params );
					}
					// else echo 'NOOPT ';
					break;

				case 'never':
					// echo 'NEVER ';
					break;	// STOP, don't render, go to next renderer
			}
		}

		return $content;
	}


	/**
	 * Quick-render a string with a single plugin and format it for output.
	 *
	 * @param string Plugin code (must have render() method)
	 * @param array
	 *   'data': Data to render
	 *   'format: format to output, see {@link format_to_output()}
	 * @return string Rendered string
	 */
	function quick( $plugin_code, $params )
	{
		global $Debuglog;

		if( !is_array($params) )
		{
			$params = array( 'format' => 'htmlbody', 'data' => $params );
		}
		else
		{
			$params = $params; // copy
		}

		$Plugin = & $this->get_by_code( $plugin_code );
		if( $Plugin )
		{
			// Get the most appropriate handler:
			$events = $this->get_enabled_events( $Plugin->ID );
			$event = false;
			if( $params['format'] == 'htmlbody' || $params['format'] == 'htmlentityencoded' )
			{
				if( in_array( 'RenderItemAsHtml', $events ) )
				{
					$event = 'RenderItemAsHtml';
				}
			}
			elseif( $params['format'] == 'xml' )
			{
				if( in_array( 'RenderItemAsXml', $events ) )
				{
					$event = 'RenderItemAsXml';
				}
			}

			if( $event )
			{
				$this->call_method( $Plugin->ID, $event, $params );
			}
			else
			{
				$Debuglog->add( $Plugin->classname.'(ID '.$Plugin->ID.'): failed to quick-render (tried method '.$event.')!', array( 'plugins', 'error' ) );
			}
			return format_to_output( $params['data'], $params['format'] );
		}
		else
		{
			$Debuglog->add( 'Plugins::quick() - failed to instantiate Plugin by code ['.$plugin_code.']!', array( 'plugins', 'error' ) );
			return format_to_output( $params['data'], $params['format'] );
		}
	}


	/**
	 * Call a specific plugin by its code.
	 *
	 * This will call the SkinTag event handler.
	 *
	 * @param string plugin code
	 * @param array Associative array of parameters
	 * @return boolean
	 */
	function call_by_code( $code, $params )
	{
		$Plugin = & $this->get_by_code( $code );

		if( ! $Plugin )
		{
			return false;
		}

		$this->call_method_if_active( $Plugin->ID, 'SkinTag', $params );

		return true;
	}


	/**
	 * Load Plugins data from T_plugins (only once), ordered by priority.
	 *
	 * This fills the needed indexes to lazy-instantiate a Plugin when requested.
	 */
	function load_plugins_table()
	{
		if( $this->loaded_plugins_table )
		{
			return;
		}
		global $Debuglog, $DB;

		$Debuglog->add( 'Loading plugins table data.', 'plugins' );
		$i = 0;
		foreach( $DB->get_results( '
				SELECT plug_ID, plug_priority, plug_classname, plug_code, plug_apply_rendering FROM T_plugins
				ORDER BY plug_priority', ARRAY_A ) as $row )
		{ // Loop through installed plugins:
			$this->index_ID_rows[$row['plug_ID']] = $row; // remember the rows to instantiate the Plugin on request
			if( ! empty( $row['plug_code'] ) )
			{
				$this->index_code_ID[$row['plug_code']] = $row['plug_ID'];
			}
			$this->index_apply_rendering_codes[$row['plug_apply_rendering']][] = $row['plug_code'];

			$this->sorted_IDs[] = $row['plug_ID'];
		}

		$this->loaded_plugins_table = true;
	}


	/**
	 * Get a specific plugin by its ID.
	 *
	 * This is the workhorse when it comes to lazy-instantiate a Plugin.
	 *
	 * @param integer plugin ID
	 * @return Plugin|false
	 */
	function & get_by_ID( $plugin_ID )
	{
		global $Debuglog;

		if( ! isset($this->index_ID_Plugins[ $plugin_ID ]) )
		{ // Plugin is not instantiated yet
			$Debuglog->add( 'get_by_ID(): Instantiate Plugin (ID '.$plugin_ID.').', 'plugins' );

			$this->load_plugins_table();

			#pre_dump( 'get_by_ID(), index_ID_rows', $this->index_ID_rows );

			if( ! isset( $this->index_ID_rows[$plugin_ID] ) || !$this->index_ID_rows[$plugin_ID] )
			{ // no plugin rows cached
				#debug_die( 'Cannot instantiate Plugin (ID '.$plugin_ID.') without DB information.' );
				$Debuglog->add( 'get_by_ID(): Plugin (ID '.$plugin_ID.') not registered in DB!', array( 'plugins', 'error' ) );
				$r = false;
				return $r;
			}

			$row = & $this->index_ID_rows[$plugin_ID];

			// Register the plugin:
			$Plugin = & $this->register( $row['plug_classname'], $row['plug_ID'], $row['plug_priority'], $row['plug_apply_rendering'] );

			if( is_string( $Plugin ) )
			{
				$Debuglog->add( 'Requested plugin [#'.$plugin_ID.'] not found!', 'plugins' );
				$r = false;
				return $r;
			}

			$this->index_ID_Plugins[ $plugin_ID ] = & $Plugin;
		}

		return $this->index_ID_Plugins[ $plugin_ID ];
	}


	/**
	 * Get a specific Plugin by its code.
	 *
	 * @param string plugin name
	 * @return Plugin|false
	 */
	function & get_by_code( $plugin_code )
	{
		global $Debuglog;

		$r = false;

		if( ! isset($this->index_code_Plugins[ $plugin_code ]) )
		{ // Plugin is not registered yet
			$this->load_plugins_table();

			if( ! isset($this->index_code_ID[ $plugin_code ]) )
			{
				$Debuglog->add( 'Requested plugin ['.$plugin_code.'] is not registered!', 'plugins' );
				return $r;
			}

			if( ! $this->get_by_ID( $this->index_code_ID[$plugin_code] ) )
			{
				$Debuglog->add( 'Requested plugin ['.$plugin_code.'] could not be instantiated!', 'plugins' );
				return $r;
			}
		}

		return $this->index_code_Plugins[ $plugin_code ];
	}


	/**
	 * Get a list of Plugins for a given event.
	 *
	 * @param string Event name
	 * @return array of Plugins
	 */
	function get_list_by_event( $event )
	{
		$r = array();

		if( isset($this->index_event_IDs[$event]) )
		{
			foreach( $this->index_event_IDs[$event] as $l_plugin_ID )
			{
				if( $Plugin = & $this->get_by_ID( $l_plugin_ID ) )
				{
					$r[ $l_plugin_ID ] = & $Plugin;
				}
			}
		}

		return $r;
	}


	/**
	 * Get a list of Plugins for a list of events. Every Plugin is only once in this list.
	 *
	 * @param array Array of events
	 * @return array List of Plugins
	 */
	function get_list_by_events( $events )
	{
		$r = array();

		foreach( $events as $l_event )
		{
			foreach( array_keys($this->get_list_by_event( $l_event )) as $l_plugin_ID )
			{
				if( $Plugin = & $this->get_by_ID( $l_plugin_ID ) )
				{
					$r[ $l_plugin_ID ] = & $Plugin;
				}
			}
		}

		return $r;
	}


	/**
	 * Get a list of plugins that provide all given events.
	 *
	 * @return array
	 */
	function get_list_by_all_events( $events )
	{
		$candidates = array();

		foreach( $events as $l_event )
		{
			if( empty($this->index_event_IDs[$l_event]) )
			{
				return array();
			}

			if( empty($candidates) )
			{
				$candidates = $this->index_event_IDs[$l_event];
			}
			else
			{
				$candidates = array_intersect( $candidates, $this->index_event_IDs[$l_event] );
				if( empty($candidates) )
				{
					return array();
				}
			}
		}

		$r = array();
		foreach( $candidates as $plugin_ID )
		{
			$Plugin = & $this->get_by_ID( $plugin_ID );
			if( $Plugin )
			{
				$r[ $plugin_ID ] = & $Plugin;
			}
		}

		return $r;
	}


	/**
	 * Get a list of (enabled) events for a given Plugin ID.
	 *
	 * @param integer Plugin ID
	 * @return array
	 */
	function get_enabled_events( $plugin_ID )
	{
		$r = array();
		foreach( $this->index_event_IDs as $l_event => $l_plugin_IDs )
		{
			if( in_array( $plugin_ID, $l_plugin_IDs ) )
			{
				$r[] = $l_event;
			}
		}
		return $r;
	}


	/**
	 * (Re)load Plugin Events.
	 */
	function load_events()
	{
		global $Debuglog, $DB;

		$this->index_event_IDs = array();

		$Debuglog->add( 'Loading plugin events.', 'plugins' );
		foreach( $DB->get_results( '
				SELECT pevt_plug_ID, pevt_event
					FROM T_pluginevents INNER JOIN T_plugins ON pevt_plug_ID = plug_ID
				 WHERE pevt_enabled > 0
				 ORDER BY plug_priority', OBJECT, 'Loading plugin events' ) as $l_row )
		{
			$this->index_event_IDs[$l_row->pevt_event][] = $l_row->pevt_plug_ID;
		}
	}


	/**
	 * Get a list of methods that are supported as events out of the Plugin's
	 * source file.
	 *
	 * @return array
	 */
	function get_registered_events( $Plugin )
	{
		global $Timer;

		if( ! function_exists( 'token_get_all' ) )
		{
			debug_die( 'We need the PHP Tokenizer functions to get the list of Plugin events (Enabled by default since PHP 4.3.0 and available since PHP 4.2.0).' );
		}

		$Timer->resume( 'plugins_detect_events' );

		$plugin_class_methods = array(); // Return value

		$filename = $this->plugins_path.'_'.str_replace( '_plugin', '.plugin', $Plugin->classname ).'.php';
		$classfile_contents = file_get_contents( $filename );

		$token_buffer = '';
		$classname = '';
		$in_class_name = false;
		$in_plugin_class = false;
		$in_function_name = false;
		foreach( token_get_all($classfile_contents) as $l_token )
		{
			if( $l_token[0] == T_COMMENT || $l_token[0] == T_WHITESPACE )
			{
				continue;
			}

			if( $in_plugin_class )
			{
				if( $l_token[0] == T_FUNCTION )
				{
					$in_function_name = true;
					$token_buffer = '';
				}
				elseif( $in_function_name )
				{
					if( $l_token[0] == T_STRING )
					{
						$token_buffer .= $l_token[1];
					}
					else
					{
						$plugin_class_methods[] = trim($token_buffer);
						$in_function_name = false;
					}
				}
			}
			elseif( $in_class_name )
			{
				if( $l_token[0] == T_STRING )
				{
					$token_buffer .= $l_token[1];
				}
				else
				{
					$classname = trim($token_buffer);
					$in_plugin_class = ( $classname == $Plugin->classname );
					$in_class_name = false;
				}
			}
			elseif( $l_token[0] == T_CLASS )
			{
				$in_class_name = true;
				$token_buffer = '';
			}
		}

		$supported_events = $this->get_supported_events();
		$supported_events = array_keys($supported_events);
		$verified_events = array_intersect( $plugin_class_methods, $supported_events );

		$Timer->pause( 'plugins_detect_events' );

		// TODO: Report, when difference in $events_verified and what getRegisteredEvents() returned
		return $verified_events;
	}


	/**
	 * Save the events that the plugin provides into DB, while removing obsolete
	 * entries (that the plugin does not register anymore).
	 *
	 * @param Plugin Plugin to save events for
	 * @param array|NULL List of events to save as enabled for the Plugin.
	 *              By default all provided events get saved as enabled. Pass array() to discover only new ones.
	 * @param array List of events to save as disabled for the Plugin.
	 *              By default, no events get disabled. Disabling an event takes priority over enabling.
	 * @return boolean True, if events have changed, false if not.
	 */
	function save_events( $Plugin, $enable_events = NULL, $disable_events = NULL )
	{
		global $DB, $Debuglog;

		$r = false;

		$saved_events = array();
		foreach( $DB->get_results( 'SELECT pevt_event, pevt_enabled FROM T_pluginevents WHERE pevt_plug_ID = '.$Plugin->ID ) as $l_row )
		{
			$saved_events[$l_row->pevt_event] = $l_row->pevt_enabled;
		}
		$available_events = $this->get_registered_events( $Plugin );
		$obsolete_events = array_diff( array_keys($saved_events), $available_events );

		if( is_null( $enable_events ) )
		{ // Enable all events:
			$enable_events = $available_events;
		}
		if( is_null( $disable_events ) )
		{
			$disable_events = array();
		}
		if( $disable_events )
		{ // Remove events to be disabled from enabled ones:
			$enable_events = array_diff( $enable_events, $disable_events );
		}

		// New discovered events:
		$discovered_events = array_diff( $available_events, array_keys($saved_events), $enable_events, $disable_events );


		// Delete obsolete events from DB:
		if( $obsolete_events && $DB->query( '
				DELETE FROM T_pluginevents
				WHERE pevt_plug_ID = '.$Plugin->ID.'
					AND pevt_event IN ( "'.implode( '", "', $obsolete_events ).'" )' ) )
		{
			$r = true;
		}

		if( $discovered_events )
		{
			$DB->query( '
				INSERT INTO T_pluginevents( pevt_plug_ID, pevt_event, pevt_enabled )
				VALUES ( '.$Plugin->ID.', "'.implode( '", 1 ), ('.$Plugin->ID.', "', $discovered_events ).'", 1 )' );
			$r = true;

			$Debuglog->add( 'Discovered events ['.implode( ', ', $discovered_events ).'] for Plugin '.$Plugin->name, 'plugins' );
		}

		if( $enable_events )
		{
			$new_events = array();
			foreach( $enable_events as $l_event )
			{
				if( ! isset( $saved_events[$l_event] ) || ! $saved_events[$l_event] )
				{ // Event not saved yet or not enabled
					$new_events[] = $l_event;
				}
			}
			if( $new_events )
			{
				$DB->query( '
					REPLACE INTO T_pluginevents( pevt_plug_ID, pevt_event, pevt_enabled )
					VALUES ( '.$Plugin->ID.', "'.implode( '", 1 ), ('.$Plugin->ID.', "', $new_events ).'", 1 )' );
				$r = true;
			}
			$Debuglog->add( 'Enabled events ['.implode( ', ', $new_events ).'] for Plugin '.$Plugin->name, 'plugins' );
		}

		if( $disable_events )
		{
			$new_events = array();
			foreach( $disable_events as $l_event )
			{
				if( ! isset( $saved_events[$l_event] ) || $saved_events[$l_event] )
				{ // Event not saved yet or enabled
					$new_events[] = $l_event;
				}
			}
			if( $new_events )
			{
				$DB->query( '
					REPLACE INTO T_pluginevents( pevt_plug_ID, pevt_event, pevt_enabled )
					VALUES ( '.$Plugin->ID.', "'.implode( '", 0 ), ('.$Plugin->ID.', "', $new_events ).'", 0 )' );
				$r = true;
			}
			$Debuglog->add( 'Disabled events ['.implode( ', ', $new_events ).'] for Plugin '.$Plugin->name, 'plugins' );
		}

		if( $r )
		{ // Something has changed: Reload event index
			$this->load_events();
		}

		return $r;
	}


	/**
	 * Load an object from a Cache plugin or create a new one if we have a
	 * cache miss or no caching plugins.
	 *
	 * It registers a shutdown function, that refreshes the data to the cache plugin
	 * which is not optimal, but we have no hook to see if data retrieved from
	 * a {@link DataObjectCache} derived class has changed.
	 * @param string object name
	 * @param string eval this to create the object. Default is to create an object
	 *               of class $objectName.
	 */
	function get_object_from_cacheplugin_or_create( $objectName, $eval_create_object = NULL )
	{
		$get_return = $this->trigger_event_first_true( 'CacheObjects',
			array( 'action' => 'get', 'key' => 'object_'.$objectName ) );

		if( isset( $get_return['plugin_ID'] ) )
		{
			$GLOBALS[$objectName] = & $get_return['data'];

			$Plugin = & $this->get_by_ID( $get_return['plugin_ID'] );
			register_shutdown_function( array(&$Plugin, 'CacheObjects'),
				array( 'action' => 'set', 'key' => 'object_'.$objectName, 'data' => & $GLOBALS[$objectName] ) );
		}
		else
		{ // Cache miss, create it:
			if( empty($eval_create_object) )
			{
				$GLOBALS[$objectName] = & new $objectName();
			}
			else
			{
				eval( '$GLOBALS[\''.$objectName.'\'] = '.$eval_create_object.';' );
			}

			// Try to set in cache:
			$set_return = $this->trigger_event_first_true( 'CacheObjects',
				array( 'action' => 'set', 'key' => 'object_'.$objectName, 'data' => & $GLOBALS[$objectName] ) );

			if( isset( $set_return['plugin_ID'] ) )
			{ // success, register a shutdown function to save this data on shutdown
				$Plugin = & $this->get_by_ID( $set_return['plugin_ID'] );
				register_shutdown_function( array(&$Plugin, 'CacheObjects'),
					array( 'action' => 'set', 'key' => 'object_'.$objectName, 'data' => & $GLOBALS[$objectName] ) );
			}
		}
	}


	/**
	 * Display the fieldset for the Plugin Settings.
	 *
	 * @uses display_settings_fieldset_field() for each field to allow recursion for array type settings
	 * @param Plugin (by reference)
	 * @param Form (by reference)
	 */
	function display_settings_fieldset( & $Plugin, & $Form )
	{
		if( ! $Plugin->Settings )
		{
			return false;
		}

		$Form->begin_fieldset( T_('Plugin settings'), array( 'class' => 'clear' ) );

		foreach( $Plugin->GetDefaultSettings() as $l_name => $l_meta )
		{
			$this->display_settings_fieldset_field( $l_name, $l_meta, $Plugin, $Form );
		}
		$Form->end_fieldset();
	}


	/**
	 * Display a single field (setting) of the Plugin's Settings.
	 *
	 * @param string Settings name (key)
	 * @param array Meta data for this setting. See {@link Plugin::GetDefaultSettings()}
	 * @param Plugin (by reference)
	 * @param Form (by reference)
	 * @param mixed Value to really use (used for recursion into array type settings)
	 */
	function display_settings_fieldset_field( $set_name, $set_meta, & $Plugin, & $Form, $use_value = NULL )
	{
		global $debug;

		$params = array();

		if( isset($set_meta['maxlength']) )
		{
			$params['maxlength'] = (int)$set_meta['maxlength'];
		}
		if( isset($set_meta['note']) )
		{
			$params['note'] = $set_meta['note'];
		}

		if( ! isset($set_meta['type']) )
		{
			$set_meta['type'] = 'text';
		}

		// Display input element:
		switch( $set_meta['type'] )
		{
			case 'checkbox':
				$Form->checkbox_input( 'edited_plugin_set_'.$set_name,
					isset($use_value) ? $use_value : $Plugin->Settings->get($set_name),
					$set_meta['label'],
					$params );
				break;

			case 'textarea':
				$textarea_rows = isset($set_meta['rows']) ? $set_meta['rows'] : 3;
				$Form->textarea_input( 'edited_plugin_set_'.$set_name,
					isset($use_value) ? $use_value : $Plugin->Settings->get($set_name),
					$textarea_rows,
					$set_meta['label'],
					$params );
				break;

			case 'array':
				$Form->begin_fieldset( (isset($set_meta['label']) ? $set_meta['label'] : '').( $debug ? ' [debug: '.$set_name.']' : '' ) );

				$plugin_value = isset($use_value) ? $use_value : $Plugin->Settings->get_unserialized( $set_name );

				$count = 0;
				foreach( $set_meta['entries'] as $l_set_name => $l_set_entry )
				{
					$l_value = isset($plugin_value[$count][$l_set_name]) ? $plugin_value[$count][$l_set_name] : NULL;
					$this->display_settings_fieldset_field( $set_name.'['.$count.']['.$l_set_name.']', $l_set_entry, $Plugin, $Form, $l_value );
				}
				$count++;
				$Form->end_fieldset();

				// if not max_number
				$Form->info_field( '', '<a href="'.regenerate_url().'">'.T_('Add another set').'</a>' );

				break;

			case 'password':
				$params['type'] = 'password'; // same as text input, but type=password

			case 'text':
				// Default: "text input"
				if( isset($set_meta['size']) )
				{
					$size = (int)$set_meta['size'];
				}
				else
				{ // Default size:
					$size = 25;
				}
				if( ! isset($params['maxlength']) || $params['maxlength'] > 255 )
				{ // T_pluginsettings.pset_value can hold 255 chars only
					$params['maxlength'] = 255;
				}

				$Form->text_input( 'edited_plugin_set_'.$set_name,
					isset($use_value) ? $use_value : $Plugin->Settings->get($set_name),
					$size,
					$set_meta['label'],
					$params );
				break;

			default:
				debug_die( 'Unsupported type from GetDefaultSettings()!' );
		}
	}
}


/**
 * A sub-class of {@link Plugins}, just to not load any DB info.
 *
 * This is only useful for displaying a list of available plugins!
 *
 * {@internal This is probably quicker and cleaner than using a member boolean in {@link Plugins} itself.}}
 */
class Plugins_no_DB extends Plugins
{
	/**
	 * No-operation.
	 */
	function load_plugins_table()
	{
	}

	/**
	 * No-operation.
	 */
	function load_events()
	{
	}
}


/*
 * $Log$
 * Revision 1.40  2006/01/28 17:07:32  blueyed
 * Moved set_empty_code_to_default() to caller.
 *
 * Revision 1.39  2006/01/27 15:10:13  fplanque
 * no message
 *
 * Revision 1.38  2006/01/26 23:47:27  blueyed
 * Added password settings type.
 *
 * Revision 1.37  2006/01/26 23:08:36  blueyed
 * Plugins enhanced.
 *
 * Revision 1.36  2006/01/26 20:27:45  blueyed
 * minor
 *
 * Revision 1.35  2006/01/25 23:37:57  blueyed
 * bugfixes
 *
 * Revision 1.34  2006/01/23 01:09:03  blueyed
 * Added get_list_by_all_events()
 *
 * Revision 1.33  2006/01/23 00:57:39  blueyed
 * Cleanup, forgot trigger_event_first_true() :/
 *
 * Revision 1.32  2006/01/21 16:34:56  blueyed
 * Fixed get_list_by_events()
 *
 * Revision 1.31  2006/01/20 00:45:32  blueyed
 * Moved "Uninstall" plugin hook to /admin/plugins.php
 *
 * Revision 1.30  2006/01/20 00:42:18  blueyed
 * Fixes
 *
 * Revision 1.29  2006/01/15 23:59:13  blueyed
 * Added Plugins::call_method_if_active()
 *
 * Revision 1.28  2006/01/15 15:29:46  blueyed
 * set_code(): handle empty codes correctly
 *
 * Revision 1.27  2006/01/15 13:59:15  blueyed
 * Cleanup
 *
 * Revision 1.26  2006/01/15 13:16:26  blueyed
 * Dynamically unregister a non-existing (filename/classname) plugin.
 *
 * Revision 1.25  2006/01/11 21:06:26  blueyed
 * Fix/cleanup $index_event_IDs handling: gets sorted by priority now. Should finally close http://dev.b2evolution.net/todo.php/2006/01/09/wacko_formatting_plugin_does_not_work_an
 *
 * Revision 1.24  2006/01/11 17:32:52  fplanque
 * wording / translation
 *
 * Revision 1.23  2006/01/11 01:23:59  blueyed
 * Fixes
 *
 * Revision 1.22  2006/01/09 18:17:42  blueyed
 * validate_list() need to call load_plugin_tables(); also fixed it for opt-in and lazy renderers.
 * Fixes http://dev.b2evolution.net/todo.php/2006/01/09/wacko_formatting_plugin_does_not_work_an
 *
 * Revision 1.21  2006/01/06 18:58:08  blueyed
 * Renamed Plugin::apply_when to $apply_rendering; added T_plugins.plug_apply_rendering and use it to find Plugins which should apply for rendering in Plugins::validate_list().
 *
 * Revision 1.20  2006/01/06 00:27:06  blueyed
 * Small enhancements to new Plugin system
 *
 * Revision 1.19  2006/01/04 15:03:52  fplanque
 * enhanced list sorting capabilities
 *
 * Revision 1.18  2005/12/29 20:19:58  blueyed
 * Renamed T_plugin_settings to T_pluginsettings
 *
 * Revision 1.17  2005/12/23 19:06:35  blueyed
 * Advanced enabling/disabling of plugin events.
 *
 * Revision 1.16  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.15  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.14  2005/11/29 14:42:28  blueyed
 * todo
 *
 * Revision 1.13  2005/11/25 15:45:39  blueyed
 * Hide passwords in Debuglog! (for event LoginAttempt)
 *
 * Revision 1.12  2005/11/24 20:43:56  blueyed
 * Timer, doc
 *
 * Revision 1.11  2005/09/18 01:46:55  blueyed
 * Fixed E_NOTICE for return by reference (PHP 4.4.0)
 *
 * Revision 1.10  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.9  2005/03/02 18:44:27  fplanque
 * comments
 *
 * Revision 1.8  2005/03/02 17:07:34  blueyed
 * no message
 *
 * Revision 1.7  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.6  2005/02/21 00:48:15  blueyed
 * parse error fixed
 *
 * Revision 1.5  2005/02/20 22:41:44  blueyed
 * sort(), use method_exists() for trigger_event, sort_Plugin_name() added, doc, whitespace
 *
 * Revision 1.4  2005/02/08 04:45:02  blueyed
 * improved $DB get_results() handling
 *
 * Revision 1.3  2004/12/17 20:41:14  fplanque
 * cleanup
 *
 * Revision 1.2  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.6  2004/10/12 16:12:18  fplanque
 * Edited code documentation.
 *
 */
?>
<?php
/**
 * This file implements the PluginS class.
 *
 * This is where you can plug in some {@link Plugin plugins} :D
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
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * This is where you can plug in some {@link Plugin plugins} :D
 *
 * @todo A plugin might want to register allowed events (that it triggers itself) on installation..
 * @package evocore
 */
class Plugins
{
	/**#@+
	 * @access private
	 */

	/**
	 * @var array Our API version as (major, minor). A Plugin can request a check against it through {@link Plugin::GetDependencies()}.
	 */
	var $api_version = array( 1, 2 );

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
	 * Cache Plugin codes by apply_rendering setting.
	 * @var array
	 */
	var $index_apply_rendering_codes = array();

	/**
	 * Path to plugins.
	 *
	 * The preferred method is to have a sub-directory for each plugin (named
	 * after the plugin's classname), but they can be supplied just in this
	 * directory.
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
	 * @var array
	 */
	var $_supported_events;

	/**#@-*/


	/**#@+
	 * @access protected
	 */

	/**
	 * @var string SQL to use in {@link load_plugins_table()}. Gets overwritten for {@link Plugins_admin}.
	 * @static
	 */
	var $sql_load_plugins_table = '
			SELECT plug_ID, plug_priority, plug_classname, plug_code, plug_name, plug_shortdesc, plug_apply_rendering, plug_status, plug_version, plug_spam_weight
			  FROM T_plugins
			 WHERE plug_status = "enabled"
			 ORDER BY plug_priority, plug_classname';

	/**
	 * @var boolean Used in class {@link Plugins} when it comes to removing plugins from the list, what we don't want
	 *                when administering plugins (see {@link $Plugins_admin}).
	 */
	var $is_admin_class = false;
	/**#@-*/


	/**
	 * Errors associated to plugins (during loading), indexed by plugin_ID and
	 * error class ("register").
	 *
	 * @var array
	 */
	var $plugin_errors = array();


	/**
	 * Constructor. Sets {@link $plugins_path} and load events.
	 */
	function Plugins()
	{
		global $basepath, $plugins_subdir;
		global $DB, $Debuglog, $Timer;

		// Set plugin path:
		$this->plugins_path = $basepath.$plugins_subdir;

		$Timer->resume( 'plugin_init' );

		$this->load_events();

		$Timer->pause( 'plugin_init' );
	}


	/**
	 * Get the list of supported/available events/hooks.
	 *
	 * Additional to the returned event methods (which can be disabled), there are internal
	 * ones which just get called on the plugin (and get not remembered in T_pluginevents), e.g.:
	 *  - AfterInstall
	 *  - BeforeEnable
	 *  - BeforeDisable
	 *  - BeforeInstall
	 *  - BeforeUninstall
	 *  - BeforeUninstallPayload
	 *  - DisplaySkin (called on a skin from {@link GetProvidedSkins()})
	 *  - ExecCronJob
	 *  - GetDefaultSettings
	 *  - GetDefaultUserSettings
	 *  - GetExtraEvents
	 *  - GetHtsrvMethods
	 *  - PluginInit
	 *  - PluginSettingsUpdateAction (Called as action before updating the plugin's settings)
	 *  - PluginSettingsEditAction (Called as action before editing the plugin's settings)
	 *  - PluginSettingsEditDisplayAfter (Called after standard plugin settings are displayed for editing)
	 *  - PluginSettingsValidateSet (Called before setting a plugin's setting in the backoffice)
	 *  - PluginUserSettingsUpdateAction (Called as action before updating the plugin's user settings)
	 *  - PluginUserSettingsEditAction (Called as action before editing the plugin's settings)
	 *  - PluginUserSettingsEditDisplayAfter (Called after displaying normal user settings)
	 *  - PluginUserSettingsValidateSet (Called before setting a plugin's user setting in the backoffice)
	 *  - PluginVersionChanged (Called when we detect a version change)
	 *
	 *  The max length of event names is 40 chars (T_pluginevents.pevt_event).
	 *
	 * @todo Finish/Complete descriptions
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
				'AdminDisplayItemFormFieldset' => '',
				'AdminEndHtmlHead' => '',
				'AdminAfterMenuInit' => '',
				'AdminTabAction' => '',
				'AdminTabPayload' => '',
				'AdminToolAction' => '',
				'AdminToolPayload' => '',

				'AdminBeforeItemEditCreate' => 'This gets called before a new item gets created.',
				'AdminBeforeItemEditUpdate' => 'This gets called before an existing item gets updated.',

				'AdminBeginPayload' => '',

				'CacheObjects' => 'Cache data objects.',
				'CachePageContent' => 'Cache page content.',
				'CacheIsCollectingContent' => 'Gets asked for if we are generating cached content.',

				'AfterCommentDelete' => 'Gets called after a comment has been deleted from the database.',
				'AfterCommentInsert' => 'Gets called after a comment has been inserted into the database.',
				'AfterCommentUpdate' => 'Gets called after a comment has been updated in the database.',

				'AfterItemDelete' => '',
				'PrependItemInsertTransact' => '',
				'AfterItemInsert' => '',
				'PrependItemUpdateTransact' => '',
				'AfterItemUpdate' => '',
				'AppendItemPreviewTransact' => '',

				'RenderItemAsHtml' => 'Renders content when generated as HTML.',
				'RenderItemAsXml' => 'Renders content when generated as XML.',
				'RenderItemAsText' => 'Renders content when generated as plain text.',

				'FilterCommentAuthor' => 'Filters the comment author.',
				'FilterCommentAuthorUrl' => 'Filters the URL of the comment author.',
				'FilterCommentContent' => 'Filters the content of a comment.',

				'AfterUserDelete' => '',
				'AfterUserInsert' => '',
				'AfterUserUpdate' => '',

				'DisplayItemAsHtml' => 'Called on an item when it gets displayed as HTML.',
				'DisplayItemAsXml' => 'Called on an item when it gets displayed as XML.',
				'DisplayItemAsText' => 'Called on an item when it gets displayed as text.',

				'FilterIpAddress' => 'Called when displaying an IP address.',

				'ItemApplyAsRenderer' => 'Asks the plugin if it wants to apply as a renderer for an item.',
				'ItemCanComment' => 'Asks the plugin if an item can receive comments/feedback.',
				'ItemSendPing' => 'Send a ping to a service about new items.',
				'ItemViewsIncreased' => 'Called when the view counter of an item got increased.',

				'SkinTag' => '',

				'AppendHitLog' => 'Called when a hit gets logged, but before it gets recorded.',

				'DisplayCommentFormButton' => '',
				'DisplayCommentFormFieldset' => '',
				'DisplayMessageFormButton' => '',
				'DisplayMessageFormFieldset' => '',
				'DisplayLoginFormFieldset' => 'Called when displaying the "Login" form.',
				'DisplayRegisterFormFieldset' => 'Called when displaying the "Register" form.',
				'DisplayValidateAccountFormFieldset' => 'Called when displaying the "Validate account" form.',

				'BeforeCommentFormInsert' => 'Called before a comment gets recorded through the public comment form.',
				'AfterCommentFormInsert' => 'Called after a comment has been added through public form.',

				'BeforeTrackbackInsert' => 'Gets called before a trackback gets recorded.',
				'AfterTrackbackInsert' => 'Gets called after a trackback has been recorded.',

				'LoginAttempt' => 'Called when a user tries to login.',
				'LoginAttemptNeedsRawPassword' => 'A plugin has to return true here, if it needs a raw (un-hashed) password in LoginAttempt.',
				'AlternateAuthentication' => '',
				'MessageFormSent' => 'Called when the "Message to user" form has been submitted.',
				'MessageFormSentCleanup' => 'Called after a email message has been sent through public form.',
				'Logout' => 'Called when a user logs out.',

				'GetSpamKarmaForComment' => 'Asks plugin for the spam karma of a comment/trackback.',

				// Other Plugins can use this:
				'CaptchaValidated' => 'Validate the test from CaptchaPayload to detect humans.',
				'CaptchaValidatedCleanup' => 'Cleanup data used for CaptchaValidated.',
				'CaptchaPayload' => 'Provide a turing test to detect humans.',

				'RegisterFormSent' => 'Called when the "Register" form has been submitted.',
				'ValidateAccountFormSent' => 'Called when the "Validate account" form has been submitted.',
				'AppendUserRegistrTransact' => 'Gets appended to the transaction that creates a new user on registration.',
				'AfterUserRegistration' => 'Gets called after a new user has registered.',

				'SessionLoaded' => '', // gets called after $Session is initialized, quite early.

				'AfterLoginAnonymousUser' => 'Gets called at the end of the login procedure for anonymous visitors.',
				'AfterLoginRegisteredUser' => 'Gets called at the end of the login procedure for registered users.',

				'BeforeBlogDisplay' => 'Gets called before a (part of the blog) gets displayed.',
				'SkinBeginHtmlHead' => 'Gets called at the top of the HTML HEAD section in a skin.',
				'DisplayTrackbackAddr' => '',

				'GetCronJobs' => 'Gets a list of implemented cron jobs.',
				'GetProvidedSkins' => 'Get a list of "skins" handled by the plugin.',
			);


			if( ! defined('EVO_IS_INSTALL') || ! EVO_IS_INSTALL )
			{ // only call this, if we're not in the process of installation, to avoid errors from Plugins in this case!
				// Let Plugins add additional events (if they trigger those events themselves):
				$this->load_plugins_table();

				$rev_sorted_IDs = array_reverse( $this->sorted_IDs ); // so higher priority overwrites lower (just for desc)

				foreach( $rev_sorted_IDs as $plugin_ID )
				{
					$Plugin = & $this->get_by_ID( $plugin_ID );

					if( ! $Plugin )
					{
						continue;
					}

					$extra_events = $Plugin->GetExtraEvents();
					if( is_array($extra_events) )
					{
						$this->_supported_events = array_merge( $this->_supported_events, $extra_events );
					}
				}
			}
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
		global $Debuglog, $Timer;

		$Timer->resume('plugins_discover');

		$Debuglog->add( 'Discovering plugins...', 'plugins' );

		// Go through directory:
		$this_dir = dir( $this->plugins_path );
		while( $this_file = $this_dir->read() )
		{
			if( preg_match( '/^_(.+)\.plugin\.php$/', $this_file, $match ) && is_file( $this->plugins_path.$this_file ) )
			{ // Plugin class name in plugins/
				$classname = $match[1].'_plugin';

				$this->register( $classname, 0 ); // auto-generate negative ID; will return string on error.
			}
			elseif( preg_match( '/^(.+)\_plugin$/', $this_file, $match )
				&& is_dir( $this->plugins_path.$this_file )
				&& is_file( $this->plugins_path.$this_file.'/_'.$match[1].'.plugin.php' ) )
			{ // Plugin class name in plugins/<plug_classname>/
				$classname = $match[1].'_plugin';
				$filepath = $this->plugins_path.$this_file.'/_'.$match[1].'.plugin.php';

				$this->register( $classname, 0, -1, NULL, $filepath );
			}
		}

		$Timer->pause('plugins_discover');
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

		foreach( $this->sorted_IDs as $k => $plugin_ID )
		{ // Instantiate every plugin, so invalid ones do not get unregistered during sorting (crashes PHP, because $sorted_IDs gets changed etc)
			if( ! $this->get_by_ID( $plugin_ID ) )
			{
				unset($this->sorted_IDs[$k]);
			}
		}

		switch( $order )
		{
			case 'name':
				usort( $this->sorted_IDs, array( & $this, 'sort_Plugin_name') );
				break;

			case 'group':
				usort( $this->sorted_IDs, array( & $this, 'sort_Plugin_group') );
				break;

			default:
				// Sort array by priority:
				usort( $this->sorted_IDs, array( & $this, 'sort_Plugin_priority') );
		}

		$this->current_idx = 0;
	}

	/**
	 * Callback function to sort plugins by priority (and classname, if they have same priority).
	 */
	function sort_Plugin_priority( & $a_ID, & $b_ID )
	{
		$a_Plugin = & $this->get_by_ID( $a_ID );
		$b_Plugin = & $this->get_by_ID( $b_ID );

		$r = $a_Plugin->priority - $b_Plugin->priority;

		if( $r == 0 )
		{
			$r = strcasecmp( $a_Plugin->classname, $b_Plugin->classname );
		}

		return $r;
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
	 * Callback function to sort plugins by group, sub-group and name.
	 *
	 * Those, which have a group get sorted above the ones without one.
	 *
	 * WARNING: do NOT sort by anything else than priority unless you're handling a list of NOT-YET-INSTALLED plugins
	 */
	function sort_Plugin_group( & $a_ID, & $b_ID )
	{
		$a_Plugin = & $this->get_by_ID( $a_ID );
		$b_Plugin = & $this->get_by_ID( $b_ID );

		// first check if both have a group (-1: only A has a group; 1: only B has a group; 0: both have a group or no group):
		$r = (int)empty($a_Plugin->group) - (int)empty($b_Plugin->group);
		if( $r != 0 )
		{
			return $r;
		}

		// Compare Group
		$r = strcasecmp( $a_Plugin->group, $b_Plugin->group );
		if( $r != 0 )
		{
			return $r;
		}

		// Compare Sub Group
		$r = strcasecmp( $a_Plugin->sub_group, $b_Plugin->sub_group );
		if( $r != 0 )
		{
			return $r;
		}

		// Compare Name
		return strcasecmp( $a_Plugin->name, $b_Plugin->name );
	}


	/**
	 * Get a list of available Plugin groups.
	 *
	 * @return array
	 */
	function get_plugin_groups()
	{
		$result = array();

		foreach( $this->sorted_IDs as $plugin_ID )
		{
			$Plugin = & $this->get_by_ID( $plugin_ID );

			if( empty($Plugin->group) || in_array( $Plugin->group, $result ) )
			{
				continue;
			}

			$result[] = $Plugin->group;
		}

		return $result;
	}


	/**
	 * Will return an array that contents are references to plugins that have the same group, regardless of the sub_group.
	 *
	 * @return array
	 */
	function get_Plugins_in_group( $group )
	{
		$result = array();

		foreach( $this->sorted_IDs as $plugin_ID )
		{
			$Plugin = & $this->get_by_ID( $plugin_ID );
			if( $Plugin->group == $group )
			{
				$result[] = & $Plugin;
			}
		}

		return $result;
	}


	/**
	 * Will return an array that contents are references to plugins that have the same group and sub_group.
	 *
	 * @return array
	 */
	function get_Plugins_in_sub_group( $group, $sub_group = '' )
	{
		$result = array();

		foreach( $this->sorted_IDs as $plugin_ID )
		{
			$Plugin = & $this->get_by_ID( $plugin_ID );
			if( $Plugin->group == $group && $Plugin->sub_group == $sub_group )
			{
				$result[] = & $Plugin;
			}
		}

		return $result;
	}


	/**
	 * Sets the status of a Plugin in DB and registers it into the internal indices when "enabled".
	 * Otherwise it gets unregisters, but only when we're not in {@link Plugins_admin}, because we
	 * want to keep it in then in our indices.
	 *
	 * {@internal
	 * Note: this should probably always get called on the {@link $Plugins} object,
	 *       not {@link $admin_Plugins}.
	 * }}
	 *
	 * @param Plugin
	 * @param string New status ("enabled", "disabled", "needs_config", "broken")
	 */
	function set_Plugin_status( & $Plugin, $status )
	{
		global $DB, $Debuglog;

		$DB->query( 'UPDATE T_plugins SET plug_status = "'.$status.'" WHERE plug_ID = "'.$Plugin->ID.'"' );

		if( $status == 'enabled' )
		{ // Reload plugins tables, which includes the plugin in further requests
			$this->loaded_plugins_table = false;
			$this->load_plugins_table();
			$this->load_events();
		}
		else
		{
			// Notify the plugin that it has been disabled:
			$Plugin->BeforeDisable();

			if( ! $this->is_admin_class )
			{
				$this->unregister( $Plugin );
			}
		}

		$Plugin->status = $status;

		$Debuglog->add( 'Set status for plugin #'.$Plugin->ID.' to "'.$status.'"!', 'plugins' );
	}


	/**
	 * Install a plugin into DB.
	 *
	 * @param string Classname of the plugin to install
	 * @param string Initial DB Status of the plugin ("enbaled", "disabled", "needs_config", "broken")
	 * @param string|NULL Optional classfile path, if not default (used for tests).
	 * @return string|Plugin The installed Plugin (perhaps with $install_dep_notes set) or a string in case of error.
	 */
	function & install( $classname, $plug_status = 'enabled', $classfile_path = NULL )
	{
		global $DB, $Debuglog;

		$this->load_plugins_table();

		// Register the plugin:
		$Plugin = & $this->register( $classname, 0, -1, NULL, $classfile_path ); // Auto-generates negative ID; New ID will be set a few lines below

		if( is_string($Plugin) )
		{ // return error message from register()
			return $Plugin;
		}

		if( isset($Plugin->number_of_installs)
		    && ( $this->count_regs( $Plugin->classname ) >= $Plugin->number_of_installs ) )
		{
			$this->unregister( $Plugin );
			$r = T_('The plugin cannot be installed again.');
			return $r;
		}

		$install_return = $Plugin->BeforeInstall();
		if( $install_return !== true )
		{
			$this->unregister( $Plugin );
			$r = T_('The installation of the plugin failed.');
			if( is_string($install_return) )
			{
				$r .= '<br />'.$install_return;
			}
			return $r;
		}

		// Dependencies:
		if( $this->is_admin_class )
		{ // We must check dependencies against installed Plugins ($Plugins)
			global $Plugins;
			$dep_msgs = $Plugins->validate_dependencies( $Plugin, 'enable' );
		}
		else
		{
			$dep_msgs = $this->validate_dependencies( $Plugin, 'enable' );
		}
		if( ! empty( $dep_msgs['error'] ) )
		{ // required dependencies
			$this->unregister( $Plugin );
			$r = T_('Some plugin dependencies are not fulfilled:').' <ul><li>'.implode( '</li><li>', $dep_msgs['error'] ).'</li></ul>';
			return $r;
		}

		// All OK, install:
		if( empty($Plugin->code) )
		{
			$Plugin->code = NULL;
		}

		$Plugin->status = $plug_status;

		// Record into DB
		$DB->begin();

		$DB->query( '
				INSERT INTO T_plugins( plug_classname, plug_priority, plug_code, plug_apply_rendering, plug_version, plug_status )
				VALUES( "'.$classname.'", '.$Plugin->priority.', '.$DB->quote($Plugin->code).', '.$DB->quote($Plugin->apply_rendering).', '.$DB->quote($Plugin->version).', '.$DB->quote($Plugin->status).' ) ' );

		// Unset auto-generated ID info
		unset( $this->index_ID_Plugins[ $Plugin->ID ] );
		$key = array_search( $Plugin->ID, $this->sorted_IDs );

		// New ID:
		$Plugin->ID = $DB->insert_id;
		$this->index_ID_Plugins[ $Plugin->ID ] = & $Plugin;
		$this->index_ID_rows[ $Plugin->ID ] = array(
				'plug_ID' => $Plugin->ID,
				'plug_priority' => $Plugin->priority,
				'plug_classname' => $Plugin->classname,
				'plug_code' => $Plugin->code,
				'plug_apply_rendering' => $Plugin->apply_rendering,
				'plug_status' => $Plugin->status,
				'plug_version' => $Plugin->version,
			);
		$this->sorted_IDs[$key] = $Plugin->ID;

		$this->save_events( $Plugin );

		$DB->commit();

		$Debuglog->add( 'Installed plugin: '.$Plugin->name.' ID: '.$Plugin->ID, 'plugins' );

		if( ! empty($dep_msgs['note']) )
		{ // Add dependency notes
			$Plugin->install_dep_notes = $dep_msgs['note'];
		}

		// Do the stuff that we've skipped in register method at the beginning:

		// Instantiate the Plugins (User)Settings members:
		$this->instantiate_Settings( $Plugin, 'Settings' );
		$this->instantiate_Settings( $Plugin, 'UserSettings' );

		$tmp_params = array('db_row' => $this->index_ID_rows[$Plugin->ID], 'is_installed' => false);

		if( $Plugin->PluginInit( $tmp_params ) === false && ! $this->is_admin_class )
		{
			$Debuglog->add( 'Unregistered plugin, because PluginInit returned false.', 'plugins' );
			$this->unregister( $Plugin );
			$Plugin = '';
		}

		$this->sort();

		return $Plugin;
	}


	/**
	 * Uninstall a plugin.
	 *
	 * Removes the Plugin, its Settings and Events from the database.
	 *
	 * @return boolean True on success
	 */
	function uninstall( $plugin_ID )
	{
		global $DB, $Debuglog;

		$Debuglog->add( 'Uninstalling plugin (ID '.$plugin_ID.')...', 'plugins' );

		$Plugin = & $this->get_by_ID( $plugin_ID ); // get the Plugin before any not loaded data might get deleted below

		$DB->begin();

		// Delete Plugin settings (constraints)
		$DB->query( "DELETE FROM T_pluginsettings
		              WHERE pset_plug_ID = $plugin_ID" );

		// Delete Plugin user settings (constraints)
		$DB->query( "DELETE FROM T_pluginusersettings
		              WHERE puset_plug_ID = $plugin_ID" );

		// Delete Plugin events (constraints)
		foreach( $DB->get_col( '
				SELECT pevt_event
				  FROM T_pluginevents
				 WHERE pevt_enabled = 1' ) as $event )
		{
			if( strpos($event, 'RenderItemAs') === 0 )
			{ // Clear pre-rendered content cache, if RenderItemAs* events get removed:
				$DB->query( 'DELETE FROM T_item__prerendering WHERE 1' );
				$ItemCache = & get_Cache( 'ItemCache' );
				$ItemCache->clear();
				break;
			}
		}
		$DB->query( "DELETE FROM T_pluginevents
		              WHERE pevt_plug_ID = $plugin_ID" );

		// Delete from DB
		$DB->query( "DELETE FROM T_plugins
		              WHERE plug_ID = $plugin_ID" );

		$DB->commit();

		if( $Plugin )
		{
			$this->unregister( $Plugin );
		}

		$Debuglog->add( 'Uninstalled plugin (ID '.$plugin_ID.').', 'plugins' );
		return true;
	}


	/**
	 * Validate dependencies of a Plugin.
	 *
	 * @param Plugin
	 * @param string Mode of check: either 'enable' or 'disable'
	 * @return array The key 'note' holds an array of notes (recommendations), the key 'error' holds a list
	 *               of messages for dependency errors.
	 */
	function validate_dependencies( & $Plugin, $mode )
	{
		global $DB, $app_name;

		$msgs = array();

		if( $mode == 'disable' )
		{ // Check the whole list of installed plugins if they depend on our Plugin or it's (set of) events.
			$required_by_plugin = array(); // a list of plugin classnames that require our poor Plugin

			foreach( $this->sorted_IDs as $validate_against_ID )
			{
				if( $validate_against_ID == $Plugin->ID )
				{ // the plugin itself
					continue;
				}

				$against_Plugin = & $this->get_by_ID($validate_against_ID);

				if( $against_Plugin->status != 'enabled' )
				{ // The plugin is not enabled (this check is needed when checking deps with the Plugins_admin class)
					continue;
				}

				$deps = $against_Plugin->GetDependencies();

				if( empty($deps['requires']) )
				{ // has no dependencies
					continue;
				}

				if( ! empty($deps['requires']['plugins']) )
				{
					foreach( $deps['requires']['plugins'] as $l_req_plugin )
					{
						if( ! is_array($l_req_plugin) )
						{
							$l_req_plugin = array( $l_req_plugin, 0 );
						}

						if( $Plugin->classname == $l_req_plugin[0] )
						{ // our plugin is required by this one, check if it is the only instance
							if( $this->count_regs($Plugin->classname) < 2 )
							{
								$required_by_plugin[] = $against_Plugin->classname;
							}
						}
					}
				}

				if( ! empty($deps['requires']['events_by_one']) )
				{
					foreach( $deps['requires']['events_by_one'] as $req_events )
					{
						// Get a list of plugins that provide all the events
						$provided_by = array_keys( $this->get_list_by_all_events( $req_events ) );

						if( in_array($Plugin->ID, $provided_by) && count($provided_by) < 2 )
						{ // we're the only Plugin which provides this set of events
							$msgs['error'][] = sprintf( T_( 'The events %s are required by %s (ID %d).' ), implode_with_and($req_events), $against_Plugin->classname, $against_Plugin->ID );
						}
					}
				}

				if( ! empty($deps['requires']['events']) )
				{
					foreach( $deps['requires']['events'] as $req_event )
					{
						// Get a list of plugins that provide all the events
						$provided_by = array_keys( $this->get_list_by_event( $req_event ) );

						if( in_array($Plugin->ID, $provided_by) && count($provided_by) < 2 )
						{ // we're the only Plugin which provides this event
							$msgs['error'][] = sprintf( T_( 'The event %s is required by %s (ID %d).' ), $req_event, $against_Plugin->classname, $against_Plugin->ID );
						}
					}
				}

				// TODO: We might also handle the 'recommends' and add it to $msgs['note']
			}

			if( ! empty( $required_by_plugin ) )
			{ // Prepend the message to the beginning, because it's the most restrictive (IMHO)
				$required_by_plugin = array_unique($required_by_plugin);
				if( ! isset($msgs['error']) )
				{
					$msgs['error'] = array();
				}
				array_unshift( $msgs['error'], sprintf( T_('The plugin is required by the following plugins: %s.'), implode_with_and($required_by_plugin) ) );
			}

			return $msgs;
		}


		// mode 'enable':
		$deps = $Plugin->GetDependencies();

		if( empty($deps) )
		{
			return array();
		}

		foreach( $deps as $class => $dep_list ) // class: "requires" or "recommends"
		{
			if( ! is_array($dep_list) )
			{ // Invalid format: "throw" error (needs not translation)
				return array(
						'error' => array( 'GetDependencies() did not return array of arrays. Please contact the plugin developer.' )
					);
			}
			foreach( $dep_list as $type => $type_params )
			{
				switch( $type )
				{
					case 'events_by_one':
						foreach( $type_params as $sub_param )
						{
							if( ! is_array($sub_param) )
							{ // Invalid format: "throw" error (needs not translation)
								return array(
										'error' => array( 'GetDependencies() did not return array of arrays for "events_by_one". Please contact the plugin developer.' )
									);
							}
							if( ! $this->are_events_available( $sub_param, true ) )
							{
								if( $class == 'recommends' )
								{
									$msgs['note'][] = sprintf( T_( 'The plugin recommends a plugin which provides all of the following events: %s.' ), implode_with_and( $sub_param ) );
								}
								else
								{
									$msgs['error'][] = sprintf( T_( 'The plugin requires a plugin which provides all of the following events: %s.' ), implode_with_and( $sub_param ) );
								}
							}
						}
						break;

					case 'events':
						if( ! $this->are_events_available( $type_params, false ) )
						{
							if( $class == 'recommends' )
							{
								$msgs['note'][] = sprintf( T_( 'The plugin recommends plugins which provide the events: %s.' ), implode_with_and( $type_params ) );
							}
							else
							{
								$msgs['error'][] = sprintf( T_( 'The plugin requires plugins which provide the events: %s.' ), implode_with_and( $type_params ) );
							}
						}
						break;

					case 'plugins':
						if( ! is_array($type_params) )
						{ // Invalid format: "throw" error (needs not translation)
							return array(
									'error' => array( 'GetDependencies() did not return array of arrays for "plugins". Please contact the plugin developer.' )
								);
						}
						foreach( $type_params as $plugin_req )
						{
							if( ! is_array($plugin_req) )
							{
								$plugin_req = array( $plugin_req, '0' );
							}
							elseif( ! isset($plugin_req[1]) )
							{
								$plugin_req[1] = '0';
							}

							if( $versions = $DB->get_col( '
								SELECT plug_version FROM T_plugins
								 WHERE plug_classname = '.$DB->quote($plugin_req[0]).'
									 AND plug_status = "enabled"' ) )
							{
								// Clean up version from CVS Revision prefix/suffix:
								$versions[] = $plugin_req[1];
								$clean_versions = preg_replace( array( '~^(CVS\s+)?\$'.'Revision:\s*~i', '~\s*\$$~' ), '', $versions );
								$clean_req_ver = array_pop($clean_versions);
								usort( $clean_versions, 'version_compare' );
								$clean_oldest_enabled = array_shift($clean_versions);

								if( version_compare( $clean_oldest_enabled, $clean_req_ver, '<' ) )
								{ // at least one instance of the installed plugins is not the current version
									$msgs['error'][] = sprintf( T_( 'The plugin requires at least version %s of the plugin %s, but you have %s.' ), $plugin_req[1], $plugin_req[0], $clean_oldest_enabled );
								}
							}
							else
							{ // no plugin existing
								if( $class == 'recommends' )
								{
									$recommends[] = $plugin_req[0];
								}
								else
								{
									$requires[] = $plugin_req[0];
								}
							}
						}

						if( ! empty( $requires ) )
						{
							$msgs['error'][] = sprintf( T_( 'The plugin requires the plugins: %s.' ), implode_with_and( $requires ) );
						}

						if( ! empty( $recommends ) )
						{
							$msgs['note'][] = sprintf( T_( 'The plugin recommends to install the plugins: %s.' ), implode_with_and( $recommends ) );
						}
						break;


					// TODO: "b2evo" (version)!?


					case 'api_min':
						$api_min = $type_params;
						if( ! is_array($api_min) )
						{
							$api_min = array( $api_min, 0 );
						}

						if( $this->api_version[0] < $api_min[0]  // API's major version too old
							|| ( $this->api_version[0] == $api_min[0] && $this->api_version[1] < $api_min[1] ) ) // API's minor version too old
						{
							if( $class == 'recommends' )
							{
								$msgs['note'][] = sprintf( T_('The plugin recommends version %s of the plugin API (%s is installed). Think about upgrading your %s installation.'), implode('.', $api_min), implode('.', $this->api_version), $app_name );
							}
							else
							{
								$msgs['error'][] = sprintf( T_('The plugin requires version %s of the plugin API, but %s is installed. You will probably have to upgrade your %s installation.'), implode('.', $api_min), implode('.', $this->api_version), $app_name );
							}
						}
						break;
				}
			}
		}

		return $msgs;
	}


	/**
	 * Register a plugin.
	 *
	 * This handles the indexes, dynamically unregisters a Plugin that does not exist (anymore)
	 * and instantiates the Plugin's (User)Settings.
	 *
	 * @access private
	 * @param string name of plugin class to instantiate and register
	 * @param int ID in database (0 if not installed)
	 * @param int Priority in database (-1 to keep default)
	 * @param array When should rendering apply? (NULL to keep default)
	 * @param string Path of the .php class file of the plugin.
	 * @param boolean Must the plugin exist (classfile_path and classname)?
	 *                This is used internally to be able to unregister a non-existing plugin.
	 * @return Plugin|string Plugin ref to newly created plugin; string in case of error
	 */
	function & register( $classname, $ID = 0, $priority = -1, $apply_rendering = NULL, $classfile_path = NULL, $must_exists = true )
	{
		global $Debuglog, $Messages, $Timer;

		if( $ID && isset($this->index_ID_Plugins[$ID]) )
		{
			debug_die( 'Tried to register already registered Plugin (ID '.$ID.')' ); // should never happen!
		}

		$Timer->resume( 'plugins_register' );

		if( empty($classfile_path) )
		{
			$plugin_filename = '_'.str_replace( '_plugin', '.plugin', $classname ).'.php';
			// Try <plug_classname>/<plug_classname>.php (subfolder) first
			$classfile_path = $this->plugins_path.$classname.'/'.$plugin_filename;

			if( ! is_readable( $classfile_path ) )
			{ // Look directly in $plugins_path
				$classfile_path = $this->plugins_path.$plugin_filename;
			}
		}

		$Debuglog->add( 'register(): '.$classname.', ID: '.$ID.', priority: '.$priority.', classfile_path: ['.$classfile_path.']', 'plugins' );

		if( ! is_readable( $classfile_path ) )
		{ // Plugin file not found!
			if( $must_exists )
			{
				$r = 'Plugin class file ['.rel_path_to_base($classfile_path).'] is not readable!';
				$Debuglog->add( $r, array( 'plugins', 'error' ) );

				// Get the Plugin object (must not exist)
				$Plugin = & $this->register( $classname, $ID, $priority, $apply_rendering, $classfile_path, false );
				$this->plugin_errors[$ID]['register'] = $r;
				$this->set_Plugin_status( $Plugin, 'broken' );

				if( $this->is_admin_class )
				{
					$Plugin->name = $Plugin->classname; // use the classname instead of "unnamed plugin"
					$Timer->pause( 'plugins_register' );
					return $Plugin;
				}

				// unregister:
				$this->unregister( $Plugin );
				$Debuglog->add( 'Unregistered plugin ['.$classname.']!', array( 'plugins', 'error' ) );

				$Timer->pause( 'plugins_register' );
				return $r;
			}
		}
		else
		{
			$Debuglog->add( 'Loading plugin class file: '.$classname, 'plugins' );
			require_once $classfile_path;
		}

		if( ! class_exists( $classname ) )
		{ // the given class does not exist
			if( $must_exists )
			{
				$r = sprintf( 'Plugin class for &laquo;%s&raquo; in file &laquo;%s&raquo; not defined.', $classname, rel_path_to_base($classfile_path) );
				$Debuglog->add( $r, array( 'plugins', 'error' ) );

				// Get the Plugin object (must not exist)
				$Plugin = & $this->register( $classname, $ID, $priority, $apply_rendering, $classfile_path, false );
				$this->plugin_errors[$ID]['register'] = $r;
				$this->set_Plugin_status( $Plugin, 'broken' );

				if( $this->is_admin_class )
				{
					$Plugin->name = $Plugin->classname; // use the classname instead of "unnamed plugin"
					$Timer->pause( 'plugins_register' );
					return $Plugin;
				}

				// unregister:
				$this->unregister( $Plugin );
				$Debuglog->add( 'Unregistered plugin ['.$classname.']!', array( 'plugins', 'error' ) );

				$Timer->pause( 'plugins_register' );
				return $r;
			}
			else
			{
				$Plugin = new Plugin;	// COPY !
				$Plugin->code = NULL;
				$Plugin->apply_rendering = 'never';
			}
		}
		else
		{
			$Plugin = new $classname;	// COPY !
		}

		$Plugin->classfile_path = $classfile_path;

		// Tell him his ID :)
		if( $ID == 0 )
		{
			$Plugin->ID = --$this->smallest_internal_ID;
		}
		else
		{
			$Plugin->ID = $ID;

			if( $ID > 0 )
			{ // Properties from T_plugins
				// Code
				$Plugin->code = $this->index_ID_rows[$Plugin->ID]['plug_code'];
				// Status
				$Plugin->status = $this->index_ID_rows[$Plugin->ID]['plug_status'];
			}
		}
		// Tell him his name :)
		$Plugin->classname = $classname;
		// Tell him his priority:
		if( $priority > -1 ) { $Plugin->priority = $priority; }

		if( isset($apply_rendering) )
		{
			$Plugin->apply_rendering = $apply_rendering;
		}

		if( empty($Plugin->name) )
		{
			$Plugin->name = $Plugin->classname;
		}

		$Plugin->Plugins = & $this;

		// Memorizes Plugin in code hash array:
		if( ! empty($this->index_code_ID[ $Plugin->code ]) && $this->index_code_ID[ $Plugin->code ] != $Plugin->ID )
		{ // The plugin's default code is already in use!
			$Plugin->code = NULL;
		}
		else
		{
			$this->index_code_Plugins[ $Plugin->code ] = & $Plugin;
			$this->index_code_ID[ $Plugin->code ] = & $Plugin->ID;
		}
		$this->index_ID_Plugins[ $Plugin->ID ] = & $Plugin;

		if( ! in_array( $Plugin->ID, $this->sorted_IDs ) ) // TODO: check if this extra check is required..
		{ // not in our sort index yet
			$this->sorted_IDs[] = & $Plugin->ID;
		}

		// Stuff only for real/existing Plugins (which exist in DB):
		if( $Plugin->ID > 0 )
		{
			// Instantiate the Plugins (User)Settings members:
			$this->instantiate_Settings( $Plugin, 'Settings' );
			$this->instantiate_Settings( $Plugin, 'UserSettings' );

			$tmp_params = array( 'db_row' => $this->index_ID_rows[$Plugin->ID], 'is_installed' => true );
			if( $Plugin->PluginInit( $tmp_params ) === false && ! $this->is_admin_class )
			{
				$Debuglog->add( 'Unregistered plugin, because PluginInit returned false.', 'plugins' );
				$this->unregister( $Plugin );
				$Plugin = '';
			}
			// Version check:
			elseif( $Plugin->version != $this->index_ID_rows[$Plugin->ID]['plug_version'] && $must_exists )
			{ // Version has changed since installation or last update
				$db_deltas = array();

				// Tell the Plugin that we've detected a version change:
				$tmp_params = array( 'old_version'=>$this->index_ID_rows[$Plugin->ID]['plug_version'], 'db_row'=>$this->index_ID_rows[$Plugin->ID] );

				if( ! $this->call_method( $Plugin->ID, 'PluginVersionChanged', $tmp_params ) )
				{
					$Debuglog->add( 'Set plugin status to "needs_config", because PluginVersionChanged returned false.', 'plugins' );
					$this->set_Plugin_status( $Plugin, 'needs_config' );
					if( ! $this->is_admin_class )
					{ // only unregister the Plugin, if it's not the admin list's class:
						$this->unregister( $Plugin );
						$Plugin = '';
					}
				}
				else
				{
					// Check if there are DB deltas required (also when downgrading!), without excluding any query type:
					require_once( dirname(__FILE__).'/_upgrade.funcs.php' );
					$db_deltas = db_delta( $Plugin->GetDbLayout() );

					if( empty($db_deltas) )
					{ // No DB changes needed, update (bump or decrease) the version
						global $DB;
						$DB->query( '
								UPDATE T_plugins
								   SET plug_version = '.$DB->quote($Plugin->version).'
								 WHERE plug_ID = '.$Plugin->ID );

						// Detect new events (and delete obsolete ones - in case of downgrade):
						if( $this->save_events( $Plugin, array() ) )
						{
							$this->load_events(); // re-load for the current request
						}

						$Debuglog->add( 'Version for '.$Plugin->classname.' changed from '.$this->index_ID_rows[$Plugin->ID]['plug_version'].' to '.$Plugin->version, 'plugins' );
					}
					else
					{ // If there are DB schema changes needed, set the Plugin status to "needs_config"

						// TODO: automatic upgrade in some cases (e.g. according to query types)?

						$this->set_Plugin_status( $Plugin, 'needs_config' );
						$Debuglog->add( 'Set plugin status to "needs_config", because version DB schema needs upgrade.', 'plugins' );

						if( ! $this->is_admin_class )
						{ // only unregister the Plugin, if it's not the admin list's class:
							$this->unregister( $Plugin );
							$Plugin = '';
						}
					}
				}
			}

			if( $this->index_ID_rows[$Plugin->ID]['plug_name'] !== NULL )
			{
				$Plugin->name = $this->index_ID_rows[$Plugin->ID]['plug_name'];
			}
			if( $this->index_ID_rows[$Plugin->ID]['plug_shortdesc'] !== NULL )
			{
				$Plugin->short_desc = $this->index_ID_rows[$Plugin->ID]['plug_shortdesc'];
			}
		}
		else
		{ // This gets called for non-installed Plugins:

			$tmp_params = array( 'db_row' => array(), 'is_installed' => false );
			if( $Plugin->PluginInit( $tmp_params ) === false && ! $this->is_admin_class )
			{
				$Debuglog->add( 'Unregistered plugin, because PluginInit returned false.', 'plugins' );
				$this->unregister( $Plugin );
				$Plugin = '';
			}
		}

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

		$this->forget_events( $Plugin->ID );

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

		if( $this->current_idx >= $sort_key )
		{ // We have removed a file before or at the $sort_key'th position
			$this->current_idx--;
		}
	}


	/**
	 * Forget the events a Plugin has registered.
	 *
	 * This gets used when {@link unregister() unregistering} a Plugin or if
	 * {@link Plugin::PluginInit()} returned false, which means
	 * "do not use it for subsequent events in the request".
	 */
	function forget_events( $plugin_ID )
	{
		// Forget events:
		foreach( array_keys($this->index_event_IDs) as $l_event )
		{
			while( ($key = array_search( $plugin_ID, $this->index_event_IDs[$l_event] )) !== false )
			{
				unset( $this->index_event_IDs[$l_event][$key] );
			}
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

		// Update references to code:
		// TODO: dh> we might want to update item renderer codes and blog ping plugin codes here! (old code=>new code)

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
	 *   false if another Plugin uses that priority already.
	 *   1 in case of setting it into DB.
	 */
	function set_priority( $plugin_ID, $priority )
	{
		global $DB;

		if( ! preg_match( '~^1?\d?\d$~', $priority ) ) // using preg_match() to catch floating numbers
		{
			debug_die( 'Plugin priority must be numeric (0-100).' );
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

		// TODO: dh> should only re-sort, if sorted by priority before - if it should get re-sorted at all!
		//$this->sort();

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

		if( $this->index_ID_rows[$Plugin->ID]['plug_apply_rendering'] == $apply_rendering )
		{ // Already set to same value
			return false;
		}

		$r = $DB->query( '
			UPDATE T_plugins
			  SET plug_apply_rendering = '.$DB->quote($apply_rendering).'
			WHERE plug_ID = '.$plugin_ID );

		$Plugin->apply_rendering = $apply_rendering;

		// Apply-rendering index:
		if( ! isset( $this->index_apply_rendering_codes[ $Plugin->apply_rendering ] )
		    || ! in_array( $Plugin->code, $this->index_apply_rendering_codes[ $Plugin->apply_rendering ] ) )
		{
			$this->index_apply_rendering_codes[ $Plugin->apply_rendering ][] = $Plugin->code;
		}

		return true;
	}


	/**
	 * Instantiate Settings member of class {@link PluginSettings} for the given
	 * plugin, if it provides default settings (through {@link Plugin::GetDefaultSettings()}).
	 *
	 * @param Plugin
	 * @return NULL|boolean NULL, if no Settings
	 */
	function instantiate_Settings( & $Plugin, $set_type )
	{
		global $Debuglog, $Timer, $model_path;

		$Timer->resume( 'plugins_inst_'.$set_type );

		$defaults = $this->call_method( $Plugin->ID, 'GetDefault'.$set_type, $params = array() );

		if( empty($defaults) )
		{
			$Timer->pause( 'plugins_inst_'.$set_type );
			return NULL;
		}

		if( ! is_array($defaults) )
		{
			$Debuglog->add( $Plugin->classname.'::GetDefault'.$set_type.'() did not return array!', array('plugins', 'error') );
		}
		elseif( $set_type == 'UserSettings' )
		{
			require_once $model_path.'settings/_pluginusersettings.class.php';

			$Plugin->UserSettings = new PluginUserSettings( $Plugin->ID );

			$set_Obj = & $Plugin->UserSettings;
		}
		else
		{
			require_once $model_path.'settings/_pluginsettings.class.php';

			$Plugin->Settings = new PluginSettings( $Plugin->ID );

			$set_Obj = & $Plugin->Settings;
		}

		foreach( $defaults as $l_name => $l_meta )
		{
			if( isset($l_meta['layout']) )
			{ // Skip non-value entries
				continue;
			}

			// Register settings as _defaults into Settings:
			if( isset($l_meta['defaultvalue']) )
			{
				$set_Obj->_defaults[$l_name] = $l_meta['defaultvalue'];
			}
			elseif( isset( $l_meta['type'] ) && $l_meta['type'] == 'array' )
			{
				$set_Obj->_defaults[$l_name] = array();
			}
			else
			{
				$set_Obj->_defaults[$l_name] = '';
			}
		}

		$Timer->pause( 'plugins_inst_'.$set_type );

		return true;
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
	 * Call all plugins for a given event.
	 *
	 * @param string event name, see {@link Plugin::get_supported_events()}
	 * @param array Associative array of parameters for the Plugin
	 * @return boolean True, if at least one plugin has been called.
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
		return true;
	}


	/**
	 * Call all plugins for a given event, until the first one returns true.
	 *
	 * @param string event name, see {@link Plugin::get_supported_events()}
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
	 * Call all plugins for a given event, until the first one returns false.
	 *
	 * @param string event name, see {@link Plugin::get_supported_events()}
	 * @param array Associative array of parameters for the Plugin
	 * @return array The (modified) params array with key "plugin_ID" set to the last called plugin;
	 *               Empty array if no Plugin returned true or no Plugin has this event registered.
	 */
	function trigger_event_first_false( $event, $params = NULL )
	{
		global $Debuglog;

		$Debuglog->add( 'Trigger event '.$event.' (first false)', 'plugins' );

		if( empty($this->index_event_IDs[$event]) )
		{ // No events registered
			$Debuglog->add( 'No registered plugins.', 'plugins' );
			return array();
		}

		$Debuglog->add( 'Registered plugin IDs: '.implode( ', ', $this->index_event_IDs[$event]), 'plugins' );
		foreach( $this->index_event_IDs[$event] as $l_plugin_ID )
		{
			$r = $this->call_method( $l_plugin_ID, $event, $params );
			if( $r === false )
			{
				$Debuglog->add( 'Plugin ID '.$l_plugin_ID.' returned false!', 'plugins' );
				$params['plugin_ID'] = & $l_plugin_ID;
				return $params;
			}
		}
		return array();
	}


	/**
	 * Call all plugins for a given event, until the first one returns a value
	 * (not NULL) (and $search is fulfilled, if given).
	 *
	 * @param string event name, see {@link Plugin::get_supported_events()}
	 * @param array|NULL Associative array of parameters for the Plugin
	 * @param array|NULL If provided, the return value gets checks against this criteria.
	 *        Can be:
	 *         - ( 'in_array' => 'needle' )
	 * @return array The (modified) params array with key "plugin_ID" set to the last called plugin
	 *               and 'plugin_return' set to the return value;
	 *               Empty array if no Plugin returned true or no Plugin has this event registered.
	 */
	function trigger_event_first_return( $event, $params = NULL, $search = NULL )
	{
		global $Debuglog;

		$Debuglog->add( 'Trigger event '.$event.' (first return)', 'plugins' );

		if( empty($this->index_event_IDs[$event]) )
		{ // No events registered
			$Debuglog->add( 'No registered plugins.', 'plugins' );
			return array();
		}

		$Debuglog->add( 'Registered plugin IDs: '.implode( ', ', $this->index_event_IDs[$event]), 'plugins' );
		foreach( $this->index_event_IDs[$event] as $l_plugin_ID )
		{
			$r = $this->call_method( $l_plugin_ID, $event, $params );
			if( isset($r) )
			{
				foreach( $search as $k => $v )
				{ // Check search criterias and continue if it does not match:
					switch( $k )
					{
						case 'in_array':
							if( ! in_array( $v, $r ) )
							{
								continue 3; // continue in main foreach loop
							}
							break;
						default:
							debug_die('Invalid search criteria in Plugins::trigger_event_first_return / '.$k);
					}
				}
				$Debuglog->add( 'Plugin ID '.$l_plugin_ID.' returned '.( $r ? 'true' : 'false' ).'!', 'plugins' );
				$params['plugin_return'] = $r;
				$params['plugin_ID'] = & $l_plugin_ID;
				return $params;
			}
		}
		return array();
	}


	/**
	 * Trigger an $event and return an index of $params.
	 *
	 * @param string Event name, see {@link Plugins::get_supported_events()}
	 * @param array Associative array of parameters for the Plugin
	 * @param string Index of $params that should get returned
	 * @return mixed The requested index of $params
	 */
	function get_trigger_event( $event, $params = NULL, $get = 'data' )
	{
		$params[$get] = & $params[$get]; // make it a reference, so it can get changed

		$this->trigger_event( $event, $params );

		return $params[$get];
	}


	/**
	 * The same as {@link get_trigger_event()}, but stop when the first Plugin returns true.
	 *
	 * @param string Event name, see {@link Plugins::get_supported_events()}
	 * @param array Associative array of parameters for the Plugin
	 * @param string Index of $params that should get returned
	 * @return mixed The requested index of $params
	 */
	function get_trigger_event_first_true( $event, $params = NULL, $get = 'data' )
	{
		$params[$get] = & $params[$get]; // make it a reference, so it can get changed

		$this->trigger_event_first_true( $event, $params );

		return $params[$get];
	}


	/**
	 * Trigger an event and return the first return value of a plugin.
	 *
	 * @param string Event name, see {@link Plugins::get_supported_events()}
	 * @param array Associative array of parameters for the Plugin
	 * @return mixed NULL if no Plugin returned something or the return value of the first Plugin
	 */
	function get_trigger_event_first_return( $event, $params = NULL )
	{
		$r = $this->trigger_event_first_return( $event, $params );

		if( ! isset($r['plugin_return']) )
		{
			return NULL;
		}

		return $r['plugin_return'];
	}


	/**
	 * Trigger an event and return an array of all return values of the
	 * relevant plugins.
	 *
	 * @param string Event name, see {@link Plugins::get_supported_events()}
	 * @param array Associative array of parameters for the Plugin
	 * @param boolean Ignore {@link empty() empty} return values?
	 * @return array List of return values, indexed by Plugin ID
	 */
	function trigger_collect( $event, $params = NULL, $ignore_empty = true )
	{
		if( empty($this->index_event_IDs[$event]) )
		{
			return array();
		}

		$r = array();
		foreach( $this->index_event_IDs[$event] as $p_ID )
		{
			$sub_r = $this->call_method_if_active( $p_ID, $event, $params );

			if( $ignore_empty && empty($sub_r) )
			{
				continue;
			}

			$r[$p_ID] = $sub_r;
		}

		return $r;
	}


	/**
	 * Trigger a karma collecting event in order to get Karma percentage.
	 *
	 * @param string Event
	 * @param array Params to the event
	 * @return integer|NULL Spam Karma (-100 - 100); "100" means "absolutely spam"; NULL if no plugin gave us a karma value
	 */
	function trigger_karma_collect( $event, $params )
	{
		global $Debuglog;

		$karma_abs = NULL;
		$karma_divider = 0; // total of the "spam detection relevance weight"

		$Debuglog->add( 'Trigger karma collect event '.$event, 'plugins' );

		if( empty($this->index_event_IDs[$event]) )
		{ // No events registered
			$Debuglog->add( 'No registered plugins.', 'plugins' );
			return NULL;
		}

		$this->load_plugins_table(); // We need index_ID_rows below

		$Debuglog->add( 'Registered plugin IDs: '.implode( ', ', $this->index_event_IDs[$event]), 'plugins' );

		$count_plugins = 0;
		foreach( $this->index_event_IDs[$event] as $l_plugin_ID )
		{
			$plugin_weight = $this->index_ID_rows[$l_plugin_ID]['plug_spam_weight'];

			if( $plugin_weight < 1 )
			{
				$Debuglog->add( 'Skipping plugin #'.$l_plugin_ID.', because is has weight '.$plugin_weight.'.', 'plugins' );
				continue;
			}

			$params['cur_karma'] = ( $karma_divider ? round($karma_abs / $karma_divider) : NULL );
			$params['cur_karma_abs'] = $karma_abs;
			$params['cur_karma_divider'] = $karma_divider;
			$params['cur_count_plugins'] = $count_plugins;

			// Call the plugin:
			$plugin_karma = $this->call_method( $l_plugin_ID, $event, $params );

			if( ! is_numeric( $plugin_karma ) )
			{
				continue;
			}

			$count_plugins++;

			if( $plugin_karma > 100 )
			{
				$plugin_karma = 100;
			}
			elseif( $plugin_karma < -100 )
			{
				$plugin_karma = -100;
			}

			$karma_abs += ( $plugin_karma * $plugin_weight );
			$karma_divider += $plugin_weight;

			if( ! empty($this->_stop_propagation) )
			{
				$this->_stop_propagation = false;
				break;
			}
		}

		if( ! $karma_divider )
		{
			return NULL;
		}

		$karma = round($karma_abs / $karma_divider);

		if( $karma > 100 )
		{
			$karma = 100;
		}
		elseif( $karma < -100 )
		{
			$karma = -100;
		}

		return $karma;
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
			// $Debuglog->add( 'Calling '.$Plugin->classname.'(#'.$Plugin->ID.')->'.$method.'( '.htmlspecialchars(var_export( $debug_params, true )).' )', 'plugins' );
			$Debuglog->add( 'Calling '.$Plugin->classname.'(#'.$Plugin->ID.')->'.$method.'( )', 'plugins' );
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

		// Make sure there's no renderer code with a dot, as the list gets imploded by that when saved:
		foreach( $validated_renderers as $k => $l_code )
		{
			if( empty($l_code) || strpos( $l_code, '.' ) !== false )
			{
				unset( $validated_renderers[$k] );
			}
		}

		// echo 'validated Renderers: '.count( $validated_renderers );
		return $validated_renderers;
	}


	/**
	 * Render the content of an item.
	 *
	 * @param string content to render (by reference)
	 * @param array renderer codes to use for opt-out, opt-in and lazy  (by reference)
	 * @param string Output format, see {@link format_to_output()}. Only 'htmlbody',
	 *        'entityencoded', 'xml' and 'text' are supported.
	 * @param array Additional params to the Render* methods (e.g. "Item" for items).
	 *              Do not use "data" or "format" here, because it gets used internally.
	 * @return string rendered content
	 */
	function render( & $content, & $renderers, $format, $params, $event_prefix = 'Render' )
	{
		// echo implode(',',$renderers);

		$params['data'] = & $content;
		$params['format'] = $format;

		if( $format == 'htmlbody' || $format == 'entityencoded' )
		{
			$event = $event_prefix.'ItemAsHtml'; // 'RenderItemAsHtml'/'DisplayItemAsHtml'
		}
		elseif( $format == 'xml' )
		{
			$event = $event_prefix.'ItemAsXml'; // 'RenderItemAsXml'/'DisplayItemAsXml'
		}
		elseif( $format == 'text' )
		{
			$event = $event_prefix.'ItemAsText'; // 'RenderItemAsText'/'DisplayItemAsText'
		}
		else debug_die( 'Unexpected format in Plugins::render(): '.var_export($format, true) );

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
	 * @param array Associative array of parameters (gets passed to the plugin)
	 * @return boolean
	 */
	function call_by_code( $code, $params = array() )
	{
		$Plugin = & $this->get_by_code( $code );

		if( ! $Plugin )
		{
			global $Debuglog;
			$Debuglog->add( 'No plugin available for code ['.$code.']!', array('plugins', 'error') );
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

		$this->index_ID_rows = array();
		$this->index_code_ID = array();
		$this->index_apply_rendering_codes = array();
		$this->sorted_IDs = array();

		foreach( $DB->get_results( $this->sql_load_plugins_table, ARRAY_A ) as $row )
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

			if( ! isset( $this->index_ID_rows[$plugin_ID] ) || ! $this->index_ID_rows[$plugin_ID] )
			{ // no plugin rows cached
				#debug_die( 'Cannot instantiate Plugin (ID '.$plugin_ID.') without DB information.' );
				$Debuglog->add( 'get_by_ID(): Plugin (ID '.$plugin_ID.') not registered/enabled in DB!', array( 'plugins', 'error' ) );
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
	 * Get a plugin by its classname.
	 *
	 * @return Plugin|false
	 */
	function & get_by_classname($classname)
	{
		$this->load_plugins_table(); // We use index_ID_rows (no own index yet)

		foreach( $this->index_ID_rows as $plug_ID => $row )
		{
			if( $row['plug_classname'] == $classname )
			{
				return $this->get_by_ID($plug_ID);
			}
		}

		$r = false;
		return $r;
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
				$Debuglog->add( 'Requested plugin ['.$plugin_code.'] is not registered/enabled!', 'plugins' );
				return $r;
			}

			if( ! $this->get_by_ID( $this->index_code_ID[$plugin_code] ) )
			{
				$Debuglog->add( 'Requested plugin ['.$plugin_code.'] could not get instantiated!', 'plugins' );
				return $r;
			}
		}

		return $this->index_code_Plugins[ $plugin_code ];
	}


	/**
	 * Get a list of Plugins for a given event.
	 *
	 * @param string Event name
	 * @return array List of Plugins, where the key is the plugin's ID
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
	 * @return array List of Plugins, where the key is the plugin's ID
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
	 * @return array The list of plugins, where the key is the plugin's ID
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
	 * Has a plugin a specific event registered/enabled?
	 *
	 * @return boolean
	 */
	function has_event( $plugin_ID, $event )
	{
		return isset($this->index_event_IDs[$event])
			&& in_array( $plugin_ID, $this->index_event_IDs[$event] );
	}


	/**
	 * Check if the requested list of events is provided by any or one plugin.
	 *
	 * @param array|string A single event or a list thereof
	 * @param boolean Make sure there's at least one plugin that provides them all?
	 *                This is useful for event pairs like "CaptchaPayload" and "CaptchaValidated", which
	 *                should be served by the same plugin.
	 * @return boolean
	 */
	function are_events_available( $events, $by_one_plugin = false )
	{
		if( ! is_array($events) )
		{
			$events = array($events);
		}

		if( $by_one_plugin )
		{
			return (bool)$this->get_list_by_all_events( $events );
		}

		return (bool)$this->get_list_by_events( $events );
	}


	/**
	 * (Re)load Plugin Events for enabled (normal use) or all (admin use) plugins.
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
				 '.( $this->is_admin_class ? '' : 'AND plug_status = "enabled"' ).'
				 ORDER BY plug_priority, plug_classname', OBJECT, 'Loading plugin events' ) as $l_row )
		{
			$this->index_event_IDs[$l_row->pevt_event][] = $l_row->pevt_plug_ID;
		}
	}


	/**
	 * Get a list of methods that are supported as events out of the Plugin's
	 * source file.
	 *
	 * @todo Extend to get list of defined classes and global functions and check this list before sourcing/including a Plugin! (prevent fatal error)
	 *
	 * @return array
	 */
	function get_registered_events( $Plugin )
	{
		global $Timer, $Debuglog;

		$Timer->resume( 'plugins_detect_events' );

		$plugin_class_methods = array();

		if( ! is_readable($Plugin->classfile_path) )
		{
			$Debuglog->add( 'get_registered_events(): "'.$Plugin->classfile_path.'" is not readable.', array('plugins', 'error') );
			return array();
		}

		$classfile_contents = @file_get_contents( $Plugin->classfile_path );
		if( ! is_string($classfile_contents) )
		{
			$Debuglog->add( 'get_registered_events(): "'.$Plugin->classfile_path.'" could not get read.', array('plugins', 'error') );
			return array();
		}

		// TODO: allow optional Plugin callback to get list of methods. Like Plugin::GetRegisteredEvents().

		if( preg_match_all( '~^\s*function\s+(\w+)~mi', $classfile_contents, $matches ) )
		{
			$plugin_class_methods = $matches[1];
		}
		else
		{
			$Debuglog->add( 'No functions found in file "'.$Plugin->classfile_path.'".', array('plugins', 'error') );
			return array();
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
		foreach( $DB->get_results( '
				SELECT pevt_event, pevt_enabled
				  FROM T_pluginevents
				 WHERE pevt_plug_ID = '.$Plugin->ID ) as $l_row )
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

		$new_events_enabled = array();
		if( $enable_events )
		{
			foreach( $enable_events as $l_event )
			{
				if( ! isset( $saved_events[$l_event] ) || ! $saved_events[$l_event] )
				{ // Event not saved yet or not enabled
					$new_events_enabled[] = $l_event;
				}
			}
			if( $new_events_enabled )
			{
				$DB->query( '
					REPLACE INTO T_pluginevents( pevt_plug_ID, pevt_event, pevt_enabled )
					VALUES ( '.$Plugin->ID.', "'.implode( '", 1 ), ('.$Plugin->ID.', "', $new_events_enabled ).'", 1 )' );
				$r = true;
			}
			$Debuglog->add( 'Enabled events ['.implode( ', ', $new_events_enabled ).'] for Plugin '.$Plugin->name, 'plugins' );
		}

		$new_events_disabled = array();
		if( $disable_events )
		{
			foreach( $disable_events as $l_event )
			{
				if( ! isset( $saved_events[$l_event] ) || $saved_events[$l_event] )
				{ // Event not saved yet or enabled
					$new_events_disabled[] = $l_event;
				}
			}
			if( $new_events_disabled )
			{
				$DB->query( '
					REPLACE INTO T_pluginevents( pevt_plug_ID, pevt_event, pevt_enabled )
					VALUES ( '.$Plugin->ID.', "'.implode( '", 0 ), ('.$Plugin->ID.', "', $new_events_disabled ).'", 0 )' );
				$r = true;
			}
			$Debuglog->add( 'Disabled events ['.implode( ', ', $new_events_disabled ).'] for Plugin '.$Plugin->name, 'plugins' );
		}

		if( $r )
		{ // Something has changed: Reload event index
			foreach( array_merge($obsolete_events, $discovered_events, $new_events_enabled, $new_events_disabled) as $event )
			{
				if( strpos($event, 'RenderItemAs') === 0 )
				{ // Clear pre-rendered content cache, if RenderItemAs* events have been added or removed:
					$DB->query( 'DELETE FROM T_item__prerendering WHERE 1' );
					$ItemCache = & get_Cache( 'ItemCache' );
					$ItemCache->clear();
					break;
				}
			}

			$this->load_events();
		}

		return $r;
	}


	/**
	 * Set the status of an event for a given Plugin.
	 *
	 * @return boolean True, if status has changed; false if not
	 */
	function set_event_status( $plugin_ID, $plugin_event, $enabled )
	{
		global $DB;

		$enabled = $enabled ? 1 : 0;

		$DB->query( '
			UPDATE T_pluginevents
			   SET pevt_enabled = '.$enabled.'
			 WHERE pevt_plug_ID = '.$plugin_ID.'
			   AND pevt_event = "'.$plugin_event.'"' );

		if( $DB->rows_affected )
		{
			$this->load_events();

			if( strpos($plugin_event, 'RenderItemAs') === 0 )
			{ // Clear pre-rendered content cache, if RenderItemAs* events have been added or removed:
				$DB->query( 'DELETE FROM T_item__prerendering WHERE 1' );
				$ItemCache = & get_Cache( 'ItemCache' );
				$ItemCache->clear();
				break;
			}

			return true;
		}

		return false;
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
			if( is_object($get_return['data']) )
			{
				$GLOBALS[$objectName] = $get_return['data']; // COPY! (get_Cache() uses $$objectName instead of $GLOBALS - no deal for PHP5 anyway)

				$Plugin = & $this->get_by_ID( $get_return['plugin_ID'] );
				register_shutdown_function( array(&$Plugin, 'CacheObjects'),
					array( 'action' => 'set', 'key' => 'object_'.$objectName, 'data' => & $GLOBALS[$objectName] ) );

				return;
			}
		}

		// Cache miss, create it:
		if( empty($eval_create_object) )
		{
			$GLOBALS[$objectName] = new $objectName(); // COPY (FUNC)
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


	/**
	 * Callback, which gets used for {@link Results}.
	 *
	 * @return Plugin|false
	 */
	function & instantiate( $row )
	{
		return $this->get_by_ID( $row->plug_ID );
	}

}


/**
 * A sub-class of {@link Plugins}, just to not load any DB info (which means Plugins and Events).
 *
 * This is only useful for displaying a list of available plugins or during installation to have
 * a global $Plugins object that does not interfere with the installation process.
 *
 * {@internal This is probably quicker and cleaner than using a member boolean in {@link Plugins} itself.}}
 *
 * @package evocore
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


/**
 * A Plugins object that loads all Plugins, not just the enabled ones. This is needed for the backoffice plugin management.
 *
 * @package evocore
 */
class Plugins_admin extends Plugins
{
	/**
	 * Load all plugins (not just enabled ones).
	 */
	var $sql_load_plugins_table = '
			SELECT plug_ID, plug_priority, plug_classname, plug_code, plug_name, plug_shortdesc, plug_apply_rendering, plug_status, plug_version, plug_spam_weight
			  FROM T_plugins
			 ORDER BY plug_priority, plug_classname';

	var $is_admin_class = true;
}


/*
 * $Log$
 * Revision 1.95  2006/10/14 20:50:29  blueyed
 * Define EVO_IS_INSTALL for /install/ and use it in Plugins to skip "dangerous" but unnecessary instantiating of other Plugins
 *
 * Revision 1.94  2006/10/14 16:27:06  blueyed
 * Client-side password hashing in the login form.
 *
 * Revision 1.93  2006/10/08 22:59:31  blueyed
 * Added GetProvidedSkins and DisplaySkin hooks. Allow for optimization in Plugins::trigger_event_first_return()
 *
 * Revision 1.92  2006/10/05 02:10:26  blueyed
 * Do not add empty codes in Plugins::validate_list()
 *
 * Revision 1.91  2006/10/05 01:06:37  blueyed
 * Removed dirty "hack"; added ItemApplyAsRenderer hook instead.
 *
 * Revision 1.90  2006/10/01 22:11:42  blueyed
 * Ping services as plugins.
 *
 * Revision 1.89  2006/10/01 19:56:36  blueyed
 * TODO
 *
 * Revision 1.88  2006/10/01 15:11:08  blueyed
 * Added DisplayItemAs* equivs to RenderItemAs*; removed DisplayItemAllFormats; clearing of pre-rendered cache, according to plugin event changes
 *
 * Revision 1.87  2006/10/01 00:14:58  blueyed
 * plug_classpath should not have get merged already
 *
 * Revision 1.86  2006/09/30 23:42:06  blueyed
 * Allow editing the plugin name and short desc of installed plugins
 *
 * Revision 1.85  2006/09/30 23:12:40  blueyed
 * Do not re-sort when setting new prio for a plugin
 *
 * Revision 1.84  2006/09/30 20:53:49  blueyed
 * Added hook RenderItemAsText, removed general RenderItem
 *
 * Revision 1.83  2006/09/30 16:49:56  blueyed
 * minor debuglog wording
 *
 * Revision 1.82  2006/09/30 16:46:26  blueyed
 * Merged fixes from v-1-8
 *
 * Revision 1.81  2006/09/08 15:34:55  blueyed
 * Enhanced debugging for get_registered_events()
 *
 * Revision 1.80  2006/08/31 19:00:03  blueyed
 * Fixed caching of objects, which was broken with get_Cache() introduction
 *
 * Revision 1.79  2006/08/30 22:51:03  blueyed
 * Trigger Plugin::BeforeDisable() as deep as possible.
 *
 * Revision 1.78  2006/08/28 20:16:29  blueyed
 * Added GetCronJobs/ExecCronJob Plugin hooks.
 *
 * Revision 1.77  2006/08/20 21:02:56  blueyed
 * Removed dependency on tokenizer. Quite a few people don't have it.. see http://forums.b2evolution.net//viewtopic.php?t=8664
 *
 * Revision 1.75  2006/08/19 10:57:40  blueyed
 * doc fixes.
 *
 * Revision 1.74  2006/08/19 08:50:27  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.72  2006/08/16 19:41:35  blueyed
 * small memory opt.
 *
 * Revision 1.71  2006/08/02 22:05:37  blueyed
 * Optimized performance of (Abstract)Settings, especially get().
 *
 * Revision 1.70  2006/07/31 20:11:28  blueyed
 * Karma: check for "numeric" instead of "integer" return values.
 *
 * Revision 1.69  2006/07/26 20:48:34  blueyed
 * Added Plugin event "Logout"
 *
 * Revision 1.68  2006/07/17 01:19:25  blueyed
 * Added events: AfterUserInsert, AfterUserUpdate, AfterUserDelete
 *
 * Revision 1.67  2006/07/16 15:09:35  blueyed
 * Added plugins_discover Timer.
 *
 * Revision 1.66  2006/07/14 00:13:48  blueyed
 * Bumped api_version.
 *
 * Revision 1.65  2006/07/10 22:53:38  blueyed
 * Grouping of plugins added, based on a patch from balupton
 *
 * Revision 1.64  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.63  2006/07/08 00:18:56  blueyed
 * (temp) Fix for a design flaw..
 *
 * Revision 1.62  2006/07/06 21:38:45  blueyed
 * Deprecated plugin constructor. Renamed AppendPluginRegister() to PluginInit().
 *
 * Revision 1.61  2006/07/06 18:54:15  fplanque
 * cleanup
 *
 * Revision 1.60  2006/07/03 23:36:00  blueyed
 * Cleanup
 *
 * Revision 1.59  2006/07/03 20:59:00  fplanque
 * too much translation/memory overhead. Geeks and devs can read English.
 *
 * Revision 1.58  2006/06/27 15:39:23  blueyed
 * fix
 *
 * Revision 1.57  2006/06/20 00:16:54  blueyed
 * Transformed Plugins table into Results object, so some columns are sortable.
 *
 * Revision 1.56  2006/06/19 20:59:14  blueyed
 * minor
 *
 * Revision 1.55  2006/06/10 19:16:17  blueyed
 * DisplayTrackbackAddr event
 *
 * Revision 1.54  2006/06/06 20:35:50  blueyed
 * Plugins can define extra events that they trigger themselves.
 *
 * Revision 1.53  2006/06/06 18:20:54  blueyed
 * fix undefinded var in msg
 *
 * Revision 1.52  2006/06/05 14:27:58  blueyed
 * Karma collect: Load plugins table, as it's needed for weight (Thanks to Tor)
 *
 * Revision 1.51  2006/05/24 20:43:19  blueyed
 * Pass "Item" as param to Render* event methods.
 *
 * Revision 1.50  2006/05/20 01:56:07  blueyed
 * ItemCanComment hook; "disable anonymous feedback" through basic antispam plugin
 *
 * Revision 1.49  2006/05/19 18:15:06  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.48.2.1  2006/05/19 15:06:25  fplanque
 * dirty sync
 *
 * Revision 1.48  2006/05/17 23:35:42  blueyed
 * cleanup
 *
 * Revision 1.47  2006/05/17 09:57:17  blueyed
 * safety check
 *
 * Revision 1.46  2006/05/15 22:26:48  blueyed
 * Event hooks for skin plugins.
 *
 * Revision 1.45  2006/05/12 21:53:38  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.44  2006/05/05 19:36:23  blueyed
 * New events
 *
 * Revision 1.43  2006/05/02 23:46:07  blueyed
 * Validate/fixed plugin spam weight handling.
 *
 * Revision 1.42  2006/05/02 23:35:22  blueyed
 * Extended karma collecting event(s)
 *
 * Revision 1.41  2006/05/02 04:36:25  blueyed
 * Spam karma changed (-100..100 instead of abs/max); Spam weight for plugins; publish/delete threshold
 *
 * Revision 1.40  2006/05/02 01:47:58  blueyed
 * Normalization
 *
 * Revision 1.39  2006/05/02 01:27:55  blueyed
 * Moved nofollow handling to basic antispam plugin; added Filter events to Comment class
 *
 * Revision 1.38  2006/05/01 04:25:07  blueyed
 * Normalization
 *
 * Revision 1.37  2006/04/29 17:37:48  blueyed
 * Added basic_antispam_plugin; Moved double-check-referers there; added check, if trackback links to us
 *
 * Revision 1.36  2006/04/29 01:04:23  blueyed
 * *** empty log message ***
 *
 * Revision 1.35  2006/04/27 20:07:19  blueyed
 * Renamed Plugin::get_htsrv_methods() to GetHtsvMethods() (normalization)
 *
 * Revision 1.34  2006/04/27 19:44:33  blueyed
 * A plugin can disable events (e.g. after install)
 *
 * Revision 1.33  2006/04/27 19:11:12  blueyed
 * Cleanup; handle broken plugins more decent
 *
 * Revision 1.32  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.31  2006/04/20 22:24:08  blueyed
 * plugin hooks cleanup
 *
 * Revision 1.30  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.28  2006/04/18 21:09:20  blueyed
 * Added hooks to manipulate Items before insert/update/preview; fixes; cleanup
 *
 * Revision 1.27  2006/04/04 22:56:12  blueyed
 * Simplified/refactored uninstalling/registering of a plugin (especially the hooking process)
 *
 * Revision 1.26  2006/03/28 22:24:46  blueyed
 * Fixed logical spam karma issues
 *
 * Revision 1.25  2006/03/24 01:06:55  blueyed
 * Also instantiate UserSettings if $current_User not (yet) set (of course!)
 *
 * Revision 1.24  2006/03/21 23:03:47  blueyed
 * Fixes for instantiating settings (Plugins without UserSettings failed to install since last commit)
 *
 * Revision 1.23  2006/03/20 00:14:37  blueyed
 * Do not instantiate UserSettings, if we have no user!
 *
 * Revision 1.22  2006/03/19 19:02:51  blueyed
 * New events for captcha plugins
 *
 * Revision 1.21  2006/03/16 19:58:18  blueyed
 * Debug-output if call_by_code() finds no plugin
 *
 * Revision 1.20  2006/03/15 23:35:35  blueyed
 * "broken" state support for Plugins: set/unset state, allowing to un-install and display error in "edit_settings" action
 *
 * Revision 1.19  2006/03/15 21:02:04  blueyed
 * Display event status for all plugins with $admin_Plugins
 *
 * Revision 1.18  2006/03/15 20:19:56  blueyed
 * Do not unregister Plugin from $admin_Plugins if it does not get enabled on install.
 *
 * Revision 1.17  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.16  2006/03/12 19:58:29  blueyed
 * T_plugin fields changed (according to fplanque)
 *
 * Revision 1.15  2006/03/11 18:25:22  blueyed
 * Karma round()ed again.
 *
 * Revision 1.13  2006/03/11 02:02:00  blueyed
 * Normalized t_pluginusersettings
 *
 * Revision 1.12  2006/03/11 01:59:00  blueyed
 * Added Plugin::forget_events()
 *
 * Revision 1.11  2006/03/11 01:21:15  blueyed
 * Allow user to deactivate test plugin.
 *
 * Revision 1.10  2006/03/08 18:49:38  blueyed
 * Memory usage decreased
 *
 * Revision 1.9  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.8  2006/03/03 20:10:21  blueyed
 * doc
 *
 * Revision 1.7  2006/03/02 22:18:24  blueyed
 * New events
 *
 * Revision 1.6  2006/03/02 19:57:53  blueyed
 * Added DisplayIpAddress() and fixed/finished DisplayItemAllFormats()
 *
 * Revision 1.5  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.4  2006/02/27 16:57:12  blueyed
 * PluginUserSettings - allows a plugin to store user related settings
 *
 * Revision 1.3  2006/02/24 22:08:59  blueyed
 * Plugin enhancements
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.47  2006/02/05 19:04:49  blueyed
 * doc fixes
 *
 * Revision 1.46  2006/02/03 17:35:17  blueyed
 * post_renderers as TEXT
 *
 * Revision 1.40  2006/01/28 17:07:32  blueyed
 * Moved set_empty_code_to_default() to caller.
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
 * validate_list() need to call load_plugins_table(); also fixed it for opt-in and lazy renderers.
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

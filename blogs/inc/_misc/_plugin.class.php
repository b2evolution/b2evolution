<?php
/**
 * This file implements the abstract {@link Plugin} class.
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
 * @package plugins
 *
 * @todo Add links to pages on manual.b2evolution.net, once they are "clean"/tiny
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Plugin Class
 *
 * Real plugins should be derived from this class.
 *
 * @abstract
 * @package plugins
 */
class Plugin
{
	/**#@+
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	/**
	 * Default plugin name as it will appear in lists.
	 *
	 * To make it available for translations set it in the constructor by
	 * using the {@link Plugin::T_()} function.
	 *
	 * This should be no longer than 50 characters.
	 *
	 * @var string
	 */
	var $name = '';

	/**
	 * Globally unique code for this plugin functionality. 32 chars. MUST BE SET.
	 *
	 * A common code MIGHT be shared between different plugins providing the same functionnality.
	 * This allows to replace a given renderer with another one and keep the associations with posts.
	 * Example: replacing a GIF smiley renderer with an SWF smiley renderer...
	 *
	 * @var string
	 */
	var $code = '';

	/**
	 * Default priority.
	 *
	 * Priority determines in which order the plugins get called.
	 * Range: 1 to 100 (the lower the number, the earlier it gets called)
	 *
	 * @var int
	 */
	var $priority = 50;

	/**
	 * Plugin version number (max 42 chars, so obscure CVS Revision keywords get handled).
	 *
	 * This must be compatible to PHP's {@link version_compare()},
	 * e.g. '1', '2', '1.1', '2.1b' and '10-1-1a' are fine.
	 *
	 * This can be used by other plugins when requiring your plugin
	 * through {@link Plugin::GetDependencies()}.
	 *
	 * By increasing it you can request a call of {@link GetDbLayout()} upon instantiating.
	 * If there are DB layout changes to be made, the plugin gets changed to status "needs_config".
	 *
	 * @var string
	 */
	var $version = '0';

	/**
	 * Plugin author.
	 *
	 * This is for user info only.
	 *
	 * @var string
	 */
	var $author = 'Unknown author';

	/**
	 * URL for more info about the plugin, author and new versions.
	 *
	 * This is for user info only.
	 *
	 * If empty, it defaults to 'http://manual.b2evolution.net/Plugins/[plugin_classname]',
	 * where '[plugin_classname]' is the plugin's PHP class name.
	 *
	 * @var string
	 */
	var $help_url = '';

	/**
	 * Plugin short description.
	 *
	 * This should be no longer than a line and is limited to 255 chars.
	 *
	 * @var string
	 */
	var $short_desc;

	/**#@-*/


	/**#@+
	 * Variables below MAY be overriden.
	 */

	/**
	 * Plugin long description.
	 *
	 * This should be no longer than a line.
	 *
	 * @var string
	 */
	var $long_desc;


	/**
	 * If this is a rendering plugin, when should rendering apply?
	 *
	 * This is the default value for the plugin and can be overriden in the Plugins
	 * administration for plugins that provide rendering events.
	 *
	 * {@internal The actual value for the plugin gets stored in T_plugins.plug_apply_rendering.}}
	 *
	 * Possible values:
	 * - 'stealth': gets always used, but not displayed as option
	 * - 'always': gets always used, and displayed as disabled checkbox
	 * - 'opt-out': enabled by default
	 * - 'opt-in': disabled by default
	 * - 'lazy': checkbox gets displayed, but is disabled
	 * - 'never': cannot get used as a renderer
	 *
	 * @todo blueyed>> IMHO we would need another value, which is the same as "lazy", but does not display a checkbox, which is useful for Plugins that add themselves as renderers on Item update
	 *
	 * @var string
	 */
	var $apply_rendering = 'never'; // By default, this may not be a rendering plugin


	/**
	 * Number of allowed installs.
	 *
	 * When installing the plugin it gets checked if the plugin is already installed this
	 * many times. If so, the installation gets aborted.
	 */
	var $number_of_installs;


	/**
	 * Main group of the plugin.
	 *
	 * @var string
	 */
	var $group;


	/**
	 * Sub-Group of the plugin.
	 *
	 * @var string
	 */
	var $sub_group;

	/**#@-*/


	/**#@+
	 * Variables below MUST NOT be overriden or changed by you!
	 * @access private
	 */

	/**
	 * Name of the current class. (AUTOMATIC)
	 *
	 * Will be set automatically (from filename) when registering plugin.
	 *
	 * @var string
	 */
	var $classname;

	/**
	 * Internal (DB) ID. (AUTOMATIC)
	 *
	 * ID < 1 means 'NOT installed'
	 *
	 * @var int
	 */
	var $ID = 0;


	/**
	 * If the plugin provides settings, this will become the object to access them.
	 *
	 * This gets instantianted in {@link Plugins::instantiate_Settings()}.
	 *
	 * @see GetDefaultSettings()
	 * @var NULL|PluginSettings
	 */
	var $Settings;


	/**
	 * If the plugin provides user settings, this will become the object to access them.
	 *
	 * This gets instantianted in {@link Plugins::instantiate_Settings()}.
	 *
	 * NOTE: its methods use {@link $current_User::ID} by default, but you may call it
	 *       if there's no {@link $current_User} instantiated (yet).
	 *
	 * @see GetDefaultUserSettings()
	 * @var NULL|PluginUserSettings
	 */
	var $UserSettings;


	/**
	 * The status of the plugin.
	 *
	 * Use {@link set_status()} to change it, if you need to.
	 *
	 * @var string Either 'enabled', 'disabled', 'needs_config' or 'broken'.
	 */
	var $status;

	/**
	 * The "mother" object, where this Plugin got instantiated from.
	 *
	 * This is needed, if you have to call a method on it (e.g. {@link Plugins::set_apply_rendering()}.
	 *
	 * @var Plugins|Plugins_admin
	 */
	var $Plugins;

	/**
	 * The translations keyed by locale. They get loaded through include() of _global.php.
	 * @see Plugin::T_()
	 * @var array
	 */
	var $trans = array();

	/**
	 * @var boolean Has the global /locales/_global.php file (where translation for
	 * all languages can be put into) been loaded?
	 */
	var $trans_loaded_global = false;

	/**#@-*/


	/**
	 * Constructor.
	 *
	 * You should not use a constructor with your plugin, but the PluginInit() method instead!
	 */
	function Plugin()
	{
	}


	/**
	 * Init the Plugin after it has been registered/instantiated.
	 *
	 * Should set name and description in a localizable fashion.
	 *
	 * This gets called on every instantiated plugin, also if it's just for
	 * discovering the list of available plugins in the backoffice.
	 *
	 * Use this to validate Settings/requirements and/or cache them into class properties.
	 *
	 * @param array Associative array of parameters.
	 *              'is_installed': true, if the plugin is installed; false if not (probably it got discovered then)
	 *              'db_row': an array with the columns of the plugin DB entry (in T_plugins).
	 *                        This is empty, if the plugin is not installed!
	 *                        E.g., 'plug_version' might be interesting to compare again "$this->version".
	 * @return boolean If this method returns false, the Plugin gets unregistered (for the current request only).
	 */
	function PluginInit( & $params )
	{
		// NOTE: the code below is just to handle stuff that has been deprecated since
		//       b2evolution 1.9. You don't have to include this, if you override this method.

		if( is_null($this->short_desc) )
		{ // may have been set in plugin's constructor (which is deprecated since 1.9)
			$this->short_desc = T_('No desc available');
		}
		if( is_null($this->long_desc) )
		{ // may have been set in plugin's constructor (which is deprecated since 1.9)
			$this->long_desc = T_('No description available');
		}

		if( method_exists( $this, 'AppendPluginRegister' ) && $params['is_installed'] )
		{ // Wrapper for deprecated AppendPluginRegister method (deprecated since 1.9)
			$this->debug_log('Plugin has deprecated AppendPluginRegister method. Use PluginInit instead.', array('deprecated'));

			return $this->AppendPluginRegister($params);
		}

		return true;
	}


	// Plugin information (settings, DB layout, ..): {{{

	/**
	 * Define here default settings that are then available in the backoffice.
	 *
	 * You can access them in the plugin through the member object
	 * {@link Plugin::Settings}, e.g.:
	 * <code>$this->Settings->get( 'my_param' );</code>
	 *
	 * You probably don't need to set or change values (other than the
	 * defaultvalues), but if you know what you're doing, see
	 * {@link PluginSettings}, where {@link Plugin::Settings} gets derived from.
	 *
	 * @return array
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 *
	 *   - 'label': Name/Title of the param, gets displayed as label for the input field, or
	 *              as "legend" tag with types "array" and "fieldset".
	 *   - 'defaultvalue': Default value for the setting, defaults to '' (empty string)
	 *   - 'type', which can be:
	 *     - 'text' (default): a simple string
	 *     - 'password': like text, but hidden during input
	 *     - 'checkbox': either 0 or 1
	 *     - 'integer': a number (no float, can have leading "+" or "-")
	 *                  (like 'text' for input, but gets validated when submitting)
	 *     - 'textarea': several lines of input. The following can be set for this type:
	 *       - 'rows': number of rows
	 *       - 'cols': number of columns
	 *     - 'select': a drop down field; you must set 'options' for it:
	 *       - 'options': an array of options ('value' => 'description'), see {@link Form::select_input_array()}.
	 *     - 'select_group': a drop down field, providing all existing groups
	 *     - 'select_user': a drop down field, providing all existing groups
	 *     - 'array': a subset of settings. The value gets automagically (un)serialized through get() and set().
	 *       The following keys apply to this type:
	 *       - 'entries': an array with the sub-settings (which can be everything from the top-level, except:
	 *                    "valid_pattern", "valid_range"). Note: currently there's no type forcing or checking
	 *                    for sub-entries involved (e.g., if you have an entry of type "integer", you could get
	 *                    a non-numeric string there).
	 *       - 'max_count': maximum count of sets (optional, default is no restriction)
	 *       - 'min_count': minimum count of sets (optional, default is no restriction)
	 *   - 'note' (gets displayed as a note to the param field),
	 *   - 'size': Size of the HTML input field (applies to types 'text', 'password' and 'integer'; defaults to 15)
	 *   - 'maxlength': maxlength attribute for the input field (See 'size' above; defaults to no limit)
	 *   - 'disabled': if true, it adds a 'disabled="disabled"' html attribute to the element and the value cannot be changed
	 *   - 'no_edit': if true, the setting is not editable. This is useful for internal settings.
	 *   - 'allow_none': set this to true to have "None" in the options list for types
	 *                   'select_group' and 'select_user'.
	 *   - 'valid_pattern': A regular expression pattern that the value must match.
	 *                      This is either just a regexp pattern as string or an array
	 *                      with the keys 'pattern' and 'error' to define a custom error message.
	 *   - 'valid_range': An array with keys 'min', 'max' and (optionally) 'error' to define
	 *                    a custom error message. At least "min" or "max" must be given.
	 *   - 'help': can be:
	 *          - '#anchor': anchor that gets appended to {@link $help_url}
	 *          - true: the settings name/key gets transformed to an html ID and gets used as anchor to {@link $help_url}.
	 *          - 'http://example.com/uri': a full URL (starting with http:// or https://)
	 *   - 'layout': Use this to visually group your settings.
	 *               Either 'begin_fieldset', 'end_fieldset' or 'separator'. You can use 'label' for 'begin_fieldset'.
	 *   - 'id', 'onchange', 'onclick', 'onfocus', 'onkeyup', 'onkeydown', 'onreset', 'onselect': get passed
	 *           through as attributes to the form/input element.
	 * e.g.:
	 * <code>
	 * return array(
	 *   'my_param' => array(
	 *     'label' => $this->T_('My Param'),
	 *     'defaultvalue' => '10',
	 *     'note' => $this->T_('Quite cool, eh?'),
	 *     'valid_pattern' => array( 'pattern' => '[1-9]\d+', $this->T_('The value must be >= 10.') ),
	 *   ),
	 *   'another_param' => array( // this one has no 'note'
	 *     'label' => $this->T_('My checkbox'),
	 *     'defaultvalue' => '1',
	 *     'type' => 'checkbox',
	 *   ),
	 *   array( 'layout' => 'separator' ),
	 *   'my_select' => array(
	 *     'label' => $this->T_('Selector'),
	 *     'defaultvalue' => 'one',
	 *     'type' => 'select',
	 *     'options' => array( 'sun' => $this->T_('Sunday'), 'mon' => $this->T_('Monday') ),
	 *   ) );
	 * </code>
	 *
	 */
	function GetDefaultSettings()
	{
		return array();
	}


	/**
	 * Define here default user settings that are then available in the backoffice.
	 *
	 * You can access them in the plugin through the member object
	 * {@link $UserSettings}, e.g.:
	 * <code>$this->UserSettings->get( 'my_param' );</code>
	 *
	 * You probably don't need to set or change values (other than the
	 * defaultvalues), but if you know what you're doing, see
	 * {@link PluginUserSettings}, where {@link $UserSettings} gets derived from.
	 *
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function GetDefaultUserSettings()
	{
	}


	/**
	 * Get the list of dependencies that the plugin has.
	 *
	 * This gets checked on install or uninstall of a plugin.
	 *
	 * There are two classes of dependencies:
	 *  - 'recommends': This is just a recommendation. If it cannot get fulfilled
	 *                  there will just be a note added on install.
	 *  - 'requires': A plugin cannot be installed if the dependencies cannot get
	 *                fulfilled. Also, a plugin cannot get uninstalled, if another
	 *                plugin depends on it.
	 *
	 * Each class of dependency can have the following types:
	 *  - 'events_by_one': A list of eventlists that have to be provided by a single plugin,
	 *                     e.g., <code>array( array('CaptchaPayload', 'CaptchaValidated') )</code>
	 *                     to look for a plugin that provides both events.
	 *  - 'plugins':
	 *    A list of plugins, either just the plugin's classname or an array with
	 *    classname and minimum version of the plugin (see {@link Plugin::version}).
	 *    E.g.: <code>array( 'test_plugin', '1' )</code> to require at least version "1"
	 *          of the test plugin.
	 *  - 'api_min': You can require a specific minimum version of the Plugins API here.
	 *               If it's just a number, only the major version is checked against.
	 *               To check also for the minor version, you have to give an array:
	 *               array( major, minor ).
	 *               Major versions will mark drastic changes, while minor version
	 *               increasement just means "new features" (probably hooks).
	 *               This way you can make sure that the hooks you need are implemented
	 *               in the core.
	 *
	 * @see test_plugin::GetDependencies()
	 * @return array
	 */
	function GetDependencies()
	{
		return array(); // no dependencies by default, of course
	}


	/**
	 * This method should return your DB schema, consisting of a list of CREATE TABLE
	 * queries.
	 *
	 * The DB gets changed accordingly on installing or enabling your Plugin.
	 *
	 * If you want to change your DB layout in a new version of your Plugin, simply
	 * adjust the queries here and increase {@link Plugin::version}, because this will
	 * request to check the current DB layout against the one you require.
	 *
	 * For restrictions see {@link db_delta()}.
	 */
	function GetDbLayout()
	{
		return array();
	}


	/**
	 * This method gets asked when plugins get installed and allows you to return a list
	 * of extra events, which your plugin triggers itself (e.g. through
	 * {@link $Plugins->trigger_event()}).
	 *
	 * NOTE: PLEASE use a distinct prefix for the event name, e.g. "$this->classname".
	 *
	 * NOTE: The length of event names is limited to 40 chars.
	 *
	 * NOTE: Please comment the params and the return value here with the list
	 *       that you return. Only informal as comment, but makes it easier for
	 *       others.
	 *
	 * @see test_plugin::GetExtraEvents()
	 * @return NULL|array "event_name" => "description"
	 */
	function GetExtraEvents()
	{
	}


	/**
	 * Override this method to define methods/functions that you want to make accessible
	 * through /htsrv/call_plugin.php, which allows you to call those methods by HTTP request.
	 *
	 * This is useful for things like AJAX or displaying an <iframe> element, where the content
	 * should get provided by the plugin itself.
	 *
	 * E.g., the image captcha plugin uses this method to serve a generated image.
	 *
	 * NOTE: the Plugin's method must be prefixed with "htsrv_", but in this list (and the URL) it
	 *       is not. E.g., to have a method "disp_image" that should be callable through this method
	 *       return <code>array('disp_image')</code> here and implement it as
	 *       <code>function htsrv_disp_image( $params )</code> in your plugin.
	 *       This is used to distinguish those methods from others, but keep URLs nice.
	 *
	 * @see get_htsrv_url()
	 * @return array
	 */
	function GetHtsrvMethods()
	{
		return array();
	}

	// }}}


	/*
	 * Event handlers. These are meant to be implemented by your plugin. {{{
	 */

	// Admin/backoffice events (without events specific to Items or Comments): {{{

	/**
	 * Event handler: Gets invoked in /admin/_header.php for every backoffice page after
	 *                the menu structure is build. You could use the {@link $AdminUI} object
	 *                to modify it.
	 *
	 * This is the hook to register menu entries. See {@link register_menu_entry()}.
	 */
	function AdminAfterMenuInit()
	{
		// Example:
		$this->register_menu_entry( $this->T_('My Tab') );
	}


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called right after displaying the admin page footer.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminAfterPageFooter( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying editor buttons.
	 *
	 * This method, if implemented, should output the buttons
	 * (probably as html INPUT elements) and return true, if
	 * button(s) have been displayed.
	 *
	 * You should provide an unique html ID with your button.
	 *
	 * @param array Associative array of parameters.
	 *              - 'target_type': either 'Comment' or 'Item'.
	 *              - 'edit_layout': "simple", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 * @return boolean did we display a button?
	 */
	function AdminDisplayEditorButton( $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying editor toolbars.
	 *
	 * @param array Associative array of parameters
	 *              - 'target_type': either 'Comment' or 'Item'.
	 *              - 'edit_layout': "simple", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when handling actions for the "Tools" menu.
	 *
	 * Use {@link msg()} to add messages for the user.
	 */
	function AdminToolAction()
	{
	}


	/**
	 * Event handler: Called when displaying the block in the "Tools" menu.
	 *
	 * @return boolean did we display something?
	 */
	function AdminToolPayload()
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Method that gets invoked when our tab is selected.
	 *
	 * You should catch (your own) params (using {@link param()}) here and do actions
	 * (but no output!).
	 *
	 * Use {@link msg()} to add messages for the user.
	 */
	function AdminTabAction()
	{
	}


	/**
	 * Event handler: Gets invoked when our tab is selected and should get displayed.
	 *
	 * Do your output here.
	 *
	 * @return boolean did we display something?
	 */
	function AdminTabPayload()
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Gets invoked before the main payload in the backoffice.
	 */
	function AdminBeginPayload()
	{
	}

	// }}}


	// Skin/Blog events: {{{

	/**
	 * Event handler: Called before a blog gets displayed (in _blog_main.inc.php).
	 */
	function BeforeBlogDisplay( & $params )
	{
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource
	 * files (CSS, JavaScript, ..)).
	 */
	function SkinBeginHtmlHead( & $params )
	{
	}

	// }}}


	// (Un)Install / (De)Activate events: {{{

	/**
	 * Event handler: Called before the plugin is going to be installed.
	 *
	 * This is the hook to create any DB tables or the like.
	 *
	 * If you just want to add a note, use {@link Plugin::msg()} (and return true).
	 *
	 * @return true|string True, if the plugin can be enabled/activated,
	 *                     a string with an error/note otherwise.
	 */
	function BeforeInstall()
	{
		return true;  // default is to allow Installation
	}


	/**
	 * Event handler: Called after the plugin has been installed.
	 */
	function AfterInstall()
	{
	}


	/**
	 * Event handler: Called before the plugin is going to be un-installed.
	 *
	 * This is the hook to remove any files or the like - tables with canonical names
	 * (see {@link Plugin::get_sql_table()}, are handled internally.
	 *
	 * See {@link BeforeUninstallPayload()} for the corresponding payload handler, which you
	 * can request to invoke by returning NULL here.
	 *
	 * Note: this method gets called again, if the uninstallation has to be confirmed,
	 *       either because you've requested a call to {@link BeforeUninstallPayload()}
	 *       or there are tables to be dropped (what the admin user has to confirm).
	 *
	 * @param array Associative array of parameters.
	 *              'unattended': true if Uninstall is unattended (e.g., the /install action "deletedb" uses it).
	 *                            This should cleanup everything without confirmation!
	 * @return boolean|NULL
	 *         true when it's ok to uninstall,
	 *         false on failure (the plugin won't get uninstalled then).
	 *               You should add the reason for it through {@link Plugin::msg()}.
	 *         NULL requests to execute the {@link BeforeUninstallPayload()} method.
	 */
	function BeforeUninstall( & $params )
	{
		return true;
	}


	/**
	 * Event handler: Gets invoked to display the payload before uninstalling the plugin.
	 *
	 * You have to request a call to this during the plugin uninstall procedure by
	 * returning NULL in {@link BeforeUninstall()}.
	 *
	 * @param array Associative array of parameters.
	 *              'Form': The {@link Form} that asks the user for confirmation (by reference).
	 *                      If your plugin uses canonical table names (see {@link Plugin::get_sql_table()}),
	 *                      there will be already a list of those tables included in it.
	 *                      Do not end the form, just add own inputs or hidden keys to it.
	 */
	function BeforeUninstallPayload( & $params )
	{
	}


	/**
	 * Event handler: Called when the admin tries to enable the plugin, changes
	 * its configuration/settings and after installation.
	 *
	 * Use this, if your plugin needs configuration before it can be used.
	 *
	 * If you want to disable your Plugin yourself, use {@link Plugin::disable()}.
	 *
	 * @return true|string True, if the plugin can be enabled/activated,
	 *                     a string with an error/note otherwise.
	 */
	function BeforeEnable()
	{
		return true;  // default is to allow Activation
	}


	/**
	 * Event handler: Your plugin gets notified here, just before it gets
	 * disabled.
	 *
	 * You cannot prevent this, but only clean up stuff, if you have to.
	 */
	function BeforeDisable()
	{
	}


	/*
	 * NOTE: function AppendPluginRegister( & $params ) is deprecated since 1.9.
	 * Use Plugin::PluginInit() instead.
	 */


	/**
	 * Event handler: Called when we detect a version change (in {@link Plugins::register()}).
	 *
	 * Use this for your upgrade needs.
	 *
	 * @param array Associative array of parameters.
	 *              'old_version': The old version of your plugin as stored in DB.
	 *              'db_row': an array with the columns of the plugin DB entry (in T_plugins).
	 *                        The key 'plug_version' is the same as the 'old_version' key.
	 * @return boolean If this method returns false, the Plugin's status gets changed to "needs_config" and
	 *                 it gets unregistered for the current request.
	 */
	function PluginVersionChanged( & $params )
	{
		return true;
	}

	// }}}


	// Item events: {{{

	/**
	 * Event handler: Called when rendering item/post contents as HTML.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderItemAsHtml( & $params )
	{
		/*
		$content = & $params['data'];
		$content = 'PREFIX__'.$content.'__SUFFIX'; // just an example
		return true;
		*/
	}


	/**
	 * Event handler: Called when rendering item/post contents as XML.
	 *
	 * Should this plugin apply to XML?
	 * It should actually only apply when:
	 * - it generates some content that is visible without HTML tags
	 * - it removes some dirty markup when generating the tags (which will get stripped afterwards)
	 * Note: htmlentityencoded is not considered as XML here.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'xml' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderItemAsXml( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when rendering item/post contents other than XML or HTML.
	 *
	 * Note: return value is ignored. You have to change $params['data'].
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 *               Only formats other than 'htmlbody', 'entityencoded' and 'xml' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderItem()
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying an item/post's content.
	 *
	 * This is different from {@link RenderItem()}, {@link RenderItemAsHtml()} and {@link RenderItemAsXml()}:
	 *  - It applies on every display (rendering might get cached later)
	 *  - It calls all Plugins that register this event, not just associated ones.
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 *   - 'Item': The {@link Item} that gets displayed (by reference).
	 *   - 'preview': Is this only a preview?
	 *   - 'dispmore': Does this include the "more" text (if available), which means "full post"?
	 * @return boolean Have we changed something?
	 */
	function DisplayItemAllFormats( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: called at the beginning of {@link Item::dbupdate() updating
	 * an item/post in the database}.
	 *
	 * Use this to manipulate the {@link Item}, e.g. adding a renderer code
	 * through {@link Item::add_renderer()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function PrependItemUpdateTransact( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link Item::dbupdate() updating
	 * an item/post in the database}, which means that it has been changed.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function AfterItemUpdate( & $params )
	{
	}


	/**
	 * Event handler: called at the beginning of {@link Item::dbinsert() inserting
	 * an item/post in the database}.
	 *
	 * Use this to manipulate the {@link Item}, e.g. adding a renderer code
	 * through {@link Item::add_renderer()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function PrependItemInsertTransact( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link Item::dbinsert() inserting
	 * a item/post into the database}, which means it has been created.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function AfterItemInsert( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link Item::dbdelete() deleting
	 * an item/post from the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function AfterItemDelete( & $params )
	{
	}


	/**
	 * Event handler: called when instantiating an Item for preview.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function AppendItemPreviewTransact( & $params )
	{
	}


	/**
	 * Event handler: Called when the view counter of an item got increased.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the Item object (by reference)
	 */
	function ItemViewsIncreased( & $params )
	{
	}


	/**
	 * Event handler: Called at the end of the "Edit item" form:
	 *
	 * @param array Associative array of parameters
	 *              'Form': the {@link Form} object (by reference)
	 *              'Item': the Item which gets edited (by reference)
	 * @return boolean did we display something?
	 */
	function AdminDisplayItemFormFieldset( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called before a new item gets created (in the backoffice).
	 *
	 * You could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the comment from being inserted.
	 *
	 * @param array Associative array of parameters
	 *              'Item': the Item which gets created (by reference)
	 */
	function AdminBeforeItemEditCreate( & $params )
	{
	}


	/**
	 * Event handler: Called before an existing item gets updated (in the backoffice).
	 *
	 * You could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the comment from being inserted.
	 *
	 * @param array Associative array of parameters
	 *              'Item': the Item which gets updated (by reference)
	 */
	function AdminBeforeItemEditUpdate( & $params )
	{
	}


	/**
	 * Event handler: the plugin gets asked if an item can receive feedback/comments.
	 *
	 * @param array Associative array of parameters
	 *              'Item': the Item
	 * @return boolean|string
	 *   true, if the Item can receive feedback
	 *   false/string, if the Item cannot receive feedback. If you return a string
	 *                 this gets displayed as an error/explanation.
	 *   NULL, if you do not want to say "yes" or "no".
	 */
	function ItemCanComment( & $params )
	{
	}


	/**
	 * Event handler: called to display the URL that accepts trackbacks for
	 *                an item.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the {@link Item} object (by reference)
	 *   - 'template': the template to display the URL (%url%)
	 */
	function DisplayTrackbackAddr( & $params )
	{
	}

	// }}}


	// Feedback (Comment/Trackback) events: {{{

	/**
	 * Event handler: Called at the end of the frontend comment form.
	 *
	 * You might want to use this to inject antispam payload to use in
	 * in {@link GetSpamKarmaForComment()} or modify the Comment according
	 * to it in {@link BeforeCommentFormInsert()}.
	 *
	 * @see Plugin::BeforeCommentFormInsert(), Plugin::AfterCommentFormInsert()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'Item': the Item for which the comment is meant
	 */
	function DisplayCommentFormFieldset( & $params )
	{
	}


	/**
	 * Event handler: Called in the submit button section of the
	 * frontend comment form.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'Item': the Item for which the comment is meant
	 */
	function DisplayCommentFormButton( & $params )
	{
	}


	/**
	 * Event handler: Called before a comment gets inserted through the public comment
	 *                form.
	 *
	 * Use this, to validate a comment: you could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the comment from being inserted.
	 *
	 * @see Plugin::DisplayCommentFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Comment': the Comment (by reference)
	 *   - 'original_comment': this is the unstripped and unformated posted comment, use with care! (by reference)
	 *   - 'is_preview': is this a request for previewing the comment? (boolean)
	 */
	function BeforeCommentFormInsert( & $params )
	{
	}


	/**
	 * Event handler: Called when a comment form has been processed and the comment
	 *                got inserted into DB.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the Comment (by reference)
	 *   - 'original_comment': this is the unstripped and unformated posted comment, use with care!
	 */
	function AfterCommentFormInsert( & $params )
	{
	}


	/**
	 * Event handler: Called to ask the plugin for the spam karma of a comment.
	 *
	 * This gets called just before the comment gets stored.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the {@link Comment} object (by reference)
	 *   - The following values are interesting if you want to provide skipping of a test:
	 *     - 'cur_karma': current karma value (cur_karma_abs/cur_karma_divider or NULL)
	 *     - 'cur_karma_abs': current karma absolute value or NULL (if no Plugin returned karma before)
	 *     - 'cur_karma_divider': current divider (sum of weights)
	 *     - 'cur_count_plugins': number of Plugins that have already been asked
	 * @return integer|NULL Spam probability (-100 - 100).
	 *                -100 means "absolutely no spam", 100 means "absolutely spam".
	 *                Only if you return an integer, it gets considered (e.g., "50" or false gets ignored, but 50 gets).
	 */
	function GetSpamKarmaForComment( & $params )
	{
		return;
	}


	/**
	 * Event handler: called at the end of {@link Comment::dbupdate() updating
	 * a comment in the database}, which means that it has changed.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 */
	function AfterCommentUpdate( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link Comment::dbinsert() inserting
	 * a comment into the database}, which means it has been created.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 */
	function AfterCommentInsert( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link Comment::dbdelete() deleting
	 * a comment from the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 */
	function AfterCommentDelete( & $params )
	{
	}


	/**
	 * Event handler: called before a trackback gets recorded.
	 *
	 * Use this, to validate a trackback: you could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the trackback from being accepted.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the trackback (which is a {@link Comment} object with "trackback" type) (by reference)
	 *        The trackback-params get mapped like this:
	 *        - "blog_name" => "author"
	 *        - "url" => "author_url"
	 *        - "title"/"excerpt" => "comment"
	 *
	 */
	function BeforeTrackbackInsert( & $params )
	{
	}


	/**
	 * Event handler: called to filter the comment's author name (blog name for trackbacks)
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the name of the author/blog (by reference)
	 *   - 'makelink': true, if the "data" contains a link
	 *   - 'Comment': the {@link Comment} object
	 */
	function FilterCommentAuthor( & $params )
	{
	}



	/**
	 * Event handler: called to filter the comment's author URL
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the URL of the author/blog (by reference)
	 *   - 'makelink': true, if the "data" contains a link
	 *   - 'Comment': the {@link Comment} object
	 */
	function FilterCommentAuthorUrl( & $params )
	{
	}


	/**
	 * Event handler: called to filter the comment's content
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the name of the author/blog (by reference)
	 *   - 'Comment': the {@link Comment} object
	 */
	function FilterCommentContent( & $params )
	{
	}

	// }}}


	// Message form events: {{{

	/**
	 * Event handler: Called at the end of the frontend message form, which
	 * allows to send an email to a user/commentator.
	 *
	 * You might want to use this to inject antispam payload to use in
	 * in {@link MessageFormSent()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'recipient_ID': ID of the user (if any)
	 *   - 'item_ID': ID of the item where the user clicked the msgform icon (if any)
	 *   - 'comment_ID': ID of the comment where the user clicked the msgform icon (if any)
	 */
	function DisplayMessageFormFieldset( & $params )
	{
	}


	/**
	 * Event handler: Called in the submit button section of the
	 * frontend message form.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'recipient_ID': ID of the user (if any)
	 *   - 'item_ID': ID of the item where the user clicked the msgform icon (if any)
	 *   - 'comment_ID': ID of the comment where the user clicked the msgform icon (if any)
	 */
	function DisplayMessageFormButton( & $params )
	{
	}


	/**
	 * Event handler: Called when a message form has been submitted.
	 *
	 * Add messages of category "error" to prevent the message from being sent.
	 *
	 * You can also alter the "message" or "message_footer" that gets sent here.
	 *
	 * @param array Associative array of parameters
	 *   - 'recipient_ID': ID of the user (if any)
	 *   - 'item_ID': ID of the item where the user clicked the msgform icon (if any)
	 *   - 'comment_ID': ID of the comment where the user clicked the msgform icon (if any)
	 *   - 'message': The message to be sent (by reference)
	 *   - 'message_footer': The footer of the message (by reference)
	 */
	function MessageFormSent( & $params )
	{
	}


	/**
	 * Event handler: Called after a message has been sent through the public email form.
	 *
	 * This is meant to cleanup generated data.
	 */
	function MessageFormSentCleanup()
	{
	}

	// }}}


	// Caching events: {{{

	/**
	 * Event handler: called to cache object data.
	 *
	 * @param array Associative array of parameters
	 *   - 'action': 'delete', 'set', 'get'
	 *   - 'key': The key to refer to 'data'
	 *   - 'data': The actual data. This must be set by the plugin.
	 * @return boolean True if action was successful, false otherwise.
	 */
	function CacheObjects( & $params )
	{
	}


	/**
	 * Event handler: called to cache page content (get cached content or request caching).
	 *
	 * This method must build a unique key for the requested page (including cookie/session info) and
	 * start an output buffer, to get the content to cache.
	 *
	 * Note, that there are special occassions when this event does not get called, because we want
	 * really fresh content always:
	 *  - we're generating static pages
	 *  - there gets a "dynamic object", such as "Messages" or "core.preview_Comment" transported in the session
	 *
	 * @see Plugin::CacheIsCollectingContent()
	 * @param array Associative array of parameters
	 *   - 'data': this must get set to the page content on cache hit
	 * @return boolean True if we handled the request (either returned caching data or started buffering),
	 *                 false if we do not want to cache this page.
	 */
	function CachePageContent( & $params )
	{
	}


	/**
	 * Event handler: gets asked for if we are generating cached content.
	 *
	 * This is useful to not generate a list of online users or the like.
	 *
	 * @see Plugin::CachePageContent()
	 * @return boolean
	 */
	function CacheIsCollectingContent()
	{
	}

	// }}}


	// PluginSettings {{{
	/**
	 * Event handler: Called before displaying or setting a plugin's setting in the backoffice.
	 *
	 * @see GetDefaultSettings()
	 * @param array Associative array of parameters
	 *   - 'name': name of the setting
	 *   - 'value': value of the setting (by reference)
	 *   - 'meta': meta data of the setting (as given in {@link GetDefaultSettings()})
	 * @return string|NULL Return a string with an error to prevent the setting from being set
	 *                     and/or a message added to the settings field.
	 */
	function PluginSettingsValidateSet( & $params )
	{
	}


	/**
	 * Event handler: Called as action just before updating the {@link Plugin::Settings plugin's settings}.
	 *
	 * The "regular" settings from {@link GetDefaultSettings()} have been set into
	 * {@link Plugin::Settings}, but get saved into DB after this method has been called.
	 *
	 * Use this to catch custom input fields from {@link PluginSettingsEditDisplayAfter()} or
	 * add notes/errors through {@link Plugin::msg()}.
	 *
	 * If you want to modify plugin events (see {@link Plugin::enable_event()} and
	 * {@link Plugin::disable_event()}), you should use {@link Plugin::BeforeEnable()}, because Plugin
	 * events get saved (according to the edit settings screen) after this event.
	 *
	 * @return false|NULL Return false to prevent the settings from being updated to DB.
	 */
	function PluginSettingsUpdateAction()
	{
	}


	/**
	 * Event handler: Called as action before displaying the "Edit plugin" form,
	 * which includes the display of the {@link Plugin::Settings plugin's settings}.
	 *
	 * You may want to use this to check existing settings or display notes about
	 * something.
	 */
	function PluginSettingsEditAction()
	{
	}


	/**
	 * Event handler: Called after the form to edit the {@link Plugin::Settings} has been
	 * displayed.
	 *
	 * Use this to add custom input fields (and catch them in {@link PluginSettingsUpdateAction()})
	 * or display custom output (e.g. a test link).
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form}, where an fieldset has been opened already (by reference)
	 */
	function PluginSettingsEditDisplayAfter( & $params )
	{
	}

	// }}}


	// PluginUserSettings {{{
	/**
	 * Event handler: Called before displaying or setting a plugin's user setting in the backoffice.
	 *
	 * @see GetDefaultUserSettings()
	 * @param array Associative array of parameters
	 *   - 'name': name of the setting
	 *   - 'value': value of the setting (by reference)
	 *   - 'meta': meta data of the setting (as given in {@link GetDefaultUserSettings()})
	 *   - 'User': the {@link User} for which the setting is
	 * @return string|NULL Return a string with an error to prevent the setting from being set
	 *                     and/or a message added to the settings field.
	 */
	function PluginUserSettingsValidateSet( & $params )
	{
	}


	/**
	 * Event handler: Called as action just before updating the {@link Plugin::UserSettings plugin's user settings}.
	 *
	 * The "regular" settings from {@link GetDefaultUserSettings()} have been set into
	 * {@link Plugin::UserSettings}, but get saved into DB after this method has been called.
	 *
	 * Use this to catch custom input fields from {@link PluginUserSettingsEditDisplayAfter()} or
	 * add notes/errors through {@link Plugin::msg()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User} for which the settings get updated
	 *
	 * @return false|NULL Return false to prevent the settings from being updated to DB.
	 */
	function PluginUserSettingsUpdateAction( & $params )
	{
	}


	/**
	 * Event handler: Called as action before displaying the "Edit user" form,
	 * which includes the display of the {@link Plugin::UserSettings plugin's user settings}.
	 *
	 * You may want to use this to check existing settings or display notes about
	 * something.
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User} for which the settings are being displayed/edited
	 */
	function PluginUserSettingsEditAction( & $params )
	{
	}


	/**
	 * Event handler: Called after the form to edit the {@link Plugin::UserSettings} has been
	 * displayed.
	 *
	 * Use this to add custom input fields (and catch them in {@link PluginUserSettingsUpdateAction()})
	 * or display custom output (e.g. a test link).
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form}, where an fieldset has been opened already (by reference)
	 */
	function PluginUserSettingsEditDisplayAfter( & $params )
	{
	}

	// }}}


	// User related events, including registration and login (procedure): {{{

	/**
	 * Event handler: Called at the end of the login procedure, if the
	 *                user is anonymous ({@link $current_User current User} NOT set).
	 *
	 * Use this for example to read some cookie and define further handling of
	 * this visitor or force them to login, by {@link Plugin::msg() adding a message}
	 * of class "login_error", which will trigger the login screen.
	 */
	function AfterLoginAnonymousUser( & $params )
	{
	}


	/**
	 * Event handler: Called at the end of the login procedure, if the
	 *                {@link $current_User current User} is set and the
	 *                user is therefor registered.
	 *
	 * Use this for example to re-act on specific {@link Plugin::UserSettings user settings},
	 * e.g., call {@link Plugin::forget_events()} to de-activate the plugin for
	 * the current request.
	 *
	 * You can also {@link Plugin::msg() add a message} of class "login_error"
	 * to prevent the user from accessing the site and triggering
	 * the login screen.
	 */
	function AfterLoginRegisteredUser( & $params )
	{
	}


	/**
	 * Event handler: Called when a new user has registered, at the end of the
	 *                DB transaction that created this user.
	 *
	 * If you want to modify the about-to-be-created user (if the transaction gets
	 * committed), you'll have to call {@link User::dbupdate()} on it, because he
	 * got already inserted (but the transaction is not yet committed).
	 *
	 * Note: if you want to re-act on a new user,
	 * use {@link Plugin::AfterUserRegistration()} instead!
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User user object} (as reference).
	 * @return boolean false if the whole transaction should get rolled back (the user does not get created).
	 */
	function AppendUserRegistrTransact( & $params )
	{
		return true;
	}


	/**
	 * Event handler: Called when a new user has registered and got created.
	 *
	 * Note: if you want to modify a new user,
	 * use {@link Plugin::AppendUserRegistrTransact()} instead!
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User user object} (as reference).
	 */
	function AfterUserRegistration( & $params )
	{
	}


	/**
	 * Event handler: Called at the end of the "Register as new user" form.
	 *
	 * You might want to use this to inject antispam payload to use
	 * in {@link Plugin::RegisterFormSent()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 */
	function DisplayRegisterFormFieldset( & $params )
	{
	}


	/**
	 * Event handler: Called when a "Register as new user" form has been submitted.
	 *
	 * You can cancel the registration process by {@link Plugin::msg() adding a message}
	 * of type "error".
	 */
	function RegisterFormSent( & $params )
	{
	}


	/**
	 * Event handler: Called at the end of the "Login" form.
	 *
	 * You might want to use this to inject payload to use
	 * in {@link LoginAttempt()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 */
	function DisplayLoginFormFieldset( & $params )
	{
	}


	/**
	 * Event handler: called when a user attemps to login.
	 *
	 * You can prevent the user from logging in by {@link Plugin::msg() adding a message}
	 * of type "login_error".
	 *
	 * Otherwise, this hook is meant to authenticate a user against some
	 * external database (e.g. LDAP) and generate a new user.
	 *
	 * @see Plugin::AlternateAuthentication()
	 * @param array Associative array of parameters
	 *   - 'login': user's login
	 *   - 'pass': user's password
	 *   - 'pass_md5': user's md5 password
	 */
	function LoginAttempt( $params )
	{
	}


	/**
	 * Event handler: Called at the end of the "Validate user account" form, which gets
	 *                invoked if newusers_mustvalidate is enabled and the user has not
	 *                been validated yet.
	 *
	 * The corresponding action event is {@link Plugin::ValidateAccountFormSent()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 */
	function DisplayValidateAccountFormFieldset( & $params )
	{
	}


	/**
	 * Event handler: Called when a "Validate user account" form has been submitted.
	 *
	 * You can cancel the registration process by {@link Plugin::msg() adding a message}
	 * of type "error".
	 */
	function ValidateAccountFormSent( & $params )
	{
	}


	/**
	 * Event handler: called at the end of the login process, if the user did not try to
	 *                login (by sending "login" and "pwd"), the session has no user attached
	 *                or only "login" is given.
	 *
	 * This hook is meant to automagically login/authenticate an user by his/her IP address,
	 * special cookie, etc..
	 *
	 * If you can authenticate the user, you'll have to attach him to the {@link $Session},
	 * either through {@link Session::set_user_ID()} or {@link Session::set_User()}.
	 *
	 * @see Plugin::LoginAttempt()
	 * @return boolean True, if the user has been authentificated (set in $Session)
	 */
	function AlternateAuthentication( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link User::dbupdate() updating
	 * an user account in the database}, which means that it has been changed.
	 *
	 * @since 1.8.1
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 */
	function AfterUserUpdate( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link User::dbinsert() inserting
	 * an user account into the database}, which means it has been created.
	 *
	 * @since 1.8.1
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 */
	function AfterUserInsert( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link User::dbdelete() deleting
	 * an user from the database}.
	 *
	 * @since 1.8.1
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 */
	function AfterUserDelete( & $params )
	{
	}

	// }}}


	// General events: {{{

	/**
	 * Event handler: general event to inject payload for a captcha test.
	 *
	 * This does not get called by b2evolution itself, but provides an interface
	 * to other plugins. E.g., the {@link dnsbl_antispam_plugin DNS blacklist plugin}
	 * uses this event optionally to whitelist a user.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link form} where payload should get added (by reference, OPTIONALLY!)
	 *     If it's not given as param, you have to create an own form, if you need one.
	 *   - 'form_use_fieldset': if a "Form" param is given and we use it, should we add
	 *                          an own fieldset? (boolean, default "true", OPTIONALLY!)
	 *   - 'key': A key that is associated to the caller of the event (string, OPTIONALLY!)
	 * @return boolean True, if you have provided payload for a captcha test
	 */
	function CaptchaPayload( & $params )
	{
	}


	/**
	 * Event handler: general event to validate a captcha which payload was added
	 * through {@link CaptchaPayload()}.
	 *
	 * This does not get called by b2evolution itself, but provides an interface
	 * to other plugins. E.g., the {@link dnsbl_antispam_plugin DNS blacklist plugin}
	 * uses this event optionally to whitelist a user.
	 *
	 * NOTE: if the action is verified/completed in total, you HAVE to call
	 *       {@link CaptchaValidatedCleanup()}, so that the plugin can cleanup its data
	 *       and is not vulnerable against multiple usage of the same captcha!
	 *
	 * @param array Associative array of parameters
	 *   - 'validate_error': you can optionally set this, if you want to give a reason
	 *     of the failure. This is optionally and meant to be used by other plugins
	 *     that trigger this event.
	 * @return boolean true if the catcha could be validated
	 */
	function CaptchaValidated( & $params )
	{
	}


	/**
	 * Event handler: general event to be called after an action has been taken, which
	 * involved {@link CaptchaPayload()} and {@link CaptchaValidated()}.
	 *
	 * This is meant to cleanup generated data for the Captcha test.
	 *
	 * This does not get called by b2evolution itself, but provides an interface
	 * to other plugins. E.g., the {@link dnsbl_antispam_plugin DNS blacklist plugin}
	 * uses this event optionally to whitelist a user.
	 */
	function CaptchaValidatedCleanup( & $params )
	{
	}

	// }}}


	/**
	 * Event handler: Called when an IP address gets displayed, typically in a protected
	 * area or for a privileged user, e.g. in the backoffice statistics menu.
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 * @return boolean Have we changed something?
	 */
	function FilterIpAddress( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called after initializing plugins, DB, Settings, Hit, .. but
	 * quite early.
	 *
	 * This is meant to be a good point for Antispam plugins to cancel the request.
	 *
	 * @see dnsbl_antispam_plugin
	 */
	function SessionLoaded()
	{
	}

	/*
	 * Event handlers }}}
	 */


	/*
	 * Helper methods. You should not change/derive those in your plugin, but use them only. {{{
	 */

	/**
	 * Get a string, unqiue for the plugin, usable in HTML form elements.
	 *
	 * @param string Optional text to append (gets prefixed with "_").
	 * @return string
	 */
	function get_class_id( $append = '' )
	{
		return $this->classname.'_id'.$this->ID.( $append ? '_'.$append : '' );
	}


	/**
	 * Translate a given string, in the Plugin's context.
	 *
	 * This means, that the translation is obtained from the Plugin's "locales" folder.
	 * @link http://manual.b2evolution.net/Localization#Plugins
	 *
	 * It uses the global/regular {@link T_()} function as fallback.
	 *
	 * {@internal This is mainly a copy of {@link T_()}, for the $use_l10n==2 case.}}
	 *
	 * @param string The string (english), that should be translated
	 * @param string Requested locale ({@link $current_locale} gets used by default)
	 * @return string
	 */
	function T_( $string, $req_locale = '' )
	{
		global $current_locale, $locales, $Debuglog, $plugins_path, $evo_charset;

		$trans = & $this->trans;

		if( empty($req_locale) )
		{ // By default we use the current locale
			if( empty( $current_locale ) )
			{ // don't translate if we have no locale
				return $string;
			}

			$req_locale = $current_locale;
		}

		if( ! isset( $locales[$req_locale]['messages'] ) )
		{
			$this->debug_log( 'No messages file dirname for locale. $locales["'
											.$req_locale.'"] is '.var_export( @$locales[$req_locale], true ), 'locale' );
			$locales[$req_locale]['messages'] = false;
		}

		$messages = $locales[$req_locale]['messages'];

		// replace special characters to msgid-equivalents
		$search = str_replace( array("\n", "\r", "\t"), array('\n', '', '\t'), $string );

		// echo "Translating ", $search, " to $messages<br />";

		if( ! isset($trans[ $messages ] ) )
		{ // Translations for current locale have not yet been loaded:

			if( ! isset($this->classfile_path) )
			{ // ->T_() called through the plugin's constructor, which is deprecated!
				$this->debug_log( 'T_() method called through plugin constructor!' );
				return $string;
			}

			$locales_dir = dirname($this->classfile_path).'/';
			if( $locales_dir == $plugins_path )
			{
				$locales_dir .= $this->classname.'/';
			}
			$locales_dir .= 'locales/';

			// First load the global messages file, if existing:
			if( ! $this->trans_loaded_global )
			{
				$this->trans_loaded_global = true;

				$file_path = $locales_dir.'_global.php';
				if( file_exists($file_path) )
				{
					if( is_readable($file_path) )
					{
						// echo 'LOADING GLOBAL '.$file_path;
						include $file_path;
					}
					else
					{
						$this->debug_log( 'Global messages file '.$file_path.' is not readable!', 'locale' );
					}
				}
			}

			// Then load locale specific files:
			$file_path = $locales_dir.$messages.'/_global.php';

			if( file_exists($file_path) )
			{
				if( is_readable($file_path) )
				{
					// echo 'LOADING '.$file_path;
					include $file_path;
				}
				else
				{
					$this->debug_log( 'Messages file '.$file_path.' for locale '.$req_locale.' is not readable!', 'locale' );
				}
			}

			if( ! isset($trans[ $messages ] ) )
			{ // Still not loaded... file doesn't exist, memorize that no translations are available
				// echo 'file not found!';
				$trans[ $messages ] = array();

				/*
				May be an english locale without translation.
				TODO: when refactoring locales, assign a key for 'original english'.
				$Debuglog->add( 'No messages found for locale ['.$req_locale.'],
												message file [/locales/'.$messages.'/_global.php]', 'locale' );*/
			}
		}

		if( isset( $trans[ $messages ][ $search ] ) )
		{ // If the string has been translated:
			$r = $trans[ $messages ][ $search ];
			$messages_charset = $locales[$req_locale]['charset'];
		}
		else
		{ // Fallback to global T_() function:
			return T_( $string, $req_locale );
		}

		if( ! empty($evo_charset) ) // this extra check is needed, because $evo_charset may not yet be determined.. :/
		{
			$r = convert_charset( $r, $evo_charset, $messages_charset );
		}

		return $r;
	}


	/**
	 * Get the absolute URL to the plugin's directory (trailing slash included).
	 *
	 * This is either below {@link $plugins_url}, if no Blog is set or we're in the
	 * backoffice, or the "plugins" directory below the Blog's URL root otherwise.
	 *
	 * @param string Get absolute URL? (or cut off $ReqHost at the beginning)
	 * @return string
	 */
	function get_plugin_url( $abs = false )
	{
		global $ReqHost, $Blog, $plugins_url, $plugins_path;

		if( isset($Blog) && ! is_admin_page() )
		{
			$base = $Blog->get('baseurl').'plugins/';
		}
		else
		{
			$base = $plugins_url;
		}

		if( strpos( $base, $ReqHost ) !== 0 )
		{ // the base url does not begin with the requested host:

			// Fix "http:" to "https:":
			if( strpos( $ReqHost, 'https:' ) === 0 && strpos( $base, 'http:' ) === 0 )
			{
				$base_fixed = 'https:'.substr( $base, 5 );

				if( strpos( $base_fixed, $ReqHost ) === 0 )
				{
					$base = $base_fixed;
				}
			}
		}

		if( ! $abs && strpos( $base, $ReqHost ) === 0 )
		{ // cut off $ReqHost if the resulting URL starts with it:
			$base = substr($base, strlen($ReqHost));
		}

		// Append sub-path below $plugins_path, if any:
		$sub_path = preg_replace( ':^'.preg_quote($plugins_path, ':').':', '', dirname($this->classfile_path).'/' );

		return $base.$sub_path;
	}


	/**
	 * Log a debug message.
	 *
	 * This gets added to {@link $Debuglog the global Debuglog} with
	 * the category '[plugin_classname]_[plugin_ID]'.
	 *
	 * NOTE: if debugging is not enabled (see {@link $debug}), {@link $Debuglog}
	 * is of class {@link Log_noop}, which means it does not accept nor display
	 * messages.
	 *
	 * @param string Message to log.
	 * @param array Optional list of additional categories.
	 */
	function debug_log( $msg, $add_cats = array() )
	{
		global $Debuglog;

		if( ! is_array($add_cats) )
		{
			$add_cats = array($add_cats);
		}

		if( ! isset($this->ID, $this->classname) )
		{ // plugin not yet instantiated. This happens, if the (deprecated) constructor of a plugin gets used.
			$add_cats[] = get_class($this).'_?';
		}
		else
		{
			$add_cats[] = $this->classname.'_'.$this->ID;
		}
		$Debuglog->add( $msg, $add_cats );
	}


	/**
	 * Get the URL to call a plugin method through http. This links to the /htsrv/call_plugin.php
	 * file.
	 *
	 * @todo we might want to provide whitelisting of methods through {@link $Session} here and check for it in the htsrv handler.
	 *
	 * @param string Method to call. This must be listed in {@link GetHtsrvMethods()}.
	 * @param array Array of optional parameters passed to the method.
	 * @param string Glue for additional GET params used internally.
	 * @return string The URL
	 */
	function get_htsrv_url( $method, $params = array(), $glue = '&amp;' )
	{
		global $htsrv_url;
		global $Session, $localtimenow;

		$r = $htsrv_url.'call_plugin.php?plugin_ID='.$this->ID.$glue.'method='.$method;
		if( !empty( $params ) )
		{
			$r .= $glue.'params='.rawurlencode(serialize( $params ));
		}

		return $r;
	}


	/**
	 * A simple wrapper around the {@link $Messages} object with a default
	 * catgory of 'note'.
	 *
	 * @param string Message
	 * @param string|array category ('note', 'error', 'success'; default: 'note')
	 */
	function msg( $msg, $category = 'note' )
	{
		global $Messages;
		$Messages->add( $msg, $category );
	}


	/**
	 * Register a tab (sub-menu) for the backoffice Tools menus.
	 *
	 * @param string Text for the tab.
	 * @param string|array Path to add the menu entry into.
	 *        See {@link AdminUI::add_menu_entries()}. Default: 'tools' for the Tools menu.
	 * @param array Optional params. See {@link AdminUI::add_menu_entries()}.
	 */
	function register_menu_entry( $text, $path = 'tools', $menu_entry_props = array() )
	{
		global $AdminUI;

		$menu_entry_props['text'] = $text;

		$AdminUI->add_menu_entries( $path, array( 'plug_ID_'.$this->ID => $menu_entry_props ) );
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
		global $Plugins;

		return $Plugins->are_events_available( $events, $by_one_plugin );
	}


	/**
	 * Set param value.
	 *
	 * @param string Name of parameter
	 * @param mixed Value of parameter
	 */
	function set_param( $parname, $parvalue )
	{
		// Set value:
		$this->$parname = $parvalue;
	}


	/**
	 * Set the status of the plugin.
	 *
	 * @param string 'enabled', 'disabled' or 'needs_config'
	 * @return boolean
	 */
	function set_status( $status )
	{
		global $Plugins;

		if( ! in_array( $status, array( 'enabled', 'disabled', 'needs_config' ) ) )
		{
			return false;
		}

		$Plugins->set_Plugin_status( $this, $status );
	}


	/**
	 * Get canonical name for database tables a plugin uses, by adding an unique
	 * prefix for your plugin instance.
	 *
	 * You should use this when refering to your SQL table names.
	 *
	 * E.g., for the "test_plugin" with ID 7 and the default {@link $tableprefix} of "evo_" it
	 * would generate: "evo_plugin_test_7_log" for a requested name "log".
	 *
	 * @param string Your name, which gets returned with the unique prefix.
	 * @return string
	 */
	function get_sql_table( $name )
	{
		global $tableprefix;

		// NOTE: table name length seems limited to 64 chars (MySQL 5) - classname is limited to 40 (in T_plugins)
		return $tableprefix.'plugin_'.preg_replace( '#_plugin$#', '', $this->classname ).'_'.$this->ID.'_'.$name;
	}


	/**
	 * Stop propagation of the event to next plugins (with lower priority)
	 * in events that get triggered for a batch of Plugins.
	 *
	 * @see Plugins::trigger_event()
	 * @see Plugins::stop_propagation()
	 */
	function stop_propagation()
	{
		global $Plugins;
		$Plugins->stop_propagation();
	}


	/**
	 * Set a data value for the session.
	 *
	 * NOTE: the session data is limited to about 64kb, so do not use it for huge data!
	 *       Please consider using an own database table (see {@link Plugin::GetDbLayout()}).
	 *
	 * @param string Name of the data's key (gets prefixed with 'plugIDX_' internally).
	 * @param mixed The value
	 * @param integer Time in seconds for data to expire (0 to disable).
	 * @param boolean Should the data get saved immediately?
	 */
	function session_set( $name, $value, $timeout, $save_immediately = false )
	{
		global $Session;

		$r = $Session->set( 'plugID'.$this->ID.'_'.$name, $value, $timeout );
		if( $save_immediately )
		{
			$Session->dbsave();
		}
		return $r;
	}


	/**
	 * Get a data value for the session, using a unique prefix to the Plugin.
	 * This checks for the data to be expired and unsets it then.
	 *
	 * @param string Name of the data's key (gets prefixed with 'plugIDX_' internally).
	 * @return mixed|NULL The value, if set; otherwise NULL
	 */
	function session_get( $name )
	{
		global $Session;

		return $Session->get( 'plugID'.$this->ID.'_'.$name );
	}


	/**
	 * Delete a value from the session data, using a unique prefix to the Plugin.
	 *
	 * @param string Name of the data's key (gets prefixed with 'plugIDX_' internally).
	 */
	function session_delete( $name )
	{
		global $Session;

		return $Session->delete( 'plugID'.$this->ID.'_'.$name );
	}


	/**
	 * Call this to unregister all your events for the current request.
	 */
	function forget_events()
	{
		global $Plugins;
		$Plugins->forget_events( $this->ID );
	}


	/**
	 * Disable an event.
	 *
	 * This removes it from the events table.
	 *
	 * @return boolean True, if status has changed; false if it was disabled already
	 */
	function disable_event( $event )
	{
		return $this->Plugins->set_event_status( $this->ID, $event, 0 );
	}


	/**
	 * Enable an event.
	 *
	 * This adds it to the events table.
	 *
	 * @return boolean True, if status has changed; false if it was enabled already
	 */
	function enable_event( $event )
	{
		return $this->Plugins->set_event_status( $this->ID, $event, 1 );
	}

	/*
	 * Helper methods }}}
	 */


	/*
	 * Interface methods. You should not override those! {{{
	 *
	 * These are used to access certain plugin internals.
	 */

	/**
	 * Template function: display plugin code
	 */
	function code()
	{
		echo $this->code;
	}


	/**
	 * Template function: Get displayable plugin name.
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean shall we display?
	 * @return string displayable plugin name.
	 */
	function name( $format = 'htmlbody', $disp = true )
	{
		if( $disp )
		{
			echo format_to_output( $this->name, $format );
		}
		else
		{
			return format_to_output( $this->name, $format );
		}
	}


	/**
	 * Template function: display short description for plug in
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean shall we display?
	 * @return string displayable short desc
	 */
	function short_desc( $format = 'htmlbody', $disp = true )
	{
		if( $disp )
		{
			echo format_to_output( $this->short_desc, $format );
		}
		else
		{
			return format_to_output( $this->short_desc, $format );
		}
	}


	/**
	 * Template function: display long description for plug in
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean shall we display?
	 * @return string displayable long desc
	 */
	function long_desc( $format = 'htmlbody', $disp = true )
	{
		if( $disp )
		{
			echo format_to_output( $this->long_desc, $format );
		}
		else
		{
			return format_to_output( $this->long_desc, $format );
		}
	}


	/**
	 * Get a link to a help page (with icon).
	 *
	 * @param string Target; one of the following:
	 *         - anchor to {@link $help_url} ("#anchor")
	 *         - absolute link to some URL, e.g. "http://example.com/example.php"
	 *         - '$help_url' or empty for {@link $help_url}, then also the "www" icon gets used
	 *         - '$readme' to link to the plugin's README.html file (if available)
	 * @return string The html A tag, linking to the help (or empty in case of $readme, if there is none).
	 */
	function get_help_link( $target = '' )
	{
		static $target_counter = 0;
		$title = '';
		$icon = 'help';
		$word = '';
		$link_attribs = array( 'use_js_popup'=>true, 'use_js_size' => '', 'id'=>'anchor_help_plugin_'.$this->ID.'_'.$target_counter++ );

		if( $target == '$help_url' || empty($target) )
		{
			$url = ! empty( $this->help_url ) ? $this->help_url : 'http://manual.b2evolution.net/Plugins/'.$this->classname;
			$title = T_('Homepage of the plugin');
			$icon = 'www';
		}
		elseif( $target == '$readme' )
		{ // README
			if( ! $this->get_help_file() )
			{
				return '';
			}

			global $admin_url;

			$link_attribs['use_js_size'] = '500, 400';
			$title = T_('Local documentation of the plugin');
			$url = url_add_param( $admin_url, 'ctrl=plugins&amp;action=disp_help_plain&amp;plugin_ID='.$this->ID );
			$icon = 'help';
		}
		elseif( substr($target, 0, 1) == '#' )
		{ // anchor
			$help_url = ! empty( $this->help_url ) ? $this->help_url : 'http://manual.b2evolution.net/Plugins/'.$this->classname;
			$url = $help_url.$target;
		}
		elseif( preg_match( '~^https?://~', $target ) )
		{ // absolute URL (strict match to allow other formats later if needed)
			$url = $target;
		}
		else
		{
			debug_die( 'Invalid get_help_link() target: '.$target );
		}

		return action_icon( $title, $icon, $url, $word, 4, 1, $link_attribs );
	}


	/**
	 * @deprecated Backwards compatibility wrapper (for 1.8)
	 */
	function get_README_link()
	{
		return $this->get_help_link('$readme');
	}


	/**
	 * Get the help file for a Plugin ID. README.LOCALE.html will take
	 * precedence above the general (english) README.html.
	 *
	 * @todo Handle encoding of files (to $io_charset)
	 *
	 * @return false|string
	 */
	function get_help_file()
	{
		global $default_locale, $plugins_path, $current_User;

		if( ! $current_User->check_perm( 'options', 'view', false ) )
		{ // README gets displayed through plugins controller, which requires these perms
			// TODO: Catch "disp_help" and "disp_help_plain" messages in plugins.php before general perms check!?
			return false;
		}

		// Get the language. We use $default_locale because it does not have to be activated ($current_locale)
		$lang = substr( $default_locale, 0, 2 );

		$help_dir = dirname($this->classfile_path).'/';
		if( $help_dir == $plugins_path )
		{
			$help_dir .= $this->classname.'/';
		}

		// Try help for the user's locale:
		$help_file = $help_dir.'README.'.$lang.'.html';

		if( ! file_exists($help_file) )
		{ // Fallback: README.html
			$help_file = $help_dir.'README.html';

			if( ! file_exists($help_file) )
			{
				return false;
			}
		}

		return $help_file;
	}


	/**
	 * Get a link to edit the Plugin's settings (if the user has permission).
	 *
	 * @return false|string
	 */
	function get_edit_settings_link()
	{
		global $current_User, $admin_url;

		if( ! $current_User->check_perm( 'options', 'view', false ) )
		{
			return false;
		}

		return action_icon( T_('Edit plugin settings!'), 'edit', $admin_url.'?ctrl=plugins&amp;action=edit_settings&amp;plugin_ID='.$this->ID );
	}

	/*
	 * Interface methods }}}
	 */

}


/*
 * $Log$
 * Revision 1.77  2006/07/23 20:18:31  fplanque
 * cleanup
 *
 * Revision 1.76  2006/07/17 01:19:25  blueyed
 * Added events: AfterUserInsert, AfterUserUpdate, AfterUserDelete
 *
 * Revision 1.75  2006/07/15 19:53:33  blueyed
 * Re-added get_README_link() for backwards compatibility.
 *
 * Revision 1.74  2006/07/10 22:53:38  blueyed
 * Grouping of plugins added, based on a patch from balupton
 *
 * Revision 1.73  2006/07/10 21:05:40  blueyed
 * Enhanced Plugin::T_():
 * Now also uses a global _global.php file, which may hold all translations in one file.
 *
 * Revision 1.72  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.71  2006/07/10 18:53:04  blueyed
 * Fixed method def; added doc
 *
 * Revision 1.70  2006/07/07 21:26:04  blueyed
 * Should have tested it.. ;/
 *
 * Revision 1.69  2006/07/07 21:21:16  blueyed
 * Handle deprecated stuff.
 *
 * Revision 1.68  2006/07/06 21:38:45  blueyed
 * Deprecated plugin constructor. Renamed AppendPluginRegister() to PluginInit().
 *
 * Revision 1.67  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.66  2006/06/30 22:58:13  blueyed
 * Abstracted charset conversation, not much tested.
 *
 * Revision 1.65  2006/06/25 23:43:34  blueyed
 * doc
 *
 * Revision 1.64  2006/06/19 20:59:14  blueyed
 * minor
 *
 * Revision 1.63  2006/06/10 19:16:17  blueyed
 * DisplayTrackbackAddr event
 *
 * Revision 1.62  2006/06/06 20:35:50  blueyed
 * Plugins can define extra events that they trigger themselves.
 *
 * Revision 1.61  2006/06/05 23:15:00  blueyed
 * cleaned up plugin help links
 *
 * Revision 1.60  2006/06/05 18:02:59  blueyed
 * doc
 *
 * Revision 1.59  2006/06/05 17:44:38  blueyed
 * doc
 *
 * Revision 1.58  2006/06/05 15:48:52  blueyed
 * Fix
 *
 * Revision 1.57  2006/06/05 15:38:29  blueyed
 * Fix
 *
 * Revision 1.56  2006/06/05 15:00:29  blueyed
 * Fix get_plugin_url to use https if ReqHost uses https.
 *
 * Revision 1.55  2006/06/05 14:34:31  blueyed
 * Added get_plugin_url()
 *
 * Revision 1.54  2006/06/05 14:26:42  blueyed
 * doc
 *
 * Revision 1.53  2006/05/30 23:08:59  blueyed
 * doc
 *
 * Revision 1.52  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.51  2006/05/24 20:43:19  blueyed
 * Pass "Item" as param to Render* event methods.
 *
 * Revision 1.50  2006/05/22 20:35:37  blueyed
 * Passthrough some attribute of plugin settings, allowing to use JS handlers. Also fixed submitting of disabled form elements.
 *
 * Revision 1.49  2006/05/20 01:56:07  blueyed
 * ItemCanComment hook; "disable anonymous feedback" through basic antispam plugin
 *
 * Revision 1.48  2006/05/17 23:35:42  blueyed
 * cleanup
 *
 * Revision 1.47  2006/05/15 22:26:48  blueyed
 * Event hooks for skin plugins.
 *
 * Revision 1.46  2006/05/13 15:46:40  blueyed
 * doc fix
 *
 * Revision 1.45  2006/05/12 21:53:38  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.44  2006/05/05 19:36:23  blueyed
 * New events
 *
 * Revision 1.43  2006/05/04 10:18:41  blueyed
 * Added Session property to skip page content caching event.
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
 * Revision 1.38  2006/05/01 04:25:05  blueyed
 * Normalization
 *
 * Revision 1.37  2006/04/29 01:04:23  blueyed
 * *** empty log message ***
 *
 * Revision 1.36  2006/04/27 20:07:19  blueyed
 * Renamed Plugin::get_htsrv_methods() to GetHtsvMethods() (normalization)
 *
 * Revision 1.35  2006/04/27 19:44:33  blueyed
 * A plugin can disable events (e.g. after install)
 *
 * Revision 1.34  2006/04/27 19:11:12  blueyed
 * Cleanup; handle broken plugins more decent
 *
 * Revision 1.33  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.32  2006/04/20 22:24:08  blueyed
 * plugin hooks cleanup
 *
 * Revision 1.31  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.29  2006/04/19 18:14:12  blueyed
 * Added "no_edit" param to GetDefault(User)Settings
 *
 * Revision 1.28  2006/04/18 21:09:20  blueyed
 * Added hooks to manipulate Items before insert/update/preview; fixes; cleanup
 *
 * Revision 1.27  2006/04/18 17:06:14  blueyed
 * Added "disabled" to plugin (user) settings (Thanks to balupton)
 *
 * Revision 1.26  2006/04/13 01:36:27  blueyed
 * Added interface method to edit Plugin settings (gets already used by YouTube Plugin)
 *
 * Revision 1.25  2006/04/13 01:23:19  blueyed
 * Moved help related functions back to Plugin class
 *
 * Revision 1.24  2006/04/11 22:09:08  blueyed
 * Fixed validation of negative integers (and also allowed "+" at the beginning)
 *
 * Revision 1.23  2006/04/05 19:16:35  blueyed
 * Refactored/cleaned up help link handling: defaults to online-manual-pages now.
 *
 * Revision 1.22  2006/04/04 22:56:12  blueyed
 * Simplified/refactored uninstalling/registering of a plugin (especially the hooking process)
 *
 * Revision 1.21  2006/03/28 22:24:46  blueyed
 * Fixed logical spam karma issues
 *
 * Revision 1.20  2006/03/21 23:17:17  blueyed
 * doc/cleanup
 *
 * Revision 1.19  2006/03/19 22:38:29  blueyed
 * added get_class_id()
 *
 * Revision 1.18  2006/03/19 19:02:51  blueyed
 * New events for captcha plugins
 *
 * Revision 1.17  2006/03/18 19:39:19  blueyed
 * Fixes for pluginsettings; added "valid_range"
 *
 * Revision 1.16  2006/03/15 23:30:20  blueyed
 * Use plugin classname in SQL table name.
 *
 * Revision 1.15  2006/03/15 21:04:36  blueyed
 * Call Plugin::BeforeEnable also on configuration changes and disable the plugin, if it does not say "ok"
 *
 * Revision 1.14  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.13  2006/03/11 15:49:48  blueyed
 * Allow a plugin to not update his settings at all.
 *
 * Revision 1.12  2006/03/11 01:59:00  blueyed
 * Added Plugin::forget_events()
 *
 * Revision 1.11  2006/03/06 22:42:20  blueyed
 * doc fixes
 *
 * Revision 1.10  2006/03/06 22:07:32  blueyed
 * doc, organized events into subsections
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
 * Revision 1.6  2006/03/02 19:57:52  blueyed
 * Added DisplayIpAddress() and fixed/finished DisplayItemAllFormats()
 *
 * Revision 1.5  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.4  2006/02/27 16:57:12  blueyed
 * PluginUserSettings - allows a plugin to store user related settings
 *
 * Revision 1.3  2006/02/24 22:22:57  blueyed
 * Fix URL for internal help.
 *
 * Revision 1.2  2006/02/24 22:08:59  blueyed
 * Plugin enhancements
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.33  2006/02/15 04:07:16  blueyed
 * minor merge
 *
 * Revision 1.32  2006/02/09 22:05:43  blueyed
 * doc fixes
 *
 * Revision 1.31  2006/02/07 11:14:21  blueyed
 * Help for Plugins improved.
 *
 * Revision 1.25  2006/01/28 21:11:16  blueyed
 * Added helpers for Session data handling.
 *
 * Revision 1.23  2006/01/28 16:59:47  blueyed
 * Removed remove_events_for_this_request() as the problem would be anyway to handle the event where it got called from.
 *
 * Revision 1.21  2006/01/26 23:47:27  blueyed
 * Added password settings type.
 *
 * Revision 1.20  2006/01/26 23:08:36  blueyed
 * Plugins enhanced.
 *
 * Revision 1.19  2006/01/23 01:12:15  blueyed
 * Added get_table_prefix() and remove_events_for_this_request(),
 *
 * Revision 1.18  2006/01/06 18:58:08  blueyed
 * Renamed Plugin::apply_when to $apply_rendering; added T_plugins.plug_apply_rendering and use it to find Plugins which should apply for rendering in Plugins::validate_list().
 *
 * Revision 1.17  2006/01/06 00:27:06  blueyed
 * Small enhancements to new Plugin system
 *
 * Revision 1.16  2006/01/05 23:57:17  blueyed
 * Enhancements to msg() and debug_log()
 *
 * Revision 1.15  2006/01/04 15:03:52  fplanque
 * enhanced list sorting capabilities
 *
 * Revision 1.14  2005/12/23 19:06:35  blueyed
 * Advanced enabling/disabling of plugin events.
 *
 * Revision 1.13  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.12  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.11  2005/11/25 00:28:04  blueyed
 * doc
 *
 * Revision 1.10  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.9  2005/06/22 14:46:31  blueyed
 * help_link(): "renderer" => "plugin"
 *
 * Revision 1.8  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.7  2005/03/14 20:22:20  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.5  2005/03/02 18:30:56  fplanque
 * tedious merging... :/
 *
 * Revision 1.4  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.3  2005/02/22 03:00:57  blueyed
 * fixed Render() again
 *
 * Revision 1.2  2005/02/20 22:34:40  blueyed
 * doc, help_link(), don't pass $param as reference
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.9  2004/10/12 16:12:18  fplanque
 * Edited code documentation.
 *
 */
?>

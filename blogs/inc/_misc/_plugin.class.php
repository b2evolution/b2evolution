<?php
/**
 * This file implements the abstract {@link Plugin} class.
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
 * @package plugins
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
	 * using the {@link T_()} function.
	 *
	 * @var string
	 */
	var $name = '__Unnamed plugin__';


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
	 * URL for more info about plugin, author and new versions.
	 *
	 * This is for user info only.
	 * If there is no website available, a mailto: URL can be provided.
	 *
	 * @var string
	 */
	var $help_url = '';

	/**
	 * Plugin short description.
	 *
	 * This should be no longer than a line.
	 *
	 * @var string
	 */
	var $short_desc = '__No desc available__';

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
	var $long_desc = 'No long description available';


	/**
	 * If this is a rendering plugin, when should rendering apply?
	 *
	 * This is the default value for the plugin and can be overriden in the Plugins
	 * administration for plugins that provide rendering events.
	 *
	 * {@internal The actual value for the plugin gets stored in T_plugins.plug_apply_rendering.}}
	 *
	 * Possible values:
	 * - 'stealth'
	 * - 'always'
	 * - 'opt-out'
	 * - 'opt-in'
	 * - 'lazy'
	 * - 'never'
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
	var $nr_of_installs;

	/**#@-*/


	/**#@+
	 * Variables below MUST NOT be overriden.
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
	 * 0 means 'NOT installed'
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
	 * @see GetDefaultUserSettings()
	 * @var NULL|PluginUserSettings
	 */
	var $UserSettings;


	/**
	 * The status of the plugin.
	 *
	 * @var string Either 'enabled', 'disabled', 'needs_config' or 'broken'.
	 */
	var $status;

	/**#@-*/


	/**
	 * Constructor.
	 *
	 * Should set name and description in a localizable fashion.
	 * NOTE FOR PLUGIN DEVELOPPERS UNFAMILIAR WITH OBJECT ORIENTED DEV:
	 * This function has the same name as the class, this makes it a "constructor".
	 * This means that this function will be called automagically by PHP when this
	 * plugin class is instantiated ("loaded").
	 */
	function Plugin()
	{
		$this->short_desc = T_('No desc available');
		$this->long_desc = T_('No description available');
	}


	// Plugin information (settings, DB layout, ..): {{{

	/**
	 * Define here default settings that are then available in the backoffice.
	 *
	 * You can access them in the plugin through the member object
	 * {@link $Settings}, e.g.:
	 * <code>$this->Settings->get( 'my_param' );</code>
	 *
	 * You probably don't need to set or change values (other than the
	 * defaultvalues), but if you know what you're doing, see
	 * {@link PluginSettings}, where {@link $Settings} gets derived from.
	 *
	 * @return array
	 * The array to be returned should define the names of the settings as keys
	 * and assign an array with the following keys to them (only 'label' is required):
	 *
	 *   - 'label': Name/Title of the param, gets displayed as label for the input field, or
	 *              as "legend" tag with types "array" and "fieldset".
	 *   - 'defaultvalue': Default value for the setting, defaults to '' (empty string)
	 *   - 'type', which can be:
	 *     - 'text' (default): a simple string
	 *     - 'password': like text, but hidden during input
	 *     - 'checkbox': either 0 or 1
	 *     - 'integer': a number (like 'text' for input, but gets validated when submitting)
	 *     - 'textarea': several lines of input. The following can be set for this type:
	 *       - 'rows': number of rows
	 *       - 'cols': number of columns
	 *     - 'select': a drop down field; you must set 'options' for it:
	 *       - 'options': an array of options ('value' => 'description'), see {@link Form::select_input_array()}.
	 *     - 'select_group': a drop down field, providing all existing groups
	 *     - 'select_user': a drop down field, providing all existing groups
	 *     - 'array': a subset of settings. The following keys apply to this type:
	 *       - 'entries': an array with the sub-settings (which can be everything from the top-level)
	 *       - 'max_count': maximum count of sets (optional, default is no restriction)
	 *       - 'min_count': minimum count of sets (optional, default is no restriction)
	 *   - 'note' (gets displayed as a note to the param field),
	 *   - 'size': Size of the HTML input field (applies to types 'text', 'password' and 'integer'; defaults to 15)
	 *   - 'maxlength': maxlength attribute for the input field (See 'size' above; defaults to no limit)
	 *   - 'allow_none': set this to true to have "None" in the options list for types
	 *                   'select_group' and 'select_user'.
	 *   - 'valid_pattern' (a regular expression pattern that the value must match).
	 *                     This is either just a regexp pattern as string or an array
	 *                     with the keys 'pattern' and 'error' to define a custom error message.
	 *   - 'help': either the anchor in the internal help (first param to {@link get_help_icon()})
	 *             or an array with all params to this method.
	 *             E.g., 'param_explained' would link to the internal help's #classname_plugin_param_explained.
	 *   - 'layout': Use this to visually group your settings.
	 *               Either 'begin_fieldset', 'end_fieldset' or 'separator'. You can use 'label' for 'begin_fieldset'.
	 * e.g.:
	 * <code>
	 * return array(
	 *   'my_param' => array(
	 *     'label' => T_('My Param'),
	 *     'defaultvalue' => '10',
	 *     'note' => T_('Quite cool, eh?'),
	 *     'valid_pattern' => array( 'pattern' => '[1-9]\d+', T_('The value must be >= 10.') ),
	 *   ),
	 *   'another_param' => array( // this one has no 'note'
	 *     'label' => T_('My checkbox'),
	 *     'defaultvalue' => '1',
	 *     'type' => 'checkbox',
	 *   ),
	 *   array( 'layout' => 'separator' ),
	 *   'my_select' => array(
	 *     'label' => T_('Selector'),
	 *     'defaultvalue' => 'one',
	 *     'type' => 'select',
	 *     'options' => array( 'sun' => T_('Sunday'), 'mon' => T_('Monday') ),
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
	 * @see test_plugin
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
	 * @see db_delta()
	 */
	function GetDbLayout()
	{
		return array();
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
	function get_htsrv_methods()
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
		$this->register_menu_entry( T_('My Tab') );
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
	 *              'target_type': either 'Comment' or 'Item.
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
	 *              'target_type': either 'Comment' or 'Item.
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
	 * This is the hook to remove any DB tables or the like.
	 *
	 * You might want to call "parent::BeforeUninstall()" in your plugin to handle canonical
	 * database tables, which is done here.
	 *
	 * See {@link UninstallPayload()} for the corresponding payload handler.
	 *
	 * @param array Associative array of parameters.
	 *              'handles_display': Setting it to true avoids a generic "Uninstall failed" message.
	 *              'unattended': true if Uninstall is unattended (Install action "deletedb"). Removes tables without confirmation.
	 * @return boolean|NULL true on success, false on failure (the plugin won't get uninstalled then).
	 *         NULL requests to execute the {@link BeforeUninstallPayload()} method.
	 */
	function BeforeUninstall( & $params )
	{
		global $DB;
		if( $this->tables_to_delete_on_uninstall = $DB->get_col( 'SHOW TABLES LIKE "'.$this->get_sql_table('%').'"' ) )
		{
			if( empty($params['unattended']) && ! param( 'plugin_'.$this->ID.'_confirm_drop', 'integer', 0 ) )
			{ // not confirmed and not silently requested: request call to BeforeUninstallPayload()
				return NULL;
			}

			// Drop tables:
			$sql = 'DROP TABLE IF EXISTS '.implode( ', ', $this->tables_to_delete_on_uninstall );
			$DB->query( $sql );
			if( empty($params['unattended']) )
			{
				$this->msg( T_('Dropped plugin tables.'), 'success' );
			}
		}

		return true;
	}


	/**
	 * Event handler: Gets invoked to display the payload before uninstalling the plugin.
	 *
	 * By default, this method asks the admin for confirmation if he wants to delete
	 * the plugin's tables (detected through table prefix).
	 *
	 * You can override or extend this method to display your own payload that has to be
	 * confirmed.
	 *
	 * See {@link BeforeUninstall()} for the corresponding action handler.
	 */
	function BeforeUninstallPayload()
	{
		?>

		<div class="panelinfo">

			<?php
			$Form = & new Form( '', 'uninstall_plugin', 'get' );
			$Form->global_icon( T_('Cancel delete!'), 'close', regenerate_url() );

			$Form->begin_form( 'fform', sprintf( /* %d is ID, %d name */ T_('Uninstall plugin #%d (%s)'), $this->ID, $this->name ) );

			echo '<p>'.T_('Uninstalling this plugin will also delete its database tables:')
				.'<ul>'
				.'<li>'
				.implode( '</li><li>', $this->tables_to_delete_on_uninstall )
				.'</li>'
				.'</ul>'
				.'</p>';

			echo '<p>'.T_('THIS CANNOT BE UNDONE!').'</p>';

			$Form->hidden( 'action', 'uninstall' );
			$Form->hidden( 'plugin_ID', $this->ID );
			$Form->hidden( 'plugin_'.$this->ID.'_confirm_drop', 1 );

			// We may need to use memorized params in the next page
			$Form->hiddens_by_key( get_memorized( 'action,plugin_ID') );

			$Form->submit( array( '', T_('I am sure!'), 'DeleteButton' ) );
			$Form->end_form();
			?>

		</div>

		<?php
	}


	/**
	 * Event handler: Called when the admin tries to enable the plugin and also
	 * after installation.
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
	 * @return boolean Have we changed something?
	 */
	function RenderItem()
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when rendering item/post contents other than XML or HTML.
	 *
	 * This is different from {@link RenderItem()}, {@link RenderItemAsHtml()} and {@link RenderItemAsXml()}:
	 *  - It applies on every display (rendering will get cached later)
	 *  - It calls all Plugins that register this event, not just associated ones.
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 *   - 'Item': The {@link Item} that gets displayed (by reference).
	 * @return boolean Have we changed something?
	 */
	function DisplayItemAllFormats( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: called at the end of {@link Item::dbupdate() updating
	 * an item/post in the database}, which means that it has changed.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function AfterItemUpdate( & $params )
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
	 * Event handler: Called when the view counter of an item got increased.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the Item object (by reference)
	 */
	function ItemViewed( & $params )
	{
	}

	// }}}


	// Comment events: {{{

	/**
	 * Event handler: Called at the end of the frontend comment form.
	 *
	 * You might want to use this to inject antispam payload to use in
	 * in {@link GetKarmaForComment()} or modify the Comment according
	 * to it in {@link CommentFormSent()}.
	 *
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
	 * Event handler: Called when a comment form got submitted.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the Item for which the comment is meant (by reference)
	 */
	function CommentFormSent( & $params )
	{
	}


	/**
	 * Event handler: Called to ask the plugin for the karma of a comment.
	 *
	 * This gets called just before the comment gets stored.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the {@link Comment} object (by reference)
	 *   - 'karma_absolute': Absolute karma (by reference)
	 *   - 'karma_max': Maximum karma (by reference)
	 */
	function GetKarmaForComment( & $params )
	{
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

	// }}}


	// Caching events: {{{

	/**
	 * Event handler: called to cache object data.
	 *
	 * @see memcache_plugin::CacheObjects()
	 * @param array Associative array of parameters
	 *   - 'action': 'delete', 'set', 'get'
	 *   - 'key': The key to refer to 'data'
	 *   - 'data': The actual data.
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
	 * @see Plugin::CacheIsCollectingContent()
	 * @see memcache_plugin::CachePageContent()
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


	/**
	 * Event handler: Called after the {@link Plugin::Settings Settings object of the Plugin}
	 * has been instantiated.
	 *
	 * Use this to validate Settings and/or cache them into class properties.
	 *
	 * @return boolean If false gets returned the Plugin gets unregistered (for the current request only).
	 */
	function PluginSettingsInstantiated()
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


	/**
	 * Event handler: Called after the {@link Plugin::UserSettings UserSettings object of the Plugin}
	 * has been instantiated.
	 *
	 * Use this to validate user Settings and/or cache them into class properties.
	 *
	 * @return boolean If false gets returned the Plugin gets unregistered (for the current request only).
	 */
	function PluginUserSettingsInstantiated()
	{
	}
	// }}}


	// User related events (including registration and login): {{{

	/**
	 * Event handler: Called when a new user has registered, at the end of the
	 *                DB transaction that creates this user.
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the user object (as reference), see {@link User}.
	 * @return boolean True, if the user should be created, false if not.
	 */
	function AppendUserRegistrTransact( & $params )
	{
	}


	/**
	 * Event handler: Called at the end of the "Register as new user" form.
	 *
	 * You might want to use this to inject antispam payload to use
	 * in {@link RegisterFormSent()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 */
	function DisplayRegisterFormFieldset( & $params )
	{
	}


	/**
	 * Event handler: Called when a "Register as new user" form got submitted.
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
	 *   - 'Form': the comment form generating object
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
	 * @param array Associative array of parameters
	 *   - 'login': user's login
	 *   - 'pass': user's password
	 *   - 'pass_md5': user's md5 password
	 */
	function LoginAttempt( $params )
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
	function DisplayIpAddress( & $params )
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

		$add_cats[] = $this->classname.'_'.$this->ID;
		$Debuglog->add( $msg, $add_cats );
	}


	/**
	 * Get the URL to call a plugin method through http. This links to the /htsrv/call_plugin.php
	 * file.
	 *
	 * @todo we might want to provide whitelisting of methods through {@link $Session} here and check for it in the htsrv handler.
	 *
	 * @param string Method to call. This must be listed in {@link get_htsrv_methods()}.
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
	 * prefix for your plugin instance ("plugin_ID[ID]_").
	 *
	 * You should use this when refering to your SQL table names.
	 *
	 * @see Plugin::sql_delta()
	 * @param string Your name, which gets returned with the unique prefix.
	 * @return string
	 */
	function get_sql_table( $name )
	{
		global $tableprefix;

		return $tableprefix.'plugin_ID'.$this->ID.'_'.$name;
	}


	/**
	 * This is a magic method you should use to create your custom database tables
	 * (if you want to create and use any): You give a list of queries to it and
	 * it will alter the DB to fit your table schema..
	 *
	 * @see Plugin::get_sql_table()
	 * @uses db_delta()
	 *
	 * @param string|array A single query as string or a list of queries as array
	 * @param boolean Execute the generated SQL right away?
	 * @return array An empty array, if no alterations were needed, otherwise a quite complex
	 *               list of what was generated. Please see {@link db_delta()} for details.
	 */
	function sql_delta( $queries, $execute = true )
	{
		require_once( dirname(__FILE__).'/_upgrade.funcs.php' );

		return db_delta( $queries, $execute );
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
	 * Display a help link.
	 *
	 * The anchor (if given) should be defined (as HTML id attribute for a headline) in either
	 *  - the README.html file (prefixed with the plugin's classname, e.g.
	 *    'test_plugin_anchor' when 'anchor' gets passed here)
	 *  - or the page that {@link $help_url} links to.
	 *  (depending on the $external param).
	 *
	 * @param string HTML anchor for a specific help topic (empty to not use it).
	 *               When linking to the internal help, it gets prefixed with "[plugin_classname]_".
	 * @param string Title for the icon/legend
	 * @param boolean Use external help? See {@link $help_url}.
	 * @param string Word to use after the icon.
	 * @param string Icon to use. Default for 'internal' is 'help', and for 'external' it is 'www'. See {@link $map_iconfiles}.
	 * @param array Additional link attributes. See {@link action_link()}
	 * @return string|false The html A tag, linking to the help.
	 *         False if internal help requested, but not available (see {@link Plugins::get_help_file()}).
	 */
	function get_help_icon( $anchor = NULL, $title = NULL, $external = false, $word = NULL, $icon = NULL, $link_attribs = array() )
	{
		global $admin_url;

		if( $external )
		{
			if( empty($this->help_url) )
			{
				return false;
			}
			$url = $this->help_url;
			if( ! empty($anchor) )
			{
				$url .= '#'.$anchor;
			}

			if( ! isset($link_attribs['target']) )
			{ // open in a new window
				$link_attribs['target'] = '_blank';
			}
		}
		else
		{ // internal help:
			if( ! $this->get_help_file() )
			{
				return false;
			}

			if( isset( $link_attribs['action'] ) )
			{
				$action = $link_attribs['action'];
				unset($link_attribs['action']);
			}
			else
			{
				$action = 'disp_help_plain';

				if( ! isset($link_attribs['use_js_popup']) )
				{
					$link_attribs['use_js_popup'] = true;

					if( ! isset($link_attribs['id']) )
					{
						$link_attribs['id'] = 'plugin_'.$this->ID.'_help_'.$anchor.rand(0, 10000);
					}
				}
			}

			$url = url_add_param( $admin_url, 'ctrl=plugins&amp;action='.$action.'&amp;plugin_ID='.$this->ID );
			if( ! empty($anchor) )
			{
				$url .= '#'.$this->classname.'_'.$anchor;
			}
		}

		if( is_null($icon) )
		{
			$icon = $external ? 'www' : 'help';
		}

		if( is_null($title) )
		{
			$title = $external ? T_('Homepage of the plugin') : T_('Local documentation of the plugin');
		}

		return action_icon( $title, $icon, $url, $word, $link_attribs );
	}


	/**
	 * Get the help file for a Plugin ID. README.LOCALE.html will take
	 * precedence above the general (english) README.html.
	 *
	 * @return false|string
	 */
	function get_help_file()
	{
		global $default_locale, $plugins_path;

		// Get the language. We use $default_locale because it does not have to be activated ($current_locale)
		$lang = substr( $default_locale, 0, 2 );

		$help_dir = $plugins_path.$this->classname.'/';

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

	/*
	 * Interface methods }}}
	 */

}


/* {{{ Revision log:
 * $Log$
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
 * }}}
 */
?>
<?php
/**
 * This file implements the abstract Plugin class.
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
	var $name = 'Unnamed plug-in';


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
	 * Range: 1 to 100
	 *
	 * @var int
	 */
	var $priority = 50;

	/**
	 * Plugin version number.
	 *
	 * This is for user info only.
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
	 * This shoulb be no longer than a line.
	 *
	 * @var string
	 */
	var $short_desc = 'No desc available';

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
	var $long_desc = 'No description available';


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
	 * @see GetDefaultSettings()
	 * @var NULL|PluginSettings
	 */
	var $Settings;

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


	/**
	 * Define here default settings that are then available in the backoffice.
	 *
	 * You can access them in the plugin through the member object
	 * {@link $Settings}, e.g.:
	 * <code>$this->Settings->get( 'my_param' );</code>
	 *
	 * You probably don't need to set or change values (other than the
	 * defaultvalues), but if you know what you're doing, see
	 * {@link PluginSettings()}, where {@link $Settings} gets derived from.
	 *
	 * The array to be returned should define the names of the settings as keys
	 * and assign an array with the following keys to them:
	 *   'label' (Name of the param)
	 * and optionally:
	 *   'defaultvalue' (default value, defaults to '')
	 *   'note' (gets displayed as a note to the param field),
	 *   'size', 'maxlength' (html input field attributes),
	 *   'type' ('checkbox', 'textarea', 'password', 'array', 'text' (default)),
	 *   'rows' (number of rows for type=='textarea'),
	 *   'cols' (number of cols for type=='textarea'),
	 *   'valid_pattern' (a regular expression pattern that the value must match)
	 *                   Either a pattern as string or an array with keys
	 *                   'pattern' and 'error' for a custom error message.
	 * e.g.:
	 * <code>
	 * return array(
	 *   'my_param' => array(
	 *     'label' => T_('My Param!'),
	 *     'defaultvalue' => '1',
	 *     'note' => T_('Quite cool, eh?'),
	 *     'valid_pattern' => array( 'pattern' => '\d+', T_('The value must be numeric.') ),
	 *   ),
	 *   'another_param' => array( // this one has no 'note'
	 *     'label' => T_('My checkbox'),
	 *     'defaultvalue' => '1',
	 *     'type' => 'checkbox',
	 *   ) );
	 * </code>
	 *
	 * @return array
	 */
	function GetDefaultSettings()
	{
		return array();
	}


	/*
	 * Event handlers. These are meant to be implemented by your plugin. {{{
	 */

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
	 *
	 * This displays the payload for handling uninstallation of Plugin database tables.
	 * If you want to use this, call parent::AdminBeginPayload() in your Plugin.
	 *
	 * @see BeforeUninstall() for the corresponding action handler
	 */
	function AdminBeginPayload()
	{
		if( ! empty($this->display_confirm_for_uninstall_tables) )
		{
			?>

			<div class="panelinfo">

				<h3><?php echo T_('Delete plugin database tables') ?></h3>

				<p><?php echo T_('Uninstalling this plugin will also delete its database tables.') ?></p>

				<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

				<?php
				$Form = & new Form( '', 'uninstall_plugin', 'get' );

				$Form->begin_form( 'inline' );
				$Form->hidden( 'action', 'uninstall' );
				$Form->hidden( 'plugin_ID', $this->ID );
				$Form->hidden( 'plugin_'.$this->ID.'_confirm_drop', 1 );

				// We may need to use memorized params in the next page
				$Form->hiddens_by_key( get_memorized( 'action,plugin_ID') );

				$Form->submit( array( '', T_('I am sure!'), 'DeleteButton' ) );
				$Form->end_form();

				$Form = & new Form( '', 'uninstall_plugin_cancel', 'get' );
				$Form->begin_form( 'inline' );
				$Form->button( array( 'submit', '', T_('CANCEL'), 'CancelButton' ) );
				$Form->end_form()
				?>

			</div>

			<?php
		}
	}


	/**
	 * Event handler: Called before the plugin is going to be installed.
	 *
	 * This is the hook to create any DB tables or the like.
	 *
	 * @return boolean true on success, false on failure (the plugin won't get installed then).
	 */
	function BeforeInstall()
	{
		return true;
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
	 * @see AdminBeginPayload() for the corresponding payload handler
	 * @param array Associative array of parameters.
	 *              'handles_display': Setting it to true avoids a generic "Uninstall failed" message.
	 *              'unattended': true if Uninstall is unattended (Install action "deletedb"). Removes tables without confirmation.
	 * @return boolean true on success, false on failure (the plugin won't get uninstalled then).
	 */
	function BeforeUninstall( & $params )
	{
		global $DB;
		if( $tables = $DB->get_col( 'SHOW TABLES LIKE "'.$this->get_table_prefix().'%"' ) )
		{
			if( empty($params['unattended']) && ! param( 'plugin_'.$this->ID.'_confirm_drop', 'integer', 0 ) )
			{ // not confirmed and not silently requested
				$this->display_confirm_for_uninstall_tables = true; // see AdminBeginPayload()
				return false;
			}

			// Drop tables:
			$sql = 'DROP TABLE IF EXISTS '.implode( ', ', $tables );
			$DB->query( $sql );
			if( empty($params['unattended']) )
			{
				$this->msg( T_('Dropped plugin tables.'), 'success' );
			}
		}

		return true;
	}


	/**
	 * Event handler: called when a user attemps to login.
	 *
	 * @param array Associative array of parameters
	 *              'login': user's login
	 *              'pass': user's password
	 *              'pass_md5': user's md5 password
	 */
	function LoginAttempt( $params )
	{
	}


	/**
	 * Event handler: Called when a new user has registered, at the end of the
	 *                DB transaction that creates this user.
	 *
	 * @param array Associative array of parameters
	 *              'User': the user object (as reference), see {@link User}.
	 * @return boolean True, if the user should be created, false if not.
	 */
	function AppendUserRegistrTransact( & $params )
	{
	}


	/**
	 * Event handler: Called when rendering item/post contents as HTML.
	 *
	 * Note: You have to change $params['content'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 * @return boolean Did we do something?
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
	 * Note: You have to change $params['content'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'xml' will arrive here.
	 * @return boolean Did we do something?
	 */
	function RenderItemAsXml( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when rendering item/post contents other than XML or HTML.
	 *
	 * blueyed>> Still wondering if this is useful at all..
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 *               Only formats other than 'htmlbody', 'entityencoded' and 'xml' will arrive here.
	 * @return boolean Did we do something?
	 */
	function RenderItem()
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


	/**
	 * Event handler: called at the end of {@link DataObject::dbupdate() updating
	 * a data object in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'DataObject': the related dataobject
	 *   - 'classname': DataObject's classname, lowercased (e.g. 'item')
	 */
	function AfterDataObjectUpdate( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link DataObject::dbinsert() inserting
	 * a data object into the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'DataObject': the related dataobject
	 *   - 'classname': DataObject's classname, lowercased (e.g. 'item')
	 */
	function AfterDataObjectInsert( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link DataObject::dbdelete() deleting
	 * a data object from the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'DataObject': the related dataobject
	 *   - 'classname': DataObject's classname, lowercased (e.g. 'item')
	 */
	function AfterDataObjectDelete( & $params )
	{
	}


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


	/**
	 * Event handler: Called before setting a plugin's setting in the backoffice.
	 *
	 * @param array Associative array of parameters
	 *   - 'name': name of the setting
	 *   - 'value': value of the setting (by reference)
	 * @return boolean false to prevent the setting being set.
	 */
	function PluginSettingsBeforeSet( & $params )
	{
	}


	/**
	 * Event handler: Called as action before displaying the payload
	 * to edit the plugin's settings.
	 */
	function PluginSettingsEditAction( & $params )
	{
	}


	/**
	 * Event handler: Called after the {@link Plugin::Settings Settings object of the Plugin}
	 * has been instantiated.
	 *
	 * Use this to validate Settings or cache them into class properties.
	 */
	function PluginSettingsInstantiated( & $params )
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
	 *   - 'Item': the Item for which the comment is meant
	 */
	function CommentFormSent( & $params )
	{
	}


	/*
	 * Event handlers }}}
	 */


	/*
	 * Helper methods. You should not change/derive those in your plugin, but use them only. {{{
	 */

	/**
	 * Log a message. This gets added to {@link $Debuglog the global Debuglog} with
	 * the category '[plugin_classname]_[plugin_ID]'.
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
	 * Get canonical prefix for database tables a plugin might create.
	 *
	 * @return string
	 */
	function get_table_prefix()
	{
		global $tableprefix;

		return $tableprefix.'plugin_ID'.$this->ID.'_';
	}


	/**
	 * Remove a list of Plugin events for the current request.
	 *
	 * @todo Needs to break out of a triggered event that caused the Plugin to call this.
	 * @param array List of events.
	 */
	function remove_events_for_this_request( $events )
	{
		global $Plugins;

		if( ! is_array($events) )
		{
			$events = array( $events );
		}

		foreach( $events as $event )
		{
			if( isset($Plugins->index_event_IDs[$event]) )
			{
				while( ($key = array_search( $this->ID, $Plugins->index_event_IDs[$event] )) !== false )
				{
					unset( $Plugins->index_event_IDs[$event][$key] );
				}
			}
		}
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
	 * @return displayable plugin name.
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
	 * @return displayable short desc
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
	 * @return displayable long desc
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
	 * Display link to the help for this plugin (if set in {@link $help_url}).
	 *
	 * @return boolean true if it was displayed; false if not
	 */
	function help_link()
	{
		if( !empty($this->help_url) )
		{ // Link to the help for this renderer plugin
			echo ' <a href="'.$this->help_url.'"'
						.' target="_blank" title="'.T_('Open help for this plugin in a new window.').'">'
						.get_icon( 'help' )
						.'</a>';
			return true;
		}

		return false;
	}

	/*
	 * Interface methods }}}
	 */

}

/* {{{ Revision log:
 * $Log$
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
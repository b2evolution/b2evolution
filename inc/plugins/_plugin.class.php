<?php
/**
 * This file implements the abstract {@link Plugin} class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 *
 * @todo Add links to pages on b2evolution.net/man, once they are "clean"/tiny
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
	 * To make it available for translation set it in the constructor by
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
	 * By increasing it, you can request a call of {@link GetDbLayout()} upon instantiating.
	 * If there are DB layout changes to be made, the plugin gets changed to status "needs_config".
	 *
	 * @var string
	 */
	var $version = '0.0.1';

	/**
	 * Plugin author.
	 *
	 * This is for user info only.
	 *
	 * @var string
	 */
	var $author = 'Unknown author';

	/**
	 * The topic of the plugin manual page in the b2evolution manual.
	 *
	 * If empty, it defaults to [plugin_classname], which is the plugin's PHP class name.
	 *
	 * @var string
	 */
	var $help_topic = '';

	/**
	 * URL for more info about the plugin, author and new versions.
	 *
	 * This is for user info only.
	 *
	 * If empty, it defaults to {@link get_manual_url( Plugin->help_topic )}.
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


	/**
	 * Name of the ping service (if this is a ping plugin, see {@link Plugin::ItemSendPing()})
	 * @var string
	 */
	var $ping_service_name;

	/**
	 * Note about the ping service, used in the list of ping services in the blog settings
	 * (if this is a ping plugin, see {@link Plugin::ItemSendPing()})
	 * @var string
	 */
	var $ping_service_note;

	/**#@-*/


	/**#@+
	 * Variables below MUST NOT be overriden or changed by you!
	 * @access private
	 */

	/**
	 * Name of the current class. (AUTOMATIC)
	 *
	 * Will be set automatically (from filename) when registering the plugin.
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
	 * This gets instantianted on Plugin registration for PHP4 and through
	 * overloading in PHP5+, which means on first access.
	 *
	 * @see GetDefaultSettings()
	 * @var PluginSettings
	 */
	var $Settings;


	/**
	 * If the plugin provides user settings, this will become the object to access them.
	 *
	 * This gets instantianted on Plugin registration for PHP4 and through
	 * overloading in PHP5+, which means on first access.
	 *
	 * NOTE: its methods use {@link $current_User::$ID} by default, but you may call it
	 *       if there's no {@link $current_User} instantiated (yet).
	 *
	 * @see GetDefaultUserSettings()
	 * @var PluginUserSettings
	 */
	var $UserSettings;


	/**
	 * The status of the plugin.
	 *
	 * Use {@link set_status()} to change it, if you need to.
	 * Either 'enabled', 'disabled', 'needs_config' or 'broken'.
	 *
	 * @var string
	 */
	var $status;

	/**
	 * The translations keyed by locale. They get loaded through include() of _global.php.
	 * @see Plugin::T_()
	 * @var array
	 */
	var $_trans = array();

	/**
	 * Has the global /locales/_global.php file (where translation for
	 * all languages can be put into) been loaded?
	 *
	 * @var boolean
	 */
	var $_trans_loaded_global = false;

	/**
	 * Plugin URL
	 * This gets lazy-filled URL by {@link get_plugin_url()}.
	 *
	 * @var string
	 */
	private $_plugin_url;

	/**
	 * Plugin template
	 *
	 * @var array
	 */
	var $template;

	/**#@-*/


	/**
	 * Constructor.
	 *
	 * You should not use a constructor with your plugin, but the
	 * {@link Plugin::PluginInit()} method instead!
	 */
	function __construct()
	{
	}


	/**
	 * Init the Plugin after it has been registered/instantiated.
	 *
	 * Should set name and description in a localizable fashion.
	 *
	 * You DON'T NEED to include this, if you override this method.
	 *
	 * This gets called on every instantiated plugin, also if it's just for discovering the list of available plugins in the backoffice.
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
		// NOTE: the code below is just to handle stuff that has been deprecated since b2evolution 1.9.
		// You DON'T NEED to include this, if you override this method.

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


	/**
	 * Load template params from current skin
	 */
	function load_template()
	{
		$params = array(
				'toolbar_before'       => '<div class="edit_toolbar $toolbar_class$">',
				'toolbar_after'        => '</div>',
				'toolbar_title_before' => '',
				'toolbar_title_after'  => '',
				'toolbar_group_before' => '',
				'toolbar_group_after'  => ' ',
				'toolbar_button_class' => 'quicktags',
			);

		if( is_admin_page() )
		{ // Get plugin template from backoffice skin
			if( is_logged_in() )
			{
				global $current_User, $UserSettings, $adminskins_path;
				$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
				require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
				$AdminUI = new AdminUI();
				$skin_template = $AdminUI->get_template( 'plugin_template' );
			}
		}
		else
		{ // Get plugin template from frontoffice skin
			global $Collection, $Blog;
			if( ! empty( $Blog ) )
			{
				$skin_ID = $Blog->get_skin_ID();
				$SkinCache = & get_SkinCache();
				if( $Skin = & $SkinCache->get_by_ID( $skin_ID, false, false ) )
				{
					$skin_template = $Skin->get_template( 'plugin_template' );
				}
			}
		}

		if( empty( $skin_template ) )
		{ // Use default template params when they are not set by skin
			$this->template = $params;
		}
		else
		{ // Merge default params with current skin params
			$this->template = array_merge( $params, $skin_template );
		}
	}


	/**
	 * Get param of the current loaded template
	 *
	 * @param string Param name
	 * @param array What should be replaced in the param (e.g. array( '$toolbar_class$' => 'quicktags_toolbar' ) )
	 */
	function get_template( $param_name, $replaces = NULL )
	{
		if( is_null( $this->template ) )
		{ // Load template only first time
			$this->load_template();
		}

		if( ! isset( $this->template[ $param_name ] ) )
		{ // Return empty string if no defined param
			return '';
		}

		$param_value = $this->template[ $param_name ];

		if( is_array( $replaces ) )
		{
			foreach( $replaces as $search => $replace )
			{
				$param_value = str_replace( $search, $replace, $param_value );
			}
		}

		return $param_value;
	}


	// Plugin information (settings, DB layout, ..): {{{

	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * You can access them in the plugin through the member object
	 * {@link Plugin::$Settings}, e.g.:
	 * <code>$this->Settings->get( 'my_param' );</code>
	 *
	 * fp> this is unclear: You probably don't need to set or change values (other than the
	 * defaultvalues), but if you know what you're doing, see
	 * {@link PluginSettings}, where {@link Plugin::$Settings} gets derived from.
	 *
	 * NOTE: this method gets called by b2evo when instantiating the plugin
	 *       settings and when the settings get displayed for editing in the backoffice.
	 *       In the second case, $params['for_editing'] will be true.
	 *
	 * @todo 3.0 fp> 1) This is not an event: RENAME to lowercase (in b2evo 3.0)
	 *           dh> Not only events are CamelCase, but "interactions" with the Plugins(_admin) class, too!
	 *               Maybe it should get prefixed with "Plugin"?!
	 *               The intention is: all interfacing methods are camel-cased. That makes a lot of sense,
	 *               given the provided helpers (get_plugin_url etc).
	 *               This applies to the other todos below, too.
	 * @todo 3.0 fp> 2) This defines more than Default values ::  confusing name
	 * @todo name tentative get_general_param_definitions()
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 *
	 * 'label': Name/Title of the param, gets displayed as label for the input field, or
	 *              as "legend" tag with types "array" and "fieldset".
	 * 'defaultvalue': Default value for the setting, defaults to '' (empty string)
	 * 'type', which can be:
	 *     'text' (default): an input field to enter a string
	 *     'string': static text to print out on plugin settings edit form the 'label' of a param
	 *     'begin_line': start a group of several params in one by <code>$Form->begin_line( param[label] );</code> (Use together with end_line)
	 *     'end_line': start a group of several params in one by <code>$Form->end_line( param[label] );</code> (Use together with begin_line)
	 *     'password': like text, but hidden during input
	 *     'html_input' : like text, but allows html
	 *     'checkbox': a checkbox field to set values either 0 or 1
	 *     'checklist': a field or several checkboxes, you must set 'options' for it:
	 *         'options': an array of options ( 'name', 'value' ), see {@link Form::checklist()}.
	 *     'integer': a number (no float, can have leading "+" or "-") (like 'text' for input, but gets validated when submitting)
	 *     'float': a floating number (can have leading "+" or "-", e.g. "+1", "-0.05") (like 'text' for input, but gets validated when submitting)
	 *     'textarea': several lines of input. The following can be set for this type:
	 *         'rows': number of rows
	 *         'cols': number of columns
	 *     'html_textarea': like textarea, but allows html
	 *     'select': a drop down field; you must set 'options' for it:
	 *         'options': an array of options ('value' => 'description'), see {@link Form::select_input_array()}.
	 *     'select_blog': a drop down field, providing all existing blogs (Blog ID is the value or "" if "allow_none" is true) (WARNING: does not scale - not recommended)
	 *     'select_group': a drop down field, providing all existing groups (Group ID is the value or "" if "allow_none" is true)
	 *     'select_user': a drop down field, providing all existing groups (User ID is the value or "" if "allow_none" is true) (WARNING: does not scale - not recommended)
	 *     'radio' : radio select (options on same line by default)
	 *          'field_lines' : set to true to have options on separate line
	 *     'array': a subset of settings without type forcing or checking
	 *                 for sub-entries involved (e.g., if you have an entry of type "integer", you could get
	 *                 a non-numeric string there). NOTE: VERY UNSAFE!
	 *     'array:integer': a subset of settings with type forcing to integer
	 *     'array:array:integer':  a subset of settings with type forcing to integer
	 *     'array:string': a subset of settings with type forcing to string
	 *     'array:array:string': a subset of settings with type forcing to string
	 *     'array:regexp': a subset of settings with type forcing by regexp
	 *         The value gets automagically (un)serialized through get() and set().
	 *         The following keys apply to these types:
	 *        'entries': an array with meta information about sub-settings
	 *             (which can be everything from the top-level, except: "valid_pattern", "valid_range").
	 *        'key': defines the key to use for each entry. This may be a text input for example
	 *              (with label, note etc). (optional, default is numeric keys, which are not editable)
	 *        'max_count': maximum count of sets (optional, default is no restriction)
	 *        'min_count': minimum count of sets (optional, default is no restriction)
	 *	   'fileselect': opens a modal window to select file. The following can be set for this type:
	 *        'root': default root
	 *		  'path': default path
	 *        'thumbnail_size': thumbnail size
	 *     'info': a form info field with label and info text see {@link Form::info()}; you must set 'info' for text.
	 *     'color': a form color picker field, use 'defaultvalue' in format '#FFFFFF'
	 * 'note' (gets displayed as a note to the param field),
	 * 'info': Text for param with type 'info'
	 * 'size': Size of the HTML input field (applies to types 'text', 'password' and 'integer'; defaults to 15)
	 * 'maxlength': maxlength attribute for the input field (See 'size' above; defaults to no limit)
	 * 'disabled': if true, it adds a 'disabled="disabled"' html attribute to the element and the value cannot be changed
	 * 'no_edit': if true, the setting is not editable. This is useful for internal settings.
	 * 'allow_none': set this to true to have "None" in the options list for types 'select_group' and 'select_user'.
	 * 'allow_empty': true to allow empty values, e.g. when type=integer but the value is not required
	 * 'valid_pattern': A regular expression pattern that the value must match.
	 *                      This is either just a regexp pattern as string or an array
	 *                      with the keys 'pattern' and 'error' to define a custom error message.
	 * 'valid_range': An array with keys 'min', 'max' and (optionally) 'error' to define
	 *                    a custom error message. At least "min" or "max" must be given.
	 * 'help': can be:
	 *          '#anchor': anchor that gets appended to {@link $help_url}
	 *          true: the settings name/key gets transformed to an html ID and gets used as anchor to {@link $help_url}.
	 *          'http://example.com/uri': a full URL (starting with http:// or https://)
	 * 'layout': Use this to visually group your settings.
	 *               Either 'begin_fieldset', 'end_fieldset' or 'separator'. You can use 'label' for 'begin_fieldset'.
	 * 'multiple': This allows to select multiple values in a SELECT (including select_*) (boolean)
	 * 'id', 'onchange', 'onclick', 'onfocus', 'onkeyup', 'onkeydown', 'onreset', 'onselect', 'cols', 'rows', 'maxlength':
	 *       get passed through as attributes to the form/input element.
	 *
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
	function GetDefaultSettings( & $params )
	{
		return array();
	}


	/**
	 * Define the PER-USER settings of the plugin here. These can then be edited by each user.
	 *
	 * You can access them in the plugin through the member object
	 * {@link $UserSettings}, e.g.:
	 * <code>$this->UserSettings->get( 'my_param' );</code>
	 *
	 * This method behaves exactly like {@link Plugin::GetDefaultSettings()},
	 * except that it defines user specific settings instead of global settings.
	 *
	 * @todo 3.0 fp> 1) This is not an event: RENAME to lowercase (in b2evo 3.0)
	 * @todo 3.0 fp> 2) This defines more than Default values ::  confusing name
	 * @todo name tentative get_user_param_definitions()
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function GetDefaultUserSettings( & $params )
	{
		return array();
	}


	/**
	 * Define here default custom settings that are to be made available
	 *     in the backoffice for collections, private messages and newsletters.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_custom_setting_definitions( & $params )
	{
		return array();
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_settings = array();

		// Set default collection plugin settings depending on group:
		switch( $this->group )
		{
			case 'rendering':
				// Renderer plugins:
				$render_note = '';
				if( empty( $this->code ) )
				{
					$render_note .= T_('Note: The plugin code is empty, so this plugin will not work as an "opt-out", "opt-in" or "lazy" renderer.');
				}
				$admin_Plugins = & get_Plugins_admin();
				$rendering_options = $admin_Plugins->get_apply_rendering_values( true );
				$default_post_rendering = ( isset( $params['default_post_rendering'] ) && in_array( $params['default_post_rendering'], $rendering_options ) ) ? $params['default_post_rendering'] : 'opt-out';
				$default_comment_rendering = ( isset( $params['default_comment_rendering'] ) && in_array( $params['default_comment_rendering'], $rendering_options ) ) ? $params['default_comment_rendering'] : 'never';
				$default_settings = array(
					'coll_apply_rendering' => array(
							'label'        => T_('Apply rendering to posts'),
							'type'         => 'select',
							'options'      => $rendering_options,
							'defaultvalue' => $default_post_rendering,
							'note'         => $render_note,
						),
					'coll_apply_comment_rendering' => array(
							'label'        => T_('Apply rendering to comments'),
							'type'         => 'select',
							'options'      => $rendering_options,
							'defaultvalue' => $default_comment_rendering,
							'note'         => $render_note,
						),
					);
				break;

			case 'editor':
				// Editor plugins:
				$default_post_using = isset( $params['default_post_using'] ) ? $params['default_post_using'] : 1;
				if( $default_post_using != 'disabled' )
				{
					$default_settings['coll_use_for_posts'] = array(
							'label'        => T_('Use for posts'),
							'type'         => 'checkbox',
							'defaultvalue' => $default_post_using,
							'note'         => '',
						);
				}
				$default_comment_using = isset( $params['default_comment_using'] ) ? $params['default_comment_using'] : 1;
				if( $default_comment_using != 'disabled' )
				{
					$default_settings['coll_use_for_comments'] = array(
							'label'        => T_('Use for comments'),
							'type'         => 'checkbox',
							'defaultvalue' => $default_comment_using,
							'note'         => '',
						);
				}
				break;
		}

		return array_merge( $default_settings, $this->get_custom_setting_definitions( $params ) );
	}


	/**
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		if( $this->group != 'rendering' )
		{
			return $this->get_custom_setting_definitions( $params );
		}

		$render_note = '';
		if( empty( $this->code ) )
		{
			$render_note .= T_('Note: The plugin code is empty, so this plugin will not work as an "opt-out", "opt-in" or "lazy" renderer.');
		}
		$admin_Plugins = & get_Plugins_admin();
		$rendering_options = $admin_Plugins->get_apply_rendering_values( true );
		$default_msg_rendering = ( isset( $params['default_msg_rendering'] ) && in_array( $params['default_msg_rendering'], $rendering_options ) ) ? $params['default_msg_rendering'] : 'never';
		$r = array(
			'msg_apply_rendering' => array(
					'label' => T_('Apply rendering to messages'),
					'type' => 'select',
					'options' => $rendering_options,
					'defaultvalue' => $default_msg_rendering,
					'note' => $render_note,
				),
			);

		return array_merge( $r, $this->get_custom_setting_definitions( $params ) );
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		if( $this->group != 'rendering' )
		{
			return $this->get_custom_setting_definitions( $params );
		}

		$render_note = '';
		if( empty( $this->code ) )
		{
			$render_note .= T_('Note: The plugin code is empty, so this plugin will not work as an "opt-out", "opt-in" or "lazy" renderer.');
		}
		$admin_Plugins = & get_Plugins_admin();
		$rendering_options = $admin_Plugins->get_apply_rendering_values( true );
		$default_email_rendering = ( isset( $params['default_email_rendering'] ) && in_array( $params['default_email_rendering'], $rendering_options ) ) ? $params['default_email_rendering'] : 'never';
		$r = array(
			'email_apply_rendering' => array(
					'label' => T_('Apply rendering to emails'),
					'type' => 'select',
					'options' => $rendering_options,
					'defaultvalue' => $default_email_rendering,
					'note' => $render_note,
				),
			);

		return array_merge( $r, $this->get_custom_setting_definitions( $params ) );
	}


	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		if( ! is_array( $params ) )
		{	// Must be array:
			$params = array();
		}

		// Load new widget to get default param definitions:
		load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );
		$ComponentWidget = new ComponentWidget();

		return array_merge( $ComponentWidget->get_param_definitions( array() ), $params );
	}


	/**
	 * Get keys for block/widget caching
	 *
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @param integer Widget ID
	 * @return array of keys this widget depends on
	 */
	function get_widget_cache_keys( $widget_ID = 0 )
	{
		// Load new widget to get default cache keys:
		load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );
		$ComponentWidget = new ComponentWidget();

		$widget_cache_keys = $ComponentWidget->get_cache_keys();

		$widget_cache_keys['wi_ID'] = $widget_ID;

		return $widget_cache_keys;
	}


	/**
	 * Get the list of dependencies that the plugin has.
	 *
	 * This gets checked on install or uninstall of a plugin.
	 *
	 * There are two <b>classes</b> of dependencies:
	 *  - 'recommends': This is just a recommendation. If it cannot get fulfilled
	 *                  there will just be a note added on install.
	 *  - 'requires': A plugin cannot be installed if the dependencies cannot get
	 *                fulfilled. Also, a plugin cannot get uninstalled, if another
	 *                plugin depends on it.
	 *
	 * Each <b>class</b> of dependency can have the following types:
	 *  - 'events_by_one': A list of eventlists that have to be provided by a single plugin,
	 *                     e.g., <code>array( array('CaptchaPayload', 'CaptchaValidated') )</code>
	 *                     to look for a plugin that provides both events.
	 *  - 'plugins':
	 *    A list of plugins, either just the plugin's classname or an array with
	 *    classname and minimum version of the plugin (see {@link Plugin::$version}).
	 *    E.g.: <code>array( 'test_plugin', '1' )</code> to require at least version "1"
	 *          of the test plugin.
	 *  - 'app_min': Minimum application (b2evo) version, e.g. "1.9".
	 *               This way you can make sure that the hooks you need are implemented
	 *               in the core.
	 *               (Available since b2evo 1.8.3. To make it work before 1.8.2 use
	 *               "api_min" and check for array(1, 2) (API version of 1.9)).
	 *  - 'api_min': You can require a specific minimum version of the Plugins API here.
	 *               If it's just a number, only the major version is checked against.
	 *               To check also for the minor version, you have to give an array:
	 *               array( major, minor ).
	 *               Obsolete since 1.9! Used API versions: 1.1 (b2evo 1.8.1) and 1.2 (b2evo 1.9).
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
	 * adjust the queries here and increase {@link Plugin::$version}, because this will
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
	 * This is useful for things like AJAX or displaying an IFRAME element, where the content
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


	/**
	 * This method gets asked for a list of cronjobs that the plugin
	 * provides.
	 * If a user installs a cron job out of this list, the
	 * {@link Plugin::ExecCronJob()} of the plugin gets called.
	 *
	 * @return array Array of arrays with keys "name", "ctrl" and "params".
	 *               "name" gets used for display. "ctrl" (string) and
	 *               "params" (array) get passed to the
	 *               {@link Plugin::ExecCronJob()} method when the cronjob
	 *               gets executed.
	 */
	function GetCronJobs( & $params )
	{
		return array();
	}


	/**
	 * Execute/handle a cron job, which has been scheduled by the admin out
	 * of the list that the Plugin provides (see {@link GetCronJobs()}).
	 *
	 * @param array Associative array of parameters
	 *   - 'ctrl': The "ctrl" name as defined in {@link GetCronJobs()}
	 *   - 'params': The "params" value as defined in {@link GetCronJobs()},
	 *               plus "ctsk_ID" which holds the cron task ID.
	 * @return array with keys "code" (integer, 1 is ok), "message" (gets logged)
	 */
	function ExecCronJob( & $params )
	{
	}

	// }}}


	/*
	 * Event handlers. These are meant to be implemented by your plugin. {{{
	 */

	// Admin/backoffice events (without events specific to Items or Comments): {{{


	/**
	 * Event handler: Gets invoked in /toolbar.inc.php after the menu structure is built.
	 */
	function AdminAfterEvobarInit()
	{
	}


	/**
	 * Event handler: Gets invoked in /admin.php for every backoffice page after
	 *                the menu structure is built. You could use the {@link $AdminUI} object
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
	 * Event handler: Called when displaying editor buttons (in back-office).
	 *
	 * This method, if implemented, should output the buttons (probably as html INPUT elements)
	 * and return true, if button(s) have been displayed.
	 *
	 * You should provide an unique html ID with each button.
	 *
	 * @param array Associative array of parameters.
	 *   - 'target_type': either 'Comment' or 'Item' or 'EmailCampaign'.
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display a button?
	 */
	function AdminDisplayEditorButton( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying editor buttons (in front-office).
	 *
	 * This method, if implemented, should output the buttons (probably as html INPUT elements)
	 * and return true, if button(s) have been displayed.
	 *
	 * You should provide an unique html ID with each button.
	 *
	 * @param array Associative array of parameters.
	 *   - 'target_type': either 'Comment' or 'Item'.
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display a button?
	 */
	function DisplayEditorButton( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @param array Associative array of parameters
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
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


	/**
	 * Event handler: Called at the beginning of the "Edit wdiget" form on back-office.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'ComponentWidget': the Widget which gets edited (by reference)
	 * @return boolean did we display something?
	 */
	function WidgetBeginSettingsForm( & $params )
	{
		return false; // Do nothing by default.
	}


	/**
	 * Event handler: Called at the end of the "Edit wdiget" form on back-office.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'ComponentWidget': the Widget which gets edited (by reference)
	 * @return boolean did we display something?
	 */
	function WidgetEndSettingsForm( & $params )
	{
		return false; // Do nothing by default.
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
	 * Event handler: Gets invoked when an action request was called which should be blocked in specific cases
	 *
	 * Blocakble actions: comment post, user login/registration, email send/validation, account activation
	 */
	function BeforeBlockableAction()
	{
	}


	/**
	 * Event handler: Called when a MainList object gets created.
	 *
	 * Note: you must create your own MainList object here, set filters and query the database, see init_MainList() for detailes.
	 *
	 * @param array Associative array of parameters
	 *   - 'MainList': The "MainList" object (by reference).
	 *   - 'limit': The number of posts to display
	 * @return boolean True if you've created your own MainList object and queried the database, false otherwise.
	 */
	function InitMainList( & $params )
	{
		// Do nothing by default:
		return false;
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlHead( & $params )
	{
	}


	/**
	 * Event handler: Called at the end of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @param array Associative array of parameters
	 */
	function SkinEndHtmlHead( & $params )
	{
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML BODY section.
	 *
	 * Use this to add any HTML snippet at the beginning of the generated page.
	 *
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlBody( & $params )
	{
	}


	/**
	 * Event handler: Called at the end of the skin's HTML BODY section.
	 *
	 * Use this to add any HTML snippet at the end of the generated page.
	 *
	 * @param array Associative array of parameters
	 */
	function SkinEndHtmlBody( & $params )
	{
	}


	/**
	 * Event handler: Gets called before skin wrapper.
	 *
	 * Use this to add any HTML code before skin wrapper and after evo toolbar.
	 *
	 * @param array Associative array of parameters
	 */
	function BeforeSkinWrapper( & $params )
	{
	}

	/**
	 * Called when a plugin gets called by its {@link $code}.
	 *
	 * If you provide this event, b2evolution will assume your plugin
	 * provides a widget and list it in the "Available widgets" list.
	 *
	 * @see $code
	 * @see Plugins::call_by_code()
	 * @param array The array passed to {@link Plugins::call_by_code()}.
	 */
	function SkinTag( & $params )
	{
	}

	/**
	 * Event handler: Gets asked about a list of skin names that the plugin handles.
	 *
	 * If one of the skins returned gets called through the "skin=X" URL param, the
	 * {@link Plugin::DisplaySkin()} method of your plugin gets called.
	 *
	 * @return array
	 */
	function GetProvidedSkins()
	{
		return array();
	}


	/**
	 * Event handler: Display a skin. Use {@link Plugin::GetProvidedSkins()} to return
	 * a list of names that you register.
	 *
	 * @param array Associative array of parameters
	 *   - 'skin': name of skin to be displayed (from the list of {@link Plugin::GetProvidedSkins()}).
	 *             If your Plugin only registers one skin, you can ignore it.
	 */
	function DisplaySkin( & $params )
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


	// Blog events: {{{

	/**
	 * Event handler: called at the end of {@link Blog::dbinsert() inserting
	 * a blog into the database}, which means it has been created.
	 *
	 * @param array Associative array of parameters
	 *   - 'Blog': the related Blog (by reference)
	 */
	function AfterCollectionInsert( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link Blog::dbupdate() updating
	 * a blog in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Blog': the related Blog (by reference)
	 */
	function AfterCollectionUpdate( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link Blog::dbdelete() deleting
	 * a blog from the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Blog': the related Blog (by reference)
	 */
	function AfterCollectionDelete( & $params )
	{
	}


	/**
	 * Event handler: Defines blog kinds, their names and description.
	 * Define blog settings in {@link Plugin::InitCollectionKinds()} method of your plugin.
	 *
	 * Note: You can change default blog kinds $params['kinds'] (which get passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'kinds': dafault blog kinds (by reference)
	 * @retun: array
	 */
	function GetCollectionKinds( & $params )
	{
		return array();
	}


	/**
	 * Event handler: Defines blog settings by its kind. Use {@link get_collection_kinds()} to return
	 * an array of available blog kinds and their names.
	 * Define new blog kinds in {@link Plugin::GetCollectionKinds()} method of your plugin.
	 *
	 * Note: You have to change $params['Blog'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'Blog': created Blog (by reference)
	 *   - 'kind': the kind of created blog (by reference)
	 */
	function InitCollectionKinds( & $params )
	{
	}

	// }}}


	// Item events: {{{

	/**
	 * Event handler: Called when rendering item/post contents as HTML. (CACHED)
	 *
	 * The rendered content will be *cached* and the cached content will be reused on subsequent displays.
	 * Use {@link DisplayItemAsHtml()} instead if you want to do rendering at display time.
	 *
 	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
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
	 * Event handler: Called prepare params before rendering attachments of item contents.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function PrepareForRenderItemAttachment( & $params )
	{
		return false; // Do nothing by default.
	}


	/**
	 * Event handler: Called when rendering attachments of item contents.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderItemAttachment( & $params )
	{
		return false; // Do nothing by default.
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
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
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
	 *   - 'format': see {@link format_to_output()}. Only 'text' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function RenderItemAsText( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying an item/post's content as HTML.
	 *
	 * This is different from {@link RenderItemAsHtml()}, because it gets called
	 * on every display (while rendering gets cached).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 *   - 'Item': The {@link Item} that gets displayed (by reference).
	 *   - 'preview': Is this only a preview?
	 *   - 'dispmore': Does this include the "more" text (if available), which means "full post"?
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function DisplayItemAsHtml( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying an item/post's content as XML.
	 *
	 * This is different from {@link RenderItemAsXml()}, because it gets called
	 * on every display (while rendering gets cached).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 *   - 'Item': The {@link Item} that gets displayed (by reference).
	 *   - 'preview': Is this only a preview?
	 *   - 'dispmore': Does this include the "more" text (if available), which means "full post"?
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function DisplayItemAsXml( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying an item/post's content as text.
	 *
	 * This is different from {@link RenderItemAsText()}, because it gets called
	 * on every display (while rendering gets cached).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'text' will arrive here.
	 *   - 'Item': The {@link Item} that gets displayed (by reference).
	 *   - 'preview': Is this only a preview?
	 *   - 'dispmore': Does this include the "more" text (if available), which means "full post"?
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function DisplayItemAsText( & $params )
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
	 * an item/post in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 *   - 'dbchanges': array with DB changes; a copy of {@link Item::dbchanges()},
	 *                  before they got applied (since 1.9)
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
	 *   - 'dbchanges': array with DB changes; a copy of {@link Item::dbchanges()},
	 *                  before they got applied (since 1.9)
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
	 * Event handler: Called at the end of the "Edit item" form.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'Item': the Item which gets edited (by reference)
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display something?
	 */
	function AdminDisplayItemFormFieldset( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called at the end of the "Edit item" form in front-office.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'Item': the Item which gets edited (by reference)
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display something?
	 */
	function DisplayItemFormFieldset( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called before an item gets deleted (in the backoffice).
	 *
	 * You could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the comment from being deleted.
	 *
	 * @since 2.0
	 * @param array Associative array of parameters
	 *              'Item': the Item which gets created (by reference)
	 */
	function AdminBeforeItemEditDelete( & $params )
	{
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
	 * Event handler: send a ping about a new item.
	 *
	 * @param array Associative array of parameters
	 *        'Item': the Item (by reference)
	 *        'xmlrpcresp': Set this to the {@link xmlrpcresp} object, if the plugin
	 *                      uses XMLRPC.
	 *        'display': Should results get displayed? (normally you should not need
	 *                   to care about this, especially if you can set 'xmlrpcresp')
	 * @return boolean Was the ping successful?
	 */
	function ItemSendPing( & $params )
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


	/**
	 * Event handler: Does your Plugin want to apply as a renderer for the item?
	 *
	 * NOTE: this is especially useful for lazy Plugins, which would look
	 *       at the content and decide, if they apply.
	 *
	 * @param array Associative array of parameters
	 *   - 'Item': the {@link Item} object (by reference)
	 * @return boolean|NULL If true, the Plugin gets added as a renderer, false
	 *         removes it as a renderer (if existing) and NULL does not change the
	 *         renderer setting regarding your Plugin.
	 */
	function ItemApplyAsRenderer( & $params )
	{
	}

	// }}}


	// Feedback (Comment/Trackback) events: {{{

	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called at the end of the "Edit comment" form on back-office.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'Comment': the Comment which gets edited (by reference)
	 *   - 'edit_layout': only NULL currently, as there's only one layout
	 * @return boolean did we display something?
	 */
	function AdminDisplayCommentFormFieldset( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called at the end of the front-office comment form.
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
	 * front-office comment form.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'Item': the Item for which the comment is meant
	 */
	function DisplayCommentFormButton( & $params )
	{
	}


	/**
	 * Event handler: Called before at the beginning, if a comment form gets sent (and received).
	 *
	 * Use this to filter input, e.g. the OpenID uses this to provide alternate authentication.
	 *
	 * If you need to delegate to another service (what OpenID does), you need to remember all
	 * these params (use array_keys($params)) and restore them when coming back.
	 * Only comment_item_ID is required at the beginning of comment_post.php (where this hook)
	 * is located (and has to be passed via GET/POST) - all other params can get stored in a
	 * local session and restored when coming back (this is recommended.)
	 *
	 * @since 1.10.0
	 * @see Plugin::DisplayCommentFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'comment_item_ID': ID of the item the comment is for
	 *   - 'comment': the comment text (by reference)
	 *   - 'original_comment': the original, unfiltered comment text - you should not modify it here,
	 *      this is meant e.g. for the OpenID plugin to re-inject it after redirection (by reference)
	 *   - 'action': "save" or "preview" (by reference)
	 *   - 'User': {@link User}, if logged in or null (by reference)
	 *   - 'anon_name': Name of the anonymous commenter (by reference)
	 *   - 'anon_email': E-Mail of the anonymous commenter (by reference)
	 *   - 'anon_url': URL of the anonymous commenter (by reference)
	 *   - 'anon_allow_msgform': "Allow msgform" preference of the anonymous commenter (by reference)
	 *   - 'anon_cookies': "Remember me" preference of the anonymous commenter (by reference)
	 *   - 'redirect_to': URL where to redirect to in the end of comment posting (by reference)
	 *   - 'crumb_comment': Crumb expected for the comment (see {@link Session::assert_received_crumb()})
	 *     (by reference).
	 */
	function CommentFormSent( & $params )
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
	 *   - 'original_comment': this is the unstripped and unformated posted comment
	 *   - 'action': "save" or "preview" (by reference) (since 1.10)
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
	 *   - 'original_comment': this is the unstripped and unformated posted comment
	 */
	function AfterCommentFormInsert( & $params )
	{
	}


	/**
	 * Event handler: Called to ask the plugin for the spam karma of a comment/trackback.
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
	 *                Only if you return a numeric value, it gets considered (e.g., "", NULL or false get ignored).
	 */
	function GetSpamKarmaForComment( & $params )
	{
		return;
	}


	/**
	 * Event handler: called at the end of {@link Comment::dbupdate() updating
	 * a comment in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 *   - 'dbchanges': array with DB changes; a copy of {@link Comment::dbchanges()},
	 *                  before they got applied (since 1.9)
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
	 *   - 'dbchanges': array with DB changes; a copy of {@link Comment::dbchanges()},
	 *                  before they got applied (since 1.9)
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
	 * Event handler: Gets called after a trackback has been recorded.
	 *
	 * @param array Associative array of parameters
	 *   - 'Comment': the trackback (which is a {@link Comment} object with "trackback" type) (by reference)
	 *        The trackback-params get mapped like this:
	 *        - "blog_name" => "author"
	 *        - "url" => "author_url"
	 *        - "title"/"excerpt" => "comment"
	 */
	function AfterTrackbackInsert( & $params )
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
	 * Event handler: called to filter the comment's author URL.
	 * This may be either the URL only or a full link (A tag).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the URL of the author/blog (by reference)
	 *   - 'makelink': true, if the "data" contains a link (HTML A tag)
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
		if( $this->group == 'rendering' )
		{ // All plugin from 'rendering' group has a 'coll_apply_comment_rendering' setting
			$Comment = & $params['Comment'];
			$comment_Item = & $Comment->get_Item();
			$comment_Blog = & $comment_Item->get_Blog();
			if( in_array( $this->code, $Comment->get_renderers_validated() ) )
			{ // Always allow rendering for comment
				$render_params = array_merge(  array( 'data' => & $Comment->content ), $params );
				$this->RenderItemAsHtml( $render_params );
			}
		}
	}

	// }}}


	// Message form events: {{{

	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called at the end of the front-office message form, which
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
	 * front-office message form.
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
	 * Event handler: Called before at the beginning, if a message of thread form gets sent (and received).
	 *
	 * Use this to filter input
	 *
	 * @param array Associative array of parameters
	 *   - 'content': the message text (by reference)
	 */
	function MessageThreadFormSent( & $params )
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
	 *   - 'sender_name': The name of the sender (by reference) (since 1.10.0)
	 *   - 'sender_email': The email address of the sender (by reference) (since 1.10.0)
	 *   - 'subject': The subject of the message to be sent (by reference) (since 1.10.0)
	 *   - 'message': The message to be sent (by reference)
	 *   - 'Blog': The blog, depending on the context (may be null) (by reference) (since 1.10.0)
	 */
	function MessageFormSent( & $params )
	{
	}


	/**
	 * Event handler: Called after a message has been sent through the public email form.
	 *
	 * This is meant to cleanup generated data.
	 *
	 * @param array Associative array of parameters
	 *   - 'success_message' (bool): true if the message has been sent, false otherwise
	 */
	function MessageFormSentCleanup( & $params )
	{
	}


	/**
	 * Event handler: called to filter the message's content
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the name of the author/blog (by reference)
	 *   - 'Message': the {@link Comment} object
	 */
	function FilterMsgContent( & $params )
	{
		if( $this->group == 'rendering' )
		{ // All plugin from 'rendering' group has a 'msg_apply_rendering' setting
			$Message = & $params['Message'];
			if( in_array( $this->code, $Message->get_renderers_validated() ) )
			{ // Always allow rendering for message
				$render_params = array_merge(  array( 'data' => & $Message->text ), $params );
				$this->RenderMessageAsHtml( $render_params );
			}
		}
	}

	/**
	 * Event handler: Called when rendering message contents as HTML. (CACHED)
	 *
	 * The rendered content will be *cached* and the cached content will be reused on subsequent displays.
	 * Use {@link DisplayContentAsHtml()} instead if you want to do rendering at display time.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function RenderMessageAsHtml( & $params )
	{
		// Use this render by default temporarily
		return $this->RenderItemAsHtml( $params );
	}


	/**
	 * Event handler: Called prepare params before rendering attachments of message contents.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Message': the {@link Message} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function PrepareForRenderMessageAttachment( & $params )
	{
		return false; // Do nothing by default.
	}


	/**
	 * Event handler: Called when rendering attachments of message contents.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Message': the {@link Message} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderMessageAttachment( & $params )
	{
		// Use this render by default temporarily:
		return $this->RenderItemAttachment( $params );
	}

	// }}}


	// Email form events: {{{

	/**
	 * Event handler: Called when displaying editor toolbars for email.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEmailToolbar( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called before at the beginning, if an email form gets sent (and received).
	 *
	 * Use this to filter input
	 *
	 * @param array Associative array of parameters
	 *   - 'content': the message text (by reference)
	 */
	function EmailFormSent( & $params )
	{
	}


	/**
	 * Event handler: called to filter the email's content
	 *
	 * @param array Associative array of parameters
	 *   - 'EmailCampaign': the {@link EmailCampaign} object
	 */
	function FilterEmailContent( & $params )
	{
		if( $this->group == 'rendering' )
		{	// All plugin from 'rendering' group has a 'email_apply_rendering' setting
			$EmailCampaign = & $params['EmailCampaign'];
			if( in_array( $this->code, $EmailCampaign->get_renderers_validated() ) )
			{ // Always allow rendering for $EmailCampaign
				$render_params = array_merge( array( 'data' => & $EmailCampaign->email_text ), $params );
				$this->RenderEmailAsHtml( $render_params );
			}
		}
	}

	/**
	 * Event handler: Called when rendering email contents as HTML. (CACHED)
	 *
	 * The rendered content will be *cached* and the cached content will be reused on subsequent displays.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'EmailCampaign': the {@link EmailCampaign} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderEmailAsHtml( & $params )
	{
		// Use this render by default temporarily
		return $this->RenderItemAsHtml( $params );
	}


	/**
	 * Event handler: Called prepare params before rendering attachments of email campaign contents.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'EmailCampaign': the {@link EmailCampaign} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function PrepareForRenderEmailAttachment( & $params )
	{
		return false; // Do nothing by default.
	}


	/**
	 * Event handler: Called when rendering attachments of email campaign contents.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'EmailCampaign': the {@link EmailCampaign} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderEmailAttachment( & $params )
	{
		// Use this render by default temporarily
		return $this->RenderItemAttachment( $params );
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


	/**
	 * This gets called before an image thumbnail gets created.
	 *
	 * This is useful to post-process the thumbnail image (add a watermark or change colors).
	 *
	 * @param array Associative array of parameters
	 *   - 'File': the related File (by reference)
	 *   - 'imh': image resource (by reference)
	 *   - 'size': size name (by reference)
	 *   - 'mimetype': mimetype of thumbnail (by reference)
	 *   - 'quality': JPEG image quality [0-100] (by reference)
	 *   - 'root_type': file root type 'user', 'group', 'collection' etc. (by reference)
	 *   - 'root_type_ID': ID of user, group or collection (by reference)
	 */
	function BeforeThumbCreate( & $params )
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
	 *   - 'action': 'display' or 'set'
	 * @return string|NULL Return a string with an error to prevent the setting from being set
	 *                     and/or a message added to the settings field.
	 */
	function PluginSettingsValidateSet( & $params )
	{
	}


	/**
	 * Event handler: Called as action just before updating the {@link Plugin::$Settings plugin's settings}.
	 *
	 * The "regular" settings from {@link GetDefaultSettings()} have been set into
	 * {@link Plugin::$Settings}, but get saved into DB after this method has been called.
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
	 * which includes the display of the {@link Plugin::$Settings plugin's settings}.
	 *
	 * You may want to use this to check existing settings or display notes about
	 * something.
	 */
	function PluginSettingsEditAction()
	{
	}


	/**
	 * Event handler: Called after the form to edit the {@link Plugin::$Settings} has been
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
	 *   - 'action': 'display' or 'set'
	 * @return string|NULL Return a string with an error to prevent the setting from being set
	 *                     and/or a message added to the settings field.
	 */
	function PluginUserSettingsValidateSet( & $params )
	{
	}


	/**
	 * Event handler: Called as action just before updating the {@link Plugin::$UserSettings plugin's user settings}.
	 *
	 * The "regular" settings from {@link GetDefaultUserSettings()} have been set into
	 * {@link Plugin::$UserSettings}, but get saved into DB after this method has been called.
	 *
	 * Use this to catch custom input fields from {@link PluginUserSettingsEditDisplayAfter()} or
	 * add notes/errors through {@link Plugin::msg()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User} for which the settings get updated
	 *   - 'action': "save", "reset"
	 *
	 * @return false|NULL Return false to prevent the settings from being updated to DB.
	 */
	function PluginUserSettingsUpdateAction( & $params )
	{
	}


	/**
	 * Event handler: Called as action before displaying the "Edit user" form,
	 * which includes the display of the {@link Plugin::$UserSettings plugin's user settings}.
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
	 * Event handler: Called after the form to edit the {@link Plugin::$UserSettings} has been
	 * displayed.
	 *
	 * Use this to add custom input fields (and catch them in {@link PluginUserSettingsUpdateAction()})
	 * or display custom output (e.g. a test link).
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form}, where an fieldset has been opened already (by reference)
	 *   - 'User': the {@link User} whose settings get displayed for editing (since 1.10.0)
	 */
	function PluginUserSettingsEditDisplayAfter( & $params )
	{
	}

	// }}}

	// PluginCollSettings {{{
	/**
	 * Event handler: Called as action just before updating plugin's collection/blog settings.
	 *
	 * Use this to add notes/errors through {@link Plugin::msg()} or to process saved settings.
	 */
	function PluginCollSettingsUpdateAction()
	{
	}

	// }}}

	// PluginMsgSettings {{{
	/**
	 * Event handler: Called as action just before updating plugin's messages settings.
	 *
	 * Use this to add notes/errors through {@link Plugin::msg()} or to process saved settings.
	 */
	function PluginMsgSettingsUpdateAction()
	{
	}

	// }}}

	// PluginEmailSettings {{{
	/**
	 * Event handler: Called as action just before updating plugin's email campaign settings.
	 *
	 * Use this to add notes/errors through {@link Plugin::msg()} or to process saved settings.
	 */
	function PluginEmailSettingsUpdateAction()
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
	 * asimo> There is no message with "login_error" class anymore,
	 * there is a $login_error global variable
	 */
	function AfterLoginAnonymousUser( & $params )
	{
	}


	/**
	 * Event handler: Called at the end of the login procedure, if the
	 *                {@link $current_User current User} is set and the
	 *                user is therefor registered.
	 *
	 * Use this for example to re-act on specific {@link Plugin::$UserSettings user settings},
	 * e.g., call {@link Plugin::forget_events()} to de-activate the plugin for
	 * the current request.
	 *
	 * You can also {@link Plugin::msg() add a message} of class "login_error"
	 * to prevent the user from accessing the site and triggering
	 * the login screen.
	 * asimo> There is no message with "login_error" class anymore,
	 * there is a $login_error global variable
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
	 * Event handler: Called at the begining of the "Register as new user" form.
	 *
	 * You might want to use this to inject antispam payload to use
	 * in {@link Plugin::RegisterFormSent()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 *   - 'inskin': boolean true if the form is displayed in skin
	 */
	function DisplayRegisterFormBefore( & $params )
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
	 *   - 'inskin': boolean true if the form is displayed in skin
	 */
	function DisplayRegisterFormFieldset( & $params )
	{
	}


	/**
	 * Event handler: Called when a "Register as new user" form has been submitted.
	 *
	 * You can cancel the registration process by {@link Plugin::msg() adding a message}
	 * of type "error".
	 *
	 * @param array Associative array of parameters
	 *   - 'login': Login name (by reference) (since 1.10.0)
	 *   - 'email': E-Mail value (by reference) (since 1.10.0)
	 *   - 'locale': Locale value (by reference) (since 1.10.0)
	 *   - 'pass1': Password (by reference) (since 1.10.0)
	 *   - 'pass2': Confirmed password (by reference) (since 1.10.0)
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
	 * To check, if a user already exists in b2evo with that login/password, you might
	 * want to use <code>user_pass_ok( $login, $pass_md5, true )</code>.
	 *
	 * NOTE: if 'pass_hashed' is not empty, you won't receive the password in clear-type. It
	 *       has been hashed using client-side Javascript.
	 *       SHA1( MD5($params['pass']).$params['pass_salt'] ) should result in $params['pass_hashed']!
	 *       If you need the raw password, see {@link LoginAttemptNeedsRawPassword()}.
	 *
	 * @see Plugin::AlternateAuthentication()
	 * @see Plugin::Logout()
	 * @param array Associative array of parameters
	 *   - 'login': user's login (by reference since 1.10.0)
	 *   - 'pass': user's password (by reference since 1.10.0)
	 *   - 'pass_md5': user's md5 password (by reference since 1.10.0)
	 *   - 'pass_salt': the salt used in "pass_hashed" (by reference)
	 *   - 'pass_hashed': if non-empty this is the users passwords hashed. See note above. (by reference)
	 *   - 'pass_ok': is the password ok for 'login'? (by reference) (since 1.10.0)
	 */
	function LoginAttempt( & $params )
	{
	}


	/**
	 * Event handler: your Plugin should return true here, if it needs a raw (un-hashed)
	 * password for the {@link Plugin::LoginAttempt()} event. If any Plugin returns true
	 * for this event, client-side hashing of the password is not used.
	 * NOTE: this causes passwords to travel un-encrypted, unless SSL/HTTPS get used.
	 *
	 * @return boolean True, if you need the raw password.
	 */
	function LoginAttemptNeedsRawPassword()
	{
		return false;
	}


	/**
	 * Event handler: called when a user logs out.
	 *
	 * This is meant to cleanup data, e.g. if you use the
	 * {@link Plugin::AlternateAuthentication()} hook.
	 *
	 * @see Plugin::AlternateAuthentication()
	 * @see Plugin::Logout()
	 * @param array Associative array of parameters
	 *   - 'User': the user object
	 */
	function Logout( $params )
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
	 * Event handler: Called at the end of the "User profile" form.
	 *
	 * The corresponding action event is {@link Plugin::ProfileFormSent()}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the user profile form generating object (by reference)
	 *   - 'User': the edited user object (by reference)
	 *   - 'edit_layout':
	 *			"public" - public front-office user profile form (info only),
	 *			"private" - private front-office user profile form (editable),
	 *   - 'is_admin_page': (boolean) indicates whether we are in front-office or back-office
	 */
	function DisplayProfileFormFieldset( & $params )
	{
		if( $params['edit_layout'] == 'public' )
		{ // Do nothing in public mode
			return false;
		}

		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called before at the beginning, if a profile form gets sent (and received).
	 *
	 * Use this to filter input
	 *
	 * @see Plugin::DisplayProfileFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'User': edited {@link User} object (by reference)
	 *   - 'newuser_firstname': firstname (by reference)
	 *   - 'newuser_lastname': lastname (by reference)
	 *   - 'newuser_nickname': nickname (by reference)
	 *   - 'newuser_locale': locale (by reference)
	 *   - 'newuser_url': URL (by reference)
	 *   - 'newuser_email': email (by reference)
	 *   - 'newuser_allow_msgform': "message form" status (by reference)
	 *   - 'newuser_notify': "notifications" status (by reference)
	 *   - 'newuser_showonline': "show online" status (by reference)
	 *   - 'newuser_gender': gender (by reference)
	 *   - 'pass1': pass1 (by reference)
	 *   - 'pass2': pass2 (by reference)
	 */
	function ProfileFormSent( & $params )
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
	 * @see Plugin::Logout()
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


	/**
	 * Event handler: called at the end of {@link DataObject::dbinsert() inserting an object in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Object': the related Object (by reference)
	 *   - 'type': class name of deleted Object (Chapter, File, Blog, Link, Comment, Slug etc.) (by reference)
	 */
	function AfterObjectInsert( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link DataObject::dbupdate() updating an object in the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Object': the related Object (by reference)
	 *   - 'type': class name of deleted Object (Chapter, File, Blog, Link, Comment, Slug etc.) (by reference)
	 */
	function AfterObjectUpdate( & $params )
	{
	}


	/**
	 * Event handler: called at the end of {@link DataObject::dbdelete() deleting an object from the database}.
	 *
	 * @param array Associative array of parameters
	 *   - 'Object': the related Object (by reference)
	 *   - 'type': class name of deleted Object (Chapter, File, Blog, Link, Comment, Slug etc.) (by reference)
	 */
	function AfterObjectDelete( & $params )
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


	/**
	 * Event handler: Called right after initializing plugins. This is the earliest event you can use.
	 *
	 * This is meant to be a good point for doing early processing and cancelling the request.
	 * Note that at this point DB charset is not set, Session and Hit aren't initialized
	 */
	function AfterPluginsInit()
	{
	}


	/**
	 * Event handler: Called at the end of _main.inc.php. This is the the latest event called before blog initialization.
	 *
	 * This is meant to be a good point for doing processing that don't require a blog to be initialized.
	 */
	function AfterMainInit()
	{
	}


	/**
	 * Event handler: Called before pruning sessions. The plugin can prevent deletion
	 * of particular sessions, by returning their IDs.
	 *
	 * Note: There can be hundreds of thousands of sessions about to be deleted.
	 * Any plugin making use of this may have serious performance/memory issues.
	 *
	 * fp> TODO: maybe we should pass the prune cut off date instead.
	 * What's a use case for this?
	 *
	 * @param array Associative array of parameters
	 *   - 'IDs': list of session IDs that are about to get deleted (WARNING: potentially huge)
	 * @return array List of IDs that should not get deleted
	 */
	function BeforeSessionsDelete( & $params )
	{
		return array();
	}


	/**
	 * Event handler: Called when a hit gets logged, but before it gets recorded.
	 *
	 * @param array Associative array of parameters
	 *   - 'Hit': The "Hit" object (by reference).
	 *
	 * @return boolean True if you've handled the recording of the hit, false otherwise.
	 */
	function AppendHitLog( & $params )
	{
		// Do nothing by default:
		return false;
	}


	/**
	 * Event handler: Called before an uploaded file gets saved on server.
	 *
	 * @param array Associative array of parameters
	 *   - 'File': The "File" object (by reference).
	 *   - 'name': file name (by reference).
	 *   - 'type': file mimetype (by reference).
	 *   - 'tmp_name': file location (by reference).
	 *   - 'size': file size in bytes  (by reference).
	 *
	 * @return boolean 'false' to abort file upload, otherwise return 'true'
	 */
	function AfterFileUpload( & $params )
	{
		return array(); // Do nothing by default:
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
	 * @link http://b2evolution.net/man/localization
	 *
	 * It uses the global/regular {@link T_()} function as a fallback.
	 *
	 * @param string The string (english), that should be translated
	 * @param string Requested locale ({@link $current_locale} gets used by default)
	 * @return string The translated string.
	 * @uses T_()
	 */
	function T_( $string, $req_locale = '' )
	{
		global $plugins_path;

		$globalfile_path = $plugins_path.$this->classname.'/locales/_global.php';
		$trans = & $this->_trans;

		// Load the global messages file, if existing:
		if( ! $this->_trans_loaded_global )
		{
			$this->_trans_loaded_global = true;

			// fp> TODO: there should not be any filestats for plugins that don't implement localization.
			if( file_exists( $globalfile_path ) && is_readable( $globalfile_path ) )
			{
				include_once $globalfile_path;
			}
			else
			{
				// This is pollution of the debug log.
				// $this->debug_log( 'T_: Plugin global messages file does not exist or is not readable: '.$globalfile_path, 'locale' );
			}
		}

		if ( ( $return = T_( $string, $req_locale, array(
								'ext_transarray' => & $this->_trans,
								'alt_basedir'    => $plugins_path.$this->classname,
							) ) ) == $string )
		{	// Fallback to global translation file:
			return T_( $string, $req_locale );
		}
		return $return;
	}


	/**
	 * Translate and escape single quotes.
	 *
	 * This is to be used mainly for Javascript strings.
	 *
	 * @uses Plugin::T_()
	 * @since 2.5.0
	 * @param string String to translate
	 * @param string Locale to use
	 * @return string The translated and escaped string.
	 */
	function TS_( $string, $req_locale = '' )
	{
		return str_replace( "'", "\\'", $this->T_( $string, $req_locale ) );
	}


	/**
	 * Get the absolute URL to the plugin's directory (where the plugins classfile is).
	 * Trailing slash included.
	 *
	 * @param string Get absolute URL? (or make it relative to $ReqHost)
	 * @return string
	 */
	function get_plugin_url( $dummy = false )
	{
		global $ReqHost, $plugins_url, $plugins_path, $Collection, $Blog;

		if( empty( $this->_plugin_url ) )
		{	// Initialize plugin URL only first time request:

			// Get sub-path below $plugins_path, if any:
			$sub_path = preg_replace( ':^'.preg_quote( $plugins_path, ':' ).':', '', dirname( $this->classfile_path ).'/' );

			if( is_admin_page() || empty( $Blog ) )
			{	// Get plugin url for nack-office and when collection is not viewed now:
				$r = $plugins_url.$sub_path;
			}
			else
			{	// Get plugin url depending on collection setting:
				$r = $Blog->get_local_plugins_url().$sub_path;
			}

			// Use the same protocol as with current host (so includes from within https do not fail when on http):
			$r = url_same_protocol( $r );

			$this->_plugin_url = $r;
		}

		return $this->_plugin_url;
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
	 * It uses either {@link $htsrv_url} or {@link $htsrv_url_sensitive} (if {@link $ReqHost} is on https).
	 *
	 * NOTE: AJAX callbacks are required to be on the same domain/protocol, so if you're using absolute
	 *       blog URLs on different domains you must set {@link $htsrv_url} dynamically, to use the same domain!
	 *
	 * @todo dh> we might want to provide whitelisting of methods through {@link $Session} here and check for it in the htsrv handler.
	 *
	 * @param string Method to call. This must be listed in {@link GetHtsrvMethods()}.
	 * @param array Array of optional parameters passed to the method.
	 * @param string Glue for additional GET params used internally.
	 * @param string Get absolute URL? (or cut off $ReqHost at the beginning)
	 * @return string The URL
	 */
	function get_htsrv_url( $method, $params = array(), $glue = '&amp;', $abs = false )
	{
		global $ReqHost;

		$base = substr( $ReqHost, 0, 6 ) == 'https:' ? get_htsrv_url( true ) : get_htsrv_url();

		if( ! $abs && strpos( $base, $ReqHost ) === 0 )
		{ // cut off $ReqHost if the resulting URL starts with it:
			$base = substr($base, strlen($ReqHost));
		}

		$r = $base.'call_plugin.php?plugin_ID='.$this->ID.$glue.'method='.$method;
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
	 *        See {@link AdminUI::add_menu_entries()}. Default: 'options' -> 'misc' for the Tools menu.
	 * @param array Optional params. See {@link AdminUI::add_menu_entries()}.
	 */
	function register_menu_entry( $text, $path = array( 'options', 'misc' ), $menu_entry_props = array() )
	{
		global $AdminUI;

		$menu_entry_props['text'] = $text;
		$menu_entry_props['href'] = $this->get_tools_tab_url();

		$AdminUI->add_menu_entries( $path, array( 'plug_ID_'.$this->ID => $menu_entry_props ) );
	}


	/**
	 * Get URL for the tools tab for this plugin.
	 *
	 * The tools tab must be provided with AdminTabPayload()
	 */
	function get_tools_tab_url()
	{
		global $admin_url;
		return $admin_url.'?ctrl=tools&amp;tab=plug_ID_'.$this->ID;
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

		return $Plugins->set_Plugin_status( $this, $status );
	}


	/**
	 * Get canonical name for database tables a plugin uses, by adding an unique
	 * prefix for your plugin instance.
	 *
	 * You should use this when referring to your SQL table names.
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
	 * @param boolean Should the data get saved immediately? (otherwise it gets saved on script shutdown)
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
	 * @param mixed Default value to use if key is not set or has expired. (since 1.10.0)
	 * @return mixed The value, if set; otherwise $default
	 */
	function session_get( $name, $default = NULL )
	{
		global $Session;

		return $Session->get( 'plugID'.$this->ID.'_'.$name, $default );
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
		$Plugins_admin = & get_Plugins_admin();
		return $Plugins_admin->set_event_status( $this->ID, $event, 0 );
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
		$Plugins_admin = & get_Plugins_admin();
		return $Plugins_admin->set_event_status( $this->ID, $event, 1 );
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
	 * Get a link to a help page (with icon).
	 *
	 * @param string Target; one of the following:
	 *         - anchor to {@link $help_url} ("#anchor")
	 *         - absolute link to some URL, e.g. "http://example.com/example.php"
	 *         - '$help_url' or empty for {@link $help_url}, then also the "www" icon gets used
	 *         - '$apply_rendering' - url to manual page about "Plugin / Apply Rendering"
	 *         - '$widget_url' - url to manual page about widget plugin
	 * @param string Icon
	 * @param boolean TRUE - to add info to display it in tooltip on mouseover
	 * @return string The html A tag, linking to the help (or empty in case of $readme, if there is none).
	 */
	function get_help_link( $target = '', $icon = 'help', $use_tooltip = true )
	{
		static $target_counter = 0;
		$title = '';
		$word = '';
		$link_attribs = array( 'target' => '_blank', 'id' => 'anchor_help_plugin_'.$this->ID.'_'.$target_counter++ );

		$info_suffix_text = '';
		if( empty( $target ) || in_array( $target, array( '$help_url', '$apply_rendering', '$widget_url' ) ) )
		{
			if( $target == '$apply_rendering' )
			{ // Url to info about plugin apply rendering options
				$url = get_manual_url( 'plugin-apply-rendering' );
			}
			else
			{ // Url to current plugin info
				$url = $this->get_help_url( $target );
			}
			$title = T_('Homepage of the plugin');
			if( $use_tooltip )
			{ // Add these data only for tooltip
				$link_attribs['class'] = 'action_icon help_plugin_icon';
				$link_attribs['rel'] = format_to_output( $this->long_desc, 'htmlspecialchars' );
			}

			// Display additional info for help plugin icon only one time. It is used on plugins.js
			$info_suffix_text = '<div id="help_plugin_info_suffix" style="display:none"><p><strong>'
					.sprintf( T_('Click %s to access full documentation for this plugin'), get_icon( 'help' ) )
				.'</strong></p></div>';
		}
		elseif( $target == '$readme' )
		{ // README
			// fplanque> note: we do NOT want the plugin to waste time searching for files on disk -- this has been replaced with bubbletips.
			return '';
		}
		elseif( substr($target, 0, 1) == '#' )
		{ // anchor
			$url = $this->get_help_url().$target;
		}
		elseif( preg_match( '~^https?://~', $target ) )
		{ // absolute URL (strict match to allow other formats later if needed)
			$url = $target;
		}
		else
		{
			debug_die( 'Invalid get_help_link() target: '.$target );
		}

		return action_icon( $title, $icon, $url, $word, 4, 1, $link_attribs ).$info_suffix_text;
	}


	/**
	 * Get the plugin's external help/website URL.
	 *
	 * If {@link Plugin::$help_url} is empty, it defaults to the manual wiki.
	 *
	 * @param string Target; one of the following:
	 *         - '$help_url' or empty for standard help url
	 *         - '$widget_url' - url to manual page about widget plugin
	 * @return string
	 */
	function get_help_url( $target = '' )
	{
		if( $target == '$widget_url' )
		{ // Get url for widget plugin manual
			$plugin_slug = ( empty( $this->help_topic ) ? $this->classname : $this->help_topic );
			$plugin_slug = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $plugin_slug ).'-widget' );
			$plugin_slug = str_replace( '-plugin', '', $plugin_slug );
			return get_manual_url( $plugin_slug );
		}
		else
		{ // Get standard help url of the plugin
			if( empty( $this->help_url ) )
			{
				return get_manual_url( empty( $this->help_topic ) ? $this->classname : $this->help_topic );
			}
			else
			{
				return $this->help_url;
			}
		}
	}


	/**
	 * @deprecated Backwards compatibility wrapper (for 1.8)
	 */
	function get_README_link()
	{
		// fplanque> note: we do NOT want the plugin to waste time searching for files on disk -- this has been replaced with bubbletips.
		return '';
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

		if( empty( $current_User ) || !$current_User->check_perm( 'options', 'view', false ) )
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
		if( $url = $this->get_edit_settings_url() )
		{
			return action_icon( T_('Edit plugin settings!'), 'edit', $url );
		}
		return false;
	}


	/**
	 * Get an URL to edit the Plugin's settings (if the user has permission).
	 *
	 * @return false|string
	 */
	function get_edit_settings_url()
	{
		global $current_User, $admin_url;

		if( ! $current_User->check_perm( 'options', 'edit', false ) )
		{
			return false;
		}

		return $admin_url.'?ctrl=plugins&amp;action=edit_settings&amp;plugin_ID='.$this->ID;
	}


	/**
	 * Get Widget setting
	 * @param string Name of setting
	 * @param array Array of params (like widget params)
	 * @return mixed|null
	 */
	function get_widget_setting( $name = NULL, $params = NULL )
	{
		if ( empty( $name ) || ! isset ( $params[$name] ) )
		{
			return NULL;
		}
		else
		{
			return $params[$name];
		}
	}


	/**
	 * PHP5 overloading of get method to lazy-load (User)Settings, when they get
	 * accessed.
	 *
	 * With PHP4, settings get instantiated in Plugins::init_settings right away.
	 *
	 * @return Reference to the object or null
	 */
	function & __get( $nm )
	{
		global $inc_path;
		global $Plugins;

		switch( $nm )
		{
			case 'Settings':
				if( $this->ID < 0 )
				{
					debug_die('Tried to access "Settings" on a non-installed plugin. ('.$this->classname.'/'.$this->ID.')');
				}
				$Plugins->instantiate_Settings( $this, 'Settings' );
				if( isset($this->Settings) )
				{
					return $this->Settings;
				}
				break;

			case 'UserSettings':
				if( $this->ID < 0 )
				{
					debug_die('Tried to access "UserSettings" on a non-installed plugin. ('.$this->classname.'/'.$this->ID.')');
				}
				$Plugins->instantiate_Settings( $this, 'UserSettings' );
				if( isset($this->UserSettings) )
				{
					return $this->UserSettings;
				}
				break;
		}
		$r = null;
		return $r;
	}

	/*
	 * Interface methods }}}
	 */

	/**
	 * Get a skin specific param value from current Blog
	 *
	 * @param string Setting name
	 * @param object Blog collection
	 * @param boolean TRUE - to allow empty Blog, it is used to get default value when Blog is not defined, e.g. in case with Messages
	 * @return string Setting value
	 */
	function get_coll_setting( $parname, & $Blog, $allow_null_blog = false )
	{
		if( empty( $Blog ) )
		{ // Blog is not defined
			if( !$allow_null_blog )
			{ // Empty blog is not allowed, Exit here
				return NULL;
			}
			else
			{ // We can get the coll default settings of this plugin
				$blog_type = 'std';
			}
		}
		else
		{ // Get setting value from Blog
			// Name of the setting in the blog settings:
			$blog_setting_name = 'plugin'.$this->ID.'_'.$parname;

			$value = $Blog->get_setting( $blog_setting_name );

			if( ! is_null( $value ) )
			{ // We have a value for this param:
				return $value;
			}

			$blog_type = $Blog->get( 'type' );
		}

		// Try default values:
		return $this->get_coll_default_setting( $parname, $blog_type );
	}


	/**
	 * Get a coll default param value of this Plugin
	 *
	 * @param string Setting name
	 * @param string Blog type
	 * @return string Setting value
	 */
	function get_coll_default_setting( $parname, $blog_type = 'std' )
	{
		$tmp_params = array( 'for_editing' => true, 'blog_type' => $blog_type );
		$params = $this->get_coll_setting_definitions( $tmp_params );
		if( isset( $params[$parname]['defaultvalue'] ) )
		{ // We have a default value:
			return $params[$parname]['defaultvalue'] ;
		}

		return NULL;
	}


	/**
	 * Set a skin specific param value for current Blog
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set_coll_setting( $parname, $parvalue, $blog = NULL )
	{
		/**
		 * @var Blog
		 */

		if( empty( $blog ) )
		{
			global $Collection, $Blog;
		}
		else
		{
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog, false, false );
		}

		// Name of the setting in the blog settings:
		$blog_setting_name = 'plugin'.$this->ID.'_'.$parname;
		$Blog->set_setting( $blog_setting_name, $parvalue );
	}


	/**
	 * Delete param setting from current Blog
	 *
	 * @param string parameter name
	 * @param string blog id
	 */
	function delete_coll_setting( $parname, $blog = NULL )
	{
		/**
		 * @var Blog
		 */

		if( empty( $blog ) )
		{
			global $Collection, $Blog;
		}
		else
		{
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = & $BlogCache->get_by_ID( $blog, false, false );
		}

		// Name of the setting in the blog settings:
		$blog_setting_name = 'plugin'.$this->ID.'_'.$parname;
		$Blog->delete_setting( $blog_setting_name );
	}

	/**
	 * Get a message specific param value
	 *
	 * @param string Setting name
	 * @return string Setting value
	 */
	function get_msg_setting( $parname )
	{
		if( empty( $this->Settings ) )
		{
			global $Plugins;
			$Plugins->instantiate_Settings( $this, 'Settings' );
		}

		// Use prefix 'msg_' for all message settings except of "msg_apply_rendering":
		$value = $this->Settings->get( $parname == 'msg_apply_rendering' ? $parname : 'msg_'.$parname );

		if( ! is_null( $value ) )
		{ // We have a value for this param:
			return $value;
		}

		// Try default values:
		$tmp_params = array( 'for_editing' => true );
		$params = $this->get_msg_setting_definitions( $tmp_params );
		if( isset( $params[$parname]['defaultvalue'] ) )
		{ // We have a default value:
			return $params[$parname]['defaultvalue'] ;
		}

		return NULL;
	}

	/**
	 * Get a email specific param value
	 *
	 * @param string Setting name
	 * @return string Setting value
	 */
	function get_email_setting( $parname )
	{
		if( empty( $this->Settings ) )
		{
			global $Plugins;
			$Plugins->instantiate_Settings( $this, 'Settings' );
		}

		// Use prefix 'email_' for all message settings except of "email_apply_rendering":
		$value = $this->Settings->get( $parname == 'email_apply_rendering' ? $parname : 'email_'.$parname );

		if( ! is_null( $value ) )
		{	// We have a value for this param:
			return $value;
		}

		// Try default values:
		$tmp_params = array( 'for_editing' => true );
		$params = $this->get_email_setting_definitions( $tmp_params );
		if( isset( $params[$parname]['defaultvalue'] ) )
		{ // We have a default value:
			return $params[$parname]['defaultvalue'] ;
		}

		return NULL;
	}


	/**
	 * Check if this plugin is enabled to render
	 *
	 * @param string Setting "apply_rendering" of current Blog or global for Messages
	 * @param array What renderers are selected for current Item, Comment or Message
	 * @return boolean TRUE if this plugin is enabled to render, otherwise FALSE
	 */
	function is_renderer_enabled( $rendering_setting_value, $renderers )
	{
		switch( $rendering_setting_value )
		{
			case 'stealth':
			case 'always':
				// Always enabled
				return true;

			case 'opt-out':
			case 'opt-in':
			case 'lazy':
				if( is_array( $renderers ) && in_array( $this->code, $renderers ) )
				{ // Option is enabled
					return true;
				}
				// Option is disabled
				return false;

			case 'never':
				// Never enabled
				return false;
		}
	}


	/**
	 * This method should return a string that used as suffix
	 *   for the field 'From Country' on the user profile page in the BackOffice
	 *
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 * @return string Field suffix
	 */
	function GetUserFromCountrySuffix( & $params )
	{
		return '';
	}


	/**
	 * This method initializes an array that used as additional columns
	 *   for the results table in the BackOffice
	 *
	 * @param array Associative array of parameters
	 *   'table'   - Special name that used to know what plugin must use current table
	 *   'column'  - DB field which contains IP address
	 *   'Results' - Object
	 */
	function GetAdditionalColumnsTable( & $params  )
	{
	}


	/**
	 * Memorize that a specific css that file will be required by the current page.
	 * @see require_css() for full documentation,
	 * this function is used to add unique version number for each plugin
	 *
	 * @param string Name of CSS file relative to current plugin folder
	 * @param boolean TRUE to print script tag on the page, FALSE to store in array to print then inside <head>
	 */
	function require_css( $css_file, $output = false )
	{
		global $app_version_long;
		require_css( $this->get_plugin_url().$css_file, 'relative', NULL, NULL, $this->version.'+'.$app_version_long, $output );
	}


	/**
	 * Memorize that a specific javascript file will be required by the current page.
	 * @see require_js() for full documentation,
	 * this function is used to add unique version number for each plugin
	 *
	 * @param string Name of JavaScript file relative to plugin folder
	 */
	function require_js( $js_file )
	{
		global $app_version_long;
		require_js( $this->get_plugin_url().$js_file, 'relative', false, false, $this->version.'+'.$app_version_long );
	}
}

?>
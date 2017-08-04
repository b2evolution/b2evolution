<?php
/**
 * This file implements the TEST plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../inc/plugins/_plugin.class.php.
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * TEST Plugin
 *
 * This plugin responds to virtually all possible plugin events :P
 *
 * @package plugins
 */
class test_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name = 'Test';
	var $code = 'evo_TEST';
	var $priority = 50;
	var $version = '6.9.3';
	var $author = 'The b2evo Group';
	var $help_url = '';  // empty URL defaults to manual wiki

	/*
	 * These variables MAY be overriden.
	 */
	var $number_of_installs = 1;
	var $group = 'rendering';


	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Test plugin');
		$this->long_desc = T_('This plugin responds to virtually all possible plugin events :P');

		// Trigger plugin settings instantiation (for testing).
		if( $params['is_installed'] )
		{
			$this->Settings->get('foo');
		}
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{
		$r = array(
			'click_me' => array(
				'label' => 'Click me!',
				'defaultvalue' => '1',
				'type' => 'checkbox',
			),
			'input_me' => array(
				'label' => 'How are you?',
				'defaultvalue' => '',
				'note' => 'Welcome to b2evolution',
			),
			'number' => array(
				'label' => 'Number',
				'defaultvalue' => '8',
				'note' => '1-9',
				'valid_range' => array( 'min'=>1, 'max'=>9 ),
			),
			'my_select' => array(
				'label' => 'Selector',
				'id' => $this->classname.'_my_select_id',
				'class' => $this->classname.'_my_select_class',
				'onchange' => 'document.getElementById("'.$this->classname.'_a_disabled_one").disabled = ( this.value == "sun" );',
				'defaultvalue' => 'one',
				'type' => 'select',
				'options' => array( 'sun' => 'Sunday', 'mon' => 'Monday' ),
				'note' => 'This combo is connected with the next field',
			),
			'a_disabled_one' => array(
				'label' => 'This one is disabled',
				'id' => $this->classname.'_a_disabled_one',
				'type' => 'checkbox',
				'defaultvalue' => '1',
				'disabled' => true, // this can be useful if you detect that something cannot be changed. You probably want to add a 'note' then, too.
				'note' => 'Change the above select input to "Monday" to enable it.',
			),
			'select_multiple' => array(
				'label' => $this->T_( 'Multiple select' ),
				'type' => 'select',
				'multiple' => true,
				'allow_none' => true,
				'options' => array( 'sci' => $this->T_( 'Scissors' ), 'pap' => $this->T_( 'Paper' ), 'sto' => $this->T_( 'Stone') ),
				'defaultvalue' => array( 'sci', 'sto' ),
				'note' => $this->T_( 'This is a free style Multiple Select. You can choose zero or one or more items' )
			),
			/*
			 * note: The $this->T_( string )function tanslates the string.
			 * However since it inherits from the class Plugin you will need
			 * to provide the translation on a per plugin basis. In other
			 * words: this will not be translated through B2evolution.
			 */
			'blog' => array(
				'label' => 'A blog',
				'type' => 'select_blog',  // TODO: does not scale with 500 blogs
				'allow_none' => true,
			),
			'blogs' => array(
				'label' => 'A set of blogs',
				'type' => 'select_blog',	// TODO: BROKEN + does not scale with 500 blogs
				'multiple' => true,
				'allow_none' => true,
			),
			'single_user' => array(
				'label' => 'A single user',
				'type' => 'select_user',
				'users_limit' => 5,
				'allow_none' => true,
				'defaultvalue' => 0,
				'note' => 'Allows chosing none or one user'
			),
			'sets' => array(
				'label' => 'Multiple users',
				'type' => 'select_user',
				'users_limit' => 10,
				'min_count' => 0,
				'max_count' => 3,
				'multiple' => 'true',
				'allow_none' => true,
				'note' => 'Allows none or one or more than one user (up to three in this example)',
				'entries' => array(
					'user' => array(
						'label' => 'A user',
						'type' => 'select_user',		// TODO: does not scale with 500 users
						'allow_none' => true,
					),
				),
			),
			'maxlen' => array(
				'label' => 'Max',
				'type' => 'textarea',
				'maxlength' => 10,
				'note' => 'Maximum length is 10 here.',
			),
			'plug_color' => array(
				'label' => 'Plugin color',
				'type' => 'color',
				'note' => 'Click on the field to display a color selector.',
				'defaultvalue' => '#F60',
			),
		);

		if( $params['for_editing'] )
		{ // we're asked for the settings for editing:
			if( $this->Settings->get('my_select') == 'mon' )
			{
				$r['a_disabled_one']['disabled'] = false;
			}
		}

		return $r;
	}


	/**
	 * Define here default custom settings that are to be made available
	 *     in the backoffice for collections, private messages and newsletters.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_custom_setting_definitions()}.
	 */
	function get_custom_setting_definitions( & $params )
	{
		return array(
			'custom' => array(
					'label' => 'Custom setting',
					'note' => 'Custom plugin setting for collections, private messages and newsletters.',
					'defaultvalue' => 'Custom value',
				),
			'custom_color' => array(
					'label' => 'Custom color',
					'type' => 'color',
					'note' => 'Click on the field to display a color selector.',
					'defaultvalue' => '#033',
				),
		);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params,
			array(
				'default_comment_rendering' => 'stealth',
				'default_post_rendering' => 'opt-out'
			)
		);

		$r = array_merge( parent::get_coll_setting_definitions( $default_params ),
				array(
					'coll_custom' => array(
							'label' => 'Collection setting',
							'note' => 'Custom plugin setting ONLY for collections.',
							'defaultvalue' => 'Collection value',
						),
					'coll_color' => array(
							'label' => 'Collection color',
							'type' => 'color',
							'note' => 'Click on the field to display a color selector.',
							'defaultvalue' => '#0C9',
						),
				)
			);

		return $r;
	}


	/**
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'stealth' ) );

		$r = array_merge( parent::get_msg_setting_definitions( $default_params ),
				array(
					'custom_msg' => array(
							'label' => 'Message setting',
							'note' => 'Custom plugin setting ONLY for messages.',
							'defaultvalue' => 'Message value',
						),
					'msg_color' => array(
							'label' => 'Message color',
							'type' => 'color',
							'note' => 'Click on the field to display a color selector.',
							'defaultvalue' => '#393',
						),
				)
			);

		return $r;
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// set params to allow rendering for emails by default:
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'stealth' ) );

		$r = array_merge( parent::get_email_setting_definitions( $default_params ),
				array(
					'custom_email' => array(
							'label' => 'Email setting',
							'note' => 'Custom plugin setting ONLY for emails.',
							'defaultvalue' => 'Email value',
						),
					'email_color' => array(
							'label' => 'Email color',
							'type' => 'color',
							'note' => 'Click on the field to display a color selector.',
							'defaultvalue' => '#DDF',
						),
				)
			);

		return $r;
	}


	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array Local params like 'for_editing' => true
	 * @return array
	 */
	function get_widget_param_definitions( $params )
	{
		$r = array(
			'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 60,
					'defaultvalue' => 'Test plugin widget',
			),
			'widget_color' => array(
				'label' => 'Widget color',
				'type' => 'color',
				'note' => 'Click on the field to display a color selector.',
				'defaultvalue' => '#F66',
			),
		);
		return $r;
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
		global $Blog;

		return array(
				'wi_ID'        => $widget_ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
			);
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
	 * @see Plugin::GetDefaultUserSettings()
	 * @param array Associative array of parameters.
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$UserSettings}.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function GetDefaultUserSettings( & $params )
	{
		return array(
				'echo_random' => array(
					'label' => 'Echo a random number in AdminBeginPayload event',
					'type' => 'checkbox',
					'defaultvalue' => '0',
				),
				'deactivate' => array(
					'label' => 'Deactivate',
					'type' => 'checkbox',
					'defaultvalue' => '0',
				),
			);
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
	 * @see Plugin::GetDependencies()
	 * @return array
	 */
	function GetDependencies()
	{
		return array(
				'recommends' => array(
					'events_by_one' => array( array('Foo', 'Bar'), array('FooBar', 'BarFoo') ), // a plugin that provides "Foo" and "Bar", and one (may be the same) that provides "FooBar" and "BarFoo"
					'events' => array( 'some_event', 'some_other_event' ),
					'plugins' => array( array( 'some_plugin', '1' ) ), // at least version 1 of some_plugin
				),

				'requires' => array(
					// Same syntax as with the 'recommends' class above, but would prevent the plugin from being installed.
				),
			);
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
	 *
	 * @see Plugin::GetDbLayout()
	 */
	function GetDbLayout()
	{
		return array(
				'CREATE TABLE '.$this->get_sql_table( 'test_table_name' ).' (
					test_ID   INT UNSIGNED NOT NULL AUTO_INCREMENT,
					test_name VARCHAR( 255 ) NOT NULL,
					PRIMARY KEY( test_ID )
				) ENGINE = innodb DEFAULT CHARSET = utf8'
			);
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
	 * @see Plugin::GetExtraEvents()
	 * @return NULL|array "event_name" => "description"
	 */
	function GetExtraEvents()
	{
		return array(
				// Gets "min" and "max" as params and should return a random number in between:
				'test_plugin_get_random' => 'TEST event that returns a random number.',
			);
	}


	/**
	 * Gets provided as plugin event (and gets also used internally for demonstration).
	 *
	 * @param array Associative array of parameters
	 *              'min': mininum number
	 *              'max': maxinum number
	 * @return integer
	 */
	function test_plugin_get_random( & $params )
	{
		return rand( $params['min'], $params['max'] );
	}


	/**
	 * Return list of custom disp types handled by this plugin
	 *
	 * @see Plugin::GetHandledDispModes()
	 * @return array list of disp modes handled by this plugin
	 */
	function GetHandledDispModes()
	{
		return array(
				'disp_test', // display our test disp
			);
	}


	/**
	 * Display our custom disp mode(s)
	 *
	 * @see Plugin::HandleDispMode()
	 * @param mixed array $params
	 *    disp > display mode requested
	 * @return did we display?
	 */
	function HandleDispMode( $params )
	{
		echo '<p>This is the test plugin handling the ['.$params['disp'].'] disp mode.</p>';
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
	 * @see Plugin::GetHtsrvMethods()
	 * @return array
	 */
	function GetHtsrvMethods()
	{
		return array( 'test_action' );
	}

	/**
	 * AJAX callback to test action.
	 *
	 * @param array Params
	 */
	function htsrv_test_action( $params )
	{
		global $DB;

		// To call this action use URL which is generated by code:
		// $htsrv_plugin_url = $this->get_htsrv_url( 'test_action', array( 'param_1' => 'value_1' ) );

		if( empty( $params['param_1'] ) )
		{	// Nothing to do:
			return;
		}

		$param2 = param( 'param2', 'string' );

		$DB->query( 'INSERT INTO '.$this->get_sql_table( 'test_table_name' ).'
			( test_name ) VALUES ( '.$DB->quote( 'param_1 = '.$params['param_1'].'; param2 = '.$param2 ).' ) ' );
	}


	/**
	 * This method gets asked for a list of cronjobs that the plugin
	 * provides.
	 * If a user installs a cron job out of this list, the
	 * {@link Plugin::ExecCronJob()} of the plugin gets called.
	 *
	 * @see Plugin::GetCronJobs()
	 * @return array Array of arrays with keys "name", "ctrl" and "params".
	 *               "name" gets used for display. "ctrl" (string) and
	 *               "params" (array) get passed to the
	 *               {@link Plugin::ExecCronJob()} method when the cronjob
	 *               gets executed.
	 */
	function GetCronJobs( & $params )
	{
		return array(
				array(
					'name' => 'TEST plugin - cron job',
					'ctrl' => 'test_job',
					'params' => array( 'param' => 1 ),
				),
			);
	}


	/**
	 * Execute/handle a cron job, which has been scheduled by the admin out
	 * of the list that the Plugin provides (see {@link GetCronJobs()}).
	 *
	 * @see Plugin::ExecCronJob()
	 * @param array Associative array of parameters
	 *   - 'ctrl': The "ctrl" name as defined in {@link GetCronJobs()}
	 *   - 'params': The "params" value as defined in {@link GetCronJobs()},
	 *               plus "ctsk_ID" which holds the cron task ID.
	 * @return array with keys "code" (integer, 1 is ok), "message" (gets logged)
	 */
	function ExecCronJob( & $params )
	{
		if( $params['ctrl'] == 'test_job' )
		{
			return array( 'code' => 1, 'message' => 'Test successful.' );
		}
	}

	// }}}


	/*
	 * Event handlers. These are meant to be implemented by your plugin. {{{
	 */

	// Admin/backoffice events (without events specific to Items or Comments): {{{


	/**
	 * Event handler: Gets invoked in /toolbar.inc.php after the menu structure is built.
	 *
	 * @see Plugin::AdminAfterEvobarInit()
	 */
	function AdminAfterEvobarInit()
	{
		// The following is a tiny bit hackish and should probably be abstracted a bit, but just a little bit
		// The idea is too let plugins hook pretty much anywhere into the menu structure, including Left AND Right menus.

		global $topleft_Menu;
		$topleft_Menu->add_menu_entries( 'tools', array(
				'urls_sep' => array(
						'separator' => true,
					),
				'urls' => array(
						'text' => 'Test plugin&hellip;',
						'href' => $this->get_tools_tab_url(),
					),
			) );
	}


	/**
	 * Event handler: Gets invoked in /admin.php for every backoffice page after
	 *                the menu structure is built. You could use the {@link $AdminUI} object
	 *                to modify it.
	 *
	 * This is the hook to register menu entries. See {@link register_menu_entry()}.
	 *
	 * @see Plugin::AdminAfterMenuInit()
	 */
	function AdminAfterMenuInit()
	{
		$this->register_menu_entry( 'Test tab' );
	}


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @see Plugin::AdminEndHtmlHead()
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		echo '<!-- This comment was added by the TEST plugin event "AdminEndHtmlHead" with function "echo" -->';

		add_headline( '<!-- This comment was added by the TEST plugin event "AdminEndHtmlHead" with function "add_headline"-->' );

		add_js_headline( 'console.log( "This JavaScript log was added by the TEST plugin event \'AdminEndHtmlHead\' with function \'add_js_headline\'" )' );

		add_css_headline( '/* This CSS was added by the TEST plugin event \'AdminEndHtmlHead\' with function "add_css_headline" */' );

		return true;
	}


	/**
	 * Event handler: Called right after displaying the admin page footer.
	 *
	 * @see Plugin::AdminAfterPageFooter()
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminAfterPageFooter( & $params )
	{
		echo '<p class="footer">This is the TEST plugin responding to the AdminAfterPageFooter event!</p>';

		return true;
	}


	/**
	 * Event handler: Called when displaying editor buttons (in back-office).
	 *
	 * This method, if implemented, should output the buttons (probably as html INPUT elements)
	 * and return true, if button(s) have been displayed.
	 *
	 * You should provide an unique html ID with each button.
	 *
	 * @see Plugin::AdminDisplayEditorButton()
	 * @param array Associative array of parameters.
	 *   - 'target_type': either 'Comment' or 'Item' or 'EmailCampaign'.
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display a button?
	 */
	function AdminDisplayEditorButton( & $params )
	{
		?>
		<input type="button" value="TEST" onclick="alert('Hi! This is the TEST plugin (AdminDisplayEditorButton)!');" class="btn btn-default" />
		<?php
		return true;
	}


	/**
	 * Event handler: Called when displaying editor buttons (in front-office).
	 *
	 * This method, if implemented, should output the buttons (probably as html INPUT elements)
	 * and return true, if button(s) have been displayed.
	 *
	 * You should provide an unique html ID with each button.
	 *
	 * @see Plugin::DisplayEditorButton()
	 * @param array Associative array of parameters.
	 *   - 'target_type': either 'Comment' or 'Item'.
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display a button?
	 */
	function DisplayEditorButton( & $params )
	{
		?>
		<input type="button" value="TEST" onclick="alert('Hi! This is the TEST plugin (DisplayEditorButton)!');" class="btn btn-default" />
		<?php
		return true;
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @see Plugin::AdminDisplayToolbar()
	 * @param array Associative array of parameters
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );

		echo $this->get_template( 'toolbar_title_before' );
		echo 'TEST toolbar for Item:';
		echo $this->get_template( 'toolbar_title_after' );

		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 1" onclick="alert(\'TEST 1\')" />';
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 2" onclick="alert(\'TEST 2\')" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 3" onclick="alert(\'TEST 3 from second group\')" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_after' );

		return true;
	}


	/**
	 * Event handler: Called when handling actions for the "Tools" menu.
	 *
	 * Use {@link msg()} to add messages for the user.
	 *
	 * @see Plugin::AdminToolAction()
	 */
	function AdminToolAction()
	{
		$this->msg( 'Hello, This is the AdminToolAction for the TEST plugin.' );
	}


	/**
	 * Event handler: Called when displaying the block in the "Tools" menu.
	 *
	 * @see Plugin::AdminToolPayload()
	 * @return boolean did we display something?
	 */
	function AdminToolPayload()
	{
		echo 'Hello, This is the AdminToolPayload for the TEST plugin.';

		return true;
	}


	/**
	 * Event handler: Method that gets invoked when our tab is selected.
	 *
	 * You should catch (your own) params (using {@link param()}) here and do actions
	 * (but no output!).
	 *
	 * Use {@link msg()} to add messages for the user.
	 *
	 * @see Plugin::AdminTabAction()
	 */
	function AdminTabAction()
	{
		global $Plugins;

		$this->text_from_AdminTabAction = '<p>This is text from AdminTabAction for the TEST plugin.</p>'
			.'<p>Here is a random number: '
			.$Plugins->get_trigger_event_first_return('test_plugin_get_random', array( 'min'=>-1000, 'max'=>1000 )).'</p>';

		if( $this->param_text = param( $this->get_class_id('text') ) )
		{
			$this->text_from_AdminTabAction .= '<p>You have said: '.$this->param_text.'</p>';
		}
	}


	/**
	 * Event handler: Gets invoked when our tab is selected and should get displayed.
	 *
	 * Do your output here.
	 *
	 * @see Plugin::AdminTabPayload()
	 * @return boolean did we display something?
	 */
	function AdminTabPayload()
	{
		echo 'Hello, this is the AdminTabPayload for the TEST plugin.';

		echo $this->text_from_AdminTabAction;

		// TODO: this is tedious.. should either be a global function (get_admin_Form()) or a plugin helper..
		$Form = new Form();
		$Form->begin_form();

		$Form->add_crumb( 'plugin_test' );
		$Form->hidden_ctrl(); // needed to pass the "ctrl=tools" param
		$Form->hiddens_by_key( get_memorized() ); // needed to pass all other memorized params, especially "tab"

		$Form->text_input( $this->get_class_id().'_text', $this->param_text, '20', 'Text' );

		$Form->button_input(); // default "submit" button

		$Form->end_form();

		return true;
	}


	/**
	 * Event handler: Gets invoked before the main payload in the backoffice.
	 *
	 * @see Plugin::AdminBeginPayload()
	 */
	function AdminBeginPayload()
	{
		global $Plugins;

		echo '<div class="panelblock center">TEST plugin: AdminBeginPayload event.</div>';

		if( $this->UserSettings->get('echo_random') )
		{
			echo '<div class="panelblock center">TEST plugin: A random number requested by user setting: '
					.$Plugins->get_trigger_event_first_return('test_plugin_get_random', array( 'min'=>0, 'max'=>1000 ) ).'</div>';
		}
	}


	/**
	 * Event handler: Called at the beginning  of the "Edit wdiget" form on back-office.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'ComponentWidget': the Widget which gets edited (by reference)
	 * @return boolean did we display something?
	 */
	function WidgetBeginSettingsForm( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin', array( 'id' => 'WidgetBeginSettingsForm' ) );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the WidgetBeginSettingsForm event for widget #'.$params['ComponentWidget']->ID.'.' );
		$params['Form']->end_fieldset( 'Foo' );

		return true;
	}


	/**
	 * Event handler: Called at the end  of the "Edit wdiget" form on back-office.
	 *
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'ComponentWidget': the Widget which gets edited (by reference)
	 * @return boolean did we display something?
	 */
	function WidgetEndSettingsForm( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin', array( 'id' => 'WidgetEndSettingsForm' ) );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the WidgetEndSettingsForm event for widget #'.$params['ComponentWidget']->ID.'.' );
		$params['Form']->end_fieldset( 'Foo' );

		return true;
	}

	// }}}


	// Skin/Blog events: {{{

	/**
	 * Event handler: Called before a blog gets displayed (in _blog_main.inc.php).
	 *
	 * @see Plugin::BeforeBlogDisplay()
	 */
	function BeforeBlogDisplay( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the BeforeBlogDisplay event.' );
	}


	/**
	 * Event handler: Gets invoked when an action request was called which should be blocked in specific cases
	 *
	 * Blocakble actions: comment post, user login/registration, email send/validation, account activation
	 *
	 * @see Plugin::BeforeBlockableAction()
	 */
	function BeforeBlockableAction()
	{
		$this->msg( 'This is the TEST plugin responding to the BeforeBlockableAction event.' );
	}


	/**
	 * Event handler: Called when a MainList object gets created.
	 *
	 * Note: you must create your own MainList object here, set filters and query the database, see init_MainList() for detailes.
	 *
	 * @see Plugin::InitMainList()
	 * @param array Associative array of parameters
	 *   - 'MainList': The "MainList" object (by reference).
	 *   - 'limit': The number of posts to display
	 * @return boolean True if you've created your own MainList object and queried the database, false otherwise.
	 */
	function InitMainList( & $params )
	{
		global $Blog;
		global $preview, $disp;
		global $postIDlist, $postIDarray, $cat_array;

		$params['MainList'] = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $params['limit'] ); // COPY (FUNC)

		if( ! $preview )
		{
			if( $disp == 'page' )
			{	// Get pages:
				$params['MainList']->set_default_filters( array(
						'itemtype_usage' => 'page', // pages
					) );
			}

			if( $disp == 'search' && param('s', 'string') )
			{	// Here we allow b2evolution to search in posts and in pages
				$this->msg( 'TEST plugin: InitMainList() method allows us to search in both posts and pages.', 'note' );

				$params['MainList']->set_default_filters( array(
						'itemtype_usage' => 'post,page'
					) );
			}

			$params['MainList']->load_from_Request( false );

			// Save aggregate setting
			$saved_aggregate_state = $Blog->get_setting( 'aggregate_coll_IDs' );

			if( $disp == 'posts' && !empty($params['MainList']->filters['tags']) )
			{	// If the MainList if filtered by tag we search for posts in all public blogs
				if( !empty($params['MainList']->filters['tags']) )
				{	// All public blogs
					$BlogCache = & get_BlogCache();
					$Blog->set_setting( 'aggregate_coll_IDs', implode( ',', $BlogCache->load_public() ) );

					$this->msg( 'TEST plugin: InitMainList() method allows us to display tagged posts from all public blogs.', 'note' );
				}
			}

			// Run the query:
			$params['MainList']->query();

			// Restore aggregate setting to its original value
			$Blog->set_setting( 'aggregate_coll_IDs', $saved_aggregate_state );

			// Old style globals for category.funcs:
			$postIDlist = $params['MainList']->get_page_ID_list();
			$postIDarray = $params['MainList']->get_page_ID_array();
		}
		else
		{	// We want to preview a single post, we are going to fake a lot of things...
			$params['MainList']->preview_from_request();

			// Legacy for the category display
			$cat_array = array();
		}

		return true; // This is required!
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @see Plugin::SkinBeginHtmlHead()
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlHead( & $params )
	{
		require_js( '#jquery#', 'blog' );
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
		echo '<script type="text/javascript">
			jQuery( document ).ready( function()
			{
				jQuery( "#plugin_test_htsrv_action_'.$this->ID.'" ).click( function()
				{
					jQuery.ajax( {
						url: "'.$this->get_htsrv_url( 'test_action', array( 'param_1' => 'value_1' ), '&', true ).'",
						data: "param2=value2",
						success: function( result )
						{
							alert( "New record has been inserted to DB table \"'.$this->get_sql_table( 'test_table_name' ).'\"" );
						}
					} );
					return false;
				} );
			} );
		</script>';
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
		echo 'TEST plugin: SkinBeginHtmlBody event.';
	}


	/**
	 * Event handler: Called at the end of the skin's HTML BODY section.
	 *
	 * Use this to add any HTML snippet at the end of the generated page.
	 *
	 * @see Plugin::SkinBeginHtmlHead()
	 * @param array Associative array of parameters
	 */
	function SkinEndHtmlBody( & $params )
	{
		echo 'TEST plugin: SkinEndHtmlBody event.';
	}


	/**
	 * Event handler: Gets called before skin wrapper.
	 *
	 * Use this to add any HTML code before skin wrapper and after evo toolbar.
	 *
	 * @see Plugin::BeforeSkinWrapper()
	 * @param array Associative array of parameters
	 */
	function BeforeSkinWrapper( & $params )
	{
		echo '<p>TEST plugin: BeforeSkinWrapper event.</p>';

		echo '<p><a href="#" id="plugin_test_htsrv_action_'.$this->ID.'">Click here</a> to test htsrv plugin action and see a result in DB table <code>'.$this->get_sql_table( 'test_table_name' ).'</code>.</p>';
	}

	/**
	 * Called when a plugin gets called by its {@link $code}.
	 *
	 * If you provide this event, b2evolution will assume your plugin
	 * provides a widget and list it in the "Available widgets" list.
	 *
	 * @see Plugins::SkinTag()
	 * @param array The array passed to {@link Plugins::call_by_code()}.
	 */
	function SkinTag( & $params )
	{
		echo $params['block_start'];

		echo $params['block_title_start'];
		echo $params['title'];
		echo $params['block_title_end'];

		echo $params['block_body_start'];

		echo 'Test plugin widget content';

		echo $params['block_body_end'];

		echo $params['block_end'];

		return true;
	}

	/**
	 * Event handler: Gets asked about a list of skin names that the plugin handles.
	 *
	 * If one of the skins returned gets called through the "skin=X" URL param, the
	 * {@link Plugin::DisplaySkin()} method of your plugin gets called.
	 *
	 * @see Plugins::GetProvidedSkins()
	 * @return array
	 */
	function GetProvidedSkins()
	{
		return array( 'bootstrap_blog_skin' );
	}


	/**
	 * Event handler: Display a skin. Use {@link Plugin::GetProvidedSkins()} to return
	 * a list of names that you register.
	 *
	 * @see Plugins::DisplaySkin()
	 * @param array Associative array of parameters
	 *   - 'skin': name of skin to be displayed (from the list of {@link Plugin::GetProvidedSkins()}).
	 *             If your Plugin only registers one skin, you can ignore it.
	 */
	function DisplaySkin( & $params )
	{
		if( $params['skin'] == 'bootstrap_blog_skin' )
		{
			global $skins_path, $app_version, $disp, $ads_current_skin_path, $disp_handlers, $disp_handler, $Skin, $Blog, $Item;

			$ads_current_skin_path = $skins_path.$params['skin'].'/';

			$disp_handler = $ads_current_skin_path.'index.main.php';

			require $disp_handler;
		}
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
	 * @see Plugin::BeforeInstall()
	 * @return true|string True, if the plugin can be enabled/activated,
	 *                     a string with an error/note otherwise.
	 */
	function BeforeInstall()
	{
		$this->msg( 'TEST plugin: BeforeInstall event.' );
		return true;
	}


	/**
	 * Event handler: Called after the plugin has been installed.
	 *
	 * @see Plugin::AfterInstall()
	 */
	function AfterInstall()
	{
		$this->msg( 'TEST plugin sucessfully installed. All the hard work we did was adding this message in the AfterInstall event.. ;)' );
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
	 * @see Plugin::BeforeUninstall()
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
		$this->msg( 'TEST plugin sucessfully un-installed. All the hard work we did was adding this message.. ;)' );
		return true;
	}


	/**
	 * Event handler: Gets invoked to display the payload before uninstalling the plugin.
	 *
	 * You have to request a call to this during the plugin uninstall procedure by
	 * returning NULL in {@link BeforeUninstall()}.
	 *
	 * @see Plugin::BeforeUninstallPayload()
	 * @param array Associative array of parameters.
	 *              'Form': The {@link Form} that asks the user for confirmation (by reference).
	 *                      If your plugin uses canonical table names (see {@link Plugin::get_sql_table()}),
	 *                      there will be already a list of those tables included in it.
	 *                      Do not end the form, just add own inputs or hidden keys to it.
	 */
	function BeforeUninstallPayload( & $params )
	{
		echo 'TEST plugin: BeforeUninstallPayload event.';
	}


	/**
	 * Event handler: Called when the admin tries to enable the plugin, changes
	 * its configuration/settings and after installation.
	 *
	 * Use this, if your plugin needs configuration before it can be used.
	 *
	 * @see Plugin::BeforeEnable()
	 * @return true|string True, if the plugin can be enabled/activated,
	 *                     a string with an error/note otherwise.
	 */
	function BeforeEnable()
	{
		$this->msg( 'TEST plugin: BeforeEnable event.' );
		return true;  // default is to allow Activation
	}


	/**
	 * Event handler: Your plugin gets notified here, just before it gets
	 * disabled.
	 *
	 * You cannot prevent this, but only clean up stuff, if you have to.
	 *
	 * @see Plugin::BeforeDisable()
	 */
	function BeforeDisable()
	{
		$this->msg( 'TEST plugin: BeforeDisable event.' );
	}


	/**
	 * Event handler: Called when we detect a version change (in {@link Plugins::register()}).
	 *
	 * Use this for your upgrade needs.
	 *
	 * @see Plugin::PluginVersionChanged()
	 * @param array Associative array of parameters.
	 *              'old_version': The old version of your plugin as stored in DB.
	 *              'db_row': an array with the columns of the plugin DB entry (in T_plugins).
	 *                        The key 'plug_version' is the same as the 'old_version' key.
	 * @return boolean If this method returns false, the Plugin's status gets changed to "needs_config" and
	 *                 it gets unregistered for the current request.
	 */
	function PluginVersionChanged( & $params )
	{
		$this->msg( 'TEST plugin: BeforeDisable event.' );
		return true;
	}

	// }}}


	// Blog events: {{{

	/**
	 * Event handler: called at the end of {@link Blog::dbinsert() inserting
	 * a blog into the database}, which means it has been created.
	 *
	 * @see Plugin::AfterCollectionInsert()
	 * @param array Associative array of parameters
	 *   - 'Blog': the related Blog (by reference)
	 */
	function AfterCollectionInsert( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterCollectionInsert event.' );
	}


	/**
	 * Event handler: called at the end of {@link Blog::dbupdate() updating
	 * a blog in the database}.
	 *
	 * @see Plugin::AfterCollectionUpdate()
	 * @param array Associative array of parameters
	 *   - 'Blog': the related Blog (by reference)
	 */
	function AfterCollectionUpdate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterCollectionUpdate event.' );
	}


	/**
	 * Event handler: called at the end of {@link Blog::dbdelete() deleting
	 * a blog from the database}.
	 *
	 * @see Plugin::AfterCollectionDelete()
	 * @param array Associative array of parameters
	 *   - 'Blog': the related Blog (by reference)
	 */
	function AfterCollectionDelete( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterCollectionDelete event.' );
	}


	/**
	 * Event handler: Defines blog kinds, their names and description.
	 * Define blog settings in {@link Plugin::InitCollectionKinds()} method of your plugin.
	 *
	 * Note: You can change default blog kinds $params['kinds'] (which get passed by reference).
	 *
	 * @see Plugin::GetCollectionKinds()
	 * @param array Associative array of parameters
	 *   - 'kinds': dafault blog kinds (by reference)
	 * @retun: array
	 */
	function GetCollectionKinds( & $params )
	{
		$params['kinds'] = array_merge( $params['kinds'], array(
				'test_kind' => array(
					'name' => 'Just another collection type',
					'desc' => 'This is the TEST plugin handling the GetCollectionKinds event.',
				),
				'std' => array( // override standard blog settings
					'name' => 'Non-standard blog',
					'desc' => 'Description is changed by TEST plugin.',
				),
			) );

		return $params['kinds'];
	}


	/**
	 * Event handler: Defines blog settings by its kind. Use {@link get_collection_kinds()} to return
	 * an array of available blog kinds and their names.
	 * Define new blog kinds in {@link Plugin::GetCollectionKinds()} method of your plugin.
	 *
	 * Note: You have to change $params['Blog'] (which gets passed by reference).
	 *
	 * @see Plugin::InitCollectionKinds()
	 * @param array Associative array of parameters
	 *   - 'Blog': created Blog (by reference)
	 *   - 'kind': the kind of created blog (by reference)
	 */
	function InitCollectionKinds( & $params )
	{
		// Load blog functions
		load_funcs( 'collections/model/_blog.funcs.php' );

		// Get all available blog kinds
		$kinds = get_collection_kinds();

		switch( $params['kind'] )
		{
			case 'std': // override standard blog settings
				$params['Blog']->set( 'name', $kinds[$params['kind']]['name'] );
				break;

			case 'test_kind':
				$params['Blog']->set( 'name', $kinds[$params['kind']]['name'] );
				$params['Blog']->set( 'shortname', 'Test blog' );
				break;
		}
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
	 * @see Plugin::DisplayItemAsHtml()
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function RenderItemAsHtml( & $params )
	{
		$params['data'] = 'TEST['.$params['data'].']TEST - RenderItemAsHtml()';

		return true;
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
	 * @see Plugin::RenderItemAsXml()
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'xml' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function RenderItemAsXml( & $params )
	{
		$params['data'] = 'TEST['.$params['data'].']TEST - RenderItemAsXml()';

		return true;
	}


	/**
	 * Event handler: Called when rendering item/post contents other than XML or HTML.
	 *
	 * Note: return value is ignored. You have to change $params['data'].
	 *
	 * @see Plugin::RenderItemAsText()
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'text' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 *   - 'view_type': What part of a post are we displaying: 'teaser', 'extension' or 'full'
	 * @return boolean Have we changed something?
	 */
	function RenderItemAsText( & $params )
	{
		$params['data'] = 'TEST['.$params['data'].']TEST - RenderItemAsText()';

		return true;
	}


	/**
	 * Event handler: Called when displaying an item/post's content as HTML.
	 *
	 * This is different from {@link RenderItemAsHtml()}, because it gets called
	 * on every display (while rendering gets cached).
	 *
	 * @see Plugin::DisplayItemAsHtml()
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
		$params['data'] = $params['data']."\n<br />-- test_plugin::DisplayItemAsHtml()";

		return true;
	}


	/**
	 * Event handler: Called when displaying an item/post's content as XML.
	 *
	 * This is different from {@link RenderItemAsXml()}, because it gets called
	 * on every display (while rendering gets cached).
	 *
	 * @see Plugin::DisplayItemAsXml()
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
		$params['data'] = $params['data']."\n<br />-- test_plugin::DisplayItemAsXml()";

		return true;
	}


	/**
	 * Event handler: Called when displaying an item/post's content as text.
	 *
	 * This is different from {@link RenderItemAsText()}, because it gets called
	 * on every display (while rendering gets cached).
	 *
	 * @see Plugin::DisplayItemAsText()
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
		$params['data'] = $params['data']."\n<br />-- test_plugin::DisplayItemAsText()";

		return true;
	}


	/**
	 * Event handler: called at the beginning of {@link Item::dbupdate() updating
	 * an item/post in the database}.
	 *
	 * Use this to manipulate the {@link Item}, e.g. adding a renderer code
	 * through {@link Item::add_renderer()}.
	 *
	 * @see Plugin::PrependItemUpdateTransact()
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function PrependItemUpdateTransact( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the PrependItemUpdateTransact event.' );
	}


	/**
	 * Event handler: called at the end of {@link Item::dbupdate() updating
	 * an item/post in the database}.
	 *
	 * @see Plugin::AfterItemUpdate()
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 *   - 'dbchanges': array with DB changes; a copy of {@link Item::dbchanges()},
	 *                  before they got applied (since 1.9)
	 */
	function AfterItemUpdate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterItemUpdate event.' );
	}


	/**
	 * Event handler: called at the beginning of {@link Item::dbinsert() inserting
	 * an item/post in the database}.
	 *
	 * Use this to manipulate the {@link Item}, e.g. adding a renderer code
	 * through {@link Item::add_renderer()}.
	 *
	 * @see Plugin::PrependItemInsertTransact()
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function PrependItemInsertTransact( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the PrependItemInsertTransact event.' );
	}


	/**
	 * Event handler: called at the end of {@link Item::dbinsert() inserting
	 * a item/post into the database}, which means it has been created.
	 *
	 * @see Plugin::AfterItemInsert()
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 *   - 'dbchanges': array with DB changes; a copy of {@link Item::dbchanges()},
	 *                  before they got applied (since 1.9)
	 */
	function AfterItemInsert( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterItemInsert event.' );
	}


	/**
	 * Event handler: called at the end of {@link Item::dbdelete() deleting
	 * an item/post from the database}.
	 *
	 * @see Plugin::AfterItemDelete()
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function AfterItemDelete( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterItemDelete event.' );
	}


	/**
	 * Event handler: called when instantiating an Item for preview.
	 *
	 * @see Plugin::AppendItemPreviewTransact()
	 * @param array Associative array of parameters
	 *   - 'Item': the related Item (by reference)
	 */
	function AppendItemPreviewTransact( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AppendItemPreviewTransact event.' );
	}


	/**
	 * Event handler: Called at the end of the "Edit item" form.
	 *
	 * @see Plugin::AdminDisplayItemFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'Item': the Item which gets edited (by reference)
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display something?
	 */
	function AdminDisplayItemFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the AdminDisplayItemFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );

		return true;
	}


	/**
	 * Event handler: Called at the end of the "Edit item" form in front-office.
	 *
	 * @see Plugin::DisplayItemFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'Item': the Item which gets edited (by reference)
	 *   - 'edit_layout': "inskin", "expert", etc. (users, hackers, plugins, etc. may create their own layouts in addition to these)
	 *                    NOTE: Please respect the "inskin" mode, which should display only the most simple things!
	 * @return boolean did we display something?
	 */
	function DisplayItemFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the DisplayItemFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );

		return true;
	}


	/**
	 * Event handler: Called before an item gets deleted (in the backoffice).
	 *
	 * You could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the comment from being deleted.
	 *
	 * @see Plugin::AdminBeforeItemEditDelete()
	 * @since 2.0
	 * @param array Associative array of parameters
	 *              'Item': the Item which gets created (by reference)
	 */
	function AdminBeforeItemEditDelete( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AdminBeforeItemEditDelete event.' );
	}


	/**
	 * Event handler: Called before a new item gets created (in the backoffice).
	 *
	 * You could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the comment from being inserted.
	 *
	 * @see Plugin::AdminBeforeItemEditCreate()
	 * @param array Associative array of parameters
	 *              'Item': the Item which gets created (by reference)
	 */
	function AdminBeforeItemEditCreate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AdminBeforeItemEditCreate event.' );
	}


	/**
	 * Event handler: Called before an existing item gets updated (in the backoffice).
	 *
	 * You could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the comment from being inserted.
	 *
	 * @see Plugin::AdminBeforeItemEditUpdate()
	 * @param array Associative array of parameters
	 *              'Item': the Item which gets updated (by reference)
	 */
	function AdminBeforeItemEditUpdate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AdminBeforeItemEditUpdate event.' );
	}


	/**
	 * Event handler: the plugin gets asked if an item can receive feedback/comments.
	 *
	 * @see Plugin::ItemCanComment()
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
	 * @see Plugin::ItemSendPing()
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
		$this->msg( 'This is the TEST plugin responding to the ItemSendPing event.' );
	}


	/**
	 * Event handler: called to display the URL that accepts trackbacks for
	 *                an item.
	 *
	 * @see Plugin::DisplayTrackbackAddr()
	 * @param array Associative array of parameters
	 *   - 'Item': the {@link Item} object (by reference)
	 *   - 'template': the template to display the URL (%url%)
	 */
	function DisplayTrackbackAddr( & $params )
	{
		echo str_replace( '%url%', 'TEST plugin DisplayTrackbackAddr', $params['template'] );
	}


	/**
	 * Event handler: Does your Plugin want to apply as a renderer for the item?
	 *
	 * NOTE: this is especially useful for lazy Plugins, which would look
	 *       at the content and decide, if they apply.
	 *
	 * @see Plugin::DisplayTrackbackAddr()
	 * @param array Associative array of parameters
	 *   - 'Item': the {@link Item} object (by reference)
	 * @return boolean|NULL If true, the Plugin gets added as a renderer, false
	 *         removes it as a renderer (if existing) and NULL does not change the
	 *         renderer setting regarding your Plugin.
	 */
	function ItemApplyAsRenderer( & $params )
	{
		return true;
	}

	// }}}


	// Feedback (Comment/Trackback) events: {{{

	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @see Plugin::DisplayCommentToolbar()
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );

		echo $this->get_template( 'toolbar_title_before' );
		echo 'TEST toolbar for Comment:';
		echo $this->get_template( 'toolbar_title_after' );

		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 1" onclick="alert(\'TEST 1\')" />';
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 2" onclick="alert(\'TEST 2\')" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 3" onclick="alert(\'TEST 3 from second group\')" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_after' );

		return true;
	}


	/**
	 * Event handler: Called at the end of the "Edit comment" form on back-office.
	 *
	 * @see Plugin::AdminDisplayCommentFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form} object (by reference)
	 *   - 'Comment': the Comment which gets edited (by reference)
	 *   - 'edit_layout': only NULL currently, as there's only one layout
	 * @return boolean did we display something?
	 */
	function AdminDisplayCommentFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the AdminDisplayCommentFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );

		return true;
	}


	/**
	 * Event handler: Called at the end of the front-office comment form.
	 *
	 * You might want to use this to inject antispam payload to use in
	 * in {@link GetSpamKarmaForComment()} or modify the Comment according
	 * to it in {@link BeforeCommentFormInsert()}.
	 *
	 * @see Plugin::DisplayCommentFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'Item': the Item for which the comment is meant
	 */
	function DisplayCommentFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the DisplayCommentFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );

		$params['form_type'] = 'comment';
		$this->CaptchaPayload( $params );

		return true;
	}


	/**
	 * Event handler: Called in the submit button section of the
	 * front-office comment form.
	 *
	 * @see Plugin::DisplayCommentFormButton()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'Item': the Item for which the comment is meant
	 */
	function DisplayCommentFormButton( & $params )
	{
		?>
		<input type="button" value="TEST" onclick="alert('Hi! This is the TEST plugin (DisplayCommentFormButton)!');" class="btn btn-default" />
		<?php
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
	 * @see Plugin::CommentFormSent()
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
		$this->msg( 'This is the TEST plugin responding to the CommentFormSent event.' );
	}


	/**
	 * Event handler: Called before a comment gets inserted through the public comment
	 *                form.
	 *
	 * Use this, to validate a comment: you could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the comment from being inserted.
	 *
	 * @see Plugin::BeforeCommentFormInsert()
	 * @param array Associative array of parameters
	 *   - 'Comment': the Comment (by reference)
	 *   - 'original_comment': this is the unstripped and unformated posted comment
	 *   - 'action': "save" or "preview" (by reference) (since 1.10)
	 *   - 'is_preview': is this a request for previewing the comment? (boolean)
	 */
	function BeforeCommentFormInsert( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the BeforeCommentFormInsert event.' );

		$params['form_type'] = 'comment';

		if( $this->CaptchaValidated( $params ) === false )
		{	// Some error on captcha validation:
			$validate_error = $params['validate_error'];
			param_error( 'captcha_'.$this->code.'_'.$this->ID.'_answer', $validate_error );
		}
	}


	/**
	 * Event handler: Called when a comment form has been processed and the comment
	 *                got inserted into DB.
	 *
	 * @see Plugin::AfterCommentFormInsert()
	 * @param array Associative array of parameters
	 *   - 'Comment': the Comment (by reference)
	 *   - 'original_comment': this is the unstripped and unformated posted comment
	 */
	function AfterCommentFormInsert( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterCommentFormInsert event.' );
	}


	/**
	 * Event handler: Called to ask the plugin for the spam karma of a comment/trackback.
	 *
	 * This gets called just before the comment gets stored.
	 *
	 * @see Plugin::GetSpamKarmaForComment()
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
		$count = preg_match_all( '~(https?|ftp)://~i', $params['Comment']->content, $matches );

		if( $count > 5 )
		{	// If comment has more 5 urls decide this comment is spam:
			return 100;
		}

		// Not spam comment:
		return -100;
	}


	/**
	 * Event handler: called at the end of {@link Comment::dbupdate() updating
	 * a comment in the database}.
	 *
	 * @see Plugin::AfterCommentUpdate()
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 *   - 'dbchanges': array with DB changes; a copy of {@link Comment::dbchanges()},
	 *                  before they got applied (since 1.9)
	 */
	function AfterCommentUpdate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterCommentUpdate event.' );
	}


	/**
	 * Event handler: called at the end of {@link Comment::dbinsert() inserting
	 * a comment into the database}, which means it has been created.
	 *
	 * @see Plugin::AfterCommentInsert()
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 *   - 'dbchanges': array with DB changes; a copy of {@link Comment::dbchanges()},
	 *                  before they got applied (since 1.9)
	 */
	function AfterCommentInsert( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterCommentInsert event.' );
	}


	/**
	 * Event handler: called at the end of {@link Comment::dbdelete() deleting
	 * a comment from the database}.
	 *
	 * @see Plugin::AfterCommentDelete()
	 * @param array Associative array of parameters
	 *   - 'Comment': the related Comment (by reference)
	 */
	function AfterCommentDelete( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterCommentDelete event.' );
	}


	/**
	 * Event handler: called before a trackback gets recorded.
	 *
	 * Use this, to validate a trackback: you could {@link Plugin::msg() add a message} of
	 * category "error" here, to prevent the trackback from being accepted.
	 *
	 * @see Plugin::BeforeTrackbackInsert()
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
		$this->msg( 'This is the TEST plugin responding to the BeforeTrackbackInsert event.' );
	}

	/**
	 * Event handler: Gets called after a trackback has been recorded.
	 *
	 * @see Plugin::AfterTrackbackInsert()
	 * @param array Associative array of parameters
	 *   - 'Comment': the trackback (which is a {@link Comment} object with "trackback" type) (by reference)
	 *        The trackback-params get mapped like this:
	 *        - "blog_name" => "author"
	 *        - "url" => "author_url"
	 *        - "title"/"excerpt" => "comment"
	 */
	function AfterTrackbackInsert( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterTrackbackInsert event.' );
	}

	/**
	 * Event handler: called to filter the comment's author name (blog name for trackbacks)
	 *
	 * @see Plugin::FilterCommentAuthor()
	 * @param array Associative array of parameters
	 *   - 'data': the name of the author/blog (by reference)
	 *   - 'makelink': true, if the "data" contains a link
	 *   - 'Comment': the {@link Comment} object
	 */
	function FilterCommentAuthor( & $params )
	{
		$params['data'] = $params['data'].' TEST plugin FilterCommentAuthor()';
	}


	/**
	 * Event handler: called to filter the comment's author URL.
	 * This may be either the URL only or a full link (A tag).
	 *
	 * @see Plugin::FilterCommentAuthorUrl()
	 * @param array Associative array of parameters
	 *   - 'data': the URL of the author/blog (by reference)
	 *   - 'makelink': true, if the "data" contains a link (HTML A tag)
	 *   - 'Comment': the {@link Comment} object
	 */
	function FilterCommentAuthorUrl( & $params )
	{
		$params['data'] = $params['data'].' TEST plugin FilterCommentAuthorUrl()';
	}


	/**
	 * Event handler: called to filter the comment's content
	 *
	 * @see Plugin::FilterCommentContent()
	 * @param array Associative array of parameters
	 *   - 'data': the name of the author/blog (by reference)
	 *   - 'Comment': the {@link Comment} object
	 */
	function FilterCommentContent( & $params )
	{
		parent::FilterCommentContent( $params );
	}

	// }}}


	// Message form events: {{{

	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @see Plugin::DisplayMessageToolbar()
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );

		echo $this->get_template( 'toolbar_title_before' );
		echo 'TEST toolbar for Message:';
		echo $this->get_template( 'toolbar_title_after' );

		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 1" onclick="alert(\'TEST 1\')" />';
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 2" onclick="alert(\'TEST 2\')" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 3" onclick="alert(\'TEST 3 from second group\')" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_after' );

		return true;
	}


	/**
	 * Event handler: Called at the end of the front-office message form, which
	 * allows to send an email to a user/commentator.
	 *
	 * You might want to use this to inject antispam payload to use in
	 * in {@link MessageFormSent()}.
	 *
	 * @see Plugin::DisplayMessageFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'recipient_ID': ID of the user (if any)
	 *   - 'item_ID': ID of the item where the user clicked the msgform icon (if any)
	 *   - 'comment_ID': ID of the comment where the user clicked the msgform icon (if any)
	 */
	function DisplayMessageFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the DisplayMessageFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );

		return true;
	}


	/**
	 * Event handler: Called in the submit button section of the
	 * front-office message form.
	 *
	 * @see Plugin::DisplayMessageFormButton()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object
	 *   - 'recipient_ID': ID of the user (if any)
	 *   - 'item_ID': ID of the item where the user clicked the msgform icon (if any)
	 *   - 'comment_ID': ID of the comment where the user clicked the msgform icon (if any)
	 */
	function DisplayMessageFormButton( & $params )
	{
		?>
		<input type="button" value="TEST" onclick="alert('Hi! This is the TEST plugin (DisplayMessageFormButton)!');" class="btn btn-default" />
		<?php
	}


	/**
	 * Event handler: Called before at the beginning, if a message of thread form gets sent (and received).
	 *
	 * Use this to filter input
	 *
	 * @see Plugin::MessageThreadFormSent()
	 * @param array Associative array of parameters
	 *   - 'content': the message text (by reference)
	 */
	function MessageThreadFormSent( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the MessageThreadFormSent event.' );
	}


	/**
	 * Event handler: Called when a message form has been submitted.
	 *
	 * Add messages of category "error" to prevent the message from being sent.
	 *
	 * You can also alter the "message" or "message_footer" that gets sent here.
	 *
	 * @see Plugin::MessageFormSent()
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
		$this->msg( 'This is the TEST plugin responding to the MessageFormSent event.' );
	}


	/**
	 * Event handler: Called after a message has been sent through the public email form.
	 *
	 * This is meant to cleanup generated data.
	 *
	 * @see Plugin::MessageFormSent()
	 * @param array Associative array of parameters
	 *   - 'success_message' (bool): true if the message has been sent, false otherwise
	 */
	function MessageFormSentCleanup( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the MessageFormSentCleanup event.' );
	}


	/**
	 * Event handler: called to filter the message's content
	 *
	 * @see Plugin::FilterMsgContent()
	 * @param array Associative array of parameters
	 *   - 'data': the name of the author/blog (by reference)
	 *   - 'Message': the {@link Comment} object
	 */
	function FilterMsgContent( & $params )
	{
		parent::FilterMsgContent( $params );
	}

	/**
	 * Event handler: Called when rendering message contents as HTML. (CACHED)
	 *
	 * The rendered content will be *cached* and the cached content will be reused on subsequent displays.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @see Plugin::RenderMessageAsHtml()
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

	// }}}


	// Email form events: {{{

	/**
	 * Event handler: Called when displaying editor toolbars for email.
	 *
	 * @see Plugin::DisplayEmailToolbar()
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEmailToolbar( & $params )
	{
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );

		echo $this->get_template( 'toolbar_title_before' );
		echo 'TEST toolbar for Email Campaign:';
		echo $this->get_template( 'toolbar_title_after' );

		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 1" onclick="alert(\'TEST 1\')" />';
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 2" onclick="alert(\'TEST 2\')" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" class="'.$this->get_template( 'toolbar_button_class' ).'" value="TEST 3" onclick="alert(\'TEST 3 from second group\')" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_after' );

		return true;
	}


	/**
	 * Event handler: Called before at the beginning, if an email form gets sent (and received).
	 *
	 * Use this to filter input
	 *
	 * @see Plugin::EmailFormSent()
	 * @param array Associative array of parameters
	 *   - 'content': the message text (by reference)
	 */
	function EmailFormSent( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the EmailFormSent event.' );
	}


	/**
	 * Event handler: called to filter the email's content
	 *
	 * @see Plugin::FilterEmailContent()
	 * @param array Associative array of parameters
	 *   - 'EmailCampaign': the {@link EmailCampaign} object
	 */
	function FilterEmailContent( & $params )
	{
		parent::FilterEmailContent( $params );
	}

	/**
	 * Event handler: Called when rendering email contents as HTML. (CACHED)
	 *
	 * The rendered content will be *cached* and the cached content will be reused on subsequent displays.
	 *
	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'EmailCampaign': the {@link Item} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderEmailAsHtml( & $params )
	{
		// Use this render by default temporarily
		return $this->RenderItemAsHtml( $params );
	}

	// }}}


	// Caching events: {{{


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
	 * @see Plugin::CachePageContent()
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
	 * @see Plugin::CacheIsCollectingContent()
	 * @return boolean
	 */
	function CacheIsCollectingContent()
	{
		// Uncomment a line below if this plugin is collecting a content in cache currently:
		// return true;
	}


	/**
	 * This gets called before an image thumbnail gets created.
	 *
	 * This is useful to post-process the thumbnail image (add a watermark or change colors).
	 *
	 * @see Plugin::BeforeThumbCreate()
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
		$this->msg( 'This is the TEST plugin responding to the BeforeThumbCreate event.' );
	}

	// }}}


	// PluginSettings {{{
	/**
	 * Event handler: Called before displaying or setting a plugin's setting in the backoffice.
	 *
	 * @see PluginSettingsValidateSet()
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
		if( $params['name'] == 'input_me' )
		{
			if( $params['value'] != 'fine' && $params['value'] != 'bad' )
			{
				return 'Answer can be either "fine" or "bad".';
			}
		}
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
	 * @see PluginSettingsUpdateAction()
	 * @return false|NULL Return false to prevent the settings from being updated to DB.
	 */
	function PluginSettingsUpdateAction()
	{
		$this->msg( 'This is the TEST plugin responding to the PluginSettingsUpdateAction event.' );
	}


	/**
	 * Event handler: Called as action before displaying the "Edit plugin" form,
	 * which includes the display of the {@link Plugin::$Settings plugin's settings}.
	 *
	 * You may want to use this to check existing settings or display notes about
	 * something.
	 *
	 * @see PluginSettingsEditAction()
	 */
	function PluginSettingsEditAction()
	{
		$this->msg( 'This is the TEST plugin responding to the PluginSettingsEditAction event.' );
	}


	/**
	 * Event handler: Called after the form to edit the {@link Plugin::$Settings} has been
	 * displayed.
	 *
	 * Use this to add custom input fields (and catch them in {@link PluginSettingsUpdateAction()})
	 * or display custom output (e.g. a test link).
	 *
	 * @see PluginSettingsEditDisplayAfter()
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form}, where an fieldset has been opened already (by reference)
	 */
	function PluginSettingsEditDisplayAfter( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the PluginSettingsEditDisplayAfter event.' );
	}

	// }}}


	// PluginUserSettings {{{
	/**
	 * Event handler: Called before displaying or setting a plugin's user setting in the backoffice.
	 *
	 * @see PluginUserSettingsValidateSet()
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
		if( $params['name'] == 'deactivate' )
		{
			if( $params['value'] == 0 )
			{
				return 'Plaese enable setting "Deactivate" of Test plugin.';
			}
		}
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
	 * @see Plugin::PluginUserSettingsUpdateAction()
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User} for which the settings get updated
	 *   - 'action': "save", "reset"
	 * @return false|NULL Return false to prevent the settings from being updated to DB.
	 */
	function PluginUserSettingsUpdateAction( & $params )
	{
		if( $this->UserSettings->get('echo_random') )
		{
			$this->msg( 'TEST plugin: Random numbers have been disabled.' );
		}
		else
		{
			$this->msg( 'TEST plugin: Random numbers have been enabled.' );
		}

		return true;
	}


	/**
	 * Event handler: Called as action before displaying the "Edit user" form,
	 * which includes the display of the {@link Plugin::$UserSettings plugin's user settings}.
	 *
	 * You may want to use this to check existing settings or display notes about
	 * something.
	 *
	 * @see PluginUserSettingsEditAction()
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User} for which the settings are being displayed/edited
	 */
	function PluginUserSettingsEditAction( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the PluginUserSettingsEditAction event.' );
	}


	/**
	 * Event handler: Called after the form to edit the {@link Plugin::$UserSettings} has been
	 * displayed.
	 *
	 * Use this to add custom input fields (and catch them in {@link PluginUserSettingsUpdateAction()})
	 * or display custom output (e.g. a test link).
	 *
	 * @see PluginUserSettingsEditDisplayAfter()
	 * @param array Associative array of parameters
	 *   - 'Form': the {@link Form}, where an fieldset has been opened already (by reference)
	 *   - 'User': the {@link User} whose settings get displayed for editing (since 1.10.0)
	 */
	function PluginUserSettingsEditDisplayAfter( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the PluginUserSettingsEditDisplayAfter event.' );
	}

	// }}}

	// PluginCollSettings {{{
	/**
	 * Event handler: Called as action just before updating plugin's collection/blog settings.
	 *
	 * Use this to add notes/errors through {@link Plugin::msg()} or to process saved settings.
	 *
	 * @see PluginCollSettingsUpdateAction()
	 */
	function PluginCollSettingsUpdateAction()
	{
		$this->msg( 'This is the TEST plugin responding to the PluginCollSettingsUpdateAction event.' );
	}

	// }}}

	// PluginMsgSettings {{{
	/**
	 * Event handler: Called as action just before updating plugin's messages settings.
	 *
	 * Use this to add notes/errors through {@link Plugin::msg()} or to process saved settings.
	 *
	 * @see PluginMsgSettingsUpdateAction()
	 */
	function PluginMsgSettingsUpdateAction()
	{
		$this->msg( 'This is the TEST plugin responding to the PluginMsgSettingsUpdateAction event.' );
	}

	// }}}

	// PluginEmailSettings {{{
	/**
	 * Event handler: Called as action just before updating plugin's email campaign settings.
	 *
	 * Use this to add notes/errors through {@link Plugin::msg()} or to process saved settings.
	 *
	 * @see PluginEmailSettingsUpdateAction()
	 */
	function PluginEmailSettingsUpdateAction()
	{
		$this->msg( 'This is the TEST plugin responding to the PluginEmailSettingsUpdateAction event.' );
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
	 *
	 * @see Plugin::AfterLoginAnonymousUser()
	 */
	function AfterLoginAnonymousUser( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterLoginAnonymousUser event.' );
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
	 *
	 * @see Plugin::AfterLoginRegisteredUser()
	 */
	function AfterLoginRegisteredUser( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterLoginRegisteredUser event.' );
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
	 * @see Plugin::AppendUserRegistrTransact()
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User user object} (as reference).
	 * @return boolean false if the whole transaction should get rolled back (the user does not get created).
	 */
	function AppendUserRegistrTransact( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AppendUserRegistrTransact event.' );

		return true;
	}


	/**
	 * Event handler: Called when a new user has registered and got created.
	 *
	 * Note: if you want to modify a new user,
	 * use {@link Plugin::AppendUserRegistrTransact()} instead!
	 *
	 * @see Plugin::AfterUserRegistration()
	 * @param array Associative array of parameters
	 *   - 'User': the {@link User user object} (as reference).
	 */
	function AfterUserRegistration( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterUserRegistration event.' );
	}


	/**
	 * Event handler: Called at the begining of the "Register as new user" form.
	 *
	 * You might want to use this to inject antispam payload to use
	 * in {@link Plugin::RegisterFormSent()}.
	 *
	 * @see Plugin::DisplayRegisterFormBefore()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 *   - 'inskin': boolean true if the form is displayed in skin
	 */
	function DisplayRegisterFormBefore( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the DisplayRegisterFormBefore event.' );
	}


	/**
	 * Event handler: Called at the end of the "Register as new user" form.
	 *
	 * You might want to use this to inject antispam payload to use
	 * in {@link Plugin::RegisterFormSent()}.
	 *
	 * @see Plugin::DisplayRegisterFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 *   - 'inskin': boolean true if the form is displayed in skin
	 */
	function DisplayRegisterFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the DisplayRegisterFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );

		return true;
	}


	/**
	 * Event handler: Called when a "Register as new user" form has been submitted.
	 *
	 * You can cancel the registration process by {@link Plugin::msg() adding a message}
	 * of type "error".
	 *
	 * @see Plugin::RegisterFormSent()
	 * @param array Associative array of parameters
	 *   - 'login': Login name (by reference) (since 1.10.0)
	 *   - 'email': E-Mail value (by reference) (since 1.10.0)
	 *   - 'locale': Locale value (by reference) (since 1.10.0)
	 *   - 'pass1': Password (by reference) (since 1.10.0)
	 *   - 'pass2': Confirmed password (by reference) (since 1.10.0)
	 */
	function RegisterFormSent( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the RegisterFormSent event.' );
	}


	/**
	 * Event handler: Called at the end of the "Login" form.
	 *
	 * You might want to use this to inject payload to use
	 * in {@link LoginAttempt()}.
	 *
	 * @see Plugin::DisplayLoginFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 */
	function DisplayLoginFormFieldset( & $params )
	{
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin hooking the DisplayLoginFormFieldset event.' );
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
	 * @see Plugin::LoginAttempt()
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
		$this->msg( 'This is the TEST plugin responding to the LoginAttempt event.', 'note' );
	}


	/**
	 * Event handler: your Plugin should return true here, if it needs a raw (un-hashed)
	 * password for the {@link Plugin::LoginAttempt()} event. If any Plugin returns true
	 * for this event, client-side hashing of the password is not used.
	 * NOTE: this causes passwords to travel un-encrypted, unless SSL/HTTPS get used.
	 *
	 * @see Plugin::LoginAttemptNeedsRawPassword()
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
	 * @see Plugin::Logout()
	 * @param array Associative array of parameters
	 *   - 'User': the user object
	 */
	function Logout( $params )
	{
		$this->msg( 'This is the TEST plugin responding to the Logout event.' );
	}


	/**
	 * Event handler: Called at the end of the "Validate user account" form, which gets
	 *                invoked if newusers_mustvalidate is enabled and the user has not
	 *                been validated yet.
	 *
	 * The corresponding action event is {@link Plugin::ValidateAccountFormSent()}.
	 *
	 * @see Plugin::DisplayValidateAccountFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the comment form generating object (by reference)
	 */
	function DisplayValidateAccountFormFieldset( & $params )
	{
		$params['Form']->info( 'TEST plugin', 'This is the TEST plugin responding to the ValidateAccountFormSent event.' );
	}


	/**
	 * Event handler: Called when a "Validate user account" form has been submitted.
	 *
	 * You can cancel the registration process by {@link Plugin::msg() adding a message}
	 * of type "error".
	 *
	 * @see Plugin::ValidateAccountFormSent()
	 */
	function ValidateAccountFormSent( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the ValidateAccountFormSent event.' );
	}


	/**
	 * Event handler: Called at the end of the "User profile" form.
	 *
	 * The corresponding action event is {@link Plugin::ProfileFormSent()}.
	 *
	 * @see Plugin::DisplayProfileFormFieldset()
	 * @param array Associative array of parameters
	 *   - 'Form': the user profile form generating object (by reference)
	 *   - 'User': the edited user object (by reference)
	 *   - 'edit_layout':
	 *			"public" - public front-office user profile form (info only),
	 *			"private" - private front-office user profile form (editable),
	 *   - 'is_admin_page': (boolean) indicates whether we are in front-office or backoffice
	 */
	function DisplayProfileFormFieldset( & $params )
	{
		if( $params['edit_layout'] == 'public' )
		{ // Do nothing in public mode
			return false;
		}

		$params['Form']->info( 'TEST plugin', 'This is the TEST plugin responding to the DisplayProfileFormFieldset event.' );
	}


	/**
	 * Event handler: Called before at the beginning, if a profile form gets sent (and received).
	 *
	 * Use this to filter input
	 *
	 * @see Plugin::ProfileFormSent()
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
		$this->msg( 'This is the TEST plugin responding to the ProfileFormSent event.' );
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
	 * @see Plugin::AlternateAuthentication()
	 * @return boolean True, if the user has been authentificated (set in $Session)
	 */
	function AlternateAuthentication( & $params )
	{
		if( 0 ) // you should only enable it for test purposes, because it automagically logs every user in as "demouser"!
		{
			global $Session, $Messages;

			$UserCache = & get_UserCache();
			if( $demo_User = & $UserCache->get_by_login('demouser') )
			{ // demouser exists:
				$Session->set_User( $demo_User );
				$Messages->add( 'Logged in as demouser.', 'success' );
				return true;
			}
		}
	}


	/**
	 * Event handler: called at the end of {@link User::dbupdate() updating
	 * an user account in the database}, which means that it has been changed.
	 *
	 * @see Plugin::AfterUserUpdate()
	 * @since 1.8.1
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 */
	function AfterUserUpdate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterUserUpdate event.' );
	}


	/**
	 * Event handler: called at the end of {@link User::dbinsert() inserting
	 * an user account into the database}, which means it has been created.
	 *
	 * @see Plugin::AfterUserInsert()
	 * @since 1.8.1
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 */
	function AfterUserInsert( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterUserInsert event.' );
	}


	/**
	 * Event handler: called at the end of {@link User::dbdelete() deleting
	 * an user from the database}.
	 *
	 * @see Plugin::AfterUserDelete()
	 * @since 1.8.1
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 */
	function AfterUserDelete( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AfterUserDelete event.' );
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
	 * @see Plugin::CaptchaPayload()
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
		if( ! isset( $params['form_type'] ) || $params['form_type'] != 'comment' )
		{	// Apply captcha only for comment form:
			return;
		}

		// Get current or new question:
		$question = $this->get_captcha_question();

		if( empty( $question ) )
		{	// No question detected:
			echo 'Test plugin CaptchaPayload: Sorry, impossible to initialize questions for captcha validation.';
			return;
		}

		if( ! isset( $params['Form'] ) )
		{	// There's no Form where we add to, but we create our own form:
			$Form = new Form( regenerate_url() );
			$Form->begin_form();
		}
		else
		{
			$Form = & $params['Form'];
		}

		$Form->info( 'TEST Captcha question', $question['question'] );
		$Form->text_input( 'captcha_'.$this->code.'_'.$this->ID.'_answer', param( 'captcha_'.$this->code.'_'.$this->ID.'_answer', 'string', '' ), 10, 'TEST Captcha answer' );

		if( ! isset( $params['Form'] ) )
		{	// There's no Form where we add to, but our own form:
			$Form->end_form( array( array( 'submit', 'submit', 'Validate me', 'ActionButton' ) ) );
		}
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
	 * @see Plugin::CaptchaValidated()
	 * @param array Associative array of parameters
	 *   - 'validate_error': you can optionally set this, if you want to give a reason
	 *     of the failure. This is optionally and meant to be used by other plugins
	 *     that trigger this event.
	 * @return boolean true if the catcha could be validated
	 */
	function CaptchaValidated( & $params )
	{
		if( ! isset( $params['form_type'] ) || $params['form_type'] != 'comment' )
		{	// Apply captcha only for comment form:
			return;
		}

		$posted_answer = utf8_strtolower( param( 'captcha_'.$this->code.'_'.$this->ID.'_answer', 'string', '' ) );

		if( empty( $posted_answer ) )
		{
			$this->debug_log( 'captcha_'.$this->code.'_'.$this->ID.'_answer' );
			$params['validate_error'] = 'Please enter TEST captcha answer.';
			if( $comment_Item = & $params['Comment']->get_Item() )
			{
				syslog_insert( 'Comment TEST captcha answer is not entered', 'warning', 'item', $comment_Item->ID, 'plugin', $this->ID );
			}
			return false;
		}

		// Get current question:
		$current_question = $this->get_captcha_question();

		if( $posted_answer != utf8_strtolower( $current_question['answer'] ) )
		{	// Wrong answer:
			$this->debug_log( 'Posted ('.$posted_answer.') and answer "test" do not match!' );
			$params['validate_error'] = 'The entered TEST answer is incorrect.';
			if( $comment_Item = & $params['Comment']->get_Item() )
			{
				syslog_insert( 'Comment TEST captcha answer is incorrect', 'warning', 'item', $comment_Item->ID, 'plugin', $this->ID );
			}
			return false;
		}

		// If answer is correct:
		//   We should clean the question ID that was assigned for current session and IP address
		//   It gives to assign new question on the next captcha event
		$this->CaptchaValidatedCleanup( $params );

		return true;
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
	 * 
	 * @see Plugin::CaptchaValidatedCleanup()
	 */
	function CaptchaValidatedCleanup( & $params )
	{
		global $Session;

		// Remove question ID from session
		$Session->delete( 'captcha_'.$this->code.'_'.$this->ID );
	}


	/**
	 * Event handler: called at the end of {@link DataObject::dbinsert() inserting an object in the database}.
	 *
	 * @see Plugin::AfterObjectInsert()
	 * @param array Associative array of parameters
	 *   - 'Object': the related Object (by reference)
	 *   - 'type': class name of deleted Object (Chapter, File, Blog, Link, Comment, Slug etc.) (by reference)
	 */
	function AfterObjectInsert( & $params )
	{
		$this->msg( sprintf('This is the TEST plugin responding to the AfterObjectInsert event. You have just created new [%s]', $params['type']), 'note' );
	}


	/**
	 * Event handler: called at the end of {@link DataObject::dbupdate() updating an object in the database}.
	 *
	 * @see Plugin::AfterObjectUpdate()
	 * @param array Associative array of parameters
	 *   - 'Object': the related Object (by reference)
	 *   - 'type': class name of deleted Object (Chapter, File, Blog, Link, Comment, Slug etc.) (by reference)
	 */
	function AfterObjectUpdate( & $params )
	{
		$this->msg( sprintf('This is the TEST plugin responding to the AfterObjectUpdate event. You have just changed a [%s]', $params['type']), 'note' );
	}


	/**
	 * Event handler: called at the end of {@link DataObject::dbdelete() deleting an object from the database}.
	 *
	 * @see Plugin::AfterObjectDelete()
	 * @param array Associative array of parameters
	 *   - 'Object': the related Object (by reference)
	 *   - 'type': class name of deleted Object (Chapter, File, Blog, Link, Comment, Slug etc.) (by reference)
	 */
	function AfterObjectDelete( & $params )
	{
		$this->msg( sprintf('This is the TEST plugin responding to the AfterObjectDelete event. You have just deleted a [%s]', $params['type']), 'note' );
	}
	// }}}


	/**
	 * Event handler: Called when an IP address gets displayed, typically in a protected
	 * area or for a privileged user, e.g. in the backoffice statistics menu.
	 *
	 * @see Plugin::FilterIpAddress()
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 * @return boolean Have we changed something?
	 */
	function FilterIpAddress( & $params )
	{
		$params['data'] = '[[IP:'.$params['data'].' (TEST plugin)]]';

		return true;
	}


	/**
	 * Event handler: Called after initializing plugins, DB, Settings, Hit, .. but
	 * quite early.
	 *
	 * This is meant to be a good point for Antispam plugins to cancel the request.
	 *
	 * @see Plugin::SessionLoaded()
	 */
	function SessionLoaded()
	{
		$this->msg( 'This is the TEST plugin responding to the SessionLoaded event.' );
	}


	/**
	 * Event handler: Called right after initializing plugins. This is the earliest event you can use.
	 *
	 * This is meant to be a good point for doing early processing and cancelling the request.
	 * Note that at this point DB charset is not set, Session and Hit aren't initialized
	 *
	 * @see Plugin::AfterPluginsInit()
	 */
	function AfterPluginsInit()
	{
		$this->msg( 'This is the TEST plugin responding to the AfterPluginsInit event.' );
	}


	/**
	 * Event handler: Called at the end of _main.inc.php. This is the the latest event called before blog initialization.
	 *
	 * This is meant to be a good point for doing processing that don't require a blog to be initialized.
	 *
	 * @see Plugin::AfterMainInit()
	 */
	function AfterMainInit()
	{
		$this->msg( 'This is the TEST plugin responding to the AfterMainInit event.' );
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
	 * @see Plugin::BeforeSessionsDelete()
	 * @param array Associative array of parameters
	 *   - 'IDs': list of session IDs that are about to get deleted (WARNING: potentially huge)
	 * @return array List of IDs that should not get deleted
	 */
	function BeforeSessionsDelete( & $params )
	{
		$this->debug_log('BeforeSessionsDelete: Could have prevented the deletion of all sessions older than ' ).date('Y-m-d H:i:s', $params['cutoff_timestamp' ] );
		return array();
	}


	/**
	 * Event handler: Called when a hit gets logged, but before it gets recorded.
	 *
	 * @see Plugin::AppendHitLog()
	 * @param array Associative array of parameters
	 *   - 'Hit': The "Hit" object (by reference).
	 *
	 * @return boolean True if you've handled the recording of the hit, false otherwise.
	 */
	function AppendHitLog( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AppendHitLog event.' );

		// Do nothing by default:
		return false;
	}


	/**
	 * Event handler: Called before an uploaded file gets saved on server.
	 *
	 * @see Plugin::AfterFileUpload()
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
		$this->msg( 'This is the TEST plugin responding to the AfterFileUpload event.' );
		return array(); // Do nothing by default:
	}


	/**
	 * This method should return a string that used as suffix
	 *   for the field 'From Country' on the user profile page in the BackOffice
	 *
	 * @see Plugin::GetUserFromCountrySuffix()
	 * @param array Associative array of parameters
	 *   - 'User': the related User (by reference)
	 * @return string Field suffix
	 */
	function GetUserFromCountrySuffix( & $params )
	{
		return 'Test plugin event "GetUserFromCountrySuffix"';
	}


	/**
	 * This method initializes an array that used as additional columns
	 *   for the results table in the BackOffice
	 *
	 * @see Plugin::GetAdditionalColumnsTable()
	 * @param array Associative array of parameters
	 *   'table'   - Special name that used to know what plugin must use current table
	 *   'column'  - DB field which contains IP address
	 *   'Results' - Object
	 */
	function GetAdditionalColumnsTable( & $params  )
	{
		$params = array_merge( array(
				'table'   => '', // sessions, activity, ipranges
				'column'  => '', // sess_ipaddress, comment_author_IP, aipr_IPv4start, hit_remote_addr
				'Results' => NULL, // object Results
			), $params );

		if( is_null( $params['Results'] ) || !is_object( $params['Results'] ) )
		{	// Results must be object
			return;
		}

		if( in_array( $params['table'], array( 'sessions', 'activity', 'ipranges', 'top_ips' ) ) )
		{	// Display column only for required tables by Test plugin:
			$column = array(
				'th' => 'TEST Column',
				'td' => 'TEST Value',
			);
			$params['Results']->cols[] = $column;
		}
	}


	////////// Custom plugin methods - START //////////


	/**
	 * Assign config questions
	 *
	 * @return array Questions array
	 */
	function get_captcha_questions()
	{
		return array(
				'1' => array( 'question' => 'Question 1?', 'answer' => 'Answer 1' ),
				'2' => array( 'question' => 'Question 2?', 'answer' => 'Answer 2' ),
			);
	}

	/**
	 * Get question for current session
	 *
	 * @return array Question data from plugin config
	 */
	function get_captcha_question()
	{
		global $Session;

		$question = NULL;

		// Get question ID from Session:
		$this->question_ID = $Session->get( 'captcha_'.$this->code.'_'.$this->ID );

		if( empty( $this->question_ID ) )
		{	// Assign new random question for current Session:
			$question = $this->get_new_captcha_question();
		}

		if( empty( $question ) && ! empty( $this->question_ID ) )
		{	// Get question data:
			$questions = $this->get_captcha_questions();
			if( isset( $questions[ $this->question_ID ] ) )
			{
				$question = $questions[ $this->question_ID ];
			}

			if( empty( $question ) )
			{	// Assign random question if previous question doesn't exist in config:
				// This case may happens when admin changed the questions but user has the old question ID in the session
				$question = $this->get_new_captcha_question();
			}
		}

		return $question;
	}


	/**
	 * Assign new random question for current session
	 *
	 * @return array Question data with keys 'question' and 'answer'
	 */
	function get_new_captcha_question()
	{
		global $Session;

		$questions = $this->get_captcha_questions();

		// Get random question:
		$this->question_index = rand( 1, count( $questions ) );

		// Save the assigned question index in the session:
		$Session->set( 'captcha_'.$this->code.'_'.$this->ID, $this->question_index );
		$Session->dbsave();

		return $questions[ $this->question_index ];
	}


	////////// Custom plugin methods - END //////////
}

?>
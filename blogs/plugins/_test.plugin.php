<?php
/**
 * This file implements the TEST plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
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
	var $version = '1.9-dev';
	var $author = 'The b2evo Group';
	var $help_url = '';  // empty URL defaults to manual wiki

	/*
	 * These variables MAY be overriden.
	 */
	var $apply_rendering = 'opt-out';
	var $number_of_installs = 1;
	var $group = 'test';


	/**
	 * Init
	 *
	 * This gets called after a plugin has been registered/instantiated.
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = 'Test plugin';
		$this->long_desc = 'This plugin responds to virtually all possible plugin events :P';
	}


	/**
	 * Get the settings that the plugin can use.
	 *
	 * Those settings are transfered into a Settings member object of the plugin
	 * and can be edited in the backoffice (Settings / Plugins).
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @see PluginSettings
	 * @see Plugin::PluginSettingsValidateSet()
	 * @return array
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
			'my_select' => array(
				'label' => 'Selector',
				'id' => $this->classname.'_my_select',
				'onchange' => 'document.getElementById("'.$this->classname.'_a_disabled_one").disabled = ( this.value == "sun" );',
				'defaultvalue' => 'one',
				'type' => 'select',
				'options' => array( 'sun' => 'Sunday', 'mon' => 'Monday' ),
			),
			'a_disabled_one' => array(
				'label' => 'This one is disabled',
				'id' => $this->classname.'_a_disabled_one',
				'type' => 'checkbox',
				'defaultvalue' => '1',
				'disabled' => true, // this can be useful if you detect that something cannot be changed. You probably want to add a 'note' then, too.
				'note' => 'Change the above select input to "Monday" to enable it.',
			),
			'blog' => array(
				'label' => 'A blog',
				'type' => 'select_blog',
				'allow_none' => true,
			),
			'blogs' => array(
				'label' => 'A set of blogs',
				'type' => 'select_blog',
				'multiple' => true,
				'allow_none' => true,
			),
			'sets' => array(
				'type' => 'array',
				'min_count' => 0,
				'max_count' => 3,
				'entries' => array(
					'user' => array(
						'label' => 'A user',
						'type' => 'select_user',
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
	 * User settings.
	 *
	 * @see Plugin::GetDefaultUserSettings()
	 * @see PluginUserSettings
	 * @see Plugin::PluginUserSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultUserSettings()
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
	 * We trigger an extra event ourself (which we also provide ourselves).
	 *
	 * @return array
	 */
	function GetExtraEvents()
	{
		return array(
				// Gets "min" and "max" as params and should return a random number in between:
				'test_plugin_get_random' => 'TEST event that returns a random number.',
			);
	}


	/**
	 * Define a test cron job
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
	 * Execute/Handle a test/sample cronjob.
	 */
	function ExecCronJob( & $params )
	{
		if( $params['ctrl'] == 'test_job' )
		{
			return array( 'code' => 1, 'message' => 'Test successful.' );
		}
	}


	/**
	 * Deactive the plugin for the current request if the user wants it so.
	 * @see Plugin::AppendLoginRegisteredUser()
	 */
	function AppendLoginRegisteredUser()
	{
		if( $this->UserSettings->get('deactivate') )
		{
			$this->forget_events();
		}
	}


	/**
	 * Define some dependencies.
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
	 * Gets asked for, if user settings get updated.
	 *
	 * We just add a note.
	 *
	 * @see Plugin::PluginUserSettingsUpdateAction()
	 */
	function PluginUserSettingsUpdateAction()
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
	 * Event handlers:
	 */

	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @see Plugin::AdminEndHtmlHead()
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		echo '<!-- This comment was added by the TEST plugin -->';

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
	 * Event handler: Called when displaying editor toolbars.
	 *
	 * @see Plugin::AdminDisplayToolbar()
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		echo '<div class="edit_toolbar">This is the TEST Toolbar</div>';

		return true;
	}


	/**
	 * Event handler: Called when displaying editor buttons.
	 *
	 * @see Plugin::AdminDisplayEditorButton()
	 * @param array Associative array of parameters
	 * @return boolean did we display ?
	 */
	function AdminDisplayEditorButton( & $params )
	{
		if( $params['edit_layout'] == 'simple' )
		{ // this is the "simple" layout, we do nothing
			return false;
		}
		?>
		<input type="button" value="TEST" onclick="alert('Hi! This is the TEST plugin (AdminDisplayEditorButton)!');" />
		<?php
		return true;
	}


	/**
	 * @see Plugin::AdminDisplayItemFormFieldset()
	 */
	function AdminDisplayItemFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the AdminDisplayItemFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );
	}


	/**
	 * @see Plugin::AdminBeforeItemEditCreate()
	 */
	function AdminBeforeItemEditCreate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AdminBeforeItemEditCreate event.' );
	}


	/**
	 * @see Plugin::AdminBeforeItemEditUpdate()
	 */
	function AdminBeforeItemEditUpdate( & $params )
	{
		$this->msg( 'This is the TEST plugin responding to the AdminBeforeItemEditUpdate event.' );
	}


	/**
	 * @see Plugin::AdminDisplayCommentFormFieldset()
	 */
	function AdminDisplayCommentFormFieldset( & $params )
	{
		$params['Form']->begin_fieldset( 'TEST plugin' );
		$params['Form']->info_field( 'TEST plugin', 'This is the TEST plugin responding to the AdminDisplayCommentFormFieldset event.' );
		$params['Form']->end_fieldset( 'Foo' );
	}


	/**
	 * Event handler: Gets invoked in /admin.php for every backoffice page after
	 *                the menu structure is build. You can use the {@link $AdminUI} object
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
	 * Event handler: Called when handling actions for the "Tools" menu.
	 *
	 * Use {@link $Messages} to add Messages for the user.
	 *
	 * @see Plugin::AdminToolAction()
	 */
	function AdminToolAction( $params )
	{
		global $Messages;

		$Messages->add( 'Hello, This is the AdminToolAction for the TEST plugin.' );
	}


	/**
	 * Event handler: Called when displaying the block in the "Tools" menu.
	 *
	 * @see Plugin::AdminToolPayload()
	 */
	function AdminToolPayload( $params )
	{
		echo 'Hello, This is the AdminToolPayload for the TEST plugin.';
	}


	/**
	 * Event handler: Method that gets invoked when our tab (?tab=plug_ID_X) is selected.
	 *
	 * You should catch params (GET/POST) here and do actions (no output!).
	 * Use {@link $Messages} to add messages for the user.
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
	 * @see Plugin::AdminTabPayload()
	 */
	function AdminTabPayload()
	{
		echo 'Hello, this is the AdminTabPayload for the TEST plugin.';

		echo $this->text_from_AdminTabAction;

		// TODO: this is tedious.. should either be a global function (get_admin_Form()) or a plugin helper..
		$Form = & new Form();
		$Form->begin_form();
		$Form->hidden_ctrl(); // needed to pass the "ctrl=tools" param
		$Form->hiddens_by_key( get_memorized() ); // needed to pass all other memorized params, especially "tab"

		$Form->text_input( $this->get_class_id().'_text', $this->param_text, '20', 'Text' );

		$Form->button_input(); // default "submit" button

		$Form->end_form();
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
	 * Event handler: Called when rendering item/post contents as HTML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$params['data'] = 'TEST['.$params['data'].']TEST';
	}


	/**
	 * Event handler: Called when rendering item/post contents as XML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::RenderItemAsXml()
	 */
	function RenderItemAsXml( & $params )
	{
		// Do the same as with HTML:
		$this->RenderItemAsHtml( $params );
	}


	/**
	 * Event handler: Called when rendering item/post contents as text.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::RenderItemAsText()
	 */
	function RenderItemAsText( & $params )
	{
		// Do nothing.
	}


	/**
	 * Event handler: Called when displaying item/post contents as HTML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::DisplayItemAsHtml()
	 */
	function DisplayItemAsHtml( & $params )
	{
		$params['data'] = $params['data']."\n<br />-- test_plugin::DisplayItemAsHtml()";
	}


	/**
	 * Event handler: Called when displaying item/post contents as XML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::DisplayItemAsXml()
	 */
	function DisplayItemAsXml( & $params )
	{
		$params['data'] = $params['data']."\n<br />-- test_plugin::DisplayItemAsXml()";
	}


	/**
	 * Event handler: Called when displaying item/post contents as text.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @see Plugin::DisplayItemAsText()
	 */
	function DisplayItemAsText( & $params )
	{
		$params['data'] = $params['data']."\n<br />-- test_plugin::DisplayItemAsText()";
	}


	/**
	 * Wrap a to be displayed IP address.
	 * @see Plugin::FilterIpAddress()
	 */
	function FilterIpAddress( & $params )
	{
		$params['data'] = '[[IP:'.$params['data'].' (TEST plugin)]]';
	}


	/**
	 * Event handler: Called before the plugin is installed.
	 * @see Plugin::BeforeInstall()
	 */
	function BeforeInstall()
	{
		global $Plugins;
		$this->msg( 'TEST plugin: BeforeInstall event.' );
		return true;
	}


	/**
	 * Event handler: Called when the plugin has been installed.
	 * @see Plugin::AfterInstall()
	 */
	function AfterInstall()
	{
		$this->msg( 'TEST plugin sucessfully installed. All the hard work we did was adding this message in the AfterInstall event.. ;)' );
	}


	/**
	 * Event handler: Called before the plugin is going to be un-installed.
	 * @see Plugin::BeforeUninstall()
	 */
	function BeforeUninstall()
	{
		$this->msg( 'TEST plugin sucessfully un-installed. All the hard work we did was adding this message.. ;)' );
		return true;
	}


	/**
	 * Event handler: called when a new user has registered.
	 * @see Plugin::AfterUserRegistration()
	 */
	function AfterUserRegistration( $params )
	{
		$this->msg( 'The TEST plugin welcomes the new user '.$params['User']->dget('login').'!' );
	}


	/**
	 * Event handler: Called at the end of the "Login" form.
	 * @see Plugin::DisplayLoginFormFieldset()
	 */
	function DisplayLoginFormFieldset( & $params )
	{
		$params['Form']->info_field( 'TEST plugin', 'This is added by the TEST plugin.' );
	}


	/**
	 * Event handler: Called when a user tries to login.
	 * @see Plugin::LoginAttempt()
	 */
	function LoginAttempt()
	{
		// $this->msg( 'NO LOGIN!', 'login_error' );
		$this->msg( 'This the TEST plugin responding to the LoginAttempt event.', 'note' );
	}


	/**
	 * Event handler: Do we need a raw password in {@link LoginAttempt()}?
	 * @see Plugin::LoginAttemptNeedsRawPassword()
	 */
	function LoginAttemptNeedsRawPassword()
	{
		return false;	// No we don't need raw. (do not implement this method if the answer is no)
	}


	/**
	 * Automagically login every user as "demouser" who is not logged in and does not
	 * try to currently.
	 *
	 * To enable/test it, change the "if-0" check below to "if( 1 )".
	 *
	 * @see Plugin::AlternateAuthentication()
	 */
	function AlternateAuthentication()
	{
		if( 0 ) // you should only enable it for test purposes, because it automagically logs every user in as "demouser"!
		{
			global $Session, $Messages;

			$UserCache = & get_Cache( 'UserCache' );
			if( $demo_User = & $UserCache->get_by_login('demouser') )
			{ // demouser exists:
				$Session->set_User( $demo_User );
				$Messages->add( 'Logged in as demouser.', 'success' );
				return true;
			}
		}
	}


	/**
	 * @see Plugin::DisplayValidateAccountFormFieldset()
	 */
	function DisplayValidateAccountFormFieldset( & $params )
	{
		$params['Form']->info( 'TEST plugin', 'This is the TEST plugin responding to the ValidateAccountFormSent event.' );
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

}


/*
 * $Log$
 * Revision 1.65  2007/01/24 00:48:58  fplanque
 * Refactoring
 *
 * Revision 1.64  2007/01/20 23:48:10  blueyed
 * Changed plugin default URL to manual.b2evolution.net/classname_plugin
 *
 * Revision 1.63  2006/12/26 00:08:01  fplanque
 * reduce strain on translators. plugin devs need to understand english anyway.
 *
 * Revision 1.62  2006/12/22 22:29:35  blueyed
 * Support for "multiple" attribute in SELECT elements, especially for GetDefault(User)Settings plugin callback
 *
 * Revision 1.61  2006/12/10 12:42:40  blueyed
 * "maxlength" handling for textarea fields through javascript
 *
 * Revision 1.60  2006/12/06 23:32:35  fplanque
 * Rollback to Daniel's most reliable password hashing design. (which is not the last one)
 * This not only strengthens the login by providing less failure points, it also:
 * - Fixes the login in IE7
 * - Removes the double "do you want to memorize this password' in FF.
 *
 * Revision 1.59  2006/12/05 01:57:32  blueyed
 * Added more settings
 *
 * Revision 1.57  2006/12/01 16:26:34  blueyed
 * Added AdminDisplayCommentFormFieldset hook
 *
 * Revision 1.56  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.55  2006/10/30 19:00:37  blueyed
 * Lazy-loading of Plugin (User)Settings for PHP5 through overloading
 *
 * Revision 1.54  2006/10/01 15:11:08  blueyed
 * Added DisplayItemAs* equivs to RenderItemAs*; removed DisplayItemAllFormats; clearing of pre-rendered cache, according to plugin event changes
 *
 * Revision 1.53  2006/09/30 20:53:49  blueyed
 * Added hook RenderItemAsText, removed general RenderItem
 *
 * Revision 1.52  2006/09/11 22:23:05  blueyed
 * (Re-)enabled AdminDisplayEditorButton for "simple" edit_layout, after adding appropriate doc.
 *
 * Revision 1.51  2006/08/29 16:44:47  blueyed
 * Logical fix for cron job return value.
 *
 * Revision 1.50  2006/08/28 20:16:30  blueyed
 * Added GetCronJobs/ExecCronJob Plugin hooks.
 *
 * Revision 1.49  2006/08/19 07:56:32  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.48  2006/07/10 22:53:38  blueyed
 * Grouping of plugins added, based on a patch from balupton
 *
 * Revision 1.47  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.46  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.45  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.44  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.43  2006/06/13 21:33:40  blueyed
 * Add note when updating PluginUserSettings
 *
 * Revision 1.42  2006/06/06 20:35:50  blueyed
 * Plugins can define extra events that they trigger themselves.
 *
 * Revision 1.41  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.40  2006/05/24 20:43:19  blueyed
 * Pass "Item" as param to Render* event methods.
 *
 * Revision 1.39  2006/05/22 20:35:37  blueyed
 * Passthrough some attribute of plugin settings, allowing to use JS handlers. Also fixed submitting of disabled form elements.
 *
 * Revision 1.38  2006/05/05 19:36:24  blueyed
 * New events
 *
 * Revision 1.37  2006/05/02 01:47:58  blueyed
 * Normalization
 *
 * Revision 1.36  2006/04/24 15:43:37  fplanque
 * no message
 *
 * Revision 1.35  2006/04/22 02:36:39  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.34  2006/04/21 16:53:27  blueyed
 * Bumping TODO, please comment.
 *
 * Revision 1.33  2006/04/20 22:24:08  blueyed
 * plugin hooks cleanup
 *
 * Revision 1.32  2006/04/19 22:26:25  blueyed
 * cleanup/polish
 *
 * Revision 1.31  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.30  2006/04/19 18:55:37  blueyed
 * Added login handling hook: AlternateAuthentication
 *
 * Revision 1.29  2006/04/18 17:06:14  blueyed
 * Added "disabled" to plugin (user) settings (Thanks to balupton)
 *
 * Revision 1.28  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
<?php
/**
 * This file implements the TEST plugin.
 *
 * For the most recent and complete Plugin API documentation
 * see {@link Plugin} in ../evocore/_plugin.class.php.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
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
 * @todo Replace phpdoc blocks with "@ see" tags to "Plugin :: function_name", because it would be more accurate documentation and make the source code more "straight".
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
	var $version = 'CVS $Revision$';
	var $author = 'The b2evo Group';
	var $help_url = 'http://manual.b2evolution.net/Plugins/test_plugin';

	/*
	 * These variables MAY be overriden.
	 */
	var $apply_rendering = 'opt-out';
	var $nr_of_installs = 1;


	/**
	 * Constructor.
	 *
	 * Should set name and description in a localizable fashion.
	 * NOTE FOR PLUGIN DEVELOPERS UNFAMILIAR WITH OBJECT ORIENTED DEV:
	 * This function has the same name as the class, this makes it a "constructor".
	 * This means that this function will be called automagically by PHP when this
	 * plugin class is instantiated ("loaded").
	 */
	function test_plugin()
	{
		$this->short_desc = T_('Test plugin');
		$this->long_desc = T_('This plugin responds to virtually all possible plugin events :P');
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
	function GetDefaultSettings()
	{
		return array(
			'click_me' => array(
				'label' => T_('Click me!'),
				'defaultvalue' => '1',
				'type' => 'checkbox',
				),
			'input_me' => array(
				'label' => T_('How are you?'),
				'defaultvalue' => '',
				'note' => T_('Welcome to b2evolution'),
				),
			'my_select' => array(
				'label' => T_('Selector'),
				'defaultvalue' => 'one',
				'type' => 'select',
				'options' => array( 'sun' => T_('Sunday'), 'mon' => T_('Monday') ),
				),
			'a_disabled_one' => array(
				'label' => 'This one is disabled',
				'type' => 'checkbox',
				'defaultvalue' => '1',
				'disabled' => true, // this can be useful if you detect that something cannot be changed. You probably want to add a 'note' then, too.
				),
			);
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
					'label' => T_('Echo a random number in AdminBeginPayload event'),
					'type' => 'checkbox',
					'defaultvalue' => '0',
				),
				'deactivate' => array(
					'label' => T_('Deactivate'),
					'type' => 'checkbox',
					'defaultvalue' => '0',
				),
			);
	}


	/**
	 * Deactive the plugin for the current request if the user wants it so.
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
	 * Event handlers:
	 */

	/**
	 * Event handler: Called when ending the admin html head section.
	 *
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
	 * @param array Associative array of parameters
	 * @return boolean did we display ?
	 */
	function AdminDisplayEditorButton( & $params )
	{
		?>
		<input type="button" value="TEST" onclick="alert('Hi! This is the TEST plugin (AdminDisplayEditorButton)!');" />
		<?php
		return true;
	}


	/**
	 * Event handler: Gets invoked in /admin/_header.php for every backoffice page after
	 *                the menu structure is build. You can use the {@link $AdminUI} object
	 *                to modify it.
	 *
	 * This is the hook to register menu entries. See {@link register_menu_entry()}.
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
	 * @param array Associative array of parameters
	 */
	function AdminToolAction( $params )
	{
		global $Messages;

		$Messages->add( 'Hello, This is the AdminToolAction for the TEST plugin.' );
	}


	/**
	 * Event handler: Called when displaying the block in the "Tools" menu.
	 *
	 * @param array Associative array of parameters
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
	 */
	function AdminTabAction()
	{
		$this->text_from_AdminTabAction = '<p>This is text from AdminTabAction for the TEST plugin.</p>'
			.'<p>Here is a random number: '.rand( 0, 10000 ).'</p>';

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
		$Form = new Form();
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
		echo '<div class="panelblock center">TEST plugin: AdminBeginPayload event.</div>';

		if( $this->UserSettings->get('echo_random') )
		{
			echo '<div class="panelblock center">TEST plugin: A random number requested by user setting: '.rand(0, 1000).'</div>';
		}
	}


	/**
	 * Event handler: Called when rendering item/post contents as HTML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 * @return boolean true if we can render something for the required output format
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
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'xml' will arrive here.
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsXml( & $params )
	{
		// Do the same as with HTML:
		$this->RenderItemAsHtml( $params );
	}


	/**
	 * Event handler: Called when rendering item/post contents other than XML or HTML.
	 *
	 * Note: return value is ignored. You have to change $params['content'].
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}.
	 *               Only formats other than 'htmlbody', 'entityencoded' and 'xml' will arrive here.
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItem()
	{
		// Do nothing.
	}


	/**
	 * Wrap a to be displayed IP address.
	 */
	function DisplayIpAddress( & $params )
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
	 * @see Plugin::AppendUserRegistrTransact()
	 */
	function AppendUserRegistrTransact( $params )
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
		$this->msg( 'The TEST plugin welcomes you..', 'note' );
	}

}


/*
 * $Log$
 * Revision 1.29  2006/04/18 17:06:14  blueyed
 * Added "disabled" to plugin (user) settings (Thanks to balupton)
 *
 * Revision 1.28  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
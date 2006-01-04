<?php
/**
 * This file implements the TEST plugin.
 *
 * For the most recent API documentation see {@link Plugin} in ../evocore/_plugin.class.php.
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
	var $help_url = 'http://b2evolution.net/';

	/*
	 * These variables MAY be overriden.
	 */
	var $apply_when = 'opt-out';
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
		$this->param_text = '<p>This is text from AdminTabAction for the TEST plugin.</p>'
			.'<p>Here is a random number: '.rand( 0, 10000 ).'</p>';
	}


	/**
	 * Event handler: Gets invoked when our tab is selected and should get displayed.
	 */
	function AdminTabPayload()
	{
		echo 'Hello, this is the AdminTabPayload for the TEST plugin.';

		echo $this->param_text;
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
	 * Event handler: Called when the plugin is going to be installed.
	 * @see Plugin::Install
	 */
	function Install()
	{
		global $Plugins;
		$this->msg( 'TEST plugin sucessfully installed. All the hard work we did was adding this message.. ;)' );
		return true;
	}


	/**
	 * Event handler: Called when the plugin is going to be un-installed.
	 * @see Plugin::Install
	 */
	function Uninstall()
	{
		$this->msg( 'TEST plugin sucessfully un-installed. All the hard work we did was adding this message.. ;)' );
		return true;
	}


	/**
	 * Event handler: called when a new user has registered.
	 * @see Plugin::Registration
	 */
	function Registration( $params )
	{
		$this->msg( 'The TEST plugin welcomes the new user '.$params['User']->dget('login').'!' );
	}

}
?>
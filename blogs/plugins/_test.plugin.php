<?php
/**
 * This file implements the TEST plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


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
	/**
	 * Variables below MAY be overriden.
	 */
	var $is_tool = true;
	var $apply_when = 'opt-out';
	var $apply_to_html = true;
	var $apply_to_xml = true;


	/**
	 * Constructor.
	 *
	 * Should set name and description in a localizable fashion.
	 * NOTE FOR PLUGIN DEVELOPPERS UNFAMILIAR WITH OBJECT ORIENTED DEV:
	 * This function has the same name as the class, this makes it a "constructor".
	 * This means that this function will be called automagically by PHP when this
	 * plugin class is instanciated ("loaded").
	 *
	 * {@internal test_plugin::test_plugin(-)}}
	 */
	function test_plugin()
	{
		$this->short_desc = T_('Test plugin');
		$this->long_desc = T_('This plugin responds to virtually all possible plugin events :P');
	}


	/**
	/**
	 * Event handlers:
	 */


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * {@internal test_plugin::AdminEndHtmlHead(-)}}
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
	 * {@internal test_plugin::AdminAfterPageFooter(-)}}
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
	 * {@internal test_plugin::DisplayToolbar(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayToolbar( & $params )
	{
		echo '<div class="edit_toolbar">This is the TEST Toolbar</div>';

		return true;
	}


 	/**
	 * Event handler: Called when displaying editor buttons.
	 *
	 * {@internal test_plugin::DisplayEditorButton(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display ?
	 */
	function DisplayEditorButton( & $params )
	{
	 	?>
		<input type="button" value="TEST" onclick="alert('Hi! This is the TEST plugin (DisplayEditorButton)!');" />
		<?php
		return true;
	}


	/**
	/**
	 * Event handler: Called when rendering text.
	 *
	 * Perform rendering
	 *
	 * {@internal test_plugin::Render(-)}}
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function Render( & $params )
	{
		if( ! parent::Render( $params ) )
		{	// We cannot render the required format
			return false;
		}

		$params['data'] = 'TEST['.$params['data'].']TEST';

		return true;
	}


 	/**
	 * Event handler: Called when displaying the tool menu.
	 *
	 * {@internal test_plugin::ToolMenu(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function ToolMenu( $params )
	{
	 	echo 'Hello, This is the ToolMenu for the TEST plugin.';
		return true;
	}
}
?>
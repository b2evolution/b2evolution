<?php
/**
 * This file implements the Test plugin for b2evolution
 *
 * This plugin responds to virtually all possible plugin events :P
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class test_plugin extends Plugin
{
	var $code = 'b2evTEST';
	var $name = 'Test';
	var $priority = 50;
	var $apply_when = 'opt-out';
	var $apply_to_html = true;
	var $apply_to_xml = true;
	var $short_desc;
	var $long_desc;

	/**
	 * Should be toolbar be displayed?
	 */
	var $display = true;

	/**
	 * Constructor
	 *
	 * {@internal test_plugin::test_plugin(-)}}
	 */
	function test_plugin()
	{
		$this->short_desc = T_('Test plugin');
		$this->long_desc = T_('This plugin responds to virtually all possible plugin events :P');
	}


	/**
	 * Display a toolbar
	 *
	 * {@internal test_plugin::DisplayToolbar(-)}}
	 *
   * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayToolbar( & $params )
	{
		if( !$this->display )
		{	// We don't want to show this toolbar
			return false;
		}

		echo '<div class="edit_toolbar">This is the TEST Toolbar</div>';

		return true;
	}

	/**
	 * Called when ending the admin html head section
	 *
	 * {@internal Plugin::AdminEndHtmlHead(-)}}
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
	 * Called right after displaying the admin page footer
	 *
	 * {@internal Plugin::AdminAfterPageFooter(-)}}
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
	 * Perform rendering
	 *
	 * {@internal test_plugin::render(-)}}
	 *
	 * @param string content to render (by reference) / rendered content
	 * @param string Output format, see {@link format_to_output()}
	 * @return boolean true if we can render something for the required output format
	 */
	function render( & $content, $format )
	{
		if( ! parent::render( $content, $format ) )
		{	// We cannot render the required format
			return false;
		}

	}
}
?>
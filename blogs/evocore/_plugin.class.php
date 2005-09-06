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
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
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
	 * Globally unique code for this plugin functionality. 8 chars. MUST BE SET.
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
	 * Should this plugin appear in the tools section?
	 *
	 * @var boolean
	 */
	var $is_tool = false;

	/**
	 * When should this rendering plugin apply?
	 * Possible values:
	 * - 'stealth'
	 * - 'always'
	 * - 'opt-out'
	 * - 'opt-in'
	 * - 'lazy'
	 * - 'never'
	 *
	 * @var string
	 * @todo get this outta here
	 */
	var $apply_when = 'never';	// By default, this may not be a rendering plugin

	/**
	 * Should this plugin apply to HTML?
	 *
	 * @var string
	 * @todo get this outta here
	 */
	var $apply_to_html = true;

	/**
	 * Should this plugin apply to XML?
	 * It should actually only apply when:
	 * - it generates some content that is visible without HTML tags
	 * - it removes some dirty markup when generating the tags (which will get stripped afterwards)
	 * Note: htmlentityencoded is not considered as XML here.
	 *
	 * @var string
	 * @todo get this outta here
	 */
	var $apply_to_xml = false;

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

	/**#@-*/


	/**
	 * Constructor.
	 *
	 * Should set name and description in a localizable fashion.
	 * NOTE FOR PLUGIN DEVELOPPERS UNFAMILIAR WITH OBJECT ORIENTED DEV:
	 * This function has the same name as the class, this makes it a "constructor".
	 * This means that this function will be called automagically by PHP when this
	 * plugin class is instanciated ("loaded").
	 *
	 * {@internal Plugin::Plugin(-) }}
	 */
	function Plugin()
	{
		$this->short_desc = T_('No desc available');
		$this->long_desc = T_('No description available');
	}


	/**
	 * Template function: display plugin code
	 *
	 * {@internal Plugin::code(-) }}
	 */
	function code()
	{
		echo $this->code;
	}


	/**
	 * Template function: Get displayable plugin name.
	 *
	 * {@internal Plugin::name(-) }}
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
	 * {@internal Plugin::short_desc(-) }}
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
	 * {@internal Plugin::long_desc(-) }}
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


	/**
	 * Set param value
	 *
	 * {@internal Plugin::set_param(-) }}
	 *
	 * @param string Name of parameter
	 * @param mixed Value of parameter
	 */
	function set_param( $parname, $parvalue )
	{
		// Set value:
		$this->$parname = $parvalue;
	}


	/*
	 * Event handlers {{{
	 */


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * {@internal Plugin::AdminEndHtmlHead(-)}}
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
	 * {@internal Plugin::AdminAfterPageFooter(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminAfterPageFooter( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when displaying editor toolbars.
	 *
	 * {@internal Plugin::DisplayToolbar(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayToolbar( & $params )
	{
		return false;		// Do nothing by default.
	}


 	/**
	 * Event handler: Called when displaying editor buttons.
	 *
	 * {@internal Plugin::DisplayEditorButton(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEditorButton( & $params )
	{
		return false;		// Do nothing by default.
	}


	/**
	 * Event handler: Called when doing an action. (EXPERIMENTAL)
	 *
	 * {@internal Plugin::DoAction(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean success?
	 */
	function DoAction( & $params )
	{
	 	echo T_('No such action!');
		return false;		// Action failed!
	}


	/**
	 * Event handler: Called when rendering text.
	 *
	 * Well, this one does nothing but checking if Rendering applies to the output format.
	 * You need to derive this function.
	 *
	 * {@internal Plugin::Render(-)}}
	 *
	 * @param array Associative array of parameters (by reference!)
	 *              (Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function Render( & $params )
	{
		switch( $params['format'] )
		{
			case 'raw':
				// do nothing!
				return false;

			case 'htmlbody':
				// display in HTML page body: allow full HTML
				return $this->apply_to_html;

			case 'entityencoded':
				// Special mode for RSS 0.92: apply renders and allow full HTML but escape it
				return $this->apply_to_html;

			case 'htmlhead':
				// strips out HTML (mainly for use in Title)
			case 'htmlattr':
				// use as an attribute: strips tags and escapes quotes
			case 'formvalue':
				// use as a form value: escapes quotes and < > but leaves code alone
				return false;

			case 'xml':
				// use in an XML file: strip HTML tags
				return $this->apply_to_xml;

			case 'xmlattr':
				// use as an attribute: strips tags and escapes quotes
				return false;

			default:
				die( 'Output format ['.$format.'] not supported by Plugins.' );
		}
	}


 	/**
	 * Event handler: Called when displaying the tool menu.
	 *
	 * {@internal Plugin::ToolMenu(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function ToolMenu( & $params )
	{
		return false;		// Do nothing by default.
	}

	/*
	 * Event handlers }}}
	 */

}

/*
 * $Log$
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
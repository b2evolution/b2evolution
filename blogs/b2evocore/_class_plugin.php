<?php
/**
 * This file implements the Plugin class (EXPERIMENTAL)
 *
 * This is the base class from which all plugins should be derived.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Plugin Class
 *
 * @package plugins
 * @abstract
 */
class Plugin
{
	/**
	 * Name of the current class.
	 *
	 * Will be set automatically (from filename) when registering plugin.
	 *
	 * @global string
	 */
	var $classname;

	/**
	 * Internal (DB) ID
	 *
	 * @global int
	 */
	var $ID = 0;		// 0 means 'NOT installed'

	var $code = '';
	var $priority = 50;
	var $name = 'Unnamed plug-in';
	var $version;
	var $author;
	var $help_url;
	var $short_desc;
	var $long_desc;


	/**
	 * Should be toolbar be displayed?
	 * @todo get this outta here
	 */
	var $display = true;

  /**
	 * When should this rendering plugin apply?
	 * Possible values:
	 * - 'stealth'
	 * - 'always'
	 * - 'opt-out'
	 * - 'opt-in'
	 * - 'lazy'
	 * - 'never'
	 * @todo get this outta here
	 */
	var $apply_when = 'never';	// By default, this may not be a rendering plugin
	/**
	 * Should this plugin apply to HTML?
	 * @todo get this outta here
	 */
	var $apply_to_html = true;
	/**
	 * Should this plugin apply to XML?
	 * It should actually only apply when:
	 * - it generates some content that is visible without HTML tags
	 * - it removes some dirty markup when generating the tags (which will get stripped afterwards)
	 * Note: htmlentityencoded is not considered as XML here.
	 * @todo get this outta here
	 */
	var $apply_to_xml = false;



	/**
	 * Constructor, should set name and description
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
	 * Template function: display plugin name
	 *
	 * {@internal Plugin::name(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function name( $format = 'htmlbody' )
	{
		echo format_to_output( $this->name, $format );
	}


	/**
	 * Template function: display short description for plug in
	 *
	 * {@internal Plugin::short_desc(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function short_desc( $format = 'htmlbody' )
	{
		echo format_to_output( $this->short_desc, $format );
	}


	/**
	 * Template function: display long description for plug in
	 *
	 * {@internal Plugin::long_desc(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function long_desc( $format = 'htmlbody' )
	{
		echo format_to_output( $this->long_desc, $format );
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


  /**
	 * Register event callbacks
	 *
	 * This method is called by b2evo to ask the plugin what events it would
	 * like to receive notifications for.
	 *
	 * {@internal Plugin::RegisterEvents(-)}}
	 *
	 * @return array List of event names we wish to be called back for
	 */
/*	function RegisterEvents()
	{
		return array();		// None by default.
	} */


	/**
	 * Display a toolbar
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
	 * Display an editor button
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
	 * Called when ending the admin html head section
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
	 * Called right after displaying the admin page footer
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
	 * Do an action
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
	 * Perform rendering
	 *
	 * Well, this one does nothing but checking if Rendering applies to the output format.
	 * You need to derive this function.
	 *
	 * {@internal Rendererplugin::render(-)}}
	 *
	 * @param string content to render (by reference) / rendered content
	 * @param string Output format, see {@link format_to_output()}
	 * @return boolean true if we can render something for the required output format
	 */
	function render( & $content, $format )
	{
		switch( $format )
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
				die( 'Output format ['.$format.'] not supported by RendererPlugin.' );
		}
	}

}
?>
<?php
/**
 * This file implements the Plugin class (EXPERIMENTAL)
 *
 * This is the base class from which all plugins classes are derived.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */

/**
 * Plugin Class
 *
 * @abstract
 */
class Plugin
{
	/**#@+
	 * Should be overriden by derived class:
	 */
	var $code = '';
	var $priority = 50;
	var $name = 'Unnamed plug-in';
	var $version;
	var $author;
	var $help_url;
	var $short_desc;
	var $long_desc;

	/**#@-*/

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

}
?>

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
	var $name = 'Unnamed plug-in';
	var $short_desc = 'No desc available';
	var $long_desc = 'No description available';

	/**#@-*/

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
	
}
?>

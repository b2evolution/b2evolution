<?php
/**
 * This file implements the Automatic Links plugin for b2evolution
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
class autolinks_plugin extends Plugin
{
	var $code = 'b2evALnk';
	var $name = 'Auto Links';
	var $priority = 60;

	var $apply_when = 'opt-out';
	var $apply_to_html = true; 
	var $apply_to_xml = false; 
	var $short_desc;
	var $long_desc;


	/**
	 * Constructor
	 *
	 * {@internal autolinks_plugin::autolinks_plugin(-)}}
	 */
	function autolinks_plugin()
	{
		$this->short_desc = T_('Make URLs clickable');
		$this->long_desc = T_('No description available');
	}


	/**
	 * Perform rendering
	 *
	 * {@internal autolinks_plugin::render(-)}}
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
	
		$content = make_clickable( $content );
		
		return true;
	}
}
?>
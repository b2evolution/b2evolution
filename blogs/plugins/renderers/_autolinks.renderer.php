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
require_once dirname(__FILE__).'/../renderer.class.php';

class autolinks_Rendererplugin extends RendererPlugin
{
	var $code = 'b2evALnk';
	var $priority = 60;
	var $apply_when = 'opt-out';
	var $apply_to_html = true;
	var $apply_to_xml = false;


	/**
	 * Constructor
	 *
	 * {@internal autolinks_Rendererplugin::autolinks_Rendererplugin(-)}}
	 */
	function autolinks_Rendererplugin()
	{
		$this->name = T_('Auto Links');
		$this->short_desc = T_('Make URLs clickable');
		$this->long_desc = T_('No description available');
	}


	/**
	 * Perform rendering
	 *
	 * {@internal autolinks_Rendererplugin::render(-)}}
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

// Register the plugin:
$this->register( new autolinks_Rendererplugin() );

?>
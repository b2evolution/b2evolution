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
	var $name = 'Auto Links';
	var $short_desc = 'Make URLs clickable';
	var $long_desc = 'No description available';

	/**
	 * Perform rendering
	 *
	 * {@internal autolinks_Rendererplugin::render(-)}} 
	 */
	function render( & $content )
	{
		$content = make_clickable( $content );
	}
}

// Register the plugin:
$this->register( new autolinks_Rendererplugin() );

?>
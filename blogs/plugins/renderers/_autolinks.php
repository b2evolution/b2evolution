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
$plugin_code = 'b2evALnk';

class autolinks_Rendererplugin
{
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
$this->Plugins[$plugin_code] = & new autolinks_Rendererplugin();

?>

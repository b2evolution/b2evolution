<?php
/**
 * This file implements the renderer (EXPERIMENTAL)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */

/**
 * Renderer Class
 */
class Renderer
{
	var $Plugins = array();
	
	/* 
	 * Constructor
	 *
	 * {@internal Renderer::Renderer(-)}}
	 *
	 */
	function Renderer()
	{
		global $core_dirout, $plugins_subdir;
		$plugins_path = dirname(__FILE__).'/'.$core_dirout.'/'.$plugins_subdir.'/renderers';
		 
		require_once $plugins_path.'/_textile.php';
		require_once $plugins_path.'/_auto_p.php';
		require_once $plugins_path.'/_gmcode.php';
		require_once $plugins_path.'/_bbcode.php';
		require_once $plugins_path.'/_autolinks.php';
		require_once $plugins_path.'/_smilies.php';
		require_once $plugins_path.'/_texturize.php';
	}	
	
	/* 
	 * Render the content
	 *
	 * {@internal Renderer::render(-)}}
	 *
	 * @param string content to render
	 * @param string mode 'content' or 'other'
	 * @return string rendered content
	 */
	function render( $content, $mode = 'content' )
	{
		global $use_textile;
		
		switch( $mode )
		{
			case 'content':
				$this->Plugins['b2DATxtl']->render( $content );
				$this->Plugins['b2WPAutP']->render( $content );
				$this->Plugins['b2evGMco']->render( $content );
				$this->Plugins['b2evBBco']->render( $content );
				$this->Plugins['b2evALnk']->render( $content );
				$this->Plugins['b2evSmil']->render( $content );
				$this->Plugins['b2WPTxrz']->render( $content );
				break;
				
			case 'other':
				$this->Plugins['b2evGMco']->render( $content );
				$this->Plugins['b2evSmil']->render( $content );
				$this->Plugins['b2WPTxrz']->render( $content );
				break;

			default:
				die( 'Rendering mode ['.$mode.'] not supported.' );
		}
		return $content; 
	}	
	
}
?>

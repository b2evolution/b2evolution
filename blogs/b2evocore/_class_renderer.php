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
		global $core_dirout, $plugins_subdir, $use_textile;
		$plugins_path = dirname(__FILE__).'/'.$core_dirout.'/'.$plugins_subdir.'/renderers';
		 
		if( $use_textile ) require_once( dirname(__FILE__). '/_functions_textile.php' );
		require_once $plugins_path.'/_gmcode.php';
		require_once $plugins_path.'/_bbcode.php';
		require_once $plugins_path.'/_autolinks.php';
		require_once $plugins_path.'/_smilies.php';
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
				//if( $use_textile ) $comment = textile( $comment );
				$this->Plugins['b2evGMco']->render( $content );
				$this->Plugins['b2evBBco']->render( $content );
				$this->Plugins['b2evALnk']->render( $content );
				$this->Plugins['b2evSmil']->render( $content );
				/* autolinks($content);
				phpcurlme( $content ); */
				break;
				
			case 'other':
				// if( $use_textile ) $comment = textile( $comment );
				$this->Plugins['b2evGMco']->render( $content );
				$this->Plugins['b2evSmil']->render( $content );
				// phpcurlme( $content );
				break;

			default:
				die( 'Rendering mode ['.$mode.'] not supported.' );
		}
		return $content; 
	}	
	
}
?>

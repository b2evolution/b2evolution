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
require_once dirname(__FILE__).'/_class_plug.php';

/**
 * Renderer Class
 */
class Renderer extends Plug
{
	/* 
	 * Constructor
	 *
	 * {@internal Renderer::Renderer(-)}}
	 *
	 */
	function Renderer()
	{
		// Call parent constructor:
		parent::Plug( 'renderer' );
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
		$this->init();	// Init if not done yet.
		
		switch( $mode )
		{
			case 'content':
				$this->index_Plugins['b2DATxtl']->render( $content );
				$this->index_Plugins['b2WPAutP']->render( $content );
				$this->index_Plugins['b2evGMco']->render( $content );
				$this->index_Plugins['b2evBBco']->render( $content );
				$this->index_Plugins['b2evALnk']->render( $content );
				$this->index_Plugins['b2evSmil']->render( $content );
				$this->index_Plugins['b2WPTxrz']->render( $content );
				break;
				
			case 'other':
				$this->index_Plugins['b2evGMco']->render( $content );
				$this->index_Plugins['b2evSmil']->render( $content );
				$this->index_Plugins['b2WPTxrz']->render( $content );
				break;

			default:
				die( 'Rendering mode ['.$mode.'] not supported.' );
		}
		return $content; 
	}	
	
}
?>

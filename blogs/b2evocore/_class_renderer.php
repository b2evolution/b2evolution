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
	 * @param array renderer codes
	 * @return string rendered content
	 */
	function render( & $content, & $renderers )
	{
		$this->init();	// Init if not done yet.
		
		$this->restart(); // Just in case.
		
		while( $loop_RendererPlugin = $this->get_next() )
		{ // Go through whole list of renders
			// echo ' ',$loop_RendererPlugin->code;

			switch( $loop_RendererPlugin->apply )
			{
				 case 'stealth':
				 case 'always':
					// echo 'FORCED';
					$loop_RendererPlugin->render( $content );
					break;
				 
				 case 'opt-out':
				 case 'opt-in':
				 case 'lazy':
					if( in_array( $loop_RendererPlugin->code, $renderers ) )
					{	// Option is activated
						// echo 'OPT';
						$loop_RendererPlugin->render( $content );
					}
					// echo 'NO';
					break;
									 
				 case 'never':
					// echo 'NEVER';
					continue;	// STOP, don't render, go to next renderer
			}		
		}

		return $content; 
	}	
	
}
?>

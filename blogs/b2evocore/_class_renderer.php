<?php
/**
 * This file implements the Renderer class. (EXPERIMENTAL)
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_class_plug.php';

/**
 * Renderer Class
 *
 * @package evocore
 */
class Renderer extends Plug
{
	/**
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
	
	
	/**
	 * Validate renderer list
	 *
	 * {@internal Renderer::validate_list(-)}}
	 *
	 * @param array renderer codes
	 * @return array validated array
	 */
	function validate_list( $renderers = array('default') )
	{
		$this->init();	// Init if not done yet.
		
		$this->restart(); // Just in case.
		
		$validated_renderers = array();
		
		while( $loop_RendererPlugin = $this->get_next() )
		{ // Go through whole list of renders
			// echo ' ',$loop_RendererPlugin->code;

			switch( $loop_RendererPlugin->apply_when )
			{
				case 'stealth':
				case 'always':
					// echo 'FORCED';
					$validated_renderers[] = $loop_RendererPlugin->code;
					break;
				 
				case 'opt-out':
					if( in_array( $loop_RendererPlugin->code, $renderers ) // Option is activated
						|| in_array( 'default', $renderers ) ) // OR we're asking for default renderer set
					{
						// echo 'OPT';
						$validated_renderers[] = $loop_RendererPlugin->code;
					}
					// else echo 'NO';
					break;

				case 'opt-in':
				case 'lazy':
					if( in_array( $loop_RendererPlugin->code, $renderers ) ) // Option is activated
					{
						// echo 'OPT';
						$validated_renderers[] = $loop_RendererPlugin->code;
					}
					// else echo 'NO';
					break;
									 
				case 'never':
					// echo 'NEVER';
					continue;	// STOP, don't render, go to next renderer
			}		
		}
		// echo count( $validated_renderers );
		return $validated_renderers; 
	}	


	/**
	 * Render the content
	 *
	 * {@internal Renderer::render(-)}}
	 *
	 * @param string content to render
	 * @param array renderer codes
	 * @param string Output format, see {@link format_to_output()}
	 * @return string rendered content
	 */
	function render( & $content, & $renderers, $format )
	{
		$this->init();	// Init if not done yet.
		
		$this->restart(); // Just in case.
		
		// echo implode(',',$renderers);
		
		while( $loop_RendererPlugin = $this->get_next() )
		{ // Go through whole list of renders
			//echo ' ',$loop_RendererPlugin->code, ':';

			switch( $loop_RendererPlugin->apply_when )
			{
				 case 'stealth':
				 case 'always':
					// echo 'FORCED ';
					$loop_RendererPlugin->render( $content, $format );
					break;
				 
				 case 'opt-out':
				 case 'opt-in':
				 case 'lazy':
					if( in_array( $loop_RendererPlugin->code, $renderers ) )
					{	// Option is activated
						// echo 'OPT ';
						$loop_RendererPlugin->render( $content, $format );
					}
					// else echo 'NOOPT ';
					break;
									 
				 case 'never':
					// echo 'NEVER ';
					break;	// STOP, don't render, go to next renderer
			}		
		}

		return $content;
	}


	/**
	 * quick-render a string with a single renderer
	 *
	 * @param string what to render
	 * @param string renderercode
	 * @param string format to output, see {@link format_to_output()}
	 */
	function quick( $string, $renderercode, $format )
	{
		$this->init();
		if( isset($this->index_Plugins[ $renderercode ]) )
		{
			$this->index_Plugins[ $renderercode ]->render( $string, $format );
			return $string;
		}
		else
		{
			return format_to_output( $string, $format );
		}
	}



}
?>
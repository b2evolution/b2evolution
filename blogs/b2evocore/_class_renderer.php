<?php
/**
 * This file implements the renderer (EXPERIMENTAL)
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( $use_textile ) require_once( dirname(__FILE__). '/_functions_textile.php' );
require_once dirname(__FILE__). '/../plugins/renderers/_gmcode.php';
require_once dirname(__FILE__). '/../plugins/renderers/_bbcode.php';


/**
 * Comment Class
 */
class Renderer
{
	/* 
	 * Constructor
	 *
	 * {@internal Renderer::Renderer(-)}}
	 *
	 */
	function Renderer()
	{
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
				if( $use_textile ) $comment = textile( $comment );
				convert_gmcode($content);
				// convert_bbcode($content);
				$content = make_clickable($content);
				convert_smilies($content);
				phpcurlme( $content );
				break;
				
			case 'other':
				// if( $use_textile ) $comment = textile( $comment );
				convert_gmcode($content);
				// convert_bbcode($content);
				// $content = make_clickable($content);
				convert_smilies($content);
				// phpcurlme( $content );
				break;

			default:
				die( 'Rendering mode ['.$mode.'] not supported.' );
		}
		return $content; 
	}	
	
}
?>

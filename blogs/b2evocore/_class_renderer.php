<?php
/**
 * This file implements the renderer
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */
// require_once dirname(__FILE__). '/_class_dataobject.php';
if( $use_textile ) require_once( dirname(__FILE__). '/_functions_textile.php' );


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
				convert_bbcode($content);
				convert_gmcode($content);
				$content = make_clickable($content);
				convert_smilies($content);
				phpcurlme( $content );
				break;
				
			case 'other':
				// if( $use_textile ) $comment = textile( $comment );
				convert_bbcode($content);
				convert_gmcode($content);
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

<?php
/**
 * This file implements the Image Smilies Renderer plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../renderer.class.php';

/**
 * @package plugins
 */
class smilies_Rendererplugin extends RendererPlugin
{
	var $code = 'b2evSmil';
	var $name = 'Smilies';
	var $priority = 80;
	var $apply_when = 'always';
	var $apply_to_html = true; 
	var $apply_to_xml = false; // Leave the smilies alone
	var $short_desc;
	var $long_desc;

	/**
	 * Text similes search array
	 *
	 * @access private
	 */
	var $search;
	
	/**
	 * IMG replace array
	 *
	 * @access private
	 */
	var $replace;

	/**
	 * Smiley definitions
	 *
	 * @access private
	 */
	var $smilies;

	/**
	 * Path to images
	 *
	 * @access private
	 */
	var $smilies_path;


	/**
	 * Constructor
	 *
	 * {@internal smilies_Rendererplugin::smilies_Rendererplugin(-)}} 
	 */
	function smilies_Rendererplugin()
	{
		$this->short_desc = T_('Convert text smilies to icons');
		$this->long_desc = T_('No description available');

		require dirname(__FILE__). '/../_smilies.conf.php';
	}


	/**
	 * Perform rendering
	 *
	 * {@internal smilies_Rendererplugin::render(-)}} 
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
	
		if( ! isset( $this->search ) )
		{	// We haven't prepared the smilies yet
			$this->search = array();


			$tmpsmilies = $this->smilies;
			uksort($tmpsmilies, 'smiliescmp');
	
			foreach($tmpsmilies as $smiley => $img)
			{
				$this->search[] = $smiley;
				$smiley_masked = '';
				for ($i = 0; $i < strlen($smiley); $i++ )
				{
					$smiley_masked .=  '&#'.ord(substr($smiley, $i, 1)).';';
				}
	
				// We don't use getimagesize() here until we have a mean
				// to preprocess smilies. It takes up to much time when
				// processing them at display time.
				$this->replace[] = '<img src="'.$this->smilies_path.'/'.$img.'" alt="'.$smiley_masked.'" class="middle" />';
			}
		}


		// REPLACE:  But not in code blocks.
		if( strpos( $content , '<code>' ) !== false )
		{ // If there are code tags run this substitution
			$content_parts = preg_split("/<\/?code>/", $content);
			$content = '';
			for ( $x = 0 ; $x < count( $content_parts ) ; $x++ )
			{
				if ( ( $x % 2 ) == 0 )
				{ // If x is even then it's not code and replace any smiles
					$content .= str_replace( $this->search, $this->replace, $content_parts[$x] );
				}
				else
				{ // If x is odd don't replace smiles. and put code tags back in.
					$content .= '<code>' . $content_parts[$x] . '</code>';
				}
			}
		}
		else
		{ // No code blocks, replace on the whole thing
			$content = str_replace( $this->search, $this->replace, $content);
		}
	
		return true;
	}
}

/**
 * sorts the smilies' array by length
 * this is important if you want :)) to superseede :) for example
 */
function smiliescmp($a, $b)
{
	if(($diff = strlen($b) - strlen($a)) == 0)
	{
		return strcmp($a, $b);
	}
	return $diff;
}



// Register the plugin:
$this->register( new smilies_Rendererplugin() );

?>
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
require_once dirname(__FILE__).'/../renderer.class.php';

class smilies_Rendererplugin extends RendererPlugin
{
	var $code = 'b2evSmil';
	var $name = 'Smilies';
	var $short_desc = 'Convert text smilies to icons';
	var $long_desc = 'No description available';

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
		require dirname(__FILE__). '/../_smilies.conf.php';
	}


	/**
	 * Perform rendering
	 *
	 * {@internal smilies_Rendererplugin::render(-)}} 
	 */
	function render( & $content )
	{	
		if( ! isset( $this->search ) )
		{	// We haven't prepared the smilies yet
			$this->search = array();


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
	
				$this->replace[] = "<img src='$this->smilies_path/$img' border='0' alt='$smiley_masked' class='middle' />";
			}
		}

		// REPLACE:
		$content = str_replace( $this->search, $this->replace, $content );
	}
}

// Register the plugin:
$this->register( new smilies_Rendererplugin() );

?>
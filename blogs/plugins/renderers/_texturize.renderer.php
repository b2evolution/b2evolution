<?php
/**
 * This file implements the Texturize plugin for b2evolution
 *
 * @author WordPress team - http://sourceforge.net/project/memberlist.php?group_id=51422
 * b2evo: 1 notice fix.
 *
 * @package plugins
 */
require_once dirname(__FILE__).'/../renderer.class.php';

class texturize_Rendererplugin extends RendererPlugin
{
	var $code = 'b2WPTxrz';
	var $priority = 90;
	var $name = 'Texturize';
	var $short_desc;
	var $long_desc;
	
	var $apply_when = 'opt-in';
	var $apply_to_html = true;
	var $apply_to_xml = true;


	/**
	 * Constructor
	 *
	 * {@internal texturize_Rendererplugin::texturize_Rendererplugin(-)}}
	 */
	function texturize_Rendererplugin()
	{
		$this->short_desc = 'Smart quotes and more';
		$this->long_desc = 'No description available';
	}


	/**
	 * Perform rendering
	 *
	 * {@internal texturize_Rendererplugin::render(-)}}
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

		$output = '';
		$textarr = preg_split("/(<.*>)/Us", $content, -1, PREG_SPLIT_DELIM_CAPTURE); // capture the tags as well as in between
		$stop = count($textarr); $next = true; // loop stuff
		for ($i = 0; $i < $stop; $i++) {
			$curl = $textarr[$i];

			if (strlen($curl) && '<' != $curl{0} && $next) { // If it's not a tag
				$curl = str_replace('---', '&#8212;', $curl);
				$curl = str_replace('--', '&#8211;', $curl);
				$curl = str_replace("...", '&#8230;', $curl);
				$curl = str_replace('``', '&#8220;', $curl);

				// This is a hack, look at this more later. It works pretty well though.
				$cockney = array("'tain't","'twere","'twas","'tis","'twill","'til","'bout","'nuff","'round");
				$cockneyreplace = array("&#8217;tain&#8217;t","&#8217;twere","&#8217;twas","&#8217;tis","&#8217;twill","&#8217;til","&#8217;bout","&#8217;nuff","&#8217;round");
				$curl = str_replace($cockney, $cockneyreplace, $curl);

				$curl = preg_replace("/'s/", '&#8217;s', $curl);
				$curl = preg_replace("/'(\d\d(?:&#8217;|')?s)/", "&#8217;$1", $curl);
				$curl = preg_replace('/(\s|\A|")\'/', '$1&#8216;', $curl);
				$curl = preg_replace('/(\d+)"/', '$1&Prime;', $curl);
				$curl = preg_replace("/(\d+)'/", '$1&prime;', $curl);
				$curl = preg_replace("/(\S)'([^'\s])/", "$1&#8217;$2", $curl);
				$curl = preg_replace('/(\s|\A)"(?!\s)/', '$1&#8220;$2', $curl);
				$curl = preg_replace('/"(\s|\Z)/', '&#8221;$1', $curl);
				$curl = preg_replace("/'([\s.]|\Z)/", '&#8217;$1', $curl);
				$curl = preg_replace("/\(tm\)/i", '&#8482;', $curl);
				$curl = preg_replace("/\(c\)/i", '&#169;', $curl);
				$curl = preg_replace("/\(r\)/i", '&#174;', $curl);
				$curl = preg_replace('/&([^#])(?![a-z]{1,8};)/', '&#038;$1', $curl);
				$curl = str_replace("''", '&#8221;', $curl);

				$curl = preg_replace('/(d+)x(\d+)/', "$1&#215;$2", $curl);

			} elseif (strstr($curl, '<code') || strstr($curl, '<pre') || strstr($curl, '<kbd' || strstr($curl, '<style') || strstr($curl, '<script'))) {
				// strstr is fast
				$next = false;
			} else {
				$next = true;
			}
			$output .= $curl;
		}
		$content = $output;

		return true;
	}
}

// Register the plugin:
$this->register( new texturize_Rendererplugin() );

?>
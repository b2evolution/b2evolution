<?php
/**
 * This file implements the Texturize plugin for b2evolution
 *
 * @author WordPress team - http://sourceforge.net/project/memberlist.php?group_id=51422
 * b2evo: 1 notice fix.
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class texturize_plugin extends Plugin
{
	var $code = 'b2WPTxrz';
	var $name = 'Texturize';
	var $priority = 90;
	var $version = '1.9-dev';
	var $apply_rendering = 'opt-in';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Smart quotes + additional typographic replacements.');
		$this->long_desc = T_('This renderer will replace standard and double quotes with typographic quotes were appropriate.<br />
		 It will also perform the following replacements:
		 <ul>
		 	<li>--- to &#8212;</li>
			<li>-- to &#8211;</li>
			<li>... to &#8230;</li>
		</ul>' );
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 *   'data': the data (by reference). You probably want to modify this.
	 *   'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

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


	/**
	 * The same as for HTML.
	 *
	 * @uses RenderItemAsHtml()
	 */
	function RenderItemAsXml( & $params )
	{
		$this->RenderItemAsHtml( $params );
	}
}



/*
 * $Log$
 * Revision 1.14  2006/12/26 03:19:12  fplanque
 * assigned a few significant plugin groups
 *
 * Revision 1.13  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.12  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.11  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.10  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
<?php
/**
 * This file implements the BBcode plugin for b2evolution
 *
 * BB style formatting, like [b]bold[/b]
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

require_once dirname(__FILE__).'/../renderer.class.php';

class bbcode_Rendererplugin extends RendererPlugin
{
	var $code = 'b2evBBco';
	var $name = 'BB code';
	var $priority = 50;
	var $apply_when = 'opt-in';
	var $apply_to_html = true; 
	var $apply_to_xml = true;  // strip the BBcode
	var $short_desc;
	var $long_desc;

	/**
	 * BBcode formatting search array
	 *
	 * @access private
	 */
	var $search = array(
									'#\[b](.+?)\[/b]#is',		// Formatting tags
									'#\[i](.+?)\[/i]#is',
									'#\[u](.+?)\[/u]#is',
									'#\[s](.+?)\[/s]#is',
									'!\[color=(#?[A-Za-z0-9]+?)](.+?)\[/color]!is',
									'#\[size=([0-9]+?)](.+?)\[/size]#is',
									'#\[font=([A-Za-z0-9 ;\-]+?)](.+?)\[/font]#is',
									// The following are dangerous, until we security check resulting code.
									//	'#\[img](.+?)\[/img]#is',		// Image
									//	'#\[url](.+?)\[/url]#is',		// URL
									//	'#\[url=(.+?)](.+?)\[/url]#is',
									//	'#\[email](.+?)\[/email]#eis',		// E-mail
									//	'#\[email=(.+?)](.+?)\[/email]#eis'
									);
	
	/**
	 * HTML replace array
	 *
	 * @access private
	 */
	var $replace = array(
									'<strong>$1</strong>',		// Formatting tags
									'<em>$1</em>',
									'<span style="text-decoration:underline">$1</span>',
									'<span style="text-decoration:line-through">$1</span>',
									'<span style="color:$1">$2</span>',
									'<span style="font-size:$1px">$2</span>',
									'<span style="font-family:$1">$2</span>',
									//	'<img src="$1" alt="" />',		// Image
									//	'<a href="$1">$1</a>',		// URL
									//	'<a href="$1" title="$2">$2</a>',
									//	'<a href=\"mailto:$1\">$1</a>',		// E-mail
									//	'<a href="mailto:$1">$2</a>'
									);


	/**
	 * Constructor
	 *
	 * {@internal bbcode_Rendererplugin::bbcode_Rendererplugin(-)}}
	 */
	function bbcode_Rendererplugin()
	{
		$this->short_desc = T_('BB formatting e-g [b]bold[/b]');
		$this->long_desc = T_('No description available');
	}


	/**
	 * Perform rendering
	 *
	 * {@internal BBcode::render(-)}} 
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
	
		$content = preg_replace( $this->search, $this->replace, $content );
		
		return true;
	}
}

// Register the plugin:
$this->register( new bbcode_Rendererplugin() );

?>
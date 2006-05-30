<?php
/**
 * This file implements the Automatic Links plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class autolinks_plugin extends Plugin
{
	var $code = 'b2evALnk';
	var $name = 'Auto Links';
	var $priority = 60;
	var $version = 'CVS $Revision$';
	var $apply_rendering = 'opt-out';
	var $short_desc;
	var $long_desc;


	/**
	 * Constructor
	 */
	function autolinks_plugin()
	{
		$this->short_desc = T_('Make URLs clickable');
		$this->long_desc = T_('This renderer will detect URLs in the text and automatically transform them into clickable links.');
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = make_clickable( $content );

		return true;
	}
}


/*
 * $Log$
 * Revision 1.9  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.8  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
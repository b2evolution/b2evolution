<?php
/**
 * This file implements the toolbars (EXPERIMENTAL)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */

/**
 * Toolbars Class
 */
class Toolbars
{
	var $Plugins = array();
	
	/* 
	 * Constructor
	 *
	 * {@internal Toolbars::Toolbars(-)}}
	 *
	 */
	function Toolbars()
	{
		global $core_dirout, $plugins_subdir, $use_textile;
		$plugins_path = dirname(__FILE__).'/'.$core_dirout.'/'.$plugins_subdir.'/toolbars';
		 
		require_once $plugins_path.'/_smilies.php';
	}	
	
	/* 
	 * Display the toolbars
	 *
	 * {@internal Toolbars::render(-)}}
	 */
	function display()
	{
		$this->Plugins['b2evSmil']->display();
	}	
	
}
?>

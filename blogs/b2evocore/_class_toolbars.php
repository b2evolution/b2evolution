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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__). '/_class_plug.php';

/**
 * Toolbars Class
 *
 * @package evocore
 */
class Toolbars extends Plug
{
	/* 
	 * Constructor
	 *
	 * {@internal Toolbars::Toolbars(-)}}
	 *
	 */
	function Toolbars()
	{
		// Call parent constructor:
		parent::Plug( 'toolbar' );
	}	
	
	/* 
	 * Display the toolbars
	 *
	 * {@internal Toolbars::render(-)}}
	 */
	function display()
	{
		$this->init();	// Init if not done yet.

		$this->index_Plugins['b2evQTag']->display();
		$this->index_Plugins['b2evSmil']->display();
	}	
	
}
?>

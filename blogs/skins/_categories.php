<?php
	/**
	 * This is the template that displays (recursive) list of (sub)categories.
	 *
	 * It calls a plugin by the 'evo_Cats' code, which you may do also directly in your skin.
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 */
	if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	# You can customize the following as you wish:
	if(!isset($cat_all)) $cat_all = /* TRANS: All categories, skin's categories list */ T_('All');	// Set to empty to hide
	# global category list delimiters:
	if(!isset($cat_main_start)) $cat_main_start = '<ul>';
	if(!isset($cat_main_end)) $cat_main_end = '</ul>';
	# Category delimiters:
	if(!isset($cat_line_start)) $cat_line_start = '<li>';
	if(!isset($cat_line_end)) $cat_line_end = '</li>';
	if(!isset($cat_line_checkbox)) $cat_line_checkbox = true;
	# Category group delimiters:
	if(!isset($cat_group_start)) $cat_group_start = '<ul>';
	if(!isset($cat_group_end)) $cat_group_end = '</ul>';
	# When multiple blogs are listed on same page:
	if(!isset($cat_blog_start)) $cat_blog_start = '<h4>';
	if(!isset($cat_blog_end)) $cat_blog_end = '</h4>';

	// -------------------------- CATEGORIES INCLUDED HERE -----------------------------
	// Call the Categories plugin:
	$Plugins->call_by_code( 'evo_Cats', array(	// Add parameters below:
			'block_start'=>'<div class="bSideItem">',
			'block_end'=>'</div>',
			'title'=>'', // e.g.: '<h3>'.T_('Categories').'</h3>'
			'list_start'=>$cat_main_start,
			'list_end'=>$cat_main_end,
			'line_start'=>$cat_line_start,
			'line_end'=>$cat_line_end,
			'form'=>$cat_line_checkbox,
			'group_start'=>$cat_group_start,
			'group_end'=>$cat_group_end,
			'coll_start'=>$cat_blog_start,
			'coll_end'=>$cat_blog_end,
		) );
	// -------------------------------- END OF CATEGORIES ----------------------------------

?>
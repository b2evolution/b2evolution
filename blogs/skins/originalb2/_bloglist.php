<?php
	/**
	 * This is the template that displays the links to the available blogs
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 * @subpackage originalb2
	 */
	if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	if( ! $display_blog_list )
	{	// We do *not* want the blog list to be displayed
		return;
	}

	# this is what will start and end your blog links
	if(!isset($blog_list_start)) $blog_list_start = '';
	if(!isset($blog_list_end)) $blog_list_end = '';
	# this is what will separate your blog links
	if(!isset($blog_item_start)) $blog_item_start = ' ';
	if(!isset($blog_item_end)) $blog_item_end = ' ';
	# This is the class of for the selected blog link:
	if(!isset($blog_selected_link_class)) $blog_selected_link_class = 'BlogButtonCurr';
	# This is the class of for the other blog links:
	if(!isset($blog_other_link_class)) $blog_other_link_class = 'BlogButton';
	# This is additionnal markup before and after the selected blog name
	if(!isset($blog_selected_name_before)) $blog_selected_name_before = '';
	if(!isset($blog_selected_name_after)) $blog_selected_name_after = '';
	# This is additionnal markup before and after the other blog names
	if(!isset($blog_other_name_before)) $blog_other_name_before = '';
	if(!isset($blog_other_name_after)) $blog_other_name_after = '';
	# This is the blogparam that will be displayed as the name:
	if(!isset($blog_name_param)) $blog_name_param = 'name';
	# This is the blogparam that will be displayed as the link title:
	if(!isset($blog_title_param)) $blog_title_param = 'shortdesc';

	/**
	 * We now call the default bloglist handler...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 */
	require get_path('skins').'_bloglist.php';

?>
<?php
	/*
	 * This is the template that displays the links to the available blogs
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");

	if( ! $display_blog_list )
	{	// We do *not* want the blog list to be displayed
		return;
	}

	# this is what will start and end your blog links
	if(!isset($blog_list_start)) $blog_list_start = '<div class="NavBar">';				
	if(!isset($blog_list_end)) $blog_list_end = '</div>';				
	# this is what will separate your blog links
	if(!isset($blog_item_start)) $blog_item_start = '';				
	if(!isset($blog_item_end)) $blog_item_end = '';
	# This is the class of for the selected blog link:
	if(!isset($blog_selected_link_class)) $blog_selected_link_class = 'NavButton2Curr';
	# This is the class of for the other blog links:
	if(!isset($blog_other_link_class)) $blog_other_link_class = 'NavButton2';
	# This is additionnal markup before and after the selected blog name
	if(!isset($blog_selected_name_before)) $blog_selected_name_before = '<span class="small"><img src="'. $baseurl.'/img/down_small.gif" width="14" height="12" border="0" alt="['.T_('Selected').']" title="" class="top" />';				
	if(!isset($blog_selected_name_after)) $blog_selected_name_after = '</span>';
	# This is additionnal markup before and after the other blog names
	if(!isset($blog_other_name_before)) $blog_other_name_before = '<span class="small">';				
	if(!isset($blog_other_name_after)) $blog_other_name_after = '</span>';
	# This is the blogparam that will be displayed as the name:
	if(!isset($blog_name_param)) $blog_name_param = 'shortname';
	# This is the blogparam that will be displayed as the link title:
	if(!isset($blog_title_param)) $blog_title_param = 'name';
	
	/*
	 * We now call the default bloglist handler...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 */
	require dirname(__FILE__).'/../../_bloglist.php';

?>
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

	?>
<div class="sidetitle" align="center"><?php echo T_('blogs') ?></div>
<div class="sidebody">
	<?php

	# this is what will start and end your blog links
	if(!isset($blog_list_start)) $blog_list_start = '';				
	if(!isset($blog_list_end)) $blog_list_end = '';				
	# this is what will separate your blog links
	if(!isset($blog_item_start)) $blog_item_start = '';				
	if(!isset($blog_item_end)) $blog_item_end = '<br />';
	# This is the class of for the selected blog link:
	if(!isset($blog_selected_link_class)) $blog_selected_link_class = '';
	# This is the class of for the other blog links:
	if(!isset($blog_other_link_class)) $blog_other_link_class = '';
	# This is additionnal markup before and after the selected blog name
	if(!isset($blog_selected_name_before)) $blog_selected_name_before = '<strong>';				
	if(!isset($blog_selected_name_after)) $blog_selected_name_after = '</strong>';
	# This is additionnal markup before and after the other blog names
	if(!isset($blog_other_name_before)) $blog_other_name_before = '';				
	if(!isset($blog_other_name_after)) $blog_other_name_after = '';
	# This is the blogparam that will be displayed as the name:
	if(!isset($blog_name_param)) $blog_name_param = 'name';
	# This is the blogparam that will be displayed as the link title:
	if(!isset($blog_title_param)) $blog_title_param = 'shortdesc';
	
	/*
	 * We now call the default bloglist handler...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 */
	require get_path('skins').'/_bloglist.php';

?>
</div>

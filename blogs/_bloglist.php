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
	if(!isset($blog_list_start)) $blog_list_start = '<ul>';				
	if(!isset($blog_list_end)) $blog_list_end = '</ul>';				
	# this is what will separate your blog links
	if(!isset($blog_item_start)) $blog_item_start = '<li>';				
	if(!isset($blog_item_end)) $blog_item_end = '</li>';
	# This is the class of for the selected blog link:
	if(!isset($blog_selected_link_class)) $blog_selected_link_class = '';
	# This is the class of for the other blog links:
	if(!isset($blog_other_link_class)) $blog_other_link_class = '';
	# This is additionnal markup before and after the selected blog name
	if(!isset($blog_selected_name_before)) $blog_selected_name_before = '[';				
	if(!isset($blog_selected_name_after)) $blog_selected_name_after = ']';
	# This is additionnal markup before and after the other blog names
	if(!isset($blog_other_name_before)) $blog_other_name_before = '';				
	if(!isset($blog_other_name_after)) $blog_other_name_after = '';
	# This is the blogparam that will be displayed as the name:
	if(!isset($blog_name_param)) $blog_name_param = 'shortname';
	# This is the blogparam that will be displayed as the link title:
	if(!isset($blog_title_param)) $blog_title_param = 'name';
	
					
	echo $blog_list_start;
	for( $curr_blog_ID=blog_list_start('stub'); 
				$curr_blog_ID!=false; 
				 $curr_blog_ID=blog_list_next('stub') ) 
	{ # by uncommenting the following lines you can hide some blogs
		// if( $curr_blog_ID == 1 ) continue; // Hide blog 1...
		// if( $curr_blog_ID == 2 ) continue; // Hide blog 2...
		echo $blog_item_start;
		if( $curr_blog_ID == $blog ) 
		{ // This is the blog being displayed on this page:
			echo '<a href="';
			blog_list_iteminfo('blogurl', 'raw');
			echo '" class="', $blog_selected_link_class, '" title="';
			blog_list_iteminfo($blog_title_param, 'htmlheader');
			echo '">';
			echo $blog_selected_name_before;
			blog_list_iteminfo($blog_name_param, 'htmlbody');
			echo $blog_selected_name_after;
			echo '</a>';
		}
		else
		{ // This is another blog:
			echo '<a href="';
			blog_list_iteminfo('blogurl', 'raw');
			echo '" class="', $blog_other_link_class, '" title="';
			blog_list_iteminfo($blog_title_param, 'htmlheader');
			echo '">';
			echo $blog_other_name_before;
			blog_list_iteminfo($blog_name_param, 'htmlbody');
			echo $blog_other_name_after;
			echo '</a>';
		} // End of testing which blog is being displayed 
		echo $blog_item_end;
	}
	echo $blog_list_end;


?>
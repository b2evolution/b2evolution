<?php
	/**
	 * This is the template that displays the links to the available blogs
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

if( ! $display_blog_list )
{ // We do *not* want the blog list to be displayed
	return;
}

# this is what will start and end your blog links
if(!isset($blog_list_start)) $blog_list_start = '<ul>';
if(!isset($blog_list_end)) $blog_list_end = '</ul>';
# This is what will separate items in the list
if(!isset($blog_list_separator)) $blog_list_separator = '';
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


$blog_links = array(); // we collect all links first, to easily implode them

for( $curr_blog_ID = blog_list_start();
			$curr_blog_ID != false;
			 $curr_blog_ID = blog_list_next() )
{
	if( !blog_list_iteminfo( 'in_bloglist', false ) )
	{ // don't show
		continue;
	}
	$blog_link = $blog_item_start;
	if( $curr_blog_ID == $blog )
	{ // This is the blog being displayed on this page:
		$blog_link .= '<a href="';
		$blog_link .= blog_list_iteminfo('blogurl', false);
		$blog_link .= '" class="'.$blog_selected_link_class.'" title="';
		$blog_link .= format_to_output( blog_list_iteminfo($blog_title_param, false), 'htmlattr' );
		$blog_link .= '">';
		$blog_link .= $blog_selected_name_before;
		$blog_link .= format_to_output( blog_list_iteminfo($blog_name_param, false ), 'htmlbody' );
		$blog_link .= $blog_selected_name_after;
		$blog_link .= '</a>';
	}
	else
	{ // This is another blog:
		$blog_link .= '<a href="';
		$blog_link .= blog_list_iteminfo('blogurl', false);
		$blog_link .= '" class="'.$blog_other_link_class.'" title="';
		$blog_link .= format_to_output( blog_list_iteminfo($blog_title_param, false), 'htmlattr' );
		$blog_link .= '">';
		$blog_link .= $blog_other_name_before;
		$blog_link .= format_to_output( blog_list_iteminfo($blog_name_param, false), 'htmlbody' );
		$blog_link .= $blog_other_name_after;
		$blog_link .= '</a>';
	} // End of testing which blog is being displayed
	$blog_link .= $blog_item_end;

	$blog_links[] = $blog_link;
}

// Output:
echo $blog_list_start;
echo implode( $blog_list_separator, $blog_links );
echo $blog_list_end;


/*
 * $Log$
 * Revision 1.12  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>

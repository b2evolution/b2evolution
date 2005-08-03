<?php
	/**
	 * This is the template that displays (recursive) list of (sub)categories
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
	if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

	# You can customize the following as you wish:
	if(!isset($cat_all)) $cat_all = /* TRANS: All categories, skin's categories list */ T_('All');	// Set to empty to hide
	# global category list delimiters:
	if(!isset($cat_main_start)) $cat_main_start = '';
	if(!isset($cat_main_end)) $cat_main_end = '';
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

	/*
	 * WARNING: the category list is displayed recursively.
	 * This is a little tricky. Don't modify below unless you really know what you're doing!
	 */

	// ----------------- START RECURSIVE CAT LIST ----------------
	cat_query( true );	// make sure the caches are loaded
	if( ! isset( $cat_array ) ) $cat_array = array();

	/**
	 * callback to start sublist
	 */
	function cat_list_before_first( $parent_cat_ID, $level )
	{	// callback to start sublist
		global $cat_group_start;
		$r = '';
		if( $level > 0 ) $r .= "\n".$cat_group_start."\n";
		return $r;
	}

	/**
	 * callback to display sublist element
	 */
	function cat_list_before_each( $cat_ID, $level )
	{	// callback to display sublist element
		global $blogfilename, $cat_array, $cat_line_start, $cat_line_checkbox;
		$cat = get_the_category_by_ID( $cat_ID );
		$r = $cat_line_start;
		if( $cat_line_checkbox )
		{
			$r .= '<label><input type="checkbox" name="catsel[]" value="'.$cat_ID.'"';
			if( in_array( $cat_ID, $cat_array ) )
			{	// This category is in the current selection
				$r .= ' checked="checked"';
			}
			$r .= ' />';
		}
		$r .= '<a href="'.url_add_param( get_bloginfo('blogurl'), 'cat='.$cat_ID ).'">'.format_to_output($cat['cat_name'], 'htmlbody').'</a> <span class="dimmed">('.$cat['cat_postcount'].')</span>';
		if( in_array( $cat_ID, $cat_array ) )
		{	// This category is in the current selection
			$r .= "*";
		}
		if( $cat_line_checkbox )
		{
			$r .= '</label>';
		}
		return $r;
	}

	/**
	 * callback to display sublist element
	 */
	function cat_list_after_each( $cat_ID, $level )
	{	// callback to display sublist element
		global $cat_line_end;
		return $cat_line_end."\n";
	}

	/**
	 * callback to end sublist
	 */
	function cat_list_after_last( $parent_cat_ID, $level )
	{	// callback to end sublist
		global  $cat_group_end;
 		$r = '';
		if( $level > 0 ) $r .= $cat_group_end."\n";
		return $r;
	}

	// Start global list:
	echo $cat_main_start;
	if( $blog > 1 )
	{	// We want to display cats for one blog
		echo "\n",$cat_group_start,"\n";

		if( !empty( $cat_all ) )
		{	// We want to display a link to all cats:
			echo $cat_line_start,"\n";
			echo '<a href="',get_bloginfo('blogurl'),'">',$cat_all,'</a>';
			echo $cat_line_end,"\n";
		}

		echo cat_children( $cache_categories, $blog, NULL, 'cat_list_before_first', 'cat_list_before_each', 'cat_list_after_each', 'cat_list_after_last', 0 );

		echo "\n",$cat_group_end,"\n";
	}
	else
	{	// We want to display cats for all blogs
		for( $curr_blog_ID=blog_list_start('stub');
					$curr_blog_ID!=false;
				 $curr_blog_ID=blog_list_next('stub') )
		{ # by uncommenting the following lines you can hide some blogs
			// if( $curr_blog_ID == 2 ) continue; // Hide blog 2...

			if( ($curr_blog_ID == 1) && empty( $cat_all ) ) continue; // Hide blog 1 if requested

			echo $cat_blog_start;
			?><a href="<?php blog_list_iteminfo('blogurl', 'raw') ?>"><?php blog_list_iteminfo('name', 'htmlbody') ?></a>
			<?php
			echo $cat_blog_end;

			// run recursively through the cats
			echo cat_children( $cache_categories, $curr_blog_ID, NULL, 'cat_list_before_first', 'cat_list_before_each', 'cat_list_after_each', 'cat_list_after_last', 1 );
		}
	}

	// End global list:
	echo $cat_main_end;

	// ----------------- END RECURSIVE CAT LIST ----------------

?>
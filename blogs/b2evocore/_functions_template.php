<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */

require_once (dirname(__FILE__)."/_functions_cats.php");
require_once (dirname(__FILE__)."/_functions_blogs.php");
require_once (dirname(__FILE__)."/_functions_bposts.php");
require_once (dirname(__FILE__)."/_functions_comments.php");
require_once (dirname(__FILE__)."/_functions_trackback.php");
require_once (dirname(__FILE__)."/_functions_pingback.php");


/*
 * apply_filters(-)
 */
function apply_filters($tag, $string) 
{
	global $b2_filter;

	if (isset($b2_filter['all'])) 
	{	// We have filters defined to be applied for everything!
		// Make sure it's an array:
		// $b2_filter['all'] = (is_string($b2_filter['all'])) ? array($b2_filter['all']) : $b2_filter['all'];
		// Merge with the filters for the specific tag:
		$b2_filter[$tag] = array_merge($b2_filter['all'], $b2_filter[$tag]);
		// Make sure we never filter twice:
		$b2_filter[$tag] = array_unique($b2_filter[$tag]);
	}

	if (isset($b2_filter[$tag])) 
	{	// We have filters to be applied for this specific tag
		// Make sure it's an array:
		// $b2_filter[$tags] = (is_string($b2_filter[$tag])) ? array($b2_filter[$tag]) : $b2_filter[$tag];

		// Apply the stuff:
		$functions = $b2_filter[$tag];
		foreach($functions as $function) 
		{
			$string = $function($string);
		}
	}
	
	return $string;
}

/*
 * add_filter(-)
 *
 * fplanque: simplied to the max
 */
function add_filter($tag, $function_to_add) 
{
	global $b2_filter;

	if( !isset($b2_filter[$tag]) ) 
		$b2_filter[$tag] = array();

	if( !in_array( $function_to_add, $b2_filter[$tag] ) )
	{
		$b2_filter[$tag][] = $function_to_add;
	}
}




/*
 * Functions to be called from the template
 */

/*
 * single_month_title(-)
 *
 * fplanque: 0.8.3: changed defaults
 */
function single_month_title($prefix = '#', $display = 'htmlbody' ) 
{
	if( $prefix == '#' ) $prefix = ' '.T_('Archives for').': ';

	global $m, $w, $month;
	if(!empty($m) && $display) 
	{
		$my_year = substr($m,0,4);
		$my_month = T_($month[substr($m,4,2)]);
		$my_day = substr($m,6,2);

		$title = $prefix.$my_month.' '.$my_year;

		if( !empty( $my_day ) )
		{	// We also want to display a day
			$title .= ", $my_day";
		}
		
		if( !empty( $w ) )
		{	// We also want to display a week number
			$title .= ", week $w";
		}

		echo format_to_output( $title, $display );
	}
}


/** 
 * Display "Archive Directory" title if it has been requested
 *
 * {@internal arcdir_title(-) }}
 *
 * @param string Prefix to be displayed if something is going to be displayed
 * @param mixed Output format, see {@link format_to_output()} or false to 
 *								return value instead of displaying it
 */
function arcdir_title( $prefix = ' ', $display = 'htmlbody' ) 
{
	global $disp;
	
	if( $disp == 'arcdir' )
	{
		$info = $prefix.T_('Archive Directory');
		if ($display)
			echo format_to_output( $info, $display );
		else
			return $info;
	}
}


/*
 * archive_link(-)
 */
function archive_link( $year, $month, $day='', $week='', $show = true, $file='', $params='' )
{
	global $use_extra_path_info;

	if( empty($file) ) 
		$link = get_bloginfo('blogurl');
	else
		$link = $file;

	if( (! $use_extra_path_info) || (!empty($params)) )
	{	// We reference by Query: Dirty but explicit permalinks
		$link .= '?'.$params.'&amp;m=';
		$separator = '';
	}
	else
	{
		$link .= '/';
		$separator = '/';
	}
	
	$link .= $year;
	
	if( !empty( $month ) )
	{
		$link .= $separator.zeroise($month,2);
		if( !empty( $day ) )
		{
			$link .= $separator.zeroise($day,2);
		}
	}
	elseif( !empty( $week ) )
	{
		if( ! $use_extra_path_info )
		{	// We reference by Query: Dirty but explicit permalinks
			$link .= '&amp;w='.$week;
		}
		else
		{
			$link .= '/w'.zeroise($week,2);
		}
	}
	
	$link .= $separator;

	if( $show )
	{
		echo $link;
	}
	return $link;
}

?>

<?php
/**
 * Misc Functions to be called from the template
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 * @author This file built upon code from original b2 - http://cafelog.com/
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

require_once( dirname(__FILE__).'/_functions_cats.php' );
require_once( dirname(__FILE__).'/_functions_blogs.php' );
require_once( dirname(__FILE__).'/_functions_bposts.php' );
require_once( dirname(__FILE__).'/_functions_comments.php' );
require_once( dirname(__FILE__).'/_functions_trackback.php' );
require_once( dirname(__FILE__).'/_functions_pingback.php' );


/**
 * Template function: output base URL to b2evo's image folder
 *
 * {@internal imgbase(-)}}
 */
function imgbase()
{
	global $img_url;
	echo $img_url, '/';
}

/**
 * single_month_title(-)
 *
 * fplanque: 0.8.3: changed defaults
 *
 * @todo Respect locales datefmt
 *
 * @param string prefix to display, default is 'Archives for: '
 * @param string format to output, default 'htmlbody'
 * @param boolean show the year as link to year's archive (in monthly mode)
 */
function single_month_title( $prefix = '#', $display = 'htmlbody', $linktoyeararchive = true, $blogurl = '', $params = '' )
{
	global $m, $w, $month;

	if( $prefix == '#' ) $prefix = ' '.T_('Archives for').': ';

	if( !empty($m) && $display )
	{
		$my_year = substr($m,0,4);
		if( strlen($m) > 4 )
			$my_month = T_($month[substr($m,4,2)]);
		else
			$my_month = '';
		$my_day = substr($m,6,2);

		if( $display == 'htmlbody' && !empty( $my_month ) && $linktoyeararchive )
		{ // display year as link to year's archive
			$my_year = '<a href="' . archive_link( $my_year, '', '', '', false, $blogurl, $params ) . '">' . $my_year . '</a>';
		}


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


/**
 * Create a link to archive
 *
 * {@internal archive_link(-)}}
 * @param string year
 * @param string month
 * @param string day
 * @param string week
 * @param boolean show or return
 * @param string link, instead of blogurl
 * @param string GET params for 'file'
 */
function archive_link( $year, $month, $day = '', $week = '', $show = true, $file = '', $params = '' )
{
	global $Settings;

	if( empty($file) )
		$link = get_bloginfo('blogurl');
	else
		$link = $file;

	if( (! $Settings->get('links_extrapath')) || (!empty($params)) )
	{	// We reference by Query: Dirty but explicit permalinks
		$link = url_add_param( $link, $params );
		$link = url_add_param( $link, 'm=' );
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
		if( ! $Settings->get('links_extrapath') )
		{	// We reference by Query: Dirty but explicit permalinks
			$link = url_add_param( $link, 'w='.$week );
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

<?php
/**
 * This file implements misc functions to be called from the templates.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author cafelog (team)
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/_category.funcs.php' );
require_once( dirname(__FILE__).'/_blog.funcs.php' );
require_once( dirname(__FILE__).'/_item.funcs.php' );
require_once( dirname(__FILE__).'/_comment.funcs.php' );
require_once( dirname(__FILE__).'/_trackback.funcs.php' );
require_once( dirname(__FILE__).'/_pingback.funcs.php' );


/**
 * Template function: output base URL to b2evo's image folder
 *
 * {@internal imgbase(-)}}
 */
function imgbase()
{
	global $img_url;
	echo $img_url;
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

		if( !empty($w) && ($w>=0) ) // Note: week # can be 0
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
 *
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
	elseif( $week !== '' )  // Note: week # can be 0 !
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

/*
 * $Log$
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.23  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 */
?>
<?php
/**
 * This file displays the archives.
 *
 * THIS FILE IS DEPRECATED. IT IS LEFT AS A STUB FOR OLDER SKINS COMPATIBILITY.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 *
 * @deprecated Deprecated by {@link archives_plugin}
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$Debuglog->add( 'Call to deprecated skin helper _archives.php', 'deprecated' );


# number of archive entries to display:
if(!isset($archive_limit)) $archive_limit = 12;
# this is what will separate your archive links
if(!isset($archive_line_start)) $archive_line_start = '<li>';
if(!isset($archive_line_end)) $archive_line_end = '</li>';
# this is what will separate dates on weekly archive links
if(!isset($archive_week_separator)) $archive_week_separator = ' - ';
# override general date format ? 0 = no: use the date format set in Options, 1 = yes: override
if(!isset($archive_date_format_over_ride)) $archive_date_format_over_ride = 0;
# options for daily archive (only if you override the general date format)
if(!isset($archive_day_date_format)) $archive_day_date_format = 'Y/m/d';


// Call the Archives plugin WITH NO MORE LINK AND NO LIST DELIMITERS:
$Plugins->call_by_code( 'evo_Arch', array(
                'title'=>'',
								'block_start'=>'',
								'block_end'=>'',
								'limit'=>$archive_limit,
								'more_link'=>'',
								'list_start'=>'',
								'list_end'=>'',
                'line_start'=>$archive_line_start,
                'line_end'=>$archive_line_end,
                'day_date_format'=>($archive_date_format_over_ride ? $archive_day_date_format : ''),
							 ) );

?>
<?php
/**
 * This file implements the Archives plugin.
 *
 * Displays a list of post archives.
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
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 * @author cafelog (group)
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Archives Plugin
 *
 * This plugin displays
 */
class archives_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name = 'Archives Skin Tag';
	var $code = 'evo_Arch';
	var $priority = 50;
	var $version = 'CVS $Revision$';
	var $author = 'The b2evo Group';
	var $help_url = 'http://b2evolution.net/';

	/**
	 * Variables below MAY be overriden.
	 */


	/**
	 * Constructor
	 *
	 * {@internal archives_plugin::archives_plugin(-)}}
	 */
	function archives_plugin()
	{
		$this->short_desc = T_('This skin tag displays a list of post archives.');
		$this->long_desc = T_('Archives can be grouped monthly, daily, weekly or post by post.');

		$this->dbtable = 'T_posts';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'ID';
	}


 	/**
	 * Event handler: SkinTag
	 *
	 * {@internal archives_plugin::SkinTag(-)}}
	 *
	 * @param array Associative array of parameters. Valid keys are:
	 *                - 'block_start' : (Default: '<div class="bSideItem">')
	 *                - 'block_end' : (Default: '</div>')
	 *                - 'title' : (Default: '<h3>'.T_('Archives').'</h3>')
	 *                - 'mode' : 'monthly'|'daily'|'weekly'|'postbypost' (Default: conf.)
	 *                - 'link_type' : 'canonic'|'context' (default: canonic)
	 *                - 'context_isolation' : what params need override when changing date/range (Default: 'm,w,p,title,unit,dstart' )
	 *                - 'form' : true|false (default: false)
	 *                - 'limit' : # of archive entries to display or '' (Default: 12)
	 *                - 'more_link' : more link text or '' (Default: 12)
	 *                - 'list_start' : (Default '<ul>')
	 *                - 'list_end' : (Default '</ul>')
	 *                - 'line_start' : (Default '<li>')
	 *                - 'line_end' : (Default '</li>')
	 *                - 'day_date_format' : (Default: conf.)
	 * @return boolean did we display?
	 */
	function SkinTag( $params )
	{
	 	global $Settings, $month;
	 	global $show_statuses, $timestamp_min, $timestamp_max;
		/**
		 * @todo get rid of these globals:
		 */
		global $blog, $Blog, $m;

		/**
		 * Default params:
		 */
		// This is what will enclose the block in the skin:
		if(!isset($params['block_start'])) $params['block_start'] = '<div class="bSideItem">';
		if(!isset($params['block_end'])) $params['block_end'] = "</div>\n";

		// Title:
		if(!isset($params['title']))
			$params['title'] = '<h3>'.T_('Archives').'</h3>';

		// Archive mode:
		if(!isset($params['mode']))
			$params['mode'] = $Settings->get('archive_mode');

		// Link type:
		if(!isset($params['link_type'])) $params['link_type'] = 'canonic';
		if(!isset($params['context_isolation'])) $params['context_isolation'] = 'm,w,p,title,unit,dstart';

		// Add form fields?:
		if(!isset($params['form']))
			$params['form'] = false;

		// Number of archive entries to display:
		if(!isset($params['limit'])) $params['limit'] = 12;

		// More link text:
		if(!isset($params['more_link'])) $params['more_link'] = T_('More...');

		// This is what will enclose the list:
		if(!isset($params['list_start'])) $params['list_start'] = '<ul>';
		if(!isset($params['list_end'])) $params['list_end'] = "</ul>\n";

		// This is what will separate the archive links:
		if(!isset($params['line_start'])) $params['line_start'] = '<li>';
		if(!isset($params['line_end'])) $params['line_end'] = "</li>\n";

		// Daily archive date format?
		if( (!isset($params['day_date_format'])) || ($params['day_date_format'] == '') )
		{
		 	$dateformat = locale_datefmt();
			$params['day_date_format'] = $dateformat;
		}

		$ArchiveList = & new ArchiveList( $blog, $params['mode'], $show_statuses,
																			$timestamp_min, $timestamp_max, $params['limit'],
																			$this->dbtable, $this->dbprefix, $this->dbIDname );

		echo $params['block_start'];

		echo $params['title'];

		echo $params['list_start'];
		while( $ArchiveList->get_item( $arc_year, $arc_month, $arc_dayofmonth, $arc_w, $arc_count, $post_ID, $post_title) )
		{
			echo $params['line_start'];
			switch( $params['mode'] )
			{
				case 'monthly':
					// --------------------------------- MONTHLY ARCHIVES -------------------------------------
					$arc_m = $arc_year.zeroise($arc_month,2);

					if( $params['form'] )
					{ // We want a radio button:
						echo '<input type="radio" name="m" value="'.$arc_m.'" class="checkbox"';
						if( $m == $arc_m ) echo ' checked="checked"' ;
						echo ' /> ';
					}

					echo '<a href="';
					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( $params['context_isolation'], 'm='.$arc_m );
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						archive_link( $arc_year, $arc_month );
					}
					echo '">';

					echo T_($month[zeroise($arc_month,2)]),' ',$arc_year;
					echo '</a> <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'daily':
					// --------------------------------- DAILY ARCHIVES ---------------------------------------
					$arc_m = $arc_year.zeroise($arc_month,2).zeroise($arc_dayofmonth,2);

					echo '<input type="radio" name="m" value="'. $arc_m. '" class="checkbox"';
					if( $m == $arc_m ) echo ' checked="checked"' ;
					echo ' /> ';

					echo '<a href="';
					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( $params['context_isolation'], 'm='.$arc_m );
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						archive_link( $arc_year, $arc_month, $arc_dayofmonth );
					}
					echo '">';

					echo mysql2date($params['day_date_format'], $arc_year.'-'.zeroise($arc_month,2).'-'.zeroise($arc_dayofmonth,2).' 00:00:00');
					echo '</a> <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'weekly':
					// --------------------------------- WEEKLY ARCHIVES --------------------------------------
					echo '<a href="';
					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( $params['context_isolation'], 'm='.$arc_year.'&amp;w='.$arc_w );
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						archive_link( $arc_year, '', '', $arc_w );
					}
					echo '">';
					echo $arc_year.', '.T_('week').' '.$arc_w;
					echo '</a> <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'postbypost':
				default:
					// -------------------------------- POST BY POST ARCHIVES ---------------------------------
					echo '<a href="';
					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( $params['context_isolation'], 'p'.$post_ID );
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						permalink_link( '', 'id', $post_ID );
					}
					echo '">';
					if ($post_title)
					{
						echo strip_tags($post_title);
					}
					else
					{
						echo $post_ID;
					}
					echo '</a>';
			}

			echo $params['line_end'];
		}

		// Display more link:
		if( !empty($params['more_link']) )
    {
			echo $params['line_start'];
     	echo '<a href="';
     	$Blog->disp( 'arcdirurl', 'raw' );
     	echo '">'.format_to_output($params['more_link']).'</a>';
			echo $params['line_end'];
    }

		echo $params['list_end'];

 		echo $params['block_end'];

		return true;
	}
}
?>
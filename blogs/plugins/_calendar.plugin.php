<?php
/**
 * This file implements the Calendar plugin.
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
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Calendar Plugin
 *
 * This plugin displays
 */
class calendar_plugin extends Plugin
{
	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */

	var $name = 'Calendar Skin Tag';
	var $code = 'evo_Calr';
	var $priority = 20;
	var $version = 'CVS $Revision$';
	var $author = 'The b2evo Group';
	var $help_url = 'http://b2evolution.net/';



	/**
	 * Constructor
	 *
	 * {@internal calendar_plugin::calendar_plugin(-)}}
	 */
	function calendar_plugin()
	{
		$this->short_desc = T_('This skin tag displays a navigable calendar.');
		$this->long_desc = T_('Days containing posts are highlighted.');

		$this->dbtable = 'T_posts';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'ID';
	}


 	/**
	 * Event handler: SkinTag
	 *
	 * {@internal calendar_plugin::SkinTag(-)}}
	 *
	 * @param array Associative array of parameters. Valid keys are:
	 *                - 'block_start' : (Default: '<div class="bSideItem">')
	 *                - 'block_end' : (Default: '</div>')
	 *                - 'title' : (Default: '<h3>'.T_('Calendar').'</h3>')
	 *                - 'displaycaption'
	 *                - 'monthformat'
	 *                - 'linktomontharchive'
	 *                - 'tablestart'
	 *                - 'tableend'
	 *                - 'monthstart'
	 *                - 'monthend'
	 *                - 'rowstart'
	 *                - 'rowend'
	 *                - 'headerdisplay'
	 *                - 'headerrowstart'
	 *                - 'headerrowend'
	 *                - 'headercellstart'
	 *                - 'headercellend'
	 *                - 'cellstart'
	 *                - 'cellend'
	 *                - 'linkpostcellstart'
	 *                - 'linkposttodaycellstart'
	 *                - 'todaycellstart'
	 *                - 'todaycellstartpost'
	 *                - 'navigation' : Where do we want to have the navigation arrows? (Default: 'tfoot')
	 *                - 'browseyears' : boolean  Do we want arrows to move one year at a time?
	 *                - 'postcount_month_cell'
	 *                - 'postcount_month_cell_one'
	 *                - 'postcount_month_atitle'
	 *                - 'postcount_month_atitle_one'
	 *                - 'postcount_year_cell'
	 *                - 'postcount_year_cell_one'
	 *                - 'postcount_year_atitle'
	 *                - 'postcount_year_atitle_one'
	 *                - 'link_type' : 'canonic'|'context' (default: canonic)
	 * @return boolean did we display?
	 */
	function SkinTag( $params )
	{
	 	global $Settings, $month;
		global $blog, $cat, $catsel;
	 	global $show_statuses;
	 	global $author;
	 	global $m, $w, $dstart, $timestamp_min, $timestamp_max;
	 	global $s, $phrase, $exact;

		/**
		 * Default params:
		 */
		// This is what will enclose the block in the skin:
		if(!isset($params['block_start'])) $params['block_start'] = '<div class="bSideItem">';
		if(!isset($params['block_end'])) $params['block_end'] = "</div>\n";

		// Title:
		if(!isset($params['title']))
			$params['title'] = '<h3>'.T_('Calendar').'</h3>';


		$Calendar = & new Calendar( $m );
		// CONSTRUCT THE WHERE CLAUSE:
		// * - - Restrict to selected blog/categories:
		$Calendar->ItemQuery->where_chapter( $blog, $cat, $catsel );

		// * Restrict to the statuses we want to show:
		$Calendar->ItemQuery->where_status( $show_statuses );

		// Restrict to selected authors:
		$Calendar->ItemQuery->where_author( $author );

		// - - - + * * if a month is specified in the querystring, load that month:
		$Calendar->ItemQuery->where_datestart( /* NO m */'', /* NO w */'', $dstart, '', $timestamp_min, $timestamp_max );

		// Keyword search stuff:
		$Calendar->ItemQuery->where_keywords( $s, $phrase, $exact );

		
		// TODO: automate with a table inside of Calendatr object. Table should also contain descriptions and default values to display in help screen.
		if( isset($params['displaycaption']) ) $Calendar->set( 'displaycaption', $params['displaycaption'] );
		if( isset($params['monthformat']) ) $Calendar->set( 'monthformat', $params['monthformat'] );
		if( isset($params['linktomontharchive']) ) $Calendar->set( 'linktomontharchive', $params['linktomontharchive'] );
		if( isset($params['tablestart']) ) $Calendar->set( 'tablestart', $params['tablestart'] );
		if( isset($params['tableend']) ) $Calendar->set( 'tableend', $params['tableend'] );
		if( isset($params['monthstart']) ) $Calendar->set( 'monthstart', $params['monthstart'] );
		if( isset($params['monthend']) ) $Calendar->set( 'monthend', $params['monthend'] );
		if( isset($params['rowstart']) ) $Calendar->set( 'rowstart', $params['rowstart'] );
		if( isset($params['rowend']) ) $Calendar->set( 'rowend', $params['rowend'] );
		if( isset($params['headerdisplay']) ) $Calendar->set( 'headerdisplay', $params['headerdisplay'] );
		if( isset($params['headerrowstart']) ) $Calendar->set( 'headerrowstart', $params['headerrowstart'] );
		if( isset($params['headerrowend']) ) $Calendar->set( 'headerrowend', $params['headerrowend'] );
		if( isset($params['headercellstart']) ) $Calendar->set( 'headercellstart', $params['headercellstart'] );
		if( isset($params['headercellend']) ) $Calendar->set( 'headercellend', $params['headercellend'] );
		if( isset($params['cellstart']) ) $Calendar->set( 'cellstart', $params['cellstart'] );
		if( isset($params['cellend']) ) $Calendar->set( 'cellend', $params['cellend'] );
		if( isset($params['emptycellstart']) ) $Calendar->set( 'emptycellstart', $params['emptycellstart'] );
		if( isset($params['emptycellend']) ) $Calendar->set( 'emptycellend', $params['emptycellend'] );
		if( isset($params['emptycellcontent']) ) $Calendar->set( 'emptycellcontent', $params['emptycellcontent'] );
		if( isset($params['linkpostcellstart']) ) $Calendar->set( 'linkpostcellstart', $params['linkpostcellstart'] );
		if( isset($params['linkposttodaycellstart']) ) $Calendar->set( 'linkposttodaycellstart', $params['linkposttodaycellstart'] );
		if( isset($params['todaycellstart']) ) $Calendar->set( 'todaycellstart', $params['todaycellstart'] );
		if( isset($params['todaycellstartpost']) ) $Calendar->set( 'todaycellstartpost', $params['todaycellstartpost'] );
		if( isset($params['navigation']) ) $Calendar->set( 'navigation', $params['navigation'] );
		if( isset($params['browseyears']) ) $Calendar->set( 'browseyears', $params['browseyears'] );
		if( isset($params['postcount_month_cell']) ) $Calendar->set( 'postcount_month_cell', $params['postcount_month_cell'] );
		if( isset($params['postcount_month_cell_one']) ) $Calendar->set( 'postcount_month_cell_one', $params['postcount_month_cell_one'] );
		if( isset($params['postcount_month_atitle']) ) $Calendar->set( 'postcount_month_atitle', $params['postcount_month_atitle'] );
		if( isset($params['postcount_month_atitle_one']) ) $Calendar->set( 'postcount_month_atitle_one', $params['postcount_month_atitle_one'] );
		if( isset($params['postcount_year_cell']) ) $Calendar->set( 'postcount_year_cell', $params['postcount_year_cell'] );
		if( isset($params['postcount_year_cell_one']) ) $Calendar->set( 'postcount_year_cell_one', $params['postcount_year_cell_one'] );
		if( isset($params['postcount_year_atitle']) ) $Calendar->set( 'postcount_year_atitle', $params['postcount_year_atitle'] );
		if( isset($params['postcount_year_atitle_one']) ) $Calendar->set( 'postcount_year_atitle_one', $params['postcount_year_atitle_one'] );
		// Link type:
		if( isset($params['link_type']) ) $Calendar->set( 'link_type', $params['link_type'] );
		if( isset($params['context_isolation']) ) $Calendar->set( 'context_isolation', $params['context_isolation'] );

		echo $params['block_start'];

		echo $params['title'];

		$Calendar->display( );

 		echo $params['block_end'];

		return true;
	}
}
?>
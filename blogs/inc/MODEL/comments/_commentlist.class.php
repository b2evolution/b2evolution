<?php
/**
 * This file implements the CommentList class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobjectlist.class.php';

/**
 * CommentList Class
 *
 * @package evocore
 */
class CommentList extends DataObjectList
{
	var $blog;

	/**
	 * Constructor
	 */
	function CommentList(
		$blog = 1,
		$comment_types = "'comment'",
		$show_statuses = array(),							// Not used yet
		$p = '',															// Restrict to specific post
		$author = '',													// Not used yet
		$order = 'DESC',											// ASC or DESC
		$orderby = '',												// list of fields to order by
		$limit = '' 													// # of comments to display on the page
		)
	{
		global $DB;
		global $cache_categories;
		global $pagenow;		// Bleh !

		// Call parent constructor:
		parent::DataObjectList( 'T_comments', 'comment_', 'comment_ID', 'Item', NULL, $limit );

		$this->blog = $blog;


		$this->sql = 'SELECT DISTINCT T_comments.*
									FROM T_comments INNER JOIN T_posts ON comment_post_ID = post_ID ';

		if( !empty( $p ) )
		{	// Restrict to comments on selected post
			$this->sql .= " WHERE comment_post_ID = $p AND ";
		}
		elseif( $blog > 1 )
		{	// Restrict to viewable posts/cats on current blog
			$this->sql .= "INNER JOIN T_postcats ON post_ID = postcat_post_ID INNER JOIN T_categories othercats ON postcat_cat_ID = othercats.cat_ID WHERE othercats.cat_blog_ID = $blog AND ";
		}
		else
		{	// This is blog 1, we don't care, we can include all comments:
			$this->sql .= ' WHERE ';
		}

		$this->sql .= "comment_type IN ($comment_types) ";

		/*
		 * ----------------------------------------------------
		 *  Restrict to the statuses we want to show:
		 * ----------------------------------------------------
		 */
		$this->sql .= ' AND '.statuses_where_clause( $show_statuses );


		// order by stuff
		if( (!empty($order)) && ((strtoupper($order) != 'ASC') && (strtoupper($order) != 'DESC')))
		{
			$order='DESC';
		}

		if(empty($orderby))
		{
			$orderby = 'comment_date '.$order;
		}
		else
		{
			$orderby_array = explode(' ',$orderby);
			$orderby = $orderby_array[0].' '.$order;
			if (count($orderby_array)>1)
			{
				for($i = 1; $i < (count($orderby_array)); $i++)
				{
					$orderby .= ', comment_'.$orderby_array[$i].' '.$order;
				}
			}
		}


		$this->sql .= "ORDER BY $orderby";
		if( !empty( $this->limit ) )
		{
			$this->sql .= ' LIMIT '.$this->limit;
		}

		// echo $this->sql;

		$this->rows = $DB->get_results( $this->sql, ARRAY_A );

		// Prebuild and cache objects:
		if( $this->result_num_rows = $DB->num_rows )
		{	// fplanque>> why this test??

			$i = 0;
			foreach( $this->rows as $row )
			{
				// Prebuild object:
				$this->Obj[$i] = new Comment( $row ); // COPY !!??

				// To avoid potential future waste, cache this object:
				// $this->DataObjectCache->add( $this->Obj[$i] );

				$i++;
			}
		}
	}


	/**
	 * Template function: display message if list is empty
	 *
	 * {@internal Comment::display_if_empty(-) }}
	 *
	 * @param string String to display if list is empty
   * @return true if empty
	 */
	function display_if_empty( $message = '' )
	{
		if( empty($message) )
		{	// Default message:
			$message = T_('No comment yet...');
		}

		return parent::display_if_empty( $message );
	}

}

/*
 * $Log$
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.14  2005/12/19 19:30:14  fplanque
 * minor
 *
 * Revision 1.13  2005/12/12 19:21:21  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.12  2005/11/21 20:37:39  fplanque
 * Finished RSS skins; turned old call files into stubs.
 *
 * Revision 1.11  2005/11/18 22:05:41  fplanque
 * no message
 *
 * Revision 1.10  2005/10/03 18:10:07  fplanque
 * renamed post_ID field
 *
 * Revision 1.9  2005/09/06 17:13:54  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.8  2005/08/25 16:06:45  fplanque
 * Isolated compilation of categories to use in an ItemList.
 * This was one of the oldest bugs on the list! :>
 *
 * Revision 1.7  2005/04/07 17:55:50  fplanque
 * minor changes
 *
 * Revision 1.6  2005/03/14 20:22:19  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.5  2005/03/09 20:29:39  fplanque
 * added 'unit' param to allow choice between displaying x days or x posts
 * deprecated 'paged' mode (ultimately, everything should be pageable)
 *
 * Revision 1.4  2005/03/06 16:30:40  blueyed
 * deprecated global table names.
 *
 * Revision 1.3  2005/02/28 09:06:32  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.20  2004/10/11 19:13:14  fplanque
 * Edited code documentation.
 *
 */
?>
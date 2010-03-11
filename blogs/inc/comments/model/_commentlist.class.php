<?php
/**
 * This file implements the CommentList2 class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
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
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );

/**
 * CommentList Class 2
 *
 * @package evocore
 */
/**
 * @author asimo
 *
 */
class CommentList2 extends DataObjectList2
{
	/**
	 * SQL object for the Query
	 */
	var $CommentQuery;

	/**
	 * Blog object this CommentList refers to
	 */
	var $Blog;
	
	/**
	 * Constructor
	 *
	 * @param Blog
	 * @param integer|NULL Limit
	 * @param string name of cache to be used
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string Name to be used when saving the filterset (leave empty to use default for collection)
	 */
	function CommentList2(
		$Blog,
		$limit = 20,
		$cache_name = 'CommentCache',	// name of cache to be used
		$param_prefix = '',
		$filterset_name = ''			// Name to be used when saving the filterset (leave empty to use default for collection)
		)
	{
		global $Settings;
		
		// Call parent constructor:
		parent::DataObjectList2( get_Cache($cache_name), $limit, $param_prefix, NULL );

		// The SQL Query object:
		$this->CommentQuery = new CommentQuery(/* $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname*/ );

		$this->Blog = & $Blog;

		if( !empty( $filterset_name ) )
		{	// Set the filterset_name with the filterset_name param
			$this->filterset_name = 'CommentList_filters_'.$filterset_name;
		}
		else
		{	// Set a generic filterset_name
			$this->filterset_name = 'CommentList_filters_coll'.$this->Blog->ID;
		}

		$this->page_param = $param_prefix.'paged';

		// Initialize the default filter set:
		$this->set_default_filters( array(
				'filter_preset' => NULL,
				'author' => NULL,
				'author_email' => NULL,
				'author_url' => NULL,
				'author_IP' => NULL,
				'post_ID' => NULL,
				'comment_ID' => NULL,
				'comment_ID_list' => NULL,
				'rating' => NULL,
				'keywords' => NULL,
				'phrase' => 'AND',
				'exact' => 0,
				'statuses' => NULL,
				'types' => array( 'comment','trackback','pingback' ),
				'orderby' => 'date',
				'order' => $this->Blog->get_setting('orderdir'),
				//'order' => 'DESC',
				'comments' => $this->limit,
				'page' => 1,
				'featured' => NULL,
		) );
	}
	
	
	/**
	 * Reset the query -- EXPERIMENTAL
	 *
	 * Useful to requery with a slighlty moidified filterset
	 */
	function reset()
	{
		// The SQL Query object:
		$this->CommentQuery = new CommentQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );

		parent::reset();
	}
	
	
	/**
	 * Set default filter values we always want to use if not individually specified otherwise:
	 *
	 * @param array default filters to be merged with the class defaults
	 * @param array default filters for each preset, to be merged with general default filters if the preset is used
	 */
	function set_default_filters( $default_filters, $preset_filters = array() )
	{
		$this->default_filters = array_merge( $this->default_filters, $default_filters );
		$this->preset_filters = $preset_filters;
	}
	
	
	/**
	 * Set/Activate filterset
	 *
	 * This will also set back the GLOBALS !!! needed for regenerate_url().
	 *
	 * @param array
	 * @param boolean
	 */
	function set_filters( $filters, $memorize = false )
	{
		if( !empty( $filters ) )
		{ // Activate the filterset (fallback to default filter when a value is not set):
			$this->filters = array_merge( $this->default_filters, $filters );
		}

		// Activate preset filters if necessary:
		$this->activate_preset_filters();

		// Funky oldstyle params:
		$this->limit = $this->filters['comments']; // for compatibility with parent class
		$this->page = $this->filters['page'];

		// asimo> memorize is always false for now, because is not fully implemented
		if( $memorize )
		{	// set back the GLOBALS !!! needed for regenerate_url() :

			/*
			 * Selected filter preset:
			 */
			memorize_param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], $this->filters['filter_preset'] );  // List of authors to restrict to

			/*
			 * Restrict to selected authors attribute:
			 */
			memorize_param( $this->param_prefix.'author', 'string', $this->default_filters['author'], $this->filters['author'] );  // List of authors ID to restrict to
			memorize_param( $this->param_prefix.'author_email', 'string', $this->default_filters['author_email'], $this->filters['author_email'] );  // List of authors ID to restrict to
			memorize_param( $this->param_prefix.'author_url', 'string', $this->default_filters['author_url'], $this->filters['author_url'] );  // List of authors ID to restrict to
			memorize_param( $this->param_prefix.'author_IP', 'string', $this->default_filters['author_IP'], $this->filters['author_IP'] );  // List of authors ID to restrict to
			
			/*
			 * Restrict to selected rating:
			 */
			memorize_param( $this->param_prefix.'rating', 'integer', $this->default_filters['rating'], $this->filters['rating'] );  // List of statuses to restrict to
			
			/*
			 * Restrict by keywords
			 */
			memorize_param( $this->param_prefix.'s', 'string', $this->default_filters['keywords'], $this->filters['keywords'] );			 // Search string
			memorize_param( $this->param_prefix.'sentence', 'string', $this->default_filters['phrase'], $this->filters['phrase'] ); // Search for sentence or for words
			memorize_param( $this->param_prefix.'exact', 'integer', $this->default_filters['exact'], $this->filters['exact'] );     // Require exact match of title or contents

			/*
			 * Restrict to selected statuses:
			 */
			memorize_param( $this->param_prefix.'status', 'string', $this->default_filters['statuses'], $this->filters['statuses'] );  // List of statuses to restrict to

			/*
			 * Restrict to selected comment type:
			 */
			memorize_param( $this->param_prefix.'type', 'string', $this->default_filters['types'], $this->filters['types'] );  // List of comment types to restrict to

			/*
			 * Restrict to the statuses we want to show:
			 */
			// Note: oftentimes, $show_statuses will have been preset to a more restrictive set of values
			//memorize_param( $this->param_prefix.'show_statuses', 'array', $this->default_filters['visibility_array'], $this->filters['visibility_array'] );	// Array of sharings to restrict to

			/*
			 * OLD STYLE orders:
			 */
			memorize_param( $this->param_prefix.'order', 'string', $this->default_filters['order'], $this->filters['order'] );   		// ASC or DESC
			memorize_param( $this->param_prefix.'orderby', 'string', $this->default_filters['orderby'], $this->filters['orderby'] );  // list of fields to order by (TODO: change that crap)

			/*
			 * Paging limits:
			 */
			memorize_param( $this->param_prefix.'comments', 'integer', $this->default_filters['comments'], $this->filters['comments'] ); 			// # of units to display on the page

			// 'paged'
			memorize_param( $this->page_param, 'integer', 1, $this->filters['page'] );      // List page number in paged display
		}
	}
	
	
	/**
	 * Activate preset default filters if necessary
	 *
	 */
	function activate_preset_filters()
	{
		$filter_preset = $this->filters['filter_preset'];

		if( empty( $filter_preset ) )
		{ // No filter preset, there are no additional defaults to use:
			return;
		}

		// Override general defaults with the specific defaults for the preset:
		$this->default_filters = array_merge( $this->default_filters, $this->preset_filters[$filter_preset] );

		// Save the name of the preset in order for is_filtered() to work properly:
		$this->default_filters['filter_preset'] = $this->filters['filter_preset'];
	}
	
	
	/**
	 *
	 *
	 * @todo count?
	 */
	function query_init()
	{
		global $current_User;

		if( empty( $this->filters ) )
		{	// Filters have not been set before, we'll use the default filterset:
			// If there is a preset filter, we need to activate its specific defaults:
			$this->filters['filter_preset'] = param( $this->param_prefix.'filter_preset', 'string', $this->default_filters['filter_preset'], true );
			$this->activate_preset_filters();

			// Use the default filters:
			$this->set_filters( $this->default_filters );
		}

		// GENERATE THE QUERY:
		
		/*
		 * Resrict to selected blog
		 */
		// If we dont have specific comment or post ids, we have to restric to blog
		if( ( $this->filters['post_ID'] == NULL || ( ! empty($this->filters['post_ID']) && substr( $this->filters['post_ID'], 0, 1 ) == '-') ) &&
			( $this->filters['comment_ID'] == NULL || ( ! empty($this->filters['comment_ID']) && substr( $this->filters['comment_ID'], 0, 1 ) == '-') ) &&
			( $this->filters['comment_ID_list'] == NULL || ( ! empty($this->filters['comment_ID_list']) && substr( $this->filters['comment_ID_list'], 0, 1 ) == '-') ) )
		{ // restriction for blog
			$this->CommentQuery->blog_restrict( $this->Blog );
		}

		/*
		 * filtering stuff:
		 */
		$this->CommentQuery->where_author( $this->filters['author'] );
		$this->CommentQuery->where_author_email( $this->filters['author_email'] );
		$this->CommentQuery->where_author_url( $this->filters['author_url'] );
		$this->CommentQuery->where_author_IP( $this->filters['author_IP'] );
		$this->CommentQuery->where_post_ID( $this->filters['post_ID'] );
		$this->CommentQuery->where_ID( $this->filters['comment_ID'], $this->filters['author'] );
		$this->CommentQuery->where_ID_list( $this->filters['comment_ID_list'] );
		$this->CommentQuery->where_rating( $this->filters['rating'] );
		$this->CommentQuery->where_keywords( $this->filters['keywords'], $this->filters['phrase'], $this->filters['exact'] );
		$this->CommentQuery->where_statuses( $this->filters['statuses'] );
		$this->CommentQuery->where_types( $this->filters['types'] );


		/*
		 * ORDER BY stuff:
		 */
		$order_by = gen_order_clause( $this->filters['orderby'], $this->filters['order'], $this->Cache->dbprefix, $this->Cache->dbIDname );

		$this->CommentQuery->order_by( $order_by );
		
		/*
		 * Page set up:
		 */
		if( $this->page > 1 )
		{ // We have requested a specific page number
			if( $this->limit > 0 )
			{
				$pgstrt = '';
				$pgstrt = (intval($this->page) -1) * $this->limit. ', ';
				$this->CommentQuery->LIMIT( $pgstrt.$this->limit );
			}
		}
		else
		{
			$this->CommentQuery->LIMIT( $this->limit );
		}
	}
	
	
	/**
	 * Run Query: GET DATA ROWS *** HEAVY ***
	 */
	function query()
	{
		global $DB;

		if( !is_null( $this->rows ) )
		{ // Query has already executed:
			return;
		}

		// INIT THE QUERY:
		$this->query_init();

		// Results style orders:
		// $this->CommentQuery->ORDER_BY_prepend( $this->get_order_field_list() );


		// We are going to proceed in two steps (we simulate a subquery)
		// 1) we get the IDs we need
		// 2) we get all the other fields matching these IDs
		// This is more efficient than manipulating all fields at once.

		// *** STEP 1 ***
		// walter> Accordding to the standart, to DISTINCT queries, all columns used
		// in ORDER BY must appear in the query. This make que query work with PostgreSQL and
		// other databases.
		// fp> That can dramatically fatten the returned data. You must handle this in the postgres class (check that order fields are in select)
		$step1_sql = 'SELECT DISTINCT '.$this->Cache->dbIDname // .', '.implode( ', ', $order_cols_to_select )
									.$this->CommentQuery->get_from()
									.$this->CommentQuery->get_where()
									.$this->CommentQuery->get_group_by()
									.$this->CommentQuery->get_order_by()
									.$this->CommentQuery->get_limit();

		// Get list of the IDs we need:
		$ID_list = implode( ',', $DB->get_col( $step1_sql, 0, 'CommentList2::Query() Step 1: Get ID list' ) );

		// *** STEP 2 ***
		$this->sql = 'SELECT *
			              FROM '.$this->Cache->dbtablename;
		if( !empty($ID_list) )
		{
			$this->sql .= ' WHERE '.$this->Cache->dbIDname.' IN ('.$ID_list.') '
										.$this->CommentQuery->get_order_by();
		}
		else
		{
			$this->sql .= ' WHERE 0';
		}

		// ATTENTION: we skip the parent on purpose here!! fp> refactor
		DataObjectList2::query( false, false, false, 'CommentList2::Query() Step 2' );
	}
	
	
	/**
	 * If the list is sorted by category...
 	 *
 	 * This is basically just a stub for backward compatibility
	 */
	function & get_Comment()
	{
		$Comment = & parent::get_next();

		if( empty($Comment) )
		{
			$r = false;
			return $r;
		}

		//pre_dump( $Comment );

		return $Comment;
	}
	
	
	/**
	 * Template function: display message if list is empty
	 *
	 * @return boolean true if empty
	 */
	function display_if_empty( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'msg_empty'   => T_('No comment yet...'),
			), $params );

		return parent::display_if_empty( $params );
	}

}

/*
 * $Log$
 * Revision 1.16  2010/03/11 10:34:57  efy-asimo
 * Rewrite CommentList to CommentList2 task
 *
 * Revision 1.15  2010/02/08 17:52:13  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.14  2009/11/30 00:22:05  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.13  2009/11/25 16:05:02  efy-maxim
 * comments awaiting moderation improvements
 *
 * Revision 1.12  2009/09/14 12:46:36  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.11  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.10  2009/03/03 20:45:07  blueyed
 * Drop unnecessary global assignments in CommentList.
 *
 * Revision 1.9  2009/01/25 19:09:32  blueyed
 * phpdoc fixes
 *
 * Revision 1.8  2009/01/23 00:05:24  blueyed
 * Add Blog::get_sql_where_aggregate_coll_IDs, which adds support for '*' in list of aggregated blogs.
 *
 * Revision 1.7  2008/09/24 08:46:45  fplanque
 * Fixed random order
 *
 * Revision 1.6  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.5  2008/01/10 19:56:58  fplanque
 * moved to v-3-0
 *
 * Revision 1.4  2008/01/09 00:25:51  blueyed
 * Vastly improve performance in CommentList for large number of comments:
 * - add index comment_date_ID; and force it in the SQL (falling back to comment_date)
 *
 * Revision 1.3  2007/12/24 10:36:07  yabs
 * adding random order
 *
 * Revision 1.2  2007/11/03 21:04:26  fplanque
 * skin cleanup
 *
 * Revision 1.1  2007/06/25 10:59:42  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/06/20 23:00:14  blueyed
 * doc fixes
 *
 * Revision 1.8  2007/05/14 02:43:04  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.7  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.6  2006/12/17 23:42:38  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.5  2006/07/04 17:32:29  fplanque
 * no message
 *
 * Revision 1.4  2006/04/20 16:31:30  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.3  2006/04/18 19:29:51  fplanque
 * basic comment status implementation
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
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

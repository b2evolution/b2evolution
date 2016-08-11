<?php
/**
 * This file implements the Archives plugin.
 *
 * Displays a list of post archives.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( '_core/ui/results/_results.class.php', 'Results' );
load_class( '/items/model/_itemlistlight.class.php', 'ItemListLight' );


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

	var $name;
	var $code = 'evo_Arch';
	var $priority = 50;
	var $version = '6.7.5';
	var $author = 'The b2evo Group';
	var $group = 'widget';
	var $subgroup = 'navigation';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->name = T_( 'Archives Widget' );
		$this->short_desc = T_('This skin tag displays a list of post archives.');
		$this->long_desc = T_('Archives can be grouped monthly, daily, weekly or post by post.');

		$this->dbtable = 'T_items__item';
		$this->dbprefix = 'post_';
		$this->dbIDname = 'post_ID';
	}


	/**
	 * Get keys for block/widget caching
	 *
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @param integer Widget ID
	 * @return array of keys this widget depends on
	 */
	function get_widget_cache_keys( $widget_ID = 0 )
	{
		global $Blog;

		return array(
				'wi_ID'        => $widget_ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
			);
	}


	/**
	 * Event handler: SkinTag
	 *
	 * @param array Associative array of parameters. Valid keys are:
	 *                - 'block_start' : (Default: '<div class="bSideItem">')
	 *                - 'block_end' : (Default: '</div>')
	 *                - 'block_body_start' : (Default: '')
	 *                - 'block_body_end' : (Default: '')
	 *                - 'title' : (Default: T_('Archives'))
	 *                - 'mode' : 'monthly'|'daily'|'weekly'|'postbypost' (Default: 'monthly' )
	 *                - 'sort_order' : 'date'|'title' (Default: date - used only if the mode is 'postbypost')
	 *                - 'link_type' : 'canonic'|'context' (default: canonic)
	 *                - 'context_isolation' : what params need override when changing date/range (Default: 'm,w,p,title,unit,dstart' )
	 *                - 'form' : true|false (default: false)
	 *                - 'limit' : # of archive entries to display or '' (Default: 12)
	 *                - 'more_link' : more link text (Default: 'More...')
	 *                - 'list_start' : (Default '<ul>')
	 *                - 'list_end' : (Default '</ul>')
	 *                - 'line_start' : (Default '<li>')
	 *                - 'line_end' : (Default '</li>')
	 *                - 'day_date_format' : (Default: conf.)
	 * @return boolean did we display?
	 */
	function SkinTag( & $params )
	{
		global $month;

		/**
		 * @todo get rid of this global:
		 */
		global $m;

		/**
		 * @var Blog
		 */
		global $Blog;

		if( empty($Blog) )
		{
			return false;
		}

		// Prefix of the ItemList object
		$itemlist_prefix = isset( $params['itemlist_prefix'] ) ? $params['itemlist_prefix'] : '';

		/**
		 * Default params:
		 */
		$params = array_merge( array(
				// This is what will enclose the block in the skin:
				'block_start'       => '<div class="bSideItem">',
				'block_end'         => "</div>\n",
				// Title:
				'block_title_start' => '<h3>',
				'block_title_end'   => '</h3>',
				// This is what will enclose the body:
				'block_body_start'  => '',
				'block_body_end'    => '',
				// This is what will enclose the list:
				'list_start'        => '<ul>',
				'list_end'          => "</ul>\n",
				// This is what will separate the archive links:
				'line_start'        => '<li>',
				'line_end'          => "</li>\n",
				// Archive mode:
				'mode'              => $Blog->get_setting( 'archive_mode' ),
				// Link type:
				'link_type'         => 'canonic',
				'context_isolation' => $itemlist_prefix.'m,'.$itemlist_prefix.'w,'.$itemlist_prefix.'p,'.$itemlist_prefix.'title,'.$itemlist_prefix.'unit,'.$itemlist_prefix.'dstart',
				// Add form fields?:
				'form'              => false,
				// Number of archive entries to display:
				'limit'             => 12,
				// More link text:
				'more_link'         => T_('More...'),
			), $params );

		// Sort order (used only in postbypost mode):
		if( $params['mode'] != 'postbypost' )
		{
			$params['sort_order'] = 'date';
		}
		if( ! isset( $params['sort_order'] ) )
		{	// Set default sort order:
			$params['sort_order'] = $Blog->get_setting( 'archives_sort_order' );
		}

		// Daily archive date format?
		if( ! isset( $params['day_date_format'] ) || $params['day_date_format'] == '' )
		{
			$dateformat = locale_datefmt();
			$params['day_date_format'] = $dateformat;
		}

		$ArchiveList = new ArchiveList( $params['mode'], $params['limit'], $params['sort_order'], ($params['link_type'] == 'context'),
																			$this->dbtable, $this->dbprefix, $this->dbIDname );

		echo $params['block_start'];

		if( !empty($params['title']) )
		{	// We want to display a title for the widget block:
			echo $params['block_title_start'];
			echo $params['title'];
			echo $params['block_title_end'];
		}

		echo $params['block_body_start'];

		echo $params['list_start'];
		while( $ArchiveList->get_item( $arc_year, $arc_month, $arc_dayofmonth, $arc_w, $arc_count, $post_ID, $post_title, $permalink) )
		{
			echo $params['line_start'];
			switch( $params['mode'] )
			{
				case 'monthly':
					// --------------------------------- MONTHLY ARCHIVES -------------------------------------
					$arc_m = $arc_year.zeroise($arc_month,2);

					if( $params['form'] )
					{ // We want a radio button:
						echo '<input type="radio" name="'.$itemlist_prefix.'m" value="'.$arc_m.'" class="checkbox"';
						if( $m == $arc_m ) echo ' checked="checked"' ;
						echo ' /> ';
					}

					$text = T_($month[zeroise($arc_month,2)]).' '.$arc_year;

					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo '<a rel="nofollow" href="'.regenerate_url( $params['context_isolation'], $itemlist_prefix.'m='.$arc_m ).'">'.$text.'</a>';
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						echo $Blog->gen_archive_link( $text, T_('View monthly archive'), $arc_year, $arc_month );
					}

					echo ' <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'daily':
					// --------------------------------- DAILY ARCHIVES ---------------------------------------
					$arc_m = $arc_year.zeroise($arc_month,2).zeroise($arc_dayofmonth,2);

					if( $params['form'] )
					{ // We want a radio button:
						echo '<input type="radio" name="'.$itemlist_prefix.'m" value="'. $arc_m. '" class="checkbox"';
						if( $m == $arc_m ) echo ' checked="checked"' ;
						echo ' /> ';
					}

					$text = mysql2date($params['day_date_format'], $arc_year.'-'.zeroise($arc_month,2).'-'.zeroise($arc_dayofmonth,2).' 00:00:00');

					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo '<a rel="nofollow" href="'.regenerate_url( $params['context_isolation'], $itemlist_prefix.'m='.$arc_m ).'">'.$text.'</a>';
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						echo $Blog->gen_archive_link( $text, T_('View daily archive'), $arc_year, $arc_month, $arc_dayofmonth );
					}

					echo ' <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'weekly':
					// --------------------------------- WEEKLY ARCHIVES --------------------------------------

					$text = $arc_year.', '.T_('week').' '.$arc_w;

					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo '<a rel="nofollow" href="'.regenerate_url( $params['context_isolation'], $itemlist_prefix.'m='.$arc_year.'&amp;'.$itemlist_prefix.'w='.$arc_w ).'">'.$text.'</a>';
					}
					else
					{	// We want to link to the absolute canonical URL for this archive:
						echo $Blog->gen_archive_link( $text, T_('View weekly archive'), $arc_year, NULL, NULL, $arc_w );
					}
					echo ' <span class="dimmed">('.$arc_count.')</span>';
					break;

				case 'postbypost':
				default:
					// -------------------------------- POST BY POST ARCHIVES ---------------------------------

					if( $post_title)
					{
						$text = strip_tags($post_title);
					}
					else
					{
						$text = $post_ID;
					}

					if( $params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo '<a rel="nofollow" href="'.regenerate_url( $params['context_isolation'], 'p='.$post_ID ).'">'.$text.'</a>';
					}
					else
					{
						// fp> THIS IS ALL OBSOLETE. There is a better way to have a post list with a specific widget.
						// TO BE DELETED (waiting for photoblog cleanup)

						// until the cleanup, a fix. I hope.

						echo '<a href="'. $permalink .'">'.$text.'</a>';
					}
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

		echo $params['block_body_end'];

		echo $params['block_end'];

		return true;
	}


  /**
   * Get definitions for widget specific editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		$r = array(
			'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 60,
					'defaultvalue' => T_('Archives'),
			),
			'limit' => array(
				'label' => T_( 'Max items' ),
				'note' => T_( 'Maximum number of items to display.' ),
				'size' => 4,
				'defaultvalue' => 12,
			),
			'mode' => array(
				'label' => T_('Archive grouping'),
				'note' => T_('How do you want to browse the post archives? May also apply to permalinks.'),
				'type' => 'radio',
				'options' => array(
						array( 'monthly', T_('monthly') ),
						array( 'weekly', T_('weekly') ),
						array( 'daily', T_('daily') ),
						array( 'postbypost', T_('post by post') ),
					),
				'defaultvalue' => 'monthly',
			),
			'sort_order' => array(
				'label' => T_('Archive sorting'),
				'note' => T_('How to sort your archives? (only in post by post mode)'),
				'type' => 'radio',
				'options' => array(
						array( 'date', T_('date') ),
						array( 'title', T_('title') ),
					),
				'defaultvalue' => 'date',
			),
		);
		return $r;
	}

}


/**
 * Archive List Class
 *
 * @package evocore
 */
class ArchiveList extends Results
{
	var $archive_mode;
	var $arc_w_last;

	/**
	 * Constructor
	 *
	 * Note: Weekly archives use MySQL's week numbering and MySQL default if applicable.
	 * In MySQL < 4.0.14, WEEK() always uses mode 0: Week starts on Sunday;
	 * Value range is 0 to 53; week 1 is the first week that starts in this year.
	 *
	 * @link http://dev.mysql.com/doc/mysql/en/date-and-time-functions.html
	 *
	 * @todo categories combined with 'ALL' are not supported (will output too many archives,
	 * some of which will resolve to no results). We need subqueries to support this efficiently.
	 *
	 * @param string
	 * @param integer
	 * @param boolean
	 */
	function __construct(
		$archive_mode = 'monthly',
		$limit = 100,
		$sort_order = 'date',
		$preserve_context = false,
		$dbtable = 'T_items__item',
		$dbprefix = 'post_',
		$dbIDname = 'ID' )
	{
		global $DB;
		global $blog, $cat, $catsel;
		global $Blog;
		global $show_statuses;
		global $author, $assgn, $status, $types;
		global $s, $sentence, $exact;

		$this->dbtable = $dbtable;
		$this->dbprefix = $dbprefix;
		$this->dbIDname = $dbIDname;
		$this->archive_mode = $archive_mode;


		/*
		 * WE ARE GOING TO CONSTRUCT THE WHERE CLOSE...
		 */
		$this->ItemQuery = new ItemQuery( $this->dbtable, $this->dbprefix, $this->dbIDname ); // TEMPORARY OBJ

		// - - Select a specific Item:
		// $this->ItemQuery->where_ID( $p, $title );

		if( is_admin_page() )
		{	// Don't restrict by date in the Back-office
			$timestamp_min = NULL;
			$timestamp_max = NULL;
		}
		else
		{	// Restrict posts by date started
			$timestamp_min = $Blog->get_timestamp_min();
			$timestamp_max = $Blog->get_timestamp_max();
		}

		if( $preserve_context )
		{	// We want to preserve the current context:
			// * - - Restrict to selected blog/categories:
			$this->ItemQuery->where_chapter( $blog, $cat, $catsel );

			// * Restrict to the statuses we want to show:
			$this->ItemQuery->where_visibility( $show_statuses );

			// Restrict to selected authors:
			$this->ItemQuery->where_author( $author );

			// Restrict to selected assignees:
			$this->ItemQuery->where_assignees( $assgn );

			// Restrict to selected satuses:
			$this->ItemQuery->where_statuses( $status );

			// - - - + * * timestamp restrictions:
			$this->ItemQuery->where_datestart( '', '', '', '', $timestamp_min, $timestamp_max );

			// Keyword search stuff:
			$this->ItemQuery->where_keywords( $s, $sentence, $exact );

			$this->ItemQuery->where_types( $types );
		}
		else
		{	// We want to preserve only the minimal context:
			// * - - Restrict to selected blog/categories:
			$this->ItemQuery->where_chapter( $blog, '', array() );

			// * Restrict to the statuses we want to show:
			$this->ItemQuery->where_visibility( $show_statuses );

			// - - - + * * timestamp restrictions:
			$this->ItemQuery->where_datestart( '', '', '', '', $timestamp_min, $timestamp_max );

			// Include all types except pages, intros and sidebar links:
			$this->ItemQuery->where_itemtype_usage( 'post' );
		}


		$this->from = $this->ItemQuery->get_from();
		$this->where = $this->ItemQuery->get_where();
		$this->group_by = $this->ItemQuery->get_group_by();

		switch( $this->archive_mode )
		{
			case 'monthly':
				// ------------------------------ MONTHLY ARCHIVES ------------------------------------
				$sql = 'SELECT EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) AS year, EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) AS month,
																	COUNT(DISTINCT postcat_post_ID) AS count '
													.$this->from
													.$this->where.'
													GROUP BY year, month
													ORDER BY year DESC, month DESC';
				break;

			case 'daily':
				// ------------------------------- DAILY ARCHIVES -------------------------------------
				$sql = 'SELECT EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) AS year, MONTH('.$this->dbprefix.'datestart) AS month,
																	DAYOFMONTH('.$this->dbprefix.'datestart) AS day,
																	COUNT(DISTINCT postcat_post_ID) AS count '
													.$this->from
													.$this->where.'
													GROUP BY year, month, day
													ORDER BY year DESC, month DESC, day DESC';
				break;

			case 'weekly':
				// ------------------------------- WEEKLY ARCHIVES -------------------------------------
				$sql = 'SELECT EXTRACT(YEAR FROM '.$this->dbprefix.'datestart) AS year, '.
															$DB->week( $this->dbprefix.'datestart', locale_startofweek() ).' AS week,
															COUNT(DISTINCT postcat_post_ID) AS count '
													.$this->from
													.$this->where.'
													GROUP BY year, week
													ORDER BY year DESC, week DESC';
				break;

			case 'postbypost':
			default:
				// ----------------------------- POSY BY POST ARCHIVES --------------------------------
				$this->count_total_rows();
				$archives_list = new ItemListLight( $Blog , $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $this->total_rows );
				$archives_list->set_filters( array(
						'visibility_array' => array( 'published' ),  // We only want to advertised published items
						'itemtype_usage' => 'post', // Include all types with usage "post"
					) );

				if($sort_order == 'title')
				{
					$archives_list->set_filters( array(
					'orderby' => 'title',
					'order' => 'ASC') );
				}

				$archives_list->query();
				$this->rows = array();
				while ($Item = $archives_list->get_item())
				{
					$this->rows[] = $Item;
				}
				$this->result_num_rows = $archives_list->result_num_rows;
				$this->current_idx = 0;
				$this->arc_w_last = '';
				return;
		}


		// dh> Temp fix for MySQL bug - apparently in/around 4.1.21/5.0.24.
		// See http://forums.b2evolution.net/viewtopic.php?p=42529#42529
		if( in_array($this->archive_mode, array('monthly', 'daily', 'weekly')) )
		{
			$sql_version = $DB->get_version();
			if( version_compare($sql_version, '4', '>') )
			{
				$sql = 'SELECT SQL_CALC_FOUND_ROWS '.substr( $sql, 7 ); // "SQL_CALC_FOUND_ROWS" available since MySQL 4
			}
		}

		parent::__construct( $sql, 'archivelist_', '', $limit );

		$this->restart();
	}


	/**
	 * Count the number of rows of the SQL result
	 *
	 * These queries are complex enough for us not to have to rewrite them:
	 * dh> ???
	 */
	function count_total_rows( $sql_count = NULL )
	{
		global $DB;

		switch( $this->archive_mode )
		{
			case 'monthly':
				// ------------------------------ MONTHLY ARCHIVES ------------------------------------
				$sql_count = 'SELECT COUNT( DISTINCT EXTRACT(YEAR FROM '.$this->dbprefix.'datestart), EXTRACT(MONTH FROM '.$this->dbprefix.'datestart) ) '
													.$this->from
													.$this->where;
				break;

			case 'daily':
				// ------------------------------- DAILY ARCHIVES -------------------------------------
				$sql_count = 'SELECT COUNT( DISTINCT EXTRACT(YEAR FROM '.$this->dbprefix.'datestart), EXTRACT(MONTH FROM '.$this->dbprefix.'datestart),
																	EXTRACT(DAY FROM '.$this->dbprefix.'datestart) ) '
													.$this->from
													.$this->where;
				break;

			case 'weekly':
				// ------------------------------- WEEKLY ARCHIVES -------------------------------------
				$sql_count = 'SELECT COUNT( DISTINCT EXTRACT(YEAR FROM '.$this->dbprefix.'datestart), '
													.$DB->week( $this->dbprefix.'datestart', locale_startofweek() ).' ) '
													.$this->from
													.$this->where;
				break;

			case 'postbypost':
			default:
				// ----------------------------- POSY BY POST ARCHIVES --------------------------------
				$sql_count = 'SELECT COUNT( DISTINCT '.$this->dbIDname.' ) '
													.$this->from
													.$this->where
													.$this->group_by;
		}

		// echo $sql_count;

		$this->total_rows = $DB->get_var( $sql_count ); //count total rows

		// echo 'total rows='.$this->total_rows;
	}


	/**
	 * Rewind resultset
	 */
	function restart()
	{
		// Make sure query has executed at least once:
		$this->run_query();

		$this->current_idx = 0;
		$this->arc_w_last = '';
	}

	/**
	 * Getting next item in archive list
	 *
	 * WARNING: these are *NOT* Item objects!
	 */
	function get_item( & $arc_year, & $arc_month, & $arc_dayofmonth, & $arc_w, & $arc_count, & $post_ID, & $post_title, & $permalink )
	{
		// echo 'getting next item<br />';

		if( $this->current_idx >= $this->result_num_rows )
		{	// No more entry
			return false;
		}

		$arc_row = $this->rows[ $this->current_idx++ ];

		switch( $this->archive_mode )
		{
			case 'monthly':
				$arc_year  = $arc_row->year;
				$arc_month = $arc_row->month;
				$arc_count = $arc_row->count;
				return true;

			case 'daily':
				$arc_year  = $arc_row->year;
				$arc_month = $arc_row->month;
				$arc_dayofmonth = $arc_row->day;
				$arc_count = $arc_row->count;
				return true;

			case 'weekly':
				$arc_year  = $arc_row->year;
				$arc_w = $arc_row->week;
				$arc_count = $arc_row->count;
				return true;

			case 'postbypost':
			default:
				$post_ID = $arc_row->ID;
				$post_title = $arc_row->title;
				$permalink = $arc_row->get_permanent_url();
				return true;
		}
	}
}

?>
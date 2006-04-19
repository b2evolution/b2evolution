<?php
/**
 * This file implements the Results class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author fsaya: Fabrice SAYA-GASNIER / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_widget.class.php';


/**
 * Results class
 */
class Results extends Widget
{
	var $DB;

	/**
	 * SQL query
	 */
	var $sql;

	/**
	 * Total number of rows (if > {@link $limit}, it will result in multiple pages)
	 */
	var $total_rows;

	/**
	 * Number of lines per page
	 */
	var $limit;

	/**
	 * Number of rows in result set for current page.
	 */
	var $result_num_rows;

	/**
	 * Total number of pages
	 */
	var $total_pages;

	/**
	 * Current page
	 */
	var $page;

	/**
	 * Array of DB rows for current page.
	 */
	var $rows;

	/**
	 * List of IDs for current page.
	 * @uses Results::ID_col
	 */
	var $page_ID_list;

	/**
	 * Array of IDs for current page.
	 * @uses Results::ID_col
	 */
	var $page_ID_array;

	/**
	 * Current object idx in $rows array:
	 */
	var $current_idx = 0;

	/**
	 * idx relative to whole list (range: 0 to total_rows-1)
	 */
	var $global_idx;

	/**
	 * Is this gobally the 1st item in the list? (NOT just the 1st in current page)
	 */
	var $global_is_first;

	/**
	 * Is this gobally the last item in the list? (NOT just the last in current page)
	 */
	var $global_is_last;


	/**
	 * Cache to use to instantiate an object and cache it for each line of results.
	 *
	 * For this to work, all columns of the related table must be selected in the query
	 */
	var $Cache;

	/**
	 * This will hold the object instantiated by the Cache for the current line.
	 */
	var $current_Obj;


	/**
	 * Definitions for each column:
	 * -th
	 * -td
	 * -order
	 * -td_start. A column with no def will de displayed using
	 * the default defs from Results::params, that is to say, one of these:
	 *   - $this->params['col_start_first'];
	 *   - $this->params['col_start_last'];
	 *   - $this->params['col_start'];
	 *
	 */
	var $cols;

	/**
	 * Lazy filled.
	 */
	var $nb_cols;

	/**
	 * Do we want to display column headers?
	 * @var boolean
	 */
	var $col_headers = true;


	/**
	 * Display parameters
	 */
	var $params = NULL;


	/**
	 * Fieldname to group on.
	 *
	 * Leave empty if you don't want to group.
	 *
	 * @var string
	 */
	var $group_by = '';

 	/**
	 * Current group identifier:
	 * @var string
	 */
	var $current_group_ID = 0;

	/**
	 * Definitions for each GROUP column:
	 * -td
	 * -td_start. A column with no def will de displayed using
	 * the default defs from Results::params, that is to say, one of these:
	 *   - $this->params['grp_col_start_first'];
	 *   - $this->params['grp_col_start_last'];
	 *   - $this->params['grp_col_start'];
	 */
	var $grp_cols = NULL;

	/**
	 * Fieldname to detect empty data rows.
	 *
	 * Empty data rows can happen when left joining on groups.
	 * Leave empty if you don't want to detect empty datarows.
	 *
	 * @var string
	 */
	var $ID_col = '';

	/**
	 * URL param names
	 */
	var $param_prefix;
	var $page_param;
	var $order_param;

	/**
		* List of sortable fields
		*/
	var $order_field_list;


	/**
	 * Constructor
	 *
	 *
	 * @todo we might not want to count total rows when not needed...
	 * @todo fplanque: I am seriously considering putting $count_sqlinto 2nd or 3rd position. Any prefs?
	 *
	 * @param string SQL query
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string default ordering of columns (special syntax) if not specified in the URL params
	 *               example: -A-- will sort in ascending order on 2nd column
	 *               example: ---D will sort in descending order on 4th column
	 * @param integer number of lines displayed on one page
	 * @param NULL|string SQL query used to count the total # of rows (if NULL, we'll try to COUNT(*) by ourselves)
	 */
	function Results( $sql, $param_prefix = '', $default_order = '', $limit = 20, $count_sql = NULL,
									$init_page = true )
	{
		global $DB;
		$this->DB = & $DB;
		$this->sql = $sql;
		$this->limit = $limit;
		$this->param_prefix = $param_prefix;

		// Count total rows:
		// TODO: check if this can be done later instead
		$this->count_total_rows( $count_sql );

		if( $init_page )
		{	// attribution of a page number
			$this->page_param = 'results_'.$param_prefix.'page';
			$page = param( $this->page_param, 'integer', 1, true );
			$this->page = min( $page, $this->total_pages );
		}

		// attribution of an order type
		$this->order_param = 'results_'.$param_prefix.'order';
 		$this->order = param( $this->order_param, 'string', $default_order, true );
	}


	/**
	 * Rewind resultset
	 */
	function restart()
	{
		// Make sure query has exexuted:
		$this->query( $this->sql );

		$this->current_idx = 0;

		$this->global_idx = (($this->page-1) * $this->limit) + $this->current_idx;

		$this->global_is_first = ( $this->global_idx <= 0 ) ? true : false;

		$this->global_is_last = ( $this->global_idx >= $this->total_rows-1 ) ? true : false;

		$this->current_group_ID = 0;
	}


	/**
	 * Increment and update all necessary counters before processing a new line in result set
	 */
	function next_idx()
	{
		$this->current_idx++;

		$this->global_idx = (($this->page-1) * $this->limit) + $this->current_idx;

		$this->global_is_first = ( $this->global_idx <= 0 ) ? true : false;

		$this->global_is_last = ( $this->global_idx >= $this->total_rows-1 ) ? true : false;

		return $this->current_idx;
	}


	/**
	 * Run the query now!
	 *
	 * Will only run if it has not executed before.
	 *
	 * @todo do we need that $sql param ???
	 */
	function query( $sql, $create_default_cols_if_needed = true, $append_limit = true )
	{
		if( !is_null( $this->rows ) )
		{ // Query has already executed:
			return;
		}

		// Make sure we have colum definitions:
		if( is_null( $this->cols ) && $create_default_cols_if_needed )
		{ // Let's create default column definitions:
			$this->cols = array();

			if( !preg_match( '#SELECT \s+ (.+?) \s+ FROM#six', $this->sql, $matches ) )
			{
				die( 'Results->query() : No SELECT clause!' );
			}

			// Split requested columns by commata
			foreach( preg_split( '#\s*,\s*#', $matches[1] ) as $l_select )
			{
				if( is_numeric( $l_select ) )
				{ // just a single value (would produce parse error as '$x$')
					$this->cols[] = array( 'td' => $l_select );
				}
				elseif( preg_match( '#^(\w+)$#i', $l_select, $match ) )
				{ // regular column
					$this->cols[] = array( 'td' => '$'.$match[1].'$' );
				}
				elseif( preg_match( '#^(.*?) AS (\w+)#i', $l_select, $match ) )
				{ // aliased column
					$this->cols[] = array( 'td' => '$'.$match[2].'$' );
				}
			}

			if( !isset($this->cols[0]) )
			{
				die( 'No columns selected!' );
			}
		}



		// Append ORDER clause if necessary:
		if( $orders = $this->get_order_field_list() )
		{	// We have orders to append

			if( strpos( $this->sql, 'ORDER BY') === false )
			{ // there is no ORDER BY clause in the original SQL query
				$this->sql .= ' ORDER BY '.$orders.' ';
			}
			else
			{ // the chosen order must be appended to an existing ORDER BY clause
				$this->sql .= ', '.$orders.' ';
			}
		}


		if( $append_limit && !empty($this->limit) )
		{	// Limit lien range to requested page
			$sql = $this->sql.' LIMIT '.max(0, ($this->page-1)*$this->limit).', '.$this->limit;
		}

		// Execute query and store results
		$this->rows = $this->DB->get_results( $sql );

		// Store row count
		$this->result_num_rows = $this->DB->num_rows;

		// echo '<br />rows on page='.$this->result_num_rows;
	}


	/**
	 * Get a list of IDs for current page
	 *
 	 * @uses Results::ID_col
	 */
	function get_page_ID_list()
	{
		if( is_null( $this->page_ID_list ) )
		{
			$this->page_ID_list = implode( ',', $this->get_page_ID_array() );
			//echo '<br />'.$this->page_ID_list;
		}

		return $this->page_ID_list;
	}


	/**
	 * Get an array of IDs for current page
	 *
 	 * @uses Results::ID_col
	 */
	function get_page_ID_array()
	{
		if( is_null( $this->page_ID_array ) )
		{
			$this->page_ID_array = array();

			foreach( $this->rows as $row )
			{ // For each row/line:
				$this->page_ID_array[] = $row->{$this->ID_col};
			}
		}

		return $this->page_ID_array;
	}


	/**
	 * Count the total number of rows of the SQL result (all pages)
	 *
	 * This is done by dynamically modifying the SQL query and forging a COUNT() into it.
	 *
	 * @todo allow overriding?
	 * @todo handle problem of empty groups!
	 */
	function count_total_rows( $sql_count = NULL )
	{
		if( empty( $sql_count ) )
		{
			if( is_null($this->sql) )
			{ // We may want to remove this later...
				$this->total_rows = 0;
				$this->total_pages = 0;
				return;
			}

 			$sql_count = $this->sql;
			// echo $sql_count;

			/*
			 *
			 * On a un problème avec la recherche sur les sociétés
			 * si on fait un select count(*), ça sort un nombre de réponses énorme
			 * mais on ne sait pas pourquoi... la solution est de lister des champs dans le COUNT()
			 * MAIS malheureusement ça ne fonctionne pas pour d'autres requêtes.
			 * L'idéal serait de réussir à isoler qu'est-ce qui, dans la requête SQL, provoque le comportement
			 * bizarre....
			 */
			// Tentative 1:
			// if( !preg_match( '#FROM(.*?)((WHERE|ORDER BY|GROUP BY) .*)?$#si', $sql_count, $matches ) )
			//  die( "Can't understand query..." );
			// if( preg_match( '#(,|JOIN)#si', $matches[1] ) )
			// { // there was a coma or a JOIN clause in the FROM clause of the original query,
			// Tentative 2:
			// fplanque: je pense que la différence est sur la présence de DISTINCT ou non.
			// if( preg_match( '#\s DISTINCT \s#six', $sql_count, $matches ) )
			if( preg_match( '#\s DISTINCT \s+ ([A-Za-z_]+)#six', $sql_count, $matches ) )
			{ //
				// Get rid of any Aliases in colmun names:
				// $sql_count = preg_replace( '#\s AS \s+ ([A-Za-z_]+) #six', ' ', $sql_count );
				// ** We must use field names in the COUNT **
				//$sql_count = preg_replace( '#SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT( $1 ) FROM', $sql_count );

				//Tentative 3: we do a distinct on the first field only when counting:
				$sql_count = preg_replace( '#SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT( DISTINCT '.$matches[1].' ) FROM', $sql_count );
			}
			else
			{ // Single table request: we must NOT use field names in the count.
				$sql_count = preg_replace( '#SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT( * ) FROM', $sql_count );
			}

			// echo $sql_count;
		}

		$this->total_rows = $this->DB->get_var( $sql_count ); //count total rows

		$this->total_pages = empty($this->limit) ? 1 : ceil($this->total_rows / $this->limit);
	}


	/**
	 * Note: this function might actually not be very useful.
	 * If you define ->Cache before display, all rows will be instantiated on the fly.
	 * No need to restart et go through the rows a second time here.
	 *
	 * @param DataObjectCache
	 */
	function instantiate_page_to_Cache( & $Cache )
	{
		$this->Cache = & $Cache;

		// Make sure query has executed and we're at the top of the resultset:
		$this->restart();

		foreach( $this->rows as $row )
		{ // For each row/line:

			// Instantiate an object for the row and cache it:
			$this->Cache->instantiate( $row );
		}

	}


	/**
	 * Display paged list/table based on object parameters
	 *
	 * This is the meat of this class!
	 *
	 * @return int # of rows displayed
	 */
	function display( $display_params = NULL, $fadeout = array() )
	{
		// Initialize displaying:
		$this->display_init( $display_params );

		// -------------------------
		// Proceed with display:
		// -------------------------
		echo $this->params['before'];

			if( $this->total_pages == 0 )
			{ // There are no results! Nothing to display!

				// START OF LIST/TABLE:
				$this->display_list_start();

				// DISPLAY FILTERS:
				$this->display_filters();

				// END OF LIST/TABLE:
				$this->display_list_end();
			}
			else
			{	// We have rows to display:

				// GLOBAL (NAV) HEADER:
				$this->display_nav( 'header' );

				// START OF LIST/TABLE:
				$this->display_list_start();

					// TITLE / FILTERS / COLUMN HEADERS:
					$this->display_head();

					// GROUP & DATA ROWS:
					$this->display_body( $fadeout );

				// END OF LIST/TABLE:
				$this->display_list_end();

				// GLOBAL (NAV) FOOTER:
				$this->display_nav( 'footer' );
			}

		echo $this->params['after'];

		// Return number of rows diplayed:
		return $this->current_idx;
	}


	/**
	 * Initialize things in order to be ready for displaying.
	 *
	 * This is useful when manually displaying, i-e: not by using Results::display()
	 */
	function display_init( $display_params = NULL )
	{
		// Make sure we have display parameters:
		if( !is_null($display_params) )
		{ // Use passed params:
			$this->params = & $display_params;
		}
		elseif( empty( $this->params ) )
		{ // Use default params from Admin Skin:
			global $AdminUI;
			$this->params = $AdminUI->get_menu_template( 'Results' );
		}

		// Make sure query has executed and we're at the top of the resultset:
		$this->restart();
	}


	/**
	 * Display list/table start.
	 *
	 * Typically outputs UL or TABLE tags.
	 *
	 * @param boolean do we want special treatment when there are no results
	 */
	function display_list_start( $detect_no_results = true )
	{
		if( $detect_no_results && $this->total_pages == 0 )
		{ // There are no results! Nothing to display!
			echo $this->replace_vars( $this->params['no_results_start'] );
		}
		else
		{	// We have rows to display:
			echo $this->params['list_start'];
		}
	}


	/**
	 * Display list/table end.
	 *
	 * Typically outputs </ul> or </table>
	 *
	 * @param boolean do we want special treatment when there are no results
	 */
	function display_list_end( $detect_no_results = true )
	{
		if( $detect_no_results && $this->total_pages == 0 )
		{ // There are no results! Nothing to display!
			echo $this->replace_vars( $this->params['no_results_end'] );
		}
		else
		{	// We have rows to display:
			echo $this->params['list_end'];
		}
	}


  /**
   * Display the filtering form
   *
   * @param boolean Do we need to create a new form for the filters (this is useful for ResultSel)
   */
	function display_filters( $create_new_form = true )
	{
		if( !empty($this->filters_callback) )
		{
			echo $this->replace_vars( $this->params['filters_start'] );

			if( $create_new_form )
			{	// We do not already have a form surrounding the whole rsult list:
				$this->Form = new Form( regenerate_url(), $this->param_prefix.'form_search', 'post', 'none' ); // COPY!!

				$this->Form->begin_form( '' );
			}

			echo T_('Filters').': ';

			$func = $this->filters_callback;

			if( ! $func( $this->Form ) )
			{	// Function has not displayed the filter button yet:
				$this->Form->submit( array( 'filter_submit', T_('Filter list'), 'search' ) );
			}

			if( $create_new_form )
			{	// We do not already have a form surrounding the whole rsult list:
				$this->Form->end_form( '' );
			}

			echo $this->params['filters_end'];
		}
	}


	/**
	 * Display list/table head.
	 *
	 * This includes list head/title and column headers.
	 * This is optional and will only produce output if column headers are defined.
	 * EXPERIMENTAL: also dispays <tfoot>
	 *
	 * @access protected
	 */
	function display_head()
	{
		echo $this->params['head_start'];


		// DISPLAY TITLE:
		if( isset($this->title) )
		{ // A title has been defined for this result set:
			echo $this->replace_vars( $this->params['head_title'] );
		}


		// DISPLAY FILTERS:
		$this->display_filters();


		// DISPLAY COLUMN HEADERS:
		if( isset( $this->cols ) )
		{

			if( !isset($this->nb_cols) )
			{	// Needed for sort strings:
				$this->nb_cols = count($this->cols);
			}

			//
			$current_colspan = 1;
			$th_group_activated = false; 
			
			// Loop on all columns to define headers cells array we have to display and set all colspans:
			foreach( $this->cols as $key=>$col )
			{
				if( isset( $col['th_group'] ) )
				{ // The column is grouped with anoter column:
					if( $current_colspan == 1 )
					{	//It's the first column of the colspan, so initialize in the first header cell its colspan to 1:
						$header_cells[0][$key]['colspan'] = 1;
					}
					else 
					{ // It's not the first column of the colspan, so set its colspan to 0 to not display it in the first header cell 
						// and increase the first colspan column
						$header_cells[0][$key]['colspan'] = 0;
						$header_cells[0][$key-($current_colspan-1)]['colspan']++;
					}
					
					$current_colspan++;
					
					if( isset( $col['th'] ) )
					{ // The column has a th defined, so set the second header cell column colspan to 1 to display it:
						$header_cells[1][$key]['colspan'] = 1;
					}
					else 
					{ // The column has not a th defined, so set the second header cell column colspan to 0 to not display it:
						$header_cells[1][$key]['colspan'] = 0;
					}
					// We have grouped columns:
					$th_group_activated = true; 
				}
				else 
				{	// The column is not grouped, so set its colspan(0) to 1 to display it in the first header cell 
					// and set its colspan(1) to 0 to not display it in the second header cell 
					$header_cells[0][$key]['colspan'] = 1;
					$header_cells[1][$key]['colspan'] = 0;
					
					$current_colspan = 1;
				}
			}
			
			if( !$th_group_activated )
			{	// No grouped columns, so need not the second header cell array
				unset( $header_cells[1] );
			}
			
			// Loop on all header cells (<tr>)
			foreach( $header_cells as $key_cell=>$header_cell )
			{ 
				echo $this->params['line_start_head'];
			
				$col_count = 0;
				foreach( $this->cols as $col_idx => $col )
				{
					if( $header_cell[$col_idx]['colspan'] )
					{ // Colspan != 0, so display column:
						if( isset( $col['th_start'] ) )
						{ // We have a customized column start for this one:
							$output = $col['th_start'];
						}
						elseif( $col_idx == 0 && isset($this->params['colhead_start_first']) )
						{ // First column can get special formatting:
							$output = $this->params['colhead_start_first'];
						}
						elseif( $col_idx == (count( $this->cols)-1) && isset($this->params['colhead_start_last']) )
						{ // Last column can get special formatting:
							$output = $this->params['colhead_start_last'];
						}
						else
						{ // Regular columns:
							$output = $this->params['colhead_start'];
						}
						
						// Get rowspan column:
						$rowspan = 1;
						// Loop on all superiors header cells until colspan = 0:
						for( $i = $key_cell; $i < count( $header_cells ); $i++ )
						{
							if( isset( $header_cells[$i+1] ) &&  $header_cells[$i+1][$col_idx]['colspan'] == 0 )
							{	// Superior header cells column will be not displayed (colspan=0), so increase rowspan value:
								$rowspan++;
							}
							else 
							{	// No superior header cells any longer column or it will be displayed (colspan!=0) so break;
								break;
							}
						}
						if( $rowspan > 1 )
						{	// We need to define a rowspan for this column:
							$output = preg_replace( '#(<)([^>]*)>$#', '$1$2 rowspan="'.$rowspan.'">' , $output );
						}
						
						if( ( $colspan = $header_cell[$col_idx]['colspan'] ) > 1 )
						{ // We need to define a colspan for this column and a th_group class:
							// EXPERIMENTAL
							if( preg_match( '#class="(.*)"#', $output ) )
							{	// The column start already has a class, so add first th_group to it:  
								$output = preg_replace( '#(.*)class="([^"]*)"(.*)#', '$1 class="$2 th_group"$3', $output );
								// Add colspan
								$output = preg_replace( '#(<)([^>]*)>$#', '$1$2 colspan="'.$colspan.'">' , $output );
							}
							else 
							{	// The column start already has not a class, so add colspan and th_group class to it:
								$output = preg_replace( '#(<)([^>]*)>$#', '$1$2 colspan="'.$colspan.'" class="th_group">' , $output );
							}
						}
						
						// Display column start:			
						echo $output;
						
						//_________________________ Display column title: ______________________________________
						if( $header_cell[$col_idx]['colspan'] > 1 )
						{	// This column is grouped with other(s) column(s), so it will be diplayed the group title
							// and the order will be the group order( BUG POSITION COLONNE)
							$th_title = isset( $col['th_group'] ) ? $col['th_group'] : '';
							$col_order = isset( $col['order_group'] ) ? $col['order_group'] : '';
						}
						else 
						{	// th title, order:
							$th_title = isset( $col['th'] ) ? $col['th'] : '';
							$col_order = isset( $col['order'] ) ? $col['order'] : '';
						}

						if( $col_order )
						{ // The column can be ordered:


							$col_sort_values = $this->get_col_sort_values( $col_idx );


							// Detrmine CLASS SUFFIX depending on wether the current column is currently sorted or not:
							if( !empty($col_sort_values['current_order']) )
							{ // We are currently sorting on the current column:
								$class_suffix = '_current';
							}
							else
							{	// We are not sorting on the current column:
								$class_suffix = '_sort_link';
							}

							// Display title depending on sort type/mode:
							if( $this->params['sort_type'] == 'single' )
							{ // single column sort type:

								// Title with toggle:
								echo '<a href="'.$col_sort_values['order_toggle'].'"'
											.' title="'.T_('Change Order').'"'
											.' class="single'.$class_suffix.'"'
											.'>'.$th_title.'</a>';

								// Icon for ascending sort:
								echo '<a href="'.$col_sort_values['order_asc'].'"'
											.' title="'.T_('Ascending order').'"'
											.'>'.$this->params['sort_asc_'.($col_sort_values['current_order'] == 'ASC' ? 'on' : 'off')].'</a>';

								// Icon for descending sort:
								echo '<a href="'.$col_sort_values['order_desc'].'"'
											.' title="'.T_('Descending order').'"'
											.'>'.$this->params['sort_desc_'.($col_sort_values['current_order'] == 'DESC' ? 'on' : 'off')].'</a>';

							}
							else
							{ // basic sort type (toggle single column):

								if( $col_sort_values['current_order'] == 'ASC' )
								{ // the sorting is ascending and made on the current column
									$sort_icon = $this->params['basic_sort_asc'];
								}
								elseif(  $col_sort_values['current_order'] == 'DESC' )
								{ // the sorting is descending and made on the current column
									$sort_icon = $this->params['basic_sort_desc'];
								}
								else
								{ // the sorting is not made on the current column
									$sort_icon = $this->params['basic_sort_off'];
								}

								// Toggle Icon + Title
								echo '<a href="'.$col_sort_values['order_toggle'].'"'
											.' title="'.T_('Change Order').'"'
											.' class="basic'.$class_suffix.'"'
											.'>'.$sort_icon.' '.$th_title.'</a>';

							}

						}
						elseif( $th_title )
						{ // the column can't be ordered, but we still have a header defined:
							echo $th_title;
						}

						//________________________________________________________________________________________________
						
						echo $this->params['colhead_end'];
					}
				}
				echo $this->params['line_end'];
			}
		} // this->cols not set

		echo $this->params['head_end'];


		// Experimental:
		echo $this->params['tfoot_start'];
		echo $this->params['tfoot_end'];
	}


	/**
	 * Display list/table body.
	 *
	 * This includes groups and data rows.
	 *
	 * @access protected
	 *
	 * @param array fadeout list
	 */
	function display_body( $fadeout = array() )
	{
		if( !empty( $fadeout ) )
		{ // Initialize fadeout javascript:
			global $rsc_url;
			echo '<script type="text/javascript" src="'.$rsc_url.'js/fadeout.js"></script>';
			echo '<script type="text/javascript">addEvent( window, "load", Fat.fade_all, false);</script>';
		}

		echo $this->params['body_start'];

		$line_count = 0;
		// Used to set an id to fadeout element
		$fadeout_count = 0;
		foreach( $this->rows as $row )
		{ // For each row/line:

			/*
			 * Group row stuff:
			 */
			if( !empty($this->group_by) )
			{	// We are grouping...
				if( $row->{$this->group_by} != $this->current_group_ID )
				{	// We have just entered a new group!
					// memorize new group identifier:
					$this->current_group_ID = $row->{$this->group_by};

					echo '<tr class="group">';

					$col_count = 0;
					foreach( $this->grp_cols as $grp_col )
					{ // For each column:
						if( isset( $grp_col['td_start'] ) )
						{ // We have a customized column start for this one:
							$output = $grp_col['td_start'];
						}
						elseif( ($col_count==0) && isset($this->params['grp_col_start_first']) )
						{ // Display first column column start:
							$output = $this->params['col_start_first'];
						}
						elseif( ($col_count==count($this->cols)-1) && isset($this->params['grp_col_start_last']) )
						{ // Last column can get special formatting:
							$output = $this->params['grp_col_start_last'];
						}
						else
						{ // Display regular colmun start:
							$output = $this->params['grp_col_start'];
						}

						// Contents to output:
						$output .= $this->parse_col_content( $grp_col['td'] );
						//echo $output;
						eval( "echo '$output';" );

						echo '</td>';
						$col_count++;
					}

					echo '</tr>';

				}
			}


			/*
			 * Data row stuff:
			 */
			if( !empty($this->ID_col) && empty($row->{$this->ID_col}) )
			{	// We have detected an empty data row which we want to ignore... (happens with empty groups)
				continue;
			}


			if( ! is_null( $this->Cache ) )
			{ // We want to instantiate an object for the row and cache it:
				// We also keep a local ref in case we want to use it for display:
				$this->current_Obj = & $this->Cache->instantiate( $row );
			}


			if( $this->current_idx % 2 )
			{ // Odd line:
				if( $this->current_idx == count($this->rows)-1 )
					echo $this->params['line_start_odd_last'];
				else
					echo $this->params['line_start_odd'];
			}
			else
			{ // Even line:
				if( $this->current_idx == count($this->rows)-1 )
					echo $this->params['line_start_last'];
				else
					echo $this->params['line_start'];
			}

			$col_count = 0;
			foreach( $this->cols as $col )
			{ // For each column:

				/**
				 * Update td start class for fadeout list results
				 */
				foreach ( $fadeout as $key=>$crit )
				{
					if( isset( $row->$key ) && in_array( $row->$key, $crit ) )
					{ // Col is in the fadeout list
						if( isset( $col['td_start'] ) )
						{	// We have a customized column start for this one:
							if( preg_match( '#.*class="(.*)".*#', $col['td_start'], $res ) )
							{	// Already has a class, so add to its fadeout class
								$col['td_start'] = '<td class="fadeout-ffff00 '.$res[1].'" id="fadeout-'.$fadeout_count.'">';
							}
							else
							{	// has no class so a add fadout class
								$col['td_start'] = '<td class="fadeout-ffff00" id="fadeout-'.$fadeout_count.'">';
							}
						}
						else
						{	// We have not a customized column start for this one, so we set one with fadeout class
								$col['td_start'] = '<td class="fadeout-ffff00" id="fadeout-'.$fadeout_count.'">';
						}
						$fadeout_count++;
						break;
					}
				}

				if( isset( $col['td_start'] ) )
				{ // We have a customized column start for this one:
					$output = $col['td_start'];
				}
				elseif( ($col_count==0) && isset($this->params['col_start_first']) )
				{ // Display first column column start:
					$output = $this->params['col_start_first'];
				}
				elseif( ($col_count==count($this->cols)-1) && isset($this->params['col_start_last']) )
				{ // Last column can get special formatting:
					$output = $this->params['col_start_last'];
				}
				else
				{ // Display regular colmun start:
					$output = $this->params['col_start'];
				}

				// Contents to output:
				$output .= $col['td'];

				$output .= $this->params['col_end'];

				$output = $this->parse_col_content($output);
				#pre_dump( '{'.$output.'}' );
				eval( "echo '$output';" );

				$col_count++;
			}
			echo $this->params['line_end'];

			$this->next_idx();
		}

		echo $this->params['body_end'];
	}


	/**
	 * Display navigation text, based on template.
	 *
	 * @param string template: 'header' or 'footer'
	 *
	 * @access protected
	 */
	function display_nav( $template )
	{
		echo $this->params[$template.'_start'];

		if( ( $this->total_pages <= 1 ) )
		{	// Single page (we probably don't want to show navigation in this case)
			echo $this->params[$template.'_text_single'];
		}
		else
		{	// Several pages
			echo $this->replace_vars( $this->params[$template.'_text'] );
		}

		echo $this->params[$template.'_end'];
	}


  /**
   * Returns values needed to make sort links for a given column
   *
   * Returns an array containing the following values:
   *  - current_order : 'ASC', 'DESC' or ''
   *  - order_asc : url to order in ascending order
   *  - order_desc
   *  - order_toggle : url to toggle sort order
   *
   * @param integer column to sort
   * @return array
   */
	function get_col_sort_values( $col_idx )
	{

		// Current order:
		$order_char = substr( $this->order, $col_idx, 1 );
		if( $order_char == 'A' )
		{
			$col_sort_values['current_order'] = 'ASC';
		}
		elseif( $order_char == 'D' )
		{
			$col_sort_values['current_order'] = 'DESC';
		}
		else
		{
			$col_sort_values['current_order'] = '';
		}


		// Generate sort values to use for sorting on the current column:
		$order_asc = '';
		$order_desc = '';
		for( $i = 0; $i < $this->nb_cols; $i++ )
		{
			if(	$i == $col_idx )
			{ // Link ordering the current column
				$order_asc .= 'A';
				$order_desc .= 'D';
			}
			else
			{
				$order_asc .= '-';
				$order_desc .= '-';
			}
		}

		$col_sort_values['order_asc'] = regenerate_url( $this->order_param, $this->order_param.'='.$order_asc );
		$col_sort_values['order_desc'] = regenerate_url( $this->order_param, $this->order_param.'='.$order_desc );

		if( $col_sort_values['current_order'] == 'ASC' )
		{
			$col_sort_values['order_toggle'] = $col_sort_values['order_desc'];
		}
		else
		{
			$col_sort_values['order_toggle'] = $col_sort_values['order_asc'];
		}

		return $col_sort_values;
	}


	/**
	 * Returns order field list add to SQL query:
	 */
	function get_order_field_list()
	{
		if( is_null( $this->order_field_list ) )
		{ // Order list is not defined yet
			if( empty( $this->order ) )
			{ // We have no user provided order:
				if( empty( $this->cols ) )
				{	// We have no columns to pick an automatic order from:
					// echo 'Can\'t determine automatic order';
					return '';
				}

				foreach( $this->cols as $col )
				{
					if( isset( $col['order'] ) )
					{ // We have found the first orderable column:
						$this->order .= 'A';
						break;
					}
					else
					{
						$this->order .= '-';
					}
				}

				if( empty( $this->cols ) )
				{	// We did not find any column to order on...
					return '';
				}
			}

			// echo ' order='.$this->order.' ';

			$orders = array();

	    for( $i = 0; $i <= strlen( $this->order ); $i++ )
	    {	// For each position in order string:
				if( isset( $this->cols[$i]['order'] ) )
				{	// if column is sortable:
					switch( substr( $this->order, $i, 1 ) )
					{
						case 'A':
							$orders[] = str_replace( ',', ' ASC,', $this->cols[$i]['order']).' ASC';
							break;

						case 'D':
							$orders[] = str_replace( ',', ' DESC,', $this->cols[$i]['order']).' DESC';
							break;
					}
				}
			}
			$this->order_field_list = implode( ',', $orders );
		}
		return $this->order_field_list;	// May be empty
	}


	/**
	 * Handle variable subtitutions for column contents.
	 *
	 * This is one of the key functions to look at when you want to use the Results class.
	 * - $var$
	 * - £var£
	 * - #var#
	 * - {row}
	 * - {global_idx}
	 * - {global_is_first}
	 * - {global_is_last}
	 * - %func()%
	 * - ¤func()¤
	 */
	function parse_col_content( $content )
	{
		// Make variable substitution for STRINGS:
		$content = preg_replace( '#\$ (\w+) \$#ix', "'.format_to_output(\$row->$1).'", $content );
		// Make variable substitution for URL STRINGS:
		$content = preg_replace( '#\£ (\w+) \£#ix', "'.format_to_output(\$row->$1, 'urlencoded').'", $content );
		// Make variable substitution for escaped strings:
		$content = preg_replace( '#² (\w+) ²#ix', "'.htmlentities(\$row->$1).'", $content );
		// Make variable substitution for RAWS:
		$content = preg_replace( '!\# (\w+) \#!ix', "\$row->$1", $content );
		// Make variable substitution for full ROW:
		$content = str_replace( '{row}', '$row', $content );
		// Make variable substitution for full global_idx:
		$content = str_replace( '{global_idx}', "\$this->global_idx", $content );
		// Make variable substitution for full global_is_first:
		$content = str_replace( '{global_is_first}', "\$this->global_is_first", $content );
		// Make variable substitution for full global_is_last:
		$content = str_replace( '{global_is_last}', "\$this->global_is_last", $content );
		// Make callback function substitution:
		$content = preg_replace( '#% (.+?) %#ix', "'.$1.'", $content );
		// Make variable substitution for intanciated Object:
		$content = str_replace( '{Obj}', "\$this->current_Obj", $content );
		// Make callback for Object method substitution:
		$content = preg_replace( '#@ (.+?) @#ix', "'.\$this->current_Obj->$1.'", $content );
		// Sometimes we need embedded function call, so we provide a second sign:
		$content = preg_replace( '#¤ (.+?) ¤#ix', "'.$1.'", $content );

		// Make callback function move_icons
		$content = str_replace( '{move}', "'.\$this->move_icons().'", $content );


		return $content;
	}


	function move_icons( )
	{
		$r = '';

		$reg = '#^'.$this->param_prefix.'order (ASC|DESC).*#';

		if( preg_match( $reg, $this->order_field_list, $res ) )
		{	// The table is sorted by the order column
			$sort = $res[1];

			// get the element ID
			$idname = $this->param_prefix . 'ID';
			$id = $this->rows[$this->current_idx]->$idname;

			// Move up arrow
			if( $this->global_is_first )
			{	// The element is the first so it can't move up, display a no move arrow
				$r .= get_icon( 'nomove' ).' ';
			}
			else
			{
				if(	$sort == 'ASC' )
				{	// ASC sort, so move_up action for move up arrow
					$action = 'move_up';
					$alt = T_( 'Move up!' );
					}
				else
				{	// Reverse sort, so action and alt are reverse too
					$action = 'move_down';
					$alt = T_('Move down! (reverse sort)');
				}
				$r .= action_icon( $alt, 'move_up', regenerate_url( 'action,'.$this->param_prefix.'ID' , $this->param_prefix.'ID='.$id.'&amp;action='.$action ) );
			}

			// Move down arrow
			if( $this->global_is_last )
			{	// The element is the last so it can't move up, display a no move arrow
				$r .= get_icon( 'nomove' ).' ';
			}
			else
			{
				if(	$sort == 'ASC' )
				{	// ASC sort, so move_down action for move down arrow
					$action = 'move_down';
					$alt = T_( 'Move down!' );
				}
				else
				{ // Reverse sort, so action and alt are reverse too
					$action = 'move_up';
					$alt = T_('Move up! (reverse sort)');
				}
				$r .= action_icon( $alt, 'move_down', regenerate_url( 'action,'.$this->param_prefix.'ID', $this->param_prefix.'ID='.$id.'&amp;action='.$action ) );
			}

			return $r;
		}
		else
		{	// The table is not sorted by the order column, so we display no move arrows

			if( $this->global_is_first )
			{
				// The element is the first so it can't move up, display a no move up arrow
				$r = get_icon( 'nomove' ).' ';
			}
			else
			{	// Display no move up arrow
				$r = action_icon( T_( 'Sort by order' ), 'nomove_up', regenerate_url( 'action','action=sort_by_order' ) );
			}

			if( $this->global_is_last )
			{
				// The element is the last so it can't move down, display a no move down arrow
				$r .= get_icon( 'nomove' ).' ';
			}
			else
			{ // Display no move down arrow
				$r .= action_icon( T_( 'Sort by order' ), 'nomove_down', regenerate_url( 'action','action=sort_by_order' ) );
			}

			return $r;
		}
	}


	/**
	 * Widget callback for template vars.
	 *
	 * This allows to replace template vars, see {@link Widget::replace_callback()}.
	 *
	 * @return string
	 */
	function replace_callback( $matches )
	{
		//echo $matches[1];
		switch( $matches[1] )
		{
			case 'start' :
				//total number of rows in the sql query
				return  ( ($this->page-1)*$this->limit+1 );

			case 'end' :
				return ( min( $this->total_rows, $this->page*$this->limit ) );

			case 'total_rows' :
				return ( $this->total_rows );

			case 'page' :
				//current page number
				return ( $this->page );

			case 'total_pages' :
				//total number of pages
				return ( $this->total_pages );

			case 'prev' :
				//inits the link to previous page
				return ( $this->page>1 )
					? '<a href="'.regenerate_url( $this->page_param, $this->page_param.'='.($this->page-1) ).'">'.$this->params['prev_text'].'</a>'
					: $this->params['prev_text'];

			case 'next' :
				//inits the link to next page
				return ( $this->page<$this->total_pages )
					? '<a href="'.regenerate_url( $this->page_param, $this->page_param.'='.($this->page+1) ).'">  '.$this->params['next_text'].'</a>'
					: $this->params['next_text'];

			case 'list' :
				//inits the page list
				return $this->page_list($this->first(),$this->last());

			case 'scroll_list' :
				//inits the scrolling list of pages
				return $this->page_scroll_list();

			case 'first' :
				//inits the link to first page
				return $this->display_first();

			case 'last' :
				//inits the link to last page
				return $this->display_last();

			case 'list_prev' :
				//inits the link to previous page range
				return $this->display_prev();

			case 'list_next' :
				//inits the link to next page range
				return $this->display_next();

			case 'nb_cols' :
				// Number of columns in result:
				if( !isset($this->nb_cols) )
				{
					$this->nb_cols = count($this->cols);
				}
				return $this->nb_cols;

			default :
				return parent::replace_callback( $matches );
		}
	}


	/**
	 * Returns the first page number to be displayed in the list
	 */
	function first()
	{
		if( $this->page <= intval( $this->params['list_span']/2 ))
		{ // the current page number is small
			return 1;
		}
		elseif( $this->page > $this->total_pages-intval( $this->params['list_span']/2 ))
		{ // the current page number is big
			return max( 1, $this->total_pages-$this->params['list_span']+1);
		}
		else
		{ // the current page number can be centered
			return $this->page - intval($this->params['list_span']/2);
		}
	}


	/**
	 * returns the last page number to be displayed in the list
	 */
	function last()
	{
		if( $this->page > $this->total_pages-intval( $this->params['list_span']/2 ))
		{ //the current page number is big
			return $this->total_pages;
		}
		else
		{
			return min( $this->total_pages, $this->first()+$this->params['list_span']-1 );
		}
	}


	/**
	 * returns the link to the first page, if necessary
	 */
	function display_first()
	{
		if( $this->first() > 1 )
		{ //the list doesn't contain the first page
			return '<a href="'.regenerate_url( $this->page_param, $this->page_param.'=1' ).'">1</a>';
		}
		else
		{ //the list already contains the first page
			return NULL;
		}
	}


	/**
	 * returns the link to the last page, if necessary
	 */
	function display_last()
	{
		if( $this->last() < $this->total_pages )
		{ //the list doesn't contain the last page
			return '<a href="'.regenerate_url( $this->page_param, $this->page_param.'='.$this->total_pages ).'">'.$this->total_pages.'</a>';
		}
		else
		{ //the list already contains the last page
			return NULL;
		}
	}


	/**
	 * returns a link to previous pages, if necessary
	 */
	function display_prev()
	{
		if( $this->display_first() != NULL )
		{ //the list has to be displayed
			return '<a href="'.regenerate_url( $this->page_param, $this->page_param.'='.($this->first()-1) ).'">'
								.$this->params['list_prev_text'].'</a>';
		}

	}


	/**
	 * returns a link to next pages, if necessary
	 */
	function display_next()
	{
		if( $this->display_last() != NULL )
		{ //the list has to be displayed
			return '<a href="'.regenerate_url( $this->page_param,$this->page_param.'='.($this->last()+1) ).'">'
								.$this->params['list_next_text'].'</a>';
		}
	}


	/**
	 * Returns the page link list under the table
	 */
	function page_list($min, $max)
	{
		$i = 0;
		$list = '';

		for( $i=$min; $i<=$max; $i++)
		{
			if( $i == $this->page )
			{ //no link for the current page
				$list = $list.'<strong class="current_page">'.$i.'</strong> ';
			}
			else
			{ //a link for non-current pages
				$list = $list.'<a href="'.regenerate_url( $this->page_param, $this->page_param.'='.$i).'">'.$i.'</a> ';
			}
		}
		return $list;
	}


	/*
	 * Returns a scrolling page list under the table
	 */
	function page_scroll_list()
	{
		$scroll = '';
		$i = 0;
		$range = $this->params['scroll_list_range'];
		$min = 1;
		$max = 1;
		$option = '';
		$selected = '';
		$range_display='';

		if( $range > $this->total_pages )
			{ //the range is greater than the total number of pages, the list goes up to the number of pages
				$max = $this->total_pages;
			}
			else
			{ //initialisation of the range
				$max = $range;
			}

		//initialization of the form
		$scroll ='<form class="inline" method="post" action="'.regenerate_url( $this->page_param ).'">
							<select name="'.$this->page_param.'" onchange="parentNode.submit()">';//javascript to change page clicking in the scroll list

		while( $max <= $this->total_pages )
		{ //construction loop
			if( $this->page <= $max && $this->page >= $min )
			{ //display all the pages belonging to the range where the current page is located
				for( $i = $min ; $i <= $max ; $i++)
				{ //construction of the <option> tags
					$selected = ($i == $this->page) ? ' selected' : '';//the "selected" option is applied to the current page
					$option = '<option'.$selected.' value="'.$i.'">'.$i.'</option>';
					$scroll = $scroll.$option;
				}
			}
			else
			{ //inits the ranges inside the list
				$range_display = '<option value="'.$min.'">'
					.T_('Pages').' '.$min.' '. /* TRANS: Pages x _to_ y */ T_('to').' '.$max;
				$scroll = $scroll.$range_display;
			}

			if( $max+$range > $this->total_pages && $max != $this->total_pages)
			{ //$max has to be the total number of pages
				$max = $this->total_pages;
			}
			else
			{
				$max = $max+$range;//incrementation of the maximum value by the range
			}

			$min = $min+$range;//incrementation of the minimum value by the range


		}
		/*$input ='';
			$input = '<input type="submit" value="submit" />';*/
		$scroll = $scroll.'</select>'./*$input.*/'</form>';//end of the form*/

		return $scroll;
	}


	/**
	 * Get number of rows available for display
	 *
	 * @return integer
	 */
	function get_num_rows()
	{
		return $this->result_num_rows;
	}


	/**
	 * Template function: display message if list is empty
	 *
	 * @param string String to display if list is empty
   * @return true if empty
	 */
	function display_if_empty( $message = '' )
	{
		if( empty($message) )
		{ // Default message:
			$message = T_('Sorry, there is nothing to display...');
		}

		if( $this->result_num_rows == 0 )
		{
			echo $message;
			return true;
		}
		return false;
	}

}


/*
 * $Log$
 * Revision 1.8  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.7  2006/04/14 19:25:32  fplanque
 * evocore merge with work app
 *
 * Revision 1.6  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.5  2006/03/11 00:09:08  blueyed
 * *** empty log message ***
 *
 * Revision 1.4  2006/03/10 21:08:26  fplanque
 * Cleaned up post browsing a little bit..
 *
 * Revision 1.3  2006/03/06 20:03:40  fplanque
 * comments
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.51  2006/02/13 20:20:09  fplanque
 * minor / cleanup
 *
 * Revision 1.50  2006/02/10 22:08:07  fplanque
 * Various small fixes
 *
 * Revision 1.49  2006/02/09 23:31:05  blueyed
 * doc fixes
 *
 * Revision 1.48  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.47  2006/01/04 15:03:53  fplanque
 * enhanced list sorting capabilities
 *
 * Revision 1.46  2005/12/30 20:13:40  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.45  2005/12/22 15:51:58  fplanque
 * Splitted display and display init
 *
 * Revision 1.44  2005/12/19 16:42:03  fplanque
 * minor
 *
 * Revision 1.43  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.40  2005/11/23 23:29:16  blueyed
 * Sorry, encoding messed up.
 *
 * Revision 1.39  2005/11/23 22:48:50  blueyed
 * minor (translation strings)
 *
 * Revision 1.38  2005/11/21 20:37:39  fplanque
 * Finished RSS skins; turned old call files into stubs.
 *
 * Revision 1.37  2005/11/18 21:01:21  fplanque
 * no message
 *
 * Revision 1.36  2005/11/17 16:46:08  fplanque
 * no message
 *
 * Revision 1.35  2005/11/07 02:13:22  blueyed
 * Cleaned up Sessions and extended Widget etc
 *
 * Revision 1.34  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.33  2005/10/14 21:00:08  fplanque
 * Stats & antispam have obviously been modified with ZERO testing.
 * Fixed a sh**load of bugs...
 *
 * Revision 1.32  2005/10/12 18:24:37  fplanque
 * bugfixes
 *
 * Revision 1.31  2005/10/11 18:31:11  fplanque
 * no message
 *
 * Revision 1.30  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.29  2005/08/04 13:25:16  fplanque
 * fixed bug when there was no limit
 *
 * Revision 1.28  2005/07/15 18:12:01  fplanque
 * option to preload results objects to cache
 *
 * Revision 1.27  2005/06/27 23:59:25  blueyed
 * display(): fixes parse error for selecting straight value, supports "x AS y" selects
 *
 * Revision 1.25  2005/06/02 18:50:53  fplanque
 * no message
 *
 * Revision 1.24  2005/05/24 15:26:53  fplanque
 * cleanup
 *
 * Revision 1.23  2005/05/09 19:07:04  fplanque
 * bugfixes + global access permission
 *
 * Revision 1.22  2005/05/03 14:43:33  fplanque
 * no message
 *
 * Revision 1.21  2005/05/03 14:38:15  fplanque
 * finished multipage userlist
 *
 * Revision 1.20  2005/05/02 19:06:47  fplanque
 * started paging of user list..
 *
 * Revision 1.19  2005/04/07 17:55:50  fplanque
 * minor changes
 *
 * Revision 1.18  2005/04/06 19:11:02  fplanque
 * refactored Results class:
 * all col params are now passed through a 2 dimensional table which allows easier parametering of large tables with optional columns
 *
 * Revision 1.17  2005/03/21 17:38:01  fplanque
 * results/table layout refactoring
 *
 * Revision 1.16  2005/03/02 15:37:59  fplanque
 * experimentoing better count() automation :/
 *
 * Revision 1.15  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.14  2005/02/27 20:28:03  blueyed
 * taken count() out of loop
 *
 * Revision 1.13  2005/02/17 19:36:24  fplanque
 * no message
 *
 * Revision 1.12  2005/01/28 19:28:03  fplanque
 * enhanced UI widgets
 *
 * Revision 1.11  2005/01/26 16:47:13  fplanque
 * i18n tuning
 *
 * Revision 1.10  2005/01/20 19:19:34  fplanque
 * bugfix
 *
 * Revision 1.9  2005/01/20 18:45:54  fplanque
 * cleanup
 *
 * Revision 1.8  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.7  2005/01/12 20:40:40  fplanque
 * no message
 *
 * Revision 1.6  2005/01/03 15:17:52  fplanque
 * no message
 *
 * Revision 1.5  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Moved stuff down from DataObjectList class
 *
 * Revision 1.3  2004/12/17 20:39:48  fplanque
 * added sort orders and extended navigation
 *
 * Revision 1.2  2004/12/13 21:29:58  fplanque
 * refactoring
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.4  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 */
?>
<?php
/**
 * This file implements the Results class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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
 * @version $Id: _results.class.php 6532 2014-04-22 13:38:39Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// DEBUG: (Turn switch on or off to log debug info for specified category)
$GLOBALS['debug_results'] = false;


load_class( '_core/ui/_uiwidget.class.php', 'Table' );
load_class( '_core/ui/_uiwidget.class.php', 'Widget' );

/**
 * Results class
 *
 * @package evocore
 * @todo Support $cols[]['order_rows_callback'] / order_objects_callback also if there's a LIMIT?
 */
class Results extends Table
{
	/**
	 * SQL query
	 */
	var $sql;

	/**
	 * SQL query to count total rows
	 */
	var $count_sql;

	/**
	 * Total number of rows (if > {@link $limit}, it will result in multiple pages)
	 *
	 * Note: Do not use directly outside of the results class, use get_total_rows() function instead!
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
	 * Current page
	 */
	var $page;

	/**
	 * Array of DB rows for current page.
	 */
	var $rows;

	/**
	 * List of IDs for current page.
	 * @uses Results::$ID_col
	 */
	var $page_ID_list;

	/**
	 * Array of IDs for current page.
	 * @uses Results::$ID_col
	 */
	var $page_ID_array;

	/**
	 * Current object idx in $rows array
	 * @var integer
	 */
	var $current_idx = 0;

	/**
	 * idx relative to whole list (range: 0 to total_rows-1)
	 * @var integer
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
	 *
	 * @var DataObjectCache
	 */
	var $Cache;

	/**
	 * This will hold the object instantiated by the Cache for the current line.
	 */
	var $current_Obj;


	/**
	 * Definitions for each column:
	 * - th
	 * - td
	 * - order: SQL column name(s) to sort by (delimited by comma)
	 * - order_objects_callback: a PHP callback function (can be array($Object, $method)).
	 *     This gets three params: $a, $b, $desc.
	 *     $a and $b are instantiated objects from {@link Results::$Cache}
	 *     $desc is either 'ASC' or 'DESC'. The function has to return -1, 0 or 1,
	 *     according to if the $a < $b, $a == $b or $a > $b.
	 * - order_rows_callback: a PHP callback function (can be array($Object, $method)).
	 *     This gets three params: $a, $b, $desc.
	 *     $a and $b are DB row objects
	 *     $desc is either 'ASC' or 'DESC'. The function has to return -1, 0 or 1,
	 *     according to if the $a < $b, $a == $b or $a > $b.
	 * - td_class
	 *
	 */
	var $cols;

	/**
	 * Do we want to display column headers?
	 * @var boolean
	 */
	var $col_headers = true;


	/**
	 * DB fieldname to group on.
	 *
	 * Leave empty if you don't want to group.
	 *
	 * NOTE: you have to use ORDER BY goup_column in your query for this to work correctly.
	 *
	 * @var mixed string or array
	 */
	var $group_by = '';

	/**
	 * Object property/properties to group on.
	 *
	 * Objects get instantiated and grouped by the given property/member value.
	 *
	 * NOTE: this requires {@link Result::$Cache} to be set and is probably only useful,
	 *       if you do not use {@link Result::$limit}, because grouping appears after
	 *       the relevant data has been pulled from DB.
	 *
	 * @var mixed string or array
	 */
	var $group_by_obj_prop;

	/**
	 * Current group identifier (by level/depth)
	 * @var array
	 */
	var $current_group_ID;

	/**
	 * Definitions for each GROUP column:
	 * -td
	 * -td_start. A column with no def will de displayed using
	 * the default defs from Results::$params, that is to say, one of these:
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
	var $page_param;
	var $order_param;
	var $limit_param;

	/**
	 * List of sortable fields
	 */
	var $order_field_list;

	/**
	 * List of sortable columns by callback ("order_objects_callback" and "order_rows_callback")
	 * @var array
	 */
	var $order_callbacks;


	/**
	 * Parameters for the functions area (to display functions at the end of results array):
	 */
	var $functions_area;


	/**
	 * Should there be nofollows on page navigation
	 */
	var $nofollow_pagenav = false;

	/**
	 * Constructor
	 *
	 * @todo we might not want to count total rows when not needed...
	 * @todo fplanque: I am seriously considering putting $count_sql into 2nd or 3rd position. Any prefs?
	 * @todo dh> We might just use "SELECT SQL_CALC_FOUND_ROWS ..." and "FOUND_ROWS()"..! - available since MySQL 4 - would save one query just for counting!
	 *
	 * @param string SQL query
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string default ordering of columns (special syntax) if not specified in the URL params
	 *               example: -A-- will sort in ascending order on 2nd column
	 *               example: ---D will sort in descending order on 4th column
	 * @param integer Default number of lines displayed on one page (0 to disable paging; null to use $UserSettings/results_per_page)
	 * @param string SQL to get the total count of results
	 * @param boolean
	 * @param string|integer SQL query used to count the total # of rows
	 * 												- if integer, we'll use that as the count
	 * 												- if NULL, we'll try to COUNT(*) by ourselves
	 */
	function Results( $sql, $param_prefix = '', $default_order = '', $default_limit = NULL, $count_sql = NULL, $init_page = true )
	{
		parent::Table( NULL, $param_prefix );

		$this->sql = $sql;
		$this->count_sql = $count_sql;

		$this->init_limit_param( $default_limit );

		if( $init_page )
		{	// attribution of a page number
			$this->page_param = 'results_'.$this->param_prefix.'page';
			$this->page = param( $this->page_param, 'integer', 1, true );
		}

		$this->init_order_param( $default_order );
	}


	/**
	 * Initialize the limit param
	 *
	 * @param integer Default number of lines displayed on one page (0 to disable paging; null to use $UserSettings/results_per_page)
	 */
	function init_limit_param( $default_limit )
	{
		global $UserSettings;

		if( empty( $UserSettings ) )
		{
			$UserSettings = new UserSettings();
		}

		// attribution of a limit number
		$this->limit_param = 'results_'.$this->param_prefix.'per_page';
		$this->limit = param( $this->limit_param, 'integer', -1, true );
		if( !empty( $this->param_prefix ) &&
		    $this->limit > -1 &&
		    $this->limit != (int)$UserSettings->get( $this->limit_param ) )
		{	// Change a limit number in DB for current user and current list
			if( $this->limit == $UserSettings->get( 'results_per_page' ) || $this->limit == 0 )
			{	// Delete a limit number for current list if it equals a default value
				$UserSettings->delete( $this->limit_param );
				$this->limit = $UserSettings->get( 'results_per_page' );
			}
			else
			{	// Set a new value of limit number current list
				$UserSettings->set( $this->limit_param, $this->limit );
			}
			$UserSettings->dbupdate();
		}

		if( !empty( $this->param_prefix ) && $this->limit == -1 )
		{	// Set a limit number from DB
			if( $UserSettings->get( $this->limit_param ) > 0 )
			{	// Set a value for current list if it was already defined
				$this->limit = $UserSettings->get( $this->limit_param );
			}
		}

		if( $this->limit == -1 || empty( $this->limit ) )
		{	// Set a default value
			$this->limit = is_null( $default_limit ) ? $UserSettings->get( 'results_per_page' ) : $default_limit;
		}
	}


	/**
	 * Initialize the order param
	 *
	 * @param string default ordering of columns (special syntax) if not specified in the URL params
	 *               example: -A-- will sort in ascending order on 2nd column
	 *               example: ---D will sort in descending order on 4th column
	 */
	function init_order_param( $default_order )
	{
		global $UserSettings;

		if( empty( $UserSettings ) )
		{
			$UserSettings = new UserSettings();
		}

		// attribution of an order type
		$this->order_param = 'results_'.$this->param_prefix.'order';
		$this->order = param( $this->order_param, 'string', '', true );
		// remove symbols '-' from the end
		$this->order = preg_replace( '/(-*[AD]+)(-*)/i', '$1', $this->order );

		if( !empty( $this->param_prefix ) &&
		    !empty( $this->order ) &&
		    $this->order != $UserSettings->get( $this->order_param ) )
		{	// Change an order param in DB for current user and current list
			if( $this->order == $default_order )
			{	// Delete an order param for current list if it is a default value
				$UserSettings->delete( $this->order_param );
			}
			else
			{	// Set a new value of an order param for current list
				$UserSettings->set( $this->order_param, $this->order );
			}
			$UserSettings->dbupdate();
		}

		if( !empty( $this->param_prefix ) && empty( $this->order ) )
		{	// Set an order param from DB
			if( $UserSettings->get( $this->order_param ) != '' )
			{	// Set a value for current list if it was already defined
				$this->order = $UserSettings->get( $this->order_param );
			}
		}

		if( empty( $this->order ) )
		{	// Set a default value
			$this->order = $default_order;
		}
	}


	/**
	 * Reset the query -- EXPERIMENTAL
	 *
	 * Useful in derived classes such as ItemList to requery with a slighlty moidified filterset
	 */
	function reset()
	{
		$this->rows = NULL;
		$this->total_rows = NULL;
	}


	/**
	 * Rewind resultset
	 */
	function restart()
	{
		if( !isset( $this->total_rows ) )
		{ // Count total rows to be able to display pages correctly
			$this->count_total_rows( $this->count_sql );
		}

		// Make sure query has executed:
		$this->query( $this->sql );

		$this->current_idx = 0;

		$this->global_idx = (($this->page-1) * $this->limit) + $this->current_idx;

		$this->global_is_first = ( $this->global_idx <= 0 ) ? true : false;

		$this->global_is_last = ( $this->global_idx >= $this->total_rows-1 ) ? true : false;

		$this->current_group_ID = NULL;
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
	 */
	function query( $create_default_cols_if_needed = true, $append_limit = true, $append_order_by = true,
										$query_title = 'Results::Query()' )
	{
		global $DB, $Debuglog;
		if( !is_null( $this->rows ) )
		{ // Query has already executed:
			return;
		}

		if( empty( $this->sql ) )
		{ // the sql query is empty so we can't process it
			return;
		}

		if( isset( $this->total_rows ) && intval( $this->total_rows ) === 0 )
		{ // The number of total rows was counted and the sql query has no results
			return;
		}

		// Make sure we have colum definitions:
		if( is_null( $this->cols ) && $create_default_cols_if_needed )
		{ // Let's create default column definitions:
			$this->cols = array();

			if( !preg_match( '#^(SELECT.*?(\([^)]*?FROM[^)]*\).*)*)FROM#six', $this->sql, $matches ) )
			{
				debug_die( 'Results->query() : No SELECT clause!' );
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
				debug_die( 'No columns selected!' );
			}
		}


		// Make a copy of the SQL, that we may change and that gets executed:
		$sql = $this->sql;

		// Append ORDER clause if necessary:
		if( $append_order_by && ($orders = $this->get_order_field_list()) )
		{	// We have orders to append

			if( strpos( $sql, 'ORDER BY') === false )
			{ // there is no ORDER BY clause in the original SQL query
				$sql .= ' ORDER BY '.$orders.' ';
			}
			else
			{	// try to insert the chosen order at an existing '*' point
				$inserted_sql = preg_replace( '# \s ORDER \s+ BY (.+) \* #xi', ' ORDER BY $1 '.$orders, $sql );

				if( $inserted_sql != $sql )
				{	// Insertion ok:
					$sql = $inserted_sql;
				}
				else
				{	// No insert point found:
					// the chosen order must be appended to an existing ORDER BY clause
					$sql .= ', '.$orders;
				}
			}
		}
		else
		{	// Make sure there is no * in order clause:
			$sql = preg_replace( '# \s ORDER \s+ BY (.+) \* #xi', ' ORDER BY $1 ', $sql );
		}

		$add_limit = $append_limit && ! empty( $this->limit );

		if( $add_limit && ! $this->order_callbacks )
		{	// No callbacks to be called, so we can limit the line range to the requested page:
			$Debuglog->add( 'LIMIT requested and no callbacks - adding LIMIT to query.', 'results' );
			$sql .= ' LIMIT '.max( 0, ( $this->page - 1 ) * $this->limit ).', '.$this->limit;
		}

		// Execute query and store results
		$this->rows = $DB->get_results( $sql, OBJECT, $query_title );

		if ( ! $this->order_callbacks || ! $add_limit )
		{
			$Debuglog->add( 'Storing row count (no LIMIT or no callbacks)', 'results' );
			$this->result_num_rows = $DB->num_rows;
		}

		// Sort with callbacks:
		if( $this->order_callbacks )
		{
			$Debuglog->add( 'Sorting with callbacks.', 'results' );
			foreach( $this->order_callbacks as $order_callback )
			{
				#echo 'order_callback: '; var_dump($order_callback);

				$this->order_callback_wrapper_data = $order_callback; // to pass ASC/DESC param and callback itself through the wrapper to the callback

				if( empty($order_callback['use_rows']) )
				{ // default: instantiate objects for the callback:
					usort( $this->rows, array( &$this, 'order_callback_wrapper_objects' ) );
				}
				else
				{
					usort( $this->rows, array( &$this, 'order_callback_wrapper_rows' ) );
				}
			}

			if ( $add_limit )
			{
				$Debuglog->add( 'Callback sorting: LIMIT needed, extracting slice from array', 'results' );
				$this->rows = array_slice( $this->rows, max( 0, ( $this->page - 1 ) * $this->limit ), $this->limit );
				$this->result_num_rows = count( $this->rows );
			}
		}

		// Group by object property:
		if( ! empty($this->group_by_obj_prop) )
		{
			if( ! is_array($this->group_by_obj_prop) )
			{
				$this->group_by_obj_prop = array($this->group_by_obj_prop);
			}

			$this->mergesort( $this->rows, array( &$this, 'callback_group_by_obj_prop' ) );
		}

		// $Debuglog->add( 'rows on page='.$this->result_num_rows, 'results' );
	}


	/**
	 * Merge sort. This is required to not re-order items when sorting for e.g. grouping at the end.
	 *
	 * @see http://de2.php.net/manual/en/function.usort.php#38827
	 *
	 * @param array List of items to sort
	 * @param callback Sort function/method
	 */
	function mergesort(&$array, $cmp_function)
	{
		// Arrays of size < 2 require no action.
		if (count($array) < 2) return;
		// Split the array in half
		$halfway = count($array) / 2;
		$array1 = array_slice($array, 0, $halfway);
		$array2 = array_slice($array, $halfway);
		// Recurse to sort the two halves
		$this->mergesort($array1, $cmp_function);
		$this->mergesort($array2, $cmp_function);
		// If all of $array1 is <= all of $array2, just append them.
		if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
				$array = array_merge($array1, $array2);
				return;
		}
		// Merge the two sorted arrays into a single sorted array
		$array = array();
		$ptr1 = $ptr2 = 0;
		while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
				if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
						$array[] = $array1[$ptr1++];
				}
				else {
						$array[] = $array2[$ptr2++];
				}
		}
		// Merge the remainder
		while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
		while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];
		return;
	 }


	/**
	 * Callback, to sort {@link Result::$rows} according to {@link Result::$group_by_obj_prop}.
	 *
	 * @param array DB row for object A
	 * @param array DB row for object B
	 * @param integer Depth, used internally (you can group on a list of member properties)
	 * @return integer
	 */
	function callback_group_by_obj_prop( $row_a, $row_b, $depth = 0 )
	{
		$obj_prop = $this->group_by_obj_prop[$depth];

		$a = & $this->Cache->instantiate($row_a);
		$a_value = $a->$obj_prop;
		$b = & $this->Cache->instantiate($row_b);
		$b_value = $b->$obj_prop;

		if( $a_value == $b_value )
		{
			if( $depth+1 < count($this->group_by_obj_prop) )
			{
				return $this->callback_group_by_obj_prop( $row_a, $row_b, ($depth + 1) );
			}
			else
			{ // on the last level of grouping:
				return 0;
			}
		}

		// Sort empty group_by-values to the bottom
		if( empty($a_value) )
			return 1;
		if( empty($b_value) )
			return -1;

		return strcasecmp( $a_value, $b_value );
	}


	/**
	 * Wrapper method to {@link usort()}, which instantiates objects and passed them on to the
	 * order callback.
	 *
	 * @return integer
	 */
	function order_callback_wrapper_objects( $row_a, $row_b )
	{
		$a = $this->Cache->instantiate($row_a);
		$b = $this->Cache->instantiate($row_b);

		return (int)call_user_func( $this->order_callback_wrapper_data['callback'],
				$a, $b, $this->order_callback_wrapper_data['order'] );
	}


	/**
	 * Wrapper method to {@link usort()}, which passes the rows to the order callback.
	 *
	 * @return integer
	 */
	function order_callback_wrapper_rows( $row_a, $row_b )
	{
		return (int)call_user_func( $this->order_callback_wrapper_data['callback'],
				$row_a, $row_b, $this->order_callback_wrapper_data['order'] );
	}


	/**
	 * Get a list of IDs for current page
	 *
	 * @uses Results::$ID_col
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
	 * @uses Results::$ID_col
	 */
	function get_page_ID_array()
	{
		if( is_null( $this->page_ID_array ) )
		{
			$this->page_ID_array = array();
			// pre_dump( $this );
			if( $this->result_num_rows )
			{	// We have some rows to explore.
				// Fp> note: I don't understand why we can sometimes have: $this->rows = array{ [0]=>  NULL }  (found in Manual skin intro post testing)
				foreach( $this->rows as $row )
				{ // For each row/line:
					$this->page_ID_array[] = $row->{$this->ID_col};
				}
			}
		}

		return $this->page_ID_array;
	}


	/**
	 * Count the total number of rows of the SQL result (all pages)
	 *
	 * This is done by dynamically modifying the SQL query and forging a COUNT() into it.
	 *
	 * @todo dh> This might get done using SQL_CALC_FOUND_ROWS (I noted this somewhere else already)
	 * fp> I have a vague memory about issues with SQL_CALC_FOUND_ROWS. Maybe it was not returned accurate counts. Or maybe it didn't work with GROUP BY. Sth like that.
	 * dh> We could just try it. Adding some assertion in there, leaving the old
	 *     code in place. I'm quite certain that it is working correctly with
	 *     recent MySQL versions.
	 *
	 * @todo allow overriding?
	 * @todo handle problem of empty groups!
	 */
	function count_total_rows( $sql_count = NULL )
	{
		global $DB;

		if( is_integer( $sql_count ) )
		{	// we have a total already
			$this->total_rows = $sql_count;
		}
		else
		{ // we need to query
			if( is_null( $sql_count ) )
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
				 * On a un probl�me avec la recherche sur les soci�t�s
				 * si on fait un select count(*), �a sort un nombre de r�ponses �norme
				 * mais on ne sait pas pourquoi... la solution est de lister des champs dans le COUNT()
				 * MAIS malheureusement �a ne fonctionne pas pour d'autres requ�tes.
				 * L'id�al serait de r�ussir � isoler qu'est-ce qui, dans la requ�te SQL, provoque le comportement
				 * bizarre....
				 */
				// Tentative 1:
				// if( !preg_match( '#FROM(.*?)((WHERE|ORDER BY|GROUP BY) .*)?$#si', $sql_count, $matches ) )
				//  debug_die( "Can't understand query..." );
				// if( preg_match( '#(,|JOIN)#si', $matches[1] ) )
				// { // there was a coma or a JOIN clause in the FROM clause of the original query,
				// Tentative 2:
				// fplanque: je pense que la diff�rence est sur la pr�sence de DISTINCT ou non.
				// if( preg_match( '#\s DISTINCT \s#six', $sql_count, $matches ) )
				if( preg_match( '#\s DISTINCT \s+ ([A-Za-z_]+)#six', $sql_count, $matches ) )
				{ //
					// Get rid of any Aliases in colmun names:
					// $sql_count = preg_replace( '#\s AS \s+ ([A-Za-z_]+) #six', ' ', $sql_count );
					// ** We must use field names in the COUNT **
					//$sql_count = preg_replace( '#SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT( $1 ) FROM', $sql_count );

					//Tentative 3: we do a distinct on the first field only when counting:
					$sql_count = preg_replace( '#^ \s* SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT( DISTINCT '.$matches[1].' ) FROM', $sql_count );
				}
				else
				{ // Single table request: we must NOT use field names in the count.
					$sql_count = preg_replace( '#^ \s* SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT( * ) FROM', $sql_count );
				}


				// Make sure there is no ORDER BY clause at the end:
				$sql_count = preg_replace( '# \s ORDER \s+ BY .* $#xi', '', $sql_count );

				// echo $sql_count;
			}

			$this->total_rows = $DB->get_var( $sql_count, 0, 0, get_class($this).'::count_total_rows()' ); //count total rows
		}

		$this->total_pages = empty($this->limit) ? 1 : ceil($this->total_rows / $this->limit);

		// Make sure we're not requesting a page out of range:
		if( $this->page > $this->total_pages )
		{
			/*
				sam2kb> Isn't it better to display "Page not found" error instead?
				Current implementation is bad for SEO bacause it can potentially display an unlimited number of duplicate pages
				fp> on what public (not admin) url do we have this problem for example?
				sam2kb>fp Ideally there must be a list of options in Blog settings > SEO
				- display error
				- redirect to last page
				- display last page (this is what current version does)
				"paged" param: http://b2evolution.net/news/releases/?paged=99
				"page" param: http://b2evolution.net/news/2011/09/09/b2evolution-4-1-0-release?page=99
				fp> ok, the current implementation only has merit when the result list is not controlled by a plublic URL param.
				Otherwise the only 2 options should be 404 and 301.
				Also, teh detection code should probably NOT be here.
			*/
			$this->page = $this->total_pages;
		}
	}


	/**
	 * Get the number of total rows in the result
	 *
	 * @return integer
	 */
	function get_total_rows()
	{
		if( !isset( $this->total_rows ) )
		{ // Count total rows to be able to display pages correctly
			$this->count_total_rows( $this->count_sql );
		}

		return $this->total_rows;
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
	 * @param array|NULL
	 * @param array Fadeout settings array( 'key column' => array of values ) or 'session'
	 * @return int # of rows displayed
	 */
	function display( $display_params = NULL, $fadeout = NULL )
	{
		// Lazy fill $this->params:
		parent::display_init( $display_params, $fadeout );

		// -------------------------
		// Proceed with display:
		// -------------------------
		echo $this->params['before'];

		if( ! is_ajax_content() )
		{ // Display TITLE/FILTERS only if NO AJAX request

			// DISPLAY TITLE:
			if( isset( $this->title ) )
			{ // A title has been defined for this result set:
				echo $this->replace_vars( $this->params['head_title'] );
			}

			// DISPLAY FILTERS:
			$this->display_filters();
		}

		// Flush in order to show the filters before slow SQL query will be executed below
		evo_flush();

		// Make sure query has executed and we're at the top of the resultset:
		$this->restart();

		if( ! is_ajax_content() )
		{ // Display COL SELECTION only if NO AJAX request
			$this->display_colselect();
		}

		// START OF AJAX CONTENT:
		echo $this->replace_vars( $this->params['content_start'] );

			if( $this->total_pages == 0 )
			{ // There are no results! Nothing to display!

				// START OF LIST/TABLE:
				$this->display_list_start();

				// END OF LIST/TABLE:
				$this->display_list_end();
			}
			else
			{ // We have rows to display:

				// GLOBAL (NAV) HEADER:
				$this->display_nav( 'header' );

				// START OF LIST/TABLE:
				$this->display_list_start();

					// DISPLAY COLUMN HEADERS:
					$this->display_col_headers();

					// GROUP & DATA ROWS:
					$this->display_body();

					// Totals line
					$this->display_totals();

					// Functions
					$this->display_functions();

				// END OF LIST/TABLE:
				$this->display_list_end();

				// GLOBAL (NAV) FOOTER:
				$this->display_nav( 'footer' );
			}

		// END OF AJAX CONTENT:
		echo $this->params['content_end'];

		echo $this->params['after'];

		// Return number of rows displayed:
		return $this->current_idx;
	}


	/**
	 * Initialize things in order to be ready for displaying.
	 *
	 * This is useful when manually displaying, i-e: not by using Results::display()
 	 *
	 * @param array ***please document***
	 * @param array Fadeout settings array( 'key column' => array of values ) or 'session'
 	 */
	function display_init( $display_params = NULL, $fadeout = NULL )
	{
		// Lazy fill $this->params:
		parent::display_init( $display_params, $fadeout );

		// Make sure query has executed and we're at the top of the resultset:
		if( ( ! isset( $this->total_rows ) ) || ( ( intval( $this->total_rows ) > 0 ) && ( ! isset( $this->rows ) ) ) )
		{ // The result is not empty but the rows are not set yet, the query needs to be executed
			$this->restart();
		}
	}


	/**
	 * Display list/table body.
	 *
	 * This includes groups and data rows.
	 */
	function display_body()
	{
		// BODY START:
		$this->display_body_start();

		// Prepare data for grouping:
		$group_by_all = array();
		if( ! empty($this->group_by) )
		{
			$group_by_all['row'] = is_array($this->group_by) ? $this->group_by : array($this->group_by);
		}
		if( ! empty($this->group_by_obj_prop) )
		{
			$group_by_all['obj_prop'] = is_array($this->group_by_obj_prop) ? $this->group_by_obj_prop : array($this->group_by_obj_prop);
		}

		$this->current_group_count = array(); // useful in parse_col_content()


		foreach( $this->rows as $row )
		{ // For each row/line:

			/*
			 * GROUP ROW stuff:
			 */
			if( ! empty($group_by_all) )
			{	// We are grouping (by SQL and/or object property)...

				$group_depth = 0;
				$group_changed = false;
				foreach( $group_by_all as $type => $names )
				{
					foreach( $names as $name )
					{
						if( $type == 'row' )
						{
							$value = $row->$name;
						}
						elseif( $type == 'obj_prop' )
						{
							$this->current_Obj = $this->Cache->instantiate($row); // useful also for parse_col_content() below
							$value = $this->current_Obj->$name;
						}
						else debug_die( 'Invalid Results-group_by-type: '.var_export( $type, true ) );


						if( $this->current_group_ID[$group_depth] != $value )
						{ // Group changed here:
							$this->current_group_ID[$group_depth] = $value;

							if( ! isset($this->current_group_count[$group_depth]) )
							{
								$this->current_group_count[$group_depth] = 0;
							}
							else
							{
								$this->current_group_count[$group_depth]++;
							}

							// unset sub-group identifiers:
							for( $i = $group_depth+1, $n = count($this->current_group_ID); $i < $n; $i++ )
							{
								unset($this->current_group_ID[$i]);
							}

							$group_changed = true;
							break 2;
						}

						$group_depth++;
					}
				}

				if( $group_changed )
				{ // We have just entered a new group!

					echo $this->params['grp_line_start']; // TODO: dh> support grp_line_start_odd, grp_line_start_last, grp_line_start_odd_last - as defined in _adminUI_general.class.php

					$col_count = 0;
					foreach( $this->grp_cols as $grp_col )
					{ // For each column:

						if( isset( $grp_col['td_class'] ) )
						{	// We have a class for the total column
							$class = $grp_col['td_class'];
						}
						else
						{	// We have no class for the total column
							$class = '';
						}

						if( ($col_count==0) && isset($this->params['grp_col_start_first']) )
						{ // Display first column column start:
							$output = $this->params['grp_col_start_first'];

							// Add the total column class in the grp col start first param class:
							$output = str_replace( '$class$', $class, $output );
						}
						elseif( ($col_count==count($this->grp_cols)-1) && isset($this->params['grp_col_start_last']) )
						{ // Last column can get special formatting:
							$output = $this->params['grp_col_start_last'];

							// Add the total column class in the grp col start end param class:
							$output = str_replace( '$class$', $class, $output );
						}
						else
						{ // Display regular column start:
							$output = $this->params['grp_col_start'];

							// Replace the "class_attrib" in the grp col start param by the td column class
							$output = str_replace( '$class_attrib$', 'class="'.$class.'"', $output );
						}

						if( isset( $grp_col['td_colspan'] ) )
						{
							$colspan = $grp_col['td_colspan'];
							if( $colspan < 0 )
							{ // We want to substract columns from the total count
								$colspan = $this->nb_cols + $colspan;
							}
							elseif( $colspan == 0 )
							{ // use $nb_cols
								$colspan = $this->nb_cols;
							}
							$output = str_replace( '$colspan_attrib$', 'colspan="'.$colspan.'"', $output );
						}
						else
						{ // remove non-HTML attrib:
							$output = str_replace( '$colspan_attrib$', '', $output );
						}

						// Contents to output:
						$output .= $this->parse_col_content( $grp_col['td'] );
						//echo $output;
						eval( "echo '$output';" );

						echo '</td>';
						$col_count++;
					}

					echo $this->params['grp_line_end'];
				}
			}


			/*
			 * DATA ROW stuff:
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


			// Check for fadeout
			$fadeout_line = false;
			if( !empty( $this->fadeout_array ) )
			{
				foreach( $this->fadeout_array as $key => $crit )
				{
					// echo 'fadeout '.$key.'='.$crit;
					if( isset( $row->$key ) && in_array( $row->$key, $crit ) )
					{ // Col is in the fadeout list
						// TODO: CLEAN THIS UP!
						$fadeout_line = true;
						break;
					}
				}
			}

			// LINE START:
			$this->display_line_start( $this->current_idx == count($this->rows)-1, $fadeout_line );

			foreach( $this->cols as $col )
			{ // For each column:

				// COL START:
				if ( ! empty($col['extra']) )
				{
					// array of extra params $col['extra']
					$this->display_col_start( $col['extra'] );
				}
				else
				{
					$this->display_col_start();
				}


				// Contents to output:
				$output = $this->parse_col_content( $col['td'] );
				#pre_dump( '{'.$output.'}' );

				$out = eval( "return '$output';" );
				// fp> <input> is needed for checkboxes in the Blog User/Group permissions table > advanced
				echo ( trim(strip_tags($out,'<img><input>')) === '' ? '&nbsp;' : $out );

				// COL START:
				$this->display_col_end();
			}

			// LINE END:
			$this->display_line_end();

			$this->next_idx();
		}

		// BODY END:
		$this->display_body_end();
	}


	/**
	 * Display totals line if set.
	 */
	function display_totals()
	{
		$total_enable = false;

		// Search if we have totals line to display:
		foreach( $this->cols as $col )
		{
			if( isset( $col['total'] ) )
			{	// We have to display a totals line
				$total_enable = true;
				break;
			}
		}

		if( $total_enable )
		{ // We have to dispaly a totals line

			echo $this->params['tfoot_start'];
			// <tr>
			echo $this->params['total_line_start'];

			$loop = 0;

			foreach( $this->cols as $col )
			{
				if( isset( $col['total_class'] ) )
				{	// We have a class for the total column
					$class = $col['total_class'];
				}
				else
				{	// We have no class for the total column
					$class = '';
				}

				if( $loop == 0)
				{	// The column is the first
					$output = $this->params['total_col_start_first'];
					// Add the total column class in the total col start first param class:
					$output = str_replace( '$class$', $class, $output );
 				}
				elseif( $loop ==( count( $this->cols ) -1 ) )
				{	// The column is the last
					$output = $this->params['total_col_start_last'];
					// Add the total column class in the total col start end param class:
					$output = str_replace( '$class$', $class, $output );
				}
				else
				{
					$output = $this->params['total_col_start'];
					// Replace the "class_attrib" in the total col start param by the total column class
					$output = str_replace( '$class_attrib$', 'class="'.$class.'"', $output );
				}

				// <td class="....">
				echo $output;

				if( isset( $col['total'] ) )
				{	// The column has a total set, so display it:
					$output = $col['total'];
					$output = $this->parse_col_content( $output );
					eval( "echo '$output';" );
				}
				else
				{	// The column has no total
					echo '&nbsp;';
				}
				// </td>
				echo  $this->params['total_col_end'];

				$loop++;
			}
			// </tr>
			echo $this->params['total_line_end'];
			echo $this->params['tfoot_end'];
		}
	}


	/**
   * Display the functions
   */
	function display_functions()
	{
		if( empty( $this->functions_area ) )
		{	// We don't want to display a functions section:
			return;
		}

		echo $this->replace_vars( $this->params['functions_start'] );

		if( !empty( $this->functions_area['callback'] ) )
		{	// We want to display functions:
			if( is_array( $this->functions_area['callback'] ) )
			{	// The callback is an object function
				$obj_name = $this->functions_area['callback'][0];
				if( $obj_name != 'this' )
				{	// We need the global object
					global $$obj_name;
				}
				$func = $this->functions_area['callback'][1];

				if( isset( $this->Form ) )
				{	// There is a created form
					$$obj_name->$func( $this->Form );
				}
				else
				{ // There is not a created form
					$$obj_name->$func();
				}
			}
			else
			{	// The callback is a function
				$func = $this->functions_area['callback'];

				if( isset( $this->Form ) )
				{	// There is a created form
					$func( $this->Form );
				}
				else
				{ // There is not a created form
					$func();
				}
			}

		}

		echo $this->params['functions_end'];
	}


	/**
	 * Display navigation text, based on template.
	 *
	 * @param string template: 'header' or 'footer'
	 */
	function display_nav( $template )
	{
		if( empty($this->limit) && isset($this->params[$template.'_text_no_limit']) )
		{	// No LIMIT (there's always only one page)
			$navigation = $this->params[$template.'_text_no_limit'];
		}
		elseif( ( $this->total_pages <= 1 ) )
		{	// Single page (we probably don't want to show navigation in this case)
			$navigation = $this->replace_vars( $this->params[$template.'_text_single'] );
		}
		else
		{	// Several pages
			$navigation = $this->replace_vars( $this->params[$template.'_text'] );
		}

		if( !empty( $navigation ) )
		{	// Display navigation
			echo $this->params[$template.'_start'];

			echo $navigation;

			echo $this->params[$template.'_end'];
		}
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

		$col_sort_values['order_asc'] = regenerate_url( $this->order_param, $this->order_param.'='.$order_asc, $this->params['page_url'] );
		$col_sort_values['order_desc'] = regenerate_url( $this->order_param, $this->order_param.'='.$order_desc, $this->params['page_url'] );


		if( !$col_sort_values['current_order'] && isset( $this->cols[$col_idx]['default_dir'] ) )
		{	// There is no current order on this column and a default order direction is set for it
			// So set a default order direction for it

			if( $this->cols[$col_idx]['default_dir'] == 'A' )
			{	// The default order direction is A, so set its toogle  order to the order_asc
				$col_sort_values['order_toggle'] = $col_sort_values['order_asc'];
			}
			else
			{ // The default order direction is A, so set its toogle order to the order_desc
				$col_sort_values['order_toggle'] = $col_sort_values['order_desc'];
			}
		}
		elseif( $col_sort_values['current_order'] == 'ASC' )
		{	// There is an ASC current order on this column, so set its toogle order to the order_desc
			$col_sort_values['order_toggle'] = $col_sort_values['order_desc'];
		}
		else
		{ // There is a DESC or NO current order on this column,  so set its toogle order to the order_asc
			$col_sort_values['order_toggle'] = $col_sort_values['order_asc'];
		}

		return $col_sort_values;
	}


	/**
	 * Returns order field list add to SQL query:
	 * @return string May be empty
	 */
	function get_order_field_list()
	{
		if( is_null( $this->order_field_list ) )
		{ // Order list is not defined yet
			if( ( !empty( $this->order ) ) && ( !empty( $this->cols ) ) && ( substr( $this->order, 0, 1 ) == '/' ) )
			{ // order is set in format '/order_field_name/A' or '/order_field_name/D'
				$order_parts = explode( '/', $this->order );
				$this->order = '';
				if( ( count( $order_parts ) == 3 ) && ( ( $order_parts[2] == 'A' ) || ( $order_parts[2] == 'D' ) ) )
				{
					foreach( $this->cols as $col )
					{ // iterate thrugh columns and find matching column name
						if( isset( $col['order'] ) && ( $col['order'] == $order_parts[1] ) )
						{ // we have found the requested orderable column
							$this->order .= $order_parts[2];
							break;
						}
						else
						{
							$this->order .= '-';
						}
					}
				}
			}

			if( empty( $this->order ) )
			{ // We have no user provided order:
				if( empty( $this->cols ) )
				{	// We have no columns to pick an automatic order from:
					// echo 'Can\'t determine automatic order';
					return '';
				}

				foreach( $this->cols as $col )
				{
					if( isset( $col['order'] ) || isset( $col['order_objects_callback'] ) || isset( $col['order_rows_callback'] ) )
					{ // We have found the first orderable column:
						$this->order .= 'A';
						break;
					}
					else
					{
						$this->order .= '-';
					}
				}
			}

			// echo ' order='.$this->order.' ';

			$orders = array();
			$this->order_callbacks = array();

			for( $i = 0; $i <= strlen( $this->order ); $i++ )
			{	// For each position in order string:
				if( isset( $this->cols[$i]['order'] ) )
				{	// if column is sortable:
					# Add ASC/DESC to any order cols (except if there is ASC/DESC given already, which is used to order NULL values always at the end)
					switch( substr( $this->order, $i, 1 ) )
					{
						case 'A':
							$orders[] = preg_replace( '~(?<!asc|desc)\s*,~i', ' ASC,', $this->cols[$i]['order'] ).' ASC';
							break;

						case 'D':
							$orders[] = preg_replace( '~(asc|desc)?\s*,~i', ' DESC,', $this->cols[$i]['order'] ).' DESC';
							break;
					}
				}

				if( isset( $this->cols[$i]['order_objects_callback'] ) )
				{	// if column is sortable by object callback:
					switch( substr( $this->order, $i, 1 ) )
					{
						case 'A':
							$this->order_callbacks[] = array(
									'callback' => $this->cols[$i]['order_objects_callback'],
									'use_rows' => false,
									'order'=>'ASC' );
							break;

						case 'D':
							$this->order_callbacks[] = array(
									'callback' => $this->cols[$i]['order_objects_callback'],
									'use_rows' => false,
									'order' => 'DESC' );
							break;
					}
				}

				if( isset( $this->cols[$i]['order_rows_callback'] ) )
				{	// if column is sortable by callback:
					switch( substr( $this->order, $i, 1 ) )
					{
						case 'A':
							$this->order_callbacks[] = array(
									'callback' => $this->cols[$i]['order_rows_callback'],
									'use_rows' => true,
									'order'=>'ASC' );
							break;

						case 'D':
							$this->order_callbacks[] = array(
									'callback' => $this->cols[$i]['order_rows_callback'],
									'use_rows' => true,
									'order' => 'DESC' );
							break;
					}
				}
			}
			$this->order_field_list = implode( ',', $orders );

			#pre_dump( $this->order_field_list );
			#pre_dump( $this->order_callbacks );
		}
		return $this->order_field_list;	// May be empty
	}


	/**
	 * Handle variable subtitutions for column contents.
	 *
	 * This is one of the key functions to look at when you want to use the Results class.
	 * - $var$
	 * - £var£ - replaced by its utf-8 hex character (c2 a3)
	 * - ²var² - replaced by its utf-8 hex character (c2 b2)
	 * - #var#
	 * - {row}
	 * - %func()%
	 * - ~func()~
	 * - ¤func()¤ - @deprecated by ~func()~ - replaced by its utf-8 hex character (c2 a4)
	 */
	function parse_col_content( $content )
	{
		// Make variable substitution for STRINGS:
		$content = preg_replace( '#\$ (\w+) \$#ix', "'.format_to_output(\$row->$1).'", $content );
		// Make variable substitution for URL STRINGS:
		$content = preg_replace( '#\x{c2}\x{a3} (\w+) \x{c2}\x{a3}#ix', "'.format_to_output(\$row->$1, 'urlencoded').'", $content );
		// Make variable substitution for escaped strings:
		$content = preg_replace( '#\x{c2}\x{b2} (\w+) \x{c2}\x{b2}#ix', "'.evo_htmlentities(\$row->$1).'", $content );
		// Make variable substitution for RAWS:
		$content = preg_replace( '!\# (\w+) \#!ix', "\$row->$1", $content );
		// Make variable substitution for full ROW:
		$content = str_replace( '{row}', '$row', $content );
		// Make callback function substitution:
		$content = preg_replace( '#% (.+?) %#ix', "'.$1.'", $content );
		// Make variable substitution for intanciated Object:
		$content = str_replace( '{Obj}', "\$this->current_Obj", $content );
		// Make callback for Object method substitution:
		$content = preg_replace( '#@ (.+?) @#ix', "'.\$this->current_Obj->$1.'", $content );
		// Sometimes we need embedded function call, so we provide a second sign:
		$content = preg_replace( '#~ (.+?) ~#ix', "'.$1.'", $content );

		// @deprecated by ~func()~. Left here for backward compatibility only, to be removed in future versions.
		$content = preg_replace( '#\x{c2}\x{a4} (.+?) \x{c2}\x{a4}#ix', "'.$1.'", $content );

		// Make callback function move_icons for orderable lists // dh> what does it do?
		$content = str_replace( '{move}', "'.\$this->move_icons().'", $content );

		$content = str_replace( '{CUR_IDX}', $this->current_idx, $content );
		$content = str_replace( '{TOTAL_ROWS}', $this->total_rows, $content );

		return $content;
	}


	/**
	 *
	 * @todo Support {@link Results::$order_callbacks}
	 */
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
				$r = action_icon( T_( 'Sort by order' ), 'nomove_up', regenerate_url( 'action', 'action=sort_by_order' ) );
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
		// echo '['.$matches[1].']';
		switch( $matches[1] )
		{
			case 'start' :
				return ( ($this->page-1)*$this->limit+1 );

			case 'end' :
				return ( min( $this->total_rows, $this->page*$this->limit ) );

			case 'total_rows' :
				//total number of rows in the sql query
				return ( $this->total_rows );

			case 'page' :
				//current page number
				return ( $this->page );

			case 'total_pages' :
				//total number of pages
				return ( $this->total_pages );

			case 'prev' :
				// inits the link to previous page
				if ( $this->page <= 1 )
				{
					return $this->params['no_prev_text'];
				}
				$r = '';
				if( isset( $this->params['page_item_before'] ) )
				{
					$r .= $this->params['page_item_before'];
				}
				$r .= '<a href="'
						.regenerate_url( $this->page_param, (($this->page > 2) ? $this->page_param.'='.($this->page-1) : ''), $this->params['page_url'] ).'"';
				$attr_rel = array();
				if( $this->nofollow_pagenav )
				{ // We want to NOFOLLOW page navigation
					$attr_rel[] = 'nofollow';
				}
				// Add attribute rel="prev" for previous page
				$attr_rel[] = 'prev';
				if( ! empty( $attr_rel ) )
				{
					$r .= ' rel="'.implode( ' ', $attr_rel ).'"';
				}
				if( ! empty( $this->params['prev_class'] ) )
				{ // Add "class" for prev link
					$r .= ' class="'.$this->params['prev_class'].'"';
				}
				$r .= '>'.$this->params['prev_text'].'</a>';
				if( isset( $this->params['page_item_after'] ) )
				{
					$r .= $this->params['page_item_after'];
				}
				return $r;

			case 'next' :
				// inits the link to next page
				if( $this->page >= $this->total_pages )
				{
					return $this->params['no_next_text'];
				}
				$r = '';
				if( isset( $this->params['page_item_before'] ) )
				{
					$r .= $this->params['page_item_before'];
				}
				$r .= '<a href="'
						.regenerate_url( $this->page_param, $this->page_param.'='.($this->page+1), $this->params['page_url'] ).'"';
				$attr_rel = array();
				if( $this->nofollow_pagenav )
				{ // We want to NOFOLLOW page navigation
					$attr_rel[] = 'nofollow';
				}
				// Add attribute rel="next" for next page
				$attr_rel[] = 'next';
				if( ! empty( $attr_rel ) )
				{
					$r .= ' rel="'.implode( ' ', $attr_rel ).'"';
				}
				if( ! empty( $this->params['next_class'] ) )
				{ // Add "class" for prev link
					$r .= ' class="'.$this->params['next_class'].'"';
				}
				$r .= '>'.$this->params['next_text'].'</a>';
				if( isset( $this->params['page_item_after'] ) )
				{
					$r .= $this->params['page_item_after'];
				}
				return $r;

			case 'list' :
				//inits the page list
				return $this->page_list( $this->first(), $this->last(), $this->params['page_url'] );

			case 'scroll_list' :
				//inits the scrolling list of pages
				return $this->page_scroll_list();

			case 'first' :
				//inits the link to first page
				return $this->display_first( $this->params['page_url'] );

			case 'last' :
				//inits the link to last page
				return $this->display_last( $this->params['page_url'] );

			case 'list_prev' :
				//inits the link to previous page range
				return $this->display_prev( $this->params['page_url'] );

			case 'list_next' :
				//inits the link to next page range
				return $this->display_next( $this->params['page_url'] );

			case 'page_size' :
				//inits the list to select page size
				return $this->display_page_size( $this->params['page_url'] );

			case 'prefix' :
				//prefix
				return $this->param_prefix;

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
	function display_first( $page_url = '' )
	{
		if( $this->first() > 1 )
		{ //the list doesn't contain the first page
			$r = '';
			if( isset( $this->params['page_item_before'] ) )
			{
				$r .= $this->params['page_item_before'];
			}

			$r .= '<a href="'.regenerate_url( $this->page_param, '', $page_url ).'">1</a>';

			if( isset( $this->params['page_item_after'] ) )
			{
				$r .= $this->params['page_item_after'];
			}

			return $r;
		}
		else
		{ //the list already contains the first page
			return NULL;
		}
	}


	/**
	 * returns the link to the last page, if necessary
	 */
	function display_last( $page_url = '' )
	{
		if( $this->last() < $this->total_pages )
		{ //the list doesn't contain the last page
			$r = '';
			if( isset( $this->params['page_item_before'] ) )
			{
				$r .= $this->params['page_item_before'];
			}

			$r .= '<a href="'.regenerate_url( $this->page_param, $this->page_param.'='.$this->total_pages, $page_url ).'">'.$this->total_pages.'</a>';

			if( isset( $this->params['page_item_after'] ) )
			{
				$r .= $this->params['page_item_after'];
			}

			return $r;
		}
		else
		{ //the list already contains the last page
			return NULL;
		}
	}


	/**
	 * returns a link to previous pages, if necessary
	 */
	function display_prev( $page_url = '' )
	{
		if( $this->first() > 2 )
		{ //the list has to be displayed
			$page_no = ceil($this->first()/2);

			$r = '';
			if( isset( $this->params['page_item_before'] ) )
			{
				$r .= $this->params['page_item_before'];
			}

			$r .= '<a href="'.regenerate_url( $this->page_param, $this->page_param.'='.$page_no, $page_url ).'">'
								.$this->params['list_prev_text'].'</a>';

			if( isset( $this->params['page_item_after'] ) )
			{
				$r .= $this->params['page_item_after'];
			}

			return $r;
		}

	}


	/**
	 * returns a link to next pages, if necessary
	 */
	function display_next( $page_url = '' )
	{
		if( $this->last() < $this->total_pages-1 )
		{ //the list has to be displayed
			$page_no = $this->last() + floor(($this->total_pages-$this->last())/2);

			$r = '';
			if( isset( $this->params['page_item_before'] ) )
			{
				$r .= $this->params['page_item_before'];
			}

			$r .= '<a href="'.regenerate_url( $this->page_param,$this->page_param.'='.$page_no, $page_url ).'">'
								.$this->params['list_next_text'].'</a>';

			if( isset( $this->params['page_item_after'] ) )
			{
				$r .= $this->params['page_item_after'];
			}

			return $r;
		}
	}


	/**
	 * returns a list to select page size
	 */
	function display_page_size( $page_url = '' )
	{
		// Don't allow to change a page size:
		if( $this->total_rows <= 10 || // if total number of rows is always less then min page size
		    empty( $this->param_prefix ) ) // the lists without defined param_prefix
		{
			return;
		}

		$page_size_options = array(
				'10' => sprintf( T_('%s lines'), '10' ),
				'20' => sprintf( T_('%s lines'), '20' ),
				'30' => sprintf( T_('%s lines'), '30' ),
				'40' => sprintf( T_('%s lines'), '40' ),
				'50' => sprintf( T_('%s lines'), '50' ),
				'100' => sprintf( T_('%s lines'), '100' ),
				'200' => sprintf( T_('%s lines'), '200' ),
				'500' => sprintf( T_('%s lines'), '500' ),
			);

		$default_page_size_value = '0';
		if( is_logged_in() )
		{	// Get default page size for current user
			global $UserSettings;

			$default_page_size_value = $UserSettings->get( 'results_per_page' );
			$default_page_size_option = array( '0' => sprintf( T_('Default (%s lines)'), $default_page_size_value ) );
			$page_size_options = $default_page_size_option + $page_size_options;
		}

		$html = '<small>';
		$html .= T_('Lines per page:');

		$html .= ' <select name="'.$this->limit_param.'" onchange="location.href=\''.regenerate_url( $this->page_param.','.$this->limit_param, $this->limit_param, $page_url ).'=\'+this.value" class="form-control">';
		foreach( $page_size_options as $value => $name )
		{
			$selected = '';
			if( $this->limit == $value && $value != $default_page_size_value )
			{
				$selected = ' selected="selected"';
			}
			$html .= '<option value="'.$value.'"'.$selected.'>'.$name.'</option>';
		}
		$html .= '</select>';

		$html .= '</small>';

		return $html;
	}


	/**
	 * Returns the page link list under the table
	 */
	function page_list( $min, $max, $page_url = '' )
	{
		$i = 0;
		$list = '';

		$page_prev_i = $this->page - 1;
		$page_next_i = $this->page + 1;
		for( $i=$min; $i<=$max; $i++)
		{
			if( isset( $this->params['page_item_before'] ) )
			{
				$list .= $this->params['page_item_before'];
			}

			if( $i == $this->page )
			{ //no link for the current page
				if( ! isset( $this->params['page_current_template'] ) )
				{
					$this->params['page_current_template'] = '<strong class="current_page">$page_num$</strong> ';
				}
				$list .= str_replace( '$page_num$', $i, $this->params['page_current_template'] );
			}
			else
			{ //a link for non-current pages
				$list .= '<a href="'
					.regenerate_url( $this->page_param, ( $i>1 ? $this->page_param.'='.$i : '' ), $page_url ).'"';
				$attr_rel = array();
				if( $this->nofollow_pagenav )
				{ // We want to NOFOLLOW page navigation
					$attr_rel[] = 'nofollow';
				}
				if( $page_prev_i == $i )
				{ // Add attribute rel="prev" for previous page
					$attr_rel[] = 'prev';
				}
				elseif( $page_next_i == $i )
				{ // Add attribute rel="next" for next page
					$attr_rel[] = 'next';
				}
				if( ! empty( $attr_rel ) )
				{
					$list .= ' rel="'.implode( ' ', $attr_rel ).'"';
				}
				$list .= '>'.$i.'</a> ';
			}

			if( isset( $this->params['page_item_after'] ) )
			{
				$list .= $this->params['page_item_after'];
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
	 * @return boolean true if empty
	 */
	function display_if_empty( $params = array() )
	{
		if( $this->result_num_rows == 0 )
		{
			// Make sure we are not missing any param:
			$params = array_merge( array(
					'before'      => '<p class="msg_nothing">',
					'after'       => '</p>',
					'msg_empty'   => T_('Sorry, there is nothing to display...'),
				), $params );

			echo $params['before'];
			echo $params['msg_empty'];
			echo $params['after'];

			return true;
		}
		return false;
	}

}


// _________________ Helper callback functions __________________

function conditional( $condition, $on_true, $on_false = '' )
{
	if( $condition )
	{
		return $on_true;
	}
	else
	{
		return $on_false;
	}
}

?>
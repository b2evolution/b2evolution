<?php
/**
 * This file implements the Results class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI grants François PLANQUE the right to license
 * PROGIDISTRI's contributions to this file and the b2evolution project
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

/**
 * Results class
 */
class Results
{
	var $DB;
	var $sql;
	var $total_rows;
	var $limit;
	var $page;
	var $total_pages;
	var $rows = NULL;
	var $cols = NULL;
	/**
	 * Array of headers for each column
	 *
	 * All defs are optional.
	 */
	var $col_headers = NULL;
	/**
	 * Array of fieldnames to sort on when clicking on each column header
 	 *
	 * All defs are optional. A column with no def will be displayed as NOT sortable.
	 */
	var $col_orders = NULL;
	/**
	 * Array of column start markup for each column.
	 *
	 * All defs are optional. A column with no def will de diaplyed using
	 * the default defs from Results::params, that is to say, one of these:
	 *   - $this->params['col_start_first'];
	 *   - $this->params['col_start_last'];
	 *   - $this->params['col_start'];
	 */
	var $col_starts = NULL;
	var $params = NULL;

	/**
	 * Number of rows in result set for current page.
	 */
	var $result_num_rows = NULL;


 	/**
	 * Current object idx in array:
	 */
	var $current_idx = 0;


	/**
	 * Constructor
	 *
	 *
	 * @todo we might not want to count total rows when not needed...
	 *
	 * @param string SQL query
	 * @param integer number of lines displayed on one screen
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param integer current page to display
	 * @param string ordering of columns (special syntax)
	 */
	function Results( $sql, $limit = 20, $param_prefix = '', $page = NULL, $order = NULL )
	{
		global $DB;
		$this->DB = & $DB;
 		$this->sql = $sql;
		$this->limit = $limit;
		$this->param_prefix = $param_prefix;

		// Count total rows:
		$this->count_total_rows();

    $this->total_pages = ceil($this->total_rows / $this->limit);

		if( is_null($page) )
		{ //attribution of a page number
			$page = param(  $param_prefix.'page', 'integer', 1, true );
		}
		$this->page = min( $page, $this->total_pages ) ;

		if( is_null($order) )
		{ //attribution of an order type
			$order = param( $param_prefix.'order', 'string', '', true );
		}
		$this->order = $order;
	}


	/**
	 * Rewind resultset
	 *
	 * {@internal DataObjectList::restart(-) }}
	 */
	function restart()
	{
		// Make sure query has exexuted:
		$this->query( $this->sql );

		$this->current_idx = 0;
	}


	/**
	 * Run the query now!
	 *
	 * Will only run if it has not executed before.
	 */
	function query( $sql )
	{
		if( is_null( $this->rows ) )
		{	// Query has not executed yet:

			$this->asc = ' ASC ';

			if( $this->order !== '' )
			{ //$order is not an empty string
				$this->asc = strstr( $this->order, 'A' ) ? ' ASC' : ' DESC';
			}
			elseif( isset( $this->col_orders ) )
			{
				$pos = $this->first_defined_order( $this->col_orders );
				for( $i = 0; $i < $pos; $i++)
				{
					$this->order .= '-';
				}
				$this->order .= strstr( $this->asc, 'A' ) ? 'A' : 'D';
			}

			if( strpos( $this->sql, 'ORDER BY') === false )
			{ //there is no ORDER BY clause in the original SQL query
				$this->sql.=$this->order($this->order, $this->asc);
			}
			else
			{ //the chosen order must be inserted into an existing ORDER BY clause
				$split = split( 'ORDER BY', $this->sql );
				$this->sql = $split['0']
					.( ( $this->order($this->order, $this->asc) !== '' ) ? $this->order($this->order, $this->asc).', ' : ' ORDER BY ' )
					.$split['1'];
			}

			// Limit to requested page
			$sql = $this->sql.' LIMIT '.max(0, ($this->page-1)*$this->limit).', '.$this->limit;

			// Execute query and store results
			$this->rows = $this->DB->get_results( $sql );

			// Store row count
	 		$this->result_num_rows = $this->DB->num_rows;

   		// echo 'rows on page='.$this->result_num_rows;
		}
	}


	/**
	 * Count the number of rows of the SQL result
	 *
	 * This is done by dynamicallt modifying the SQL query and forging a COUNT() into it.
	 */
	function count_total_rows()
	{
		if( is_null($this->sql) )
		{	// We may want to remove this later...
			$this->total_rows = 0;
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
		if( preg_match( '#\s DISTINCT \s#six', $sql_count ) )
		{ //
			// Get rid of any Aliases in colmun names:
			$sql_count = preg_replace( '#\s AS \s+ ([A-Za-z_]+) #six', ' ', $sql_count );
			// ** We must use field names in the COUNT **
			$sql_count = preg_replace( '#SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT( $1 ) FROM', $sql_count );
		}
		else
		{	// Single table request: we must NOT use field names in the count.
			$sql_count = preg_replace( '#SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT( * ) FROM', $sql_count );
		}

		// echo $sql_count;

		$this->total_rows = $this->DB->get_var( $sql_count ); //count total rows
	}


	/**
	 * Display paged list/table based on object parameters
	 *
	 * This is the meat of this class!
	 *
	 * @return int # of rows displayed
	 */
	function display( $display_params = NULL )
	{
		if( !is_null($display_params) )
		{	// Use passed params:
			$this->params = & $display_params;
		}
		elseif( empty( $this->params ) )
		{	// Set default params:
			$this->params = array(
				'before' => '<div class="results">',
					'header_start' => '<div class="results_nav">',
					'header_text' => '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$',
					'header_text_single' => T_('1 page'),
					'header_end' => '</div>',
					'title_start' => "<div>\n",
					'title_end' => "</div>\n",
					'list_start' => '<table class="grouped" cellspacing="0">'."\n\n",
						'head_start' => "<thead><tr>\n",
							'head_title_start' => '<th colspan="$nb_cols$">'."\n",
							'head_title_end' => "</th></tr>\n\n<tr>\n",
							'colhead_start' => '<th>',
							'colhead_start_first' => '<th class="firstcol">',
							'colhead_start_last' => '<th class="lastcol">',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => '<img src="../admin/img/grey_arrow_up.gif" alt="A" title="'.T_('Ascending order').'" height="12" width="11" />',
							'sort_asc_on' => '<img src="../admin/img/black_arrow_up.gif" alt="A" title="'.T_('Ascending order').'" height="12" width="11" />',
							'sort_desc_off' => '<img src="../admin/img/grey_arrow_down.gif" alt="D" title="'.T_('Descending order').'" height="12" width="11" />',
							'sort_desc_on' => '<img src="../admin/img/black_arrow_down.gif" alt="D" title="'.T_('Descending order').'" height="12" width="11" />',
							'basic_sort_off' => '<img src="../admin/img/basic_sort_off.gif" width="16" height="16" />',
							'basic_sort_asc' => '<img src="../admin/img/basic_sort_asc.gif" width="16" height="16" />',
							'basic_sort_desc' => '<img src="../admin/img/basic_sort_desc.gif" width="16" height="16" />',
						'head_end' => "</tr></thead>\n\n",
						'tfoot_start' => "<tfoot>\n",
						'tfoot_end' => "</tfoot>\n\n",
						'body_start' => "<tbody>\n",
							'line_start' => "<tr>\n",
							'line_start_odd' => '<tr class="odd">'."\n",
							'line_start_last' => '<tr class="lastline">'."\n",
							'line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'col_start' => '<td>',
								'col_start_first' => '<td class="firstcol">',
								'col_start_last' => '<td class="lastcol">',
								'col_end' => "</td>\n",
							'line_end' => "</tr>\n\n",
						'body_end' => "</tbody>\n\n",
					'list_end' => "</table>\n\n",
					'footer_start' => '<div class="results_nav">',
					'footer_text' => /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
														'<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$'
														/* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => T_('1 page'),
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "</div>\n\n",
					'no_results' => T_('No results.'),
				'after' => '</div>',
				'sort_type' => 'basic'
				);
		}

		echo $this->params['before'];

		if( $this->total_pages == 0 )
		{	// There are no results! Nothing to display!
			echo $this->params['no_results'];
			echo $this->params['after'];
			return 0;
		}


		// Make sure query has executed and we're at the top of the resultset:
		$this->restart();


		if( is_null( $this->cols ) )
		{	// Let's create default column definitions:
			$this->cols = array();

			if( !preg_match( '#SELECT \s+ (.+?) \s+ FROM#six', $this->sql, $matches ) ) die( 'No SELECT clause!' );

			$select = $matches[1].',';	// Add a , to normalize list

			if( !($nb_cols = preg_match_all( '#(\w+) \s* ,#six', $select, $matches )) )
				die( 'No columns selected!' );

			for( $i=0; $i<$nb_cols; $i++ )
			{
			 	$col = $matches[1][$i];

				$this->cols[] = '$'.$col.'$';
				echo $col;
			}
		}


		echo $this->params['header_start'];
		$this->nav_text( $this->params['header_text'], $this->params['header_text_single'] );
   	echo $this->params['header_end'];

		/*
		echo $this->params['title_start'];
		echo $this->title;
		echo $this->params['title_end'];
		*/

		echo $this->params['list_start'];

		// -----------------------------
		// COLUMN HEADERS:
		// -----------------------------
		if( !is_null( $this->col_headers ) )
		{	// We have headers to display:
			echo $this->params['head_start'];

			if( isset($this->title) )
			{	// A title has been defined for this result set:
		 		echo str_replace( '$nb_cols$', count($this->cols), $this->params['head_title_start'] );
				echo $this->title;
				echo $this->params['head_title_end'];
			}

			$col_count = 0;
			$col_names = array();
			foreach( $this->col_headers as $col_header )
			{ // For each column:

				if( ($col_count==0) && isset($this->params['colhead_start_first']) )
				{ // First column can get special formatting:
					echo $this->params['colhead_start_first'];
				}
				elseif( ($col_count==count($this->cols)-1) && isset($this->params['colhead_start_last']) )
				{ // Last column can get special formatting:
					echo $this->params['colhead_start_last'];
				}
				else
				{	// Regular columns:
					echo $this->params['colhead_start'];
				}

				if( !empty( $this->col_orders[$col_count] ) && strcasecmp( $this->col_orders[$col_count], '' ) )
				{ //the column can be ordered

					$order_asc = '';
					$order_desc = '';
					$color_asc = '';
					$color_desc = '';

					for( $i = 0; $i < count($this->cols); $i++)
					{ //construction of the values which can be taken by $order
						if( !empty( $this->default_col ) && !strcasecmp( $this->col_orders[$col_count], $this->default_col ) )
						{ // there is a default order 
							$order_asc.='A';
							$order_desc.='D';
						}
						elseif(	$i == $col_count )
						{ //link ordering the current column
							$order_asc.='A';
							$order_desc.='D';
						}
						else
						{
							$order_asc.='-';
							$order_desc.='-';
						}
					}
						
					$style = $this->params['sort_type'];
					
					$asc_status = ( strstr( $this->order, 'A' ) && $col_count == strpos( $this->order, 'A') ) ? 'on' : 'off' ;
					$desc_status = ( strstr( $this->order, 'D' ) && $col_count == strpos( $this->order, 'D') ) ? 'on' : 'off' ;
					$sort_type = ( strstr( $this->order, 'A' ) && $col_count == strpos( $this->order, 'A') ) ? $order_desc : $order_asc;
					$title = strstr( $sort_type, 'A' ) ? T_('Ascending order') : T_('Descending order');
					$title = ' title="'.$title.'" ';
					
					$pos =  strpos( $this->order, 'D');
					
					if( strstr( $this->order, 'A' ) )
					{
						$pos = strpos( $this->order, 'A' );
					}
					
					if( $col_count == $pos ) 
					{ //the column header must be displayed in bold
						$class = ' class="'.$style.'_current" ';
					}
					else
					{
						$class = ' class="'.$style.'_sort_link" ';
					}

					if( $this->params['sort_type'] == 'single' )
					{ // single sort mode:

						echo '<a href="'.regenerate_url( $this->param_prefix.'order', $this->param_prefix.'order='.$sort_type)
									.'" '.$title.$class.' >'
									.$col_header.'</a>' 
									.'<a href="'.regenerate_url( $this->param_prefix.'order', $this->param_prefix.'order='.$order_asc)
									.'" title="'.T_('Ascending order')
									.'" '.$class.' >'.$this->params['sort_asc_'.$asc_status].'</a>'
									.'<a href="'.regenerate_url( $this->param_prefix.'order', $this->param_prefix.'order='.$order_desc)
									.'" title="'.T_('Descending order')
									.'" '.$class.' >'.$this->params['sort_desc_'.$desc_status].'</a> ';
					}
					elseif( $this->params['sort_type'] == 'basic' )
					{ // basic sort mode:

						if( $asc_status == 'off' && $desc_status == 'off' )
						{ // the sorting is not made on the current column 
							$sort_item = $this->params['basic_sort_off'];
						}
						elseif( $asc_status == 'on' )
						{ // the sorting is ascending and made on the current column 
							$sort_item = $this->params['basic_sort_asc'];
						}
						elseif( $desc_status == 'on' )
						{ // the sorting is descending and made on the current column 
							$sort_item = $this->params['basic_sort_desc'];
						}
					
						echo '<a href="'.regenerate_url( $this->param_prefix.'order', $this->param_prefix.'order='.$sort_type).'" title="'.T_('Change Order')
									.'" '.$class.' >'.$sort_item.' '.$col_header.'</a>';
					}
				}
				elseif( empty( $this->col_orders[$col_count] ) )
				{ // the column can't be ordered:

					echo $col_header ;
				}
				$col_count++;
					
 				echo $this->params['colhead_end'];
	
			}

    	echo $this->params['head_end'];
		}

   	echo $this->params['tfoot_start'];

   	echo $this->params['tfoot_end'];

   	echo $this->params['body_start'];


		// -----------------------------
		// DATA ROWS:
		// -----------------------------
		$line_count = 0;
		foreach( $this->rows as $row )
		{	// For each row/line:

			if( $this->current_idx % 2 )
			{	// Odd line:
				if( $this->current_idx == count($this->rows)-1 )
					echo $this->params['line_start_odd_last'];
				else
					echo $this->params['line_start_odd'];
			}
			else
			{	// Even line:
				if( $this->current_idx == count($this->rows)-1 )
					echo $this->params['line_start_last'];
				else
					echo $this->params['line_start'];
			}

			$col_count = 0;
			foreach( $this->cols as $col )
			{	// For each column:

				if( isset($this->col_starts[$col_count] ) )
				{ // We have a customized column start for this one:
					$output = $this->col_starts[$col_count];
				}
				elseif( ($col_count==0) && isset($this->params['col_start_first']) )
				{	// Display first column column start:
					$output = $this->params['col_start_first'];
				}
				elseif( ($col_count==count($this->cols)-1) && isset($this->params['col_start_last']) )
				{ // Last column can get special formatting:
					$output = $this->params['col_start_last'];
				}
				else
				{	// Display regular colmun start:
					$output = $this->params['col_start'];
				}

				$output .= $col; 

				// Make variable substitution:
				$output = preg_replace( '#\$ (\w+) \$#ix', "'.format_to_output(\$row->$1).'", $output );
				// Make callback function substitution:
				$output = preg_replace( '#% (.+?) %#ix', "'.$1.'", $output );

				eval( "echo '$output';" );

				echo $this->params['col_end'];
				$col_count++;
			}
			echo $this->params['line_end'];
			$this->current_idx++;
		}
   	echo $this->params['body_end'];

		echo $this->params['list_end'];

   	echo $this->params['footer_start'];
   	$this->nav_text( $this->params['footer_text'], $this->params['footer_text_single'] );
   	echo $this->params['footer_end'];

		echo $this->params['after'];

		return $this->current_idx;
	}


	/**
	 * Returns the way the list/table has to be ordered
	 */
	function order($order, $asc)
	{	
		$sql_order = '';
		
		if ( isset( $this->col_orders ) )
		{ //the names of the DB columns are defined
			$pos = max( strpos( $order, 'A' ), strpos( $order, 'D' ) );
			$sql_order = ' ORDER BY '.$this->col_orders[$pos].' '.$asc;
	
			$sql_order = str_replace( ',', $this->asc.', ', $sql_order );
		}

		return $sql_order;
	}


	/**
	 * Returns the position of the first defined element of an array
	 */
	function first_defined_order($array)
	{
		$i = 0;
		$alert = 0;
		while( $alert != 1 )
		{ //verification of the array up to the first defined element
			if( !empty( $array[$i] ) )
			{ //the current element of the array is defined
				$alert = 1;
				return $i;
			}
			$i++;
		}
	}	
	
	
	/**
	 * Displays navigation text, based on template:
	 *
	 * @param string template
	 * @param string to display if there is only one page
	 */
	function nav_text( $template, $single = NULL )
	{
		if( empty( $template ) )
			return;

		if( ( $this->total_pages <= 1 ) && !is_null( $single ) )
		{
			echo $single;
		}
		else
		{	//preg_replace_callback is used to avoid calculating unecessary values
			echo preg_replace_callback( '#\$([a-z_]+)\$#', array( $this, 'callback'), $template);
		}
	}
				

	/**
	 * Callback function used to replace only necessary values in template
	 */
	function callback( $matches )
	{
		//echo $matches[1];
		switch( $matches[1] )
			{
				case 'start' : 
					//total number of rows in the sql query 
					return  ( ($this->page-1)*$this->limit+1 ); 
					
				case 'end' : 
					return (	min( $this->total_rows, $this->page*$this->limit ) );		
					
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
					return ( $this->page>1 ) ? '<a href="'.regenerate_url( $this->param_prefix.'page', $this->param_prefix.'page='.($this->page-1) ).'">'.
																$this->params['prev_text'].'</a>' : $this->params['prev_text'];

				case 'next' :
					//inits the link to next page
					return ( $this->page<$this->total_pages ) ? '<a href="'.regenerate_url( $this->param_prefix.'page',  $this->param_prefix.'page='.($this->page+1) )
							.		'">  '.$this->params['next_text'].'</a>' : $this->params['next_text'];
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

				default : return $matches[1];
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
			return $this->page-intval($this->params['list_span']/2);
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
			return '<a href="'.regenerate_url( $this->param_prefix.'page', $this->param_prefix.'page=1' ).'">1</a>';
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
		{	//the list doesn't contain the last page
			return '<a href="'.regenerate_url( $this->param_prefix.'page', $this->param_prefix.'page='.$this->total_pages ).'">'.$this->total_pages.'</a>';
		}
		else
		{	//the list already contains the last page
			return NULL;
		}
	}


	/**
	 * returns a link to previous pages, if necessary
	 */
	function display_prev()
	{
		if( $this->display_first() != NULL )
		{	//the list has to be displayed
			return '<a href="'.regenerate_url( $this->param_prefix.'page', $this->param_prefix.'page='.($this->first()-1) ).'">'
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
			return '<a href="'.regenerate_url( $this->param_prefix.'page', $this->param_prefix.'page='.($this->last()+1) ).'">'
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
				$list = $list.'<a href="'.regenerate_url( $this->param_prefix.'page', $this->param_prefix.'page='.$i).'">'.$i.'</a> ';
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
		$scroll ='<form class="inline" method="post" action="'.regenerate_url( $this->param_prefix.'page' ).'">
    					<select name="'.$this->param_prefix.'page" onchange="parentNode.submit()">';//javascript to change page clicking in the scroll list

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
													.T_('Pages').' '.$min.' '.T_('to').' '.$max;
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
	 * {@internal DataObjectList::get_num_rows(-) }}
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
	 * {@internal DataObjectList::display_if_empty(-) }}
	 *
	 * @param string String to display if list is empty
   * @return true if empty
	 */
	function display_if_empty( $message = '' )
	{
		if( empty($message) )
		{	// Default message:
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
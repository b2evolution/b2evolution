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
 * @author fsaya: Fabrice SAYA GASNIER for PROGIDISTRI.
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
	var $rows;
	var $action;
	var $col_headers = NULL;
	var $cols = NULL;
	var $params = NULL;

	/**
	 * Constructor
	 *
	 * @param string SQL query
	 * @param integer number of lines displayed on one screen
	 * @param 
	 */
	function Results( $sql, $limit = 20, $param_prefix = '', $page = NULL, $order = NULL, $action = NULL)
	{
		global $DB;
		$this->DB = $DB;
 		$this->sql = $sql;
		$this->limit = $limit;
		$this->param_prefix = $param_prefix;
		
		// Count total rows:
		$sql_count = preg_replace( '#SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT(*) FROM', $sql );
	
		if( $this->total_rows = $this->DB->get_var( $sql_count ) )
		{
	    $this->total_pages = ceil($this->total_rows / $this->limit);

 			if( is_null($page) )
			{//attribution of a page number
				$page = param(  $param_prefix.'page', 'integer', 1, true );
			}
			$this->page = min( $page, $this->total_pages ) ;
		
			if( is_null($order) )
			{//attribution of an order type
				$order = param( $param_prefix.'order', 'string', '', true );
			}
			$this->order = $order;
 			
			if( is_null($action) )
			{//attribution of a page number
				$action = param(  'action', 'string', '', true );
			}
			$this->page = min( $page, $this->total_pages ) ;
		}
		else 
		{// there is no page to display
	    $this->total_pages = 0;
	    $this->$page = 0;
		}
	}

	/**
	 * @return int # of rows displayed
	 */
	function display()
	{
		if( is_null( $this->params ) )
		{	// Set default params:
			$this->params = array(
				'before' => '<div>',
					'header_start' => '',
					'header_text' => '',
					'header_end' => '',
					'list_start' => '<table class="grouped" cellspacing="0">',
						'head_start' => '<thead>',
							'colhead_start' => '<th>',
							'colhead_start_first' => '<th class="firstcol">',
							'colhead_end' => '</th>',
						'head_end' => '</thead>',
						'tfoot_start' => '<tfoot>',
						'tfoot_end' => '</tfoot>',
						'body_start' => '<tbody>',
							'line_start' => '<tr>',
							'line_start_odd' => '<tr class="odd">',
								'col_start' => '<td>',
								'col_start_first' => '<td class="firstcol">',
								'col_end' => '</td>',
							'line_end' => '</tr>',
						'body_end' => '</tbody>',
					'list_end' => '</table>',
					'footer_start' => '<div class="center">',
					'footer_text' => ($this->total_pages > 1 ) ? 
															'$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$' : '1 '
															.T_('Page'),
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'range' => 5,																																																		 
					'footer_end' => '</div>',
					'no_results' => T_('No results.'),
				'after' => '</div>',
				);
		}

		echo $this->params['before'];
		
		if( $this->total_pages == 0 )
		{	// There are no results! Nothing to display!
			echo $this->params['no_results'];
			echo $this->params['after'];
			return 0;
		}
	
		//setting of an ascending or descending order
		$asc = ( strstr( $this->param_prefix, 'evt' ) || strstr( $this->param_prefix, 'per' ) ) ? ' DESC' : ' ASC';//the default order type is descending only
																																																							 //for events and periods

		if( $this->order !== '' )
		{ //$order is not an empty string
			$asc = strstr( $this->order, 'A' ) ? ' ASC' : ' DESC';
		}
		elseif( isset( $this->col_orders) )
		{ 
			$pos = $this->first_defined_order( $this->col_orders );
			for( $i = 0; $i < $pos; $i++)
			{
				$this->order .= '-';
			}
			$this->order .= strstr( $asc, 'A' ) ? 'A' : 'D';
		}
	
		if( strpos( $this->sql, 'ORDER BY') === false )
		{ //there is no ORDER BY clause in the original SQL query
			$this->sql.=$this->order($this->order, $asc);
		}
		else
		{//the chosen order must be inserted into an existing ORDER BY clause
			$split = split( 'ORDER BY', $this->sql );
			$this->sql = $split['0']
				.( ( $this->order($this->order, $asc) !== '' ) ? $this->order($this->order, $asc).', ' : ' ORDER BY ' )
				.$split['1'];
		}
	
		//Execute real query
		$this->rows = $this->DB->get_results( $this->sql.' LIMIT '.($this->page-1)*$this->limit.', '.$this->limit, ARRAY_A );
		
		if( is_null( $this->cols ) )
		{	// Let's create default column definitions:
			$this->cols = array();

			if( !preg_match( '#SELECT \s+ (.+?) \s+ FROM#six', $this->sql, $matches ) )
				die( 'No SELECT clause!' );

			$select = $matches[1].',';	// Add a , to normalize list

			if( !($nb_cols = preg_match_all( '#(\w+) \s* ,#six', $select, $matches )) )
				die( 'No columns selected!' );

			for( $i=0; $i<$nb_cols; $i++ )
			{
			 	$col = $matches[1][$i];
				
				$this->cols[] = '$'.$col.'$';
			}
		}
		echo $this->params['header_start'];
   	
		$this->nav_text( $this->params['header_text'] );
   	echo $this->params['header_end'];

		echo $this->params['list_start'];

		if( !is_null( $this->col_headers ) )
		{	// We have headers to display:
			echo $this->params['head_start'];

			$col_count = 0;
			$col_names = array();
		
			foreach( $this->col_headers as $col_header )
			{
				if( ($col_count==0) && isset($this->params['col_start_first']) )
				{
					echo $this->params['colhead_start_first'];
				}
				else
				{
					echo $this->params['colhead_start'];
				}
				
				if( isset( $this->col_orders[$col_count] ) && strcasecmp( $this->col_orders[$col_count], '' ) )
				{//the column can be ordered
					
					$order_asc = '';
					$order_desc = '';
					$color_asc = '';
					$color_desc = '';
					
					for( $i = 0; $i < count($this->cols); $i++)
					{//construction of the values which can be taken by $order
						if(	$i == $col_count )
						{//link ordering the current column
							$order_asc.='A';
							$order_desc.='D';
						}
						else
						{
							$order_asc.='-';
							$order_desc.='-';
						}
					}
						
					$color_asc = ( strstr( $this->order, 'A' ) && $col_count == strpos( $this->order, 'A') ) ? 'black' : 'grey' ; //color of the ascending arrow 
					$color_desc = ( strstr( $this->order, 'D' ) && $col_count == strpos( $this->order, 'D') ) ? 'black' : 'grey' ; //color of the descending arrow
					
						echo '<a href="'.regenerate_url( $this->param_prefix.'order', $this->param_prefix.'order='.$order_asc).'" title="'.T_('Ascending Order')
								.'" ><img src="../admin/img/'.$color_asc.'_arrow_down.gif" alt="A" title="'.T_('Ascending Order')
								.'" ></a>' 
								.'<a href="'.regenerate_url( $this->param_prefix.'order', $this->param_prefix.'order='.$order_desc).'" title="'.T_('Descending Order')
								.'" ><img src="../admin/img/'.$color_desc.'_arrow_up.gif" alt="D" title="'.T_('Descending Order')
								.'" ></a> ';
				}	
				echo $col_header;

 				echo $this->params['colhead_end'];
				$col_count++;
			}

    	echo $this->params['head_end'];
		}

   	echo $this->params['tfoot_start'];

   	echo $this->params['tfoot_end'];

   	echo $this->params['body_start'];

		$line_count = 0;
		foreach( $this->rows as $row )
		{	// For each row/line:
			if( ($line_count % 2) && isset($this->params['line_start_odd']) )
				echo $this->params['line_start_odd'];
			else
				echo $this->params['line_start'];

			$col_count = 0;
			foreach( $this->cols as $col )
			{	// For each column:
				if( ($col_count==0) && isset($this->params['col_start_first']) )
				 	echo $this->params['col_start_first'];
				else
					echo $this->params['col_start'];

				// Make variable substitution:
				$output = preg_replace( '#\$ (\w+) \$#ix', "'.format_to_output(\$row['$1']).'", $col );
				// Make callback function substitution:
				$output = preg_replace( '#% (.+?) %#ix', "'.$1.'", $output );

				eval( "echo '$output';" );

				echo $this->params['col_end'];
				$col_count++;
			}
			echo $this->params['line_end'];
			$line_count++;
		}
   	echo $this->params['body_end'];

		echo $this->params['list_end'];

   	echo $this->params['footer_start'];
   	$this->nav_text( $this->params['footer_text'] );
   	echo $this->params['footer_end'];

		echo $this->params['after'];

		return $line_count;
	}

	/**
	 * return the way the table has to be ordered
	 */
	function order($order, $asc)
	{	
		$sql_order = '';
		
		if ( isset( $this->col_orders ) )
		{ //the names of the DB columns are defined
			$pos = max( strpos( $order, 'A' ), strpos( $order, 'D' ) );
			$sql_order = ' ORDER BY '.$this->col_orders[$pos].' '.$asc;
		}
	
		$sql_order = str_replace( ',', ' ASC, ', $sql_order );

		return $sql_order;
	}

	/**
	 * return the position of  first defined element of an array
	 */
	function first_defined_order($array)
	{
		$i = 0;
		$alert = 0;
		while( $alert != 1 )
		{//verification of the array up to the first defined element
			if( isset( $array[$i] ) )
			{//the current element of the array is defined
				$alert = 1;
				return $i;
			}
			$i++;
		}
	}	
	
	
	/**
	 * Display navigation text, based on template:
	 *
	 * @param string template
	 */
	function nav_text( $template )
	{
		if( empty( $template ) )
			return;

		//preg_replace_callback is used to avoid calculating unecessary values
		echo preg_replace_callback( '#\$([a-z_]+)\$#', array( $this, 'callback'), $template); 
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
					return ( $this->page>1 ) ? '<a href="'.regenerate_url( 'page', 'page='.($this->page-1) ).'">'.
																$this->params['prev_text'].'</a>' : $this->params['prev_text'];
				
				case 'next' :
					//inits the link to next page
					return ( $this->page<$this->total_pages ) ? '<a href="'.regenerate_url( 'page', 'page='.($this->page+1) ).
							  		 													   '">  '.$this->params['next_text'].'</a>' : $this->params['next_text'];
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

	/*
	 * returns the first page number to be displayed in the list
	 */
	function first()
	{
		if( $this->page <= intval( $this->params['list_span']/2 ))
		{//the current page number is small
			return 1;
		}
		elseif( $this->page > $this->total_pages-intval( $this->params['list_span']/2 ))
		{//the current page number is big
			return $this->total_pages-$this->params['list_span']+1;
		}
		else
		{//the current page numbe rcan be centered
			return $this->page-intval($this->params['list_span']/2); 
		}
	}
	
	/*
	 * returns the last page number to be displayed in the list
	 */
	function last()
	{
		if( $this->page > $this->total_pages-intval( $this->params['list_span']/2 ))
		{//the current page number is big
			return $this->total_pages;
		}
		else
		{
			return $this->first()+$this->params['list_span']-1;
		}
	}
	
	/*
	 * returns the link to the first page, if necessary
	 */
	function display_first()
	{
		if( $this->first() > 1 )
		{//the list doesn't contain the first page
			return '<a href="'.regenerate_url( 'page', 'page=1' ).'">1</a>';
		}
		else
		{//the list already contains the first page
			return NULL;
		}
	}
	
	/*
	 * returns the link to the first page, if necessary
	 */	
	function display_last()
	{
		if( $this->last() < $this->total_pages )
		{//the list doesn't contain the last page
			return '<a href="'.regenerate_url( 'page', 'page='.$this->total_pages ).'">'.$this->total_pages.'</a>';
		}
		else
		{//the list already contains the last page
			return NULL;
		}
	}
	
	/*
	 * returns a link to previous pages, if necessary
	 */
	function display_prev()
	{
		if( $this->display_first() != NULL )
		{//the list has to be displayed
			return '<a href="'.regenerate_url( 'page', 'page='.($this->first()-1) ).'">'.$this->params['list_prev_text'].'</a>';
		}
			
	}
	
	function display_next()
	{
		if( $this->display_last() != NULL )
		{//the list has to be displayed
			return '<a href="'.regenerate_url( 'page', 'page='.($this->last()+1) ).'">'.$this->params['list_next_text'].'</a>';
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
			{//no link for the current page
				$list = $list.'<strong>'.$i.'</strong> ';
			}
			else
			{//a link for non-current pages
				$list = $list.'<a href="'.regenerate_url( 'page', 'page='.$i).'">'.$i.'</a> ';
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
		$range = $this->params['range'];
		$min = 1; 
		$max = 1;
		$option = '';
		$selected = '';
		$range_display='';

		if( $range > $this->total_pages )
			{//the range is greater than the total number of pages, the list goes up to the number of pages
				$max = $this->total_pages;
			}
			else
			{//initialisation of the range
				$max = $range;
			}
		
		$scroll ='<form class="inline" name="list" action="'.regenerate_url( 'page' ).'">'.//initialization of the form 
				' <select name="page" size="1" onChange="parentNode.submit()">'.$this->page;//the parentNode property is defined in W3C DOM level 1 (09/29/2000)

		while( $max <= $this->total_pages )
		{//construction loop
			
			if( $this->page <= $max && $this->page >= $min )
			{//display of all the pages belonging to the range where the current page is located
				for( $i = $min ; $i <= $max ; $i++)
				{//construction of the <option> tags
					$selected = ($i == $this->page) ? 'selected ' : '';//the "selected" option is applied to the current page
					$option = '<option '.$selected.'value="'.$i.'">'.$i.'</option>';
					$scroll = $scroll.$option;
				}
			}
			else
			{//inits the ranges inside the list
				$range_display = '<option value="'.$min.'">'.T_('Pages').' '.$min.' '.T_('to').' '.$max;
				$scroll = $scroll.$range_display;
			}
						
			if( $max+$range > $this->total_pages && $max != $this->total_pages)
			{//$max has to be the total number of pages
				$max = $this->total_pages;
			}
			else
			{
				$max = $max+$range;//incrementation of the maximum value by the range
			}

			$min = $min+$range;//incrementation of the minimum value by the range
				
		}
		$scroll = $scroll.'</select></form>';//end of the form

		return $scroll;
	}

}



/*
 * $Log$
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
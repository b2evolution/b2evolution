<?php
/**
 * This file implements the Results class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
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
	var $col_headers = NULL;
	var $cols = NULL;
	var $params = NULL;

	/**
	 * @param string SQL query
	 * @param integer number of lines displayed on one screen
	 * @param 
	 */
	function Results( $sql, $limit = 20, $page = NULL )
	{
		global $DB;
		$this->DB = $DB;
 		$this->sql = $sql;
		$this->limit = $limit;

		// Count total rows:
		$sql_count = preg_replace( '#SELECT \s+ (.+?) \s+ FROM#six', 'SELECT COUNT(*) FROM', $sql );
		if( $this->total_rows = $this->DB->get_var( $sql_count ) )
		{
	    $this->total_pages = ceil($this->total_rows / $this->limit);

 			if( is_null($page) )
			{
				$page = param( 'page', 'integer', 1, true );
			}
			$this->page = min( $page, $this->total_pages ) ;


		 	// Execute real query
			$this->rows = $this->DB->get_results( $this->sql." LIMIT ".($this->page-1)*$this->limit.", $this->limit ", ARRAY_A );
		}
		else
		{
			$this->rows = array();
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
					'footer_text' => T_( '$prev$ page $page$/$total_pages$ $next$' ),
						'prev_text' => '&lt;',
						'next_text' => '&gt;',
					'footer_end' => '</div>',
					'no_results' => T_('No results.'),
				'after' => '</div>',
				);
		}

		echo $this->params['before'];

	 	if( ! count($this->rows) )
		{	// There are no results! Nothing to display!
			echo $this->params['no_results'];
			echo $this->params['after'];
			return 0;
		}


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
				// echo ' - col: '.$col;
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
			foreach( $this->col_headers as $col_header )
			{
				if( ($col_count==0) && isset($this->params['col_start_first']) )
				 	echo $this->params['colhead_start_first'];
				else
					echo $this->params['colhead_start'];

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
	 * Display navigation text, based on template:
	 *
	 * @param strin template
	 */
	function nav_text( $template )
	{
		if( empty( $template ) )
			return;

		echo preg_replace( array(
															'#\$start\$#',
															'#\$end\$#',
															'#\$total_rows\$#',
															'#\$page\$#',
															'#\$total_pages\$#',
															'#\$prev\$#',
															'#\$next\$#',
														),
											 array(
											 				($this->page-1)*$this->limit+1,
											 				min( $this->total_rows, $this->page*$this->limit ),
											 				$this->total_rows,
											 				$this->page,
											 				$this->total_pages,
											 				($this->page>1) ? '<a href="'.regenerate_url( 'page', 'page='.($this->page-1) ).
											 					'">'.$this->params['prev_text'].'</a>' : '',
											 				($this->page<$this->total_pages) ? '<a href="'.regenerate_url( 'page', 'page='.($this->page+1) ).
											 					'">'.$this->params['next_text'].'</a>' : '',
											 			),
											 	$template );

	}

}

/*
 * $Log$
 * Revision 1.4  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 */
?>
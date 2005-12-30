<?php
/**
 * This file implements the SQL class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * SQL class: help constructing queries
 *
 * @todo (fplanque)
 */
class SQL
{
	var $select = '';
	var $from = '';
	var $where = '';
	var $group_by = '';
	var $order_by = '';
	var $limit = '';
	var $search_field = array();


	/**
	 * Constructor.
	 */
	function SQL()
	{
	}


  /**
	 * Get whole query
	 */
	function get()
	{
		$sql = '';
		if( !empty($this->select) ) $sql .= ' SELECT '.$this->select;
		$sql .= $this->get_from();
		$sql .= $this->get_where();
		$sql .= $this->get_group_by();
		$sql .= $this->get_order_by();
		$sql .= $this->get_limit();
		return $sql;
	}


  /**
	 * Get FROM clause if there is something inside
	 */
	function get_from( $prefix = ' FROM ' )
	{
		if( !empty($this->from) )
		{
			return $prefix.$this->from;
		}

		return '';
	}


  /**
	 * Get WHERE clause if there is something inside
	 */
	function get_where( $prefix = ' WHERE ' )
	{
		if( !empty($this->where) )
		{
			return $prefix.$this->where;
		}

		return '';
	}


  /**
	 * Get GROUP BY clause if there is something inside
	 */
	function get_group_by( $prefix = ' GROUP BY ' )
	{
		if( !empty($this->group_by) )
		{
			return $prefix.$this->group_by;
		}

		return '';
	}


  /**
	 * Get ORDER BY clause if there is something inside
	 */
	function get_order_by( $prefix = ' ORDER BY ' )
	{
		if( !empty($this->order_by) )
		{
			return $prefix.$this->order_by;
		}

		return '';
	}


  /**
	 * Get LIMIT clause if there is something inside
	 */
	function get_limit( $prefix = ' LIMIT ' )
	{
		if( !empty($this->limit) )
		{
			return $prefix.$this->limit;
		}

		return '';
	}


  /**
	 * Set SELECT clause
	 */
	function SELECT( $select )
	{
		$this->select = $select;
	}


	/**
	 * Extends the SELECT clause.
	 *
	 * @param srting should typically start with a comma ','
	 */
	function SELECT_add( $select_add )
	{
		if( empty( $this->select ) ) die( 'Cannot extend empty SELECT clause' );

		$this->select .= ' '.$select_add;
	}


  /**
	 *
	 */
	function FROM( $from )
	{
		$this->from = $from;
	}

	/**
	 * Extends the FROM clause.
	 *
	 * @param string should typically start with INNER JOIN or LEFT JOIN
	 */
	function FROM_add( $from_add )
	{
		if( empty( $this->from ) ) die( 'Cannot extend empty FROM clause' );

		$this->from .= ' '.$from_add;
	}


  /**
	 *
	 */
	function WHERE( $where )
	{
		$this->where = $where;
	}

	/*
	 * Extends the WHERE cakuse with AND
	 */
	function WHERE_and( $where_and )
	{
		if( empty($where_and) )
		{	// Nothing to append:
			return false;
		}

		if( ! empty($this->where) )
		{ // We already have something in the WHERE clause:
			$this->where .= ' AND ';
		}

		// Append payload:
		$this->where .= '('.$where_and.')';
	}

	function GROUP_BY( $group_by )
	{
		$this->group_by = $group_by;
	}

	function ORDER_BY( $order_by )
	{
		$this->order_by = $order_by;
	}

 	function ORDER_BY_prepend( $order_by_prepend )
	{
		if( empty( $order_by_prepend ) )
		{
			return;
		}

		if( empty( $this->order_by ) )
		{
			$this->order_by = $order_by_prepend;
		}
		else
		{
			$this->order_by = $order_by_prepend.', '.$this->order_by;
		}
	}

	function LIMIT( $limit )
	{
		$this->limit = $limit;
	}

	/**
	 * create array of search fields
	 *
	 * @param string field to search on
	 */
	function add_search_field( $field )
	{
		$this->search_field[] = $field;
	}
	
	/**
	 * create the filter whith the search field array
	 * @param string search 
	 * @param string operator( AND , OR , PHRASE ) for the filter
	 */
	function WHERE_keyword( $search, $search_kw_combine )
	{
		// Concat the list of search fields ( concat(' ',field1,field2,field3...) ) 
		if (count( $this->search_field ) > 1)
		{	
			$search_field = 'CONCAT_WS(\' \',' . implode( ',', $this->search_field).')';
		}
		else 
		{
			$search_field = $this->search_field[0];
		}
		
		switch( $search_kw_combine )
		{
			case 'AND':
			case 'OR':
						// create array of key words of the search string
						$keyword_array = explode( ' ', $search );
						$keyword_array = array_filter( $keyword_array, 'filter_empty' ); 
						
						$twhere = array();
						foreach($keyword_array as $keyword)
						{
							$twhere[] = $search_field.' like \'%'.$keyword.'%\'';
						}
						$where = implode( ' '.$search_kw_combine.' ', $twhere);				
						break;
						
			case 'PHRASE':
						$where = $search_field.' like "%'.$search.'%"';
						break;
						
			case 'BEGINWITH':
						$twhere = array();
						foreach( $this->search_field as $field )
						{
							$twhere[] = $field." LIKE '".$search."'";
						}
						$where = implode( ' OR ', $twhere ); 
						break;
						
		}
		$this->WHERE_and( $where );
	}
	
}

/*
 * $Log$
 * Revision 1.8  2005/12/30 20:13:40  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.7  2005/12/05 18:17:19  fplanque
 * Added new browsing features for the Tracker Use Case.
 *
 * Revision 1.6  2005/11/18 21:01:21  fplanque
 * no message
 *
 * Revision 1.5  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.4  2005/09/01 17:11:46  fplanque
 * no message
 *
 * Revision 1.3  2005/08/31 19:08:51  fplanque
 * Factorized Item query WHERE clause.
 * Fixed calendar contextual accuracy.
 *
 * Revision 1.2  2005/07/26 18:58:00  fplanque
 * minor
 *
 * Revision 1.1  2005/04/26 18:19:25  fplanque
 * no message
 *
 * Revision 1.3  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.2  2005/02/21 00:34:34  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.2  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 */
?>
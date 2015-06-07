<?php
/**
 * This file implements the SQL class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * SQL class: help constructing queries
 *
 * @todo dh> should provide quoting, e.g. via $DB->quote()..
 *           Maybe using printf-style, where all args get quoted.
 *
 * @todo (fplanque)
 */
class SQL
{
	var $select = '';
	var $from = '';
	var $where = '';
	var $group_by = '';
	var $having = '';
	var $order_by = '';
	var $limit = '';
	var $append = '';
	var $search_field = array();
	var $search_field_regexp = array();
	var $title;


	/**
	 * Constructor.
	 */
	function SQL($title = NULL)
	{
		if( $title )
			$this->title = $title;
	}


  /**
	 * Get whole query
	 */
	function get()
	{
		$sql = '';
		$sql .= $this->get_select();
		$sql .= $this->get_from();
		$sql .= $this->get_where();
		$sql .= $this->get_group_by();
		$sql .= $this->get_having();
		$sql .= $this->get_order_by();
		$sql .= $this->get_limit();
		$sql .= $this->get_append();
		return $sql;
	}


  /**
	 * Get whole query
	 */
	function display()
	{
		echo $this->get_select( '<br />SELECT ' );
		echo $this->get_from( '<br />FROM ' );
		echo $this->get_where( '<br />WHERE ' );
		echo $this->get_group_by( '<br />GROUP BY ' );
		echo $this->get_having( '<br />HAVING ' );
		echo $this->get_order_by( '<br />ORDER BY ' );
		echo $this->get_limit( '<br />LIMIT ' );
		echo $this->get_append( '<br />' );
	}


  /**
	 * Get SELECT clause if there is something inside
	 */
	function get_select( $prefix = ' SELECT ' )
	{
		if( !empty($this->select) )
		{
			return $prefix.$this->select;
		}

		return '';
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
	 * Get HAVING clause if there is something inside
	 */
	function get_having( $prefix = ' HAVING ' )
	{
		if( !empty($this->having) )
		{
			return $prefix.$this->having;
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
	 * Get anything to be appended at the end there is something to be appended
	 */
	function get_append( $prefix = ' ' )
	{
		if( !empty($this->append) )
		{
			return $prefix.$this->append;
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
		if( empty( $this->select ) ) debug_die( 'Cannot extend empty SELECT clause' );

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
		if( empty( $this->from ) ) debug_die( 'Cannot extend empty FROM clause' );

		$this->from .= ' '.$from_add;
	}


  /**
	 *
	 */
	function WHERE( $where )
	{
		$this->where = $where;
	}

	/**
	 * Extends the WHERE clause with AND
	 * @param string
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

	/**
	 * Extends the WHERE clause with OR
	 *
	 * NOTE: there is almost NEVER a good reason to use this! Think again!
	 *
	 * @param string
	 */
	function WHERE_or( $where_or )
	{
		if( empty($where_or) )
		{	// Nothing to append:
			return false;
		}

		if( ! empty($this->where) )
		{ // We already have something in the WHERE clause:
			$this->where .= ' OR ';
		}

		// Append payload:
		$this->where .= '('.$where_or.')';
	}

	function GROUP_BY( $group_by )
	{
		$this->group_by = $group_by;
	}

	function HAVING( $having )
	{
		$this->having = $having;
	}

	/**
	 * Extends the HAVING clause with AND
	 *
	 * @param string
	 */
	function HAVING_and( $having_and )
	{
		if( empty( $having_and ) )
		{	// Nothing to append:
			return false;
		}

		if( ! empty( $this->having ) )
		{ // We already have something in the HAVING clause:
			$this->having .= ' AND ';
		}

		// Append payload:
		$this->having .= '('.$having_and.')';
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

	function append( $append )
	{
		$this->append = $append;
	}

	/**
	 * create array of search fields
	 *
	 * @param string field to search on
	 * @param string regular expression we want to use on the search for the field param
	 */
	function add_search_field( $field, $reg_exp = '' )
	{
		$this->search_field[] = $field;

		if( !empty( $reg_exp ) )
		{	// We want to use a regular expression on the search for this field, so add to the search field regexp array
			$this->search_field_regexp[$field] = $reg_exp;
		}
	}

	/**
	 * create the filter whith the search field array
	 *
	 * @param string keywords separated by space
	 * @param string operator( AND , OR , PHRASE ) for the filter
	 */
	function WHERE_keywords( $search, $search_kw_combine )
	{
		global $DB;

		// Concat the list of search fields ( concat(' ',field1,field2,field3...) )
		if (count( $this->search_field ) > 1)
		{
			$search_field = 'CONCAT_WS(\' \','.implode( ',', $this->search_field).')';
		}
		else
		{
			$search_field = $this->search_field[0];
		}

		switch( $search_kw_combine )
		{
			case 'AND':
			case 'OR':
				// Create array of key words of the search string
				$keyword_array = explode( ' ', $search );
				$keyword_array = array_filter( $keyword_array, create_function( '$val', 'return !empty($val);' ) );

				$twhere = array();
				// Loop on all keywords
				foreach($keyword_array as $keyword)
				{
					$twhere[] = '( '.$search_field.' LIKE \'%'.$DB->escape( $keyword ).'%\''.$this->WHERE_regexp( $keyword, $search_kw_combine ).' )';
				}
				$where = implode( ' '.$search_kw_combine.' ', $twhere);
				break;

			case 'PHRASE':
					$where = $search_field." LIKE '%".$DB->escape( $search )."%'".$this->WHERE_regexp( $search, $search_kw_combine );
					break;

			case 'BEGINWITH':
				$twhere = array();
				foreach( $this->search_field as $field )
				{
					$twhere[] = $field." LIKE '".$DB->escape( $search )."%'";
				}
				$where = implode( ' OR ', $twhere ).$this->WHERE_regexp( $search, $search_kw_combine);
				break;

		}
		$this->WHERE_and( $where );
	}

	/**
	 * create the filter whith the search field regexp array
	 *
	 * @param string search
	 * @param string operator( AND , OR , PHRASE ) for the filter
	 *
	 */
	function WHERE_regexp( $search, $search_kw_combine )
	{
		$where = '';

		// Loop on all fields we have to use a replace regular expression on search:
		foreach( $this->search_field_regexp as $field=>$reg_exp )
		{
				// Use reg exp replace on search
				$search_reg_exp = preg_replace( $reg_exp, '', $search );

				if( !empty( $search_reg_exp ) )
				{	// The reg exp search is not empty, so we add it to the request with an 'OR' operator:
					switch( $search_kw_combine )
					{
						case 'AND':
						case 'OR':
						case 'PHRASE':
							$where .= ' OR '.$field.' LIKE \'%'.$DB->escape( $search_reg_exp ).'%\'';
							break;

						case 'BEGINWITH':
							$where .= ' OR '.$field.' LIKE \''.$DB->escape( $search_reg_exp ).'%\'';
							break;
					}
				}
		}
		return $where;
	}


}

?>
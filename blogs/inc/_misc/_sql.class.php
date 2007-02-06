<?php
/**
 * This file implements the SQL class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
	var $search_field_regexp = array();


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
		$sql .= $this->get_select();
		$sql .= $this->get_from();
		$sql .= $this->get_where();
		$sql .= $this->get_group_by();
		$sql .= $this->get_order_by();
		$sql .= $this->get_limit();
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
		echo $this->get_order_by( '<br />ORDER BY ' );
		echo $this->get_limit( '<br />LIMIT ' );
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
	 * @param string regular expression we want to use on the search for the field param
	 */
	function add_search_field( $field,  $reg_exp = '' )
	{
		$this->search_field[] = $field;

		if( !empty( $reg_exp ) )
		{	// We want to use a regular expression on the search for this field, so add to the search field regexp array
			$this->search_field_regexp[$field] = $reg_exp;
		}
	}

	/**
	 * create the filter whith the search field array
	 * @param string search
	 * @param string operator( AND , OR , PHRASE ) for the filter
	 */
	function WHERE_keyword( $search, $search_kw_combine )
	{
		global $DB;
	
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
				// Create array of key words of the search string
				$keyword_array = explode( ' ', $search );
				$keyword_array = array_filter( $keyword_array, 'filter_empty' );

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

/*
 * $Log$
 * Revision 1.10  2007/02/06 13:27:26  waltercruz
 * Changing double quotes to single quotes
 *
 * Revision 1.9  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.8  2006/07/01 22:41:37  fplanque
 * security
 *
 * Revision 1.7  2006/06/19 20:59:38  fplanque
 * noone should die anonymously...
 *
 * Revision 1.6  2006/06/01 19:00:09  fplanque
 * no message
 *
 * Revision 1.5  2006/05/30 23:12:17  blueyed
 * added WHERE_or()
 *
 * Revision 1.4  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.3  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 * Revision 1.2  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.9  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
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
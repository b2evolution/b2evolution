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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


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

	/**
	 * Constructor.
	 */
	function SQL()
	{
	}

	function get()
	{
		$sql = '';
		if( !empty($this->select) ) $sql .= ' SELECT '.$this->select;
		if( !empty($this->from) ) $sql .= ' FROM '.$this->from;
		if( !empty($this->where) ) $sql .= ' WHERE '.$this->where;
		if( !empty($this->group_by) ) $sql .= ' GROUP BY '.$this->group_by;
		if( !empty($this->order_by) ) $sql .= ' ORDER BY '.$this->order_by;
		return $sql;
	}


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


}

/*
 * $Log$
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
<?php
/**
 * This file implements the Group class, which manages user groups.
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
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_class_dataobject.php';

/**
 * User Group
 *
 * Group of users with specific permissions.
 *
 * @package evocore
 */
class Group extends DataObject
{
	/**
	 * Name of group
	 *
	 * Please use get/set functions to read or write this param
	 *
	 * @var string
	 * @access protected
	 */
	var	$name;
	/**
	 * Permissions for stats
	 *
	 * Possible values: none, view, edit
	 *
	 * Please use get/set functions to read or write this param
	 *
	 * @var string
	 * @access protected
	 */
	var	$perm_stats;
	var	$perm_blogs;
	var	$perm_spamblacklist;
	var	$perm_options;
	var	$perm_templates;
	var	$perm_users;

	/** 
	 * Constructor
	 *
	 * {@internal Group::Group(-) }}
	 *
	 * @param object DB row
	 */
	function Group( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_users', 'grp_', 'grp_ID' );

		if( $db_row == NULL )
		{
			// echo 'Creating blank group';
			$this->name = T_('New group');
			$this->perm_blogs = 'user';
			$this->perm_stats = 'none';
			$this->perm_spamblacklist = 'none';
			$this->perm_options = 'none';
			$this->perm_templates = 0;
			$this->perm_users = 'none';
		}
		else
		{
			// echo 'Instanciating existing group';
			$this->ID = $db_row->grp_ID;
			$this->name = $db_row->grp_name;
			$this->perm_blogs = $db_row->grp_perm_blogs;
			$this->perm_stats = $db_row->grp_perm_stats;
			$this->perm_spamblacklist = $db_row->grp_perm_spamblacklist;
			$this->perm_options = $db_row->grp_perm_options;
			$this->perm_templates = $db_row->grp_perm_templates;
			$this->perm_users = $db_row->grp_perm_users;
		}
	}	
	
	/** 
	 * Set param value
	 *
	 * {@internal Group::set(-) }}
	 *
	 * @param string Parameter name
	 * @return mixed Parameter value
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			case 'perm_templates':
				parent::set_param( $parname, 'number', $parvalue );
			break;
			
			default:
				parent::set_param( $parname, 'string', $parvalue );
		}
	}

	/** 
	 * Check a permission for this group
	 *
	 * {@internal Group::check_perm(-) }}
	 *
	 * @param string Permission name:
	 *									- templates
	 *									- stats
	 *									- spamblacklist
	 *									- options
	 *									- users
	 *									- blogs
	 * @param string Permission level
	 * @return strind Permission value
	 */
	function check_perm( $permname, $permlevel )
	{
		eval( '$permvalue = $this->perm_'.$permname.';' );
		// echo $permvalue;

		switch( $permname )
		{
			case 'templates':
				if( $permvalue )
					return true;	// Permission granted
				break;
				
			case 'blogs':
				switch( $permvalue )
				{
					case 'editall':
						// All permissions granted
						return true;	// Permission granted
						
					case 'viewall':
						// User can only ask for view perm
						if(( $permlevel == 'view' ) || ( $permlevel == 'any' ))
							return true;	// Permission granted
						break;	
				}

			case 'stats':
			case 'spamblacklist':
			case 'options':
			case 'users':
				switch( $permvalue )
				{
					case 'edit':
						// All permissions granted
						return true;	// Permission granted
						
					case 'view':
						// User can only ask for view perm
						if( $permlevel == 'view' )
							return true;	// Permission granted
						break;	
				}
		}		

		return false;	// Permission denied!
	}
	
}

/*
 * $Log$
 * Revision 1.19  2004/10/12 16:12:17  fplanque
 * Edited code documentation.
 *
 * Revision 1.18  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>
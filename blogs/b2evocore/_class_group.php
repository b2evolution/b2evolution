<?php
/**
 * This file implements User Groups
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */
require_once dirname(__FILE__).'/_class_dataobject.php';

/**
 * User Group
 *
 * Group of users with specific permissions.
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
		global $tablegroups;
		
		// Call parent constructor:
		parent::DataObject( $tablegroups, 'grp_', 'grp_ID' );
	
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
?>

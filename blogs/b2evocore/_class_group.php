<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
require_once dirname(__FILE__).'/_class_dataobject.php';

class Group extends DataObject
{
	var	$name;
	var	$perm_stats;
	var	$perm_spamblacklist;
	var	$perm_options;
	var	$perm_templates;

	/* 
	 * Group::Group(-)
	 *
	 * Constructor
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
			$this->perm_stats = 'none';
			$this->perm_spamblacklist = 'none';
			$this->perm_options = 'none';
			$this->perm_templates = 0;
		}
		else
		{
			// echo 'Instanciating existing group';
			$this->ID = $db_row->grp_ID;
			$this->name = $db_row->grp_name;
			$this->perm_stats = $db_row->grp_perm_stats;
			$this->perm_spamblacklist = $db_row->grp_perm_spamblacklist;
			$this->perm_options = $db_row->grp_perm_options;
			$this->perm_templates = $db_row->grp_perm_templates;
		}
	}	
	
	/* 
	 * Group::set(-)
	 *
	 * Set param value
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			case 'perm_templates':
				parent::set_param( $parname, 'int', $parvalue );
			break;
			
			default:
				parent::set_param( $parname, 'string', $parvalue );
		}
	}

	/*
	 * Group::check_perm(-)
	 *
	 * Check permission
	 */
	function check_perm( $permname, $permrequested )
	{
		eval( '$permvalue = $this->perm_'.$permname.';' );
		// echo $permvalue;

		switch( $permname )
		{
			case 'templates':
				if( $permvalue )
					return true;	// Permission granted
				break;
				
			case 'stats':
			case 'spamblacklist':
			case 'options':
				switch( $permvalue )
				{
					case 'edit':
						// All permissions granted
						return true;	// Permission granted
						
					case 'view':
						// User can only ask for view perm
						if( $permrequested == 'view' )
							return true;	// Permission granted
						break;	
				}
		}		
		return false;	// Permission denied!
	}
	
}
?>

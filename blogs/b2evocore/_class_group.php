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

	/* 
	 * Group::Group(-)
	 *
	 * Constructor
	 */
	function Group( $db_row = NULL )
	{
		global $tablegroups;
		
		// Call parent constructor:
		parent::DataObject( $tablegroups, 'grp_' );
	
		if( $db_row == NULL )
		{
			// echo 'Creating blank group';
			$this->name = T_('New group');
			$this->perm_stats = 'none';
			$this->perm_spamblacklist = 'none';
		}
		else
		{
			// echo 'Instanciating existing group';
			$this->ID = $db_row->grp_ID;
			$this->name = $db_row->grp_name;
			$this->perm_stats = $db_row->grp_perm_stats;
			$this->perm_spamblacklist = $db_row->grp_perm_spamblacklist;
		}
	}	
	
	
}
?>

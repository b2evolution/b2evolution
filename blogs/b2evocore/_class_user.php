<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
require_once dirname(__FILE__).'/_class_dataobject.php';

class User extends DataObject
{
	var	$login;
	var	$pass;
	var	$firstname;
	var	$lastname;
	var	$nickname;
	var	$idmode;
	var	$email;
	var	$url;
	var	$icq;
	var	$aim;
	var	$msn;
	var	$yim;
	var	$ip;
	var	$domain;
	var	$browser;
	var	$datecreated;
	var	$level;
	var	$notify;

	var $Group;	// Pointer to group

	/* 
	 * User::User(-)
	 *
	 * Constructor
	 */
	function User( & $userdata )
	{
		global $tableusers;
		
		// echo 'Instanciating ', $userdata['user_login'], '...<br />';
		
		// Call parent constructor:
		parent::DataObject( $tableusers, 'user_' );
			
		$this->ID = $userdata['ID'];
		$this->login = $userdata['user_login'];
		$this->pass = $userdata['user_pass'];
		$this->firstname = $userdata['user_firstname'];
		$this->lastname = $userdata['user_lastname'];
		$this->nickname = $userdata['user_nickname'];
		$this->idmode = $userdata['user_idmode'];
		$this->email = $userdata['user_email'];
		$this->url = $userdata['user_url'];
		$this->icq = $userdata['user_icq'];
		$this->aim = $userdata['user_aim'];
		$this->msn = $userdata['user_msn'];
		$this->yim = $userdata['user_yim'];
		$this->ip = $userdata['user_ip'];
		$this->domain = $userdata['user_domain'];
		$this->browser = $userdata['user_browser'];
		$this->datecreated = $userdata['dateYMDhour'];
		$this->level = $userdata['user_level'];
		$this->notify = $userdata['user_notify'];
		
		// Group for this user:
		$this->Group = Group_get_by_ID( $userdata['user_grp_ID'] );
	}	
	
	/* 
	 * User::get(-)
	 *
	 * Get a param
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'preferedname':
				// Prefered name to display
				switch( $this->idmode ) 
				{
					case 'namefl':
						return parent::get('firstname').' '.parent::get('lastname');
						
					case 'namelf':
						return parent::get('lastname').' '.parent::get('firstname');
						
					default:
						return parent::get($this->idmode);
				}
			
			default:
			// All other params:
				return parent::get( $parname );
		}
	}


	/* 
	 * User::setGroup(-)
	 *
	 * Set new Group
	 */
	function setGroup( & $Group )
	{
		$this->Group = $Group;
		
		$this->dbchange( 'user_grp_ID', 'int', 'Group->get(\'ID\')' );
	}
}
?>

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
	function User( $userdata = NULL )
	{
		global $tableusers;
		
		// Call parent constructor:
		parent::DataObject( $tableusers, 'user_' );
			
		if( $userdata == NULL )
		{
			// echo 'Creating blank user';
			$this->name = T_('New user');
			$this->login = 'login';
			$this->pass = 'pass';
			$this->firstname = '';
			$this->lastname = '';
			$this->nickname = '';
			$this->idmode = 'login';
			$this->email = '';
			$this->url = '';
			$this->icq = 0;
			$this->aim = '';
			$this->msn = '';
			$this->yim = '';
			$this->ip = '';
			$this->domain = '';
			$this->browser = '';
			$this->datecreated = date('Y-m-d H:i:s', time());	// We don't know local time here!
			$this->level = 0;
			$this->notify = 1;
			// Group for this user:
			$this->Group = Group_get_by_ID( 1 );
		}
		else
		{
			// echo 'Instanciating existing user';
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
	 * User::set(-)
	 *
	 * Set param value
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			// case 'icq':		// Dangerous: easy to forget it's not a string
			case 'level':
			case 'notify':
				parent::set( $parname, 'string', $parvalue );
			break;
			
			default:
				parent::set( $parname, 'string', $parvalue );
		}
	}

	/* 
	 * User::set_datecreated(-)
	 *
	 * Set date created
	 */
	function set_datecreated( $datecreated )
	{
		// Set value:
		$this->datecreated = date('Y-m-d H:i:s', $datecreated );
		// Remmeber change for later db update:
		$this->dbchange( 'dateYMDhour' , 'string', 'datecreated' );
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

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

	// Blog posts statuses permissions:
	var $blog_post_statuses = array();

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
				parent::set_param( $parname, 'int', $parvalue );
			break;
			
			default:
				parent::set_param( $parname, 'string', $parvalue );
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
	
	/*
	 * User::check_perm(-)
	 *
	 * Check permission
	 */
	function check_perm( $permname, $permlevel, $assert = false, $perm_target = NULL )
	{
		$perm = false;
	
		switch( $permname )
		{
			case 'blog_post_statuses':
			case 'blog_del_post':
			case 'blog_comments':
				$perm = $this->check_perm_blogusers( $permname, $permlevel, $perm_target );
				break;
			
			default:
				// Forward request to group:
				$perm = $this->Group->check_perm( $permname, $permlevel );
		}
		
		if( !$perm && $assert )
		{ // We can't let this go on!
			die( T_('Permission denied!') );
		}
		
		return $perm;
	}

	/*
	 * User::check_perm_blogusers(-)
	 *
	 * Check permission on specified blog
	 */
	function check_perm_blogusers( $permname, $permlevel, $perm_target_blog )
	{
		global $tableblogusers, $querycount;
		
		if( !isset( $this->blog_post_statuses[$perm_target_blog] ) )
		{	// Allowed blog post statuses have not been loaded yet:
			if( $this->ID == 0 )
			{	// User not in DB, nothing to load!:
				return false;	// Permission denied			
			}

			// Load now:
			// echo 'loading allowed statuses';
			$query = "SELECT *
								FROM $tableblogusers 
								WHERE bloguser_blog_ID = $perm_target_blog
								  AND bloguser_user_ID = $this->ID";
			// echo $query, '<br />';
			$result = mysql_query($query) or mysql_oops( $query ); 
			$querycount++; 
			$row = mysql_fetch_array($result);

			$this->blog_post_statuses[$perm_target_blog] = array();

			$bloguser_perm_post = $row['bloguser_perm_poststatuses'];
			if( empty($bloguser_perm_post ) )
				$this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] = array();
			else
				$this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] = explode( ',', $bloguser_perm_post );

			$this->blog_post_statuses[$perm_target_blog]['blog_del_post'] = $row['bloguser_perm_delpost'];
			$this->blog_post_statuses[$perm_target_blog]['blog_comments'] = $row['bloguser_perm_comments'];
			$this->blog_post_statuses[$perm_target_blog]['blog_cats'] = $row['bloguser_perm_cats'];
		}
	
		// Check if permission is granted:
		switch( $permname )
		{
			case 'blog_post_statuses':
				if( $permlevel == 'any' )
				{ // Any prermission will do:
					// echo count($this->blog_post_statuses);
					return ( count($this->blog_post_statuses[$perm_target_blog]['blog_post_statuses']) > 0 );
				}
				
				// We want a specific permission:
				// echo 'checking :', implode( ',', $this->blog_post_statuses  ), '<br />';
				return in_array( $permlevel, $this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] );

			default:
				// echo $permname, '=', $this->blog_post_statuses[$perm_target_blog][$permname], ' ';
				return $this->blog_post_statuses[$perm_target_blog][$permname];
		}
	}
	
	
	/*
	 * User::is_blog_member(-)
	 *
	 * Check if user is a member of specified blog
	 */
	function is_blog_member( $blog_ID )
	{
		/* echo 'blog=',$blog_ID ,'<br />';
		echo 'statuses=', $this->check_perm_blogusers( 'blog_post_statuses', 'any', $blog_ID ) ,'<br />';
		echo 'statuses=', $this->check_perm_blogusers( 'blog_del_post', 1, $blog_ID )  ,'<br />';
		echo 'statuses=', $this->check_perm_blogusers( 'blog_comments', 1, $blog_ID )  ,'<br />'; */
		return ( $this->check_perm_blogusers( 'blog_post_statuses', 'any', $blog_ID )
					 || $this->check_perm_blogusers( 'blog_del_post', 1, $blog_ID )
					 || $this->check_perm_blogusers( 'blog_comments', 1, $blog_ID ) );
	}
	
	
	/** 
	 * Delete user and dependencies from database
	 *
	 * Deleted dependencies:
	 * - users's posts
	 * - comments on this users' posts
	 * - user/blog permissions
	 *
	 * {@internal User::dbdelete(-) }
	 */
	function dbdelete()
	{
		global $querycount, $tablecomments, $tableposts, $tableblogusers;

		if( $this->ID == 0 ) die( 'Non persistant object cannot be deleted!' );

		// Delete comments
		$sql="DELETE FROM $tablecomments INNER JOIN $tableposts 
												ON comment_post_ID = ID
								 WHERE post_author = $this->ID";
		$result=mysql_query($sql) or mysql_oops( $sql );
		$querycount++;

		// Delete posts
		$sql="DELETE FROM $tableposts WHERE post_author = $this->ID";
		$result=mysql_query($sql) or mysql_oops( $sql );
		$querycount++;
		
		// Delete userblog permission
		$sql="DELETE FROM $tableblogusers WHERE bloguser_user_ID = $this->ID";
		$result=mysql_query($sql) or mysql_oops( $sql );
		$querycount++;

		// Delete main object:
		return parent::dbdelete();
	}
}
?>

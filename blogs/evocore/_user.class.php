<?php
/**
 * This file implements the User class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobject.class.php';

/**
 * User Class
 *
 * @package evocore
 */
class User extends DataObject
{
	var $login;
	var $pass;
	var $firstname;
	var $lastname;
	var $nickname;
	var $idmode;
	var $locale;
	var $email;
	var $url;
	var $icq;
	var $aim;
	var $msn;
	var $yim;
	var $ip;
	var $domain;
	var $browser;
	var $datecreated;
	var $level;
	var $notify;
	var $showonline;

	/**
	 * Number of posts by this user. Use getNumPosts() to access this (lazy filled).
	 * @var integer|NULL
	 * @access protected
	 */
	var $_numPosts;

	/**
	* @var NULL|Group Reference to group
	 */
	var $Group;

	/**
	 * Blog posts statuses permissions
	 */
	var $blog_post_statuses = array();


	/**
	 * Constructor
	 *
	 * {@internal User::User(-)}}
	 *
	 * @param object DB row
	 */
	function User( $db_row = NULL )
	{
		global $GroupCache, $default_locale, $Settings, $servertimenow;

		// Call parent constructor:
		parent::DataObject( 'T_users', 'user_' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_posts', 'fk'=>'post_lastedit_user_ID', 'msg'=>T_('%d posts last edited by this user') ),
				array( 'table'=>'T_posts', 'fk'=>'post_assigned_user_ID', 'msg'=>T_('%d posts assigned to this user') ),
				array( 'table'=>'T_links', 'fk'=>'link_creator_user_ID', 'msg'=>T_('%d links created by this user') ),
				array( 'table'=>'T_links', 'fk'=>'link_lastedit_user_ID', 'msg'=>T_('%d links last edited by this user') ),
			);

   	$this->delete_cascades = array(
				array( 'table'=>'T_usersettings', 'fk'=>'uset_user_ID', 'msg'=>T_('%d user settings on collections') ),
				array( 'table'=>'T_sessions', 'fk'=>'sess_user_ID', 'msg'=>T_('%d sessions opened by this user') ),
				array( 'table'=>'T_coll_user_perms', 'fk'=>'bloguser_user_ID', 'msg'=>T_('%d user permissions on blogs') ),
				array( 'table'=>'T_subscriptions', 'fk'=>'sub_user_ID', 'msg'=>T_('%d subscriptions') ),
				array( 'table'=>'T_posts', 'fk'=>'post_creator_user_ID', 'msg'=>T_('%d posts created by this user') ),
			);

		if( $db_row == NULL )
		{
			// echo 'Creating blank user';
			$this->login = 'login';
			$this->pass = md5('pass');
			$this->firstname = '';
			$this->lastname = '';
			$this->nickname = '';
			$this->idmode = 'login';
			$this->locale = (isset( $Settings )
											? $Settings->get('default_locale') // TODO: (settings) use "new users template setting"
											: $default_locale );
			$this->email = '';
			$this->url = '';
			$this->icq = 0;
			$this->aim = '';
			$this->msn = '';
			$this->yim = '';
			$this->ip = '';
			$this->domain = '';
			$this->browser = '';
			$this->set( 'level', isset( $Settings ) ? $Settings->get('newusers_level') : 0 );
			$this->notify = 1 ;
			$this->showonline = 1;
			if( isset($servertimenow) )
			{
				$this->datecreated = date('Y-m-d H:i:s', $servertimenow );
			}
			else
			{ // We don't know local time here!
				$this->datecreated = date('Y-m-d H:i:s', time() );
			}

			if( isset( $GroupCache ) )
			{ // Group for this user:
				$this->Group = $GroupCache->get_by_ID( $Settings->get('newusers_grp_ID') );
			}
		}
		else
		{
			// echo 'Instanciating existing user';
			$this->ID = $db_row->ID;
			$this->login =$db_row->user_login;
			$this->pass = $db_row->user_pass;
			$this->firstname = $db_row->user_firstname;
			$this->lastname = $db_row->user_lastname;
			$this->nickname = $db_row->user_nickname;
			$this->idmode = $db_row->user_idmode;
			$this->locale = $db_row->user_locale;
			$this->email = $db_row->user_email;
			$this->url = $db_row->user_url;
			$this->icq = $db_row->user_icq;
			$this->aim = $db_row->user_aim;
			$this->msn = $db_row->user_msn;
			$this->yim = $db_row->user_yim;
			$this->ip = $db_row->user_ip;
			$this->domain = $db_row->user_domain;
			$this->browser = $db_row->user_browser;
			$this->datecreated = $db_row->dateYMDhour;
			$this->level = $db_row->user_level;
			$this->notify = $db_row->user_notify;
			$this->showonline = $db_row->user_showonline;

			// Group for this user:
			$this->Group = $GroupCache->get_by_ID( $db_row->user_grp_ID );
		}
	}


	/**
	 * Get a param
	 *
	 * {@internal User::get(-)}}
	 * @param string the parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 'fullname':
				return $this->firstname.' '.$this->lastname;

			case 'preferedname':
				return $this->getPreferedName();

			case 'num_posts':
				return $this->getNumPosts();

			default:
			// All other params:
				return parent::get( $parname );
		}
	}


	/**
	 * Get prefered name for the user.
	 *
	 * @return string
	 */
	function getPreferedName()
	{
		switch( $this->idmode )
		{
			case 'namefl':
				return parent::get('firstname').' '.parent::get('lastname');

			case 'namelf':
				return parent::get('lastname').' '.parent::get('firstname');

			default:
				return parent::get($this->idmode);
		}
	}


	/**
	 * Get the number of posts for the user.
	 *
	 * @return integer
	 */
	function getNumPosts()
	{
		global $DB;

		if( is_null( $this->_numPosts ) )
		{
			$this->_numPosts = $DB->get_var( "SELECT count(*)
																				FROM T_posts
																				WHERE post_creator_user_ID = $this->ID" );
		}

		return $this->_numPosts;
	}


	/**
	 * Get the path to the media directory. If it does not exist, it will be created.
	 *
	 * @return mixed the path as string on success, false if the dir could not be created
	 */
	function getMediaDir()
	{
		global $basepath, $media_subdir, $Messages, $Settings, $Debuglog;

		if( ! $Settings->get( 'fm_enable_roots_user' ) )
		{	// User directories are disabled:
			$Debuglog->add( 'Attempt to access user media dir, but this feature is disabled', 'files' );
			return false;
		}

		$userdir = $basepath.$media_subdir.'users/'.$this->login.'/';
		if( !is_dir( $userdir ) )
		{
			if( !mkdir( $userdir ) ) // defaults to 0777
			{ // add error
				$Messages->add( sprintf( T_("The user's directory &laquo;%s&raquo; could not be created."), $userdir ), 'error' );
				return false;
			}
			else
			{ // add note
				$Messages->add( sprintf( T_("The user's directory &laquo;%s&raquo; has been created with permissions %s."), $userdir, '777' ), 'success' );
			}
		}
		return $userdir;
	}


	/**
	 * Get the URL to the media folder
	 *
	 * @return string the URL
	 */
	function getMediaUrl()
	{
		global $baseurl, $media_subdir, $Settings, $Debuglog;

 		if( ! $Settings->get( 'fm_enable_roots_user' ) )
		{	// User directories are disabled:
			$Debuglog->add( 'Attempt to access user media URL, but this feature is disabled', 'files' );
			return false;
		}

		return $baseurl.$media_subdir.'users/'.$this->login.'/';
	}


	/**
	 * Set param value
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			// case 'icq':		// Dangerous: easy to forget it's not a string
			case 'level':
			case 'notify':
				parent::set_param( $parname, 'number', $parvalue );
			break;

			default:
				parent::set_param( $parname, 'string', $parvalue );
		}
	}


	/**
	 * Set date created.
	 *
	 * @param integer seconds since Unix Epoch.
	 */
	function set_datecreated( $datecreated, $isYMDhour = false )
	{
		if( !$isYMDhour )
		{
			$datecreated = date('Y-m-d H:i:s', $datecreated );
		}
		// Set value:
		$this->datecreated = $datecreated;
		// Remmeber change for later db update:
		$this->dbchange( 'dateYMDhour', 'string', 'datecreated' );
	}


	/*
	 * User::setGroup(-)
	 *
	 * Set new Group
	 */
	function setGroup( & $Group )
	{
		$this->Group = & $Group;

		$this->dbchange( 'user_grp_ID', 'number', 'Group->get(\'ID\')' );
	}


	/**
	 * Check permission for this user
	 *
	 * {@internal User::check_perm(-) }}
	 *
	 * @param string Permission name, can be one of:
	 *                - 'upload'
	 *                - 'edit_timestamp'
	 *                - 'cats_post_statuses', see {@link User::check_perm_catsusers()}
	 *                - either group permission names, see {@link Group::check_perm()}
	 *                - either blogusers permission names, see {@link User::check_perm_blogusers()}
	 * @param string Permission level
	 * @param boolean Execution will halt if this is !0 and permission is denied
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean 0 if permission denied
	 */
	function check_perm( $permname, $permlevel = 'any', $assert = false, $perm_target = NULL )
	{
		global $Debuglog;

		$perm = false;

		switch( $permname )
		{ // What permission do we want to check?
			case 'edit_timestamp':
				// Global permission to edit timestamps...
				$perm = ($this->level >= 5);
				break;

			case 'cats_post_statuses':
				// Category permissions...
				$perm = $this->check_perm_catsusers( $permname, $permlevel, $perm_target );
				break;

			case 'blog_properties':
				// Blog permission to edit its properties... (depending on user AND its group)
				// Forward request to group:
				if( $this->Group->check_perm( 'blogs', $permlevel ) )
				{ // If group says yes
					$perm = true;
					break;
				}
				if( $perm_target > 0 )
				{ // Check user perm for this blog
					$perm = $this->check_perm_blogusers( $permname, $permlevel, $perm_target );
				}
				break;

			case 'blog_ismember':
			case 'blog_post_statuses':
			case 'blog_del_post':
			case 'blog_comments':
			case 'blog_cats':
			case 'blog_genstatic':
				// Blog permission to this or that... (depending on this user only)
				$perm = $this->check_perm_blogusers( $permname, $permlevel, $perm_target );
				break;

			default:
				// Other global permissions (see if the group can handle them), includes:
				// files
				// Forward request to group:
				$perm = $this->Group->check_perm( $permname, $permlevel, $perm_target );
		}

		if( !$perm && $assert )
		{ // We can't let this go on!
			die( "Permission denied! ($permname:$permlevel:$perm_target)" );
		}

		// echo "<br>Checking user perm $permname:$permlevel:$perm_target";
		$Debuglog->add( "User perm $permname:$permlevel:$perm_target => ".($perm?'granted':'denied'), 'perms' );

		return $perm;
	}


	/**
	 * Check permission for this user on a set of specified categories
	 *
	 * This is not for direct use, please call {@link User::check_perm()} instead
	 *
	 * {@internal User::check_perm_catsusers(-) }}
	 *
	 * @see User::check_perm()
	 * @param string Permission name, can be one of the following:
	 *                  - cat_post_statuses
	 *                  - more to come later...
	 * @param string Permission level
	 * @param array Array of target cat IDs
	 * @return boolean 0 if permission denied
	 */
	function check_perm_catsusers( $permname, $permlevel, & $perm_target_cats )
	{
		// Check if permission is granted:
		switch( $permname )
		{
			case 'cats_post_statuses':
				// We'll actually pass this on to blog permissions
				// First we need to create an array of blogs, not cats
				$perm_target_blogs = array();
				foreach( $perm_target_cats as $loop_cat_ID )
				{
					$loop_cat_blog_ID = get_catblog( $loop_cat_ID );
					// echo "cat $loop_cat_ID -> blog $loop_cat_blog_ID <br />";
					if( ! in_array( $loop_cat_blog_ID, $perm_target_blogs ) )
					{ // not already in list: add it:
						$perm_target_blogs[] = $loop_cat_blog_ID;
					}
				}
				// Now we'll check permissions for each blog:
				foreach( $perm_target_blogs as $loop_blog_ID )
				{
					if( ! $this->check_perm_blogusers( 'blog_post_statuses', $permlevel, $loop_blog_ID ) )
					{ // If at least one blog is denied:
						return false;	// permission denied
					}
				}
				return true;	// Permission granted
		}

		return false; 	// permission denied
	}


	/**
	 * Check permission for this user on a specified blog
	 *
	 * This is not for direct use, please call {@link User::check_perm()} instead
	 *
	 * {@internal User::check_perm_blogusers(-) }}
	 *
	 * @see User::check_perm()
	 * @param string Permission name, can be one of the following:
	 *                  - blog_ismember
	 *                  - blog_post_statuses
	 *                  - blog_del_post
	 *                  - blog_comments
	 *                  - blog_cats
	 *                  - blog_properties
	 *                  - blog_genstatic
	 * @param string Permission level
	 * @param integer Permission target blog ID
	 * @return boolean 0 if permission denied
	 */
	function check_perm_blogusers( $permname, $permlevel, $perm_target_blog )
	{
		global $DB;
		// echo "checkin for $permname >= $permlevel on blog $perm_target_blog<br />";

		if( !isset( $this->blog_post_statuses[$perm_target_blog] ) )
		{ // Allowed blog post statuses have not been loaded yet:
			if( $this->ID == 0 )
			{ // User not in DB, nothing to load!:
				return false;	// Permission denied
			}

			// Load now:
			// echo 'loading allowed statuses';
			$query = "SELECT *
								FROM T_coll_user_perms
								WHERE bloguser_blog_ID = $perm_target_blog
								  AND bloguser_user_ID = $this->ID";
			// echo $query, '<br />';
			if( ($row = $DB->get_row( $query, ARRAY_A )) == NULL )
			{ // No rights set for this Blog/User
				return false;	// Permission denied
			}
			else
			{ // OK, rights found:
				$this->blog_post_statuses[$perm_target_blog] = array();

				$this->blog_post_statuses[$perm_target_blog]['blog_ismember'] = $row['bloguser_ismember'];

				$bloguser_perm_post = $row['bloguser_perm_poststatuses'];
				if( empty($bloguser_perm_post ) )
					$this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] = array();
				else
					$this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] = explode( ',', $bloguser_perm_post );

				$this->blog_post_statuses[$perm_target_blog]['blog_del_post'] = $row['bloguser_perm_delpost'];
				$this->blog_post_statuses[$perm_target_blog]['blog_comments'] = $row['bloguser_perm_comments'];
				$this->blog_post_statuses[$perm_target_blog]['blog_cats'] = $row['bloguser_perm_cats'];
				$this->blog_post_statuses[$perm_target_blog]['blog_properties'] = $row['bloguser_perm_properties'];
			}
		}

		// Check if permission is granted:
		switch( $permname )
		{
			case 'blog_genstatic':
				return ($this->level >= 2);

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


	/**
	 * Delete user and dependencies from database
	 *
	 * Includes WAY TOO MANY requests because we try to be compatible with MySQL 3.23, bleh!
	 *
	 * {@internal User::dbdelete(-) }}
	 *
	 * @todo delete comments on user's posts
	 *
	 * @param boolean true if you want to echo progress
	 */
	function dbdelete( $echo = false )
	{
		global $DB;

		if( $this->ID == 0 ) die( 'Non persistant object cannot be deleted!' );

		$DB->begin();


		// Transform registered user comments to unregistered:
		if( $echo ) echo '<br />Transforming user\'s comments to unregistered comments... ';
		$ret = $DB->query( 'UPDATE T_comments
												SET comment_author_ID = NULL,
														comment_author = '.$DB->quote( $this->get('preferedname') ).',
														comment_author_email = '.$DB->quote( $this->get('email') ).',
														comment_author_url = '.$DB->quote( $this->get('url') ).'
												WHERE comment_author_ID = '.$this->ID );
		if( $echo ) printf( '(%d rows)', $ret );

		// Get list of posts that are going to be deleted (3.23)
		if( $echo ) echo '<br />Getting post list to delete... ';
		$post_list = $DB->get_list( "SELECT ID
																	FROM T_posts
																	WHERE post_creator_user_ID = $this->ID" );

		if( empty( $post_list ) )
		{
			echo 'None!';
		}
		else
		{
			// Delete comments
			if( $echo ) echo '<br />Deleting comments on user\'s posts... ';
			$ret = $DB->query( "DELETE FROM T_comments
													WHERE comment_post_ID IN ($post_list)" );
			if( $echo ) printf( '(%d rows)', $ret );

			// Delete post extracats
			if( $echo ) echo '<br />Deleting user\'s posts\' extracats... ';
			$ret = $DB->query(	"DELETE FROM T_postcats
													WHERE postcat_post_ID IN ($post_list)" );
			if( $echo ) printf( '(%d rows)', $ret );

			// Posts will we auto-deleted by parent method
		} // no posts

		// Delete main object:
		if( $echo ) echo '<br />Deleting User... ';
		parent::dbdelete();

		echo '<br />Done.</p>';
		$DB->commit();
	}


	function callback_optionsForIdMode( $value )
	{
		$field_options = '';
		$idmode = $this->get( 'idmode' );

		foreach( array( 'nickname' => array( T_('Nickname') ),
										'login' => array( T_('Login') ),
										'firstname' => array( T_('First name') ),
										'lastname' => array( T_('Last name') ),
										'namefl' => array( T_('First name').' '.T_('Last name'),
																				implode( ' ', array( $this->get('firstname'), $this->get('lastname') ) ) ),
										'namelf' => array( T_('Last name').' '.T_('First name'),
																				implode( ' ', array( $this->get('lastname'), $this->get('firstname') ) ) ),
										)
							as $lIdMode => $lInfo )
		{
			$disp = isset( $lInfo[1] ) ? $lInfo[1] : $this->get($lIdMode);

			$field_options .= '<option value="'.$lIdMode.'"';
			if( $value == $lIdMode )
			{
				$field_options .= ' selected="selected"';
			}
			$field_options .= '>'.( !empty( $disp ) ? $disp.' ' : ' - ' )
												.'&laquo;'.$lInfo[0].'&raquo;'
												.'</option>';
		}

		return $field_options;
	}


	// Template functions {{{

	/**
	 * Template function: display user's level
	 *
	 * {@internal User::level(-) }}
	 */
	function level()
	{
		$this->disp( 'level', 'raw' );
	}


	/**
	 * Template function: display user's login
	 *
	 * {@internal User::login(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function login( $format = 'htmlbody' )
	{
		$this->disp( 'login', $format );
	}


	/**
	 * Template function: display a link to a message form for this user
	 *
	 * {@internal User::msgform_link(-)}}
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function msgform_link( $form_url = NULL, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		global $img_url;

		if( empty($this->email) )
		{ // We have no email for this User :(
			return false;
		}

		if( is_null($form_url) )
		{
			global $Blog;
			$form_url = isset($Blog) ? $Blog->get('msgformurl') : '';
		}

		$form_url = url_add_param( $form_url, 'recipient_id='.$this->ID );

		if( $title == '#' ) $title = T_('Send email to user');
		if( $text == '#' ) $text = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $title ) );

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Template function: display user's prefered name
	 *
	 * {@internal User::prefered_name(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function prefered_name( $format = 'htmlbody', $disp = true )
	{
		if( $disp )
		{
			$this->disp( 'preferedname', $format );
		}
		else
		{
			return $this->dget( 'preferedname', $format );
		}
	}


	/**
	 * Return user's prefered name
	 *
	 * {@internal User::prefered_name(-) }}
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function prefered_name_return( $format = 'htmlbody' )
	{
		return $this->prefered_name( $format, false );
	}


	/**
	 * Template function: display user's URL
	 *
	 * {@internal User::url(-) }}
	 *
	 * @param string string to display before the date (if changed)
	 * @param string string to display after the date (if changed)
	 * @param string Output format, see {@link format_to_output()}
	 */
	function url( $before = '', $after = '', $format = 'htmlbody' )
	{
		if( !empty( $this->url ) )
		{
			echo $before;
			$this->disp( 'url', $format );
			echo $after;
		}
	}


	/**
	 * Template function: display number of user's posts
	 */
	function numPosts( $format = 'htmlbody' )
	{
		echo format_to_output( $this->getNumPosts(), $format );
	}


	/**
	 * Template function: display first name of the user
	 */
	function firstName( $format = 'htmlbody' )
	{
		$this->disp( 'firstname', $format );
	}


	/**
	 * Template function: display last name of the user
	 */
	function lastName( $format = 'htmlbody' )
	{
		$this->disp( 'lastname', $format );
	}


	/**
	 * Template function: display nickname of the user
	 */
	function nickName( $format = 'htmlbody' )
	{
		$this->disp( 'nickname', $format );
	}


	/**
	 * Template function: display email of the user
	 */
	function email( $format = 'htmlbody' )
	{
		$this->disp( 'email', $format );
	}


	/**
	 * Template function: display ICQ of the user
	 */
	function icq( $format = 'htmlbody' )
	{
		$this->disp( 'icq', $format );
	}


	/**
	 * Template function: display AIM of the user.
	 *
	 * NOTE: Replaces spaces with '+' ?!?
	 */
	function aim( $format = 'htmlbody' )
	{
		echo format_to_output( str_replace(' ', '+', $this->get('aim') ), $format );
	}


	/**
	 * Template function: display Yahoo IM of the user
	 */
	function yim( $format = 'htmlbody' )
	{
		$this->disp( 'yim', $format );
	}


	/**
	 * Template function: display MSN of the user
	 */
	function msn( $format = 'htmlbody' )
	{
		$this->disp( 'msn', $format );
	}

	// }}}

}

/*
 * $Log$
 * Revision 1.36  2005/06/20 17:40:23  fplanque
 * minor
 *
 * Revision 1.35  2005/06/10 23:21:12  fplanque
 * minor bugfixes
 *
 * Revision 1.34  2005/06/06 17:59:39  fplanque
 * user dialog enhancements
 *
 * Revision 1.33  2005/06/03 15:12:33  fplanque
 * error/info message cleanup
 *
 * Revision 1.32  2005/05/25 17:13:33  fplanque
 * implemented email notifications on new comments/trackbacks
 *
 * Revision 1.31  2005/05/12 18:39:24  fplanque
 * storing multi homed/relative pathnames for file meta data
 *
 * Revision 1.30  2005/05/11 13:21:38  fplanque
 * allow disabling of mediua dir for specific blogs
 *
 * Revision 1.29  2005/05/10 18:40:08  fplanque
 * normalizing
 *
 * Revision 1.28  2005/05/09 16:09:42  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.27  2005/05/06 20:04:48  fplanque
 * added contribs
 * fixed filemanager settings
 *
 * Revision 1.26  2005/05/04 18:16:55  fplanque
 * Normalizing
 *
 * Revision 1.25  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.22  2005/03/22 19:17:27  fplanque
 * cleaned up some nonsense...
 *
 * Revision 1.21  2005/03/15 19:19:48  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.20  2005/03/13 19:52:14  blueyed
 * use $servertimenow for creation of new blank user
 *
 * Revision 1.19  2005/03/07 00:17:16  blueyed
 * use $Settings instead of $default_locale
 *
 * Revision 1.18  2005/03/02 18:30:56  fplanque
 * tedious merging... :/
 *
 * Revision 1.17  2005/03/01 23:26:34  blueyed
 * upload perms..
 *
 * Revision 1.16  2005/02/28 09:06:34  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.15  2005/02/27 20:24:48  blueyed
 * minor
 *
 * Revision 1.14  2005/02/23 21:43:30  blueyed
 * fix instantiating new User without existing $GroupCache (install)
 *
 * Revision 1.13  2005/02/23 04:26:18  blueyed
 * moved global $start_of_week into $locales properties
 *
 * Revision 1.12  2005/02/22 02:53:33  blueyed
 * getPreferedName()
 *
 * Revision 1.11  2005/02/20 22:41:13  blueyed
 * use setters in constructor (dbchange()), fixed getNumPosts(), enhanced set_datecreated(), fixed setGroup()
 *
 * Revision 1.10  2005/02/19 18:54:52  blueyed
 * doc
 *
 * Revision 1.9  2005/02/15 22:05:10  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.8  2005/01/20 20:37:59  fplanque
 * bugfix
 *
 * Revision 1.7  2005/01/13 19:53:51  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.6  2004/12/29 04:30:58  blueyed
 * removed safefilename()
 *
 * Revision 1.5  2004/12/10 19:45:55  fplanque
 * refactoring
 *
 * Revision 1.4  2004/11/15 18:57:05  fplanque
 * cosmetics
 *
 * Revision 1.3  2004/11/09 00:25:12  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.58  2004/10/12 17:22:29  fplanque
 * Edited code documentation.
 *
 */
?>
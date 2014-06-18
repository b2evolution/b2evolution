<?php
/**
 * This file implements the Group class, which manages user groups.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _group.class.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );
load_class( 'users/model/_groupsettings.class.php', 'GroupSettings' );

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
	var $name;

	/**
	 * Blog posts statuses permissions
	 */
	var $blog_post_statuses = array();

	var $perm_blogs;
	var $perm_security;
	var $perm_bypass_antispam = false;
	var $perm_xhtmlvalidation = 'always';
	var $perm_xhtmlvalidation_xmlrpc = 'always';
	var $perm_xhtml_css_tweaks = false;
	var $perm_xhtml_iframes = false;
	var $perm_xhtml_javascript = false;
	var $perm_xhtml_objects = false;
	var $perm_stats;

	/**
	 * Pluggable group permissions
	 *
	 * @var Instance of GroupSettings class
	 */
	var $GroupSettings;


	/**
	 * Constructor
	 *
	 * @param object DB row
	 */
	function Group( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_groups', 'grp_', 'grp_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_users', 'fk'=>'user_grp_ID', 'msg'=>T_('%d users in this group') ),
			);

		$this->delete_cascades = array(
			);

		if( $db_row == NULL )
		{
			// echo 'Creating blank group';
			$this->set( 'name', T_('New group') );
			$this->set( 'perm_blogs', 'user' );
			$this->set( 'perm_stats', 'none' );
		}
		else
		{
			// echo 'Instanciating existing group';
			$this->ID                           = $db_row->grp_ID;
			$this->name                         = $db_row->grp_name;
			$this->perm_blogs                   = $db_row->grp_perm_blogs;
			$this->perm_bypass_antispam         = $db_row->grp_perm_bypass_antispam;
			$this->perm_xhtmlvalidation         = $db_row->grp_perm_xhtmlvalidation;
			$this->perm_xhtmlvalidation_xmlrpc  = $db_row->grp_perm_xhtmlvalidation_xmlrpc;
			$this->perm_xhtml_css_tweaks        = $db_row->grp_perm_xhtml_css_tweaks;
			$this->perm_xhtml_iframes           = $db_row->grp_perm_xhtml_iframes;
			$this->perm_xhtml_javascript        = $db_row->grp_perm_xhtml_javascript;
			$this->perm_xhtml_objects           = $db_row->grp_perm_xhtml_objects;
			$this->perm_stats                   = $db_row->grp_perm_stats;
		}
	}

	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Messages, $demo_mode;

		// Edited Group Name
		param( 'edited_grp_name', 'string' );
		param_check_not_empty( 'edited_grp_name', T_('You must provide a group name!') );
		$this->set_from_Request('name', 'edited_grp_name', true);

		// Edited Group Permission Blogs
		param( 'edited_grp_perm_blogs', 'string', true );
		$this->set_from_Request( 'perm_blogs', 'edited_grp_perm_blogs', true );

		$apply_antispam = ( param( 'apply_antispam', 'integer', 0 ) ? 0 : 1 );
		$perm_xhtmlvalidation = param( 'perm_xhtmlvalidation', 'string', true );
		$perm_xhtmlvalidation_xmlrpc = param( 'perm_xhtmlvalidation_xmlrpc', 'string', true );
		$prevent_css_tweaks = ( param( 'prevent_css_tweaks', 'integer', 0 ) ? 0 : 1 );
		$prevent_iframes = ( param( 'prevent_iframes', 'integer', 0 ) ? 0 : 1 );
		$prevent_javascript = ( param( 'prevent_javascript', 'integer', 0 ) ? 0 : 1 );
		$prevent_objects = ( param( 'prevent_objects', 'integer', 0 ) ? 0 : 1 );

		if( $demo_mode && ( $apply_antispam || ( $perm_xhtmlvalidation != 'always' ) && ( $perm_xhtmlvalidation_xmlrpc != 'always' )
			 || $prevent_css_tweaks || $prevent_iframes || $prevent_javascript || $prevent_objects ) )
		{ // Demo mode restriction: Do not allow to change these settings in demo mode, because it may lead to security problem!
			$Messages->add( 'Validation settings and security filters are not editable in demo mode!', 'error' );
		}
		else
		{
			// Apply Antispam
			$this->set( 'perm_bypass_antispam', $apply_antispam );

			// XHTML Validation
			$this->set( 'perm_xhtmlvalidation', $perm_xhtmlvalidation );

			// XHTML Validation XMLRPC
			$this->set( 'perm_xhtmlvalidation_xmlrpc', $perm_xhtmlvalidation_xmlrpc );

			// CSS Tweaks
			$this->set( 'perm_xhtml_css_tweaks', $prevent_css_tweaks );

			// Iframes
			$this->set( 'perm_xhtml_iframes', $prevent_iframes );

			// Javascript
			$this->set( 'perm_xhtml_javascript', $prevent_javascript );

			// Objects
			$this->set( 'perm_xhtml_objects', $prevent_objects );
		}

		// Stats
		$this->set( 'perm_stats', param( 'edited_grp_perm_stats', 'string', true ) );

		// Load pluggable group permissions from request
		$GroupSettings = & $this->get_GroupSettings();
		foreach( $GroupSettings->permission_values as $name => $value )
		{
			// We need to handle checkboxes and radioboxes separately , because when a checkbox isn't checked the checkbox variable is not sent
			if( $name == 'perm_createblog' || $name == 'perm_getblog' || $name == 'perm_templates'
				|| $name == 'cross_country_allow_profiles' || $name == 'cross_country_allow_contact' )
			{ // These permissions are represented by checkboxes, all other pluggable group permissions are represented by radiobox.
				$value = param( 'edited_grp_'.$name, 'string', 'denied' );
			}
			elseif( ( $name == 'perm_admin' || $name == 'perm_users' ) && ( $this->ID == 1 ) )
			{ // Admin group has always admin perm, it can not be set or changed.
				continue;
			}
			else
			{
				$value = param( 'edited_grp_'.$name, 'string', '' );
			}
			if( ( $value != '') || ( $name == 'max_new_threads'/*allow empty*/ ) )
			{ // if radio is not set, then doesn't change the settings
				$GroupSettings->set( $name, $value, $this->ID );
			}
		}

		return !param_errors_detected();
	}


	/**
	 * Set param value
	 *
	 * @param string Parameter name
	 * @param mixed Parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'perm_templates':
				return $this->set_param( $parname, 'number', $parvalue, $make_null );

			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get the {@link GroupSettings} of the group.
	 *
	 * @return GroupSettings (by reference)
	 */
	function & get_GroupSettings()
	{
		if( ! isset( $this->GroupSettings ) )
		{
			$this->GroupSettings = new GroupSettings();
			$this->GroupSettings->load( $this->ID );
		}
		return $this->GroupSettings;
	}


	/**
	 * Check a permission for this group.
	 *
	 * @param string Permission name:
	 *                - templates
	 *                - stats
	 *                - spamblacklist
	 *                - options
	 *                - users
	 *                - blogs
	 *                - admin (levels "visible", "hidden")
	 *                - messaging
	 * @param string Requested permission level
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_perm( $permname, $permlevel = 'any', $perm_target = NULL )
	{
		global $Debuglog;

		$perm = false; // Default is false!

		// echo "<br>Checking group perm $permname:$permlevel against $permvalue";
		if( isset($this->{'perm_'.$permname}) )
		{
			$permvalue = $this->{'perm_'.$permname};
		}
		else
		{ // Object's perm-property not set!
			$Debuglog->add( 'Group permission perm_'.$permname.' not defined!', 'perms' );

			$permvalue = false; // This will result in $perm == false always. We go on for the $Debuglog..
		}

		$pluggable_perms = array( 'admin', 'shared_root', 'import_root', 'spamblacklist', 'slugs', 'templates', 'options', 'emails', 'files', 'users' );
		if( in_array( $permname, $pluggable_perms ) )
		{
			$permname = 'perm_'.$permname;
		}
		// echo "<br>Checking group perm $permname:$permlevel against $permvalue";

		// Check group permission:
		switch( $permname )
		{
			case 'blogs':
				switch( $permvalue )
				{ // Depending on current group permission:

					case 'editall':
						// All permissions granted
						$perm = true;
						break;

					case 'viewall':
						// User can only ask for view perm
						if(( $permlevel == 'view' ) || ( $permlevel == 'any' ))
						{ // Permission granted
							$perm = true;
							break;
						}
				}

				if( ! $perm && ( $permlevel == 'create' ) && $this->check_perm( 'perm_createblog', 'allowed' ) )
				{ // User is allowed to create a blog (for himself)
					$perm = true;
				}
				break;

			case 'stats':
				if( ! $this->check_perm( 'admin', 'restricted' ) )
				{
					$perm = false;
					break;
				}
				switch( $permvalue )
				{ // Depending on current group permission:

					case 'edit':
						// All permissions granted
						$perm = true;
						break;

					case 'view':
						// User can ask for view perm...
						if( $permlevel == 'view' )
						{
							$perm = true;
							break;
						}
						// ... or for any lower priority perm... (no break)

					case 'user':
						// This is for stats. User perm can grant permissions in the User class
						// Here it will just allow to list
					case 'list':
						// User can only ask for list perm
						if( $permlevel == 'list' )
						{
							$perm = true;
							break;
						}
				}
				break;

			case 'perm_files':
				if( ! $this->check_perm( 'admin', 'restricted' ) )
				{
					$perm = false;
					break;
				}
				// no break, perm_files is pluggable permission

			default:

				// Check pluggable permissions using group permission check function
				$perm = Module::check_perm( $permname, $permlevel, $perm_target, 'group_func', $this );
				if( $perm === NULL )
				{	// Even if group permisson check function doesn't exist we should return false value
					$perm = false;
				}

				break;
		}

		$target_ID = $perm_target;
		if( is_object($perm_target) ) $target_ID = $perm_target->ID;

		$Debuglog->add( "Group perm $permname:$permlevel:$target_ID => ".($perm?'granted':'DENIED'), 'perms' );

		return $perm;
	}


	/**
	 * Check permission for this group on a specified blog
	 *
	 * This is not for direct use, please call {@link User::check_perm()} instead
	 * user is checked for privileges first, group lookup only performed on a false result
	 *
	 * @see User::check_perm()
	 * @param string Permission name can be any from the blog advanced perm names. A few possible permname:
	 *                  - blog_ismember
	 *                  - blog_can_be_assignee
	 *                  - blog_del_post
	 *                  - blog_edit_ts
	 *                  - blog_post_statuses
	 *                  - blog_edit
	 *                  - blog_comment_statuses
	 *                  - blog_edit_cmt
	 *                  - blog_cats
	 *                  - blog_properties
	 * @param string Permission level
	 * @param integer Permission target blog ID
	 * @param Item post that we want to edit
	 * @param User for who we would like to check this permission
	 * @return boolean 0 if permission denied
	 */
	function check_perm_bloggroups( $permname, $permlevel, $perm_target_blog, $perm_target = NULL, $User = NULL )
	{
		if( !isset( $this->blog_post_statuses[$perm_target_blog] ) )
		{
			$this->blog_post_statuses[$perm_target_blog] = array();
			if( ! load_blog_advanced_perms( $this->blog_post_statuses[$perm_target_blog], $perm_target_blog, $this->ID, 'bloggroup' ) )
			{ // Could not load blog advanced user perms
				return false;
			}
		}

		$blog_perms = $this->blog_post_statuses[$perm_target_blog];
		if( empty( $User ) )
		{ // User is not set
			$user_ID = NULL;
		}
		else
		{ // User is set, advanced user perms must be loaded
			$user_ID = $User->ID;
			if( isset( $User->blog_post_statuses[$perm_target_blog] ) )
			{ // Merge user advanced perms with group advanced perms
				$edit_perms = array( 'no' => 0, 'own' => 1, 'anon' => 2, 'lt' => 3, 'le' => 4, 'all' => 5 );
				foreach( $User->blog_post_statuses[$perm_target_blog] as $key => $value )
				{ // For each collection advanced permission use the higher perm value between user and group perms
					if( ( $key == 'blog_edit' ) || ( $key == 'blog_edit_cmt' ) )
					{
						if( $edit_perms[$value] > $edit_perms[$blog_perms[$key]] )
						{ // Use collection user edit permission because it is greater than the collection group perm
							$blog_perms[$key] = $value;
						}
					}
					elseif( isset( $blog_perms[$key] ) )
					{ // Check user and group perm as well
						$blog_perms[$key] = (int) $value | (int) $blog_perms[$key];
					}
				}
			}
		}
		return check_blog_advanced_perm( $blog_perms, $user_ID, $permname, $permlevel, $perm_target );
	}


	/**
	 * Get name of the Group
	 *
	 * @return string
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 */
	function dbinsert()
	{
		global $DB;

		$DB->begin();

		parent::dbinsert();

		// Create group permissions/settings for the current group
		$GroupSettings = & $this->get_GroupSettings();
		$GroupSettings->dbupdate( $this->ID );

		$DB->commit();
	}


	/**
	 * Update the DB based on previously recorded changes
	 */
	function dbupdate()
	{
		global $DB;

		$DB->begin();

		parent::dbupdate();

		// Update group permissions/settings of the current group
		$GroupSettings = & $this->get_GroupSettings();
		$GroupSettings->dbupdate( $this->ID );

		$DB->commit();
	}


	/**
	 * Delete object from DB.
	 */
	function dbdelete( $Messages = NULL )
	{
		global $DB;

		$DB->begin();

		// Delete group permissions of the current group
		$GroupSettings = & $this->get_GroupSettings();
		$GroupSettings->delete( $this->ID );
		$GroupSettings->dbupdate( $this->ID );

		parent::dbdelete( $Messages );

		$DB->commit();
	}
}

?>
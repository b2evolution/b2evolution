<?php
/**
 * This file implements the Group class, which manages user groups.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
	 * Usage of group: 'primary' or 'secondary'
	 *
	 * Please use get/set functions to read or write this param
	 *
	 * @var string
	 * @access protected
	 */
	var $usage;

	/**
	 * Level of group
	 *
	 * Please use get/set functions to read or write this param
	 *
	 * @var integer
	 * @access protected
	 */
	var $level;

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
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_groups', 'grp_', 'grp_ID' );

		if( $db_row == NULL )
		{
			// echo 'Creating blank group';
			$this->set( 'name', T_('New group') );
			$this->set( 'perm_blogs', 'user' );
			$this->set( 'perm_stats', 'none' );
			$this->set( 'usage', 'primary' );
		}
		else
		{
			// echo 'Instanciating existing group';
			$this->ID                           = $db_row->grp_ID;
			$this->name                         = $db_row->grp_name;
			$this->usage                        = $db_row->grp_usage;
			$this->level                        = $db_row->grp_level;
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
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table'=>'T_users', 'fk'=>'user_grp_ID', 'msg'=>T_('%d users in this group') ),
				array( 'table'=>'T_users__invitation_code', 'fk'=>'ivc_grp_ID', 'msg'=>T_('%d user invitation codes in this group') ),
			);
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

		// Edited Group Usage:
		$usage = param( 'edited_grp_usage', 'string' );
		if( $this->ID > 0 && $usage != $this->get( 'usage' ) )
		{	// Display a warning if group usage has been changed:
			global $DB;
			if( $usage == 'primary' )
			{
				$group_users_count = intval( $DB->get_var( 'SELECT COUNT( sug_grp_ID ) FROM T_users__secondary_user_groups WHERE sug_grp_ID = '.$this->ID ) );
				if( $group_users_count > 0 )
				{
					$Messages->add( sprintf( T_('You made this group primary but there are %d users still using it as a secondary group.'), $group_users_count ), 'warning' );
				}
			}
			else
			{
				$group_users_count = intval( $DB->get_var( 'SELECT COUNT( user_ID ) FROM T_users WHERE user_grp_ID = '.$this->ID ) );
				if( $group_users_count > 0 )
				{
					$Messages->add( sprintf( T_('You made this group secondary but there are %d users still using it as a primary group.'), $group_users_count ), 'warning' );
				}
			}
		}
		$this->set_from_Request( 'usage', 'edited_grp_usage' );

		// Edited Group Level
		param_integer_range( 'edited_grp_level', 0, 10, T_('Group level must be between %d and %d.') );
		$this->set_from_Request( 'level', 'edited_grp_level', true );

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
			if( $name == 'perm_createblog' || $name == 'perm_getblog' || $name == 'perm_centralantispam'
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
			if( ( $value != '') || ( $name == 'max_new_threads'/*allow empty*/ ) || ( $name == 'perm_max_createblog_num' ) )
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
	 *                - centralantispam
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

		$pluggable_perms = array( 'admin', 'shared_root', 'import_root', 'skins_root', 'spamblacklist', 'slugs', 'templates', 'options', 'emails', 'files', 'users', 'orgs', 'centralantispam' );
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
						// User can permissions to view all collections
						if( $permlevel == 'view' || $permlevel == 'list' )
						{
							$perm = true;
							break;
						}
						break;

					case 'user':
						// This is for stats. User perm can grant permissions in the User class
						// Here it will just allow to list
					case 'list':
						// User can only ask for list perm
						// But for requested collection we should check perm in user/group perms of the collections
						if( $permlevel == 'list' && $perm_target === NULL )
						{
							$perm = check_coll_first_perm( 'perm_analytics', 'group', $this->ID );
							break;
						}
				}
				break;

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
	 * Get name of the Group with level
	 *
	 * @return string
	 */
	function get_name()
	{
		return $this->name.' ('.$this->level.')';
	}


	/**
	 * Get name of the Group without level
	 *
	 * @return string
	 */
	function get_name_without_level()
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
		$GroupSettings->update( $this->ID );

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
		$GroupSettings->update( $this->ID );

		$DB->commit();
	}


	/**
	 * Delete object from DB.
	 */
	function dbdelete()
	{
		global $DB;

		$DB->begin();

		// Delete group permissions of the current group
		$GroupSettings = & $this->get_GroupSettings();
		$GroupSettings->delete( $this->ID );
		$GroupSettings->dbupdate( $this->ID );

		parent::dbdelete();

		$DB->commit();
	}


	/**
	 * Check if this group can be assigned by current user
	 *
	 * @return boolean TRUE if current use can assign this group to users
	 */
	function can_be_assigned()
	{
		global $current_User;

		if( ! is_logged_in() )
		{	// User must be assigned:
			return false;
		}

		if( $current_User->check_perm( 'users', 'edit' ) )
		{	// Allow to assing any group if current user has full access to edit users:
			return true;
		}

		if( ! $current_User->check_perm( 'users', 'moderate' ) )
		{	// User must has a permission at least to modearate the users:
			return false;
		}

		$user_Group = & $current_User->get_Group();

		if( ! $user_Group )
		{	// User's group must be defined:
			return false;
		}

		// Current user can assign this group if his group level is more than level of this group
		return ( $this->get( 'level' ) < $user_Group->get( 'level' ) );
	}
}

?>
<?php
/**
 * This file implements the Group class, which manages user groups.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class('_core/model/dataobjects/_dataobject.class.php');

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

	var $perm_admin;
  var $perm_blogs;
  var $perm_security;
  var $perm_xhtmlvalidation = 'always';
  var $perm_xhtmlvalidation_xmlrpc = 'always';
	var $perm_xhtml_css_tweaks = false;
	var $perm_xhtml_iframes = false;
	var $perm_xhtml_javascript = false;
	var $perm_xhtml_objects = false;
  var $perm_spamblacklist;
  var $perm_templates;
  var $perm_stats;
  var $perm_files;
  var $perm_options;
  var $perm_users;


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
			$this->set( 'perm_admin', 'visible' );
			$this->set( 'perm_blogs', 'user' );
			$this->set( 'perm_spamblacklist', 'none' );
			$this->set( 'perm_templates', 0 );
			$this->set( 'perm_stats', 'none' );
			$this->set( 'perm_files', 'none' );
			$this->set( 'perm_options', 'none' );
			$this->set( 'perm_users', 'none' );
		}
		else
		{
			// echo 'Instanciating existing group';
			$this->ID = $db_row->grp_ID;
			$this->name = $db_row->grp_name;
			$this->perm_admin = $db_row->grp_perm_admin;
			$this->perm_blogs = $db_row->grp_perm_blogs;
			$this->perm_xhtmlvalidation         = $db_row->grp_perm_xhtmlvalidation;
			$this->perm_xhtmlvalidation_xmlrpc  = $db_row->grp_perm_xhtmlvalidation_xmlrpc;
			$this->perm_xhtml_css_tweaks        = $db_row->grp_perm_xhtml_css_tweaks;
			$this->perm_xhtml_iframes           = $db_row->grp_perm_xhtml_iframes;
			$this->perm_xhtml_javascript        = $db_row->grp_perm_xhtml_javascript;
			$this->perm_xhtml_objects           = $db_row->grp_perm_xhtml_objects;
			$this->perm_spamblacklist = $db_row->grp_perm_spamblacklist;
			$this->perm_templates = $db_row->grp_perm_templates;
			$this->perm_stats = $db_row->grp_perm_stats;
			$this->perm_files = $db_row->grp_perm_files;
			$this->perm_options = $db_row->grp_perm_options;
			$this->perm_users = $db_row->grp_perm_users;
		}
	}


	/**
	 * Set param value
	 *
	 * @param string Parameter name
	 * @param mixed Parameter value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue )
	{
		switch( $parname )
		{
			case 'perm_templates':
				return parent::set_param( $parname, 'number', $parvalue );

			default:
				return parent::set_param( $parname, 'string', $parvalue );
		}
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

		// echo "<br>Checking group perm $permname:$permlevel against $permvalue";

		// Check group permission:
		switch( $permname )
		{
			case 'admin':
				switch( $permvalue )
				{ // Depending on current group permission:

					case 'visible':
						// All permissions granted
						$perm = true; // Permission granted
						break;

					case 'hidden':
						// User can only ask for hidden perm
						if(( $permlevel == 'hidden' ) || ( $permlevel == 'any' ))
						{ // Permission granted
							$perm = true;
							break;
						}
				}
				break;

			case 'templates':
				if( $permvalue )
				{ // Permission granted
					$perm = true;
				}
				break;

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
				break;

			case 'spamblacklist':
			case 'stats':
			case 'options':
			case 'users':
				switch( $permvalue )
				{ // Depending on current group permission:

					case 'edit':
						// All permissions granted
						$perm = true;
						break;

					case 'add':
						// User can ask for add perm...
						if( $permlevel == 'add' )
						{
							$perm = true;
							break;
						}
						// ... or for any lower priority perm... (no break)

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

			case 'files':
				switch( $permvalue )
				{ // Depending on current group permission:
					case 'all':
						// All permissions granted
						$perm = true;
						break;

					case 'edit':
						// User can ask for normal edit perm...
						if( $permlevel == 'edit' )
						{
							$perm = true;
							break;
						}
						// ... or for any lower priority perm... (no break)

					case 'add':
						// User can ask for add perm...
						if( $permlevel == 'add' )
						{
							$perm = true;
							break;
						}
						// ... or for any lower priority perm... (no break)

					case 'view':
						// User can ask for view perm...
						if( $permlevel == 'view' )
						{
							$perm = true;
							break;
						}
						// ... or for any lower priority perm... (no break)

					case 'list':
						// User can only ask for list perm
						if( $permlevel == 'list' )
						{
							$perm = true;
							break;
						}
				}
				break;
		}

		$Debuglog->add( "Group perm $permname:$permlevel:$perm_target => ".($perm?'granted':'DENIED'), 'perms' );

		return $perm;
	}


	/**
	 * Check permission for this group on a set of specified categories
	 *
	 * This is not for direct use, please call {@link User::check_perm()} instead
	 *
	 * @see User::check_perm()
	 * @param string Permission name, can be one of the following:
	 *                  - cat_post_statuses
	 *                  - more to come later...
	 * @param string Permission level
	 * @param array Array of target cat IDs
	 * @return boolean 0 if permission denied
	 */
	function check_perm_catsgroups( $permname, $permlevel, & $perm_target_cats )
	{
		// Check if permission is granted:
		switch( $permname )
		{
			case 'cats_post_statuses':
			case 'cats_post!published':
			case 'cats_post!protected':
			case 'cats_post!private':
			case 'cats_post!draft':
			case 'cats_post!deprecated':
			case 'cats_post!redirected':
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
					if( ! $this->check_perm( 'blog_'.substr($permname,5), $permlevel, $loop_blog_ID ) )
					{ // If at least one blog is denied:
						return false;	// permission denied
					}
				}
				return true;	// Permission granted
		}

		return false; 	// permission denied
	}


	/**
	 * Check permission for this group on a specified blog
	 *
	 * This is not for direct use, please call {@link User::check_perm()} instead
	 * user is checked for privileges first, group lookup only performed on a false result
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
	 * @param Item post that we want to edit
	 * @return boolean 0 if permission denied
	 */
	function check_perm_bloggroups( $permname, $permlevel, $perm_target_blog, $Item = NULL, $User = NULL )
	{
		global $DB;
		// echo "checkin for $permname >= $permlevel on blog $perm_target_blog<br />";

		$BlogCache = & get_Cache('BlogCache');
    /**
		 * @var Blog
		 */
		$Blog = & $BlogCache->get_by_ID( $perm_target_blog );
		if( ! $Blog->advanced_perms )
		{	// We do not abide to advanced perms
			return false;
		}

		if( !isset( $this->blog_post_statuses[$perm_target_blog] ) )
		{ // Allowed blog post statuses have not been loaded yet:
			if( $this->ID == 0 )
			{ // User not in DB, nothing to load!:
				return false;	// Permission denied
			}

			// Load now:
			// echo 'loading allowed statuses';
			$query = "SELECT *
								FROM T_coll_group_perms
								WHERE bloggroup_blog_ID = $perm_target_blog
								  AND bloggroup_group_ID = $this->ID";

			$row = $DB->get_row( $query, ARRAY_A );

			if( empty($row) )
			{ // No rights set for this Blog/Group: remember this (in order not to have the same query next time)
				$this->blog_post_statuses[$perm_target_blog] = array(
						'blog_ismember' => '0',
						'blog_post_statuses' => array(),
						'blog_edit' => 'no',
						'blog_del_post' => '0',
						'blog_comments' => '0',
						'blog_cats' => '0',
						'blog_properties' => '0',
						'blog_admin' => '0',
					);
			}
			else
			{ // OK, rights found:
				$this->blog_post_statuses[$perm_target_blog] = array();

				$this->blog_post_statuses[$perm_target_blog]['blog_ismember'] = $row['bloggroup_ismember'];

				$bloggroup_perm_post = $row['bloggroup_perm_poststatuses'];
				if( empty($bloggroup_perm_post ) )
					$this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] = array();
				else
					$this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] = explode( ',', $bloggroup_perm_post );

				$this->blog_post_statuses[$perm_target_blog]['blog_edit'] = $row['bloggroup_perm_edit'];
				$this->blog_post_statuses[$perm_target_blog]['blog_del_post'] = $row['bloggroup_perm_delpost'];
				$this->blog_post_statuses[$perm_target_blog]['blog_comments'] = $row['bloggroup_perm_comments'];
				$this->blog_post_statuses[$perm_target_blog]['blog_cats'] = $row['bloggroup_perm_cats'];
				$this->blog_post_statuses[$perm_target_blog]['blog_properties'] = $row['bloggroup_perm_properties'];
				$this->blog_post_statuses[$perm_target_blog]['blog_admin'] = $row['bloggroup_perm_admin'];
			}
		}

		// Check if permission is granted:
		switch( $permname )
		{
			case 'stats':
				// Wiewing stats is the same perm as being authorized to edit properties: (TODO...)
				if( $permlevel == 'view' )
				{
					return $this->blog_post_statuses[$perm_target_blog]['blog_properties'];
				}
				// No other perm can be granted here (TODO...)
				return false;

			case 'blog_genstatic':
				// generate static pages is not currently a group permission.  if you are here user is denied already anyway
				return (false);

			case 'blog_post_statuses':
				return ( count($this->blog_post_statuses[$perm_target_blog]['blog_post_statuses']) > 0 );

			case 'blog_post!published':
			case 'blog_post!protected':
			case 'blog_post!private':
			case 'blog_post!draft':
			case 'blog_post!deprecated':
			case 'blog_post!redirected':
				// We want a specific permission:
				$subperm = substr( $permname, 10 );
				// echo "checking : $subperm - ", implode( ',', $this->blog_post_statuses[$perm_target_blog]['blog_post_statuses']  ), '<br />';
				$perm = in_array( $subperm, $this->blog_post_statuses[$perm_target_blog]['blog_post_statuses'] );

				// TODO: the following probably should be handled by the Item class!
				if( $perm && $permlevel == 'edit' && !empty($Item) )
				{	// Can we edit this specific Item?
					switch( $this->blog_post_statuses[$perm_target_blog]['blog_edit'] )
					{
						case 'own':
							// Own posts only:
							return ($Item->creator_user_ID == $User->ID);

						case 'lt':
							// Own + Lower level posts only:
							if( $Item->creator_user_ID == $User->ID )
							{
								return true;
							}
							$item_creator_User = & $Item->get_creator_User();
							return ( $item_creator_User->level < $User->level );

						case 'le':
							// Own + Lower or equal level posts only:
							if( $Item->creator_user_ID == $User->ID )
							{
								return true;
							}
							$item_creator_User = & $Item->get_creator_User();
							return ( $item_creator_User->level <= $User->level );

						case 'all':
							return true;

						case 'no':
						default:
							return false;
					}
				}

				return $perm;

			default:
				// echo $permname, '=', $this->blog_post_statuses[$perm_target_blog][$permname], ' ';
				return $this->blog_post_statuses[$perm_target_blog][$permname];
		}
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

}

/*
 * $Log$
 * Revision 1.3  2008/01/20 15:31:12  fplanque
 * configurable validation/security rules
 *
 * Revision 1.2  2008/01/19 10:57:10  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.1  2007/06/25 11:01:45  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.15  2007/06/11 01:55:57  fplanque
 * level based user permissions
 *
 * Revision 1.14  2007/05/31 03:02:23  fplanque
 * Advanced perms now disabled by default (simpler interface).
 * Except when upgrading.
 * Enable advanced perms in blog settings -> features
 *
 * Revision 1.13  2007/05/29 01:17:20  fplanque
 * advanced admin blog settings are now restricted by a special permission
 *
 * Revision 1.12  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.11  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.10  2007/03/20 09:53:26  fplanque
 * Letting boggers view their own stats.
 * + Letthing admins view the aggregate by default.
 *
 * Revision 1.9  2007/03/07 02:34:29  fplanque
 * Fixed very sneaky bug
 *
 * Revision 1.8  2006/12/07 16:06:23  fplanque
 * prepared new file editing permission
 *
 * Revision 1.7  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
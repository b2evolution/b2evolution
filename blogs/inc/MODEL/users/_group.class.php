<?php
/**
 * This file implements the Group class, which manages user groups.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../dataobjects/_dataobject.class.php';

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
			$this->set( 'perm_stats', 'none' );
			$this->set( 'perm_spamblacklist', 'none' );
			$this->set( 'perm_templates', 0 );
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
			$this->perm_stats = $db_row->grp_perm_stats;
			$this->perm_spamblacklist = $db_row->grp_perm_spamblacklist;
			$this->perm_templates = $db_row->grp_perm_templates;
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

			case 'stats':
			case 'spamblacklist':
			case 'options':
			case 'files':
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
					if( ! $this->check_perm_bloggroups( 'blog_post_statuses', $permlevel, $loop_blog_ID ) )
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
	 * @return boolean 0 if permission denied
	 */
	function check_perm_bloggroups( $permname, $permlevel, $perm_target_blog )
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
								FROM T_coll_group_perms
								WHERE bloggroup_blog_ID = $perm_target_blog
								  AND bloggroup_group_ID = $this->ID";

			if( ($row = $DB->get_row( $query, ARRAY_A )) == NULL )
			{ // No rights set for this Blog/User
				return false;	// Permission denied
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

				$this->blog_post_statuses[$perm_target_blog]['blog_del_post'] = $row['bloggroup_perm_delpost'];
				$this->blog_post_statuses[$perm_target_blog]['blog_comments'] = $row['bloggroup_perm_comments'];
				$this->blog_post_statuses[$perm_target_blog]['blog_cats'] = $row['bloggroup_perm_cats'];
				$this->blog_post_statuses[$perm_target_blog]['blog_properties'] = $row['bloggroup_perm_properties'];
			}
		}

		// Check if permission is granted:
		switch( $permname )
		{
			case 'blog_genstatic':
				// generate static pages is not currently a group permission.  if you are here user is denied already anyway
				return (false);

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
	 * Template function: display name of blog
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function name( $format = 'htmlbody', $disp = true )
	{
		if( $disp )
		{ //the result must be displayed
			$this->disp( 'name', $format );
		}
		else
		{ //the result must be returned
			return $this->dget( 'name', $format );
		}
	}


	/**
	 * Template function: return name of group
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function name_return( $format = 'htmlbody' )
	{
		$r = $this->name( $format, false );
		return $r;
	}

}

/*
 * $Log$
 * Revision 1.4  2006/04/21 17:05:08  blueyed
 * cleanup
 *
 * Revision 1.3  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.2  2006/03/12 23:09:00  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.18  2006/02/10 22:08:07  fplanque
 * Various small fixes
 *
 * Revision 1.17  2006/02/03 16:24:51  blueyed
 * *** empty log message ***
 *
 * Revision 1.16  2005/11/04 21:42:22  blueyed
 * Use setter methods to set parameter values! dataobject::set_param() won't pass the parameter to dbchange() if it is already set to the same member value.
 *
 * Revision 1.15  2005/11/04 13:50:57  blueyed
 * Dataobject::set_param() / set(): return true if a value has been set and false if it did not change. It will not get considered for dbchange() then, too.
 *
 * Revision 1.14  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.13  2005/08/31 19:06:41  fplanque
 * minor
 *
 * Revision 1.12  2005/08/24 14:02:33  fplanque
 * minor changes
 *
 * Revision 1.11  2005/08/21 16:20:13  halton
 * Added group based blogging permissions (new tab under blog). Required schema change
 *
 * Revision 1.10  2005/07/12 00:29:00  blueyed
 * check_perm(): added $Debuglog, removed unneeded eval(), doc
 *
 * Revision 1.9  2005/06/20 17:40:23  fplanque
 * minor
 *
 * Revision 1.8  2005/05/10 18:40:08  fplanque
 * normalizing
 *
 * Revision 1.7  2005/05/09 19:07:04  fplanque
 * bugfixes + global access permission
 *
 * Revision 1.6  2005/05/09 16:09:42  fplanque
 * implemented file manager permissions through Groups
 *
 * Revision 1.5  2005/05/04 18:16:55  fplanque
 * Normalizing
 *
 * Revision 1.4  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.3  2005/01/13 19:53:50  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.2  2004/12/21 21:22:46  fplanque
 * factoring/cleanup
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.19  2004/10/12 16:12:17  fplanque
 * Edited code documentation.
 *
 * Revision 1.18  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>
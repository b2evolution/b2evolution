<?php
/**
 * This file implements the UI view for the blogs list on blog management screens.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $dispatcher;


$SQL = new SQL();
$SQL->SELECT( 'T_blogs.*, user_login' );
$SQL->FROM( 'T_blogs INNER JOIN T_users ON blog_owner_user_ID = user_ID' );

if( ! $current_User->check_perm( 'blogs', 'view' ) )
{	// We do not have perm to view all blogs... we need to restrict to those we're a member of:

	$SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON (blog_advanced_perms <> 0'
		. ' AND blog_ID = bloguser_blog_ID'
		. ' AND bloguser_user_ID = ' . $current_User->ID . ' )'
		. ' LEFT JOIN T_coll_group_perms ON (blog_advanced_perms <> 0'
		. ' AND blog_ID = bloggroup_blog_ID'
		. ' AND bloggroup_group_ID = ' . $current_User->group_ID . ' )' );
	$SQL->WHERE( 'blog_owner_user_ID = ' . $current_User->ID
		. ' OR bloguser_ismember <> 0'
		. ' OR bloggroup_ismember <> 0' );

	$no_results = T_('Sorry, you have no permission to edit/view any blog\'s properties.');
}
else
{
	$no_results = T_('No blog has been created yet!');
}

// Create result set:
$Results = new Results( $SQL->get(), 'blog_' );
$Results->Cache = & get_BlogCache();
$Results->title = T_('Blog list');
$Results->no_results_text = $no_results;

if( $current_User->check_perm( 'blogs', 'create' ) )
{
	$Results->global_icon( T_('New blog...'), 'new', url_add_param( $dispatcher, 'ctrl=collections&amp;action=new' ), T_('New blog...'), 3, 4 );
}

// Initialize Results object
blogs_results( $Results );

$Results->display( NULL, 'session' );


/*
 * $Log$
 * Revision 1.18  2013/11/06 08:03:58  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>
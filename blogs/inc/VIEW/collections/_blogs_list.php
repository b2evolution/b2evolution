<?php
/**
 * This file implements the UI view for the blogs list on blog management screens.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

$sql = 'SELECT T_blogs.*, user_login
					FROM T_blogs INNER JOIN T_users ON blog_owner_user_ID = user_ID';
if( ! $current_User->check_perm( 'blogs', 'view' ) )
{	// We do not have perm to view all blogs... we need to restrict to those we're a member of:
	$sql .= ' LEFT JOIN T_coll_user_perms ON ( blog_ID = bloguser_blog_ID
																					AND bloguser_user_ID ='.$current_User->ID.' )';
	$sql .= ' LEFT JOIN T_coll_group_perms ON ( blog_ID = bloggroup_blog_ID
																					AND bloggroup_group_ID ='.$current_User->group_ID.' )';
	$sql .= ' WHERE bloguser_ismember = 1
							 OR bloggroup_ismember = 1';

	$no_results = T_('Sorry, you have no permission to edit/view any blog\'s properties.');
}
else
{
	$no_results = T_('No blog has been created yet!');
}

// Create result set:
$Results = & new Results( $sql, 'blog_' );
$Results->Cache = & get_Cache( 'BlogCache' );
$Results->title = T_('Blog list');
$Results->no_results_text = $no_results;

if( $current_User->check_perm( 'blogs', 'create' ) )
{
	$Results->global_icon( T_('New blog...'), 'new', url_add_param( $dispatcher, 'ctrl=collections&amp;action=new' ), T_('New blog...'), 3, 4 );
}

$Results->cols[] = array(
						'th' => T_('ID'),
						'order' => 'blog_ID',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '$blog_ID$',
					);

function disp_coll_name( $coll_name, $coll_ID )
{
	global $current_User;
	if( $current_User->check_perm( 'blog_properties', 'edit', false, $coll_ID ) )
	{
		$edit_url = regenerate_url( 'ctrl', 'ctrl=coll_settings&amp;blog='.$coll_ID );
		$r = '<a href="'.$edit_url.'" title="'.T_('Edit properties...').'">';
		$r .= $coll_name;
		$r .= '</a>';
	}
	else
	{
		$r = $coll_name;
	}
	return $r;
}
$Results->cols[] = array(
						'th' => T_('Name'),
						'order' => 'blog_shortname',
						'td' => '<strong>%disp_coll_name( #blog_shortname#, #blog_ID# )%</strong>',
					);

$Results->cols[] = array(
						'th' => T_('Full Name'),
						'order' => 'blog_name',
						'td' => '$blog_name$',
					);

$Results->cols[] = array(
						'th' => T_('Owner'),
						'order' => 'user_login',
						'td' => '$user_login$',
					);

$Results->cols[] = array(
						'th' => T_('Blog URL'),
						'td' => '<a href="@get(\'blogurl\')@">@get(\'suburl\')@</a>',
					);

function disp_static_filename( & $Blog )
{
	global $current_User;
	if( $Blog->get('staticfilename') )
	{
		$r = '<a href="'.$Blog->get('staticurl').'">';
		$r .= $Blog->dget('staticfilename');
		$r .= '</a>';

		if( $current_User->check_perm( 'blog_genstatic', 'any', false, $Blog->ID ) )
		{
			$gen_url = '?ctrl=collections&amp;action=GenStatic&amp;blog='.$Blog->ID;
			$r .= ' [<a href="'.$gen_url.'" title="'.T_('Edit properties...').'">';
			$r .= /* TRANS: abbrev. for "generate !" */ T_('Gen!');
			$r .= '</a>]';
		}
	}
	else
	{ // for IE
		$r = '&nbsp;';
	}
	return $r;
}
$Results->cols[] = array(
						'th' => T_('Static File'),
						'order' => 'blog_staticfilename',
						'td' => '%disp_static_filename( {Obj} )%',
					);

$Results->cols[] = array(
						'th' => T_('Locale'),
						'order' => 'blog_locale',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%locale_flag( #blog_locale# )%',
					);


function disp_actions( $curr_blog_ID )
{
	global $current_User;
	$r = '';

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
	{
		$r .= action_icon( T_('Edit properties...'), 'properties', regenerate_url( 'ctrl', 'ctrl=coll_settings&amp;blog='.$curr_blog_ID ) );
	}

	if( $current_User->check_perm( 'blog_cats', '', false, $curr_blog_ID ) )
	{
		$r .= action_icon( T_('Edit categories...'), 'edit', regenerate_url( 'ctrl', 'ctrl=chapters&amp;blog='.$curr_blog_ID ) );
	}

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $curr_blog_ID ) )
	{
		$r .= action_icon( T_('Delete this blog...'), 'delete', regenerate_url( '', 'action=delete&amp;blog='.$curr_blog_ID ) );
	}

	if( empty($r) )
	{ // for IE
		$r = '&nbsp;';
	}

	return $r;
}
$Results->cols[] = array(
						'th' => T_('Actions'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%disp_actions( #blog_ID# )%',
					);


$Results->display( NULL, 'session' );


/*
 * $Log$
 * Revision 1.15  2007/01/23 04:19:50  fplanque
 * handling of blog owners
 *
 * Revision 1.14  2007/01/15 18:48:20  fplanque
 * cleanup
 *
 * Revision 1.13  2007/01/15 00:38:06  fplanque
 * pepped up "new blog" creation a little. To be continued.
 *
 * Revision 1.12  2007/01/14 22:42:55  fplanque
 * removed old crap
 *
 * Revision 1.11  2007/01/14 22:09:52  fplanque
 * attempt to display the list of blogs in a modern way.
 *
 */
?>

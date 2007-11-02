<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * @var Blog
 */
global $edited_Blog;


global $action, $next_action, $blogtemplate, $blog, $tab;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', $next_action );
$Form->hidden( 'tab', $tab );
if( $next_action == 'create' )
{
	$Form->hidden( 'kind', get_param('kind') );
	$Form->hidden( 'skin_ID', get_param('skin_ID') );
}
else
{
	$Form->hidden( 'blog', $blog );
}

$Form->begin_fieldset( T_('General parameters'), array( 'class'=>'fieldset clear' ) );

	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Full Name'), T_('Will be displayed on top of the blog.') );

	$Form->text( 'blog_shortname', $edited_Blog->get( 'shortname', 'formvalue' ), 12, T_('Short Name'), T_('Will be used in selection menus and throughout the admin interface.') );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings
	}

	$owner_User = & $edited_Blog->get_owner_User();
	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings

		$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('URL Name'), T_('Used to uniquely identify this blog. Appears in URLs and gets used as default for the media location (see the advanced tab).'), 255 );

		// fp> Note: There are 2 reasons why we don't provide a select here:
		// 1. If there are 1000 users, it's a pain.
		// 2. A single blog owner is not necessarily allowed to see all other users.
		$Form->text( 'owner_login', $owner_User->login, 20, T_('Owner'), T_('Login of this blog\'s owner.') );
	}
	else
	{
		$Form->info( T_('URL Name'), $edited_Blog->get( 'urlname' ), T_('Used to uniquely identify this blog in URLs.') /* Note: message voluntarily shorter than admin message */ );

		$Form->info( T_('Owner'), $owner_User->login, $owner_User->dget('fullname') );
	}

	$Form->select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options_return', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );

	$Form->end_fieldset();


$Form->begin_fieldset( T_('Content / Posts') );
	$Form->select_input_array( 'orderby', $edited_Blog->get_setting('orderby'), array(
												'datestart' => T_('Date issued (Default)'),
												//'datedeadline' => T_('Deadline'),
												'title'     => T_('Title'),
												'datecreated' => T_('Date created'),
												'datemodified' => T_('Date last modified'),
												'urltitle'     => T_('URL Title'),
												'priority'     => T_('Priority'),
											), T_('Order by'), T_('Default ordering of posts.') );
	$Form->select_input_array( 'orderdir', $edited_Blog->get_setting('orderdir'), array(
												'ASC'  => T_('Ascending'),
												'DESC' => T_('Descending'), ), T_('Direction') );
	$Form->radio( 'what_to_show', $edited_Blog->get_setting('what_to_show'),
								array(  array( 'days', T_('days') ),
												array( 'posts', T_('posts') ),
											), T_('Display unit'), false,  T_('Do you want to restrict on the number of days or the number of posts?') );
	$Form->text( 'posts_per_page', $edited_Blog->get_setting('posts_per_page'), 4, T_('Posts/Days per page'), T_('How many days or posts fo you want to display on the home page?'), 4 );
	$Form->radio( 'archive_mode',  $edited_Blog->get_setting('archive_mode'),
							array(  array( 'monthly', T_('monthly') ),
											array( 'weekly', T_('weekly') ),
											array( 'daily', T_('daily') ),
											array( 'postbypost', T_('post by post') )
										), T_('Archive grouping'), false,  T_('How do you want to browse the post archives? May also apply to permalinks.') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Description') );
	$Form->text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is diplayed under the blog name on the blog template.'), 250 );
	$Form->textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 5, T_('Long Description'), T_('This is displayed on the blog template.'), 50, 'large' );
$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

/*
 * $Log$
 * Revision 1.2  2007/11/02 02:38:29  fplanque
 * refactored blog settings / UI
 *
 * Revision 1.1  2007/06/25 10:59:36  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.25  2007/05/31 03:02:23  fplanque
 * Advanced perms now disabled by default (simpler interface).
 * Except when upgrading.
 * Enable advanced perms in blog settings -> features
 *
 * Revision 1.24  2007/05/29 01:17:20  fplanque
 * advanced admin blog settings are now restricted by a special permission
 *
 * Revision 1.23  2007/05/08 19:36:06  fplanque
 * automatic install of public blog list widget on new blogs
 *
 * Revision 1.22  2007/05/08 00:54:31  fplanque
 * public blog list as a widget
 *
 * Revision 1.21  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.20  2007/03/25 13:20:52  fplanque
 * cleaned up blog base urls
 * needs extensive testing...
 *
 * Revision 1.19  2007/01/23 08:57:35  fplanque
 * decrap!
 *
 * Revision 1.18  2007/01/23 04:19:50  fplanque
 * handling of blog owners
 *
 * Revision 1.17  2007/01/15 03:54:36  fplanque
 * pepped up new blog creation a little more
 *
 * Revision 1.16  2007/01/15 00:38:05  fplanque
 * pepped up "new blog" creation a little. To be continued.
 *
 */
?>
<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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

$Form->add_crumb( 'collection' );
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

$Form->begin_fieldset( T_('General parameters').get_manual_link('blogs_general_parameters'), array( 'class'=>'fieldset clear' ) );

	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Title'), T_('Will be displayed on top of the blog.'), 255 );

	$Form->text( 'blog_shortname', $edited_Blog->get( 'shortname', 'formvalue' ), 15, T_('Short name'), T_('Will be used in selection menus and throughout the admin interface.'), 255 );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings
	}

	$owner_User = & $edited_Blog->get_owner_User();
	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings

		$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('URL "filename"'),
				sprintf( T_('"slug" used to uniquely identify this blog in URLs. Also used as <a %s>default media folder</a>.'),
					'href="?ctrl=coll_settings&tab=advanced&blog='.$blog.'"'), 255 );

		// fp> Note: There are 2 reasons why we don't provide a select here:
		// 1. If there are 1000 users, it's a pain.
		// 2. A single blog owner is not necessarily allowed to see all other users.
		$Form->username( 'owner_login', $owner_User, T_('Owner'), T_('Login of this blog\'s owner.') );
	}
	else
	{
		$Form->info( T_('URL Name'), $edited_Blog->get( 'urlname' ), T_('Used to uniquely identify this blog in URLs.') /* Note: message voluntarily shorter than admin message */ );

		$Form->info( T_('Owner'), $owner_User->login, $owner_User->dget('fullname') );
	}

	$Form->select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options_return', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );

	$Form->end_fieldset();


$Form->begin_fieldset( T_('Description') );
	$Form->text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is displayed under the blog name on the blog template.'), 250 );
	$Form->textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 5, T_('Long Description'), T_('This is displayed on the blog template.'), 50, 'large' );
$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

/*
 * $Log$
 * Revision 1.27  2011/10/05 12:05:02  efy-yurybakh
 * Blog settings > features tab refactoring
 *
 * Revision 1.26  2011/09/04 22:13:14  fplanque
 * copyright 2011
 *
 * Revision 1.25  2011/01/02 18:47:38  sam2kb
 * typo: diplayed => displayed
 *
 * Revision 1.24  2010/06/19 01:09:31  blueyed
 * Improve jQuery hintbox integration.
 *
 *  - Load js/css in form class method
 *  - Use JS to load the CSS, since LINK is not valid in HTML BODY
 *  - Remove disableEnterKey onkeypress: handled properly by
 *    hintbox (patch sent upstream). This allows form submission from
 * 	 the input field now again.
 *  - Add proper CSS class to input field. This makes the "loading"
 *    background image not appear anymore, but that depends on the
 * 	 admin skin.
 *
 * Revision 1.23  2010/06/18 23:57:46  blueyed
 * Move hintbox to jquery subdir.
 *
 * Revision 1.22  2010/06/15 20:17:22  blueyed
 * todo
 *
 * Revision 1.21  2010/03/01 07:52:30  efy-asimo
 * Set manual links to lowercase
 *
 * Revision 1.20  2010/02/14 14:18:39  efy-asimo
 * insert manual links
 *
 * Revision 1.19  2010/02/08 17:52:09  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.18  2010/01/03 13:45:36  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.17  2009/12/02 00:05:52  fplanque
 * no message
 *
 * Revision 1.16  2009/12/01 14:56:58  efy-maxim
 * blog settings - username field instead simple text field
 *
 * Revision 1.15  2009/09/09 20:34:28  tblue246
 * Translation update
 *
 * Revision 1.14  2009/08/31 17:21:32  fplanque
 * minor
 *
 * Revision 1.13  2009/08/27 11:54:40  tblue246
 * General blog settings: Added default value for archives_sort_order
 *
 * Revision 1.12  2009/08/10 17:15:25  waltercruz
 * Adding permalinks on postbypost archive mode and adding a button to set the sort order on postbypost mode
 *
 * Revision 1.11  2009/07/06 12:50:51  sam2kb
 * Typo fo => do
 *
 * Revision 1.10  2009/07/01 23:39:55  fplanque
 * UI adjustments
 *
 * Revision 1.9  2009/05/31 17:04:42  sam2kb
 * blog_shortname field extended to 255 characters
 * Please change the new_db_version
 *
 * Revision 1.8  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.7  2008/12/28 17:35:51  fplanque
 * increase blog name max length to 255 chars
 *
 * Revision 1.6  2008/09/24 08:44:11  fplanque
 * Fixed and normalized order params for widgets (Comments not done yet)
 *
 * Revision 1.5  2008/02/09 02:56:00  fplanque
 * explicit order by field
 *
 * Revision 1.4  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/12/23 16:16:17  fplanque
 * Wording improvements
 *
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

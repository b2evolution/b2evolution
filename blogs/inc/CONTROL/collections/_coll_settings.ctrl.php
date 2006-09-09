<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @todo (sessions) When creating a blog, provide "edit options" (3 tabs) instead of a single long "New" form (storing the new Blog object with the session data).
 * @todo Currently if you change the name of a blog it gets not reflected in the blog list buttons!
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

$AdminUI->set_path( 'blogs' );

param_action( 'edit' );
param( 'tab', 'string', 'general', true );

$BlogCache = & get_Cache( 'BlogCache' );
$edited_Blog = & $BlogCache->get_by_ID( $blog );
$Blog = & $edited_Blog; // used for "Exit to blogs.." link


/**
 * Perform action:
 */
switch( $action )
{
	case 'edit':
	case 'filter1':
	case 'filter2':
		// Edit collection form (depending on tab):
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		$AdminUI->append_path_level( $tab );
		break;


	case 'update':
		// Update DB:
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		switch( $tab )
		{
			case 'general':
			case 'display':
				if( $edited_Blog->load_from_Request( array() ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
				}
				break;

			case 'skin':
				if( $edited_Blog->load_from_Request( array() ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog skin selection has been updated'), 'success' );
				}
				break;

			case 'advanced':
				if( $edited_Blog->load_from_Request( array( 'pings' ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
				}
				break;

			case 'perm':
				blog_update_user_perms( $blog );
				$Messages->add( T_('The blog permissions have been updated'), 'success' );
				break;

			case 'permgroup':
				blog_update_group_perms( $blog );
				$Messages->add( T_('The blog permissions have been updated'), 'success' );
				break;
		}

		$AdminUI->append_path_level( $tab );
		break;
}

/**
 * Display page header, menus & messages:
 */
$blogListButtons = $AdminUI->get_html_collection_list( 'blog_properties', 'edit',
											'?ctrl=collections&amp;action=edit&amp;blog=%d&amp;tab='.$tab,
											T_('List'), '?ctrl=collections&amp;blog=0' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


// Begin payload block:
$AdminUI->disp_payload_begin();


// Display VIEW:
switch( $AdminUI->get_path(1) )
{
	case 'general':
		$next_action = 'update';
		$AdminUI->disp_view( 'collections/_blogs_general.form.php' );
		break;

	case 'skin':
		$AdminUI->disp_view( 'collections/_blogs_skin.form.php' );
		break;

	case 'display':
		$AdminUI->disp_view( 'collections/_blogs_display.form.php' );
		break;

	case 'advanced':
		$AdminUI->disp_view( 'collections/_blogs_advanced.form.php' );
		break;

	case 'perm':
		$AdminUI->disp_view( 'collections/_blogs_permissions.form.php' );
		break;

	case 'permgroup':
		$AdminUI->disp_view( 'collections/_blogs_permissions_group.form.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.1  2006/09/09 17:51:33  fplanque
 * started new category/chapter editor
 *
 * Revision 1.23  2006/08/20 22:25:20  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.22  2006/08/20 20:12:32  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.21  2006/08/20 05:36:40  blueyed
 * Fix: send charset in backoffice again; remove notice in generated static pages - please merge to v-1-8 and v-1-9, if ok!
 *
 * Revision 1.20  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.19  2006/08/18 18:29:37  fplanque
 * Blog parameters reorganization + refactoring
 *
 * Revision 1.18  2006/08/18 17:23:58  fplanque
 * Visual skin selector
 *
 * Revision 1.17  2006/08/18 00:40:35  fplanque
 * Half way through a clean blog management - too tired to continue
 * Should be working.
 *
 * Revision 1.16  2006/08/05 23:33:54  fplanque
 * Fixed static page generation
 *
 * Revision 1.15  2006/06/25 21:15:03  fplanque
 * Heavy refactoring of the user blog perms so it stays manageable with a large number of users...
 *
 * Revision 1.14  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.13  2006/05/12 21:53:37  blueyed
 * Fixes, cleanup, translation for plugins
 *
 * Revision 1.12  2006/05/02 18:07:12  blueyed
 * Set blog to be used for exit to blogs link
 *
 * Revision 1.11  2006/04/20 16:31:29  fplanque
 * comment moderation (finished for 1.8)
 *
 * Revision 1.10  2006/04/19 20:13:49  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.9  2006/04/14 19:25:31  fplanque
 * evocore merge with work app
 *
 * Revision 1.8  2006/04/04 21:37:42  blueyed
 * Add bloguser_perm_media_*=1 for the created blog and current user.
 *
 * Revision 1.7  2006/03/29 23:24:40  blueyed
 * todo!
 *
 * Revision 1.6  2006/03/20 22:28:34  blueyed
 * Changed defaults for Log's display methods to "all" categories.
 *
 * Revision 1.4  2006/03/18 18:35:24  blueyed
 * Fixed paths
 *
 * Revision 1.2  2006/03/12 23:08:54  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:56  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.49  2006/01/30 19:49:17  fplanque
 * Fixed the 3 broken check_perm() features! 1) text_no_perm 2) perm_eval 3) average user trying to edit his profile
 *
 * Revision 1.48  2006/01/26 20:37:57  blueyed
 * minor
 *
 * Revision 1.47  2006/01/25 19:16:54  blueyed
 * moved to 1-2-3-4 scheme, todo.
 */
?>
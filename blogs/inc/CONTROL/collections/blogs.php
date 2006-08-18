<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004 by The University of North Carolina at Charlotte as contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
 * Parts of this file are copyright (c)2005 by Jason Edgecombe.
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
 *
 * The University of North Carolina at Charlotte grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 *
 * Jason EDGECOMBE grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author jwedgeco: Jason EDGECOMBE (for hire by UNC-Charlotte)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 *
 * @todo (sessions) When creating a blog, provide "edit options" (3 tabs) instead of a single long "New" form (storing the new Blog object with the session data).
 * @todo move to "new standard", i-e:
 *    1 - init params
 *    2 - perform actions
 *    3 - display error messages
 *    4 - display payload
 *    Currently if you change the name of a blog it gets not reflected in the blog list buttons!
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$AdminUI->set_path( 'blogs' );

$Request->param_action( 'list' );
$Request->param( 'tab', 'string', 'general', true );

if( $action != 'new' )
{
	if( !empty( $blog ) )
	{
		$edited_Blog = & $BlogCache->get_by_ID( $blog );
		$Blog = & $edited_Blog; // used for "Exit to blogs.." link
	}
}

if( $action == 'list' && !empty($blog) )
{
	$action = 'edit';
}


/**
 * Perform action:
 */
switch( $action )
{
	case 'new':
		// New collection:
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$AdminUI->append_path_level( 'new', array( 'text' => T_('New') ) );

		$edited_Blog = & new Blog( NULL );
		$edited_Blog->set( 'name', T_('New weblog') );
		$edited_Blog->set( 'shortname', T_('New blog') );
		$edited_Blog->set( 'urlname', 'new' );
		break;

	case 'copy':
		// Duplicate by prefilling form:
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$AdminUI->append_path_level( 'new', array( 'text' => T_('New') ) );

		// handle a blog copy
		$new_Blog = $edited_Blog;	// COPY TODO: PHP5 requires clone here / not backward compatible
		unset( $edited_Blog );
		$edited_Blog = & $new_Blog;

		$edited_Blog->set( 'urlname', 'new' );
		$edited_Blog->set( 'stub', '' );
		break;	
	
	case 'edit':
	case 'filter1':
	case 'filter2':
		// Edit collection form (depending on tab):
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		$AdminUI->append_path_level( $tab );
		break;


	case 'create':
		// Insert into DB:
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );
		
		$edited_Blog = & new Blog( NULL );

		if( $edited_Blog->load_from_Request( array() ) )
		{
			// DB INSERT
			$edited_Blog->dbinsert();

			// Set default user permissions for this blog (All permissions for the current user)
			// Proceed insertions:
			$DB->query( "
					INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
							bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
							bloguser_perm_cats, bloguser_perm_properties,
							bloguser_perm_media_upload, bloguser_perm_media_browse, bloguser_perm_media_change )
					VALUES ( $edited_Blog->ID, $current_User->ID, 1,
							'published,protected,private,draft,deprecated', 1, 1, 1, 1, 1, 1, 1 )" );

			// Commit changes in cache:
			$BlogCache->add( $edited_Blog );

			$Messages->add( T_('The new blog has been created.'), 'success' );
			
			if( param( 'blogtemplate', 'integer', -1 ) == -1 )
			{
				$Messages->add( sprintf( T_('You should <a %s>create categories</a> for this blog now!'),
												'href="?ctrl=chapters&amp;action=newcat&amp;blog='.$edited_Blog->ID.'"' ), 'note' );
			}
			else
			{
				// copy the categories from $blogtemplateid to $blog
				// TODO: checkbox on duplication form
				blog_copy_cats($blogtemplate, $edited_Blog->ID);
			
				$Messages->add( T_('Categories have been duplicated.'), 'success' );
			}
		}
		break;


	case 'update':
		// Update DB:
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
		
		switch( $tab )
		{
			case 'general':
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
		
		
	case 'delete':
		// ----------  Delete a blog from DB ----------
		if( $blog == 1 )
		{
			bad_request_die( 'You can\'t delete Blog #1!' );
		}

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed
			// Delete from DB:
			$msg = sprintf( T_('Blog &laquo;%s&raquo; deleted.'), $edited_Blog->dget('name') );

			param( 'delete_stub_file', 'integer', 0 );
			param( 'delete_static_file', 'integer', 0 );
			$edited_Blog->dbdelete( $delete_stub_file, $delete_static_file, false );

			$Messages->add( $msg, 'success' );			

			$BlogCache->remove_by_ID( $blog );
			unset( $edited_Blog );
			unset( $Blog );
			forget_param( 'blog' );
			$blog = 0;
			$UserSettings->delete( 'selected_blog' );	// Needed or subsequent pages may try to access the delete blog
			$UserSettings->dbupdate();

			$action = 'list';
		}

		break;
		

	case 'GenStatic':
		// ----------  Generate static homepage for blog ----------
		$AdminUI->append_to_titlearea( sprintf( T_('Generating static page for blog [%s]'), $edited_Blog->dget('name') ) );
		$current_User->check_perm( 'blog_genstatic', 'any', true, $blog );

		$staticfilename = $edited_Blog->get('staticfilename');
		if( empty( $staticfilename ) )
		{
			$Messages->add( T_('You haven\'t set a static filename for this blog!') );
			break;
		}

		// GENERATION!
		$static_gen_saved_locale = $current_locale;
		$generating_static = true;
		$resolve_extra_path = false;
		flush();
		ob_start();
		switch( $edited_Blog->access_type )
		{
			case 'default':
			case 'index.php':
				// Access through index.php
				// We need to set required variables
				$blog = $edited_Blog->ID;
				# This setting retricts posts to those published, thus hiding drafts.
				$show_statuses = array();
				# This is the list of categories to restrict the linkblog to (cats will be displayed recursively)
				$linkblog_cat = '';
				# This is the array if categories to restrict the linkblog to (non recursive)
				$linkblog_catsel = array( );
				# Here you can set a limit before which posts will be ignored
				$timestamp_min = '';
				# Here you can set a limit after which posts will be ignored
				$timestamp_max = 'now';
				// That's it, now let b2evolution do the rest! :)
				require $inc_path.'_blog_main.inc.php';
				break;

			case 'stub':
				// Access through stub file
				// TODO: stub file might be empty or handled by webserver (mod_rewrite)! We cannot require this!
				// TODO: It presently also allows to include ".php" files here!!
				require $edited_Blog->get('dynfilepath');
		}
		$generated_static_page_html = ob_get_contents();
		ob_end_clean();
		unset( $generating_static );

		// Switch back to saved locale (the blog page may have changed it):
		locale_activate( $static_gen_saved_locale);

		$staticfilename = $edited_Blog->get('staticfilepath');

		if( ! ($fp = @fopen( $staticfilename, 'w' )) )
		{ // could not open file
			$Messages->add( T_('File cannot be written!') );
			$Messages->add( sprintf( '<p>'.T_('You should check the file permissions for [%s]. See <a %s>online manual on file permissions</a>.').'</p>',$staticfilename, 'href="http://b2evolution.net/man/install/file_permissions.html"' ) );
		}
		else
		{ // file is writable
			fwrite( $fp, $generated_static_page_html );
			fclose( $fp );
			$Messages->add( sprintf( T_('Generated static file &laquo;%s&raquo;.'), $staticfilename ), 'success' );
		}
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


switch($action)
{
	case 'new':
	case 'copy':
		// ---------- "New blog" form ----------
		echo '<div class="panelblock">';
		echo '<h2>'.T_('New blog').':</h2>';

		if( !isset( $blog_siteurl_type ) )
		{ // determine siteurl type (if not set from update-action)
			if( preg_match('#https?://#', $edited_Blog->get( 'siteurl' ) ) )
			{ // absolute
				$blog_siteurl_type = 'absolute';
				$blog_siteurl_relative = '';
				$blog_siteurl_absolute = $edited_Blog->get( 'siteurl' );
			}
			else
			{ // relative
				$blog_siteurl_type = 'relative';
				$blog_siteurl_relative = $edited_Blog->get( 'siteurl' );
				$blog_siteurl_absolute = 'http://';
			}
		}

		$next_action = 'create';

		$AdminUI->disp_view( 'collections/_blogs_general.form.php' );

		echo '</div>';
		break;


	case 'edit':
	case 'filter1':
	case 'filter2':
	case 'update':
		// ---------- Edit blog form ----------
		if( $action == 'edit' )
		{ // permissions have not been checked on update:
			$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
		}

		// Begin payload block:
		$AdminUI->disp_payload_begin();


		switch( $AdminUI->get_path(1) )
		{
			case 'general':

				if( !isset( $blog_siteurl_type ) )
				{ // determine siteurl type (if not set from update-action)
					if( preg_match('#https?://#', $edited_Blog->get( 'siteurl' ) ) )
					{ // absolute
						$blog_siteurl_type = 'absolute';
						$blog_siteurl_relative = '';
						$blog_siteurl_absolute = $edited_Blog->get( 'siteurl' );
					}
					else
					{ // relative
						$blog_siteurl_type = 'relative';
						$blog_siteurl_relative = $edited_Blog->get( 'siteurl' );
						$blog_siteurl_absolute = 'http://';
					}
				}

				$next_action = 'update';
				// Display VIEW:
				$AdminUI->disp_view( 'collections/_blogs_general.form.php' );
				break;

			case 'skin':
				$AdminUI->disp_view( 'collections/_blogs_skin.form.php' );
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

		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		// Not confirmed
		?>
		<div class="panelinfo">
			<h3><?php printf( T_('Delete blog [%s]?'), $edited_Blog->dget( 'name' ) )?></h3>

			<p><?php echo T_('Deleting this blog will also delete all its categories, posts and comments!') ?></p>

			<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

			<p>

			<?php

				$Form = & new Form( NULL, '', 'get', 'none' );

				$Form->begin_form( 'inline' );

				$Form->hidden_ctrl();
				$Form->hidden( 'action', 'delete' );
				$Form->hidden( 'blog', $edited_Blog->ID );
				$Form->hidden( 'confirm', 1 );

				if( is_file( $edited_Blog->get('dynfilepath') ) )
				{
					?>
					<input type="checkbox" id="delete_stub_file" name="delete_stub_file" value="1" />
					<label for="delete_stub_file"><?php printf( T_('Also try to delete stub file [<strong><a %s>%s</a></strong>]'), 'href="'.$edited_Blog->dget('dynurl').'"', $edited_Blog->dget('dynfilepath') ); ?></label><br />
					<br />
					<?php
				}
				if( is_file( $edited_Blog->get('staticfilepath') ) )
				{
					?>
					<input type="checkbox" id="delete_static_file" name="delete_static_file" value="1" />
					<label for="delete_static_file"><?php printf( T_('Also try to delete static file [<strong><a %s>%s</a></strong>]'), 'href="'.$edited_Blog->dget('staticurl').'"', $edited_Blog->dget('staticfilepath') ); ?></label><br />
					<br />
					<?php
				}

				$Form->submit( array( '', T_('I am sure!'), 'DeleteButton' ) );

				$Form->end_form();


				$Form->begin_form( 'inline' );
					$Form->hidden_ctrl();
					$Form->hidden( 'blog', 0 );
					$Form->submit( array( '', T_('CANCEL'), 'CancelButton' ) );
				$Form->end_form();
			?>

			</p>

			</div>
		<?php
		break;


	default:
		// List the blogs:
		// Display VIEW:
		$AdminUI->disp_view( 'collections/_blogs_list.php' );

}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
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
 *
 * Revision 1.46  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.45  2005/11/24 18:14:22  blueyed
 * using getter for Blog->siteurl again (after having fixed Blog class)
 *
 * Revision 1.44  2005/11/24 16:13:21  blueyed
 * Do not use getter for siteurl, because it actually returns $baseurl.. :(
 *
 * Revision 1.43  2005/11/20 17:53:21  blueyed
 * Better fix for generating static pages
 *
 * Revision 1.42  2005/11/09 00:02:04  blueyed
 * todo
 *
 * Revision 1.41  2005/10/31 00:15:27  blueyed
 * Removed b2categories.php to categories.php
 *
 * Revision 1.40  2005/10/28 20:08:46  blueyed
 * Normalized AdminUI
 *
 * Revision 1.39  2005/08/21 16:20:12  halton
 * Added group based blogging permissions (new tab under blog). Required schema change
 *
 * Revision 1.38  2005/08/04 17:22:14  fplanque
 * better fix for "no linkblog": allow storage of NULL value.
 *
 * Revision 1.37  2005/06/03 15:12:31  fplanque
 * error/info message cleanup
 *
 * Revision 1.36  2005/05/25 17:13:32  fplanque
 * implemented email notifications on new comments/trackbacks
 *
 * Revision 1.35  2005/03/16 19:58:17  fplanque
 * small AdminUI cleanup tasks
 *
 * Revision 1.34  2005/03/15 19:19:46  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.33  2005/03/07 00:06:16  blueyed
 * admin UI refactoring, part three
 *
 * Revision 1.32  2005/03/06 19:58:27  blueyed
 * admin UI refactoring, part deux
 *
 * Revision 1.31  2005/03/04 20:14:31  fplanque
 * moved blog list generation to a unique AdminUI method
 *
 * Revision 1.30  2005/03/04 18:40:27  fplanque
 * added Payload display wrappers to admin skin object
 *
 * Revision 1.29  2005/03/02 17:07:33  blueyed
 * no message
 *
 * Revision 1.28  2005/02/28 09:06:39  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.27  2005/02/27 20:34:49  blueyed
 * Admin UI refactoring
 *
 * Revision 1.26  2005/02/24 22:17:45  edgester
 * Added a blog option to allow for a CSS file in the blog media dir to override the skin stylesheet.
 * Added a second blog option to allow for a user CSS file to  override the skin and blog stylesheets.
 *
 * Revision 1.25  2005/02/17 19:36:23  fplanque
 * no message
 *
 * Revision 1.24  2005/02/15 22:05:24  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.23  2005/01/27 13:34:57  fplanque
 * i18n tuning
 *
 * Revision 1.22  2005/01/13 19:53:48  fplanque
 * Refactoring... mostly by Fabrice... not fully checked :/
 *
 * Revision 1.21  2005/01/05 17:48:54  fplanque
 * consistent blog switcher on top
 *
 * Revision 1.20  2004/12/06 21:45:23  jwedgeco
 * Added header info and granted Francois PLANQUE the right to relicense under the Mozilla Public License.
 *
 * Revision 1.19  2004/11/30 21:51:34  jwedgeco
 * when copying a blog, categories are copied as well.
 *
 * Revision 1.18  2004/11/22 10:41:58  fplanque
 * minor changes
 *
 */
?>
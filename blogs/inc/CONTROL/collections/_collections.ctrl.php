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
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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

$AdminUI->set_path( 'blogs' );

param_action( 'list' );

if( $action != 'new'
	&& $action != 'list'
	&& $action != 'create' )
{
	if( valid_blog_requested() )
	{
		$edited_Blog = & $Blog;
	}
	else
	{
		$action = 'list';
	}
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
			$BlogCache = & get_Cache( 'BlogCache' );
			$BlogCache->add( $edited_Blog );

			$Messages->add( T_('The new blog has been created.'), 'success' );

			// Successful creation, move on to chapters:
			$Messages->add( T_('You should create categories for this blog now!'), 'note' );

			header_nocache();
			header_redirect( url_add_param( $admin_url, 'ctrl=chapters&blog='.$edited_Blog->ID, '&' ) ); // will save $Messages into Session
		}
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
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
			set_working_blog( 0 );
			$UserSettings->delete( 'selected_blog' );	// Needed or subsequent pages may try to access the delete blog
			$UserSettings->dbupdate();

			$action = 'list';
		}
		break;


	case 'GenStatic':
		// ----------  Generate static homepage for blog ----------
		$AdminUI->append_to_titlearea( sprintf( T_('Generating static page for blog [%s]'), $edited_Blog->dget('name') ) );
		$current_User->check_perm( 'blog_genstatic', 'any', true, $blog );

		param( 'redir_after_genstatic', 'string', NULL );

		$staticfilename = $edited_Blog->get('staticfilename');
		if( empty( $staticfilename ) )
		{
			$Messages->add( T_('You haven\'t set a static filename for this blog!') );
		}
		else
		{
			// GENERATION!
			$static_gen_saved_locale = $current_locale;
			$generating_static = true;
			$resolve_extra_path = false;
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
		}

		if( !empty( $redir_after_genstatic ) )
		{
			header_redirect( $redir_after_genstatic );
		}
		break;
}

/**
 * Display page header, menus & messages:
 */
// fp> TODO: fall back to ctrl=chapters when no perm for blog_properties
$blogListButtons = $AdminUI->get_html_collection_list( 'blog_properties', 'edit',
											'?ctrl=coll_settings&amp;tab=general&amp;blog=%d',
											T_('List'), '?ctrl=collections&amp;blog=0' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


switch($action)
{
	case 'new':
	case 'copy':
	case 'create': // in case of validation error
		// ---------- "New blog" form ----------
		echo '<div class="panelblock">';
		echo '<h2>'.T_('New blog').':</h2>';

		$next_action = 'create';

		$AdminUI->disp_view( 'collections/_blogs_general.form.php' );

		echo '</div>';
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
 * Revision 1.11  2006/12/18 03:20:21  fplanque
 * _header will always try to set $Blog.
 * autoselect_blog() will do so also.
 * controllers can use valid_blog_requested() to make sure we have one
 * controllers should call set_working_blog() to change $blog, so that it gets memorized in the user settings
 *
 * Revision 1.10  2006/12/17 23:42:38  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.9  2006/12/17 02:42:21  fplanque
 * streamlined access to blog settings
 *
 * Revision 1.8  2006/12/13 18:17:39  blueyed
 * Fixed header_redirect() which would only work if b2evo is installed in DOCUMENT_ROOT and would not have been RFC-compliant anyway
 *
 * Revision 1.7  2006/12/11 16:53:47  fplanque
 * controller name cleanup
 *
 * Revision 1.6  2006/12/11 00:32:26  fplanque
 * allow_moving_chapters stting moved to UI
 * chapters are now called categories in the UI
 *
 * Revision 1.5  2006/11/26 02:30:38  fplanque
 * doc / todo
 *
 * Revision 1.4  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.3  2006/11/24 18:06:02  blueyed
 * Handle saving of $Messages centrally in header_redirect()
 *
 * Revision 1.2  2006/09/11 19:36:58  fplanque
 * blog url ui refactoring
 *
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
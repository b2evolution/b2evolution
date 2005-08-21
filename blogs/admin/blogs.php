<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by The University of North Carolina at Charlotte as contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
 * Parts of this file are copyright (c)2005 by Jason Edgecombe.
 *
 * {@internal
 * The University of North Carolina at Charlotte grants François PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 *
 * Jason EDGECOMBE grants François PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author jwedgeco: Jason EDGECOMBE (for hire by UNC-Charlotte)
 * @author edgester: Jason EDGECOMBE (personal contributions, not for hire)
 *
 * @todo (sessions) When creating a blog, provide "edit options" instead of "New" form (storing the new Blog with the session data).
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_header.php'; // this will actually load blog params for the requested blog

param( 'tab', 'string', 'general' );
$AdminUI->setPath( 'blogs' );

param( 'action', 'string', '' );
param( 'blogtemplate', 'integer', -1 );


if( $action == 'edit' || $action == 'update' || $action == 'delete' || $action == 'GenStatic' )
{ // we need the blog param
	param( 'blog', 'integer', true );
	$edited_Blog = & $BlogCache->get_by_ID( $blog );
}
elseif( $action == 'new' && $blogtemplate != -1 )
{
	$edited_Blog = & $BlogCache->get_by_ID( $blogtemplate );
}
else
{
	$edited_Blog = & new Blog( NULL );
}


function set_edited_Blog_from_params( $for )
{
	global $edited_Blog, $default_locale;
	global $blog_siteurl_type, $blog_siteurl_relative, $blog_siteurl_absolute;
	global $DB, $Messages, $locales;

	switch( $for )
	{
		case 'new':
		case 'general':
			$req = ( $for != 'new' );  // are params required?

			$edited_Blog->set( 'name',          param( 'blog_name',          'string', $req ? true : T_('New weblog') ) );
			$edited_Blog->set( 'shortname',     param( 'blog_shortname',     'string', $req ? true : T_('New blog') ) );
			$edited_Blog->set( 'locale',        param( 'blog_locale',        'string', $req ? true : $default_locale ) );
			$edited_Blog->set( 'access_type',   param( 'blog_access_type',   'string', $req ? true : 'index.php' ) );
			$edited_Blog->set( 'stub',          param( 'blog_stub',          'string', $req ? true : '' ) );

			$edited_Blog->set( 'urlname',       param( 'blog_urlname',       'string', $req ? true : 'new' ) );
			$edited_Blog->set( 'default_skin',  param( 'blog_default_skin',  'string', $req ? true : 'basic' ) );

			// checkboxes (will not get send, if unchecked)
			$edited_Blog->set( 'force_skin',  1-param( 'blog_force_skin',    'integer', $req ? 0 : 0 ) );
			$edited_Blog->set( 'allowblogcss', param( 'blog_allowblogcss', 'integer', $req ? 0 : 1 ) );
			$edited_Blog->set( 'allowusercss', param( 'blog_allowusercss', 'integer', $req ? 0 : 1 ) );
			$edited_Blog->set( 'disp_bloglist', param( 'blog_disp_bloglist', 'integer', $req ? 0 : 1 ) );
			$edited_Blog->set( 'in_bloglist',   param( 'blog_in_bloglist',   'integer', $req ? 0 : 1 ) );

			$edited_Blog->set( 'links_blog_ID', param( 'blog_links_blog_ID', 'integer', $req ? true : '' ) );

			$edited_Blog->set( 'description',   param( 'blog_description',   'string', $req ? true : '' ) );
			$edited_Blog->set( 'keywords',      param( 'blog_keywords',      'string', $req ? true : '' ) );

			// format html
			$edited_Blog->set( 'tagline',       format_to_post( param( 'blog_tagline',  'html', $req ? true : '' ), 0, 0, $locales[ $edited_Blog->get('locale') ][ 'charset' ] ) );
			$edited_Blog->set( 'longdesc',      format_to_post( param( 'blog_longdesc', 'html', $req ? true : '' ), 0, 0, $locales[ $edited_Blog->get('locale') ][ 'charset' ] ) );
			$edited_Blog->set( 'notes',         format_to_post( param( 'blog_notes',    'html', $req ? true : '' ), 0, 0, $locales[ $edited_Blog->get('locale') ][ 'charset' ] ) );


			// abstract settings (determines blog_siteurl)
			param( 'blog_siteurl_type',     'string', $req ? true : 'relative' );
			param( 'blog_siteurl_relative', 'string', $req ? true : '' );
			param( 'blog_siteurl_absolute', 'string', $req ? true : '' );

			if( $blog_siteurl_type == 'absolute' )
			{
				$blog_siteurl = & $blog_siteurl_absolute;
				if( !preg_match( '#^https?://.+#', $blog_siteurl ) )
				{
					$Messages->add( T_('Blog Folder URL').': '
													.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>)!'), 'error' );
				}
			}
			else
			{ // relative siteurl
				$blog_siteurl = & $blog_siteurl_relative;
				if( preg_match( '#^https?://#', $blog_siteurl ) )
				{
					$Messages->add( T_('Blog Folder URL').': '
													.T_('You must provide a relative URL (without <code>http://</code> or <code>https://</code>)!'), 'error' );
				}
			}
			$edited_Blog->set( 'siteurl', $blog_siteurl );

			// check urlname
			if( '' == $edited_Blog->get( 'urlname' ) )
			{ // urlname is empty
				$Messages->add( T_('You must provide an URL blog name!'), 'error' );
			}
			elseif( $DB->get_var( 'SELECT COUNT(*)
															FROM T_blogs
															WHERE blog_urlname = '.$DB->quote($edited_Blog->get( 'urlname' ))
															.( $for != 'new' ? ' AND blog_ID <> '.$edited_Blog->ID : '' )
													) )
			{ // urlname is already in use
				$Messages->add( T_('This URL blog name is already in use by another blog. Please choose another name.'), 'error' );
			}

			break;

		case 'advanced':
			$edited_Blog->set( 'staticfilename',  param( 'blog_staticfilename',  'string', '' ) );
			$edited_Blog->set( 'allowtrackbacks', param( 'blog_allowtrackbacks', 'integer', 0 ) );
			$edited_Blog->set( 'allowpingbacks',  param( 'blog_allowpingbacks',  'integer', 0 ) );
			$edited_Blog->set( 'pingb2evonet',    param( 'blog_pingb2evonet',    'integer', 0 ) );
			$edited_Blog->set( 'pingtechnorati',  param( 'blog_pingtechnorati',  'integer', 0 ) );
			$edited_Blog->set( 'allowcomments',   param( 'blog_allowcomments',   'string', 'always' ) );
			$edited_Blog->set( 'pingweblogs',     param( 'blog_pingweblogs',     'integer', 0 ) );
			$edited_Blog->set( 'pingblodotgs',    param( 'blog_pingblodotgs',    'integer', 0 ) );
			$edited_Blog->set( 'media_location',  param( 'blog_media_location',  'string', 'default' ) );
			$edited_Blog->setMediaSubDir(         param( 'blog_media_subdir',    'string', '' ) );
			$edited_Blog->setMediaFullPath(       param( 'blog_media_fullpath',  'string', '' ) );
			$edited_Blog->setMediaUrl(            param( 'blog_media_url',       'string', '' ) );

			// check params
			switch( $edited_Blog->get( 'media_location' ) )
			{
				case 'custom': // custom path and URL
					if( '' == $edited_Blog->get( 'media_fullpath' ) )
					{
						$Messages->add( T_('Media dir location').': '.T_('You must provide the full path of the media directory.'), 'error' );
					}
					if( !preg_match( '#https?://#', $edited_Blog->get( 'media_url' ) ) )
					{
						$Messages->add( T_('Media dir location').': '
														.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>)!'), 'error' );
					}
					break;

				case 'subdir':
					if( '' == $edited_Blog->get( 'media_subdir' ) )
					{
						$Messages->add( T_('Media dir location').': '.T_('You must provide the media subdirectory.'), 'error' );
					}
					break;
			}

			break;
	}
}


// page title
switch( $action )
{
	case 'new':
	case 'create':
		$AdminUI->addPath( 'new', array( 'text' => T_('New') ) );
		break;

	case 'update':
	case 'edit':
		$AdminUI->addPath( $tab );
		$AdminUI->addPath( 'blog', array( 'text' => '&laquo;'.$edited_Blog->dget('shortname').'&raquo;' ) );

		// Generate available blogs list:
		$blogListButtons = $AdminUI->getCollectionList( 'blog_properties', 'edit',
													'blogs.php?action=edit&amp;blog=%d&amp;tab='.$AdminUI->getPath(1),
													T_('List'), 'blogs.php' );
}

require dirname(__FILE__).'/_menutop.php';


switch($action)
{
	case 'new':
		// ---------- "New blog" form ----------
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		if ($blogtemplate == -1)
		{
			set_edited_Blog_from_params( 'new' );
		}
		else
		{
			// handle a blog copy
			$edited_Blog->set( 'name',          param( 'blog_name',          'string', T_('New weblog') ) );
			$edited_Blog->set( 'shortname',     param( 'blog_shortname',     'string', T_('New blog') ) );
			$edited_Blog->set( 'stub',          param( 'blog_stub',          'string', '' ) );
			$edited_Blog->set( 'urlname',       param( 'blog_urlname',       'string', 'new' ) );
			param( 'blog_siteurl_type',     'string', 'relative' );
			param( 'blog_siteurl_relative', 'string', '' );
			param( 'blog_siteurl_absolute', 'string', '' );
		}

		echo '<div class="panelblock">';
		echo '<h2>'.T_('New blog').':</h2>';

		$next_action = 'create';
		require dirname(__FILE__).'/_blogs_general.form.php';

		echo '</div>';
		break;


	case 'create':
		// ---------- Create new blog ----------
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		?>
		<div class="panelinfo">
			<h3><?php echo T_('Creating blog...') ?></h3>
		<?php

		set_edited_Blog_from_params( 'general' );

		if( !$Messages->display_cond( T_('Cannot create, please correct this error:' ), T_('Cannot create, please correct these errors:' )) )
		{
			// DB INSERT
			$edited_Blog->dbinsert();

			// Set default user permissions for this blog
			// Proceed insertions:
			$DB->query( "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
															bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
															bloguser_perm_cats, bloguser_perm_properties )
										VALUES ( $edited_Blog->ID, $current_User->ID, 1,
														 'published,protected,private,draft,deprecated', 1, 1, 1, 1 )" );

			// Commit changes in cache:
			$BlogCache->add( $edited_Blog );

			if ($blogtemplate==-1)
			{
				echo '<p><strong>';
				printf( T_('You should <a %s>create categories</a> for this blog now!'),
							'href="b2categories.php?action=newcat&amp;blog='.$edited_Blog->ID.'"' );
				echo '</strong></p>';
			}
			else
			{
				// copy the categories from $blogtemplateid to $blog
				blog_copy_cats($blogtemplate, $edited_Blog->ID);
			}
			echo '</div>';

			// List the blogs:
			require dirname(__FILE__).'/_blogs_list.php';

			break;
		}


		echo '</div>';
		// NOTE: no break here, we go on to next form if there was an error!


	case 'update':
		// ---------- Update blog in DB ----------
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		?>
		<div class="panelinfo">
			<h3><?php printf( T_('Updating Blog [%s]...'), $edited_Blog->dget( 'name' ) )?></h3>
		<?php

		switch( $AdminUI->getPath(1) )
		{
			case 'general':
				set_edited_Blog_from_params( 'general' );
				break;

			case 'perm':
				blog_update_user_perms( $blog );
				break;
			case 'permgroup':
				blog_update_group_perms( $blog );
				break;
			case 'advanced':
				set_edited_Blog_from_params( 'advanced' );
				break;
		}

		// Commit changes in cache: (so that changes are not lost in the form)
		$BlogCache->add( $edited_Blog );

		if( !$Messages->display_cond( T_('Cannot update, please correct this error:' ), T_('Cannot update, please correct these errors:' )) )
		{ // Commit update to the DB:
			$edited_Blog->dbupdate();
		}

		// display notes
		$Messages->display( '', '', true, 'note' );

		?>
		</div>
		<?php
		// NOTE: no break here, we go on to edit!


	case 'edit':
		// ---------- Edit blog form ----------
		if( $action == 'edit' )
		{ // permissions have not been checked on update:
			$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
		}

		// Begin payload block:
		$AdminUI->dispPayloadBegin();


		switch( $AdminUI->getPath(1) )
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
				require dirname(__FILE__).'/_blogs_general.form.php';
				break;

			case 'perm':
				require dirname(__FILE__).'/_blogs_permissions.form.php';
				break;
			case 'permgroup':
				require dirname(__FILE__).'/_blogs_permissions_group.form.php';
				break;
			case 'advanced':
				require dirname(__FILE__).'/_blogs_advanced.form.php';
				break;
		}

		// End payload block:
		$AdminUI->dispPayloadEnd();

		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		param( 'confirm', 'integer', 0 );

		if( $blog == 1 )
		{
			die( 'You can\'t delete Blog #1!' );
		}

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		if( ! $confirm )
		{ // Not confirmed
			?>
			<div class="panelinfo">
				<h3><?php printf( T_('Delete blog [%s]?'), $edited_Blog->dget( 'name' ) )?></h3>

				<p><?php echo T_('Deleting this blog will also delete all its categories, posts and comments!') ?></p>

				<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

				<p>

				<?php

					$Form = &new Form( 'blogs.php', '', 'get', 'none' );

					$Form->begin_form( 'inline' );

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

					$Form->submit( array( '', T_('I am sure!'), 'search' ) );

					$Form->end_form();


					$Form->begin_form( 'inline' );
					$Form->submit( array( '', T_('CANCEL'), 'search' ) );
					$Form->end_form();
				?>

				</p>

				</div>
			<?php
		}
		else
		{ // Confirmed: Delete from DB:
			param( 'delete_stub_file', 'integer', 0 );
			param( 'delete_static_file', 'integer', 0 );

			echo '<div class="panelinfo">
							<h3>Deleting Blog [';
			$edited_Blog->disp( 'name' );
			echo ']...</h3>';
			$edited_Blog->dbdelete( $delete_stub_file, $delete_static_file, true );
			unset($edited_Blog);
			echo '</div>';
		}
		break;


	case 'GenStatic':
		// ----------  Generate static homepage for blog ----------
		?>
			<div class="panelinfo">
				<h3>
				<?php
					printf( T_('Generating static page for blog [%s]'), $edited_Blog->dget('name') );
				?>
				</h3>
		<?php
		$current_User->check_perm( 'blog_genstatic', 'any', true, $blog );

		$staticfilename = $edited_Blog->get('staticfilename');
		if( empty( $staticfilename ) )
		{
			echo '<p class="error">', T_('You haven\'t set a static filename for this blog!'), "</p>\n</div>\n";
			break;
		}

		// GENERATION!
		$static_gen_saved_locale = $current_locale;
		$generating_static = true;
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
				require $basepath.$core_subdir.'_blog_main.inc.php';
				break;

			case 'stub':
				// Access through stub file
				require $edited_Blog->get('dynfilepath');
		}
		$page = ob_get_contents();
		ob_end_clean();
		unset( $generating_static );

		// Switch back to saved locale (the blog page may have changed it):
		locale_activate( $static_gen_saved_locale);

		$staticfilename = $edited_Blog->get('staticfilepath');

		if( ! ($fp = @fopen( $staticfilename, 'w' )) )
		{ // could not open file
			?>
			<div class="error">
				<p class="error"><?php echo T_('File cannot be written!') ?></p>
				<p><?php printf( '<p>'.T_('You should check the file permissions for [%s]. See <a %s>online manual on file permissions</a>.').'</p>',$staticfilename, 'href="http://b2evolution.net/man/install/file_permissions.html"' ); ?></p>
			</div>
			<?php
		}
		else
		{ // file writing OK
			printf( '<p>'.T_('Writing to file [%s]...').'</p>', $staticfilename );
			fwrite( $fp, $page );
			fclose( $fp );

			echo '<p>'.T_('Done.').'</p>';
		}
		?>
		</div>
		<?php
		break;

	default:
		// List the blogs:
		require dirname(__FILE__).'/_blogs_list.php';

}


require dirname(__FILE__).'/_footer.php';


/*
 * $Log$
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
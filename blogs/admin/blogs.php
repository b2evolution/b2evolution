<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_header.php'; // this will actually load blog params for the requested blog


$admin_tab = 'blogs';
$admin_pagetitle = T_('Blogs');

param( 'action', 'string', '' );
param( 'tab', 'string', 'general' );


if( $action == 'edit' || $action == 'update' || $action == 'delete' || $action == 'GenStatic' )
{ // we need the blog param
	param( 'blog', 'integer', true );
	$edited_Blog = & $BlogCache->get_by_ID( $blog );
}
else
{
	$edited_Blog = & new Blog( NULL );
}


function set_edited_Blog_from_params( $for )
{{{
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
			$edited_Blog->set( 'disp_bloglist', param( 'blog_disp_bloglist', 'integer', $req ? 0 : 1 ) );
			$edited_Blog->set( 'in_bloglist',   param( 'blog_in_bloglist',   'integer', $req ? 0 : 1 ) );

			$edited_Blog->set( 'links_blog_ID', param( 'blog_links_blog_ID', 'integer', $req ? true : 0 ) );

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
					$Messages->add( T_('Blog Folder URL').': '.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>)!') );
				}
			}
			else
			{ // relative siteurl
				$blog_siteurl = & $blog_siteurl_relative;
				if( preg_match( '#^https?://#', $blog_siteurl ) )
				{
					$Messages->add( T_('Blog Folder URL').': '.T_('You must provide an relative URL (without <code>http://</code> or <code>https://</code>)!') );
				}
			}
			$edited_Blog->set( 'siteurl', $blog_siteurl );

			// check urlname
			if( '' == $edited_Blog->get( 'urlname' ) )
			{ // urlname is empty
				$Messages->add( T_('You must provide an URL blog name!') );
			}
			elseif( $DB->get_var( 'SELECT COUNT(*)
															FROM T_blogs
															WHERE blog_urlname = '.$DB->quote($edited_Blog->get( 'urlname' ))
															.( $for != 'new' ? ' AND blog_ID <> '.$edited_Blog->ID : '' )
													) )
			{ // urlname is already in use
				$Messages->add( T_('This URL blog name is already in use by another blog. Please choose another name.') );
			}

			break;

		case 'advanced':
			$edited_Blog->set( 'staticfilename',  param( 'blog_staticfilename',  'string', '' ) );
			$edited_Blog->set( 'allowtrackbacks', param( 'blog_allowtrackbacks', 'integer', 0 ) );
			$edited_Blog->set( 'allowpingbacks',  param( 'blog_allowpingbacks',  'integer', 0 ) );
			$edited_Blog->set( 'pingb2evonet',    param( 'blog_pingb2evonet',    'integer', 0 ) );
			$edited_Blog->set( 'pingtechnorati',  param( 'blog_pingtechnorati',  'integer', 0 ) );
			$edited_Blog->set( 'pingweblogs',     param( 'blog_pingweblogs',     'integer', 0 ) );
			$edited_Blog->set( 'pingblodotgs',    param( 'blog_pingblodotgs',    'integer', 0 ) );
			$edited_Blog->set( 'media_location',  param( 'blog_media_location',  'string', 'default' ) );
			$edited_Blog->set( 'media_subdir',    param( 'blog_media_subdir',    'string', '' ) );
			$edited_Blog->set( 'media_fullpath',  param( 'blog_media_fullpath',  'string', '' ) );
			$edited_Blog->set( 'media_url',       param( 'blog_media_url',       'string', '' ) );

			// check params
			switch( $edited_Blog->get( 'media_location' ) )
			{
				case 'custom': // custom path and URL
					if( '' == $edited_Blog->get( 'media_fullpath' ) )  // TODO: check for slashes/real path
					{
						$Messages->add( T_('Media dir location').': '.T_('You must provide the full path of the media directory.') );
					}
					if( !preg_match( '#https?://#', $edited_Blog->get( 'media_url' ) ) )
					{
						$Messages->add( T_('Media dir location').': '.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>)!') );
					}
					break;

				case 'subdir':
					if( '' == $edited_Blog->get( 'media_subdir' ) )  // TODO: check for slashes/real path
					{
						$Messages->add( T_('Media dir location').': '.T_('You must provide the media subdirectory.') );
					}
					break;
			}

			break;
	}
}}}


// page title {{{
switch( $action )
{
	case 'new':
	case 'create':
		$admin_pagetitle .= ' :: '.T_('New');
		break;

	case 'update':
	case 'edit':
		$admin_pagetitle .= ' :: ['.$edited_Blog->dget('shortname').']';
		switch( $tab )
		{
			case 'general':
				$admin_pagetitle .= ' :: '. T_('General');
				break;
			case 'perm':
				$admin_pagetitle .= ' :: '. T_('Permissions');
				break;
			case 'advanced':
				$admin_pagetitle .= ' :: '. T_('Advanced');
				break;
		}
		break;
} // }}}


require( dirname(__FILE__).'/_menutop.php' );
require( dirname(__FILE__).'/_menutop_end.php' );


switch($action)
{
	case 'new':
		// ---------- "New blog" form ---------- {{{
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		set_edited_Blog_from_params( 'new' );

		echo '<div class="panelblock">';
		echo '<h2>', T_('New blog'), ':</h2>';

		$next_action = 'create';
		require( dirname(__FILE__).'/_blogs_general.form.php' );

		echo '</div>';

		// }}}
		break;


	case 'create':
		// ---------- Create new blog ---------- {{{
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
			$DB->query( "INSERT INTO T_blogusers( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
												bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
												bloguser_perm_cats, bloguser_perm_properties )
										VALUES ( $edited_Blog->ID, $current_User->ID, 1,
														 'published,protected,private,draft,deprecated',
															1, 1, 1, 1 )" );

			// Commit changes in cache:
			$BlogCache->add( $edited_Blog );

			echo '<p><strong>';
			printf( T_('You should <a %s>create categories</a> for this blog now!'),
							'href="b2categories.php?action=newcat&amp;blog='.$edited_Blog->ID.'"' );
			echo '</strong></p>';
			echo '</div>';
			break;
		}
		echo '</div>';
		// }}}
		// NOTE: no break here, we go on to next form if there was an error!


	case 'update':
		// ---------- Update blog in DB ---------- {{{
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		?>
		<div class="panelinfo">
			<h3><?php printf( T_('Updating Blog [%s]...'), $edited_Blog->dget( 'name' ) )?></h3>
		<?php

		switch( $tab )
		{
			case 'general':
				set_edited_Blog_from_params( 'general' );
				break;

			case 'perm':
				blog_update_user_perms( $blog );
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
		<?php // }}}
		// NOTE: no break here, we go on to edit!


	case 'edit':
		// ---------- Edit blog form ---------- {{{
		if( $action == 'edit' )
		{ // permissions have not been checked on update:
			$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
		}
		?>
		<div class="pt" >
			<ul class="hack">
				<li><!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display --></li>
			</ul>
			<div class="panelblocktabs">
				<ul class="tabs">
				<?php
					if( $tab == 'general' )
						echo '<li class="current">';
					else
						echo '<li>';
					echo '<a href="blogs.php?blog='.$blog.'&amp;action=edit">'. T_('General'). '</a></li>';

					if( $tab == 'perm' )
						echo '<li class="current">';
					else
						echo '<li>';
					echo '<a href="blogs.php?blog='.$blog.'&amp;action=edit&amp;tab=perm">'. T_('Permissions'). '</a></li>';

					if( $tab == 'advanced' )
						echo '<li class="current">';
					else
						echo '<li>';
					echo '<a href="blogs.php?blog='.$blog.'&amp;action=edit&amp;tab=advanced">'. T_('Advanced'). '</a></li>';

				?>
				</ul>
			</div>
		</div>
		<div class="tabbedpanelblock">

		<?php
		switch( $tab )
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
				require( dirname(__FILE__).'/_blogs_general.form.php' );
				break;

			case 'perm':
				require( dirname(__FILE__).'/_blogs_permissions.form.php' );
				break;

			case 'advanced':
				require( dirname(__FILE__).'/_blogs_advanced.form.php' );
				break;
		}
		echo '</div>'; // }}}
		break;


	case 'delete':
		// ----------  Delete a blog from DB ---------- {{{
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
					<form action="blogs.php" method="get" class="inline">
						<input type="hidden" name="action" value="delete" />
						<input type="hidden" name="blog" value="<?php $edited_Blog->ID() ?>" />
						<input type="hidden" name="confirm" value="1" />

						<?php
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
						?>

						<input type="submit" value="<?php echo T_('I am sure!') ?>" class="search" />
					</form>
					<form action="blogs.php" method="get" class="inline">
						<input type="submit" value="<?php echo T_('CANCEL') ?>" class="search" />
					</form>
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
			echo '</div>';
		}
		// }}}
		break;


	case 'GenStatic':
		// ----------  Generate static homepage for blog ---------- {{{
		?>
			<div class="panelinfo">
				<h3>
				<?php
					printf( T_('Generating static page for blog [%s]'), $edited_Blog->dget('name') );
				?>
				</h3>
		<?php
		$current_User->check_perm( 'blog_genstatic', 'any', true, $blog );

		$staticfilename = get_bloginfo('staticfilename');
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
				require $basepath.$core_subdir.'_blog_main.php';
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
		// }}}
		break;
}


// List the blogs:
require( dirname(__FILE__).'/_blogs_list.php' );
require( dirname(__FILE__).'/_footer.php' );
?>
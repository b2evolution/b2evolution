<?php
/**
 * Editing the blogs
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
require_once( dirname(__FILE__). '/_header.php' ); // this will actually load blog params for req blog
$admin_tab = 'blogs';
$admin_pagetitle = T_('Blogs');
param( 'action', 'string' );
param( 'tab', 'string', 'general' );

switch($action)
{
	case 'create':
		// ---------- Create blog in DB ----------
		$admin_pagetitle .= ' :: '.T_('New');
		require( dirname(__FILE__).'/_menutop.php' );
		require( dirname(__FILE__).'/_menutop_end.php' );

		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		?>
		<div class="panelinfo">
			<h3><?php echo T_('Creating blog...') ?></h3>
		<?php

		param( 'blog_tagline', 'html', '' );
		param( 'blog_longdesc', 'html', '' );
		param( 'blog_notes', 'html', '' );
		param( 'blog_stub', 'string', true );

		param( 'blog_name', 'string', true );
		param( 'blog_shortname', 'string', true );
		param( 'blog_description', 'string', true );
		param( 'blog_locale', 'string', true );
		param( 'blog_access_type', 'string', true );
		param( 'blog_siteurl_type', 'string', true );
		param( 'blog_siteurl_relative', 'string', true );
		param( 'blog_siteurl_absolute', 'string', true );
		param( 'blog_keywords', 'string', true );
		param( 'blog_disp_bloglist', 'integer', 0 );
		param( 'blog_in_bloglist', 'integer', 0 );
		param( 'blog_linkblog', 'integer', 0 );
		param( 'blog_default_skin', 'string', true );
		param( 'blog_force_skin', 'integer', 0 );
		$blog_force_skin = 1-$blog_force_skin;

		$blog_tagline = format_to_post( $blog_tagline, 0, 0 );
		$blog_longdesc = format_to_post( $blog_longdesc, 0, 0 );
		$blog_notes = format_to_post( $blog_notes, 0, 0 );

		// check params:
		if( $blog_siteurl_type == 'absolute' )
		{
			if( !preg_match( '#^https?://#', $blog_siteurl_absolute ) )
			{
				$Messages->add( T_('Blog Folder URL').': '.T_('You must provide an absolute URL (starting with <code>http://</code> or <code>https://</code>) !') );
			}
			$blog_siteurl = $blog_siteurl_absolute;
		}
		if( $blog_siteurl_type == 'relative' )
		{
			if( preg_match( '#^https?://#', $blog_siteurl_relative )
					|| (!empty( $blog_siteurl_relative ) && !preg_match( '#^/#', $blog_siteurl_relative ) )
				)
			{
				$Messages->add( T_('Blog Folder URL').': '.T_('You must provide an relative URL (without http:// or https:// and starting with a / !') );
			}
			$blog_siteurl = $blog_siteurl_relative;
		}
		if( preg_match( '#/$#', $blog_siteurl ) )
		{
			$Messages->add( T_('Blog Folder URL').': '.T_('No trailing slash, please.') );
		}

		if( empty($blog_stub) )
		{	// Stub name is empty
			$Messages->add( T_('You must provide an URL blog name / Stub name!') );
		}
		else if( $DB->get_var( "SELECT COUNT(*)
												FROM $tableblogs
												WHERE blog_stub = ".$DB->quote($blog_stub) ) )
		{	// Stub name is already in use
			$Messages->add( T_('This URL blog name / Stub name is already in use by another blog. Choose another name.') );
		}

		if( !$Messages->display( T_('Cannot create, please correct these errors:' ), '') )
		{
			$edited_Blog = & new Blog( NULL );

			$edited_Blog->set( 'tagline', $blog_tagline );
			$edited_Blog->set( 'longdesc', $blog_longdesc );
			$edited_Blog->set( 'notes', $blog_notes );

			$edited_Blog->set( 'name', $blog_name );
			$edited_Blog->set( 'shortname', $blog_shortname );
			$edited_Blog->set( 'description', $blog_description );
			$edited_Blog->set( 'locale', $blog_locale );
			$edited_Blog->set( 'access_type', $blog_access_type );
			$edited_Blog->set( 'siteurl', $blog_siteurl );
			$edited_Blog->set( 'stub', $blog_stub );
			$edited_Blog->set( 'keywords', $blog_keywords );
			$edited_Blog->set( 'disp_bloglist', $blog_disp_bloglist );
			$edited_Blog->set( 'in_bloglist', $blog_in_bloglist );
			$edited_Blog->set( 'links_blog_ID', $blog_linkblog );
			$edited_Blog->set( 'default_skin', $blog_default_skin );
			$edited_Blog->set( 'force_skin', $blog_force_skin );

			// Additional default params:
			$edited_Blog->set( 'pingweblogs', 1 );
			$edited_Blog->set( 'allowtrackbacks', 0 );
			$edited_Blog->set( 'allowpingbacks', 0 );

			// DB INSERT
			$edited_Blog->dbinsert();

			// Set default user permissions for this blog
			// Proceed insertions:
			$DB->query( "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
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
		// NOTE: no break here, we go on to nexw form if there was an error!


	case 'new':
		// ---------- New blog form ----------
		if( $action == 'new' )
		{ // we haven't arrived here after a failed creation:
			$admin_pagetitle .= ' :: '.T_('New');
			require( dirname(__FILE__). '/_menutop.php' );
			require( dirname(__FILE__). '/_menutop_end.php' );

			// Check permissions:
			$current_User->check_perm( 'blogs', 'create', true );

			param( 'blog_name', 'string', T_('New weblog') );
			param( 'blog_shortname', 'string', T_('New blog') );
			param( 'blog_tagline', 'html', '' );
			param( 'blog_locale', 'string', $default_locale );
			param( 'blog_access_type', 'string', 'index.php' );
			param( 'blog_siteurl', 'string', '' );
			param( 'blog_siteurl_type', 'string', 'relative' );
			param( 'blog_siteurl_relative', 'string', '' );
			param( 'blog_stub', 'string', 'new' );
			param( 'blog_default_skin', 'string', 'basic' );
			param( 'blog_longdesc', 'html', '' );
			param( 'blog_notes', 'html', '' );
			param( 'blog_description', 'string', '' );
			param( 'blog_keywords', 'string', '' );
			$blog_disp_bloglist = 1;
			$blog_in_bloglist = 1;
			param( 'blog_linkblog', 'integer', 0 );
			$blog_force_skin = 0;
			param( 'blog_linkblog', 'integer', 0 );
		}

		echo '<div class="panelblock">';
		echo'<h2>', T_('New blog'), ':</h2>';
		$next_action = 'create';
		require( dirname(__FILE__).'/_blogs_general.form.php' );
		echo '</div>';

		require( dirname(__FILE__). '/_footer.php' );
		exit();


	case 'update':
		// ---------- Update blog in DB ----------
		param( 'blog', 'integer', true );
		$edited_Blog = $BlogCache->get_by_ID( $blog );
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

		require( dirname(__FILE__). '/_menutop.php' );
		require( dirname(__FILE__). '/_menutop_end.php' );

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		?>
		<div class="panelinfo">
			<h3><?php printf( T_('Updating Blog [%s]...'), $edited_Blog->dget( 'name' ) )?></h3>
		<?php

		switch( $tab )
		{
			case 'general':
				param( 'blog_tagline', 'html', '' );
				param( 'blog_longdesc', 'html', '' );
				param( 'blog_notes', 'html', '' );
				param( 'blog_stub', 'string', true );

				param( 'blog_name', 'string', true );
				param( 'blog_shortname', 'string', true );
				param( 'blog_description', 'string', true );
				param( 'blog_locale', 'string', true );
				param( 'blog_access_type', 'string', true );
				param( 'blog_siteurl_absolute', 'string', true );
				param( 'blog_siteurl_relative', 'string', true );
				param( 'blog_siteurl_type', 'string', true );
				param( 'blog_keywords', 'string', true );
				param( 'blog_disp_bloglist', 'integer', 0 );
				param( 'blog_in_bloglist', 'integer', 0 );
				param( 'blog_linkblog', 'integer', 0 );
				param( 'blog_default_skin', 'string', true );
				param( 'blog_force_skin', 'integer', 0 );
				$blog_force_skin = 1-$blog_force_skin;

				$blog_tagline = format_to_post( $blog_tagline, 0, 0 );
				$blog_longdesc = format_to_post( $blog_longdesc, 0, 0 );
				$blog_notes = format_to_post( $blog_notes, 0, 0 );

				// check params:
				if( $blog_siteurl_type == 'absolute' )
				{
					if( !preg_match( '#^https?://#', $blog_siteurl_absolute ) )
					{
						$Messages->add( T_('Blog Folder URL').': '.T_('You must provide an absolute URL (starting with http:// or https:// !') );
					}
					$blog_siteurl = $blog_siteurl_absolute;
				}
				if( $blog_siteurl_type == 'relative' )
				{
					if( preg_match( '#^https?://#', $blog_siteurl_relative )
							|| (!empty( $blog_siteurl_relative ) && !preg_match( '#^/#', $blog_siteurl_relative ) )
						)
					{
						$Messages->add( T_('Blog Folder URL').': '.T_('You must provide an relative URL (without http:// or https:// and starting with a / !') );
					}
					$blog_siteurl = $blog_siteurl_relative;
				}
				if( preg_match( '#/$#', $blog_siteurl ) )
				{
					$Messages->add( T_('Blog Folder URL').': '.T_('No trailing slash, please.') );
				}

				if( empty($blog_stub) )
				{	// Stub name is empty
					$Messages->add( T_('You must provide an URL blog name / Stub name!') );
				}
				else if( $DB->get_var( "SELECT COUNT(*)
														FROM $tableblogs
														WHERE blog_stub = ".$DB->quote($blog_stub)."
															AND blog_ID <> ".$edited_Blog->ID ) )
				{	// Stub name is already in use
					$Messages->add( T_('This URL blog name / Stub name is already in use by another blog. Choose another name.') );
				}

				if ( $Messages->display( T_('Cannot update, please correct these errors:' ), '') )
				{
					echo '</div>';
					break;
				}

				$edited_Blog->set( 'tagline', $blog_tagline );
				$edited_Blog->set( 'longdesc', $blog_longdesc );
				$edited_Blog->set( 'notes', $blog_notes );
				$edited_Blog->set( 'stub', $blog_stub );
				$edited_Blog->set( 'name', $blog_name );
				$edited_Blog->set( 'shortname', $blog_shortname );
				$edited_Blog->set( 'description', $blog_description );
				$edited_Blog->set( 'locale', $blog_locale );
				$edited_Blog->set( 'access_type', $blog_access_type );
				$edited_Blog->set( 'siteurl', $blog_siteurl );
				$edited_Blog->set( 'keywords', $blog_keywords );
				$edited_Blog->set( 'disp_bloglist', $blog_disp_bloglist );
				$edited_Blog->set( 'in_bloglist', $blog_in_bloglist );
				$edited_Blog->set( 'links_blog_ID', $blog_linkblog );
				$edited_Blog->set( 'default_skin', $blog_default_skin );
				$edited_Blog->set( 'force_skin', $blog_force_skin );

				break;

			case 'perm':
				// Update the user permissions for this blog
				blog_update_user_perms( $blog );
				break;

			case 'advanced':
				param( 'blog_staticfilename', 'string', '' );
				$edited_Blog->set( 'staticfilename', $blog_staticfilename );

				param( 'blog_allowtrackbacks', 'integer', 0 );
				$edited_Blog->set( 'allowtrackbacks', $blog_allowtrackbacks );

				param( 'blog_allowpingbacks', 'integer', 0 );
				$edited_Blog->set( 'allowpingbacks', $blog_allowpingbacks );

				param( 'blog_pingb2evonet', 'integer', 0 );
				$edited_Blog->set( 'pingb2evonet', $blog_pingb2evonet );

				param( 'blog_pingtechnorati', 'integer', 0 );
				$edited_Blog->set( 'pingtechnorati', $blog_pingtechnorati );

				param( 'blog_pingweblogs', 'integer', 0 );
				$edited_Blog->set( 'pingweblogs', $blog_pingweblogs );

				param( 'blog_pingblodotgs', 'integer', 0 );
				$edited_Blog->set( 'pingblodotgs', $blog_pingblodotgs );

				break;
		}

		if( !$Messages->count() )
		{	// Commit update to the DB:
			$edited_Blog->dbupdate();

			// Commit changes in cache:
			$BlogCache->add( $edited_Blog );
		}

		?>
		</div>
		<?php
		// NOTE: no break here, we go on to edit!

	case 'edit':
		// ---------- Edit blog form ----------
		if( $action == 'edit' )
		{	// this has not already been displayed on update:
			param( 'blog', 'integer', true );
			$edited_Blog = $BlogCache->get_by_ID( $blog );
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
			require( dirname(__FILE__). '/_menutop.php' );
			require( dirname(__FILE__). '/_menutop_end.php' );

			// Check permissions:
			$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
		}
		?>
		<div class="pt" >
			<ul class="tabs">
				<!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display -->
			</ul>
			<div class="panelblocktabs">
				<ul class="tabs">
				<?php
					if( $tab == 'general' )
						echo '<li class="current">';
					else
						echo '<li>';
					echo '<a href="b2blogs.php?blog='.$blog.'&amp;action=edit">'. T_('General'). '</a></li>';

					if( $tab == 'perm' )
						echo '<li class="current">';
					else
						echo '<li>';
					echo '<a href="b2blogs.php?blog='.$blog.'&amp;action=edit&amp;tab=perm">'. T_('Permissions'). '</a></li>';

					if( $tab == 'advanced' )
						echo '<li class="current">';
					else
						echo '<li>';
					echo '<a href="b2blogs.php?blog='.$blog.'&amp;action=edit&amp;tab=advanced">'. T_('Advanced'). '</a></li>';

				?>
				</ul>
			</div>
		</div>
		<div class="tabbedpanelblock">

		<?php
		switch( $tab )
		{
			case 'general':
				if( $action == 'edit' )
				{	// we didn't end up here from a failed update:
					$blog_name = get_bloginfo( 'name' );
					$blog_shortname = get_bloginfo( 'shortname' );
					$blog_tagline = get_bloginfo( 'tagline' );
					$blog_description = get_bloginfo( 'description' );
					$blog_longdesc = get_bloginfo( 'longdesc' );
					$blog_locale = get_bloginfo( 'locale' );
					$blog_access_type = $edited_Blog->get( 'access_type' );
					$blog_siteurl = get_bloginfo( 'subdir' );
					if( preg_match('#https?://#', $blog_siteurl) )
					{ // absolute
						$blog_siteurl_type = 'absolute';
						$blog_siteurl_absolute = $blog_siteurl;
					}
					else
					{ // relative
						$blog_siteurl_type = 'relative';
						$blog_siteurl_relative = $blog_siteurl;
					}
					$blog_stub = get_bloginfo( 'stub' );
					$blog_linkblog = get_bloginfo( 'links_blog_ID' );
					$blog_notes = get_bloginfo( 'notes' );
					$blog_keywords = get_bloginfo( 'keywords' );
					$blog_disp_bloglist = get_bloginfo( 'disp_bloglist' );
					$blog_in_bloglist = get_bloginfo( 'in_bloglist' );
					$blog_default_skin = get_bloginfo( 'default_skin' );
					$blog_force_skin = $edited_Blog->get( 'force_skin' );
				}
				$next_action = 'update';
				require( dirname(__FILE__).'/_blogs_general.form.php' );
				break;

			case 'perm':
				require( dirname(__FILE__).'/_blogs_permissions.form.php' );
				break;

			case 'advanced':
				$blog_staticfilename = get_bloginfo( 'staticfilename' );
				$blog_allowtrackbacks = get_bloginfo( 'allowtrackbacks' );
				$blog_allowpingbacks = get_bloginfo( 'allowpingbacks' );
				$blog_pingb2evonet = get_bloginfo( 'pingb2evonet' );
				$blog_pingtechnorati = get_bloginfo( 'pingtechnorati' );
				$blog_pingweblogs = get_bloginfo( 'pingweblogs' );
				$blog_pingblodotgs = get_bloginfo( 'pingblodotgs' );
				require( dirname(__FILE__).'/_blogs_advanced.form.php' );
				break;
		}
		echo '</div>';
		require( dirname(__FILE__).'/_footer.php' );
		exit();




	case 'delete':
		// ----------  Delete a blog from DB ----------
		param( 'blog', 'integer', true );
		param( 'confirm', 'integer', 0 );
		require( dirname(__FILE__). '/_menutop.php' );
		require( dirname(__FILE__). '/_menutop_end.php' );

		if( $blog == 1 )
			die( 'You can\'t delete Blog #1!' );

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		$deleted_Blog = Blog_get_by_ID( $blog );

		if( ! $confirm )
		{	// Not confirmed
			?>
			<div class="panelinfo">
				<h3><?php printf( T_('Delete blog [%s]?'), $deleted_Blog->dget( 'name' ) )?></h3>

				<p><?php echo T_('Deleting this blog will also delete all its categories, posts and comments!') ?></p>

				<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

				<p>
					<form action="b2blogs.php" method="get" class="inline">
						<input type="hidden" name="action" _="delete" />
						<input type="hidden" name="blog" _="<?php $deleted_Blog->ID() ?>" />
						<input type="hidden" name="confirm" _="1" />

						<?php
						if( is_file( $deleted_Blog->get('dynfilepath') ) )
						{
							?>
							<input type="checkbox" id="delete_stub_file" name="delete_stub_file" _="1" />
							<label for="delete_stub_file"><?php printf( T_('Also try to delete stub file [<strong><a %s>%s</a></strong>]'), 'href="'.$deleted_Blog->dget('dynurl').'"', $deleted_Blog->dget('dynfilepath') ); ?></label><br />
							<br />
							<?php
						}
						if( is_file( $deleted_Blog->get('staticfilepath') ) )
						{
							?>
							<input type="checkbox" id="delete_static_file" name="delete_static_file" _="1" />
							<label for="delete_static_file"><?php printf( T_('Also try to delete static file [<strong><a %s>%s</a></strong>]'), 'href="'.$deleted_Blog->dget('staticurl').'"', $deleted_Blog->dget('staticfilepath') ); ?></label><br />
							<br />
							<?php
						}
						?>

						<input type="submit" _="<?php echo T_('I am sure!') ?>" class="search" />
					</form>
					<form action="b2blogs.php" method="get" class="inline">
						<input type="submit" _="<?php echo T_('CANCEL') ?>" class="search" />
					</form>
				</p>

				</div>
			<?php
		}
		else
		{	// Confirmed: Delete from DB:
			param( 'delete_stub_file', 'integer', 0 );
			param( 'delete_static_file', 'integer', 0 );

			echo '<div class="panelinfo">
							<h3>Deleting Blog [';
			$deleted_Blog->disp( 'name' );
			echo ']...</h3>';
			$deleted_Blog->dbdelete( $delete_stub_file, $delete_static_file, true );
			echo '</div>';
		}
		break;


	case 'GenStatic':
		// ----------  Generate static homepage for blog ----------
		param( 'blog', 'integer', true );
		require( dirname(__FILE__) . '/_menutop.php' );
		require( dirname(__FILE__) . '/_menutop_end.php' );
		$edited_Blog = Blog_get_by_ID( $blog );
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
				require $basepath.'/'.$core_subdir.'/_blog_main.php';
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
		{	// could not open file
			?>
			<div class="error">
				<p class="error"><?php echo T_('File cannot be written!') ?></p>
				<p><?php printf( '<p>'.T_('You should check the file permissions for [%s]. See <a %s>online manual on file permissions</a>.').'</p>',$staticfilename, 'href="http://b2evolution.net/man/install/file_permissions.html"' ); ?></p>
			</div>
			<?php
		}
		else
		{	// file writing OK
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
		require( dirname(__FILE__). '/_menutop.php' );
		require( dirname(__FILE__). '/_menutop_end.php' );

}

// List the blogs:
require( dirname(__FILE__). '/_blogs_list.php' );
require( dirname(__FILE__). '/_footer.php' );
?>
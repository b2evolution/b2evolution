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
require_once( dirname(__FILE__). '/_header.php' ); // this will actually load blog params for req blog
$admin_tab = 'blogs';
$admin_pagetitle = T_('Blogs');
param( 'action', 'string' );
param( 'tab', 'string', 'general' );

switch($action)
{
	case 'new':
		// ---------- New blog form ----------
		$admin_pagetitle .= ' :: '.T_('New');
		require( dirname(__FILE__). '/_menutop.php' );
		require( dirname(__FILE__). '/_menutop_end.php' );

		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );
		
		param( 'blog_name', 'string', 'new weblog' );
		param( 'blog_shortname', 'string', 'new blog' );
		param( 'blog_tagline', 'html', '' );
		param( 'blog_locale', 'string', $default_locale );
		param( 'blog_access_type', 'string', 'index.php' );
		param( 'blog_siteurl', 'string', '' );
		param( 'blog_stub', 'string', 'new_file.php' );
		param( 'blog_default_skin', 'string', '' );

		require( dirname(__FILE__) . '/_blogs_new.form.php' );

		require( dirname(__FILE__). '/_footer.php' );
		exit();


	case 'create':
		// ---------- Create blog in DB ----------
		require( dirname(__FILE__) . '/_menutop.php' );
		require( dirname(__FILE__) . '/_menutop_end.php' );

		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		?>
		<div class="panelinfo">
			<h3><?php echo T_('Creating blog...') ?></h3>
		<?php

		param( 'blog_name', 'string', true );
		param( 'blog_shortname', 'string', true );
		param( 'blog_tagline', 'html', true );
		param( 'blog_locale', 'string', true );
		param( 'blog_access_type', 'string', true );
		param( 'blog_siteurl', 'string', true );
		param( 'blog_stub', 'string', true );
		param( 'blog_default_skin', 'string', true );

		if ( errors_display( T_('Cannot create, please correct these errors:'),
			'[<a href="javascript:history.go(-1)">'.T_('Back to new blog form').'</a>]'))
		{
			echo '</div>';
			require( dirname(__FILE__) . '/_footer.php' );
			die();
			break;
		}

		// DB INSERT
		$blog_ID = blog_create( $blog_name, $blog_shortname, $blog_siteurl, '',
									$blog_stub, '', '', '', '', $blog_locale, '', '', '', 0 );

		// Set default user permissions for this blog
		// Proceed insertions:
		$DB->query( "INSERT INTO $tableblogusers( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
											bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
											bloguser_perm_cats, bloguser_perm_properties )
									VALUES ( $blog_ID, $current_User->ID, 1, 'published,protected,private,draft,deprecated', 
																1, 1, 1, 1 )" );

		// Quick hack to create a stub file:
		if( $blog_siteurl == '' && (1 == 0) )
		{ // TEMPORARILY DISABLED	
			echo '<p>', T_('Trying to create stub file'), '</p>';
			// Determine the edit folder:
			$current_folder = str_replace( '\\', '/', dirname(__FILE__) );
			$last_pos = 0;
			while( $pos = strpos( $current_folder, $admin_subdir, $last_pos ) )
			{	// make sure we use the last occurrence
				$edit_folder = substr( $current_folder, 0, $pos-1 );
				$last_pos = $pos+1;
			}

			$stub_contents = file( $edit_folder.'/stub.model' );
			echo '<p>', T_('Loading'), ': ', $edit_folder.'/stub.model', '</p>';

			if( empty( $stub_contents ) )
			{
					echo '<p class="error">', T_('Could not load stub model.'), '</p>';
			}
			else
			{
				$new_stub_file = $edit_folder . '/' . $blog_filename;
				echo '<p>', T_('Creating'), ': ', $new_stub_file, '...</p>';
				$f = @fopen( $new_stub_file , "w" );
				if( $f == false )
				{
					echo '<p class="error">', T_('Cannot create!'), '</p>';
				}
				else
				{
					$found = false;
					foreach( $stub_contents as $idx => $stub_line )
					{
						$stub_line = ereg_replace( '\$blog *= *.+;', '$blog = '.$blog_ID.';', $stub_line );
						fwrite( $f, $stub_line);
					}
					fclose($f);
				}

				if( isset($default_stub_mod) )
				{
					printf( T_('<p>Changing mod to %o</p>'), $default_stub_mod );
					if( ! chmod( $new_stub_file, $default_stub_mod ) )
					{
						echo '<p class="error">', T_('Warning'), ': ', T_('chmod failed!'), '</p>';
					}
				}

				if( isset($default_stub_owner) )
				{
					printf( T_('<p>Changing owner to %s</p>'), $default_stub_owner );
					if( ! chown( $new_stub_file, $default_stub_owner ) )
					{
						echo '<p class="error">', T_('Warning'), ': ', T_('chown failed!'), '</p>';
					}
				}
			}
		}

		?>
		<p><strong><?php 
			printf( T_('You should <a %s>create categories</a> for this blog now!'), 
			'href="b2categories.php?action=newcat&amp;blog_ID='.$blog_ID.'"' );
		?></strong></p>
		</div>
		<?php
		break;


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
				param( 'blog_roll', 'html', '' );

				$blog_tagline = format_to_post( $blog_tagline, 0, 0 );
				$blog_longdesc = format_to_post( $blog_longdesc, 0, 0 );
				$blog_roll = format_to_post( $blog_roll, 0, 0 );

				if ( errors_display( T_('Cannot update, please correct these errors:' ),
					'[<a href="javascript:history.go(-1)">' . T_('Back to blog editing') . '</a>]') )
				{
					echo '</div>';
					require( dirname(__FILE__) . '/_footer.php' );
					exit();
				}

				$edited_Blog->set( 'tagline', $blog_tagline );
				$edited_Blog->set( 'longdesc', $blog_longdesc );
				$edited_Blog->set( 'roll', $blog_roll );

				param( 'blog_name', 'string', true );
				$edited_Blog->set( 'name', $blog_name );

				param( 'blog_shortname', 'string', true );
				$edited_Blog->set( 'shortname', $blog_shortname );

				param( 'blog_description', 'string', true );
				$edited_Blog->set( 'description', $blog_description );

				param( 'blog_locale', 'string', true );
				$edited_Blog->set( 'locale', $blog_locale );

				param( 'blog_access_type', 'string', true );
				$edited_Blog->set( 'access_type', $blog_access_type );

				param( 'blog_siteurl', 'string', true );
				$edited_Blog->set( 'siteurl', $blog_siteurl );

				param( 'blog_stub', 'string', true );
				$edited_Blog->set( 'stub', $blog_stub );

				param( 'blog_keywords', 'string', true );
				$edited_Blog->set( 'keywords', $blog_keywords );

				param( 'blog_disp_bloglist', 'integer', 0 );
				$edited_Blog->set( 'disp_bloglist', $blog_disp_bloglist );

				param( 'blog_default_skin', 'string', true );
				$edited_Blog->set( 'default_skin', $blog_default_skin );

				param( 'blog_force_skin', 'integer', 0 );
				$edited_Blog->set( 'force_skin', 1-$blog_force_skin );

				break;

			case 'perm':
				// Update the user permissions for this blog
				blog_update_user_perms( $blog );
				break;

			case 'advanced':
				param( 'blog_filename', 'string', '' );
				$edited_Blog->set( 'filename', $blog_filename );

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

		// Commit update to the DB:
		$edited_Blog->dbupdate();
		
		// Commit changes in cache:
		$BlogCache->add( $edited_Blog );

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
				$blog_name = get_bloginfo( 'name' );
				$blog_shortname = get_bloginfo( 'shortname' );
				$blog_tagline = get_bloginfo( 'tagline' );
				$blog_description = get_bloginfo( 'description' );
				$blog_longdesc = get_bloginfo( 'longdesc' );
				$blog_locale = get_bloginfo( 'locale' );
				$blog_access_type = $edited_Blog->get( 'access_type' );
				$blog_siteurl = get_bloginfo( 'subdir' );
				$blog_stub = get_bloginfo( 'stub' );
				$blog_roll = get_bloginfo( 'blogroll' );
				$blog_keywords = get_bloginfo( 'keywords' );
				$blog_disp_bloglist = get_bloginfo( 'disp_bloglist' );
				$blog_default_skin = get_bloginfo( 'default_skin' );
				$blog_force_skin = $edited_Blog->get( 'force_skin' );
				require( dirname(__FILE__) . '/_blogs_form.php' );
				break;
				
			case 'perm':
				require( dirname(__FILE__) . '/_blogs_permissions.form.php' );
				break;
				
			case 'advanced':
				$blog_filename = get_bloginfo( 'filename' );
				$blog_staticfilename = get_bloginfo( 'staticfilename' );
				$blog_allowtrackbacks = get_bloginfo( 'allowtrackbacks' );
				$blog_allowpingbacks = get_bloginfo( 'allowpingbacks' );
				$blog_pingb2evonet = get_bloginfo( 'pingb2evonet' );
				$blog_pingtechnorati = get_bloginfo( 'pingtechnorati' );
				$blog_pingweblogs = get_bloginfo( 'pingweblogs' );
				$blog_pingblodotgs = get_bloginfo( 'pingblodotgs' );
				require( dirname(__FILE__) . '/_blogs_advanced.form.php' );
				break;
		}
		echo '</div>';
		require( dirname(__FILE__). '/_footer.php' );
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
				<h3><?php printf( T_('Delete Blog [%s]?'), $deleted_Blog->dget( 'name' ) )?></h3>

				<p><?php echo T_('Deleting this blog will also delete all its categories, posts and comments!') ?></p>

				<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

				<p>
					<form action="b2blogs.php" method="get" class="inline">
						<input type="hidden" name="action" value="delete" />
						<input type="hidden" name="blog" value="<?php $deleted_Blog->ID() ?>" />
						<input type="hidden" name="confirm" value="1" />
						
						<?php 
						if( is_file( $deleted_Blog->get('dynfilepath') ) )
						{
							?>
							<input type="checkbox" id="delete_stub_file" name="delete_stub_file" value="1" />
							<label for="delete_stub_file"><?php printf( T_('Also try to delete stub file [<strong><a %s>%s</a></strong>]'), 'href="'.$deleted_Blog->dget('dynurl').'"', $deleted_Blog->dget('dynfilepath') ); ?></label><br />
							<br />
							<?php
						}
						if( is_file( $deleted_Blog->get('staticfilepath') ) )
						{ 
							?>
							<input type="checkbox" id="delete_static_file" name="delete_static_file" value="1" />
							<label for="delete_static_file"><?php printf( T_('Also try to delete static file [<strong><a %s>%s</a></strong>]'), 'href="'.$deleted_Blog->dget('staticurl').'"', $deleted_Blog->dget('staticfilepath') ); ?></label><br />
							<br />
							<?php
						}
						?>						
						
						<input type="submit" value="<?php echo T_('I am sure!') ?>" class="search" />
					</form>
					<form action="b2blogs.php" method="get" class="inline">
						<input type="submit" value="<?php echo T_('CANCEL') ?>" class="search" />
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
			echo '<p>', T_('You haven\'t set a static filename for this blog!'), "</p>\n</div>\n";
			break;
		}

		// Determine the edit folder:
		$filename = $edited_Blog->get('dynfilepath');
		$staticfilename = $edited_Blog->get('staticfilepath');

		printf( '<p>'.T_('Generating page from <strong>%s</strong> to <strong>%s</strong>...'), $filename, $staticfilename );
		echo "<br />\n";
		flush();

		ob_start();
		require $filename;
		$page = ob_get_contents();
		ob_end_clean();

		// Switching back to default locale (the blog page may have changed it):
		locale_activate( $default_locale );

		echo T_('Writing to file...'), '<br />', "\n";

		$fp = fopen ( $staticfilename, 'w');
		fwrite($fp, $page);
		fclose($fp);

		echo T_('Done.');
	?>
		</p>
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
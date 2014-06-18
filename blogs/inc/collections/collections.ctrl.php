<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: collections.ctrl.php 6493 2014-04-17 05:45:28Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$AdminUI->set_path( 'blogs' );

$default_tab = $current_User->check_perm( 'options', 'view' ) ? 'site_settings' : 'list';
param( 'tab', 'string', $default_tab, true );

param_action( 'list' );

if( !in_array( $action, array( 'new', 'new-selskin', 'new-installskin', 'new-name', 'list', 'create', 'update_settings_blog', 'update_settings_site' ) ) )
{
	if( valid_blog_requested() )
	{
		// echo 'valid blog requested';
		$edited_Blog = & $Blog;
	}
	else
	{
		// echo 'NO valid blog requested';
		$action = 'list';
	}
}
else
{	// We are not working on a specific blog (yet) -- prevent highlighting one in the list
	set_working_blog( 0 );
}


/**
 * Perform action:
 */
switch( $action )
{
	case 'new':
		// New collection: Select blog type
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$AdminUI->append_path_level( 'new', array( 'text' => T_('New') ) );
		break;

	case 'new-selskin':
	case 'new-installskin':
		// New collection: Select or Install skin
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		param( 'kind', 'string', true );

		// dh> TODO: "New %s" is probably too generic. What can %s become? (please comment it in "TRANS")
		// Tblue> Look at get_collection_kinds(). I wrote a TRANS comment (30.01.09 22:03, HEAD).
		$AdminUI->append_path_level( 'new', array( 'text' => sprintf( /* TRANS: %s can become "Standard blog", "Photoblog", "Group blog" or "Forum" */ T_('New %s'), get_collection_kinds($kind) ) ) );
		break;

	case 'new-name':
		// New collection: Set general parameters
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$edited_Blog = new Blog( NULL );

		$edited_Blog->set( 'owner_user_ID', $current_User->ID );

		param( 'kind', 'string', true );
		$edited_Blog->init_by_kind( $kind );

		param( 'skin_ID', 'integer', true );

		$AdminUI->append_path_level( 'new', array( 'text' => sprintf( T_('New %s'), get_collection_kinds($kind) ) ) );
		break;

	case 'create':
		// Insert into DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$edited_Blog = new Blog( NULL );

		$edited_Blog->set( 'owner_user_ID', $current_User->ID );

		param( 'kind', 'string', true );
		$edited_Blog->init_by_kind( $kind );
		if( ! $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
		{ // validate the urlname, which was already set by init_by_kind() function
		 	// It needs to validated, because the user can not set the blog urlname, and every new blog would have the same urlname without validation.
		 	// When user has edit permission to blog admin part, the urlname will be validated in load_from_request() function.
			$edited_Blog->set( 'urlname', urltitle_validate( $edited_Blog->get( 'urlname' ) , '', 0, false, 'blog_urlname', 'blog_ID', 'T_blogs' ) );
		}

		param( 'skin_ID', 'integer', true );
		$edited_Blog->set_setting( 'normal_skin_ID', $skin_ID );

		if( $edited_Blog->load_from_Request( array() ) )
		{
			// create the new blog
			$edited_Blog->create( $kind );

			// We want to highlight the edited object on next list display:
			// $Session->set( 'fadeout_array', array( 'blog_ID' => array($edited_Blog->ID) ) );

			header_redirect( $dispatcher.'?ctrl=coll_settings&tab=features&blog='.$edited_Blog->ID ); // will save $Messages into Session
		}
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed
			// Delete from DB:
			$msg = sprintf( T_('Blog &laquo;%s&raquo; deleted.'), $edited_Blog->dget('name') );

			if( $edited_Blog->dbdelete() )
			{ // Blog was deleted
				$Messages->add( $msg, 'success' );

				$BlogCache->remove_by_ID( $blog );
				unset( $edited_Blog );
				unset( $Blog );
				forget_param( 'blog' );
				set_working_blog( 0 );
				$UserSettings->delete( 'selected_blog' );	// Needed or subsequent pages may try to access the delete blog
				$UserSettings->dbupdate();
			}

			$action = 'list';
			// Redirect so that a reload doesn't write to the DB twice:
			$redirect_to = param( 'redirect_to', 'url', '?ctrl=collections' );
			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{ // Check if blog has delete restrictions
			if( ! $edited_Blog->check_delete( sprintf( T_('Cannot delete Blog &laquo;%s&raquo;'), $edited_Blog->get_name() ), array( 'file_root_ID', 'cat_blog_ID' ) ) )
			{ // There are restrictions:
				$action = 'view';
			}
		}
		break;


	case 'update_settings_blog':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collectionsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( param( 'default_blog_ID', 'integer', NULL ) !== NULL )
		{
			$Settings->set( 'default_blog_ID', $default_blog_ID );
		}

		$Settings->set( 'blogs_order_by', param( 'blogs_order_by', 'string', true ) );
		$Settings->set( 'blogs_order_dir', param( 'blogs_order_dir', 'string', true ) );

		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		$Settings->set( 'newblog_cache_enabled', param( 'newblog_cache_enabled', 'integer', 0 ) );
		$Settings->set( 'newblog_cache_enabled_widget', param( 'newblog_cache_enabled_widget', 'integer', 0 ) );

		// Outbound pinging:
		param( 'outbound_notifications_mode', 'string', true );
		$Settings->set( 'outbound_notifications_mode',  get_param('outbound_notifications_mode') );

		// Categories:
		$Settings->set( 'allow_moving_chapters', param( 'allow_moving_chapters', 'integer', 0 ) );
		$Settings->set( 'chapter_ordering', param( 'chapter_ordering', 'string', 'alpha' ) );

		// Cross posting:
		$Settings->set( 'cross_posting', param( 'cross_posting', 'integer', 0 ) );
		$Settings->set( 'cross_posting_blogs', param( 'cross_posting_blogs', 'integer', 0 ) );

		// Redirect moved posts:
		$Settings->set( 'redirect_moved_posts', param( 'redirect_moved_posts', 'integer', 0 ) );

		// Subscribing to new blogs:
		$Settings->set( 'subscribe_new_blogs', param( 'subscribe_new_blogs', 'string', 'public' ) );

		// Default skins:
		if( param( 'def_normal_skin_ID', 'integer', NULL ) !== NULL )
		{ // this can't be NULL
			$Settings->set( 'def_normal_skin_ID', get_param( 'def_normal_skin_ID' ) );
		}
		$Settings->set( 'def_mobile_skin_ID', param( 'def_mobile_skin_ID', 'integer', 0 ) );
		$Settings->set( 'def_tablet_skin_ID', param( 'def_tablet_skin_ID', 'integer', 0 ) );

		// Comment recycle bin
		param( 'auto_empty_trash', 'integer', $Settings->get_default('auto_empty_trash'), false, false, true, false );
		$Settings->set( 'auto_empty_trash', get_param('auto_empty_trash') );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('Blog settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=collections&tab=blog_settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'update_settings_site':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collectionsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// Lock system
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			$system_lock = param( 'system_lock', 'integer', 0 );
			if( $Settings->get( 'system_lock' ) && ( ! $system_lock ) && ( ! $Messages->has_errors() ) && ( 1 == $Messages->count() ) )
			{ // System lock was turned off and there was no error, remove the warning about the system lock
				$Messages->clear();
			}
			$Settings->set( 'system_lock', $system_lock );
		}

		// Site code
		$Settings->set( 'site_code',  param( 'site_code', 'string', '' ) );

		// Site color
		$site_color = param( 'site_color', 'string', '' );
		param_check_regexp( 'site_color', '~^(#([a-f0-9]{3}){1,2})?$~i', T_('Invalid color code.'), NULL, false );
		$Settings->set( 'site_color', $site_color );

		// Site short name
		$short_name = param( 'notification_short_name', 'string', '' );
		param_check_not_empty( 'notification_short_name' );
		$Settings->set( 'notification_short_name', $short_name );

		// Site long name
		$Settings->set( 'notification_long_name', param( 'notification_long_name', 'string', '' ) );

		// Small site logo url
		$Settings->set( 'notification_logo', param( 'notification_logo', 'string', '' ) );

		// Large site logo url
		$Settings->set( 'notification_logo_large', param( 'notification_logo_large', 'string', '' ) );

		// Site footer text
		$Settings->set( 'site_footer_text', param( 'site_footer_text', 'string', '' ) );

		// Enable site skins
		$Settings->set( 'site_skins_enabled', param( 'site_skins_enabled', 'integer', 0 ) );

		// Blog for info pages
		$Settings->set( 'info_blog_ID', param( 'info_blog_ID', 'integer', 0 ) );

		if( param( 'default_blog_ID', 'integer', NULL ) !== NULL )
		{
			$Settings->set( 'default_blog_ID', $default_blog_ID );
		}

		// Reload page timeout
		$reloadpage_timeout = param_duration( 'reloadpage_timeout' );
		if( $reloadpage_timeout > 99999 )
		{
			param_error( 'reloadpage_timeout', sprintf( T_( 'Reload-page timeout must be between %d and %d seconds.' ), 0, 99999 ) );
		}
		$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

		// General cache
		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('Site settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=collections&tab=site_settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		break;
}

$AdminUI->set_path( 'blogs', $tab );

$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Structure'), '?ctrl=collections' );

/**
 * Display page header, menus & messages:
 */
if( strpos( $action, 'new' ) === false && $action != 'create' )
{ // Not creating a new blog:
	// fp> TODO: fall back to ctrl=chapters when no perm for blog_properties
	$AdminUI->set_coll_list_params( 'blog_properties', 'edit',
												array( 'ctrl' => 'coll_settings', 'tab' => 'general' ),
												T_('Site'), '?ctrl=collections&amp;blog=0' );

	$AdminUI->breadcrumbpath_add( T_('Site'), '?ctrl=collections' );

	switch( $tab )
	{
		case 'site_settings':
			// Check minimum permission:
			$current_User->check_perm( 'options', 'view', true );

			$AdminUI->breadcrumbpath_add( T_('Site Settings'), '?ctrl=collections&amp;tab=site_settings' );
			init_colorpicker_js();
			break;

		case 'blog_settings':
			// Check minimum permission:
			$current_User->check_perm( 'options', 'view', true );

			$AdminUI->breadcrumbpath_add( T_('Blog Settings'), '?ctrl=collections&amp;tab=blog_settings' );
			break;

		case 'list':
		default:
			init_field_editor_js( array(
					'field_prefix' => 'order-blog-',
					'action_url' => $ReqURI.'&order_action=update&order_data=',
				) );

			$AdminUI->breadcrumbpath_add( T_('Blogs'), '?ctrl=collections&amp;tab=list' );
			break;
	}
}
else
{	// Creating a new blog
	$AdminUI->breadcrumbpath_add( T_('New blog'), '?ctrl=collections&amp;action=new' );
	// Init JS to autcomplete the user logins
	init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


switch( $action )
{
	case 'new':
		$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'collections/views/_coll_sel_type.view.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'new-selskin':
	case 'new-installskin':
		$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'skins/views/_coll_sel_skin.view.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'new-name':
	case 'create': // in case of validation error
		$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$next_action = 'create';

		$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		// Not confirmed
		if( $current_User->check_perm( 'files', 'view', false ) )
		{ // User has permission to view files in this blog's fileroot, diplay link
			$delete_warning = sprintf( T_('Deleting this blog will also delete ALL its categories, posts, comments and ALL its attached files in the blog\'s <a %s>fileroot</a> !'),
				'href="'.$edited_Blog->get_filemanager_link().'"' );
		}
		else
		{ // User has no permission to view files in this blog's fielroot
			$delete_warning = T_('Deleting this blog will also delete ALL its categories, posts, comments and ALL its attached files in the blog\'s <a %s>fileroot</a> !');
		}
		?>
		<div class="panelinfo">
			<h3><?php printf( T_('Delete blog [%s]?'), $edited_Blog->dget( 'name' ) )?></h3>

			<p class="warning"><?php echo $delete_warning; ?></p>

			<p><?php echo T_('Note: Some files in this blog\'s fileroot may be linked to users or to other blogs posts and comments. Those links will be inadvertently deleted!') ?></p>

			<p class="warning"><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

			<p>

			<?php
				$redirect_to = param( 'redirect_to', 'url', '' );

				$Form = new Form( NULL, '', 'get', 'none' );

				$Form->begin_form( 'inline' );
					$Form->add_crumb( 'collection' );
					$Form->hidden_ctrl();
					$Form->hidden( 'tab', $tab );
					$Form->hidden( 'action', 'delete' );
					$Form->hidden( 'blog', $edited_Blog->ID );
					$Form->hidden( 'confirm', 1 );
					$Form->hidden( 'redirect_to', $redirect_to );
					$Form->submit( array( '', T_('I am sure!'), 'DeleteButton' ) );
				$Form->end_form();

				$Form = new Form( !empty( $redirect_to ) ? $redirect_to: NULL, '', 'get', 'none' );

				$Form->begin_form( 'inline' );
					if( empty( $redirect_to ) )
					{ // If redirect url is not defined we should go to blogs list after cancel action
						$Form->hidden_ctrl();
						$Form->hidden( 'tab', $tab );
						$Form->hidden( 'blog', 0 );
					}
					$Form->submit( array( '', T_('CANCEL'), 'CancelButton' ) );
				$Form->end_form();
			?>

			</p>

			</div>
		<?php
		break;


	default:
		// List the blogs:
		$AdminUI->disp_payload_begin();
		// Display VIEW:
		switch( $tab )
		{
			case 'site_settings':
				$AdminUI->disp_view( 'collections/views/_coll_settings_site.form.php' );
				break;

			case 'blog_settings':
				$AdminUI->disp_view( 'collections/views/_coll_settings_blog.form.php' );
				break;

			case 'list':
			default:
				$AdminUI->disp_view( 'collections/views/_coll_list.view.php' );
				break;
		}
		$AdminUI->disp_payload_end();

}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>
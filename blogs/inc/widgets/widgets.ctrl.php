<?php
/**
 * This file implements the UI controller for managing widgets inside of a blog.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;
/**
 * @var Plugins
 */
global $Plugins;

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

param( 'action', 'string', 'list' );
param( 'display_mode', 'string', 'normal' );
$display_mode = ( in_array( $display_mode, array( 'js', 'normal' ) ) ? $display_mode : 'normal' );
if( $display_mode == 'js' )
{	// Javascript in debug mode conflicts/fails.
	// fp> TODO: either fix the debug javascript or have an easy way to disable JS in the debug output.
	$debug = 0;
}
// This should probably be handled with teh existing $mode var

/*
 * Init the objects we want to work on.
 */
switch( $action )
{
 	case 'nil':
 	case 'list':
 	case 'reload':
		// Do nothing
		break;

	case 'create':
		param( 'type', 'string', true );
		param( 'code', 'string', true );
	case 'new':
		param( 'container', 'string', true, true );	// memorize
		break;

	case 're-order' : // js request
		param( 'container_list', 'string', true );
		$containers_list = explode( ',', $container_list );
		$containers = array();
		foreach( $containers_list as $a_container )
		{	// add each container and grab its widgets:
			if( $container_name = trim( str_replace( array( 'container_', '_' ), array( '', ' ' ), $a_container ), ',' ) )
			{
				$containers[ $container_name ] = explode( ',', param( trim( $a_container, ',' ), 'string', true ) );
			}
		}
		break;

	case 'edit':
	case 'update':
	case 'delete':
	case 'move_up':
	case 'move_down':
	case 'toggle':
		param( 'wi_ID', 'integer', true );
		$WidgetCache = & get_WidgetCache();
		$edited_ComponentWidget = & $WidgetCache->get_by_ID( $wi_ID );
		// Take blog from here!
		// echo $edited_ComponentWidget->coll_ID;
 		set_working_blog( $edited_ComponentWidget->coll_ID );
		$BlogCache = & get_BlogCache();
		/**
		* @var Blog
		*/
		$Blog = & $BlogCache->get_by_ID( $blog );

		break;

	default:
		debug_die( 'Init objects: unhandled action' );
}

if( ! valid_blog_requested() )
{
	debug_die( 'Invalid blog requested' );
}

switch( $display_mode )
{
	case 'js' : // js response needed
// fp> when does this happen -- should be documented
		if( !$current_User->check_perm( 'blog_properties', 'edit', false, $blog ) )
		{	// user doesn't have permissions
			$Messages->add( T_('You do not have permission to perform this action' ) );
// fp>does this only happen when we try to edit settings. The hardcoded 'closeWidgetSettings' response looks bad.
			send_javascript_message( array( 'closeWidgetSettings' => array() ) );
		}
		break;

	case 'normal':
	default : // take usual approach
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
}

// Get Skin used by current Blog:
$SkinCache = & get_SkinCache();
$Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );
// Make sure containers are loaded for that skin:
$container_list = $Skin->get_containers();


/**
 * Perform action:
 */
switch( $action )
{
 	case 'nil':
 	case 'new':
 	case 'edit':
		// Do nothing
		break;

	case 'create':
		// Add a Widget to container:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		if( !in_array( $container, $container_list ) )
		{
			$Messages->add( T_('WARNING: you are adding to a container that does not seem to be part of the current skin.'), 'error' );
		}

		switch( $type )
		{
			case 'core':
				// Check the requested core widget is valid:
				$objtype = $code.'_Widget';
				load_class( 'widgets/widgets/_'.$code.'.widget.php', $objtype );
				$edited_ComponentWidget = new $objtype();
				break;

			case 'plugin':
				if( ! $Plugin = & $Plugins->get_by_code( $code ) )
				{
					debug_die( 'Requested plugin not found' );
				}
				if( ! $Plugins->has_event( $Plugin->ID, 'SkinTag' ) )
				{
					debug_die( 'Requested plugin does not support SkinTag' );
				}
				$edited_ComponentWidget = new ComponentWidget( NULL, 'plugin', $code );
				break;

			default:
				debug_die( 'Unhandled widget type' );
		}

		$edited_ComponentWidget->set( 'coll_ID', $Blog->ID );
		$edited_ComponentWidget->set( 'sco_name', $container );
		$edited_ComponentWidget->set( 'enabled', 1 );

		// INSERT INTO DB:
		$edited_ComponentWidget->dbinsert();

		$Messages->add( sprintf( T_('Widget &laquo;%s&raquo; has been added to container &laquo;%s&raquo;.'),
					$edited_ComponentWidget->get_name(), T_($container)	), 'success' );

		switch( $display_mode )
		{
			case 'js' :	// this is a js call, lets return the settings page -- fp> what do you mean "settings page" ?
				// fp> wthis will visually live insert the new widget into the container; it probably SHOULD open the edit properties right away
				send_javascript_message( array(
					'addNewWidgetCallback' => array(
						$edited_ComponentWidget->ID,
						$container,
						$edited_ComponentWidget->get( 'order' ),
						$edited_ComponentWidget->get_name(),
					),
					// Open widget settings:
					'editWidget' => array(
						'wi_ID_'.$edited_ComponentWidget->ID,
					),
				) );
				break;

			case 'normal' :
			default : // take usual action
				header_redirect( '?ctrl=widgets&action=edit&wi_ID='.$edited_ComponentWidget->ID );
				break;
		}
		break;


	case 'update':
		// Update Settings

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$edited_ComponentWidget->load_from_Request();

		if(	! param_errors_detected() )
		{	// Update settings:
			$edited_ComponentWidget->dbupdate();
			$Messages->add( T_('Widget settings have been updated'), 'success' );
			switch( $display_mode )
			{
				case 'js' : // js reply
					$edited_ComponentWidget->init_display( array() );
					send_javascript_message(array( 'widgetSettingsCallback' => array( $edited_ComponentWidget->ID, $edited_ComponentWidget->get_desc_for_list() ), 'closeWidgetSettings' => array() ), true );
					break;
			}
			$action = 'list';
			$Session->set( 'fadeout_id', $edited_ComponentWidget->ID );
			header_redirect( '?ctrl=widgets&blog='.$Blog->ID, 303 );
		}
		elseif( $display_mode == 'js' )
		{ // send errors back as js
			send_javascript_message( array(), true );
		}
		break;


	case 'move_up':
		// Move the widget up:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$order = $edited_ComponentWidget->order;
		$DB->begin();

 		// Get the previous element
		$row = $DB->get_row( 'SELECT *
														FROM T_widget
													 WHERE wi_coll_ID = '.$Blog->ID.'
													 	 AND wi_sco_name = '.$DB->quote($edited_ComponentWidget->sco_name).'
														 AND wi_order < '.$order.'
													 ORDER BY wi_order DESC
													 LIMIT 0,1' );
		if( !empty( $row) )
		{
			$prev_ComponentWidget = new ComponentWidget( $row );
			$prev_order = $prev_ComponentWidget->order;

			$edited_ComponentWidget->set( 'order', 0 );	// Temporary
			$edited_ComponentWidget->dbupdate();

			$prev_ComponentWidget->set( 'order', $order );
			$prev_ComponentWidget->dbupdate();

			$edited_ComponentWidget->set( 'order', $prev_order );
			$edited_ComponentWidget->dbupdate();

		}
		$DB->commit();
		break;

	case 'move_down':
		// Move the widget down:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$order = $edited_ComponentWidget->order;
		$DB->begin();

 		// Get the next element
		$row = $DB->get_row( 'SELECT *
														FROM T_widget
													 WHERE wi_coll_ID = '.$Blog->ID.'
													 	 AND wi_sco_name = '.$DB->quote($edited_ComponentWidget->sco_name).'
														 AND wi_order > '.$order.'
													 ORDER BY wi_order ASC
													 LIMIT 0,1' );
		if( !empty( $row ) )
		{
			$next_ComponentWidget = new ComponentWidget( $row );
			$next_order = $next_ComponentWidget->order;

			$edited_ComponentWidget->set( 'order', 0 );	// Temporary
			$edited_ComponentWidget->dbupdate();

			$next_ComponentWidget->set( 'order', $order );
			$next_ComponentWidget->dbupdate();

			$edited_ComponentWidget->set( 'order', $next_order );
			$edited_ComponentWidget->dbupdate();

		}
		$DB->commit();
		break;

	case 'toggle':
		// Enable or disable the widget:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );
		
		$enabled = $edited_ComponentWidget->get( 'enabled' );
		$edited_ComponentWidget->set( 'enabled', (int)! $enabled );
		$edited_ComponentWidget->dbupdate();

		if ( $enabled )
		{
			$msg = T_( 'Widget has been disabled.' );
		}
		else
		{
			$msg = T_( 'Widget has been enabled.' );
		}
		$Messages->add( $msg, 'success' );

		if ( $display_mode == 'js' )
		{
			// EXITS:
			send_javascript_message( array( 'doToggle' => array( $edited_ComponentWidget->ID, (int)! $enabled ) ) );
		}
		header_redirect( '?ctrl=widgets&blog='.$Blog->ID, 303 );
		break;

	case 'delete':
		// Remove a widget from container:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$msg = sprintf( T_('Widget &laquo;%s&raquo; removed.'), $edited_ComponentWidget->get_name() );
		$edited_widget_ID = $edited_ComponentWidget->ID;
		$edited_ComponentWidget->dbdelete( true );
		unset( $edited_ComponentWidget );
		forget_param( 'wi_ID' );
		$Messages->add( $msg, 'success' );

		switch( $display_mode )
		{
			case 'js' :	// js call : return success message
				send_javascript_message( array( 'doDelete' => $edited_widget_ID ) );
				break;

			case 'normal' :
			default : // take usual action
				// PREVENT RELOAD & Switch to list mode:
				header_redirect( '?ctrl=widgets&blog='.$blog );
				break;
		}
		break;

 	case 'list':
		break;

 	case 're-order' : // js request
 		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );
		
 		$DB->begin();

 		// Reset the current orders and make container names temp to avoid duplicate entry errors
		$DB->query( 'UPDATE T_widget
										SET wi_order = wi_order * -1,
												wi_sco_name = CONCAT( \'temp_\', wi_sco_name )
									WHERE wi_coll_ID = '.$Blog->ID );

		foreach( $containers as $container => $widgets )
		{	// loop through each container and set new order
			$order = 0; // reset counter for this container
			foreach( $widgets as $widget )
			{	// loop through each widget
				if( $widget = preg_replace( '~[^0-9]~', '', $widget ) )
				{ // valid widget id
					$order++;
					$DB->query( 'UPDATE T_widget
													SET wi_order = '.$order.',
															wi_sco_name = '.$DB->quote( $container ).'
												WHERE wi_ID = '.$widget.'
												  AND wi_coll_ID = '.$Blog->ID );	// Doh! Don't trust the client request!!
				}
			}
		}

		// Cleanup deleted widgets and empty temp containers
		$DB->query( 'DELETE FROM T_widget
									WHERE wi_order < 1
										AND wi_coll_ID = '.$Blog->ID ); // Doh! Don't touch other blogs!

		$DB->commit();

 		$Messages->add( T_( 'Widgets updated' ), 'success' );
 		send_javascript_message( array( 'sendWidgetOrderCallback' => array( 'blog='.$Blog->ID ) ) ); // exits() automatically
 		break;


	case 'reload':
		// Reload containers:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

 		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		$SkinCache = & get_SkinCache();
		/**
		 * @var Skin
		 */
		$edited_Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );

		// Look for containers in skin file:
		$edited_Skin->discover_containers();

		// Save to DB:
		$edited_Skin->db_save_containers();

		header_redirect( '?ctrl=widgets&blog='.$Blog->ID, 303 );
		break;

	default:
		debug_die( 'Action: unhandled action' );
}

if( $display_mode == 'normal' )
{	// this is a normal (not a JS) request
	// fp> This probably shouldn't be handled like this but with $mode
	/**
	 * Display page header, menus & messages:
	 */
	$AdminUI->set_coll_list_params( 'blog_properties', 'edit', array( 'ctrl' => 'widgets' ),
				T_('List'), '?ctrl=collections&amp;blog=0' );

	$AdminUI->set_path( 'blogs', 'widgets' );

	// load the js and css required to make the magic work
	add_js_headline( '
	/**
	 * @internal T_ array of translation strings required by the UI
	 */
	var T_arr = new Array();
	T_arr["Changes pending"] = \''.TS_( 'Changes pending' ).'\';
	T_arr["Saving changes"] = \''.TS_( 'Saving changes' ).'\';
	T_arr["Widget order unchanged"] = \''.TS_( 'Widget order unchanged' ).'\';
	T_arr["Update cancelled"] = \''.TS_( 'Update cancelled' ).'\';
	T_arr["Update Paused"] = \''.TS_( 'Update Paused' ).'\';

	/**
	 * Image tags for the JavaScript widget UI.
	 *
	 * @internal Tblue> We get the whole img tags here (easier).
	 */
	var enabled_icon_tag = \''.get_icon( 'enabled', 'imgtag', array( 'title' => T_( 'The widget is enabled.' ) ) ).'\';
	var disabled_icon_tag = \''.get_icon( 'disabled', 'imgtag', array( 'title' => T_( 'The widget is disabled.' ) ) ).'\';
	var activate_icon_tag = \''.get_icon( 'activate', 'imgtag', array( 'title' => T_( 'Enable this widget!' ) ) ).'\';
	var deactivate_icon_tag = \''.get_icon( 'deactivate', 'imgtag', array( 'title' => T_( 'Disable this widget!' ) ) ).'\';

	var b2evo_dispatcher_url = "'.$admin_url.'";' );
	require_js( '#jqueryUI#' ); // auto requires jQuery
	require_js( 'communication.js' ); // auto requires jQuery
	require_js( 'blog_widgets.js' );
	require_css( 'blog_widgets.css' );


	$AdminUI->breadcrumbpath_init( false );
	$AdminUI->breadcrumbpath_add( T_('Blog settings'), '?ctrl=coll_settings&amp;blog=$blog$' );
	$AdminUI->breadcrumbpath_add( T_('Widgets'), '?ctrl=widgets&amp;blog=$blog$' );

	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();
}

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'new':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'widgets/views/_widget_list_available.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;


	case 'edit':
	case 'update':	// on error
		switch( $display_mode )
		{
			case 'js' : // js request
				ob_start();
				// Display VIEW:
				$AdminUI->disp_view( 'widgets/views/_widget.form.php' );
				$output = ob_get_clean();
				send_javascript_message( array( 'widgetSettings' => $output ) );
				break;

			case 'normal' :
			default : // take usual action
				// Begin payload block:
				$AdminUI->disp_payload_begin();

				// Display VIEW:
				$AdminUI->disp_view( 'widgets/views/_widget.form.php' );

				// End payload block:
				$AdminUI->disp_payload_end();
				break;
		}
		break;


	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:

		// this will be enabled if js available:
		echo '<div class="available_widgets">'."\n";
		echo '<div class="available_widgets_toolbar"><a href="#" class="rollover floatright" style="padding: 1px 0;">'.get_icon('close').'</a>'.T_( 'Select widget to add:' ).'</div>'."\n";
		echo '<div id="available_widgets_inner">'."\n";
		$AdminUI->disp_view( 'widgets/views/_widget_list_available.view.php' );
		echo '</div></div>'."\n";
		echo '
		<script type="text/javascript">
			<!--
			var blog = '.$Blog->ID.';
			// -->
		</script>
		';

		// Display VIEW:
		$AdminUI->disp_view( 'widgets/views/_widget_list.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log$
 * Revision 1.44  2010/01/30 18:55:35  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.43  2010/01/29 17:21:38  efy-yury
 * add: crumbs in ajax calls
 *
 * Revision 1.42  2010/01/21 18:16:49  efy-yury
 * update: fadeouts
 *
 * Revision 1.41  2010/01/20 20:46:47  efy-yury
 * fix: toggleWidget bug in js
 * add: widjets controller redirect
 *
 * Revision 1.40  2010/01/17 04:14:38  fplanque
 * minor / fixes
 *
 * Revision 1.39  2010/01/16 14:27:04  efy-yury
 * crumbs, fadeouts, redirect, action_icon
 *
 * Revision 1.38  2009/12/20 20:38:26  fplanque
 * Enhanced skin containers reload for skin developers
 *
 * Revision 1.37  2009/12/12 01:13:08  fplanque
 * A little progress on breadcrumbs on menu structures alltogether...
 *
 * Revision 1.36  2009/09/29 03:47:07  fplanque
 * doc
 *
 * Revision 1.35  2009/09/26 20:51:37  tblue246
 * minor
 *
 * Revision 1.34  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.33  2009/09/25 07:33:30  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.32  2009/09/18 15:30:24  waltercruz
 * Fixing load_class for PHP5
 *
 * Revision 1.31  2009/09/14 13:52:55  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.30  2009/09/12 11:01:28  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.29  2009/08/03 13:19:11  tblue246
 * Fixed http://forums.b2evolution.net//viewtopic.php?p=94778
 *
 * Revision 1.28  2009/08/03 12:35:09  tblue246
 * JS widget screen: Open settings page after adding a new widget
 *
 * Revision 1.27  2009/07/07 15:21:10  yabs
 * Bug fix : error messages are now shown
 *
 * Revision 1.26  2009/06/01 12:23:59  tblue246
 * Translation fixes
 *
 * Revision 1.25  2009/03/15 01:32:35  fplanque
 * fixed yabbariffic bug that killed widgets when editing 2 different blogs at the same time
 *
 * Revision 1.24  2009/03/14 21:50:46  fplanque
 * still cleaning up...
 *
 * Revision 1.23  2009/03/14 20:53:41  fplanque
 * Fixed the add widget links so that you now know what you can click on or not
 *
 * Revision 1.22  2009/03/14 20:01:05  fplanque
 * stop the clickless nonsense that opens and closes without your consent
 *
 * Revision 1.21  2009/03/13 02:32:08  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.20  2009/03/13 00:59:20  fplanque
 * fixing debug mode
 *
 * Revision 1.19  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.18  2009/02/23 18:21:07  tblue246
 * Fixing log :/
 *
 * Revision 1.17  2009/02/23 18:13:40  yabs
 * Next attempt at rolling back my incompetance :D
 *
 * Revision 1.16  2009/02/23 08:13:31  yabs
 * Added check for excerpts
 *
 * Revision 1.15  2009/02/05 21:33:34  tblue246
 * Allow the user to enable/disable widgets.
 * Todo:
 * 	* Fix CSS for the widget state bullet @ JS widget UI.
 * 	* Maybe find a better solution than modifying get_Cache() to get only enabled widgets... :/
 * 	* Buffer JS requests when toggling the state of a widget??
 *
 * Revision 1.14  2008/12/30 23:00:42  fplanque
 * Major waste of time rolling back broken black magic! :(
 * 1) It was breaking the backoffice as soon as $admin_url was not a direct child of $baseurl.
 * 2) relying on dynamic argument decoding for backward comaptibility is totally unmaintainable and unreliable
 * 3) function names with () in log break searches big time
 * 4) complexity with no purpose (at least as it was)
 *
 * Revision 1.12  2008/10/05 04:36:50  fplanque
 * notes for Yabba
 *
 * Revision 1.11  2008/10/02 23:33:08  blueyed
 * - require_js(): remove dirty dependency handling for communication.js.
 * - Add add_js_headline() for adding inline JS and use it for admin already.
 */
?>

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

load_class( 'widgets/model/_widget.class.php' );

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
		// Do nothing
		break;

	case 'create':
		param( 'type', 'string', true );
		param( 'code', 'string', true );
	case 'new':
		param( 'container', 'string', true, true );	// memorize
		break;

	case 're-order' : // js request
		param( 'container_list', 'string' );
		$containers_list = explode( ',', $container_list );
		$containers = array();
		foreach( $containers_list as $a_container )
		{	// add each container and grab it's widgets
			if( $container_name = trim( str_replace( array( 'container_', '_' ), array( '', ' ' ), $a_container ), ',' ) )
			{
				$containers[ $container_name ] = explode( ',', param( trim( $a_container, ',' ), 'string' ) );
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
		$WidgetCache = & get_Cache( 'WidgetCache' );
		$edited_ComponentWidget = & $WidgetCache->get_by_ID( $wi_ID );
		// Take blog from here!
		// echo $edited_ComponentWidget->coll_ID;
 		set_working_blog( $edited_ComponentWidget->coll_ID );
		$BlogCache = & get_Cache( 'BlogCache' );
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
$SkinCache = & get_Cache( 'SkinCache' );
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
		if( !in_array( $container, $container_list ) )
		{
			$Messages->add( T_('WARNING: you are adding to a container that does not seem to be part of the current skin.'), 'error' );
		}

		switch( $type )
		{
			case 'core':
				// Check the requested core widget is valid:
				load_class( 'widgets/widgets/_'.$code.'.widget.php' );
				$objtype = $code.'_Widget';
				$edited_ComponentWidget = & new $objtype();
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
				$edited_ComponentWidget = & new ComponentWidget( NULL, 'plugin', $code, array() );
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
				send_javascript_message( array( 'addNewWidgetCallback' => array( $edited_ComponentWidget->ID, $container, $edited_ComponentWidget->get( 'order' ), $edited_ComponentWidget->get_name() ) ) ); // will be sent with settings form
				$action = 'edit'; // pulls up the settings form
				break;

			case 'normal' :
			default : // take usual action
				header_redirect( '?ctrl=widgets&action=edit&wi_ID='.$edited_ComponentWidget->ID );
				break;
		}
		break;


	case 'update':
		// Update Settings
		$edited_ComponentWidget->load_from_Request();

		if(	! param_errors_detected() )
		{	// Update settings:
			$edited_ComponentWidget->dbupdate();
			$Messages->add( T_('Widget settings have been updated'), 'success' );
			switch( $display_mode )
			{
				case 'js' : // js reply
					$edited_ComponentWidget->init_display( array() );
					$widget_name =  '<strong>'.$edited_ComponentWidget->disp_params[ 'widget_name' ].'</strong>';
					if( $edited_ComponentWidget->disp_params[ 'widget_name' ] != $edited_ComponentWidget->get_short_desc() )
					{	// The name is customized and the short desc may be relevant additional info
						$widget_name .= ' ('.$edited_ComponentWidget->get_short_desc().')';
					}
					send_javascript_message(array( 'widgetSettingsCallback' => array( $edited_ComponentWidget->ID, $widget_name ), 'closeWidgetSettings' => array() ), true );
					break;
			}
			$action = 'list';
		}
		break;


	case 'move_up':
		// Move the widget up:

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
			$prev_ComponentWidget = & new ComponentWidget( $row );
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
			$next_ComponentWidget = & new ComponentWidget( $row );
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
		break;

	case 'delete':
		// Remove a widget from container:
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
												WHERE wi_ID = '.$widget );
				}
			}
		}

		// Cleanup deleted widgets and empty temp containers (fp> what do you mean 'and empty temp containers' ??? wi_order < 1 should be enough for all scenarios, no?)
		$DB->query( 'DELETE FROM T_widget
									WHERE wi_order < 1
										 OR wi_sco_name LIKE \'temp_%\'' );

		$DB->commit();

 		$Messages->add( T_( 'Widgets updated' ), 'success' );
 		send_javascript_message( array( 'sendWidgetOrderCallback' => array() ) ); // exits() automatically
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
	var T_ = new Array();
	T_["Changes pending"] = "'.T_( 'Changes pending' ).'";
	T_["Saving changes"] = "'.T_( 'Saving changes' ).'";
	T_["Widget order unchanged"] = "'.T_( 'Widget order unchanged' ).'";

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
		echo '<fieldset class="available_widgets">'."\n"; // this will be enabled if js available
		echo '<legend>'.T_( 'Add new widget' ).'</legend>'."\n";
		echo '<div id="available_widgets_inner">'."\n";
		$AdminUI->disp_view( 'widgets/views/_widget_list_available.view.php' );
		echo '</div></fieldset><!-- /available_widgets -->'."\n";

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
 *
 * Revision 1.10  2008/07/04 06:23:54  yabs
 * minor bug fix
 *
 * Revision 1.9  2008/07/03 09:52:51  yabs
 * widget UI
 *
 * Revision 1.8  2008/01/21 09:35:36  fplanque
 * (c) 2008
 *
 * Revision 1.7  2008/01/05 02:28:17  fplanque
 * enhanced blog selector (bloglist_buttons)
 *
 * Revision 1.6  2007/12/23 17:47:59  fplanque
 * fixes
 *
 * Revision 1.5  2007/12/23 14:14:26  fplanque
 * Enhanced widget name display
 *
 * Revision 1.4  2007/12/23 13:01:14  yabs
 * behaviour change - after install display widget settings form
 *
 * Revision 1.3  2007/12/22 19:52:17  yabs
 * cleanup from adding core params
 *
 * Revision 1.2  2007/12/22 16:56:35  yabs
 * adding core parameters for css id/classname and widget list title
 *
 * Revision 1.1  2007/06/25 11:01:54  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.14  2007/06/23 22:05:17  fplanque
 * fixes
 *
 * Revision 1.13  2007/06/22 23:46:43  fplanque
 * bug fixes
 *
 * Revision 1.12  2007/06/19 20:42:53  fplanque
 * basic demo of widget params handled by autoform_*
 *
 * Revision 1.11  2007/06/19 00:03:27  fplanque
 * doc / trying to make sense of automatic settings forms generation.
 *
 * Revision 1.10  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 * Revision 1.9  2007/04/26 00:11:07  fplanque
 * (c) 2007
 *
 * Revision 1.8  2007/03/26 17:12:40  fplanque
 * allow moving of widgets
 *
 * Revision 1.7  2007/01/14 01:32:11  fplanque
 * more widgets supported! :)
 *
 * Revision 1.6  2007/01/13 04:10:44  fplanque
 * implemented "add" support for plugin widgets
 *
 * Revision 1.5  2007/01/12 05:15:07  fplanque
 * minor fix
 *
 * Revision 1.4  2007/01/12 02:40:26  fplanque
 * widget default params proof of concept
 * (param customization to be done)
 *
 * Revision 1.3  2007/01/11 02:57:25  fplanque
 * implemented removing widgets from containers
 *
 * Revision 1.2  2007/01/08 23:45:48  fplanque
 * A little less rough widget manager...
 * (can handle multiple instances of same widget and remembers order)
 *
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>

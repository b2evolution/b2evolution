<?php
/**
 * This file implements the UI controller for managing widgets inside of a blog.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
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

// Memorize this as the last "tab" used in the Blog Settings:
$UserSettings->set( 'pref_coll_settings_tab', 'widgets' );
$UserSettings->dbupdate();

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );


// Check permissions on requested blog and autoselect an appropriate blog if necessary.
// This will prevent a fat error when switching tabs and you have restricted perms on blog properties.
if( $selected = autoselect_blog( 'blog_properties', 'edit' ) ) // Includes perm check
{	// We have a blog to work on:

	if( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
	{	// Selected a new blog:
		$BlogCache = & get_BlogCache();
		/**
		 * @var Blog
		 */
		$Collection = $Blog = & $BlogCache->get_by_ID( $blog );
	}

	/**
	 * @var Blog
	 */
	$edited_Blog = & $Blog;
}
else
{	// We could not find a blog we have edit perms on...
	// Note: we may still have permission to edit categories!!
	// redirect to blog list:
	header_redirect( $admin_url.'?ctrl=dashboard' );
	// EXITED:
	$Messages->add( T_('Sorry, you have no permission to edit blog properties.'), 'error' );
	$action = 'nil';
	$tab = '';
}

$action = param_action( 'list' );
param( 'display_mode', 'string', 'normal' );
$display_mode = ( in_array( $display_mode, array( 'js', 'normal' ) ) ? $display_mode : 'normal' );
if( $display_mode == 'js' )
{	// JavaScript mode:

	// Check that this action request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'widget' );

	// Javascript in debug mode conflicts/fails.
	// fp> TODO: either fix the debug javascript or have an easy way to disable JS in the debug output.
	$debug = 0;
	$debug_jslog = false;
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
	case 'activate':
	case 'deactivate':
		// Do nothing
		break;

	case 'create':
		param( 'type', 'string', true );
		param( 'code', 'string', true );
	case 'new':
		param( 'container', 'string', true, true );	// memorize
		// Change the symbols back to normal view as they are stored in DB
		$container = str_replace( array( '_', '-' ), array( ' ', ':' ), $container );
		break;

	case 're-order' : // js request
		param( 'container_list', 'string', true );
		$containers_list = explode( ',', $container_list );
		$containers = array();
		foreach( $containers_list as $a_container )
		{	// add each container and grab its widgets:
			if( $container_name = trim( str_replace( array( 'container_', '_', '-' ), array( '', ' ', ':' ), $a_container ), ',' ) )
			{
				$containers[ $container_name ] = explode( ',', param( trim( $a_container, ',' ), 'string', true ) );
			}
		}
		break;

	case 'edit':
	case 'update':
	case 'update_edit':
	case 'delete':
	case 'move_up':
	case 'move_down':
	case 'toggle':
	case 'cache_enable':
	case 'cache_disable':
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
		$Collection = $Blog = & $BlogCache->get_by_ID( $blog );

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
		// Initialize JS for color picker field on the edit plugin settings form:
		init_colorpicker_js();
}

// Get Skin used by current Blog:
$blog_normal_skin_ID = $Blog->get_setting( 'normal_skin_ID' );
$SkinCache = & get_SkinCache();
$Skin = & $SkinCache->get_by_ID( $blog_normal_skin_ID );
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
						'<a href="'.regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$edited_ComponentWidget->ID ).'" class="widget_name">'
							.$edited_ComponentWidget->get_desc_for_list()
						.'</a> '.$edited_ComponentWidget->get_help_link(),
						$edited_ComponentWidget->get_cache_status( true ),
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
	case 'update_edit':
		// Update Settings

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$edited_ComponentWidget->load_from_Request();

		if(	! param_errors_detected() )
		{ // Update settings:
			$edited_ComponentWidget->dbupdate();
			$Messages->add( T_('Widget settings have been updated'), 'success' );
			switch( $display_mode )
			{
				case 'js' : // js reply
					$edited_ComponentWidget->init_display( array() );
					$methods = array();
					$methods['widgetSettingsCallback'] = array(
							$edited_ComponentWidget->ID,
							$edited_ComponentWidget->get_desc_for_list(),
							$edited_ComponentWidget->get_cache_status( true )
						);
					if( $action == 'update' )
					{	// Close window after update, and don't close it when user wants continue editing after updating:
						$methods['closeWidgetSettings'] = array( $action );
					}
					else
					{	// Scroll to messages after update:
						$methods['showMessagesWidgetSettings'] = array( 'success' );
					}
					send_javascript_message( $methods, true );
					break;
			}
			if( $action == 'update_edit' )
			{	// Stay on edit widget form:
				header_redirect( $admin_url.'?ctrl=widgets&blog='.$Blog->ID.'&action=edit&wi_ID='.$edited_ComponentWidget->ID, 303 );
			}
			else
			{	// Redirect to widgets list:
				$Session->set( 'fadeout_id', $edited_ComponentWidget->ID );
				header_redirect( $admin_url.'?ctrl=widgets&blog='.$Blog->ID, 303 );
			}
		}
		elseif( $display_mode == 'js' )
		{	// Send errors back as js:
			send_javascript_message( array( 'showMessagesWidgetSettings' => array( 'failed' ) ), true );
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
		header_redirect( $admin_url.'?ctrl=widgets&blog='.$Blog->ID, 303 );
		break;

	case 'cache_enable':
	case 'cache_disable':
		// Enable or disable the block caching for the widget:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		if( $edited_ComponentWidget->get_cache_status() == 'disallowed' )
		{ // Don't allow to change cache status because it is not allowed by widget config
			$Messages->add( T_( 'This widget cannot be cached in the block cache.' ), 'error' );
		}
		else
		{ // Update widget cache status
			$edited_ComponentWidget->set( 'allow_blockcache', $action == 'cache_enable' ? 1 : 0 );
			$edited_ComponentWidget->dbupdate();

			if( $action == 'cache_enable' )
			{
				$Messages->add( T_( 'Block caching has been turned on for this widget.' ), 'success' );
			}
			else
			{
				$Messages->add( T_( 'Block caching has been turned off for this widget.' ), 'success' );
			}
		}

		if ( $display_mode == 'js' )
		{
			// EXITS:
			send_javascript_message( array( 'doToggleCache' => array(
					$edited_ComponentWidget->ID,
					$edited_ComponentWidget->get_cache_status( true ),
				) ) );
		}
		header_redirect( $admin_url.'?ctrl=widgets&blog='.$Blog->ID, 303 );
		break;

	case 'activate':
	case 'deactivate':
		// Enable or disable the widgets:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$widgets = param( 'widgets', 'array:integer' );

		if( count( $widgets ) )
		{ // Enable/Disable the selected widgets
			$updated_widgets = $DB->query( 'UPDATE T_widget
				  SET wi_enabled = '.$DB->quote( $action == 'activate' ? '1' : '0' ).'
				WHERE wi_ID IN ( '.$DB->quote( $widgets ).' )
				  AND wi_coll_ID = '.$DB->quote( $Blog->ID ) );
		}

		if( ! empty( $updated_widgets ) )
		{ // Display a result message only when at least one widget has been updated
			if( $action == 'activate' )
			{
				$Messages->add( sprintf( T_( '%d widgets have been enabled.' ), $updated_widgets ), 'success' );
			}
			else
			{
				$Messages->add( sprintf( T_( '%d widgets have been disabled.' ), $updated_widgets ), 'success' );
			}
		}

		header_redirect( $admin_url.'?ctrl=widgets&blog='.$Blog->ID, 303 );
		break;

	case 'delete':
		// Remove a widget from container:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$msg = sprintf( T_('Widget &laquo;%s&raquo; removed.'), $edited_ComponentWidget->get_name() );
		$edited_widget_ID = $edited_ComponentWidget->ID;
		$edited_ComponentWidget->dbdelete();
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
		$edited_Skin = & $SkinCache->get_by_ID( $blog_normal_skin_ID );

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
	$AdminUI->set_coll_list_params( 'blog_properties', 'edit', array( 'ctrl' => 'widgets' ) );

	$AdminUI->set_path( 'collections', 'widgets' );

	// We should activate toolbar menu items for this controller and mode
	$activate_collection_toolbar = true;

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
	var enabled_icon_tag = \''.get_icon( 'bullet_green', 'imgtag', array( 'title' => T_( 'The widget is enabled.' ) ) ).'\';
	var disabled_icon_tag = \''.get_icon( 'bullet_empty_grey', 'imgtag', array( 'title' => T_( 'The widget is disabled.' ) ) ).'\';
	var activate_icon_tag = \''.get_icon( 'activate', 'imgtag', array( 'title' => T_( 'Enable this widget!' ) ) ).'\';
	var deactivate_icon_tag = \''.get_icon( 'deactivate', 'imgtag', array( 'title' => T_( 'Disable this widget!' ) ) ).'\';
	var cache_enabled_icon_tag = \''.get_icon( 'block_cache_on', 'imgtag', array( 'title' => T_( 'Caching is enabled. Click to disable.' ) ) ).'\';
	var cache_disabled_icon_tag = \''.get_icon( 'block_cache_off', 'imgtag', array( 'title' => T_( 'Caching is disabled. Click to enable.' ) ) ).'\';
	var cache_disallowed_icon_tag = \''.get_icon( 'block_cache_disabled', 'imgtag', array( 'title' => T_( 'This widget cannot be cached.' ) ) ).'\';
	var cache_denied_icon_tag = \''.get_icon( 'block_cache_denied', 'imgtag', array( 'title' => T_( 'This widget could be cached but the block cache is OFF. Click to enable.' ) ) ).'\';

	var b2evo_dispatcher_url = "'.$admin_url.'";' );
	require_js( '#jqueryUI#' ); // auto requires jQuery
	require_css( 'blog_widgets.css' );


	$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=coll_settings&amp;tab=dashboard&amp;blog=$blog$' ) );
	$AdminUI->breadcrumbpath_add( T_('Widgets'), $admin_url.'?ctrl=widgets&amp;blog=$blog$' );

	// Set an url for manual page:
	$AdminUI->set_page_manual_link( 'widget-settings' );

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
	case 'update_edit':
		switch( $display_mode )
		{
			case 'js' : // js request
				ob_start();
				// Display VIEW:
				$AdminUI->disp_view( 'widgets/views/_widget.form.php' );
				$output = ob_get_clean();
				send_javascript_message( array(
						'widgetSettings' => array( $output, $edited_ComponentWidget->get( 'type' ), $edited_ComponentWidget->get( 'code' ) ),
						'evo_initialize_colorpicker_inputs' => array(),
					) );
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
		echo '<div class="available_widgets_toolbar modal-header">'
						.'<a href="#" class="floatright close">'.get_icon('close').'</a>'
						.'<h4 class="modal-title">'.T_( 'Select widget to add:' ).'</h4>'
					.'</div>'."\n";
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

?>

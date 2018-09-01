<?php
/**
 * This file implements the UI view for the widgets installed on a blog.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Collection, $Blog, $Skin, $admin_url;

global $container_Widget_array;

// Load widgets for current collection:
$WidgetCache = & get_WidgetCache();
$container_Widget_array = & $WidgetCache->get_by_coll_ID( $Blog->ID, false, get_param( 'skin_type' ) );

/**
 * @param string Title of the container. This gets passed to T_()!
 * @param boolean Is included in collection skin
 */
function display_container( $WidgetContainer, $is_included = true )
{
	global $Collection, $Blog, $admin_url;
	global $Session;

	$Table = new Table();

	// Table ID - fp> needs to be handled cleanly by Table object
	if( isset( $WidgetContainer->ID ) && ( $WidgetContainer->ID > 0 ) )
	{
		$widget_container_id = 'wico_ID_'.$WidgetContainer->ID;
		$add_widget_url = regenerate_url( '', 'action=new&amp;wico_ID='.$WidgetContainer->ID.'&amp;container='.$widget_container_id );
		$destroy_container_url = url_add_param( $admin_url, 'ctrl=widgets&amp;action=destroy_container&amp;wico_ID='.$WidgetContainer->ID.'&amp;'.url_crumb('widget_container') );
	}
	else
	{
		$wico_code = $WidgetContainer->get( 'code' );
		$widget_container_id = 'wico_code_'.$wico_code;
		$add_widget_url = regenerate_url( '', 'action=new&amp;wico_code='.$wico_code.'&amp;container='.$widget_container_id );
		$destroy_container_url = url_add_param( $admin_url, 'ctrl=widgets&amp;action=destroy_container&amp;wico_code='.$wico_code );
	}

	$widget_container_name = T_( $WidgetContainer->get( 'name' ) );
	if( ! empty( $WidgetContainer->ID ) )
	{
		$widget_container_name = '<a href="'.$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=edit_container&amp;wico_ID='.$WidgetContainer->ID.'">'.$widget_container_name.'</a>';
		// Display additional info for Page Container:
		if( $WidgetContainer->get( 'item_ID' ) > 0 )
		{	// If Page Container depends on Item:
			$widget_container_name .= ' '.sprintf( T_('on Page #%s'), $WidgetContainer->get( 'item_ID' ) );
		}
	}
	$Table->title = '<span class="dimmed">'.$WidgetContainer->get( 'order' ).'</span> '
		.'<span class="container_name" data-wico_id="'.$widget_container_id.'">'.$widget_container_name.'</span> '
		.'<span class="dimmed">'.$WidgetContainer->get( 'code' ).'</span>';

	if( ! $is_included )
	{	// Allow to destroy sub-container when it is not included into the selected skin:
		$Table->global_icon( T_('Destroy sub-container'), 'delete', $destroy_container_url, T_('Destroy sub-container'), 3, 4 );
	}
	$Table->global_icon( T_('Add a widget...'), 'new', $add_widget_url, /* TRANS: ling used to add a new widget */ T_('Add widget').' &raquo;', 3, 4, array( 'id' => 'add_new_'.$widget_container_id, 'class' => 'action_icon btn-primary' ) );

	$Table->display_init( array(
			'list_start' => '<div class="panel panel-default">',
			'list_end'   => '</div>',
		) );

	$Table->display_list_start();

	// TITLE / COLUMN HEADERS:
	$Table->display_head();

	// BODY START:
	echo '<ul id="container_'.$widget_container_id.'" class="widget_container">';

	/**
	 * @var WidgetCache
	 */
	$WidgetCache = & get_WidgetCache();
	$Widget_array = & $WidgetCache->get_by_container_ID( $WidgetContainer->ID );

	if( ! empty( $Widget_array ) )
	{
		$widget_count = 0;
		foreach( $Widget_array as $ComponentWidget )
		{
			$widget_count++;
			$enabled = $ComponentWidget->get( 'enabled' );

			// START Widget row:
			echo '<li id="wi_ID_'.$ComponentWidget->ID.'" class="draggable_widget">';

			// Checkbox:
			echo '<span class="widget_checkbox'.( $enabled ? ' widget_checkbox_enabled' : '' ).'">'
					.'<input type="checkbox" name="widgets[]" value="'.$ComponentWidget->ID.'" />'
				.'</span>';

			// State:
			echo '<span class="widget_state">'
					.'<a href="#" onclick="return toggleWidget( \'wi_ID_'.$ComponentWidget->ID.'\' );">'
						.get_icon( ( $enabled ? 'bullet_green' : 'bullet_empty_grey' ), 'imgtag', array( 'title' => ( $enabled ? T_('The widget is enabled.') : T_('The widget is disabled.') ) ) )
					.'</a>'
				.'</span>';

			// Name:
			$ComponentWidget->init_display( array() );
			echo '<span class="widget_title">'
					.'<a href="'.regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID ).'" class="widget_name" onclick="return editWidget( \'wi_ID_'.$ComponentWidget->ID.'\' )">'
						.$ComponentWidget->get_desc_for_list()
					.'</a> '
					.$ComponentWidget->get_help_link()
				.'</span>';

			// Cache:
			echo'<span class="widget_cache_status">';
			$widget_cache_status = $ComponentWidget->get_cache_status( true );
			switch( $widget_cache_status )
			{
				case 'disallowed':
					echo get_icon( 'block_cache_disabled', 'imgtag', array( 'title' => T_( 'This widget cannot be cached.' ), 'rel' => $widget_cache_status ) );
					break;

				case 'denied':
					echo action_icon( T_( 'This widget could be cached but the block cache is OFF. Click to enable.' ),
						'block_cache_denied',
						$admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$Blog->ID.'#fieldset_wrapper_caching', NULL, NULL, NULL,
						array( 'rel' => $widget_cache_status ) );
					break;

				case 'enabled':
					echo action_icon( T_( 'Caching is enabled. Click to disable.' ),
						'block_cache_on',
						regenerate_url( 'blog', 'action=cache_disable&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb( 'widget' ) ), NULL, NULL, NULL,
						array(
								'rel'     => $widget_cache_status,
								'onclick' => 'return toggleCacheWidget( \'wi_ID_'.$ComponentWidget->ID.'\', \'disable\' )',
							) );
					break;

				case 'disabled':
					echo action_icon( T_( 'Caching is disabled. Click to enable.' ),
						'block_cache_off',
						regenerate_url( 'blog', 'action=cache_enable&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb( 'widget' ) ), NULL, NULL, NULL,
						array(
								'rel'     => $widget_cache_status,
								'onclick' => 'return toggleCacheWidget( \'wi_ID_'.$ComponentWidget->ID.'\', \'enable\' )',
							) );
					break;
			}
			echo '</span>';

			// Actions:
			echo '<span class="widget_actions">'
					// Enable/Disable:
					.action_icon( ( $enabled ? T_('Disable this widget!') : T_('Enable this widget!') ),
							( $enabled ? 'deactivate' : 'activate' ),
							regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ), NULL, NULL, NULL,
							array( 'onclick' => 'return toggleWidget( \'wi_ID_'.$ComponentWidget->ID.'\' )', 'class' => 'toggle_action' )
						)
					// Edit:
					.action_icon( T_('Edit widget settings!'),
							'edit',
							regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID ), NULL, NULL, NULL,
							array( 'onclick' => 'return editWidget( \'wi_ID_'.$ComponentWidget->ID.'\' )', 'class' => '' )
						)
					// Remove:
					.action_icon( T_('Remove this widget!'),
							'delete',
							regenerate_url( 'blog', 'action=delete&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb( 'widget' ) ), NULL, NULL, NULL,
							array( 'onclick' => 'return deleteWidget( \'wi_ID_'.$ComponentWidget->ID.'\' )', 'class' => '' )
						)
				.'</span>';

			// END Widget row:
			echo '</li>';
		}
	}

	// BODY END:
	echo '</ul>';

	$Table->display_list_end();
}


/**
 * Display containers
 *
 * @param string Skin type: 'normal', 'mobile', 'tablet'
 * @param boolean TRUE to display main containers, FALSE - sub containers
 * @param boolean TRUE to display collection containers, FALSE - shared containers
 * @param boolean TRUE to display page containers, FALSE - for all others
 */
function display_containers( $skin_type, $main = true, $shared = false, $paged = false )
{
	global $Blog, $DB, $blog_container_list;

	$WidgetContainerCache = & get_WidgetContainerCache();

	if( $main )
	{	// Display MAIN containers:
		if( $shared )
		{	// Get shared containers:
			$WidgetContainerCache->clear();
			$WidgetContainerCache->load_where( 'wico_main = 1
				AND wico_coll_ID IS NULL
				AND wico_skin_type = '.$DB->quote( $skin_type ) );
			$main_containers = $WidgetContainerCache->cache;
		}
		else
		{	// Get collection/skin containers:
			$main_containers = array();
			$coll_containers = $Blog->get_main_containers();
			foreach( $coll_containers as $container_code => $container_data )
			{
				$WidgetContainer = & $WidgetContainerCache->get_by_coll_and_code( $Blog->ID, $container_code );
				if( ! $WidgetContainer )
				{	// If widget container doesn't exist in DB but it is detected in skin file:
					$WidgetContainer = new WidgetContainer();
					$WidgetContainer->set( 'code', $container_code );
					$WidgetContainer->set( 'name', $container_data[0] );
					$WidgetContainer->set( 'coll_ID', $Blog->ID );
					$WidgetContainer->set( 'skin_type', $skin_type );
				}
				if( $WidgetContainer->get( 'skin_type' ) != $skin_type )
				{	// Skip this container because another type is requested:
					continue;
				}
				$main_containers[] = $WidgetContainer;
			}
		}
		foreach( $main_containers as $WidgetContainer )
		{
			display_container( $WidgetContainer );
		}
	}
	else
	{	// Display SUB containers:
		if( $paged )
		{	// Set SQL condition to select all page containers:
			$pages_where_sql = 'wico_item_ID IS NOT NULL';
		}
		else
		{	// Set SQL condition to select not page containers:
			$pages_where_sql = 'wico_item_ID IS NULL';
		}
		$WidgetContainerCache->clear();
		$WidgetContainerCache->load_where( 'wico_main = 0
			AND wico_coll_ID '.( $shared ? 'IS NULL' : ' = '.$Blog->ID ).'
			AND wico_skin_type = '.$DB->quote( $skin_type ).'
			AND ( '.$pages_where_sql.' )' );

		foreach( $WidgetContainerCache->cache as $WidgetContainer )
		{
			display_container( $WidgetContainer, false );
		}
	}
}

$Form = new Form( $admin_url.'?ctrl=widgets&blog='.$Blog->ID );

$Form->add_crumb( 'widget' );

$Form->begin_form();

// fp> what browser do we need a fielset for?
echo '<fieldset id="current_widgets">'."\n"; // fieldsets are cool at remembering their width ;)

echo '<div class="row">';

// Skin Containers:
echo '<div class="col-md-4 col-sm-12">';
	echo '<h4 class="pull-left">'.T_('Skin Containers').'</h4>';
	if( $current_User->check_perm( 'options', 'edit', false ) )
	{	// Display a button to scan skin for widgets if current User has a permission:
		echo action_icon( T_('Scan skin'), 'reload',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=reload&amp;'.url_crumb('widget'), T_('Scan skin'), 3, 4, array( 'class' => 'action_icon hoverlink btn btn-info pull-right' ) );
	}
	echo '<div class="clearfix"></div>';
	display_containers( get_param( 'skin_type' ), true, false );
echo '</div>';

// Sub-Containers & Page Containers:
echo '<div class="col-md-4 col-sm-12">';
	// Sub-Containers:
	echo '<h4 class="pull-left">'.T_('Sub-Containers').'</h4>';
	if( $current_User->check_perm( 'options', 'edit', false ) )
	{	// Display a button to add new sub-container if current User has a permission:
		echo action_icon( T_('Add container'), 'add',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=new_container&amp;container_type=sub&amp;skin_type='.get_param( 'skin_type' ), T_('Add container').' &raquo;', 3, 4, array( 'class' => 'action_icon hoverlink btn btn-default pull-right' ) );
	}
	echo '<div class="clearfix"></div>';
	display_containers( get_param( 'skin_type' ), false, false );

	// Page Containers:
	echo '<h4 class="pull-left">'.T_('Page Containers').'</h4>';
	if( $current_User->check_perm( 'options', 'edit', false ) )
	{	// Display a button to add new sub-container if current User has a permission:
		echo action_icon( T_('Add container'), 'add',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=new_container&amp;container_type=page&amp;skin_type='.get_param( 'skin_type' ), T_('Add container').' &raquo;', 3, 4, array( 'class' => 'action_icon hoverlink btn btn-default pull-right' ) );
	}
	echo '<div class="clearfix"></div>';
	display_containers( get_param( 'skin_type' ), false, false, true );
echo '</div>';

// Shared Containers:
echo '<div class="col-md-4 col-sm-12">';
	echo '<h4 class="pull-left">'.T_('Shared Containers').'</h4>';
	if( $current_User->check_perm( 'options', 'edit', false ) )
	{	// Display a button to add new sub-container if current User has a permission:
		echo action_icon( T_('Add container'), 'add',
			$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=new_container&amp;container_type=shared&amp;skin_type='.get_param( 'skin_type' ), T_('Add container').' &raquo;', 3, 4, array( 'class' => 'action_icon hoverlink btn btn-default pull-right' ) );
	}
	echo '<div class="clearfix"></div>';
	display_containers( get_param( 'skin_type' ), true, true );
	display_containers( get_param( 'skin_type' ), false, true );
echo '</div>';

echo '</div>';

echo '</fieldset>'."\n";

echo '<span class="btn-group">';
$Form->button( array(
		'value' => get_icon( 'check_all' ).' '.T_('Check All'),
		'id'    => 'widget_button_check_all',
		'tag'   => 'button',
		'type'  => 'button'
	) );
$Form->button( array(
		'value' => get_icon( 'uncheck_all' ).' '.T_('Uncheck All'),
		'id'    => 'widget_button_uncheck_all',
		'tag'   => 'button',
		'type'  => 'button'
	) );
echo '</span>';

echo '<span class="btn-group">';
$Form->button( array(
		'value' => get_icon( 'check_all' ).' '.get_icon( 'bullet_green' ).' '.T_('Check Active'),
		'id'    => 'widget_button_check_active',
		'tag'   => 'button',
		'type'  => 'button'
	) );
$Form->button( array(
		'value' => get_icon( 'check_all' ).' '.get_icon( 'bullet_empty_grey' ).' '.T_('Check Inactive'),
		'id'    => 'widget_button_check_inactive',
		'tag'   => 'button',
		'type'  => 'button'
	) );
echo '</span>';

echo ' '.T_('With checked do:');
echo '<span class="btn-group">';
$Form->button( array(
		'value' => get_icon( 'bullet_green' ).' '.T_('Activate'),
		'name'  => 'actionArray[activate]',
		'tag'   => 'button',
		'type'  => 'submit'
	) );
$Form->button( array(
		'value' => get_icon( 'bullet_empty_grey' ).' '.T_('Deactivate'),
		'name'  => 'actionArray[deactivate]',
		'tag'   => 'button',
		'type'  => 'submit'
	) );
echo '</span>';

$Form->end_form();

echo get_icon( 'pixel', 'imgtag', array( 'class' => 'clear' ) );

?>
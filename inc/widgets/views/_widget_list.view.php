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

global $Blog, $Skin, $admin_url;

global $container_Widget_array;

global $blog_container_list, $skins_container_list, $embedded_containers;

if( $current_User->check_perm( 'options', 'edit', false ) )
{
	echo '<div class="pull-right" style="margin-bottom:10px">';
		echo action_icon( TS_('Add a new container!'), 'add',
					'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=new_container', T_('Add container').' &raquo;', 3, 4, array( 'class' => 'action_icon hoverlink btn btn-default' ) );
		echo action_icon( TS_('Recheck declared skin containers!'), 'reload',
					'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=reload&amp;'.url_crumb('widget'), T_('Recheck skin containers'), 3, 4, array( 'class' => 'action_icon hoverlink btn btn-info' ) );
	echo '</div>';
}

// Load widgets for current collection:
$WidgetCache = & get_WidgetCache();
$container_Widget_array = & $WidgetCache->get_by_coll_ID( $Blog->ID );

/**
 * @param string Title of the container. This gets passed to T_()!
 * @param string Suffix of legend
 */
function display_container( $WidgetContainer, $legend_suffix = '' )
{
	global $Blog, $admin_url, $embedded_containers;
	global $Session;

	$Table = new Table();

	// Table ID - fp> needs to be handled cleanly by Table object
	if( isset( $WidgetContainer->ID ) && ( $WidgetContainer->ID > 0 ) )
	{
		$table_id = 'wico_ID_'.$WidgetContainer->ID;
		$add_widget_url = regenerate_url( '', 'action=new&amp;wico_ID='.$WidgetContainer->ID.'&amp;container='.$table_id );
		$destroy_container_url = url_add_param( $admin_url, 'ctrl=widgets&amp;action=destroy_container&amp;wico_ID='.$WidgetContainer->ID.'&amp;'.url_crumb('widget_container') );
	}
	else
	{
		$wico_code = $WidgetContainer->get( 'code' );
		$table_id = 'wico_code_'.$wico_code;
		$add_widget_url = regenerate_url( '', 'action=new&amp;wico_code='.$wico_code.'&amp;container='.$table_id );
		$destroy_container_url = url_add_param( $admin_url, 'ctrl=widgets&amp;action=destroy_container&amp;wico_code='.$wico_code );
	}

	$widget_container_name = T_( $WidgetContainer->get( 'name' ) );
	if( ! empty( $WidgetContainer->ID ) )
	{
		$widget_container_name = '<a href="'.$admin_url.'?ctrl=widgets&amp;blog='.$Blog->ID.'&amp;action=edit_container&amp;wico_ID='.$WidgetContainer->ID.'">'.$widget_container_name.'</a>';
	}
	$Table->title = '<span class="container_name" data-wico_id="'.$table_id.'">'.$widget_container_name.'</span>'
		.' <span class="dimmed">'.$WidgetContainer->get( 'code' ).' '.$WidgetContainer->get( 'order' ).'</span>'
		.$legend_suffix;

	if( ! empty( $legend_suffix ) )
	{ // Legend suffix is not empty when the container is not included into the selected skin
		// TODO: asimo> Implement cleaner condition for this
		$Table->global_icon( T_('Destroy container'), 'delete', $destroy_container_url, T_('Destroy container'), 3, 4 );
	}
	$Table->global_icon( T_('Add a widget...'), 'new', $add_widget_url, /* TRANS: ling used to add a new widget */ T_('Add widget').' &raquo;', 3, 4, array( 'id' => 'add_new_'.$table_id, 'class' => 'action_icon btn-primary' ) );

	$Table->cols = array(
			array(
				'th' => '', // checkbox
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap' ),
			array(
				'th' => /* TRANS: shortcut for enabled */ T_( 'En' ),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap' ),
			array( 'th' => T_('Widget') ),
			array( 'th' => T_('Type') ),
			array(
				'th' => T_('Move'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap' ),
			array(
				'th' => T_('Cache'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap widget_cache_status' ),
			array(
				'th' => T_('Actions'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap' ),
		);
	//enable fadeouts here
	$Table->display_init( array(
				'list_attrib' => 'id="'.$table_id.'"',
				'list_class'  => 'widget_container_list'
			),
			array( 'fadeouts' => true )
		);

	/*
	if( $legend_suffix )
	{	// add jQuery no-drop -- fp> what do we need this one for?
		$Table->params['head_title'] = str_replace( 'class="grouped"', 'class="grouped no-drop"', $Table->params['head_title'] );
	}
	*/

	// Dirty hack for bootstrap skin
	$Table->params['list_start'] = str_replace( '<div class="', '<div class="panel panel-default ', $Table->params['list_start'] );

	$Table->display_list_start();

	// TITLE / COLUMN HEADERS:
	$Table->display_head();

	// BODY START:
	$Table->display_body_start();

	/**
	 * @var WidgetCache
	 */
	$WidgetCache = & get_WidgetCache();
	$Widget_array = & $WidgetCache->get_by_container_ID( $WidgetContainer->ID );

	if( empty($Widget_array) )
	{	// TODO: cleanup
		$Table->display_line_start( true );
		$Table->display_col_start( array( 'colspan' => 6 ) );
		echo '<span class="new_widget">'.T_('There is no widget in this container yet.').'</span>';
		$Table->display_col_end();
		$Table->display_line_end();
	}
	else
	{
		$widget_count = 0;
		foreach( $Widget_array as $ComponentWidget )
		{
			$widget_count++;
			$enabled = $ComponentWidget->get( 'enabled' );

			$fadeout_id = $Session->get( 'fadeout_id' );
			if( isset($fadeout_id) && $ComponentWidget->ID == $fadeout_id )
			{
				$fadeout = true;
				$Session->delete( 'fadeout_id' );
			}
			else
			{
				$fadeout = false;
			}

			if( $ComponentWidget->get( 'code' ) == 'subcontainer' )
			{
				$container_code = $ComponentWidget->get_param( 'container' );
				if( ! isset( $embedded_containers[$container_code] ) ) {
					$embedded_containers[$container_code] = true;
				}
			}

			$Table->display_line_start( false, $fadeout );

			$Table->display_col_start();
			echo '<input type="checkbox" name="widgets[]" value="'.$ComponentWidget->ID.'" />';
			$Table->display_col_end();

			$Table->display_col_start();
			if ( $enabled )
			{
				// Indicator for the JS UI:
				echo '<span class="widget_is_enabled">';
				echo action_icon( T_( 'The widget is enabled.' ), 'bullet_green', regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
				echo '</span>';
			}
			else
			{
				echo action_icon( T_( 'The widget is disabled.' ), 'bullet_empty_grey', regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			$Table->display_col_end();

			$Table->display_col_start();
			$ComponentWidget->init_display( array() );
			echo '<a href="'.regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID ).'" class="widget_name">'
						.$ComponentWidget->get_desc_for_list().'</a> '
						.$ComponentWidget->get_help_link();
			$Table->display_col_end();

			// Note: this is totally useless, but we need more cols for the screen to feel "right":
			$Table->display_col_start();
			echo $ComponentWidget->type;
			$Table->display_col_end();

			// Move
			$Table->display_col_start();
			//echo $ComponentWidget->order.' ';
			if( $widget_count > 1 )
			{
				echo action_icon( T_('Move up!'), 'move_up', regenerate_url( 'blog', 'action=move_up&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			else
			{
				echo get_icon( 'nomove', 'imgtag', array( 'class'=>'action_icon' ) );
			}
			if( $widget_count < count($Widget_array) )
			{
				echo action_icon( T_('Move down!'), 'move_down', regenerate_url( 'blog', 'action=move_down&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			else
			{
				echo get_icon( 'nomove', 'imgtag', array( 'class'=>'action_icon' ) );
			}
			$Table->display_col_end();

			// Cache
			$Table->display_col_start();
			$widget_cache_status = $ComponentWidget->get_cache_status( true );
			switch( $widget_cache_status )
			{
				case 'disallowed':
					echo get_icon( 'block_cache_disabled', 'imgtag', array( 'title' => T_( 'This widget cannot be cached.' ), 'rel' => $widget_cache_status ) );
					break;

				case 'denied':
					echo action_icon( T_( 'This widget could be cached but the block cache is OFF. Click to enable.' ), 'block_cache_denied', $admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$Blog->ID.'#fieldset_wrapper_caching', NULL, NULL, NULL, array( 'rel' => $widget_cache_status ) );
					break;

				case 'enabled':
					echo action_icon( T_( 'Caching is enabled. Click to disable.' ), 'block_cache_on', regenerate_url( 'blog', 'action=cache_disable&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb( 'widget' ) ), NULL, NULL, NULL, array( 'rel' => $widget_cache_status ) );
					break;

				case 'disabled':
					echo action_icon( T_( 'Caching is disabled. Click to enable.' ), 'block_cache_off', regenerate_url( 'blog', 'action=cache_enable&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb( 'widget' ) ), NULL, NULL, NULL, array( 'rel' => $widget_cache_status ) );
					break;
			}
			$Table->display_col_end();

			// Actions
			$Table->display_col_start();
			if ( $enabled )
			{
				echo action_icon( T_( 'Disable this widget!' ), 'deactivate', regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			else
			{
				echo action_icon( T_( 'Enable this widget!' ), 'activate', regenerate_url( 'blog', 'action=toggle&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) );
			}
			echo '<span class="edit_icon_hook">'.action_icon( T_('Edit widget settings!'), 'edit', regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$ComponentWidget->ID ) ).'</span>';
			echo '<span class="delete_icon_hook">'.action_icon( T_('Remove this widget!'), 'delete', regenerate_url( 'blog', 'action=delete&amp;wi_ID='.$ComponentWidget->ID.'&amp;'.url_crumb('widget') ) ).'</span>';
			$Table->display_col_end();

			$Table->display_line_end();
		}
	}

	// BODY END:
	$Table->display_body_end();

	$Table->display_list_end();
}

$Form = new Form( $admin_url.'?ctrl=widgets&blog='.$Blog->ID );

$Form->add_crumb( 'widget' );

$Form->begin_form();

// fp> what browser do we need a fielset for?
echo '<fieldset id="current_widgets">'."\n"; // fieldsets are cool at remembering their width ;)

// Display containers for current skin:
$displayed_containers = array();
$embedded_containers = array();
$WidgetContainerCache = & get_WidgetContainerCache();
foreach( $skins_container_list as $container_code => $container_name )
{
	$WidgetContainer = & $WidgetContainerCache->get_by_coll_and_code( $Blog->ID, $container_code );
	if( ! $WidgetContainer )
	{
		$WidgetContainer = new WidgetContainer();
		$WidgetContainer->set( 'code', $container_code );
		$WidgetContainer->set( 'name', $container_name );
		$WidgetContainer->set( 'coll_ID', $Blog->ID );
	}

	display_container( $WidgetContainer );
	if( $WidgetContainer->ID > 0 )
	{ // Container exists in the database
		$displayed_containers[$container_code] = $WidgetContainer->ID;
	}
}

// Display embedded containers
reset( $embedded_containers );
while( count( $embedded_containers ) > 0 )
{
	// Get the first item key, and remove the first item from the array
	$container_code = key( $embedded_containers );
	array_shift( $embedded_containers );
	if( isset( $displayed_containers[$container_code] ) )
	{ // This container was already displayed
		continue;
	}

	$WidgetContainer = & $WidgetContainerCache->get_by_coll_and_code( $Blog->ID, $container_code );
	if( $WidgetContainer )
	{ // Confirmed that it is part of the blog's containers in the database
		display_container( $WidgetContainer );
		$displayed_containers[$container_code] = $WidgetContainer->ID;
	}
}

// Display other blog containers which are not in the current skin
foreach( $blog_container_list as $container_ID )
{
	if( in_array( $container_ID, $displayed_containers ) )
	{
		continue;
	}

	$WidgetContainer = & $WidgetContainerCache->get_by_ID( $container_ID );
	display_container( $WidgetContainer, ' '.T_('[NOT USED IN CURRENT SKINS!]') );
}

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
		'value' => get_icon( 'bullet_empty_grey' ).' '.T_('De-activate'),
		'name'  => 'actionArray[deactivate]',
		'tag'   => 'button',
		'type'  => 'submit'
	) );
echo '</span>';

$Form->end_form();

echo get_icon( 'pixel', 'imgtag', array( 'class' => 'clear' ) );

?>
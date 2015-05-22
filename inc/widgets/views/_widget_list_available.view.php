<?php
/**
 * This file implements the UI view for the Available widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $container;

echo '<h2><span class="right_icons">'.action_icon( T_('Cancel!'), 'close', regenerate_url( 'container' ) ).'</span>'
	.sprintf(T_('Widgets available for insertion into &laquo;%s&raquo;'), $container ).'</h2>';


/**
 * @var Plugins
 */
global $Plugins, $Debuglog;

$Plugin_array = $Plugins->get_list_by_event( 'SkinTag' );
$Plugin_array_grouped = array();
// Remove the plugins, which have no code, because this gets used to install them:
foreach( $Plugin_array as $k => $Plugin )
{
	if( empty( $Plugin->code ) )
	{
		$Debuglog->add( sprintf( 'Removing plugin %s (#%d) from list of widgets, because of empty code.', $v->classname, $v->ID ), 'plugins' );
		unset( $Plugin_array[ $k ] );
	}
	else
	{
		$plugin_group = empty( $Plugin->subgroup ) ? 'other' : $Plugin->subgroup;
		if( ! isset( $Plugin_array_grouped[ $plugin_group ] ) )
		{
			$Plugin_array_grouped[ $plugin_group ] = array();
		}
		$Plugin_array_grouped[ $plugin_group ][] = $Plugin;
	}
}

if( isset( $Plugin_array_grouped['other'] ) )
{ // Move "other" group at the end
	$plugins_other_group = $Plugin_array_grouped['other'];
	unset( $Plugin_array_grouped['other'] );
	$Plugin_array_grouped['other'] = $plugins_other_group;
	unset( $plugins_other_group );
}
unset( $Plugin_array );


// NOTE: Text "*multipurpose*" is used to move the plugins in the widget groups by Plugin->subgroup
$core_componentwidget_defs = array(
		'*multipurpose*'.T_('Multi-Purpose Widgets'),
			'coll_logo',
			'coll_avatar',
			'free_html',
			'user_links',
		'*menu*'.T_('Menu Item Widgets'),
			'menu_link',
			'msg_menu_link',
			'profile_menu_link',
		'*navigation*'.T_('Navigation Widgets'),
			'coll_search_form',
			'coll_category_list',
			'content_hierarchy',
			'coll_tag_cloud',
			'breadcrumb_path',
			'coll_common_links',
		'*content*'.T_('Content Listing Widgets'),
			'coll_post_list',         // Simple Post list
			'coll_page_list',         // Simple Page list
			'coll_link_list',         // Simple Sidebar Links list
			'coll_related_post_list', // Simple Related Posts list
			'linkblog',               // Simple Linkblog Links list
			'coll_item_list',         // Universal Item list
			'coll_featured_intro',    // Featured/Intro Post 
			'coll_media_index',       // Photo index
			'coll_comment_list',      // Comment list
		'*collection*'.T_('Collection Support Widgets'),
			'coll_title',
			'coll_tagline',
			'coll_longdesc',
			'coll_current_filters',
			'coll_xml_feeds',
		'*site*'.T_('Site Support Widgets'),
			'colls_list_public',
			'colls_list_owner',
			'user_avatars',
		'*user*'.T_('User Support Widgets'),
			'user_login',
			'user_register',
			'user_tools',
		'*other*'.T_('Other'),
			'org_members',
			'online_users',
			'member_count',
			'mobile_skin_switcher',
	);
$i = 0;
$prev_group_code = '';
foreach( $core_componentwidget_defs as $code )
{
	$i++;
	if( $code[0] == '*' )
	{ // group
		preg_match( '/\^*([a-z0-9\-_]+)\*(.+)$/i', $code, $code_match );
		if( isset( $group_code ) && $group_code != $code_match[1] )
		{ // New group is starting
			$prev_group_code = $group_code;
		}
		$group_code = $code_match[1];
		$group_name = $code_match[2];

		// Plugin widgets:
		if( $prev_group_code != '' && isset( $Plugin_array_grouped[ $prev_group_code ] ) )
		{ // Print out all plugins of previous group at the end of group after all system widget
			foreach( $Plugin_array_grouped[ $prev_group_code ] as $ID => $Plugin )
			{
				echo '<li>';
				echo '<a href="'.regenerate_url( '', 'action=create&amp;type=plugin&amp;code='.$Plugin->code.'&amp;'.url_crumb( 'widget' ) ).'" title="'.T_('Add this widget to the container').'">';
				echo get_icon( 'puzzle' ).' <strong>'.$Plugin->name.'</strong>';
				echo '</a> <span class="notes">'.$Plugin->short_desc.'</span> '.$Plugin->get_help_link( '$widget_url', 'manual', false );
				echo '</li>';
			}
			unset( $Plugin_array_grouped[ $prev_group_code ] );
		}

		if( $i > 1 )
		{ // End of previous group
			echo '</ul>';
		}

		// Group title:
		echo '<h3>'.$group_name.':</h3><ul class="widget_list">';
	}
	else
	{
		$classname = $code.'_Widget';
		load_class( 'widgets/widgets/_'.$code.'.widget.php', $classname);

		$ComponentWidget = new $classname( NULL, 'core', $code );

		// System widget:
		echo '<li>';
		echo '<a href="'.regenerate_url( '', 'action=create&amp;type=core&amp;code='.$ComponentWidget->code.'&amp;'.url_crumb('widget') ).'" title="'.T_('Add this widget to the container').'">';
		echo get_icon( 'new' ).' <strong>'.$ComponentWidget->get_name().'</strong>';
		echo '</a> <span class="notes">'.$ComponentWidget->get_desc().'</span> '.$ComponentWidget->get_help_link( 'manual', false );
		echo '</li>';
	}
}

if( ! empty( $Plugin_array_grouped ) )
{ // Put all ungrouped plugins to the last group "Other"
	foreach( $Plugin_array_grouped as $plugin_group => $Plugin_array )
	{
		foreach( $Plugin_array as $ID => $Plugin )
		{
			echo '<li>';
			echo '<a href="'.regenerate_url( '', 'action=create&amp;type=plugin&amp;code='.$Plugin->code.'&amp;'.url_crumb( 'widget' ) ).'" title="'.T_('Add this widget to the container').'">';
			echo get_icon( 'puzzle' ).' <strong>'.$Plugin->name.'</strong>';
			echo '</a> <span class="notes">'.$Plugin->short_desc.'</span> '.$Plugin->get_help_link( '$widget_url', 'manual', false );
			echo '</li>';
		}
	}
}

echo '</ul>';
?>
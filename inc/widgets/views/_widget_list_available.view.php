<?php
/**
 * This file implements the UI view for the Available widgets.
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
unset( $Plugin_array );

$widget_groups = array (
	'multipurpose' => T_('Multi-Purpose Widgets'),
	'menu_item'    => T_('Menu Item Widgets'),
	'navigation'   => T_('Navigation Widgets'),
	'content'      => T_('Content Listing Widgets'),
	'infoitem'     => T_('Info about a specific Item'),
	'collection'   => T_('Collection Support Widgets'),
	'site'         => T_('Site Support Widgets'),
	'user'         => T_('User Support Widgets'),
	'other'        => T_('Other'),
);

$core_componentwidget_defs = array(
	'multipurpose' => array(
			'image',
			'coll_avatar',
			'free_html',
			'user_links',
			'social_links'
		),
	'menu_item' => array(
			'menu_link',
			'msg_menu_link',
			'flag_menu_link',
			'profile_menu_link',
		),
	'navigation' => array(
			'coll_search_form',
			'coll_category_list',
			'content_hierarchy',
			'coll_tag_cloud',
			'breadcrumb_path',
			'coll_common_links',
			'coll_current_filters',
		),
	'content' => array(
			'coll_featured_posts',    // Simplified UIL: Featured Posts
			'coll_post_list',         // Simple Post list
			'coll_page_list',         // Simple Page list
			'coll_related_post_list', // Simple Related Posts list
			'coll_flagged_list',      // Simplified UIL: Flagged Items
			'coll_item_list',         // Universal Item list
			'coll_featured_intro',    // Featured/Intro Post
			'coll_media_index',       // Photo index
			'coll_comment_list',      // Comment list
		),
	'infoitem' => array(
			'item_info_line',
			'item_content',
			'item_attachments',
			'item_location',
			'item_small_print',
			'item_tags',
			'item_about_author',
			'item_seen_by',
			'item_vote',
		),
	'collection' => array(
			'coll_logo',
			'coll_title',
			'coll_tagline',
			'coll_longdesc',
			'coll_member_count',
			'coll_xml_feeds',
			'coll_subscription',
		),
	'site' => array(
			'colls_list_public',
			'colls_list_owner',
			'user_avatars',
		),
	'user' => array(
			'user_login',
			'user_register',
			'user_tools',
		),
	'other' => array(
			'org_members',
			'online_users',
			'mobile_skin_switcher',
			'poll',
			'page_404_not_found',
		),
);


foreach( $widget_groups as $widget_group_code => $widget_group_title )
{
	// Group title:
	echo '<h3>'.$widget_group_title.':</h3>';

	if( ! isset( $core_componentwidget_defs[ $widget_group_code ] ) )
	{ // No widgets for this group
		continue;
	}

	echo '<ul class="widget_list">';

	// Core widgets:
	if( isset( $core_componentwidget_defs[ $widget_group_code ] ) )
	{
		foreach( $core_componentwidget_defs[ $widget_group_code ] as $widget_code )
		{
			$classname = $widget_code.'_Widget';
			load_class( 'widgets/widgets/_'.$widget_code.'.widget.php', $classname);

			$ComponentWidget = new $classname( NULL, 'core', $widget_code );

			echo '<li>';
			echo '<a href="'.regenerate_url( '', 'action=create&amp;type=core&amp;code='.$ComponentWidget->code.'&amp;'.url_crumb( 'widget' ) ).'" title="'.T_('Add this widget to the container').'">';
			echo get_icon( 'new' ).' <strong>'.$ComponentWidget->get_name().'</strong>';
			echo '</a> <span class="notes">'.$ComponentWidget->get_desc().'</span> '.$ComponentWidget->get_help_link( 'manual', false );
			echo '</li>';
		}
	}

	// Plugin widgets:
	if( isset( $Plugin_array_grouped[ $widget_group_code ] ) )
	{
		foreach( $Plugin_array_grouped[ $widget_group_code ] as $Plugin )
		{
			echo '<li>';
			echo '<a href="'.regenerate_url( '', 'action=create&amp;type=plugin&amp;code='.$Plugin->code.'&amp;'.url_crumb( 'widget' ) ).'" title="'.T_('Add this widget to the container').'">';
			echo get_icon( 'puzzle' ).' <strong>'.$Plugin->name.'</strong>';
			echo '</a> <span class="notes">'.$Plugin->short_desc.'</span> '.$Plugin->get_help_link( '$widget_url', 'manual', false );
			echo '</li>';
		}
	}

	echo '</ul>';
}
?>
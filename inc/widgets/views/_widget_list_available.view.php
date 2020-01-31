<?php
/**
 * This file implements the UI view for the Available widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $AdminUI, $WidgetContainer, $container, $mode;

if( $mode == 'customizer' )
{	// Display customizer tabs to switch between skin and widgets in special div on customizer mode:
	$AdminUI->display_customizer_tabs( array(
			'path' => array( 'coll', 'widgets' ),
		) );

	// Start of customizer content:
	echo '<div class="evo_customizer__content evo_customizer__available_widgets">';

	// Display page title:
	echo '<p class="alert alert-info" style="margin:10px">'.sprintf( T_('Choose a widget to add to "%s":'), $WidgetContainer->get( 'name' ) ).'</p>';
}
else
{	// Display this title for normal view from back-office:
	echo '<h2><span class="right_icons">'.action_icon( T_('Cancel').'!', 'close', regenerate_url( 'container' ) ).'</span>'
		.sprintf(T_('Widgets available for insertion into &laquo;%s&raquo;'), $container ).'</h2>';
}


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
	'free_content' => T_('Basic blocks'),
	'embed_blocks' => T_('Embedded contents'),
	'menu_item'    => T_('Menu Items / Buttons'),
	'navigation'   => T_('Navigation'),
	'content'      => T_('Listing Contents'),
	'disp_content' => T_('Displaying Contents'),
	'infoitem'     => T_('Item Details'),
	'collection'   => T_('Collection Details'),
	'about_user'   => T_('User Details'),
	'user'         => T_('User Related'),
	'other'        => T_('Other'),
);

$core_componentwidget_defs = array(
	'free_content' => array(
			'free_text',    // Text
			'free_html',    // HTML
			'image',        // Image
			'spacer',       // Spacer
			'separator',    // Separator
		),
	'embed_blocks' => array(
			'subcontainer',        // Sub-Container
			'subcontainer_row',    // Columns (Sub-Containers)
			'embed_menu',        // Embed Menu
			'content_block',       // Content Block
			'coll_featured_intro', // Featured/Intro Post
			'display_item',        // Specific Item
			'inc_file',            // .inc file
			'poll',                // Poll
		),
	'menu_item' => array(
			'basic_menu_link',   // Menu link or button
			'msg_menu_link',     // Messaging Menu link or button
			'flag_menu_link',    // Flagged Items Menu link or button
			'profile_menu_link', // My Profile Menu link or button
			'embed_menu',        // Embed Menu
			'colls_list_public', // Collections list
			'colls_list_owner',  // Same owner's collections list
			'coll_common_links', // Common Navigation Links
			'user_tools',        // User Tools
		),
	'navigation' => array(
			'breadcrumb_path',              // Breadcrumb Path
			'coll_search_form',             // Search Form
			'site_logo',                    // Site logo
			'cat_title',                    // Category Title
			'cat_content_list',             // Category Content List
			'coll_current_filters',         // Current Item filters
			'coll_current_comment_filters', // Current Comment filters
			'coll_item_list_pages',         // List Pager
			'item_next_previous',           // Next/Previous Item
			'coll_locale_switch',           // Language/Locale/Version switch
			'coll_category_list',           // Category list
			'content_hierarchy',            // Content Hierarchy
			'coll_tag_cloud',               // Tag cloud
			// Plugin: Calendar
			// Plugin: Date Archives
		),
	'content' => array(
			'coll_post_list',         // Post list
			'content_hierarchy',      // Content Hierarchy
			'coll_page_list',         // Page list
			'coll_featured_posts',    // Featured Posts list
			'coll_related_post_list', // Related Posts list
			'coll_flagged_list',      // Flagged Item List
			'coll_item_list',         // Universal Item list
			'coll_media_index',       // Photo index
			'coll_comment_list',      // Comment list
		),
	'disp_content' => array(
			'content_block',       // Content Block
			'coll_featured_intro', // Featured/Intro Post
			'display_item',        // Specific Item
			'item_fields_compare', // Compare Items
			'param_switcher',    // Param Switcher
		),
	'infoitem' => array(
			'item_title',                // Title
			'item_visibility_badge',     // Visibility Badge
			'item_content',              // Content
			'item_tags',                 // Tags
			'item_info_line',            // Info Line
			'item_small_print',          // Small Print
			'item_about_author',         // About Author
			'item_custom_fields',        // Custom Fields
			'item_attachments',          // Attachments
			'item_link',                 // Item Link
			'item_location',             // Location
			'item_vote',                 // Voting
			'item_seen_by',              // Seen by
			'item_workflow',             // Workflow Properties
			'item_footer',               // Footer
			'item_comment_form',         // Comment Form
			'item_comment_feed_link',    // Comment Feed Link
			'item_comment_notification', // Comment Notification
			'coll_item_notification',    // Subscribe to Item
		),
	'collection' => array(
			'coll_logo',                 // Logo
			'coll_title',                // Title
			'coll_tagline',              // Tagline
			'coll_longdesc',             // Long description
			'coll_member_count',         // Member count
			'coll_xml_feeds',            // XML Feeds (RSS / Atom)
			'coll_subscription',         // Subscribe to Items
			'coll_comment_notification', // Subscribe to Comments
			'coll_activity_stats',       // Activity Statistics
		),
	'about_user' => array(
			'user_profile_pics', // User Profile Picture
			'user_links',        // User Social Links
			'user_info',         // User info
			'user_action',       // User action
			'user_fields',       // User fields
		),
	'user' => array(
			'user_login',              // User log in
			'user_greetings',          // User greetings
			'user_register_quick',     // Email capture / Quick registration
			'user_register_standard',  // Registration form
			'newsletter_subscription', // Newsletter/Email list subscription
			'user_avatars',            // User list
			'org_members',             // Organization Members
			'online_users',            // Online users
		),
	'other' => array(
			'social_links', // Free Social Links
			'page_404_not_found',   // 404 Not Found
			'mobile_skin_switcher', // Mobile Skin Switcher
			// Plugin: Facebook Like/Share
			// Plugin: Financial Contribution
			// Plugin: Who's online Widget
		),
);

if( is_pro() )
{	// Additional widget for pro version:
	$core_componentwidget_defs['menu_item'][] = 'mustread_menu_link';
}

// Set additional param to add new widget:
$mode_url_param = $mode == 'customizer' ? '&amp;mode=customizer' : '';

$Form = new Form( NULL, 'new_widget_selector', 'post', 'accordion' );

$Form->begin_form( 'fform' );

$Form->begin_group();

foreach( $widget_groups as $widget_group_code => $widget_group_title )
{
	// Group title:
	$Form->begin_fieldset( $widget_group_title );

	if( ! isset( $core_componentwidget_defs[ $widget_group_code ] ) )
	{ // No widgets for this group
		$Form->end_fieldset();
		continue;
	}

	echo '<ul class="widget_list">';

	// Core widgets:
	if( isset( $core_componentwidget_defs[ $widget_group_code ] ) )
	{
		foreach( $core_componentwidget_defs[ $widget_group_code ] as $widget_code )
		{
			if( ! file_exists( $inc_path.'widgets/widgets/_'.$widget_code.'.widget.php' ) )
			{	// Skip not found widget to avoid die error:
				echo '<li><span class="label label-warning evo_widget_icon"><span class="fa fa-warning"></span></span> <b>Not found widget by code</b> <code>'.$widget_code.'</code></li>';
				continue;
			}

			$classname = $widget_code.'_Widget';
			load_class( 'widgets/widgets/_'.$widget_code.'.widget.php', $classname);

			$ComponentWidget = new $classname( NULL, 'core', $widget_code );

			echo '<li>';
			echo '<a href="'.regenerate_url( '', 'action=create&amp;type=core&amp;code='.$ComponentWidget->code.$mode_url_param.'&amp;'.url_crumb( 'widget' ) ).'"'
				.' title="'.format_to_output( $ComponentWidget->get_desc(), 'htmlattr' ).'"'
				.' data-toggle="tooltip">';
			echo $ComponentWidget->get_icon().' <strong>'.$ComponentWidget->get_name().'</strong>';
			echo '</a> '.$ComponentWidget->get_help_link( 'manual', false );
			echo '</li>';
		}
	}

	// Plugin widgets:
	if( isset( $Plugin_array_grouped[ $widget_group_code ] ) )
	{
		foreach( $Plugin_array_grouped[ $widget_group_code ] as $Plugin )
		{
			echo '<li>';
			echo '<a href="'.regenerate_url( '', 'action=create&amp;type=plugin&amp;code='.$Plugin->code.$mode_url_param.'&amp;'.url_crumb( 'widget' ) ).'"'
				.' title="'.T_('Add this widget to the container').'"'
				.' data-toggle="tooltip">';
			echo $Plugin->get_widget_icon().' <strong>'.$Plugin->name.'</strong>';
			echo '</a> '.$Plugin->get_help_link( '$widget_url', 'manual', false );
			echo '</li>';
		}
	}

	echo '</ul>';

	$Form->end_fieldset();
}

$Form->end_group();

$Form->end_form();

if( $mode == 'customizer' )
{	// End of customizer content:
	echo '</div>';
}
?>
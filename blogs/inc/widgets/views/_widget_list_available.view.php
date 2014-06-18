<?php
/**
 * This file implements the UI view for the Available widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id: _widget_list_available.view.php 6426 2014-04-08 16:26:27Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $container;

echo '<h2><span class="right_icons">'.action_icon( T_('Cancel!'), 'close', regenerate_url( 'container' ) ).'</span>'
	.sprintf(T_('Widgets available for insertion into &laquo;%s&raquo;'), $container ).'</h2>';


$core_componentwidget_defs = array(
		'*'.T_('General purpose widgets'),
			'free_html',
			'coll_logo',
			'coll_avatar',
		'*'.T_('Different ways of listing the blog contents'),
			'coll_item_list',			// Universal Item List
			'coll_post_list',			// Simple List
			'coll_related_post_list',			// Simple List
			'coll_page_list',			// Simple List
			'coll_link_list',     // Simple List
			'linkblog',	        	// Simple List
			'coll_media_index',
			'coll_comment_list',
			'coll_featured_intro',
		'*'.T_('Blog navigation'),
			'coll_tag_cloud',
			'coll_category_list',
			'coll_common_links',
			'coll_search_form',
			'coll_xml_feeds',
			'menu_link',
			'msg_menu_link',
		'*'.T_('Meta info for the blog'),
			'coll_title',
			'coll_tagline',
			'coll_longdesc',
		'*'.T_('Other'),
			'colls_list_public',
			'colls_list_owner',
			'user_tools',
			'user_login',
			'user_avatars',
			'online_users',
			'mobile_skin_switcher',
	);
$i = 0;
foreach( $core_componentwidget_defs as $code )
{
	$i++;
	if( $code[0] == '*' )
	{ // group
		if( $i > 1 )
		{
			echo '</ul>';
		}
		echo '<h3>'.substr( $code, 1 ).':</h3><ul class="widget_list">';
	}
	else
	{
		$classname = $code.'_Widget';
		load_class( 'widgets/widgets/_'.$code.'.widget.php', $classname);

		$ComponentWidget = new $classname( NULL, 'core', $code );

		echo '<li>';
		echo '<a href="'.regenerate_url( '', 'action=create&amp;type=core&amp;code='.$ComponentWidget->code.'&amp;'.url_crumb('widget') ).'" title="'.T_('Add this widget to the container').'">';
		echo get_icon( 'new' ).'<strong>'.$ComponentWidget->get_name().'</strong>';
		echo '</a> <span class="notes">'.$ComponentWidget->get_desc().'</span>';
		echo '</li>';
	}
}


// Now, let's try to get the Plugins that implement a skintag...
// TODO: at some point we may merge them with the above, but alphabetical order probably isn't the best solution

/**
 * @var Plugins
 */
global $Plugins, $Debuglog;

$Plugin_array = $Plugins->get_list_by_event( 'SkinTag' );
// Remove the plugins, which have no code, because this gets used to install them:
foreach( $Plugin_array as $k => $v )
{
	if( empty($v->code) )
	{
		$Debuglog->add( sprintf('Removing plugin %s (#%d) from list of widgets, because of empty code.', $v->classname, $v->ID), 'plugins' );
		unset($Plugin_array[$k]);
	}
}
if( ! empty($Plugin_array) )
{ // We have some plugins

	echo '</ul>';
	echo '<h3>'.T_('Plugins').':</h3><ul class="widget_list">';

	foreach( $Plugin_array as $ID => $Plugin )
	{
		echo '<li>';
		echo '<a href="'.regenerate_url( '', 'action=create&amp;type=plugin&amp;code='.$Plugin->code.'&amp;'.url_crumb('widget') ).'" title="'.T_('Add this widget to the container').'">';
		echo get_icon( 'new' ).'<strong>'.$Plugin->name.'</strong>';
		echo '</a> <span class="notes">'.$Plugin->short_desc.'</span>';
		echo '</li>';
	}
}
echo '</ul>';

?>
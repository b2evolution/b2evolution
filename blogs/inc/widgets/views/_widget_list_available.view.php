<?php
/**
 * This file implements the UI view for the Available widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id$
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
		'*'.T_('Blog navigation'),
			'coll_tag_cloud',
			'coll_category_list',
			'coll_common_links',
			'coll_search_form',
			'coll_xml_feeds',
			'menu_link',
		'*'.T_('Meta info for the blog'),
			'coll_title',
			'coll_tagline',
			'coll_longdesc',
		'*'.T_('Other'),
			'colls_list_public',
			'colls_list_owner',
			'user_tools',
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
		echo '<a href="'.regenerate_url( '', 'action=create&amp;type=plugin&amp;code='.$Plugin->code ).'" title="'.T_('Add this widget to the container').'">';
		echo get_icon( 'new' ).'<strong>'.$Plugin->name.'</strong>';
		echo '</a> <span class="notes">'.$Plugin->short_desc.'</span>';
		echo '</li>';
	}
}
echo '</ul>';


/*
 * $Log$
 * Revision 1.27  2010/01/30 18:55:35  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.26  2010/01/16 14:27:04  efy-yury
 * crumbs, fadeouts, redirect, action_icon
 *
 * Revision 1.25  2009/09/20 00:33:59  blueyed
 * Add widget to display avatar of collection/blog owner. Install it for all new blogs by default.
 *
 * Revision 1.24  2009/09/18 15:47:10  fplanque
 * doc/cleanup
 *
 * Revision 1.23  2009/09/18 15:02:38  waltercruz
 * Fixing widget list
 *
 * Revision 1.22  2009/03/20 23:28:31  fplanque
 * renamed coll_link_list widget
 *
 * Revision 1.21  2009/03/20 23:20:16  fplanque
 * Related posts widget
 *
 * Revision 1.20  2009/03/15 22:48:16  fplanque
 * refactoring... final step :)
 *
 * Revision 1.19  2009/03/15 20:54:53  fplanque
 * minor cleanup
 *
 * Revision 1.18  2009/03/15 20:35:18  fplanque
 * Universal Item List proof of concept
 *
 * Revision 1.17  2009/03/13 02:45:55  fplanque
 * normalized
 *
 * Revision 1.16  2009/03/13 01:25:11  blueyed
 * Add Debuglog entry if a widget gets removed out of the installable list because of empty plugin code
 *
 * Revision 1.15  2009/03/13 00:58:52  fplanque
 * making sense of widgets -- work in progress
 *
 * Revision 1.14  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.13  2009/02/22 23:40:09  fplanque
 * dirty links widget :/
 *
 * Revision 1.12  2009/01/24 00:29:27  waltercruz
 * Implementing links in the blog itself, not in a linkblog, first attempt
 *
 * Revision 1.11  2008/09/23 09:04:33  fplanque
 * moved media index to a widget
 *
 * Revision 1.10  2008/02/13 07:37:55  fplanque
 * renamed Blog Logo widget
 *
 * Revision 1.9  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.8  2007/12/26 20:04:54  fplanque
 * minor
 *
 * Revision 1.7  2007/12/24 14:53:49  yabs
 * adding coll_comment_list widget
 *
 * Revision 1.6  2007/12/23 16:16:18  fplanque
 * Wording improvements
 *
 * Revision 1.5  2007/12/20 22:59:34  fplanque
 * TagCloud widget prototype
 *
 * Revision 1.4  2007/09/28 02:17:48  fplanque
 * Menu widgets
 *
 * Revision 1.3  2007/08/21 22:28:29  blueyed
 * Do not display plugins which then fail to install in the widgets list
 *
 * Revision 1.2  2007/07/01 03:55:05  fplanque
 * category plugin replaced by widget
 *
 * Revision 1.1  2007/06/25 11:02:01  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.10  2007/06/21 00:44:36  fplanque
 * linkblog now a widget
 *
 * Revision 1.9  2007/06/20 13:19:29  fplanque
 * Free html widget
 *
 * Revision 1.8  2007/06/20 01:12:49  fplanque
 * groups
 *
 * Revision 1.7  2007/06/18 21:25:48  fplanque
 * one class per core widget
 *
 * Revision 1.6  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.5  2007/01/14 01:32:11  fplanque
 * more widgets supported! :)
 *
 * Revision 1.4  2007/01/12 21:53:12  blueyed
 * Probably fixed Plugins::get_list_by_* methods: the returned references were always the one to the last Plugin
 *
 * Revision 1.3  2007/01/12 21:38:42  blueyed
 * doc
 *
 * Revision 1.2  2007/01/12 05:17:15  fplanque
 * $Plugins->get_list_by_event() returns crap :((
 *
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 */
?>

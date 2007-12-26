<?php
/**
 * This file implements the UI view for the Available widgets.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
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
		'*'.T_('Different ways of listing the blog contents'),
			'coll_category_list',
			'coll_tag_cloud',
			'coll_post_list',
			'coll_page_list',
			'coll_comment_list',
			'coll_xml_feeds',
		'*'.T_('Meta info for the blog'),
			'coll_title',
			'coll_tagline',
			'coll_longdesc',
			'coll_logo',
		'*'.T_('Blog navigation'),
			'menu_link',
			'coll_common_links',
			'coll_search_form',
			'user_tools',
		'*'.T_('Other'),
			'colls_list_public',
			'colls_list_owner',
			'linkblog',
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
		echo '<h3>'.substr( $code, 1 ).':</h3><ul>';
	}
	else
	{
		load_class( 'widgets/widgets/_'.$code.'.widget.php' );
		$classname = $code.'_Widget';
		$ComponentWidget = & new $classname( NULL, 'core', $code );

		echo '<li>';
		echo '<a href="'.regenerate_url( '', 'action=create&amp;type=core&amp;code='.$ComponentWidget->code ).'" title="'.T_('Add this widget to the container').'">';
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
global $Plugins;

$Plugin_array = $Plugins->get_list_by_event( 'SkinTag' );
// Remove the plugins, which have no code, because this gets used to install them:
foreach( $Plugin_array as $k => $v )
{
	if( empty($v->code) )
	{
		unset($Plugin_array[$k]);
	}
}
if( ! empty($Plugin_array) )
{ // We have some plugins

	echo '</ul>';
	echo '<h3>'.T_('Plugins').':</h3><ul>';

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
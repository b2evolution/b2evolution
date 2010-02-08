<?php
/**
 * This file implements the links_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/widgets/_coll_item_list.widget.php', 'coll_item_list_Widget' );

/**
 * links_widget class
 *
 * This widget displays the links from a blog, from the posts with post_type = Link, without using a linkblog.
 *
 * @package evocore
 */
class coll_link_list_Widget extends coll_item_list_Widget
{
	/**
	 * Constructor
	 */
	function coll_link_list_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_link_list' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// This is derived from coll_post_list_Widget, so we DO NOT ADD ANY param here!
		$r = parent::get_param_definitions( $params );
		// We only change the defaults and hide some params.
		$r['title']['defaultvalue'] = T_('Links');
		$r['title_link']['no_edit'] = true;
		$r['item_type']['no_edit'] = true;
		$r['follow_mainlist']['no_edit'] = true;
		$r['blog_ID']['no_edit'] = true;
		$r['item_title_link_type']['no_edit'] = true;
		$r['disp_excerpt']['no_edit'] = true;
		$r['disp_teaser']['no_edit'] = true;
		$r['disp_teaser_maxwords']['no_edit'] = true;
		$r['widget_css_class']['no_edit'] = true;
		$r['widget_ID']['no_edit'] = true;

		return $r;
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Simple Sidebar Links list');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Simplified Item list for listing Sidebar links.');
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		// Force some params (because this is a simplified widget):
		$params['item_type'] = '3000';	// Use item types 3000 (sidebar links) only

		parent::init_display( $params );
	}

}


/*
 * $Log$
 * Revision 1.5  2010/02/08 17:54:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.4  2009/12/06 18:07:44  fplanque
 * Fix simplified list widgets.
 *
 * Revision 1.3  2009/09/14 13:54:13  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.2  2009/09/12 11:11:21  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.1  2009/03/20 23:28:31  fplanque
 * renamed coll_link_list widget
 *
 * Revision 1.12  2009/03/15 22:48:16  fplanque
 * refactoring... final step :)
 *
 * Revision 1.11  2009/03/15 02:16:35  fplanque
 * auto link option for titles
 *
 * Revision 1.10  2009/03/14 03:02:56  fplanque
 * Moving towards an universal item list widget, step 1
 *
 * Revision 1.9  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.8  2009/03/13 00:54:37  fplanque
 * calling it "sidebar links"
 *
 * Revision 1.7  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.6  2009/03/04 00:59:19  fplanque
 * doc
 *
 * Revision 1.5  2009/02/25 17:18:03  waltercruz
 * Linkroll stuff, take #2
 *
 * Revision 1.4  2009/02/22 23:40:09  fplanque
 * dirty links widget :/
 *
 * Revision 1.3  2009/02/22 14:42:03  waltercruz
 * A basic implementation that merges disp_cat_item_list2(links) and disp_cat_item_list(linkblog). Will delete disp_cat_item_list2 as soon fplanque says that the merge it's ok
 *
 * Revision 1.2  2009/02/22 14:15:48  waltercruz
 * updating docs
 *
 * Revision 1.1  2009/01/24 00:29:27  waltercruz
 * Implementing links in the blog itself, not in a linkblog, first attempt
 *
 *
 */
?>

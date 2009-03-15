<?php
/**
 * This file implements the links_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class( 'widgets/model/_widget.class.php' );

/**
 * links_widget class
 *
 * This widget displays the links from a blog, from the posts with post_type = Link, without using a linkblog.
 *
 * @package evocore
 */
class links_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function links_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'links' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		$title = T_('Links list');
		return $title;
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
		return T_('List of sidebar links (grouped by category), click goes to link destination.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $Blog;
		// Demo data:
		$r = array_merge( array(
				'title' => array(
					'label' => 'Block title',
					'size' => 60,
					'defaultvalue' => T_('Links'),
					'note' => T_( 'This is the title to display in your skin.' ),
				),
				'order_by' => array(
					'label' => T_('Order by'),
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => get_available_sort_options(),
					'defaultvalue' => 'title',
				),
				'order_dir' => array(
					'label' => T_('Direction'),
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => array( 'ASC'  => T_('Ascending'), 'DESC' => T_('Descending') ),
					'defaultvalue' => 'ASC',
				),
				'limit' => array(
					'label' => T_( 'Limit' ),
					'size' => 4,
					'defaultvalue' => 20,
					'note' => T_( 'Maximum number of items to display.' ),
				),
				'item_title_link_type' => array(
					'label' => T_('Link titles'),
					'note' => T_('Where should titles be linked to?'),
					'type' => 'select',
					'options' => array(
							'auto'        => T_('Automatic'),
							'permalink'   => T_('Item permalink'),
							'linkto_url'  => T_('Item URL'),
							'none'        => T_('Nowhere'),
						),
					'defaultvalue' => 'auto',
				),
				'disp_excerpt' => array(
					'label' => T_( 'Excerpt' ),
					'note' => T_( 'Display excerpt for each item.' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'disp_teaser' => array(
					'label' => T_( 'Teaser' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
					'note' => T_( 'Display teaser for each item.' ),
				),
				'disp_teaser_maxwords' => array(
					'label' => T_( 'Max Words' ),
					'type' => 'integer',
					'defaultvalue' => 20,
					'note' => T_( 'Max number of words for the teasers' ),
				),
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		// List of pages:
		$this->disp_cat_item_list();

		return true;
	}
}


/*
 * $Log$
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

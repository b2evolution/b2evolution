<?php
/**
 * This file implements the linkblog_Widget class.
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
 * linkblog_widget class
 *
 * This widget displays another blog as a linkblog.
 *
 * @package evocore
 */
class linkblog_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function linkblog_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'linkblog' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		$title = T_('Linkblog');
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
		return T_('Display blog entries, grouped by category.');
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
					'defaultvalue' => T_('Linkblog'),
					'note' => T_( 'This is the title to display in your skin.' ),
				),
				'linkblog_ID' => array(
					'label' => T_( 'Blog ID' ),
					'size' => 4,
					'defaultvalue' => $Blog->get('links_blog_ID'),	// Here we conveniently recycle the previous value from its deprecated links_blog_ID param. We will eventually drop that field from the database.
					'note' => T_( 'This is the ID number of the blog to display.' ),
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
				'disp_excerpt' => array(
					'label' => T_( 'Excerpt' ),
					'note' => T_( 'Display excerpt for each item.' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
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
 * Revision 1.19  2009/03/15 02:19:47  fplanque
 * auto link option for titles
 *
 * Revision 1.17  2009/03/14 03:02:56  fplanque
 * Moving towards an universal item list widget, step 1
 *
 * Revision 1.16  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.15  2009/03/13 00:58:52  fplanque
 * making sense of widgets -- work in progress
 *
 * Revision 1.14  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.13  2009/03/04 01:19:41  fplanque
 * doc
 *
 * Revision 1.12  2009/02/23 08:13:21  yabs
 * Added check for excerpts
 *
 * Revision 1.11  2009/02/22 23:40:09  fplanque
 * dirty links widget :/
 *
 * Revision 1.10  2009/02/07 11:08:39  yabs
 * adding settings
 *
 * Revision 1.9  2008/05/31 22:38:55  blueyed
 * doc, indent
 *
 * Revision 1.8  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.7  2008/01/17 18:10:12  fplanque
 * deprecated linkblog_ID blog param
 *
 * Revision 1.6  2007/12/23 14:14:25  fplanque
 * Enhanced widget name display
 *
 * Revision 1.5  2007/12/22 19:55:00  yabs
 * cleanup from adding core params
 *
 * Revision 1.3  2007/12/20 10:48:50  fplanque
 * doc
 *
 * Revision 1.2  2007/12/18 10:27:30  yabs
 * adding params
 *
 * Revision 1.1  2007/06/25 11:02:26  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.1  2007/06/21 00:44:37  fplanque
 * linkblog now a widget
 *
 * Revision 1.2  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>

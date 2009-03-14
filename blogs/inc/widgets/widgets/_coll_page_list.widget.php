<?php
/**
 * This file implements the xyz Widget class.
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
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_page_list_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_page_list_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_page_list' );
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_( 'Title to display in your skin.' ),
					'size' => 60,
					'defaultvalue' => T_('Pages'),
				),
				'title_link' => array(
					'label' => T_('Link to blog'),
					'note' => T_('Link the block title to the blog?'),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'blog_ID' => array(
					'label' => T_( 'Blog' ),
					'note' => T_( 'ID of the blog to use, leave empty for the current blog.' ),
					'size' => 4,
				),
				'order_by' => array(
					'label' => T_('Order by'),
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => get_available_sort_options(),
					'defaultvalue' => 'datestart',
				),
				'order_dir' => array(
					'label' => T_('Direction'),
					'note' => T_('How to sort the items'),
					'type' => 'select',
					'options' => array( 'ASC'  => T_('Ascending'), 'DESC' => T_('Descending') ),
					'defaultvalue' => 'DESC',
				),
				'limit' => array(
				'label' => T_( 'Max items' ),
				'note' => T_( 'Maximum number of items to display.' ),
					'size' => 4,
					'defaultvalue' => 20,
				),
				'item_title_link_type' => array(
					'label' => T_('Link titles'),
					'note' => T_('Where should titles be linked to?'),
					'type' => 'select',
					'options' => array(
							'permalink'   => T_('Item permalink'),
							'linkto_url'  => T_('Item URL'),
							'none'        => T_('Nowhere'),
						),
					'defaultvalue' => 'permalink',
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
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Page list');
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
		return T_('List of all pages; click goes to page.');
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
		$this->disp_item_list( 'pages' );

		return true;
	}
}


/*
 * $Log$
 * Revision 1.13  2009/03/14 03:02:56  fplanque
 * Moving towards an universal item list widget, step 1
 *
 * Revision 1.12  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.11  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.10  2008/09/24 08:44:11  fplanque
 * Fixed and normalized order params for widgets (Comments not done yet)
 *
 * Revision 1.9  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.8  2007/12/26 23:12:48  yabs
 * changing RANDOM to RAND
 *
 * Revision 1.7  2007/12/26 20:04:54  fplanque
 * minor
 *
 * Revision 1.6  2007/12/24 14:21:17  yabs
 * adding params
 *
 * Revision 1.5  2007/12/24 12:05:31  yabs
 * bugfix "order" is a reserved name, used by wi_order
 *
 * Revision 1.4  2007/12/24 11:01:21  yabs
 * adding random order
 *
 * Revision 1.3  2007/12/23 16:16:18  fplanque
 * Wording improvements
 *
 * Revision 1.2  2007/12/23 15:44:39  yabs
 * adding params
 *
 * Revision 1.1  2007/06/25 11:02:16  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.2  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>
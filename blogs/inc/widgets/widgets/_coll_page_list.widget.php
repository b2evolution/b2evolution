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

load_class( 'widgets/widgets/_coll_item_list.widget.php' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_page_list_Widget extends coll_item_list_Widget
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
		// This is derived from coll_post_list_Widget, so we DO NOT ADD ANY param here!
		$r = parent::get_param_definitions( $params );
		// We only change the defaults and hide some params.
		$r['title']['defaultvalue'] = T_('Pages');
		$r['title_link']['no_edit'] = true;
		$r['item_type']['no_edit'] = true;
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
		return T_('Simple Page list');
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
		return T_('Simplified Item list for listing pages.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		// Force some params (because this is a simplified widget):
		$this->disp_params['item_type'] = '1000';	// Use item types 1000 (pages) only

		// List of posts:
		$this->disp_item_list();

		return true;
	}
}


/*
 * $Log$
 * Revision 1.14  2009/03/15 20:35:18  fplanque
 * Universal Item List proof of concept
 *
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
 */
?>
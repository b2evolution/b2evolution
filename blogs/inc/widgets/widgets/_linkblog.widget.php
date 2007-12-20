<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
		$this->init_display( array() );
		$title = T_('Linkblog');
		if( $this->disp_params[ 'widget_name' ] )
		{ // TODO: generalize for all widgets + revert (put custom name first)
			$title .= ' ( '.$this->disp_params[ 'widget_name' ].' )';
		}
		return $title;
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display list of entries from the linkblog, grouped by category.');
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
		$r = array(
			'widget_name' => array(
				'label' => T_( 'Widget name' ),
				'size' => 60,
				'note' => T_( 'This is the name displayed in the widget list' ),
			),
			'title' => array(
				'label' => 'Block title',
				'size' => 60,
				'defaultvalue' => T_('Linkblog'),
				'note' => T_( 'This is title to display in your skin' ),
			),
			'linkblog_ID' => array(
				'label' => T_( 'Linkblog' ),
				'size' => 4,
				'defaultvalue' => $Blog->get('links_blog_ID'),
				'note' => T_( 'This is ID number of the blog to use as a linkblog' ),
			),
			'linkblog_limit' => array(
				'label' => T_( 'Display' ),
				'size' => 4,
				'note' => T_( 'This is the maximum number of links to display, leave empty for all links' ),
// TODO: put 100 as the default, 1000 as the max and don't lie about the illusion of ifinity :p
			),
		);

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
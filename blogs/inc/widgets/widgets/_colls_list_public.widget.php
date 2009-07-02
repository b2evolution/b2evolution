<?php
/**
 * This file implements the colls_list_public Widget class.
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
class colls_list_public_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function colls_list_public_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'colls_list_public' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Public blog list');
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
		return T_('Display list of all blogs marked as public.');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $use_strict;
		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display, $icon$ will be replaced by the feed icon' ),
					'defaultvalue' => T_('All blogs'),
				),
				/* 3.3? this is borked
				'list_type' => array(
					'label' => T_( 'Display type' ),
					'type' => 'select',
					'defaultvalue' => 'list',
					'options' => array( 'list' => T_('List'), 'form' => T_('Select menu') ),
					'note' => T_( 'How do you want to display blogs?' ),
				),
				*/
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

		$this->disp_coll_list( 'public' );

		return true;
	}
}


/*
 * $Log$
 * Revision 1.7  2009/07/02 21:50:13  fplanque
 * commented out unfinished code
 *
 * Revision 1.6  2009/06/18 07:35:53  yabs
 * bugfix : $type is already a param ;)
 *
 * Revision 1.5  2009/05/28 06:49:06  sam2kb
 * Blog list widget can be either a "regular list" or a "select menu"
 * See http://forums.b2evolution.net/viewtopic.php?t=18794
 *
 * Revision 1.4  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.3  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:02:24  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.2  2007/06/20 14:25:00  fplanque
 * fixes
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>
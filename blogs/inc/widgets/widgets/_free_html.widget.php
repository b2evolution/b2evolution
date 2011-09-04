<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class free_html_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function free_html_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'free_html' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		$title = T_( 'Free HTML' );
		return $title;
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * @return string The block title, the first 60 characters of the block
	 *                content or an empty string.
	 */
	function get_short_desc()
	{
		if( empty( $this->disp_params['title'] ) )
		{
			return strmaxlen( $this->disp_params['content'], 60, NULL, /* use htmlspecialchars() */ 'formvalue' );
		}

		return format_to_output( $this->disp_params['title'] );
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Custom text/HTML of your choice.');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Demo data:
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'size' => 60,
				),
				'content' => array(
					'type' => 'html_textarea',
					'label' => T_('Block content'),
					'rows' => 10,
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
		global $Blog;

		$this->init_display( $params );

		// Collection common links:
		echo $this->disp_params['block_start'];

		$this->disp_title( $this->disp_params['title'] );

		echo format_to_output( $this->disp_params['content'] );

		echo $this->disp_params['block_end'];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.23  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.22  2010/02/08 17:54:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.21  2009/09/29 13:29:58  tblue246
 * Proper security fixes
 *
 * Revision 1.20  2009/09/29 03:29:58  fplanque
 * security fix
 *
 * Revision 1.19  2009/09/27 12:57:29  blueyed
 * strmaxlen: add format param, which is used on the (possibly) cropped string.
 *
 * Revision 1.18  2009/09/14 13:54:13  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.17  2009/09/12 11:03:13  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.16  2009/08/06 16:12:57  tblue246
 * - Make block title field name translatable - again...
 * - Show first 60 chars of block content if block title is empty
 *
 * Revision 1.15  2009/08/06 15:04:25  fplanque
 * internal name is overkill. (too many confusing params not good)
 * (maybe the first chars of content if title is empty ?)
 *
 * Revision 1.14  2009/08/03 14:05:04  tblue246
 * minor
 *
 * Revision 1.13  2009/08/03 12:33:57  tblue246
 * Add "internal name" field to HTML widget and make another field title translatable
 *
 * Revision 1.12  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.11  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.10  2008/05/06 23:35:47  fplanque
 * The correct way to add linebreaks to widgets is to add them to $disp_params when the container is called, right after the array_merge with defaults.
 *
 * Revision 1.8  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.7  2007/12/23 16:16:18  fplanque
 * Wording improvements
 *
 * Revision 1.6  2007/12/23 14:14:25  fplanque
 * Enhanced widget name display
 *
 * Revision 1.5  2007/12/22 19:55:00  yabs
 * cleanup from adding core params
 *
 * Revision 1.4  2007/12/22 17:02:50  yabs
 * removing obsolete params
 *
 * Revision 1.3  2007/11/28 23:23:18  yabs
 * clarification of params
 *
 * Revision 1.2  2007/11/27 10:02:04  yabs
 * added params
 *
 * Revision 1.1  2007/06/25 11:02:25  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.2  2007/06/20 21:42:14  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.1  2007/06/20 13:19:29  fplanque
 * Free html widget
 *
 */
?>

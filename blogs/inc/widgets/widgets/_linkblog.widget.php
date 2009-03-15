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

load_class( 'widgets/widgets/_coll_item_list.widget.php' );

/**
 * linkblog_widget class
 *
 * This widget displays another blog as a linkblog.
 *
 * @package evocore
 */
class linkblog_Widget extends coll_item_list_Widget
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
		$r['title']['defaultvalue'] = T_('Linkblog');
		$r['title_link']['no_edit'] = true;
		$r['blog_ID']['defaultvalue'] = 0;		// zero is a magic number that we'll use to try and use defaults used in previous versions of B2evo
		$r['item_group_by']['defaultvalue'] = 'chapter';
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
		return T_('Linkblog');
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
		return T_('Simplified Item list for listing posts.');
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

		// Force some params (because this is a simplified widget):

		if( $this->disp_params['blog_ID'] == 0 )
		{	// We want to try and use previous defaults:
			if( !empty( $this->disp_params['linkblog_ID'] ) )
			{
				$this->disp_params['blog_ID'] = $this->disp_params['linkblog_ID'];
			}
			else
			{ // Recycle the previous value from deprecated links_blog_ID param. We will eventually drop that field from the database.
				$this->disp_params['blog_ID'] = $Blog->get('links_blog_ID');
			}
		}

		parent::display( $params );

		return true;
	}
}


/*
 * $Log$
 * Revision 1.20  2009/03/15 21:56:22  fplanque
 * factoring benefits are now falling into place...
 *
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
 */
?>

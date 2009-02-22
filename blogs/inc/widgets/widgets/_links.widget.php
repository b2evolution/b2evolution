<?php
/**
 * This file implements the links_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
		$title = T_('Links 2'); // fp >> Call this Linkroll -- EVRYWHERE
		return $title;
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display list of links, grouped by category.');
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
				'linkblog_limit' => array(
					'label' => T_( 'Display' ),
					'size' => 4,
					'defaultvalue' => 100,
					'note' => T_( 'This is the maximum number of links to display.' ),
				),
				'linkblog_excerpts' => array(
					'label' => T_( 'Excerpts' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
					'note' => T_( 'Show contents for entries' ),
				),
				'linkblog_cutoff' => array(
					'label' => T_( 'Max Words' ),
					'type' => 'integer',
					'defaultvalue' => 40,
					'note' => T_( 'Max number of words to show in exerpts' ),
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

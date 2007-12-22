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
class coll_common_links_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_common_links_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_common_links' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Common Navigation Links');
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display these links: Recently, Archives, Categories, Latest Comments');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( parent::get_param_definitions( $params ),
				array(
				'show_recently' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Recently"'),
					'note' => T_('Go to the most recent posts / the blog\'s home.'),
					'defaultvalue' => '1',
				),
				'show_archives' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Archives"'),
					'note' => T_('Go to the monthly/weekly/daily archive list.'),
					'defaultvalue' => '1',
				),
				'show_categories' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Categories"'),
					'note' => T_('Go to the category tree.'),
					'defaultvalue' => '1',
				),
				'show_latestcomments' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Latest comments"'),
					'note' => T_('Go to the latest comments.'),
					'defaultvalue' => '1',
				),
			)
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
		global $Blog;

		$this->init_display( $params );

		// Collection common links:
		echo $this->disp_params['block_start'];
		echo $this->disp_params['list_start'];

		if( $this->disp_params['show_recently'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('url').'">'.T_('Recently').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_archives'] )
		{
			// fp> TODO: don't display this if archives plugin not installed... or depluginize archives (I'm not sure)
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('arcdirurl').'">'.T_('Archives').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_categories'] )
		{
			// fp> TODO: don't display this if categories plugin not installed... or depluginize categories (I'm not sure)
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('catdirurl').'">'.T_('Categories').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_latestcomments'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('lastcommentsurl').'">'.T_('Latest comments').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.2  2007/12/22 19:55:00  yabs
 * cleanup from adding core params
 *
 * Revision 1.1  2007/06/25 11:02:06  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.2  2007/06/20 00:48:17  fplanque
 * some real life widget settings
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>
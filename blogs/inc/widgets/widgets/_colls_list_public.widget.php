<?php
/**
 * This file implements the colls_list_public Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _colls_list_public.widget.php 6135 2014-03-08 07:54:05Z manuel $
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
				'order_by' => array(
					'label' => T_('Order by'),
					'note' => T_('How to sort the blogs'),
					'type' => 'select',
					'options' => get_coll_sort_options(),
					'defaultvalue' => 'order',
				),
				'order_dir' => array(
					'label' => T_('Direction'),
					'note' => T_('How to sort the blogs'),
					'type' => 'radio',
					'options' => array( array( 'ASC', T_('Ascending') ),
										array( 'DESC', T_('Descending') ) ),
					'defaultvalue' => 'ASC',
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

		$this->disp_coll_list( 'public', $this->disp_params['order_by'], $this->disp_params['order_dir'] );

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Blog;

		return array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' =>'any', 					// Have the settings of ANY blog changed ? (ex: new skin here, new name on another)
			);
	}
}

?>
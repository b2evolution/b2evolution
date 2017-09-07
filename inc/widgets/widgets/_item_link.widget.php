<?php
/**
 * This file implements the item_links Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @author erhsatingin: Erwin Rommel Satingin
 *
 * @version $Id: $
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
class item_link_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_link' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-link-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Link');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Link') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item link.');
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
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'prefix' => array(
					'label' => T_( 'Prefix' ),
					'size' => 20,
					'note' => T_( 'Label before the link' ),
					'defaultvalue' => T_('Link')
				),
				'link_text' => array(
					'label' => T_( 'Link text' ),
					'size' => 50,
					'note' => T_( 'Leave empty to display the actual URL' ),
					'defaultvalue' => ''
				),
				'link_class' => array(
					'label' => T_( 'Link class' ),
					'size' => 20,
					'defaultvalue' => ''
				),

			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $preview;

		parent::init_display( $params );

		if( $preview )
		{	// Disable block caching for this widget when item is previewed currently:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		if( empty( $Item ) )
		{ // Don't display this widget when no Item object
			return;
		}

		$this->init_display( $params );

		$this->disp_params = array_merge( array(
				'widget_item_link_before' => '',
				'widget_item_link_after'  => '',
			), $this->disp_params );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		$params = array(
				'before'        => $this->disp_params['widget_item_link_before'].$this->disp_params['prefix'].( empty( $this->disp_params['prefix'] ) ? '' : ': ' ),
				'after'         => $this->disp_params['widget_item_link_after'],
				'link_class'     => $this->disp_params['link_class'],
				'podcast'       => '#', // Auto display mp3 player if post type is podcast (=> false, to disable)
			);

		if( ! empty( $this->disp_params['link_text'] ) )
		{
			$params['text_template'] = $this->disp_params['link_text'];
		}

		$Item->url_link( $params );

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $current_User, $Item;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
				'item_ID'      => $Item->ID, // Has the Item page changed?
			);
	}
}

?>
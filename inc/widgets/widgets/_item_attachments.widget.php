<?php
/**
 * This file implements the item_attachments Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
class item_attachments_Widget extends ComponentWidget
{
	var $icon = 'link';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_attachments' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-attachments-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Attachments');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Attachments') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item attachments.');
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
					'defaultvalue' => T_('Attachments').':',
				),
			'display_mode' => array(
					'type' => 'select',
					'label' => T_('Display as'),
					'options' => array(
							'list'    => T_('List'),
							'buttons' => T_('Buttons'),
						),
					'defaultvalue' => 'list',
				),
			'disp_download_icon' => array(
					'type' => 'checkbox',
					'label' => T_('Display download icon'),
					'defaultvalue' => 1,
					'note' => '',
				),
			'link_btn_text' => array(
					'label' => T_('Link text'),
					'size' => 40,
					'defaultvalue' => '',
				),
			'link_text' => array(
					'label' => T_('Link'),
					'note' => '',
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
							array( 'filename', T_('Always display Filename') ),
							array( 'title', T_('Display Title if available') ) ),
					'defaultvalue' => 'title',
				),
			'link_class' => array(
					'label' => T_('Link class'),
					'size' => 40,
					'defaultvalue' => '',
				),
			'disp_file_size' => array(
					'type' => 'checkbox',
					'label' => T_('Display file size'),
					'defaultvalue' => 1,
					'note' => '',
				),
			'disp_file_desc' => array(
					'type' => 'checkbox',
					'label' => T_('Add descriptions'),
					'defaultvalue' => 1,
					'note' => T_('Display description if available.'),
				),
			), parent::get_param_definitions( $params ) );

		return $r;
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
		{	// Don't display this widget when no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		$this->init_display( $params );

		$this->disp_params = array_merge( array(
				'widget_item_attachments_params' => array(),
			), $this->disp_params );

		$style_params = array(
				'display_download_icon' => $this->disp_params['disp_download_icon'],
				'file_link_text'        => $this->disp_params['link_text'],
				'file_link_class'       => $this->disp_params['link_class'],
				'display_file_size'     => $this->disp_params['disp_file_size'],
				'display_file_desc'     => $this->disp_params['disp_file_desc'],
			);

		if( $this->disp_params['display_mode'] == 'list' )
		{	// List style:
			$style_params = array_merge( $style_params, array(
					'before'           => '<div class="item_attachments"><ul class="bFiles">',
					'after'            => '</ul></div>',
					'file_link_format' => ( empty( $this->disp_params['link_btn_text'] ) ? '' : '<b>'.$this->disp_params['link_btn_text'].'</b> ' ).'$file_name$'
				) );
		}
		else
		{	// Button style:
			$style_params = array_merge( $style_params, array(
					'before'           => '',
					'before_attach'    => '',
					'after_attach'     => '',
					'after'            => '',
					'attach_format'    => '$file_link$',
					'file_link_format' => '$icon$ '.( empty( $this->disp_params['link_btn_text'] ) ? '' : '<b>'.$this->disp_params['link_btn_text'].'</b><br />' ).'$file_name$ $file_size$ $file_desc$',
				) );
		}

		// Get attachments/files that are linked to the current item:
		$item_files = $Item->get_files( array_merge( $this->disp_params['widget_item_attachments_params'], $style_params ) );

		if( empty( $item_files ) )
		{	// Don't display this widget when Item has no attachments:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because Item has no attachments.' );
			return false;
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		// Display attachments/files that are linked to the current item:
		echo $item_files;

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
		global $Collection, $Blog, $Item;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
				'item_ID'      => ( empty( $Item->ID ) ? 0 : $Item->ID ), // Has the Item page changed?
			);
	}
}

?>

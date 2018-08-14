<?php
/**
 * This file implements the item_images Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
 * @author erhsatingin: Erwin Rommel Satingin.
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
class item_images_Widget extends ComponentWidget
{
	var $icon = 'image';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_images' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-images-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Images');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Images') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item images.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		/*
		$params = array_merge( array(
			'before'                     => '<div>',
			'before_image'               => '<div class="image_block">',
			'before_image_legend'        => '<div class="image_legend">',
			'after_image_legend'         => '</div>',
			'after_image'                => '</div>',
			'after'                      => '</div>',
			'image_size'                 => 'fit-720x500',
			'image_size_x'               => 1, // Use '2' to build 2x sized thumbnail that can be used for Retina display
			'image_link_to'              => 'original', // Can be 'original' (image) or 'single' (this post)
			'limit'                      => 1000, // Max # of images displayed
			'before_gallery'             => '<div class="bGallery">',
			'after_gallery'              => '</div>',
			'gallery_image_size'         => 'crop-80x80',
			'gallery_image_limit'        => 1000,
			'gallery_colls'              => 5,
			'gallery_order'              => '', // 'ASC', 'DESC', 'RAND'
			'gallery_link_rel'           => 'lightbox[p'.$this->ID.']',
			'restrict_to_image_position' => 'teaser,teaserperm,teaserlink,aftermore', // 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'
			'data'                       =>  & $r,
			'get_rendered_attachments'   => true,
			'links_sql_select'           => '',
			'links_sql_orderby'          => 'link_order',
		), $params );
		*/

		// Load to use function get_available_thumb_sizes()
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
			'title' => array(
					'label' => T_('Title'),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
			'display_type' => array(
					'label' => T_('What to display'),
					'note' => '',
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
							array( 'cover', T_('Cover image') ),
							array( 'cover_with_fallback', T_('Cover image (or Teaser image if no Cover)') ),
							array( 'teaser', T_('Teaser images') ),
							array( 'aftermore', T_('"After more" images') ) ),
					'defaultvalue' => 'cover',
				),
			'invert_display_type' => array(
					'label' => T_('All images except the one selected above'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
				),
			'image_limit' => array(
					'label' => T_('Limit'),
					'type' => 'integer',
					'defaultvalue' => 1,
				),
			'image_size' => array(
					'label' => T_('Image Size'),
					'label' => T_('Thumbnail size'),
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'fit-480x600',
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
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		$this->init_display( $params );

		$this->disp_params = array_merge( array(
				'widget_item_images_params' => array(),
			), $this->disp_params );

		$image_positions = array( 'cover', 'teaser', 'teaserperm', 'teaserlink', 'aftermore' );
		switch( $this->disp_params['display_type'] )
		{
			case 'cover':
				$selected_position = array( 'cover' );
				break;
			case 'cover_with_fallback':
				$selected_position = array( 'cover', 'teaser', 'teaserperm', 'teaserlink' );
				break;
			case 'teaser':
				$selected_position = array( 'teaser', 'teaserperm', 'teaserlink' );
				break;
			case 'aftermore':
				$selected_position = array( 'aftermore' );
				break;
		}

		if( $this->disp_params['invert_display_type'] )
		{
			$selected_position = array_diff( $image_positions, $selected_position );
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		// Display images that are linked to the current item:
		$Item->images( array_merge( $this->disp_params['widget_item_images_params'], array(
				'image_size'                 => $this->disp_params['image_size'],
				'limit'                      => $this->disp_params['image_limit'],
				'restrict_to_image_position' => implode( ',', $selected_position ),
			) ) );

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
				'item_ID'      => $Item->ID, // Has the Item page changed?
			);
	}
}

?>

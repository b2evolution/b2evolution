<?php
/**
 * This file implements the Image Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * iamge_Widget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class image_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'image' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'image-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Image');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * MAY be overriden by core widgets. Example: menu link widget.
	 */
	function get_short_desc()
	{
		$this->load_param_array();
		if( !empty($this->param_array['image_file'] ) )
		{
			return $this->param_array['image_file'];
		}
		else
		{
			return $this->get_name();
		}
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Include an image.');
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
				'image_source' => array(
					'label' => T_('Image source'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'skin', T_('Skin folder') ),
							array( 'coll', T_('Collection File Root') ),
							array( 'shared', T_('Shared File Root') ) ),
					'defaultvalue' => 'skin',
				),
				'image_file' => array(
					'label' => T_('Image filename'),
					'note' => T_('Relative to the root of the selected source.'),
					'defaultvalue' => 'logo.png',
					'valid_pattern' => array( 'pattern'=>'~^[a-z0-9_\-/][a-z0-9_.\-/]*$~i',
																		'error'=>T_('Invalid filename.') ),
					'size' => 128,
				),
				'size_begin_line' => array(
					'type' => 'begin_line',
					'label' => T_('Image size'),
				),
					'width' => array(
						'label' => T_('Image width'),
						'note' => '',
						'defaultvalue' => '300px',
						'allow_empty' => true,
						'size' => 4,
						'hide_label' => true,
						'valid_pattern' => array(
								'pattern' => '~^(\d+(px|%)?)?$~i',
								'error'   => sprintf( T_('Invalid image size, it must be specified in px or %%.') ) ),
					),
					'size_separator' => array(
						'label' => ' x ',
						'type' => 'string',
					),
					'height' => array(
						'label' => T_('Image height'),
						'note' => '',
						'defaultvalue' => '',
						'allow_empty' => true,
						'size' => 4,
						'hide_label' => true,
						'valid_pattern' => array(
								'pattern' => '~^(\d+(px|%)?)?$~i',
								'error'   => sprintf( T_('Invalid image size, it must be specified in px or %%.') ) ),
					),
				'size_end_line' => array(
					'type' => 'end_line',
					'label' => T_('Leave blank for auto.'),
				),
				'alt' => array(
					'label' => T_('Image Alt text'),
					'note' => '',
					'defaultvalue' => '',
					'size' => 128,
				),
				'check_file' => array(
					'label' => T_('Check file'),
					'note' => T_('Check if file exists. If not, no IMG tag will be created.'),
					'type' => 'checkbox',
					'defaultvalue' => true,
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
		global $Collection, $Blog;

		switch( $this->disp_params['image_source'] )
		{
			case 'skin':
				global $skins_url, $skins_path;
				$skin_folder = $Blog->get_skin_folder();
				$image_url = $skins_url.$skin_folder.'/';
				$image_path = $skins_path.$skin_folder.'/';
				break;

			case 'shared':
				global $media_url, $media_path;
				$image_url = $media_url.'shared/';
				$image_path = $media_path.'shared/';
				break;

			case 'coll':
			default:
				$image_url = $Blog->get_media_url();
				$image_path = $Blog->get_media_dir();
				break;
		}

		if( $this->disp_params['check_file'] && ! file_exists( $image_path.$this->disp_params['image_file'] ) )
		{ // Logo file doesn't exist, Exit here because of widget setting requires this
			return true;
		}

		$this->init_display( $params );

		echo $this->disp_params['block_start'];

		// Initialize image attributes:
		$image_attrs = array(
				'src'   => $image_url.$this->disp_params['image_file'],
				'alt'   => $this->disp_params['alt'],
			);
		// Image width:
		$image_attrs['style'] = 'width:'.( empty( $this->disp_params['width'] ) ? 'auto' : format_to_output( $this->disp_params['width'], 'htmlattr' ) ).';';
		// Image height:
		$image_attrs['style'] .= 'height:'.( empty( $this->disp_params['height'] ) ? 'auto' : format_to_output( $this->disp_params['height'], 'htmlattr' ) ).';';
		// If no unit is specified in a size, consider the unit to be px:
		$image_attrs['style'] = preg_replace( '/(\d+);/', '$1px;', $image_attrs['style'] );

		// Print out image html tag with link to current collection front page:
		echo '<a href="'.$Blog->get( 'url' ).'"><img'.get_field_attribs_as_string( $image_attrs ).' /></a>';

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
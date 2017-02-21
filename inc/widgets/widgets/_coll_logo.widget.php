<?php
/**
 * This file implements the xyz Widget class.
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
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_logo_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_logo' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'logo-title-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Logo title');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * MAY be overriden by core widgets. Example: menu link widget.
	 */
	function get_short_desc()
	{
		$this->load_param_array();
		if( !empty($this->param_array['logo_file'] ) )
		{
			return $this->param_array['logo_file'];
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
		return T_('Include a logo (as a title replacement).');
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
				'logo_file_ID' => array(
					'label' => T_('Image'),
					'defaultvalue' => '',
					'type' => 'fileselect',
					'thumbnail_size' => 'fit-320x320',
				),
				'image_source' => array(
					'label' => T_('Fallback image source'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'skin', T_('Skin folder') ),
							array( 'coll', T_('Collection File Root') ),
							array( 'shared', T_('Shared File Root') ) ),
					'defaultvalue' => 'coll',
				),
				'logo_file' => array(
					'label' => T_('Fallback image filename'),
					'note' => T_('If no file was selected. Relative to the root of the selected source.'),
					'defaultvalue' => 'logo.png',
					'valid_pattern' => array( 'pattern'=>'~^$|^[a-z0-9_\-/][a-z0-9_.\-/]*$~i',
											  'error'=>T_('Invalid filename.') ),
					// the following is necessary to catch user input value of "<". Otherwise, "<" and succeeding characters
					// will translate to an empty string and pass the regex pattern below
					'type' => 'html_input',
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
					'note' => T_('Leave empty to use collection title by default.'),
					'defaultvalue' => '',
					'size' => 128,
				),
				'check_file' => array(
					'label' => T_('Check file'),
					'note' => '',
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
							array( 'none', T_('Don\'t check. Assume image file exists.') ),
							array( 'check', T_('Check -> if image doesn\'t exist, display nothing.') ),
							array( 'title', T_('Check -> if image doesn\'t exist, display the collection title instead.') ) ),
					'defaultvalue' => 'title',
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

		// Get a widget setting to know how we should check a file:
		$check_file = $this->disp_params['check_file'];
		$file_ID = $this->disp_params['logo_file_ID'];
		$FileCache = & get_FileCache();
		$File = false;

		if( ! empty( $file_ID ) )
		{
			$File = & $FileCache->get_by_ID( $file_ID, false );
		}

		if( ! empty( $File ) && file_exists( $File->get_full_path() ) )
		{
			$image_url = $File->get_url();
		}
		elseif( ! empty( $this->disp_params['logo_file'] ) && ( $check_file == 'none' || file_exists( $image_path.$this->disp_params['logo_file'] ) ) )
		{
			$image_url .= $this->disp_params['logo_file'];
		}
		else
		{
			$image_url = '';
		}

		if( $check_file != 'title' && empty( $image_url ) )
		{
			return true;
		}

		$this->init_display( $params );

		// Collection logo:
		echo $this->disp_params['block_start'];

		$title = '<a href="'.$Blog->get( 'url' ).'">';

		// Initialize the image tag for logo:
		$image_attrs = '';
		if( ! empty( $this->disp_params['width'] ) )
		{ // Image width
			$image_attrs .= ' width="'.intval( $this->disp_params['width'] ).'"';
		}
		if( ! empty( $this->disp_params['height'] ) )
		{ // Image height
			$image_attrs .= ' height="'.intval( $this->disp_params['height'] ).'"';
		}

		if( $check_file == 'title' && empty( $image_url ) )
		{ // Logo file doesn't exist, Display a collection title because widget setting requires this:
			$title .= $Blog->get( 'name' );
		}
		else
		{
			// Initialize image attributes:
			$image_attrs = array(
					'src'   => $image_url,
					'alt'   => empty( $this->disp_params['alt'] ) ? $Blog->dget( 'name', 'htmlattr' ) : $this->disp_params['alt'],
				);

			// Image width:
			$image_attrs['style'] = 'width:'.( empty( $this->disp_params['width'] ) ? 'auto' : format_to_output( $this->disp_params['width'], 'htmlattr' ) ).';';
			// Image height:
			$image_attrs['style'] .= 'height:'.( empty( $this->disp_params['height'] ) ? 'auto' : format_to_output( $this->disp_params['height'], 'htmlattr' ) ).';';
			// If no unit is specified in a size, consider the unit to be px:
			$image_attrs['style'] = preg_replace( '/(\d+);/', '$1px;', $image_attrs['style'] );

			$title .= '<img'.get_field_attribs_as_string( $image_attrs ).' />';
		}

		$title .= '</a>';

		// Display as a title:
		$this->disp_title( $title );

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
<?php
/**
 * This file implements the Image Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
	function image_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'image' );
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
				'width' => array(
					'label' => T_('Image width'),
					'note' => T_('pixels'),
					'type' => 'integer',
					'defaultvalue' => '300',
					'allow_empty' => true,
				),
				'height' => array(
					'label' => T_('Image height'),
					'note' => T_('pixels'),
					'type' => 'integer',
					'defaultvalue' => '',
					'allow_empty' => true,
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
		global $Blog;

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

		// Collection logo:
		echo $this->disp_params['block_start'];

		$image_attrs = '';
		if( ! empty( $this->disp_params['width'] ) )
		{ // Image width
			$image_attrs .= ' width="'.intval( $this->disp_params['width'] ).'"';
		}
		if( ! empty( $this->disp_params['height'] ) )
		{ // Image height
			$image_attrs .= ' height="'.intval( $this->disp_params['height'] ).'"';
		}

		echo '<a href="'.$Blog->get( 'url' ).'">'
							.'<img src="'.$image_url.$this->disp_params['image_file'].'" alt=""'.$image_attrs.' />'
							.'</a>';

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
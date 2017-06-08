<?php
/**
 * This file implements the Spacer Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
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
class spacer_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'spacer' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'spacer-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Spacer');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * @return string The block title, the first 60 characters of the block
	 *                content or an empty string.
	 */
	function get_short_desc()
	{
		return T_('Spacer');
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display a spacer with your width and height.');
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
				'width' => array(
					'label' => T_('Width'),
					'note' => T_('E-g: ').'40px, 2em, '.T_('etc.'),
				),
				'height' => array(
					'label' => T_('Height'),
					'note' => T_('E-g: ').'40px, 2em, '.T_('etc.'),
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
		$this->init_display( $params );

		$styles = array();

		// Width:
		$width = trim( $this->disp_params['width'] );
		if( is_numeric( $width ) )
		{	// Use pixels by default for numbers without units:
			$width .= 'px';
		}
		if( ! empty( $width ) )
		{
			$styles[] = 'width:'.$width;
		}

		// Height:
		$height = trim( $this->disp_params['height'] );
		if( is_numeric( $height ) )
		{	// Use pixels by default for numbers without units:
			$height .= 'px';
		}
		if( ! empty( $height ) )
		{
			$styles[] = 'height:'.$height;
		}

		$wrapper_html_tags = array(
			array( 'block_start', 'block_end' ),
			array( 'block_body_start', 'block_body_end' ),
			array( 'list_start', 'list_end' ),
			array( 'item_start', 'item_end' ),
		);

		// Print out wrapper start html tags:
		$wrapper_end_html_tags = '';
		$start_tag_is_detected = false;
		foreach( $wrapper_html_tags as $wrapper_html_tag )
		{
			$wrapper_start = $this->disp_params[ $wrapper_html_tag[0] ];
			if( strpos( $wrapper_start, '<' ) !== false )
			{	// Find first wrapper with html tag and append style attribute for it:
				$wrapper_start = update_html_tag_attribs( $wrapper_start, array( 'style' => implode( ';', $styles ) ) );
				$start_tag_is_detected = true;
			}
			echo $wrapper_start;
			$wrapper_end_html_tags = $this->disp_params[ $wrapper_html_tag[1] ].$wrapper_end_html_tags;
			if( $start_tag_is_detected )
			{	// If first html tag has been detected then don't touch others:
				break;
			}
		}

		if( ! $start_tag_is_detected )
		{	// If no html tag has been detected then use simple <div> instead:
			$styles[] = 'display:inline-block';
			echo '<div style="'.implode( ';', $styles ).'"></div>';
		}

		// Print out wrapper end html tags:
		echo $wrapper_end_html_tags;

		return true;
	}
}
?>
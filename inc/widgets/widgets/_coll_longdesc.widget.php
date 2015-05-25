<?php
/**
 * This file implements the Long description Widget class.
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
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_longdesc_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_longdesc_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_longdesc' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'long-description-of-collection-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Long Description of this Collection');
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
		global $Blog;
		return sprintf( T_('Long description from the blog\'s <a %s>general settings</a>.'),
						'href="?ctrl=coll_settings&tab=general&blog='.$Blog->ID.'"' );
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
					'label' => T_('Block title'),
					'note' => T_( 'Title to display in your skin. Use $title$ to display the collection title.' ),
					'size' => 40,
					'defaultvalue' => '',
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

		$this->init_display( $params );

		// Collection long description:
		echo $this->disp_params['block_start'];

		if( strpos( $this->disp_params['title'], '$title$' ) !== false )
		{ // Replace mask $title$ with real blog name with link to blog home page as it does widget coll_title
			$this->disp_params['title'] = str_replace( '$title$',
				'<a href="'.$Blog->get( 'url' ).'">'.$Blog->dget( 'name', 'htmlbody' ).'</a>', $this->disp_params['title'] );
		}

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		$Blog->disp( 'longdesc', 'htmlbody' );

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
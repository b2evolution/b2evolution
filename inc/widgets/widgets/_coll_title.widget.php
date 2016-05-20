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
class coll_title_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_title' );
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
				'add_tagline' => array(
					'label' => T_('Add tagline'),
					'note' => T_('check to add the collection tagline after the title.'),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'collection-title-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Collection title');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		global $Blog;

		return $Blog->dget( 'name', 'htmlbody' );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		global $Blog;
		return sprintf( T_('&laquo;%s&raquo; from the blog\'s <a %s>general settings</a>.'),
				'<strong>'.$Blog->dget('name').'</strong>', 'href="?ctrl=coll_settings&tab=general&blog='.$Blog->ID.'"' );
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

		// Collection title:
		echo $this->disp_params['block_start'];

		$title = '<a href="'.$Blog->get( 'url' ).'">'
							.$Blog->dget( 'name', 'htmlbody' )
							.'</a>';
		if( $this->disp_params['add_tagline'] )
		{ // Add a tagline after blog title
			$title .= ' <small>'.$Blog->dget( 'tagline', 'htmlbody' ).'</small>';
		}
		$this->disp_title( $title );

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
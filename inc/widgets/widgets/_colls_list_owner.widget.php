<?php
/**
 * This file implements the colls_list_owner Widget class.
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
class colls_list_owner_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function colls_list_owner_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'colls_list_owner' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'same-owner-collections-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Same owner\'s collections list');
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
		return T_('Display list of all blogs owned by the same user.');
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
					'defaultvalue' => T_('My blogs'),
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

		$this->disp_coll_list( 'owner', $this->disp_params['order_by'], $this->disp_params['order_dir'] );

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		return array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' =>'any', 					// Have the settings of ANY blog changed ? (ex: new skin here, new name on another)
			);
	}
}

?>
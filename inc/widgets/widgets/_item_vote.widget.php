<?php
/**
 * This file implements the item_vote_Widget class.
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
class item_vote_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_vote' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-vote-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Item Vote');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Vote') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display buttons to vote on item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $admin_url;

		$r = array_merge( array(
				'label' => array(
					'label' => T_('Label'),
					'size' => 40,
					'note' => '',
					'defaultvalue' => T_('My vote:'),
				),
				'note' => array(
					'label' => T_('Note'),
					'type' => 'info',
					'info' => sprintf( T_('To configure what buttons to show (Positive/Neutral/Negative), go to Post Features &gt; <a %s>Voting Options</a>'),
						'href="'.$admin_url.'?ctrl=coll_settings&tab=features&blog='.$this->coll_ID.'#fieldset_wrapper_voting_options"' ),
				),
				'display_summary' => array(
					'label' => T_('Show summary'),
					'note' => '',
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
							array( 'no', T_('No') ),
							array( 'replace', T_('Replace label after vote') ),
							array( 'always', T_('Always display after icons') ) ),
					'defaultvalue' => 'always',
				),
				'display_summary_author' => array(
					'label' => T_('Always show summary to author'),
					'size' => 40,
					'note' => T_('Author will always see a summary (since he can\'t vote).'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
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
		global $Collection, $Blog, $current_User, $DB;

		$this->init_display( $params );

		if( empty( $this->disp_params['Item'] ) )
		{	// Use current global Item:
			global $Item;
			$widget_Item = & $Item;
		}
		else
		{	// Use a specific Item:
			$widget_Item = & $this->disp_params['Item'];
		}

		if( empty( $widget_Item ) || ! $widget_Item->can_vote() )
		{	// Don't display the voting panel if a voting on the item is not allowed by some reason:
			return;
		}

		echo add_tag_class( $this->disp_params['block_start'], 'clearfix' );
		echo $this->disp_params['block_body_start'];

		$this->display_voting_panel( $widget_Item, $params );

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Display a panel with voting buttons
	 *
	 * @param object Item
	 * @param array Params
	 */
	function display_voting_panel( $Item, $params = array() )
	{
		$this->init_display( $params );

		// Display buttons to vote on item:
		$Item->display_voting_panel( array_merge( array(
				'label_text'             => $this->disp_params['label'],
				'display_summary'        => $this->disp_params['display_summary'],
				'display_summary_author' => $this->disp_params['display_summary_author'],
				'widget_ID'              => $this->ID,
			), $params ) );
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $current_User;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
			);
	}
}

?>
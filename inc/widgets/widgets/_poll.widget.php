<?php
/**
 * This file implements the Poll Widget class.
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
class poll_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function poll_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'poll' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'poll-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_( 'Poll' );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display poll.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array local params
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
				'label' => T_( 'Block title' ),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_( 'Quick poll' ),
			),
			'poll_ID' => array(
				'label' => T_('Poll ID'),
				'type' => 'integer',
				'size' => 11,
				'defaultvalue' => '',
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

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		$PollCache = & get_PollCache();
		$Poll = $PollCache->get_by_ID( $this->disp_params['poll_ID'], false, false );

		if( ! $Poll )
		{	// We cannot find a poll by the entered ID in widget settings:
			echo '<p class="red">'.sprintf( T_('Poll #%s not found.'), '<b>'.format_to_output( $this->disp_params['poll_ID'], 'text' ).'</b>' ).'</p>';
		}
		else
		{	// Display a form for voting on poll:
			echo $Poll->get( 'question_text' );

			$poll_options = $Poll->get_poll_options();
			if( count( $poll_options ) )
			{	// Display a form only if at least one poll option exists:
				$Form = new Form();
				$Form->begin_form();
				echo '<table class="poll_table">';
				foreach( $poll_options as $PollOption )
				{
					echo '<tr>';
					echo '<td><input type="radio" id="poll_answer_'.$PollOption->ID.'" name="poll_answer" /></td>';
					echo '<td><label for="poll_answer_'.$PollOption->ID.'">'.$PollOption->get( 'option_text' ).'</label></td>';
					echo '</tr>';
				}
				echo '</table>';
				$Form->button( array( 'submit', 'submit', T_('Vote'), 'SaveButton' ) );
				$Form->end_form();
			}
			else
			{	// Display this red message to inform admin to create the poll options:
				echo '<p class="red">'.T_('This poll doesn\'t contain any answer.').'</p>';
			}
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}
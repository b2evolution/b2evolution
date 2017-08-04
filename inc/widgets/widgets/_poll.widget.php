<?php
/**
 * This file implements the Poll Widget class.
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
class poll_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'poll' );
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
			echo '<p>'.$Poll->get( 'question_text' ).'</p>';

			$poll_options = $Poll->get_poll_options();
			if( count( $poll_options ) )
			{	// Display a form only if at least one poll option exists:
				if( is_logged_in() )
				{	// Set form action to vote if current user is logged in:
					$form_action = get_htsrv_url().'action.php?mname=polls';
				}
				else
				{	// Set form action to log in:
					$form_action = get_login_url( 'poll widget' );
				}

				$Form = new Form( $form_action );

				$Form->begin_form();

				if( is_logged_in() )
				{	// Set the hidden fields for voting only when user is logged in:
					$Form->add_crumb( 'polls' );
					$Form->hidden( 'action', 'vote' );
					$Form->hidden( 'poll_ID', $Poll->ID );
				}

				// Get the option ID if current user already voted on this poll question:
				$user_vote_option_ID = $Poll->get_user_vote();

				if( $user_vote_option_ID )
				{	// Get max percent:
					$max_poll_options_percent = $Poll->get_max_poll_options_percent();
				}

				echo '<table class="evo_poll__table">';
				foreach( $poll_options as $poll_option )
				{
					echo '<tr>';
					echo '<td class="evo_poll__selector"><input type="radio" id="poll_answer_'.$poll_option->ID.'"'
							.' name="poll_answer" value="'.$poll_option->ID.'"'
							.( $user_vote_option_ID == $poll_option->ID ? ' checked="checked"' : '' ).' /></td>';
					echo '<td class="evo_poll__title"><label for="poll_answer_'.$poll_option->ID.'">'.$poll_option->option_text.'</label></td>';
					if( $user_vote_option_ID )
					{	// If current user already voted on this poll, Display the voting results:
						// Calculate a percent for style relating on max percent:
						$style_percent = $max_poll_options_percent > 0 ? ceil( $poll_option->percent / $max_poll_options_percent * 100 ) : 0;
						echo '<td class="evo_poll__percent_bar"><div><div style="width:'.$style_percent.'%"></div></div></td>';
						echo '<td class="evo_poll__percentage">'.$poll_option->percent.'%</td>';
					}
					echo '</tr>';
				}
				echo '</table>';

				if( is_logged_in() )
				{	// Display a button to vote:
					$Form->button( array( 'submit', 'submit',
							( $user_vote_option_ID ? T_('Change vote') : T_('Vote') ),
							'SaveButton'.( $user_vote_option_ID ? ' btn-default' : '' ) ) );
				}
				else
				{	// Display a button to log in:
					$Form->button( array( 'submit', 'submit', T_('Log in'), 'SaveButton btn-success' ) );
				}

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
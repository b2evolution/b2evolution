<?php
/**
 * This file implements the Messages class for displaying messages about performed actions.
 *
 * It additionally provides the class Log_noop that implements the same (used) methods, but as
 * no-operation functions. This is useful to create a more resource friendly object when
 * you don't need it (think Debuglog).
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 *
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Messages class. For displaying notes, successful actions & errors.
 *
 * @todo CLEAN UP A LOT because of previous over factorization with Log class.
 *
 * Messages can be logged into different categories (aka levels)
 * Examples: 'note', 'error'. Note: 'all' is reserved to display all categories together.
 * Messages can later be displayed grouped by category/level.
 *
 * @package evocore
 */
class Messages
{
	/**
	 * The stored messages text.
	 * array of Strings
	 *
	 * @var array
	 */
	var $messages_text = array();

	/**
	 * The stored messages type.
	 * array of Strings
	 *
	 * @var array
	 */
	var $messages_type = array();

	/**
	 * The number of messages
	 *
	 * @var integer
	 */
	var $count = 0;

	/**
	 * Error message was added or not.
	 *
	 * @var boolean
	 */
	var $has_errors = false;

	/**
	 * Params
	 *
	 * @var array
	 */
	var $params = array();

	/**
	 * Indicates message group is open or not.
	 *
	 * @var boolean
	 */
	var $message_group_open = false;

	/**
	 * The  message group header.
	 *
	 * @var string
	 */
	var $message_group_header = null;

	/**
	 * The stored message group text.
	 *
	 * @var array of Strings
	 */
	var $message_group_text = array();

	/**
	 * The current group message type
	 *
	 * @var string
	 */
	var $message_group_type = 'error';

	/**
	 * Display message group header even if the group is empty (no group item).
	 * Even when set to True, the message group will still not be displayed if
	 * the message group header is also empty.
	 *
	 * @var boolean
	 */
	var $display_empty_group = false;

	/**
	 * The number of messages in the current group
	 *
	 * @var integer
	 */
	var $group_count = 0;


	/**
	 * Constructor.
	 *
	 * @param array Params
	 */
	function __construct( $params = array() )
	{
		// Default params of messages
		$this->params = array_merge( array(
				'class_success'  => 'log_success',
				'class_warning'  => 'log_warning',
				'class_error'    => 'log_error',
				'class_note'     => 'log_note',
				'before_message' => '',
				'before_success' => '',
				'after_success'  => '',
				'before_warning' => '',
				'after_warning'  => '',
				'before_error'   => '',
				'after_error'    => '',
				'before_note'    => '',
				'after_note'     => '',
				'before_group'   => '<ul class="message_group">',
				'after_group'    => '</ul>',
				'before_group_item' => '<li>',
				'after_group_item'  => '</li>'
			), $params );
	}

	/**
	 * Clears messages content
	 */
	function clear()
	{
		$this->messages_text = array();
		$this->messages_type = array();
		$this->count = 0;
		$this->has_errors = false;

		$this->message_group_open = false;
		$this->message_group_header = NULL;
		$this->message_group_text = array();
		$this->message_group_type = 'error';
		$this->group_count = 0;
	}


	/**
	 * Add a message.
	 *
	 * @param string the message
	 * @param string the message type, it can have this values: 'success', 'warning', 'error', 'note'
	 */
	function add( $text, $type = 'error' )
	{
		$this->close_group();

		$this->messages_text[$this->count] = $text;
		$this->messages_type[$this->count] = $type;
		$this->count++;
		if( !$this->has_errors )
		{
			$this->has_errors = ( $type == 'error' );
		}
	}


	/**
	 * Add a Messages object to this.
	 *
	 * @param Messages object
	 */
	function add_messages( $p_Messages )
	{
		$this->count = $this->count + $p_Messages->count;
		for( $i = 0; $i < $p_Messages->count; $i++ )
		{
			$this->messages_text[] = $p_Messages->messages_text[$i];
			$this->messages_type[] = $p_Messages->messages_type[$i];
			if( !$this->has_errors )
			{
				$this->has_errors = ( $p_Messages->messages_type[$i] == 'error' );
			}
		}

		if( $this->has_open_group() )
		{	// Check if this is still the same group by comparing the first text of message group, message group type and display empty group values
			$same_group = ( $this->message_group_header === $p_Messages->message_group_header )	&& ( $this->message_group_type == $p_Messages->message_group_type );

			if( ! $same_group )
			{ // No longer the same message group, close the previous group and append to current message queue
				$r = $p_Messages->close_group( true );
				$this->add( $r[0], $r[1] );
			}
			else
			{ // Append group messages
				$this->group_count += $p_Messages->group_count;
				for( $i = 0; $i < $p_Messages->group_count; $i++ )
				{
					$this->message_group_text[] = $p_Messages->message_group_text[$i];
				}
			}
		}
		else
		{ // No open group, just copy the other object's group values
			$this->message_group_open = $p_Messages->message_group_open;
			$this->message_group_header = $p_Messages->message_group_header;
			$this->message_group_text = $p_Messages->message_group_text;
			$this->message_group_type = $p_Messages->message_group_type;
			$this->group_count = $p_Messages->group_count;
		}
	}


	/**
	 * Closes any open group and start a new group
	 *
	 * @param string the group header/title
	 * @param string the message type, it can have this values: 'success', 'warning', 'error', 'note'
	 */
	function start_group( $header = NULL, $type = 'error' )
	{
		$this->close_group();

		$this->message_group_header = $header;
		$this->message_group_type = $type;
		$this->message_group_open = true;
	}


	/**
	 * Add a group item message
	 *
	 * @param string the message
	 * @param string the message type, it can have this values: 'success', 'warning', 'error', 'note'
	 * @param string the group header/title
	 * @param boolean closes any open group and start a new group
	 */
	function add_to_group( $text, $type = 'error', $header = NULL, $force_new_group = false )
	{
		if( $force_new_group || ( $this->message_group_open && ( ( $this->message_group_type != $type ) || ( $this->message_group_header != $header ) ) ) )
		{
			$this->close_group();
		}

		$this->message_group_header = $header;
		$this->message_group_type = $type;
		$this->message_group_text[$this->group_count] = $text;
		$this->group_count++;
		$this->message_group_open = true;
	}


	/**
	 * Closes the current message group and add to message queue.
	 *
	 * @param boolean True to only output message group but not add it to the message queue
	 * @return mixed NULL if there is no open group messag otherwise an array of group message and message type of current group
	 */
	function close_group( $output = false )
	{
		if( $this->has_open_group() )
		{
			if( $this->group_count === 1 && empty( $this->message_group_header ) )
			{ // There is only a single group item and no header text to display, display as single line message
				$message = $this->message_group_text[0];
			}
			else
			{
				$message = $this->params['before_group'].$this->message_group_header;
				for( $i = 0; $i < $this->group_count; $i++ )
				{
					$message .= $this->params['before_group_item'].$this->message_group_text[$i].$this->params['after_group_item'];
				}
				$message .= $this->params['after_group'];
			}

			// Clear message group
			$this->message_group_open = false;
			$this->message_group_header = NULL;
			$this->message_group_text = array();
			$this->group_count = 0;

			$this->add( $message, $this->message_group_type );

			return array( $message, $this->message_group_type );
		}

		return NULL;
	}


	/**
	 * Check if there is currently an open message group
	 *
	 * @return boolean
	 */
	function has_open_group()
	{
		return $this->message_group_open;
	}


	/**
	 * TEMPLATE TAG
	 *
	 * The purpose here is to have a tag which is simple yet flexible.
	 * the display function is WAAAY too bloated.
	 *
	 * @todo optimize
	 *
	 * @param string HTML to display before the log when there is something to display
	 * @param string HTML to display after the log when there is something to display
	 */
	function disp( $before = '<div class="action_messages">', $after = '</div>' )
	{
		if( $this->has_open_group() )
		{
			$this->close_group();
		}

		if( $this->count )
		{
			$disp = $this->display( NULL, NULL, false, NULL );

			if( !empty( $disp ) )
			{
				echo $before.$disp.$after;
			}
		}
	}


	/**
	 * Display messages of the object.
	 *
	 * - You can output/get the messages
	 * - Head/Foot will be displayed on top/bottom of the messages.
	 * - You can suppress the outer div or set a css class for it (defaults to
	 *   'log_container').
	 *
	 * @todo Make this simple!
	 * start by getting rid of the $category selection and the special cases for 'all'. If you don't want to display ALL messages,
	 * then you should not log them in the same Log object and you should instantiate separate logs instead.
	 *
	 * @param string|NULL Header/title
	 * @param string|NULL Footer
	 * @param boolean to display or return (default: display)
	 * @param mixed the outer div, may be false
	 * @return boolean false, if no messages; else true (and outputs if $display)
	 */
	function display( $head = NULL, $foot = NULL, $display = true, $outerdivclass = 'log_container' )
	{
		if( $this->has_open_group() )
		{
			$this->close_group();
		}

		if( $this->count == 0 ) {
			return false;
		}

		$disp = '';

		if( isset( $this->params['class_outerdiv'] ) && $outerdivclass == 'log_container' )
		{ // Use default class from object params instead of default function param
			$outerdivclass = $this->params['class_outerdiv'];
		}

		if( $outerdivclass )
		{
			$disp .= "\n<div class=\"$outerdivclass\">";
		}

		if( !empty( $head ) )
		{
			$disp .= '<h3>'.$head.'</h3>';
		}

		$disp .= '<ul>';
		for( $i = 0; $i < $this->count; $i++ )
		{
			$class = isset( $this->params['class_'.$this->messages_type[$i]] ) ?
					$this->params['class_'.$this->messages_type[$i]] :
					$this->params['class_note'];
			$disp .= "<li>\t<div class=\"{$class}\"".'>'
					.$this->params['before_'.$this->messages_type[$i]]
					.$this->params['before_message']
					.$this->messages_text[$i]
					.$this->params['after_'.$this->messages_type[$i]]
				."</div></li>\n";
		}
		$disp .= '</ul>';

		if( !empty( $foot ) )
		{
			$disp .= "\n<p>".$foot."</p>";
		}

		if( $outerdivclass )
		{
			$disp .= "</div>\n";
		}

		if( $display )
		{
			echo $disp;
			return true;
		}
		return $disp;
	}


	/**
	 * Concatenates messages of a given category to a string
	 *
	 * @param string prefix of the string
	 * @param string suffic of the string
	 * @param string the glue
	 * @param string result format
	 * @return string the messages, imploded. Tags stripped.
	 */
	function get_string( $head = '', $foot = '', $implodeBy = ', ', $format = 'striptags' )
	{
		if( $this->has_open_group() )
		{
			$this->close_group();
		}

		if( !$this->count )
		{
			return false;
		}

		$r = '';
		if( '' != $head )
		{
			$r .= $head.' ';
		}
		$r .= implode( $implodeBy, $this->messages_text );
		if( '' != $foot )
		{
			$r .= ' '.$foot;
		}

		switch( $format )
		{
			case 'xmlrpc':
				$r = strip_tags( $r );	// get rid of <code>
				$r = str_replace( '&lt;', '<', $r );
				$r = str_replace( '&gt;', '>', $r );
				$r = str_replace( '&quot;', '"', $r );
				break;

			case 'striptags':
				$r = strip_tags( $r );
				break;
		}

		return $r;
	}


	/**
	 * Get the number of messages
	 *
	 * @return number of messages
	 */
	function count()
	{
		if( $this->has_open_group() )
		{
			return $this->count + 1;
		}
		else
		{
			return $this->count;
		}
	}


	/**
	 * Has error message in current object
	 *
	 * @return boolean true if error message was added, false otherwise
	 */
	function has_errors()
	{
		if( $this->has_open_group() )
		{
			return $this->has_errors || $this->message_group_type == 'error';
		}
		else
		{
			return $this->has_errors;
		}
	}


	/**
	 * Set params for messages
	 *
	 * @param array Params
	 */
	function set_params( $params = array() )
	{
		if( ! empty( $params ) )
		{ // Change default params
			$this->params = array_merge( $this->params, $params );
		}
	}
}

?>
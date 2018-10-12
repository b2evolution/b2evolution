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
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
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
	 * The stored messages
	 * array of Array
	 *
	 * @var array
	 */
	var $messages = array();

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
	 * @deprecated
	 */
	var $messages_type = array();

	/**
	 * Message counters
	 *
	 * @var array
	 */
	var $counters = array();

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
	 * @deprecated
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
	 * Affix the displayed messages. Works in conjunction with template function
	 * init_affix_messages_js().
	 *
	 * @var boolean
	 */
	var $affixed = false;

	/**
	 * Marks added messages as suppressed
	 *
	 * @var boolean
	 */
	var $suppressed = false;


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
		$this->messages = array();
		$this->messages_text = array();
		$this->messages_type = array();
		$this->count = 0;
		$this->counters = array();
		$this->has_errors = false;

		$this->message_group_open = false;
		$this->message_group_header = NULL;
		$this->message_group_text = array();
		$this->message_group_type = 'error';
		$this->group_count = 0;
	}

	function increment_counter( $type )
	{
		if( isset( $this->counters[$type] ) )
		{
			$this->counters[$type]++;
		}
		else
		{
			$this->counters[$type] = 1;
		}

		return $this->counters[$type];
	}

	function get_count( $type )
	{
		if( isset( $this->counters[$type] ) )
		{
			return $this->counters[$type];
		}
		else
		{
			return 0;
		}
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

		$this->messages_text[$this->get_count( 'msg' )] = $text;
		$this->messages_type[$this->get_count( 'msg' )] = $type;
		$this->count++;
		$this->increment_counter( 'msg' );
		$this->increment_counter( $type );
		if( $this->suppressed )
		{
			$this->increment_counter( 'suppressed' );
		}
		$this->messages[] = array(
				'entry' => 'message',
				'type' => $type,
				'text' => $text,
				'count' => $this->get_count( 'msg' ),
				'suppressed' => $this->suppressed,
				//'header' => $this->message_group_header,
			);

		if( !$this->has_errors )
		{
			$this->has_errors = ( $type == 'error' );
		}
	}


	/**
	 * Prepend Messages object to this.
	 *
	 * @param Messages object
	 */
	function prepend_messages( $p_Messages )
	{
		// Cleanup $p_Messages last message entry and/or $this first message entry:
		if( count( $this->messages ) )
		{
			$p_last_entry = $p_Messages->messages[count( $p_Messages->messages ) - 1];
			if( $p_Messages->has_open_group() || $p_last_entry['entry'] == 'end_group' )
			{
				$p_header = $p_Messages->has_open_group() ? $p_Messages->message_group_header : $p_last_entry['header'];
				$p_type   = $p_Messages->has_open_group() ? $p_Messages->message_group_type : $p_last_entry['type'];

				if( $this->messages[0]['entry'] == 'start_group' )
				{
					if( ( $this->messages[0]['header'] === $p_header ) && ( $this->messages[0]['type'] === $p_type_) )
					{	// Same group, remove $this start group entry:
						unset( $this->messages[0] );

						if( $p_last_entry['entry'] == 'end_group' )
						{	// Remove $p_Messages end group entry
							unset( $p_Messages->messages[count($p_Messages->messages) - 1] );
						}
					}
					else
					{	// Close previous group:
						$p_Messages->messages[] = array( 'entry' => 'end_group', 'header' => $p_header, 'type' => $p_type );
					}
				}
				elseif( $p_Messages->has_open_group() )
				{	// Close previous group:
					$p_Messages->messages[] = array( 'entry' => 'end_group', 'header' => $p_header, 'type' => $p_type );
				}
			}
		}
		else
		{
			$this->message_group_open   = $p_Messages->message_group_open;
			$this->message_group_header = $p_Messages->message_group_header;
		}

		$this->messages    = $p_Messages->messages + $this->messages;
		$this->count       = $this->count + $p_Messages->count;
		$sums = array();
		foreach( array_keys( $this->counters + $p_Messages->counters ) as $key )
		{
			$sums[$key] = ( isset( $this->counters[$key] ) ? $this->counters[$key] : 0 ) + ( isset( $p_Messages->counters[$key] ) ? $p_Messages->counters[$key] : 0 );
		}
		$this->counters = $sums;
		$this->group_count = $this->group_count + $p_Messages->group_count;

		if( ! $this->has_errors )
		{
			$this->has_errors = $p_Messages->has_errors;
		}
	}


	/**
	 * Closes any open group and start a new group
	 *
	 * @param string the group header/title
	 * @param string the message type, it can have this values: 'success', 'warning', 'error', 'note'
	 */
	function start_group( $header = NULL, $type = 'error', $close_previous = true )
	{
		if( $close_previous )
		{
			$this->close_group();
		}

		$this->messages[] = array( 'entry' => 'start_group', 'header' => $header, 'type' => $type );

		$this->group_count++;
		$this->message_group_header = $header;
		$this->message_group_type   = $type;
		$this->message_group_open   = true;
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

		if( ! $this->message_group_open )
		{
			$this->start_group( $header, $type );
		}

		$this->messages_text[$this->count] = $text;

		$this->count++;
		$count = $this->increment_counter( 'msg' );
		$this->increment_counter( $type );
		if( $this->suppressed )
		{
			$this->increment_counter( 'suppressed' );
		}
		$this->messages[] = array(
				'entry' => 'message',
				'type' => $type,
				'text' => $text,
				'count' => $count,
				'suppressed' => $this->suppressed,
				//'header' => $this->message_group_header,
			);

		if( !$this->has_errors )
		{
			$this->has_errors = ( $type == 'error' );
		}
	}


	/**
	 * Closes the current message group and add to message queue.
	 *
	 * @param boolean True to only output message group but not add it to the message queue
	 */
	function close_group( $output = false )
	{
		if( $this->has_open_group() )
		{
			$this->messages[] = array(
					'entry' => 'end_group',
					'header' => $this->message_group_header,
					'type' => $this->message_group_type
				);

			// Clear message group
			$this->message_group_open = false;
			$this->message_group_header = NULL;
			$this->message_group_text = array();
		}
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

		if( $this->affixed )
		{	// Add "affixed_messages" class to $before param
			$old_before = $before;
			$before = add_tag_class( $before, 'affixed_messages' );
			if( $old_before == $before )
			{	// No tag to add class to, wrap in DIV with "affixed_messages" class:
				$before = '<div class="affixed_messages">'.$before;
				$after = $after.'</div>';
			}
		}

		if( $this->get_count( 'msg' ) )
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
	 * @param boolean display suppressed messages
	 * @return boolean false, if no messages; else true (and outputs if $display)
	 */
	function display( $head = NULL, $foot = NULL, $display = true, $outerdivclass = 'log_container', $display_suppressed = false )
	{
		if( $this->has_open_group() )
		{
			$this->close_group();
		}

		if( $this->get_count( 'msg' ) == 0 ) {
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
		$in_group = false;
		$group_data = NULL;
		$group_messages = array();
		$group_msg_counter = 0;
		foreach( $this->messages as $message )
		{
			switch( $message['entry'] )
			{
				case 'start_group':
					$in_group = true;
					$group_data = $message;
					break;

				case 'end_group':
					if( $in_group )
					{
						if( ! empty( $group_messages ) && ! empty( $group_data ) )
						{
							$class = isset( $this->params['class_'.$group_data['type']] ) ? $this->params['class_'.$group_data['type']] : $this->params['class_note'];
							if( $group_msg_counter === 1 && empty( $group_data['header'] ) )
							{
								$disp .= '<li><div class="'.$class.'">'
										.$this->params['before_'.$message['type']]
										.$this->params['before_message']
										.$group_messages[0]
										.$this->params['after_'.$message['type']]
										.'</div></li>';
							}
							else
							{
								$disp .= '<li><div class="'.$class.'">'
										.$this->params['before_'.$group_data['type']]
										.$this->params['before_message']
										.$this->params['before_group'].$group_data['header'];

								foreach( $group_messages as $msg )
								{
									$disp .= $this->params['before_group_item'].$msg.$this->params['after_group_item'];
								}

								$disp .= $this->params['after_group']
										.$this->params['after_'.$group_data['type']]
										.'</div></li>';
							}
						}
					}
					$in_group          = false;
					$group_data        = NULL;
					$group_messages    = array();
					$group_msg_counter = 0;
					break;

				case 'message':
					if( ! $message['suppressed'] || $display_suppressed )
					{
						if( $in_group )
						{
							$group_messages[] = $message['text'];
							$group_msg_counter++;
						}
						else
						{
							$class = isset( $this->params['class_'.$message['type']] ) ? $this->params['class_'.$message['type']] : $this->params['class_note'];
							$disp .= '<li><div class="'.$class.'">'
									.$this->params['before_'.$message['type']]
									.$this->params['before_message']
									.$message['text']
									.$this->params['after_'.$message['type']]
									.'</div></li>';
						}
					}
					break;
			}
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

		/*
		pre_dump( array(
			'messages' => $this->messages,
			'string' => $this->get_string(),
			'msg_count' => $this->count,
			'grp_count' => $this->group_count,
		) );
		*/

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
	 * @param string suffix of the string
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

		if( !$this->get_count( 'msg' ) )
		{
			return false;
		}

		$r = '';
		if( '' != $head )
		{
			$r .= $head.' ';
		}

		$messages = array();
		foreach( $this->messages as $msg )
		{
			if( $msg['entry'] == 'message' && ! $msg['suppressed'] )
			{
				$messages[] = $msg['text'];
			}
		}
		$r .= implode( $implodeBy, $messages );

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
	function count( $type = NULL, $include_suppressed = false )
	{
		$count = 0;
		if( is_null( $type ) )
		{
			$count = isset( $this->counters['msg'] ) ? $this->counters['msg'] : 0;
		}
		else
		{
			$count = isset( $this->counters[$type] ) ? $this->counters[$type] : 0;
		}

		if( ! $include_suppressed )
		{
			$suppressed_count = isset( $this->counters['suppressed'] ) ? $this->counters['suppressed'] : 0;
			$count = $count - $suppressed_count;
		}

		return $count;
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
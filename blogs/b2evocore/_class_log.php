<?php
/**
 * This file implements Logging of notes and errors.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
 */

class Log
{
	var $messages;

	/**
	 * Constructor.
	 *
	 * @param string sets default level
	 */
	function Log( $level = 'error' )
	{
		$this->defaultlevel = $level;

		// create the array for this level
		$this->messages[$level] = array();
	}


	/**
	 * clears the Log
	 *
	 * @param string level, use 'all' to unset all levels
	 */
	function clear( $level = '#' )
	{
		if( $level == 'all' )
		{
			unset( $this->messages );
		}
		else
		{
			if( $level == '#' )
			{
				$level = $this->defaultlevel;
			}
			unset( $this->messages[ $level ] );
		}
	}


	/**
	 * add a message to the Log
	 *
	 * @param string the message
	 * @param string the level, default is to use the object's default level
	 */
	function add( $message, $level = '#' )
	{
		if( $level == '#' )
		{
			$level = $this->defaultlevel;
		}

		$this->messages[ $level ][] = $message;
	}


	/**
	 * display messages of the Log object.
	 *
	 * @param string header/title
	 * @param string footer
	 * @param boolean to display or return
	 * @param string the level of messages to use
	 * @param string the style to use, '<ul>' (with <li> for every message) or everything else for '<br />'
	 */
	function display( $head, $foot, $display = true, $level = '#', $style = '<ul>' )
	{
		$messages = $this->messages( $level );
		
		if( !count($messages) )
			return false;
		
		if( $level == '#' )
			$level = $this->defaultlevel;

		$class = 'log_'.$level;
			
		$disp = "\n<div class=\"$class\">";
		
		if( !empty($head) )
			$disp .= '<p class="'.$class.'">'.$head.'</p>';
			
		if( $style == '<ul>' )
		{
			if( count($messages) == 1 )
				$style = '<br>';
			else $disp .= '<ul class="log">';
		}
		
		if( $style != '<ul>' )
			$disp .= '<p>';
		
		foreach( $messages as $message )
		{
			if( $style == '<ul>' )
				$disp .= '<li>'.$message.'</li>';
			else
				$disp .= $message.'<br />';
		}
		
		// close list
		$disp .= ( $style == '<ul>' ) ? '</ul>' : '</p>';
		
		if( !empty($foot) )
			$disp .= '<p class="'.$class.'">'.$foot.'</p>';
			
		$disp .= '</div>';

		if( $display )
		{
			echo $disp;
			return true;
		}

		return $disp;
	}


	/**
	 * Concatenates messages of a given level to a string
	 *
	 * @param string prefix of the string
	 * @param string suffic of the string
	 * @param string the level
	 * @return string the messages, imploded. Tags stripped.
	 */	
	function string( $head, $foot, $level = '#' )
	{
		if( !$this->count( $level ) )
		{
			return false;
		}
		
		$r = '';
		if( '' != $head )
			$r .= $head.' ';
		$r .= implode(', ', $this->messages($level));
		if( '' != $foot )
			$r .= ' '.$foot;
		
		return strip_tags( $r );
	}


	/**
	 * counts messages of a given level
	 *
	 * @param string the level
	 * @return number of messages
	 */
	function count( $level = '#' )
	{
		return count( $this->messages($level) );
	}


	/**
	 * returns array of messages of that level
	 *
	 * @param string the level
	 * @return array of messages, one-dimensional for a specific level, two-dimensional for level 'all'
	 */
	function messages( $level = '#' )
	{
		$messages = array();
		
		if( $level == 'all' )
		{
			foreach( $this->messages as $llevel => $lmsgs )
			{
				foreach( $lmsgs as $lmsg )
				{
					$messages[$llevel][] = $lmsg;
				}
			}
		}
		else
		{
			if( $level == '#' )
			{
				$level = $this->defaultlevel;
			}
	
			if( isset($this->messages[$level]) )
			{ // we have messages for this level
				$messages = $this->messages[$level];
			}
		}

		return $messages;
	}

}

?>

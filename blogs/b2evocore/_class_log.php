<?php
/**
 * This file implements the Log class, which logs notes and errors. {{{
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$ }}}
 *
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
	 * @param string header/title (default: empty)
	 * @param string footer (default: empty)
	 * @param boolean to display or return (default: true)
	 * @param string the level of messages to use (default: set by constructor)
	 * @param string the CSS class of the outer <div> (default: 'log_'.$level)
	 * @param string the style to use, '<ul>', '<p>', '<br>' (default: <br> for single message, <ul> for more)
	 * @return mixed false, if no messages; else true/output
	 */
	function display( $head = '', $foot = '', $display = true, $level = NULL, $cssclass = NULL, $style = NULL )
	{
		static $levelAllRecurse = false;

		$messages = & $this->messages( $level ); // we get an subarray for each level (if level == 'all')
		if( !count($messages) )
		{
			return false;
		}

		if( $level == 'all' && count($messages) == 1 )
		{ // only one level has messages, leave 'all' mode
			list( $level, $messages ) = each($messages);
		}

		if( $level === NULL )
		{
			$level = $this->defaultlevel;
		}

		if( $cssclass === NULL )
		{
			$cssclass = 'log_'.$level;
		}

		$disp = "\n<div class=\"$cssclass\">";
		if( !$levelAllRecurse )
		{
			if( !empty($head) )
			{ // header
				$disp .= '<h2>'.$head.'</h2>';
			}
		}

		if( $level == 'all' )
		{
			$levelAllRecurse = true;
			foreach( $messages as $llevel => $lmessages )
			{
				$disp .= $this->display( '', '', false, $llevel, NULL, $style );
			}
			$levelAllRecurse = false;
		}
		else
		{
			if( $style == NULL )
			{ // '<br>' for a single message, '<ul>' for more
				$style = count($messages) == 1 ? '<br>' : '<ul>';
			}

			// implode messages
			if( $style == '<ul>' )
			{
				$disp .= '<ul class="'.$cssclass.'"><li>'.implode( "</li>\n<li>", $messages ).'</li></ul>';
			}
			elseif( $style == '<p>' )
			{
				$disp .= '<p class="'.$cssclass.'">'.implode( "</p>\n<p>", $messages ).'</p>';
			}
			else
			{
				$disp .= implode( '<br />', $messages );
			}
		}

		if( !$levelAllRecurse )
		{
			if( !empty($foot) )
			{
				$disp .= '<p>'.$foot.'</p>';
			}
		}
		$disp .= '</div>';

		if( $display )
		{
			echo $disp;
			return true;
		}

		return $disp;
	}


	/**
	 * Wrapper for {@link display}: use header/footer dependent on message count
	 * (one or more).
	 *
	 * @param string header/title (if one message)
	 * @param string header/title (if more than one message)
	 * @param string footer (if one message)
	 * @param string footer (if more than one message)
	 * @param boolean to display or return (default: true)
	 * @param string the level of messages to use (default: set by constructor)
	 * @param string the CSS class of the outer <div> (default: 'log_'.$level)
	 * @param string the style to use, '<ul>', '<p>', '<br>' (default: <br> for single message, <ul> for more)
	 * @return mixed false, if no messages; else true/output
	 */
	function display_cond( $head1 = '', $head2 = '', $foot1 = '', $foot2 = '', $display = true, $level = NULL, $cssclass = NULL, $style = '<ul>' )
	{
		switch( $this->count( $level ) )
		{
			case 0:
				return false;

			case 1:
				return $this->display( $head1, $foot1, $display, $level, $cssclass, $style );

			default:
				return $this->display( $head2, $foot2, $display, $level, $cssclass, $style );
		}
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
		$r .= implode(', ', $this->messages( $level, true ));
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
		return count( $this->messages( $level, true ) );
	}


	/**
	 * returns array of messages of that level
	 *
	 * @param string the level
	 * @param boolean force one dimension
	 * @return array of messages, one-dimensional for a specific level, two-dimensional for level 'all' (depends on second param)
	 */
	function messages( $level = NULL, $forceonedimension = false )
	{
		$messages = array();

		if( $level === NULL )
		{
			$level = $this->defaultlevel;
		}

		// sort by level ('error' above 'note')
		$ksortedmessages = $this->messages;
		ksort( $ksortedmessages );

		if( $level == 'all' )
		{
			foreach( $ksortedmessages as $llevel => $lmsgs )
			{
				foreach( $lmsgs as $lmsg )
				{
					if( $forceonedimension )
					{
						$messages[] = $lmsg;
					}
					else
					{
						$messages[$llevel][] = $lmsg;
					}
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
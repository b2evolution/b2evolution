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
	 * Get header/footer for a specific level, used by {@link display()}
	 *
	 * @param mixed head or foot (array [ level => head/foot or string [for container])
	 * @param string the level (or container)
	 * @param string template, where the head/foot gets used (sprintf)
	 */
	function getHeadFoot( $headfoot, $level, $template )
	{
		$r = '';
		if( is_array($headfoot) )
		{ // Container-Head
			$r = isset($headfoot[$level]) ? $headfoot[$level] : '';
		}
		elseif( !empty($headfoot) && $level == 'container' )
		{ // header
			$r = $headfoot;
		}
		if( !empty( $r ) )
		{
			return sprintf( $template, $r );
		}
		return false;
	}

	/**
	 * Display messages of the Log object.
	 *
	 * @param string header/title (default: empty), might be array ( level => msg ),
	 *               'container' is then top
	 * @param string footer (default: empty), might be array ( level => msg ),
	 *               'container' is then bottom
	 * @param boolean to display or return (default: true)
	 * @param mixed the level of messages to use (level, 'all', or list of levels (array)
	 * @param string the CSS class of the outer <div> (default: 'log_'.$level)
	 * @param string the style to use, '<ul>', '<p>', '<br>' (default: <br> for single message, <ul> for more)
	 * @return boolean false, if no messages; else true (and outputs)
	 */
	function display( $head = '', $foot = '', $display = true, $level = NULL, $cssclass = NULL, $style = NULL )
	{
		if( $level === NULL )
		{
			$level = $this->defaultlevel;
		}
		$disp = '';

		$messages = & $this->getMessages( $level );
		if( !count($messages) )
		{
			return false;
		}

		$disp .= "\n<div class=\"log_container\">";

		$disp .= $this->getHeadFoot( $head, 'container', '<h2>%s</h2>' );

		foreach( $messages as $llevel => $lmessages )
		{
			$lcssclass = ( $cssclass === NULL ? 'log_'.$llevel : $cssclass );
			$disp .= "\n<div class=\"$lcssclass\">";

			$disp .= $this->getHeadFoot( $head, $llevel, '<h2>%s</h2>' );

			if( $style == NULL )
			{ // '<br>' for a single message, '<ul>' for more
				$style = count($lmessages) == 1 ? '<br>' : '<ul>';
			}

			// implode messages
			if( $style == '<ul>' )
			{
				$disp .= '<ul class="'.$lcssclass.'"><li>'.implode( "</li>\n<li>", $lmessages ).'</li></ul>';
			}
			elseif( $style == '<p>' )
			{
				$disp .= '<p class="'.$lcssclass.'">'.implode( "</p>\n<p>", $lmessages ).'</p>';
			}
			else
			{
				$disp .= implode( '<br />', $lmessages );
			}
			$disp .= $this->getHeadFoot( $foot, $llevel, '<p>%s</p>' );
			$disp .= '</div>';
		}

		$disp .= $this->getHeadFoot( $foot, 'container', '<p>%s</p>' );

		$disp .= "\n</div>";


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
	 * @param string header/title for one message (default: empty), might be array
	 *               ( level => msg ), 'container' is then top
	 * @param string header/title (if more than one message)
	 * @param string footer (if one message) (default: empty), might be array
	 *               ( level => msg ), 'container' is then bottom
	 * @param string footer (if more than one message)
	 * @param boolean to display or return (default: true)
	 * @param mixed the level of messages to use (level, 'all', or list of levels (array)
	 * @param string the CSS class of the outer <div> (default: 'log_'.$level)
	 * @param string the style to use, '<ul>', '<p>', '<br>' (default: <br> for single message, <ul> for more)
	 * @return boolean false, if no messages; else true (and outputs)
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
	function getString( $head = '', $foot = '', $level = NULL )
	{
		if( !$this->count( $level ) )
		{
			return false;
		}

		$r = '';
		if( '' != $head )
		{
			$r .= $head.' ';
		}
		$r .= implode(', ', $this->getMessages( $level, true ));
		if( '' != $foot )
		{
			$r .= ' '.$foot;
		}

		return strip_tags( $r );
	}


	/**
	 * Counts messages of a given level
	 *
	 * @param string the level
	 * @return number of messages
	 */
	function count( $level = NULL )
	{
		return count( $this->getMessages( $level, true ) );
	}


	/**
	 * Returns array of messages of that level
	 *
	 * @param string the level
	 * @param boolean if true will use subarrays for each level
	 * @return array the messages, one or two dimensions (depends on second param)
	 */
	function getMessages( $level = NULL, $singleDimension = false )
	{
		$messages = array();

		if( $level === NULL )
		{
			$level = $this->defaultlevel;
		}

		if( $level == 'all' )
		{
			$level = array_keys( $this->messages );
		}
		elseif( !is_array($level) )
		{
			$level = array( $level );
		}

		foreach( $this->messages as $llevel => $lmsgs )
		{
			if( !in_array( $llevel, $level ) )
			{
				continue;
			}
			foreach( $lmsgs as $lmsg )
			{
				if( $singleDimension )
				{
					$messages[] = $lmsg;
				}
				else
				{
					$messages[$llevel][] = $lmsg;
				}
			}
		}
		return $messages;
	}

}

?>
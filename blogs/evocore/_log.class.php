<?php
/**
 * This file implements the Log class, which logs notes and errors.
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

/**
 * Log class. Logs notes and errors.
 *
 * Messages can be logged into different categories (aka levels)
 * Examples: 'note', 'error'. Note: 'all' is reserved to display all levels together.
 * Messages can later be displayed grouped by category/level.
 *
 * @package evocore
 */
class Log
{
	var $messages = array();
	var $defaultlevel = 'error';

	/**
	 * @var boolean Should {@link add()} automatically output the messages?
	 */
	var $dumpAdds = false;


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
	 * Clears the Log
	 *
	 * @param string level, use 'all' to unset all levels
	 */
	function clear( $level = NULL )
	{
		if( $level == 'all' )
		{
			unset( $this->messages );
		}
		else
		{
			if( $level === NULL )
			{
				$level = $this->defaultlevel;
			}
			unset( $this->messages[ $level ] );
		}
	}


	/**
	 * Add a message to the Log.
	 *
	 * @param string the message
	 * @param string the level, default is to use the object's default level
	 * @param boolean Dump (echo) this directly?
	 */
	function add( $message, $level = NULL, $dumpThis = false )
	{
		if( $level === NULL )
		{ // By default, we use the default level:
			$level = $this->defaultlevel;
		}

		$this->messages[ $level ][] = $message;


		if( $this->dumpAdds || $dumpThis )
		{
			Log::display( '', '', $message, $level );
		}
	}


	/**
	 * Get head/foot for a specific level, designed for internal use of {@link display()}
	 *
	 * @static
	 * @access private
	 *
	 * @param mixed head or foot (array [ level => head/foot, level => 'string', 'template',
	 *              or string [for container only])
	 * @param string the level (or container)
	 * @param string template, where the head/foot gets used (%s)
	 */
	function getHeadFoot( $headfoot, $level, $template = NULL )
	{
		if( is_string($headfoot) && $level == 'container' )
		{ // container head or foot
			$r = $headfoot;
		}
		elseif( is_array($headfoot) )
		{ // head or foot for levels
			if( isset($headfoot[$level]) )
			{
				$r = $headfoot[$level];
			}
			elseif( isset($headfoot['all']) )
			{
				$r = $headfoot['all'];
			}
			else
			{
				return false;
			}

			if( is_array($r) )
			{
				if( isset($r['template']) )
				{
					$template = $r['template'];
				}
				$r = $r['string'];
			}

			if( strstr( $r, '%s' ) )
			{
				$r = sprintf( $r, $level );
			}
		}

		if( empty($r) )
		{
			return false;
		}


		if( !empty($template) )
		{
			$r = sprintf( $template, $r );
		}

		return $r;
	}


	/**
	 * Display all messages of a single or all level(s).
	 *
	 * @param string the level to use (defaults to 'all')
	 * @return void
	 */
	function dumpAll( $level = 'all' )
	{
		$this->display( '', '', true, $level );
	}


	/**
	 * Wrapper to display messages as simple paragraphs.
	 *
	 * @param mixed the level of messages, see {@link display()}
	 * @param mixed the outer div, see {@link display()}
	 * @param mixed the css class for inner paragraphs
	 */
	function displayParagraphs( $level = NULL, $outerdivclass = 'panelinfo', $cssclass = false )
	{
		return $this->display( '', '', true, $level, $cssclass, 'p', $outerdivclass );
	}


	/**
	 * Display messages of the Log object.
	 *
	 * - You can either output/get the logs of a level (string),
	 *   all levels ('all') or level groups (array of strings).
	 * - Head/Foot will be displayed on top/bottom of the messages. You can pass
	 *   an array as head/foot with the level as key and this will be displayed
	 *   on top of the level's messages.
	 * - You can choose from various styles for message groups ('ul', 'p', 'br')
	 *   and set a css class for it (by default 'log_'.$level gets used).
	 * - You can suppress the outer div or set a css class for it (defaults to
	 *   'log_container').
	 *
	 * You can also call this function static (without creating an object), like:
	 *   <code>
	 *   Log::display( 'head', 'foot', 'message' );
	 *   </code>
	 *   Please note: when called static, it will always display, because $display
	 *                equals true.
	 *
	 * @param string header/title (default: empty), might be array ( level => msg ),
	 *               'container' is then top
	 * @param string footer (default: empty), might be array ( level => msg ),
	 *               'container' is then bottom
	 * @param boolean to display or return (default: true)
	 * @param mixed the level of messages to use (level, 'all', or list of levels (array))
	 * @param string the CSS class of the messages div tag (default: 'log_'.$level)
	 * @param string the style to use, 'ul', 'p', 'br'
	 *               (default: 'br' for single message, 'ul' for more)
	 * @param mixed the outer div, may be false
	 * @return boolean false, if no messages; else true (and outputs if $display)
	 */
	function display( $head = '', $foot = '', $display = true, $level = NULL,
										$cssclass = NULL, $style = NULL, $outerdivclass = 'log_container' )
	{
		if( $level === NULL )
		{
			$level = isset( $this->defaultlevel ) ?
								$this->defaultlevel :
								'error';
		}
		if( !is_bool($display) )
		{
			$messages = array( $level => array($display) );
		}
		else
		{
			$messages =& $this->getMessages( $level );
		}

		if( !count($messages) )
		{
			return false;
		}
		$disp = '';


		if( $outerdivclass )
		{
			$disp .= "\n<div class=\"$outerdivclass\">";
		}

		$disp .= Log::getHeadFoot( $head, 'container', '<h2>%s</h2>' );

		foreach( $messages as $llevel => $lmessages )
		{
			$lcssclass = ( $cssclass === NULL ? 'log_'.$llevel : $cssclass );

			$disp .= "\n";
			if( $lcssclass )
			{
				$disp .= "\t<div class=\"$lcssclass\">";
			}

			$disp .= Log::getHeadFoot( $head, $llevel, '<h2>%s</h2>' );

			if( $style == NULL )
			{ // 'br' for a single message, 'ul' for more
				$style = count($lmessages) == 1 ? 'br' : 'ul';
			}

			// implode messages
			if( $style == 'ul' )
			{
				$disp .= "\t<ul".( $lcssclass ? " class=\"$lcssclass\"" : '' ).'><li>'
							.implode( "</li>\n<li>", $lmessages )."</li></ul>\n";
			}
			elseif( $style == 'p' )
			{
				$disp .= "\t<p".( $lcssclass ? " class=\"$lcssclass\"" : '' ).'>'
							.implode( "</p>\n<p class=\"$lcssclass\">", $lmessages )."</p>\n";
			}
			else
			{
				$disp .= "\t".implode( "\n<br />\t", $lmessages );
			}
			$disp .= Log::getHeadFoot( $foot, $llevel, "\n<p>%s</p>" );
			if( $lcssclass )
			{
				$disp .= "\t</div>\n";
			}
		}

		$disp .= Log::getHeadFoot( $foot, 'container', "\n<p>%s</p>" );

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
	 * Wrapper for {@link display()}: use header/footer dependent on message count
	 * (one or more).
	 *
	 * @param string header/title for one message (default: empty), might be array
	 *               ( level => msg ), 'container' is then top
	 * @param string|NULL header/title (if more than one message) - NULL means "use $head1"
	 * @param string footer (if one message) (default: empty), might be array
	 *               ( level => msg ), 'container' is then bottom
	 * @param string|NULL footer (if more than one message) - NULL means "use $foot1"
	 * @param boolean to display or return (default: true)
	 * @param mixed the level of messages to use (level, 'all', or list of levels (array))
	 * @param string the CSS class of the messages div tag (default: 'log_'.$level)
	 * @param string the style to use, 'ul', 'p', 'br'
	 *               (default: 'br' for single message, 'ul' for more)
	 * @param mixed the outer div, may be false
	 * @return boolean false, if no messages; else true (and outputs if $display)
	 */
	function display_cond( $head1 = '', $head2 = '', $foot1 = '', $foot2 = '',
													$display = true, $level = NULL, $cssclass = NULL,
													$style = NULL, $outerdivclass = 'log_container' )
	{
		if( is_null( $head2 ) )
		{
			$head2 = $head1;
		}
		if( is_null( $foot2 ) )
		{
			$foot2 = $foot1;
		}
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
	function getString( $head = '', $foot = '', $level = NULL, $implodeBy = ', ' )
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
		$r .= implode( $implodeBy, $this->getMessages( $level, true ) );
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
	 * Returns array of messages of a single level or group of levels.
	 *
	 * If the level is an array, those levels will be used (where 'all' will
	 * be translated with the not already processed levels).
	 * <code>getMessages( array('error', 'note', 'all') )</code> would return
	 * 'errors', 'notes' and the remaining messages, in that order.
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

		$levelsDone = array();

		while( $llevel = array_shift( $level ) )
		{
			if( $llevel == 'all' )
			{ // Put those levels in queue, which have not been processed already
				$level = array_merge( array_diff( array_keys( $this->messages ), $levelsDone ), $level );
				continue;
			}
			if( in_array($llevel, $levelsDone ) )
			{
				continue;
			}
			$levelsDone[] = $llevel;


			if( !isset($this->messages[$llevel]) || !count($this->messages[$llevel]) )
			{
				continue;
			}

			if( $singleDimension )
			{
				$messages += $this->messages[$llevel];
			}
			else
			{
				$messages[$llevel] = $this->messages[$llevel];
			}
		}
		return $messages;
	}

}

/*
 * $Log$
 * Revision 1.8  2005/02/10 22:59:56  blueyed
 * added NULL handling for 2nd parameters for display_cond()
 *
 * Revision 1.7  2005/02/09 00:31:43  blueyed
 * dumpThis param for add()
 *
 * Revision 1.6  2005/01/02 19:16:44  blueyed
 * $implodeBy added to getString(), $dumpAdds added
 *
 * Revision 1.3  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.2  2004/10/14 16:28:41  fplanque
 * minor changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.16  2004/10/12 16:12:18  fplanque
 * Edited code documentation.
 *
 */
?>
<?php
/**
 * This file implements the Messages class for displaying messages about performed actions.
 *
 * It additionally provides the class Log_noop that implements the same (used) methods, but as
 * no-operation functions. This is useful to create a more resource friendly object when
 * you don't need it (think Debuglog).
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$ }}}
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
	 * Clears messages content
	 */
	function clear()
	{
		$this->messages_text = array();
		$this->messages_type = array();
		$this->count = 0;
		$this->has_errors = false;
	}


	/**
	 * Add a message.
	 *
	 * @param string the message
	 * @param string the message type, it can have this values: 'success', 'warning', 'error', 'note'
	 */
	function add( $text, $type = 'error' )
	{
		$this->messages_text[$this->count] = $text;
		$this->messages_type[$this->count] = $type;
		$this->count++;
		$this->error = ( $type == 'error' );
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
			$this->error = ( $p_Messages->messages_type[$i] == 'error' );
		}
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
		if( $this->count )
		{
			global $preview;
			if( $preview )
			{
				return;
			}

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
		if( $this->count == 0 ) {
			return false;
		}

		$disp = '';

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
			$disp .= "<li>\t<div class=\"log_{$this->messages_type[$i]}\"".'>'
					.$this->messages_text[$i]."</div></li>\n";
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
		return $this->count;
	}


	/**
	 * Has error message in current object
	 *
	 * @return boolean true if error message was added, false otherwise
	 */
	function has_errors()
	{
		return $has_errors;
	}
}

/*
 * $Log$
 * Revision 1.3  2010/11/25 15:16:34  efy-asimo
 * refactor $Messages
 *
 * Revision 1.2  2010/02/08 17:51:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.1  2009/11/30 00:22:04  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.9  2009/09/02 20:17:50  blueyed
 * Log: Drop li.clear workaround for Konqueror, where it has been fixed.
 *
 * Revision 1.8  2009/03/08 23:57:40  fplanque
 * 2009
 *
 * Revision 1.7  2009/02/23 20:18:00  blueyed
 * Log_noop: add constructor as no-op, too. Make the 'do-nothing' funcs take less lines.
 *
 * Revision 1.6  2009/02/22 18:09:40  blueyed
 * TODO
 *
 * Revision 1.5  2008/11/07 23:20:10  tblue246
 * debug_info() now supports plain text output for the CLI.
 *
 * Revision 1.4  2008/01/21 09:35:24  fplanque
 * (c) 2008
 *
 * Revision 1.3  2008/01/19 10:57:11  fplanque
 * Splitting XHTML checking by group and interface
 *
 * Revision 1.2  2007/09/23 18:55:17  fplanque
 * attempting to debloat. The Log class is insane.
 *
 * Revision 1.1  2007/06/25 10:58:55  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.19  2007/06/19 23:22:28  blueyed
 * doc fixes
 *
 * Revision 1.18  2007/06/16 19:20:38  blueyed
 * doc/question
 *
 * Revision 1.17  2007/05/07 18:03:28  fplanque
 * cleaned up skin code a little
 *
 * Revision 1.16  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.15  2007/01/13 22:28:12  fplanque
 * doc
 *
 * Revision 1.14  2006/12/07 23:13:13  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.13  2006/11/30 00:28:13  blueyed
 * Interface fixes for Log_noop
 *
 * Revision 1.12  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
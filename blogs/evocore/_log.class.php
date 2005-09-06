<?php
/**
 * This file implements the Log class, which logs notes and errors.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * @author blueyed: Daniel HAHLER
 * @author fplanque: François PLANQUE
 *
 * @version $Id$ }}}
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Log class. Logs notes and errors.
 *
 * Messages can be logged into different categories (aka levels)
 * Examples: 'note', 'error'. Note: 'all' is reserved to display all categories together.
 * Messages can later be displayed grouped by category/level.
 *
 * @package evocore
 */
class Log
{
	/**
	 * The stored messages (by category).
	 * @var array array of arrays
	 */
	var $messages = array();

	/**
	 * Default category for messages.
	 * @var string
	 */
	var $defaultcategory = 'error';

	/**
	 * @var mixed string or array to display before messages
	 */
	var $head = '';

	/**
	 * @var strinf to display after messages
	 */
	var $foot = '';

	/**
	 * @var boolean Should {@link add()} automatically output the messages?
	 */
	var $dumpAdds = false;

	/**
	 * @var array Cache for {@link count()}
	 */
	var $_count = array();


	/**
	 * Constructor.
	 *
	 * @param string sets default category
	 */
	function Log( $category = 'error' )
	{
		$this->defaultcategory = $category;

		// create the array for this category
		$this->messages[$category] = array();
	}


	/**
	 * Clears the Log (all or specified category).
	 *
	 * @param string category, use 'all' to unset all categories
	 */
	function clear( $category = NULL )
	{
		if( $category == 'all' )
		{
			$this->messages = array();
			$this->_count = array();
		}
		else
		{
			if( $category === NULL )
			{
				$category = $this->defaultcategory;
			}
			unset( $this->messages[ $category ] );
			unset( $this->_count[$category] );
			unset( $this->_count['all'] );
		}
	}


	/**
	 * Add a message to the Log.
	 *
	 * @param string the message
	 * @param string the category, default is to use the object's default category
	 * @param boolean Dump (echo) this directly?
	 */
	function add( $message, $category = NULL, $dumpThis = false )
	{
		if( $category === NULL )
		{ // By default, we use the default category:
			$category = $this->defaultcategory;
		}

		$this->messages[$category][] = $message;

		if( empty($this->_count[$category]) )
		{
			$this->_count[$category] = 0;
		}
		$this->_count[$category]++;


		if( $this->dumpAdds || $dumpThis )
		{
			Log::display( '', '', $message, $category );
		}
	}


	/**
	 * Get head/foot for a specific category, designed for internal use of {@link display()}
	 *
	 * @static
	 * @access private
	 *
	 * @param mixed head or foot (array [ category => head/foot, category => 'string', 'template',
	 *              or string [for container only])
	 * @param string the category (or container)
	 * @param string template, where the head/foot gets used (%s)
	 */
	function getHeadFoot( $headfoot, $category, $template = NULL )
	{
		if( is_string($headfoot) && $category == 'container' )
		{ // container head or foot
			$r = $headfoot;
		}
		elseif( is_array($headfoot) )
		{ // head or foot for categories
			if( isset($headfoot[$category]) )
			{
				$r = $headfoot[$category];
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
				$r = sprintf( $r, $category );
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
	 * Display all messages of a single or all categories.
	 *
	 * @param string the category to use (defaults to 'all')
	 * @return void
	 */
	function dumpAll( $category = 'all' )
	{
		$this->display( '', '', true, $category );
	}


	/**
	 * Wrapper to display messages as simple paragraphs.
	 *
	 * @param mixed the category of messages, see {@link display()}
	 * @param mixed the outer div, see {@link display()}
	 * @param mixed the css class for inner paragraphs
	 */
	function displayParagraphs( $category = NULL, $outerdivclass = 'panelinfo', $cssclass = NULL )
	{
		if( is_null($cssclass) )
		{
			$cssclass = array( 'all' => array( 'divClass' => false ) );
		}
		return $this->display( '', '', true, $category, $cssclass, 'p', $outerdivclass );
	}


	/**
	 * Display messages of the Log object.
	 *
	 * - You can either output/get the logs of a category (string),
	 *   all categories ('all') or category groups (array of strings).
	 * - Head/Foot will be displayed on top/bottom of the messages. You can pass
	 *   an array as head/foot with the category as key and this will be displayed
	 *   on top of the category's messages.
	 * - You can choose from various styles for message groups ('ul', 'p', 'br')
	 *   and set a css class for it (by default 'log_'.$category gets used).
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
	 * @param string header/title (default: empty), might be array ( category => msg ),
	 *               'container' is then top
	 * @param string footer (default: empty), might be array ( category => msg ),
	 *               'container' is then bottom
	 * @param boolean to display or return (default: display)
	 * @param mixed the category of messages to use (category, 'all', or list of categories (array))
	 * @param string the CSS class of the messages div tag (default: 'log_'.$category)
	 * @param string the style to use, 'ul', 'p', 'br'
	 *               (default: 'br' for single message, 'ul' for more)
	 * @param mixed the outer div, may be false
	 * @return boolean false, if no messages; else true (and outputs if $display)
	 */
	function display( $head = NULL, $foot = NULL, $display = true, $category = NULL,
										$cssclass = NULL, $style = NULL, $outerdivclass = 'log_container' )
	{
		if( is_null( $head ) )
		{ // Use object default:
			$head = isset( $this->head ) ? $this->head : '';
		}
		if( is_null( $foot ) )
		{ // Use object default:
			$foot = isset( $this->foot ) ? $this->foot : '';
		}
		if( is_null( $category ) )
		{
			$category = isset( $this->defaultcategory ) ? $this->defaultcategory : 'error';
		}
		if( !is_bool($display) )
		{ // We have just a string - static use case
			$messages = array( $category => array($display) );
		}
		elseif( !$this->count( $category ) )
		{ // no messages
			return false;
		}
		else
		{
			$messages = $this->getMessages( $category );
		}

		if( !is_array($cssclass) )
		{
			$cssclass = array( 'all' => array( 'class' => is_null($cssclass)
																											? NULL
																											: $cssclass,
																					'divClass' => true ) );
		}
		elseif( !isset($cssclass['all']) )
		{
			$cssclass['all'] = array( 'class' => NULL, 'divClass' => true );
		}


		$disp = '';

		if( $outerdivclass )
		{
			$disp .= "\n<div class=\"$outerdivclass\">";
		}

		$disp .= Log::getHeadFoot( $head, 'container', '<h2>%s</h2>' );


		foreach( $messages as $lcategory => $lmessages )
		{
			$lcssclass = isset($cssclass[$lcategory]) ? $cssclass[$lcategory] : $cssclass['all'];
			if( !isset($lcssclass['class']) || is_null($lcssclass['class']) )
			{
				$lcssclass['class'] = 'log_'.$lcategory;
			}
			if( !isset($lcssclass['divClass']) || is_null($lcssclass['divClass']) || $lcssclass['divClass'] === true )
			{
				$lcssclass['divClass'] = $lcssclass['class'];
			}


			$disp .= "\n";
			if( $lcssclass['divClass'] )
			{
				$disp .= "\t<div class=\"{$lcssclass['divClass']}\">";
			}

			$disp .= Log::getHeadFoot( $head, $lcategory, '<h3>%s</h3>' );

			if( $style == NULL )
			{ // 'br' for a single message, 'ul' for more
				$style = count($lmessages) == 1 ? 'br' : 'ul';
			}

			// implode messages
			if( $style == 'ul' )
			{
				$disp .= "\t<ul".( $lcssclass['class'] ? " class=\"{$lcssclass['class']}\"" : '' ).'><li>'
							.implode( "</li>\n<li>", $lmessages )."</li></ul>\n";
			}
			elseif( $style == 'p' )
			{
				$disp .= "\t<p".( $lcssclass['class'] ? " class=\"{$lcssclass['class']}\"" : '' ).'>'
							.implode( "</p>\n<p class=\"{$lcssclass['class']}\">", $lmessages )."</p>\n";
			}
			else
			{
				$disp .= "\t".implode( "\n<br />\t", $lmessages );
			}
			$disp .= Log::getHeadFoot( $foot, $lcategory, "\n<p>%s</p>" );
			if( $lcssclass['divClass'] )
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
	 * Wrapper for {@link Log::display()}: use header/footer dependent on message count
	 * (one or more).
	 *
	 * @param string header/title for one message (default: empty), might be array
	 *               ( category => msg ), 'container' is then top
	 * @param string|NULL header/title (if more than one message) - NULL means "use $head1"
	 * @param string footer (if one message) (default: empty), might be array
	 *               ( category => msg ), 'container' is then bottom
	 * @param string|NULL footer (if more than one message) - NULL means "use $foot1"
	 * @param boolean to display or return (default: true)
	 * @param mixed the category of messages to use (category, 'all', or list of categories (array))
	 * @param string the CSS class of the messages div tag (default: 'log_'.$category)
	 * @param string the style to use, 'ul', 'p', 'br'
	 *               (default: 'br' for single message, 'ul' for more)
	 * @param mixed the outer div, may be false
	 * @return boolean false, if no messages; else true (and outputs if $display)
	 */
	function display_cond( $head1 = '', $head_more = '', $foot1 = '', $foot_more = '',
													$display = true, $category = NULL, $cssclass = NULL,
													$style = NULL, $outerdivclass = 'log_container' )
	{
		if( is_null( $head_more ) )
		{
			$head_more = $head1;
		}

		if( is_null( $foot_more ) )
		{
			$foot_more = $foot1;
		}

		switch( $this->count( $category ) )
		{
			case 0:
				return false;

			case 1:
				return $this->display( $head1, $foot1, $display, $category, $cssclass, $style );

			default:
				return $this->display( $head_more, $foot_more, $display, $category, $cssclass, $style );
		}
	}


	/**
	 * Concatenates messages of a given category to a string
	 *
	 * @param string prefix of the string
	 * @param string suffic of the string
	 * @param string the category
	 * @return string the messages, imploded. Tags stripped.
	 */
	function getString( $head = '', $foot = '', $category = NULL, $implodeBy = ', ' )
	{
		if( !$this->count( $category ) )
		{
			return false;
		}

		$r = '';
		if( '' != $head )
		{
			$r .= $head.' ';
		}
		$r .= implode( $implodeBy, $this->getMessages( $category, true ) );
		if( '' != $foot )
		{
			$r .= ' '.$foot;
		}

		return strip_tags( $r );
	}


	/**
	 * Counts messages of a given category
	 *
	 * @param string the category
	 * @return number of messages
	 */
	function count( $category = NULL )
	{
		if( is_null($category) )
		{	// use default category:
			$category = $this->defaultcategory;
		}

		if( is_string($category) )
		{
			if( empty( $this->_count[$category] ) )
			{
				$this->_count[$category] = count( $this->getMessages( $category, true ) );
			}
			if( $category != 'all' )
			{
				unset($this->_count['all']);
			}
			return $this->_count[$category];
		}

		return count( $this->getMessages( $category, true ) );
	}


	/**
	 * Returns array of messages of a single category or group of categories.
	 *
	 * If the category is an array, those categories will be used (where 'all' will
	 * be translated with the not already processed categories).
	 * <code>getMessages( array('error', 'note', 'all') )</code> would return
	 * 'errors', 'notes' and the remaining messages, in that order.
	 *
	 * @param string the category
	 * @param boolean if true will use subarrays for each category
	 * @return array the messages, one or two dimensions (depends on second param)
	 */
	function getMessages( $category = NULL, $singleDimension = false )
	{
		$messages = array();

		if( is_null($category) )
		{
			$category = $this->defaultcategory;
		}

		if( $category == 'all' )
		{
			$category = array_keys( $this->messages );
			sort($category);
		}
		elseif( !is_array($category) )
		{
			$category = array( $category );
		}

		$categoriesDone = array();

		while( $lcategory = array_shift( $category ) )
		{
			if( $lcategory == 'all' )
			{ // Put those categories in queue, which have not been processed already
				$category = array_merge( array_diff( array_keys( $this->messages ), $categoriesDone ), $category );
				sort($category);
				continue;
			}
			if( in_array( $lcategory, $categoriesDone ) )
			{
				continue;
			}
			$categoriesDone[] = $lcategory;


			if( !isset($this->messages[$lcategory][0]) )
			{ // no messages
				continue;
			}

			if( $singleDimension )
			{
				$messages = array_merge( $messages, $this->messages[$lcategory] );
			}
			else
			{
				$messages[$lcategory] = $this->messages[$lcategory];
			}
		}
		return $messages;
	}

}

/*
 * $Log$
 * Revision 1.16  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.15  2005/05/26 19:11:11  fplanque
 * no message
 *
 * Revision 1.14  2005/04/19 16:23:03  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.13  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.12  2005/02/22 02:27:51  blueyed
 * refactoring, optimized, fix for getMessages( plain )
 *
 * Revision 1.11  2005/02/21 00:34:34  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.10  2005/02/19 23:02:45  blueyed
 * getMessages(): sort by category for 'all'
 *
 * Revision 1.9  2005/02/17 19:36:24  fplanque
 * no message
 *
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
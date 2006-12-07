<?php
/**
 * This file implements the Log class, which logs notes and errors.
 *
 * It additionally provides the class Log_noop that implements the same (used) methods, but as
 * no-operation functions. This is useful to create a more resource friendly object when
 * you don't need it (think Debuglog).
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
	 * array of arrays
	 *
	 * @var array
	 */
	var $messages = array();

	/**
	 * Default category for messages.
	 * @var string
	 */
	var $defaultcategory = 'error';

	/**
	 * string or array to display before messages
	 * @var mixed
	 */
	var $head = '';

	/**
	 * to display after messages
	 * @var string
	 */
	var $foot = '';

	/**
	 * Should {@link Log::add()} automatically output the messages?
	 * @var boolean
	 */
	var $dump_add = false;

	/**
	 * Cache for {@link Log::count()}
	 * @var array
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
	 * @param string|array the category, default is to use the object's default category.
	 *        Can also be an array of categories to add the same message to.
	 * @param boolean Dump (output) this directly?
	 */
	function add( $message, $category = NULL, $dump_this_add = false )
	{
		if( $category === NULL )
		{ // By default, we use the default category:
			$category = $this->defaultcategory;
		}

		if( is_array($category) )
		{
			foreach( $category as $l_cat )
			{
				$this->add( $message, $l_cat, false );
			}
		}
		else
		{
			$this->messages[$category][] = $message;

			if( empty($this->_count[$category]) )
			{
				$this->_count[$category] = 0;
			}
			$this->_count[$category]++;


			if( $this->dump_add || $dump_this_add )
			{
				Log::display( '', '', $message, $category );
			}
		}
	}


	/**
	 * Add an array of messages.
	 *
	 * @param array Array of messages where the keys are the categories and hold an array of messages.
	 */
	function add_messages( $messages )
	{
		foreach( $messages as $l_cat => $l_messages  )
		{
			foreach( $l_messages as $l_message )
			{
				$this->add( $l_message, $l_cat );
			}
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
	function get_head_foot( $headfoot, $category, $template = NULL )
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
			elseif( isset($headfoot['all']) && $category != 'container' )
			{ // use 'all' info, except if for container
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

			// Replace '%s' with category:
			$r = str_replace( '%s', $category, $r );
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
	 * Wrapper to display messages as simple paragraphs.
	 *
	 * @param mixed the category of messages, see {@link display()}. NULL for default category.
	 * @param mixed the outer div, see {@link display()}
	 * @param mixed the css class for inner paragraphs
	 */
	function display_paragraphs( $category = 'all', $outerdivclass = 'panelinfo', $cssclass = NULL )
	{
		if( is_null($cssclass) )
		{
			$cssclass = array( 'all' => array( 'divClass' => false ) );
		}
		return $this->display( '', '', true, $category, $cssclass, 'p', $outerdivclass );
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
		if( count($this->messages) )
		{
			echo $before;

			$this->display( NULL, NULL, true, 'all', NULL, NULL, NULL );

			echo $after;
		}
	}


	/**
	 * Display messages of the Log object.
	 *
	 * - You can either output/get the messages of a category (string),
	 *   all categories ('all') or category groups (array of strings) (defaults to 'all').
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
	 * @todo Make this simple!
	 * start by getting rid of the $category selection and the special cases for 'all'. If you don't want to display ALL messages,
	 * then you should not log them in the same Log ovject and you should instanciate separate logs instead.
	 *
	 * @param string|NULL Header/title, might be array ( category => msg ),
	 *                    'container' is then top. NULL for object's default ({@link Log::head}.
	 * @param string|NULL Footer, might be array ( category => msg ), 'container' is then bottom.
	 *                    NULL for object's default ({@link Log::foot}.
	 * @param boolean to display or return (default: display)
	 * @param mixed the category of messages to use (category, 'all', list of categories (array)
	 *              or NULL for {@link $defaultcategory}).
	 * @param string the CSS class of the messages div tag (default: 'log_'.$category)
	 * @param string the style to use, 'ul', 'p', 'br'
	 *               (default: 'br' for single message, 'ul' for more)
	 * @param mixed the outer div, may be false
	 * @return boolean false, if no messages; else true (and outputs if $display)
	 */
	function display( $head = NULL, $foot = NULL, $display = true, $category = 'all', $cssclass = NULL, $style = NULL, $outerdivclass = 'log_container' )
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
			$category = isset( $this, $this->defaultcategory ) ? $this->defaultcategory : 'error';
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
			$messages = $this->get_messages( $category );
		}

		if( !is_array($cssclass) )
		{
			$cssclass = array( 'all' => array( 'class' => is_null($cssclass) ? NULL : $cssclass, 'divClass' => true ) );
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

		$disp .= Log::get_head_foot( $head, 'container', '<h2>%s</h2>' );


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

			$disp .= Log::get_head_foot( $head, $lcategory, '<h3>%s</h3>' );

			if( $style == NULL )
			{ // 'br' for a single message, 'ul' for more
				$style = count($lmessages) == 1 ? 'br' : 'ul';
			}

			// implode messages
			if( $style == 'ul' )
			{
				$disp .= "\t<ul".( $lcssclass['class'] ? " class=\"{$lcssclass['class']}\"" : '' ).'>'
					.'<li class="clear">' // "clear" to fix Konqueror (http://bugs.kde.org/show_bug.cgi?id=117509)
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
			$disp .= Log::get_head_foot( $foot, $lcategory, "\n<p>%s</p>" );
			if( $lcssclass['divClass'] )
			{
				$disp .= "\t</div>\n";
			}
		}

		$disp .= Log::get_head_foot( $foot, 'container', "\n<p>%s</p>" );

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
	 * @param mixed the category of messages to use (category, 'all', or list of categories (array); NULL for default category)
	 * @param string the CSS class of the messages div tag (default: 'log_'.$category)
	 * @param string the style to use, 'ul', 'p', 'br'
	 *               (default: 'br' for single message, 'ul' for more)
	 * @param mixed the outer div, may be false
	 * @return boolean false, if no messages; else true (and outputs if $display)
	 */
	function display_cond( $head1 = '', $head_more = '', $foot1 = '', $foot_more = '',
													$display = true, $category = 'all', $cssclass = NULL,
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
	function get_string( $head = '', $foot = '', $category = NULL, $implodeBy = ', ' )
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
		$r .= implode( $implodeBy, $this->get_messages( $category, true ) );
		if( '' != $foot )
		{
			$r .= ' '.$foot;
		}

		return strip_tags( $r );
	}


	/**
	 * Counts messages of a given category
	 *
	 * @todo this seems a bit weird (not really relying on the cache ($_count) and unsetting 'all') -> write testcases to safely be able to change it.
	 * @param string|array the category, NULL=default, 'all' = all
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
				$this->_count[$category] = count( $this->get_messages( $category, true ) );
			}
			if( $category != 'all' )
			{
				unset($this->_count['all']);
			}
			return $this->_count[$category];
		}

		return count( $this->get_messages( $category, true ) );
	}


	/**
	 * Returns array of messages of a single category or group of categories.
	 *
	 * If the category is an array, those categories will be used (where 'all' will
	 * be translated with the not already processed categories).
	 * <code>get_messages( array('error', 'note', 'all') )</code> would return
	 * 'errors', 'notes' and the remaining messages, in that order.
	 *
	 * @param string|array the category, NULL=default, 'all' = all
	 * @param boolean if true will use subarrays for each category
	 * @return array the messages, one or two dimensions (depends on second param)
	 */
	function get_messages( $category = NULL, $singleDimension = false )
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


/**
 * This is a no-operation implementation of {@link Log}.
 *
 * It just implements the used methods {@link get()} and {@link display()}.
 *
 * @package evocore
 */
class Log_noop {
	/**
	 * This is a no-operation method.
	 */
	function add()
	{
	}

	/**
	 * This is a no-operation method.
	 */
	function add_messages()
	{
	}


	/**
	 * This is a no-operation method.
	 */
	function clear()
	{
	}


	/**
	 * This is a no-operation method.
	 */
	function count()
	{
	}


	/**
	 * This is a no-operation method.
	 */
	function disp()
	{
	}


	/**
	 * This is a no-operation method.
	 */
	function display()
	{
	}


	/**
	 * This is a no-operation method.
	 */
	function display_cond()
	{
	}


	/**
	 * This is a no-operation method.
	 */
	function display_paragraphs()
	{
	}


	/**
	 * This is a no-operation method.
	 */
	function get_messages()
	{
		return array();
	}


	/**
	 * This is a no-operation method.
	 */
	function get_string()
	{
		return '';
	}
}

/*
 * $Log$
 * Revision 1.14  2006/12/07 23:13:13  fplanque
 * @var needs to have only one argument: the variable type
 * Otherwise, I can't code!
 *
 * Revision 1.13  2006/11/30 00:28:13  blueyed
 * Interface fixes for Log_noop
 *
 * Revision 1.12  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.11  2006/08/05 23:37:03  fplanque
 * no message
 *
 * Revision 1.10  2006/07/31 19:51:11  blueyed
 * doc
 *
 * Revision 1.9  2006/06/26 23:10:24  fplanque
 * minor / doc
 *
 * Revision 1.8  2006/04/19 23:08:58  blueyed
 * Added all public methods from Log to Log_noop.
 *
 * Revision 1.7  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.6  2006/03/23 21:02:22  fplanque
 * cleanup
 *
 * Revision 1.5  2006/03/20 22:28:35  blueyed
 * Changed defaults for Log's display methods to "all" categories.
 *
 * Revision 1.4  2006/03/15 00:53:54  blueyed
 * Added Log_noop::add_messages()
 *
 * Revision 1.3  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.24  2006/02/05 19:04:48  blueyed
 * doc fixes
 *
 * Revision 1.23  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.22  2005/12/04 15:47:07  blueyed
 * display(): fix static use without category;
 * Konqueror fix for first listitem in <ul> mode.
 *
 * Revision 1.21  2005/11/30 19:53:05  blueyed
 * Display a list of Debuglog categories with links to the categories messages html ID.
 *
 * Revision 1.20  2005/11/18 00:13:55  blueyed
 * Normalized Log class
 *
 * Revision 1.19  2005/11/07 18:34:38  blueyed
 * Added class Log_noop, a no-operation implementation of class Log, which gets used if $debug is false.
 *
 * Revision 1.18  2005/11/01 23:32:30  blueyed
 * Added add_messages() to add an array of messages. This helps to add messages from a Messages object stored in session data.
 *
 * Revision 1.17  2005/11/01 21:18:44  blueyed
 * Log::add(): allow adding a message to multiple categories. This allows to keep messages in their category, but additionally tag them as "error" for example.
 *
 * Revision 1.16  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
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
 * refactoring, optimized, fix for get_messages( plain )
 *
 * Revision 1.11  2005/02/21 00:34:34  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.10  2005/02/19 23:02:45  blueyed
 * get_messages(): sort by category for 'all'
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
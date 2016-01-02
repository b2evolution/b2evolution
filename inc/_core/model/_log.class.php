<?php
/**
 * This file implements the Log class FOR DEBUGGING
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
	var $defaultcategory = 'note';

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
	 * Cache for {@link Log::count()}
	 * @var array
	 */
	var $_count = array();


	/**
	 * Constructor.
	 *
	 * @param string sets default category
	 */
	function __construct( $category = 'note' )
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
	function add( $message, $category = NULL )
	{
		if( $category === NULL )
		{ // By default, we use the default category:
			$category = $this->defaultcategory;
		}

		if( is_string($category) && isset($GLOBALS['debug_'.$category]) && $GLOBALS['debug_'.$category] == false )
		{	// We don't want to debug this category
			return;
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
	 * @access private
	 *
	 * @param mixed head or foot (array [ category => head/foot, category => 'string', 'template',
	 *              or string [for container only])
	 * @param string the category (or container)
	 * @param string template, where the head/foot gets used (%s)
	 */
	static function get_head_foot( $headfoot, $category, $template = NULL )
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
	 * @param boolean Skip if previewing?
	 *        TODO: dh> This appears to not display e.g. errors which got inserted?!!
	 *                  I also don't see how this is a "simple" param (in the sense
	 *                  of useful/required)
	 */
	function disp( $before = '<div class="action_messages">', $after = '</div>', $skip_if_preview = true )
	{
		if( count($this->messages) )
		{
			global $preview;
			if( $preview )
			{
				return;
			}

			$disp = $this->display( NULL, NULL, false, 'all', NULL, NULL, NULL );

			if( !empty( $disp ) )
			{
				echo $before.$disp.$after;
			}
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
	 * @todo Make this simple!
	 * start by getting rid of the $category selection and the special cases for 'all'. If you don't want to display ALL messages,
	 * then you should not log them in the same Log object and you should instantiate separate logs instead.
	 *
	 * @param string|NULL Header/title, might be array ( category => msg ),
	 *                    'container' is then top. NULL for object's default ({@link Log::$head}.
	 * @param string|NULL Footer, might be array ( category => msg ), 'container' is then bottom.
	 *                    NULL for object's default ({@link Log::$foot}.
	 * @param boolean to display or return (default: display)
	 * @param mixed the category of messages to use (category, 'all', list of categories (array)
	 *              or NULL for {@link $defaultcategory}).
	 * @param string the CSS class of the messages div tag (default: 'log_'.$category)
	 * @param string the style to use, 'ul', 'p', 'br', 'raw'
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

		if( !$this->count( $category ) )
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
					.'<li>'.implode( "</li>\n<li>", $lmessages )."</li></ul>\n";
			}
			elseif( $style == 'p' )
			{
				$disp .= "\t<p".( $lcssclass['class'] ? " class=\"{$lcssclass['class']}\"" : '' ).'>'
							.implode( "</p>\n<p class=\"{$lcssclass['class']}\">", $lmessages )."</p>\n";
			}
			elseif( $style == 'raw' )
			{
				$disp .= implode( "\n", $lmessages )."\n";
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
	 * Concatenates messages of a given category to a string
	 *
	 * @param string prefix of the string
	 * @param string suffic of the string
	 * @param string the category
	 * @return string the messages, imploded. Tags stripped.
	 */
	function get_string( $head = '', $foot = '', $category = NULL, $implodeBy = ', ', $format = 'striptags' )
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
 * This is used for $Debuglog, when $debug is not enabled.
 *
 * @package evocore
 */
class Log_noop {
	/**
	 * This is a no-operation method.
	 */
	function __construct() {}

	/**
	 * This is a no-operation method.
	 */
	function add() {}

	/**
	 * This is a no-operation method.
	 */
	function add_messages() {}

	/**
	 * This is a no-operation method.
	 */
	function clear() {}

	/**
	 * This is a no-operation method.
	 */
	function count() {}

	/**
	 * This is a no-operation method.
	 */
	function disp() {}

	/**
	 * This is a no-operation method.
	 */
	function display() {}

	/**
	 * This is a no-operation method.
	 */
	function display_paragraphs() {}

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

?>
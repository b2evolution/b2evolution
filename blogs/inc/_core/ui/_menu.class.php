<?php
/**
 * This file implements the Menu class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/ui/_uiwidget.class.php', 'Table' );
load_class( '_core/ui/_uiwidget.class.php', 'Widget' );

/**
 * Menu class
 *
 * @package evocore
 */
class Menu extends Widget
{
	/**
	 * The menu structure (array of arrays)
	 *
	 * Use {@link add_menu_entries()} to add them here.
	 *
	 * @access protected
	 * @var array
	 */
	var $_menus = array();


	/**
	 * Add menu entries to the list of entries for a given path.
	 *
	 * @param NULL|string|array The path to add the entries to. See {@link get_node_by_path()}.
	 * @param array Menu entries to add (key (string) => entry (array)).
	 *   An entry can have the following keys:
	 *     'text': Text/Caption for this entry.
	 *     'href': The link for this entry.
	 *     'entries': array of sub-entries
	 *     DEPRECATED 'style': CSS style for this entry.
	 *     DEPRECATED 'onclick': onclick property for this entry.
	 *     DEPRECATED 'name': name attribute of the link/entry.
	 */
	function add_menu_entries( $path, $new_entries )
	{
		// Get a reference to the node in the menu list.
		$node = & $this->get_node_by_path( $path, true );

		/*
		if( !is_array($node) )
		{
			debug_die( 'add_menu_entries() with non-existing path!' );
		}
		*/

		foreach( $new_entries as $l_key => $l_new_entry )
		{
			$node['entries'][$l_key] = $l_new_entry;
		}
	}


	/**
	 * Get the reference of a node from the menu entries using a path.
	 *
	 * @param array|string|NULL The path. NULL means root, string means child of root,
	 *                          array means path below root. (eg <code>array('options', 'general')</code>).
	 * @param boolean Should the node be created if it does not exist already?
	 * @return array|false The node as array or false, if the path does not exist (and we do not $createIfNotExisting).
	 */
	function & get_node_by_path( $path, $createIfNotExisting = false )
	{
		if( is_null($path) )
		{ // root element
			$path = array();
		}
		elseif( ! is_array($path) )
		{
			$path = array($path);
		}

		$node = & $this->_menus;
		foreach( $path as $lStep )
		{
			if( ! isset($node['entries'][$lStep]) )
			{
				if( $createIfNotExisting )
				{
					$node['entries'][$lStep] = array();
				}
				else
				{
					$r = false;
					return $r;
				}
			}
			$node = & $node['entries'][$lStep];
		}

		return $node;
	}


	/**
	 * Get menu entries for a given path.
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @return array The menu entries (may be empty).
	 */
	function get_menu_entries( $path )
	{
		$node = & $this->get_node_by_path( $path );

		return isset( $node['entries'] ) ? $node['entries'] : array();
	}


	/**
	 * Get the key of a selected entry for a path.
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @return string|false
	 */
	function get_selected( $path )
	{
		$node = & $this->get_node_by_path($path);

		if( isset($node['selected']) )
		{
			return $node['selected'];
		}

		return false;
	}


	/**
	 * Get the HTML for the menu entries of a specific path.
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @param string Template name, see {@link get_template()}.
	 * @return string The HTML for the menu.
	 */
	function get_html_menu( $path = NULL, $template = 'main', $level = 0 )
	{
		global $current_User;

		$r = '';

		if( is_null($path) )
		{
			$path = array();
		}
		elseif( ! is_array( $path ) )
		{
			$path = array( $path );
		}

		$templateForLevel = $this->get_template( $template, $level );

		if( !( $menuEntries = $this->get_menu_entries($path) ) )
		{	// No menu entries at this level
			if( isset($templateForLevel['empty']) )
			{
				$r .= $templateForLevel['empty'];
			}
		}
		else
		{	// There are entries to display:
			$r .= $templateForLevel['before'];

			$selected = $this->get_selected($path);

			foreach( $menuEntries as $loop_key => $loop_details )
			{
				if( !empty( $loop_details['separator'] ) )
				{	// Separator
					$r .= $templateForLevel['separator'];
					continue;
				}


				// Menu entry
				if( isset( $loop_details['href'] ) )
				{
					$href = $loop_details['href'];
				}
				elseif( !empty($loop_details['href_eval']) )
				{ // Useful for passing dynamic context vars (fp>> I AM using it)
					$href = eval( $loop_details['href_eval'] );
				}
				else
				{
					$href = NULL;
				}

				$anchor = '<a';

				if( !empty($href) )
				{
					$anchor .= ' href="'.$href.'"';
				}
				if( isset($loop_details['target']) )
				{
					$anchor .= ' target="'.$loop_details['target'].'"';
				}
				if( isset($loop_details['style']) )
				{
					$anchor .= ' style="'.$loop_details['style'].'"';
				}
				if( isset($loop_details['onclick']) )
				{
					$anchor .= ' onclick="'.$loop_details['onclick'].'"';
				}
				if( isset($loop_details['name']) )
				{
					$anchor .= ' name="'.$loop_details['name'].'"';
				}
				if( isset($loop_details['title']) )
				{
					$anchor .= ' title="'.$loop_details['title'].'"';
				}

				// CLASS
				$class = '';
				if( !empty( $loop_details['class'] ) )
				{	// disabled
					$class .= ' '.$loop_details['class'];
				}
				if( !empty( $loop_details['disabled'] ) )
				{	// disabled
					$class .= ' '.$templateForLevel['disabled_class'];
				}
				if( !empty($class) )
				{	// disabled
					$anchor .= ' class="'.trim($class).'"';
				}

				$anchor .= '>'.(isset($loop_details['text']) ? format_to_output( $loop_details['text'], 'htmlbody' ) : '?')."</a>";

				if( $loop_key == $selected )
				{ // Highlight selected entry
					if( isset( $templateForLevel['_props']['recurse'] )
							&& $templateForLevel['_props']['recurse'] != 'no'
							&& ( $recursePath = array_merge( $path, array($loop_key) ) )
							&& $this->get_menu_entries($recursePath) )
					{
						$r .= isset($templateForLevel['beforeEachSelWithSub']) ? $templateForLevel['beforeEachSelWithSub'] : $templateForLevel['beforeEachSel'];
						$r .= $anchor;

						if( $recurse != 'no' )
						{ // Recurse:
							$r .= $this->get_html_menu( $recursePath, $template, $level+1 );
						}

						$r .= isset($templateForLevel['afterEachSelWithSub']) ? $templateForLevel['afterEachSelWithSub'] : $templateForLevel['afterEachSel'];
					}
					else
					{
						$r .= $templateForLevel['beforeEachSel'];
						$r .= $anchor;
						$r .= $templateForLevel['afterEachSel'];
					}
				}
				else
				{	// Not selected entry
					if( isset( $templateForLevel['_props']['recurse'] )
							&& $templateForLevel['_props']['recurse'] == 'always'
							&& ( $recursePath = array_merge( $path, array($loop_key) ) )
							&& $this->get_menu_entries($recursePath) )
					{
						$r .= isset($templateForLevel['beforeEachWithSub']) ? $templateForLevel['beforeEachWithSub'] : $templateForLevel['beforeEachSel'];
						$r .= $anchor;
						// recurse:
						$r .= $this->get_html_menu( $recursePath, $template, $level+1 );
						$r .= isset($templateForLevel['afterEachWithSub']) ? $templateForLevel['afterEachWithSub'] : $templateForLevel['afterEachSel'];
					}
					else
					{
						$r .= $templateForLevel['beforeEach'];
						$r .= $anchor;
						$r .= $templateForLevel['afterEach'];
					}
				}
			}
			$r .= $templateForLevel['after'];
		}

		return $r;
	}



	/**
	 * Get a template by name.
	 *
	 * This is a method (and not a member array) to allow dynamic generation and T_()
	 *
	 * @param string Name of the template ('main', 'sub')
	 * @return array Associative array which defines layout and optionally properties.
	 */
	function get_template( $name, $level = 0 )
	{
		switch( $name )
		{
			case 'sf-menu-left':
			case 'sf-menu-right':
				return array(
					'before' => '<ul class="sf-menu '.$name.'">',
					'after' => '</ul>',
					'beforeEach' => '<li>',
					'afterEach' => '</li>',
					'beforeEachSel' => '<li class="current">',
					'afterEachSel' => '</li>',
					'separator' => '<li class="separator"><a><hr /></a></li>',
					'disabled_class' => 'disabled',
					'_props' => array(
						'recurse' => 'always',  // options are: 'no' 'always' or 'intoselected'
					),
				);
				break;

			default:
				debug_die( 'Unknown $name for Menu::get_template(): '.var_export($name, true) );
		}
	}
}

/*
 * $Log$
 * Revision 1.9  2009/09/15 19:31:56  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.8  2009/09/14 10:56:06  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.7  2009/08/30 17:27:03  fplanque
 * better NULL param handling all over the app
 *
 * Revision 1.6  2009/03/23 22:19:45  fplanque
 * evobar right menu is now also customizable by plugins
 *
 * Revision 1.4  2009/03/23 12:38:21  tblue246
 * get_html_menu(): Also handle the case when $path is a string
 *
 * Revision 1.3  2009/03/23 12:21:31  fplanque
 * cleaner fix (I guess)
 *
 * Revision 1.2  2009/03/23 11:51:51  tblue246
 * Fixing array_merge() notices
 *
 * Revision 1.1  2009/03/23 04:09:43  fplanque
 * Best. Evobar. Menu. Ever.
 * menu is now extensible by plugins
 *
 */
?>

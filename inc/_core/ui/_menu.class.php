<?php
/**
 * This file implements the Menu class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
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
	 * @param string Node name after which we should insert the menu entries, NULL - to insert to the end
	 */
	function add_menu_entries( $path, $new_entries, $after_node = NULL )
	{
		// Get a reference to the node in the menu list.
		$node = & $this->get_node_by_path( $path, true );

		/*
		if( !is_array($node) )
		{
			debug_die( 'add_menu_entries() with non-existing path!' );
		}
		*/

		$new_entires_are_inserted = false;

		if( !is_null( $after_node ) )
		{	// We should insert new entries after specific node
			$new_node = $node;
			$new_node['entries'] = array();
			if( isset( $node['entries'] ) )
			{
				foreach( $node['entries'] as $node_key => $node_entry )
				{
					$new_node['entries'][ $node_key ] = $node_entry;
					if( $node_key == $after_node )
					{	// Insert new entires here after specific node
						foreach( $new_entries as $l_key => $l_new_entry )
						{
							$new_node['entries'][$l_key] = $l_new_entry;
						}
						$new_entires_are_inserted = true;
					}
				}
			}
			$node = $new_node;
		}

		if( !$new_entires_are_inserted )
		{	// Insert new entries to the end if they are still not inserted after specific node
			foreach( $new_entries as $l_key => $l_new_entry )
			{
				$node['entries'][$l_key] = $l_new_entry;
			}
		}
	}

	/**
	 * Insert new menu entries right after the menu entry passed as path
	 *
	 * @param NULL|string|array The path. See {@link get_node_by_path()}.
	 * @param new entries
	 * @param position after which to insert new entries. If not given, new entries is inserted after Path
	 * @returns boolean Whether inserting was successfull.
	 */
	function insert_menu_entries_after( $path, $new_entries, $index = false )
	{
		$menu_item = '';
		if( $index === false )
		{ // get menu item after which to insert new entries

			if( is_array( $path ) )
			{
				$menu_item = array_pop( $path );
			}
			elseif( is_string( $path ) )
			{
				$menu_item = $path;
				$path = NULL;
			}
		}
		$menu = & $this->get_node_by_path( $path );
		if( $menu === false )
		{ // no such path
			return false;
		}
		$entries = & $menu['entries'];
		if( $menu_item )
		{ // find index of menu itemafter which to insert new entries
			$keys = array_keys( $entries );

			if( !empty( $keys ) ) $index = array_search( $menu_item, $keys );
		}

		if( ( $index === false ) || ($index === NULL) )
		{
			return false;
		}

		// make new menu entries
		$menu['entries'] = array_merge(
			array_slice( $entries, 0, $index + 1 ),
			$new_entries,
			array_slice( $entries, $index )
		);

		return true;
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
	 * @param integer Level
	 * @param boolean TRUE - to get template for empty menu, used to hide menus
	 * @return string The HTML for the menu.
	 */
	function get_html_menu( $path = NULL, $template = 'main', $level = 0, $force_empty = false )
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

		if( $force_empty || !( $menuEntries = $this->get_menu_entries($path) ) )
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
				if( empty( $loop_details ) )
				{ // Empty placeholder, skip it. Might happen if the files module is disabled for example, then we had a file placeholder
					// in the blog menu that will never be used. So don't display it...
					continue;
				}

				if( !empty( $loop_details['separator'] ) )
				{ // Separator
					$r .= $templateForLevel['separator'];
					continue;
				}


				// Menu entry
				if( isset( $loop_details['href'] ) )
				{
					$href = $loop_details['href'];
				}
				elseif( ! empty( $loop_details['href_eval'] ) )
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
				{ // disabled
					$class .= ' '.$loop_details['class'];
				}
				if( !empty( $loop_details['disabled'] ) )
				{ // disabled
					$class .= ' '.$templateForLevel['disabled_class'];
				}
				if( ! empty( $class ) )
				{ // disabled
					$anchor .= ' class="'.trim($class).'"';
				}

				$anchor .= '>'.(isset($loop_details['text']) ? format_to_output( $loop_details['text'], 'htmlbody' ) : '?');
				$anchor_end = '</a>';

				if( $loop_key == $selected )
				{ // Highlight selected entry
					if( isset( $templateForLevel['_props']['recurse'] )
							&& $templateForLevel['_props']['recurse'] != 'no'
							&& ( $recursePath = array_merge( $path, array($loop_key) ) )
							&& $this->get_menu_entries($recursePath) )
					{
						$r .= isset($templateForLevel['beforeEachSelWithSub']) ? $templateForLevel['beforeEachSelWithSub'] : $templateForLevel['beforeEachSel'];
						$r .= $anchor;

						if( $templateForLevel['_props']['recurse'] != 'no' && // Recurse:
							  ( ! isset( $templateForLevel['_props']['recurse_level'] ) ||
							    ( isset( $templateForLevel['_props']['recurse_level'] ) &&
							      $templateForLevel['_props']['recurse_level'] > $level + 1 ) ) )
						{ // Display submenus if this level is not limited by param 'recurse_level'
							$r .= $templateForLevel['arrow_level_'.( $level + 1 )].'</a>';
							$r .= $this->get_html_menu( $recursePath, $template, $level+1 );
						}
						else
						{ // End anchor without sub menus
							$r .= $anchor_end;
						}

						$r .= isset($templateForLevel['afterEachSelWithSub']) ? $templateForLevel['afterEachSelWithSub'] : $templateForLevel['afterEachSel'];
					}
					else
					{
						if( isset( $loop_details['order'] ) && $loop_details['order'] == 'group_last' &&
						    isset( $templateForLevel['beforeEachSelGrpLast'], $templateForLevel['afterEachSelGrpLast'] ) )
						{ // This selected menu item is last in a group
							$r .= $templateForLevel['beforeEachSelGrpLast'];
							$r .= $anchor.$anchor_end;
							$r .= $templateForLevel['afterEachSelGrpLast'];
						}
						else
						{ // Normal selected menu item
							$r .= $templateForLevel['beforeEachSel'];
							$r .= $anchor.$anchor_end;
							$r .= $templateForLevel['afterEachSel'];
						}
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
						$r .= $anchor.$templateForLevel['arrow_level_'.( $level + 1 )].'</a>';
						// recurse:
						$r .= $this->get_html_menu( $recursePath, $template, $level+1 );
						$r .= isset($templateForLevel['afterEachWithSub']) ? $templateForLevel['afterEachWithSub'] : $templateForLevel['afterEachSel'];
					}
					else
					{
						if( isset( $loop_details['order'] ) && $loop_details['order'] == 'group_last' &&
						    isset( $templateForLevel['beforeEachGrpLast'], $templateForLevel['afterEachGrpLast'] ) )
						{ // This menu item is last in a group
							$r .= $templateForLevel['beforeEachGrpLast'];
							$r .= $anchor.$anchor_end;
							$r .= $templateForLevel['afterEachGrpLast'];
						}
						else
						{ // Normal menu item
							$r .= $templateForLevel['beforeEach'];
							$r .= $anchor.$anchor_end;
							$r .= $templateForLevel['afterEach'];
						}
					}
				}

				// Additional attribures for each menu entry
				$entry_attrs = '';
				$entry_class = '';
				if( ! empty( $loop_details['entry_class'] ) )
				{ // Css class for entry
					if( strpos( $r, '$entry_class$' ) === false )
					{
						$entry_attrs .= ' class="'.$loop_details['entry_class'].'"';
					}
					$entry_class .= ' '.$loop_details['entry_class'];
				}
				$r = str_replace( array( '$entry_attrs$', '$entry_class$' ), array( $entry_attrs, $entry_class ), $r );
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
			case 'evobar-menu-right':
				$arrow_level_2 = '<span class="evobar-icon-left fa fa-caret-left"></span>';
			case 'evobar-menu-left':
				return array(
					'before'         => '<ul class="evobar-menu '.$name.'">',
					'after'          => '</ul>',
					'beforeEach'     => '<li$entry_attrs$>',
					'afterEach'      => '</li>',
					'beforeEachSel'  => '<li class="current$entry_class$"$entry_attrs$>',
					'afterEachSel'   => '</li>',
					'separator'      => '<li class="separator"><hr /></li>',
					'arrow_level_1'  => '<span class="evobar-icon-down fa fa-caret-down"></span>',
					'arrow_level_2'  => isset( $arrow_level_2 ) ? $arrow_level_2 : '<span class="evobar-icon-right fa fa-caret-right"></span>',
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


	/**
	 * Check if menu is empty or contains at least one entry
	 * 
	 * @return boolean true if the menu is not empty | false otherwise
	 */
	function has_entires()
	{
		return !empty( $this->_menus );
	}


	/**
	 * Clear menu entries of the given path.
	 *
	 * @param NULL|string The path to clear the entries from. See {@link get_node_by_path()}.
	 */
	function clear_menu_entries( $path )
	{
		// Get a reference to the node in the menu list.
		$node = & $this->get_node_by_path( $path, true );

		$node['entries'] = array();
	}
}

?>
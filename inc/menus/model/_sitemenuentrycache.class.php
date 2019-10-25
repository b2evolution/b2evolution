<?php
/**
 * This file implements the SiteMenuCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'menus/model/_sitemenuentry.class.php', 'SiteMenuEntry' );

/**
 * SiteMenuEntryCache Class
 *
 * @package evocore
 */
class SiteMenuEntryCache extends DataObjectCache
{
	var $subset_cache = array();

	/**
	 * Which property of the objects defines the subset
	 */
	var $subset_property;

	/**
	 * Which subsets have been loaded
	 */
	var $loaded_subsets = array();

	/**
	 * These are the level 0 entries (which have no parent)
	 */
	var $root_entries = array();

	/**
	 * These are the level 0 entries (which have no parent) for each subset
	 */
	var $subset_root_entries = array();

	/**
	 * Have the children been revealed for all subsets yet?
	 */
	var $revealed_all_children = false;
	/**
	 * Have the children been revealed for all subsets yet?
	 */
	var $revealed_subsets = array();

	/**
	 * Flags about which arrays are already sorted
	 */
	var $sorted_flags = array();

	/**
	 * Lazy filled index of url titles
	 */
	var $urlname_index = array();


	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct( 'SiteMenuEntry', false, 'T_menus__entry', 'ment_', 'ment_ID', 'ment_text' );

		// This is the property by which we will filter out subsets, for example 'menu_ID' if we want to only load a specific collection:
		$this->subset_property = 'menu_ID';
	}


	/**
	 * Empty/reset the cache
	 */
	function clear( $keep_shadow = false )
	{
		$this->subset_cache = array();
		$this->loaded_subsets = array();
		$this->root_entries = array();
		$this->subset_root_entries = array();
		$this->revealed_all_children = false;
		$this->revealed_subsets = array();
		$this->sorted_flags = array();
		$this->urlname_index = array();
		parent::clear( $keep_shadow );
	}


	/**
	 * Add a dataobject to the cache
	 *
	 * @param object SiteMenuEntry object to add in cache
	 * @return boolean TRUE on adding, FALSE on wrong object or if it is already in cache
	 */
	function add( $SiteMenuEntry )
	{
		if( parent::add( $SiteMenuEntry ) )
		{	// Successfully added

			if( ! empty( $this->subset_property ) )
			{	// Also add to subset cache:
				$this->subset_cache[ $SiteMenuEntry->{$this->subset_property} ][ $SiteMenuEntry->ID ] = $SiteMenuEntry;
			}
			return true;
		}

		return false;
	}


	/**
	 * Get an array with Site Menu Entries ID that are located in the given Menu from root to the given Site Menu Entry
	 *
	 * @param integer Site Menu ID
	 * @param integer Site Menu Entry ID
	 * @return array Site Menu Entries ID
	 */
	function get_menu_entry_path( $menu_ID, $menu_entry_ID )
	{
		$this->load_subset( $menu_ID );

		$menu_entry_path = array( $menu_entry_ID );
		if( isset( $this->subset_cache[ $menu_ID ] ) )
		{
			$menu_entries = $this->subset_cache[ $menu_ID ];
			if( isset( $menu_entries[ $menu_entry_ID ] ) )
			{
				$SiteMenuEntry = $menu_entries[ $menu_entry_ID ];
				while( $SiteMenuEntry->get( 'parent_ID' ) > 0 )
				{
					$menu_entry_path[] = $SiteMenuEntry->get( 'parent_ID' );
					// Select a parent Site Menu Entry:
					$SiteMenuEntry = $menu_entries[ $SiteMenuEntry->get( 'parent_ID' ) ];
				}
			}
		}

		return $menu_entry_path;
	}


	/**
	 * Load a keyed subset of the cache
	 *
	 * @param integer|NULL NULL for all subsets
	 * @param string Force 'order by' setting ('manual' = 'ORDER BY ment_order')
	 */
	function load_subset( $subset_ID, $force_order_by = '' )
	{
		global $DB, $Debuglog, $Settings;

		if( $this->all_loaded || isset( $this->loaded_subsets[$subset_ID] ) )
		{ // Already loaded
			return false;
		}

		// fp> TODO: This kills other subsets. BAD if we want to handle multiple subsets independently
		$this->clear( true );

		$Debuglog->add( 'SiteMenuEntryCache - Loading <strong>menu entries('.$subset_ID.')</strong> into cache', 'dataobjects' );
		$sql = 'SELECT *
					FROM T_menus__entry
					WHERE ment_menu_ID = '.$subset_ID;

		foreach( $DB->get_results( $sql, OBJECT, 'Loading menu entries('.$subset_ID.') into cache' ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		$this->loaded_subsets[$subset_ID] = true;

		return true;
	}


	/**
	 * Support function for move_SiteMenuEntry_subtree
	 *
	 * @param SiteMenuEntry
	 * @param array
	 */
	function recurse_move_subtree( & $SiteMenuEntry, & $list_array )
	{
		// Add this to the list:
		$list_array[] = $SiteMenuEntry->ID;

		foreach( $SiteMenuEntry->children as $child_SiteMenuEntry )
		{
			$this->recurse_move_subtree( $child_SiteMenuEntry, $list_array );
		}
	}


	/**
	 * Instanciate a new object within this cache
	 *
	 * @param object|NULL
	 * @param integer|NULL subset to use for new object
	 */
	function & new_obj( $row = NULL, $subset_ID = NULL )
	{
		// Instantiate a custom object
		$SiteMenuEntry = new SiteMenuEntry( $row, $subset_ID ); // Copy

		return $SiteMenuEntry;
	}


	/**
	 * Reveal children
	 *
	 * After executing this, each Site Menu Entry will have an array pointing to its direct children
	 *
	 * @param integer|NULL NULL for all subsets
	 * @param boolean Set true to sort root entries or leave it on false otherwise
	 */
	function reveal_children( $subset_ID = NULL, $sort_root_entries = false )
	{
		global $Debuglog;
		global $Timer;

		if( $this->revealed_all_children )
		{	// All children have already been revealed: (can happen even if we require a subset *now*)
			if( $sort_root_entries )
			{
				$this->sort_root_entries( $subset_ID );
			}
			return;
		}

		if( empty($this->subset_property) )
		{	// We are not handling a subset but everything instead:

			echo 'DEBUG - PARTIAL IMPLEMENTATION - Revealing all children -- this is not yet handling all edge cases that the subset version can handle';

			// Make sure everything has been loaded:
			$this->load_all();

			// Reveal children:
			if( !empty( $this->cache ) )
			{	// There are loaded entries, so loop on all loaded entries to set their children list if it has:
				foreach( $this->cache as $ment_ID => $SiteMenuEntry )
				{
					if( ! is_null( $SiteMenuEntry->parent_ID ) )
					{	// This Site Menu Entry has a parent, so add it to its parent children list:
						$this->cache[$SiteMenuEntry->parent_ID]->add_child_entry( $this->cache[$ment_ID] );
					}
					else
					{	// This Site Menu Entry has no parent, so add it to the parent entries list
						$this->root_entries[] = & $this->cache[$ment_ID];
					}
				}
			}

			if( $sort_root_entries )
			{ // Sort root entries as it was requested
				$this->sort_root_entries( NULL );
			}

			$this->revealed_all_children = true;
		}
		else
		{	// We are handling a subset (for example Chapers of a specific menu_ID only):

			if( is_null( $subset_ID ) )
			{	// No specific subset requested, we are going to reveal all subsets in a row:

				// Make sure everything has been loaded:
				$this->load_all();

				// Commented out, because it is needed in case of cross posting through Site Menus
				// echo 'DEBUG - PARTIAL IMPLEMENTATION - REVEALING ALL SUBSETS in a row. Is this needed?';

				foreach( $this->subset_cache as $subset_ID => $dummy )
				{
					$this->reveal_children( $subset_ID, $sort_root_entries );
				}

				$this->revealed_all_children = true;
			}
			else
			{	// We're interested in a specific subset:
				// *** THIS IS THE CASE USED IN B2EVOLUTION ***

				if( !empty( $this->revealed_subsets[$subset_ID] ) )
				{	// Children have already been revealed:
					if( $sort_root_entries )
					{
						$this->sort_root_entries( $subset_ID );
					}
					return;
				}

				$Timer->resume('reveal_children', false );

				$Debuglog->add( 'Revealing subset of children', 'SiteMenuEntryCache' );

				// Make sure the requested subset has been loaded:
				$this->load_subset($subset_ID);

				// Reveal children:
				if( !empty( $this->subset_cache[$subset_ID] ) )
				{	// There are loaded entries

					// Now check that each Site Menu Entry has a path to the root:
					foreach( $this->subset_cache[$subset_ID] as $ment_ID => $dummy )	// "as" would give a freakin COPY of the object :(((
					{
						$this->check_path_to_root( $subset_ID, $ment_ID );
					}

					// loop on all loaded entries to set their children list if they have some:
					foreach( $this->subset_cache[$subset_ID] as $ment_ID => $dummy )	// "as" would give a freakin COPY of the object :(((
					{
						$SiteMenuEntry = & $this->subset_cache[$subset_ID][$ment_ID];
						if( ! is_null( $SiteMenuEntry->parent_ID ) )
						{	// This Site Menu Entry has a parent, so add it to its parent children list:
							$Debuglog->add( 'adding child with ID='.$ment_ID.' to parent ID='.$SiteMenuEntry->parent_ID, 'SiteMenuEntryCache' );
							$this->cache[$SiteMenuEntry->parent_ID]->add_child_entry( $this->cache[$ment_ID] );
						}
						else
						{	// This Site Menu Entry has no parent, so add it to the parent entries list
							$Debuglog->add( 'adding parent with ID='.$ment_ID, 'SiteMenuEntryCache' );
							$this->root_entries[] = & $SiteMenuEntry; // $this->cache[$ment_ID];
							$this->subset_root_entries[$this->cache[$ment_ID]->{$this->subset_property}][] = & $SiteMenuEntry;
						}
					}

					if( $sort_root_entries )
					{ // Sort subset root entries as it was requested
						$this->sort_root_entries( $subset_ID );
					}

					$Timer->pause('reveal_children', false );

				}

				// Children have been revealed.
				$this->revealed_subsets[$subset_ID] = true;
			}
		}

	}


	/**
	 * Support function for reveal_children()
	 *
	 * @param integer
	 * @param integer
	 * @return true if root parent
	 */
	function check_path_to_root( $subset_ID, $ment_ID, $path_array = array() )
	{
		global $Debuglog;
		$padding = str_pad('',count($path_array),'*');
		$Debuglog->add( $padding.'Checking path to root for menu entry ID='.$ment_ID.' with path:'.implode(',',$path_array), 'SiteMenuEntryCache' );
		$SiteMenuEntry = & $this->subset_cache[$subset_ID][$ment_ID];
		if( is_null( $SiteMenuEntry->parent_ID ) )
		{	// Found root parent
			$Debuglog->add( $padding.'*OK', 'SiteMenuEntryCache' );
			return true;
		}
		// This is not the last parent...
		// Record path...
		$path_array[] = $SiteMenuEntry->ID;

		if( in_array( $SiteMenuEntry->parent_ID, $path_array ) )
		{	// We are about to enter an infinite loop
			$Debuglog->add( $padding.'*LOOP! -> assign to root', 'SiteMenuEntryCache' );
			$SiteMenuEntry->parent_ID = NULL;
			return false;
		}
		elseif( ! isset($this->cache[$SiteMenuEntry->parent_ID] ) )
		{
			$Debuglog->add( $padding.'*Missing parent ID('.$SiteMenuEntry->parent_ID.')! -> assign to root', 'SiteMenuEntryCache' );
			$SiteMenuEntry->parent_ID = NULL;
			return false;
		}

		// Recurse!
		return $this->check_path_to_root( $subset_ID, $SiteMenuEntry->parent_ID, $path_array );
	}


	/**
	 * Sort root entries or root entries in a subset if they are not sorted yet
	 *
	 * @param integer subset_ID, leave it on null to sort in all subsets
	 */
	function sort_root_entries( $subset_ID = NULL )
	{
		if( $subset_ID == NULL && empty($this->subset_property) )
		{
			if( !$this->sorted_flags['root_entries'] )
			{ // Root entries were not sorted yet
				usort( $this->root_entries, array( $this,'compare_site_menu_entries' ) );
				$this->sorted_flags['root_entries'] = true;
			}
			return;
		}

		if( $subset_ID == NULL )
		{
			foreach( array_keys( $this->subset_root_entries ) as $key )
			{
				if( $this->sorted_flags[$key] )
				{ // This subset was already sorted
					continue;
				}

				usort( $this->subset_root_entries[$key], array( $this,'compare_site_menu_entries' ) );
				$this->sorted_flags[$key] = true;
			}
			return;
		}

		if( empty($this->sorted_flags[$subset_ID]) )
		{ // This subset was not sorted yet
			usort( $this->subset_root_entries[$subset_ID], array( $this,'compare_site_menu_entries' ) );
			$this->sorted_flags[$subset_ID] = true;
		}
	}


	/**
	 * Return recursive display of loaded entries
	 *
	 * @param array callback functions (to format the display)
	 * @param integer|NULL NULL for all subsets
	 * @param array entries list to display
	 * @param integer depth of entries list
	 * @param integer Max depth of entries list, 0/empty - is infinite
	 * @param array Other params regarding the recursive display:
	 *   boolean sorted, sort entries or not - default value is false
	 *   string entry_path, array of Site Menu Entry ids from root Site Menu Entry to the selected Site Menu Entry
	 *   boolean expand_all, set true to expand all entries and not only the one in the Site Menu Entry path
	 *   etc.
	 *
	 * @return string recursive list of all loaded entries
	 */
	function recurse( $callbacks, $subset_ID = NULL, $entries_array = NULL, $level = 0, $max_level = 0, $params = array() )
	{
		$params = array_merge( array(
				'highlight_current' => true,
				'sorted' => false,
				'entry_path' => array(),
				'expand_all' => true,
				'subset_ID' => $subset_ID,
				'max_level' => $max_level
			), $params );

		// Make sure children have been (loaded and) revealed for specific subset:
		$this->reveal_children( $subset_ID, $params['sorted'] );

		if( is_null( $entries_array ) )
		{	// Get all parent entries:
			if( is_null( $subset_ID ) )
			{
				$entries_array = $this->root_entries;
			}
			elseif( isset( $this->subset_root_entries[$subset_ID] ) )
			{ // We have root entries for the requested subset:
				$entries_array = $this->subset_root_entries[$subset_ID];
			}
			else
			{
				$entries_array = array();
			}
		}

		$r = '';

		// Go through all entries at this level:
		foreach( $entries_array as $SiteMenuEntry )
		{
			// Check if Site Menu Entry is expended
			$params['is_selected'] = $params['highlight_current'] && in_array( $SiteMenuEntry->ID, $params['entry_path'] );
			$params['is_opened'] = $params['expand_all'] || $params['is_selected'];

			// Display a Site Menu Entry:
			if( is_array( $callbacks['line'] ) )
			{ // object callback:
				$r .= $callbacks['line'][0]->{$callbacks['line'][1]}( $SiteMenuEntry, $level, $params ); // <li> Site Menu Entry  - or - <tr><td>Site Menu Entry</td></tr> ...
			}
			else
			{
				$r .= $callbacks['line']( $SiteMenuEntry, $level, $params ); // <li> Site Menu Entry  - or - <tr><td>Site Menu Entry</td></tr> ...
			}

			if( $params['is_opened'] || ( $max_level > $level + 1 ) )
			{	// Iterate through sub entries recursively:
				$params['level'] = $level;
				$r .= $this->iterate_through_site_menu_entry_children( $SiteMenuEntry, $callbacks, true, $params );
			}
		}

		if( ! empty( $r ) )
		{ // We have something to display at this level, wrap in in before/after:
			$r_before = '';
			if( is_array( $callbacks['before_level'] ) )
			{ // object callback:
				$r_before .= $callbacks['before_level'][0]->{$callbacks['before_level'][1]}( $level ); // <ul>
			}
			elseif( isset( $callbacks['before_level'] ) )
			{
				$r_before .= $callbacks['before_level']( $level ); // <ul>
			}

			$r = $r_before.$r;

			if( is_array( $callbacks['after_level'] ) )
			{ // object callback:
				$r .= $callbacks['after_level'][0]->{$callbacks['after_level'][1]}( $level ); // </ul>
			}
			elseif( isset( $callbacks['after_level'] ) )
			{
				$r .= $callbacks['after_level']( $level ); // </ul>
			}
		}

		return $r;
	}


	/**
	 * Iterate through the given Site Menu Entry sub entries
	 *
	 * @param Object SiteMenuEntry
	 * @param array  Callback functions to display a Site Menu Entry
	 * @param boolean Set true to iterate sub entries recursively, false otherwise
	 * @param array Any additional params ( e.g. 'sorted', 'level', 'list_subs_start', etc. )
	 * @return string the concatenated callbacks result
	 */
	function iterate_through_site_menu_entry_children( $SiteMenuEntry, $callbacks, $recurse = false, $params = array() )
	{
		$r = "";
		$has_sub_entries = ! empty( $SiteMenuEntry->children );
		$params = array_merge( array(
				'sorted'    => false,
				'level'     => 0,
				'max_level' => 0,
				'subset_ID' => $SiteMenuEntry->menu_ID,
			), $params );

		if( $params['sorted'] && $has_sub_entries )
		{
			$SiteMenuEntry->sort_children();
		}

		if( $has_sub_entries )
		{	// Display Site Menu Entry:
			$entry_index = 0;
			$subentries_to_display = array();
			$entry_children_ids = array_keys( $SiteMenuEntry->children );
			$has_more_children = isset( $entry_children_ids[$entry_index] );

			if( ( $has_more_children ) && isset( $params['list_subs_start'] ) )
			{
				$r .= $params['list_subs_start'];
			}

			while( $has_more_children )
			{
				$current_sub_SiteMenuEntry = $has_more_children ? $SiteMenuEntry->children[$entry_children_ids[$entry_index]] : NULL;
				if( $current_sub_SiteMenuEntry != NULL )
				{
					$subentries_to_display[] = $current_sub_SiteMenuEntry;
					$has_more_children = isset( $entry_children_ids[++$entry_index] );
				}
			}

			if( ! empty( $subentries_to_display ) )
			{ // Display all subentries which were not displayed yet
				if( $recurse )
				{
					$r .= $this->recurse( $callbacks, $params['subset_ID'], $subentries_to_display, $params['level'] + 1, $params['max_level'], $params );
				}
				else
				{ // Display each Site Menu Entry without recursion
					foreach( $subentries_to_display as $sub_SiteMenuEntry )
					{ // Display each Site Menu Entry:
						if( is_array( $callbacks['line'] ) )
						{ // object callback:
							$r .= $callbacks['line'][0]->{$callbacks['line'][1]}( $sub_SiteMenuEntry, 0, $params ); // <li> Site Menu Entry  - or - <tr><td>Site Menu Entry</td></tr> ...
						}
						else
						{
							$r .= $callbacks['line']( $sub_SiteMenuEntry, 0, $params ); // <li> Site Menu Entry  - or - <tr><td>Site Menu Entry</td></tr> ...
						}
					}
				}
			}

			if( ( $entry_index > 0 ) && isset( $params['list_subs_end'] ) )
			{
				$r .= $params['list_subs_end'];
			}
		}
		elseif( isset( $callbacks['no_children'] ) )
		{ // Display message when no children
			if( is_array( $callbacks['no_children'] ) )
			{ // object callback:
				$r .= $callbacks['no_children'][0]->{$callbacks['no_children'][1]}( $SiteMenuEntry, $params['level'] + 1 ); // </li>
			} else {
				$r .= $callbacks['no_children']( $SiteMenuEntry, $params['level'] + 1 ); // </li>
			}
		}

		return $r;
	}


	/**
	 * Return recursive select options list of all loaded entries
	 *
	 * @param integer selected Site Menu Entry in the select input
	 * @param integer NULL for all subsets
	 * @param boolean Include the root element?
	 * @param array SiteMenuEntry objects to display (will recurse from those starting points)
	 * @param integer depth of entries list
	 * @param array IDs of entries to exclude (their children will be ignored to)
	 *
	 * @return string select options list of all loaded entries
	 */
	function recurse_select( $selected = NULL, $subset_ID = NULL, $include_root = false, $entries_array = NULL,
							$level = 0, $exclude_array = array() )
	{
		// pre_dump( $exclude_array );

		// Make sure children have been revealed for specific subset:
		$this->reveal_children( $subset_ID );

		if( is_null( $entries_array ) )
		{	// Get all parent menu entries:
			$entries_array = $this->root_entries;
		}

		// Sort entries alphabetically or manually depending on settings:
		usort( $entries_array, array( $this, 'compare_site_menu_entries' ) );

		$r = '';

		if( $include_root )
		{
			$r .= '<option value="">'.T_('Root').'</option>';
			$level++;
		}

		foreach( $entries_array as $SiteMenuEntry )
		{
			if( in_array( $SiteMenuEntry->ID, $exclude_array ) )
			{	// We want to exclude that Site Menu Entry:
				continue;
			}

			// Set Site Menu Entry indentation in the select:
			$indent = '';
			for($i = 0; $i < $level; $i++)
			{
				$indent .='&nbsp;&nbsp;';
			}
			// Set Site Menu Entry option:
			$r .= '<option value="'.$SiteMenuEntry->ID.'" ';
			if( $SiteMenuEntry->ID == $selected ) $r .= ' selected="selected"';
			$r .= ' >'.$indent.$SiteMenuEntry->text.'</option>';

			if( !empty( $SiteMenuEntry->children ) )
			{	// Add children entries:
				$r .= $this->recurse_select( $selected, $subset_ID, false, $SiteMenuEntry->children, $level+1, $exclude_array );
			}
		}

		return $r;
	}


	/**
	 * Compare two Site Menu Entries based on the parent/menu sort Site Menu Entry setting
	 *
	 * @param Site Menu Entry A
	 * @param Site Menu Entry B
	 * @return number -1 if A < B, 1 if A > B, 0 if A == B
	 */
	static function compare_site_menu_entries( $a_SiteMenuEntry, $b_SiteMenuEntry )
	{
		if( $a_SiteMenuEntry == NULL || $b_SiteMenuEntry == NULL ) {
			debug_die('Invalid Site Menu Entry objects received to compare.');
		}

		if( $a_SiteMenuEntry->ID == $b_SiteMenuEntry->ID )
		{ // The two Site Menu Entries are the same
			return 0;
		}

		if( $a_SiteMenuEntry->parent_ID != $b_SiteMenuEntry->parent_ID )
		{ // Two Site Menu Entries from the same Site Menu, but different parrents
			// Compare those parents of these Site Menu Entries which have a common parent Site Menu Entry or they are both root Site Menu Entries.
			$path_to_root_a = array_reverse( $this->get_menu_entry_path( $a_SiteMenuEntry->menu_ID, $a_SiteMenuEntry->ID ) );
			$path_to_root_b = array_reverse( $this->get_menu_entry_path( $b_SiteMenuEntry->menu_ID, $b_SiteMenuEntry->ID ) );
			$index = 0;
			while( isset( $path_to_root_a[$index] ) && isset( $path_to_root_b[$index] ) )
			{
				if( $path_to_root_a[$index] != $path_to_root_b[$index] )
				{ // The first different parent on the same level was found, compare parent objects
					$parent_a_SiteMenuEntry = $this->get_by_ID( $path_to_root_a[$index] );
					$parent_b_SiteMenuEntry = $this->get_by_ID( $path_to_root_b[$index] );
					return $this->compare_site_menu_entries( $parent_a_SiteMenuEntry, $parent_b_SiteMenuEntry );
				}
				$index++;
			}

			// One of the Site Menu Entries is a parent of the other, the parent is considered greater than the other
			return isset( $path_to_root_a[$index] ) ? 1 : -1;
		}

		if( $a_SiteMenuEntry->order === NULL )
		{ // NULL values are greater than any number
			$result = ( $b_SiteMenuEntry->order !== NULL ) ? 1 : 0;
		}
		elseif( $b_SiteMenuEntry->order === NULL )
		{ // NULL values are greater than any number, so a is lower than b
			$result = -1;
		}
		else
		{
			$result = ( $a_SiteMenuEntry->order > $b_SiteMenuEntry->order ) ? 1 : ( ( $a_SiteMenuEntry->order < $b_SiteMenuEntry->order ) ? -1 : 0 );
		}

		if( $result == 0 )
		{ // In case if the order fields are equal order by ID
			$result = $a_SiteMenuEntry->ID > $b_SiteMenuEntry->ID ? 1 : -1;
		}

		return $result;
	}


	/**
	 * Get chapters by collection ID and parent ID
	 *
	 * @param integer Site Menu ID
	 * @param integer Parent Site Menu Entry ID
	 * @param boolean Set to true to sort the result, leave it on false otherwise
	 *
	 * @return array set of chapters
	 */
	function get_entries( $menu_ID, $parent_ID = 0, $sorted = false )
	{
		$this->reveal_children( $menu_ID, $sorted );

		if( $parent_ID == 0 )
		{
			return $this->subset_root_cats[$coll_ID];
		}

		$parent_SiteMenuEntry = & $this->get_by_ID( $parent_ID );
		if( $sorted )
		{	// Sort Site Menu Entry:
			$parent_SiteMenuEntry->sort_children();
		}

		return $parent_SiteMenuEntry->children;
	}
}

?>
<?php
/**
 * This file implements the Category list Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2008 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_category_list_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_category_list' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'category-list-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Category list');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('List of all categories; click filters blog on selected category.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array local params
	 *  - 'title': block title (string, default "Categories")
	 *  - 'option_all': "All categories" link title, empty to disable (string, default "All")
	 *  - 'use_form': Add a form with checkboxes to allow selection of multiple categories (boolean)
	 *  - 'disp_names_for_coll_list': Display blog names, if this is an aggregated blog? (boolean)
	 *  - 'display_checkboxes': Add checkboxes (but not a complete form) to allow selection of multiple categories (boolean)
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
					'type' => 'text',
					'label' => T_('Block title'),
					'defaultvalue' => T_('Categories'),
					'maxlength' => 100,
				),
			'option_all' => array(
					'type' => 'text',
					'label' => T_('Option "All"'),
					'defaultvalue' => T_('All'),
					'maxlength' => 100,
					'note' => T_('The "All categories" link allows to reset the filter. Leave blank if you want no such option.'),
				),
			'use_form' => array(
					'type' => 'checkbox',
					'label' => T_('Use form'),
					'defaultvalue' => 0,
					'note' => T_('Add checkboxes to allow selection of multiple categories.'),
				),
			'default_match' => array(
					'label' => /* TRANS: here we ask to select 'OR', 'NOR' or 'AND' */ T_('Default combining'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'or', T_('OR') ),
							array( 'nor', T_('NOR') ),
							array( 'and', T_('AND') ) ),
					'defaultvalue' => 'or',
				),
			'disp_names_for_coll_list' => array(
					'type' => 'checkbox',
					'label' => T_('Display blog names'),
					'defaultvalue' => 1, /* previous behaviour */
					'note' => T_('Display blog names, if this is an aggregated blog.'),
				),
			'exclude_cats' => array(
					'type' => 'text',
					'label' => T_('Exclude categories'),
					'note' => T_('A comma-separated list of category IDs that you want to exclude from the list.'),
					'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																		'error'   => T_('Invalid list of Category IDs.') ),
				),
			'max_colls' => array(
					'type' => 'text',
					'label' => T_('Max collections'),
					'note' => T_('This allows to limit processing time and list length in case of large aggregated collections.'),
					'defaultvalue' => 15,
					'size' => 2,
				),
			'start_level' => array(
					'type' => 'text',
					'label' => T_('Start level'),
					'note' => '',
					'defaultvalue' => 1,
					'size' => 2,
				),
			'level' => array(
					'type' => 'text',
					'label' => T_('Recurse'),
					'note' => T_('levels'),
					'defaultvalue' => 5,
					'size' => 2,
				),
			'mark_first_selected' => array(
					'type' => 'checkbox',
					'label' => T_('Show as selected'),
					'defaultvalue' => 1,
					'note' => T_('Mark first selected cat (highest level selected cat)'),
				),
			'mark_children' => array(
					'type' => 'checkbox',
					'label' => '',
					'defaultvalue' => 1,
					'note' => T_('Mark descendants (children of first selected cat)'),
				),
			'mark_parents' => array(
					'type' => 'checkbox',
					'label' => '',
					'defaultvalue' => 1,
					'note' => T_('Mark ancestors (ancestors of highest level cat)'),
				),

			// Hidden, used by the item list sidebar in the backoffice.
			'display_checkboxes' => array(
					'label' => 'Internal: Display checkboxes', // This key is required
					'defaultvalue' => 0,
					'no_edit' => true,
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Disable "allow blockcache" because this widget uses the selected items
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		parent::init_display( $params );

		// Disable "allow blockcache" because this widget uses the selected items
		$this->disp_params['allow_blockcache'] = 0;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $cat_modifier;
		global $Blog;

		$this->init_display( $params );

		/**
		 * @var ChapterCache
		 */
		$ChapterCache = & get_ChapterCache();

		$callbacks = array(
			'line'         => array( $this, 'cat_line' ),
			'before_level' => array( $this, 'cat_before_level' ),
			'after_level'  => array( $this, 'cat_after_level' )
		);

		if( !empty( $params['callback_posts'] ) )
		{
			$callbacks['posts'] = $params['callback_posts'];
		}

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		if ( $this->disp_params['use_form'] )
		{	// We want a complete form:
			echo '<form method="get" action="'.$Blog->gen_blogurl().'">';
		}

		// Set depth level
		$depth_level = intval( $this->disp_params['level'] );
		$start_level = intval( $this->disp_params['start_level'] );
		if( $depth_level > 0 )
		{ // Don't limit if depth is null
			$depth_level += $start_level > 1 ? $start_level - 1 : 0;
		}

		$aggregate_coll_IDs = $Blog->get_setting('aggregate_coll_IDs');
		if( empty($aggregate_coll_IDs) )
		{ // ____________________ We want to display cats for ONE blog ____________________
			$tmp_disp = '';

			if( $this->disp_params['option_all'] && intval( $this->disp_params['start_level'] ) < 2 )
			{ // We want to display a link to all cats:
				$tmp_disp .= $this->add_cat_class_attr( $this->disp_params['item_start'], 'evo_cat_all' );
				$tmp_disp .= '<a href="';
				if( $this->disp_params['link_type'] == 'context' )
				{	// We want to preserve current browsing context:
					$tmp_disp .= regenerate_url( 'cats,catsel' );
				}
				else
				{
					$tmp_disp .= $Blog->gen_blogurl();
				}
				$tmp_disp .= '">'.$this->disp_params['option_all'].'</a>';
				$tmp_disp .= $this->disp_params['item_end'];
			}

			// Load current collection categories (if needed) and recurse through them:
			$r = $tmp_disp . $ChapterCache->recurse( $callbacks, /* subset ID */ $Blog->ID, NULL, 0, $depth_level, array( 'sorted' => true ) );

			if( ! empty($r) )
			{
				echo $this->disp_params['list_start'];
				echo $r;
				echo $this->disp_params['list_end'];
			}
		}
		else
		{ // ____________________ We want to display cats for SEVERAL blogs ____________________

			$BlogCache = & get_BlogCache();

			// Make sure everything is loaded at once (vs multiple queries)
			// fp> TODO: scaling
			$ChapterCache->load_all();

			echo $this->disp_params['collist_start'];

			if( $aggregate_coll_IDs == '*' )
			{
				$BlogCache->load_all();
				$coll_ID_array = $BlogCache->get_ID_array();
			}
			else
			{
				$coll_ID_array = sanitize_id_list($aggregate_coll_IDs, true);
			}

			// Get max allowed collections for this widget:
			$max_colls = intval( $this->disp_params['max_colls'] );

			foreach( $coll_ID_array as $c => $curr_blog_ID )
			{
				if( $max_colls > 0 && $max_colls <= $c )
				{	// Limit by max collections number:
					break;
				}

				// Get blog:
				$loop_Blog = & $BlogCache->get_by_ID( $curr_blog_ID, false );
				if( empty($loop_Blog) )
				{	// That one doesn't exist (any more?)
					continue;
				}

				// Display blog title, if requested:
				if( $this->disp_params['disp_names_for_coll_list'] )
				{
					echo $this->disp_params['coll_start'];
					echo '<a href="';
					if( $this->disp_params['link_type'] == 'context' )
					{	// We want to preserve current browsing context:
						echo regenerate_url( 'blog,cats,catsel', 'blog='.$curr_blog_ID );
					}
					else
					{
						$loop_Blog->disp('url','raw');
					}
					echo '">';
					$loop_Blog->disp('name');
					echo '</a>';
					echo $this->disp_params['coll_end'];
				}

				// Load current collection categories (if needed) and recurse through them:
				$r = $ChapterCache->recurse( $callbacks, /* subset ID */ $curr_blog_ID, NULL, 0, $depth_level, array( 'sorted' => true ) );

				if( ! empty($r) )
				{
					echo $this->disp_params['list_start'];
					echo $r;
					echo $this->disp_params['list_end'];
				}
			}
		}


		if( $this->disp_params['use_form'] || $this->disp_params['display_checkboxes'] )
		{ // We want to add form fields:
			if( $cat_modifier == '-' || ( empty( $cat_modifier ) && $this->disp_params['default_match'] == 'nor' ) )
			{ // Select NOR
				$cat_modifier_selected = 'nor';
			}
			else if( $cat_modifier == '*' || ( empty( $cat_modifier ) && $this->disp_params['default_match'] == 'and' ) )
			{ // Select AND
				$cat_modifier_selected = 'and';
			}
			else
			{ // Select OR
				$cat_modifier_selected = 'or';
			}
		?>
		<div class="multiple_cat_match_options">
			<p class="multiple_cat_match_title"><?php echo /* Any/None/All of the selected categories */ T_('Retain only results that match:'); ?></p>
			<div class="tile">
				<input type="radio" name="cat" value="|" id="cat_or" class="radio"<?php if( $cat_modifier_selected == 'or' ) echo ' checked="checked"'; ?> />
				<label for="cat_or"><?php echo T_( 'Any selected category (OR)' ); ?></label>
			</div>
			<div class="tile">
				<input type="radio" name="cat" value="-" id="cat_nor" class="radio"<?php if( $cat_modifier_selected == 'nor' ) echo ' checked="checked"'; ?> />
				<label for="cat_nor"><?php echo T_( 'None of the selected categories (NOR)' ); ?></label>
			</div>
			<div class="tile">
				<input type="radio" name="cat" value="*" id="cat_and" class="radio"<?php if( $cat_modifier_selected == 'and' ) echo ' checked="checked"'; ?> />
				<label for="cat_and"><?php echo T_( 'All of the selected categories (AND)' ); ?></label>
			</div>
			<?php
			if( $this->disp_params['use_form'] )
			{ // We want a complete form:
			?>
				<div class="tile">
					<input type="submit" value="<?php echo T_( 'Filter categories' ); ?>" class="btn btn-info" />
				</div>
				</form>
			<?php
			}
			?>
		</div>
		<?php
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Callback: Generate category line when it has children
	 *
	 * @param object Chapter we want to display
	 * @param integer Level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_line( $Chapter, $level )
	{
		global $cat_array;

		if( ! isset( $cat_array ) )
		{
			$cat_array = array();
		}

		$exclude_cats = sanitize_id_list( $this->disp_params['exclude_cats'], true );
		if( in_array( $Chapter->ID, $exclude_cats ) )
		{ // Cat ID is excluded, skip it
			return;
		}

		// ID of the current selected category
		$first_selected_cat_ID = isset( $cat_array[0] ) ? $cat_array[0] : 0;

		if( ! isset( $this->disp_params['current_parents'] ) )
		{ // Try to find the parent categories in order to select it because of widget setting is enabled
			$this->disp_params['current_all_cats'] = array(); // All children of the root parent of the selcted category
			$this->disp_params['current_parents'] = array(); // All parents of the selected category
			$this->disp_params['current_selected_level'] = 0; // Level of the selected category
			if( $first_selected_cat_ID > 0 )
			{
				$this->disp_params['current_selected_level'] = $this->disp_params['current_selected_level'] + 1;
				$ChapterCache = & get_ChapterCache();
				$parent_Chapter = & $ChapterCache->get_by_ID( $first_selected_cat_ID, false, false );

				while( $parent_Chapter !== NULL )
				{ // Go up to the first/root category
					$root_parent_ID = $parent_Chapter->ID;
					if( $parent_Chapter = & $parent_Chapter->get_parent_Chapter() )
					{
						$this->disp_params['current_parents'][] = $parent_Chapter->ID;
						$this->disp_params['current_all_cats'][] = $parent_Chapter->ID;
						$this->disp_params['current_selected_level'] = $this->disp_params['current_selected_level'] + 1;
					}
				}

				// Load all categories of the current selected path (these categories should be visible on page)
				$this->disp_params['current_all_cats'] = $cat_array;
				$this->load_category_children( $root_parent_ID, $this->disp_params['current_all_cats'], $this->disp_params['current_parents'] );
			}
		}

		$parent_cat_is_visible = isset($this->disp_params['parent_cat_is_visible']) ? $this->disp_params['parent_cat_is_visible'] : false;
		$start_level = intval( $this->disp_params['start_level'] );
		if( $start_level > 1 &&
		    ( $start_level > $level + 1 ||
		      ( ! in_array( $Chapter->ID, $this->disp_params['current_all_cats'] ) && ! $this->disp_params['parent_cat_is_visible'] ) ||
		      ( $this->disp_params['current_selected_level'] < $level && ! $this->disp_params['parent_cat_is_visible'] )
		    ) )
		{ // Don't show this item because of level restriction
			$this->disp_params['parent_cat_is_visible'] = false;
			//return '<span style="font-size:10px">hidden: ('.$level.'|'.$this->disp_params['current_selected_level'].')</span>';
			return '';
		}
		elseif( ! isset( $this->disp_params['current_cat_level'] ) )
		{ // Save level of the current selected category
			$this->disp_params['current_cat_level'] = $level;
			$this->disp_params['parent_cat_is_visible'] = true;
		}

		if( // First category that should be selected:
		    ( $this->disp_params['mark_first_selected'] && $Chapter->ID == $first_selected_cat_ID ) ||
		    // OR Select only children of the current category(don't select parent category):
		    ( $this->disp_params['mark_children'] && $Chapter->ID != $first_selected_cat_ID && in_array( $Chapter->ID, $cat_array ) ) ||
		    // OR Select only parents of the current category(don't select current category):
		    ( $this->disp_params['mark_parents'] && $Chapter->ID != $first_selected_cat_ID && in_array( $Chapter->ID, $this->disp_params['current_parents'] ) ) )
		{ // This category should be selected
			$start_tag = $this->disp_params['item_selected_start'];
		}
		else
		{
			$start_tag = $this->disp_params['item_start'];
		}

		if( empty( $Chapter->children ) )
		{	// Add class name "evo_cat_leaf" for categories without children:
			$start_tag = $this->add_cat_class_attr( $start_tag, 'evo_cat_leaf' );
		}
		else
		{	// Add class name "evo_cat_node" for categories with children:
			$start_tag = $this->add_cat_class_attr( $start_tag, 'evo_cat_node' );
		}

		if( $Chapter->meta )
		{	// Add class name "evo_cat_meta" for meta categories:
			$start_tag = $this->add_cat_class_attr( $start_tag, 'evo_cat_meta' );
		}

		$r = $start_tag;

		if( $this->disp_params['use_form'] || $this->disp_params['display_checkboxes'] )
		{ // We want to add form fields:
			$cat_checkbox_params = '';
			if( $Chapter->meta )
			{ // Disable the checkbox of meta category ( and hide it by css )
				$cat_checkbox_params = ' disabled="disabled"';
			}

			$r .= '<label><input type="checkbox" name="catsel[]" value="'.$Chapter->ID.'" class="checkbox middle"';
			if( in_array( $Chapter->ID, $cat_array ) )
			{ // This category is in the current selection
				$r .= ' checked="checked"';
			}
			$r .= $cat_checkbox_params.' /> ';
		}

		$cat_name = $Chapter->dget('name');
		if( $Chapter->lock && isset( $this->disp_params['show_locked'] ) && $this->disp_params['show_locked'] )
		{
			$cat_name .= '<span style="padding:0 5px;" >'.get_icon( 'file_not_allowed', 'imgtag', array( 'title' => T_('Locked') ) ).'</span>';
		}

		// Make a link from category name
		$r .= '<a href="';
		if( $this->disp_params['link_type'] == 'context' )
		{ // We want to preserve current browsing context:
			$r .= regenerate_url( 'cats,catsel', 'cat='.$Chapter->ID );
		}
		else
		{
			$r .= $Chapter->get_permanent_url();
		}
		$r .= '">'.$cat_name.'</a>';

		if( $this->disp_params['use_form'] || $this->disp_params['display_checkboxes'] )
		{ // We want to add form fields:
			$r .= '</label>';
		}

		// End the line even if it has children, since this is the end of one single item
		// To close the whole group of categories with all of it's children see @cat_before_level and @cat_after_level
		// Note: If this solution will not work, and we can't add the 'item_end' here, then create new after_line callback,
		// which then must be called from a the ChapterCache recurse method
		$r .= $this->disp_params['item_end'];

		return $r;
	}


	/**
	 * Callback: Generate code when entering a new level
	 *
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_before_level( $level )
	{
		$start_level = intval( $this->disp_params['start_level'] );
		if( $start_level > 1 && $this->disp_params['current_cat_level'] >= $level )
		{ // Don't show a start of group because of level restriction
			return;
		}

		if( $level > 0 )
		{ // If this is not the root:
			return $this->disp_params['group_start'];
		}
	}


	/**
	 * Callback: Generate code when exiting from a level
	 *
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_after_level( $level )
	{
		$start_level = intval( $this->disp_params['start_level'] );
		if( $start_level > 1 && $this->disp_params['current_cat_level'] >= $level )
		{ // Don't show a start of group because of level restriction
			return;
		}

		if( $level > 0 )
		{ // If this is not the root:
			return $this->disp_params['group_end']
				// End current (parent) line:
				.$this->disp_params['item_end'];
		}
	}


	/**
	 * Add new class name for start tag
	 *
	 * @param string HTML start tag: e.g. <div class="div_class"> or <div>
	 * @param string New class name
	 * @return string HTML start tag with new added class name
	 */
	function add_cat_class_attr( $start_tag, $class_name )
	{
		if( preg_match( '/ class="[^"]*"/i', $start_tag ) )
		{ // Append to already existing attribute
			return preg_replace( '/ class="([^"]*)"/i', ' class="$1 '.$class_name.'"', $start_tag );
		}
		else
		{ // Add new attribute for meta class
			return preg_replace( '/^<([^\s>]+)/', '<$1 class="'.$class_name.'"', $start_tag );
		}
	}


	/**
	 * Load the children with restriction by level depth first level
	 *
	 * @param integer Parent category ID
	 * @param array Array with children categories that modified by reference
	 * @param array We should load children only of these categories (these parents is the selected path)
	 */
	function load_category_children( $cat_ID, & $cats, $allowed_parents = array() )
	{
		global $DB;

		// Try to get all children of the given category
		$SQL = new SQL();
		$SQL->SELECT( 'cat_ID' );
		$SQL->FROM( 'T_categories' );
		$SQL->WHERE( 'cat_parent_ID = '.$DB->quote( $cat_ID ) );

		$category_children = $DB->get_col( $SQL->get() );

		foreach( $category_children as $category_child_ID )
		{
			$cats[] = $category_child_ID;
			if( in_array( $category_child_ID, $allowed_parents ) )
			{ // Load children if it is not restricted by level depth
				$this->load_category_children( $category_child_ID, $cats, $allowed_parents );
			}
		}
	}
}

?>
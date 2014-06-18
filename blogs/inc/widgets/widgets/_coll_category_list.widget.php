<?php
/**
 * This file implements the Category list Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2008 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_category_list.widget.php 6411 2014-04-07 15:17:33Z yura $
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
	function coll_category_list_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_category_list' );
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
					'label' => T_('Default Match'),
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
				),

			// Hidden, used by the item list sidebar in the backoffice.
			'display_checkboxes' => array(
					'label' => 'Internal: Display checkboxes', // This key is required
					'defaultvalue' => 0,
					'no_edit' => true,
				),
			), parent::get_param_definitions( $params ) );

		return $r;
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
			'no_children'  => array( $this, 'cat_no_children' ),
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

		$aggregate_coll_IDs = $Blog->get_setting('aggregate_coll_IDs');
		if( empty($aggregate_coll_IDs) )
		{ // ____________________ We want to display cats for ONE blog ____________________
			$tmp_disp = '';

			if( $this->disp_params['option_all'] )
			{	// We want to display a link to all cats:
				$tmp_disp .= $this->add_cat_class_attr( $this->disp_params['item_start'], 'all' );
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

			$r = $tmp_disp . $ChapterCache->recurse( $callbacks, $Blog->ID );

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
			foreach( $coll_ID_array as $curr_blog_ID )
			{
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

				$r = $ChapterCache->recurse( $callbacks, $curr_blog_ID );

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
			<p class="multiple_cat_match_title"><?php echo T_('Retain only results that match:'); ?></p>
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
					<input type="submit" value="<?php echo T_( 'Filter categories' ); ?>" />
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
	 * @param Chapter generic category we want to display
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_line( $Chapter, $level )
	{
		global $cat_array;

		if( !isset($cat_array) )
		{
			$cat_array = array();
		}

		$exclude_cats = sanitize_id_list($this->disp_params['exclude_cats'], true);
		if( in_array( $Chapter->ID, $exclude_cats ) )
		{ // Cat ID is excluded, skip it
			return;
		}

		if( in_array( $Chapter->ID, $cat_array ) )
		{ // This category is in the current selection
			$start_tag = $this->disp_params['item_selected_start'];
		}
		else if( empty( $Chapter->children ) )
		{ // This category has no children
			$start_tag = $this->disp_params['item_last_start'];
		}
		else
		{
			$start_tag = $this->disp_params['item_start'];
		}

		if( $Chapter->meta )
		{ // Add class name "meta" for meta categories
			$start_tag = $this->add_cat_class_attr( $start_tag, 'meta' );
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

		// Do not end line here because we need to include children first!
		// $r .= $this->disp_params['item_end'];

		return $r;
	}


	/**
	 * Callback: Generate category line when it has no children
	 *
	 * @param Chapter generic category we want to display
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_no_children( $Chapter, $level )
	{
		// End current line:
		return $this->disp_params['item_end'];
	}


	/**
	 * Callback: Generate code when entering a new level
	 *
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_before_level( $level )
	{
		$r = '';
		if( $level > 0 )
		{	// If this is not the root:
			$r .= $this->disp_params['group_start'];
		}
		return $r;
	}


	/**
	 * Callback: Generate code when exiting from a level
	 *
	 * @param int level of the category in the recursive tree
	 * @return string HTML
	 */
	function cat_after_level( $level )
	{
		$r = '';
		if( $level > 0 )
		{	// If this is not the root:
			$r .= $this->disp_params['group_end'];
			// End current (parent) line:
			$r .= $this->disp_params['item_end'];
		}
		return $r;
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
}

?>
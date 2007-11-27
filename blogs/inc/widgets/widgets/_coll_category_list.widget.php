<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php' );

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
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display list of categories; click filters on selected category');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array(
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
		);

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
		$ChapterCache = & get_Cache( 'ChapterCache' );

		$callbacks = array(
			'line' 			 	 => array( $this, 'cat_line' ),
			'no_children'  => array( $this, 'cat_no_children' ),
			'before_level' => array( $this, 'cat_before_level' ),
			'after_level'	 => array( $this, 'cat_after_level' )
		);

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		$aggregate_coll_IDs = $Blog->get_setting('aggregate_coll_IDs');
		if( empty($aggregate_coll_IDs) )
		{ // ____________________ We want to display cats for ONE blog ____________________
			$tmp_disp = '';

			if( $this->disp_params['option_all'] )
			{	// We want to display a link to all cats:
				$tmp_disp .= $this->disp_params['item_start'].'<a href="';
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

			$BlogCache = & get_Cache( 'BlogCache' );

			// Make sure everything is loaded at once (vs multiple queries)
			// fp> TODO: scaling
			$ChapterCache->load_all();

			echo $this->disp_params['collist_start'];

			$coll_ID_array = explode( ',', $aggregate_coll_IDs );
			foreach( $coll_ID_array as $curr_blog_ID )
			{
				// Get blog:
				$loop_Blog = & $BlogCache->get_by_ID( $curr_blog_ID, false );
				if( empty($loop_Blog) )
				{	// That one doesn't exist (any more?)
					continue;
				}

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

				$r = $ChapterCache->recurse( $callbacks, $curr_blog_ID );

				if( ! empty($r) )
				{
					echo $this->disp_params['list_start'];
					echo $r;
					echo $this->disp_params['list_end'];
				}
			}
		}



		if( $this->disp_params['use_form'] )
		{	// We want to add form fields:
		?>
			<div class="tile">
				<input type="radio" name="cat" value="" id="catANY" class="radio" <?php if( $cat_modifier != '-' && $cat_modifier != '*' ) echo 'checked="checked" '?> />
				<label for="catANY"><?php echo T_('ANY') ?></label>
			</div>
			<div class="tile">
				<input type="radio" name="cat" value="-" id="catANYBUT" class="radio" <?php if( $cat_modifier == '-' ) echo 'checked="checked" '?> />
				<label for="catANYBUT"><?php echo T_('ANY BUT') ?></label>
			</div>
			<div class="tile">
				<input type="radio" name="cat" value="*" id="catALL" class="radio" <?php if( $cat_modifier == '*' ) echo 'checked="checked" '?> />
				<label for="catALL"><?php echo T_('ALL') ?></label>
			</div>
		<?php
		}


		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Generate category line when it has children
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

		if( in_array( $Chapter->ID, $cat_array ) )
		{ // This category is in the current selection
			$r = $this->disp_params['item_selected_start'];
		}
		else
		{
			$r = $this->disp_params['item_start'];
		}

		if( $this->disp_params['use_form'] )
		{	// We want to add form fields:
			$r .= '<label><input type="checkbox" name="catsel[]" value="'.$Chapter->ID.'" class="checkbox"';
			if( in_array( $Chapter->ID, $cat_array ) )
			{ // This category is in the current selection
				$r .= ' checked="checked"';
			}
			$r .= ' /> ';
		}

		$r .= '<a href="';

		if( $this->disp_params['link_type'] == 'context' )
		{	// We want to preserve current browsing context:
			$r .= regenerate_url( 'cats,catsel', 'cat='.$Chapter->ID );
		}
		else
		{
			$r .= $Chapter->get_permanent_url();
		}

		$r .= '">'.$Chapter->dget('name').'</a>';

		if( $this->disp_params['use_form'] )
		{	// We want to add form fields:
			$r .= '</label>';
		}

		// Do not end line here because we need to include children first!
		// $r .= $this->disp_params['item_end'];

		return $r;
	}


	/**
	 * Generate category line when it has no children
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
	 * Generate code when entering a new level
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
	 * Generate code when exiting from a level
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

}


/*
 * $Log$
 * Revision 1.3  2007/11/27 10:01:05  yabs
 * validation
 *
 * Revision 1.2  2007/07/01 15:12:28  fplanque
 * simplified 'use_form'
 *
 * Revision 1.1  2007/07/01 03:55:04  fplanque
 * category plugin replaced by widget
 *
 */
?>
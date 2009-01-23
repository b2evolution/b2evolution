<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
		return T_('List of all categories; click filters blog on selected category.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array local params
	 *  - 'title': block title (string, default "Categories")
	 *  - 'option_all': "All categories" link title, empty to disable (string, default "All")
	 *  - 'use_form': Add checkboxes to allow selection of multiple categories (boolean)
	 *  - 'disp_names_for_coll_list': Display blog names, if this is an aggregated blog? (boolean)
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
			'disp_names_for_coll_list' => array(
					'type' => 'checkbox',
					'label' => T_('Display blog names'),
					'defaultvalue' => 1, /* previous behaviour */
					'note' => T_('Display blog names, if this is an aggregated blog.'),
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
		$ChapterCache = & get_Cache( 'ChapterCache' );

		$callbacks = array(
			'line'         => array( $this, 'cat_line' ),
			'no_children'  => array( $this, 'cat_no_children' ),
			'before_level' => array( $this, 'cat_before_level' ),
			'after_level'  => array( $this, 'cat_after_level' )
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

			if( $aggregate_coll_IDs == '*' )
			{
				$BlogCache->load_all();
				$coll_ID_array = $BlogCache->get_ID_array();
			}
			else
			{
				$coll_ID_array = explode( ',', $aggregate_coll_IDs );
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
}


/*
 * $Log$
 * Revision 1.14  2009/01/23 00:06:25  blueyed
 * Support '*' for aggregate_coll_IDs in coll_category_list.widget, too.
 *
 * Revision 1.13  2008/05/30 19:57:37  blueyed
 * really fix indent
 *
 * Revision 1.12  2008/05/30 16:39:06  blueyed
 * Indent, doc
 *
 * Revision 1.11  2008/05/06 23:35:47  fplanque
 * The correct way to add linebreaks to widgets is to add them to $disp_params when the container is called, right after the array_merge with defaults.
 *
 * Revision 1.9  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.8  2008/01/06 17:52:50  fplanque
 * minor/doc
 *
 * Revision 1.7  2008/01/06 15:35:34  blueyed
 * - added "disp_names_for_coll_list" param
 * -doc
 *
 * Revision 1.6  2007/12/23 16:16:18  fplanque
 * Wording improvements
 *
 * Revision 1.5  2007/12/23 14:14:25  fplanque
 * Enhanced widget name display
 *
 * Revision 1.4  2007/12/22 19:54:59  yabs
 * cleanup from adding core params
 *
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

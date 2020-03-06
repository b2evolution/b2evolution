<?php
/**
 * Widget class to display current Category's Contents (Sub-categories and Items) as List or Tiles (using Template)
 *  Also works in collection root.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
class cat_content_list_Widget extends ComponentWidget
{
	var $icon = 'list';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'cat_content_list' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'cat-content-list-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Category Contents (List/Tiles)');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the contents (sub-categories and posts) of the current category (or collection root).');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $current_User, $admin_url;

		// Get available templates:
		$context = 'content_list_master';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );
		$template_options = array( NULL => T_('No template') ) + $TemplateCache->get_code_option_array();
		$template_input_suffix = ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? '&nbsp;'
				.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context, NULL, NULL, NULL,
				array( 'onclick' => 'return b2template_list_highlight( this )' ),
				array( 'title' => T_('Manage templates').'...' ) ) : '' );

		// Get all catgories of the widget Collection:
		$ChapterCache = & get_ChapterCache();
		$chapter_options = $ChapterCache->recurse_select_options( $this->get_Blog()->ID );

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $template_options,
					'defaultvalue' => 'content_list',
					'input_suffix' => $template_input_suffix,
					'class' => 'evo_template_select',
				),
				'exclude_cats' => array(
					'label' => T_('Categories to exclude'),
					'type' => 'select',
					'multiple' => true,
					'allow_none' => true,
					'options' => $chapter_options,
					'defaultvalue' => array(),
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
		global $cat, $DB;

		$this->init_display( $params );

		// Display block TITLE:
		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		// Get current Chapter:
		$ChapterCache = & get_ChapterCache();
		if( ! empty( $cat ) && ! ( $curr_Chapter = & $ChapterCache->get_by_ID( $cat, false, false ) ) )
		{	// Display error if no cat is found by current cat ID:
			$this->display_error_message( sprintf( 'No %s found by ID %s. Cannot display widget "%s".', '<code>cat</code>', $cat, $this->get_name() ) );
			return false;
		}

		// Render MASTER quick template:
		// In theory, this should not display anything.
		// Instead, this should set variables to define sub-templates (and potentially additional variables)
		echo render_template_code( $this->disp_params['template'], /* BY REF */ $this->disp_params );

		// Check if requested sub-templates exist:
		$TemplateCache = & get_TemplateCache();
		if( ! empty( $this->disp_params['subcat_template'] ) &&
		    ! ( $cat_Template = & $TemplateCache->get_by_code( $this->disp_params['subcat_template'], false, false ) ) )
		{	// Display error when no or wrong template for listing a category:
			$this->display_error_message( sprintf( 'Template is not found: %s for listing a category', '<code>'.$this->disp_params['subcat_template'].'</code>' ) );
			return false;
		}
		if( ! empty( $this->disp_params['item_template'] ) &&
		    ! ( $item_Template = & $TemplateCache->get_by_code( $this->disp_params['item_template'], false, false ) ) )
		{	// Display error when no or wrong template for listing a category:
			$this->display_error_message( sprintf( 'Template is not found: %s for listing an item', '<code>'.$this->disp_params['item_template'].'</code>' ) );
			return false;
		}

		// Display MAIN CONTENT: subcategories and posts:
		if( isset( $this->disp_params['before_list'] ) )
		{
			echo $this->disp_params['before_list'];
		}

		$exclude_cats = ( empty( $this->disp_params['exclude_cats'] ) ? array() : $this->disp_params['exclude_cats'] );
		//pre_dump( $exclude_cats );

		if( empty( $curr_Chapter ) )
		{	// Display all ROOT categories of the current Collection:
			global $Blog;
			$ChapterCache->clear();
			$SQL = $ChapterCache->get_SQL_object();
			$SQL->WHERE( 'cat_blog_ID = '.$Blog->ID );
			$SQL->WHERE_and( 'cat_parent_ID IS NULL' );
			if( ! empty( $exclude_cats ) )
			{	// Exclude categories:
				$SQL->WHERE_and( 'cat_ID NOT IN ( '.$DB->quote( $exclude_cats ).' )' );
			}
			if( $Blog->get_setting( 'category_ordering' ) == 'manual' )
			{	// Manual order
				$SQL->SELECT_add( ', IF( cat_order IS NULL, 999999999, cat_order ) AS temp_order' );
				$SQL->ORDER_BY( 'temp_order' );
			}
			else
			{	// Alphabetic order
				$SQL->ORDER_BY( 'cat_name' );
			}
			$ChapterCache->load_by_sql( $SQL );
			foreach( $ChapterCache->cache as $Chapter )
			{
				if( in_array( $Chapter->ID, $exclude_cats ) )
				{	// Skip excluded category:
					continue;
				}
				$this->display_subcat_template( $Chapter, 0, $this->disp_params );
			}
		}
		else
		{	// Display CHILD categories and posts of the current Category:
			$callbacks = array(
				'line'  => array( $this, 'display_subcat_template' ),
				'posts' => array( $this, 'display_item_template' ),
			);
			$ChapterCache->iterate_through_category_children( $curr_Chapter, $callbacks, false, array_merge( $this->disp_params, array(
					'sorted'       => true,
					'exclude_cats' => $exclude_cats,
				) ) );
		}

		if( isset( $this->disp_params['after_list'] ) )
		{
			echo $this->disp_params['after_list'];
		}

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $cat;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => $Blog->ID, // Has the content of the displayed blog changed ?
				'cat_ID'       => empty( $cat ) ? 0 : $cat, // Has the chapter changed ?
				'template_code'   => $this->get_param( 'template' ), // Has the Template changed?
				'master_template' => true, // This widget cache must be invalidated on updating of any Template because it may has a Master Template.
			);
	}


	/**
	 * In-skin display of a Chapter.
	 *
	 * @param object Chapter
	 * @param integer Level
	 * @param array Params
	 */
	function display_subcat_template( $param_Chapter, $level, $params = array() )
	{
		if( empty( $params['subcat_template'] ) )
		{	// No template is provided for listing a category:
			return;
		}

		// Render Chapter by quick template:
		echo render_template_code( $params['subcat_template'], $params, array( 'Chapter' => $param_Chapter ) );
	}


	/**
	 * In-skin display of an Item.
	 *
	 * @param object Item
	 */
	function display_item_template( $param_Item, $level, $params = array() )
	{
		if( empty( $params['item_template'] ) )
		{	// No template is provided for listing an item:
			return;
		}

		$item_template_params = array_merge( $params, array(
				// In case of cross-posting, we EXPECT tp navigate in same category and same collection if possible:
				'post_navigation' => 'same_category',			// Stay in the same category if Item is cross-posted
				'nav_target'      => $params['chapter_ID'],	// for use with 'same_category' : set the category ID as nav target
				'target_blog'     => 'auto', 						// Stay in current collection if it is allowed for the Item
			) );

		// Render Item by quick template:
		echo render_template_code( $params['item_template'], $item_template_params, array( 'Item' => $param_Item ) );
	}
}

?>

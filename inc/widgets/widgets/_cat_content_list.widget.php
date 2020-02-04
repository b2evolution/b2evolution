<?php
/**
 * This file implements the Category Content List Widget class.
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
		return T_('Category Content List');
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
		return T_('Display the content list of the current category when browsing categories.').' (disp=posts)';
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
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_where( 'tpl_parent_tpl_ID IS NULL' );
		$template_options = array( NULL => T_('No template / use settings below').':' ) + $TemplateCache->get_code_option_array();
		$template_input_suffix = ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? '&nbsp;'
			.action_icon( '', 'edit', $admin_url.'?ctrl=templates', NULL, NULL, NULL, array(), array( 'title' => T_('Manage templates').'...' ) ) : '' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'template_cat' => array(
					'label' => T_('Template for listing a category'),
					'type' => 'select',
					'options' => $template_options,
					'defaultvalue' => 'content_list_subcat',
					'input_suffix' => $template_input_suffix,
				),
				'template_item' => array(
					'label' => T_('Template for listing an item'),
					'type' => 'select',
					'options' => $template_options,
					'defaultvalue' => 'content_list_item',
					'input_suffix' => $template_input_suffix,
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
		global $cat;

		$this->init_display( $params );

		$ChapterCache = & get_ChapterCache();

		if( ! ( $curr_Chapter = & $ChapterCache->get_by_ID( $cat, false, false ) ) )
		{	// Display error when no cat is found:
			$this->display_error_message( sprintf( 'No %s ID found. Cannot display widget "%s".', '<code>cat</code>', $this->get_name() ) );
			return false;
		}

		$TemplateCache = & get_TemplateCache();

		if( ! ( $cat_Template = & $TemplateCache->get_by_code( $this->disp_params['template_cat'], false, false ) ) )
		{	// Display error when no or wrong template for listing a category:
			$this->display_error_message( sprintf( 'Template is not found: %s for listing a category', '<code>'.$this->disp_params['template_cat'].'</code>' ) );
			return false;
		}

		if( ! ( $item_Template = & $TemplateCache->get_by_code( $this->disp_params['template_item'], false, false ) ) )
		{	// Display error when no or wrong template for listing a category:
			$this->display_error_message( sprintf( 'Template is not found: %s for listing an item', '<code>'.$this->disp_params['template_item'].'</code>' ) );
			return false;
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		$callbacks = array(
			'line'  => array( $this, 'cat_inskin_display' ),
			'posts' => array( $this, 'item_inskin_display' ),
		);

		// Display subcategories and posts
		echo '<ul class="chapters_list posts_list">';

		$ChapterCache->iterate_through_category_children( $curr_Chapter, $callbacks, false, array_merge( $params, array( 'sorted' => true ) ) );

		echo '</ul>';

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
			);
	}


	/**
	 * In-skin display of a Chapter.
	 *
	 * @param object Chapter
	 * @param integer Level
	 * @param array Params
	 */
	function cat_inskin_display( $param_Chapter, $level, $params = array() )
	{
		global $Chapter;

		// Default params:
		$params = array_merge( array(
				'before_cat'         => '<li class="chapter">',
				'after_cat'          => '</li>',
				'before_cat_title'   => '<h3>',
				'after_cat_title'    => '</h3>',
				'before_cat_content' => '<div>',
				'after_cat_content'  => '</div>',
			), $params );

		if( ! empty( $param_Chapter ) )
		{	// Display chapter:
			$Chapter = $param_Chapter;

			echo $params['before_cat'];

			load_funcs( 'templates/model/_template.funcs.php' );
			echo render_template( $this->disp_params['template_cat'], $params );

			echo $params['after_cat'];
		}
	}


	/**
	 * In-skin display of an Item.
	 * It is a wrapper around the skin '_item_list.inc.php' file.
	 *
	 * @param object Item
	 */
	function item_inskin_display( $param_Item, $level, $params = array() )
	{
		global $cat, $Item;

		// Set global $Item for widgets in container "Item in List":
		$Item = $param_Item;

		// Default params:
		$params = array_merge( array(
				'post_navigation'   => 'same_category', // Always navigate through category in this skin
				'before_item'       => '<li>',
				'after_item'        => '</li>',
				'before_content'    => '<div class="excerpt">',
				'after_content'     => '</div>',
				'permalink_text'    => get_icon( 'file_message' ).'$title$',
				'permalink_class'   => 'link',
			), $params );

		echo $params['before_item'];

		load_funcs( 'templates/model/_template.funcs.php' );
		echo render_template( $this->disp_params['template_item'], $params );

		echo $params['after_item'];
	}
}

?>
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
		$template_options = array( NULL => T_('No template') ) + $TemplateCache->get_code_option_array();
		$template_input_suffix = ( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) ? '&nbsp;'
			.action_icon( '', 'edit', $admin_url.'?ctrl=templates', NULL, NULL, NULL, array(), array( 'title' => T_('Manage templates').'...' ) ) : '' );

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

		// Set params from quick template:
		$rendered_content = render_template_code( $this->disp_params['template'], $params );

		$TemplateCache = & get_TemplateCache();

		if( ! empty( $params['subcat_template'] ) &&
		    ! ( $cat_Template = & $TemplateCache->get_by_code( $params['subcat_template'], false, false ) ) )
		{	// Display error when no or wrong template for listing a category:
			$this->display_error_message( sprintf( 'Template is not found: %s for listing a category', '<code>'.$params['subcat_template'].'</code>' ) );
			return false;
		}

		if( ! empty( $params['item_template'] ) &&
		    ! ( $item_Template = & $TemplateCache->get_by_code( $params['item_template'], false, false ) ) )
		{	// Display error when no or wrong template for listing a category:
			$this->display_error_message( sprintf( 'Template is not found: %s for listing an item', '<code>'.$params['item_template'].'</code>' ) );
			return false;
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		// Print out text if it was found in the template:
		echo trim( $rendered_content );

		// Display subcategories and posts:
		if( isset( $params['before_list'] ) )
		{
			echo $params['before_list'];
		}

		$callbacks = array(
			'line'  => array( $this, 'cat_inskin_display' ),
			'posts' => array( $this, 'item_inskin_display' ),
		);
		$ChapterCache->iterate_through_category_children( $curr_Chapter, $callbacks, false, array_merge( $params, array( 'sorted' => true ) ) );

		if( isset( $params['after_list'] ) )
		{
			echo $params['after_list'];
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
		if( empty( $params['subcat_template'] ) )
		{	// No template is provided for listing a category:
			return;
		}

		if( ! empty( $param_Chapter ) )
		{	// Render Chapter by quick template:
			echo render_template_code( $params['subcat_template'], $params, array( 'Chapter' => $param_Chapter ) );
		}
	}


	/**
	 * In-skin display of an Item.
	 *
	 * @param object Item
	 */
	function item_inskin_display( $param_Item, $level, $params = array() )
	{
		if( empty( $params['item_template'] ) )
		{	// No template is provided for listing an item:
			return;
		}

		$template_params = array_merge( $params, array(
				'permalink_text'  => get_icon( 'file_message' ).'$title$',
				'permalink_class' => 'link',
				'post_navigation' => 'same_category', // Always navigate through category in this skin
				'target_blog'     => 'auto', // Auto navigate to current collection if it is allowed for the Item
				'nav_target'      => $params['chapter_ID'], // set the category ID as nav target
			) );

		// Render Item by quick template:
		echo render_template_code( $params['item_template'], $template_params, array( 'Item' => $param_Item ) );
	}
}

?>
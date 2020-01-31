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
		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
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
	function cat_inskin_display( $Chapter, $level, $params = array() )
	{
		// Default params:
		$params = array_merge( array(
				'before_cat'         => '<li class="chapter">',
				'after_cat'          => '</li>',
				'before_cat_title'   => '<h3>',
				'after_cat_title'    => '</h3>',
				'before_cat_content' => '<div>',
				'after_cat_content'  => '</div>',
			), $params );

		if( ! empty( $Chapter ) )
		{	// Display chapter:
			echo $params['before_cat'];

			echo $params['before_cat_title']
				.'<a href="'.$Chapter->get_permanent_url().'" class="link">'.get_icon( 'expand' ).$Chapter->dget( 'name' ).'</a>'
				.$params['after_cat_title'];

			if( $Chapter->dget( 'description' ) != '' )
			{	// Display chapter description:
				echo $params['before_cat_content']
					.$Chapter->dget( 'description' )
					.$params['after_cat_content'];
			}

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
				// Params with mask values: $item_icon$, $flag_icon$, $item_status$, $read_status$, $link_view_changes$
				'before_title'      => '<h3>',
				'after_title'       => ( isset( $cat ) && ( $cat != $Item->main_cat_ID ) ? '</h3>' : '$flag_icon$</h3>$item_status$' ),
				'before_title_text' => '$item_icon$',
				'after_title_text'  => '',
			), $params );

		// Replace masks with values in params:
		$mask_params = array( 'before_title', 'after_title', 'before_title_text', 'after_title_text' );
		$mask_values = array();
		foreach( $mask_params as $mask_param )
		{
			if( strpos( $params[ $mask_param ], '$flag_icon$' ) !== false && ! isset( $mask_values['$flag_icon$'] ) )
			{	// Flag icon:
				$mask_values['$flag_icon$'] = $Item->get_flag( array(
						'before'       => ' ',
						'only_flagged' => true,
						'allow_toggle' => false,
					) );
			}
			if( strpos( $params[ $mask_param ], '$item_icon$' ) !== false && ! isset( $mask_values['$item_icon$'] ) )
			{	// Item icon:
				$mask_values['$item_icon$'] = get_icon( 'file_message' );
			}
			if( strpos( $params[ $mask_param ], '$item_status$' ) !== false && ! isset( $mask_values['$item_status$'] ) )
			{	// Status(only not published):
				$mask_values['$item_status$'] = $Item->status == 'published' ? '' : $Item->get_format_status( array(
						'template' => '<div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
					) );
			}
			if( strpos( $params[ $mask_param ], '$read_status$' ) !== false && ! isset( $mask_values['$read_status$'] ) )
			{	// Read status(New/Updated/Read):
				$mask_values['$read_status$'] = $Item->get_unread_status( array(
						'style'  => 'text',
						'before' => '<span class="evo_post_read_status">',
						'after'  => '</span>'
					) );
			}
			if( strpos( $params[ $mask_param ], '$link_view_changes$' ) !== false && ! isset( $mask_values['$link_view_changes$'] ) )
			{	// Link to view changes:
				$mask_values['$link_view_changes$'] = $Item->get_changes_link( array(
						'class' => button_class( 'text' ),
					) );
			}
			$params[ $mask_param ] = str_replace( array_keys( $mask_values ), $mask_values, $params[ $mask_param ] );
		}

		echo $params['before_item'];

		// ------------------------- "Item in List" CONTAINER EMBEDDED HERE --------------------------
		// Display container contents:
		widget_container( 'item_in_list', array(
			'widget_context' => 'item',	// Signal that we are displaying within an Item
			// The following (optional) params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			// This will enclose each widget in a block:
			'block_start' => '<div class="evo_widget $wi_class$">',
			'block_end' => '</div>',
			// This will enclose the title of each widget:
			'block_title_start' => '<h3>',
			'block_title_end' => '</h3>',

			// Controlling the title:
			'widget_item_title_params'  => array(
				'before'          => $params['before_title'],
				'after'           => $params['after_title'],
				'before_title'    => $params['before_title_text'],
				'after_title'     => $params['after_title_text'],
				'post_navigation' => $params['post_navigation'],
				'link_class'      => 'link',
			),
			// Item Visibility Badge widget template
			'widget_item_visibility_badge_display' => ( ! $Item->is_intro() && $Item->status != 'published' ),
			'widget_item_visibility_badge_params'  => array(
					'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
				),
		) );
		// ----------------------------- END OF "Item in List" CONTAINER -----------------------------

		echo $params['after_item'];
	}
}

?>
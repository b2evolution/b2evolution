<?php
/**
 * This file implements the Widget class to build a breadcrumb path.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
class breadcrumb_path_Widget extends ComponentWidget
{
	var $icon = 'angle-right';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'breadcrumb_path' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'separator' => array(
					'label' => T_('Separator'),
					'note' => T_('Separator between breadcrumbs.'),
					'defaultvalue' => ' &gt; ',
				),
				'start_with' => array(
					'label' => T_('Start with'),
					'type' => 'select',
					'options' => array( 'blog' => T_('Collection name'), 'cat' => T_('Root parent category') ),
					'defaultvalue' => 'blog',
				),
				'coll_logo_size' => array(
					'type' => 'select',
					'label' => T_('Collection logo size'),
					'options' => get_available_thumb_sizes( T_('None') ),
					'note' => T_('Select size to display collection logo at start of breadcrumb path.'),
					'defaultvalue' => 'fit-128x16',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget uses the selected items:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'breadcrumb-path-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Breadcrumb Path');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return $this->get_desc();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Breadcrumb Path');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Collection, $Blog, $cat, $disp, $thumbnail_sizes;

		$params = array_merge( array(
				'item_mask'             => '<a href="$url$">$title$</a>',
				'item_logo_mask'        => '$logo$ <a href="$url$">$title$</a>',
				'item_active_mask'      => '$title$',
				'item_active_logo_mask' => '$logo$ $title$',
				'suffix_text'           => '', // Used to add custom item at the end of list
			), $params );

		// Make sure we include the above params:
		$this->disp_params = NULL;
		$this->init_display( $params );

		$this->disp_params = array_merge( array(
			'widget_breadcrumb_path_before'      => '',
			'widget_breadcrumb_path_after'       => '',
		), $this->disp_params );

		$breadcrumbs = array();

		if( ! empty( $this->disp_params['suffix_text'] ) )
		{	// Append custom breadcrumb item at the end:
			$breadcrumbs[] = array(
					'title' => $this->disp_params['suffix_text'],
				);
		}

		// Use current category:
		$chapter_ID = $cat;

		if( ! empty( $disp ) && $disp == 'single' )
		{ // Include current post
			global $Item;
			if( ! empty( $Item ) )
			{
				$breadcrumbs[] = array(
						'title' => $Item->dget( 'title' ),
						// Don't get an item url because we don't use a link for last crumb
						//'url'   => $Item->get_permanent_url(),
					);
				if( empty( $chapter_ID ) )
				{	// Use main category of the current Item:
					$chapter_ID = $Item->get( 'main_cat_ID' );
				}
			}
		}

		if( ! empty( $chapter_ID ) )
		{ // Include full path of the selected chapter
			$ChapterCache = & get_ChapterCache();
			do
			{	// Get all parent chapters:
				if( $Chapter = & $ChapterCache->get_by_ID( $chapter_ID, false ) )
				{
					$breadcrumbs[] = array(
							'title' => $Chapter->dget( 'name' ),
							'url'   => $Chapter->get_permanent_url(),
						);
					$chapter_ID = $Chapter->get( 'parent_ID' );
				}
				else
				{ // No parent chapter else, Stop here
					break;
				}
			}
			while( ! empty( $chapter_ID ) );
		}

		if( $this->disp_params['start_with'] == 'blog' )
		{ // Include Blog name
			$breadcrumbs[] = array(
					'title' => $Blog->get( 'name' ),
					'url'   => $Blog->get( 'blogurl' ),
				);
		}

		if( empty( $breadcrumbs ) )
		{	// Nothing to display
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is nothing to display.' );
			return false;
		}

		echo $this->disp_params['block_start'];

		$breadcrumb_logo = NULL;
		if( ! empty( $this->disp_params['coll_logo_size'] ) &&
		    isset( $thumbnail_sizes[ $this->disp_params['coll_logo_size'] ] ) &&
		    $coll_logo_File = $Blog->get( 'collection_image' ) )
		{	// Display collection logo in the breadcrumb path:
			$breadcrumb_logo = $coll_logo_File->get_thumb_imgtag( $this->disp_params['coll_logo_size'] );
		}

		// Print out the breadcrumbs
		$breadcrumbs = array_reverse( $breadcrumbs );
		echo $this->disp_params['widget_breadcrumb_path_before'];
		foreach( $breadcrumbs as $b => $breadcrumb )
		{
			if( $b == count( $breadcrumbs ) - 1 )
			{ // Last crumb is active
				if( $b === 0 && isset( $breadcrumb_logo ) )
				{	// Display logo:
					$item_mask = $this->disp_params['item_active_logo_mask'];
				}
				else
				{
					$item_mask = $this->disp_params['item_active_mask'];
				}
				echo str_replace( array( '$title$', '$logo$' ),
						array( $breadcrumb['title'], $breadcrumb_logo ),
						$item_mask );
			}
			else
			{ // All other crumbs are not active
				if( $b === 0 && isset( $breadcrumb_logo ) )
				{	// Display logo:
					$item_mask = $this->disp_params['item_logo_mask'];
				}
				else
				{
					$item_mask = $this->disp_params['item_mask'];
				}
				echo str_replace( array( '$url$', '$title$', '$logo$' ),
						array( $breadcrumb['url'], $breadcrumb['title'], $breadcrumb_logo ),
						$item_mask );
				// Separator
				echo $this->disp_params['separator'];
			}
		}
		echo $this->disp_params['widget_breadcrumb_path_after'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Display debug message e-g on designer mode when we need to show widget when nothing to display currently
	 *
	 * @param string Message
	 */
	function display_debug_message( $message = NULL )
	{
		if( $this->mode == 'designer' )
		{	// Display message on designer mode:
			echo $this->disp_params['block_start'];
			echo $message;
			echo $this->disp_params['block_end'];
		}
	}
}

?>

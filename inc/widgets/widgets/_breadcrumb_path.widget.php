<?php
/**
 * This file implements the Widget class to build a breadcrumb path.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
	/**
	 * Constructor
	 */
	function breadcrumb_path_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'breadcrumb_path' );
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
			), parent::get_param_definitions( $params ) );

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
		global $Blog, $cat, $disp;

		$params = array_merge( array(
				'item_mask'        => '<a href="$url$">$title$</a>',
				'item_active_mask' => '$title$',
			), $params );

		$this->init_display( $params );

		$breadcrumbs = array();

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
			}
		}

		if( ! empty( $cat ) )
		{ // Include full path of the selected chapter
			$ChapterCache = & get_ChapterCache();

			$chapter_ID = $cat;
			do
			{ // Get all parent chapters
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
		{ // Nothing to display
			return;
		}

		echo $this->disp_params['block_start'];

		// Print out the breadcrumbs
		$breadcrumbs = array_reverse( $breadcrumbs );
		foreach( $breadcrumbs as $b => $breadcrumb )
		{
			if( $b == count( $breadcrumbs ) - 1 )
			{ // Last crumb is active
				echo str_replace( '$title$', $breadcrumb['title'], $params['item_active_mask'] );
			}
			else
			{ // All other crumbs are not active
				echo str_replace( array( '$url$', '$title$' ),
						array( $breadcrumb['url'], $breadcrumb['title'] ),
						$params['item_mask'] );
				// Separator
				echo $this->disp_params['separator'];
			}
		}

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
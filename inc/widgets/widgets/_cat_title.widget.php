<?php
/**
 * This file implements the cat_title Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: $
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
class cat_title_Widget extends ComponentWidget
{
	var $icon = 'header';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'cat_title' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'cat-title-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Category Title');
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
		return T_('Display the name of the current category when browsing categories.').' (disp=posts|single)';
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
				'display_when' => array(
					'label' => T_( 'When to display' ),
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
							array( 'browse_cat', T_('Any time we are browsing a category') ),
							array( 'no_intro', T_('Only if the category has no intro post') ),
						),
					'defaultvalue' => 'browse_cat',
				),
				'display_buttons' => array(
					'label' => T_( 'Display buttons' ),
					'type' => 'checklist',
					'options' => array(
							array( 'edit_cat', T_('Button to edit category'), 1 ),
							array( 'add_intro', T_('Button to add an intro post'), 1 ),
						),
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
		global $cat, $disp, $Blog;

		$this->init_display( $params );

		if( empty( $cat ) )
		{	// Display error when no cat is found:
			$this->display_error_message( sprintf( 'No %s ID found. Cannot display widget "%s".', '<code>cat</code>', $this->get_name() ) );
			return false;
		}
		else
		{
			$intro_Item = NULL;
			if( $this->disp_params['display_when'] == 'no_intro' )
			{
				// Go grab the first featured/intro post for the current collection without aggregation and without moving the cursor:
				$intro_Item = & get_featured_Item( $disp, NULL, true );
			}

			if( $this->disp_params['display_when'] == 'browse_cat' || empty( $intro_Item ) || $intro_Item->get( 'title' ) == '' )
			{	// Display chapter title only if intro post has no title
				$ChapterCache = & get_ChapterCache();
				// Load blog's categories
				$ChapterCache->reveal_children( empty( $Blog ) ? NULL : $Blog->ID );
				if( ! ( $curr_Chapter = & $ChapterCache->get_by_ID( $cat, false ) ) )
				{	// Display error when no cat is found by requested ID:
					$this->display_error_message( sprintf( 'No %s found by ID %s. Cannot display widget "%s".', '<code>cat</code>', $cat, $this->get_name() ) );
					return false;
				}

				echo $this->disp_params['block_start'];
				$this->disp_title();
				echo $this->disp_params['block_body_start'];

				echo '<div class="cat_title">';

					echo '<h1>'.$curr_Chapter->get( 'name' ).'</h1>';
					if( ! empty( $this->disp_params['display_buttons']['edit_cat'] ) || ! empty( $this->disp_params['display_buttons']['add_intro'] ) )
					{
						echo '<div class="'.button_class( 'group' ).'">';
						if( ! empty( $this->disp_params['display_buttons']['edit_cat'] ) )
						{	// Display button to edit category:
							echo $curr_Chapter->get_edit_link( array(
									'text'          => get_icon( 'edit' ).' '.T_('Edit Cat'),
									'class'         => button_class( 'text' ),
									'redirect_page' => 'front',
								) );
						}

						if( ! empty( $this->disp_params['display_buttons']['add_intro'] ) )
						{	// Display button to create a new page:
							$write_new_intro_url = $Blog->get_write_item_url( $cat, '', '', 'intro-cat' );
							if( !empty( $write_new_intro_url ) )
							{	// Display button to write a new intro:
								echo '<a href="'.$write_new_intro_url.'" class="'.button_class( 'text' ).'">'
										.get_icon( 'add' ).' '
										.T_('Add Intro')
									.'</a>';
							}
						}
						echo '</div>';
					}
				echo '</div>';

				echo $this->disp_params['block_body_end'];
				echo $this->disp_params['block_end'];
			}
			else
			{
				$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is nothing to display.' );
				return false;
			}
		}

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $current_User, $cat;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cont_coll_ID' => $Blog->ID, // Has the content of the displayed blog changed ?
				'cat_ID'       => empty( $cat ) ? 0 : $cat, // Has the chapter changed ?
			);
	}
}

?>
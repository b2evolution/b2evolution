<?php
/**
 * This file implements the item_small_print Widget class.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _item_small_print.widget.php 10056 2015-10-16 12:47:15Z yura $
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
class item_small_print_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 * @param object $db_row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_small_print' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'small-print-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Small Print');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Small Print') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Print small information about item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array $params local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'format' => array(
					'label' => T_('Format'),
					'note' => T_('Select what format should be displayed'),
					'type' => 'select',
					'options' => array(
							'standard' => T_('Blog standard'),
							'revision' => T_('Revisions'),
						),
					'defaultvalue' => 'standard',
				),
				'avatar_size' => array(
					'label' => T_('Avatar Size'),
					'note' => '',
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'crop-top-32x32',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $preview;

		parent::init_display( $params );

		if( $preview )
		{	// Disable block caching for this widget when item is previewed currently:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array $params MUST contain at least the basic display params
	 * @return bool
	 */
	function display( $params )
	{
		global $Item;

		if( empty( $Item ) )
		{ // Don't display this widget when no Item object
			return false;
		}

		$this->init_display( $params );

		// We renamed some params; older skin may use the old names; let's convert those params now:
		$this->convert_legacy_param( 'widget_coll_small_print_before', 'widget_item_small_print_before' );
		$this->convert_legacy_param( 'widget_coll_small_print_after', 'widget_item_small_print_after' );
		$this->convert_legacy_param( 'widget_coll_small_print_display_author', 'widget_item_small_print_display_author' );

		$this->disp_params = array_merge( array(
				'widget_item_small_print_before'    => '',
				'widget_item_small_print_after'     => '',
				'widget_item_small_print_separator' => ' &bull; ',
			), $this->disp_params );

		echo add_tag_class( $this->disp_params['block_start'], 'clearfix' );
		$this->disp_title();
		echo $this->disp_params['block_body_start'];
		echo $this->disp_params['widget_item_small_print_before'];

		if( $this->disp_params['format'] == 'standard' )
		{ // Blog standard
			/**
			 * @global Skin
			 */
			global $Skin;

			$Item->author( array(
					'link_text'   => 'only_avatar',
					'link_rel'    => 'nofollow',
					'thumb_size'  => $this->disp_params['avatar_size'],
					'thumb_class' => 'leftmargin',
				) );

			if( isset( $Skin ) && $Skin->get_setting( 'display_post_date' ) )
			{ // We want to display the post date:
				$Item->issue_time( array(
						'before'      => /* TRANS: date */ T_('This entry was posted on').' ',
						'time_format' => 'F jS, Y',
					) );
				$Item->issue_time( array(
						'before'      => /* TRANS: time */ T_('at').' ',
						'time_format' => '#short_time',
					) );
				$Item->author( array(
						'before'    => /* TRANS: author name */ T_('by').' ',
						'link_text' => 'auto',
					) );
			}
			else
			{
				$Item->author( array(
						'before'    => T_('This entry was posted by').' ',
						'link_text' => 'auto',
					) );
			}

			$Item->categories( array(
					'before'           => ' '.T_('and is filed under').' ',
					'after'            => '.',
					'include_main'     => true,
					'include_other'    => true,
					'include_external' => true,
					'link_categories'  => true,
				) );

			// List all tags attached to this post:
			$Item->tags( array(
					'before'    => ' '.T_('Tags').': ',
					'after'     => ' ',
					'separator' => ', ',
				) );

			$Item->edit_link( array( // Link to backoffice for editing
					'before' => '',
					'after'  => '',
				) );
		}
		else
		{ // Revisions
			$Item->author( array(
					'before'    => T_('Created by').' ',
					'after'     => $this->disp_params['widget_item_small_print_separator'],
					'link_text' => 'auto',
				) );

			$Item->lastedit_user( array(
					'before'    => T_('Last edit by').' ',
					'after'     => /* TRANS: "on" is followed by a date here */ ' '.T_('on').' '.$Item->get_mod_date( 'F jS, Y' ),
					'link_text' => 'auto',
				) );

			echo $Item->get_history_link( array(
					'before'    => $this->disp_params['widget_item_small_print_separator'],
					'link_text' => T_('View history')
				) );
		}

		echo $this->disp_params['widget_item_small_print_after'];
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
		global $Blog, $current_User, $Item;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
				'item_ID'      => $Item->ID, // Has the Item page changed?
			);
	}
}

?>
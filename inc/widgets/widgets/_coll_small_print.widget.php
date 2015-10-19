<?php
/**
 * This file implements the coll_small_print Widget class.
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
 * @version $Id: _coll_small_print.widget.php 10056 2015-10-16 12:47:15Z yura $
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
class coll_small_print_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_small_print_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_small_print' );
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
		global $Item;

		if( empty( $Item ) )
		{ // Don't display this widget when no Item object
			return;
		}

		$this->init_display( $params );

		$this->disp_params = array_merge( array(
				'widget_coll_small_print_before' => '',
				'widget_coll_small_print_after'  => '',
			), $this->disp_params );

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];
		echo $this->disp_params['widget_coll_small_print_before'];

		if( $this->disp_params['format'] == 'standard' )
		{ // Blog standard
			global $Skin;

			$Item->author( array(
					'link_text'   => 'only_avatar',
					'link_rel'    => 'nofollow',
					'thumb_size'  => 'crop-top-32x32',
					'thumb_class' => 'leftmargin',
				) );

			if( isset( $Skin ) && $Skin->get_setting( 'display_post_date' ) )
			{ // We want to display the post date:
				$Item->issue_time( array(
						'before'      => /* TRANS: date */ T_('This entry was posted on '),
						'time_format' => 'F jS, Y',
					) );
				$Item->issue_time( array(
						'before'      => /* TRANS: time */ T_('at '),
						'time_format' => '#short_time',
					) );
				$Item->author( array(
						'before'    => T_('by '),
						'link_text' => 'preferredname',
					) );
			}
			else
			{
				$Item->author( array(
						'before'    => T_('This entry was posted by '),
						'link_text' => 'preferredname',
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
					'before'    => T_('Created by '),
					'after'     => ' &bull; ',
					'link_text' => 'name',
				) );

			$Item->lastedit_user( array(
					'before'    => T_('Last edit by '),
					'after'     => T_(' on ').$Item->get_mod_date( 'F jS, Y' ),
					'link_text' => 'name',
				) );

			echo $Item->get_history_link( array(
					'before'    => ' &bull; ',
					'link_text' => T_('View history')
				) );
		}

		echo $this->disp_params['widget_coll_small_print_after'];
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
		global $Blog, $current_User;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
			);
	}
}

?>
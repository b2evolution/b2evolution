<?php
/**
 * This file implements the Featured/Intro Post Widget class.
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
 * @version $Id: _coll_featured_intro.widget.php 7043 2014-07-02 08:35:45Z yura $
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
class coll_featured_intro_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_featured_intro_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_featured_intro' );
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
				'skin_template' => array(
					'label' => T_('Template'),
					'note' => '.inc.php',
					'defaultvalue' => '_item_block',
				),
				'item_class' => array(
					'label' => T_('Item class'),
					'defaultvalue' => 'featurepost',
				),
				'disp_title' => array(
					'label' => T_( 'Title' ),
					'note' => T_( 'Display title.' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
				'item_title_link_type' => array(
					'label' => T_('Link title'),
					'note' => T_('Intro posts are never linked to their permalink URL'),
					'type' => 'select',
					'options' => array(
							'auto'        => T_('Automatic'),
							'permalink'   => T_('Item permalink'),
							'linkto_url'  => T_('Item URL'),
							'none'        => T_('Nowhere'),
						),
					'defaultvalue' => 'auto',
				),
				'image_size' => array(
					'label' => T_('Image Size'),
					'note' => T_('Cropping and sizing of thumbnails'),
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'fit-400x320',
				),
				'attached_pics' => array(
					'label' => T_('Attached pictures'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'none', T_('None') ),
							array( 'first', T_('Display first') ),
							array( 'all', T_('Display all') ) ),
					'defaultvalue' => 'none',
				),
				'item_pic_link_type' => array(
					'label' => T_('Link pictures'),
					'note' => T_('Where should pictures be linked to?'),
					'type' => 'select',
					'options' => array(
							'original' => T_('Image URL'),
							'single'   => T_('Item permalink'),
						),
					'defaultvalue' => 'original',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Featured/Intro Post');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return $this->get_name();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display an Item if an Intro or a Featured item is available for display.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		$this->init_display( $params );

		// Go Grab the featured post:
		if( $Item = get_featured_Item( 'front' ) )
		{ // We have a featured/intro post to display:
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( $this->disp_params['skin_template'].'.inc.php', array(
					'feature_block'        => true,
					'content_mode'         => 'auto',   // 'auto' will auto select depending on $disp-detail
					'intro_mode'           => 'normal', // Intro posts will be displayed in normal mode
					'item_class'           => $this->disp_params['item_class'],
					'image_size'           => $this->disp_params['image_size'],
					'disp_title'           => $this->disp_params['disp_title'],
					'item_title_link_type' => $this->disp_params['item_title_link_type'],
					'attached_pics'        => $this->disp_params['attached_pics'],
					'item_pic_link_type'   => $this->disp_params['item_pic_link_type'],
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------
		}

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
				'wi_ID' => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID' => (is_logged_in() ? $current_User->ID : 0), // Has the current User changed?
				'intro_feat_coll_ID' => empty($this->disp_params['blog_ID']) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the intro/featured post changed ?
			);
	}
}
?>
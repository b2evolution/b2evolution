<?php
/**
 * This file implements the item_about_author Widget class.
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
 * @version $Id: _item_about_author.widget.php 10056 2015-10-16 12:47:15Z yura $
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
class item_about_author_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_about_author' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'about-author-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('About Author');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('About Author') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display information about item author.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Load Userfield class and all fields:
		load_class( 'users/model/_userfield.class.php', 'Userfield' );
		$UserFieldCache = & get_UserFieldCache();
		$UserFieldCache->load_all();
		$user_fields = $UserFieldCache->get_option_array();

		// Set default user field as "Micro bio"
		$default_user_field_ID = 0;
		foreach( $user_fields as $user_field_ID => $user_field_name )
		{
			if( $user_field_name == 'Micro bio' )
			{
				$default_user_field_ID = $user_field_ID;
				break;
			}
		}

		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'thumb_size' => array(
					'label' => T_('Display user image'),
					'note' => T_('Cropping and sizing of thumbnails'),
					'type' => 'select',
					'options' => array( '' => T_('None') ) + get_available_thumb_sizes(),
					'defaultvalue' => 'crop-top-48x48',
				),
				'link_profile' => array(
					'label' => T_('Link to profile'),
					'note' => T_('link profile picture to user profile'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'user_field' => array(
					'label' => T_('Display user field'),
					'note' => T_('Select what user field should be displayed'),
					'type' => 'select',
					'options' => array( '' => T_('None') ) + $user_fields,
					'defaultvalue' => $default_user_field_ID,
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
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		if( empty( $Item ) )
		{ // Don't display this widget when no Item object
			return false;
		}

		$this->init_display( $params );

		if( empty( $this->disp_params['user_field'] ) )
		{ // Not defined user field in the widget settings
			return false;
		}

		// Load user fields
		$creator_User = & $Item->get_creator_User();
		$creator_User->userfields_load();
		if( empty( $creator_User->userfields_by_type[ $this->disp_params['user_field'] ] ) )
		{ // No user field by ID for current author
			return false;
		}

		$user_info = '';

		$user_info .= '<div class="evo_author_display_field">';
		$user_info .= $creator_User->userfield_value_by_ID( $this->disp_params['user_field'] );
		$user_info .= '</div>';

		if( empty( $user_info ) )
		{ // No user info
			return false;
		}

		// Display user info only when it is defined for current author
		echo add_tag_class( $this->disp_params['block_start'], 'clearfix' );
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		if( ! empty( $this->disp_params['thumb_size'] ) )
		{
			echo '<div class="evo_avatar">';

			$user_url = $this->disp_params['link_profile'] ? $creator_User->get_userpage_url() : '';

			if( ! empty( $user_url ) )
			{
				echo '<a href="'.$user_url.'" class="user_link" rel="bubbletip_user_'.$creator_User->ID.'">';
			}

			echo $creator_User->get_avatar_imgtag( $this->disp_params['thumb_size'] );
			if( ! empty( $user_url ) )
			{
				echo '</a>';
			}

			echo '</div>';
		}
		echo $user_info;

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
		global $Blog, $Item;

		if( ! empty( $Item ) && ( $creator_User = & $Item->get_creator_User() ) !== false )
		{ // Get ID of creator User
			$creator_user_ID = $creator_User->ID;
		}
		else
		{ // Cannot get creator User by some reason
			$creator_user_ID = 0;
		}

		return array(
				'wi_ID'       => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'     => $creator_user_ID, // Has the creator User changed?
				'item_ID'     => $Item->ID, // Has the Item page changed?
			);
	}
}

?>
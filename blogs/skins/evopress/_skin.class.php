<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage evopress
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class evopress_Skin extends Skin
{
  /**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'evoPress';
	}


  /**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'normal';
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
				'display_post_date' => array(
					'label' => T_('Post date'),
					'note' => T_('Display the date of each post'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'sidebar_position' => array(
					'label' => T_('Sidebar position'),
					'note' => '',
					'defaultvalue' => 'right',
					'options' => array( 'left' => $this->T_('Left'), 'right' => $this->T_('Right') ),
					'type' => 'select',
				),
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		// call parent:
		parent::display_init();

		// Add CSS:
		// fp> Note: having those here should allow
		// 1) Requesting them earlier as if they are @import'ed
		// 2) Allow bundling
		// fp> I am not 100% sure though. Comments welcome :)
		require_css( 'basic.css' );
		require_css( 'basic_styles.css', false, NULL, NULL, '' );	// Do not include v= for now or else that css will be loaded twice due to @import
		require_css( 'blog_base.css' );
		require_css( 'item_base.css', false, NULL, NULL, '' );	// Do not include v= for now or else that css will be loaded twice due to @import
		require_css( 'item.css', true );
		require_css( 'style.css', true );
	}

}

/*
 * $Log$
 * Revision 1.4  2010/01/22 12:19:46  efy-eugene
 * Adding left/right switch to evopress
 *
 * Revision 1.3  2010/01/13 23:57:48  fplanque
 * Date param.
 *
 * Revision 1.2  2009/12/12 19:22:36  fplanque
 * minor
 *
 * Revision 1.1  2009/12/02 03:54:39  fplanque
 * Attempt to let more CSS be loaded sequentially instead of serially (which happens with @import)
 * Also prepares for bundling.
 *
 * Revision 1.3  2009/05/24 21:14:38  fplanque
 * _skin.class.php can now provide skin specific settings.
 * Demo: the custom skin has configurable header colors.
 * The settings can be changed through Blog Settings > Skin Settings.
 * Anyone is welcome to extend those settings for any skin you like.
 *
 * Revision 1.2  2009/05/23 22:49:10  fplanque
 * skin settings
 *
 * Revision 1.1  2009/05/23 20:20:17  fplanque
 * Skins can now have a _skin.class.php file to override default Skin behaviour. Currently only the default name but can/will be extended.
 *
 */
?>

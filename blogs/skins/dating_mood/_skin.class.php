<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage dating_mood
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class dating_mood_Skin extends Skin
{
  /**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Dating Mood';
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
				'body_bg_color' => array(
					'label' => T_('Background Color'),
					'note' => T_('E-g: #000000 for black'),
					'defaultvalue' => '#000',
					'valid_pattern' => array( 'pattern'=>'~^(#([a-f0-9]{3}){1,2})?$~i',
																		'error'=>T_('Invalid color code.') ),
				),
				'colorbox' => array(
					'label' => T_('Colorbox Image Zoom'),
					'note' => T_('Check to enable javascript zooming on images (using the colorbox script)'),
					'defaultvalue' => 1,
					'type'	=>	'checkbox',
				),
				'gender_colored' => array(
					'label' => T_('Display gender'),
					'note' => T_('Use colored usernames to differentiate men & women.'),
					'defaultvalue' => 0,
					'type' => 'checkbox',
				),
				'bubbletip' => array(
					'label' => T_('Username bubble tips'),
					'note' => T_('Check to enable bubble tips on usernames'),
					'defaultvalue' => 0,
					'type' => 'checkbox',
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

		// Make sure standard CSS is called ahead of custom CSS generated below:
		require_css( 'style.css', true );

		// Add custom CSS:
		$custom_css = '';

		if( $body_bg_color = $this->get_setting( 'body_bg_color') )
		{	// Custom Header background color:
			$custom_css .= '	body { background-color: '.$body_bg_color." }\n";
		}

		if( !empty( $custom_css ) )
		{
			$custom_css = '<style type="text/css">
	<!--
'.$custom_css.'	-->
	</style>';
			add_headline( $custom_css );
		}

		// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
		if($this->get_setting("colorbox")) 
		{
			require_js_helper( 'colorbox', 'blog' );
		}
	}

	/**
	 * Credits to Dating Mood skin
	 */
	function display_skin_credits()
	{
		$skin_links = array( '' => array( 'http://www.datingmood.com/', array( array( 50, 'dating skin'), array( 80, 'Dating Mood'), array( 100, 'dating'), ) ) );
		display_param_link( $skin_links );
	}
}

/*
 * $Log$
 * Revision 1.10  2011/10/03 10:07:06  efy-yurybakh
 * bubbletips & identity_links cleanup
 *
 * Revision 1.9  2011/09/29 12:22:23  efy-yurybakh
 * skin param for bubbletip
 *
 * Revision 1.8  2011/09/17 02:31:59  fplanque
 * Unless I screwed up with merges, this update is for making all included files in a blog use the same domain as that blog.
 *
 * Revision 1.7  2011/09/14 20:19:48  fplanque
 * cleanup
 *
 * Revision 1.5  2011/09/09 23:26:47  lxndral
 * Add _skins.class.php to all skins  (Easy task)
 *
 * Revision 1.4  2011/09/08 13:42:37  lxndral
 * Add _skins.class.php to all skins  (Easy task)
 *
 * Revision 1.3  2011/09/07 00:28:27  sam2kb
 * Replace non-ASCII character in regular expressions with ~
 *
 * Revision 1.2  2011/09/04 02:30:21  fplanque
 * colorbox integration (MIT license)
 *
 * Revision 1.1  2010/12/06 20:36:49  fplanque
 * adding skin
 *
 */
?>

<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage default_site_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class default_site_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.1.2';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = true;  // true|false|'check' Set this to true for better optimization

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Default site skin';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'normal';
	}


	/**
	 * Does this skin provide normal (collection) skin functionality?
	 */
	function provides_collection_skin()
	{
		return false;
	}


	/**
	 * Does this skin provide site-skin functionality?
	 */
	function provides_site_skin()
	{
		return true;
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 7;
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'section_header_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Header')
				),
					'menu_bar_logo_padding' => array(
						'label' => T_('Menu bar logo padding'),
						'input_suffix' => ' px ',
						'note' => T_('Set the padding around the logo.'),
						'defaultvalue' => '2',
						'type' => 'integer',
						'size' => 1,
					),
					'fixed_header' => array(
						'label' => T_('Fixed position'),
						'note' => T_('Check to fix header top on scroll down'),
						'type' => 'checkbox',
						'defaultvalue' => 1,
					),
				'section_header_end' => array(
					'layout' => 'end_fieldset',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get ready for displaying the site skin.
	 *
	 * This may register some CSS or JS...
	 */
	function siteskin_init()
	{
		// Include the default skin style.css relative current SITE skin folder:
		require_css( 'style.min.css', 'siteskin' );

		// Add custom styles:
		$menu_bar_logo_padding = $this->get_setting( 'menu_bar_logo_padding' );

		$css = '.evo_site_skin__header a.evo_widget__site_logo_image img {
	padding: '.$menu_bar_logo_padding.'px;
}';

		if( $this->get_setting( 'fixed_header' ) )
		{	// Enable fixed position for header:
			$css .= '.evo_site_skin__header {
	position: fixed;
	top: 0;
	width: 100%;
	z-index: 10000;
}
body.evo_toolbar_visible .evo_site_skin__header {
	top: 27px;
}
body {
	padding-top: 43px;
}';
		}

		add_css_headline( $css );
	}
}

?>
<?php
/**
 * This file implements the Mobile Skin Switcher Widget class.
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
class mobile_skin_switcher_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function mobile_skin_switcher_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'mobile_skin_switcher' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'mobile-skin-switcher-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_( 'Mobile Skin Switcher' );
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * @return string The block title, the first 60 characters of the block
	 *                content or an empty string.
	 */
	function get_short_desc()
	{
		if( empty( $this->disp_params['title'] ) )
		{
			return strmaxlen( $this->disp_params['content'], 60, NULL, /* use htmlspecialchars() */ 'formvalue' );
		}

		return format_to_output( $this->disp_params['title'] );
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Mobile Skin Switcher.');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Demo data:
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Title'),
					'size' => 60,
					'defaultvalue' => T_('Mobile skin'),
				),
			), parent::get_param_definitions( $params )	);

		return $r;

	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		// Template params: $widget_title$, $switch_url$
		$params = array_merge( array(
				'wms_template_normal_to_mobile' => '<p><a href="$switch_url$">'.T_('Switch to mobile skin').'</a></p>',
				'wms_template_normal_to_tablet' => '<p><a href="$switch_url$">'.T_('Switch to tablet skin').'</a></p>',
				'wms_template_mobile' => '<div id="switch">$widget_title$:<div>
							<span class="on active">'.T_('ON').'</span>
							<a href="$switch_url$" class="off">'.T_('OFF').'</a>
						</div></div>',
				'wms_template_tablet' => '<div id="switch">$widget_title$:<div>
							<span class="on active">'.T_('ON').'</span>
							<a href="$switch_url$" class="off">'.T_('OFF').'</a>
						</div></div>',
			), $params );

		global $ReqURI, $Session, $Blog;

		if( empty( $Blog ) )
		{ // Blog must be defined
			return;
		}

		$is_mobile_session = $Session->is_mobile_session();
		$is_tablet_session = $Session->is_tablet_session();

		if( ( ! $is_mobile_session && ! $is_tablet_session )
		 || ( $is_mobile_session && $Blog->get_setting( 'mobile_skin_ID', true ) < 1 )
		 || ( $is_tablet_session && $Blog->get_setting( 'tablet_skin_ID', true ) < 1 ) )
		{ // Display the switcher only for mobile/tablet devices and when the mobile/tablet skins are defined
			return;
		}

		$force_skin = $Session->get( 'force_skin' );

		$this->init_display( $params );

		// Collection common links:
		echo $this->disp_params['block_start'];

		if( empty( $force_skin ) || $force_skin == 'mobile' || $force_skin == 'tablet' )
		{ // Mobile skin is enabled now, Display a link to switch on desktop skin
			if( empty( $force_skin ) )
			{ // Set what skin to use when user didn't switch skin yet
				$force_skin = $is_mobile_session ? 'mobile' : 'tablet';
			}
			$switch_url = url_add_param( $ReqURI, 'force_skin=normal' );
			$template_name = 'wms_template_'.$force_skin;
		}
		else
		{ // Desktop skin is enabled now, Display a link to switch back on mobile/tablet skin
			$this->disp_title( $this->disp_params['title'] );

			$switch_url = url_add_param( $ReqURI, 'force_skin=auto' );
			if( $is_mobile_session )
			{ // Mobile session
				$template_name = 'wms_template_normal_to_mobile';
			}
			else
			{ // Tablet session
				$template_name = 'wms_template_normal_to_tablet';
			}
		}

		echo $this->disp_params['block_body_start'];

		// Print out the template with the replaced vars
		echo str_replace( array( '$widget_title$', '$switch_url$' ),
			array( $this->disp_params['title'], $switch_url ),
			$params[ $template_name ] );

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
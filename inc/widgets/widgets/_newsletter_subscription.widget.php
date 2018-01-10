<?php
/**
 * This file implements the newsletter_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2017 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );
load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class newsletter_subscription_Widget extends ComponentWidget
{
	var $icon = 'envelope';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'newsletter_subscription' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'list-subscription-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('List subscription');
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
		return T_('Display a form for list subscription.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Load all active newsletters or if newsletter is currently used by this widget:
		$NewsletterCache = & get_NewsletterCache();
		$NewsletterCache->load_where( 'enlt_active = 1'.
			( empty( $params['infinite_loop'] ) ? ' OR enlt_ID = '.intval( $this->get_param( 'enlt_ID', true ) ) : '' ) );

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => T_('Get our list!'),
				),
				'intro' => array(
					'label' => T_('Intro text'),
					'note' => '',
					'type' => 'html_textarea',
					'defaultvalue' => T_('Don\'t miss the news!'),
				),
				'bottom' => array(
					'label' => T_('Bottom note'),
					'note' => '',
					'type' => 'html_textarea',
					'defaultvalue' => '',
				),
				'enlt_ID' => array(
					'label' => T_('List'),
					'note' => '',
					'type' => 'select',
					'options' => array( ''  => T_('None') ) + $NewsletterCache->get_option_array(),
					'defaultvalue' => '',
				),
				'button_notsubscribed' => array(
					'label' => T_('Button title when not subscribed'),
					'note' => T_('Text that appears on the form submit button.'),
					'size' => 40,
					'defaultvalue' => T_('Subscribe Now!'),
				),
				'button_notsubscribed_class' => array(
					'label' => T_('Button class when not subscribed'),
					'note' => T_('Form submit button class'),
					'size' => 40,
					'defaultvalue' => 'btn-danger'
				),
				'button_subscribed' => array(
					'label' => T_('Button title when subscribed'),
					'note' => T_('Text that appears on the form submit button.'),
					'size' => 40,
					'defaultvalue' => T_('Subscribed'),
				),
				'button_subscribed_class' => array(
					'label' => T_('Button class when subscribed'),
					'note' => T_('Form submit button class'),
					'size' => 40,
					'defaultvalue' => 'btn-success'
				),

				// Hidden, used by subscribe shorttag
				'inline' => array(
					'label' => 'Internal: Display inline',
					'defaultvalue' => 0,
					'no_edit' => true,
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Set default blockcache to false and disable this setting because caching is never allowed for this widget
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Collection, $Blog, $current_User;

		if( ! is_logged_in() )
		{	// Don't display when user is not logged in:
			return false;
		}

		$this->init_display( $params );

		if( isset( $this->BlockCache ) )
		{	// Do NOT cache some of these links are using a redirect_to param, which makes it page dependent.
			// Note: also beware of the source param.
			// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
			// (which could have been shared between several pages):
			$this->BlockCache->abort_collect();
		}

		echo $this->disp_params['block_start'];

		$redirect_to = param( 'redirect_to', 'url', regenerate_url( '', '', '', '&' ) );

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		$NewsletterCache = & get_NewsletterCache();
		if( ! ( $widget_Newsletter = & $NewsletterCache->get_by_ID( $this->disp_params['enlt_ID'], false, false ) ) ||
		    ! $widget_Newsletter->get( 'active' ) )
		{	// Display an error when newsletter is not found or not active:
			echo '<div class="red">'.T_('List subscription widget references an inactive list.').'</div>';
		}
		else
		{	// Display a form to subscribe⁄unsubscribe:
			if( trim( $this->disp_params['intro'] ) !== '' )
			{	// Display intro text:
				echo '<p>'.$this->disp_params['intro'].'</p>';
			}

			$Form = new Form( get_htsrv_url().'action.php' );

			$Form->begin_form();

			$Form->add_crumb( 'collections_newsletter_widget' );
			$Form->hidden( 'mname', 'collections' );
			$Form->hidden( 'action', 'newsletter_widget' );
			$Form->hidden( 'widget', $this->ID );
			$Form->hidden( 'redirect_to', $redirect_to );

			if( $this->disp_params['inline'] == 1 )
			{
				$Form->hidden( 'inline', 1 );
				$Form->hidden( 'newsletter', $this->disp_params['enlt_ID'] );
			}

			// Display a button to subscribe⁄unsubscribe:
			echo '<div class="center">';
			if( $current_User->is_subscribed( $widget_Newsletter->ID ) )
			{	// If current user is already subscribed:
				$Form->button_input( array(
						'name'  => 'unsubscribe',
						'value' => $this->disp_params['button_subscribed'],
						'class' => $this->disp_params['button_subscribed_class'].' submit' )
					);
			}
			else
			{	// If current user is not subscribed yet:
				$Form->button_input( array(
						'name'  => 'subscribe',
						'value' => $this->disp_params['button_notsubscribed'],
						'class' => $this->disp_params['button_notsubscribed_class'].' submit' )
					);
			}
			echo '</div>';

			$Form->end_form();

			if( trim( $this->disp_params['bottom'] ) !== '' )
			{	// Display bottom note:
				echo '<p class="margin-top">'.$this->disp_params['bottom'].'</p>';
			}
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>
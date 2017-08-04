<?php
/**
 * This file implements the Contact Form Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
class contact_form_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'contact_form' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'contact-form-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Contact Form');
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
		return T_('Display contact form');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $disp, $current_User, $DB, $cookie_name, $cookie_email;

		if( $disp != 'msgform' )
		{ // Don't use this widget on $disp != 'msgform'
			echo '<p class="red">'.sprintf( T_('The widget %s should be used only on $disp = %s.'), '<b>'.$this->get_name().'</b>', '<b>msgform</b>' ).'</p>';
			return false;
		}

		$this->init_display( $params );

		$blog_ID = isset( $this->disp_params['blog_ID'] ) ? intval( $this->disp_params['blog_ID'] ) : 0;
		if( $blog_ID > 0 )
		{ // Get Blog for widget setting
			$BlogCache = & get_BlogCache();
			$widget_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );
		}
		if( empty( $widget_Blog ) )
		{ // Use current blog
			global $Blog;
			$widget_Blog = & $Blog;
		}

		// Parameters
		/* TODO: dh> params should get remembered, e.g. if somebody clicks on the
		 *       login/logout link from the msgform page.
		 *       BUT, for the logout link remembering it here is too late normally.. :/
		 */
		$redirect_to = param( 'redirect_to', 'url', '' ); // pass-through (hidden field)
		$recipient_id = param( 'recipient_id', 'integer', 0 );
		$post_id = param( 'post_id', 'integer', 0 );
		$comment_id = param( 'comment_id', 'integer', 0 );
		$subject = param( 'subject', 'string', '' );

		// User's preferred name or the stored value in her cookie (from commenting):
		$email_author = '';
		// User's email address or the stored value in her cookie (from commenting):
		$email_author_address = '';
		if( is_logged_in() )
		{
			$email_author = $current_User->get_preferred_name();
			$email_author_address = $current_User->email;
		}
		if( ! strlen( $email_author ) )
		{ // Try to get params from $_COOKIE through the param() function
			$email_author = param_cookie( $cookie_name, 'string', '' );
			$email_author_address = param_cookie( $cookie_email, 'string', '' );
		}

		$recipient_User = NULL;
		// Get the name and email address of the recipient
		if( ! empty( $recipient_id ) )
		{	// If the email is to a registered user get the email address from the users table
			$UserCache = & get_UserCache();
			$recipient_User = & $UserCache->get_by_ID( $recipient_id );

			if( $recipient_User )
			{ // recipient User found
				$recipient_name = $recipient_User->get_username();
				$recipient_address = $recipient_User->get( 'email' );
			}
		}
		elseif( ! empty( $comment_id ) )
		{	// If the email is to anonymous user of comment
			$CommentCache = & get_CommentCache();
			if( $Comment = & $CommentCache->get_by_ID( $comment_id, false ) )
			{
				$recipient_User = & $Comment->get_author_User();
				if( empty( $recipient_User ) && ( $Comment->allow_msgform ) && ( is_email( $Comment->get_author_email() ) ) )
				{	// Get recipient name and email from comment's author:
					$recipient_name = $Comment->get_author_name();
					$recipient_address = $Comment->get_author_email();
				}
			}
		}

		if( empty( $recipient_address ) )
		{ // We should never have called this in the first place!
			// Could be that commenter did not provide an email, etc...
			echo T_('No recipient specified!');
			return false;
		}

		// Form to send email
		if( ! empty( $widget_Blog ) && ( $widget_Blog->get_ajax_form_enabled() ) )
		{
			if( empty( $subject ) )
			{
				$subject = '';
			}
			// init params
			$json_params = array(
				'action' => 'get_msg_form',
				'subject' => $subject,
				'recipient_id' => $recipient_id,
				'recipient_name' => $recipient_name,
				'email_author' => $email_author,
				'email_author_address' => $email_author_address,
				'blog' => $widget_Blog->ID,
				'comment_id' => $comment_id,
				'redirect_to' => $redirect_to,
				'params' => $params );

			// generate form wtih ajax request
			display_ajax_form( $json_params );
		}
		else
		{
			if( ! empty( $recipient_User ) )
			{ // Get identity link for existed users
				$recipient_link = $recipient_User->get_identity_link( array( 'link_text' => 'auto' ) );
			}
			else
			{ // Get login name for anonymous user
				$gender_class = '';
				if( check_setting( 'gender_colored' ) )
				{ // Set a gender class if the setting is ON
					$gender_class = ' nogender';
				}
				$recipient_link = '<span class="user anonymous'.$gender_class.'" rel="bubbletip_comment_'.$comment_id.'">'.$recipient_name.'</span>';
			}

			require skin_template_path( '_contact_msg.form.php' );
		}

		return true;
	}
}

?>
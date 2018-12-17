<?php
/**
 * This file implements the Email Elements plugin for b2evolution
 *
 * Email Elements
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

class email_elements_plugin extends Plugin
{
	var $code = 'b2evEmailEl';
	var $name = 'Email Elements';
	var $priority = 50;
	var $version = '7.0.0';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'email-elements-plugin';
	var $number_of_installs = 1;

	var $cta_numbers = array( 1, 2, 3 );
	var $button_types = array( 'primary', 'success', 'warning', 'danger', 'info', 'default', 'link' );

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Email Elements');
		$this->long_desc = T_('Enables users to add UI elements to emails.');
	}


	/**
	 * Define here the default collection/blog settings that are to be made available in the backoffice
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_post_rendering' => 'never' ) );
		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// set params to allow rendering for emails by default:
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'opt-out' ) );
		return parent::get_email_setting_definitions( $default_params );
	}


	/**
	 * Display Toolbar
	 *
	 * @param object Blog
	 */
	function DisplayCodeToolbar( $params = array() )
	{
		global $Hit, $baseurl, $debug;

		if( $Hit->is_lynx() )
		{	// let's deactivate toolbar on Lynx, because they don't work there:
			return false;
		}

		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		$js_code_prefix = $params['js_prefix'].$this->code;

		// Load js to work with textarea:
		require_js( 'functions.js', 'blog', true, true );

		// Initialize JavaScript to build and open window:
		echo_modalwindow_js();

		$Form = new Form();
		$Form->output = false;
		$cta_select = $Form->select_input_array( 'cta_num', NULL, $this->cta_numbers, T_('CTA number') );
		$style_select = $Form->select_input_array( 'button_type', NULL, $this->button_types, T_('Link/Button style') );
		$button_text_input = $Form->text_input( 'button_text', '', NULL, T_('Text'), '', array( 'style' => 'width:100%;' ) );
		$button_url_input = $Form->text_input( 'button_url', '', NULL, T_('URL'), '', array( 'style' => 'width:100%;' ) );

		?><script type="text/javascript">
		//<![CDATA[
		function email_elements_toolbar( title, prefix )
		{
			var r = '<?php echo format_to_js( $this->get_template( 'toolbar_title_before' ) );?>' + title + '<?php echo format_to_js( $this->get_template( 'toolbar_title_after' ) );?>'
				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_before' ) ); ?>'

				// Button element
				+ '<input type="button" title="<?php echo format_to_js( T_('Button (or link) for any use') );?>"'
				+ ' class="<?php echo format_to_js( $this->get_template( 'toolbar_button_class' ) );?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|button" value="<?php echo format_to_js( T_('Button') );?>" />'

				// Call to Action Button element
				+ '<input type="button" title="<?php echo format_to_js( T_('Button (or link) which additionally records CTA stats') );?>"'
				+ ' class="<?php echo format_to_js( $this->get_template( 'toolbar_button_class' ) );?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|cta" value="<?php echo format_to_js( T_('Call to Action') );?>" />'

				// Like Button element
				+ '<input type="button" title="<?php echo format_to_js( T_('Button which records a like') );?>"'
				+ ' class="<?php echo format_to_js( $this->get_template( 'toolbar_button_class' ) );?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|like" value="<?php echo format_to_js( T_('Like') );?>" />'

				// Dislike Button element
				+ '<input type="button" title="<?php echo format_to_js( T_('Button which record a dislike') );?>"'
				+ ' class="<?php echo format_to_js( $this->get_template( 'toolbar_button_class' ) );?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|dislike" value="<?php echo format_to_js( T_('Dislike') );?>" />'

				// Activate
				+ '<input type="button" title="<?php echo format_to_js( T_('Button which activates the User\'s account') );?>"'
				+ ' class="<?php echo format_to_js( $this->get_template( 'toolbar_button_class' ) );?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|activate" value="<?php echo format_to_js( T_('Activate') );?>" />'

				// Unsubscribe
				+ '<input type="button" title="<?php echo format_to_js( T_('Button which Unsubscribes the User from the current List') );?>"'
				+ ' class="<?php echo format_to_js( $this->get_template( 'toolbar_button_class' ) );?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|unsubscribe" value="<?php echo format_to_js( T_('Unsubscribe') );?>" />'

				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ) );?>';

				jQuery( '.' + prefix + '<?php echo $this->code;?>_toolbar' ).html( r );
		}

		<?php echo $js_code_prefix;?>_insert_button = function( type )
		{
			var modal_window_title;
			var r = '<form id="email_element_button_wrapper" class="form-horizontal">';

			if( type == 'cta' )
			{
				r += '<?php echo format_to_js( $cta_select );?>';
			}

			if( type == 'button' || type == 'cta' || type == 'activate' || type == 'unsubscribe' )
			{
				r += '<?php echo format_to_js( $style_select );?>';
			}

			if( type != 'unsubscribe' )
			{
				r += '<?php echo format_to_js( $button_url_input );?>';
			}

			r += '<?php echo format_to_js( $button_text_input );?>';

			r += '</form>';

			switch( type )
			{
				case 'button':
					modal_window_title = '<?php echo format_to_js( T_('Add a link button') );?>';
					break;

				case 'like':
					modal_window_title = '<?php echo format_to_js( T_('Add a like button') );?>';
					break;

				case 'dislike':
					modal_window_title = '<?php echo format_to_js( T_('Add a dislike button') );?>';
					break;

				case 'cta':
					modal_window_title = '<?php echo format_to_js( T_('Add a call to action button') );?>';
					break;

				case 'activate':
					modal_window_title = '<?php echo format_to_js( T_('Add an activate button') );?>';
					break;

				case 'unsubscribe':
					modal_window_title = '<?php echo format_to_js( T_('Add an unsubscribe button') );?>';
					break;
			}

			openModalWindow( r, '600px', '', true,
					modal_window_title, // Window title
					[ '-', 'email_element_button_buttons' ],
					true );

			// Set max-height to keep the action buttons on screen:
			var modal_window = jQuery( '#email_element_button_wrapper' ).parent();
			var modal_height = jQuery( window ).height() - 20;

			if( modal_window.hasClass( 'modal-body' ) )
			{	// Extract heights of header and footer:
				modal_height -= 55 + 64 +
					parseInt( modal_window.css( 'padding-top' ) ) + parseInt( modal_window.css( 'padding-bottom' ) );
			}
			modal_window.css( {
				'display': 'block',
				'overflow': 'auto',
				'max-height': modal_height
			} );

			// Add insert button:
			var buttons_side_obj = jQuery( '.email_element_button_buttons' ).length ?
						jQuery( '.email_element_button_buttons' ) :
						jQuery( '#email_element_button_buttons' );
			buttons_side_obj.after( '<button id="email_element_button_insert" class="btn btn-primary" data-function="' + type + '"><?php echo T_('Insert');?></button>' );

			// Set button type dropdown default:
			var button_defaults = { 'button': 'primary', 'cta': 'primary', 'activate': 'success', 'unsubscribe': 'link' };
			var button_type = jQuery( 'select[name=button_type]', '#email_element_button_wrapper' ).val( button_defaults[type] );

			// To prevent link default event:
			return false;
		}

		// Insert a button short tag to textarea
		jQuery( document ).on( 'click', '#email_element_button_insert', function()
		{
			var type = jQuery( this ).data( 'function' );
			var url = jQuery( 'input[name=button_url]', '#email_element_button_wrapper' ).val();
			var text = jQuery( 'input[name=button_text]', '#email_element_button_wrapper' ).val();
			var button_type = jQuery( 'select[name=button_type]', '#email_element_button_wrapper' ).val();
			var myField = <?php echo $params['js_prefix']; ?>b2evoCanvas;
			var shortTag;

			// Insert tag text in area
			switch( type )
			{
				case 'button':
					shortTag = '[button' + ':' + button_type + ( url == '' ? '' : ':' + url ) + ']'+text+'[/button]';
					break;

				case 'like':
					shortTag = '[like' + ( url == '' ? '' : ':' + url ) + ']'+text+'[/like]';
					break;

				case 'dislike':
					shortTag = '[dislike' + ( url == '' ? '' : ':' + url ) + ']'+text+'[/dislike]'
					break;

				case 'cta':
					var cta_num = jQuery( 'select[name=cta_num]', '#email_element_button_wrapper' ).val();
					shortTag = '[cta:' + cta_num + ':' + button_type + ( url == '' ? '' : ':' + url ) + ']'+text+'[/cta]'
					break;

				case 'activate':
					shortTag = '[activate' + ':' + button_type + ( url == '' ? '' : ':' + url ) + ']'+text+'[/activate]';
					break;

				case 'unsubscribe':
					shortTag = '[unsubscribe' + ':' + button_type + ']'+text+'[/unsubscribe]';
					break;
			}
			textarea_wrap_selection( myField, shortTag, '', 0 );
			// Close main modal window
			closeModalWindow();

			// To prevent link default event
			return false;
		} );

		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?>
		<script type="text/javascript">email_elements_toolbar( '<?php echo TS_('Email Elements:'); ?>', '<?php echo $params['js_prefix']; ?>' );</script>
		<?php

		return true;
	}


	/**
	 * Event handler: Called when displaying editor toolbars for email.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEmailToolbar( & $params )
	{
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{	// Print toolbar on screen:
			return $this->DisplayCodeToolbar( $params );
		}
		return false;
	}


	/**
	 * Dummy placeholder. Without it the plugin would ne be considered to be a renderer...
	 *
	 * @see Plugin::RenderItemAsHtml
	 */
	function RenderItemAsHtml( & $params )
	{
		return false;
	}


	/**
	 * Perform rendering of email
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 */
	function RenderEmailAsHtml( & $params )
	{
		$content = & $params['data'];
		$default_destination = isset( $params['EmailCampaign'] ) && !empty( $params['EmailCampaign']->email_defaultdest ) ? $params['EmailCampaign']->email_defaultdest : '';

		$search_pattern = '#\[(button|like|dislike|cta|activate|unsubscribe):?([^\[\]]*?)](.*?)\[\/\1]#';
		preg_match_all( $search_pattern, $content, $matches );

		if( ! empty( $matches[0] ) )
		{
			foreach( $matches[0] as $i => $current_element )
			{
				switch( $matches[1][$i] )
				{
					case 'button':
						$button_text = trim( $matches[3][$i] );

						$options = explode( ':', $matches[2][$i], 2 );
						$button_type = trim( $options[0] );
						if( in_array( $button_type, $this->button_types ) )
						{
							$url = isset( $options[1] ) ? trim( $options[1] ) : NULL;
						}
						else
						{
							$button_type = 'primary';
							$url = trim( $matches[2][$i] );
						}

						if( empty( $url ) )
						{
							$url = $default_destination;
						}

						if( empty( $button_text ) || empty( $url ) || validate_url( $url ) )
						{
							$link_tag = $current_element;
						}
						else
						{
							$link_tag = get_link_tag( $url, $button_text, $button_type == 'link' ? '' : 'div.btn a+a.btn-'.$button_type );
						}
						break;

					case 'like':
					case 'dislike':
						$url = trim( $matches[2][$i] );
						$button_text = trim( $matches[3][$i] );

						if( empty( $url ) )
						{
							$url = $default_destination;
						}

						if( empty( $button_text ) || empty( $url ) || validate_url( $url ) )
						{
							$link_tag = $current_element;
						}
						else
						{
							$url = url_add_param( $url, array( 'evo_mail_function' => $matches[1][$i] ) );
							$link_tag = get_link_tag( $url, $button_text, 'div.btn a+a.btn-'.( $matches[1][$i] == 'like' ? 'success' : 'danger' ) );
						}
						break;

					case 'cta':
						$options = explode( ':', $matches[2][$i], 3 );
						$cta_num = trim( $options[0] );
						$button_type = trim( $options[1] );
						if( in_array( $button_type, $this->button_types ) )
						{
							$url = isset( $options[2] ) ? trim( $options[2] ) : '';
						}
						else
						{
							$button_type = 'primary';
							$url = implode( ':', array_slice( $options, 1 ) ); // ignore cta number option
						}
						$text = trim( $matches[3][$i] );

						if( empty( $url ) )
						{
							$url = $default_destination;
						}

						if( ! in_array( $cta_num, $this->cta_numbers ) )
						{
							$link_tag = $current_element;
							break;
						}

						if( empty( $text ) || empty( $url ) || validate_url( $url ) )
						{
							$link_tag = $current_element;
						}
						else
						{
							$url = url_add_param( $url, array( 'evo_mail_function' => $matches[1][$i].$cta_num ) );
							$link_tag = get_link_tag( $url, $text, $button_type == 'link' ? '' : 'div.btn a+a.btn-'.$button_type );
						}

						break;

					case 'activate':
						$button_text = trim( $matches[3][$i] );

						if( isset( $matches[2][$i] ) )
						{
							$options = explode( ':', $matches[2][$i], 2 );
							$button_type = trim( $options[0] );
							$url = isset( $options[1] ) ? trim( $options[1] ) : NULL;
						}
						else
						{
							$button_type = 'primary';
						}

						// Only EASY activation will work for this case:
						$activation_url = get_htsrv_url().'login.php?action=activateacc_ez&userID=$user_ID$&reminderKey=$reminder_key$';

						if( isset( $url ) )
						{
							$activation_url = url_add_param( $activation_url, array( 'redirect_to' => $url ) );
						}

						$link_tag = get_link_tag( $activation_url, $button_text, $button_type == 'link' ? '' : 'div.btn a+a.btn-'.$button_type );
						break;

					case 'unsubscribe':
						$button_text = trim( $matches[3][$i] );

						if( isset( $matches[2][$i] ) )
						{
							$options = explode( ':', $matches[2][$i], 2 );
							$button_type = trim( $options[0] );
							$url = isset( $options[1] ) ? trim( $options[1] ) : NULL;
						}
						else
						{
							$button_type = 'link';
						}

						$notifications_url = get_htsrv_url().'quick_unsubscribe.php?type=newsletter&newsletter=$newsletter_ID$&user_ID=$user_ID$&key=$unsubscribe_key$';
						$link_tag = get_link_tag( $notifications_url, $button_text, $button_type == 'link' ? '' : 'div.btn a+a.btn-'.$button_type );
						break;
				}

				$content = str_replace( $current_element, $link_tag, $content );
			}
		}

		return true;
	}
}
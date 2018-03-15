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
	var $version = '6.10.1';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'email-elements-plugin';
	var $number_of_installs = 1;

	var $cta_numbers = array( 1, 2, 3 );
	var $cta_button_types = array( 'primary', 'success', 'warning', 'danger', 'info', 'default', 'link' );

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

		?><script type="text/javascript">
		//<![CDATA[
		function email_elements_toolbar( title, prefix )
		{
			var r = '<?php echo format_to_js( $this->get_template( 'toolbar_title_before' ) ); ?>' + title + '<?php echo format_to_js( $this->get_template( 'toolbar_title_after' ) ); ?>'
				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_before' ) ); ?>'

				// Button element
				+ '<input type="button" title="<?php echo TS_('Button') ?>"'
				+ ' class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|button" value="<?php echo TS_('Button') ?>" />'

				// Like Button element
				+ '<input type="button" title="<?php echo TS_('Like') ?>"'
				+ ' class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|like" value="<?php echo TS_('Like') ?>" />'

				// Dislike Button element
				+ '<input type="button" title="<?php echo TS_('Dislike') ?>"'
				+ ' class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|dislike" value="<?php echo TS_('Dislike') ?>" />'

				// Call to Action Button element
				+ '<input type="button" title="<?php echo TS_('Call to Action') ?>"'
				+ ' class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>"'
				+ ' data-func="<?php echo $js_code_prefix;?>_insert_button|cta" value="<?php echo TS_('Call to Action') ?>" />'

				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ) ); ?>';

				jQuery( '.' + prefix + '<?php echo $this->code ?>_toolbar' ).html( r );
		}

		<?php echo $js_code_prefix;?>_insert_button = function( type )
		{
			var modal_window_title;
			var r = '<form id="email_element_button_wrapper" class="form">';

			if( type == 'cta' )
			{
				r += '<div class="form-group"><label class="control-label"><?php echo T_('CTA number');?></label><div class="controls">'
						+ '<select class="form-control" name="cta_num" style="width: auto;">'
						<?php
						foreach( $this->cta_numbers as $cta_num )
						{
							echo '+ \'<option value="'.$cta_num.'">'.$cta_num.'</option>\'';
						}
						?>
						+ '</select>'
						+ '</div></div>';

				r += '<div class="form-group"><label class="control-label"><?php echo T_('Button type');?></label><div class="controls">'
						+ '<select class="form-control" name="button_type" style="width: auto;">'
						<?php
						foreach( $this->cta_button_types as $button_type )
						{
							echo '+ \'<option value="'.$button_type.'">'.$button_type.'</option>\'';
						}
						?>
						+ '</select>'
						+ '</div></div>';
			}

			r += '<div class="form-group"><label class="control-label"><?php echo T_('URL');?></label><div class="controls"><input class="form_text_input form-control" type="text" name="button_url" /></div></div>'
					+ '<div class="form-group"><label class="control-label"><?php echo T_('Text');?></label><div class="controls"><input class="form_text_input form-control" type="text" name="button_text" /></div></div>'
					+ '</form>';

			switch( type )
			{
				case 'button':
					modal_window_title = '<?php echo TS_('Add a link button');?>';
					break;

				case 'like':
					modal_window_title = '<?php echo TS_('Add a like button');?>';
					break;

				case 'dislike':
					modal_window_title = '<?php echo TS_('Add a dislike button');?>';
					break;

				case 'cta':
					modal_window_title = '<?php echo TS_('Add a call to action button');?>';
					break;
			}

			openModalWindow( r, '550px', '', true,
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

			// Add insert button
			var buttons_side_obj = jQuery( '.email_element_button_buttons' ).length ?
						jQuery( '.email_element_button_buttons' ) :
						jQuery( '#email_element_button_buttons' );
			buttons_side_obj.after( '<button id="email_element_button_insert" class="btn btn-primary" data-function="' + type + '"><?php echo T_('Insert');?></button>' );

			// To prevent link default event:
			return false;
		}

		// Insert a button short tag to textarea
		jQuery( document ).on( 'click', '#email_element_button_insert', function()
		{
			var type = jQuery( this ).data( 'function' );
			var url = jQuery( 'input[name=button_url]', '#email_element_button_wrapper' ).val();
			var text = jQuery( 'input[name=button_text]', '#email_element_button_wrapper' ).val();
			var myField = <?php echo $params['js_prefix']; ?>b2evoCanvas;
			var shortTag;

			// Insert tag text in area
			switch( type )
			{
				case 'button':
					shortTag = '[button:' + url + ']'+text+'[/button]';
					break;

				case 'like':
					shortTag = '[like:' + url + ']'+text+'[/like]';
					break;

				case 'dislike':
					shortTag = '[dislike:' + url + ']'+text+'[/dislike]'
					break;

				case 'cta':
					var cta_num = jQuery( 'select[name=cta_num]', '#email_element_button_wrapper' ).val();
					var button_type = jQuery( 'select[name=button_type]', '#email_element_button_wrapper' ).val();
					shortTag = '[cta:' + cta_num + ':' + button_type + ':' + url + ']'+text+'[/cta]'
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

		$search_pattern = '#\[(button|like|dislike|cta):([^\[\]]*?)](.*?)\[\/\1]#';
		preg_match_all( $search_pattern, $content, $matches );

		if( ! empty( $matches[0] ) )
		{
			foreach( $matches[0] as $i => $current_element )
			{
				switch( $matches[1][$i] )
				{
					case 'button':
						$url = trim( $matches[2][$i] );
						$button_text = trim( $matches[3][$i] );

						$link_tag = get_link_tag( $url, $button_text, 'div.btn a+a.btn-primary' );
						break;

					case 'like':
					case 'dislike':
						$url = trim( $matches[2][$i] );
						$button_text = trim( $matches[3][$i] );
						if( empty( $button_text ) || empty( $url ) )
						{
							$link_tag = '';
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
						$url = trim( $options[2] );
						$text = trim( $matches[3][$i] );

						if( ! in_array( $cta_num, $this->cta_numbers ) )
						{
							$link_tag = '<span style="color: red;">'.T_('Invalid CTA number parameter').'</span>';
							break;
						}

						if( ! in_array( $button_type, $this->cta_button_types ) )
						{
							$link_tag = '<span style="color: red;">'.T_('Invalid CTA button type parameter').'</span>';
							break;
						}

						if( empty( $text ) || empty( $url ) )
						{
							$link_tag = '';
						}
						else
						{
							$url = url_add_param( $url, array( 'evo_mail_function' => $matches[1][$i].$cta_num ) );
							$link_tag = get_link_tag( $url, $text, $button_type == 'link' ? '' : 'div.btn a+a.btn-'.$button_type );
						}

						break;
				}

				$content = str_replace( $current_element, $link_tag, $content );
			}
		}

		return true;
	}
}
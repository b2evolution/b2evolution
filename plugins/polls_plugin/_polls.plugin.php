<?php
/**
 * This file implements the Poll plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Renderer plugin that replaces [poll:nnn] with the same thing as the poll widget displays
 *
 * @package plugins
 */
class polls_plugin extends Plugin
{
	var $code = 'evo_poll';
	var $name = 'Polls';
	var $priority = 65;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '6.10.1';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Polls plugin');
		$this->long_desc = T_('This is a basic poll plugin. Use it by entering [poll:nnn] into your post, where nnn is the ID of the poll.');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array(
				'default_comment_rendering' => 'opt-out',
				'default_post_rendering' => 'opt-out'
			) );

		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'opt-out' ) );
		return parent::get_msg_setting_definitions( $default_params );
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'never' ) );
		return parent::get_email_setting_definitions( $default_params );
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
	 * Perform rendering
	 *
	 * @see Plugin::DisplayrItemAsHtml()
	 */
	function DisplayItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$params['check_code_block'] = true; // TRUE to find inline tags only outside of codeblocks

		$content = $this->render_polls_data( $content, $params );

		return true;
	}


	/**
	 * Convert inline poll tags into HTML tags like:
	 *    [poll:123] - Display a widget "Poll" with poll ID #123
	 *    [poll:123:Panel Title] - Use custom panel title instead of default T_('Poll')
	 *    [poll:123:-] - No title and panel are displayed at all
	 *    [poll:123:Panel Title:Question message?] - Custom title + Replace poll question from DB with custom question text
	 *    [poll:123:Panel Title:-] - Custom title + Hide question
	 *    [poll:123:-:-] - Hide title + Hide question
	 *
	 * @param string Source content
	 * @param array Params
	 * @return string Content
	 */
	function render_polls_data( $content, $params = array() )
	{
		if( isset( $params['check_code_block'] ) && $params['check_code_block'] && ( ( stristr( $content, '<code' ) !== false ) || ( stristr( $content, '<pre' ) !== false ) ) )
		{	// Call $this->render_polls_data() on everything outside code/pre:
			$params['check_code_block'] = false;
			$content = callback_on_non_matching_blocks( $content,
				'~<(code|pre)[^>]*>.*?</\1>~is',
				array( $this, 'render_polls_data' ), array( $params ) );
			return $content;
		}

		// Find all matches with tags of poll data:
		preg_match_all( '#\[poll:(\d+):?([^:\]]*):?(.*?)\]#', $content, $tags );

		if( count( $tags[0] ) > 0 )
		{	// If at least one poll inline tag is found in content:

			// Initialize widget "Poll" in order to render poll blocks:
			load_class( 'widgets/widgets/_poll.widget.php', 'poll_Widget' );
			$poll_Widget = new poll_Widget();

			foreach( $tags[0] as $t => $source_tag )
			{	// Render poll inline tag as html with widget "Poll":
				$poll_title = ( empty( $tags[2][ $t ] ) ? T_('Poll') : $tags[2][ $t ] );
				$poll_question = ( empty( $tags[3][ $t ] ) ? NULL : $tags[3][ $t ] );

				// Display title only when it doesn't equal "-":
				$display_title = ( $poll_title !== '-' );

				ob_start();
				$poll_Widget->display( array(
						'poll_ID'             => $tags[1][ $t ],
						'title'               => $poll_title,
						'poll_question'       => $poll_question,
						'block_display_title' => $display_title,
						'block_start'         => $display_title ? '<div class="panel panel-default">' : '',
						'block_end'           => $display_title ? '</div>' : '',
						'block_title_start'   => $display_title ? '<div class="panel-heading">' : '',
						'block_title_end'     => $display_title ? '</div>' : '',
						'block_body_start'    => $display_title ? '<div class="panel-body">' : '',
						'block_body_end'      => $display_title ? '</div>' : '',
					) );
				$poll_Widget->disp_params = NULL;

				// Replace poll inline tag with the rendered poll html block:
				$content = substr_replace( $content, ob_get_clean(), strpos( $content, $source_tag ), strlen( $source_tag ) );
			}
		}

		return $content;
	}


	/**
	 * Event handler: Called when displaying editor toolbars on a post/item form.
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( ! empty( $params['Item'] ) )
		{	// Item is set, get Blog from post:
			$edited_Item = & $params['Item'];
			$Collection = $Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog:
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available:
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
/*
		if( ! empty( $params['Comment'] ) )
		{	// Comment is set, get Blog from comment:
			$Comment = & $params['Comment'];
			if( ! empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Collection = $Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{	// Comment is not set, try global Blog:
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available:
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}
*/
		// Print toolbar on screen
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{	// Print toolbar on screen:
			return $this->DisplayCodeToolbar( $params );
		}
		return false;
	}


	/**
	 * Display Toolbar
	 *
	 * @param array Params
	 */
	function DisplayCodeToolbar( $params = array() )
	{
		global $Hit, $debug;

		if( $Hit->is_lynx() )
		{ // let's deactive toolbar on Lynx, because they don't work there
			return false;
		}

		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		// Load JS to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		// Load CSS for modal window
		$this->require_css( 'polls.css', true );

		// Initialize JavaScript to build and open window:
		echo_modalwindow_js();

		?>
		<script type="text/javascript">
		//<![CDATA[
		function polls_toolbar( title, prefix )
		{
			var r = '<?php echo format_to_js( $this->get_template( 'toolbar_title_before' ) ); ?>'	+ title + '<?php echo format_to_js( $this->get_template( 'toolbar_title_after' ) ); ?>'
					+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_before' ) ); ?>'
					+ '<input type="button" title="<?php echo TS_('Insert a Poll');?>"'
					+ ' class="<?php echo $this->get_template( 'toolbar_button_class' );?>"'
					+ ' data-func="polls_load_window|' + prefix + '" value="<?php echo TS_('Insert a Poll');?>" />'
					+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ) ); ?>';

			jQuery( '.' + prefix + '<?php echo $this->code;?>_toolbar' ).html( r );
		}

		function polls_load_window( prefix )
		{
			openModalWindow( '<div id="poll_wrapper"></div>', 'auto', '', true,
					'<?php echo TS_('Insert a Poll');?>',
					[ 'Insert Poll' ],
					true );

			// Load available polls
			polls_load_polls( prefix );

			// To prevent link default event
			return false;
		}

		function polls_api_request( api_path, obj_selector, func )
		{
			jQuery.ajax( {
					url: restapi_url + api_path
				} )
				.then( func, function( jqXHR )
				{
					polls_api_print_error( obj_selector, jqXHR );
				} );
		}

		function polls_api_print_error( obj_selector, error )
		{
			if( typeof( error ) != 'string' && typeof( error.code ) == 'undefined' )
			{
				error = typeof( error.responseJSON ) == 'undefined' ? error.statusText : error.responseJSON;
			}

			if( typeof( error.code ) == 'undefined' )
			{ // Unknown non-JSON response
				var error_text = '<h4 class="text-danger">Unknown error: ' + error + '</h4>';
			}
			else
			{
				var error_text = '<h4 class="text-danger">' + error.message + '</h4>';
				<?php
				if( $debug )
				{
				?>
				error_text += '<div><b>Code:</b> '	+ error.code + '</div>'
						+ '<div><b>Status:</b> ' + error.data.status + '</div>';
				<?php
				}
				?>
			}

			jQuery( obj_selector ).html( error_text );
		}

		function polls_load_polls( prefix )
		{
			prefix = ( prefix ? prefix : '' );

			polls_api_request( 'polls', '#poll_wrapper', function( data )
			{
				var r = '<div id="' + prefix + 'polls_list">';

				r += '<ul>';
				for( var p in data.polls )
				{
					var poll = data.polls[p];
					r += '<li><a href="#" data-poll-id="' + poll.pqst_ID + '" data-prefix="' + prefix + '">' + poll.pqst_question_text + '</a></li>';
				}
				r += '</ul>';
				r += '</div>';

				jQuery( '#poll_wrapper' ).html( r );

			} );
		}

		// Insert a poll short tag to textarea
		jQuery( document ).on( 'click', '#<?php echo $params['js_prefix']; ?>polls_list a[data-poll-id]', function()
		{
			if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
			{
				tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
			}

			var prefix = jQuery( this ).data( 'prefix' ) ? jQuery( this ).data( 'prefix' ) : '';

			// Insert tag text in area
			textarea_wrap_selection( window[ prefix + 'b2evoCanvas' ], '[poll:' + jQuery( this ).data( 'pollId' ) + ']', '', 0 );
			// Close main modal window
			closeModalWindow();

			// To prevent link default event
			return false;
		} );

		//]]>
		</script>
		<?php
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?>
		<script type="text/javascript">polls_toolbar( '<?php echo TS_('Polls').':';?>', '<?php echo $params['js_prefix']; ?>' );</script>
		<?php

		return true;
	}
}
?>
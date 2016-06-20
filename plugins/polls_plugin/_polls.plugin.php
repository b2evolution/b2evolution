<?php
/**
 * This file implements the Poll plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
	var $version = '6.7.0';
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

		$search_pattern = '#\[poll:(\d+)(:?)(.*?)]#';
		preg_match_all( $search_pattern, $content, $inlines );

		if( ! empty( $inlines[0] ) )
		{
			foreach( $inlines[0] as $i => $current_poll_tag )
			{
				$poll_ID = $inlines[1][$i];
				$poll_title = 'Poll';
				if( ! empty( $inlines[2][$i] ) && ! empty( $inlines[3][$i] ) )
				{
					$poll_title = $inlines[3][$i];
				}

				$poll = $this->renderPoll( $poll_ID, $poll_title );
				if( ! $poll )
				{
					$poll = $current_poll_tag;
				}

				$content = str_replace( $current_poll_tag, $poll, $content );
			}
		}

		return true;
	}


	/**
	 * Delegates rendering of poll to poll widget
	 *
	 * @param integer Poll ID to render
	 * @param string Optional poll title to display
	 */
	function renderPoll( $poll_ID, $poll_title = NULL )
	{
		load_class( 'widgets/widgets/_poll.widget.php', 'poll_Widget' );

		$Poll = new poll_Widget();

		ob_start();
		$Poll->display( array(
				'poll_ID' => $poll_ID,
				'title' => $poll_title,
				'block_display_title' => true,
				'block_start' => '<div class="panel panel-default">',
				'block_end' => '</div>',
				'block_title_start' => '<div class="panel-heading">',
				'block_title_end' => '</div>',
				'block_body_start' => '<div class="panel-body">',
				'block_body_end' => '</div>'
			) );
		$output = ob_get_clean();

		return $output;
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
			$Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog:
			global $Blog;
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

		return $this->DisplayCodeToolbar();
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
				$Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{	// Comment is not set, try global Blog:
			global $Blog;
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
		return $this->DisplayCodeToolbar();
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
			return $this->DisplayCodeToolbar();
		}
		return false;
	}


	/**
	 * Display Toolbar
	 */
	function DisplayCodeToolbar()
	{
		global $Hit, $debug;

		if( $Hit->is_lynx() )
		{ // let's deactive toolbar on Lynx, because they don't work there
			return false;
		}

		// Load JS to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		// Load CSS for modal window
		require_css( $this->get_plugin_url().'polls.css', 'relative', NULL, NULL, '#', true );

		// Initialize JavaScript to build and open window:
		echo_modalwindow_js();

		?>
		<script type="text/javascript">
		//<![CDATA[
		function polls_toolbar( title )
		{
			var r = '<?php echo $this->get_template( 'toolbar_title_before' );?>'	+ title + '<?php echo $this->get_template( 'toolbar_title_after' ); ?>'
					+ '<?php echo $this->get_template( 'toolbar_group_before' );?>'
					+ '<input type="button" title="<?php echo TS_('Insert a Poll');?>"'
					+ ' class="<?php echo $this->get_template( 'toolbar_button_class' );?>"'
					+ ' data-func="polls_load_window" value="<?php echo TS_('Insert a Poll');?>" />'
					+ '<?php echo $this->get_template( 'toolbar_group_after' );?>';

			jQuery( '.<?php echo $this->code;?>_toolbar' ).html( r );
		}

		function polls_load_window()
		{
			openModalWindow( '<div id="poll_wrapper"></div>', 'auto', '', true,
					'<?php echo TS_('Insert a Poll');?>',
					[ 'Insert Poll' ],
					true );

			// Load available polls
			polls_load_polls();

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

		function polls_load_polls()
		{
			polls_api_request( 'polls', '#poll_wrapper', function( data )
			{
				var r = '<div id="polls_list">';

				r += '<ul>';
				for( var p in data.polls )
				{
					var poll = data.polls[p];
					r += '<li><a href="#" data-poll-id="' + poll.pqst_ID + '">' + poll.pqst_question_text + '</a></li>';
				}
				r += '</ul>';
				r += '</div>';

				jQuery( '#poll_wrapper' ).html( r );

			} );
		}

		// Insert a poll short tag to textarea
		jQuery( document ).on( 'click', '#polls_list a[data-poll-id]', function()
		{
			if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
			{
				tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
			}
			// Insert tag text in area
			textarea_wrap_selection( b2evoCanvas, '[poll:' + jQuery( this ).data( 'pollId' ) + ']', '', 0 );
			// Close main modal window
			closeModalWindow();

			// To prevent link default event
			return false;
		} );

		//]]>
		</script>
		<?php
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?>
		<script type="text/javascript">polls_toolbar( '<?php echo TS_('Polls:');?>' );</script>
		<?php

		return true;
	}
}
?>
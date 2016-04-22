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
class poll_plugin extends Plugin
{
	var $code = 'evo_poll';
	var $name = 'Poll';
	var $priority = 65;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '0.1.0';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Poll plugin');
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
				$poll_title = NULL;
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
}
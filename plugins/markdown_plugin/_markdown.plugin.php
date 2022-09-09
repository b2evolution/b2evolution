<?php
/**
 * This file implements the Markdown plugin for b2evolution
 *
 * Markdown
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 * @ignore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class markdown_plugin extends Plugin
{
	var $code = 'b2evMark';
	var $name = 'Markdown';
	var $priority = 20;
	var $version = '7.2.5';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'markdown-plugin';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		require_once( dirname( __FILE__ ).'/_parsedown.inc.php' );
		require_once( dirname( __FILE__ ).'/_parsedown_extra.inc.php' );
		require_once( dirname( __FILE__ ).'/_parsedown_b2evo.inc.php' );

		$this->short_desc = T_('Markdown');
		$this->long_desc = T_('Accepted formats:<br />
# h1<br />
## h2<br />
### h3<br />
#### h4<br />
##### h5<br />
###### h6<br />
--- (horizontal rule)<br />
* * * (horizontal rule)<br />
- - - - (horizontal rule)<br />
`code spans`<br />
> blockquote');
	}


	/**
	 * Define here default custom settings that are to be made available
	 *     in the backoffice for collections, private messages and newsletters.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_custom_setting_definitions()}.
	 */
	function get_custom_setting_definitions( & $params )
	{
		return array(
			'links' => array(
					'label' => T_( 'Links' ),
					'type' => 'checkbox',
					'note' => T_( 'Detect and convert markdown link markup.' ),
					'defaultvalue' => 1,
				),
			'images' => array(
					'label' => T_( 'Images' ),
					'type' => 'checkbox',
					'note' => T_( 'Detect and convert markdown image markup.' ),
					'defaultvalue' => 1,
				),
			'text_styles' => array(
					'label' => T_( 'Italic & Bold styles' ),
					'type' => 'checkbox',
					'note' => T_( 'Detect and convert markdown italics and bold markup.' ),
					'defaultvalue' => 1,
				),
			'table' => array(
					'label' => T_('Tables'),
					'type' => 'checkbox',
					'note' => '<code>|</code> '.( ( $php_less_7 = version_compare( PHP_VERSION, '7', '<' ) ) ? '<span class="text-warning">('.sprintf( T_('Requires PHP %s'), '7.0+' ).')</span>' : '' ),
					'defaultvalue' => $php_less_7 ? 0 : 1,
					'disabled' => $php_less_7,
				),
			'deflist' => array(
					'label' => T_('Definition Lists'),
					'type' => 'checkbox',
					'note' => '<code>:</code>',
					'defaultvalue' => 1,
				),
			'footnote' => array(
					'label' => T_('Footnotes'),
					'type' => 'checkbox',
					'note' => '<code>[^1]</code>',
					'defaultvalue' => 1,
				),
			'abbr' => array(
					'label' => T_('Abbreviations'),
					'type' => 'checkbox',
					'note' => '<code>*[W3C]: World Wide Web Consortium</code>',
					'defaultvalue' => 1,
				),
		);
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
				'default_comment_rendering' => 'never',
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
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'opt-out' ) );
		return parent::get_email_setting_definitions( $default_params );
	}


	/**
	 * Define here default shared settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_shared_setting_definitions( & $params )
	{
		// set params to allow rendering for shared container widgets by default:
		$default_params = array_merge( $params, array( 'default_shared_rendering' => 'opt-out' ) );
		return parent::get_shared_setting_definitions( $default_params );
	}


	/**
	 * Perform rendering
	 *
	 * @param array Associative array of parameters
	 *   'data': the data (by reference). You probably want to modify this.
	 *   'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 * @return boolean true if we can render something for the required output format
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$parsedown_options = array(
			'table'    => array( 'Table' ),
			'deflist'  => array( 'DefinitionList' ),
			'footnote' => array( 'Footnote', 'FootnoteMarker' ),
			'abbr'     => array( 'Abbreviation' ),
		);
		$disabled_options = array();

		if( $setting_Blog = & $this->get_Blog_from_params( $params ) )
		{	// We are rendering Item, Comment or Widget now, Get the settings depending on Collection:
			$text_styles_enabled = $this->get_coll_setting( 'text_styles', $setting_Blog );
			$links_enabled = $this->get_coll_setting( 'links', $setting_Blog );
			$images_enabled = $this->get_coll_setting( 'images', $setting_Blog );
			foreach( $parsedown_options as $setting_key => $option_names )
			{
				if( ! $this->get_coll_setting( $setting_key, $setting_Blog ) )
				{	// Disable parsedown option if it is not checked in collection plugin settings:
					$disabled_options = array_merge( $disabled_options, $option_names );
				}
			}
		}
		elseif( ! empty( $params['Message'] ) )
		{	// We are rendering Message now:
			$text_styles_enabled = $this->get_msg_setting( 'text_styles' );
			$links_enabled = $this->get_msg_setting( 'links' );
			$images_enabled = $this->get_msg_setting( 'images' );
			foreach( $parsedown_options as $setting_key => $option_names )
			{
				if( ! $this->get_msg_setting( $setting_key ) )
				{	// Disable parsedown option if it is not checked in messaging plugin settings:
					$disabled_options = array_merge( $disabled_options, $option_names );
				}
			}
		}
		elseif( ! empty( $params['EmailCampaign'] ) )
		{	// We are rendering EmailCampaign now:
			$text_styles_enabled = $this->get_email_setting( 'text_styles' );
			$links_enabled = $this->get_email_setting( 'links' );
			$images_enabled = $this->get_email_setting( 'images' );
			foreach( $parsedown_options as $setting_key => $option_names )
			{
				if( ! $this->get_email_setting( $setting_key ) )
				{	// Disable parsedown option if it is not checked in emails plugin settings:
					$disabled_options = array_merge( $disabled_options, $option_names );
				}
			}
		}
		else
		{ // Unknown call, Don't render this case
			return;
		}

		// Initialize object to parse markdown code:
		$ParsedownB2evo = new ParsedownB2evo();
		$ParsedownB2evo->set_b2evo_parse_font_styles( $text_styles_enabled );
		$ParsedownB2evo->set_b2evo_parse_links( $links_enabled );
		$ParsedownB2evo->set_b2evo_parse_images( $images_enabled );
		$ParsedownB2evo->disable_options( $disabled_options );

		// Parse markdown code to HTML
		if( stristr( $content, '<code' ) !== false ||
		    stristr( $content, '<pre' ) !== false ||
		    preg_match( '/\[[a-z]+:[^\]`]+\]/i', $content ) )
		{ // Call replace_content() on everything outside code/pre:
			$content = callback_on_non_matching_blocks( $content,
				'~(<code[^>]*>.*?</code>|'
				.'<pre[^>]*>.*?</pre>|'
				.'\[[a-z]+:[^\]`]+\])~is',
				array( $ParsedownB2evo, 'text' ) );
		}
		else
		{ // No code/pre blocks, replace on the whole thing
			$content = $ParsedownB2evo->text( $content );
		}

		return true;
	}


	/**
	 * Event handler: Defines blog settings by its kind. Use {@link get_collection_kinds()} to return
	 * an array of available blog kinds and their names.
	 * Define new blog kinds in {@link Plugin::GetCollectionKinds()} method of your plugin.
	 *
	 * Note: You have to change $params['Blog'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'Blog': created Blog (by reference)
	 *   - 'kind': the kind of created blog (by reference)
	 */
	function InitCollectionKinds( & $params )
	{
		if( empty( $params['Blog'] ) || empty( $params['kind'] ) )
		{ // Invalid data, Exit here
			return;
		}

		switch( $params['kind'] )
		{
			case 'forum':
			case 'manual':
				$params['Blog']->set_setting( 'plugin'.$this->ID.'_coll_apply_comment_rendering', 'opt-out' );
				break;
		}
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @todo dh> This seems to be a lot of Javascript. Please try exporting it in a
	 *       (dynamically created) .js src file. Then we could use cache headers
	 *       to let the browser cache it.
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( ! empty( $params['Item'] ) )
		{ // Item is set, get Blog from post
			$edited_Item = & $params['Item'];
			$Collection = $Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{ // Item is not set, try global Blog
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		// Print toolbar on screen
		return $this->DisplayCodeToolbar( 'coll', $Blog, $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		global $disable_markdown_toolbar_for_frontoffice;

		if( ! is_admin_page() && $disable_markdown_toolbar_for_frontoffice )
		{	// Disable markdown toolbar until JS can be fixed to defer load:
			return false;
		}

		if( ! empty( $params['Comment'] ) )
		{ // Comment is set, get Blog from comment
			$Comment = & $params['Comment'];
			if( !empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Collection = $Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{ // Comment is not set, try global Blog
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{ // Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		// Print toolbar on screen
		return $this->DisplayCodeToolbar( 'coll', $Blog, $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		global $disable_markdown_toolbar_for_frontoffice;

		if( ! is_admin_page() && $disable_markdown_toolbar_for_frontoffice )
		{	// Disable markdown toolbar until JS can be fixed to defer load:
			return false;
		}

		$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{ // Print toolbar on screen
			return $this->DisplayCodeToolbar( 'msg', NULL, $params );
		}
		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars for email.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEmailToolbar( & $params )
	{
		global $disable_markdown_toolbar_for_frontoffice;

		if( ! is_admin_page() && $disable_markdown_toolbar_for_frontoffice )
		{	// Disable markdown toolbar until JS can be fixed to defer load:
			return false;
		}
		
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{	// Print toolbar on screen:
			return $this->DisplayCodeToolbar( 'email', NULL, $params );
		}
		return false;
	}


	/**
	 * Display Toolbar
	 *
	 * @param string Setting type: 'coll', 'msg', 'email'
	 * @param object Blog
	 * @param array Params
	 */
	function DisplayCodeToolbar( $type = 'coll', $Blog = NULL, $params = array() )
	{
		global $Hit;

		if( $Hit->is_lynx() )
		{ // let's deactivate toolbar on Lynx, because they don't work there.
			return false;
		}

		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		switch( $type )
		{
			case 'msg':
				// Get plugin setting values for messages:
				$text_styles_enabled = $this->get_msg_setting( 'text_styles' );
				$links_enabled = $this->get_msg_setting( 'links' );
				$images_enabled = $this->get_msg_setting( 'images' );
				break;

			case 'email':
				// Get plugin setting values for emails:
				$text_styles_enabled = $this->get_email_setting( 'text_styles' );
				$links_enabled = $this->get_email_setting( 'links' );
				$images_enabled = $this->get_email_setting( 'images' );
				break;

			default:
				// Get plugin setting values for current collection:
				$text_styles_enabled = $this->get_coll_setting( 'text_styles', $Blog );
				$links_enabled = $this->get_coll_setting( 'links', $Blog );
				$images_enabled = $this->get_coll_setting( 'images', $Blog );
				break;
		}

		// Load js to work with textarea
		require_js_defer( 'functions.js', 'blog', true );

		$js_config = array(
				'js_prefix' => $params['js_prefix'],
				'enable_text_styles' => (int) $text_styles_enabled,
				'enable_links'       => (int) $links_enabled,
				'enable_images'      => (int) $images_enabled,

				'btn_title_bold'   => T_('Bold'),
				'btn_title_italic' => T_('Italic'),
				'btn_title_link'   => T_('Link'),
				'btn_title_image'  => T_('Image'),
				'btn_title_h1'     => T_('Header 1'),
				'btn_title_h2'     => T_('Header 2'),
				'btn_title_h3'     => T_('Header 3'),
				'btn_title_h4'     => T_('Header 4'),
				'btn_title_h5'     => T_('Header 5'),
				'btn_title_h6'     => T_('Header 6'),
				'btn_title_li'     => T_('Unordered list item'),
				'btn_title_ol'     => T_('Ordered list item'),
				'btn_title_blockquote' => T_('Blockquote'),
				'btn_title_codespan'   => T_('Codespan'),
				'btn_title_preblock'   => T_('Preformatted code block'),
				'btn_title_codeblock'  => T_('Highlighted code block'),
				'btn_title_hr' => T_('Horizontal Rule'),
				'btn_title_br' => T_('Line Break'),
				'btn_title_close_all_tags' => T_('Close all tags'),

				'toolbar_before'       => $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) ),
				'toolbar_after'        => $this->get_template( 'toolbar_after' ),
				'toolbar_button_class' => $this->get_template( 'toolbar_button_class' ),
				'toolbar_title_before' => $this->get_template( 'toolbar_title_before' ),
				'toolbar_title_after'  => $this->get_template( 'toolbar_title_after' ),
				'toolbar_group_before' => $this->get_template( 'toolbar_group_before' ),
				'toolbar_group_after'  => $this->get_template( 'toolbar_group_after' ),
				'toolbar_title'        => T_('Markdown'),

				'prompt_url' => T_('URL'),
				'prompt_text' => T_('Text'),
				'prompt_title' => T_('Title'),

				'plugin_code' => $this->code,

			);

		if( is_ajax_request() )
		{
			?>
			<script>
				jQuery( document ).ready( function() {
						window.evo_init_markdown_toolbar( <?php echo evo_json_encode( $js_config ); ?> );
					} );
			</script>
			<?php
		}
		else
		{
			expose_var_to_js( 'mardown_toolbar_'.$params['js_prefix'], $js_config, 'evo_init_markdown_toolbar_config' );
		}

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );

		return true;
	}
}

?>

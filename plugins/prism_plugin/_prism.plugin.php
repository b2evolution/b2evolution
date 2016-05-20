<?php
/**
 * This file implements the Prism plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class prism_plugin extends Plugin
{
	var $code = 'evo_prism';
	var $name = 'Prism';
	var $priority = 27;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '5.0.0';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_( 'Display computer code.' ).' '.T_( '(Plugin not available in WYSIWYG mode)' );
		$this->long_desc = T_( 'Display computer code rendered by JavaScript plugin Prism.' ).' '.T_( '(Plugin not available in WYSIWYG mode)' );
	}


	/**
	 * Filters out the custom tag that would not validate, PLUS escapes the actual code.
	 *
	 * @param mixed $params
	 */
	function FilterItemContents( & $params )
	{
		$content = & $params['content'];
		$content = $this->filter_code( $content );

		return true;
	}


	/**
	 * Formats post contents ready for editing
	 *
	 * @param mixed $params
	 */
	function UnfilterItemContents( & $params )
	{
		$content = & $params['content'];
		$content = $this->unfilter_code( $content );

		return true;
	}


	/**
	 * Event handler: Called before at the beginning, if a comment form gets sent (and received).
	 */
	function CommentFormSent( & $params )
	{
		$ItemCache = & get_ItemCache();
		$comment_Item = & $ItemCache->get_by_ID( $params['comment_item_ID'], false );
		if( !$comment_Item )
		{ // Incorrect item
			return false;
		}

		$item_Blog = & $comment_Item->get_Blog();
		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $item_Blog );
		if( $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{ // render code blocks in comment
			$params['content' ] = & $params['comment'];
			$this->FilterItemContents( $params );
		}
	}


	/**
	 * Event handler: Called before at the beginning, if a message of thread form gets sent (and received).
	 */
	function MessageThreadFormSent( & $params )
	{
		$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
		if( $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{ // render code blocks in message
			$this->FilterItemContents( $params );
		}
	}


	/**
	 * Event handler: Called before at the beginning, if an email form gets sent (and received).
	 */
	function EmailFormSent( & $params )
	{
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{	// render code blocks in message:
			$this->FilterItemContents( $params );
		}
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		/* Required a declaration of this method to identify this plugin as renderer */
	}


	/**
	 * Encode HTML entities inside <code> blocks
	 *
	 * @param array Block
	 * @return string
	 */
	function encode_html_entities( $block )
	{
		return $block[1].htmlspecialchars( $block[2] ).$block[3];
	}


	/**
	 * Convert code blocks to html tags
	 *
	 * @param string Content
	 * @return string Content
	 */
	function filter_code( $content )
	{
		// change all [codeblock]  segments before format_to_post() gets a hold of them
		// 1 - codeblock or codespan
		// 2 - attribs : lang &| line
		// 3 - code content
		$content = preg_replace_callback( '#\[(codeblock|codespan)([^\]]*?)\]([\s\S]+?)?\[/\1\]#i',
								array( $this, 'filter_code_callback' ), $content );

		return $content;
	}


	/**
	 * Formats code ready for rendering
	 *
	 * @param array $block ( 1 - type, 2 - attributes, 3 - content )
	 * @return string formatted code || empty
	 */
	function filter_code_callback( $block )
	{
		$content = trim( $block[3] );

		if( empty( $content ) )
		{ // Don't render if no code content
			return '';
		}

		// Type of code: 'codeblock' OR 'codespan'
		$type = $block[1];

		// Language:
		$lang = strtolower( preg_replace( '/.*lang="?([a-z]+)"?.*/i', '$1', html_entity_decode( $block[2] ) ) );
		if( ! in_array( $lang, array( 'php', 'css', 'javascript', 'sql', 'markup', 'apacheconf' ) ) )
		{ // Use Markup for unknown language
			$lang = '';
		}

		$code_class = '';
		if( $type == 'codespan' )
		{	// Use standard class for codespan:
			$code_class .= 'codespan';
		}
		if( ! empty( $lang ) )
		{	// Use language class only for known language:
			$code_class .= ' language-'.$lang;
		}

		$content = $block[3];

		if( $type == 'codeblock' )
		{	// Remove first empty line from codeblock content:
			$content = preg_replace( '/^\r?\n/', '', $content );
		}

		$r = '<code'.( empty( $code_class ) ? '' : ' class="'.trim( $code_class ).'"' ).'>'.$content.'</code>';

		if( $type == 'codeblock' )
		{ // Set special template and attributes only for codeblock

			// Detect number of start line:
			$line = intval( preg_replace( '/.*line="?(-?[0-9]+)"?.*/i', '$1', html_entity_decode( $block[2] ) ) );
			$line = $line != 1 ? ' data-start="'.$line.'"' : '';

			// Put <pre> around <code> to render codeblock
			$r = '<pre class="line-numbers"'.$line.'>'.$r.'</pre>';
		}

		return $r;
	}


	/**
	 * Convert code html tags to code blocks to edit format
	 *
	 * @param string Content
	 * @return string Content
	 */
	function unfilter_code( $content )
	{
		$content = preg_replace_callback( '#(<pre class="line-numbers"( data-start="(-?[0-9]+)")?>)?<code( class="([^"]+)")?>([\s\S]+?)?</code>(</pre>)?#i',
								array( $this, 'unfilter_code_callback' ), $content );

		return $content;
	}


	/**
	 * Formats code ready for editing
	 *
	 * @param array $block ( 1 - start of <pre> tag, 4 - language, 5 - content )
	 * @return string formatted code || empty
	 */
	function unfilter_code_callback( $block )
	{
		$content = $block[6];

		if( empty( $block[1] ) )
		{ // [codespan]
			$code_tag = 'codespan';
			// codespan doesn't provide line numbers
			$line = '';
		}
		else
		{ // [codeblock]
			$code_tag = 'codeblock';
			// Detect number of start line:
			preg_match( '/.*data-start="(-?[0-9]+)".*/i', html_entity_decode( $block[1] ), $line );
			$line = ' line="'.( isset( $line[1] ) ? intval( $line[1] ) : '1' ).'"';

			// Revert back first empty line:
			$content = "\r\n".$content;
		}

		$lang = trim( strtolower( $block[5] ) );
		if( ! empty( $lang ) )
		{	// Add lang attribute only if it is defined:
			preg_match( '/language-([a-z]+)/', $lang, $lang_match );
			$lang = empty( $lang_match[1] ) ? '' : trim( $lang_match[1] );
			if( empty( $lang ) || ! in_array( $lang_match[1], array( 'php', 'css', 'javascript', 'sql', 'markup', 'apacheconf' ) ) )
			{	// Don't allow unknown language:
				$lang = '';
			}
			else
			{	// It is allowed language
				$lang = ' lang="'.strtolower( $lang ).'"';
			}
		}

		// Build codeblock:
		$r = '['.$code_tag.$lang.$line.']';
		$r .= $content;
		$r .= '[/'.$code_tag.']';

		return $r;
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlHead( & $params )
	{
		global $Blog;

		if( ! isset( $Blog ) || (
		    $this->get_coll_setting( 'coll_apply_rendering', $Blog ) == 'never' && 
		    $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) == 'never' ) )
		{ // Don't load css/js files when plugin is not enabled
			return;
		}

		require_js( $this->get_plugin_url().'/js/prism.min.js', true );
		require_css( $this->get_plugin_url().'/css/prism.min.css', true );
	}


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		global $ctrl;

		if( $ctrl == 'campaigns' && get_param( 'tab' ) == 'send' && $this->get_email_setting( 'email_apply_rendering' ) )
		{	// Load this only on form to preview email campaign:
			require_js( $this->get_plugin_url().'/js/prism.min.js', 'relative' );
			require_css( $this->get_plugin_url().'/css/prism.min.css', 'relative' );
		}
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		if( !empty( $params['Comment'] ) )
		{ // Comment is set, get Blog from comment
			$Comment = & $params['Comment'];
			if( !empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{ // Comment is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{
			return $this->display_toolbar();
		}
		return false;
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
		{
			return $this->display_toolbar();
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
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{
			return $this->display_toolbar();
		}
		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( !empty( $params['Item'] ) )
		{ // Item is set, get Blog from post
			$edited_Item = & $params['Item'];
			$Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{ // Item is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Don't display toolbar:
			return false;
		}

		// Display toolbar
		$this->display_toolbar();
	}


	/**
	 * Display toolbar
	 */
	function display_toolbar()
	{
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );

		// Codespan buttons:
		echo $this->get_template( 'toolbar_title_before' ).T_('Codespan').': '.$this->get_template( 'toolbar_title_after' );
		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" title="'.T_('Insert Markup codespan').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|markup|span" value="'.format_to_output( T_('Markup'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert CSS codespan').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|css|span" value="CSS" />';
		echo '<input type="button" title="'.T_('Insert JavaScript codespan').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|javascript|span" value="JS" />';
		echo '<input type="button" title="'.T_('Insert PHP codespan').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|php|span" value="PHP" />';
		echo '<input type="button" title="'.T_('Insert SQL codespan').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|sql|span" value="SQL" />';
		echo '<input type="button" title="'.T_('Insert Apache codespan').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|apacheconf|span" value="Apache" />';
		echo $this->get_template( 'toolbar_group_after' );

		// Codeblock buttons:
		echo $this->get_template( 'toolbar_title_before' ).T_('Codeblock').': '.$this->get_template( 'toolbar_title_after' );
		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" title="'.T_('Insert Markup codeblock').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|markup" value="'.format_to_output( T_('Markup'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert CSS codeblock').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|css" value="CSS" />';
		echo '<input type="button" title="'.T_('Insert JavaScript codeblock').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|javascript" value="JS" />';
		echo '<input type="button" title="'.T_('Insert PHP codeblock').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|php" value="PHP" />';
		echo '<input type="button" title="'.T_('Insert SQL codeblock').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|sql" value="SQL" />';
		echo '<input type="button" title="'.T_('Insert Apache codeblock').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="prism_tag|apacheconf" value="Apache" />';
		echo $this->get_template( 'toolbar_group_after' );

		echo $this->get_template( 'toolbar_after' );

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
			//<![CDATA[
			function prism_tag( lang, type )
			{
				var line = '';
				switch( type )
				{
					case 'span':
						type = 'codespan';
						break;
					case 'block':
					default:
						type = 'codeblock';
						line = ' line="1"';
						break;
				}

				textarea_wrap_selection( b2evoCanvas, '['+type+' lang="'+lang+'"'+line+']', '[/'+type+']', 0 );
			}
			//]]>
		</script><?php

		return true;
	}
}

?>
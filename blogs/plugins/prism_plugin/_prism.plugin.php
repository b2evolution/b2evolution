<?php
/**
 * This file implements the Prism plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$this->allow_html = false;
		// Get a setting "Allow HTML" for:
		if( ! empty( $params['Comment'] ) )
		{ // Comment
			$Comment = & $params['Comment'];
			$comment_Item = & $Comment->get_Item();
			$item_Blog = $comment_Item->get_Blog();
			$this->allow_html = $item_Blog->get_setting( 'allow_html_comment' );
		}
		else if( ! empty( $params['Item'] ) )
		{ // Item
			$Item = & $params['Item'];
			$item_Blog = $Item->get_Blog();
			$this->allow_html = $item_Blog->get_setting( 'allow_html_post' );
		}

		if( $this->allow_html )
		{ // If HTML is allowed in content we should disallow this for <code> content
			$content = preg_replace_callback( '#(<code class="language-[a-z]+">)([\s\S]+?)(</code>)#i',
				array( $this, 'encode_html_entities' ), $content );
		}
	}


	/**
	 * Encode HTML entities inside <code> blocks
	 *
	 * @param array Block
	 * @return string
	 */
	function encode_html_entities( $block )
	{
		return $block[1].evo_htmlspecialchars( $block[2] ).$block[3];
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
		if( ! in_array( $lang, array( 'php', 'css', 'javascript', 'sql' ) ) )
		{ // Use Markup for unknown language
			$lang = 'markup';
		}

		$r = '<code class="language-'.$lang.'">'.$block[3].'</code>';

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
		$content = preg_replace_callback( '#(<pre class="line-numbers"( data-start="(-?[0-9]+)")?>)?<code class="language-([a-z]+)">([\s\S]+?)?</code>(</pre>)?#i',
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
		}

		// Build codeblock
		$r = '['.$code_tag.' lang="'.strtolower( $block[4] ).'"'.$line.']';
		$r .= $block[5];
		$r .= '[/'.$code_tag.']';

		return $r;
	}


	/**
	 * @see Plugin::SkinBeginHtmlHead()
	 */
	function SkinBeginHtmlHead()
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
	 * Event handler: Called when displaying editor toolbars.
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
	 * Display a toolbar in admin
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

		$coll_setting_name = ( $params['target_type'] == 'Comment' ) ? 'coll_apply_comment_rendering' : 'coll_apply_rendering';
		$apply_rendering = $this->get_coll_setting( $coll_setting_name, $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{ // Don't display toolbar
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
		echo '<div class="edit_toolbar prism_toolbar">';
		// Codespan buttons:
		echo T_('Codespan').': ';
		echo '<input type="button" title="'.T_('Insert Markup codespan').'" data-func="prism_tag|markup|span" value="'.format_to_output( T_('Markup'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert CSS codespan').'" data-func="prism_tag|css|span" value="'.format_to_output( T_('CSS'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert JavaScript codespan').'" data-func="prism_tag|javascript|span" value="'.format_to_output( T_('JS'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert PHP codespan').'" data-func="prism_tag|php|span" value="'.format_to_output( T_('PHP'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert SQL codespan').'" data-func="prism_tag|sql|span" value="'.format_to_output( T_('SQL'), 'htmlattr' ).'" />';
		// Codeblock buttons:
		echo ' '.T_('Codeblock').': ';
		echo '<input type="button" title="'.T_('Insert Markup codeblock').'" data-func="prism_tag|markup" value="'.format_to_output( T_('Markup'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert CSS codeblock').'" data-func="prism_tag|css" value="'.format_to_output( T_('CSS'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert JavaScript codeblock').'" data-func="prism_tag|javascript" value="'.format_to_output( T_('JS'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert PHP codeblock').'" data-func="prism_tag|php" value="'.format_to_output( T_('PHP'), 'htmlattr' ).'" />';
		echo '<input type="button" title="'.T_('Insert SQL codeblock').'" data-func="prism_tag|sql" value="'.format_to_output( T_('SQL'), 'htmlattr' ).'" />';
		echo '</div>';

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?>
		<script type="text/javascript">
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
		</script>
		<?php

		return true;
	}
}

?>
<?php
/**
 * This file implements the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2007 by Yabba/Scott - {@link http://astonishme.co.uk/contact/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Yabba/Scott grant Francois PLANQUE the right to license
 * Yabba's/Scott's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package plugins
 *
 * @author Yabba: Paul Jones - {@link http://astonishme.co.uk/}
 * @author Stk: Scott Kimler - {@link http://astonishme.co.uk/}
 *
 * @version $Id: _code_highlight.plugin.php 7134 2014-07-16 12:01:07Z yura $
 */

/**
 * AstonishMe Display Code plugin.
 *
 *    Features:
 *        1) Character entity rendering on-the-fly
 *        2) Easy to use, just cut'n-paste your code
 *        3) Automatically adds line numbers and alternate colouring
 *        4) Customizable CSS for integrating for your site
 *        5) XHTML (Strict) and CSS valid code
 *        6) Auto-senses code block length
 *        7) BBCode tags pass through and allow to highlight the code
 *        8) No accidental smilie rendering
 *        9) PHP Syntax highlighting
 *       10) Variable start line numbers
 *       11) Links php functions to the php.net documentation
 *       12) Code is preserved if plugin uninstalled ( stored as : <!--amphp--><pre>&lt;php echo 'hello world'; ?&gt;</pre><!--/amphp--> )
 *
 *    To use:
 *        ************************************** THIS WILL NEED REWRITING ******************************************
 *        * Upload and install the plugin via the back office
 *        * Paste your code between <amcode> </amcode> tags
 *        * Paste your php between <amphp> </amphp> tags
 *        * start from any line number with the line attribute
 *        * <amcode line="99"> or <amphp line="999">
 *
 * @todo fp> for semantic purposes, <code> </code> should be automagically added
 * yabs > I assume you mean that <code> block </code> should also be converted/highlighted
 * in which case "done", I also added in <php> </php> ( was an easier regex :p )
 * obviously we originally picked the tag names to suit ourselves
 *
 * @todo dh> I'd like to be able to disable line numbering in codeblocks.
 *           Line numbers do not make sense for short code blocks (and also for longer ones
 *           not necessarily).
 *       fp> how about if no line=".." attribute is specified then no numbering?
 *       dh> Well.. this would change the default "[codeblock]" though. Yes, when
 *           clicking the button it uses '[codeblock lang="" line="1"][/codeblock]', but
 *           the "default" is still just the tag, and at least I've been using that
 *           (typing instead of clicking).
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */

class code_highlight_plugin extends Plugin
{
	var $name = 'Code highlight';
	var $code = 'evo_code';
	var $priority = 27;
	var $version = '5.0.0';
	var $author = 'Astonish Me';
	var $group = 'rendering';
	var $help_topic = 'code-highlight-plugin';
	var $number_of_installs = 1;

	/**
	 * Text php functions array
	 *
	 * @access private
	 */
	var $php_functions = array();

	/**
	 * Text php syntax highlighting colours array
	 *
	 * @access private
	 */
	var $highlight_colours = array();


	/**
	 * EXPERIMENTAL - array language classes cache
	 *
	 * @access private
	 */
	var $languageCache = array();


	/**
	 * TRUE when HTML tags are allowed for content of current rendered post/comment/message
	 * In this case we should prepare a content with function evo_htmlspecialchars() to display a code as it is
	 *
	 * @var boolean
	 */
	var $allow_html = false;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_( 'Display computer code in a post.' ).' '.T_( '(Plugin not available in WYSIWYG mode)' );
		$this->long_desc = T_( 'Display computer code easily with syntax coloring and allowing for easy copy/paste.' ).' '.T_( '(Plugin not available in WYSIWYG mode)' );
	}


	/**
	 * Get the settings that the plugin can use.
	 *
	 * Those settings are transfered into a Settings member object of the plugin
	 * and can be edited in the backoffice (Settings / Plugins).
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @see PluginSettings
	 * @see Plugin::PluginSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultSettings( & $params )
	{
		$r = array(
			'strict' => array(
					'label' => $this->T_( 'XHTML strict' ),
					'type' => 'checkbox',
					'defaultvalue' => '0', // use transitional as default
					'note' => $this->T_( 'If enabled this will remove the \' target="_blank" \' from the PHP documentation links' ),
				),
			'toolbar_default' => array(
					'label' => $this->T_( 'Display code toolbar' ),
					'type' => 'checkbox',
					'defaultvalue' => '1',
					'note' => $this->T_( 'Display code toolbar in expert mode and on comment form (indivdual users can override this).' ),
				),
			);
		return $r;
	}


	/**
	 * Allowing the user to override the display of the toolbar.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @see PluginSettings
	 * @see Plugin::PluginSettingsValidateSet()
	 *
	 * @return array
	 */
	function GetDefaultUserSettings()
	{
		return array(
				'display_toolbar' => array(
					'label' => T_( 'Display code toolbar' ),
					'defaultvalue' => $this->Settings->get('toolbar_default'),
					'type' => 'checkbox',
					'note' => $this->T_( 'Display the code toolbar' ),
				),
			);
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_comment_rendering' => 'never' ) );
		return parent::get_coll_setting_definitions( $default_params );
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
		if( !empty( $apply_rendering ) && $apply_rendering != 'never'
		&& ( ( is_logged_in() && $this->UserSettings->get( 'display_toolbar' ) )
			|| ( !is_logged_in() && $this->Settings->get( 'toolbar_default' ) ) ) )
		{
			return $this->DisplayCodeToolbar();
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
		{	// Item is set, get Blog from post
			$edited_Item = & $params['Item'];
			$Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$coll_setting_name = ( $params['target_type'] == 'Comment' ) ? 'coll_apply_comment_rendering' : 'coll_apply_rendering';
		$apply_rendering = $this->get_coll_setting( $coll_setting_name, $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' ||
		    $params['edit_layout'] == 'simple' || !$this->UserSettings->get( 'display_toolbar' ) )
		{	// This is too complex for simple mode, or user doesn't want the toolbar, don't display it:
			return false;
		}
		$this->DisplayCodeToolbar();
	}


	function DisplayCodeToolbar()
	{
		echo '<div class="edit_toolbar code_toolbar">';
		// TODO: dh> make this optional.. just like with line numbers, this "Code" line is not feasible with oneliners.
		echo T_('Code').': ';
		echo '<input type="button" id="code_samp" title="'.T_('Insert &lt;samp&gt; tag').'" class="quicktags" data-func="code_tag|samp" value="'.T_('samp').'" />';
		echo '<input type="button" id="code_kbd" title="'.T_('Insert &lt;kbd&gt; tag').'" class="quicktags" data-func="code_tag|kbd" value="'.T_('kbd').'" />';
		echo '<input type="button" id="code_var" title="'.T_('Insert &lt;var&gt; tag').'" class="quicktags" data-func="code_tag|var" value="'.T_('var').'" />';
		echo '<input type="button" id="code_code" title="'.T_('Insert &lt;code&gt; tag').'" class="quicktags" data-func="code_tag|code" value="'.T_('code').'" />';
		/* space */
		echo '<input type="button" id="codespan" title="'.T_('Insert codespan').'" style="margin-left:8px;" class="quicktags" data-func="codespan_tag| " value="'.T_('codespan').'" />';
		/* space */
		echo '<input type="button" id="codeblock" title="'.T_('Insert codeblock').'" style="margin-left:8px;" class="quicktags" data-func="codeblock_tag| " value="'.T_('codeblock').'" />';
		echo '<input type="button" id="codeblock_xml" title="'.T_('Insert XML codeblock').'" class="quicktags" data-func="codeblock_tag|xml" value="'.T_('XML').'" />';
		echo '<input type="button" id="codeblock_html" title="'.T_('Insert HTML codeblock').'" class="quicktags" data-func="codeblock_tag|html" value="'.T_('HTML').'" />';
		echo '<input type="button" id="codeblock_php" title="'.T_('Insert PHP codeblock').'" class="quicktags" data-func="codeblock_tag|php" value="'.T_('PHP').'" />';
		echo '<input type="button" id="codeblock_css" title="'.T_('Insert CSS codeblock').'" class="quicktags" data-func="codeblock_tag|css" value="'.T_('CSS').'" />';
		echo '<input type="button" id="codeblock_shell" title="'.T_('Insert Shell codeblock').'" class="quicktags" data-func="codeblock_tag|shell" value="'.T_('Shell').'" />';
		echo '</div>';

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?>
		<script type="text/javascript">
			//<![CDATA[
			function code_tag( tag_name )
			{
				tag = '<' + tag_name + '>';

				textarea_wrap_selection( b2evoCanvas, tag, '</' + tag_name + '>', 0 );
			}
			function codespan_tag( lang )
			{
				tag = '[codespan]';

				textarea_wrap_selection( b2evoCanvas, tag, '[/codespan]', 0 );
			}
			function codeblock_tag( lang )
			{
				tag = '[codeblock lang="'+lang+'" line="1"]';

				textarea_wrap_selection( b2evoCanvas, tag, '[/codeblock]', 0 );
			}
			//]]>
		</script>
		<?php

		return true;
	}


  /**
   * Filters out the custom tag that would not validate, PLUS escapes the actual code.
   *
	 * @param mixed $params
	 */
	function FilterItemContents( & $params )
	{
		$title   = & $params['title'];
		$content = & $params['content'];

		// echo 'FILTERING CODE';

		// Note : This regex is different from the original - just in case it gets moved again ;)

		// change all <codeblock> || [codeblock]  segments before format_to_post() gets a hold of them
		// 1 - amcode or codeblock
		// 2 - attribs : lang &| line
		// 3 - code block
		$content = preg_replace_callback( '#[<\[](codeblock)([^>\]]*?)[>\]]([\s\S]+?)?[<\[]/\1[>\]]#i',
								array( $this, 'filter_codeblock_callback' ), $content );

		// Quick and dirty escaping of inline code <codespan> || [codespan]:
			// fp> please provide example of what the following fix does: it looks weird to me :p
			// $content = preg_replace_callback( '#[<\[]codespan[^>\]](.*?)[<\[]/codespan[>\]]#',
		$content = preg_replace_callback( '#[<\[]codespan[>\]]([\s\S]*?)[<\[]/codespan[>\]]#',
								array( $this, 'filter_codespan_callback' ), $content );

		return true;
	}


	/**
	 * Format codespan for display
	 *
	 * @todo This is a bit quick 'n dirty.
	 * @todo We might want to unfilter this too.
	 * @todo We might want to highlight this too (based on a lang attribute).
	 */
	function filter_codespan_callback( $matches )
	{
		$code = $matches[1];

		return '<code class="codespan">'.$code.'</code>';
	}


	/**
	 * Formats post contents ready for editing
	 *
	 * @param mixed $params
	 */
	function UnfilterItemContents( & $params )
	{
		$title   = & $params['title'];
		$content = & $params['content'];

		// 1 - attribs : lang &| line
		// 2 - codeblock
		$content = preg_replace_callback( '#<\!--\s*codeblock([^-]*?)\s*--><pre[^>]*><code>(.+?)</code></pre><\!--\s+/codeblock\s*-->#is', array( $this, 'format_to_edit' ), $content );

		$content = preg_replace_callback( '#<code class="codespan">(.+?)</code>#is', array( $this, 'format_span_to_edit' ), $content );

		return true;
	}


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
			if( empty( $params['dont_remove_pre'] ) || !$params['dont_remove_pre'] )
			{ // remove <pre>
				$params['comment'] = preg_replace( '#(<\!--\s*codeblock[^-]*?\s*-->)<pre[^>]*><code>(.+?)</code></pre>(<\!--\s+/codeblock\s*-->)#is', '$1<code>$2</code>$3', $params['comment'] );
			}
		}
	}


	function BeforeCommentFormInsert( $params )
	{
		$Comment = & $params['Comment'];
		$comment_Item = & $Comment->get_Item();
		$item_Blog = & $comment_Item->get_Blog();
		if( $this->get_coll_setting( 'coll_apply_comment_rendering', $item_Blog ) )
		{	// render code blocks in comment
			// add <pre> back in so highlighting is done, will be removed by highlighter
			$params['Comment']->content =  preg_replace( '#(<\!--\s*codeblock[^-]*?\s*-->)<code>(.+?)</code>(<\!--\s+/codeblock\s*-->)#is', '$1<pre class="codeblock"><code>$2</code></pre>$3', $params['Comment']->content );
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

		// 2 - attribs : lang &| line
		// 4 - codeblock
		$content = preg_replace_callback( '#(\<p>)?\<!--\s*codeblock([^-]*?)\s*-->(\</p>)?\<pre[^>]*><code>([\s\S]+?)</code>\</pre>(\<p>)?\<!--\s*/codeblock\s*-->(\</p>)?#i',
								array( $this, 'render_codeblock_callback' ), $content );

		if( strpos( $content, '\\/codespan' ) !== false || strpos( $content, '\\/codeblock' ) !== false )
		{ // Replace [\/codeblock] or [\/codespan] to normal view
			$content = preg_replace( '#([<\[])\\\/code(span|block)([>\]])#i', '$1/code$2$3', $content );
		}

		return true;
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsXml()
	 *
	 * Note : Do we actually want to do this? - yabs
	 */
	function RenderItemAsXml( & $params )
	{
		$this->RenderItemAsHtml( $params );
	}


	/**
	 * Tidys up a block of code ready for numbering
	 *
	 * @param string $block - the code to be tidied up
	 * @param string $line_seperator - the seperator between lines of code ( default \n )
	 * @return string - the tidied code
	 */
	function tidy_code_output( $block, $line_seperator = "\n" )
	{
		// lets split the block into individual lines
		$code = explode( $line_seperator,
			// after removing windows garbage
			str_replace( "\r", '', $block ) );

		// time to rock and roll ;)
		$still_open = array(); // this holds all the spans that need closing and re-opening on the following code line
		for( $i = 0; $i < count( $code ); $i++ )
		{
			// we need to note all opening spans
			$spans =
				// get rid of the first element, it's always empty
				array_slice(
				// split line at each opening span
				explode( '<span class="',
				// add any open spans back in
				implode( '', $still_open )
				.$code[$i] )
				, 1 );
//			pre_dump( $spans );
			// reset still_open array
			$still_open = array();
			// $spans now contains a list of opening spans
			for( $z = 0; $z < count( $spans ); $z++ )
			{
				// add the span to the still_open array
				$still_open[] = '<span class="'.substr( $spans[$z], 0, strpos( $spans[$z], '"' ) ).'">';
//				pre_dump( $still_open );
				// count all closing spans and remove them from the open spans list
				if( $closed = substr_count( $spans[$z], '</span>' ) )
					$still_open = array_slice( $still_open, 0, $closed * -1 );
			}
			// lets rebuild the code line and close any remaining spans
			$code[$i] = '<span class="'.implode( '<span class="', $spans ).str_repeat( '</span>', count( $still_open ) );
		}
		// lets stitch it all back together again
		$cleaned = implode( "\n", $code );
		// and get rid of any empty spans
		while( preg_match( '#\<span[^>]+?>\</span>#', $cleaned ) )
			$cleaned = preg_replace( '#\<span[^>]+?>\</span>#', '', $cleaned );
//		pre_dump( $cleaned );
		// return the cleaned up code
		return $cleaned;
	}


	/**
	 * Formats code ready for the database
	 *
	 * @param array $block ( 2 - attributes, 3 - the code )
	 * @return string formatted code || empty
	 */
	function filter_codeblock_callback( $block )
	{ // if code block exists then tidy everything up for the database, otherwise just remove the pointless tag
		$attributes = str_replace( array( '"', '\'' ), '', evo_html_entity_decode( $block[2] ) );
		return ( empty( $block[3] ) ||  !trim( $block[3] ) ? '' : '<!-- codeblock'.$attributes.' --><pre class="codeblock"><code>'
						.$block[3]
						.'</code></pre><!-- /codeblock -->' );
	}


	/**
	 * Formats codeblock ready for editing
	 *
	 * @param array $block ( 1 - attributes, 2 - the code )
	 * @return string formatted code
	 */
	function format_to_edit( $block )
	{
		return '[codeblock'.$block[1].']'.$block[2].'[/codeblock]';
	}


	/**
	 * Formats codespan ready for editing
	 *
	 * @param array $span ( 1 - the code )
	 * @return string formatted code
	 */
	function format_span_to_edit( $span )
	{
		return '[codespan]'.$span[1].'[/codespan]';
	}


	/**
	 * Spits out the styles used
	 *
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

		require_css( $this->get_plugin_url().'amcode.css', true );
	}


	/**
	 * Spits out the styles used
	 *
	 * @see Plugin::AdminEndHtmlHead()
	 */
	function AdminEndHtmlHead()
	{
		$this->SkinBeginHtmlHead();
	}


	/**
	 * Formats code ready for displaying
	 * With "SwipeFriendly" line numbers
	 *
	 * @todo fp> no table should be needed. Also since we have odd/even coloring, word wrapping may be ok.
	 * yabs > unfortunately the tables are required to make the odd/even colouring and swipeable numbers work :(
	 *
	 * an example :
	 * Danny uploaded a page to demo his colours of choice ( http://brendoman.com/dev/youtube.html )
	 * if you scroll down to RenderItemAsHtml() you'll see it has 4 problems
	 * 1) some lines wrap - but they wrap underneath the line number
	 * 2) some lines don't wrap so they force a browser scrollbar
	 *   ( although a scrollable div will cure this)
	 * 3) the lines that don't wrap lose their background colour once they become longer than the browser width
	 *   ( a scrollable div has the same problem )
	 * 4) If you try and copy/paste the code you also get the line numbers
	 *   ( like every other code formatter that we've seen on the web that uses line numbers ... which is why most don't ;) )
	 *
	 * compare that to the same section of code using this plugin ( http://cvs.astonishme.co.uk/index.php/2007/04/01/a_demo )
	 *
	 * fp> almost all that can be fixed with proper CSS and will have the advantage of not adding extra space in front of the lines when copy/pasting. I'll work on it when I have time.
	 *
	 * yabs> The operative word in that sentance is "almost" ;) ... I've removed the trailing spaces for all but empty lines ( required to "prop them open" ) ... I'd forgotten all about them :p
	 *
	 * @param string the code block to be wrapped up
	 * @param string the attributes - currently only the starting line number
	 * @param string the code type ( code || php )
	 *
	 * @return string formatted code
	 */
	function do_numbering( $code, $offset = 0, $type = 'code' )
	{
		$temp = str_replace( array( '&nbsp;&nbsp;', '  ', "\t", '[x', '[/x' ),  array( '&#160;&#160;', '&#160;&#160;', '&#160;&#160;', '[', '[/' ), $code );
		$temp = explode( "\n", $temp );
		$count = 0;
		$output = '';
		$odd_line = false;
		foreach( $temp as $line )
		{
			$output .= '<tr class="amc_code_'.( ( $odd_line = !$odd_line ) ? 'odd' : 'even' ).'"><td class="amc_line">'
									.$this->create_number( ++$count + $offset ).'</td><td><code>'.$line
									// add an &nbsp; to empty lines to stop them "collapsing"
									.( empty( $line ) ? '&nbsp;' : '' )
									.'</code></td></tr>';//."\n"; yura: I commented this because Auto-P plugin creates the tags <p></p> from this symbol
		}
		// make "long" value a setting ? - yabs
		return '<p class="codeblock_title">'.$this->languageCache[ $type ]->language_title.'</p><div class="codeblock codeblock_with_title amc_'.$type.' '.( $count < 26 ? 'amc_short' : 'amc_long' ).'"><table>'.$output.'</table></div>';
	}


	/**
	 * Creates the SwipeFriendly line numbers
	 *
	 * @param integer the line number to produce
	 * @return string the html for the line number
	 */
	function create_number( $num )
	{	// part of Swipe 'n' Paste magic ;)
		$result = '';
		$count = 0;
		while ( $num )
		{
			$result .= '<div class="amc'.( $num - ( floor( $num / 10 ) * 10 ) ).'">';
			$num = floor( $num / 10 );
			$count++;
		}
		$result .= str_repeat( '</div>', $count );
		return $result;
	}


	/**
	 * Formats codeblock ready for displaying
	 * Each language is stored as a classfile
	 * This would allow new languages to be added more easily
	 * It would also allow Geshi to be used as the highlighter with no code changes ;)
	 *
	 * Replaces both (current) highlighter functions
	 *
	 * ..... still requires some more thought though :p
	 *
	 * @param array $block ( 2 - attributes, 4 - the code )
	 * @return string formatted code
	 */
	function render_codeblock_callback( $block )
	{
		// set the offset if present - default : 0
		preg_match( '#line=("|\'?)([0-9]+?)(["\']?)$#', $block[2], $match );
		$offset = ( empty( $match[2] ) ? 0 : $match[2] - 1 );

		// set the language if present - default : code
		preg_match( '#lang=("|\'?)([^\1]+?)([\s"\']+?)#', $block[2], $match );
		$language = strtolower( ( empty( $match[2] ) ? 'code' : $match[2] ) );

		if( $code = trim( $block[4] ) )
		{ // we have a code block
			// is the relevant language highlighter already cached?
			if( empty( $this->languageCache[ $language ] ) )
			{ // lets attempt to load the language
				$language_file = dirname(__FILE__).'/highlighters/'.$language.'.highlighter.php';
				if( is_file( $language_file ) )
				{ // language class exists, lets load and cache an instance of it
					require_once $language_file;
					$class = 'am_'.$language.'_highlighter';
					$this->languageCache[ $language ] = new $class( $this );
				}
				else
				{ // language class doesn't exists, fallback to default highlighter
					$language = 'code';
					if( empty( $this->languageCache[ $language ] ) )
					{ // lets attempt to load the default language
						$language_file = dirname(__FILE__).'/highlighters/'.$language.'.highlighter.php';
						if( is_file( $language_file ) )
						{ // default lanugage exists
							require_once $language_file;
							$class = 'am_'.$language.'_highlighter';
							// add the language to the cache
							$this->languageCache[ $language ] = new $class( $this );
						}
						else
						{ // if we hit this we might as well go to the pub ;)
							// echo '***** error ***** no language or default language file is present';
							return $code;
						}
					}
				}
			}
			$this->languageCache[ $language ]->requested_language = $language;
			$this->languageCache[ $language ]->strict_mode = $this->Settings->get( 'strict' );
			$code = $this->languageCache[ $language ]->highlight_code( $code );
			// add the line numbers
			$code = $this->do_numbering( $code, $offset, $language );
		}
		return $code;
	}
}

?>
<?php
/**
 * This file implements the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
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
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */

class code_highlight_plugin extends Plugin
{
	var $name = 'Code highlight';
	var $code = 'evo_code';
	var $priority = 80;
	var $version = '2.0';
	var $author = 'Astonish Me';
	var $group = 'rendering';
	var $help_url = 'http://b2evo.astonishme.co.uk/';
	var $apply_rendering = 'opt-out';
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
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_( 'Display computer code in a post.' );
		$this->long_desc = T_( 'Display computer code easily.  This plugin renders character entities on the fly, so you can cut-and-paste normal code directly into your posts and it will always look like normal code, even when editing the post (i.e., no preprocessing of the code is required). Include line numbers (customizable starting number).  The best part about the line numbers - visitors can cut-and-paste the code from your post, leaving the line numbers behind! Accepts BBcode tags and does not render smilies.  Colouration of PHP code, plus PHP manual links for PHP functions. Easy to install and easy to use. No hacks. Degrades nicely, if the plugin is off.  Styling completely customizable via your skins CSS file.' );
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
			'render_comments' => array(
					'label' => $this->T_( 'Render comments' ),
					'type' => 'checkbox',
					'defaultvalue' => '0',
					'note' => $this->T_( 'Render codeblocks in comments.' ),
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
	 * Event handler: Called when displaying editor toolbars.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		if( $this->Settings->get( 'render_comments' )
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
		if( $params['edit_layout'] == 'simple' || !$this->UserSettings->get( 'display_toolbar' )  )
		{	// This is too complex for simple mode, or user doesn't want the toolbar, don't display it:
			return false;
		}
		$this->DisplayCodeToolbar();
	}

	function DisplayCodeToolbar()
	{
		echo '<div class="edit_toolbar">';
		echo T_('Code').': ';
		echo '<input type="button" id="codespan" title="'.T_('Insert codespan').'" class="quicktags" onclick="codespan_tag(\'\');" value="'.T_('codespan').'" />';
		echo '<input type="button" id="codeblock" title="'.T_('Insert codeblock').'" style="margin-left:8px;" class="quicktags" onclick="codeblock_tag(\'\');" value="'.T_('codeblock').'" />';
		echo '<input type="button" id="codeblock_xml" title="'.T_('Insert XML codeblock').'" class="quicktags" onclick="codeblock_tag(\'xml\');" value="'.T_('XML').'" />';
		echo '<input type="button" id="codeblock_php" title="'.T_('Insert PHP codeblock').'" class="quicktags" onclick="codeblock_tag(\'php\');" value="'.T_('PHP').'" />';
		echo '<input type="button" id="codeblock_css" title="'.T_('Insert CSS codeblock').'" class="quicktags" onclick="codeblock_tag(\'css\');" value="'.T_('CSS').'" />';
		echo '</div>';

		?>
		<script type="text/javascript">
			//<![CDATA[
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
		$content = preg_replace_callback( '#[<\[]codespan[>\]](.*?)[<\[]/codespan[>\]]#',
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

		return '<code class="codespan">'.str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), $code).'</code>';
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
		$content = preg_replace_callback( '#<\!--\s*codeblock([^-]*?)\s*--><pre><code>(.+?)</code></pre><\!--\s+/codeblock\s*-->#i', array( $this, 'format_to_edit' ), $content );

		return true;
	}


	function CommentFormSent( & $params )
	{
		if( $this->Settings->get( 'render_comments' ) )
		{	// render code blocks in comment
			$params['content' ] = & $params['comment'];
			$this->FilterItemContents( $params );
			// remove <pre>
			$params['comment'] = preg_replace( '#(<\!--\s*codeblock[^-]*?\s*-->)<pre><code>(.+?)</code></pre>(<\!--\s+/codeblock\s*-->)#is', '$1<code>$2</code>$3', $params['comment'] );
		}
	}

	function BeforeCommentFormInsert( $params )
	{
		if( $this->Settings->get( 'render_comments' ) )
		{	// render code blocks in comment
			// add <pre> back in so highlighting is done, will be removed by highlighter
			$params['Comment']->content =  preg_replace( '#(<\!--\s*codeblock[^-]*?\s*-->)<code>(.+?)</code>(<\!--\s+/codeblock\s*-->)#is', '$1<pre><code>$2</code></pre>$3', $params['Comment']->content );
		}
	}




	/**
	 * Render comments if required
	 *
	 * @param array mixed $params
	 */
	function FilterCommentContent( $params )
	{
		if( $this->Settings->get( 'render_comments' ) )
		{	// render code blocks in comment
			$this->RenderItemAsHtml( $params );
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
		$content = preg_replace_callback( '#(\<p>)?\<!--\s*codeblock([^-]*?)\s*-->(\</p>)?\<pre><code>([\s\S]+?)</code>\</pre>(\<p>)?\<!--\s*/codeblock\s*-->(\</p>)?#i',
								array( $this, 'render_codeblock_callback' ), $content );

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
		return ( empty( $block[3] ) ||  !trim( $block[3] ) ? '' : '<!-- codeblock'.$block[2].' --><pre><code>'
						.str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), $block[3] )
						.'</code></pre><!-- /codeblock -->' );
	}


	/**
	 * Formats code ready for editing
	 *
	 * @param array $block ( 1 - attributes, 2 - the code )
	 * @return string formatted code
	 */
	function format_to_edit( $block )
	{
		return '[codeblock'.$block[1].']'.str_replace( array( '&lt;', '&gt;', '&amp;' ), array( '<', '>', '&' ), $block[2] ).'[/codeblock]';
	}


	/**
	 * Spits out the styles used
	 *
	 * @see Plugin::SkinBeginHtmlHead()
	 */
	function SkinBeginHtmlHead()
	{
		add_css_headline('/* AstonishMe code plugin styles */'
			.'.amc0,.amc1,.amc2,.amc3,.amc4,.amc5,.amc6,.amc7,.amc8,.amc9 {'
			.'background:url('.$this->get_plugin_url().'img/numbers.gif) no-repeat; }');
        
		require_css($this->get_plugin_url().'amcode.css', /* absolute path for ResourceBundles, evaluates to true in b2evo: */ dirname(__FILE__).'/amcode.css');

		// TODO: dh> move this to a IE-specific file, e.g. add_css_headline, but which is specific for IE
		//           Or easier: fix it with a hack in amcode.css itself?!
		add_headline('<!--[if IE]>'
			.'<style type="text/css">'
			.'/* IE: make sure the last line is not hidden by a scrollbar */'
			.'div.codeblock.amc_short table {'
			.'	margin-bottom: 2ex;'
			.'}'
			.'</style>'
			.'<![endif]-->');
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
									.'</code></td></tr>'."\n";
		}
		// make "long" value a setting ? - yabs
		return '<p class="amcode">'.$this->languageCache[ $type ]->language_title.':</p><div class="codeblock amc_'.$type.' '.( $count < 26 ? 'amc_short' : 'amc_long' ).'"><table>'.$output.'</table></div>';
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
		preg_match( '#line=("|\')([0-9]+?)\1#', $block[2], $match );
		$offset = ( empty( $match[2] ) ? 0 : $match[2] - 1 );

		// set the language if present - default : code
		preg_match( '#lang=("|\')([^\1]+?)\1#', $block[2], $match );
		$language = strtolower( ( empty( $match[2] ) ? 'code' : $match[2] ) );

		if( $code = trim( $block[4] ) )
		{	// we have a code block
			// is the relevant language highlighter already cached?
			if( empty( $this->languageCache[ $language ] ) )
			{	// lets attempt to load the language
				$language_file = dirname(__FILE__).'/highlighters/'.$language.'.highlighter.php';
				if( is_file( $language_file ) )
				{ // language class exists, lets load and cache an instance of it
					require_once $language_file;
					$class = 'am_'.$language.'_highlighter';
					$this->languageCache[ $language ] = & new $class( $this );
				}
				else
				{	// language class doesn't exists, fallback to default highlighter
					$language = 'code';
					if( empty( $this->languageCache[ $language ] ) )
					{	// lets attempt to load the default language
						$language_file = dirname(__FILE__).'/highlighters/'.$language.'.highlighter.php';
						if( is_file( $language_file ) )
						{ // default lanugage exists
							require_once $language_file;
							$class = 'am_'.$language.'_highlighter';
							// add the language to the cache
							$this->languageCache[ $language ] = & new $class( $this );
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


/*
 * $Log$
 * Revision 1.19  2009/01/25 23:13:55  blueyed
 * Fix CVS log section, which is not phpdoc
 *
 * Revision 1.18  2009/01/25 18:56:50  blueyed
 * doc fix: Error on line 747 - Unclosed code tag in DocBlock, parsing will be incorrect
 *
 * Revision 1.17  2008/12/30 23:00:41  fplanque
 * Major waste of time rolling back broken black magic! :(
 * 1) It was breaking the backoffice as soon as $admin_url was not a direct child of $baseurl.
 * 2) relying on dynamic argument decoding for backward comaptibility is totally unmaintainable and unreliable
 * 3) function names with () in log break searches big time
 * 4) complexity with no purpose (at least as it was)
 *
 * Revision 1.15  2008/11/12 14:14:55  blueyed
 * code_highlight_plugin: use add_css_headline/require_css/add_headline for CSS injections.
 *
 * Revision 1.14  2008/03/21 16:07:02  fplanque
 * longer post slugs
 *
 * Revision 1.13  2008/03/21 10:31:17  yabs
 * add ability to render code in comments
 *
 * Revision 1.12  2008/01/21 09:35:41  fplanque
 * (c) 2008
 *
 * Revision 1.11  2008/01/19 16:13:02  yabs
 * removed obsolete version changed function
 *
 * Revision 1.10  2007/07/09 19:07:44  fplanque
 * minor
 *
 * Revision 1.9  2007/07/05 07:59:34  yabs
 * added user setting for display toolbar on EdB's suggestion :
 * http://edb.evoblog.com/blogstuff/thoughts-while-i-clear-my-head
 *
 * Revision 1.8  2007/07/03 10:45:00  yabs
 * changed <codeblock/span> to [codeblock/span]
 *
 * Revision 1.7  2007/07/01 03:59:49  fplanque
 * rollback until clean implementation
 *
 * Revision 1.5  2007/06/26 02:40:53  fplanque
 * security checks
 *
 * Revision 1.4  2007/06/17 13:28:22  blueyed
 * Fixed doc
 *
 * Revision 1.3  2007/05/14 02:43:06  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.2  2007/05/04 20:43:08  fplanque
 * MFB
 *
 * Revision 1.1.2.4  2007/05/01 09:09:58  yabs
 * removed css toolbar button
 *
 * Revision 1.1.2.3  2007/04/23 11:59:11  yabs
 * removed old code
 * pass $this to language classes
 *
 * Revision 1.1.2.2  2007/04/20 02:50:14  fplanque
 * code highlight plugin aka AM code plugin
 *
 * Revision 1.1.2.15  2007/04/18 23:37:59  fplanque
 * removed old code
 *
 * Revision 1.1.2.14  2007/04/18 22:53:23  fplanque
 * minor
 *
 * Revision 1.1.2.13  2007/04/08 14:35:59  yabs
 * Minor bugfixes
 * Minor doc changes
 * Amended PluginVersionChanged() to the new <!-- codeblock --> tags
 * Added in an experimental highlighter utilising classes per language
 *
 * Revision 1.1.2.12  2007/04/07 22:20:24  fplanque
 * codespan + changed method names
 *
 * Revision 1.1.2.11  2007/04/07 15:38:15  fplanque
 * "codeblock"
 *
 * Revision 1.1.2.10  2007/04/07 07:26:36  yabs
 * Minor changes to classnames
 * Minor changes to docs
 * Added target="_blank" ++ setting for xhtml strict
 * Moved code tidying to a seperate function so it can be reused if we add more languages
 * Renamed highlighting functions to be more descriptive
 * Removed trailing space from do_numbering()
 * Probably added a few notes as well :p
 *
 * Revision 1.1.2.9  2007/04/05 22:43:00  fplanque
 * Added hook: UnfilterItemContents
 *
 * Revision 1.1.2.7  2007/04/01 13:36:26  yabs
 * rewritten the php syntax highlighter .... hopefully this one will work in all php version ;)
 *
 * Revision 1.1.2.6  2007/04/01 10:00:55  yabs
 * made some minor changes to the doc
 * made a few minor code changes ( line="99" etc )
 * added in styles for admin area
 * added in code/php tags
 * added a fair few comments
 *
 * Revision 1.1.2.5  2007/03/31 22:42:44  fplanque
 * FilterItemContent event
 *
 * Revision 1.1.2.4  2007/03/31 18:03:51  fplanque
 * doc / todos (not necessarily short term)
 *
 * Revision 1.1.2.3  2007/03/31 09:57:48  yabs
 * minor php5 corrections
 * removed highlighting bug ( would replace all occurences of colours with classnames )
 *
 * Revision 1.1.2.2  2007/03/31 09:07:16  yabs
 * Correcting highlighting for php5
 *
 * Revision 1.1.2.1  2007/03/31 07:22:28  yabs
 * Added to cvs
 *
 *
 */
?>

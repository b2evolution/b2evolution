<?php
/**
 * This file is part of the AstonishMe Code plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2007 by Yabba/Scott - {@link http://astonishme.co.uk/contact/}.
 *
 * @package plugins
 *
 * @author Yabba: Paul Jones - {@link http://astonishme.co.uk/}
 * @author Stk: Scott Kimler - {@link http://astonishme.co.uk/}
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 * @todo
 * yabs > would like this to extend an "am_highlighter" class, but not sure how to handle $this->T_()
 * fp> is T_() actually used?
 * fp> you should probably just pass the plugin as argument to the constructor, this way you can call $plugin->T_()
 * yabs> it's only used in this one class, made the changes :)
 */
class am_php_highlighter
{
	/**
	 * Array php functions
	 *
	 * @access private
	 */
	var $php_functions = array();

	/**
	 * Array php syntax highlighting colours
	 *
	 * @access private
	 */
	var $highlight_colours = array();


	/**
	 * Text name of language for display
	 *
	 * This is unused whilst "Experimental" as it requires a modification of the plugin
	 * it would be used to replace the text output above the codeblock instead of ucfirst( language )
	 *
	 */
	var $language_title = 'PHP';


	/**
	 * Boolean are we in strict mode ?
	 *
	 */
	var $strict_mode = false;


	/**
	 * Called automatically on class innit
	 *
	 * @param object $parent
	 * @return object am_php_highlighter
	 */
	function __construct( & $parent )
	{
		$this->parent = & $parent;
		return $this;
	}


	/**
	 * Highlights php ready for displaying
	 * Links all known php functions to php.net documentation
	 *
	 * @param string $block - the code
	 * @return string highlighted php code
	 */

	function highlight_code( $block )
	{
		// check if we've already grabbed existing php functions
		if( empty( $this->php_functions ) )
		{	// lets build a list of all native php functions
			$this->php_functions = get_defined_functions();
			// and add any missing ones
			// possible enhancement is to link all evo functions to it's own docs - yabs
			$this->php_functions['internal'] = array_merge( $this->php_functions['internal'], array( 'die', 'exit', 'array', 'require', 'require_once', 'include', 'include_once' ) );
		}

		// check if we've already grabbed the highlight colours
		if( empty( $this->highlight_colours ) )
		{	// get the users php_ini colours for highlight_string()
			$this->highlight_colours = array(
					'highlight_bg' => '<span class="#FFFFFF',
					'highlight_comment' => '<span class="#FF8000',
					'highlight_default' => '<span class="#0000BB',
					'highlight_html' => '<span class="#000000',
					'highlight_keyword' => '<span class="#007700',
					'highlight_string' => '<span class="#DD0000',
				);
		}
		// lets sort out the code and highlighting
		$code = // take a deep breath and read this code upwards ;)
			// stitch it all back together again
			implode( "\n",
			// gets rid of the < ?php & * / that we added
			array_slice( explode( "\n",
			// clean it all up ready for numbering
			$this->parent->tidy_code_output(
			// find all potential php functions and link relevant ones to the documentation
			preg_replace_callback( '#([a-z0-9\-_]+?)(\</span>\<span class="amc_keyword">)?\(#i', array( $this, 'link_to_manual' ),
			// change the php.ini highlight colours to our class names
			str_replace( $this->highlight_colours, array( '<span class="amc_background', '<span class="amc_comment', '<span class="amc_default', '<span class="amc_html', '<span class="amc_keyword', '<span class="amc_string' ),
			// convert php 4's <font> tags to <span> & prepare to convert php 4 & 5 to our class names
			preg_replace( array( '#\<font color="\#([0-9a-f]+?)">#i', '#\</font>#', '#\<span style="color: \#([0-9a-f]+?)">#i' ), array( '<span class="#$1">', '</span>','<span class="#$1">' ),
			// remove <code></code> tags and \n's added by highlight_string
			str_replace( array( '<code>', '</code>', "\n" ), '',
			// top code with < ?php & to ensure highlight occurs
			highlight_string( '<?php'."\n"
			// convert relevant entities back for highlighting
			.str_replace( array( '&lt;', '&gt;', '&amp;', '&quot;' ), array( '<', '>', '&', '"' ),
			// get rid of empty start/end lines, and add */ to the end to overcome highlight_string() bug with unterminated comments
			 trim( $block ) )."\n".'*/', true)
			) ) ) ), '<br />' )
			// get rid of the first and last array elements
			), 1, -1 ) );
		return $code;
	}


	/**
	 * Links a php function to the documentation
	 *
	 * @param array $function ( 1 - function name, 2 - additional trailing html )
	 * @return string php.net documentation link if appropriate
	 */
	function link_to_manual( $function )
	{	// check if $function is a native php function and provide a link to the documentation if true
		// if not in xhtml strict mode ( setting ) then add target="_blank"
		return ( in_array( trim( $function[1] ), $this->php_functions['internal'] ) ? sprintf( '<a href="http://www.php.net/function.%1$s" title=" %2$s : '.$function[1].'() "'.( $this->strict_mode ? '' : ' target="_blank"' ).' class="codeblock_external_link">%1$s</a>', trim( $function[1] ), $this->parent->T_( 'Read the PHP.net documentation for' ) ) : $function[1] ).( empty( $function[2] ) ? '' : $function[2] ).'(';
	}

}

?>
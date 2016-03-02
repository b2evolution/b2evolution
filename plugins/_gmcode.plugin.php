<?php
/**
 * This file implements the GMcode plugin for b2evolution
 *
 * GreyMatter style formatting
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '../plugins/_custom_tags.plugin.php', 'custom_tags_plugin' );

/**
 * Replaces GreyMatter markup in HTML (not XML).
 *
 * @todo dh> Do not replace in tags, it matches e.g. the following for italic:
 *           """<img src="//url" /> [...] http://"""!
 *
 * @package plugins
 */
class gmcode_plugin extends custom_tags_plugin
{
	var $code = 'b2evGMco';
	var $name = 'GM code';
	var $priority = 45;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '5.0.0';
	var $number_of_installs = 1;

	var $configurable_post_list = false;
	var $configurable_comment_list = false;
	var $configurable_message_list = false;
	var $configurable_email_list = false;

	var $default_search_list = '# \*\* (.+?) \*\* #x
	# \\\\ (.+?) \\\\ #x
	# (?<!:) \x2f\x2f (.+?) \x2f\x2f #x
	# __ (.+?) __ #x
	/ \#\# (.+?) \#\# /x
	/ %% ( \s*? \n )? (.+?) ( \n \s*? )? %% /sx';

	var $default_replace_list = '<strong>$1</strong>
	<em>$1</em>
	<em>$1</em>
	<span style="text-decoration: underline">$1</span>
	<tt>$1</tt>
	<div class="codeblock"><pre><code>$2</code></pre></div>';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('GreyMatter style formatting');
		$this->long_desc = T_('**bold** \\\\italics\\\\ //italics// __underline__ ##tt## %%codeblock%%');
	}


	function replace_callback( $content, $search, $replace )
	{ // Replace text outside of html tags
		return callback_on_non_matching_blocks( $content, '~<[^>]*>~s', array( $this, 'second_replace_callback' ), array( $search, $replace ) );
	}


	function second_replace_callback( $content, $search, $replace)
	{
		return preg_replace( $search, $replace, $content );
	}

	/**
	 * The following function are here so the events will be registered
	 * @see Plugins_admin::get_registered_events()
	 */
	function RenderItemAsHtml( & $params )
	{
		parent::RenderItemAsHtml( $params );
	}

}

?>
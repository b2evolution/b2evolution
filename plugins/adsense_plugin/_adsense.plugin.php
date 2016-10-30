<?php
/**
 * This file implements the AdSense plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Replaces AdSense markup in HTML.
 *
 * @todo Remove it in XML Feeds?
 *
 * @package plugins
 */
class adsense_plugin extends Plugin
{
	var $code = 'evo_adsense';
	var $name = 'AdSense';
	var $priority = 10;
	var $group = 'rendering';
	var $help_url = 'http://b2evolution.net/blog-ads/adsense-plugin.php';
	var $short_desc;
	var $long_desc;
	var $version = '6.7.8';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Easy AdSense insertion into posts.');
		$this->long_desc = T_('<p>This plugin allows you to easily insert AdSense into your posts (simply type [adsense:], or use the toolbar button in expert mode).</p>
<p>The plugin will stop expanding AdSense blocks when a limit is reached (3 by default).</p>
<p>Look for version 2.0 for multiple AdSense format support.</p>');
	}


	/**
	 * Define the GLOBAL settings of the plugin here. These can then be edited in the backoffice in System > Plugins.
	 *
	 * @param array Associative array of parameters (since v1.9).
	 *    'for_editing': true, if the settings get queried for editing;
	 *                   false, if they get queried for instantiating {@link Plugin::$Settings}.
	 * @return array see {@link Plugin::GetDefaultSettings()}.
	 * The array to be returned should define the names of the settings as keys (max length is 30 chars)
	 * and assign an array with the following keys to them (only 'label' is required):
	 */
	function GetDefaultSettings( & $params )
	{

		$r = array(
			'adsense_block' => array(
					'label' => 'Default AdSense block',
					'type' => 'html_textarea',
					'cols' => 60,
					'rows' => 10,
					'defaultvalue' => '<div style="float:right; clear:both; margin:5px;">

<!-- Paste from here... -->
<div style="border:1px solid red; width:150px; padding:3px;"><strong>You must copy/paste your AdSense code in here.</strong> You can do this on the Plugin config page.</div>
<!-- ...to here -->

</div>',
					'note' => 'Copy/Paste your AdSense code from Google into here. You can surround it with some CSS for decoration and/or positionning.',
				),
			'max_blocks_in_content' => array(
					'label' => 'Default Max # of blocks',
					'type' => 'integer',
					'size' => 2,
					'maxlength' => 2,
					'defaultvalue' => 3,
					'note' => T_('Maximum number of AdSense blocks the plugin should expand in post contents. Google terms typically set the limit to 3. You may wish to set it to less if you add blocks into the sidebar.'),
				),
			);

		return $r;
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$r = array(
			'coll_adsense_block' => array(
					'label' => 'AdSense block',
					'type' => 'html_textarea',
					'cols' => 60,
					'rows' => 10,
					'defaultvalue' => $this->Settings->get('adsense_block'),
					'note' => 'Copy/Paste your AdSense code from Google into here. You can surround it with some CSS for decoration and/or positionning.',
				),
			'coll_max_blocks_in_content' => array(
					'label' => 'Max # of blocks',
					'type' => 'integer',
					'size' => 2,
					'maxlength' => 2,
					'defaultvalue' => $this->Settings->get('max_blocks_in_content'),
					'note' => T_('Maximum number of AdSense blocks the plugin should expand in post contents. Google terms typically set the limit to 3. You may wish to set it to less if you add blocks into the sidebar.'),
				),
			);

		return array_merge( parent::get_coll_setting_definitions( $params ), $r );
	}


	/**
	 * Comments out the adsense tags so that they don't get worked on by other renderers like Auto-P
	 *
	 * @param mixed $params
	 */
	function FilterItemContents( & $params )
	{
		$content = & $params['content'];

		$content = preg_replace( '~\[(adsense:)\]~', '<!-- [$1] -->', $content );

		return true;
	}


	/**
	 * Changes the commented out tags into something that is visible to the editor
	 *
	 * @param mixed $params
	 */
	function UnfilterItemContents( & $params )
	{
		$content = & $params['content'];

		$content = preg_replace( '~<!-- \[(adsense:)\] -->~', '[$1]', $content );

		return true;
	}


	/**
	 * Event handler: Called when rendering item/post contents as HTML. (CACHED)
	 *
	 * The rendered content will be *cached* and the cached content will be reused on subsequent displays.
	 * Use {@link DisplayItemAsHtml()} instead if you want to do rendering at display time.
	 *
 	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderItemAsHtml( & $params )
	{
		// Dummy placeholder. Without it the plugin would ne be considered to be a renderer...
		return false;
	}


	/**
	 * Perform rendering (at display time, i-e: NOT cached)
	 *
	 * @todo does this actually get fed out in the xml feeds?
	 *
	 * @see Plugin::DisplayItemAsHtml()
	 */
	function DisplayItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = replace_content_outcode( '~<!-- \[adsense:\] -->~', array( $this, 'DisplayItem_callback' ), $content, 'replace_content_callback' );

		return true;
	}


	/**
	 *
	 */
	function DisplayItem_callback( $matches )
	{
		global $Blog;

	  	/**
		 * How many blocks already displayed?
		 */
		static $adsense_blocks_counter = 0;

		$adsense_blocks_counter++;

		if( $adsense_blocks_counter > $this->Settings->get( 'max_blocks_in_content' ) )
		{
			return '<!-- Adsense block #'.$adsense_blocks_counter.' not displayed since it exceed the limit of '
							.$this->get_coll_setting( 'coll_max_blocks_in_content', $Blog ).' -->';
		}

		return $this->get_coll_setting( 'coll_adsense_block', $Blog );
	}

	/**
	 * Filter out adsense tags from XML content.
	 *
	 * @see Plugin::RenderItemAsXml()
	 */
	function DisplayItemAsXml( & $params )
	{
		$content = & $params['data'];

		$content = preg_replace( '~\[adsense:\]~', '', $content );

		return true;
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
			return $this->DisplayCodeToolbar();
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
			return $this->DisplayCodeToolbar();
		}

		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		$Comment = & $params['Comment'];
		if( $Comment )
		{	// Get a post of the comment:
			if( $comment_Item = & $Comment->get_Item() )
			{
				$Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog
			global $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		return $this->DisplayCodeToolbar();
	}


	/**
	 * Display a code toolbar
	 *
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCodeToolbar()
	{
		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_title_before' ).'AdSense: '.$this->get_template( 'toolbar_title_after' );
		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" id="adsense_default" title="'.T_('Insert AdSense block').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="textarea_wrap_selection|b2evoCanvas|[adsense:]| |1" value="'.T_('AdSense').'" />';
		echo $this->get_template( 'toolbar_group_after' );
		echo $this->get_template( 'toolbar_after' );

		return true;
	}
}

?>
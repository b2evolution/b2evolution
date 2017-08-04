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
	var $priority = 85;
	var $group = 'rendering';
	var $subgroup = 'other';
	var $help_url = 'http://b2evolution.net/blog-ads/adsense-plugin.php';
	var $short_desc;
	var $long_desc;
	var $version = '6.9.3';
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
			'auto_block' => array(
					'label' => 'Auto blocks',
					'type' => 'checkbox',
					'defaultvalue' => 0,
					'note' => T_('Automatically add an ad block in the middle of any post that has no <code>[adsense:]</code> tag yet.'),
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
			'coll_auto_block' => array(
					'label' => 'Auto blocks',
					'type' => 'checkbox',
					'defaultvalue' => $this->Settings->get('auto_block'),
					'note' => T_('Automatically add an ad block in the middle of any post that has no <code>[adsense:]</code> tag yet.'),
				),
			);

		return array_merge( parent::get_coll_setting_definitions( $params ), $r );
	}


	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
				'label' => T_('Block title'),
				'note' => T_('Title to display in your skin.'),
				'size' => 40,
				'defaultvalue' => '',
			),
		), parent::get_widget_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Comments out the adsense tags so that they don't get worked on by other renderers like Auto-P
	 *
	 * @param mixed $params
	 */
	function FilterItemContents( & $params )
	{
		$content = & $params['content'];

		$content = replace_content_outcode( '~\[(adsense:)\]~', '<!-- [$1] -->', $content );

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

		$content = replace_content_outcode( '~<!-- \[(adsense:)\] -->~', '[$1]', $content );

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
		if( empty( $params['Item'] ) )
		{	// Allow only for Items
			return false;
		}

		$content = & $params['data'];
		$Item = & $params['Item'];
		$item_Blog = & $params['Item']->get_Blog();

		if( ! $this->get_coll_setting( 'coll_auto_block', $item_Blog ) )
		{	// Don't insert auto blocks because it is not enabled by setting:
			return false;
		}

		// Insert AdSense block automatically in the middle of content that has no this tag yet:
		$content = $this->insert_auto_adsense_block( $content );

		return true;
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

		$content = replace_content_outcode( '~<!-- \[adsense:\] -->~', array( $this, 'get_adsense_block' ), $content, 'replace_content_callback' );

		return true;
	}


	/**
	 * Get AdSense block
	 *
	 * @param array Matches of replace result if it is used as callback
	 * @param boolean TRUE to limit a count of AdSense blocks by setting
	 * @return string AdSense block
	 */
	function get_adsense_block( $matches = array(), $limit_by_counter = true )
	{
		global $Collection, $Blog;

		/**
		 * How many blocks already displayed?
		 */
		static $adsense_blocks_counter = 0;

		$adsense_blocks_counter++;

		if( $limit_by_counter && $adsense_blocks_counter > $this->get_coll_setting( 'coll_max_blocks_in_content', $Blog ) )
		{	// Stop AdSense blocks because of limit by setting:
			return '<!-- Adsense block #'.$adsense_blocks_counter.' not displayed since it exceed the limit of '
							.$this->get_coll_setting( 'coll_max_blocks_in_content', $Blog ).' -->';
		}

		return $this->get_coll_setting( 'coll_adsense_block', $Blog );
	}


	/**
	 * Insert AdSense block automatically in the middle of content that has no this tag yet
	 *
	 * @param string
	 * @return string
	 */
	function insert_auto_adsense_block( $content )
	{
		if( preg_match_outcode( '~\[adsense:\]~', $content, $matches ) )
		{	// If at least one AdSense block exists in content then we should NOT insert auto block:
			return $content;
		}

		// Split content by end of paragraph:
		$splitted_content = preg_split( '~</p>~', $content );

		// Get the middle of content:
		$middle_index = floor( count( $splitted_content ) / 2 ) - 1;
		if( $middle_index < 0 )
		{
			$middle_index = 0;
		}

		// Insert auto block in the middle of content:
		$auto_content = '';
		foreach( $splitted_content as $p => $content_part )
		{
			$auto_content .= $content_part.'</p>';
			if( $middle_index == $p )
			{	// This is middle of content, insert auto block:
				$auto_content .= '<!-- [adsense:] -->';
			}
		}

		return $auto_content;
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
			$Collection = $Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog:
			global $Collection, $Blog;
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

		return $this->DisplayCodeToolbar( $params );
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
			return $this->DisplayCodeToolbar( $params );
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
			return $this->DisplayCodeToolbar( $params );
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
				$Collection = $Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog
			global $Collection, $Blog;
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

		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Display a code toolbar
	 *
	 * @param array Params
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCodeToolbar( $params = array() )
	{
		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_title_before' ).'AdSense: '.$this->get_template( 'toolbar_title_after' );
		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" id="adsense_default" title="'.T_('Insert AdSense block').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="textarea_wrap_selection|'.$params['js_prefix'].'b2evoCanvas|[adsense:]| |1" value="'.T_('AdSense').'" />';
		echo $this->get_template( 'toolbar_group_after' );
		echo $this->get_template( 'toolbar_after' );

		return true;
	}


	/**
	 * Event handler: SkinTag (widget)
	 *
	 * @param array Associative array of parameters.
	 * @return boolean did we display?
	 */
	function SkinTag( & $params )
	{
		echo $params['block_start'];

		if( $params['block_display_title'] && ! empty( $params['title'] ) )
		{	// Display widget title:
			echo $params['block_title_start'];
			echo $params['title'];
			echo $params['block_title_end'];
		}

		echo $params['block_body_start'];

		// Display AdSense block:
		echo $this->get_adsense_block( array(), false );

		echo $params['block_body_end'];

		echo $params['block_end'];

		return true;
	}
}

?>
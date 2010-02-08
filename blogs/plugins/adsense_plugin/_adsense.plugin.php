<?php
/**
 * This file implements the AdSense plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
	var $apply_rendering = 'opt-out';
	var $group = 'rendering';
	var $help_url = 'http://b2evolution.net/blog-ads/adsense-plugin.php';
	var $short_desc;
	var $long_desc;
	var $version = '0.9.1';
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
			'adsense_block' => array(
					'label' => 'AdSense block',
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
					'label' => 'Max # of blocks',
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
   * Comments out the adsense tags so that they don't get worked on by other renderers like Auto-P
   *
	 * @param mixed $params
	 */
	function FilterItemContents( & $params )
	{
		$content = & $params['content'];

		$content = preg_replace( '¤\[(adsense:)\]¤', '<!-- [$1] -->', $content );

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

		$content = preg_replace( '¤<!-- \[(adsense:)\] -->¤', '[$1]', $content );

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

		$content = preg_replace_callback( '¤<!-- \[adsense:\] -->¤', array( $this, 'DisplayItem_callback' ), $content );

		return true;
	}


  /**
	 *
	 */
	function DisplayItem_callback( $matches )
	{
	  /**
		 * How many blocks already displayed?
		 */
		static $adsense_blocks_counter = 0;

		$adsense_blocks_counter++;

		if( $adsense_blocks_counter > $this->Settings->get( 'max_blocks_in_content' ) )
		{
			return '<!-- Adsense block #'.$adsense_blocks_counter.' not displayed since it exceed the limit of '
							.$this->Settings->get( 'max_blocks_in_content' ).' -->';
		}

		return $this->Settings->get( 'adsense_block' );
	}

	/**
	 * Filter out adsense tags from XML content.
	 *
	 * @see Plugin::RenderItemAsXml()
	 */
	function DisplayItemAsXml( & $params )
	{
		$content = & $params['data'];

		$content = preg_replace( '¤\[adsense:\]¤', '', $content );

		return true;
	}

	/**
	 * Display a toolbar in admin
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( $params['edit_layout'] == 'simple' )
		{	// This is too complex for simple mode, don't display it:
			return false;
		}

		echo '<div class="edit_toolbar">';
		echo '<input type="button" id="adsense_default" title="'.T_('Insert AdSense block').'" class="quicktags" onclick="textarea_wrap_selection( b2evoCanvas, \'[adsense:]\', \'\', 1 );" value="'.T_('AdSense').'" />';
		echo '</div>';

		return true;
	}

}


/*
 * $Log$
 * Revision 1.6  2010/02/08 17:55:50  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.5  2009/03/08 23:57:48  fplanque
 * 2009
 *
 * Revision 1.4  2009/01/25 19:09:32  blueyed
 * phpdoc fixes
 *
 * Revision 1.3  2008/09/27 00:05:55  fplanque
 * minor/version bump
 *
 * Revision 1.2  2008/01/21 09:35:41  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/10/08 22:50:09  fplanque
 * integrated adsense plugin
 *
 */
?>

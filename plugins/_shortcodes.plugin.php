<?php
/**
 * This file implements the Shortcodes Toolbar plugin for b2evolution
 *
 * This is Ron's remix!
 * Includes code from the WordPress team -
 *  http://sourceforge.net/project/memberlist.php?group_id=51422
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
class shortcodes_plugin extends Plugin
{
	var $code = 'evo_shortcodes';
	var $name = 'Short Codes';
	var $priority = 40;
	var $version = '6.7.0';
	var $group = 'editor';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Short codes inserting');
		$this->long_desc = T_('This plugin will display a toolbar with buttons to quickly insert short codes.');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array( 'default_comment_using' => 'disabled' ) );

		return parent::get_coll_setting_definitions( $default_params );
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
		global $Hit;

		if( $Hit->is_lynx() )
		{ // let's deactivate quicktags on Lynx, because they don't work there.
			return false;
		}

		$Item = & $params['Item'];

		if( empty( $Item ) || $Item->is_intro() || ! $Item->get_type_setting( 'allow_breaks' ) )
		{	// Teaser and page breaks are not allowed for current item type and for all intro items:
			return false;
		}

		$item_Blog = & $Item->get_Blog();

		if( ! $this->get_coll_setting( 'coll_use_for_posts', $item_Blog ) )
		{	// This plugin is disabled to use for posts:
			return false;
		}

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
		//<![CDATA[
		var shortcodes_buttons = new Array();

		function shortcodes_button( id, text, tag, title, style )
		{
			this.id = id;       // used to name the toolbar button
			this.text = text;   // label on button
			this.tag = tag;     // tag code to insert
			this.title = title; // title
			this.style = style; // style on button
		}

		shortcodes_buttons[shortcodes_buttons.length] = new shortcodes_button(
				'shortcodes_teaserbreak', '[teaserbreak]', '[teaserbreak]',
				'<?php echo TS_('Teaser break') ?>', ''
			);
		shortcodes_buttons[shortcodes_buttons.length] = new shortcodes_button(
				'shortcodes_pagebreak', '[pagebreak]', '[pagebreak]',
				'<?php echo TS_('Page break') ?>'
			);

		function shortcodes_toolbar( title )
		{
			var r = '<?php echo $this->get_template( 'toolbar_title_before' ); ?>' + title + '<?php echo $this->get_template( 'toolbar_title_after' ); ?>'
				+ '<?php echo $this->get_template( 'toolbar_group_before' ); ?>';
			for( var i = 0; i < shortcodes_buttons.length; i++ )
			{
				var button = shortcodes_buttons[i];
				r += '<input type="button" id="' + button.id + '" title="' + button.title + '"'
					+ ( typeof( button.style ) != 'undefined' ? ' style="' + button.style + '"' : '' ) + ' class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="shortcodes_insert_tag|b2evoCanvas|'+i+'" value="' + button.text + '" />';
			}
			r += '<?php echo $this->get_template( 'toolbar_group_after' ); ?>';

			jQuery( '.<?php echo $this->code ?>_toolbar' ).html( r );
		}

		function shortcodes_insert_tag( canvas_field, i )
		{
			if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
			{ // tinyMCE plugin is active now, we should focus cursor to the edit area
				tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
			}
			// Insert tag text in area
			textarea_wrap_selection( canvas_field, shortcodes_buttons[i].tag, '', 0 );
		}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?><script type="text/javascript">shortcodes_toolbar( '<?php echo TS_('Shortcodes:'); ?>' );</script><?php

		return true;
	}
}

?>
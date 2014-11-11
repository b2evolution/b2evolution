<?php
/**
 * This file implements the Shortcodes Toolbar plugin for b2evolution
 *
 * This is Ron's remix!
 * Includes code from the WordPress team -
 *  http://sourceforge.net/project/memberlist.php?group_id=51422
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
class shortcodes_plugin extends Plugin
{
	var $code = 'evo_shortcodes';
	var $name = 'Short Codes';
	var $priority = 40;
	var $version = '5.0.0';
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
	 * Display a toolbar
	 *
	 * @todo dh> This seems to be a lot of Javascript. Please try exporting it in a
	 *       (dynamically created) .js src file. Then we could use cache headers
	 *       to let the browser cache it.
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		global $Hit, $edited_Comment;

		if( ! empty( $edited_Comment ) )
		{ // Don't display the toolbars on edit comment form
			return false;
		}

		if( $Hit->is_lynx() )
		{ // let's deactivate quicktags on Lynx, because they don't work there.
			return false;
		}

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?>
		<script type="text/javascript">
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
				'<?php echo TS_('Teaser break') ?>', 'margin-left:8px;'
			);
		shortcodes_buttons[shortcodes_buttons.length] = new shortcodes_button(
				'shortcodes_pagebreak', '[pagebreak]', '[pagebreak]',
				'<?php echo TS_('Page break') ?>'
			);

		function shortcodes_toolbar( title )
		{
			document.write( '<div>' + title );
			for( var i = 0; i < shortcodes_buttons.length; i++ )
			{
				var button = shortcodes_buttons[i];
				document.write( '<input type="button" id="' + button.id + '" title="' + button.title + '"'
					+ ( typeof( button.style ) != 'undefined' ? ' style="' + button.style + '"' : '' ) + ' class="shortcodes" data-func="shortcodes_insert_tag|b2evoCanvas|'+i+'" value="' + button.text + '" />' );
			}
			document.write('</div>');
		}

		function shortcodes_insert_tag( canvas_field, i )
		{
			var tag = shortcodes_buttons[i].tag;
			if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
			{ // tinyMCE plugin is active now, we should insert separators by plugin buttons to render them
				if( tag == '[teaserbreak]' && typeof( tinyMCE.activeEditor.execCommands.mceMore ) != 'undefined' )
				{ // MorePlugin exists in tinyMCE
					tinyMCE.execCommand( 'mceMore', false, tinyMCE.activeEditor.id );
					return;
				}
				if( tag == '[pagebreak]' && typeof( tinyMCE.activeEditor.execCommands.mcePageBreak ) != 'undefined' )
				{ // PageBreakPlugin exists in tinyMCE
					tinyMCE.execCommand( 'mcePageBreak', false, tinyMCE.activeEditor.id );
					return;
				}
			}
			// Insert plain text in simple textarea
			textarea_wrap_selection( canvas_field, tag, '', 0 );
		}
		//]]>
		</script>

		<div class="edit_toolbar shortcodes_toolbar"><script type="text/javascript">shortcodes_toolbar( '<?php echo TS_('Shortcodes:'); ?>' );</script></div>

		<?php
		return true;
	}
}

?>
<?php
/**
 * This file implements the Quicktags Toolbar plugin for b2evolution
 *
 * This is Ron's remix!
 * Includes code from the WordPress team -
 *  http://sourceforge.net/project/memberlist.php?group_id=51422
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class quicktags_plugin extends Plugin
{
	var $code = 'b2evQTag';
	var $name = 'Quick Tags';
	var $priority = 30;
	var $version = '2.4.1';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Easy HTML tags inserting');
		$this->long_desc = T_('This plugin will display a toolbar with buttons to quickly insert HTML tags around selected text in a post.');
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
		global $Hit;

		$simple = ( $params['edit_layout'] == 'simple' );

		if( $Hit->is_lynx() )
		{ // let's deactivate quicktags on Lynx, because they don't work there.
			return false;
		}
		?>

		<script type="text/javascript">
		//<![CDATA[
		var b2evoButtons = new Array();
		var b2evoLinks = new Array();
		var b2evoOpenTags = new Array();

		function b2evoButton(id, display, style, tagStart, tagEnd, access, tit, open)
		{
			this.id = id;							// used to name the toolbar button
			this.display = display;		// label on button
			this.style = style;				// style on button
			this.tagStart = tagStart; // open tag
			this.tagEnd = tagEnd;			// close tag
			this.access = access;			// access key
			this.tit = tit;						// title
			this.open = open;					// set to -1 if tag does not need to be closed
		}

	<?php
	if( $simple )
	{ ?>
		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_bold'
				,'bold', 'font-weight:bold;'
				,'<b>','</b>'
				,'b'
				,'<?php echo T_('Bold [Alt-B]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_italic'
				,'italic', 'font-style:italic;'
				,'<i>','</i>'
				,'i'
				,'<?php echo T_('Italic [Alt-I]') ?>'
			);
		<?php
	}
	else
	{
		?>
		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_ins'
				,'ins', ''
				,'<ins>','</ins>'
				,'b'
				,'<?php echo T_('INSerted') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_del'
				,'del', 'text-decoration:line-through;'
				,'<del>','</del>'
				,'i'
				,'<?php echo T_('DELeted') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_strong'
				,'str', 'font-weight:bold;'
				,'<strong>','</strong>'
				,'s'
				,'<?php echo T_('STRong [Alt-S]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_em'
				,'em', 'font-style:italic;'
				,'<em>','</em>'
				,'e'
				,'<?php echo T_('EMphasis [Alt-E]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_code'
				,'code', ''
				,'<code>','</code>'
				,'c'
				,'<?php echo T_('CODE [Alt-C]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_par'
				,'p', 'margin-left:8px;'
				,'<p>','</p>'
				,'p'
				,'<?php echo T_('Paragraph [Alt-P]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_block'
				,'block', ''
				,'<blockquote>','</blockquote>'
				,'b'
				,'<?php echo T_('BLOCKQUOTE [Alt-B]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_pre'
				,'pre', ''
				,'<pre>','</pre>'
				,'r'
				,'<?php echo T_('PREformatted text [Alt-R]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_ul'
				,'ul', ''
				,'<ul>\n','</ul>\n\n'
				,'u'
				,'<?php echo T_('Unordered List [Alt-U]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_ol'
				,'ol', ''
				,'<ol>\n','</ol>\n\n'
				,'o'
				,'<?php echo T_('Ordered List [Alt-O]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_li'
				,'li', ''
				,'  <li>','</li>\n'
				,'l'
				,'<?php echo T_('List Item [Alt-L]') ?>'
			);

		<?php
	}
	?>

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_img'
				,'<?php echo ($simple ? 'image' : 'img') ?>', 'margin-left:8px;'
				,'',''
				,'g'
				,'<?php echo T_('IMaGe [Alt-G]') ?>'
				,-1
			); // special case

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_link'
				,'link', 'text-decoration:underline;'
				,'','</a>'
				,'a'
				,'<?php echo T_('A href [Alt-A]') ?>'
			); // special case

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_more'
				,'<?php echo ($simple ? 'more separator' : '!M') ?>', 'margin-left:8px;'
				,'<!-'+'-more-'+'->',''
				,'m'
				,'<?php echo T_('More [Alt-M]') ?>'
				,-1
			);

	<?php
		if( !$simple )
		{ ?>
		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_noteaser'
				,'!NT', ''
				,'<!-'+'-noteaser-'+'->',''
				,'t'
				,'<?php echo T_('no teaser [Alt-T]') ?>'
				,-1
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_next'
				,'!NP', ''
				,'<!-'+'-nextpage-'+'->',''
				,'q'
				,'<?php echo T_('next page [Alt-Q]') ?>'
				,-1
			);
			<?php
		}
	?>

		function b2evoShowButton(button, i)
		{
			if( button.id == 'b2evo_img' )
			{
				document.write('<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit
						+ '" style="' + button.style + '" class="quicktags" onclick="b2evoInsertImage(b2evoCanvas);" value="' + button.display + '" />');
			}
			else if( button.id == 'b2evo_link' )
			{
				document.write('<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit
						+ '" style="' + button.style + '" class="quicktags" onclick="b2evoInsertLink(b2evoCanvas, ' + i + ');" value="' + button.display + '" />');
			}
			else
			{	// Normal buttons:
				document.write('<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit
						+ '" style="' + button.style + '" class="quicktags" onclick="b2evoInsertTag(b2evoCanvas, ' + i + ');" value="' + button.display + '"  />');
			}
		}

		// Memorize a new open tag
		function b2evoAddTag(button)
		{
			if( b2evoButtons[button].tagEnd != '' )
			{
				b2evoOpenTags[b2evoOpenTags.length] = button;
				document.getElementById(b2evoButtons[button].id).value = '/' + document.getElementById(b2evoButtons[button].id).value;
			}
		}

		// Forget about an open tag
		function b2evoRemoveTag(button)
		{
			for (i = 0; i < b2evoOpenTags.length; i++)
			{
				if (b2evoOpenTags[i] == button)
				{
					b2evoOpenTags.splice(i, 1);
					document.getElementById(b2evoButtons[button].id).value = document.getElementById(b2evoButtons[button].id).value.replace('/', '');
				}
			}
		}

		function b2evoCheckOpenTags(button)
		{
			var tag = 0;
			for (i = 0; i < b2evoOpenTags.length; i++)
			{
				if (b2evoOpenTags[i] == button)
				{
					tag++;
				}
			}

			if (tag > 0)
			{
				return true; // tag found
			}
			else
			{
				return false; // tag not found
			}
		}

		function b2evoCloseAllTags()
		{
			var count = b2evoOpenTags.length;
			for (o = 0; o < count; o++)
			{
				b2evoInsertTag(b2evoCanvas, b2evoOpenTags[b2evoOpenTags.length - 1]);
			}
		}

		function b2evoToolbar()
		{
			document.write('<div>');
			for (var i = 0; i < b2evoButtons.length; i++)
			{
				b2evoShowButton(b2evoButtons[i], i);
			}
			document.write('<input type="button" id="b2evo_close" class="quicktags" onclick="b2evoCloseAllTags();" title="<?php echo T_('Close all tags') ?>" value="<?php echo ($simple ? 'close all tags' : 'X') ?>" style="margin-left:8px;" />');
			document.write('</div>');
		}

		/**
		 * insertion code
		 */
		function b2evoInsertTag( myField, i )
		{
			// we need to know if something is selected.
			// First, ask plugins, then try IE and Mozilla.
			var sel_text = b2evo_Callbacks.trigger_callback("get_selected_text_for_"+myField.id);
			var focus_when_finished = false; // used for IE

			if( sel_text == null )
			{ // detect selection:
				//IE support
				if(document.selection)
				{
					myField.focus();
					var sel = document.selection.createRange();
					sel_text = sel.text;
					focus_when_finished = true;
				}
				//MOZILLA/NETSCAPE support
				else if(myField.selectionStart || myField.selectionStart == '0')
				{
					var startPos = myField.selectionStart;
					var endPos = myField.selectionEnd;
					sel_text = (startPos != endPos);
				}
			}

			if( sel_text )
			{ // some text selected
				textarea_wrap_selection( myField, b2evoButtons[i].tagStart, b2evoButtons[i].tagEnd, 0 );
			}
			else
			{
				if( !b2evoCheckOpenTags(i) || b2evoButtons[i].tagEnd == '')
				{
					textarea_wrap_selection( myField, b2evoButtons[i].tagStart, '', 0 );
					b2evoAddTag(i);
				}
				else
				{
					textarea_wrap_selection( myField, '', b2evoButtons[i].tagEnd, 0 );
					b2evoRemoveTag(i);
				}
			}
			if(focus_when_finished)
			{
				myField.focus();
			}
		}


		function b2evoInsertLink(myField, i, defaultValue)
		{
			if (!defaultValue)
			{
				defaultValue = 'http://';
			}

			if (!b2evoCheckOpenTags(i)) {
				var URL = prompt( '<?php echo T_('URL') ?>:', defaultValue);
				if (URL)
				{
					b2evoButtons[i].tagStart = '<a href="' + URL + '">';
					b2evoInsertTag(myField, i);
				}
			}
			else
			{
				b2evoInsertTag( myField, i );
			}
		}

		function b2evoInsertImage(myField)
		{
			var myValue = prompt( '<?php echo T_('URL') ?>:', 'http://' );
			if (myValue) {
				myValue = '<img src="'
						+ myValue
						+ '" alt="' + prompt('<?php echo T_('ALTernate text') ?>:', '')
						+ '" title="' + prompt('<?php echo T_('Title') ?>:', '')
						+ '" />';
				textarea_wrap_selection( myField, myValue, '', 1 );
			}
		}
		//]]>
		</script>

		<div class="edit_toolbar"><script type="text/javascript">b2evoToolbar();</script></div>

		<?php
		return true;
	}
}

/*
 * $Log$
 * Revision 1.34  2009/03/08 23:57:47  fplanque
 * 2009
 *
 * Revision 1.33  2009/03/04 00:10:42  blueyed
 * Make Hit constructor more lazy.
 *  - Move referer_dom_ID generation/fetching to own method
 *  - wrap Debuglog additons with "debug"
 *  - Conditionally call detect_useragent, if required. Move
 *    vars to methods for this
 *  - get_user_agent alone does not require detect_useragent
 * Feel free to revert it (since it changed all the is_foo vars
 * to methods - PHP5 would allow to use __get to handle legacy
 * access to those vars however), but please consider also
 * removing this stuff from HTML classnames, since that is kind
 * of disturbing/unreliable by itself).
 *
 * Revision 1.32  2008/02/13 07:26:23  fplanque
 * quicktags in simple mode
 *
 * Revision 1.31  2008/01/21 09:35:41  fplanque
 * (c) 2008
 *
 * Revision 1.30  2007/09/17 20:04:40  fplanque
 * UI improvements
 *
 * Revision 1.29  2007/09/03 16:46:58  fplanque
 * minor
 *
 * Revision 1.28  2007/06/29 23:12:18  blueyed
 * todo
 *
 * Revision 1.27  2007/06/29 23:10:01  blueyed
 * - Cleaned up code duplication in b2evoInsertTag()
 * - Added get_selected_text_for_ID-JS callback
 *
 * Revision 1.26  2007/04/26 00:11:04  fplanque
 * (c) 2007
 *
 * Revision 1.25  2007/04/20 01:42:32  fplanque
 * removed excess javascript
 *
 * Revision 1.24  2006/09/23 15:06:03  blueyed
 * Javascript fixes: use CDATA
 *
 * Revision 1.23  2006/07/16 23:04:05  fplanque
 * Most plugins should keep as quiet as possible in simple mode.
 * Smilies are about the only thing simple enough for simple mode.
 *
 * Revision 1.22  2006/07/12 21:13:17  blueyed
 * Javascript callback handler (e.g., for interaction of WYSIWYG editors with toolbar plugins)
 *
 * Revision 1.21  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.20  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.19  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.18  2006/06/26 23:10:24  fplanque
 * minor / doc
 *
 * Revision 1.17  2006/06/24 05:19:39  smpdawg
 * Fixed various javascript warnings and errors.
 * Spelling corrections.
 * Fixed PHP warnings.
 *
 * Revision 1.16  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.15  2006/05/30 20:31:18  blueyed
 * typo
 *
 * Revision 1.14  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.13  2006/03/12 23:09:28  fplanque
 * doc cleanup
 *
 * Revision 1.12  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.11  2005/11/21 18:16:29  fplanque
 * okay, a TWO liner :P
 *
 */
?>
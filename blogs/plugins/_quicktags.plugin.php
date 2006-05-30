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
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
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
	var $version = 'CVS $Revision$';

	/**
	 * Constructor
	 */
	function quicktags_plugin()
	{
		$this->short_desc = T_('Easy HTML tags inserting');
		$this->long_desc = T_('This plugin will display a toolbar with buttons to quicly insert HTML tags around selected text in a post.');
	}


	/**
	 * Display a toolbar
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		global $Hit;

		if( $Hit->is_lynx )
		{ // let's deactivate quicktags on Lynx, because they don't work there.
			return false;
		}
		?>

		<script type="text/javascript">
		<!--
		var b2evoButtons = new Array();
		var b2evoLinks = new Array();
		var b2evoOpenTags = new Array();

		function b2evoButton(id, display, tagStart, tagEnd, access, tit, open) {
			this.id = id;							// used to name the toolbar button
			this.display = display;		// label on button
			this.tagStart = tagStart; // open tag
			this.tagEnd = tagEnd;			// close tag
			this.access = access;			// access key
			this.tit = tit;						// title
			this.open = open;					// set to -1 if tag does not need to be closed
		}

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_ins'
				,'ins'
				,'<ins>','</ins>'
				,'i'
				,'<?php echo T_('INSerted [Alt-I]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_del'
				,'del'
				,'<del>','</del>'
				,'d'
				,'<?php echo T_('DELeted [Alt-D]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_strong'
				,'str'
				,'<strong>','</strong>'
				,'s'
				,'<?php echo T_('STRong [Alt-S]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_em'
				,'em'
				,'<em>','</em>'
				,'e'
				,'<?php echo T_('EMphasis [Alt-E]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_code'
				,'code'
				,'<code>','</code>'
				,'c'
				,'<?php echo T_('CODE [Alt-C]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_par'
				,'p'
				,'<p>','</p>'
				,'p'
				,'<?php echo T_('Paragraph [Alt-P]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_ul'
				,'ul'
				,'<ul>\n','</ul>\n\n'
				,'u'
				,'<?php echo T_('Unordered List [Alt-U]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_ol'
				,'ol'
				,'<ol>\n','</ol>\n\n'
				,'o'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_li'
				,'li'
				,'  <li>','</li>\n'
				,'l'
				,'<?php echo T_('List Item [Alt-L]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_block'
				,'block'
				,'<blockquote>','</blockquote>'
				,'b'
				,'<?php echo T_('BLOCKQUOTE [Alt-B]') ?>'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_img'
				,'img'
				,'',''
				,'g'
				,'<?php echo T_('IMaGe [Alt-G]') ?>'
				,-1
			); // special case

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_link'
				,'link'
				,'','</a>'
				,'a'
				,'<?php echo T_('A href [Alt-A]') ?>'
			); // special case

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_pre'
				,'pre'
				,'<pre>','</pre>'
				,'r'
				,'[Alt-R]'
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_more'
				,'!M'
				,'<!-'+'-more-'+'->',''
				,'m'
				,'<?php echo T_('More [Alt-M]') ?>'
				,-1
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_noteaser'
				,'!NT'
				,'<!-'+'-noteaser-'+'->',''
				,'t'
				,'<?php echo T_('no teaser [Alt-T]') ?>'
				,-1
			);

		b2evoButtons[b2evoButtons.length] = new b2evoButton(
				'b2evo_next'
				,'!NP'
				,'<!-'+'-nextpage-'+'->',''
				,'q'
				,'<?php echo T_('next page [Alt-Q]') ?>'
				,-1
			);

		function b2evoLink() {
			this.display = '';
			this.URL = '';
			this.newWin = 0;
		}

		function b2evoShowButton(button, i)
		{
			if (button.id == 'b2evo_img')
			{
				document.write('<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit + '" class="quicktags" onclick="b2evoInsertImage(b2evoCanvas);" value="' + button.display + '" />');
			}
			else if (button.id == 'b2evo_link')
			{
				document.write('<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit + '" class="quicktags" onclick="b2evoInsertLink(b2evoCanvas, ' + i + ');" value="' + button.display + '" />');
			}
			else
			{
				document.write('<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit + '" class="quicktags" onclick="b2evoInsertTag(b2evoCanvas, ' + i + ');" value="' + button.display + '"  />');
			}
		}

		function b2evoAddTag(button) {
			if (b2evoButtons[button].tagEnd != '') {
				b2evoOpenTags[b2evoOpenTags.length] = button;
				document.getElementById(b2evoButtons[button].id).value = '/' + document.getElementById(b2evoButtons[button].id).value;
			}
		}

		function b2evoRemoveTag(button) {
			for (i = 0; i < b2evoOpenTags.length; i++) {
				if (b2evoOpenTags[i] == button) {
					b2evoOpenTags.splice(i, 1);
					document.getElementById(b2evoButtons[button].id).value = 		document.getElementById(b2evoButtons[button].id).value.replace('/', '');
				}
			}
		}

		function b2evoCheckOpenTags(button) {
			var tag = 0;
			for (i = 0; i < b2evoOpenTags.length; i++) {
				if (b2evoOpenTags[i] == button) {
					tag++;
				}
			}
			if (tag > 0) {
				return true; // tag found
			}
			else {
				return false; // tag not found
			}
		}

		function b2evoCloseAllTags() {
			var count = b2evoOpenTags.length;
			for (o = 0; o < count; o++) {
				b2evoInsertTag(b2evoCanvas, b2evoOpenTags[b2evoOpenTags.length - 1]);
			}
		}

		function b2evoToolbar() {
			document.write('<div>');
			for (i = 0; i < b2evoButtons.length; i++) {
				b2evoShowButton(b2evoButtons[i], i);
			}
			document.write('<input type="button" id="b2evo_close" class="quicktags" onclick="b2evoCloseAllTags();" title="<?php echo T_('Close all tags') ?>" value="X" />');
			document.write('</div>');
		}

		// insertion code
		function b2evoInsertTag(myField, i)
		{
			//IE support
			if (document.selection)
			{
				myField.focus();
					sel = document.selection.createRange();
				if (sel.text.length > 0) {
					sel.text = b2evoButtons[i].tagStart + sel.text + b2evoButtons[i].tagEnd;
				}
				else {
					if (!b2evoCheckOpenTags(i) || b2evoButtons[i].tagEnd == '') {
						sel.text = b2evoButtons[i].tagStart;
						b2evoAddTag(i);
					}
					else {
						sel.text = b2evoButtons[i].tagEnd;
						b2evoRemoveTag(i);
					}
				}
				myField.focus();
			}
			//MOZILLA/NETSCAPE support
			else if (myField.selectionStart || myField.selectionStart == '0')
			{
				var startPos = myField.selectionStart;
				var endPos = myField.selectionEnd;
				var cursorPos = endPos;

				var scrollTop, scrollLeft;
				if( myField.type == 'textarea' && typeof myField.scrollTop != 'undefined' )
				{ // remember old position
					scrollTop = myField.scrollTop;
					scrollLeft = myField.scrollLeft;
				}

				if (startPos != endPos)
				{ // some text selected
					myField.value = myField.value.substring(0, startPos)
												+ b2evoButtons[i].tagStart
												+ myField.value.substring(startPos, endPos)
												+ b2evoButtons[i].tagEnd
												+ myField.value.substring(endPos, myField.value.length);
					cursorPos += b2evoButtons[i].tagStart.length + b2evoButtons[i].tagEnd.length;
				}
				else
				{
					if (!b2evoCheckOpenTags(i) || b2evoButtons[i].tagEnd == '')
					{
						myField.value = myField.value.substring(0, startPos)
													+ b2evoButtons[i].tagStart
													+ myField.value.substring(endPos, myField.value.length);
						b2evoAddTag(i);
						cursorPos = startPos + b2evoButtons[i].tagStart.length;
					}
					else
					{
						myField.value = myField.value.substring(0, startPos)
													+ b2evoButtons[i].tagEnd
													+ myField.value.substring(endPos, myField.value.length);
						b2evoRemoveTag(i);
						cursorPos = startPos + b2evoButtons[i].tagEnd.length;
					}
				}

				if( typeof scrollTop != 'undefined' )
				{ // scroll to old position
					myField.scrollTop = scrollTop;
					myField.scrollLeft = scrollLeft;
				}

				myField.focus();
				myField.selectionStart = cursorPos;
				myField.selectionEnd = cursorPos;
			}
			else
			{ // Browser not especially supported
				if (!b2evoCheckOpenTags(i) || b2evoButtons[i].tagEnd == '') {
					myField.value += b2evoButtons[i].tagStart;
					b2evoAddTag(i);
				}
				else {
					myField.value += b2evoButtons[i].tagEnd;
					b2evoRemoveTag(i);
				}
				myField.focus();
			}
		}

		function b2evoInsertContent(myField, myValue) {
			//IE support
			if (document.selection) {
				myField.focus();
				sel = document.selection.createRange();
				sel.text = myValue;
				myField.focus();
			}
			//MOZILLA/NETSCAPE support
			else if (myField.selectionStart || myField.selectionStart == '0') {
				var startPos = myField.selectionStart;
				var endPos = myField.selectionEnd;
				myField.value = myField.value.substring(0, startPos)
											+ myValue
											+ myField.value.substring(endPos, myField.value.length);
				myField.focus();
				myField.selectionStart = startPos + myValue.length;
				myField.selectionEnd = startPos + myValue.length;
			} else {
				myField.value += myValue;
				myField.focus();
			}
		}

		function b2evoInsertLink(myField, i, defaultValue) {
			if (!defaultValue) {
				defaultValue = 'http://';
			}
			if (!b2evoCheckOpenTags(i)) {
				var URL = prompt('<?php echo T_('URL') ?>:' ,defaultValue);
				if (URL) {
					b2evoButtons[i].tagStart = '<a href="' + URL + '">';
					b2evoInsertTag(myField, i);
				}
			}
			else {
				b2evoInsertTag(myField, i);
			}
		}

		function b2evoInsertImage(myField) {
			var myValue = prompt('<?php echo T_('URL') ?>:', 'http://');
			if (myValue) {
				myValue = '<img src="'
						+ myValue
						+ '" alt="' + prompt('<?php echo T_('ALTernate text') ?>:', '')
						+ '" title="' + prompt('<?php echo T_('Title') ?>:', '')
						+ '" />';
				b2evoInsertContent(myField, myValue);
			}
		}
		// -->
		</script>

		<div class="edit_toolbar"><script type="text/javascript">b2evoToolbar();</script></div>

		<?php
		return true;
	}
}

/*
 * $Log$
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
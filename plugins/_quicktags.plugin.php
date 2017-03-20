<?php
/**
 * This file implements the Quicktags Toolbar plugin for b2evolution
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
class quicktags_plugin extends Plugin
{
	var $code = 'b2evQTag';
	var $name = 'Quick Tags';
	var $priority = 30;
	var $version = '6.7.9';
	var $group = 'editor';
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
		$Item = & $params['Item'];

		if( empty( $Item ) || ! $Item->get_type_setting( 'allow_html' ) )
		{	// Only when HTML is allowed in post:
			return false;
		}

		$item_Blog = & $Item->get_Blog();

		if( ! $this->get_coll_setting( 'coll_use_for_posts', $item_Blog ) )
		{	// This plugin is disabled to use for posts:
			return false;
		}

		return $this->DisplayCodeToolbar( $params );
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
			$comment_Item = & $Comment->get_Item();
		}

		if( empty( $comment_Item ) || ! $comment_Item->get_type_setting( 'allow_html' ) )
		{	// Only when HTML is allowed in post:
			return false;
		}

		$item_Blog = & $comment_Item->get_Blog();

		if( ! $this->get_coll_setting( 'coll_use_for_comments', $item_Blog ) )
		{	// This plugin is disabled to use for comments:
			return false;
		}

		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Display a code toolbar
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCodeToolbar( & $params )
	{
		global $Hit;

		if( $Hit->is_lynx() )
		{ // let's deactivate quicktags on Lynx, because they don't work there.
			return false;
		}

		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		$simple = ( isset( $params['edit_layout'] ) && $params['edit_layout'] == 'inskin' );

		// Load js to work with textarea
		require_js( 'functions.js', 'blog', true, true );

		?><script type="text/javascript">
		//<![CDATA[
		var <?php echo $params['js_prefix']; ?>b2evoButtons = new Array();
		var <?php echo $params['js_prefix']; ?>b2evoLinks = new Array();
		var <?php echo $params['js_prefix']; ?>b2evoOpenTags = new Array();

		function <?php echo $params['js_prefix']; ?>b2evoButton(id, display, style, tagStart, tagEnd, access, tit, open, grp_pos)
		{
			this.id = id;							// used to name the toolbar button
			this.display = display;		// label on button
			this.style = style;				// style on button
			this.tagStart = tagStart; // open tag
			this.tagEnd = tagEnd;			// close tag
			this.access = access;			// access key
			this.tit = tit;						// title
			this.open = open;					// set to -1 if tag does not need to be closed
			this.grp_pos = grp_pos;   // position in the group, e.g. 'last'
		}

	<?php
	if( $simple )
	{ ?>
		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_bold'
				,'bold', 'font-weight:bold;'
				,'<b>','</b>'
				,'b'
				,'<?php echo TS_('Bold [Alt-B]') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_italic'
				,'italic', 'font-style:italic;'
				,'<i>','</i>'
				,'i'
				,'<?php echo TS_('Italic [Alt-I]') ?>', -1, 'last'
			);
		<?php
	}
	else
	{
		?>
		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_ins'
				,'ins', ''
				,'<ins>','</ins>'
				,'b'
				,'<?php echo TS_('INSerted') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_del'
				,'del', 'text-decoration:line-through;'
				,'<del>','</del>'
				,'i'
				,'<?php echo TS_('DELeted') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_strong'
				,'str', 'font-weight:bold;'
				,'<strong>','</strong>'
				,'s'
				,'<?php echo TS_('STRong [Alt-S]') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_em'
				,'em', 'font-style:italic;'
				,'<em>','</em>'
				,'e'
				,'<?php echo TS_('EMphasis [Alt-E]') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_code'
				,'code', ''
				,'<code>','</code>'
				,'c'
				,'<?php echo TS_('CODE [Alt-C]') ?>', -1, 'last'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_par'
				,'p', ''
				,'<p>','</p>'
				,'p'
				,'<?php echo TS_('Paragraph [Alt-P]') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_block'
				,'block', ''
				,'<blockquote>','</blockquote>'
				,'b'
				,'<?php echo TS_('BLOCKQUOTE [Alt-B]') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_pre'
				,'pre', ''
				,'<pre>','</pre>'
				,'r'
				,'<?php echo TS_('PREformatted text [Alt-R]') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_ul'
				,'ul', ''
				,'<ul>\n','</ul>\n\n'
				,'u'
				,'<?php echo TS_('Unordered List [Alt-U]') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_ol'
				,'ol', ''
				,'<ol>\n','</ol>\n\n'
				,'o'
				,'<?php echo TS_('Ordered List [Alt-O]') ?>'
			);

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_li'
				,'li', ''
				,'  <li>','</li>\n'
				,'l'
				,'<?php echo TS_('List Item [Alt-L]') ?>', -1, 'last'
			);

		<?php
	}
	?>

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_img'
				,'<?php echo ($simple ? 'image' : 'img') ?>', ''
				,'',''
				,'g'
				,'<?php echo TS_('IMaGe [Alt-G]') ?>'
				,-1
			); // special case

		<?php echo $params['js_prefix']; ?>b2evoButtons[<?php echo $params['js_prefix']; ?>b2evoButtons.length] = new <?php echo $params['js_prefix']; ?>b2evoButton(
				'<?php echo $params['js_prefix']; ?>b2evo_link'
				,'link', 'text-decoration:underline;'
				,'','</a>'
				,'a'
				,'<?php echo TS_('A href [Alt-A]') ?>'
			); // special case

		function <?php echo $params['js_prefix']; ?>b2evoGetButton(button, i)
		{
			var r = '';
			if( button.id == '<?php echo $params['js_prefix']; ?>b2evo_img' )
			{
				r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit
					+ '" style="' + button.style + '" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="<?php echo $params['js_prefix']; ?>b2evoInsertImage|<?php echo $params['js_prefix']; ?>b2evoCanvas" value="' + button.display + '" />';
			}
			else if( button.id == '<?php echo $params['js_prefix']; ?>b2evo_link' )
			{
				r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit
					+ '" style="' + button.style + '" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="<?php echo $params['js_prefix']; ?>b2evoInsertLink|<?php echo $params['js_prefix']; ?>b2evoCanvas|'+i+'" value="' + button.display + '" />';
			}
			else
			{	// Normal buttons:
				r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.tit
					+ '" style="' + button.style + '" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="<?php echo $params['js_prefix']; ?>b2evoInsertTag|<?php echo $params['js_prefix']; ?>b2evoCanvas|'+i+'" value="' + button.display + '" />';
			}

			return r;
		}

		// Memorize a new open tag
		function <?php echo $params['js_prefix']; ?>b2evoAddTag(button)
		{
			if( <?php echo $params['js_prefix']; ?>b2evoButtons[button].tagEnd != '' )
			{
				<?php echo $params['js_prefix']; ?>b2evoOpenTags[<?php echo $params['js_prefix']; ?>b2evoOpenTags.length] = button;
				document.getElementById(<?php echo $params['js_prefix']; ?>b2evoButtons[button].id).value = '/' + document.getElementById(<?php echo $params['js_prefix']; ?>b2evoButtons[button].id).value;
			}
		}

		// Forget about an open tag
		function <?php echo $params['js_prefix']; ?>b2evoRemoveTag(button)
		{
			for (i = 0; i < <?php echo $params['js_prefix']; ?>b2evoOpenTags.length; i++)
			{
				if (<?php echo $params['js_prefix']; ?>b2evoOpenTags[i] == button)
				{
					<?php echo $params['js_prefix']; ?>b2evoOpenTags.splice(i, 1);
					document.getElementById(<?php echo $params['js_prefix']; ?>b2evoButtons[button].id).value = document.getElementById(<?php echo $params['js_prefix']; ?>b2evoButtons[button].id).value.replace('/', '');
				}
			}
		}

		function <?php echo $params['js_prefix']; ?>b2evoCheckOpenTags(button)
		{
			var tag = 0;
			for (i = 0; i < <?php echo $params['js_prefix']; ?>b2evoOpenTags.length; i++)
			{
				if (<?php echo $params['js_prefix']; ?>b2evoOpenTags[i] == button)
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

		function <?php echo $params['js_prefix']; ?>b2evoCloseAllTags()
		{
			var count = <?php echo $params['js_prefix']; ?>b2evoOpenTags.length;
			for (o = 0; o < count; o++)
			{
				<?php echo $params['js_prefix']; ?>b2evoInsertTag(<?php echo $params['js_prefix']; ?>b2evoCanvas, <?php echo $params['js_prefix']; ?>b2evoOpenTags[<?php echo $params['js_prefix']; ?>b2evoOpenTags.length - 1]);
			}
		}

		function <?php echo $params['js_prefix']; ?>b2evoToolbar( title )
		{
			var r = '<?php echo format_to_js( $this->get_template( 'toolbar_title_before' ) ); ?>' + title + '<?php echo format_to_js( $this->get_template( 'toolbar_title_after' ) ); ?>'
				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_before' ) ); ?>';
			for (var i = 0; i < <?php echo $params['js_prefix']; ?>b2evoButtons.length; i++)
			{
				r += <?php echo $params['js_prefix']; ?>b2evoGetButton( <?php echo $params['js_prefix']; ?>b2evoButtons[i], i );
				if( <?php echo $params['js_prefix']; ?>b2evoButtons[i].grp_pos == 'last' && i > 0 && i < <?php echo $params['js_prefix']; ?>b2evoButtons.length - 1 )
				{ // Separator between groups
					r += '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ).$this->get_template( 'toolbar_group_before' ) ); ?>';
				}
			}
			r += '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ).$this->get_template( 'toolbar_group_before' ) ); ?>'
				+ '<input type="button" id="b2evo_close" class="<?php echo $this->get_template( 'toolbar_button_class' ); ?>" data-func="<?php echo $params['js_prefix']; ?>b2evoCloseAllTags" title="<?php echo format_to_output( T_('Close all tags'), 'htmlattr' ); ?>" value="<?php echo ($simple ? 'close all tags' : 'X') ?>" />'
				+ '<?php echo format_to_js( $this->get_template( 'toolbar_group_after' ) ); ?>';

			jQuery( '.<?php echo $params['js_prefix'].$this->code ?>_toolbar' ).html( r );
		}

		/**
		 * insertion code
		 */
		function <?php echo $params['js_prefix']; ?>b2evoInsertTag( myField, i )
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
				textarea_wrap_selection( myField, <?php echo $params['js_prefix']; ?>b2evoButtons[i].tagStart, <?php echo $params['js_prefix']; ?>b2evoButtons[i].tagEnd, 0 );
			}
			else
			{
				if( !<?php echo $params['js_prefix']; ?>b2evoCheckOpenTags(i) || <?php echo $params['js_prefix']; ?>b2evoButtons[i].tagEnd == '')
				{
					textarea_wrap_selection( myField, <?php echo $params['js_prefix']; ?>b2evoButtons[i].tagStart, '', 0 );
					<?php echo $params['js_prefix']; ?>b2evoAddTag(i);
				}
				else
				{
					textarea_wrap_selection( myField, '', <?php echo $params['js_prefix']; ?>b2evoButtons[i].tagEnd, 0 );
					<?php echo $params['js_prefix']; ?>b2evoRemoveTag(i);
				}
			}
			if(focus_when_finished)
			{
				myField.focus();
			}
		}


		function <?php echo $params['js_prefix']; ?>b2evoInsertLink(myField, i, defaultValue)
		{
			if (!defaultValue)
			{
				defaultValue = 'http://';
			}

			if (!<?php echo $params['js_prefix']; ?>b2evoCheckOpenTags(i)) {
				var URL = prompt( '<?php echo TS_('URL') ?>:', defaultValue);
				if (URL)
				{
					b2evoButtons[i].tagStart = '<a href="' + URL + '">';
					<?php echo $params['js_prefix']; ?>b2evoInsertTag(myField, i);
				}
			}
			else
			{
				<?php echo $params['js_prefix']; ?>b2evoInsertTag( myField, i );
			}
		}

		function <?php echo $params['js_prefix']; ?>b2evoInsertImage(myField)
		{
			var myValue = prompt( '<?php echo TS_('URL') ?>:', 'http://' );
			if (myValue) {
				myValue = '<img src="'
						+ myValue
						+ '" alt="' + prompt('<?php echo TS_('ALTernate text') ?>:', '')
						+ '" title="' + prompt('<?php echo TS_('Title') ?>:', '')
						+ '" />';
				textarea_wrap_selection( myField, myValue, '', 1 );
			}
		}
		//]]>
		</script><?php

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );
		?><script type="text/javascript"><?php echo $params['js_prefix']; ?>b2evoToolbar( 'HTML: ' );</script><?php

		return true;
	}
}

?>
<?php
/**
 * This file implements the Image Smilies Renderer plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @package plugins
 */
class smilies_plugin extends Plugin
{
	var $code = 'b2evSmil';
	var $name = 'Smilies';
	var $priority = 80;
	var $version = 'CVS $Revision$';
	var $apply_rendering = 'opt-out';

	/**
	 * Text similes search array
	 *
	 * @access private
	 */
	var $search;

	/**
	 * IMG replace array
	 *
	 * @access private
	 */
	var $replace;

	/**
	 * Smiley definitions
	 *
	 * @access private
	 */
	var $smilies;

	/**
	 * Path to images
	 *
	 * @access private
	 */
	var $smilies_path;


	/**
	 * Constructor
	 */
	function smilies_plugin()
	{
		$this->short_desc = T_('Graphical smileys');
		$this->long_desc = T_('This renderer will convert text smilies like :) to graphical icons.<br />
			Optionally, it will also display a toolbar for quick insertion of smilies into a post.');

		/**
		 * Smilies configuration.
		 * TODO: Move/transform to PluginSettings
		 */
		require dirname(__FILE__).'/_smilies.conf.php';
	}


	/**
	* Defaults for user specific settings: "Display toolbar"
	 *
	 * @return array
	 */
	function GetDefaultSettings()
	{
		return array(
				'use_toolbar_default' => array(
					'label' => T_( 'Use smilies toolbar' ),
					'defaultvalue' => '1',
					'type' => 'checkbox',
					'note' => T_( 'This is the default setting. Users can override it in their profile.' ),
				),
			);
	}


	/**
	 * Allowing the user to deactivate the toolbar..
	 *
	 * @return array
	 */
	function GetDefaultUserSettings()
	{
		return array(
				'use_toolbar' => array(
					'label' => T_( 'Use smilies toolbar' ),
					'defaultvalue' => $this->Settings->get('use_toolbar_default'),
					'type' => 'checkbox',
				),
			);
	}


	/**
	 * Display a toolbar
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( ! $this->UserSettings->get('use_toolbar') )
		{
			return false;
		}

		$grins = '';
		$smiled = array();
		foreach( $this->smilies as $smiley => $grin )
		{
			if (!in_array($grin, $smiled))
			{
				$smiled[] = $grin;
				$smiley = str_replace(' ', '', $smiley);
				$grins .= '<img src="'. $this->smilies_path. '/'. $grin. '" title="'.$smiley.'" alt="'.$smiley
									.'" class="top" onclick="grin(\''. str_replace("'","\'",$smiley). '\');" /> ';
			}
		}

		print('<div class="edit_toolbar">'. $grins. '</div>');
		ob_start();
		?>
		<script type="text/javascript">
		function grin(tag)
		{
			var myField = b2evoCanvas;

			if (document.selection) {
				myField.focus();
				sel = document.selection.createRange();
				sel.text = tag;
				myField.focus();
			}
			else if (myField.selectionStart || myField.selectionStart == '0') {
				var startPos = myField.selectionStart;
				var endPos = myField.selectionEnd;
				var cursorPos = endPos;
				myField.value = myField.value.substring(0, startPos)
								+ tag
								+ myField.value.substring(endPos, myField.value.length);
				cursorPos += tag.length;
				myField.focus();
				myField.selectionStart = cursorPos;
				myField.selectionEnd = cursorPos;
			}
			else {
				myField.value += tag;
				myField.focus();
			}
		}

		</script>
		<?php
		$grins = ob_get_contents();
		ob_end_clean();
		print($grins);

		return true;
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		if( ! isset( $this->search ) )
		{	// We haven't prepared the smilies yet
			$this->search = array();


			$tmpsmilies = $this->smilies;
			uksort($tmpsmilies, array(&$this, 'smiliescmp'));

			foreach($tmpsmilies as $smiley => $img)
			{
				$this->search[] = $smiley;
				$smiley_masked = '';
				for ($i = 0; $i < strlen($smiley); $i++ )
				{
					$smiley_masked .=  '&#'.ord(substr($smiley, $i, 1)).';';
				}

				// We don't use getimagesize() here until we have a mean
				// to preprocess smilies. It takes up to much time when
				// processing them at display time.
				$this->replace[] = '<img src="'.$this->smilies_path.'/'.$img.'" alt="'.$smiley_masked.'" class="middle" />';
			}
		}


		// REPLACE:  But only in non-HTML blocks, totally excluding <CODE>..</CODE> and <PRE>..</PRE>

		$content = & $params['data'];

		// Lazy-check first, using stristr() (stripos() is only available since PHP5):
		if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false )
		{ // Call ReplaceTagSafe() on everything outside <pre></pre> and <code></code>:
			$content = callback_on_non_matching_blocks( $content,
					'~<(code|pre)[^>]*>.*?</\1>~is',
					array( & $this, 'ReplaceTagSafe' ) );
		}
		else
		{ // No CODE or PRE blocks, replace on the whole thing
			$content = $this->ReplaceTagSafe($content);
		}

		return true;
	}


	/**
	 * This callback gets called once after every tags+text chunk
	 * @return string Text with replaced smilies
	 */
	function preg_insert_smilies_callback( $text )
	{
		return str_replace( $this->search, $this->replace, $text );
	}


	/**
	 * Replace smilies in non-HTML-tag portions of the text.
	 * @uses callback_on_non_matching_blocks()
	 */
	function ReplaceTagSafe($text)
	{
		return callback_on_non_matching_blocks( $text, '~<[^>]*>~', array(&$this, 'preg_insert_smilies_callback') );
	}


	/**
	 * sorts the smilies' array by length
	 * this is important if you want :)) to superseede :) for example
	 */
	function smiliescmp($a, $b)
	{
		if(($diff = strlen($b) - strlen($a)) == 0)
		{
			return strcmp($a, $b);
		}
		return $diff;
	}

}


/*
 * $Log$
 * Revision 1.21  2006/05/30 20:26:59  blueyed
 * typo
 *
 * Revision 1.20  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.19  2006/04/24 20:16:08  blueyed
 * Use callback_on_non_matching_blocks(); excluding PRE and CODE blocks
 *
 * Revision 1.18  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
<?php
/**
 * This file implements the Image Smilies Renderer plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @author bgorge
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
	var $apply_when = 'opt-out';
	var $apply_to_html = true;
	var $apply_to_xml = false; // Leave the smilies alone

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
	 *
	 * {@internal smilies_plugin::smilies_plugin(-)}}
	 */
	function smilies_plugin()
	{
		$this->short_desc = T_('Graphical smileys');
		$this->long_desc = T_('One click smilies inserting + Convert text smilies to icons');

		require dirname(__FILE__). '/_smilies.conf.php';
	}


  /**
	 * Register event callbacks
	 *
	 * This method is called by b2evo to ask the plugin what events it would
	 * like to receive notifications for.
	 *
	 * {@internal Plugin::RegisterEvents(-)}}
	 *
	 * @return array List of event names we wish to be called back for
	 */
/*	function RegisterEvents()
	{
		return array( 'DisplayToolbar' );
	} */


	/**
	 * Display a toolbar
	 *
	 * {@internal smilies_plugin::DisplayToolbar(-)}}
	 *
   * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayToolbar( & $params )
	{
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
			var myField;
			if (document.getElementById('content') && document.getElementById('content').type == 'textarea') {
				myField = document.getElementById('content');
			}
			else {
				return false;
			}
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
	 * {@internal smilies_plugin::Render(-)}}
	 *
	 * @param array Associative array of parameters
	 * 							(Output format, see {@link format_to_output()})
	 * @return boolean true if we can render something for the required output format
	 */
	function Render( & $params )
	{
		if( ! parent::render( $params ) )
		{	// We cannot render the required format
			return false;
		}

		if( ! isset( $this->search ) )
		{	// We haven't prepared the smilies yet
			$this->search = array();


			$tmpsmilies = $this->smilies;
			uksort($tmpsmilies, 'smiliescmp');

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


		// REPLACE:  But not in code blocks.

		$content = & $params['data'];

		if( strpos( $content , '<code>' ) !== false )
		{ // If there are code tags run this substitution
			$content_parts = preg_split("/<\/?code>/", $content);
			$content = '';
			for ( $x = 0 ; $x < count( $content_parts ) ; $x++ )
			{
				if ( ( $x % 2 ) == 0 )
				{ // If x is even then it's not code and replace any smiles
					$content .= $this->ReplaceTagSafe($content_parts[$x]);
				}
				else
				{ // If x is odd don't replace smiles. and put code tags back in.
					$content .= '<code>' . $content_parts[$x] . '</code>';
				}
			}
		}
		else
		{ // No code blocks, replace on the whole thing
			$content = $this->ReplaceTagSafe($content);
		}

		return true;
	}

	/**
	 * This callback gets called once after every tags+text chunk
	 */
	function preg_insert_smilies_callback($s)
	{
		return  $s[1] // Unmodified tags
						.str_replace( $this->search, $this->replace, $s[3]); // Text with replaced smilies
	}

	function ReplaceTagSafe($text)
	{
		// The pattern catches as many optional tags as possible, then catches as much text as possible without hitting a new tag
		$search = "/((<[^>]+>)*)([^<]*)/si";

		return preg_replace_callback($search, array($this, 'preg_insert_smilies_callback'), $text);
	}
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
?>
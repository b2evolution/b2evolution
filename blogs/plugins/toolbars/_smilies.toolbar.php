<?php
/**
 * This file implements the Image Smilies Toolbar plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
require_once dirname(__FILE__).'/../toolbar.class.php';

class smilies_Toolbarplugin extends ToolbarPlugin
{
	var $code = 'b2evSmil';
	var $name = 'Smilies';
	var $priority = 70;
	var $short_desc = 'One click smilies inserting';
	var $long_desc = 'No description available';

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
	 * {@internal smilies_Toolbarplugin::smilies_Toolbarplugin(-)}} 
	 */
	function smilies_Toolbarplugin()
	{
		require dirname(__FILE__). '/../_smilies.conf.php';
	}


	/**
	 * Display the toolbar
	 *
	 * {@internal smilies_Toolbarplugin::render(-)}} 
	 */
	function display()
	{	
		$grins = '';
		$smiled = array();
		foreach( $this->smilies as $smiley => $grin )
		{
			if (!in_array($grin, $smiled))
			{
				$smiled[] = $grin;
				$smiley = str_replace(' ', '', $smiley);
				$grins .= '<img src="'. $this->smilies_path. '/'. $grin. '" alt="'. $smiley.
									'" onclick="grin(\''. str_replace("'","\'",$smiley). '\');"/> ';
			}
		}
	
		print('<div>'. $grins. '</div>');
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
	}
}

// Register the plugin:
$this->register( new smilies_Toolbarplugin() );

?>
<?php
/**
 * This file implements the Image Smilies plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */


/*
 * convert_smilies(-)
 */
function convert_smilies( & $content)
{
	global $smilies_directory;
	global $b2smilies, $b2_smiliessearch, $b2_smiliesreplace;

	if( ! isset( $b2_smiliessearch ) )
	{	// We haven't prepared the smilies yet
		$b2_smiliessearch = array();
		$tmpsmilies = $b2smilies;
		uksort($tmpsmilies, 'smiliescmp');

		foreach($tmpsmilies as $smiley => $img)
		{
			$b2_smiliessearch[] = $smiley;
			$smiley_masked = '';
			for ($i = 0; $i < strlen($smiley); $i++ )
			{
				$smiley_masked .=  '&#'.ord(substr($smiley, $i, 1)).';';
			}

			$b2_smiliesreplace[] = "<img src='$smilies_directory/$img' border='0' alt='$smiley_masked' class='middle' />";
		}
	}

	// REPLACE:
	$content = str_replace($b2_smiliessearch, $b2_smiliesreplace, $content);
}


function b2evo_grins() 
{
	global $smilies_directory, $b2smilies;
	$grins = '';
	$smiled = array();
	foreach ($b2smilies as $smiley => $grin)
	{
		if (!in_array($grin, $smiled))
		{
			$smiled[] = $grin;
			$smiley = str_replace(' ', '', $smiley);
			$grins .= '<img src="'. $smilies_directory. '/'. $grin. '" alt="'. $smiley.
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


?>

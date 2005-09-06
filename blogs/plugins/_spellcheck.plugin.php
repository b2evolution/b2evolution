<?php
/**
 * This file implements the Test plugin for b2evolution
 *
 * This plugin responds to virtually all possible plugin events :P
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
class spellcheck_plugin extends Plugin
{
	var $code = 'cafeSpell';
	var $name = 'Spellchecker';
	var $priority = 10;
	var $apply_when = 'never';

	var $useSpellcheckOnThisPage = false; // So far we have not requested it on this page.

	/**
	 * Constructor
	 *
	 * {@internal spellcheck_plugin::spellcheck_plugin(-)}}
	 */
	function spellcheck_plugin()
	{
		$this->short_desc = T_('Simple Spellchecker (English only)');
		$this->long_desc = T_('This plugins calls a simple online spellchecker.');
	}


	/**
	 * Called when ending the admin html head section
	 *
	 * {@internal Plugin::AdminEndHtmlHead(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		global $AdminUI, $admin_url;

		if( $AdminUI->getPath(1) != 'new' )
		{ // We won't need the spellchecker
			return false;
		}

		?>
		<script type="text/javascript" language="javascript">
			<!--
			function DoSpell(formname, subject, body)
			{
				document.SPELLDATA.formname.value=formname
				document.SPELLDATA.subjectname.value=subject
				document.SPELLDATA.messagebodyname.value=body
				document.SPELLDATA.companyID.value="custom\\http://cafelog.com"
				document.SPELLDATA.language.value=1033
				document.SPELLDATA.opener.value="<?php echo $admin_url ?>sproxy.php"
				document.SPELLDATA.formaction.value="http://www.spellchecker.com/spell/startspelling.asp "
				window.open("<?php echo $admin_url ?>b2spell.php","Spell","toolbar=no,directories=no,location=yes,resizable=yes,width=620,height=400,top=100,left=100")
			}
			// End -->
		</script>
		<?php

		return true;
	}


	/**
	 * Called right after displaying the admin page footer
	 *
	 * {@internal Plugin::AdminAfterPageFooter(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminAfterPageFooter( & $params )
	{
		if( ! $this->useSpellcheckOnThisPage )
		{ // no spellcheck on this page, no need for this...
			return false;
		}
		?>
		<!-- this is for the spellchecker -->
		<form action="" name="SPELLDATA"><div>
		<input name="formname" type="hidden" value="" />
		<input name="messagebodyname" type="hidden" value="" />
		<input name="subjectname" type="hidden" value="" />
		<input name="companyID" type="hidden" value="" />
		<input name="language" type="hidden" value="" />
		<input name="opener" type="hidden" value="" />
		<input name="formaction" type="hidden" value="" />
		</div></form>
		<?php
		return true;
	}


	/**
	 * Display an editor button
	 *
	 * {@internal Plugin::DisplayEditorButton(-)}}
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEditorButton( & $params )
	{
		// This means we are using the spellchecker on this page!
		$this->useSpellcheckOnThisPage = true;
		?>
		<input type="button" value="<?php echo T_('Spellcheck') ?>" onclick="DoSpell('post','content','');" />
		<?php
		return true;
	}

}
?>
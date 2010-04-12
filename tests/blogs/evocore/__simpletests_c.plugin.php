<?php
/**
 * Test plugin used e.g. in test_get_registered_events
 */
class simpletests_c_plugin extends Plugin
{
	var $name = 'Simpletests C plugin';
	var $code = '';
	var $priority = 50;
	var $version = '1.0';
	var $author = 'b2evolution developer team';
	var $help_url = '';
	var $short_desc = 'Simpletests C plugin';

	function YetAnotherCustomMethod()
	{
		echo 'Foobar';
	}

	function PluginInit( & $params )
	{
		return true;
	}

	function AdminBeginPayload()
	{
		$this->YetAnotherCustomMethod();
	}

	function BeforeUninstall( & $params )
	{
		return true;
	}

	function AdminEndHtmlHead( & $params )
	{
		echo '<script type="text/javascript>';
		echo 'function AdminAfterPageFooter( arg_one ) { }';
		echo '</script>';
		return true;
	}

	function MyOwnFunction( & $params )
	{
		$this->AdminEndHtmlHead( $params );
	}
}

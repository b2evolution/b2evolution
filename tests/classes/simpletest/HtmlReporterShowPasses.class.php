<?php
/**
 * We extend the HtmlReporter class.
 *
 */

require_once( SIMPLETEST_DIR.'/reporter.php' );

/**
 *
 */
class HtmlReporterShowPasses extends HtmlReporter
{
	function HtmlReporterShowPasses()
	{
		$this->HtmlReporter();
	}

	function paintPass($message) {
		parent::paintPass($message);

		print "<span class=\"pass\">Pass</span>: ";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		print implode(" -&gt; ", $breadcrumb);
		print " -&gt; $message<br />\n";
	}

	function _getCss() {
		return parent::_getCss() . ' .pass { color: green; }';
	}

}
?>

<?php
/**
 * This file implements the extended HtmlReporter class,
 * which shows passes.
 */

/**
 * The original reporter.
 */
require_once( SIMPLETEST_DIR.'reporter.php' );

/**
 * The extended HtmlReporter class, which shows passes.
 */
class HtmlReporterShowPasses extends HtmlReporter
{
	function HtmlReporterShowPasses()
	{
		$this->HtmlReporter();
	}

	function paintPass($message)
	{
		parent::paintPass($message);

		print "<span class=\"pass\">Pass</span>: ";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		print implode(" -&gt; ", $breadcrumb);
		print ' -&gt; '.htmlspecialchars($message)."<br />\n";
	}

	function _getCss() {
		return parent::_getCss()
						." .pass { color: green; } \n"
						." .fail { font-weight:bold; font-size:1.2em; }.\n";
	}

}
?>

<?php
/**
 * This file implements the RendererPlugin class (EXPERIMENTAL)
 *
 * This is the base class from which you should derive all rendering plugins.
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
require_once dirname(__FILE__).'/renderer.class.php';

/**
 * RendererPlugin Class
 */
class RendererPlugin extends Plugin
{
	/**
	 * Possible values:
	 * - 'stealth'
	 * - 'always'
	 * - 'opt-out'
	 * - 'opt-in'
	 * - 'lazy'
	 * - 'never'
	 */
	var $apply = 'opt-out';

	/** 
	 * Does plugin apply?
	 *
	 * {@internal Plugin::applies(-) }}
	 */
	function applies() 
	{
		return (($this->apply == 'stealth') || ($this->apply == 'always') || ($this->apply == 'opt-out' ));
	}

	/** 
	 * Can plugin apply?
	 *
	 * {@internal Plugin::can_apply(-) }}
	 */
	function can_apply() 
	{
		return ( ! ($this->apply == 'never') );
	}

	/** 
	 * Is plugin optional?
	 *
	 * {@internal Plugin::is_optional(-) }}
	 */
	function is_optional() 
	{
		return (($this->apply == 'opt-in') || ($this->apply == 'opt-out' ));
	}
}
?>
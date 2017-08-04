/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * This file is used to work with debug info of AJAX response
 */


/**
 * Clear ajax request from debug text
 *
 * @param string AJAX Response text
 * @return string AJAX Response Text without debug text
 */
function ajax_debug_clear( result )
{
	// Delete a verifying text from result
	var check_exp = /<!-- Ajax response end -->/;
	result = result.replace( check_exp, '' );

	// Delete debug info
	result = result.replace( /(<div class="jslog">[\s\S]*)/i, '' );

	return jQuery.trim( result );
}

/**
 * Check ajax response data for correct format
 *
 * @param string AJAX Response text
 * @return boolean TRUE if response data has a correct format
 */
function ajax_response_is_correct( result )
{
	var check_exp = /<!-- Ajax response end -->/;
	var is_correct = result.match( check_exp );

	if( !is_correct )
	{	// Response data is incorrect
		return false;
	}

	// Delete a debug data from result
	result = ajax_debug_clear( result );
	
	// TRUE if result is not empty
	return result != '';
}
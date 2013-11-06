<?php
/**
 * This file implements the Hit and Session pruning Cron controller
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	keyphrase_job();
	return 1; /* ok */

?>
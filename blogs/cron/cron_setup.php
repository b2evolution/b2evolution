<?php

require_once '../conf/_config.php';

// This MIGHT be overkill. TODO: check...
require_once $inc_path .'_main.inc.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Cron setup</title>
	<link rel="stylesheet" type="text/css" href="<?php echo $rsc_url ?>css/basic.css">
</head>
<body>
	<h1>Cron setup</h1>
	<p>This script will set up a few sample cron tasks in the cron queue.</p>
	<?php

 	$start_time = $localtimenow;

	// TEST: insert a task to run:
	$start_time += 5;
	$job_params = array(
			'message' => 'hello',
		);
	$sql = 'INSERT INTO T_cron__task( ctsk_start_datetime, ctsk_repeat_after, ctsk_name, ctsk_controller, ctsk_params )
					VALUES( '.$DB->quote(date2mysql($start_time)).', NULL, "Test", "cron/_test.job.php", '
									.$DB->quote(serialize($job_params)).' )';
	$DB->query( $sql, 'Insert test task' );



	debug_info();

	?>
</body>
</html>
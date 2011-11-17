<?php

	if (PHP_SAPI != 'cli') {
		echo 'This is a console application. Please execute it through the CLI SAPI.';
		exit(1);
	}

	set_include_path(dirname(__FILE__) . '/../src');
	
	require_once 'DnsUpdater.php';
	
	DnsUpdater::main(array_slice($argv, 1));
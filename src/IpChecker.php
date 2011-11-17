<?php

	interface IpChecker
	{
		/**
		 * determines the public IP address of a machine
		 * 
		 * @return string the IP address
		 */
		function resolvePublicIp();
	}
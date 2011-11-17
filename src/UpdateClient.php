<?php

	interface UpdateClient
	{
		/**
		 * udpates the DNS records with the
		 * given public IP address
		 *
		 * @param string $publicIp the IP address
		 * @throws UpdateFailedException on error
		 */
		function updateDnsRecords($publicIp);
	}
<?php

	require_once 'HttpClient.php';
	require_once 'IpChecker.php';
	
	class DynDnsIpChecker implements IpChecker
	{
		const DYNDNS_CHECKIP_URL = 'http://checkip.dyndns.org';
		
		/**
		 * @var HttpClient
		 */
		private static $httpClient;
		
		function __construct() 
		{
			if (self::$httpClient == null) {
				self::$httpClient = new HttpClient();
			}
		}
		
		function resolvePublicIp()
		{
			self::$httpClient->setUri(self::DYNDNS_CHECKIP_URL);
			
			try {
				$response = self::$httpClient->request();
			}
			catch (Zend_Http_Client_Adapter_Exception $e) {
				throw new ServiceUnavailableException($e->getMessage());
			}
			
			if (!$response->isSuccessful()) {
				require_once 'ServiceUnavailableException.php';
				throw new ServiceUnavailableException();
			}
			else if (self::$httpClient->getRedirectionsCount() > 0) {
				if (($host = $response->getHeader('Host')) != 'checkip.dyndns.org') {
					throw new ServiceUnavailableException(
						"Unexpected redirect to different host \"$host\", " . 
						'maybe your connection is down'
					);
				}
			}
			$body = $response->getBody();
			
			//just use preg_match now, we can improve later if needed
			if (!preg_match('#(([\d]{1,3}\.?){4})#', $body, $match)) {
				require_once 'UnexpectedResponseFormatException.php';
				throw new UnexpectedResponseFormatException();
			}
			
			//test if the string is a valid IP address
			if (false == ip2long($match[1])) {
				require_once 'UnexpectedResponseFormatException.php';
				throw new UnexpectedResponseFormatException();
			}
			
			return $match[1];
		}
	}
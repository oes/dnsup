<?php

	require_once 'Zend/Http/Client.php';

	class HttpClient extends Zend_Http_Client
	{
		private $maxRetries;
		
		function __construct($maxRetries = 3)
		{
			$this->setMaxRetries($maxRetries);
		}
		
		function setMaxRetries($maxRetries)
		{
			$this->maxRetries = $maxRetries;
		}
		
		function request($method = null)
		{
			$retries = 0;
			
			// we're either returning immediately after
			// parent::request returns or we retry to call
			// that method as many times as specified, otherwise
			// we throw an exception so there is really no 
			// need for a loop condition
			while (true) 
			{
				try {
					return parent::request($method);
				}
				catch (Zend_Http_Client_Exception $e) {
					// if it's not that stupid exception rethrow it
					if ($e->getMessage() != 'Unable to read response, or response is empty') {
						throw $e;
					}
					//otherwise retry as many times as specified, then rethrow it
					if (++$retries > $this->maxRetries) {
						throw $e;	
					}	
				}
			}
		}
	}
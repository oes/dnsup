<?php

	require_once 'HttpClient.php';
	require_once 'UpdateClient.php';
	require_once 'Config.php';
	
	class SilUpdateClient implements UpdateClient
	{
		const SIL_DOMAIN_ADMIN_URI = 'https://admin.sil.at/admin/domainadmin/domainadmin.php';
		
		/**
		 * @var HttpClient
		 */
		private $httpClient;
		
		/**
		 * @var Config
		 */
		private $conf;
		
		function __construct()
		{
			$this->httpClient = new HttpClient();
			$conf = Config::forClass(__CLASS__);
			if ($conf == null) {
				throw new RuntimeException('configuration is required for ' . __CLASS__);	
			}
			$this->conf = $conf;
		}
		
		function updateDnsRecords($publicIp)
		{
			$this->httpClient->setUri(self::SIL_DOMAIN_ADMIN_URI);
			
			$customer = $this->conf->get('customer');
			$domain = $this->conf->get('domain');
			$password = $this->conf->get('password');
			$ttl = $this->conf->get('ttl', '600');
			$names = $this->conf->get('names');
			
			$this->httpClient->setAuth($customer, $password);
			
			if ($customer == null) {
				throw new RuntimeException('missing required configuration setting "customer"');
			}
			if ($domain == null) {
				throw new RuntimeException('missing required configuration setting "domain"');
			}
			if ($password == null) {
				throw new RuntimeException('missing required configuration setting "password"');
			}
			
			if (is_array($names)) {
				foreach ($names as $name) {
					$this->updateRecord($publicIp, $name, $domain, $customer, $ttl);
				}
			}
			
			$this->updateRecord($publicIp, '', $domain, $customer, $ttl);
		}
		
		private function updateRecord($publicIp, $name, $domain, $customer, $ttl, $type = 'A') {
			$this->httpClient
				->setParameterPost('name', $name)
				->setParameterPost('ttl', $ttl)
				->setParameterPost('type', $type)
				->setParameterPost('mode', 'ersetzen')
				->setParameterPost('parameter', $publicIp)
				->setParameterPost('customer_cn', $customer)
				->setParameterPost('domain', $domain)
				->setParameterPost('submitbutton', 'durchfuehren');
			
			$response = $this->httpClient->request(HttpClient::POST);
			
			$expect = preg_quote(empty($name) ? $domain : "$name.$domain", '#');
			$expectPattern = "#Changed $expect IN A#";
			if (!$response->isSuccessful()) {
				require_once 'UpdateFailedException.php';
				throw new UpdateFailedException(
					"http request failed with status code \"{$response->getStatus()}\""
				);
			}
			else if(!preg_match($expectPattern, $response->getBody())) {
				require_once 'UpdateFailedException.php';
				throw new UpdateFailedException(
					"Expected message pattern \"$expectPattern\" not found"
				);
			}
		}
	}

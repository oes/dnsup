<?php

	class DnsUpdater
	{
		const LOG_NONE 		= PHP_INT_MIN;
		const LOG_ALL		= PHP_INT_MAX;
		
		const LOG_FINE		= 1;
		const LOG_INFO 		= 2;
		const LOG_WARN 		= 3;
		const LOG_SEVERE 	= 4;
		
		
		static $logLevels = array(
			self::LOG_FINE		=> 'FINE',
			self::LOG_INFO 		=> 'INFO',
			self::LOG_WARN 		=> 'WARN',
			self::LOG_SEVERE 	=> 'SEVERE',
		);
		
		static private $homeDir;
		static private $lastIpFile;
		static private $logFile;
		static private $logLevel;
		
		static function main(array $args)
		{
			if (count($args) == 0) {
				self::printUsage();
				exit(0);	
			}

			if (false !== ($logLevelOpt = array_search('-log', $args))) {
				if ($logLevelOpt + 1 >= count($args)) {
					throw new RuntimeException('Missing value for "-log" argument');
				}
				$levelStr = strtoupper($args[$logLevelOpt + 1]);
				$level = array_search($levelStr, self::$logLevels);
				if (false === $level) {
					throw new RuntimeException("log level \"$levelStr\" doesn't exist");
				}
				self::$logLevel = $level;
			}
			
			$userHome = getenv('HOME');
			if (!$userHome) {
				echo 'Couldn\'t find your home directory, do ' . 
					'you want to enter it manually? (Y/n): ';
				$enterManually = trim(fgets(STDIN));
				if ($enterManually == '' || strtolower($enterManually) == 'y') {
					echo 'Enter your home directory: ';
					$userHome = trim(fgets(STDIN));
				}
			}
			if (!is_dir($userHome)) {
				throw new Exception("home directory \"$userHome\" doesn't exist");	
			}
			
			self::$homeDir = $userHome . '/.dnsUpdater';
			self::$lastIpFile = self::$homeDir . '/lastIp';
			self::$logFile = self::$homeDir . '/log';
			
			if (!is_dir(self::$homeDir)) {
				if (!mkdir(self::$homeDir)) {
					throw new Exception('failed to create ".dnsUpdater" directory ' . 
						'in your home directory; please check permissions or create it manually');
				}
				//make sure the file exists
				touch(self::$lastIpFile);
			}
			
			self::launch($args);
		}
		
		static function launch(array $args)
		{			
			$class = new ReflectionClass('DnsUpdater');
			
			if (!$class->hasMethod($args[0])) {
				require_once 'UsageException.php';
				throw new UsageException("undefined command \"{$args[0]}\"");
			}
			
			$class->getMethod($args[0])->invoke(new self());
		}
		
		static private function printUsage()
		{
			echo 'Usage: dnsup [ check | update ]', PHP_EOL;
		}

		static private function log($m, $level = self::LOG_INFO)
		{
			if ($level >= self::$logLevel) {
				$label =  self::$logLevels[$level];
				if ($m instanceof Exception) {
					self::logException($m, $label);
				}
				else {
					self::logMessage($m, $label);
				}
			}
		}
		
		static private function logMessage($message, $label)
		{
			file_put_contents(
				self::$logFile, 
				sprintf('[%s] %s: %s', $label, date('c'), $message) . PHP_EOL,
				FILE_APPEND
			);
		}
		
		static private function logException(Exception $e, $label)
		{
			$message = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
			self::logMessage($message, $label);
		}
		
		function update()
		{
			$lastIp = file_get_contents(self::$lastIpFile);
			try {
				self::log('checking IP address', self::LOG_FINE);
				require_once 'DynDnsIpChecker.php';
				$ipChecker = new DynDnsIpChecker();
				$ip = $ipChecker->resolvePublicIp();			
				self::log('current public IP address is ' . $ip, self::LOG_FINE);
				if ($lastIp == $ip) {
					self::log('IP address hasn\'t changed, skipping update', self::LOG_FINE);
				}
				else {
					self::log("updating DNS records from \"$lastIp\" to \"$ip\"", self::LOG_INFO);
					require_once 'SilUpdateClient.php';
					$sil = new SilUpdateClient();
					$sil->updateDnsRecords($ip);
					self::log('update successful', self::LOG_INFO);
					file_put_contents(self::$lastIpFile, $ip);
				}
			}
			catch (Exception $e) {
				self::log($e, self::LOG_WARN);
			}
		}
		
		function check()
		{
			require_once 'DynDnsIpChecker.php';
			$ipChecker = new DynDnsIpChecker();
			$ip = $ipChecker->resolvePublicIp();
			echo 'Your public IP address is ', $ip, PHP_EOL;
		}
	}
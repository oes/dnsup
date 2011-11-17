<?php

	class Config
	{
		private $settings;
		
		static function forClass($class) {
			$file = dirname(__FILE__) . "/../config/$class.php";
			if (!file_exists($file)) {
				return null;
			}
			$settings = require $file;
			return new self($settings);
		}
		
		function __construct(array $settings) {
			$this->settings = $settings;
		}
		
		function get($key, $default = null, $type = null) {
			if (!array_key_exists($key, $this->settings)) {
				return $default;
			}
			$value = $this->settings[$key];
			if ($type != null) {
				settype($value, $type);
			}
			return $value;
		}
	}
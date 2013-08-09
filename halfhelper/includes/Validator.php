<?php

class Validator {
	public static $reason = '';
	
	private function __construct() {}
	
	/**
	 * @param string of an ISBN
	 * @return false if its not 13
	 */
	public static function isbns($isbn) {
		self::$reason = '';
		if (strlen($isbn) != 13) {
			self::$reason = "ISBN's must be 13 digits";
			return false;
		}
			return true;
	}
	
	/**
	 * @param string $str
	 * @return bool
	 */
	public static function password($str) {
		self::$reason = '';
		
		if (strlen($str) < 5) {
			self::$reason = 'Password must be at least 5 characters long.';
			return false;
		}
		elseif (!preg_match('/[0-9]+/', $str)) {
			self::$reason = 'Password must contain at least one number.';
			return false;
		}
		return true;
	}
	
	/**
	 * @param string $str
	 * @return bool
	 */
	public static function email($str) {
		self::$reason = '';
		
		$at_index = strrpos($str, "@");
		if (is_bool($at_index) && !$at_index) {
			return false;
		}
		else {
			$domain = substr($str, $at_index+1);
			$local = substr($str, 0, $at_index);
			$local_len = strlen($local);
			$domain_len = strlen($domain);
			if ($local_len < 1 || $local_len > 64) {
				// local part length exceeded
				return false;
			}
			else if ($domain_len < 1 || $domain_len > 255) {
				// domain part length exceeded
				return false;
			}
			else if ($local[0] == '.' || $local[$local_len-1] == '.') {
				// local part starts or ends with '.'
				return false;
			}
			else if (preg_match('/\\.\\./', $local)) {
				// local part has two consecutive dots
				return false;
			}
			else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
				// character not valid in domain part
				return false;
			}
			else if (preg_match('/\\.\\./', $domain)) {
				// domain part has two consecutive dots
				return false;
			}
			else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
				// character not valid in local part unless
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
					return false;
				}
			}
		}
		return true;
	}
}

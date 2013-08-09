<?php
set_error_handler('errorHandler');
set_exception_handler('exceptionHandler');
error_reporting(E_ALL);

set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__)));

session_start();
include 'includes/config.php';
include 'includes/template.php';
include 'includes/Validator.php';
include 'includes/Finder.php';
include 'includes/connect.php';

/**
	 * Handles PHP errors
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 */
	function errorHandler($errno, $errstr, $errfile, $errline) {
		$errdesc = '';
		switch ($errno) {
			case E_WARNING:
				return false;
			case E_USER_ERROR:
				$errdesc = 'E_USER_ERROR';
				break;
			case E_USER_WARNING:
				$errdesc = 'E_USER_WARNING';
				break;
			case E_USER_NOTICE:
				$errdesc = 'E_USER_NOTICE';
				break;
		}
		$arr = array(
			'errdesc' => $errdesc,
			'errno' => $errno,
			'errstr' => $errstr,
			'errfile' => $errfile,
			'errline' => $errline
		);
		if (!headers_sent()) { header('HTTP/1.1 500 Internal Server Error'); }
		$html = wrap('Halfhelper', '', '', print_r($arr, true));
		print $html;
		exit();
	}
	
	/**
	 * Handles un-caught exceptions
	 * @param Exception object $exception
	 */
	function exceptionHandler($exception) {
		if (!headers_sent()) { header('HTTP/1.1 500 Internal Server Error'); }
		$html = wrap('Halfhelper', '', '', print_r($exception, true));
		print $html;
		exit();
	}
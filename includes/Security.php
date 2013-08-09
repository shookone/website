<?php

class Security {
	const SESSKEY = 'security';
	
	private function __construct() {}
	
	/**
	 * Remove the 'www' subdomain
	 */
	public static function removeWww() {
		if (isset($_SERVER['HTTP_HOST']) && substr($_SERVER['HTTP_HOST'], 0, 4) == 'www.') {
			header("Location: " . get_proto() . BASE_URL_NO_PROTO . REL_PATH . basename($_SERVER['REQUEST_URI']), true, 301); // HTTP 301 (permanent)
			exit();
		}
	}
	
	/**
	 * Force using of HTTPS protocol
	 */
	public static function useHttps() {
		if (APP_MODE != 'development') {
			if (get_proto() == 'http') {
				header('Location: https' . BASE_URL_NO_PROTO . REL_PATH . basename($_SERVER['REQUEST_URI']));
				exit();
			}
		}
	}
	
	/**
	 * Remove the HTTPS protocol
	 */
	public static function removeHttps() {
		if (get_proto() == 'https') {
			header('Location: http' . BASE_URL_NO_PROTO . REL_PATH . basename($_SERVER['REQUEST_URI']));
			exit();
		}
	}
	
	/**
	 */
	public static function getLoginRedir() {
		if (self::getReferer()) {
			return self::getReferer();
		}
		elseif (isset($_GET['r']) && strlen($_GET['r'])) {
			return get_proto() . BASE_URL_NO_PROTO . $_GET['r'];
		}
		return get_proto() . BASE_URL_NO_PROTO . 'account/index.php';
	}
	
	/**
	 */
	public static function loginRedir() {
		$redir = self::getLoginRedir();
		self::clearReferer();
		header('Location: ' . $redir);
		exit();
	}
	
	/**
	 * Returns a randomly generated token
	 * @return string
	 */
	public static function token() {
		return sha1(uniqid(mt_rand(), true));
	}
	
	/**
	 * Returns the hash of the given string
	 * @param string $str
	 * @return string
	 */
	public static function hash($str) {
		$str = sha1(md5($str));
		return $str;
	}
	
	/**
	 * Encrypts the password with the given salt
	 * @param string $pwd - plaintext password
	 * @param string $salt - optional salt
	 * @return string - The encrypted password
	 */
	public static function encryptPassword($pwd, $salt = '') {
		return self::hash($salt . $pwd);
	}
	
	/**
	 * Requires the user to be logged in
	 * @param string $notice custom message to display on the login page
	 */
	public static function requireLogin($notice = false) {
		if (!self::isLoggedIn()) {
			$notice = ($notice ? $notice : 'You must be logged in to view that page.');
			Alerts::add($notice, 'notice', 'Notice!');
			self::setReferer(get_proto() . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			header('Location: ' . get_proto() . BASE_URL_NO_PROTO . 'account/login.php');
			exit();
		}
	}
	
	/**
	 * Requires the user to be logged in and be an admin
	 */
	public static function requireAdmin() {
		self::requireLogin();
		if (!self::isAdmin()) {
			Alerts::add('You do not have access to that page.', 'notice', 'Notice!');
			header('Location: ' . get_proto() . BASE_URL_NO_PROTO . 'account/index.php');
			exit();
		}
	}
	
	/**
	 * Requires the user to be logged in and be a portal manager
	 */
	public static function requirePortalManager() {
		self::requireLogin();
		if (!self::isPortalManager()) {
			Alerts::add('You do not have access to that page.', 'notice', 'Notice!');
			header('Location: ' . get_proto() . BASE_URL_NO_PROTO . 'account/index.php');
			exit();
		}
	}
	
	/**
	 * @param string $url
	 */
	public static function setReferer($url) {
		$_SESSION[self::SESSKEY]['referer'] = $url;
	}
	
	/**
	 * @return string on success, false when no referer exists
	 */
	public static function getReferer() {
		if (!isset($_SESSION[self::SESSKEY]['referer'])) {
			return false;
		}
		return $_SESSION[self::SESSKEY]['referer'];
	}
	
	/**
	 */
	public static function clearReferer() {
		if (isset($_SESSION[self::SESSKEY]['referer'])) {
			unset($_SESSION[self::SESSKEY]['referer']);
		}
	}
	
	/**
	 * Determines whether a user is logged in or not.
	 * @return bool - True if the user is logged in, false otherwise
	 */
	public static function isLoggedIn() {
		return (
			isset($_SESSION[self::SESSKEY]['logged_in'])
			&& $_SESSION[self::SESSKEY]['logged_in']
		);
	}
	
	/**
	 * Returns the currently logged in user ID or the emulated user ID if the
	 * logged in user is an admin
	 * @param Database object $db
	 * @return int False when not logged in
	 */
	public static function getUserId($db) {
		if (!self::isLoggedIn()) {
			return false;
		}
		if (self::isAdmin()) {
			if (isset($_GET['uid']) && SimpleUser::existsById($_GET['uid'], $db)) {
				return $_GET['uid'];
			}
		}
		return $_SESSION[self::SESSKEY]['user_id'];
	}
	
	/**
	 * @return bool
	 */
	public static function isAdmin() {
		if (!self::isLoggedIn()) {
			return false;
		}
		return isset($_SESSION[self::SESSKEY]['is_admin']) && $_SESSION[self::SESSKEY]['is_admin'];
	}
	
	/**
	 * @return bool
	 */
	public static function isPortalManager() {
		if (!self::isLoggedIn()) {
			return false;
		}
		return isset($_SESSION[self::SESSKEY]['portal_id']) && $_SESSION[self::SESSKEY]['portal_id'];
	}
	
	/**
	 * @param int $user_id
	 * @param Database object $db
	 * @return bool
	 */
	public static function isUserIdActive($user_id, $db) {
		try {
			$simple_user = SimpleUser::withId($user_id, $db);
		}
		catch (NotFoundException $e) { return false; }
		
		if (!$simple_user->is_active) { return false; }
		return true;
	}
	
	/**
	 * @param string $username
	 * @param string $password
	 * @param Database object $db
	 * @return int False on failure, user ID on success
	 */
	public static function checkLogin($username, $password, $db) {
		try {
			$user_id = SimpleUser::getIdByUsername($username, $db);
		}
		catch (NotFoundException $e) { return false; }
		
		if (!$user_id) {
			return false;
		}
		
		$simple_user = new SimpleUser($user_id, $db);
		
		$enc_password = self::encryptPassword($password, $simple_user->salt);
		if ($enc_password != $simple_user->password) {
			return false;
		}
		
		return $user_id;
	}
	
	/**
	 * @param int $user_id
	 * @param Database object $db
	 * @return bool
	 */
	public static function loginByUserId($user_id, $db) {
		if (!SimpleUser::existsById($user_id, $db)) {
			return false;
		}
		
		$simple_user = new SimpleUser($user_id, $db);
		
		$_SESSION[self::SESSKEY]['logged_in'] = true;
		$_SESSION[self::SESSKEY]['user_id'] = $user_id;
		$_SESSION[self::SESSKEY]['is_admin'] = false;
		$_SESSION[self::SESSKEY]['portal_id'] = false;
		$_SESSION[self::SESSKEY]['emulator_admin_id'] = false;
		if ($simple_user->is_admin) {
			$_SESSION[self::SESSKEY]['is_admin'] = true;
		}
		if ($simple_user->portal_id) {
			$_SESSION[self::SESSKEY]['portal_id'] = $simple_user->portal_id;
		}
		return true;
	}
	
	/**
	 * @param int $id
	 */
	public static function setEmulatorId($id) {
		$_SESSION[self::SESSKEY]['emulator_admin_id'] = $id;
	}
	
	/**
	 * @return int
	 */
	public static function getEmulatorId() {
		if (isset($_SESSION[self::SESSKEY]['emulator_admin_id'])) {
			return $_SESSION[self::SESSKEY]['emulator_admin_id'];
		}
	}
	
	/**
	 */
	public static function logout() {
		session_regenerate_id(true);
		$_SESSION = array();
		setcookie('SID');
		setcookie('fingerprint');
	}
	
	/**
	 * Creates a password reset token and saves it to the database, then sends
	 * an email to the user with a link to reset their password with that token
	 * @param int $user_id
	 * @param Database object $db
	 * @return bool
	 */
	public static function sendPasswordReset($user_id, $db) {
		$token = self::token();
		
		$simple_user = new SimpleUser($user_id, $db);
		$simple_user->saveCol('pwd_reset_key', $token);
		
		$url = get_proto() . BASE_URL_NO_PROTO . 'account/password-reset-form.php?k=' . $token;
		
		ob_start();
		?>
<p>You seem to have forgotten your password! No problem, follow the link below to reset your password:</p>
<p><a href="<?php echo $url; ?>"><?php echo $url; ?></a></p>
<p>If you didn't request to have your password reset, just ignore this e-mail and your password will remain the same.</p>
<p>Thanks!</p>
		<?php
		$message = ob_get_clean();
		
		$from = 'password-reset@' . EMAIL_DOMAIN;
		$subject = 'Password Reset';
		
		$headers = "From: {$from}\r\n";
		$headers .= "Reply-To: {$from}\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
		$headers .= "X-Mailer: PHP v" . phpversion() . "\r\n";
		
		mail($simple_user->email, $subject, $message, $headers);
		return true;
	}
}

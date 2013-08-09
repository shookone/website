<?php

class Finder{
	private function __construct() {}
	
	public static $message = '';
	
	/*
	 * Takes an email and returns array('id', 'pwd', 'salt') on success
	 * and false on failure
	*/
	public static function getUserByEmail($email) {
		$sql = "
			SELECT *
			FROM `users`
			WHERE `email` LIKE '" . mysql_real_escape_string($email) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		if (mysql_num_rows($result) == 0) {
			self::$message = 'User Name does not exist';
			return false;
		}
		if (mysql_num_rows($result) > 1) {
			self::$message = 'More than one email';
			return false;
		}
		return mysql_fetch_assoc($result);
	}
	
	/*
	 * Takes a key and returns array('id', 'pwd', 'salt') on success
	 * and false on failure
	*/
	public static function getUserByResetKey($key) {
		$sql = "
			SELECT `id`, `pwd`, `salt`, `email`
			FROM `users`
			WHERE `key` LIKE '" . mysql_real_escape_string($key) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		if (mysql_num_rows($result) == 0) {
			self::$message = 'User Name does not exist';
			return false;
		}
		if (mysql_num_rows($result) > 1) {
			self::$message = 'More than one key';
			return false;
		}
		return mysql_fetch_assoc($result);
	}
	
	/*
	 * Takes an id and returns array('key') on success
	 * and false on failure
	*/
	public static function getKeyById($id) {
		$sql = "
			SELECT `key`
			FROM `users`
			WHERE `id` LIKE '" . mysql_real_escape_string($id) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		if (mysql_num_rows($result) == 0) {
			self::$message = 'User Name does not exist';
			return false;
		}
		if (mysql_num_rows($result) > 1) {
			self::$message = 'More than one key';
			return false;
		}
		return mysql_fetch_assoc($result);
	}
	
	/*
	 * Takes an array of isbns and returns array('isbn', 'title', 'author', 'abbr') on success
	 * and false on failure
	 */
	public static function getBookResults($isbns) {
		$arr = array();
		$sql = "
			SELECT `isbn`, `title`, `author`, `edition`
			FROM `books` 
			WHERE 0";
			foreach ($isbns as $isbn) {
				$sql .= " OR `isbn` LIKE '" . mysql_real_escape_string($isbn) . "'";
				$sql .= " OR `title` LIKE '%" . mysql_real_escape_string($isbn) . "%'";
				$sql .= " OR `author` LIKE '%" . mysql_real_escape_string($isbn) . "%'";
			}
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		while($row = mysql_fetch_assoc($result)) {
			$arr[] = $row;
		}
		return $arr;
	}
	
	/*
	 * Takes an isbn and returns an array('isbn', 'title', 'author', 'edition', `id`)
	 * and false on failure
	 */
	public static function getBookByISBN($isbn) {
		$arr = array();
		$sql = "
			SELECT `isbn`, `title`, `author`, `edition`, `id`
			FROM `books` 
			WHERE `isbn` LIKE '" . mysql_real_escape_string($isbn) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		if (mysql_num_rows($result) == 0) {
			return false;
		}
		if (mysql_num_rows($result) > 1) {
			return false;
		}
		return mysql_fetch_assoc($result);
	}
	
	/*
	 * Returns selected ISBNs
	 * and false on failure
	 */
	public static function getSelectedISBNs($startAt) {
		//40000, 300 corresponds to the number of calls we make in one day to half.com
		$startAt *= MAX_CALLS;
		$arr = array();
		$sql = "
			SELECT `isbn` FROM `books`
			ORDER BY `isbn` ASC
			LIMIT $startAt, 300";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		if (mysql_num_rows($result) == 0) {
			return false;
		}
		while($row = mysql_fetch_row($result)) {
			$arr[] = $row;
		}
		$ar = array();
		foreach ($arr as $inner) {
			foreach ($inner as $isbn) {
				$ar[] = $isbn;
			}
		}
		return $ar;
	}
	
	/*
	 * Returns selected ISBNs
	 * and false on failure
	 */
	public static function getAllISBNs() {
		//40000 corresponds to the number of calls we make in one day to half.com
		$arr = array();
		$sql = "
			SELECT `isbn` FROM `books`";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		if (mysql_num_rows($result) == 0) {
			return false;
		}
		while($row = mysql_fetch_row($result)) {
			$arr[] = $row;
		}
		$ar = array();
		foreach ($arr as $inner) {
			foreach ($inner as $isbn) {
				$ar[] = $isbn;
			}
		}
		return $ar;
	}
	
	/*
	 * Takes an user_id and name and returns an array('id')
	 * and false on failure
	 */
	public static function getListIDByUser($id, $name) {
		$sql = "
			SELECT `id`
			FROM `lists` 
			WHERE `user_id` = '" . mysql_real_escape_string($id) . "'	AND `name` LIKE '" . mysql_real_escape_string($name) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		if (mysql_num_rows($result) == 0) {
			return false;
		}
		
		if (mysql_num_rows($result) > 1) {
			return false;
		}
		return mysql_fetch_assoc($result);
	}
	
	/*
	 * Takes an array of isbns and returns array('isbn', 'title', 'author') on success
	 * and false on failure
	 */
	public static function getBookResultsByISBN($isbn, $booksListID) {
		$sql = "
			SELECT b.*, bl.condition_id
			FROM books b
			LEFT JOIN `books_lists` bl 
			ON bl.id = '" . mysql_real_escape_string($booksListID) . "'
			WHERE `isbn` LIKE '" . mysql_real_escape_string($isbn) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		return mysql_fetch_assoc($result);
	}
	
	/*
	 * Takes ListID and returns All ISBNS and Conditions associated 
	 * with it and false on failure
	 */
	public static function getISBNCondition($listID) {
		$sql = "
		SELECT b.isbn, bl.condition_id 
		FROM `books_lists` bl 
		LEFT JOIN `books` b 
		ON bl.book_id = b.id 
		WHERE list_id = '" . mysql_real_escape_string($listID) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		while($row = mysql_fetch_assoc($result)) {
			$arr[] = $row;
		}
		return $arr;
	}
	
	/*
	 * Takes a user id and returns array of list names
	 * and false on failure
	 */
	public static function getListNameByUser($id) {
		$arr = array();
		$sql = "
			SELECT `name`, `id`  FROM `lists` WHERE `user_id` = '" . mysql_real_escape_string($id) ."'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		while($row = mysql_fetch_assoc($result)) {
			$arr[] = $row;
		}
		return $arr;
	}
	
	/*
	 * Takes a user id and returns array of list names
	 * and false on failure
	 */
	public static function getListByUser($id) {
		$arr = array();
		$sql = "
			SELECT `name`, `created_at`, `id`  FROM `lists` WHERE `user_id` = '" . mysql_real_escape_string($id) ."'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		while($row = mysql_fetch_assoc($result)) {
			$arr[] = $row;
		}
		return $arr;
	}
	
	/*
	 * Takes the name of a list and returns array of isbns on that list
	 * and false on failure
	 */
	public static function getISBNSByListID($id) {
		$arr = array();
		$sql = "
			Select b.isbn, bl.id
			from lists l
			LEFT JOIN books_lists bl
			ON bl.list_id = l.id
			LEFT JOIN books b
			ON b.id = bl.book_id
			WHERE l.id = '" . mysql_real_escape_string($id) ."'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		while($row = mysql_fetch_assoc($result)) {
			$arr[] = $row;
		}
		return $arr;
	}
	
	/*
	 * Takes a list name and returns the number of books it contains
	 * and false on failure
	 *
	*/
	public static function getTotalBookCount($listName) {
		$arr = array();
		$sql = "
			SELECT * FROM `books_lists` bl
			LEFT JOIN lists l
			ON bl.list_id = l.id
			WHERE l.name = '" . mysql_real_escape_string($listName) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		return mysql_num_rows($result);
	}
	
	/*
	 * Takes a list name and book and returns the number of times the book appears on the list
	 * and false on failure
	 *
	*/
	public static function getIndividualBookCount($bookID, $listID) {
		$arr = array();
		$sql = "
			SELECT * FROM `books_lists`
			WHERE `list_id` = '" . mysql_real_escape_string($listID) . "'
			AND `book_id` = '" . mysql_real_escape_string($bookID) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		return mysql_num_rows($result);
	}
	
	/*
	 * Takes a list name and book and returns the number of times the book appears on the list
	 * and false on failure
	 *
	*/
	public static function getListName($listID) {
		$sql = "
			SELECT `name` FROM `lists`
			WHERE `id` = '" . mysql_real_escape_string($listID) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		return mysql_fetch_assoc($result);
	}
	
	/*
	 * Takes a list name and returns true if the name exists
	 * and false on failure
	 *
	*/
	public static function nameExists($listName, $userID) {
		$sql = " SELECT *
				FROM `lists`
				WHERE `user_id` = '" . mysql_real_escape_string($userID) . "'
				AND `name` LIKE '" . mysql_real_escape_string($listName) . "'";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		if(mysql_num_rows($result) != 0) {
			return true;
		}
		return false;
	}
	
	/*
	 * Given an isbn, return all data for prices about it
	*/
	public static function getPriceData($isbn, $conditionID) {
		$arr = array();
		$sql = "
			SELECT a.*, p.feedback, p.price, b.isbn
			FROM api_calls a
			LEFT JOIN price p
			ON p.api_call_id = a.id
			LEFT JOIN books b
			ON b.id = a.book_id
			WHERE pulled_at > CURDATE() - INTERVAL 366 DAY
			AND b.isbn = '" . mysql_real_escape_string($isbn) . "'
			AND a.condition_id = '" . mysql_real_escape_string($conditionID) . "'
			ORDER BY a.pulled_at DESC, p.price ASC";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		while($row = mysql_fetch_assoc($result)) {
			$arr[] = $row;
		}
		return $arr;
	}
	
	/*
	 * Given an isbn, return all data for prices about it
	*/
	public static function getGraphData($bookID, $conditionID) {
		$arr = array();
		$sql = "
			SELECT a.*, p.feedback, p.price, b.isbn
			FROM api_calls a
			LEFT JOIN price p
			ON p.api_call_id = a.id
			LEFT JOIN books b
			ON b.id = a.book_id
			WHERE pulled_at > CURDATE() - INTERVAL 366 DAY
			AND b.id = '" . mysql_real_escape_string($bookID) . "'
			AND a.condition_id = '" . mysql_real_escape_string($conditionID) . "'
			ORDER BY a.id, p.price ASC";
		$result = mysql_query($sql);
		if (!$result) {
			die('Invalid query: ' . mysql_error());
		}
		while($row = mysql_fetch_assoc($result)) {
			$arr[] = $row;
		}
		return $arr;
	}
}

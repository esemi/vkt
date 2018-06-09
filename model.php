<?php

const ROLE_CUSTOMER = 0;
const ROLE_MERCHANT = 1;
const AMOUNT_FACTOR = 1000;  //system float point for all prices
const MARGIN_FACTOR = 2;  // system price margin in percents

const DB_TRANSACTION = 'transaction';
const DB_USER = 'user';
const DB_ORDER = 'order';

const DB_CONFIG = [
	DB_TRANSACTION => ['root', 'root', 'localhost', 'vk_test'],
	DB_USER => ['root', 'root', 'localhost', 'vk_test'],
	DB_ORDER => ['root', 'root', 'localhost', 'vk_test'],
];

$dbConnections = [];

/**
 * @return bool
 */
function initDB() {
	global $dbConnections;
	$driver_options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
	foreach (DB_CONFIG as $dbName => $creds) {
		try {
			$dbConnections[$dbName] = new PDO("mysql:host={$creds[2]};dbname={$creds[3]}", $creds[0], $creds[1], $driver_options);
		} catch (PDOException $e) {
			send_warning('cant connect to db %s', $e);
			return False;
		}
	}
	return True;
}

function getDb($name) {
	global $dbConnections;
	if (empty($dbConnections)) {
		initDB();
	}
	return $dbConnections[$name] ?? null;
}


function send_warning($message, $exception=null) {
	//	todo
	var_dump("warning {$message}", $exception);
}


function encodeAmount($amount) {
	return intval($amount * AMOUNT_FACTOR);
}

function decodeAmount($amount) {
	return intval($amount / AMOUNT_FACTOR);
}

function sql_execute($db, $query) {
	$params = array_slice(func_get_args(), 2);
	$conn = getDb($db);
	$stmt = $conn->prepare($query);
	return $stmt->execute($params);
}

function checkUserRole($userId, $role) {
	if (empty($userId)) {
		return False;
	}
	$userRow = sql_execute('select id from `user` where id = ? AND role = ?', $userId, $role);
	return !empty($userRow);
}

function addTransaction($userId, $amount) {
	$preparedAmount = encodeAmount($amount);
	return sql_execute(DB_TRANSACTION, 'insert into transaction (user_id, date_create, amount) values (?, UTC_TIMESTAMP, ?)', $userId, $preparedAmount);
}

function increaseUserBalance($userId, $amount, $deductMargin=False) {
	$preparedAmount = encodeAmount($amount);
	$res = sql_execute('update user set balance = balance + ? where id = ?', $preparedAmount, $userId);
	if ($res) {
		try {
			addTransaction($userId, $amount);
		} catch (Exception $e) {
			send_warning('add transaction exception', $e);
		}
	}
	return $res;
}

function decreaseUserBalance($userId, $amount) {
	$preparedAmount = encodeAmount($amount);
	$res = sql_execute('update user set balance = balance - ? where id = ? AND balance >= ?', $preparedAmount, $userId, $preparedAmount);
	if ($res) {
		try {
			addTransaction($userId, $amount);
		} catch (Exception $e) {
			send_warning('decrease balance exception', $e);
		}
	}
	return $res;
}

function createOrder($ownerUserId, $name, $price) {
	$preparedAmount = encodeAmount($price);
	return sql_execute('insert into `order` (owner_user_id, name, price) values (?, ?, ?)', $ownerUserId, $name, $preparedAmount);
}
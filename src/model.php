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
			send_warning("cant connect to db {$dbName}", $e);
			return False;
		}
	}
	return True;
}

/**
 * @param $name string
 * @return null|PDO
 */
function getDb($name) {
	global $dbConnections;
	if (empty($dbConnections)) {
		initDB();
	}
	return $dbConnections[$name] ?? null;
}


function send_warning($message, $exception=null) {
	var_dump("warning {$message}", $exception);
}


function encodeAmount($amount) {
	return intval($amount * AMOUNT_FACTOR);
}

function decodeAmount($amount) {
	return intval($amount / AMOUNT_FACTOR);
}

function sql_execute($db, $query, $params=[], $fetch=False) {
	$conn = getDb($db);
	$stmt = $conn->prepare($query);
	$execute = $stmt->execute($params);
	if ($execute) {
		if ($fetch) {
			return $stmt->fetchAll();
		} else {
			return $stmt->rowCount();
		}
	}
	return False;
}

function checkUserRole($userId, $role) {
	if (empty($userId)) {
		return False;
	}
	$userRow = sql_execute(
		DB_USER,
		'select id from `user` where id = ? AND role = ?',
		[$userId, $role],
		True
	);
	return !empty($userRow);
}

function addTransaction($userId, $amount) {
	$preparedAmount = encodeAmount($amount);
	return sql_execute(
		DB_TRANSACTION,
		'insert into transaction (user_id, date_create, amount) values (?, UTC_TIMESTAMP, ?)',
		[$userId, $preparedAmount]
	);
}

function getUserBalance($userId) {
	$userRow = sql_execute(
		DB_USER,
		'select balance from `user` where id = ?',
		[$userId],
		True
	);
	if ($userRow) {
		return decodeAmount($userRow[0]->balance);
	} else {
		return 0;
	}
}


function increaseUserBalance($userId, $amount, $deductMargin=False) {
	$preparedAmount = encodeAmount($amount);
	$res = sql_execute(DB_USER,'update user set balance = balance + ? where id = ?', [$preparedAmount, $userId]);
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
	$res = sql_execute(
		DB_USER,
		'update user set balance = balance - ? where id = ? AND balance >= ?',
		[$preparedAmount, $userId, $preparedAmount]);
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
	return sql_execute(
		DB_ORDER,
		'insert into `order` (owner_user_id, name, price) values (?, ?, ?)',
		[$ownerUserId, $name, $preparedAmount]
	);
}

function closeOrder($orderId, $customerUserId) {
	return sql_execute(
		DB_USER,
		'update `order` set customer_user_id = ? where id = ? AND owner_user_id != ? AND customer_user_id IS NULL',
		[$customerUserId, $orderId, $customerUserId]
	);
}

function getOrder($orderId) {
	return sql_execute(
		DB_ORDER,
		'select id, owner_user_id, customer_user_id from `order` where id = ?',
		[$orderId],
		True
	)[0] ?? null;
}
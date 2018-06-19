<?php

require_once 'model.php';

// todo logging

const LIMIT_FEED = 10;

/**
 * @return int
 */
function getCurrentUserId() {
	// @fixme check user auth and get userId from session
	return (int) ($_GET['user_id'] ?? 0);
}

/**
 * @return array
 */
function place_order_process() {
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		return [405, null];
	}
	$userId = getCurrentUserId();
	$name = trim($_POST['name'] ?? '');
	$price = (int) ($_POST['price'] ?? 0);

	$validateResult = _place_order_validation($userId, $name, $price);
	if (is_array($validateResult)) {
		return $validateResult;
	}
	return _place_order($userId, $name, $price);
}

/**
 * @param $userId integer
 * @param $name string
 * @param $price integer
 * @return array|bool
 */
function _place_order_validation($userId, $name, $price) {
	$userRoleCheckResult = checkUserRole($userId, ROLE_MERCHANT);
	if (!$userRoleCheckResult) {
		return [403, ['invalid role for this action']];
	}
	// check fields
	if (empty($name) || strlen($name) > 255) {
		return [400, ['invalid name']];
	}
	if (empty($price) || $price > PHP_INT_MAX) {
		return [400, ['invalid price']];
	}
	if ($price < decodeAmount(MIN_PRICE)) {
		return [400, ['low price']];
	}

	return True;
}

/**
 * @param $userId integer
 * @param $name string
 * @param $price integer
 * @return array
 */
function _place_order($userId, $name, $price) {
	try {
		$balanceDecreaseResult = decreaseUserBalance($userId, $price);
	} catch (Exception $e) {
		return [500, ['decrease balance error']];
	}
	if (!$balanceDecreaseResult) {
		return [402, ['balance too low']];
	}

	try {
		createOrder($userId, $name, $price);
	} catch (Exception $e) {
		try {
			increaseUserBalance($userId, $price);
		} catch (Exception $_) {
			return [500, ['increase balance error']];
		} finally {
			send_warning('create order error', $e);
		}
		return [500, ['create order error']];
	}

	return [201, ['order created']];
}


function close_order_process() {
	if ($_SERVER['REQUEST_METHOD'] != 'PUT') {
		return [405, null];
	}
	$userId = getCurrentUserId();
	$orderId = (int) ($_POST['order'] ?? 0);

	$validateResult = _close_order_validation($userId, $orderId);
	if (is_array($validateResult)) {
		return $validateResult;
	}
	return _close_order($userId, $orderId);
}

function _close_order_validation($userId, $orderId) {
	$userRoleCheckResult = checkUserRole($userId, ROLE_CUSTOMER);
	if (!$userRoleCheckResult) {
		return [403, ['invalid role for this action']];
	}
	if (empty($orderId)) {
		return [400, ['invalid order id']];
	}

	$order = getOrder($orderId);
	if (empty($order)) {
		return [400, ['invalid order id']];
	}

	return True;
}


/**
 * @param $userId integer
 * @param $orderId integer
 * @return array
 */
function _close_order($userId, $orderId) {
	try {
		$orderCloseResult = closeOrder($orderId, $userId);
	} catch (Exception $e) {
		return [500, ['close order error']];
	}
	if (!$orderCloseResult) {
		return [409, ['u cant close this order']];
	}

	try {
		$orderPrice = getOrder($orderId)->price;
		increaseUserBalance($userId, $orderPrice, True);
	} catch (Exception $e) {
		try {
			reopenOrder($orderId);
		} catch (Exception $_) {
			return [500, ['reopen order error']];
		} finally {
			send_warning('increase balance error', $e);
		}
		return [500, ['increase balance error']];
	}

	return [200, ['order closed']];
}


function get_feed_process() {
	if ($_SERVER['REQUEST_METHOD'] != 'GET') {
		return [405, null];
	}
	$userId = getCurrentUserId();
	if (checkUserRole($userId, ROLE_CUSTOMER) ) {
		$orders = getCustomerOrdersFeed($userId, LIMIT_FEED);
	} else if (checkUserRole($userId, ROLE_MERCHANT)) {
		$orders = getMerchantOrdersFeed($userId, LIMIT_FEED);
	} else {
		return [401, ['user not found']];
	}
	return [200, [
		'orders' => array_map(function($x) {return (array) $x;}, $orders),
		'balance' => getUserBalance($userId),
		]
	];
}
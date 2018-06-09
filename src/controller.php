<?php

require_once 'model.php';

// todo logging

/**
 * @return int
 */
function getCurrentUserId() {
	// @fixme check user auth and get userId from session
	return (int) $_GET['user_id'];
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
	return _place_order(getCurrentUserId(), $name, $price);
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

	return [200, ['order created']];
}


function close_order_process() {
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		return [405, null];
	}
	$userId = getCurrentUserId();
	$orderId = (int) ($_POST['order'] ?? 0);

	$validateResult = _close_order_validation($userId, $orderId);
	if (is_array($validateResult)) {
		return $validateResult;
	}
	return _place_order(getCurrentUserId(), $name, $price);
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
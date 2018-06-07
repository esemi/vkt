<?php

require_once 'model.php';


function getCurrentUserId() {
	// todo check user auth and get userId from session
	return (int) $_GET['user_id'];
}


function place_order_process() {
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		return [405, null];
	}
	$name = trim($_POST['name'] ?? '');
	$price = (int) $_POST['price'] ?? 0;
	return _place_order(getCurrentUserId(), $name, $price);
}


function _place_order($userId, $name, $price) {
	$userRoleCheckResult = checkUserRole($userId, ROLE_MERCHANT);
	if (!$userRoleCheckResult) {
		return [403, null];
	}

	// check fields
	if (empty($name) || mb_strlen($name) > 255) {
		return [400, ['invalid name']];
	}
	if (empty($price) || $price > PHP_INT_MAX) {
		return [400, ['invalid price']];
	}

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
		} catch (Exception $e) {
			return [500, ['increase balance error']];
		}
		// todo send warning
		return [500, ['create order error']];
	}

	return [200, ['order created']];
}



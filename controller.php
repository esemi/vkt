<?php

require_once 'model.php';

// todo logging


function getCurrentUserId() {
	// todo check user auth and get userId from session
	return (int) $_GET['user_id'];
}


function place_order_process() {
	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		return [405, null];
	}
	$userId = getCurrentUserId();
	$name = trim($_POST['name'] ?? '');
	$price = (int) $_POST['price'] ?? 0;

	$validateResult = _place_order_validation($userId, $name, $price);
	if ($validateResult != True) {
		return $validateResult;
	}

	return _place_order(getCurrentUserId(), $name, $price);
}


function _place_order_validation($userId, $name, $price) {
	$userRoleCheckResult = checkUserRole($userId, ROLE_MERCHANT);
	if (!$userRoleCheckResult) {
		return [403, ['invalid role for this action']];
	}
	// check fields
	if (empty($name) || mb_strlen($name) > 255) {
		return [400, ['invalid name']];
	}
	if (empty($price) || $price > PHP_INT_MAX) {
		return [400, ['invalid price']];
	}

	return True;
}


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
		} catch (Exception $e) {

			return [500, ['increase balance error']];
		} finally {
			// todo send warning
			;
		}
		return [500, ['create order error']];
	}

	return [200, ['order created']];
}



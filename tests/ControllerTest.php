<?php

require_once __DIR__ . '/../src/controller.php';
require_once __DIR__ . '/../src/model.php';

/*
 * Нет, я понимаю почему на проде не ООП, но тесты то на локалке я погонять могу?)
 */

use PHPUnit\Framework\TestCase;


class PlaceOrderTest extends TestCase
{
	// todo fixture it
	const MERCHANT_USERID = 1;
	const CUSTOMER_USERID = 2;
	const TEST_BALANCE = 123;

	public function provider_place_order_validation() {
		return [
			[0, '', '', [403, ['invalid role for this action']]],
			[PlaceOrderTest::MERCHANT_USERID, '', '', [400, ['invalid name']]],
			[PlaceOrderTest::MERCHANT_USERID, 'valid name', '', [400, ['invalid price']]],
			[PlaceOrderTest::MERCHANT_USERID, 'valid name', '100500', True],
		];
	}

	/**
	 * @dataProvider provider_place_order_validation
	 */
	public function test_place_order_validation($userId, $name, $price, $expectedResult) {
		$result = _place_order_validation($userId, $name, $price);
		$this->assertEquals($result, $expectedResult);
	}

	public function test_place_order_smoke() {
		increaseUserBalance(PlaceOrderTest::MERCHANT_USERID, PlaceOrderTest::TEST_BALANCE);
		$currentbalance = getUserBalance(PlaceOrderTest::MERCHANT_USERID);

		$result = _place_order(PlaceOrderTest::MERCHANT_USERID, 'test name', $currentbalance + 1);
		$this->assertEquals([402, ['balance too low']], $result);

		$result = _place_order(PlaceOrderTest::MERCHANT_USERID, 'test name', $currentbalance - 1);
		$this->assertEquals([200, ['order created']], $result);
		$this->assertEquals(1, getUserBalance(PlaceOrderTest::MERCHANT_USERID));
	}
}


class CloseOrderTest extends TestCase
{
	public function provider_close_order_validation() {
		createOrder(PlaceOrderTest::MERCHANT_USERID, 'order title', 100500);
		$order = sql_execute(DB_ORDER,'select id, owner_user_id from `order` where customer_user_id IS NULL limit 1', [], True)[0];
		return [
			[PlaceOrderTest::MERCHANT_USERID, 0, [403, ['invalid role for this action']]],
			[PlaceOrderTest::CUSTOMER_USERID, 0, [400, ['invalid order id']]],
			[PlaceOrderTest::CUSTOMER_USERID, -1, [400, ['invalid order id']]],
			[PlaceOrderTest::CUSTOMER_USERID, $order->id, True],
		];
	}

	/**
	 * @dataProvider provider_close_order_validation
	 */
	public function test_close_order_validation($userId, $orderId, $expectedResult) {
		$result = _close_order_validation($userId, $orderId);
		$this->assertEquals($result, $expectedResult);
	}


//	public function test_close_order_smoke() {
//		increaseUserBalance(PlaceOrderTest::CUSTOMER_USERID, PlaceOrderTest::TEST_BALANCE);
//		$currentbalance = getUserBalance(PlaceOrderTest::CUSTOMER_USERID);
//
//		$result = _close_order(PlaceOrderTest::CUSTOMER_USERID, 'test name', $currentbalance + 1);
//		$this->assertEquals([402, ['already closed']], $result);
//
//		$result = _close_order(PlaceOrderTest::CUSTOMER_USERID, 'test name', $currentbalance - 1);
//		$this->assertEquals([200, ['order created']], $result);
//		$this->assertEquals($currentbalance + , getUserBalance(PlaceOrderTest::CUSTOMER_USERID));
//	}
}
<?php

require_once __DIR__ . '/../src/controller.php';
require_once __DIR__ . '/../src/model.php';

/*
 * Нет, я понимаю почему на проде не ООП, но тесты то на локалке я погонять могу?)
 */

use PHPUnit\Framework\TestCase;


class PlaceOrderTest extends TestCase
{
	const MERCHANT_USERID = 1;
	const CUSTOMER_USERID = 2;
	const TEST_BALANCE = 123;

	public function provider_place_order_validation() {
		return [
			[0, '', '', [403, ['invalid role for this action']]],
			[PlaceOrderTest::MERCHANT_USERID, '', '', [400, ['invalid name']]],
			[PlaceOrderTest::MERCHANT_USERID, 'valid name', '', [400, ['invalid price']]],
			[PlaceOrderTest::MERCHANT_USERID, 'valid name', '0.1', [400, ['low price']]],
			[PlaceOrderTest::MERCHANT_USERID, 'valid name', decodeAmount(MIN_PRICE), True],
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
		$this->assertEquals([201, ['order created']], $result);
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


	public function test_close_order_smoke() {
		$currentbalance = getUserBalance(PlaceOrderTest::CUSTOMER_USERID);
		createOrder(PlaceOrderTest::MERCHANT_USERID, 'test name', PlaceOrderTest::TEST_BALANCE);
		$testOrder = sql_execute(
			DB_ORDER,
			'select id, owner_user_id, price from `order` where owner_user_id = ? AND customer_user_id IS NULL order by price desc limit 1',
			[PlaceOrderTest::MERCHANT_USERID],
			True
		)[0];

		$result = _close_order(PlaceOrderTest::MERCHANT_USERID, $testOrder->id);
		$this->assertEquals([409, ['u cant close this order']], $result);

		$result = _close_order(PlaceOrderTest::CUSTOMER_USERID, $testOrder->id);
		$this->assertEquals([200, ['order closed']], $result);
		$addedPrice = decodeAmount(deductMargin($testOrder->price, 2));
		$this->assertEquals($currentbalance + $addedPrice, getUserBalance(PlaceOrderTest::CUSTOMER_USERID));

		$result = _close_order(PlaceOrderTest::CUSTOMER_USERID, $testOrder->id);
		$this->assertEquals([409, ['u cant close this order']], $result);
	}
}


class FeedTest extends TestCase
{
	public function test_feed_orders_smoke() {
		global $_SERVER, $_GET;
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$res = get_feed_process();
		$this->assertEquals([401, ['user not found']], $res);

		$_GET['user_id'] = PlaceOrderTest::CUSTOMER_USERID;
		$res = get_feed_process();
		$this->assertEquals(200, $res[0]);
		$this->assertGreaterThanOrEqual(0, $res[1]['balance']);
		$this->assertLessThanOrEqual(100, count($res[1]['orders']));
		foreach ($res[1]['orders'] as $order) {
			$this->assertArrayHasKey('id', $order);
			$this->assertArrayHasKey('name', $order);
			$this->assertArrayHasKey('owner_user_id', $order);
			$this->assertArrayHasKey('customer_user_id', $order);
			$this->assertArrayHasKey('price', $order);
			$this->assertNull($order['customer_user_id']);
			$this->assertNotEquals(PlaceOrderTest::CUSTOMER_USERID, $order['owner_user_id']);
		}

		$_GET['user_id'] = PlaceOrderTest::MERCHANT_USERID;
		$res = get_feed_process();
		$this->assertEquals(200, $res[0]);
		$this->assertGreaterThanOrEqual(0, $res[1]['balance']);
		$this->assertLessThanOrEqual(100, count($res[1]['orders']));
		foreach ($res[1]['orders'] as $order) {
			$this->assertArrayHasKey('id', $order);
			$this->assertArrayHasKey('name', $order);
			$this->assertArrayHasKey('owner_user_id', $order);
			$this->assertArrayHasKey('customer_user_id', $order);
			$this->assertArrayHasKey('price', $order);
			$this->assertEquals(PlaceOrderTest::MERCHANT_USERID, $order['owner_user_id']);
		}

	}
}
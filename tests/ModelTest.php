<?php

require_once __DIR__ . '/../src/controller.php';
require_once __DIR__ . '/../src/model.php';
require_once 'ControllerTest.php';

use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{

	public function test_init_db() {
		$result = initDB();
		$this->assertTrue($result);
		$this->assertNotNull(getDb(DB_ORDER));
	}

	public function test_add_transaction_smoke() {
		$res = addTransaction(PlaceOrderTest::MERCHANT_USERID, 100);
		$this->assertEquals(1, $res);
	}

	public function provider_check_user_role() {
		return [
			[0, 0, False],
			[PlaceOrderTest::MERCHANT_USERID, ROLE_MERCHANT, True],
			[PlaceOrderTest::MERCHANT_USERID, ROLE_CUSTOMER, False],
			[PlaceOrderTest::CUSTOMER_USERID, ROLE_MERCHANT, False],
			[PlaceOrderTest::CUSTOMER_USERID, ROLE_CUSTOMER, True],
		];
	}

	/**
	 * @dataProvider provider_check_user_role
	 */
	public function test_check_user_role($userId, $role, $expected) {
		$res = checkUserRole($userId, $role);
		$this->assertEquals($res, $expected);
	}

	public function test_get_user_balance() {
		$currentBalance = getUserBalance(0);
		$this->assertEquals(0, $currentBalance);
	}

	/**
	 * @depends test_get_user_balance
	 */
	public function test_increase_user_balance() {
		$currentBalance = getUserBalance(PlaceOrderTest::MERCHANT_USERID);
		$res = increaseUserBalance(PlaceOrderTest::MERCHANT_USERID, PlaceOrderTest::TEST_BALANCE);
		$this->assertEquals(1, $res);

		$this->assertEquals(
			$currentBalance + PlaceOrderTest::TEST_BALANCE,
			getUserBalance(PlaceOrderTest::MERCHANT_USERID)
		);
	}

	/**
	 * @depends test_increase_user_balance
	 */
	public function test_decrease_user_balance() {
		$currentBalance = getUserBalance(PlaceOrderTest::MERCHANT_USERID);
		$res = decreaseUserBalance(PlaceOrderTest::MERCHANT_USERID, PlaceOrderTest::TEST_BALANCE);
		$this->assertEquals(1, $res);

		$this->assertEquals(
			$currentBalance - PlaceOrderTest::TEST_BALANCE,
			getUserBalance(PlaceOrderTest::MERCHANT_USERID)
		);
	}

	public function test_create_order() {
		$res = createOrder(PlaceOrderTest::MERCHANT_USERID, 'order title', 100500);
		$this->assertEquals(1, $res);
	}

	/**
	 * @depends test_create_order
	 */
	public function test_get_order() {
		$res = getOrder(0);
		$this->assertNull($res);

		$order = sql_execute(DB_ORDER,'select id, owner_user_id, customer_user_id from `order` limit 1', [], True)[0];
		$res = getOrder($order->id);
		$this->assertEquals($order, $res);

	}

	/**
	 * @depends test_create_order
	 */
	public function test_close_order() {
		createOrder(PlaceOrderTest::MERCHANT_USERID, 'order title', 100500);
		$order = sql_execute(DB_ORDER,'select id, owner_user_id from `order` where customer_user_id IS NULL limit 1', [], True)[0];

		$res = closeOrder(0, PlaceOrderTest::CUSTOMER_USERID);
		$this->assertEquals(0, $res);

		// deny close by owner
		$res = closeOrder($order->id, $order->owner_user_id);
		$this->assertEquals(0, $res);
		$this->assertNull(getOrder($order->id)->customer_user_id);

		$res = closeOrder($order->id, PlaceOrderTest::CUSTOMER_USERID);
		$this->assertEquals(1, $res);
		$this->assertEquals(PlaceOrderTest::CUSTOMER_USERID, getOrder($order->id)->customer_user_id);
	}
}


<?php

require_once __DIR__ . '/../controller.php';
require_once __DIR__ . '/../model.php';
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
		$res = addTransaction(ControllerTest::MERCHANT_USERID, 100);
		$this->assertEquals(1, $res);
	}

	public function provider_check_user_role() {
		return [
			[0, 0, False],
			[ControllerTest::MERCHANT_USERID, ROLE_MERCHANT, True],
			[ControllerTest::MERCHANT_USERID, ROLE_CUSTOMER, False],
			[ControllerTest::CUSTOMER_USERID, ROLE_MERCHANT, False],
			[ControllerTest::CUSTOMER_USERID, ROLE_CUSTOMER, True],
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
		$currentBalance = getUserBalance(ControllerTest::MERCHANT_USERID);
		$res = increaseUserBalance(ControllerTest::MERCHANT_USERID, ControllerTest::TEST_BALANCE);
		$this->assertEquals(1, $res);

		$this->assertEquals(
			$currentBalance + ControllerTest::TEST_BALANCE,
			getUserBalance(ControllerTest::MERCHANT_USERID)
		);
	}

	/**
	 * @depends test_increase_user_balance
	 */
	public function test_decrease_user_balance() {
		$currentBalance = getUserBalance(ControllerTest::MERCHANT_USERID);
		$res = decreaseUserBalance(ControllerTest::MERCHANT_USERID, ControllerTest::TEST_BALANCE);
		$this->assertEquals(1, $res);

		$this->assertEquals(
			$currentBalance - ControllerTest::TEST_BALANCE,
			getUserBalance(ControllerTest::MERCHANT_USERID)
		);
	}

	public function test_create_order() {
		$res = createOrder(ControllerTest::MERCHANT_USERID, 'order title', 100500);
		$this->assertEquals(1, $res);
	}
}


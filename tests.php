<?php

require_once 'controller.php';
require_once 'model.php';

use PHPUnit\Framework\TestCase;

/*
 * Нет, я понимаю почему на проде не ООП, но тесты то на локалке я погонять могу?)
 */
class ControllerTest extends TestCase
{
	// todo fixture it
	const MERCHANT_USERID = 1;
	const CUSTOMER_USERID = 2;
	const TEST_BALANCE = 123;

	public function provider_place_order_validation() {
		return [
			[0, '', '', [403, ['invalid role for this action']]],
			[ControllerTest::MERCHANT_USERID, '', '', [400, ['invalid name']]],
			[ControllerTest::MERCHANT_USERID, 'valid name', '', [400, ['invalid price']]],
			[ControllerTest::MERCHANT_USERID, 'valid name', '100500', True],
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
		$result = _place_order(0, 'test name', 1);
		$this->assertEquals($result, [402, ['balance too low']]);

		increaseUserBalance(ControllerTest::MERCHANT_USERID, ControllerTest::TEST_BALANCE);
		$result = _place_order(ControllerTest::MERCHANT_USERID, 'test name', ControllerTest::TEST_BALANCE);
		$this->assertEquals($result, [200, ['order created']]);

	}
}


class ModelTest extends TestCase {

	public function test_init_db() {
		$result = initDB();
		$this->assertTrue($result);
		$this->assertNotNull(getDb(DB_ORDER));
	}

	public function test_add_transaction_smoke() {
		$res = addTransaction(ControllerTest::MERCHANT_USERID, 100);
		$this->assertTrue($res);
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
}

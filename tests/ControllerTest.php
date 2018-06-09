<?php

require_once __DIR__ . '/../controller.php';
require_once __DIR__ . '/../model.php';

/*
 * Нет, я понимаю почему на проде не ООП, но тесты то на локалке я погонять могу?)
 */

use PHPUnit\Framework\TestCase;


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
		increaseUserBalance(ControllerTest::MERCHANT_USERID, 1);
		$currentbalance = getUserBalance(ControllerTest::MERCHANT_USERID);

		$result = _place_order(ControllerTest::MERCHANT_USERID, 'test name', $currentbalance + 1);
		$this->assertEquals([402, ['balance too low']], $result);

		$result = _place_order(ControllerTest::MERCHANT_USERID, 'test name', $currentbalance - 1);
		$this->assertEquals([200, ['order created']], $result);
		$this->assertEquals(1, getUserBalance(ControllerTest::MERCHANT_USERID));

	}
}


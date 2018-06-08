<?php

require_once 'controller.php';

use PHPUnit\Framework\TestCase;

/*
 * Нет, я понимаю почему на продакшене нет ООП, но тесты то на локалке я погонять могу?)
 */
class ControllerTest extends TestCase
{
	// todo fixture it
	const MERCHANT_USERID = 1;

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
		// todo not only smoke
		$result = _place_order()
	}
}

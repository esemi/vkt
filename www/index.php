<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/controller.php';

const PLACE_ORDER_ROUTE = 'place_order';
const CLOSE_ORDER_ROUTE = 'close_order';
const FEED_ROUTE = 'feed';


function serve() {
	switch ($_GET['action'] ?? '') {
		case PLACE_ORDER_ROUTE:
			list($code, $data) = place_order_process();
			break;
		case CLOSE_ORDER_ROUTE:
			list($code, $data) = close_order_process();
			break;
		case FEED_ROUTE:
			list($code, $data) = get_feed_process();
			break;
		default:
			list($code, $data) = [404, []];
			break;
	};

	$response = ['code' => $code];
	if (!empty($data)) {
		$response['data'] = $data;
	}
	http_response_code($code);
	header('Content-Type: application/json');
	print(json_encode($response));
}

serve();

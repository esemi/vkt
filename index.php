<?php

require_once 'controller.php';



(function () {
	switch ($_GET['action'] ?? '') {
		case 'place_order':
			list($code, $data) = place_order_process();
			break;
		default:
			list($code, $data) = [404, []];
			break;
	};

	http_response_code($code);
	header('Content-Type: application/json');
	if (empty($data)) {
		$data = [];
	}
	$data['code'] = $code;
	print(json_encode($data));
})();





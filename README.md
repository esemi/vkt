vkt [![Build Status](https://travis-ci.org/esemi/vkt.svg?branch=master)](https://travis-ci.org/esemi/vkt)
---
Yet another test task


```
$ composer install
$ ./vendor/bin/phpunit tests
$ php -S localhost:8080 -t www www/api.php
$
$ curl -X 'POST' --data "name=sdsdsd&price=1000" "localhost:8080/index.php?action=place_order&user_id=111"
$ curl -X 'PUT' --data "order=1" "localhost:8080/index.php?action=close_order&user_id=222"
$ curl "localhost:8080/index.php?action=feed&user_id=222"
$
$ siege -c100 https://vkt.esemi.ru/api.php?action=feed&user_id=1
```

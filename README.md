vkt
---


```
$ composer install
$ ./vendor/bin/phpunit tests
$ php -S localhost:8080 -t www www/index.php
$ curl -X 'POST' --data "name=sdsdsd&price=1000" "localhost:8080/index.php?action=place_order&user_id=111"

```


#### TODO

- ~place order backend~
- ~close order backend~

- place order frontend
- close order frontend

- mysql profiling
- cleaning
- deploy
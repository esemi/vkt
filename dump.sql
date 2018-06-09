CREATE TABLE IF NOT EXISTS `transaction`
(
  id int PRIMARY KEY AUTO_INCREMENT,
  user_id smallint,
  date_create datetime,
  amount int
);


CREATE TABLE  IF NOT EXISTS `user`
(
  id int PRIMARY KEY AUTO_INCREMENT,
  role tinyint,
  balance int DEFAULT 0
);


CREATE TABLE  IF NOT EXISTS `order`
(
  id int PRIMARY KEY AUTO_INCREMENT,
  owner_user_id int,
  customer_user_id int DEFAULT NULL,
  name varchar(255),
  price int
);

INSERT INTO vk_test.user (id, role, balance) VALUES (1, 1, 0);
INSERT INTO vk_test.user (id, role, balance) VALUES (2, 0, 0);
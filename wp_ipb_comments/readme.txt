Перед активацией плагина в БД выполнить запрос

CREATE TABLE `wp_comments_rating_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

В single.php шаблона сайта в месте, где необходимо выводить комментарии, вставить строку
<?php $wpIpbComments->comments(); ?>

Форум должен быть в директории /forum/
Префикс таблиц в БД у блога 'wp_' у форума 'ipb_'


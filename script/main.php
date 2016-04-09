<?php
	require_once('Movie.class.php');
	$movie = new Movie();
	$user = $movie->hot_user('经典');
	echo('开始抓评论');
	foreach ($user as $key => $value) {
		$movie->rating($value);
	}
?>
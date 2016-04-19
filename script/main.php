<?php
	require_once('Movie.class.php');
	$movie = new Movie();
	//$movie->user_info('mochizukikaoru');
	/*$user = $movie->hot_user('日本');
	echo('开始抓评论');
	foreach ($user as $key => $value) {
		$movie->rating($value);
	}*/
	$url = $movie->rating_movie();
	foreach ($url as $key => $value) {
		$movie->movie_info($value);
	}
	var_dump('finished');
?>
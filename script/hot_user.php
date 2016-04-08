<?php
    function hot_user($category = '最新'){
		$newest = json_decode(file_get_contents('https://movie.douban.com/j/search_subjects?type=movie&tag='.urlencode($category).'&page_limit=1000&page_start=0'),true);
		$content = file_get_contents('');
		$main = $main.preg_replace("/[\t\n\r]+/","",$content); 
		return $newest;
    }
    
?>
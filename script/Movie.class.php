<?php
	Class Movie{
		private $mysql_server_name="localhost"; //数据库服务器名称
		private $mysql_username="root"; // 连接数据库用户名
		private $mysql_password=""; // 连接数据库密码
		private $mysql_database="movie"; // 数据库的名字
		private $conn;
		private $cookie_file;
		private $exist = 0;
		function __construct(){
			//模拟登陆
			$this->cookie_file = dirname(__FILE__).'/cookie.txt';
			if(!file_exists($this->cookie_file))
			{
    			$login_data = array('source'=>'movie','redir'=>'https://movie.douban.com/celebrity/1274240/','form_email'=>'378681741@qq.com','form_password'=>'0412yxyxys','login'=>'登录','remember'=>'on');
				self::http_post($login_data,'https://accounts.douban.com/login');
			}
			// 连接到数据库
			$this->conn=mysql_connect($this->mysql_server_name, $this->mysql_username,
		                        $this->mysql_password) or die('failed'); 
		    mysql_query("set names 'utf8'");
		}
		private function http_post($post_data,$url){
			$o = "" ;
			foreach ( $post_data as $k => $v ) 
			{ 
     			$o .= "$k=" . urlencode ( $v ) . "&" ;
			} 
			$post_data = substr ( $o , 0 ,- 1 ) ;
			$ch = curl_init () ;
			curl_setopt ( $ch , CURLOPT_POST , 1 ) ;
			curl_setopt ( $ch , CURLOPT_HEADER , 0 ) ;
			curl_setopt ( $ch , CURLOPT_URL , $url ) ;
			//为了支持cookie 
			curl_setopt ( $ch , CURLOPT_COOKIEJAR , $this->cookie_file ) ;
			curl_setopt ( $ch , CURLOPT_POSTFIELDS , $post_data ) ;
			$result = curl_exec ( $ch ) ;
			curl_close($ch);
			return $result;
		}
		private function http_get($url){
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file); //使用上面获取的cookies
			$response = curl_exec($ch);	
			curl_close($ch);
			return $response;
		}
		//获得所有评论中的电影url
		public function rating_movie(){
			$sql = "SELECT DISTINCT `mid` FROM `comment`";
			$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 18'.mysql_error());
			$url = array();
			while ($result=mysql_fetch_assoc($rs)) {
				array_push($url,$result['mid']);
			}	
			return $url;
		}
		//用户信息
		public function user_info($uid){
			$content = file_get_contents('https://www.douban.com/people/'.$uid.'/');
			$main = preg_replace("/[\t\n\r]+/","",$content);
			var_dump($main);
			exit();
			return $user;
    	}
		//获得热门用户
		public function hot_user($category = '最新'){
			$newest = json_decode(file_get_contents('https://movie.douban.com/j/search_subjects?type=movie&tag='.urlencode($category).'&page_limit=1000&page_start=0'),true);
			$user = array();
			foreach ($newest['subjects'] as $key => $value) {
				$content = self::movie_info($value['url']);
				$commentPartern = '/<span class="comment-info">                <a href="https:\/\/www.douban.com\/people\/([^<>]+)\/" class="">([^<>]+)<\/a>                    <span class="([^<>]+)" title="([^<>]+)"><\/span>                <span class="">                    ([^<>]+)                <\/span>            <\/span>/';
				preg_match_all($commentPartern,$content,$commentResult);
				foreach ($commentResult[1] as $key => $value) {
				  	array_push($user, $value);
				}  
				sleep(0.1);
			}
			return $user;
    	}
    	public function movie_info($movie_url){
    		$sql = "select * from info where douban = '".mysql_escape_string($movie_url)."'";
			$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 50'.mysql_error());
			$row=mysql_fetch_row($rs);
			if($row!== false)
			{
				var_dump('已存在'.(++$this->exist));
				return;
			}
			$content = self::http_get($movie_url);
			$main = preg_replace("/[\t\n\r]+/","",$content); 
			$titlePartern = '/<span property="v:itemreviewed">([^<>]+)<\/span>/';
			//$infoPartern = '/<span ><span class=\'pl\'>导演<\/span>: <span class=\'attrs\'><a href="([^<>]+)" rel="v:directedBy">([^<>]+)<\/a><\/span><\/span><br\/>       <span class="actor"><span class=\'pl\'>主演<\/span>: <span class=\'attrs\'>(.+)<\/span><\/span><br\/>/';
			//年份
			$yearPartern = '/<span class="year">([^<>]+)<\/span>/';
			//导演
			$directorPartern = '/<a href="([^<>]+)" rel="v:directedBy">([^<>]+)<\/a>/';
			//编剧
			$writerPartern = '/<a href="\/celebrity\/([^r]+)">([^<>]+)<\/a>/';
			//演员
			$actorPartern = '/<a href="([^<>]+)" rel="v:starring">([^<>]+)<\/a>/';
			//类型
			$typePartern = '/<span property="v:genre">([^<>]+)<\/span>/';
			//封面,id
			$coverPartern = '/<img src="([^<>]+)" title="点击看更多海报" alt="([^<>]+)" rel="v:image" \/>/';
			//上映时间
			$initialPartern = '/<span property="v:initialReleaseDate" content="([^<>]+)">/';
			//语言
			$languagePartern = '/<span class="pl">语言:<\/span> ([^<>]+)<br\/>/';
			//又名
			$nicknamePartern = '/<span class="pl">又名:<\/span> ([^<>]+)<br\/>/';
			//评分
			$ratingPartern = '/<strong class="ll rating_num" property="v:average">([^<>]+)<\/strong>/';
			//imdb
			$imdbPartern = '/IMDb链接:<\/span> <a href="([^<>]+)" target="_blank" rel="nofollow">/';
			//官网
			$officialPartern = '/官方网站:<\/span> <a href="([^<>]+)" rel="nofollow" target="_blank">/';
			//剧照
			$photoPartern = '/<img src="([^<>]+)" alt="图片" \/>/';
			//预告片
			$trailerPartern = '/<a class="related-pic-video" href="([^<>]+)">                        <span><\/span>                        <img src="([^<>]+)" width="([^<>]+)" height="([^<>]+)" alt="预告片" \/>/';
			preg_match_all($titlePartern,$main,$titleResult); 
			unset($titleResult[0]);
			preg_match_all($yearPartern,$main,$yearResult); 
			unset($yearResult[0]);
			preg_match_all($directorPartern,$main,$directorResult);
			unset($directorResult[0]);
			preg_match_all($writerPartern,$main,$writerResult);
			unset($writerResult[0]);
			preg_match_all($actorPartern,$main,$actorResult); 
			unset($actorResult[0]);
			preg_match_all($typePartern,$main,$typeResult); 
			unset($typeResult[0]);
			preg_match_all($coverPartern,$main,$coverResult); 
			unset($coverResult[0]);
			preg_match_all($initialPartern,$main,$initialResult); 
			unset($initialResult[0]);
			preg_match_all($languagePartern,$main,$languageResult); 
			unset($languageResult[0]);
			preg_match_all($nicknamePartern,$main,$nicknameResult);
			unset($nicknameResult[0]);
			preg_match_all($ratingPartern,$main,$ratingResult);
			unset($ratingResult[0]);
			preg_match_all($imdbPartern,$main,$imdbResult); 
			unset($imdbResult[0]);
			preg_match_all($officialPartern,$main,$officialResult); 
			unset($officialResult[0]);
			preg_match_all($photoPartern,$main,$photoResult); 
			unset($photoResult[0]);
			preg_match_all($trailerPartern,$main,$trailerResult); 
			unset($trailerResult[0]);
			//用字符串函数抓取简介
			$start = strpos($main, '<span property="v:summary" class="">');
			$end = strpos($main,'</span>',$start);
			$summary = substr($main, $start + strlen('<span property="v:summary" class="">'),$end - $start - strlen('<span property="v:summary" class="">'));
			if (substr($summary,1,1)!== " "){
				$start = strpos($main, '<span property="v:summary">');
			$end = strpos($main,'</span>',$start);
			$summary = substr($main, $start + strlen('<span property="v:summary">'),$end - $start - strlen('<span property="v:summary">'));
			}
			$sql = "insert into info (title,year,director,writer,actor,type,mid,cover,initial,language,nickname,rating,imdb,official,douban,summary,photo,trailer) values('".mysql_escape_string($titleResult[1][0])."','"
				.mysql_escape_string($yearResult[1][0])."','".mysql_escape_string(json_encode($directorResult))."','".mysql_escape_string(json_encode($writerResult))."','"
				.mysql_escape_string(json_encode($actorResult))."','".mysql_escape_string(json_encode($typeResult))."','".mysql_escape_string($coverResult[2][0])."','"
				.mysql_escape_string($coverResult[1][0])."','".mysql_escape_string(json_encode($initialResult))."','".mysql_escape_string($languageResult[1][0])."','"
				.mysql_escape_string($nicknameResult[1][0])."','".mysql_escape_string($ratingResult[1][0])."','".mysql_escape_string($imdbResult[1][0])."','".mysql_escape_string($officialResult[1][0])."','"
				.mysql_escape_string($movie_url)."','".mysql_escape_string($summary)."','".mysql_escape_string(json_encode($photoResult))."','".mysql_escape_string(json_encode($trailerResult))."');";
			$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 84'.mysql_error());
			return $main;
    	}
    	//获取评论
    	public function rating($uid){
    		$sql = "select * from comment where uid = '".$uid."'";
			$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 39'.mysql_error());
			$row=mysql_fetch_row($rs);
			if($row!== false)
			{
				return;
			}   
			$page = 1;
			$main = '';
			printf('%s\n',$uid);
			while(true){
				$offset = $page == 1 ? 1 : ($page - 1) * 15 ; 
				$content = file_get_contents('https://movie.douban.com/people/'.$uid.'/collect?start='.$offset.'&sort=time&rating=all&filter=all&mode=grid');
				$location = strrpos($content,'class="intro"');
				if($location === false){
					break;
				}
				echo $offset;
				$main = $main.preg_replace("/[\t\n\r]+/","",$content); 
				sleep(0.1);
				$page++; 
			}
			printf('\n');
			//echo $main;
			$partern1='/<div class="item" >            <div class="pic">                <a title="([^<>]+)" href="([^<>]+)" class="nbg">                    <img alt="([^<>]+)" src="([^<>]+)" class="">                <\/a>            <\/div>            <div class="info">                <ul>                    <li class="title">                        <a href="([^<>]+)" class="">                            <em>([^<>]+)<\/em>                             ([^<>]+)                        <\/a>                    <\/li>                        <li class="intro">([^<>]+)<\/li>                    <li>                                    <span class="([^<>]+)"><\/span>                        <span class="date">([^<>]+)<\/span>                                                    <span class="tags">([^<>]+)<\/span>                    <\/li>                    <li>                        <span class="comment">([^<>]+)<\/span>                                            <\/li>                <\/ul>            <\/div>        <\/div>/';
			//有用
			$partern2='/<div class="item" >            <div class="pic">                <a title="([^<>]+)" href="([^<>]+)" class="nbg">                    <img alt="([^<>]+)" src="([^<>]+)" class="">                <\/a>            <\/div>            <div class="info">                <ul>                    <li class="title">                        <a href="([^<>]+)" class="">                            <em>([^<>]+)<\/em>           ([^<>]+)                        <\/a>                    <\/li>                        <li class="intro">([^<>]+)<\/li>                    <li>                                    <span class="([^<>]+)"><\/span>                        <span class="date">([^<>]+)<\/span>                                                    <span class="tags">([^<>]+)<\/span>                    <\/li>                    <li>                        <span class="comment">([^<>]+)<\/span>                                                    <span class="pl">([^<>]+)<\/span>                    <\/li>                <\/ul>            <\/div>        <\/div>/';
			//可播放
			$partern3='/<div class="item" >            <div class="pic">                <a title="([^<>]+)" href="([^<>]+)" class="nbg">                    <img alt="([^<>]+)" src="([^<>]+)" class="">                <\/a>            <\/div>            <div class="info">                <ul>                    <li class="title">                        <a href="([^<>]+)" class="">                            <em>([^<>]+)<\/em>                             ([^<>]+)                        <\/a>                            <span class="playable">([^<>]+)<\/span>                    <\/li>                        <li class="intro">([^<>]+)<\/li>                    <li>                                    <span class="([^<>]+)"><\/span>                        <span class="date">([^<>]+)<\/span>                                                    <span class="tags">([^<>]+)<\/span>                    <\/li>                    <li>                        <span class="comment">([^<>]+)<\/span>                                            <\/li>                <\/ul>            <\/div>        <\/div>/';
			//无别称
			$partern4='/<div class="item" >            <div class="pic">                <a title="([^<>]+)" href="([^<>]+)" class="nbg">                    <img alt="([^<>]+)" src="([^<>]+)" class="">                <\/a>            <\/div>            <div class="info">                <ul>                    <li class="title">                        <a href="([^<>]+)" class="">                            <em>([^<>]+)<\/em>                                                    <\/a>                    <\/li>                        <li class="intro">([^<>]+)<\/li>                    <li>                                    <span class="([^<>]+)"><\/span>                        <span class="date">([^<>]+)<\/span>                                                    <span class="tags">([^<>]+)<\/span>                    <\/li>                    <li>                        <span class="comment">([^<>]+)<\/span>                                            <\/li>                <\/ul>            <\/div>        <\/div>/';
			//无评论有别称不可播放
			$partern5='/<div class="item" >            <div class="pic">                <a title="([^<>]+)" href="([^<>]+)" class="nbg">                    <img alt="([^<>]+)" src="([^<>]+)" class="">                <\/a>            <\/div>            <div class="info">                <ul>                    <li class="title">                        <a href="([^<>]+)" class="">                            <em>([^<>]+)<\/em>                             ([^<>]+)                        <\/a>                    <\/li>                        <li class="intro">([^<>]+)<\/li>                    <li>                                    <span class="([^<>]+)"><\/span>                        <span class="date">([^<>]+)<\/span>                                            <\/li>                <\/ul>            <\/div>        <\/div>/';
			//无评论有别称可播放
			$partern6='/<div class="item" >            <div class="pic">                <a title="([^<>]+)" href="([^<>]+)" class="nbg">                    <img alt="([^<>]+)" src="([^<>]+)" class="">                <\/a>            <\/div>            <div class="info">                <ul>                    <li class="title">                        <a href="([^<>]+)" class="">                            <em>([^<>]+)<\/em>                             ([^<>]+)                        <\/a>                            <span class="playable">([^<>]+)<\/span>                    <\/li>                        <li class="intro">([^<>]+)<\/li>                    <li>                                    <span class="([^<>]+)"><\/span>                        <span class="date">([^<>]+)<\/span>                                            <\/li>                <\/ul>            <\/div>        <\/div>/';
			//有评论无别称可播放无有用
			$partern7='/<div class="item" >            <div class="pic">                <a title="([^<>]+)" href="([^<>]+)" class="nbg">                    <img alt="([^<>]+)" src="([^<>]+)" class="">                <\/a>            <\/div>            <div class="info">                <ul>                    <li class="title">                        <a href="([^<>]+)" class="">                            <em>([^<>]+)<\/em>                                                    <\/a>                            <span class="playable">([^<>]+)<\/span>                    <\/li>                        <li class="intro">([^<>]+)<\/li>                    <li>                                    <span class="([^<>]+)"><\/span>                        <span class="date">([^<>]+)<\/span>                                            <\/li>                    <li>                        <span class="comment">([^<>]+)<\/span>                                            <\/li>                <\/ul>            <\/div>        <\/div>/';
			//写入普通数值至数据库
			preg_match_all($partern1,$main,$result); 
			if(!empty($result[0])){
				for($i=0;$i<count($result[0]);$i++){
					//检查是否有旧数据
					/*$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 39'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row!== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 45'.mysql_error());
					}*/
					$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content) values('".$uid."','"
						   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
						   .$result[6][$i]."','".$result[7][$i]."','".$result[8][$i]."','"
						   .$result[9][$i]."','".$result[10][$i]."','".$result[11][$i]."','"
						   .$result[12][$i]."');";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed'.mysql_error());
					//$row=mysql_fetch_row($rs);
					//var_dump($row);
				} 
			} 
			unset($result);
			//写入“有用”数值至数据库
			preg_match_all($partern2,$main,$result); 
			if(!empty($result[0])){
				for($i=0;$i<count($result[0]);$i++){
					//检查是否有旧数据
					/*$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 53'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 59 '.mysql_error());
					}*/
					$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content,remark) values('".$uid."','"
						   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
						   .$result[6][$i]."','".$result[7][$i]."','".$result[8][$i]."','"
						   .$result[9][$i]."','".$result[10][$i]."','".$result[11][$i]."','"
						   .$result[12][$i]."','".$result[13][$i]."');";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 66'.mysql_error());
					//$row=mysql_fetch_row($rs);
					//var_dump($row);
				} 
			}
			unset($result);
			//写入“可播放”数值至数据库
			preg_match_all($partern3,$main,$result);
			if(!empty($result[0])){
				for($i=0;$i<count($result[0]);$i++){
					//检查是否有旧数据
					/*$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 53'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 95 '.mysql_error());
					}*/
					$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content,remark) values('".$uid."','"
						   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
						   .$result[6][$i]."','".$result[7][$i]."','".$result[9][$i]."','"
						   .$result[10][$i]."','".$result[11][$i]."','".$result[12][$i]."','"
						   .$result[13][$i]."','".$result[8][$i]."');";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 102'.mysql_error());
					//$row=mysql_fetch_row($rs);
					//var_dump($row);
				} 
			}
			unset($result);
			//写入“无别称”数值至数据库
			preg_match_all($partern4,$main,$result);
			if(!empty($result[0])){
				for($i=0;$i<count($result[0]);$i++){
					//检查是否有旧数据
					/*$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 53'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 120 '.mysql_error());
					}*/
					$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content) values('".$uid."','"
						   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
						   .$result[6][$i]."','','".$result[7][$i]."','"
						   .$result[8][$i]."','".$result[9][$i]."','".$result[10][$i]."','"
						   .$result[11][$i]."');";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 127'.mysql_error());
					//$row=mysql_fetch_row($rs);
					//var_dump($row);
				} 
			}
			unset($result);
			//写入无评论数值至数据库
			preg_match_all($partern5,$main,$result);
			if(!empty($result[0])){
				for($i=0;$i<count($result[0]);$i++){
					//检查是否有旧数据
					/*$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 143'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 149 '.mysql_error());
					}*/
					$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content) values('".$uid."','"
						   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
						   .$result[6][$i]."','".$result[7][$i]."','".$result[8][$i]."','"
						   .$result[9][$i]."','".$result[10][$i]."','','');";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 155'.mysql_error());
					//$row=mysql_fetch_row($rs);
					//var_dump($row);
				} 
			}
			unset($result);
			//写入无评论可播放数值至数据库
			preg_match_all($partern6,$main,$result);
			if(!empty($result[0])){
				for($i=0;$i<count($result[0]);$i++){
					//检查是否有旧数据
					/*$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 143'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 149 '.mysql_error());
					}*/
					$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content,remark) values('".$uid."','"
						   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
						   .$result[6][$i]."','".$result[7][$i]."','".$result[9][$i]."','"
						   .$result[10][$i]."','".$result[11][$i]."','','','可播放');";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 155'.mysql_error());
					//$row=mysql_fetch_row($rs);
					//var_dump($row);
				} 
			}
			unset($result);
			//写入有评论无别称可播放无有用无标签至数据库
			preg_match_all($partern7,$main,$result);
			if(!empty($result[0])){
				for($i=0;$i<count($result[0]);$i++){
					//检查是否有旧数据
					/*$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 143'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 149 '.mysql_error());
					}*/
					$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content,remark) values('".$uid."','"
						   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
						   .$result[6][$i]."','','".$result[8][$i]."','"
						   .$result[9][$i]."','".$result[10][$i]."','','".$result[11][$i]."','可播放');";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 155'.mysql_error());
					//$row=mysql_fetch_row($rs);
					//var_dump($row);
				} 
			}
		}
	}
?>
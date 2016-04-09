<?php
	Class Movie{
		private $mysql_server_name="localhost"; //数据库服务器名称
		private $mysql_username="root"; // 连接数据库用户名
		private $mysql_password=""; // 连接数据库密码
		private $mysql_database="movie"; // 数据库的名字
		private $conn;
		function __construct(){
			 // 连接到数据库

			$this->conn=mysql_connect($this->mysql_server_name, $this->mysql_username,
		                        $this->mysql_password) or die('failed'); 
		    mysql_query("set names 'utf8'");
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
			$content = file_get_contents($movie_url);
			$main = preg_replace("/[\t\n\r]+/","",$content); 
			$titlePartern = '/<div id="content">            <h1>        <span property="v:itemreviewed">([^<>]+)<\/span>            <span class="year">([^<>]+)<\/span>/';
			//$infoPartern = '/<span ><span class=\'pl\'>导演<\/span>: <span class=\'attrs\'><a href="([^<>]+)" rel="v:directedBy">([^<>]+)<\/a><\/span><\/span><br\/>       <span class="actor"><span class=\'pl\'>主演<\/span>: <span class=\'attrs\'>(.+)<\/span><\/span><br\/>/';
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
			preg_match_all($titlePartern,$main,$titleResult); 
			unset($titleResult[0]);
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
			$sql = "select * from info where mid = '".$coverResult[2][0]."'";
			$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 73'.mysql_error());
			$row=mysql_fetch_row($rs);
			if($row!== false)
			{
				return;
			}
			$sql = "insert into info (title,year,director,writer,actor,type,mid,cover,initial,language,nickname,rating,imdb,official) values('".mysql_escape_string($titleResult[1][0])."','"
				.mysql_escape_string($titleResult[2][0])."','".mysql_escape_string(json_encode($directorResult))."','".mysql_escape_string(json_encode($writerResult))."','"
				.mysql_escape_string(json_encode($actorResult))."','".mysql_escape_string(json_encode($typeResult))."','".mysql_escape_string($coverResult[2][0])."','"
				.mysql_escape_string($coverResult[1][0])."','".mysql_escape_string(json_encode($initialResult))."','".mysql_escape_string($languageResult[1][0])."','"
				.mysql_escape_string($nicknameResult[1][0])."','".mysql_escape_string($ratingResult[1][0])."','".mysql_escape_string($imdbResult[1][0])."','".mysql_escape_string($officialResult[1][0])."');";
			//var_dump($sql);
			//exit();
			$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 84'.mysql_error());
			return $main;
    	}
    	//获取评论
    	public function rating($uid){   
			$page = 1;
			$main = '';
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
					$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 39'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row!== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 45'.mysql_error());
					}
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
					$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 53'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 59 '.mysql_error());
					}
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
					$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 53'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 95 '.mysql_error());
					}
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
					$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 53'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 120 '.mysql_error());
					}
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
					$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 143'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 149 '.mysql_error());
					}
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
					$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 143'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 149 '.mysql_error());
					}
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
					$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
					$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 143'.mysql_error());
					$row=mysql_fetch_row($rs);
					if($row !== false)
					{
						//删除旧数据
						$sql = "delete from comment where id = ".$row[0];
						$rs=mysql_db_query($this->mysql_database, $sql, $this->conn) or die('failed 149 '.mysql_error());
					}
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
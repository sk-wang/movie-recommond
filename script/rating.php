<?php
	$mysql_server_name="localhost"; //数据库服务器名称
    $mysql_username="root"; // 连接数据库用户名
    $mysql_password=""; // 连接数据库密码
    $mysql_database="movie"; // 数据库的名字
    // 连接到数据库
    $conn=mysql_connect($mysql_server_name, $mysql_username,
                        $mysql_password) or die('failed'); 
    mysql_query("set names 'utf8'");
	$uid = 'boliwawa';
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
	//写入普通数值至数据库
	preg_match_all($partern1,$main,$result); 
	if(!empty($result[0])){
		for($i=0;$i<count($result[0]);$i++){
			//检查是否有旧数据
			$sql = "select * from comment where uid = '".$uid."' and mid = '".$result[2][$i]."'";
			$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 39'.mysql_error());
			$row=mysql_fetch_row($rs);
			if($row!== false)
			{
				//删除旧数据
				$sql = "delete from comment where id = ".$row[0];
				$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 45'.mysql_error());
			}
			$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content) values('".$uid."','"
				   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
				   .$result[6][$i]."','".$result[7][$i]."','".$result[8][$i]."','"
				   .$result[9][$i]."','".$result[10][$i]."','".$result[11][$i]."','"
				   .$result[12][$i]."');";
			$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed'.mysql_error());
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
			$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 53'.mysql_error());
			$row=mysql_fetch_row($rs);
			if($row !== false)
			{
				//删除旧数据
				$sql = "delete from comment where id = ".$row[0];
				$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 59 '.mysql_error());
			}
			$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content,remark) values('".$uid."','"
				   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
				   .$result[6][$i]."','".$result[7][$i]."','".$result[8][$i]."','"
				   .$result[9][$i]."','".$result[10][$i]."','".$result[11][$i]."','"
				   .$result[12][$i]."','".$result[13][$i]."');";
			$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 66'.mysql_error());
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
			$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 53'.mysql_error());
			$row=mysql_fetch_row($rs);
			if($row !== false)
			{
				//删除旧数据
				$sql = "delete from comment where id = ".$row[0];
				$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 59 '.mysql_error());
			}
			$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content,remark) values('".$uid."','"
				   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
				   .$result[6][$i]."','".$result[7][$i]."','".$result[9][$i]."','"
				   .$result[10][$i]."','".$result[11][$i]."','".$result[12][$i]."','"
				   .$result[13][$i]."','".$result[8][$i]."');";
			$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 66'.mysql_error());
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
			$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 53'.mysql_error());
			$row=mysql_fetch_row($rs);
			if($row !== false)
			{
				//删除旧数据
				$sql = "delete from comment where id = ".$row[0];
				$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 59 '.mysql_error());
			}
			$sql = "insert into comment (uid,mname_key,mid,mimg,mname,mnickname,minfo,rating,date,tag,content) values('".$uid."','"
				   .$result[1][$i]."','".$result[2][$i]."','".$result[4][$i]."','"
				   .$result[6][$i]."','','".$result[7][$i]."','"
				   .$result[8][$i]."','".$result[9][$i]."','".$result[10][$i]."','"
				   .$result[11][$i]."');";
			$rs=mysql_db_query($mysql_database, $sql, $conn) or die('failed 66'.mysql_error());
			//$row=mysql_fetch_row($rs);
			//var_dump($row);
		} 
	}
?>
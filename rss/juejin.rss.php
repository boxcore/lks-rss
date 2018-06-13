<?php


require '../rss.init.php';

$log_file = RSS_LOG . basename($_SERVER['SCRIPT_NAME']);

// 一些缓存配置
header ("Content-Type:text/xml");
header("Cache-Control: public");
header("Pragma: cache");
$offset = 2*60; // cache 2min
$ExpStr = "Expires: ".gmdate("D, d M Y H:i:s", time() + $offset)." GMT";
header($ExpStr);

$ETag=md5(date('Y-m-d H'));
if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && ($_SERVER['HTTP_IF_NONE_MATCH']==$ETag) ){
	header('HTTP/1.1 304 Not Modified'); //返回304，告诉浏览器调用缓存
	exit();
}else{
	header('Etag:'.$ETag);
}

$rss_conf = array();
$rss_conf = array(
    'name' => '掘金',
    'mark' => 'juejin',
    'host' => 'juejin.im',
    'type' => 1,
    'list' => array(
        'url' => '/'
    ),
    'content' => array(

    ),
);

$url_list = "https://timeline-merger-ms.juejin.im/v1/get_entry_by_rank?src=web&category=all";
throw_file("开始获取列表：$url_list");
$html     = gethtml($url_list);
if($html =='error'){
    throw_file("获取列表异常");
    exit(-1);
}

$json = json_decode($html,1);

if(!empty($json['d']['entrylist'])){
    foreach($json['d']['entrylist'] as $k=>$v){
        // var_dump($v['originalUrl'], $v['title']);
        
        if(preg_match('#juejin.im/post/#is',$v['originalUrl'],$rs_url)){
            $rs[1][] = $v['originalUrl'];
            // throw_file($v['originalUrl']);
        }
    }


    $url_cnt = count($rs[1]);
    throw_file("获取到{$url_cnt}篇文章");
    $header     = '<?xml version="1.0" encoding="utf-8"?><rss version="2.0"><channel><title>'.$rss_conf['name'].'</title>';
    $footer     = '</channel></rss>';
    $rss = '';
    $i = 1;
    foreach($rs[1] as $url){
        throw_file("开始处理第{$i}篇文章： {$url}");
        $url_html = gethtml($url);
        if(($url_html =='error')){
            throw_file("没有获取到内容： {$url}");
        }

        phpQuery::newDocument($url_html);
        $content = pq('.article-content')->html();
        $title = pq('h1.article-title')->html();
        $content = trim($content);

        if($content){
            $rss.='<item><title>'.$title.'</title><link><![CDATA['.$url.']]></link><description><![CDATA['.$content.']]></description></item>';
            throw_file("获取文章成功！标题： $title");

        }else{
            throw_file("获取文章异常！地址： $url");
        }
        $i++;

        //print_r($rss);exit;

    }

    if($rss){

    	$html = '';
    	$html .= $header;
    	$html .= $rss;
    	$html .= $footer;

    	echo $html;

  //   	$md5 = md5($html);

  //   	/**
		//  * 启用etag后如果要启用session要这么处理：
		//  * session_cache_limiter('public');//设置session缓存
		//  * session_start();//读取session
		//  */
		// $ETag=$md5;
		// if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && ($_SERVER['HTTP_IF_NONE_MATCH']==$ETag) ){
		// 	header('HTTP/1.1 304 Not Modified'); //返回304，告诉浏览器调用缓存
		// 	exit();
		// }else{
		// 	header('Etag:'.$ETag);
		// 	file_put_contents($static_file,$str);
		// };

        // $rss_path = RSS_STATIC . $rss_conf['mark'].'.xml';
        // $rss_url = RSS_URL . $rss_conf['mark'].'.xml';
        // file_put_contents($rss_path, $header. $rss. $footer);

        // throw_file("RSS已生成！feed地址： {$rss_url}");

    }else{
        throw_file("获取文章规则异常！");
    }
}else{
    throw_file("json解析格式错误");
}

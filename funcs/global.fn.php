<?php
/**
 * Created by PhpStorm.
 * User: boxcore
 * Date: 17/3/8
 * Time: 上午9:58
 */

/**
 * @param        $url
 * @param null   $json
 * @param string $code
 * @return mixed|string
 */
function gethtml($url,$json=null,$code='UTF-8'){
    $args = array();
    if($json) $args = json_decode($json,true);
    $useragent = isset($args["useragent"]) ? $args["useragent"] : 'Mozilla/5.0';
    $timeout = isset($args["timeout"]) ? $args["timeout"] : 9000;
    $ch = curl_init();
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => $useragent,
        CURLOPT_TIMEOUT_MS => $timeout,
        CURLOPT_NOSIGNAL => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 1
    );
    if(isset($args["ip"])){
        $options[CURLOPT_HTTPHEADER] = array('CLIENT-IP:'.$args["ip"],'X-FORWARDED-FOR:'.$args["ip"]);
    }
    if (preg_match('/^https/',$url)){
        $options[CURLOPT_SSL_VERIFYHOST] = 2; // fixed php 5.4+ problem
        $options[CURLOPT_SSL_VERIFYPEER] = 0;
        //$options[CURLOPT_SSLVERSION] = 3;
        //curl_setopt($ch, CURLOPT_SSLVERSION, 3);
    }
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);
    if($code != 'UTF-8'){
        $data = iconv($code, "UTF-8", $data); 
    }
    
    $curl_errno = curl_errno($ch);
    curl_close($ch);
    if($curl_errno>0){
        var_dump($curl_errno);
        return 'error';
    }else{
        return $data;
    }
}

/**
 * 自定义抛出日志格式
 *
 * @author boxcore
 * @date   2015-01-31
 * @param  string     $str
 * @return string
 */
function throw_log($str, $is_return=false, $log_file=''){
    $s = '['.date('Y-m-d H:i:s').']';
    $s .= basename($_SERVER['SCRIPT_NAME']);
    $s .= ': ' . trim($str)."\n";

    if( isset($log_file) && $log_file && is_dir(dirname($log_file))){
        file_put_contents($log_file,$s,FILE_APPEND);
        return null;
    }

    if($is_return){
        return $s;
    }
    echo $s;
}

function throw_file($str){
    global $log_file;
    if(PHP_SAPI == 'cli'){
        return throw_log($str,false, $log_file);
    }
}

/**
 * 获取服务IP
 *
 * @date   2018-01-31
 * @return [type]     [description]
 */
function get_server_ip(){
    $serve_addr = null;
    if(isset($_SERVER['SERVER_ADDR'])){
        $serve_addr = $_SERVER['SERVER_ADDR'];
    }elseif( (PHP_SAPI == 'cli') && isset($_SERVER['SSH_CONNECTION']) ){
        $info = explode(" ", $_SERVER['SSH_CONNECTION'] );
        if($info[2]) $serve_addr = $info[2];
    } 

    return $serve_addr;
}

function gunzip($zipped) {
    $offset = 0;
    if (substr($zipped,0,2) == "\x1f\x8b") $offset = 2;
    if (substr($zipped,$offset,1) == "\x08")  {
        # file_put_contents("tmp.gz", substr($zipped, $offset - 2));
        return gzinflate(substr($zipped, $offset + 8));
    }
    return "Unknown Format";
}


/**
 * 数组关系映射
 * @param  [type] $data    [description]
 * @param  [type] $mapping [description]
 * @return [type]          [description]
 */
function array_mapping($data, $mapping){
    if(empty($data) || empty($mapping)) return false;

    $tmp = array();
    $map_cnt = count($mapping);

    foreach($data as $v){
        if($map_cnt == 1) $tmp[$v[$mapping[0]]] = $v;
        if($map_cnt == 2) $tmp[$v[$mapping[0]]][$v[$mapping[1]]] = $v;
        if($map_cnt == 3) $tmp[$v[$mapping[0]]][$v[$mapping[1]]][$v[$mapping[2]]] = $v;
        if($map_cnt == 4) $tmp[$v[$mapping[0]]][$v[$mapping[1]]][$v[$mapping[2]]][$v[$mapping[3]]] = $v;
    }

    return $tmp;
}

/**
 * 数组关系映射
 * @param  array $data    数据源
 * @param  array $mapping 关系映射键
 * @return array
 */
function array_mapping_str_old($data, $mapping){
    if(empty($data) || empty($mapping)) return false;

    $tmp = array();
    $map_cnt = count($mapping);

    foreach($data as $v){
        if($map_cnt == 1) $tmp[$v[$mapping[0]]] = $v;
        if($map_cnt == 2) $tmp[$v[$mapping[0]] . '||' . $v[$mapping[1]]] = $v;
        if($map_cnt == 3) $tmp[$v[$mapping[0]] . '||' . $v[$mapping[1]] . '||' . $v[$mapping[2]]] = $v;
        if($map_cnt == 4) $tmp[$v[$mapping[0]] . '||' . $v[$mapping[1]] . '||' . $v[$mapping[2]] . '||' . $v[$mapping[3]]] = $v;
    }

    return $tmp;
}

/**
 * 数组关系映射
 * @param  array $data    数据源
 * @param  array $mapping 关系映射键
 * @return array
 */
function array_mapping_str($data, $mapping, $mark = '||'){
    if( empty($data) ) return false;

    if(empty($mapping)){
        return $data;
    }

    $tmp = array();
    $map_cnt = count($mapping);

    foreach($data as $v){
        $ka = array();
        foreach($mapping as $vo){
            $ka[] = $v[$vo];
        }

        $ks = join($mark, $ka);

        $tmp[$ks] = $v;
    }

    return $tmp;
}

/**
 * 数组关系合并
 *
 * @param  array $data    数据源
 * @param  array $mapping 关系映射键
 * @return array 返回合并后的数组
 */
function array_mapping_combine($data, $mapping, $mark = '||', $mapping_filter = array()){
    if( empty($data) ) return false;

    if(empty($mapping)){
        return $data;
    }

    $tmp = array();

    foreach ($data as $v){
        $ka = array();
        foreach($mapping as $vo){
            $ka[] = $v[$vo];
        }
        $ks = join($mark, $ka);

        foreach($v as $ki=>$vi){
            if( !empty($mapping_filter) && in_array($ki,$mapping_filter) ) continue;
            if(!in_array($ki, $mapping) ) {
                $tmp[$ks][$ki] += $vi;
            }else{
                $tmp[$ks][$ki] = $vi;
            }
        }
    }

    return $tmp;
}

/**
 * 多数组合集获取 - 多个 三维数组 通过键值合并
 *
 * @date   2016-08-22
 * @return array
 */
function get_universe_set(){
    $arr = func_get_args();
    if(empty($arr))  return null;

    $data=array();
    foreach($arr as $v){
        if(empty($v) || ($v===null)) continue;

        foreach($v as $k1=>$v2){
            if( isset($data[$k1]) ){
                $data[$k1] = array_merge($data[$k1],$v2);
            }else{
                $data[$k1] = $v2;
            }
        }
    }

    return array_values($data);
}

/**
 * 多数组合集获取 - 多个 三维数组 通过键值合并(保留键值)
 *
 * @date   2016-08-22
 * @return array
 */
function get_universe_kset(){
    $arr = func_get_args();
    if(empty($arr))  return null;

    $data=array();
    foreach($arr as $v){
        if(empty($v) || ($v===null)) continue;

        foreach($v as $k1=>$v2){
            if( isset($data[$k1]) ){
                $data[$k1] = array_merge($data[$k1],$v2);
            }else{
                $data[$k1] = $v2;
            }
        }
    }

    return $data;
}



/**
 * 获取第一个集合+并集
 *
 * @date   2016-08-22
 * @return array
 */
function get_first_set(){
    $data = array();
    $arr = func_get_args();
    if(!empty($arr)){
        $c = count($arr);
        foreach($arr[0] as $k=>$v){
            $tmp = $v;
            $t = 1;
            for($t; $t<$c; ++$t){
                if( isset($arr[$t][$k]) ) $tmp = array_merge($tmp, $arr[$t][$k]);
            }
            $data[$k] = $tmp;

            unset($tmp);
        }
    }

    return $data;
}

/**
 * 一维数据集合并
 *
 * @date   2016-10-12
 * @return array
 */
function get_merge_set(){
    $data = array();
    $arr = func_get_args();
    if(!empty($arr)){
        foreach($arr as $k=>$v){
            if(!empty($v)){
                $data = array_merge($data,$v);
            }
        }
    }

    return $data;
}


/**
 * 获取快捷插入数据sql
 * @param $table
 * @param $data
 */
function get_insert_sql( $table, $data,$is_addslashes=1 ) {
    $insert_fileds = array();
    $insert_data   = array();
    foreach( $data as $field => $value ) {
        if($is_addslashes){
            $value = addslashes($value);
        }
        array_push( $insert_fileds, "`{$field}`" );
        array_push( $insert_data, sprintf( '"%s"', $value ) );
    }
    $insert_fileds = implode( ', ', $insert_fileds );
    $insert_data   = implode( ', ', $insert_data );
    return "INSERT INTO `{$table}` ({$insert_fileds}) values ({$insert_data})";
}

/**
 * 快捷更新表
 * @param $table
 * @param $data
 * @param $wheres
 */
function get_update_sql( $table, $data, $wheres = array(), $is_addslashes=1) {
    $update_data  = array();
    $update_where = array();
    foreach( $data as $field => $value ) {
        if($is_addslashes) $value = addslashes($value);
        array_push( $update_data, sprintf( '`%s` = "%s"', $field, $value ) );
    }
    $update_data  = implode( ', ', $update_data );
    
    if( ! empty( $wheres ) ) {
        foreach( $wheres as $field => $value ) {
            array_push( $update_where, sprintf( '`%s` = "%s"', $field, $value ) );
        }
        $update_where = 'WHERE ' . implode( ' AND ', $update_where );
    } else {
        $update_where = '';
    }
    $update = "UPDATE `{$table}` SET {$update_data} {$update_where}";
    return $update;
}

/**
 * 快捷查询
 * @param $table
 * @param $field 字段
 * @param $wheres
 */
function get_select_sql( $table, $wheres = array(), $field='*') {
    $select_where = array();
    
    if( ! empty( $wheres ) ) {
        foreach( $wheres as $f => $v ) {
            array_push( $select_where, sprintf( '`%s` = "%s"', $f, $v ) );
        }
        $select_where = 'WHERE ' . implode( ' AND ', $select_where );
    } else {
        $select_where = '';
    }
    $select = "SELECT {$field} FROM `{$table}` {$select_where}";
    return $select;
}


/**
 * 判断数据保存或插入
 *
 * @date   2016-07-18
 * @param  object     $db    数据库对象
 * @param  string     $table 表名称
 * @param  array      $data  单条数据
 * @param  array      $unque 查询唯一映射
 * @param  array      $skp   跳出特征符，如array('is_loc'=>2), 当获取到目标的数组中
 *                           含有$v['is_loc']= 2 的数据时会跳出更新操作
 */
function save_data_by_check_skp( $table='', $data=array(), $unque=array(), $skp=null, $auto_increment_field=null, $db=null){
    // 检查是否更新操作

    $rs = null;
    $unque_str = $where_up = array();
    foreach($unque as $v){
        $unque_str[] = "{$v} => {$data[$v]}";
        $where_up[$v] = $data[$v];
    }
    $unque_str = join(', ',$unque_str);


    $sql = get_select_sql($table, $where_up );
    $data_exist_row = get_line($sql);

    if(!empty($data_exist_row)){
        // 判断是否要跳过循环
        $skp_t=false;
        if($skp && !empty($skp)){
            foreach($skp as $k=>$v){
                if($data_exist_row[$k] == $v) $skp_t=1;
            }

            $print_str = "$table 表 $unque_str 不需要执行更新！";
        }

        if(!$skp_t){
            $rs = update( $table, $data, $where_up );
            $print_str = ($rs) ? "$table 表 $unque_str 更新成功！" : "$table 表 $unque_str 更新失败，请检查！";

            $rs = -1;
        }

    }else{
        // 是否自己计算自增
        if($auto_increment_field){
            $max_id = get_var("SELECT max(`{$auto_increment_field}`) from {$table}");
            if($max_id>0){
                $data[$auto_increment_field] = $max_id+1;
            }else{
                $data[$auto_increment_field] = 1;
            }
        }

        $rs = insert( $table, $data );
        $print_str = ($rs) ? "$table 表 $unque_str 添加成功！" : "$table 表 $unque_str 添加失败，请检查！";
    }

    throw_log($print_str);
    return $rs;

}

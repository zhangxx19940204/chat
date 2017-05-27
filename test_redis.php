<?php
    //连接本地的 Redis 服务
   $redis = new Redis();
   $con_status= $redis->connect('127.0.0.1', 6379);
   $redis->auth('zxx@123456');
   if ($con_status) {
     echo "Connection to server sucessfully<br/>";
     echo "Server is running: " . $redis->ping().'<br/>';
   }else{
     echo 'fail';
   }
// $redis->delete('zhangxx19940204@163.com');
// $redis->hmset('1',$hset);
// $redis->hset('1','birth','13323');
var_dump($redis->hmget('8',array('user_name')));
// var_dump($redis->hmget('tuntun',array('marry','apple')));
var_dump($redis->keys('*'));
// var_dump($redis->hgetall('1'));
// echo $redis->EXPIRE('1', 30);
// $cli = new Swoole\Coroutine\Http\Client('127.0.0.1', 80);
// $cli->setHeaders([
//     'Host' => "localhost",
//     "User-Agent" => 'Chrome/49.0.2587.3',
//     'Accept' => 'text/html,application/xhtml+xml,application/xml',
//     'Accept-Encoding' => 'gzip',
// ]);
// $cli->set([ 'timeout' => 1]);
// $cli->get('/index.php');
// echo $cli->body;
// $cli->close();

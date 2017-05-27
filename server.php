<?php
$server = new swoole_websocket_server("0.0.0.0", 9501);
//连接本地的 Redis 服务
// $redis = new Redis();
// $con_status= $redis->connect('127.0.0.1', 6379);
// if ($con_status) {
//  echo "Connection to server sucessfully<br/>";
//  echo "Server is running: " . $redis->ping().'<br/>';
// }else{
//  echo 'fail';
// }

$server->on('open', function (swoole_websocket_server $server, $request) {
    echo "server: handshake success with fd{$request->fd}\n";//$request->fd 是客户端id
    // 有新的客户端连接，返回当前用户列表
    $user_list = array();
    foreach($server->connections as $fd){
      // 加入redis判定，如果$fd存在redis中，则使用redis中存在的名字
      $redis = new Redis();
      $con_status= $redis->connect('127.0.0.1', 6379);
      $redis->auth('zxx@123456');
      $user_state = $redis->hmget($fd,array('user_name'))['user_name'];
      if ($user_state) {
        // 有设置用户名
        $text = array('fd'=>$fd,'user_name'=>$user_state);
      }else{
        // 未设置用户名
        $text = array('fd'=>$fd,'user_name'=>$fd);
      }
      $user_list[] = $text;
    }
    foreach($server->connections as $fd){

        $server->push($fd , json_encode(array('code'=>'1101','user_list'=>$user_list)));//返回用户利润表
    }

});

$server->on('message', function (swoole_websocket_server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    //$frame->fd 是客户端id，$frame->data是客户端发送的数据
    //服务端向客户端发送数据是用 $server->push( '客户端id' ,  '内容')

    // 将$frame->fd替换为变量,判断是否存在于redis中，存在用名称代替id
    $redis = new Redis();
    $con_status= $redis->connect('127.0.0.1', 6379);
    $redis->auth('zxx@123456');
    $user_state = $redis->hmget($frame->fd,array('user_name'))['user_name'];
    if ($user_state) {
      // 有设置用户名
      $framefd = $user_state;
    }else{
      // 未设置用户名
      $framefd = $frame->fd;
    }

    $data = explode(',',$frame->data);
    $text = array('code'=>'1103','msg_info'=>$data[1],'user_info'=>$framefd);
    switch ($data[0]) {
      case '0'://并未勾选任何用户和机器人，信息群发给用户
          $text['to_user'] = '所有用户';
          foreach($server->connections as $fd){
              $server->push($fd , json_encode($text));//循环广播
          }
          break;
      case 'jiqi'://勾选了机器人，发送给聊天机器人
          // 逻辑:向此用户，推送两条信息，一条:自己发送的 另一条:机器人的回复
          $text['to_user'] = '机器人';
          $server->push($frame->fd , json_encode($text));//推送给自己

          // 拿到机器人的回复，在推送给自己
          $text['user_info'] = '机器人';
          $text['to_user'] = $framefd;
          $text['msg_info'] = get_jiqi_reply($data[1]);
          $server->push($frame->fd , json_encode($text));//
          break;
      case 'user'://用户提交用户信息
          // 0：user 1：用户名  2：用户密码
          $redis = new Redis();
          $con_status= $redis->connect('127.0.0.1', 6379);
          $redis->auth('zxx@123456');
          $user_info = array('user_name'=>$data[1],'user_pwd'=>$data[2]);
          $redis->hmset($frame->fd,$user_info);
          // 用户提交账户信息保存到redis中成功
          $show_user = array('code'=>'1108','user_name'=>$data[1]);
          $server->push($frame->fd , json_encode($show_user));//将用户名更新到自己账户页面中展示
          //群发所有用户进行更改展示名称
          foreach($server->connections as $fd8){
              $server->push($fd8 , json_encode(array('code'=>'1109','user_name'=>$data[1],'fd'=>$frame->fd)));//循环广播
          }

          break;
      default://发给指定的用户
          // 原理:发送给指定用户的信息，同时推送给自己
          $user_state = $redis->hmget($data[0],array('user_name'))['user_name'];
          if ($user_state) {
            // 有设置用户名
            $framefd = $user_state;
          }else{
            // 未设置用户名
            $framefd = $data[0];
          }
          $text['to_user'] = $framefd;
          $server->push($frame->fd , json_encode($text));//推给自己
          $server->push($data[0] , json_encode($text));//推给指定用户
          break;
    }
});

$server->on('close', function (swoole_websocket_server $server, $fd) {
    echo "client {$fd} closed\n";
    //从redis中移除此用户
    $redis = new Redis();
    $con_status= $redis->connect('127.0.0.1', 6379);
    $redis->auth('zxx@123456');
    $redis->delete($fd);
    // 有客户离开，返回用户的标志
    foreach($server->connections as $fd1){
        if($fd1 == $fd){
          continue;
        }
        $server->push($fd1 , json_encode(array('code'=>'1102','user_info'=>$fd)));//循环广播
    }
});

$server->start();

function get_jiqi_reply($data){
  $res_json = file_get_contents('http://op.juhe.cn/robot/index?info='.urlencode($data).'&key=bd8cfd71b17cba73cbec17e4703d3ae4');
  $res_arr = json_decode($res_json,true);
  return $res_arr['result']['text'];
}

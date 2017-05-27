<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>千寻聊天室</title>

    <!-- Bootstrap -->
    <link href="https://cdn.bootcss.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.bootcss.com/layer/3.0.1/mobile/need/layer.min.css" rel="stylesheet">
    <script src="https://cdn.bootcss.com/jquery/2.2.4/jquery.min.js"></script>
    <script src="https://cdn.bootcss.com/jquery-cookie/1.4.1/jquery.cookie.js"></script>
    <script src="https://cdn.bootcss.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.bootcss.com/layer/3.0.1/layer.min.js"></script>
    <!-- <link rel="shortcut icon" href="favicon.ico"/> -->
    <!-- <link rel="bookmark" href="favicon.ico"/> -->
    <style type="text/css">
        .chat-body{
            overflow-y:scroll;
            height:555px;
            padding:0px;
        }

        .list-body{
            overflow-y:scroll;
            height:450px;
            padding:0px;
        }

        .msg-list-body{
            margin:8px;
        }

        .msg-wrap{
            margin-top: 0px;
            margin-bottom: 8px;
            padding: 0px;
        }

        .msg-content{
            margin-top: 14px;
            padding: 8px;
            padding-bottom: 4px;
            background-color:#f5f5f5;
            border:1px solid #ccc;
            border-radius: 4px;
            word-break:break-all;
        }

        .img-icon{
            width: 64px;
            height: 64px;
            border:2px solid #ccc;
            border-radius: 4px;
        }

        .msg-head{
            z-index:100;
        }

        .msg-name{
            margin-left: 8px;
        }

        .msg-time{
            margin-left: 8px;
        }

        .list-table{
            margin-top: -1px;
            margin-bottom: 0px;
        }

        .emotion-panel{
            position:fixed;
            display:none;
            z-index:200;
        }
    </style>
    <!-- Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="myModalLabel">填写用户信息</h4>
          </div>
          <div class="modal-body">
            <form>
              <div class="form-group">
                <label for="recipient-name" class="control-label">用户名:</label>
                <input type="text" class="form-control" id="user_name">
              </div>
              <div class="form-group">
                <label for="message-text" class="control-label">密码:</label>
                <input type="text" class="form-control" id="user_password">
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            <button type="button" class="btn btn-primary" id="save_user">保存</button>
          </div>
        </div>
      </div>
    </div>
    <script type="text/JavaScript">
      $(document).ready(function(){
        window.onbeforeunload = function(){
          return "确定离开此页面？";
        }
        //页面加载完毕弹出
        $('#myModal').modal({
          keyboard: false,
          backdrop:false
        });
      });
    </script>
    <script>
      var ws = new WebSocket("ws://139.196.223.114:9501");
      ws.onopen = function(){
          console.log("握手成功");
         //  ws.send('hello world!!!');
         jQuery('#send_chat_btn').click(function(){
           var msg_info = jQuery('#input-edit').val();
           if (msg_info == '') {
             alert('少年，至少写一点内容吧！');
             return false;
           }
           jQuery('#input-edit').val('');

           if (jQuery('input[name="user_only_id"]').is(":checked")) {
            //有选中其中一个用户（包括机器人）
            var msg_user = jQuery('input[name="user_only_id"]:checked').val();
           }else{
             //未选中如何的用户
             var msg_user = '0';//0:未选中任何用户，群发用户； jiqi:只发给机器人  ；其他：发送给指定用户了
           }
           var msg_info_arr = [];
           msg_info_arr.push(msg_user);
           msg_info_arr.push(msg_info.replace(/,/gm,'，'));
           console.log(msg_info_arr);
           ws.send(msg_info_arr);
         });

         // 用户点击保存
         jQuery('#save_user').click(function(){
          var user_name = jQuery('#user_name').val();
          var user_password = jQuery('#user_password').val();
          var user_info_arr = [];
          user_info_arr.push('user');
          user_info_arr.push(user_name.replace(/,/gm,'，'));
          user_info_arr.push(user_password.replace(/,/gm,'，'));
          ws.send(user_info_arr);
         });
      };
      ws.onmessage = function(e){
          // console.log(e.data);
          var obj = JSON.parse(e.data);

          // 若编码为1108，则为用户设置名称,更改指定用户的面板信息
          if (obj.code == '1108') {
            jQuery('#my-nickname').html('昵称：&nbsp;'+obj.user_name);//
          }

          // 若编码为1109，则为用户设置名称,更改所有用户列表中的显示名称
          if (obj.code == '1109') {
            jQuery("#current_user_list .user_only_id_"+obj.fd+" span").text(obj.user_name);
          }


          // 若编码为1101，则为新增用户时，返回列表
          if (obj.code == '1101') {
            // console.log(obj.user_list);
            var user_html = '<tr><td><input type="checkbox" name="user_only_id" value="jiqi" />&nbsp;聊天机器人</td></tr>';
            jQuery.each(obj.user_list,function(index, data){
              // 进行用户的拼接
              user_html += '<tr class="user_only_id_'+data.fd+'"><td><input type="checkbox" name="user_only_id" value="'+data.fd+'" />&nbsp;用户:<span>'+data.user_name+'</span></td></tr>';
            });
            jQuery('#current_user_list').html(user_html);//进行用户列表的填充
            jQuery('#current_user_num').html(obj.user_list.length);//当前用户数
            // 判定用户不允许选择多个
            jQuery("input[name='user_only_id']").click(function(){
              alert('仅支持群发和单用户发送,请不要多选');
            });

          }

          // 若编码为1102，则为用户离开
          if (obj.code == '1102') {
            // console.log(obj.user_info);
            jQuery("#current_user_list .user_only_id_"+obj.user_info).remove();
            var current_num = jQuery('#current_user_num').text();//当前用户数
            jQuery('#current_user_num').html(current_num - 1);
          }

          // 若编码为1103，则为用户发送信息
          if (obj.code == '1103') {
            // console.log(obj);
            var mydate = new Date();
            var t=mydate.toLocaleString();
            var msg_html = '<div class="clearfix msg-wrap">';
            msg_html += '<div class="msg-head">';
            msg_html += '<span class="msg-name label label-primary pull-left">';
            msg_html += '<span class="glyphicon glyphicon-user"></span>';
            msg_html += '&nbsp;用户:'+obj.user_info+'=>用户：'+obj.to_user;
            msg_html += '<span class="msg-time label label-default pull-left">';
            msg_html += '<span class="glyphicon glyphicon-time"></span>';
            msg_html += '&nbsp;'+t;
            msg_html += '</span>';
            msg_html += '</div>';
            msg_html += '<div class="msg-content">'+obj.msg_info+'</div>';
            msg_html += '</div>';
            jQuery("#msg_list_show").append(msg_html);
          }


      };

      ws.onerror = function(){
          console.log("error");
      };
      // 监听Socket的关闭
      ws.onclose = function(event) {
          console.log('Client notified socket has closed'+event);
      };
    </script>
</head>
<body>
<div class="container">
    <div class="row" style="margin-top:15px;">

        <!-- 聊天区 -->
        <div class="col-sm-8">
            <!-- 聊天内容 -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-earphone"></span>
                    &nbsp;聊天内容
                </div>
                <div class="panel-body chat-body">
                    <div class="msg-list-body" id="msg_list_show">

                    </div>
                </div>
            </div>

            <!-- 输入框 -->
            <div class="input-group input-group-lg">
                <span class="input-group-btn">
                    <button class="btn btn-default" id="emotion-btn" type="button">
                        <span class="glyphicon glyphicon-pencil" aria-hidden="true" style="width:24px;height:24px;"></span>
                    </button>
                </span>
                <input type="text" class="form-control" id="input-edit" placeholder="请输入聊天内容">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="button" id="send_chat_btn">
                        发送
                        <span class="glyphicon glyphicon-send"></span>
                    </button>
                </span>
            </div>
        </div>

        <!-- 个人信息 -->
        <div class="col-sm-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-user"></span>
                    &nbsp;个人信息
                </div>
                <div class="panel-body">
                    <div class="col-sm-9"><h5 id="my-nickname">昵称：还未设置</h5></div>
                    <div class="col-sm-3">
                        <button class="btn btn-default">修改</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 在线列表 -->
        <div class="col-sm-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-list"></span>
                    &nbsp;在线名单
                </div>
                <div class="panel-body list-body">
                    <table class="table table-hover list-table" id="current_user_list">

                    </table>
                </div>
                <div class="panel-footer" id="list-count">当前在线：<span id="current_user_num">0</span>&nbsp;人</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="login-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only"></span>
                </button>
                <h4 class="modal-title" id="myModalLabel">请设置聊天昵称</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-8 col-sm-push-2">
                        <div class="alert alert-danger" role="alert" id="nickname-error" style="display: none">
                            <span class="glyphicon glyphicon-remove"></span>
                            请填写昵称
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon">昵称</span>
                            <input type="text" id="nickname-edit" class="form-control" placeholder="请输入昵称">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">应用昵称</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>

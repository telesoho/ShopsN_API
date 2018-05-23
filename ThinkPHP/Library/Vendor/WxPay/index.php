<?php
$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
$dir = dirname($url);
?>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/> 
        <title>演示：微信JSAPI公众号支付</title>
            <meta name="keywords" content="微信JSAPI支付,微信公众号支付,微信支付回调" /> 
            <meta name="description" content="微信JSAPI公众号支付是素材火群主提供的，支付成功后跳转到订单详情页，里面是微信支付成功后回调的数据，由第三方微信支付平台定时请求获取。" /> 
            <style type="text/css">
                ul {
                    margin-left:10px;
                    margin-right:10px;
                    margin-top:10px;
                    padding: 0;
                }
                li {
                    width: 32%;
                    float: left;
                    margin: 0px;
                    margin-left:1%;
                    padding: 0px;
                    height: 100px;
                    display: inline;
                    line-height: 100px;
                    color: #fff;
                    font-size: x-large;
                    word-break:break-all;
                    word-wrap : break-word;
                    margin-bottom: 5px;
                }
                a {
                    -webkit-tap-highlight-color: rgba(0,0,0,0);
                    text-decoration:none;
                    color:#fff;
                }
                a:link{
                    -webkit-tap-highlight-color: rgba(0,0,0,0);
                    text-decoration:none;
                    color:#fff;
                }
                a:visited{
                    -webkit-tap-highlight-color: rgba(0,0,0,0);
                    text-decoration:none;
                    color:#fff;
                }
                a:hover{
                    -webkit-tap-highlight-color: rgba(0,0,0,0);
                    text-decoration:none;
                    color:#fff;
                }
                a:active{
                    -webkit-tap-highlight-color: rgba(0,0,0,0);
                    text-decoration:none;
                    color:#fff;
                }
            </style>
    </head>
    <body>
        <p style="text-align: center;color:red;font-size:20px;margin-top: 120px">请用微信浏览器打开以下链接</p>
        <div align="center">

            <ul>
                <li style="background-color:#FF7F24"><a href="jsapi.php">JSAPI支付</a></li>
         

            </ul>
             <p style="font-size:12px">
                复制该地址到微信：  <?php echo $dir."/jsapi.php";?>
            </p>
        </div>
    </body>
</html>


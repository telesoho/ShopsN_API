# 说明

ShopsN 分为五个端 网站、手机网站、微信、安卓、IOS


ShopsN是面向2020后的。所以在技术上比较超前。仅在web端保留了传统的开发方式。

移动端采用了先进的前后端分离（Vue）技术，放在网站内的手机网站（mobile）目录，是手机网站源代码编译后生成的，并不是源代码。放在moblie目录默认的编译代码，是我们的演示数据。

因此您需要下载手机网站源代码，进行编译，然后将生成的文件放入到mobile目录下(删除mobile内原有的老演示数据）。具体如何编译，请参考ShopsN手机H5端源代码目录的说明文档。

ShopsN接口端是对接app接口和H5及微信的，需要单独建一个虚拟主机站点。不要与网站共享代码。因为接口操作的结果是与网站共享数据库，读取写入结果到数据库。

Uploads.zip是演示图包，可以下载后放到网站根目录。
请注意网站务必放在根目录！

微信版在2.2版本正式加入ShopsN家族！

安卓和ios在各自文档不必细说了。

前后端分离技术高度适用于外包公司及中大公司技术开发，可以减轻很大的工作量。


公司官网www.shopsn.net 欢迎使用我们的电商系统，也欢迎定制开发！

ShopsN可免费商用，允许二次开发（包括允许外包公司代客户开发），但严禁未获得授权下擅自删除版权标志，或用于改名（包括修改代码）后侵犯著作权当做自己产品二次销售！否则发现即追责到底。

# API

api站顾名思义是专门为api接口服务的。之所以与www分离出来，原因是ShopsN设计理念是更加偏重于企业成长性需求，而不是一个难以扩展开发的小网店。这也是ShopsN与其他b2c商城系统的较大区别之一。 
ShopsN更加适合移动端扩展的开发需求。 

现在介绍一下api站点的配置内容。 

假设你的web站点是   web.yourdomain.com   web可随意根据需要改动，如shop、www等 

api站点是                    api.yourdomain.com   尽量取名叫api 吧 


api根目录\Application\Common\Conf 下有两个文件  config.php   db_config.php 

快速查找可按ctrl+F 配置方式如下： 
config.php 

第一处：

```php
define('__SERVER__', 'http://demo.shopsn.net'); 
```

这里将demo.shopsn.net改为web.yourdomain.com 

第二处：    //图片域名地址 

```php
    'img_url'            => 'http://demo.shopsn.net'
```
    这里将demo.shopsn.net改为web.yourdomain.com 

第三处： //同步跳转 

```php
 'return_url' => "http://api.shopsn.net/#/home",  
 ```
 这里将api.shopsn.net改为api.yourdomain.com 


db_config.php 这是配置数据库链接的 

```php
    'DB_TYPE' => 'mysql', //数据库类型 
    'DB_HOST' => '127.0.0.1', //数据库主机 
    'DB_NAME' => 'shopsn', //数据库名称 
    'DB_USER' => 'root',  //数据库用户名 
    'DB_PWD'  => '123456', //数据库密码 
    'DB_PORT' => '3306', //数据库端口 
    'DB_PREFIX' => 'db_', //数据库前缀 
    'DB_CHARSET'=> 'utf8', // 字符集 
    'DB_DEBUG'  => true, // 数据库调试模式 开启后可以记录SQL日志 
```

一般来说只需要改这几行黑体的。其他的没特殊情况不需要改。 


还有个地方不要忘记了 

api站根目录\Application\Home\Controller\CommonController.class.php 

CommonController.class.php修改方法 

文本编辑器搜索demo.shopsn.net即可查找到两处网址，进行替换为你的web.yourdomain.com站点名称即可，如下 

        
```sh
//加@符号curl就会把它当成是文件上传处 
curl_setopt( $ch,CURLOPT_URL,"http://web.yourdomain.com/index.php/Home/AppUpload/headerUpload" ); 

//加@符号curl就会把它当成是文件上传处 
curl_setopt( $ch,CURLOPT_URL,"http://web.yourdomain.com/index.php/Home/AppUpload/commentUpload" ); 
```


# 短信发送验证码：

api站根目录/Application/Home/ControllerCommonController.class.php文件内的send_msg方法按代码内的提示修改实际申请的短信账户名、密码、发送内容和短信请求路径

# 支付：

* api站根目录/rsa_private_key.pem为支付宝私钥
* api站根目录/rsa_public_key.pem为开放平台公钥

api站根目录\Application\Home\Controller\PayController.class.php文件为APP端支付控制器，包含支付宝支付和微信支付，按注释修改相应的信息。

api站根目录
\Application\Home\Controller\AlipayMobileController.class.php文件为手机移动网站支付控制器,根据自行注释修改支付配置


注意：尽量不要用记事本修改！这会产生bom头。关于这个bom头，几乎所有开发者都被坑过。很多代码会因为bom导致异常错误。而代码是没有任何问题的！建议用sublime webstorm等专业工具。 

当然，你要是已经踏进坑了。我也有办法。在2.2.1发布后，api站的代码下载目录内，我赠送大家一个小工具，名为killbom.php。只需要传到任意你需要清理bom的站点根目录，在浏览器执行即可。OK，bom被杀死了 ：）

请注意：web站点不需要额外配置。仅需放置于api站点下。

Apache 用户 将 .htaccess 文件置于网站根目录

IIS    用户将  web.config 文件置于网站根目录

Nginx  用户 打开 Nginx.txt  复制内容,然后打开nginx\conf\nginx.conf  文件, 复制进去!配置好域名 及目录

server_name:域名

root:目录
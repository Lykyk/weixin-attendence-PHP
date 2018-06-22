<?php
    /**
     * demo_simple.php
     * 简单接受用户消息并回复消息 DEMO
     *
     * wechat-php-sdk DEMO
     *
     * @author 		gaoming13 <gaoming13@yeah.net>
     * @link 		https://github.com/gaoming13/wechat-php-sdk
     * @link 		http://me.diary8.com/
     */

    require '../autoload.php';

    use Gaoming13\WechatPhpSdk\Wechat;

    $wechat = new Wechat(array(
        // 开发者中心-配置项-AppID(应用ID)
        'appId' 		=>	'your appId',
        // 开发者中心-配置项-服务器配置-Token(令牌)
        'token' 		=> 	'your token',
        // 开发者中心-配置项-服务器配置-EncodingAESKey(消息加解密密钥)
        // 可选: 消息加解密方式勾选 兼容模式 或 安全模式 需填写
        'encodingAESKey' =>	'your encodingAESKey'
    ));

    // 获取微信消息
    $msg = $wechat->serve();

    //随机产生六位数签到码Begin
    function randStr($len=6,$format='NUMBER') { 
        switch($format) { 
        case 'ALL':
        $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~'; break;
        case 'CHAR':
        $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-@#~'; break;
        case 'NUMBER':
        $chars='0123456789'; break;
        default :
        $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~'; 
        break;
        }
        mt_srand((double)microtime()*1000000*getmypid()); 
        $password="";
        while(strlen($password)<$len)
        $password.=substr($chars,(mt_rand()%strlen($chars)),1);
        return $password;
    } 
    //随机产生六位数签到码End


    $conn=mysql_connect("localhost","root","baixuewuyaak47") or die("数据库服务器连接错误".mysql_error());
    mysql_select_db("wx",$conn) or die("数据库访问错误".mysql_error());
    mysql_query("set character set utf8");
    mysql_query("set names utf8");


    if ($msg->MsgType == 'text') {
        $userMessage = $msg->Content;
        $userOpenid = $msg->FromUserName;
        $toUserMessage = '';
        $adminOpenid = "oT_d3096S2XEn34jDGUbbqRCf0ng";
        $acode = '';

        $userMessageExplode = explode(" ",$userMessage);
        $action = '';
        $info = '';

        if (count($userMessageExplode) != 2 && count($userMessageExplode) != 1 ) {
            $toUserMessage .= "一脸懵逼 1";
        } else {
            $action = $userMessageExplode[0];
            $info = $userMessageExplode[1];

            if ($action == 'BDXH') {
                $toUserMessage .= "绑定学号";
                $result = mysql_query("SELECT * FROM  `user` WHERE  `openid` =  '" . $userOpenid . "'");
                if (mysql_num_rows($result) != 0) {
                    $toUserMessage .= "\n\n此微信号已经绑定过学号";
                } else {
                    if ($info == null) {
                        $toUserMessage .= "\n未输入学号";
                    } else {
                        $result = mysql_query("SELECT * FROM  `student` WHERE  `id` =  '" . $info . "'");
                        // $result = mysql_query("SELECT * FROM  `student` WHERE  `id` =  '1516040835'");
                        if (mysql_num_rows($result) == 0) {
                            $toUserMessage .= "\n\n学生名单不存在此学号";
                        } else {
                            $result = mysql_query("INSERT INTO `user` (openid, id) VALUES ('" . $userOpenid . "', '" . $info . "')");
                            if($result) {
                                $toUserMessage .= "\n\n绑定学号成功";
                            } else {
                                $toUserMessage .= "\n\n绑定学号失败";
                            }
                        }
                    }
                }

            } else if($action == 'KSQD' && $userOpenid == $adminOpenid) {
                $toUserMessage .= "开始签到";
                $acode = randStr();
                $result = mysql_query("UPDATE `acode` SET `code`='" . $acode . "', `start_time` = CURRENT_TIMESTAMP WHERE 1");
                if($result) {
                    $toUserMessage .= "\n\n生成签到码成功";
                    $toUserMessage .= "\n" . $acode;
                } else {
                    $toUserMessage .= "\n\n生成签到码失败";
                }

            } else if($action == 'QD') {
                $toUserMessage .= "签到";
                $result = mysql_query("SELECT * FROM  `acode` WHERE `code` = " . $info);
                if (mysql_num_rows($result) == 0) {
                    $toUserMessage .= "\n\n签到码不正确";
                } else {
                    $result = mysql_query("INSERT INTO `attendence` (`openid`, `atime`) VALUES ('" . $userOpenid . "', CURRENT_TIMESTAMP)");
                    if($result) {
                        $toUserMessage .= "\n\n签到成功";
                    } else {
                        $result = mysql_query("SELECT * FROM  `attendence` WHERE  `openid` =  '" . $userOpenid . "'");
                        if (mysql_num_rows($result) != 0) {
                            $toUserMessage .= "\n\n你已经签到过";
                        } else {
                            $toUserMessage .= "\n\n签到失败";
                        }
                    }
                }

            } else if($action == 'QQ') {
                $toUserMessage .= "缺勤";
                $result = mysql_query("SELECT * FROM `student` WHERE `student`.`id` IN (SELECT `user`.`id` FROM `user` WHERE `user`.`openid` NOT IN (SELECT `openid` FROM `attendence`))");
                $absenceNum = mysql_num_rows($result);
                if ( $absenceNum == 0) {
                    $toUserMessage .= "\n\n没有缺勤";
                } else {
                    $toUserMessage .= "\n\n缺勤人数:". $absenceNum; 
                    while ($row = mysql_fetch_array($result)) {
                        $toUserMessage .= "\n";
                        $toUserMessage .= $row['id'] . " " . $row['name'];
                    }
                }
                

            } else if ($action == QCQD && $userOpenid == $adminOpenid) {
                $result = mysql_query("TRUNCATE TABLE  `attendence`");
                if($result) {
                    $toUserMessage .= "清楚签到记录成功";
                } else {
                    $toUserMessage .= "清楚签到记录失败";
                }

            } else {
                $toUserMessage .= "一脸懵逼 2";
            }
        }
    } else {
        $toUserMessage .= "只支持文本消息";
    }

    $wechat->reply($toUserMessage);

    mysql_close($conn);
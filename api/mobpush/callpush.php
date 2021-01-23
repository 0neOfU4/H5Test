<?php
/**
 *推送接口
 *  发送推送
 *  查询推送（根据batchId）
 *  查询推送（根据workno）
 * 推送统计接口
 *  查询推送统计（根据batchId）
 *  查询推送统计（根据workno）
 * 别名操作接口
 *  查询别名
 *  设置别名
 * 标签操作接口
 *  查询标签
 *  设置标签
 * 公共接口
 *  地理位置信息接口
 */
/**
 * 推送参数处理
 * type 1通知 2自定义
 * target 推送范围:1广播；2别名；3标签；4regid；5地理位置;6用户分群
 * plat 1 android 2 ios 如包含ios和android则为[1,2]
 * 数组 plats tags alias registrationIds androidContent
 * taskTime 格式 Y-m-d H:i:s
 */
include "push.php";
function pushOne()
{
    # 实例化
    $push = new Push("2cd1e0f7a9638", "c4a05e004d47383bdd576b76469a6d71");
    $content = $_GET["content"];
    $title = $_GET["title"];
    $aa = $_GET["alias"];
    $rr = $_GET["regid"];
    if (!isset($aa) && !isset($rr)){
        exit("-1|no target params!");
    }
    if (!isset($content)){
        exit("-1|no push content!");
    }
    if (!isset($title)){
        $title = "";
    }
    $params = array(
        'plats' => array(1),
        'appkey' => "2cd1e0f7a9638",
        # 设置推送范围
        'target' => 2,
        'content' => $content,
        'type' => 1,
//        'tags' => array(),
        'alias' => explode('|', $aa),
//        'registrationIds' => explode('|', $rr),
        # 设置Android定制信息
        'androidContent' => array($content),
        'androidTitle' => $title,
        'androidstyle' => 3,
        'androidVoice' => true,
        'androidShake' => true,
        'androidLight' => true,
        # 设置iOS定制信息
//        'iosProduction' => 1,
//        'title' => $title,
//        'subtitle' => '',
//        'sound' => 'default',
//        'badge' => 1,
//        'slientPush' => 1,
//        'contentAvailable' => 1,
//        'mutableContent' => 1,
        # 设置推送扩展信息
        'unlineTime' => 1,
//        'extras' => json_encode(array(            'tid' => 0,        )),
    );
    $res = $push->postPush($params, 1);
    error_log(json_encode($params)."\r\n", 3, "trace.log");
    print_r($res);
}

# 测试
pushOne();

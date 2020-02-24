<?php
/**
 * 阿里云OSS配置
 */
return [
    'KeyId'      => '',  //您的Access Key ID
    'KeySecret'  => '',  //您的Access Key Secret
    'Endpoint'   => 'http://oss-cn-beijing.aliyuncs.com',  //阿里云oss 外网地址endpoint（使用Cname时设置为Cname）
    'Bucket'     => '',  //Bucket名称
    'UseCname'   => false, //是否使用Cname
    'Cname'      => '',  //Cname名称，用于域名连接生成
];
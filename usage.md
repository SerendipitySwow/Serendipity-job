# 使用说明
## 环境

* PHP8 + Swow
* Nsq
* Mysql
* Redis

## 配置

* consumer.php  redis缓存的配置时间
  ```php
    'task_redis_consumer_time' => 60, //记录消息被消费成功redis缓存的时间
    'task_redis_cache_time' => 24 * 60 * 60, //记录统计的各个数据的缓存时间
  ```
* crontab.php  定时脚本
  ```php
    'enable' => env('ENABLE_CRONTAB', true), //是否开启定时脚本
    'crontab' => [
        // Callback类型定时任务（默认）Example
        (new Serendipity\Job\Crontab\Crontab())->setName('Foo')->setRule('*/5 * * * *')->setCallback([EchoCrontab::class, 'execute'])->setMemo('这是一个示例的定时任务'), //crontab
    ],
  ```
*  dingtalk.php  钉钉通知
  ```php
        'enabled' => env('DING_ENABLED', true),
        'token' => env('DING_TOKEN', ''),   //钉钉token
        'ssl_verify' => env('DING_SSL_VERIFY', true), //确认是否开启ssl验证
        'secret' => env('DING_SECRET', true), //secret 
        'options' => [
            'timeout' => env('DING_TIME_OUT', 2.0), //超时时间
        ],
  ```
* nsq.php nsq的配置文件

* redis.php redis的配置文件

* server.php server的配置信息
  ```php
    'server' => Server::class,
    'host' => env('SERVER_HOST', '127.0.0.1'), //host
    'type' => env('SERVER_TYPE', \Swow\Socket::TYPE_TCP), //tcp server
    'port' => (int) env('SERVER_PORT', 9502), //port
    'backlog' => (int) env('SERVER_BACKLOG', 8192), //backlog
    'multi' => (bool) env('SERVER_MULTI', true),
  ```
* signature.php 用于生成服务签名的文件  
  ```php
  //签名密钥
    'signatureSecret' => env('SIGNATURE_SECRET', ''),
    //签名key
    'signatureAppKey' => env('SIGNATURE_APP_KEY', ''),
    //签名有效期限秒,默认30天
    'timestampValidity' => 3600 * 24 * 60 * 30,
  ```  
* subscribers event事件  
  ```php
    UpdateWorkflowSubscriber::class,  //dag执行完成回调事件
    UpdateJobSubscriber::class,  //task任务回调事件
    CrontabRegisterSubscriber::class, //crontab注册事件
* providers.yaml server启动时需要启动的服务
  ```yaml
  Serendipity-Job:
  BootApp:
    - Serendipity\Job\Config\ConfigProvider
    - Serendipity\Job\Event\EventProvider
    - Serendipity\Job\Logger\LoggerProvider
    ## 服务启动一定要在最后
    - Serendipity\Job\Server\ServerProvider
  Manage-Job:
  BootApp:
    - Serendipity\Job\Config\ConfigProvider
    - Serendipity\Job\Logger\LoggerProvider
    - Serendipity\Job\Event\EventProvider
  ```
* 是否需要开启调试模式 配置.env文件里的DEBUG
##  启动server
```bash
php bin/serendipity-job serendipity-job:start
```
## 启动Job
```bash
php bin/serendipity-job job:start  --host=127.0.0.1 --port=9764
```
 #### 参数详解
   1. host server host监听地址,用于取消任务或者查卡任务详情
   2. port server port监听端口号
## 接口
[接口文档](API.md)

## 项目架构
[架构](Serendipity-Job.png)

## 测试

```bash
composer test
```
## 贡献

可以通过以下方式贡献：

1. 通过 [issue tracker](https://github.com/SerendipitySwow/Serendipity-job/issues) 提交 bug 或者建议给我们。
2. 回答 [issue tracker](https://github.com/SerendipitySwow/Serendipity-job/issues) 中的问题或者修复 bug。
3. 更新和完善文档，或者提交一些改进的代码给我们。

贡献没有什么特别的要求，只需要保证编码风格遵循 PSR2/PSR12，排版遵循 [中文文案排版指北](https://github.com/sparanoid/chinese-copywriting-guidelines)。

## License

MIT

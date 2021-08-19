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
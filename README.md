# 🚀Serendipity-Job  For 🏆Swow 任务平台

🚀 🏆  Task Platform Developed Based On Swow and Php8

## Features

```
1.支持Api投递任务.推送Nsq进行消费.(完成)
2.支持任务单个运行，并限制在时间内.超出限制时间抛异常(完成)
3.支持任务编排,单个任务限制时间.(完成)
4.支持任务编排支持事务.(暂不考虑)
5.支持重试机制,中间件(完成)
6.支持可视化查看任务信息.
7.支持后台配置任务.
8.支持定时任务Crontab.(完成)
9.支持任务图表(成功,失败,重试,超时,终止.)(未完成)
10.支持任务取消(完成)
11.签名验证(完成)
12.支持刷新应用签名(完成)
```

## 基于Vue、Vditor，所构建的在线 Markdown 编辑器，支持流程图、甘特图、时序图、任务列表、HTML 自动转换为 Markdown 等功能；🎉新增「所见即所得」编辑模式。

[地址](https://github.com/nicejade/markdown-online-editor)

## Please note

```
1.传递的任务Task必须实现JobInterface
2.不能包含资源对象.
3.Swow/channel push 和pop 都是毫秒.任务都可以支持毫秒.以后必须要注意.
4.Di主要使用Hyperf/Di
5.取消任务使用kill
6.crontab随消费进程一起启动
7.不建议使用多个消费者消费任务,心智负担很重,所以取消了多个消费者
8.限制任务执行时间通过channel 限制pop时间如果pop超时直接对执行任务的协程抛出异常.$coroutine->throw($exception);
[ERROR] Consumer failed to consume Consumer,reason: Channel wait producer failed, reason: Timed out for 5000 ms,file: /Users/heping/Serendipity-Job/src/Util/Waiter.php,line: 53
9.不建议同时启动dag和task两个消费。最好单独部署两个项目,server需要连接对应消费端启动的server查看任务详情或者取消任务.而且定时任务没有做集群处理。多台机器只能执行一个任务.
10.请尽量使用框架自带协程的创建方法,主要用日志上下文管理
Serendipity\Job\Util\Coroutine::create()
```

## 接口文档

见API.md

## TODO
* 计划开发后台
* 测试dag
* 完善文档
* SQL 
* 环境
## Come on!

## Thanks Hyperf.Swow!

## Required

````
1.PHP8
2.Nsq
3.redis
4.mysql
5.swow
````

## Usage
[使用说明](usage.md)

1.启动Serendipity-Job Server.

````bash
 php bin/serendipity-job serendipity-job:start
````

2.启动Job 进行任务消费

```bash
php bin/serendipity-job manage-job:start --type=task  --host=127.0.0.1 --port=9764
```
#### 参数详解
1. type 任务类型task或者dag
2. host server host监听地址,用于取消任务或者查卡任务详情
3. port server port监听端口号

3.配置Crontab

```php
 (new Serendipity\Job\Crontab\Crontab())->setName('Foo')->setRule('*/5 * * * *')->setCallback([EchoCrontab::class, 'execute'])->setMemo('这是一个示例的定时任务'),
```

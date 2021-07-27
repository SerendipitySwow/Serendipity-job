# Serendipity-Job  For Swow 任务平台

Run into the beauty of PHP8 and Swow

## Features

```
1.支持Api投递任务.推送Nsq进行消费.(完成)
2.支持任务单个运行，并限制在时间内.(完成)
3.支持任务编排,单个任务限制时间.(完成)
4.支持任务编排支持事务.
5.支持重试机制,中间件(完成)
6.支持可视化查看任务信息.
7.支持后台配置任务.
8.支持定时任务Crontab.(完成)
9.支持任务图表(成功,失败,重试,超时,终止.)
10.支持任务取消(完成)
11.签名验证(完成)
12.支持刷新应用签名(完成)
```

## Please note
```
1.传递的任务Task必须实现JobInterface
2.不能包含资源对象.
3.Swow/channel push 和pop 都是毫秒.任务都可以支持毫秒.以后必须要注意.
4.Di主要使用Hyperf/Di
5.取消任务使用kill
6.crontab随消费进程一起启动
```

## TODO
* 测试任务的相关接口(查看任务详情,取消任务,api推送任务,刷新应用接口)
* 测试job的相关接口(取消任务,任务详情)
* 完善api.md
* 计划开发后台
## Come on!
## Thanks Hyperf.!
## Required
````
1.PHP8
2.Nsq
3.redis
4.mysql
5.swow
````
## Usage
1.启动Serendipity-Job Server.
````bash
 php bin/serendipity-job serendipity-job:start
 
````
2.启动Job 进行任务消费
```bash
php bin/serendipity-job manage-job:start --type=task --limit=1 --host=127.0.0.1 --por
t=9764
```
3.配置Crontab
```php
 (new Serendipity\Job\Crontab\Crontab())->setName('Foo')->setRule('*/5 * * * *')->setCallback([EchoCrontab::class, 'execute'])->setMemo('这是一个示例的定时任务'),
```

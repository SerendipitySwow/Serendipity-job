# Serendipity-Job  For Swow 任务平台

Run into the beauty of PHP8 and Swow

## Features

```
1.支持Api投递任务.推送Nsq进行消费.(完成)
2.支持任务单个运行，并限制在时间内.(完成)
3.支持任务编排,单个任务限制时间.
4.支持任务编排支持事务.
5.支持重试机制,中间件
6.支持可视化查看任务信息.
7.支持后台配置任务.
8.支持定时任务.
9.支持任务图表(成功,失败,重试,超时,终止.)
```

## TODO

```
1.MySQLPool
2.RedisPool
3.EventDispatcher
4.SwowServer
```

## Please note
```
1.传递的任务Task必须实现JobInterface
2.不能包含资源对象.
```
## Come on!

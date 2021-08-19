# 系统环境

需要PHP8+、MySQL,Redis,Nsq,Swow

# 接口鉴权
部分接口需要签名验证,需要添加header头
```
nonce:nm33An1FX22SAjZK
signature:ZjUyZTE3M2RkMmUyYzY5MTEyYzRhNTFiZmFkOWFjYjZlZGZhMmJkZGUwZTRjYmViZjVkMWExNmQ4M2FiYjQ0Yw==
app_key:E3F5l1uKFcXUe1gd
payload:57bcd60eefcac9701fd2407080a5a7b0
timestamps:1627371056
```
代码
```php
$timestamp = $request->getHeaderLine('timestamps') ?? '';
$nonce = $request->getHeaderLine('nonce') ?? '';
$payload = $request->getHeaderLine('payload') ?? '';
$appKey = $request->getHeaderLine('app_key') ?? '';
$signature = $request->getHeaderLine('signature') ?? '';
```
## 接口地址

`url`：http://127.0.0.1:9502

## 头部信息

> signature不参与签名

| 参数名     | 类型     | 是否必填 | 默认值 | 说明      |
|---------|--------|------|-----|---------|
| app_key | string | 是    | -   | APP KEY |
| nonce | string | 是    | -   | 随机字符串 |
| timestamps | string | 是    | -   | 当前时间：2021-03-04 11:42:36 |
| signature | string | 是    | -   | 签名信息 |
| payload | string | 是    |   | payload） |


# 接口说明

> 系统将使用POST方法将数据{data:xxxx}提交到接口地址,接口返回success字符串表示执行成功,在提交数据的同时系统会使用下面接口鉴权的方法提交头部信息，以便于接入端校验请求是否来源于平台。

## 创建应用

`uri`:/application/create

### 接口参数

| 参数名     | 类型     | 是否必填 | 默认值 | 说明      |
|---------|--------|------|-----|---------|
| appName | string | 是    | -   | 任务名称 |
| linkUrl | string | 是    | -   | 接口地址 |
| step | int | 否    | -   | 重试间隔。最大值为3600 |
| retryTotal | int | 否    | -   | 重试次数。最大值为10 |
| remark | string | 否    |  当前时间   | 任务描述|

> 任务重试时间分别为执行结束后的5秒 10秒 15秒,最大值3600。
```json
{
    "appName": "帅的批爆1",
    "step": 10,
    "retryTotal": 0,
    "linkUrl": "http://127.0.0.1",
    "remark": "呵呵"
}
```
### 返回结果

```json
{
    "code": 0,
    "msg": "Ok!",
    "data": {
        "nonce": "S5D7zmW5HNnSiqf0",
        "timestamps": "1626418779",
        "signature": "NTBhNmIyYTRkZjFkODM4MDIyYTYzMzllMDIwN2QyN2MyYjNiYzg3YjM0ODc1YWNiZWZlOGQxZGU0MmE4NzgxMg==",
        "appKey": "wWt1aSZj2KXMO7fE",
        "payload": "f3c5fa5b2b4045bcb1132ddece49c0f4"
    }
}
```

## 投递任务

`uri`:/task/create

### 接口参数

| 参数名     | 类型     | 是否必填 | 默认值 | 说明      |
|---------|--------|------|-----|---------|
| taskNo | string | 是    | -   | 任务编号 |
| runtime | int | 否    | -   | 执行时间，示例 2021-03-05 12:00:00 |
| content | string | 是    | -   | 任务内容(JSON 字符串) |
| name | string | 是    | -   | 任务名称 |
| timeout | int | 是    | -   | 任务的执行限制时间(单位是ms), |

```json
{
    "taskNo": "taskNo82",
    "content": {
        "class": "\\Serendipity\\Job\\Job\\SimpleJob",
        "_params": {
            "startDate": "2021-07-27 17:50:50",
            "endDate": "2021-07-27 17:50:50"
        }
    },
    "timeout": 60000,
    "name": "name",
    "runtime": "2021-07-27 17:30:30"
}
```
### 返回结果

```json
{
    "code": 0,
    "msg": "ok!",
    "data": {
        "taskId": 81
    }
}
```


## 取消任务

`uri`:/task/cancel

### 接口参数

| 参数名     | 类型     | 是否必填 | 默认值 | 说明      |
|---------|--------|------|-----|---------|
| coroutine_id | int | 是    | -   | 任务运行的协程ID |
| id | int | 是    | -   | 任务ID |

> 程序会尽量拦截任务,不保证拦截成功率(越早拦截成功率越高)！
```json
{
    "coroutine_id":6,
    "id":18
}
```
### 返回结果

```json
{
    "code": 1,
    "msg": "Unknown!",
    "data": []
}
```

## 任务详情

`uri`:/task/detail

### 接口参数

| 参数名     | 类型     | 是否必填 | 默认值 | 说明      |
|---------|--------|------|-----|---------|
| coroutine_id | int | 是    | -   | 任务执行的协程ID |

### 返回结果

```json
{
    "code": 0,
    "msg": "ok!",
    "data": {
        "state": "waiting",
        "trace_list": "[{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/src\\/Job\\/SimpleJob.php\",\"line\":37,\"function\":\"sleep\",\"args\":[20]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/src\\/Nsq\\/Consumer\\/TaskConsumer.php\",\"line\":125,\"function\":\"handle\",\"class\":\"Serendipity\\\\Job\\\\Job\\\\SimpleJob\",\"object\":{\"identity\":81,\"timeout\":60000,\"retryTimes\":1,\"name\":\"name\",\"step\":10},\"type\":\"->\",\"args\":[]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/vendor\\/hyperf\\/utils\\/src\\/Pipeline.php\",\"line\":104,\"function\":\"Serendipity\\\\Job\\\\Nsq\\\\Consumer\\\\{closure}\",\"class\":\"Serendipity\\\\Job\\\\Nsq\\\\Consumer\\\\TaskConsumer\",\"object\":{},\"type\":\"->\",\"args\":[{\"identity\":81,\"timeout\":60000,\"retryTimes\":1,\"name\":\"name\",\"step\":10}]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/src\\/Job\\/JobMiddleware.php\",\"line\":18,\"function\":\"Hyperf\\\\Utils\\\\{closure}\",\"class\":\"Hyperf\\\\Utils\\\\Pipeline\",\"type\":\"::\",\"args\":[{\"identity\":81,\"timeout\":60000,\"retryTimes\":1,\"name\":\"name\",\"step\":10}]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/vendor\\/hyperf\\/utils\\/src\\/Pipeline.php\",\"line\":137,\"function\":\"handle\",\"class\":\"Serendipity\\\\Job\\\\Job\\\\JobMiddleware\",\"object\":{},\"type\":\"->\",\"args\":[{\"identity\":81,\"timeout\":60000,\"retryTimes\":1,\"name\":\"name\",\"step\":10},{}]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/vendor\\/hyperf\\/utils\\/src\\/Pipeline.php\",\"line\":95,\"function\":\"Hyperf\\\\Utils\\\\{closure}\",\"class\":\"Hyperf\\\\Utils\\\\Pipeline\",\"object\":{},\"type\":\"->\",\"args\":[{\"identity\":81,\"timeout\":60000,\"retryTimes\":1,\"name\":\"name\",\"step\":10}]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/src\\/Nsq\\/Consumer\\/TaskConsumer.php\",\"line\":126,\"function\":\"then\",\"class\":\"Hyperf\\\\Utils\\\\Pipeline\",\"object\":{},\"type\":\"->\",\"args\":[{}]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/src\\/Nsq\\/Consumer\\/TaskConsumer.php\",\"line\":67,\"function\":\"handle\",\"class\":\"Serendipity\\\\Job\\\\Nsq\\\\Consumer\\\\TaskConsumer\",\"object\":{},\"type\":\"->\",\"args\":[{\"identity\":81,\"timeout\":60000,\"retryTimes\":1,\"name\":\"name\",\"step\":10}]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/src\\/Util\\/Waiter.php\",\"line\":44,\"function\":\"Serendipity\\\\Job\\\\Nsq\\\\Consumer\\\\{closure}\",\"class\":\"Serendipity\\\\Job\\\\Nsq\\\\Consumer\\\\TaskConsumer\",\"object\":{},\"type\":\"->\",\"args\":[]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/vendor\\/hyperf\\/utils\\/src\\/Functions.php\",\"line\":271,\"function\":\"Serendipity\\\\Job\\\\Util\\\\{closure}\",\"class\":\"Serendipity\\\\Job\\\\Util\\\\Waiter\",\"object\":{},\"type\":\"->\",\"args\":[]},{\"file\":\"\\/Users\\/heping\\/Serendipity-Job\\/src\\/Util\\/Coroutine.php\",\"line\":60,\"function\":\"call\",\"args\":[{}]},{\"function\":\"Serendipity\\\\Job\\\\Util\\\\{closure}\",\"class\":\"Serendipity\\\\Job\\\\Util\\\\Coroutine\",\"type\":\"::\",\"args\":[]}]",
        "executed_file_name": "/Users/heping/Serendipity-Job/src/Job/SimpleJob.php",
        "executed_function_name": "sleep",
        "executed_function_line": 37,
        "vars": [],
        "round": 509,
        "elapsed": 9805
    }
}
```

## 投递任务

`uri`:/nsq/publish

### 接口参数

| 参数名     | 类型     | 是否必填 | 默认值 | 说明      |
|---------|--------|------|-----|---------|
| task_id | int | 是    | -   | 任务ID |
```json
{
    "task_id": 1
}
```
### 返回结果

```json
{
    "code": 0,
    "msg": "ok!",
    "data": {
        
    }
}
```

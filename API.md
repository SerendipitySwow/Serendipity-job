# 系统环境

需要PHP8+、MySQL,Redis,Nsq,Swow

# 接口鉴权

## 接口地址

`url`：http://host:port

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

### 返回结果

```
{
    "code": 1,
    "msg": "请勿重复提交!",
    "data": []
}
```

> 你可以使用taskId查询任务执行结果！

## 取消任务

`uri`:/task/cancel

### 接口参数

| 参数名     | 类型     | 是否必填 | 默认值 | 说明      |
|---------|--------|------|-----|---------|
| coroutine_id | int | 是    | -   | 任务运行的协程ID |

> 程序会尽量拦截任务,不保证拦截成功率(越早拦截成功率越高)！

### 返回结果

```
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

```
{
    "code": 0,
    "msg": "ok!",
    "data": {
        "state": null,
        "trace_list": "null",
        "executed_file_name": null,
        "executed_function_name": null,
        "executed_function_line": null,
        "vars": null,
        "round": null,
        "elapsed": null
    }
}
```

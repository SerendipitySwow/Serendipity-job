# 数据表介绍
### 1.workflow表 任务编排信息

### 2.vertex_edge表 任务编排ID和task关联关系

# 编排流程图
 ```mermaid
graph TD;
    A --> B
    A --> C
    A --> D
    D --> G
    C --> G
    C --> F
    B --> F
    B --> E
    B --> H
    H --> I
    E --> I
    F --> I
    G --> I
```  
假设我们有一系列任务，拓扑结构如上图所示，顶点代表任务，边缘代表依赖关系。(A完成后才能完成B、C、D，B完成后才能完成H、E、F...最后完成I)

## 每个任务必须实现DagInterface

```php
declare(strict_types=1);

namespace SwowCloud\Job\Dag\Task;

use SwowCloud\Job\Contract\DagInterface;
use SwowCloud\Job\Kernel\Concurrent\ConcurrentMySQLPattern;

class Task1 implements DagInterface
{
    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @param array $results 返回之前执行任务的结果集
     * {@inheritDoc}
     */
    public function run(array $results): int | bool
    {
        echo "Task1::run()\n";

        return true;
    }

    public function isNext(): bool
    {
        return true;
    }

    public function getIdentity(): int | string
    {
        return 1;
    }

    public function getTimeout(): int
    {
        return 5;
    }

    public function runConcurrentMySQLPattern(ConcurrentMySQLPattern $pattern): mixed
    {
    }
}

```
## 用数据存储Task类
```json
{
  "class": "Serendipity\\Job\\Dag\\Task\\Task1",
  "params": {
    "startDate": "2021-06-09",
    "endDate": "2021-06-19"
  }
 }
```
## 手动停止任务或者根据之前执行任务的结果集停止继续执行
```php
throw new DagException('never done!');
```

# 启动dag脚本命令
```bash
php bin/job dag:start --host=127.0.0.1 --port=9764
```
# 投递任务编排见API.md





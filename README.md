# rate-limit

> 适配 hyperf 框架的请求频率限流器，基于 Hyperf\Redis\Redis 实现

## 安装

```shell
composer require bud/rate-limit
```

## 注解使用

> 以下代码仅做用法展示

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use Bud\RateLimit\Annotation\RateLimitAnnotation;

#[AutoController(prefix: "test")]
class TestController
{
    /**
     * 五秒内仅允许请求一次
     */
    #[RateLimitAnnotation('test', 1, 5)]
    public function index()
    {
        return [
            'name' => 'rate-limit_annotation'
        ];
    }
    /**
     * 同id的记录一分钟仅允许修改一次
     */
    #[RateLimitAnnotation('test:{id}', 1, 60)]
    public function update(int $id)
    {
        return [
            'name' => 'rate-limit_annotation'
        ];
    }
     
    public function search(RequestInterface $request)
    {
        return $this->getList($request->query());
    }
    
    /**
     * 根据请求参数中的name字段值进行限流，当第一参数中存在占位符时第四参数无效
     */
     #[RateLimitAnnotation('name', 1, 60, '{data}')]
    protected function getList(array $data)
    {
        return $data;
    }
}
```

## 静态方法使用

```
    /**
     * @param string $key 限流键
     * @param int $unit_time 单位时间 默认1分钟
     * @param int $max_number 最大访问次数 默认60次，即一秒一次
    */
    \Bud\RateLimit\RateLimit::checkLimit(string $key, int $unit_time = 60, int $max_number = 60)
```

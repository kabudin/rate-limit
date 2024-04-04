# rate-limit

> 适配 hyperf 框架的请求频率限流器，基于 Hyperf\Redis\Redis 实现

## 安装

```shell
composer require Zeno/rate-limit
```

## 注解使用

> 以下代码仅做用法展示

```php
<?php
declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use Zeno\RateLimit\Annotation\RateLimitAnnotation;

#[AutoController(prefix: "test")]
class TestController
{
    /**
     * 20秒内仅允许请求一次
     */
    #[RequestMapping(path: "", methods: "get"),RateLimitAnnotation('test', 20, 1)]
    public function index()
    {
        return [
            'key' => 'rate_limit:App\Controller\IndexController:index:test'
        ];
    }

    /**
     * 相同路径参数一分钟仅允许访问一次
     */
    #[RequestMapping(path: "{id}", methods: "get"),RateLimitAnnotation('test:{id}', 60, 1)]
    public function info(int $id)
    {
        return [
            'key' => "rate_limit:App\Controller\IndexController:info:test:$id"
        ];
    }
}
```

## 静态方法使用

```php
    /**
     * @param string $key 限流键
     * @param int $unit_time 单位时间 默认1分钟
     * @param int $max_number 最大访问次数 默认60次，即一秒一次
    */
    \Zeno\RateLimit\RateLimit::checkLimit(string $key, int $unit_time = 60, int $max_number = 60)
```

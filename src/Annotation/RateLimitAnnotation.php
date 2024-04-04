<?php
declare(strict_types=1);

namespace Zeno\RateLimit\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
class RateLimitAnnotation extends AbstractAnnotation
{
    /**
     * 基于redis的请求频率限制
     * @param string $key 限流键。支持{}占位符，从入参列表中检索值，用于根据参数值动态限流
     * @param int|string $unit_time 单位时间 默认1分钟。为string时会调用当前类对应的方法，必须返回int类型
     * @param int|string $max_number 最大访问次数 默认60次，即一秒一次。为string时会调用当前类对应的方法，必须返回int类型
     */
    public function __construct(
        public string       $key,
        public int|string   $unit_time = 60,
        public int|string   $max_number = 60
    )
    {
    }
}

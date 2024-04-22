<?php
declare(strict_types=1);

namespace Bud\RateLimit;

use Bud\RateLimit\Aspect\RateLimitAnnotationAspect;
use Bud\RateLimit\Exception\ReteLimitException;
use Hyperf\Context\ApplicationContext;

class RateLimit
{
    /**
     * redis限流
     * @param string $key 限流键
     * @param int $unit_time 单位时间(秒)默认(60)秒
     * @param int $max_number 最大访问次数默认60次，即一秒一次
     * @return void
     */
    public static function checkLimit(string $key, int $unit_time = 60, int $max_number = 60)
    {
        $source = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
        if ($source['class'] != RateLimitAnnotationAspect::class)
            $key = "rate_limit:{$source['class']}:{$source['function']}:$key";
        $redis = ApplicationContext::getContainer()->get(\Hyperf\Redis\Redis::class);
        // 检查键是否存在
        if ($redis->exists($key)) {
            // 键存在，获取当前访问次数并自增计数器
            $currentCount = (int)$redis->get($key);
            $redis->incrby($key, 1); // 自增计数器
        } else {
            // 键不存在，创建新的计数器并设置过期时间
            $redis->setex($key, $unit_time, 1); // 设置键的过期时间为当前时间加上时间窗口
            $currentCount = 0; // 初始值为1，表示第一次访问
        }
        // 检查是否超过了限制次数
        if ($currentCount >= $max_number) {
            throw new ReteLimitException('系统繁忙！请稍后重试', 429);
        }
    }
}

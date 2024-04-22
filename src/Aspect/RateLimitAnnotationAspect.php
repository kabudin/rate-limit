<?php
declare(strict_types=1);

namespace Bud\RateLimit\Aspect;

use Bud\RateLimit\Annotation\RateLimitAnnotation;
use Bud\RateLimit\RateLimit;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Bud\RateLimit\Exception\ReteLimitException;
use Hyperf\Di\ReflectionManager;
use function Hyperf\Support\make;

class RateLimitAnnotationAspect extends AbstractAspect
{
    public array $annotations = [RateLimitAnnotation::class];

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws \Hyperf\Di\Exception\Exception
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $className = $proceedingJoinPoint->className;
        /** @var RateLimitAnnotation $rateLimit */
        if (isset($proceedingJoinPoint->getAnnotationMetadata()->method[RateLimitAnnotation::class])) {
            $rateLimit = $proceedingJoinPoint->getAnnotationMetadata()->method[RateLimitAnnotation::class];
        }
        $unit_time = $this->parseUnitTime($className, $rateLimit->unit_time);
        $max_number = $this->parseMaxNumber($className, $rateLimit->max_number);
        $prefix = "rate_limit:$className:$proceedingJoinPoint->methodName";
        $key = $this->parseKey($prefix, $rateLimit->key, $proceedingJoinPoint->arguments['keys']);
        // 检查是否超过了限制次数
        RateLimit::checkLimit($key, $unit_time, $max_number);
        return $proceedingJoinPoint->process();
    }

    /**
     * 解析限流键
     * @param string $prefix
     * @param string $key
     * @param array $params
     * @return string
     */
    private function parseKey(string $prefix, string $key, array $params): string
    {
        if (str_contains($key, '{') && str_contains($key, '}')) {
            $str = preg_replace('/^{|}$/', '', $key);
            foreach ($params as $k => $v) {
                $placeholder = '{' . $k . '}';
                if (is_int($v) || is_string($v))
                    $key = str_replace($placeholder, (string)$v, $key);
            }
            $key = preg_replace('/^{|}$/', '', $key);
            if ($key == $str) throw new ReteLimitException('Invalid rate limit dynamic parameter', 500);
            return "$prefix:$key";
        }
        if (!empty($params)) {
            if (!is_string($params[$key]) && !is_int($params[$key]))
                throw new ReteLimitException('The dynamic current limiting value must be of type int or string', 500);
            return "$prefix:$key:$params[$key]";
        }
        return "$prefix:$key";
    }

    /**
     * 解析单位时间
     * @param string $className
     * @param int|string $unit_time
     * @return int
     */
    private function parseUnitTime(string $className, int|string $unit_time): int
    {
        if (is_string($unit_time)) {
            $method = $unit_time;
            try {
                $reflectionClass = ReflectionManager::reflectClass($className);
                $reflectionMethod = $reflectionClass->getMethod($method);
                if ($reflectionMethod->isPublic()) {
                    if ($reflectionMethod->isStatic()) {
                        $unit_time = $className::$method();
                    } else {
                        $unit_time = make($className)->{$method}();
                    }
                    if (is_int($unit_time)) {
                        return $unit_time;
                    }
                    throw new ReteLimitException('The RateLimit unit_time method must return an int type', 500);
                }
                throw new ReteLimitException('RateLimit unit_time method is not public', 500);
            } catch (\ReflectionException $e) {
                throw new ReteLimitException('Resolve RateLimit unit_time failed：' . $e->getMessage(), 500);
            }
        }
        return $unit_time;
    }

    /**
     * 解析最大次数
     * @param string $className
     * @param int|string $max_number
     * @return int
     */
    private function parseMaxNumber(string $className, int|string $max_number): int
    {
        if (is_string($max_number)) {
            $method = $max_number;
            try {
                $reflectionClass = ReflectionManager::reflectClass($className);
                $reflectionMethod = $reflectionClass->getMethod($method);
                if ($reflectionMethod->isPublic()) {
                    if ($reflectionMethod->isStatic()) {
                        $max_number = $className::$method();
                    } else {
                        $max_number = make($className)->{$method}();
                    }
                    if (is_int($max_number)) {
                        return $max_number;
                    }
                    throw new ReteLimitException('The RateLimit max_number method must return an int type', 500);
                }
                throw new ReteLimitException('RateLimit max_number method is not public', 500);
            } catch (\ReflectionException $e) {
                throw new ReteLimitException('Resolve RateLimit max_number failed：' . $e->getMessage(), 500);
            }
        }
        return $max_number;
    }
}

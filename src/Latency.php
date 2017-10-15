<?php
namespace PromSR7;

use Prometheus\CollectorRegistry;
use Prometheus\Histogram;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Latency
{
    /**
     * @var Histogram
     */
    private $latencyHistogram;

    public function __construct(
        CollectorRegistry $collectorRegistry,
        string $namespace,
        array $buckets = [0.005, 0.01, 0.05, 0.10, 0.25, 0.40, 0.70, 1]
    ) {
        $this->latencyHistogram = $collectorRegistry->getOrRegisterHistogram(
            $namespace,
            'request_duration_seconds',
            'The duration of requests, in seconds',
            ['path'],
            $buckets
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) {
        $startTime = microtime(true);
        $response = $next($request, $response);

        $this->latencyHistogram->observe(
            microtime(true) - $startTime,
            [$request->getUri()->getPath()]
        );

        return $response;
    }
}

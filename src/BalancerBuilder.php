<?php

namespace Cmp\FeatureBalancer;

use Cmp\FeatureBalancer\Config\Identifier;
use Cmp\FeatureBalancer\Decorator\ExceptionSilencerDecorator;
use Cmp\FeatureBalancer\Decorator\LoggerDecorator;
use Cmp\FeatureBalancer\Decorator\MonitoringDecorator;
use Cmp\Monitoring\Monitor;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class BalancerBuilder
{
    /**
     * @var Monitor|null
     */
    private $monitor;

    /**
     * @var string|null
     */
    private $metric;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var string|null
     */
    private $logLevel;

    /**
     * @var bool
     */
    private $exceptions = true;

    /**
     * @param array $config
     *
     * @return BalancerInterface|ConfigurableBalancerInterface
     */
    public function create(array $config = [])
    {
        $balancer = new Balancer();
        $balancer->setConfig($config);

        if ($this->monitor instanceof Monitor) {
            $balancer = new MonitoringDecorator($balancer, $this->monitor, $this->metric);
        }

        if ($this->logger instanceof LoggerInterface) {
            $balancer = new LoggerDecorator($balancer, $this->logger, $this->logLevel);
        }

        if (!$this->exceptions) {
            $balancer = new ExceptionSilencerDecorator($balancer, $this->logger);
        }

        return $balancer;
    }

    /**
     * @return BalancerInterface|ConfigurableBalancerInterface
     */
    public function createNullBalancer()
    {
        return new ExceptionSilencerDecorator(new Balancer());
    }

    /**
     * @param Monitor $monitor
     * @param string  $metric
     *
     * @return $this
     */
    public function withMonitor(Monitor $monitor, $metric)
    {
        $this->monitor = $monitor;
        $this->metric  = (string) new Identifier($metric);

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $logLevel
     *
     * @return $this
     */
    public function withLogger(LoggerInterface $logger, $logLevel = LogLevel::INFO)
    {
        $this->logger   = $logger;
        $this->logLevel = $logLevel;

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutExceptions()
    {
        $this->exceptions = false;

        return $this;
    }
}

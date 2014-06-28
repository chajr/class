<?php
/**
 * Initialize benchmark module
 *
 * @package     Core
 * @subpackage  Benchmark
 * @author      chajr <chajr@bluetree.pl>
 */
namespace Core\Benchmark;
use Core\Blue\Model\Initialize\InitAbstract;
use Loader;
class Initialize extends InitAbstract
{
    /**
     * initialize benchmark module
     */
    public function init()
    {
        $configuration  = Loader::getConfiguration()->getCore();
        $benchmark      = $configuration->getBenchmark() === 'enabled';
        $tracer         = $configuration->getTracer() === 'enabled';

        if ($benchmark) {
            Helper\Benchmark::turnOnBenchmark();
        } else {
            Helper\Benchmark::turnOffBenchmark();
        }

        if ($tracer) {
            Helper\Tracer::turnOnTracer();
        } else {
            Helper\Tracer::turnOffTracer();
        }
    }
}

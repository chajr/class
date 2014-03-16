<?php
/**
 * Initialize benchmark module
 *
 * @package     Core
 * @subpackage  Benchmark
 * @author      chajr <chajr@bluetree.pl>
 */
class Core_Benchmark_Initialize extends Core_Blue_Model_Initialize_Abstract
{
    /**
     * initialize benchmark module
     */
    public function init()
    {
        $configuration  = Loader::getConfiguration()->getConfiguration();
        $benchmark      = $configuration->getBenchmark() === 'enabled';
        $tracer         = $configuration->getTracer() === 'enabled';

        if ($benchmark) {
            Core_Benchmark_Helper_Benchmark::turnOnBenchmark();
        } else {
            Core_Benchmark_Helper_Benchmark::turnOffBenchmark();
        }

        if ($tracer) {
            Core_Benchmark_Helper_Tracer::turnOnTracer();
        } else {
            Core_Benchmark_Helper_Tracer::turnOffTracer();
        }
    }
}
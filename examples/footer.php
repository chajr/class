<?php
use Core\Benchmark\Helper as Performance;
Performance\Benchmark::setMarker('end');
Performance\Benchmark::stop();
echo Performance\Benchmark::display();
echo Performance\Tracer::display();
?>
</body>
</html>
<?php
ob_end_flush();
?>
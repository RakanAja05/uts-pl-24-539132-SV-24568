<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    /**
     * Expose Prometheus metrics.
     */
    public function index(): Response
    {
        $filePath = storage_path('framework/prometheus_metrics.json');
        $metricsData = [];

        if (file_exists($filePath)) {
            $fp = @fopen($filePath, 'r');
            if ($fp) {
                if (flock($fp, LOCK_SH)) {
                    $size = filesize($filePath);
                    if ($size > 0) {
                        rewind($fp);
                        $content = fread($fp, $size);
                        $decoded = json_decode($content, true);
                        if (is_array($decoded)) {
                            $metricsData = $decoded;
                        }
                    }
                    flock($fp, LOCK_UN);
                }
                fclose($fp);
            }
        }

        $lines = [];

        // 1. Counter: plant_http_requests_total
        $lines[] = '# HELP plant_http_requests_total Total number of HTTP requests processed by the application';
        $lines[] = '# TYPE plant_http_requests_total counter';

        if (empty($metricsData)) {
            $lines[] = 'plant_http_requests_total{method="GET",path="/health",status="200"} 0';
        } else {
            foreach ($metricsData as $key => $count) {
                $parts = explode('|', $key);
                if (count($parts) === 3) {
                    [$method, $path, $status] = $parts;
                    $methodEsc = addcslashes($method, "\"\\\n");
                    $pathEsc = addcslashes($path, "\"\\\n");
                    $statusEsc = addcslashes($status, "\"\\\n");
                    $lines[] = sprintf(
                        'plant_http_requests_total{method="%s",path="%s",status="%s"} %d',
                        $methodEsc,
                        $pathEsc,
                        $statusEsc,
                        $count
                    );
                }
            }
        }

        // 2. Gauge: plant_db_connected
        $dbConnected = 0;
        try {
            DB::select('SELECT 1');
            $dbConnected = 1;
        } catch (\Throwable $e) {
            $dbConnected = 0;
        }

        $lines[] = '# HELP plant_db_connected Database connection status (1 for connected, 0 for disconnected)';
        $lines[] = '# TYPE plant_db_connected gauge';
        $lines[] = "plant_db_connected {$dbConnected}";

        $output = implode("\n", $lines) . "\n";

        return response($output, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}

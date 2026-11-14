<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMetricsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Abaikan route /metrics agar scrape Prometheus tidak mengotori grafik trafik aplikasi
        $path = '/' . ltrim($request->path(), '/');
        if ($path === '/metrics' || $request->is('metrics')) {
            return $response;
        }

        $method = $request->method();
        // Ubah segmen angka ID seperti /api/plants/12 menjadi /api/plants/{id}
        $normalizedPath = preg_replace('#/\d+#', '/{id}', $path);
        $status = (string) $response->getStatusCode();

        $this->incrementCounter($method, $normalizedPath, $status);

        return $response;
    }

    /**
     * Simpan counter secara persisten dan thread-safe menggunakan flock LOCK_EX.
     */
    protected function incrementCounter(string $method, string $path, string $status): void
    {
        $filePath = storage_path('framework/prometheus_metrics.json');
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $key = "{$method}|{$path}|{$status}";

        $fp = @fopen($filePath, 'c+');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                $data = [];
                $size = filesize($filePath);
                if ($size > 0) {
                    rewind($fp);
                    $content = fread($fp, $size);
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) {
                        $data = $decoded;
                    }
                }

                $data[$key] = ($data[$key] ?? 0) + 1;

                rewind($fp);
                ftruncate($fp, 0);
                fwrite($fp, json_encode($data, JSON_PRETTY_PRINT));
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
            @chmod($filePath, 0666);
        }
    }
}

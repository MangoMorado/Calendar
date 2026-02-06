<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HealthController extends Controller
{
    /**
     * Muestra el dashboard de monitoreo de salud de la aplicación.
     * Solo accesible para usuarios con rol Mango.
     */
    public function index(Request $request): Response
    {
        $metrics = [
            'environment' => config('app.env'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone'),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->getStorageInfo(),
            'counts' => [
                'users' => User::count(),
                'calendars' => Calendar::count(),
                'appointments' => Appointment::count(),
                'notes' => Note::count(),
            ],
            'queue_connection' => config('queue.default'),
        ];

        return Inertia::render('health', [
            'metrics' => $metrics,
        ]);
    }

    /**
     * @return array{status: string, message: string, driver?: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::connection()->getDatabaseName();

            return [
                'status' => 'healthy',
                'message' => 'Conexión correcta',
                'driver' => config('database.default'),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'driver' => config('database.default'),
            ];
        }
    }

    /**
     * @return array{status: string, message: string, driver?: string}
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_'.uniqid();
            Cache::put($key, true, 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value ? 'healthy' : 'unhealthy',
                'message' => $value ? 'Funcionando correctamente' : 'No se pudo leer/escribir',
                'driver' => config('cache.default'),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'driver' => config('cache.default'),
            ];
        }
    }

    /**
     * @return array{status: string, message: string, size_mb?: float}
     */
    private function getStorageInfo(): array
    {
        try {
            $path = storage_path('app');
            if (! is_dir($path)) {
                return ['status' => 'unknown', 'message' => 'Directorio no existe'];
            }
            $size = $this->getDirSize($path);

            return [
                'status' => 'healthy',
                'message' => 'Accesible',
                'size_mb' => round($size / 1024 / 1024, 2),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getDirSize(string $path): int
    {
        $size = 0;
        foreach (glob(rtrim($path, '/').'/*', GLOB_NOSORT) as $item) {
            $size += is_file($item) ? filesize($item) : $this->getDirSize($item);
        }

        return $size;
    }
}

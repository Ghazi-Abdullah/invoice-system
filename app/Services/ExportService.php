<?php
namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExportService
{
    /**
     * تصدير البيانات إلى Excel وحفظها في الخادم
     */
    public function exportToExcel(string $exportClass, array $data, string $fileName, array $filters = []): array
    {
        try {
            $export = new $exportClass($data, $filters);
            $filePath = $this->generateFilePath($fileName);

            // تخزين الملف
            Excel::store($export, $filePath, 'public');

            return [
                'success' => true,
                'file_path' => Storage::url($filePath),
                'file_name' => $fileName,
                'full_path' => storage_path('app/public/' . $filePath),
                'download_url' => url('storage/' . $filePath),
                'created_at' => now()->toDateTimeString(),
                'size' => Storage::disk('public')->size($filePath)
            ];
        } catch (\Exception $e) {
            throw new \Exception('فشل في تصدير الملف: ' . $e->getMessage());
        }
    }

    /**
     * تحميل الملف مباشرة إلى المتصفح
     */
    public function downloadExcel(string $exportClass, array $data, string $fileName, array $filters = [])
    {
        $export = new $exportClass($data, $filters);
        return Excel::download($export, $fileName);
    }

    /**
     * إنشاء مسار الملف مع التاريخ
     */
    private function generateFilePath(string $fileName): string
    {
        $date = Carbon::now()->format('Y/m/d');
        $directory = "exports/{$date}";

        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory, 0755, true);
        }

        return "{$directory}/{$fileName}";
    }

    /**
     * إنشاء اسم ملف فريد
     */
    public function generateFileName(string $reportType, string $extension = 'xlsx'): string
    {
        $timestamp = Carbon::now()->format('Y_m_d_His');
        $random = Str::random(6);
        return "report_{$reportType}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * الحصول على قائمة الملفات المصدرة
     */
    public function getExportedFiles(int $limit = 20): array
    {
        $files = [];

        if (Storage::disk('public')->exists('exports')) {
            $allFiles = Storage::disk('public')->allFiles('exports');

            foreach ($allFiles as $file) {
                $files[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'url' => Storage::url($file),
                    'size' => $this->formatSize(Storage::disk('public')->size($file)),
                    'size_bytes' => Storage::disk('public')->size($file),
                    'modified' => Carbon::createFromTimestamp(
                        Storage::disk('public')->lastModified($file)
                    )->format('Y-m-d H:i:s'),
                    'date' => Carbon::createFromTimestamp(
                        Storage::disk('public')->lastModified($file)
                    )->format('Y-m-d')
                ];
            }

            // ترتيب تنازلي حسب التاريخ
            usort($files, function ($a, $b) {
                return strtotime($b['modified']) - strtotime($a['modified']);
            });

            return array_slice($files, 0, $limit);
        }

        return [];
    }

    /**
     * تنظيف الملفات القديمة
     */
    public function cleanupOldFiles(int $days = 7): int
    {
        $deleted = 0;
        $cutoff = Carbon::now()->subDays($days);

        if (Storage::disk('public')->exists('exports')) {
            $files = Storage::disk('public')->allFiles('exports');

            foreach ($files as $file) {
                $modified = Carbon::createFromTimestamp(
                    Storage::disk('public')->lastModified($file)
                );

                if ($modified->lt($cutoff)) {
                    Storage::disk('public')->delete($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * حذف ملف معين
     */
    public function deleteFile(string $fileName): bool
    {
        $filePath = $this->getFilePathByName($fileName);

        if ($filePath && Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }

        return false;
    }

    /**
     * البحث عن ملف بالاسم
     */
    public function getFilePathByName(string $fileName): ?string
    {
        if (Storage::disk('public')->exists('exports')) {
            $allFiles = Storage::disk('public')->allFiles('exports');

            foreach ($allFiles as $file) {
                if (basename($file) === $fileName) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * تنسيق حجم الملف
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * الحصول على إحصائيات الملفات
     */
    public function getExportStats(): array
    {
        $totalSize = 0;
        $fileCount = 0;

        if (Storage::disk('public')->exists('exports')) {
            $files = Storage::disk('public')->allFiles('exports');
            $fileCount = count($files);

            foreach ($files as $file) {
                $totalSize += Storage::disk('public')->size($file);
            }
        }

        return [
            'total_files' => $fileCount,
            'total_size' => $this->formatSize($totalSize),
            'total_size_bytes' => $totalSize,
            'last_cleanup' => $this->cleanupOldFiles(0) // تنظيف بدون حذف
        ];
    }
}

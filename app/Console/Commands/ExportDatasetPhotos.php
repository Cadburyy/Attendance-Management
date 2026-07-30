<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ExportDatasetPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dataset:export-photos {--limit=1000 : Maximum number of photos to export}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Decrypt and export attendance photos for YOLOv8 retraining (JPG format)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check PHP GD WebP Support
        if (!function_exists('imagetypes') || !(imagetypes() & IMG_WEBP)) {
            $this->error('❌ PHP GD Extension tidak mendukung format WebP. Aktifkan extension=gd di php.ini Anda.');
            return 1;
        }

        $this->warn('⚠️ PERHATIAN: Command ini akan mendekripsi foto biometrik massal ke disk lokal.');
        if (!$this->confirm('Apakah Anda yakin ingin mengekspor foto presensi untuk pelatihan dataset?', false)) {
            $this->info('Ekspor dibatalkan.');
            return 0;
        }

        $exportDir = storage_path('app/dataset_export/images');

        if (!File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $limit = (int) $this->option('limit');
        $totalAvailable = Attendance::whereNotNull('image')->count();

        if ($totalAvailable === 0) {
            $this->warn('Tidak ada foto presensi yang ditemukan di database.');
            return 0;
        }

        $secretString = env('CUSTOM_DECRYPTION_KEY');
        if (!$secretString) {
            $this->error('CUSTOM_DECRYPTION_KEY belum dikonfigurasi di file .env.');
            return 1;
        }

        $kek = hash('sha256', $secretString, true);
        $exportedCount = 0;
        $invalidPayloadCount = 0;
        $decryptFailCount = 0;
        $tamperedCount = 0;
        $processed = 0;

        $this->info("Mulai mengekspor maksimal {$limit} foto terbaru (Chunking per 50 baris dengan chunkByIdDesc)...");
        $bar = $this->output->createProgressBar(min($totalAvailable, $limit));
        $bar->start();

        // Chunking descending with strict manual limit control
        Attendance::whereNotNull('image')
            ->chunkByIdDesc(50, function ($attendances) use ($kek, $exportDir, &$exportedCount, &$invalidPayloadCount, &$decryptFailCount, &$tamperedCount, &$processed, $limit, $bar) {
                foreach ($attendances as $att) {
                    if ($processed >= $limit) {
                        return false; // Stop chunking when limit is reached
                    }

                    try {
                        $payload = json_decode($att->image, true);
                        if (!$payload || !isset($payload['data'], $payload['iv'], $payload['edek'], $payload['dek_iv'])) {
                            $invalidPayloadCount++;
                            $processed++;
                            $bar->advance();
                            continue;
                        }

                        $dekIv = base64_decode($payload['dek_iv']);
                        $dek = openssl_decrypt($payload['edek'], 'aes-256-cbc', $kek, 0, $dekIv);

                        if ($dek === false) {
                            $decryptFailCount++;
                            $processed++;
                            $bar->advance();
                            continue;
                        }

                        $iv = base64_decode($payload['iv']);
                        $decryptedBase64 = openssl_decrypt($payload['data'], 'aes-256-cbc', $dek, 0, $iv);

                        if ($decryptedBase64 === false) {
                            $decryptFailCount++;
                            $processed++;
                            $bar->advance();
                            continue;
                        }

                        // Cryptographic Integrity Verification (SHA-256 Check)
                        if (!empty($att->data_integrity_hash)) {
                            $calculatedHash = hash('sha256', $decryptedBase64);
                            if ($calculatedHash !== $att->data_integrity_hash) {
                                $tamperedCount++;
                                $processed++;
                                $bar->advance();
                                continue;
                            }
                        }

                        $cleanBase64 = preg_replace('#^data:image/[^;]+;base64,#', '', $decryptedBase64);
                        $cleanBase64 = str_replace(' ', '+', $cleanBase64);
                        $imageBinary = base64_decode($cleanBase64);

                        if ($imageBinary === false) {
                            $invalidPayloadCount++;
                            $processed++;
                            $bar->advance();
                            continue;
                        }

                        // Fix Windows Carbon date format
                        $dateStr = ($att->date instanceof \Carbon\Carbon) ? $att->date->format('Y-m-d') : substr((string)$att->date, 0, 10);
                        $filename = "attendance_{$att->id}_user_{$att->user_id}_{$dateStr}.jpg";

                        // Convert WebP binary stream to true JPG
                        $img = @imagecreatefromstring($imageBinary);
                        if ($img !== false) {
                            imagejpeg($img, $exportDir . '/' . $filename, 92);
                            imagedestroy($img);
                        } else {
                            File::put($exportDir . '/' . $filename, $imageBinary);
                        }

                        $exportedCount++;
                    } catch (\Exception $e) {
                        Log::error("Dataset Export Error on Attendance ID {$att->id}: " . $e->getMessage());
                    }

                    $processed++;
                    $bar->advance();
                }

                return $processed < $limit;
            });

        $bar->finish();
        $this->newLine(2);

        // Audit Log
        Log::info("Dataset photos exported. Total: {$exportedCount}, Tampered: {$tamperedCount}");

        $this->info("✅ Berhasil mengekspor {$exportedCount} foto presensi ke format .jpg!");

        if ($tamperedCount > 0) $this->error("🚨 Terdeteksi foto yang diubah/di-tamper (Gagal SHA-256 Hash): {$tamperedCount}");
        if ($invalidPayloadCount > 0) $this->warn("⚠️ Payload tidak valid/rusak: {$invalidPayloadCount}");
        if ($decryptFailCount > 0) $this->warn("⚠️ Gagal dekripsi (key/IV error): {$decryptFailCount}");

        $this->newLine();
        $this->comment("📁 Lokasi Dataset Foto: " . $exportDir);
        $this->comment("🔒 Ingat untuk menghapus folder ini setelah pelatihan selesai.");

        return 0;
    }
}

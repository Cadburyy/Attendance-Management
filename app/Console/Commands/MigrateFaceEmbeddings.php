<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MigrateFaceEmbeddings extends Command
{
    protected $signature = 'face:migrate-embeddings';
    protected $description = 'Generates face embeddings for all existing users with pictures';

    public function handle()
    {
        // Remove whereNull to re-generate for the new ArcFace model
        $users = User::whereNotNull('picture')->get();
        $this->info("Found " . $users->count() . " users to process for ArcFace upgrade.");

        $secretString = env('CUSTOM_DECRYPTION_KEY') ? env('CUSTOM_DECRYPTION_KEY') : 'AM2026';
        $kek = hash('sha256', $secretString, true);

        foreach ($users as $user) {
            $this->info("Processing user: " . $user->name);

            try {
                // 1. Decrypt Picture
                $dekIv = base64_decode($user->dek_iv);
                $dek = openssl_decrypt($user->encrypted_dek, 'aes-256-cbc', $kek, 0, $dekIv);
                
                $pictureIv = base64_decode($user->picture_iv);
                $decrypted = openssl_decrypt($user->picture, 'aes-256-cbc', $dek, 0, $pictureIv);

                if ($decrypted === false) {
                    $this->error("Failed to decrypt picture for " . $user->name);
                    continue;
                }

                // 2. Get Embedding
                $base64Image = 'data:image/jpeg;base64,' . base64_encode($decrypted);
                
                $response = Http::post('http://127.0.0.1:5000/represent', [
                    'image' => $base64Image
                ]);

                if ($response->successful() && isset($response->json()['embedding'])) {
                    $user->update([
                        'face_embedding' => $response->json()['embedding']
                    ]);
                    $this->info("Successfully migrated embedding for " . $user->name);
                } else {
                    $this->error("AI Server failed for " . $user->name . " (Status: " . $response->status() . ")");
                    $this->error("Response: " . $response->body());
                }

            } catch (\Exception $e) {
                $this->error("Error processing " . $user->name . ": " . $e->getMessage());
            }
        }

        $this->info("Migration completed.");
    }
}

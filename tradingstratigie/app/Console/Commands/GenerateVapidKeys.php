<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateVapidKeys extends Command
{
    protected $signature = 'pwa:generate-vapid';
    protected $description = 'Generate VAPID keys for web push notifications';

    public function handle()
    {
        $this->info('🔑 Generating VAPID Keys...');
        $this->newLine();

        try {
            // Generate VAPID keys using OpenSSL
            $keys = $this->generateVapidKeys();

            $this->info('✅ VAPID keys generated successfully!');
            $this->newLine();

            $this->line('Public Key:');
            $this->line($keys['publicKey']);
            $this->newLine();

            $this->line('Private Key:');
            $this->line($keys['privateKey']);
            $this->newLine();

            // Ask if user wants to add to .env
            if ($this->confirm('Add these keys to your .env file?', true)) {
                $this->addToEnv($keys);
            } else {
                $this->warn('Please manually add these keys to your .env file:');
                $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
                $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
                $this->line('VAPID_SUBJECT=mailto:your-email@example.com');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to generate VAPID keys: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Alternative: Use online generator at https://web-push-codelab.glitch.me/');
            return 1;
        }
    }

    private function generateVapidKeys(): array
    {
        // Generate EC private key
        $privateKey = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if (!$privateKey) {
            throw new \Exception('Failed to generate private key');
        }

        // Export private key
        openssl_pkey_export($privateKey, $privateKeyPem);

        // Get public key
        $keyDetails = openssl_pkey_get_details($privateKey);
        $publicKeyPem = $keyDetails['key'];

        // Convert to base64url format
        $publicKeyBase64 = $this->pemToBase64Url($publicKeyPem);
        $privateKeyBase64 = $this->pemToBase64Url($privateKeyPem);

        return [
            'publicKey' => $publicKeyBase64,
            'privateKey' => $privateKeyBase64,
        ];
    }

    private function pemToBase64Url(string $pem): string
    {
        // Remove PEM headers and footers
        $pem = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----'], '', $pem);
        $pem = str_replace(['-----BEGIN EC PRIVATE KEY-----', '-----END EC PRIVATE KEY-----'], '', $pem);
        $pem = str_replace(["\r", "\n", ' '], '', $pem);

        // Convert to base64url
        $base64 = base64_decode($pem);
        $base64url = rtrim(strtr(base64_encode($base64), '+/', '-_'), '=');

        return $base64url;
    }

    private function addToEnv(array $keys): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            $this->error('.env file not found!');
            return;
        }

        $envContent = File::get($envPath);

        // Check if keys already exist
        if (str_contains($envContent, 'VAPID_PUBLIC_KEY')) {
            if (!$this->confirm('VAPID keys already exist in .env. Overwrite?', false)) {
                return;
            }

            // Remove existing keys
            $envContent = preg_replace('/VAPID_PUBLIC_KEY=.*\n/', '', $envContent);
            $envContent = preg_replace('/VAPID_PRIVATE_KEY=.*\n/', '', $envContent);
            $envContent = preg_replace('/VAPID_SUBJECT=.*\n/', '', $envContent);
        }

        // Add new keys
        $newKeys = "\n# Web Push Notification Keys\n";
        $newKeys .= "VAPID_PUBLIC_KEY={$keys['publicKey']}\n";
        $newKeys .= "VAPID_PRIVATE_KEY={$keys['privateKey']}\n";
        $newKeys .= "VAPID_SUBJECT=mailto:admin@earningsstrategy.com\n";

        File::put($envPath, $envContent . $newKeys);

        $this->info('✅ Keys added to .env file successfully!');
        $this->warn('⚠️  Remember to update VAPID_SUBJECT with your actual email');
    }
}

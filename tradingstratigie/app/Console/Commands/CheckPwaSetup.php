<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckPwaSetup extends Command
{
    protected $signature = 'pwa:check';
    protected $description = 'Check PWA setup and configuration';

    public function handle()
    {
        $this->info('🔍 Checking PWA Setup...');
        $this->newLine();

        $allGood = true;

        // Check manifest.json
        if (File::exists(public_path('manifest.json'))) {
            $this->info('✅ manifest.json exists');
        } else {
            $this->error('❌ manifest.json missing');
            $allGood = false;
        }

        // Check service worker
        if (File::exists(public_path('sw.js'))) {
            $this->info('✅ sw.js (Service Worker) exists');
        } else {
            $this->error('❌ sw.js missing');
            $allGood = false;
        }

        // Check PWA script
        if (File::exists(public_path('js/pwa.js'))) {
            $this->info('✅ pwa.js exists');
        } else {
            $this->error('❌ pwa.js missing');
            $allGood = false;
        }

        // Check icons directory
        if (File::isDirectory(public_path('images/icons'))) {
            $this->info('✅ Icons directory exists');
            
            $requiredSizes = [72, 96, 128, 144, 152, 192, 384, 512];
            $missingIcons = [];
            
            foreach ($requiredSizes as $size) {
                $iconPath = public_path("images/icons/icon-{$size}x{$size}.png");
                if (!File::exists($iconPath)) {
                    $missingIcons[] = "{$size}x{$size}";
                }
            }
            
            if (empty($missingIcons)) {
                $this->info('✅ All required icons present');
            } else {
                $this->warn('⚠️  Missing icons: ' . implode(', ', $missingIcons));
                $this->line('   Generate icons at: /generate-icons.html');
                $allGood = false;
            }
        } else {
            $this->error('❌ Icons directory missing');
            $allGood = false;
        }

        $this->newLine();

        // Check VAPID keys
        $publicKey = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');

        if ($publicKey && $privateKey) {
            $this->info('✅ VAPID keys configured');
        } else {
            $this->error('❌ VAPID keys not configured');
            $this->line('   Add to .env:');
            $this->line('   VAPID_PUBLIC_KEY=your_public_key');
            $this->line('   VAPID_PRIVATE_KEY=your_private_key');
            $allGood = false;
        }

        $this->newLine();

        // Check database
        try {
            $subscriptionCount = \App\Models\PushSubscription::count();
            $this->info("✅ Push subscriptions table exists ({$subscriptionCount} subscriptions)");
        } catch (\Exception $e) {
            $this->error('❌ Push subscriptions table missing');
            $this->line('   Run: php artisan migrate');
            $allGood = false;
        }

        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        if ($allGood) {
            $this->info('🎉 PWA Setup Complete!');
            $this->newLine();
            $this->line('Next steps:');
            $this->line('1. Start server: php artisan serve');
            $this->line('2. Visit: http://localhost:8000');
            $this->line('3. Click "Install App" button');
            $this->line('4. Test on Android device');
        } else {
            $this->warn('⚠️  PWA Setup Incomplete');
            $this->newLine();
            $this->line('Please fix the issues above and run this command again.');
        }

        $this->newLine();

        return $allGood ? 0 : 1;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\EarningsStrategyService;
use App\Services\TempPushNotificationService;
use Illuminate\Console\Command;

class SendTestNotifications extends Command
{
    protected $signature = 'notifications:test-send {--interval=10}';
    protected $description = 'Send test notifications for Very Good and Good opportunities every X seconds';

    public function handle(
        EarningsStrategyService $strategyService,
        TempPushNotificationService $notificationService
    ) {
        $interval = (int) $this->option('interval');
        
        $this->info("🔔 Starting notification sender...");
        $this->info("📊 Sending notifications every {$interval} seconds");
        $this->info("🛑 Press Ctrl+C to stop");
        $this->newLine();

        $iteration = 0;

        while (true) {
            $iteration++;
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Iteration #{$iteration} - " . now()->format('H:i:s'));
            $this->newLine();

            try {
                // Get all opportunities
                $opportunities = $strategyService->getEligibleOpportunities();
                
                // Filter for Very Good and Good only
                $goodOpportunities = collect($opportunities)->filter(function ($opp) {
                    return in_array($opp['confidence_level'], ['Very Good', 'Good']);
                })->values();

                if ($goodOpportunities->isEmpty()) {
                    $this->warn('⚠️  No "Very Good" or "Good" opportunities found');
                    $this->line('   Waiting for next check...');
                } else {
                    $this->info("✅ Found {$goodOpportunities->count()} opportunities:");
                    $this->newLine();

                    // Display opportunities
                    $tableData = [];
                    foreach ($goodOpportunities as $opp) {
                        $tableData[] = [
                            $opp['symbol'],
                            $opp['confidence_level'],
                            $opp['confidence_score'],
                            $opp['days_until_earnings'] . ' days',
                        ];
                    }

                    $this->table(
                        ['Symbol', 'Confidence', 'Score', 'Days Until'],
                        $tableData
                    );

                    // Send notifications for each opportunity
                    foreach ($goodOpportunities as $opp) {
                        $payload = $notificationService->getOpportunityPayload($opp);
                        
                        $this->line("📤 Sending: {$payload['title']}");
                        $sentCount = $notificationService->sendToAll(
                            $payload['title'],
                            $payload['body'],
                            $payload['data']
                        );
                        
                        if ($sentCount > 0) {
                            $this->info("   ✓ Sent to {$sentCount} subscriber(s)");
                        } else {
                            $this->warn("   ⚠ No subscribers found");
                        }
                    }
                }

            } catch (\Exception $e) {
                $this->error("❌ Error: " . $e->getMessage());
            }

            $this->newLine();
            $this->line("⏳ Waiting {$interval} seconds...");
            $this->newLine();
            
            sleep($interval);
        }

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugYahooHtml extends Command
{
    protected $signature = 'debug:yahoo-html {symbol=AAPL}';
    protected $description = 'Debug Yahoo Finance HTML to see actual page structure';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $url = "https://finance.yahoo.com/quote/{$symbol}";
        
        $this->info("🔍 Fetching HTML from: {$url}");
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                $html = $response->body();
                
                $this->info("✅ Successfully fetched HTML ({" . strlen($html) . "} characters)");
                $this->newLine();
                
                // Look for price patterns
                $this->line("🔍 Searching for price patterns...");
                if (preg_match_all('/data-symbol="' . $symbol . '"[^>]*>([^<]+)</', $html, $matches)) {
                    $this->line("Found data-symbol matches: " . json_encode($matches[1]));
                }
                
                // Look for common price selectors
                if (preg_match('/regularMarketPrice[^>]*>([^<]+)</', $html, $matches)) {
                    $this->line("Found regularMarketPrice: " . $matches[1]);
                }
                
                // Look for fin-streamer elements (new Yahoo structure)
                if (preg_match_all('/<fin-streamer[^>]*data-field="regularMarketPrice"[^>]*>([^<]+)</', $html, $matches)) {
                    $this->line("Found fin-streamer price: " . json_encode($matches[1]));
                }
                
                // Save a sample of HTML for inspection
                $sample = substr($html, 0, 2000);
                $this->newLine();
                $this->line("📄 HTML Sample (first 2000 chars):");
                $this->line($sample);
                
            } else {
                $this->error("❌ Failed to fetch HTML: " . $response->status());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
        }
    }
}

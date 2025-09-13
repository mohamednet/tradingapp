<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugYahooAnalysis extends Command
{
    protected $signature = 'debug:yahoo-analysis {symbol=AAPL}';
    protected $description = 'Debug Yahoo Finance Analysis page to see actual HTML structure';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        $url = "https://finance.yahoo.com/quote/{$symbol}/analysis";
        
        $this->info("🔍 Fetching Analysis HTML from: {$url}");
        
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                $html = $response->body();
                
                $this->info("✅ Successfully fetched HTML ({" . strlen($html) . "} characters)");
                $this->newLine();
                
                // Look for price target patterns
                $this->line("🎯 Searching for price target patterns...");
                
                // Look for various price target patterns
                $patterns = [
                    '/price target.*?(\$?[\d,]+\.?\d*)/i',
                    '/target price.*?(\$?[\d,]+\.?\d*)/i',
                    '/mean.*?(\$?[\d,]+\.?\d*)/i',
                    '/average.*?(\$?[\d,]+\.?\d*)/i',
                    '/consensus.*?(\$?[\d,]+\.?\d*)/i',
                ];
                
                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $html, $matches)) {
                        $this->line("Pattern '{$pattern}' found: " . json_encode(array_slice($matches[1], 0, 5)));
                    }
                }
                
                // Look for recommendation patterns
                $this->line("📊 Searching for recommendation patterns...");
                if (preg_match_all('/(buy|sell|hold|strong|outperform|underperform)/i', $html, $matches)) {
                    $recommendations = array_count_values(array_map('strtolower', $matches[1]));
                    $this->line("Recommendations found: " . json_encode($recommendations));
                }
                
                // Save a sample for inspection
                $sample = substr($html, strpos($html, 'target') ?: 0, 1000);
                $this->newLine();
                $this->line("📄 HTML Sample around 'target' (1000 chars):");
                $this->line($sample);
                
            } else {
                $this->error("❌ Failed to fetch HTML: " . $response->status());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
        }
    }
}

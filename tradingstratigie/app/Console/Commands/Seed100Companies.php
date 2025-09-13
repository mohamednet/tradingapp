<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;

class Seed100Companies extends Command
{
    protected $signature = 'seed:100-companies';
    protected $description = 'Seed database with 100 NASDAQ companies for scale testing';

    public function handle()
    {
        $this->info("🌱 Seeding 100 NASDAQ companies...");
        
        $companies = [
            // Top 50 (existing)
            ['AAPL', 'Apple Inc'], ['MSFT', 'Microsoft Corporation'], ['AMZN', 'Amazon.com Inc'], 
            ['GOOGL', 'Alphabet Inc'], ['TSLA', 'Tesla Inc'], ['META', 'Meta Platforms Inc'], 
            ['NFLX', 'Netflix Inc'], ['NVDA', 'NVIDIA Corporation'], ['ADBE', 'Adobe Inc'], 
            ['PYPL', 'PayPal Holdings Inc'], ['INTC', 'Intel Corporation'], ['CRM', 'Salesforce Inc'], 
            ['CSCO', 'Cisco Systems Inc'], ['ORCL', 'Oracle Corporation'], ['QCOM', 'QUALCOMM Inc'], 
            ['TXN', 'Texas Instruments Inc'], ['AMD', 'Advanced Micro Devices Inc'], ['AVGO', 'Broadcom Inc'], 
            ['MU', 'Micron Technology Inc'], ['AMAT', 'Applied Materials Inc'], ['LRCX', 'Lam Research Corporation'], 
            ['KLAC', 'KLA Corporation'], ['MRVL', 'Marvell Technology Inc'], ['SNPS', 'Synopsys Inc'], 
            ['CDNS', 'Cadence Design Systems Inc'], ['ADI', 'Analog Devices Inc'], ['NOW', 'ServiceNow Inc'], 
            ['ADSK', 'Autodesk Inc'], ['WDAY', 'Workday Inc'], ['ZM', 'Zoom Video Communications Inc'], 
            ['DOCU', 'DocuSign Inc'], ['CRWD', 'CrowdStrike Holdings Inc'], ['OKTA', 'Okta Inc'], 
            ['SNOW', 'Snowflake Inc'], ['PLTR', 'Palantir Technologies Inc'], ['U', 'Unity Software Inc'], 
            ['RBLX', 'Roblox Corporation'], ['PTON', 'Peloton Interactive Inc'], ['ROKU', 'Roku Inc'], 
            ['SHOP', 'Shopify Inc'], ['SQ', 'Block Inc'], ['TWLO', 'Twilio Inc'], ['UBER', 'Uber Technologies Inc'], 
            ['LYFT', 'Lyft Inc'], ['DASH', 'DoorDash Inc'], ['ABNB', 'Airbnb Inc'], ['PINS', 'Pinterest Inc'], 
            ['SNAP', 'Snap Inc'], ['TWTR', 'Twitter Inc'], ['SPOT', 'Spotify Technology SA'],
            
            // Additional 50 companies
            ['BKNG', 'Booking Holdings Inc'], ['COST', 'Costco Wholesale Corporation'], ['SBUX', 'Starbucks Corporation'],
            ['ISRG', 'Intuitive Surgical Inc'], ['GILD', 'Gilead Sciences Inc'], ['REGN', 'Regeneron Pharmaceuticals Inc'],
            ['VRTX', 'Vertex Pharmaceuticals Inc'], ['BIIB', 'Biogen Inc'], ['ILMN', 'Illumina Inc'],
            ['MRNA', 'Moderna Inc'], ['AMGN', 'Amgen Inc'], ['CELG', 'Celgene Corporation'], 
            ['TMUS', 'T-Mobile US Inc'], ['CHTR', 'Charter Communications Inc'], ['CMCSA', 'Comcast Corporation'],
            ['ATVI', 'Activision Blizzard Inc'], ['EA', 'Electronic Arts Inc'], ['TTWO', 'Take-Two Interactive Software Inc'],
            ['NTES', 'NetEase Inc'], ['BIDU', 'Baidu Inc'], ['JD', 'JD.com Inc'], ['PDD', 'PDD Holdings Inc'],
            ['MELI', 'MercadoLibre Inc'], ['SE', 'Sea Limited'], ['DDOG', 'Datadog Inc'], ['FTNT', 'Fortinet Inc'],
            ['PANW', 'Palo Alto Networks Inc'], ['ZS', 'Zscaler Inc'], ['NET', 'Cloudflare Inc'], ['TEAM', 'Atlassian Corporation'],
            ['SPLK', 'Splunk Inc'], ['VEEV', 'Veeva Systems Inc'], ['FICO', 'Fair Isaac Corporation'], ['INTU', 'Intuit Inc'],
            ['PAYX', 'Paychex Inc'], ['ADP', 'Automatic Data Processing Inc'], ['FISV', 'Fiserv Inc'], ['FIS', 'Fidelity National Information Services Inc'],
            ['PYPL', 'PayPal Holdings Inc'], ['MA', 'Mastercard Inc'], ['V', 'Visa Inc'], ['COIN', 'Coinbase Global Inc'],
            ['HOOD', 'Robinhood Markets Inc'], ['SOFI', 'SoFi Technologies Inc'], ['AFRM', 'Affirm Holdings Inc'], ['UPST', 'Upstart Holdings Inc'],
            ['LC', 'LendingClub Corporation'], ['OPEN', 'Opendoor Technologies Inc'], ['Z', 'Zillow Group Inc'], ['REDFN', 'Redfin Corporation']
        ];

        $this->line("Clearing existing companies...");
        Company::truncate();

        $this->line("Adding companies...");
        $progressBar = $this->output->createProgressBar(count($companies));
        
        foreach ($companies as [$symbol, $name]) {
            Company::create([
                'symbol' => $symbol,
                'name' => $name,
                'favorite' => false
            ]);
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $totalCount = Company::count();
        $this->info("✅ Successfully seeded {$totalCount} companies!");
        
        // Show sample
        $this->line("Sample companies:");
        Company::take(5)->get()->each(function($company) {
            $this->line("  - {$company->symbol}: {$company->name}");
        });
    }
}

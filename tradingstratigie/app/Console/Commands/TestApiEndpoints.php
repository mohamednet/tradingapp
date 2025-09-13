<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Http\Resources\CompanyResource;

class TestApiEndpoints extends Command
{
    protected $signature = 'test:api-endpoints';
    protected $description = 'Test API endpoints functionality';

    public function handle()
    {
        $this->info("🧪 Testing API Resource Classes");
        
        try {
            // Test basic company loading
            $company = Company::with(['financialData', 'earnings'])->first();
            
            if (!$company) {
                $this->error("No companies found in database");
                return;
            }
            
            $this->line("✅ Company loaded: {$company->symbol}");
            
            // Test CompanyResource
            $resource = new CompanyResource($company);
            $data = $resource->toArray(request());
            
            $this->line("✅ CompanyResource works");
            $this->line("  - Symbol: {$data['symbol']}");
            $this->line("  - Name: {$data['name']}");
            $this->line("  - Has Financial Data: " . ($data['summary']['has_financial_data'] ? 'Yes' : 'No'));
            $this->line("  - Has Earnings: " . ($data['summary']['has_earnings_data'] ? 'Yes' : 'No'));
            
            if ($data['summary']['has_financial_data']) {
                $this->line("  - Current Price: $" . number_format($data['financial_data']['current_stock_price'], 2));
                $this->line("  - Market Cap: {$data['financial_data']['market_cap_formatted']}");
            }
            
            $this->info("✅ API Resources are working correctly!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}

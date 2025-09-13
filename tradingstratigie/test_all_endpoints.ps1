# Complete API test script
$baseUrl = "http://127.0.0.1:8000/api"

function Test-Endpoint {
    param(
        [string]$Url,
        [string]$Method = "GET",
        [hashtable]$Body = $null
    )
    
    try {
        $headers = @{ "Content-Type" = "application/json" }
        
        if ($Method -eq "POST" -and $Body) {
            $jsonBody = $Body | ConvertTo-Json
            $response = Invoke-WebRequest -Uri $Url -Method $Method -Headers $headers -Body $jsonBody -UseBasicParsing
        } else {
            $response = Invoke-WebRequest -Uri $Url -Method $Method -Headers $headers -UseBasicParsing
        }
        
        return @{
            Status = $response.StatusCode
            Content = $response.Content | ConvertFrom-Json
        }
    } catch {
        return @{
            Status = $_.Exception.Response.StatusCode.value__
            Content = $_.Exception.Message
        }
    }
}

Write-Host "🚀 COMPLETE API TESTING SUITE" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""

# Test 1: Single Company
Write-Host "1. GET /api/companies/{symbol} - Single Company:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/AAPL"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    $company = $result.Content.data
    Write-Host "✅ $($company.symbol): $($company.name)" -ForegroundColor Green
    Write-Host "   Price: $([math]::Round($company.financial_data.current_stock_price, 2))" -ForegroundColor White
    Write-Host "   Market Cap: $($company.financial_data.market_cap_formatted)" -ForegroundColor White
    Write-Host "   1W Performance: $([math]::Round($company.financial_data.price_performance_1week, 2))%" -ForegroundColor White
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}
Write-Host ""

# Test 2: Multiple Companies by Symbols
Write-Host "2. POST /api/companies/symbols - Multiple Companies:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/symbols" -Method "POST" -Body @{ symbols = "AAPL,MSFT,GOOGL" }
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    Write-Host "✅ Found $($result.Content.data.Count) companies:" -ForegroundColor Green
    foreach ($company in $result.Content.data) {
        Write-Host "   - $($company.symbol): $([math]::Round($company.financial_data.current_stock_price, 2))" -ForegroundColor White
    }
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}
Write-Host ""

# Test 3: All Companies (Paginated)
Write-Host "3. GET /api/companies - All Companies (Paginated):" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies?per_page=10"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    Write-Host "✅ Page 1: $($result.Content.data.Count) companies" -ForegroundColor Green
    Write-Host "   Total: $($result.Content.meta.total) companies" -ForegroundColor White
    Write-Host "   Pages: $($result.Content.meta.last_page)" -ForegroundColor White
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}
Write-Host ""

# Test 4: Companies with Financial Data
Write-Host "4. GET /api/companies/with-financial-data:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/with-financial-data"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    Write-Host "✅ Companies with financial data: $($result.Content.data.Count)" -ForegroundColor Green
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}
Write-Host ""

# Test 5: Companies with Earnings
Write-Host "5. GET /api/companies/with-earnings:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/with-earnings"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    Write-Host "✅ Companies with earnings: $($result.Content.data.Count)" -ForegroundColor Green
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}
Write-Host ""

# Test 6: Top Performers
Write-Host "6. GET /api/companies/top-performers:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/top-performers?limit=5&period=1week"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    Write-Host "✅ Top 5 weekly performers:" -ForegroundColor Green
    foreach ($company in $result.Content.data) {
        $perf = [math]::Round($company.financial_data.price_performance_1week, 2)
        Write-Host "   - $($company.symbol): $($perf)%" -ForegroundColor White
    }
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}
Write-Host ""

# Test 7: Market Summary
Write-Host "7. GET /api/companies/market-summary:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/market-summary"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    $summary = $result.Content.data
    Write-Host "✅ Market Summary:" -ForegroundColor Green
    Write-Host "   Total Companies: $($summary.total_companies)" -ForegroundColor White
    Write-Host "   Financial Coverage: $($summary.financial_data_coverage)%" -ForegroundColor White
    Write-Host "   Earnings Coverage: $($summary.earnings_coverage)%" -ForegroundColor White
    Write-Host "   Total Market Cap: $($summary.total_market_cap_formatted)" -ForegroundColor White
    Write-Host "   Avg 1W Performance: $([math]::Round($summary.market_performance.average_1week_performance, 2))%" -ForegroundColor White
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}

Write-Host ""
Write-Host "🎉 API TESTING COMPLETED SUCCESSFULLY!" -ForegroundColor Green

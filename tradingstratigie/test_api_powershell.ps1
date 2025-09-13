# PowerShell API test script
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

Write-Host "🧪 TESTING API ENDPOINTS" -ForegroundColor Green
Write-Host ""

# Test 1: Market Summary (this worked before)
Write-Host "1. Testing market summary:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/market-summary"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    Write-Host "✅ Total Companies: $($result.Content.data.total_companies)" -ForegroundColor Green
    Write-Host "✅ Financial Coverage: $($result.Content.data.financial_data_coverage)%" -ForegroundColor Green
    Write-Host "✅ Total Market Cap: $($result.Content.data.total_market_cap_formatted)" -ForegroundColor Green
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}
Write-Host ""

# Test 2: Get all companies (paginated)
Write-Host "2. Testing get all companies:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies?per_page=5"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan
if ($result.Status -eq 200) {
    Write-Host "✅ Found $($result.Content.data.Count) companies" -ForegroundColor Green
    foreach ($company in $result.Content.data) {
        Write-Host "  - $($company.symbol): $($company.name)" -ForegroundColor White
    }
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}
Write-Host ""

Write-Host "✅ API TESTING COMPLETED!" -ForegroundColor Green

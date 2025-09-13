# Simple Earnings Strategy Test
$baseUrl = "http://127.0.0.1:8000/api"

Write-Host "Testing Earnings Strategy API..." -ForegroundColor Green

try {
    $response = Invoke-WebRequest -Uri "$baseUrl/companies/earnings-strategy" -UseBasicParsing
    $data = $response.Content | ConvertFrom-Json
    
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Cyan
    Write-Host "Total Evaluated: $($data.summary.total_evaluated)" -ForegroundColor White
    Write-Host "Eligible: $($data.summary.eligible_count)" -ForegroundColor White
    Write-Host "Very Good: $($data.summary.very_good_count)" -ForegroundColor Green
    Write-Host "Good: $($data.summary.good_count)" -ForegroundColor Green
    Write-Host "Not Eligible: $($data.summary.not_eligible_count)" -ForegroundColor Gray
    
    Write-Host "`nTop 3 Opportunities:" -ForegroundColor Yellow
    $eligible = $data.data | Where-Object { $_.eligible -eq $true }
    $top3 = $eligible | Select-Object -First 3
    
    foreach ($opp in $top3) {
        Write-Host "$($opp.symbol): $($opp.confidence_rating) - $($opp.days_until_earnings) days" -ForegroundColor White
    }
    
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nTesting completed!" -ForegroundColor Green

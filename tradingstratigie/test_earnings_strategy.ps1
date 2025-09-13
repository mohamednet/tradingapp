# Test Earnings Strategy API
$baseUrl = "http://127.0.0.1:8000/api"

function Test-Endpoint {
    param(
        [string]$Url,
        [string]$Method = "GET"
    )
    
    try {
        $headers = @{ "Content-Type" = "application/json" }
        $response = Invoke-WebRequest -Uri $Url -Method $Method -Headers $headers -UseBasicParsing
        
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

Write-Host "🎯 TESTING EARNINGS ANTICIPATION STRATEGY API" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""

# Test 1: Full Earnings Strategy
Write-Host "1. GET /api/companies/earnings-strategy - Full Strategy Analysis:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/earnings-strategy"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan

if ($result.Status -eq 200) {
    $summary = $result.Content.summary
    Write-Host "✅ Strategy Analysis Complete:" -ForegroundColor Green
    Write-Host "   Total Evaluated: $($summary.total_evaluated)" -ForegroundColor White
    Write-Host "   Eligible: $($summary.eligible_count)" -ForegroundColor White
    Write-Host "   Very Good: $($summary.very_good_count)" -ForegroundColor Green
    Write-Host "   Good: $($summary.good_count)" -ForegroundColor Green
    Write-Host "   Neutral: $($summary.neutral_count)" -ForegroundColor Yellow
    Write-Host "   Bad: $($summary.bad_count)" -ForegroundColor Red
    Write-Host "   Very Bad: $($summary.very_bad_count)" -ForegroundColor Red
    Write-Host "   Not Eligible: $($summary.not_eligible_count)" -ForegroundColor Gray
    
    Write-Host "`n📊 Top Opportunities:" -ForegroundColor Yellow
    $topOpportunities = $result.Content.data | Where-Object { $_.eligible -eq $true } | Select-Object -First 5
    
    foreach ($opportunity in $topOpportunities) {
        $rating = $opportunity.confidence_rating
        $score = $opportunity.confidence_score
        $days = $opportunity.days_until_earnings
        $symbol = $opportunity.symbol
        $name = $opportunity.name
        
        $color = switch ($rating) {
            "Very Good" { "Green" }
            "Good" { "Green" }
            "Neutral" { "Yellow" }
            "Bad" { "Red" }
            "Very Bad" { "Red" }
            default { "White" }
        }
        
        Write-Host "   $symbol ($name)" -ForegroundColor $color
        Write-Host "     Rating: $rating ($score points)" -ForegroundColor White
        Write-Host "     Days to Earnings: $days" -ForegroundColor White
        Write-Host "     Position Size: $($opportunity.recommended_position_size)" -ForegroundColor White
        
        if ($opportunity.supporting_factors.Count -gt 0) {
            Write-Host "     Factors: $($opportunity.supporting_factors[0])" -ForegroundColor Gray
        }
        
        if ($opportunity.risk_warnings.Count -gt 0) {
            Write-Host "     ⚠️ Risks: $($opportunity.risk_warnings[0])" -ForegroundColor Red
        }
        Write-Host ""
    }
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}

Write-Host ""

# Test 2: Filter by Rating
Write-Host "2. GET /api/companies/earnings-strategy/rating?rating=Very Good:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/earnings-strategy/rating?rating=Very Good"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan

if ($result.Status -eq 200) {
    $count = $result.Content.filter.count
    Write-Host "✅ Very Good Opportunities: $count" -ForegroundColor Green
    
    if ($count -gt 0) {
        foreach ($opportunity in $result.Content.data) {
            Write-Host "   $($opportunity.symbol): $($opportunity.confidence_score) points, $($opportunity.days_until_earnings) days" -ForegroundColor Green
        }
    }
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}

Write-Host ""

# Test 3: Filter by Good Rating
Write-Host "3. GET /api/companies/earnings-strategy/rating?rating=Good:" -ForegroundColor Yellow
$result = Test-Endpoint -Url "$baseUrl/companies/earnings-strategy/rating?rating=Good"
Write-Host "Status: $($result.Status)" -ForegroundColor Cyan

if ($result.Status -eq 200) {
    $count = $result.Content.filter.count
    Write-Host "✅ Good Opportunities: $count" -ForegroundColor Green
    
    if ($count -gt 0) {
        foreach ($opportunity in $result.Content.data) {
            Write-Host "   $($opportunity.symbol): $($opportunity.confidence_score) points, $($opportunity.days_until_earnings) days" -ForegroundColor Green
        }
    }
} else {
    Write-Host "❌ Error: $($result.Content)" -ForegroundColor Red
}

Write-Host ""
Write-Host "🎉 EARNINGS STRATEGY API TESTING COMPLETED!" -ForegroundColor Green

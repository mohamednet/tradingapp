@extends('layouts.app')

@section('title', 'Earnings Anticipation Trading Strategy')

@section('content')
<div x-data="earningsStrategy()" x-init="loadData()">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="bg-white rounded-lg card-shadow p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-bullseye text-blue-600 mr-3"></i>
                        Earnings Anticipation Strategy
                    </h2>
                    <p class="text-gray-600">
                        Buy 1-4 weeks before earnings, sell 1-3 days before announcement to capture pre-earnings momentum
                    </p>
                </div>
                <div class="mt-4 lg:mt-0">
                    <button @click="refreshData()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 flex items-center">
                        <i class="fas fa-sync-alt mr-2" :class="{'animate-spin': loading}"></i>
                        Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg card-shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Companies</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="summary.total_evaluated || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg card-shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Eligible</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="summary.eligible_count || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg card-shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-emerald-100 text-emerald-600">
                    <i class="fas fa-star text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Very Good</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="summary.very_good_count || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg card-shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-thumbs-up text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Good</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="summary.good_count || 0"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg card-shadow p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-filter text-blue-600 mr-2"></i>
            Filters
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Eligibility Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Eligibility</label>
                <select x-model="filters.eligibility" @change="applyFilters()" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Companies</option>
                    <option value="eligible_only">Eligible Only</option>
                </select>
            </div>

            <!-- Confidence Rating Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confidence Rating</label>
                <select x-model="filters.confidence_rating" @change="applyFilters()" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Ratings</option>
                    <option value="Very Good">Very Good</option>
                    <option value="Good">Good</option>
                    <option value="Neutral">Neutral</option>
                    <option value="Bad">Bad</option>
                    <option value="Very Bad">Very Bad</option>
                </select>
            </div>

            <!-- Time Period Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Time Until Earnings</label>
                <select x-model="filters.time_period" @change="applyFilters()" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Periods</option>
                    <option value="1_week">1 Week (7-14 days)</option>
                    <option value="2_weeks">2 Weeks (14-21 days)</option>
                    <option value="3_weeks">3 Weeks (21-28 days)</option>
                    <option value="1_month">1 Month (7-30 days)</option>
                </select>
            </div>

            <!-- Favorites Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Favorites</label>
                <select x-model="filters.favorites" @change="applyFilters()" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Companies</option>
                    <option value="favorites_only">Favorites Only</option>
                </select>
            </div>
        </div>

        <!-- Active Filters Display -->
        <div class="mt-4 flex flex-wrap gap-2" x-show="hasActiveFilters()">
            <span class="text-sm text-gray-600">Active filters:</span>
            <template x-for="(filter, filterIndex) in getActiveFilters()" :key="'filter_' + filterIndex + '_' + filter.key">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <span x-text="filter.label"></span>
                    <button @click="clearFilter(filter.key)" class="ml-1 text-blue-600 hover:text-blue-800">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
            </template>
            <button @click="clearAllFilters()" class="text-xs text-red-600 hover:text-red-800 underline">
                Clear all
            </button>
        </div>
    </div>

    <!-- Results Section -->
    <div class="bg-white rounded-lg card-shadow">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    Trading Opportunities
                    <span class="ml-2 text-sm font-normal text-gray-500" x-text="`(${filteredData.length} results)`"></span>
                </h3>
                
                <!-- Sort Options -->
                <div class="mt-3 sm:mt-0">
                    <select x-model="sortBy" @change="sortData()" 
                            class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="confidence_score">Sort by Confidence</option>
                        <option value="days_until_earnings">Sort by Days to Earnings</option>
                        <option value="symbol">Sort by Symbol</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading opportunities...</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && filteredData.length === 0" class="p-8 text-center">
            <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No opportunities found</h3>
            <p class="text-gray-600">Try adjusting your filters to see more results.</p>
        </div>

        <!-- Opportunities Grid -->
        <div x-show="!loading && filteredData.length > 0" class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <template x-for="(opportunity, index) in filteredData" :key="opportunity.symbol + '_' + index">
                    <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow duration-200 animate-fade-in">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900" x-text="opportunity.symbol"></h4>
                                <p class="text-sm text-gray-600" x-text="opportunity.name"></p>
                                <p class="text-lg font-bold text-blue-600 mt-1" x-show="opportunity.current_price" x-text="'$' + parseFloat(opportunity.current_price || 0).toFixed(2)"></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium border"
                                      :class="getConfidenceClass(opportunity.confidence_rating)"
                                      x-text="opportunity.confidence_rating"></span>
                            </div>
                        </div>

                        <!-- Key Metrics -->
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Confidence Score:</span>
                                <span class="font-semibold" x-text="(opportunity.confidence_score || 0) + ' points'"></span>
                            </div>
                            
                            <div class="flex justify-between items-center" x-show="opportunity.eligible">
                                <span class="text-sm text-gray-600">Days to Earnings:</span>
                                <span class="font-semibold" x-text="Math.round(opportunity.days_until_earnings || 0) + ' days'"></span>
                            </div>
                            
                            <div class="flex justify-between items-center" x-show="opportunity.eligible">
                                <span class="text-sm text-gray-600">Earnings Date:</span>
                                <span class="font-semibold" x-text="formatDate(opportunity.earnings_date)"></span>
                            </div>
                            
                            <div class="flex justify-between items-center" x-show="opportunity.eligible">
                                <span class="text-sm text-gray-600">Position Size:</span>
                                <span class="font-semibold text-sm" x-text="opportunity.recommended_position_size || 'N/A'"></span>
                            </div>
                        </div>

                        <!-- Supporting Factors -->
                        <div x-show="opportunity.supporting_factors && Array.isArray(opportunity.supporting_factors) && opportunity.supporting_factors.length > 0" class="mb-4">
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Supporting Factors:</h5>
                            <ul class="space-y-1">
                                <template x-for="(factor, factorIndex) in (opportunity.supporting_factors || []).slice(0, 2)" :key="'factor_' + factorIndex + '_' + factor">
                                    <li class="text-xs text-green-600 flex items-start">
                                        <i class="fas fa-check text-green-500 mr-2 mt-0.5 text-xs"></i>
                                        <span x-text="factor"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Risk Warnings -->
                        <div x-show="opportunity.risk_warnings && Array.isArray(opportunity.risk_warnings) && opportunity.risk_warnings.length > 0" class="mb-4">
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Risk Warnings:</h5>
                            <ul class="space-y-1">
                                <template x-for="(warning, warningIndex) in (opportunity.risk_warnings || []).slice(0, 2)" :key="'warning_' + warningIndex + '_' + warning">
                                    <li class="text-xs text-red-600 flex items-start">
                                        <i class="fas fa-exclamation-triangle text-red-500 mr-2 mt-0.5 text-xs"></i>
                                        <span x-text="warning"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Action Button -->
                        <div class="pt-4 border-t border-gray-100">
                            <button x-show="opportunity.eligible" 
                                    @click="showDetails(opportunity)"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md text-sm font-medium transition-colors duration-200">
                                View Details
                            </button>
                            <div x-show="!opportunity.eligible" 
                                 class="w-full bg-gray-100 text-gray-500 py-2 px-4 rounded-md text-sm font-medium text-center">
                                Not Eligible
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div x-show="showModal" 
         x-cloak
         @click.away="closeModal()"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeModal()"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900" x-text="selectedOpportunity?.symbol"></h3>
                            <p class="text-sm text-gray-600" x-text="selectedOpportunity?.name"></p>
                        </div>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Confidence Badge -->
                    <div class="mb-6">
                        <span class="px-3 py-1 rounded-full text-sm font-medium border"
                              :class="getConfidenceClass(selectedOpportunity?.confidence_rating)"
                              x-text="selectedOpportunity?.confidence_rating"></span>
                    </div>

                    <!-- Key Metrics Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Current Price</p>
                            <p class="text-2xl font-bold text-blue-600" x-text="selectedOpportunity?.current_price ? '$' + parseFloat(selectedOpportunity.current_price).toFixed(2) : 'N/A'"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Market Cap</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="selectedOpportunity?.market_cap ? '$' + (parseFloat(selectedOpportunity.market_cap) / 1000000000).toFixed(2) + 'B' : 'N/A'"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Confidence Score</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="(selectedOpportunity?.confidence_score || 0).toFixed(1) + ' points'"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Days to Earnings</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="Math.round(selectedOpportunity?.days_until_earnings || 0) + ' days'"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Earnings Date</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="formatDate(selectedOpportunity?.earnings_date)"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">Position Size</p>
                            <p class="text-lg font-semibold text-gray-900" x-text="selectedOpportunity?.recommended_position_size || 'N/A'"></p>
                        </div>
                    </div>

                    <!-- Supporting Factors -->
                    <div x-show="selectedOpportunity?.supporting_factors && selectedOpportunity.supporting_factors.length > 0" class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            Supporting Factors
                        </h4>
                        <ul class="space-y-2">
                            <template x-for="(factor, index) in (selectedOpportunity?.supporting_factors || [])" :key="'modal_factor_' + index">
                                <li class="flex items-start text-sm text-gray-700">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span x-text="factor"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Risk Warnings -->
                    <div x-show="selectedOpportunity?.risk_warnings && selectedOpportunity.risk_warnings.length > 0" class="mb-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                            Risk Warnings
                        </h4>
                        <ul class="space-y-2">
                            <template x-for="(warning, index) in (selectedOpportunity?.risk_warnings || [])" :key="'modal_warning_' + index">
                                <li class="flex items-start text-sm text-gray-700">
                                    <i class="fas fa-exclamation-triangle text-red-500 mr-2 mt-1"></i>
                                    <span x-text="warning"></span>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <!-- Strategy Recommendation -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-blue-900 mb-2">
                            <i class="fas fa-lightbulb mr-2"></i>
                            Strategy Recommendation
                        </h4>
                        <p class="text-sm text-blue-800">
                            Buy <strong x-text="selectedOpportunity?.symbol"></strong> 
                            <strong x-text="Math.round(selectedOpportunity?.days_until_earnings || 0) + ' days'"></strong> before earnings 
                            (around <strong x-text="formatDate(selectedOpportunity?.earnings_date)"></strong>). 
                            Sell 1-3 days before the announcement to capture pre-earnings momentum while avoiding earnings volatility.
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="closeModal()" 
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function earningsStrategy() {
    return {
        loading: false,
        rawData: [],
        filteredData: [],
        summary: {},
        sortBy: 'confidence_score',
        showModal: false,
        selectedOpportunity: null,
        filters: {
            eligibility: 'all',
            confidence_rating: 'all',
            time_period: 'all',
            favorites: 'all'
        },

        async loadData() {
            this.loading = true;
            try {
                const response = await fetch('/dashboard/earnings-data');
                const data = await response.json();
                
                if (data.success) {
                    this.rawData = data.data;
                    this.summary = data.summary;
                    this.applyFilters();
                }
            } catch (error) {
                console.error('Error loading data:', error);
            } finally {
                this.loading = false;
            }
        },

        async refreshData() {
            await this.loadData();
        },

        applyFilters() {
            let filtered = [...this.rawData];

            // Apply eligibility filter
            if (this.filters.eligibility === 'eligible_only') {
                filtered = filtered.filter(opp => opp.eligible);
            }

            // Apply confidence rating filter
            if (this.filters.confidence_rating !== 'all') {
                filtered = filtered.filter(opp => opp.confidence_rating === this.filters.confidence_rating);
            }

            // Apply time period filter
            if (this.filters.time_period !== 'all') {
                filtered = filtered.filter(opp => {
                    // Only apply time filter to eligible companies with earnings data
                    if (!opp.eligible || !opp.days_until_earnings) return false;
                    
                    const days = opp.days_until_earnings;
                    switch (this.filters.time_period) {
                        case '1_week': return days >= 7 && days <= 14;
                        case '2_weeks': return days >= 14 && days <= 21;
                        case '3_weeks': return days >= 21 && days <= 28;
                        case '1_month': return days >= 7 && days <= 30;
                        default: return true;
                    }
                });
            }

            this.filteredData = filtered;
            this.sortData();
        },

        sortData() {
            this.filteredData.sort((a, b) => {
                switch (this.sortBy) {
                    case 'confidence_score':
                        return (b.confidence_score || 0) - (a.confidence_score || 0);
                    case 'days_until_earnings':
                        return (a.days_until_earnings || 999) - (b.days_until_earnings || 999);
                    case 'symbol':
                        return a.symbol.localeCompare(b.symbol);
                    default:
                        return 0;
                }
            });
        },

        getConfidenceClass(rating) {
            const classes = {
                'Very Good': 'confidence-very-good',
                'Good': 'confidence-good',
                'Neutral': 'confidence-neutral',
                'Bad': 'confidence-bad',
                'Very Bad': 'confidence-very-bad',
                'Not Eligible': 'confidence-not-eligible'
            };
            return classes[rating] || 'confidence-not-eligible';
        },

        hasActiveFilters() {
            return Object.values(this.filters).some(filter => filter !== 'all');
        },

        getActiveFilters() {
            const active = [];
            const labels = {
                eligibility: { 'eligible_only': 'Eligible Only' },
                confidence_rating: {
                    'Very Good': 'Very Good',
                    'Good': 'Good',
                    'Neutral': 'Neutral',
                    'Bad': 'Bad',
                    'Very Bad': 'Very Bad'
                },
                time_period: {
                    '1_week': '1 Week',
                    '2_weeks': '2 Weeks', 
                    '3_weeks': '3 Weeks',
                    '1_month': '1 Month'
                },
                favorites: { 'favorites_only': 'Favorites Only' }
            };

            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== 'all' && labels[key] && labels[key][value]) {
                    active.push({ key, label: labels[key][value] });
                }
            });

            return active;
        },

        clearFilter(key) {
            this.filters[key] = 'all';
            this.applyFilters();
        },

        clearAllFilters() {
            Object.keys(this.filters).forEach(key => {
                this.filters[key] = 'all';
            });
            this.applyFilters();
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString();
        },

        showDetails(opportunity) {
            this.selectedOpportunity = opportunity;
            this.showModal = true;
            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.showModal = false;
            this.selectedOpportunity = null;
            // Restore body scroll
            document.body.style.overflow = '';
        }
    }
}
</script>
@endpush

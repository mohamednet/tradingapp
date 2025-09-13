<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            // NASDAQ-100 Major Companies
            ['name' => 'Apple Inc.', 'symbol' => 'AAPL', 'favorite' => 1],
            ['name' => 'Microsoft Corporation', 'symbol' => 'MSFT', 'favorite' => 1],
            ['name' => 'Amazon.com Inc.', 'symbol' => 'AMZN', 'favorite' => 1],
            ['name' => 'Alphabet Inc. Class A', 'symbol' => 'GOOGL', 'favorite' => 1],
            ['name' => 'Alphabet Inc. Class C', 'symbol' => 'GOOG', 'favorite' => 1],
            ['name' => 'Tesla Inc.', 'symbol' => 'TSLA', 'favorite' => 1],
            ['name' => 'Meta Platforms Inc.', 'symbol' => 'META', 'favorite' => 1],
            ['name' => 'NVIDIA Corporation', 'symbol' => 'NVDA', 'favorite' => 1],
            ['name' => 'Netflix Inc.', 'symbol' => 'NFLX', 'favorite' => 1],
            ['name' => 'Adobe Inc.', 'symbol' => 'ADBE', 'favorite' => 1],
            
            // Technology Giants
            ['name' => 'Intel Corporation', 'symbol' => 'INTC', 'favorite' => 1],
            ['name' => 'Advanced Micro Devices Inc.', 'symbol' => 'AMD', 'favorite' => 1],
            ['name' => 'Oracle Corporation', 'symbol' => 'ORCL', 'favorite' => 1],
            ['name' => 'Salesforce Inc.', 'symbol' => 'CRM', 'favorite' => 1],
            ['name' => 'Cisco Systems Inc.', 'symbol' => 'CSCO', 'favorite' => 1],
            ['name' => 'Broadcom Inc.', 'symbol' => 'AVGO', 'favorite' => 1],
            ['name' => 'Qualcomm Incorporated', 'symbol' => 'QCOM', 'favorite' => 1],
            ['name' => 'Texas Instruments Incorporated', 'symbol' => 'TXN', 'favorite' => 0],
            ['name' => 'Applied Materials Inc.', 'symbol' => 'AMAT', 'favorite' => 0],
            ['name' => 'Micron Technology Inc.', 'symbol' => 'MU', 'favorite' => 0],
            
            // Biotech & Healthcare
            ['name' => 'Moderna Inc.', 'symbol' => 'MRNA', 'favorite' => 0],
            ['name' => 'Gilead Sciences Inc.', 'symbol' => 'GILD', 'favorite' => 0],
            ['name' => 'Regeneron Pharmaceuticals Inc.', 'symbol' => 'REGN', 'favorite' => 0],
            ['name' => 'Biogen Inc.', 'symbol' => 'BIIB', 'favorite' => 0],
            ['name' => 'Amgen Inc.', 'symbol' => 'AMGN', 'favorite' => 0],
            ['name' => 'Vertex Pharmaceuticals Incorporated', 'symbol' => 'VRTX', 'favorite' => 0],
            ['name' => 'Illumina Inc.', 'symbol' => 'ILMN', 'favorite' => 0],
            ['name' => 'Seagen Inc.', 'symbol' => 'SGEN', 'favorite' => 0],
            ['name' => 'BioMarin Pharmaceutical Inc.', 'symbol' => 'BMRN', 'favorite' => 0],
            ['name' => 'Alexion Pharmaceuticals Inc.', 'symbol' => 'ALXN', 'favorite' => 0],
            
            // Consumer & Retail
            ['name' => 'Costco Wholesale Corporation', 'symbol' => 'COST', 'favorite' => 0],
            ['name' => 'PepsiCo Inc.', 'symbol' => 'PEP', 'favorite' => 0],
            ['name' => 'Starbucks Corporation', 'symbol' => 'SBUX', 'favorite' => 0],
            ['name' => 'Mondelez International Inc.', 'symbol' => 'MDLZ', 'favorite' => 0],
            ['name' => 'Kraft Heinz Company', 'symbol' => 'KHC', 'favorite' => 0],
            ['name' => 'Dollar Tree Inc.', 'symbol' => 'DLTR', 'favorite' => 0],
            ['name' => 'Ross Stores Inc.', 'symbol' => 'ROST', 'favorite' => 0],
            ['name' => 'Lululemon Athletica Inc.', 'symbol' => 'LULU', 'favorite' => 0],
            ['name' => 'Booking Holdings Inc.', 'symbol' => 'BKNG', 'favorite' => 0],
            ['name' => 'Expedia Group Inc.', 'symbol' => 'EXPE', 'favorite' => 0],
            
            // Communication Services
            ['name' => 'Comcast Corporation', 'symbol' => 'CMCSA', 'favorite' => 0],
            ['name' => 'T-Mobile US Inc.', 'symbol' => 'TMUS', 'favorite' => 0],
            ['name' => 'Charter Communications Inc.', 'symbol' => 'CHTR', 'favorite' => 0],
            ['name' => 'Zoom Video Communications Inc.', 'symbol' => 'ZM', 'favorite' => 0],
            ['name' => 'Electronic Arts Inc.', 'symbol' => 'EA', 'favorite' => 0],
            ['name' => 'Activision Blizzard Inc.', 'symbol' => 'ATVI', 'favorite' => 0],
            ['name' => 'Match Group Inc.', 'symbol' => 'MTCH', 'favorite' => 0],
            ['name' => 'Liberty Media Corporation', 'symbol' => 'LSXMK', 'favorite' => 0],
            ['name' => 'Sirius XM Holdings Inc.', 'symbol' => 'SIRI', 'favorite' => 0],
            ['name' => 'ViacomCBS Inc.', 'symbol' => 'VIAC', 'favorite' => 0],
            
            // Financial Services
            ['name' => 'PayPal Holdings Inc.', 'symbol' => 'PYPL', 'favorite' => 0],
            ['name' => 'Intuit Inc.', 'symbol' => 'INTU', 'favorite' => 0],
            ['name' => 'American Express Company', 'symbol' => 'AXP', 'favorite' => 0],
            ['name' => 'Fiserv Inc.', 'symbol' => 'FISV', 'favorite' => 0],
            ['name' => 'Automatic Data Processing Inc.', 'symbol' => 'ADP', 'favorite' => 0],
            ['name' => 'Paychex Inc.', 'symbol' => 'PAYX', 'favorite' => 0],
            ['name' => 'MarketAxess Holdings Inc.', 'symbol' => 'MKTX', 'favorite' => 0],
            ['name' => 'Nasdaq Inc.', 'symbol' => 'NDAQ', 'favorite' => 0],
            ['name' => 'CME Group Inc.', 'symbol' => 'CME', 'favorite' => 0],
            ['name' => 'Intercontinental Exchange Inc.', 'symbol' => 'ICE', 'favorite' => 0],
            
            // Transportation & Logistics
            ['name' => 'FedEx Corporation', 'symbol' => 'FDX', 'favorite' => 0],
            ['name' => 'United Parcel Service Inc.', 'symbol' => 'UPS', 'favorite' => 0],
            ['name' => 'Old Dominion Freight Line Inc.', 'symbol' => 'ODFL', 'favorite' => 0],
            ['name' => 'JetBlue Airways Corporation', 'symbol' => 'JBLU', 'favorite' => 0],
            ['name' => 'Alaska Air Group Inc.', 'symbol' => 'ALK', 'favorite' => 0],
            ['name' => 'Southwest Airlines Co.', 'symbol' => 'LUV', 'favorite' => 0],
            ['name' => 'American Airlines Group Inc.', 'symbol' => 'AAL', 'favorite' => 0],
            ['name' => 'Delta Air Lines Inc.', 'symbol' => 'DAL', 'favorite' => 0],
            ['name' => 'United Airlines Holdings Inc.', 'symbol' => 'UAL', 'favorite' => 0],
            ['name' => 'Spirit Airlines Inc.', 'symbol' => 'SAVE', 'favorite' => 0],
            
            // Energy & Utilities
            ['name' => 'Exelon Corporation', 'symbol' => 'EXC', 'favorite' => 0],
            ['name' => 'Xcel Energy Inc.', 'symbol' => 'XEL', 'favorite' => 0],
            ['name' => 'American Electric Power Company Inc.', 'symbol' => 'AEP', 'favorite' => 0],
            ['name' => 'NextEra Energy Inc.', 'symbol' => 'NEE', 'favorite' => 0],
            ['name' => 'Enphase Energy Inc.', 'symbol' => 'ENPH', 'favorite' => 0],
            ['name' => 'First Solar Inc.', 'symbol' => 'FSLR', 'favorite' => 0],
            ['name' => 'SolarEdge Technologies Inc.', 'symbol' => 'SEDG', 'favorite' => 0],
            ['name' => 'Plug Power Inc.', 'symbol' => 'PLUG', 'favorite' => 0],
            ['name' => 'Bloom Energy Corporation', 'symbol' => 'BE', 'favorite' => 0],
            ['name' => 'Ballard Power Systems Inc.', 'symbol' => 'BLDP', 'favorite' => 0],
            
            // Industrial & Manufacturing
            ['name' => 'Honeywell International Inc.', 'symbol' => 'HON', 'favorite' => 0],
            ['name' => 'Danaher Corporation', 'symbol' => 'DHR', 'favorite' => 0],
            ['name' => 'Illinois Tool Works Inc.', 'symbol' => 'ITW', 'favorite' => 0],
            ['name' => 'Caterpillar Inc.', 'symbol' => 'CAT', 'favorite' => 0],
            ['name' => 'Deere & Company', 'symbol' => 'DE', 'favorite' => 0],
            ['name' => 'General Electric Company', 'symbol' => 'GE', 'favorite' => 0],
            ['name' => '3M Company', 'symbol' => 'MMM', 'favorite' => 0],
            ['name' => 'Boeing Company', 'symbol' => 'BA', 'favorite' => 0],
            ['name' => 'Lockheed Martin Corporation', 'symbol' => 'LMT', 'favorite' => 0],
            ['name' => 'Raytheon Technologies Corporation', 'symbol' => 'RTX', 'favorite' => 0],
            
            // Real Estate & REITs
            ['name' => 'American Tower Corporation', 'symbol' => 'AMT', 'favorite' => 0],
            ['name' => 'Crown Castle International Corp.', 'symbol' => 'CCI', 'favorite' => 0],
            ['name' => 'Digital Realty Trust Inc.', 'symbol' => 'DLR', 'favorite' => 0],
            ['name' => 'Equinix Inc.', 'symbol' => 'EQIX', 'favorite' => 0],
            ['name' => 'Prologis Inc.', 'symbol' => 'PLD', 'favorite' => 0],
            ['name' => 'Public Storage', 'symbol' => 'PSA', 'favorite' => 0],
            ['name' => 'Simon Property Group Inc.', 'symbol' => 'SPG', 'favorite' => 0],
            ['name' => 'Realty Income Corporation', 'symbol' => 'O', 'favorite' => 0],
            ['name' => 'AvalonBay Communities Inc.', 'symbol' => 'AVB', 'favorite' => 0],
            ['name' => 'Equity Residential', 'symbol' => 'EQR', 'favorite' => 0],
            
            // Materials & Chemicals
            ['name' => 'Linde plc', 'symbol' => 'LIN', 'favorite' => 0],
            ['name' => 'Air Products and Chemicals Inc.', 'symbol' => 'APD', 'favorite' => 0],
            ['name' => 'DuPont de Nemours Inc.', 'symbol' => 'DD', 'favorite' => 0],
            ['name' => 'Dow Inc.', 'symbol' => 'DOW', 'favorite' => 0],
            ['name' => 'PPG Industries Inc.', 'symbol' => 'PPG', 'favorite' => 0],
            ['name' => 'Sherwin-Williams Company', 'symbol' => 'SHW', 'favorite' => 0],
            ['name' => 'International Flavors & Fragrances Inc.', 'symbol' => 'IFF', 'favorite' => 0],
            ['name' => 'Eastman Chemical Company', 'symbol' => 'EMN', 'favorite' => 0],
            ['name' => 'LyondellBasell Industries N.V.', 'symbol' => 'LYB', 'favorite' => 0],
            ['name' => 'CF Industries Holdings Inc.', 'symbol' => 'CF', 'favorite' => 0],
            
            // Emerging Tech & Growth
            ['name' => 'Shopify Inc.', 'symbol' => 'SHOP', 'favorite' => 0],
            ['name' => 'Square Inc.', 'symbol' => 'SQ', 'favorite' => 0],
            ['name' => 'Roku Inc.', 'symbol' => 'ROKU', 'favorite' => 0],
            ['name' => 'Peloton Interactive Inc.', 'symbol' => 'PTON', 'favorite' => 0],
            ['name' => 'DocuSign Inc.', 'symbol' => 'DOCU', 'favorite' => 0],
            ['name' => 'Snowflake Inc.', 'symbol' => 'SNOW', 'favorite' => 0],
            ['name' => 'Palantir Technologies Inc.', 'symbol' => 'PLTR', 'favorite' => 0],
            ['name' => 'Unity Software Inc.', 'symbol' => 'U', 'favorite' => 0],
            ['name' => 'CrowdStrike Holdings Inc.', 'symbol' => 'CRWD', 'favorite' => 0],
            ['name' => 'Okta Inc.', 'symbol' => 'OKTA', 'favorite' => 0],
            
            // Additional NASDAQ Companies (150-200)
            ['name' => 'Workday Inc.', 'symbol' => 'WDAY', 'favorite' => 0],
            ['name' => 'ServiceNow Inc.', 'symbol' => 'NOW', 'favorite' => 0],
            ['name' => 'Splunk Inc.', 'symbol' => 'SPLK', 'favorite' => 0],
            ['name' => 'Atlassian Corporation Plc', 'symbol' => 'TEAM', 'favorite' => 0],
            ['name' => 'MongoDB Inc.', 'symbol' => 'MDB', 'favorite' => 0],
            ['name' => 'Elastic N.V.', 'symbol' => 'ESTC', 'favorite' => 0],
            ['name' => 'Datadog Inc.', 'symbol' => 'DDOG', 'favorite' => 0],
            ['name' => 'Zscaler Inc.', 'symbol' => 'ZS', 'favorite' => 0],
            ['name' => 'Cloudflare Inc.', 'symbol' => 'NET', 'favorite' => 0],
            ['name' => 'Fastly Inc.', 'symbol' => 'FSLY', 'favorite' => 0],
            
            // Semiconductor & Hardware (200-250)
            ['name' => 'Marvell Technology Inc.', 'symbol' => 'MRVL', 'favorite' => 0],
            ['name' => 'Analog Devices Inc.', 'symbol' => 'ADI', 'favorite' => 0],
            ['name' => 'Maxim Integrated Products Inc.', 'symbol' => 'MXIM', 'favorite' => 0],
            ['name' => 'Xilinx Inc.', 'symbol' => 'XLNX', 'favorite' => 0],
            ['name' => 'Lam Research Corporation', 'symbol' => 'LRCX', 'favorite' => 0],
            ['name' => 'KLA Corporation', 'symbol' => 'KLAC', 'favorite' => 0],
            ['name' => 'Synopsys Inc.', 'symbol' => 'SNPS', 'favorite' => 0],
            ['name' => 'Cadence Design Systems Inc.', 'symbol' => 'CDNS', 'favorite' => 0],
            ['name' => 'ASML Holding N.V.', 'symbol' => 'ASML', 'favorite' => 0],
            ['name' => 'Taiwan Semiconductor Manufacturing Company Limited', 'symbol' => 'TSM', 'favorite' => 0],
            
            // Biotech & Pharma Extended (250-300)
            ['name' => 'Incyte Corporation', 'symbol' => 'INCY', 'favorite' => 0],
            ['name' => 'Alexion Pharmaceuticals Inc.', 'symbol' => 'ALXN', 'favorite' => 0],
            ['name' => 'Exact Sciences Corporation', 'symbol' => 'EXAS', 'favorite' => 0],
            ['name' => 'Alnylam Pharmaceuticals Inc.', 'symbol' => 'ALNY', 'favorite' => 0],
            ['name' => 'Sarepta Therapeutics Inc.', 'symbol' => 'SRPT', 'favorite' => 0],
            ['name' => 'Neurocrine Biosciences Inc.', 'symbol' => 'NBIX', 'favorite' => 0],
            ['name' => 'Ultragenyx Pharmaceutical Inc.', 'symbol' => 'RARE', 'favorite' => 0],
            ['name' => 'Bluebird Bio Inc.', 'symbol' => 'BLUE', 'favorite' => 0],
            ['name' => 'CRISPR Therapeutics AG', 'symbol' => 'CRSP', 'favorite' => 0],
            ['name' => 'Editas Medicine Inc.', 'symbol' => 'EDIT', 'favorite' => 0],
            
            // Consumer Discretionary Extended (300-350)
            ['name' => 'MercadoLibre Inc.', 'symbol' => 'MELI', 'favorite' => 0],
            ['name' => 'JD.com Inc.', 'symbol' => 'JD', 'favorite' => 0],
            ['name' => 'Pinduoduo Inc.', 'symbol' => 'PDD', 'favorite' => 0],
            ['name' => 'Sea Limited', 'symbol' => 'SE', 'favorite' => 0],
            ['name' => 'Carvana Co.', 'symbol' => 'CVNA', 'favorite' => 0],
            ['name' => 'Wayfair Inc.', 'symbol' => 'W', 'favorite' => 0],
            ['name' => 'Etsy Inc.', 'symbol' => 'ETSY', 'favorite' => 0],
            ['name' => 'eBay Inc.', 'symbol' => 'EBAY', 'favorite' => 0],
            ['name' => 'Airbnb Inc.', 'symbol' => 'ABNB', 'favorite' => 0],
            ['name' => 'DoorDash Inc.', 'symbol' => 'DASH', 'favorite' => 0],
            
            // Financial Technology (350-400)
            ['name' => 'Block Inc.', 'symbol' => 'SQ', 'favorite' => 0],
            ['name' => 'Affirm Holdings Inc.', 'symbol' => 'AFRM', 'favorite' => 0],
            ['name' => 'SoFi Technologies Inc.', 'symbol' => 'SOFI', 'favorite' => 0],
            ['name' => 'Upstart Holdings Inc.', 'symbol' => 'UPST', 'favorite' => 0],
            ['name' => 'LendingClub Corporation', 'symbol' => 'LC', 'favorite' => 0],
            ['name' => 'Robinhood Markets Inc.', 'symbol' => 'HOOD', 'favorite' => 0],
            ['name' => 'Coinbase Global Inc.', 'symbol' => 'COIN', 'favorite' => 0],
            ['name' => 'MasterCard Incorporated', 'symbol' => 'MA', 'favorite' => 0],
            ['name' => 'Visa Inc.', 'symbol' => 'V', 'favorite' => 0],
            ['name' => 'Global Payments Inc.', 'symbol' => 'GPN', 'favorite' => 0],
            
            // Healthcare & Medical Devices (400-450)
            ['name' => 'Dexcom Inc.', 'symbol' => 'DXCM', 'favorite' => 0],
            ['name' => 'Intuitive Surgical Inc.', 'symbol' => 'ISRG', 'favorite' => 0],
            ['name' => 'Veracyte Inc.', 'symbol' => 'VCYT', 'favorite' => 0],
            ['name' => 'Guardant Health Inc.', 'symbol' => 'GH', 'favorite' => 0],
            ['name' => 'Teladoc Health Inc.', 'symbol' => 'TDOC', 'favorite' => 0],
            ['name' => 'Veeva Systems Inc.', 'symbol' => 'VEEV', 'favorite' => 0],
            ['name' => 'IQVIA Holdings Inc.', 'symbol' => 'IQV', 'favorite' => 0],
            ['name' => 'Illumina Inc.', 'symbol' => 'ILMN', 'favorite' => 0],
            ['name' => 'Pacific Biosciences of California Inc.', 'symbol' => 'PACB', 'favorite' => 0],
            ['name' => '10x Genomics Inc.', 'symbol' => 'TXG', 'favorite' => 0],
            
            // Electric Vehicles & Clean Energy (450-500)
            ['name' => 'Rivian Automotive Inc.', 'symbol' => 'RIVN', 'favorite' => 0],
            ['name' => 'Lucid Group Inc.', 'symbol' => 'LCID', 'favorite' => 0],
            ['name' => 'NIO Inc.', 'symbol' => 'NIO', 'favorite' => 0],
            ['name' => 'XPeng Inc.', 'symbol' => 'XPEV', 'favorite' => 0],
            ['name' => 'Li Auto Inc.', 'symbol' => 'LI', 'favorite' => 0],
            ['name' => 'ChargePoint Holdings Inc.', 'symbol' => 'CHPT', 'favorite' => 0],
            ['name' => 'Blink Charging Co.', 'symbol' => 'BLNK', 'favorite' => 0],
            ['name' => 'Wallbox N.V.', 'symbol' => 'WBX', 'favorite' => 0],
            ['name' => 'QuantumScape Corporation', 'symbol' => 'QS', 'favorite' => 0],
            ['name' => 'Solid Power Inc.', 'symbol' => 'SLDP', 'favorite' => 0],
        ];

        // Insert companies in chunks for better performance
        $chunks = array_chunk($companies, 50);
        
        foreach ($chunks as $chunk) {
            DB::table('companies')->insert($chunk);
        }
        
        $this->command->info('Successfully seeded ' . count($companies) . ' NASDAQ companies!');
    }
}

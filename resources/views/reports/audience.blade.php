<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Audience Insights Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .chart {
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Audience Insights Report</h1>
        <p>
            @if($account)
                For {{ $account->platform }} Account: {{ $account->username }}
            @else
                For Team: {{ $team->name }}
            @endif
        </p>
        <p>Generated on: {{ $generatedAt->format('F j, Y g:i A') }}</p>
    </div>

    <div class="section">
        <h2 class="section-title">Overview</h2>
        <table>
            <tr>
                <th>Total Followers</th>
                <td>{{ number_format($data['overview']['total_followers']) }}</td>
            </tr>
            <tr>
                <th>Total Engagement</th>
                <td>{{ number_format($data['overview']['total_engagement']) }}</td>
            </tr>
            <tr>
                <th>Average Engagement Rate</th>
                <td>{{ number_format($data['overview']['average_engagement_rate'], 2) }}%</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Demographics</h2>
        @foreach($data['demographics'] as $category => $values)
            <h3>{{ ucfirst($category) }}</h3>
            <table>
                <tr>
                    <th>Category</th>
                    <th>Percentage</th>
                </tr>
                @foreach($values as $key => $value)
                    <tr>
                        <td>{{ $key }}</td>
                        <td>{{ number_format($value, 1) }}%</td>
                    </tr>
                @endforeach
            </table>
        @endforeach
    </div>

    <div class="section">
        <h2 class="section-title">Top Interests</h2>
        <table>
            <tr>
                <th>Interest</th>
                <th>Percentage</th>
            </tr>
            @foreach($data['interests'] as $interest => $percentage)
                <tr>
                    <td>{{ $interest }}</td>
                    <td>{{ number_format($percentage, 1) }}%</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Active Hours</h2>
        <table>
            <tr>
                <th>Hour</th>
                <th>Activity Level</th>
            </tr>
            @foreach($data['active_hours'] as $hour => $level)
                <tr>
                    <td>{{ $hour }}</td>
                    <td>{{ number_format($level, 1) }}%</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Content Preferences</h2>
        <table>
            <tr>
                <th>Content Type</th>
                <th>Percentage</th>
            </tr>
            @foreach($data['content_preferences'] as $type => $percentage)
                <tr>
                    <td>{{ $type }}</td>
                    <td>{{ number_format($percentage, 1) }}%</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <h2 class="section-title">Audience Clusters</h2>
        @foreach($data['clusters'] as $cluster)
            <h3>{{ $cluster->name }}</h3>
            <table>
                <tr>
                    <th>Size</th>
                    <td>{{ number_format($cluster->size) }}</td>
                </tr>
                <tr>
                    <th>Engagement Rate</th>
                    <td>{{ number_format($cluster->engagement_rate, 2) }}%</td>
                </tr>
                <tr>
                    <th>Best Posting Time</th>
                    <td>{{ $cluster->best_posting_time }}</td>
                </tr>
            </table>

            @if(isset($aiInsights[$cluster->id]))
                <h4>AI Recommendations</h4>
                <table>
                    <tr>
                        <th>Content Types</th>
                        <td>{{ implode(', ', $aiInsights[$cluster->id]['content_types']) }}</td>
                    </tr>
                    <tr>
                        <th>Recommended Topics</th>
                        <td>{{ implode(', ', $aiInsights[$cluster->id]['topics']) }}</td>
                    </tr>
                    <tr>
                        <th>Engagement Strategies</th>
                        <td>{{ implode(', ', $aiInsights[$cluster->id]['engagement_strategies']) }}</td>
                    </tr>
                </table>
            @endif
        @endforeach
    </div>

    <div class="footer">
        <p>This report was generated automatically by PostFlex AI</p>
        <p>© {{ date('Y') }} PostFlex AI. All rights reserved.</p>
    </div>
</body>
</html> 
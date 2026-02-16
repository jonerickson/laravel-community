<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Dispute Defense Package</title>
        <style>
            @page {
                margin: 40px;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 12px;
                line-height: 1.5;
                color: #1a1a1a;
            }

            .header {
                border-bottom: 2px solid #1a1a1a;
                padding-bottom: 16px;
                margin-bottom: 24px;
            }

            .header-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
            }

            .company-name {
                font-size: 18px;
                font-weight: 700;
                letter-spacing: 1px;
            }

            .company-address {
                font-size: 10px;
                color: #666;
                margin-top: 4px;
            }

            .report-date {
                font-size: 10px;
                color: #666;
                text-align: right;
            }

            .document-title {
                font-size: 16px;
                font-weight: 700;
                text-align: center;
                margin: 24px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .section {
                margin-bottom: 24px;
                break-inside: avoid;
            }

            .section-title {
                font-size: 13px;
                font-weight: 700;
                background-color: #f0f0f0;
                padding: 6px 10px;
                margin-bottom: 12px;
                border-left: 3px solid #1a1a1a;
                break-after: avoid;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 12px;
            }

            th,
            td {
                padding: 6px 10px;
                text-align: left;
                border-bottom: 1px solid #e0e0e0;
                font-size: 11px;
            }

            th {
                font-weight: 600;
                background-color: #fafafa;
                font-size: 10px;
                text-transform: uppercase;
                text-wrap: nowrap;
                letter-spacing: 0.5px;
                color: #666;
            }

            .disclaimer {
                margin-top: 32px;
                padding: 16px;
                background-color: #fafafa;
                border: 1px solid #e0e0e0;
                font-size: 10px;
                color: #444;
                line-height: 1.6;
                break-inside: avoid;
            }

            .disclaimer-title {
                font-weight: 700;
                font-size: 11px;
                margin-bottom: 8px;
            }

            .no-data {
                color: #999;
                font-style: italic;
                padding: 8px 10px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="header-top">
                <div>
                    <div class="company-name">REACT Studios</div>
                    <div class="company-address">3655 Torrance Blvd, 3rd Floor #6015, Torrance, CA 90503</div>
                </div>
                <div class="report-date">Report Generated: {{ now()->format('F j, Y') }}</div>
            </div>
        </div>

        <div class="document-title">Official Certificate of Digital Fulfillment</div>

        {{-- Section A: Transaction Summary --}}
        <div class="section">
            <div class="section-title">Section A: Transaction Summary</div>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Order ID</td>
                        <td>{{ $data->externalOrderId ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Invoice ID</td>
                        <td>{{ $data->externalInvoiceId ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Event ID</td>
                        <td>{{ $data->externalEventId ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Internal ID</td>
                        <td>{{ $data->referenceId }}</td>
                    </tr>
                    <tr>
                        <td>Amount</td>
                        <td>${{ number_format($data->amountPaid, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>{{ $data->status }}</td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td>{{ $data->orderCreatedAt->format('F j, Y g:i A T') }}</td>
                    </tr>
                    <tr>
                        <td>Customer Email</td>
                        <td>{{ $data->userEmail }}</td>
                    </tr>
                    @if ($data->customerId)
                        <tr>
                            <td>Customer ID</td>
                            <td>{{ $data->customerId }}</td>
                        </tr>
                    @endif

                    @foreach ($data->integrations as $integration)
                        <tr>
                            <td>{{ ucfirst($integration['provider']) }} ID</td>
                            <td>{{ $integration['provider_id'] }}</td>
                        </tr>
                        @if ($integration['provider_name'])
                            <tr>
                                <td>{{ ucfirst($integration['provider']) }} Username</td>
                                <td>{{ $integration['provider_name'] }}</td>
                            </tr>
                        @endif
                    @endforeach

                    <tr>
                        <td>Account Created</td>
                        <td>{{ $data->userCreatedAt->format('F j, Y g:i A T') }}</td>
                    </tr>
                </tbody>
            </table>

            @if (count($data->orderItems) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data->orderItems as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td>${{ number_format($item['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Section B: Proof of Consent --}}
        <div class="section">
            <div class="section-title">Section B: Proof of Consent</div>
            @if (count($data->consents) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Policy</th>
                            <th>Version</th>
                            <th>Agreed At</th>
                            <th>IP Address</th>
                            <th>Fingerprint ID</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data->consents as $consent)
                            <tr>
                                <td>{{ $consent['title'] }}</td>
                                <td>{{ $consent['version'] ?? 'N/A' }}</td>
                                <td>{{ $consent['consented_at']?->format('F j, Y g:i A T') ?? 'N/A' }}</td>
                                <td>{{ $consent['ip_address'] ?? 'N/A' }}</td>
                                <td>{{ $consent['fingerprint_id'] ?? 'N/A' }}</td>
                                <td>{{ $consent['user_agent'] ?? 'N/A' }}</td>
                            </tr>
                            @if ($consent['url'])
                                <tr>
                                    <td colspan="6" style="font-size: 10px; color: #666; border-bottom: 2px solid #e0e0e0">
                                        View policy:
                                        <a href="{{ $consent['url'] }}" style="color: #2563eb; text-decoration: underline">{{ $consent['url'] }}</a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <p style="font-size: 10px; color: #666; padding: 4px 10px">
                    The customer explicitly agreed to the above policies by performing a consent action (e.g., checkbox acceptance) at the timestamps
                    shown.
                </p>
            @else
                <p class="no-data">No policy consent records found for this user.</p>
            @endif
        </div>

        {{-- Section C: Technical Delivery Logs --}}
        <div class="section">
            <div class="section-title">Section C: Technical Delivery Logs</div>
            @if (count($data->orderItems) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Fulfillment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data->orderItems as $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td style="color: #16a34a; font-weight: 600">Fulfilled</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="no-data">No order items found.</p>
            @endif
        </div>

        {{-- Section D: Post-Purchase Activity --}}
        <div class="section">
            <div class="section-title">Section D: Post-Purchase Activity</div>
            @if (count($data->activityLogs) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Event</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data->activityLogs as $log)
                            <tr>
                                <td>{{ $log['description'] }}</td>
                                <td>{{ $log['event'] }}</td>
                                <td>{{ $log['created_at']?->format('F j, Y g:i A T') ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="no-data">No post-purchase activity records found.</p>
            @endif
        </div>

        {{-- Section E: Service Access Logs --}}
        <div class="section">
            <div class="section-title">Section E: Service Access Logs</div>
            @if (count($data->accessLogs) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data->accessLogs as $log)
                            <tr>
                                <td>{{ $log['endpoint'] }}</td>
                                <td>{{ strtoupper($log['method']) }}</td>
                                <td>{{ $log['status'] ?? 'N/A' }}</td>
                                <td>{{ $log['created_at']?->format('F j, Y g:i A T') ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p style="font-size: 10px; color: #666; padding: 4px 10px">
                    The above logs (limited to the 10 most recent) demonstrate that the service was accessed using identifiers associated with the
                    customer's account.
                </p>
            @else
                <p class="no-data">No service access logs found for this user.</p>
            @endif
        </div>

        {{-- Legal Disclaimer --}}
        <div class="disclaimer">
            <div class="disclaimer-title">Legal Disclaimer</div>
            <p>
                This document constitutes an official record of digital fulfillment generated by REACT Studios. The customer referenced in this
                document consented to the applicable Terms of Service prior to completing their purchase. By agreeing to these terms, the customer
                acknowledged the nature of digital goods delivery and waived the right of withdrawal upon successful fulfillment of the digital
                content. All timestamps are recorded at the time of the respective events and are maintained in our system of record.
            </p>
        </div>
    </body>
</html>

<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Ubnt\UcrmPluginSdk\Service\UcrmApi;

// Get UCRM API instance
$api = UcrmApi::create();

// Fetch all tickets for display in admin panel
$tickets = [];
try {
    $tickets = $api->get('ticketing/tickets');
} catch (Exception $e) {
    $tickets = [];
}

// Sort tickets by created date (newest first)
usort($tickets, function($a, $b) {
    return strtotime($b['createdDate'] ?? '0') - strtotime($a['createdDate'] ?? '0');
});

// Get recent tickets (last 50)
$recentTickets = array_slice($tickets, 0, 50);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Support Ticketing - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 16px;
            padding: 24px 32px;
            margin-bottom: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #1a1a2e;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .stat-card h3 {
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .stat-card.total .number { color: #667eea; }
        .stat-card.open .number { color: #f59e0b; }
        .stat-card.pending .number { color: #3b82f6; }
        .stat-card.solved .number { color: #10b981; }
        
        .tickets-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .tickets-section h2 {
            color: #1a1a2e;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .tickets-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .tickets-table th {
            text-align: left;
            padding: 12px 16px;
            background: #f9fafb;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .tickets-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
            font-size: 14px;
        }
        
        .tickets-table tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.open { background: #fef3c7; color: #d97706; }
        .status-badge.pending { background: #dbeafe; color: #2563eb; }
        .status-badge.solved { background: #d1fae5; color: #059669; }
        .status-badge.closed { background: #e5e7eb; color: #6b7280; }
        
        .public-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 16px;
        }
        
        .public-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .concern-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎫 Technical Support Ticketing</h1>
            <p>Manage and view customer support tickets submitted through the public portal</p>
        </div>
        
        <?php
        $totalTickets = count($tickets);
        $openTickets = count(array_filter($tickets, fn($t) => ($t['status'] ?? 0) == 0));
        $pendingTickets = count(array_filter($tickets, fn($t) => ($t['status'] ?? 0) == 1));
        $solvedTickets = count(array_filter($tickets, fn($t) => ($t['status'] ?? 0) == 2));
        ?>
        
        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Total Tickets</h3>
                <div class="number"><?= $totalTickets ?></div>
            </div>
            <div class="stat-card open">
                <h3>Open</h3>
                <div class="number"><?= $openTickets ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending</h3>
                <div class="number"><?= $pendingTickets ?></div>
            </div>
            <div class="stat-card solved">
                <h3>Solved</h3>
                <div class="number"><?= $solvedTickets ?></div>
            </div>
        </div>
        
        <div class="tickets-section">
            <h2>Recent Tickets</h2>
            
            <?php if (empty($recentTickets)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p>No tickets found. Tickets submitted through the public form will appear here.</p>
                </div>
            <?php else: ?>
                <table class="tickets-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                            <tr>
                                <td>#<?= htmlspecialchars((string)($ticket['id'] ?? '')) ?></td>
                                <td class="concern-preview"><?= htmlspecialchars($ticket['subject'] ?? 'No Subject') ?></td>
                                <td><?= htmlspecialchars($ticket['clientFirstName'] ?? '') ?> <?= htmlspecialchars($ticket['clientLastName'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $status = $ticket['status'] ?? 0;
                                    $statusClass = 'open';
                                    $statusText = 'Open';
                                    if ($status == 1) { $statusClass = 'pending'; $statusText = 'Pending'; }
                                    elseif ($status == 2) { $statusClass = 'solved'; $statusText = 'Solved'; }
                                    elseif ($status == 3) { $statusClass = 'closed'; $statusText = 'Closed'; }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                                <td><?= isset($ticket['createdDate']) ? date('M j, Y g:i A', strtotime($ticket['createdDate'])) : 'N/A' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

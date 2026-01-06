<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Ubnt\UcrmPluginSdk\Service\UcrmApi;
use Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager;

// Debug mode - set to true to see detailed errors
$debugMode = true;
$debugInfo = '';

$api = null;
$apiError = '';

try {
    $api = UcrmApi::create();
} catch (Exception $e) {
    $apiError = 'API Init Error: ' . $e->getMessage();
}

$message = '';
$messageType = '';
$accountNumber = '';
$concern = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountNumber = trim($_POST['account_number'] ?? '');
    $concern = trim($_POST['concern'] ?? '');
    
    // Check if API is available
    if ($api === null) {
        $message = $debugMode ? $apiError : 'System temporarily unavailable. Please try again later.';
        $messageType = 'error';
    } elseif (empty($accountNumber)) {
        $message = 'Account Number is required.';
        $messageType = 'error';
    } elseif (empty($concern)) {
        $message = 'Please describe your concern.';
        $messageType = 'error';
    } elseif (strlen($concern) > 1000) {
        $message = 'Concern must be 1000 characters or less.';
        $messageType = 'error';
    } else {
        // Search for client by Custom ID (userIdent)
        try {
            if ($debugMode) $debugInfo .= "Fetching clients... ";
            $clients = $api->get('clients');
            if ($debugMode) $debugInfo .= "Got " . count($clients) . " clients. ";
            
            $foundClient = null;
            
            foreach ($clients as $client) {
                // Check if userIdent (Custom ID) matches the account number
                if (isset($client['userIdent']) && $client['userIdent'] === $accountNumber) {
                    $foundClient = $client;
                    break;
                }
            }
            
            if ($foundClient === null) {
                $message = 'Account number cannot be found.';
                $messageType = 'error';
            } else {
                if ($debugMode) $debugInfo .= "Found client ID: " . $foundClient['id'] . ". ";
                
                // Get client name for subject
                $clientName = '';
                if (!empty($foundClient['firstName']) || !empty($foundClient['lastName'])) {
                    $clientName = trim(($foundClient['firstName'] ?? '') . ' ' . ($foundClient['lastName'] ?? ''));
                } elseif (!empty($foundClient['companyName'])) {
                    $clientName = $foundClient['companyName'];
                } else {
                    $clientName = 'Account #' . $accountNumber;
                }
                
                // Create ticket for the found client (without message - add as comment after)
                $ticketData = [
                    'clientId' => (int)$foundClient['id'],
                    'subject' => 'Support Request: ' . substr($concern, 0, 100),
                    'status' => 0
                ];
                
                if ($debugMode) $debugInfo .= "Creating ticket with: " . json_encode($ticketData) . "... ";
                
                $response = $api->post('ticketing/tickets', $ticketData);
                
                if (isset($response['id'])) {
                    $ticketId = $response['id'];
                    if ($debugMode) $debugInfo .= "Ticket #$ticketId created! Adding comment... ";
                    
                    // Add the concern as a comment to the ticket
                    $commentAdded = false;
                    $commentEndpoints = [
                        'ticketing/tickets/' . $ticketId . '/comments',
                        'ticketing/tickets/' . $ticketId . '/activity'
                    ];
                    
                    foreach ($commentEndpoints as $commentEndpoint) {
                        if ($commentAdded) break;
                        
                        // Try different field names for the comment body
                        $fieldNames = ['body', 'content', 'message', 'text'];
                        
                        foreach ($fieldNames as $fieldName) {
                            try {
                                $commentData = [
                                    'public' => true,
                                    $fieldName => $concern
                                ];
                                $api->post($commentEndpoint, $commentData);
                                $commentAdded = true;
                                if ($debugMode) $debugInfo .= "Comment added via $commentEndpoint ($fieldName). ";
                                break;
                            } catch (Exception $ce) {
                                if ($debugMode) $debugInfo .= "$commentEndpoint/$fieldName failed. ";
                            }
                        }
                    }
                    
                    $message = 'Your support ticket has been submitted successfully! Ticket ID: #' . $ticketId;
                    $messageType = 'success';
                    // Clear form on success
                    $accountNumber = '';
                    $concern = '';
                } else {
                    $message = $debugMode ? 'Failed to create ticket. Response: ' . json_encode($response) : 'Failed to create ticket. Please try again.';
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            if ($debugMode) {
                $message = 'Error: ' . $errorMsg;
            } else {
                // Show more helpful error for production
                if (strpos($errorMsg, '404') !== false) {
                    $message = 'Ticketing system not available. Please contact support.';
                } elseif (strpos($errorMsg, '401') !== false || strpos($errorMsg, '403') !== false) {
                    $message = 'Permission denied. Please contact support.';
                } else {
                    $message = 'An error occurred. Please try again later.';
                }
            }
            $messageType = 'error';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Support Ticket</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 500px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 32px;
            text-align: center;
        }
        
        .card-header .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
        }
        
        .card-header h1 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .card-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .card-body {
            padding: 32px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group label .required {
            color: #ef4444;
            margin-left: 4px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #f9fafb;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #9ca3af;
        }
        
        .form-group textarea {
            min-height: 160px;
            resize: vertical;
        }
        
        .char-count {
            text-align: right;
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }
        
        .char-count.warning {
            color: #f59e0b;
        }
        
        .char-count.danger {
            color: #ef4444;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .message-icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .help-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
        }
        
        .footer a {
            color: white;
            text-decoration: none;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.3s ease-in-out;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="icon">🎫</div>
                <h1>Technical Support</h1>
                <p>Submit a support ticket and we'll get back to you soon</p>
            </div>
            
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="message <?= $messageType ?>">
                        <span class="message-icon"><?= $messageType === 'success' ? '✅' : '❌' ?></span>
                        <span><?= htmlspecialchars($message) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($debugMode && $debugInfo): ?>
                    <div class="message" style="background: #e0e7ff; color: #3730a3; border: 1px solid #a5b4fc;">
                        <span class="message-icon">🔍</span>
                        <span style="font-size: 12px; word-break: break-all;"><?= htmlspecialchars($debugInfo) ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="ticketForm">
                    <div class="form-group">
                        <label for="account_number">
                            Account Number<span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="account_number" 
                            name="account_number" 
                            placeholder="Enter your account number"
                            value="<?= htmlspecialchars($accountNumber) ?>"
                            required
                            autocomplete="off"
                        >
                        <p class="help-text">Your account number can be found on your billing statement</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="concern">
                            Your Concern<span class="required">*</span>
                        </label>
                        <textarea 
                            id="concern" 
                            name="concern" 
                            placeholder="Please describe your issue or concern in detail..."
                            required
                            maxlength="1000"
                        ><?= htmlspecialchars($concern) ?></textarea>
                        <div class="char-count" id="charCount">
                            <span id="currentChars">0</span> / 1000 characters
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">
                        <span id="btnText">Submit Ticket</span>
                    </button>
                </form>
            </div>
        </div>
        
        <div class="footer">
            <p>Need immediate assistance? Contact us directly.</p>
        </div>
    </div>
    
    <script>
        // Character counter
        const concernTextarea = document.getElementById('concern');
        const charCount = document.getElementById('charCount');
        const currentChars = document.getElementById('currentChars');
        
        function updateCharCount() {
            const length = concernTextarea.value.length;
            currentChars.textContent = length;
            
            charCount.classList.remove('warning', 'danger');
            if (length > 900) {
                charCount.classList.add('danger');
            } else if (length > 750) {
                charCount.classList.add('warning');
            }
        }
        
        concernTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count
        
        // Form submission handling
        const form = document.getElementById('ticketForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        
        form.addEventListener('submit', function(e) {
            const accountNumber = document.getElementById('account_number').value.trim();
            const concern = concernTextarea.value.trim();
            
            if (!accountNumber || !concern) {
                e.preventDefault();
                form.classList.add('shake');
                setTimeout(() => form.classList.remove('shake'), 300);
                return;
            }
            
            submitBtn.disabled = true;
            btnText.innerHTML = '<span class="loading"></span> Submitting...';
        });
    </script>
</body>
</html>

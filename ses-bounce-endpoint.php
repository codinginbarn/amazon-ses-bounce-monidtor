<?php
// Fail-fast: only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    exit('Empty request');
}

// Always log raw payload for debugging
file_put_contents(__DIR__ . "/sns-debug.log", date('c') . " | " . $raw . PHP_EOL, FILE_APPEND);

$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['Type'])) {
    http_response_code(400);
    exit('Invalid SNS message');
}

// --- Handle Subscription Confirmation ---
if ($data['Type'] === 'SubscriptionConfirmation' && isset($data['SubscribeURL'])) {
    // Use curl to confirm subscription
    $ch = curl_init($data['SubscribeURL']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);

    file_put_contents(__DIR__ . "/sns-debug.log", date('c') . " | Subscription confirmed" . PHP_EOL, FILE_APPEND);
    http_response_code(200);
    exit("OK");
}

// --- Handle Notification (Bounce / Complaint) ---
if ($data['Type'] === 'Notification' && isset($data['Message'])) {
    $msg = json_decode($data['Message'], true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        file_put_contents(__DIR__ . "/sns-debug.log",
            date('c') . " | Non-JSON Message: " . $data['Message'] . PHP_EOL,
            FILE_APPEND
        );
    } elseif (isset($msg['notificationType'])) {

        // Robust subject extraction: commonHeaders.subject OR headers[name=Subject]
        $subject = 'n/a';
        if (!empty($msg['mail']['commonHeaders']['subject'])) {
            $subject = $msg['mail']['commonHeaders']['subject'];
        } elseif (!empty($msg['mail']['headers']) && is_array($msg['mail']['headers'])) {
            foreach ($msg['mail']['headers'] as $h) {
                if (isset($h['name']) && strtolower($h['name']) === 'subject') {
                    $subject = $h['value'] ?? 'n/a';
                    break;
                }
            }
        }

        // Also capture source and destinations for context
        $source       = $msg['mail']['source'] ?? 'n/a';
        $destinations = implode(',', $msg['mail']['destination'] ?? []);

        if ($msg['notificationType'] === 'Bounce') {
            $bounceType    = $msg['bounce']['bounceType']    ?? 'Unknown';
            $bounceSubType = $msg['bounce']['bounceSubType'] ?? 'n/a';
            $reportingMTA  = $msg['bounce']['reportingMTA']  ?? 'n/a';

            foreach ($msg['bounce']['bouncedRecipients'] as $recipient) {
                $email          = $recipient['emailAddress']   ?? 'unknown';
                $status         = $recipient['status']         ?? 'n/a';
                $action         = $recipient['action']         ?? 'n/a';
                $diagnosticCode = $recipient['diagnosticCode'] ?? 'n/a';

                // Log format (11 fields)
                file_put_contents(
                    __DIR__ . "/bounces.log",
                    date('c') . " | $email | $subject | $source | $destinations | $bounceType | $bounceSubType | $status | $action | $diagnosticCode | $reportingMTA" . PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );
            }
        } elseif ($msg['notificationType'] === 'Complaint') {
            $feedbackType = $msg['complaint']['complaintFeedbackType'] ?? 'Complaint';
            $subType      = $msg['complaint']['complaintSubType']      ?? 'n/a';
            $userAgent    = $msg['complaint']['userAgent']             ?? 'n/a';
            $arrivalDate  = $msg['complaint']['arrivalDate']           ?? 'n/a';

            foreach ($msg['complaint']['complainedRecipients'] as $recipient) {
                $email = $recipient['emailAddress'] ?? 'unknown';

                // Log format (9 fields)
                file_put_contents(
                    __DIR__ . "/complaints.log",
                    date('c') . " | $email | $subject | $source | $destinations | $feedbackType | $subType | $userAgent | $arrivalDate" . PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );
            }
        }
    }
}

http_response_code(200);
echo "OK";

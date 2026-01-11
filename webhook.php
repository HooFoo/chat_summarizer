<?php

require_once 'config.php';
require_once 'database.php';
require_once 'gemini.php';

// Get updates from Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$db = Database::getInstance();

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $chatTitle = $message['chat']['title'] ?? 'Private Chat';
    $messageId = $message['message_id'];
    $userId = $message['from']['id'];
    $username = $message['from']['username'] ?? null;
    $firstName = $message['from']['first_name'] ?? null;
    $lastName = $message['from']['last_name'] ?? null;
    $timestamp = $message['date'];
    $text = $message['text'] ?? $message['caption'] ?? '';
    $replyToId = $message['reply_to_message']['message_id'] ?? null;

    // Detect media type
    $mediaType = 'text';
    if (isset($message['photo'])) $mediaType = 'photo';
    elseif (isset($message['video'])) $mediaType = 'video';
    elseif (isset($message['document'])) $mediaType = 'document';
    elseif (isset($message['voice'])) $mediaType = 'voice';
    elseif (isset($message['audio'])) $mediaType = 'audio';
    elseif (isset($message['sticker'])) $mediaType = 'sticker';
    elseif (isset($message['animation'])) $mediaType = 'animation';
    elseif (isset($message['video_note'])) $mediaType = 'video_note';

    // Update chat info
    $db->updateChat($chatId, $chatTitle);

    // Save message to DB
    $db->saveMessage([
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'user_id' => $userId,
        'username' => $username,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'text' => $text,
        'reply_to_message_id' => $replyToId,
        'media_type' => $mediaType,
        'timestamp' => $timestamp
    ]);

    // Handle commands
    if (strpos($text, '/') === 0) {
        handleCommand($message, $db);
    }
}

function handleCommand($message, $db) {
    $text = $message['text'] ?? '';
    $chatId = $message['chat']['id'];
    $parts = explode(' ', $text);
    $command = $parts[0];
    
    // Support command with bot username (e.g. /summary@bot_name)
    $command = explode('@', $command)[0];

    if (strpos($command, '/summary') === 0) {
        $referenceDate = time();
        $datePart = null;

        if ($command === '/summary' || $command === '/summary_users' || $command === '/summary_tasks') {
            if (isset($parts[1])) {
                $datePart = $parts[1];
            }
        }

        if ($datePart) {
            $parsedDate = strtotime($datePart);
            if ($parsedDate !== false) {
                $referenceDate = $parsedDate;
            }
        }

        $startTimestamp = $referenceDate - (DEFAULT_SUMMARY_DAYS * 24 * 60 * 60);
        $endTimestamp = $referenceDate;

        $messages = $db->getMessagesForPeriod($chatId, $startTimestamp, $endTimestamp);

        if (empty($messages)) {
            sendTelegramMessage($chatId, "Сообщений за выбранный период не найдено.", $message['message_id']);
            return;
        }

        $formattedTranscript = formatTranscript($messages);
        
        $gemini = new GeminiClient();
        
        $systemPrompt = PROMPT_GENERAL;
        if ($command === '/summary_users') {
            $systemPrompt = PROMPT_USERS;
        } elseif ($command === '/summary_tasks') {
            $systemPrompt = PROMPT_TASKS;
        }
        
        $summary = $gemini->generateSummary($formattedTranscript, $systemPrompt);
        
        sendTelegramMessage($chatId, $summary, $message['message_id']);
    }
}

function formatTranscript($messages) {
    $output = "";
    foreach ($messages as $msg) {
        $date = date("Y-m-d H:i:s", $msg['timestamp']);
        $author = $msg['username'] ?: ($msg['first_name'] . ' ' . $msg['last_name']);
        $author = trim($author);
        
        $replyInfo = $msg['reply_to_message_id'] ? " (в ответ на #{$msg['reply_to_message_id']})" : "";
        $mediaInfo = $msg['media_type'] !== 'text' ? "[Медиа: {$msg['media_type']}] " : "";
        
        $output .= "{$date} [#{$msg['message_id']}] {$author}{$replyInfo}: {$mediaInfo}{$msg['text']}\n";
    }
    return $output;
}

function sendTelegramMessage($chatId, $text, $replyToId = null) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    if ($replyToId) {
        $data['reply_to_message_id'] = $replyToId;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        log_message("Telegram API error (HTTP $httpCode): " . $response);
    }
}

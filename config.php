<?php

// Telegram Bot Configuration
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ai_manager');
define('DB_USER', 'root');
define('DB_PASS', '');

// Gemini API Configuration
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
define('GEMINI_MODEL', 'gemini-1.5-pro'); // or gemini-1.5-flash

// Prompts
define('PROMPT_GENERAL', "Ты - ассистент, который делает краткое содержание переписки в чате. Сделай структурированный отчет о обсуждаемых темах, принятых решениях и важных деталях. Используй русский язык.");
define('PROMPT_USERS', "Ты - ассистент. Проанализируй сообщения в чате и сделай саммари по каждому активному пользователю: чем занимался, какие вопросы задавал, какие задачи решал. Используй русский язык.");
define('PROMPT_TASKS', "Ты - ассистент. Извлеки из переписки список конкретных задач (поставленных, в процессе, выполненных). Укажи ответственных, если они есть. Используй русский язык.");

// App Settings
define('DEFAULT_SUMMARY_DAYS', 30);

// Logging (optional but helpful)
define('LOG_FILE', __DIR__ . '/bot.log');

function log_message($message) {
    file_put_contents(LOG_FILE, "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL, FILE_APPEND);
}

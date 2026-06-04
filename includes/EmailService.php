<?php

require_once __DIR__ . '/../config/database.php';

final class EmailService
{
    private array $config;

    public function __construct()
    {
        $configPath = __DIR__ . '/../config/mail.php';
        $this->config = is_file($configPath) ? require $configPath : [];

        $localConfigPath = __DIR__ . '/../config/mail.local.php';
        if (is_file($localConfigPath)) {
            $this->config = array_merge($this->config, require $localConfigPath);
        }
    }

    public function smtpEnabled(): bool
    {
        return !empty($this->config['enabled']);
    }

    public function queueToUser(int $userId, string $subject, string $body): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare('SELECT email FROM users WHERE user_id = ? LIMIT 1');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user) {
                $this->queue($userId, $user['email'], $subject, $body);
            }
        } catch (mysqli_sql_exception) {
            // Email logging should not block the main admission workflow.
        }
    }

    public function queue(?int $userId, string $recipientEmail, string $subject, string $body): void
    {
        $status = 'Queued';
        $errorMessage = null;

        if ($this->smtpEnabled()) {
            try {
                $this->sendSmtp($recipientEmail, $subject, $body);
                $status = 'Sent';
            } catch (Throwable $exception) {
                $status = 'Failed';
                $errorMessage = $exception->getMessage();
            }
        }

        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                'INSERT INTO email_logs (user_id, recipient_email, subject, body, status, error_message)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('isssss', $userId, $recipientEmail, $subject, $body, $status, $errorMessage);
            $stmt->execute();
        } catch (mysqli_sql_exception) {
            // The email_logs table may not exist until schema.sql is imported.
        }
    }

    public function recentLogs(int $limit = 20): array
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                'SELECT * FROM email_logs ORDER BY created_at DESC LIMIT ?'
            );
            $stmt->bind_param('i', $limit);
            $stmt->execute();

            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (mysqli_sql_exception) {
            return [];
        }
    }

    private function sendSmtp(string $recipientEmail, string $subject, string $body): void
    {
        $host = trim((string) ($this->config['host'] ?? ''));
        $port = (int) ($this->config['port'] ?? 0);
        $username = trim((string) ($this->config['username'] ?? ''));
        $password = (string) ($this->config['password'] ?? '');
        $encryption = strtolower((string) ($this->config['encryption'] ?? 'tls'));
        $fromEmail = trim((string) ($this->config['from_email'] ?? $username));
        $fromName = trim((string) ($this->config['from_name'] ?? 'WKU International Admission'));

        if ($host === '' || $port <= 0 || $username === '' || $password === '' || $fromEmail === '') {
            throw new RuntimeException('SMTP settings are incomplete.');
        }

        $target = $encryption === 'ssl' ? 'ssl://' . $host . ':' . $port : $host . ':' . $port;
        $socket = @stream_socket_client($target, $errno, $error, 20, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            throw new RuntimeException('SMTP connection failed: ' . $error);
        }

        stream_set_timeout($socket, 20);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO localhost', [250]);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('SMTP TLS negotiation failed.');
                }
                $this->command($socket, 'EHLO localhost', [250]);
            }

            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($username), [334]);
            $this->command($socket, base64_encode($password), [235]);
            $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $recipientEmail . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $message = $this->buildMessage($fromEmail, $fromName, $recipientEmail, $subject, $body);
            fwrite($socket, $message . "\r\n.\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function buildMessage(string $fromEmail, string $fromName, string $recipientEmail, string $subject, string $body): string
    {
        $headers = [
            'From: ' . $this->formatAddress($fromEmail, $fromName),
            'To: <' . $recipientEmail . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $safeBody = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $body);

        return implode("\r\n", $headers) . "\r\n\r\n" . $safeBody;
    }

    private function formatAddress(string $email, string $name): string
    {
        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function command($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $expectedCodes);
    }

    private function expect($socket, array $expectedCodes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^\d{3}\s/', $line)) {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }

        return $response;
    }
}

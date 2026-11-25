<?php
// config/init.php
require_once __DIR__ . '/database.php';
/** @var PDO $pdo */

if (!class_exists('PdoSessionHandler')) {
    class PdoSessionHandler implements SessionHandlerInterface {
        private PDO $pdo;
        private static bool $tableChecked = false;

        public function __construct(PDO $pdo) {
            $this->pdo = $pdo;
            if (!self::$tableChecked) {
                $this->pdo->exec("
                    CREATE TABLE IF NOT EXISTS php_sessions (
                        id VARCHAR(128) PRIMARY KEY,
                        data TEXT NOT NULL,
                        timestamp INTEGER NOT NULL
                    )
                ");
                self::$tableChecked = true;
            }
        }

        public function open(string $path, string $name): bool {
            return true;
        }

        public function close(): bool {
            return true;
        }

        public function read(string $id): string|false {
            $stmt = $this->pdo->prepare("SELECT data FROM php_sessions WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $data = $stmt->fetchColumn();
            return $data !== false ? (string)$data : '';
        }

        public function write(string $id, string $data): bool {
            $stmt = $this->pdo->prepare("
                INSERT INTO php_sessions (id, data, timestamp)
                VALUES (:id, :data, :ts)
                ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, timestamp = EXCLUDED.timestamp
            ");
            return $stmt->execute([
                ':id'   => $id,
                ':data' => $data,
                ':ts'   => time(),
            ]);
        }

        public function destroy(string $id): bool {
            $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        }

        public function gc(int $max_lifetime): int|false {
            $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE timestamp < :expired");
            $stmt->execute([':expired' => time() - $max_lifetime]);
            return $stmt->rowCount();
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (isset($pdo) && ($pdo instanceof PDO)) {
        $handler = new PdoSessionHandler($pdo);
        session_set_save_handler($handler, true);
    }

    session_start();
}
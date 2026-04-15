<?php
/**
 * Database — Singleton wrapper cho mysqli
 * Cung cấp Prepared Statements an toàn, Transaction support
 */
class Database
{
    private static ?self $instance = null;
    private mysqli $conn;

    private function __construct()
    {
        // Lấy thông tin kết nối từ db_config (đã require trước)
        global $conn;
        if (!$conn instanceof mysqli) {
            throw new RuntimeException('Database connection chưa được khởi tạo. Hãy require db_config.php trước.');
        }
        $this->conn = $conn;
        $this->conn->set_charset('utf8mb4');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Reset instance (dùng khi test hoặc reconnect) ─────────────────────
    public static function reset(): void
    {
        self::$instance = null;
    }

    // ── Execute query với Prepared Statement ──────────────────────────────
    /**
     * @param string $sql   SQL với ? placeholders
     * @param array  $params Mảng giá trị tương ứng
     * @param string $types  Chuỗi type ('i'=int, 's'=string, 'd'=double, 'b'=blob)
     *                       Nếu để trống sẽ tự detect dựa trên type PHP
     * @return mysqli_result|bool
     */
    public function query(string $sql, array $params = [], string $types = ''): mysqli_result|bool
    {
        if (empty($params)) {
            $result = $this->conn->query($sql);
            if ($result === false) {
                throw new RuntimeException('DB Query Error: ' . $this->conn->error . ' | SQL: ' . $sql);
            }
            return $result;
        }

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('DB Prepare Error: ' . $this->conn->error . ' | SQL: ' . $sql);
        }

        if (empty($types)) {
            $types = $this->detectTypes($params);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();
        $stmt->close();

        return $result;
    }

    // ── Insert và trả về last insert ID ───────────────────────────────────
    public function insert(string $sql, array $params = [], string $types = ''): int
    {
        $this->query($sql, $params, $types);
        return (int)$this->conn->insert_id;
    }

    // ── Execute và trả về số hàng bị ảnh hưởng ───────────────────────────
    public function execute(string $sql, array $params = [], string $types = ''): int
    {
        if (empty($params)) {
            $this->conn->query($sql);
            return $this->conn->affected_rows;
        }

        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('DB Prepare Error: ' . $this->conn->error);
        }

        if (empty($types)) {
            $types = $this->detectTypes($params);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    // ── Lấy một hàng duy nhất ─────────────────────────────────────────────
    public function fetchOne(string $sql, array $params = [], string $types = ''): ?array
    {
        $result = $this->query($sql, $params, $types);
        if ($result instanceof mysqli_result) {
            $row = $result->fetch_assoc();
            $result->free();
            return $row ?: null;
        }
        return null;
    }

    // ── Lấy tất cả hàng ───────────────────────────────────────────────────
    public function fetchAll(string $sql, array $params = [], string $types = ''): array
    {
        $result = $this->query($sql, $params, $types);
        if ($result instanceof mysqli_result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $rows;
        }
        return [];
    }

    // ── Đếm số hàng ───────────────────────────────────────────────────────
    public function count(string $sql, array $params = [], string $types = ''): int
    {
        $result = $this->query($sql, $params, $types);
        if ($result instanceof mysqli_result) {
            $count = $result->num_rows;
            $result->free();
            return $count;
        }
        return 0;
    }

    // ── Transaction ───────────────────────────────────────────────────────
    public function beginTransaction(): void
    {
        $this->conn->begin_transaction();
    }

    public function commit(): void
    {
        $this->conn->commit();
    }

    public function rollback(): void
    {
        $this->conn->rollback();
    }

    // ── Escape string (dùng tạm khi cần) ─────────────────────────────────
    public function escape(string $value): string
    {
        return $this->conn->real_escape_string($value);
    }

    // ── Lấy last error ────────────────────────────────────────────────────
    public function getError(): string
    {
        return $this->conn->error;
    }

    // ── Auto-detect types từ PHP types ────────────────────────────────────
    private function detectTypes(array $params): string
    {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
}

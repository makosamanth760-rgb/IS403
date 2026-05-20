<?php
/**
 * Login password verification against staff/students tables in the wpu database.
 */

function verifyLoginPassword(string $plainPassword, string $storedPassword): bool {
    if ($plainPassword === '' || $storedPassword === '') {
        return false;
    }

    // Bcrypt hash from password_hash()
    if (preg_match('/^\$2[ayb]\$/', $storedPassword)) {
        return password_verify($plainPassword, $storedPassword);
    }

    // Legacy plaintext seed data (pass123, rainyday, password, etc.)
    return hash_equals($storedPassword, $plainPassword);
}

function shouldUpgradePasswordHash(string $storedPassword): bool {
    return !preg_match('/^\$2[ayb]\$/', $storedPassword);
}

function upgradeAccountPassword(mysqli $conn, string $table, string $idColumn, string $id, string $plainPassword): void {
    $allowed = [
        'students' => 'student_id',
        'staff' => 'staff_id',
    ];
    if (!isset($allowed[$table]) || $allowed[$table] !== $idColumn) {
        return;
    }

    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $sql = "UPDATE `{$table}` SET password = ? WHERE `{$idColumn}` = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $hash, $id);
        $stmt->execute();
        $stmt->close();
    }
}

function databaseTablesReady(mysqli $conn): bool {
    $required = ['staff', 'students'];
    foreach ($required as $table) {
        $safe = $conn->real_escape_string($table);
        $result = $conn->query("SHOW TABLES LIKE '{$safe}'");
        if (!$result || $result->num_rows === 0) {
            return false;
        }
    }
    return true;
}

<?php
include 'config.php';

$botConn = new mysqli($botDbHost, $botDbUser, $botDbPass, $botDbName);
if ($botConn->connect_error) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Bot DB connection failed: " . $botConn->connect_error . "\n", FILE_APPEND);
    exit;
}
$botConn->set_charset("utf8");

function checkAndCreateTablesAndColumns($botConn) {
    $tableAdminSettings = "CREATE TABLE IF NOT EXISTS `admin_settings` (
        `admin_id` int NOT NULL,
        `total_traffic` bigint DEFAULT NULL,
        `expiry_date` date DEFAULT NULL,
        `status` varchar(50) DEFAULT 'active',
        `user_limit` bigint DEFAULT NULL,
        PRIMARY KEY (`admin_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

    $botConn->query($tableAdminSettings);
    echo "Table `admin_settings` checked and created if necessary.\n";

    $tableUserStates = "CREATE TABLE IF NOT EXISTS `user_states` (
        `user_id` bigint NOT NULL,
        `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
        `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
        `state` varchar(50) DEFAULT NULL,
        `admin_id` int DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `data` text,
        `message_id` int DEFAULT NULL,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";

    $botConn->query($tableUserStates);
    echo "Table `user_states` checked and created if necessary.\n";

    $tableUserTemporaries = "CREATE TABLE IF NOT EXISTS `user_temporaries` (
        `user_id` int NOT NULL,
        `user_key` varchar(50) NOT NULL,
        `value` text,
        PRIMARY KEY (`user_id`, `user_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";

    $botConn->query($tableUserTemporaries);
    echo "Table `user_temporaries` checked and created if necessary.\n";

    $columnsAdminSettings = [
        'total_traffic' => "ALTER TABLE `admin_settings` ADD `total_traffic` bigint DEFAULT NULL;",
        'expiry_date' => "ALTER TABLE `admin_settings` ADD `expiry_date` date DEFAULT NULL;",
        'status' => "ALTER TABLE `admin_settings` ADD `status` varchar(50) DEFAULT 'active';",
        'user_limit' => "ALTER TABLE `admin_settings` ADD `user_limit` bigint DEFAULT NULL;"
    ];
    checkAndAddColumns($botConn, 'admin_settings', $columnsAdminSettings);

    $columnsUserStates = [
        'username' => "ALTER TABLE `user_states` ADD `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL;",
        'lang' => "ALTER TABLE `user_states` ADD `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL;",
        'state' => "ALTER TABLE `user_states` ADD `state` varchar(50) DEFAULT NULL;",
        'admin_id' => "ALTER TABLE `user_states` ADD `admin_id` int DEFAULT NULL;",
        'updated_at' => "ALTER TABLE `user_states` ADD `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;",
        'data' => "ALTER TABLE `user_states` ADD `data` text;",
        'message_id' => "ALTER TABLE `user_states` ADD `message_id` int DEFAULT NULL;"
    ];
    checkAndAddColumns($botConn, 'user_states', $columnsUserStates);

    $columnsUserTemporaries = [
        'value' => "ALTER TABLE `user_temporaries` ADD `value` text;"
    ];
    checkAndAddColumns($botConn, 'user_temporaries', $columnsUserTemporaries);
}

function checkAndAddColumns($botConn, $tableName, $columns) {
    foreach ($columns as $columnName => $alterQuery) {
        $result = $botConn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        if ($result->num_rows == 0) {
            if ($botConn->query($alterQuery) === TRUE) {
                echo "Column '$columnName' added to table '$tableName'.\n";
            } else {
                echo "Error adding column '$columnName' to table '$tableName': " . $botConn->error . "\n";
            }
        } else {
            echo "Column '$columnName' already exists in table '$tableName'.\n";
        }
    }
}

checkAndCreateTablesAndColumns($botConn);

$botConn->close();
?>

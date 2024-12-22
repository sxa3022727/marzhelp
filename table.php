<?php
include 'config.php';

$botConn = new mysqli($botDbHost, $botDbUser, $botDbPass, $botDbName);
if ($botConn->connect_error) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Bot DB connection failed: " . $botConn->connect_error . "\n", FILE_APPEND);
    exit(1); 
}
$botConn->set_charset("utf8");

function checkAndCreateTablesAndColumns($botConn) {
    $hasCriticalError = false;

    $tableAdminSettings = "CREATE TABLE IF NOT EXISTS `admin_settings` (
        `admin_id` int NOT NULL,
        `total_traffic` bigint DEFAULT NULL,
        `expiry_date` date DEFAULT NULL,
        `status` varchar(50) DEFAULT 'active',
        `user_limit` bigint DEFAULT NULL,
        PRIMARY KEY (`admin_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";

    if (!$botConn->query($tableAdminSettings)) {
        echo "Critical error creating `admin_settings`: " . $botConn->error . "\n";
        $hasCriticalError = true;
    }

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

    if (!$botConn->query($tableUserStates)) {
        echo "Critical error creating `user_states`: " . $botConn->error . "\n";
        $hasCriticalError = true;
    }

    $tableUserTemporaries = "CREATE TABLE IF NOT EXISTS `user_temporaries` (
        `user_id` int NOT NULL,
        `user_key` varchar(50) NOT NULL,
        `value` text,
        PRIMARY KEY (`user_id`, `user_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";

    if (!$botConn->query($tableUserTemporaries)) {
        echo "Critical error creating `user_temporaries`: " . $botConn->error . "\n";
        $hasCriticalError = true;
    }

    $columnsAdminSettings = [
        'total_traffic' => "ALTER TABLE `admin_settings` ADD `total_traffic` bigint DEFAULT NULL;",
        'expiry_date' => "ALTER TABLE `admin_settings` ADD `expiry_date` date DEFAULT NULL;",
        'status' => "ALTER TABLE `admin_settings` ADD `status` varchar(50) DEFAULT 'active';",
        'user_limit' => "ALTER TABLE `admin_settings` ADD `user_limit` bigint DEFAULT NULL;"
    ];
    $hasCriticalError = $hasCriticalError || checkAndAddColumns($botConn, 'admin_settings', $columnsAdminSettings);

    $columnsUserStates = [
        'username' => "ALTER TABLE `user_states` ADD `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL;",
        'lang' => "ALTER TABLE `user_states` ADD `lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL;",
        'state' => "ALTER TABLE `user_states` ADD `state` varchar(50) DEFAULT NULL;",
        'admin_id' => "ALTER TABLE `user_states` ADD `admin_id` int DEFAULT NULL;",
        'updated_at' => "ALTER TABLE `user_states` ADD `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;",
        'data' => "ALTER TABLE `user_states` ADD `data` text;",
        'message_id' => "ALTER TABLE `user_states` ADD `message_id` int DEFAULT NULL;",
        'template_index' => "ALTER TABLE `user_states` ADD COLUMN `template_index` INT DEFAULT 0 AFTER `message_id`;" 
    ];
    $hasCriticalError = $hasCriticalError || checkAndAddColumns($botConn, 'user_states', $columnsUserStates);

    $columnsUserTemporaries = [
        'value' => "ALTER TABLE `user_temporaries` ADD `value` text;"
    ];
    $hasCriticalError = $hasCriticalError || checkAndAddColumns($botConn, 'user_temporaries', $columnsUserTemporaries);

    return $hasCriticalError;
}

function checkAndAddColumns($botConn, $tableName, $columns) {
    $hasCriticalError = false;

    foreach ($columns as $columnName => $alterQuery) {
        $result = $botConn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
        if ($result->num_rows == 0) {
            if ($botConn->query($alterQuery) === TRUE) {
                echo "Column '$columnName' added to table '$tableName'.\n";
            } else {
                echo "Error adding column '$columnName' to table '$tableName': " . $botConn->error . "\n";
                $hasCriticalError = true;
            }
        }
    }

    return $hasCriticalError;
}

$hasCriticalError = checkAndCreateTablesAndColumns($botConn);

$botConn->close();

if ($hasCriticalError) {
    exit(1); 
} else {
    exit(0);
}
?>

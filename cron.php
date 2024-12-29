<?php
date_default_timezone_set('Asia/Tehran');

require 'config.php';

$marzbanConn = new mysqli($vpnDbHost, $vpnDbUser, $vpnDbPass, $vpnDbName);
if ($marzbanConn->connect_error) {
    file_put_contents('logs.txt', date('Y-m-d H:i:s') . " - VPN DB connection failed: " . $marzbanConn->connect_error . "\n", FILE_APPEND);
    exit;
}

$botConn = new mysqli($botDbHost, $botDbUser, $botDbPass, $botDbName);
if ($botConn->connect_error) {
    file_put_contents('logs.txt', date('Y-m-d H:i:s') . " - Bot DB connection failed: " . $botConn->connect_error . "\n", FILE_APPEND);
    exit;
}

$marzbanConn->set_charset("utf8mb4");
$botConn->set_charset("utf8mb4");

function sendRequest($method, $parameters) {
    global $apiURL, $botConn;
    
    $url = $apiURL . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        file_put_contents('logs.txt', date('Y-m-d H:i:s') . " - cURL error: " . curl_error($ch) . "\n", FILE_APPEND);
    }
    
    curl_close($ch);
    $result = json_decode($response, true);
    
    if (isset($result['result']['message_id']) && isset($parameters['chat_id'])) {
        $messageId = $result['result']['message_id'];
        $userId = $parameters['chat_id'];

        $stmt = $botConn->prepare("UPDATE user_states SET message_id = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $messageId, $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    return $result;
}
function getLang($userId) {
    global $botConn;

    $langCode = 'en';

    if ($stmt = $botConn->prepare("SELECT lang FROM user_states WHERE user_id = ?")) {
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if (in_array($row['lang'], ['fa', 'en', 'ru'])) {
                    $langCode = $row['lang'];
                }
            }
        } else {
            file_put_contents('logs.txt', date('Y-m-d H:i:s') . " - Error executing statement: " . $stmt->error . "\n", FILE_APPEND);
        }
        
        $stmt->close();
    } else {
        file_put_contents('logs.txt', date('Y-m-d H:i:s') . " - Error preparing statement: " . $botConn->error . "\n", FILE_APPEND);
    }

    $languages = include 'languages.php';

    if (isset($languages[$langCode])) {
        return $languages[$langCode];
    }

    return $languages['en']; 
}
function triggerCheck($connection, $triggerName, $adminId) {
    $preventFlag = false;
    $triggerExistsResult = $connection->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
    if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
        $triggerResult = $connection->query("SHOW CREATE TRIGGER `$triggerName`");
        if ($triggerResult && $triggerResult->num_rows > 0) {
            $triggerRow = $triggerResult->fetch_assoc();
            $triggerBody = $triggerRow['SQL Original Statement'];
            if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                $adminIdsStr = str_replace(' ', '', $matches[1]);
                $adminIds = explode(',', $adminIdsStr);
                if (in_array($adminId, $adminIds)) {
                    $preventFlag = true;
                }
            }
        }
    }
    return $preventFlag;
}

function getAdminInfo($adminId) {
    global $marzbanConn, $botConn;


    $stmtAdmin = $marzbanConn->prepare("SELECT username FROM admins WHERE id = ?");
    $stmtAdmin->bind_param("i", $adminId);
    $stmtAdmin->execute();
    $adminResult = $stmtAdmin->get_result();
    if ($adminResult->num_rows === 0) {
        return false;
    }
    $admin = $adminResult->fetch_assoc();
    $adminUsername = $admin['username'];
    $stmtAdmin->close();

    $stmtTraffic = $marzbanConn->prepare("
    SELECT admins.username, 
    (
        (
            SELECT IFNULL(SUM(users.used_traffic), 0)
            FROM users
            WHERE users.admin_id = admins.id
        )
        +
        (
            SELECT IFNULL(SUM(user_usage_logs.used_traffic_at_reset), 0)
            FROM user_usage_logs
            WHERE user_usage_logs.user_id IN (
                SELECT id FROM users WHERE users.admin_id = admins.id
            )
        )
        +
        (
            SELECT IFNULL(SUM(user_deletions.used_traffic), 0) 
            + IFNULL(SUM(user_deletions.reseted_usage), 0)
            FROM user_deletions
            WHERE user_deletions.admin_id = admins.id
        )
    ) / 1073741824 AS used_traffic_gb
    FROM admins
    WHERE admins.id = ?
    GROUP BY admins.username, admins.id;
    ");
    $stmtTraffic->bind_param("i", $adminId);
    $stmtTraffic->execute();
    $trafficResult = $stmtTraffic->get_result();
    $trafficData = $trafficResult->fetch_assoc();
    $stmtTraffic->close();

    $usedTraffic = isset($trafficData['used_traffic_gb']) ? round($trafficData['used_traffic_gb'], 2) : 0;

    $stmtSettings = $botConn->prepare("SELECT total_traffic, expiry_date, status, user_limit FROM admin_settings WHERE admin_id = ?");
    $stmtSettings->bind_param("i", $adminId);
    $stmtSettings->execute();
    $settingsResult = $stmtSettings->get_result();
    $settings = $settingsResult->fetch_assoc();
    $stmtSettings->close();

    $totalTraffic = isset($settings['total_traffic']) ? round($settings['total_traffic'] / 1073741824, 2) : '♾️';
    $remainingTraffic = ($totalTraffic !== '♾️') ? round($totalTraffic - $usedTraffic, 2) : '♾️';

    $expiryDate = isset($settings['expiry_date']) ? $settings['expiry_date'] : '♾️';
    $daysLeft = ($expiryDate !== '♾️') ? ceil((strtotime($expiryDate) - time()) / 86400) : '♾️';

    $status = isset($settings['status']) ? $settings['status'] : 'active';

    $stmtUserStats = $marzbanConn->prepare("
        SELECT
            COUNT(*) AS total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_users,
            SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) AS expired_users,
            SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, now(), online_at) = 0 THEN 1 ELSE 0 END) AS online_users
        FROM users
        WHERE admin_id = ?
    ");
    $stmtUserStats->bind_param("i", $adminId);
    $stmtUserStats->execute();
    $userStatsResult = $stmtUserStats->get_result();
    $userStats = $userStatsResult->fetch_assoc();
    $stmtUserStats->close();

    $userLimit = isset($settings['user_limit']) ? $settings['user_limit'] : '♾️';
    if ($userLimit !== '♾️') {
        $remainingUserLimit = $userLimit - $userStats['active_users'];
    } else {
        $remainingUserLimit = '♾️';
    }

    $preventUserCreation = triggerCheck($marzbanConn, 'prevent_user_creation', $adminId);
    $preventUserReset = triggerCheck($marzbanConn, 'prevent_User_Reset_Usage', $adminId);
    $preventRevokeSubscription = triggerCheck($marzbanConn, 'prevent_revoke_subscription', $adminId);
    $preventUnlimitedTraffic = triggerCheck($marzbanConn, 'prevent_unlimited_traffic', $adminId);
    $preventUserDelete = triggerCheck($marzbanConn, 'admin_delete', $adminId);

    return [
        'username' => $adminUsername,
        'userid' => $adminId,
        'usedTraffic' => $usedTraffic,
        'totalTraffic' => $totalTraffic,
        'remainingTraffic' => $remainingTraffic,
        'expiryDate' => $expiryDate,
        'daysLeft' => $daysLeft,
        'status' => $status,
        'userLimit' => $userLimit,
        'remainingUserLimit' => $remainingUserLimit,
        'preventUserReset' => $preventUserReset,
        'preventUserCreation' => $preventUserCreation,
        'preventUserDeletion' => $preventUserDelete,
        'preventRevokeSubscription' => $preventRevokeSubscription,
        'preventUnlimitedTraffic' => $preventUnlimitedTraffic,
        'userStats' => $userStats
    ];
}



function getAdminlimits($adminId) {
    global $marzbanConn, $botConn;

    $stmtSettings = $botConn->prepare("SELECT total_traffic, expiry_date, user_limit FROM admin_settings WHERE admin_id = ?");
    $stmtSettings->bind_param("i", $adminId);
    $stmtSettings->execute();
    $settingsResult = $stmtSettings->get_result();
    $settings = $settingsResult->fetch_assoc();
    $stmtSettings->close();

    $userLimit = $settings['user_limit'] ?? null;
    $expiryDate = $settings['expiry_date'] ?? null;

    return [
        'userid' => $adminId,
        'userLimit' => $userLimit,
        'expiryDate' => $expiryDate
    ];
}

function manageUserLimitTrigger($marzbanConn, $triggerName, $adminId, $activeUsers, $userLimit) {
    global $botConn;

    $existingTriggerQuery = $marzbanConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
    $existingAdminIds = [];

    if ($existingTriggerQuery && $existingTriggerQuery->num_rows > 0) {
        $triggerResult = $marzbanConn->query("SHOW CREATE TRIGGER `$triggerName`");
        if ($triggerResult && $triggerResult->num_rows > 0) {
            $triggerRow = $triggerResult->fetch_assoc();
            $triggerBody = $triggerRow['SQL Original Statement'];

            if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                $existingAdminIdsStr = $matches[1];
                $existingAdminIdsStr = str_replace(' ', '', $existingAdminIdsStr);
                $existingAdminIds = explode(',', $existingAdminIdsStr);
            }
        }
    }

    $isOverLimit = (!is_null($userLimit) && $activeUsers >= $userLimit);

    if ($isOverLimit) {
        if (!in_array($adminId, $existingAdminIds)) {
            $existingAdminIds[] = $adminId;

            $stmt = $marzbanConn->prepare("SELECT telegram_id, username FROM admins WHERE id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                $telegramId = $admin['telegram_id'];
                $username = $admin['username'];

                if (!empty($telegramId)) {
                    $lang = getLang($telegramId);

                    $message = sprintf(
                        $lang['user_limit_exceeded'],
                        $username
                    );

                    sendRequest('sendMessage', [
                        'chat_id' => $telegramId,
                        'text' => $message
                    ]);
                }
            }
            $stmt->close();
        }
    } else {
        $existingAdminIds = array_diff($existingAdminIds, [$adminId]);
    }

    if (empty($existingAdminIds)) {
        $marzbanConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
    } else {
        $adminIdsStr = implode(', ', $existingAdminIds);
        $triggerBody = "
        CREATE TRIGGER `$triggerName` BEFORE INSERT ON `users`
        FOR EACH ROW
        BEGIN
            IF NEW.admin_id IN ($adminIdsStr) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User creation not allowed for this admin ID.';
            END IF;
        END;
        ";

        $marzbanConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
        $marzbanConn->query($triggerBody);
    }
}


$currentMinute = (int)date('i');

$adminsResult = $marzbanConn->query("SELECT id FROM admins");
if ($adminsResult) {
    while ($adminRow = $adminsResult->fetch_assoc()) {
        $adminId = $adminRow['id'];

        $adminLimits = getAdminlimits($adminId);
        $userLimit = $adminLimits['userLimit'];

        $adminInfo = getAdminInfo($adminId);
        $activeUsers = $adminInfo['userStats']['active_users'];

        manageUserLimitTrigger($marzbanConn, 'cron_prevent_user_creation', $adminId, $activeUsers, $userLimit);
    }
}



if ($currentMinute % 15 === 0) {
    $adminsResult->data_seek(0);
    while ($adminRow = $adminsResult->fetch_assoc()) {
        $adminInfo = getAdminInfo($adminRow['id']);
        if (!$adminInfo) continue;

        $usedTraffic = $adminInfo['usedTraffic'];

        $stmt = $botConn->prepare("INSERT INTO admin_usage (admin_id, used_traffic_gb) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("id", $adminRow['id'], $usedTraffic);
            $stmt->execute();
            $stmt->close();
        }
    }
}
$currentTime = date('H:i');

if ($currentTime === '00:00') {
    $adminsResult = $marzbanConn->query("SELECT id, username, telegram_id FROM admins");
    if ($adminsResult) {
        while ($adminRow = $adminsResult->fetch_assoc()) {
            $adminId = $adminRow['id'];
            $telegramId = $adminRow['telegram_id'];
            $username = $adminRow['username'];

            if (empty($telegramId)) continue;

            $adminLimits = getAdminlimits($adminId);
            $userLimit = $adminLimits['userLimit'];

            if (is_null($userLimit)) continue; 

            $adminInfo = getAdminInfo($adminId);
            $activeUsers = $adminInfo['userStats']['active_users'];
            $remainingSlots = $userLimit - $activeUsers;

            if ($remainingSlots > 0 && $remainingSlots <= 5) {
                $lang = getLang($telegramId);

                $message = sprintf(
                    $lang['user_limit_warning'],
                    $username
                );

                sendRequest('sendMessage', [
                    'chat_id' => $telegramId,
                    'text' => $message
                ]);
            }
        }
    }
}

$adminsResult = $marzbanConn->query("SELECT id, username, hashed_password, telegram_id FROM admins");
if ($adminsResult) {
    while ($adminRow = $adminsResult->fetch_assoc()) {
        $adminId = $adminRow['id'];
        $telegramId = $adminRow['telegram_id'];
        $username = $adminRow['username'];
        $currentHashedPassword = $adminRow['hashed_password'];

        $adminLimits = getAdminlimits($adminId);
        $expiryDate = $adminLimits['expiryDate'];

        if (is_null($expiryDate)) continue;

        $expiryTimestamp = strtotime($expiryDate);
        $currentTimestamp = time();
        $timeSinceExpiry = $currentTimestamp - $expiryTimestamp;

        $adminSettings = $botConn->query("SELECT hashed_password_before, status, last_expiry_notification FROM admin_settings WHERE admin_id = $adminId")->fetch_assoc();
        $hashedPasswordBefore = $adminSettings['hashed_password_before'];
        $status = $adminSettings['status'];
        $lastNotification = strtotime($adminSettings['last_expiry_notification']);

        if ($timeSinceExpiry > 0) {
            if ($status !== 'disabled' && (is_null($lastNotification) || $lastNotification < $expiryTimestamp)) {
                if (empty($hashedPasswordBefore)) {
                    $stmt = $botConn->prepare("UPDATE admin_settings SET hashed_password_before = ?, status = 'disabled', last_expiry_notification = NOW() WHERE admin_id = ?");
                    $stmt->bind_param("si", $currentHashedPassword, $adminId);
                    $stmt->execute();
                    $stmt->close();
                }

                $randomPassword = bin2hex(random_bytes(16));
                $randomHashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);

                $stmt = $marzbanConn->prepare("UPDATE admins SET hashed_password = ? WHERE id = ?");
                $stmt->bind_param("si", $randomHashedPassword, $adminId);
                $stmt->execute();
                $stmt->close();

                $lang = getLang($telegramId);
                sendRequest('sendMessage', [
                    'chat_id' => $telegramId,
                    'text' => $lang['panel_expired']
                ]);
            }

            if ($timeSinceExpiry > 86400) {
                $marzbanConn->query("UPDATE users SET status = 'disabled' WHERE admin_id = '$adminId' AND status = 'active'");

                $lang = getLang($telegramId);

                sendRequest('sendMessage', [
                    'chat_id' => $telegramId,
                    'text' => $lang['users_disabled_notify']
                ]);
            }
        }

        if ($timeSinceExpiry <= 0 && $status === 'disabled') {
            if (!empty($hashedPasswordBefore)) {
                $stmt = $marzbanConn->prepare("UPDATE admins SET hashed_password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPasswordBefore, $adminId);
                $stmt->execute();
                $stmt->close();
            }

            $marzbanConn->query("UPDATE users SET status = 'active' WHERE admin_id = '$adminId' AND status = 'disabled'");

            $stmt = $botConn->prepare("UPDATE admin_settings SET hashed_password_before = NULL, status = 'active', last_expiry_notification = NULL WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$adminsResult = $marzbanConn->query("SELECT id, username, hashed_password, telegram_id FROM admins");
if ($adminsResult) {
    while ($adminRow = $adminsResult->fetch_assoc()) {
        $adminId = $adminRow['id'];
        $telegramId = $adminRow['telegram_id'];
        $username = $adminRow['username'];
        $currentHashedPassword = $adminRow['hashed_password'];

        $adminInfo = getAdminInfo($adminId);
        $usedTraffic = $adminInfo['usedTraffic'];
        $totalTraffic = $adminInfo['totalTraffic'];
        $remainingTraffic = $adminInfo['remainingTraffic'];

            $adminSettings = $botConn->query("SELECT hashed_password_before, last_traffic_notify, last_traffic_notification FROM admin_settings WHERE admin_id = $adminId")->fetch_assoc();
            $hashedPasswordBefore = $adminSettings['hashed_password_before'] ?? null;
            $status = $adminSettings['status'] ?? null;
            $lastTrafficNotification = $adminSettings['last_traffic_notification'] ?? null;

            $lastNotification = $adminSettings['last_traffic_notify'] ?? null;





        if ($remainingTraffic <= 300 && $remainingTraffic > 200 && $lastTrafficNotification != 300) {
            $lang = getLang($telegramId);
            sendRequest('sendMessage', [
                'chat_id' => $telegramId,
                'text' => sprintf($lang['traffic_warning'], $username, $remainingTraffic)
            ]);

            $stmt = $botConn->prepare("UPDATE admin_settings SET last_traffic_notification = 300 WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $stmt->close();
        } elseif ($remainingTraffic <= 200 && $remainingTraffic > 100 && $lastTrafficNotification != 200) {
            $lang = getLang($telegramId);
            sendRequest('sendMessage', [
                'chat_id' => $telegramId,
                'text' => sprintf($lang['traffic_warning'], $username, $remainingTraffic)
            ]);

            $stmt = $botConn->prepare("UPDATE admin_settings SET last_traffic_notification = 200 WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $stmt->close();
        } elseif ($remainingTraffic <= 100 && $remainingTraffic > 50 && $lastTrafficNotification != 100) {
            $lang = getLang($telegramId);
            sendRequest('sendMessage', [
                'chat_id' => $telegramId,
                'text' => sprintf($lang['traffic_warning'], $username, $remainingTraffic)
            ]);

            $stmt = $botConn->prepare("UPDATE admin_settings SET last_traffic_notification = 100 WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $stmt->close();
        }
        
        if ($remainingTraffic <= 50 && $remainingTraffic > 0 && $lastNotification == null) {
                $stmt = $botConn->prepare("UPDATE admin_settings SET hashed_password_before = ?, last_traffic_notify = 1 WHERE admin_id = ?");
                $stmt->bind_param("si", $currentHashedPassword, $adminId);
                $stmt->execute();
                $stmt->close();
        
                $randomPassword = bin2hex(random_bytes(16));
                $randomHashedPassword = password_hash($randomPassword, PASSWORD_BCRYPT);
        
                $stmt = $marzbanConn->prepare("UPDATE admins SET hashed_password = ? WHERE id = ?");
                $stmt->bind_param("si", $randomHashedPassword, $adminId);
                $stmt->execute();
                $stmt->close();
        
                $lang = getLang($telegramId);
                sendRequest('sendMessage', [
                    'chat_id' => $telegramId,
                    'text' => sprintf($lang['traffic_critical'], $username)
                ]);
            
        }
        
        if ($remainingTraffic <= 0 && $status == 'active') {
            $marzbanConn->query("UPDATE users SET status = 'disabled' WHERE admin_id = '$adminId' AND status = 'active'");

            $lang = getLang($telegramId);
            sendRequest('sendMessage', [
                'chat_id' => $telegramId,
                'text' => sprintf($lang['traffic_exhausted'], $username)
            ]);
        }

        if ($remainingTraffic > 50 && !empty($hashedPasswordBefore)) {
            $stmt = $marzbanConn->prepare("UPDATE admins SET hashed_password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPasswordBefore, $adminId);
            $stmt->execute();
            $stmt->close();

            $marzbanConn->query("UPDATE users SET status = 'active' WHERE admin_id = '$adminId' AND status = 'disabled'");

            $stmt = $botConn->prepare("UPDATE admin_settings SET hashed_password_before = NULL, status = 'active', last_traffic_notification = NULL, last_traffic_notify = NULL WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $stmt->close();

            $lang = getLang($telegramId);
            sendRequest('sendMessage', [
                'chat_id' => $telegramId,
                'text' => sprintf($lang['traffic_restored'], $username)
            ]);
        }
    }
}




$marzbanConn->close();
$botConn->close();

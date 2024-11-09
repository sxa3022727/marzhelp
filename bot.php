<?php

if (php_sapi_name() !== 'cli') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header("Location: https://roverloom.com/");
        exit;
    }
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

$botConn = new mysqli($botDbHost, $botDbUser, $botDbPass, $botDbName);
if ($botConn->connect_error) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Bot DB connection failed: " . $botConn->connect_error . "\n", FILE_APPEND);
    exit;
}
$botConn->set_charset("utf8");

// If you have run MySql on a different port
// $vpnConn = new mysqli($vpnDbHost, $vpnDbUser, $vpnDbPass, $vpnDbName, $vpnDbPort);
$vpnConn = new mysqli($vpnDbHost, $vpnDbUser, $vpnDbPass, $vpnDbName);
if ($vpnConn->connect_error) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - VPN DB connection failed: " . $vpnConn->connect_error . "\n", FILE_APPEND);
    exit;
}
$vpnConn->set_charset("utf8");

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
            file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Error executing statement: " . $stmt->error . "\n", FILE_APPEND);
        }
        
        $stmt->close();
    } else {
        file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Error preparing statement: " . $botConn->error . "\n", FILE_APPEND);
    }

    $languages = include 'languages.php';

    if (isset($languages[$langCode])) {
        return $languages[$langCode];
    }

    return $languages['en']; 
}

function sendRequest($method, $parameters) {
    global $apiURL, $botConn;
    
    $url = $apiURL . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - cURL error: " . curl_error($ch) . "\n", FILE_APPEND);
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

function getMainMenuKeyboard($userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['manage_admins'], 'callback_data' => 'manage_admins']
            ],
            [
                ['text' => $lang['account_info'], 'callback_data' => 'account_info'] 
            ]
        ]
    ];
}

function getbacktoadminselectbutton($userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [ 
            [
                ['text' => $lang['back'], 'callback_data' => 'manage_admins']
            ]
        ]
        ];
}

function getAdminKeyboard($userId, $adminId, $status) {
    global $allowedUsers; 
    
    if (in_array($userId, $allowedUsers)) {
        return getAdminManagementKeyboard($adminId, $status, $userId); 
    } else {
        return getLimitedAdminManagementKeyboard($adminId, $status, $userId); 
    }
}

function getAdminManagementKeyboard($adminId, $status, $userId) {

    $lang = getLang($userId);

    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['admin_specifications_settings'], 'callback_data' => 'show_display_only_admin']
            ],
            [
                ['text' => $lang['set_traffic_button'], 'callback_data' => 'set_traffic:' . $adminId],
                ['text' => $lang['set_expiry_button'], 'callback_data' => 'set_expiry:' . $adminId]
            ],
            [
                ['text' => $lang['setuserlimitbutton'], 'callback_data' => 'set_user_limit:' . $adminId],
                ['text' => $lang['securityButton'], 'callback_data' => 'security:' . $adminId]
            ],
            [
                ['text' => $lang['admin_limitations_settings'], 'callback_data' => 'show_display_only_limit']
            ],
            [
                ['text' => $lang['limit_inbounds_button'], 'callback_data' => 'limit_inbounds:' . $adminId],
                [
                    'text' => ($status === 'active') ? $lang['disable_users_button'] : $lang['enable_users_button'],
                    'callback_data' => ($status === 'active') ? 'disable_users:' . $adminId : 'enable_users:' . $adminId
                ]
            ],
            [
                ['text' => $lang['GoToLimitsButton'], 'callback_data' => 'show_restrictions:' . $adminId],
                ['text' => $lang['protocolsettingsbutton'], 'callback_data' => 'protocol_settings:' . $adminId]
            ],
            [
                ['text' => $lang['admin_users_settings'], 'callback_data' => 'show_display_only_users']
            ],
            [
                ['text' => $lang['add_time_button'], 'callback_data' => 'add_time:' . $adminId],
                ['text' => $lang['subtract_time_button'], 'callback_data' => 'reduce_time:' . $adminId]
            ],
            [
                ['text' => $lang['adddatalimitbutton'], 'callback_data' => 'add_data_limit:' . $adminId],
                ['text' => $lang['subtractdata_button'], 'callback_data' => 'subtract_data_limit:' . $adminId]
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'manage_admins'],
                ['text' => $lang['refresh_button'], 'callback_data' => 'select_admin:' . $adminId]
            ]
        ]
    ];
}

function getLimitedAdminManagementKeyboard($adminId, $status, $userId) {
    $lang = getLang($userId);
    
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['add_time_button'], 'callback_data' => 'add_time:' . $adminId],
                ['text' => $lang['subtract_time_button'], 'callback_data' => 'reduce_time:' . $adminId]
            ],
            [
                ['text' => $lang['adddatalimitbutton'], 'callback_data' => 'add_data_limit:' . $adminId],
                ['text' => $lang['subtractdata_button'], 'callback_data' => 'subtract_data_limit:' . $adminId]
            ],
        [
            ['text' => $lang['back'], 'callback_data' => 'manage_admins'],
            ['text' => $lang['refresh_button'], 'callback_data' => 'select_admin:' . $adminId]
        ]
        ]
    ];
    
}

function getprotocolsttingskeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['add_protocol_button'], 'callback_data' => 'add_protocol:' . $adminId],
                ['text' => $lang['remove_protocol_button'], 'callback_data' => 'remove_protocol:' . $adminId]
            ],
            [
                ['text' => $lang['enable_inbounds_button'], 'callback_data' => 'enable_inbounds:' . $adminId],
                ['text' => $lang['disable_inbounds_button'], 'callback_data' => 'disable_inbounds:' . $adminId]
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
    
}

function getSecurityKeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['changePasswordButton'], 'callback_data' => 'change_password:' . $adminId],
                ['text' => $lang['changeSudoButton'], 'callback_data' => 'change_sudo:' . $adminId]
            ],
            [
                ['text' => $lang['changeTelegramIdButton'], 'callback_data' => 'change_telegram_id:' . $adminId],
                ['text' => $lang['changeUsernameButton'], 'callback_data' => 'change_username:' . $adminId]
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
}

function getSudoConfirmationKeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['confirm_yes_button'], 'callback_data' => 'confirm_sudo_yes:' . $adminId],
                ['text' => $lang['confirm_no_button'], 'callback_data' => 'confirm_sudo_no:' . $adminId]
            ]
        ]
    ];
    
}

function getConfirmationKeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['confirm_yes_button'], 'callback_data' => 'confirm_disable_yes:' . $adminId],
                ['text' => $lang['confirm_no_button'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
    
}

function getBackToAdminManagementKeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
    
}

function getBackToMainKeyboard($userId) {
    $lang = getLang($userId);
    
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_main']
            ]
        ]];
}

function getProtocolSelectionKeyboard($adminId, $action, $userId) {
    $lang = getLang($userId);

    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['protocol_vmess'], 'callback_data' => $action . ':vmess:' . $adminId],
                ['text' => $lang['protocol_vless'], 'callback_data' => $action . ':vless:' . $adminId]
            ],
            [
                ['text' => $lang['protocol_trojan'], 'callback_data' => $action . ':trojan:' . $adminId],
                ['text' => $lang['protocol_shadowsocks'], 'callback_data' => $action . ':shadowsocks:' . $adminId]
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]];
    }

function getRestrictionsKeyboard($adminId, $preventUserDeletion, $preventUserCreation, $preventUserReset, $preventRevokeSubscription, $preventUnlimitedTraffic, $userId) {
    
    $lang = getLang($userId);

        $preventUserDeletionStatus = $preventUserDeletion ? $lang['active_status'] : $lang['inactive_status'];
        $preventUserCreationStatus = $preventUserCreation ? $lang['active_status'] : $lang['inactive_status'];
        $preventUserResetStatus = $preventUserReset ? $lang['active_status'] : $lang['inactive_status'];
        $preventRevokeSubscriptionStatus = $preventRevokeSubscription ? $lang['active_status'] : $lang['inactive_status'];
        $preventUnlimitedTrafficStatus = $preventUnlimitedTraffic ? $lang['active_status'] : $lang['inactive_status'];
    
        $preventUserDeletionButtonText = $lang['preventUserDeletionButton'] . ' ' . $preventUserDeletionStatus;
        $preventUserCreationButtonText = $lang['preventUserCreationButton'] . ' ' . $preventUserCreationStatus;
        $preventUserResetButtonText = $lang['preventUserResetButton'] . ' ' . $preventUserResetStatus;
        $preventRevokeSubscriptionButtonText = $lang['preventRevokeSubscriptionButton'] . ' ' . $preventRevokeSubscriptionStatus;
        $preventUnlimitedTrafficButtonText = $lang['preventUnlimitedTrafficButton'] . ' ' . $preventUnlimitedTrafficStatus;
    
        return [
            'inline_keyboard' => [
                [
                    ['text' => $preventUserDeletionButtonText, 'callback_data' => 'toggle_prevent_user_deletion:' . $adminId],
                    ['text' => $preventUserCreationButtonText, 'callback_data' => 'toggle_prevent_user_creation:' . $adminId]
                ],
                [
                    ['text' => $preventUserResetButtonText, 'callback_data' => 'toggle_prevent_user_reset:' . $adminId],
                    ['text' => $preventRevokeSubscriptionButtonText, 'callback_data' => 'toggle_prevent_revoke_subscription:' . $adminId]
                ],
                [
                    ['text' => $preventUnlimitedTrafficButtonText, 'callback_data' => 'toggle_prevent_unlimited_traffic:' . $adminId]
                ],
                [
                    ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
                ]
            ]
        ];
    }

    function getUserRole($telegramId) {
    global $allowedUsers, $vpnConn;
    
    if (in_array($telegramId, $allowedUsers)) {
        return 'main_admin';
    }
    
    $stmt = $vpnConn->prepare("SELECT id FROM admins WHERE telegram_id = ?");
    $stmt->bind_param("i", $telegramId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isLimitedAdmin = $result->num_rows > 0;
    $stmt->close();
    
    if ($isLimitedAdmin) {
        return 'limited_admin';
    }
    
    return 'unauthorized';
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

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function createAdmin($userId, $chatId) {
    global $vpnConn, $botConn;

    $lang = getLang($userId); 

    $username = handleTemporaryData('get', $userId, 'new_admin_username');
    $hashedPassword = handleTemporaryData('get', $userId, 'new_admin_password');
    $isSudo = handleTemporaryData('get', $userId, 'new_admin_sudo') ?? 0;
    $telegramId = handleTemporaryData('get', $userId, 'new_admin_telegram_id') ?? 0;
    $nothashedpassword = handleTemporaryData('get', $userId, 'new_admin_password_nothashed');
     $stmt = $botConn->prepare("SELECT state, admin_id, message_id FROM user_states WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userStateResult = $stmt->get_result();
    $userState = $userStateResult->fetch_assoc();
    $stmt->close();

    if (!$username || !$hashedPassword) {
        sendRequest('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $userState['message_id'],
        ]);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $lang['createAdmin_error_insufficient_data']
        ]);
        return;
    }

    $createdAt = date('Y-m-d H:i:s');

    $stmt = $vpnConn->prepare("INSERT INTO admins (username, hashed_password, created_at, is_sudo, telegram_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $username, $hashedPassword, $createdAt, $isSudo, $telegramId);
    
    if ($stmt->execute()) {
        $newAdminId = $stmt->insert_id;

        $promptMessageId = $userState['message_id'];

        sendRequest('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $promptMessageId
        ]);

        $successText = sprintf($lang['createAdmin_success_added'], $username, $nothashedpassword, $telegramId);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $successText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $newAdminId, 'active')
        ]);
    } else {
        $promptMessageId = $userState['message_id'];

        sendRequest('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $promptMessageId
        ]);

        $errorText = sprintf($lang['createAdmin_error_adding_failed'], $stmt->error);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $errorText,
        ]);
    }
    $stmt->close();

    handleUserState('clear', $userId);

    handleTemporaryData('clear', $userId);
}

function handleUserState($action, $userId, $state = null, $adminId = null) {
    global $botConn;

    if ($action === 'set') {
        if ($adminId !== null) {
            $sql = "INSERT INTO user_states (user_id, state, admin_id) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE state = ?, admin_id = ?";
            $stmt = $botConn->prepare($sql);
            $stmt->bind_param("isisi", $userId, $state, $adminId, $state, $adminId);
        } else {
            $sql = "INSERT INTO user_states (user_id, state) VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE state = ?";
            $stmt = $botConn->prepare($sql);
            $stmt->bind_param("iss", $userId, $state, $state);
        }

        if (!$stmt->execute()) {
            file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - SQL error: " . $stmt->error . "\n", FILE_APPEND);
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;

    } elseif ($action === 'get') {
        $stmt = $botConn->prepare("SELECT state, admin_id, message_id FROM user_states WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $state = null;
        $adminId = null;
        $messageId = null;
        
        if ($row = $result->fetch_assoc()) {
            $state = $row['state'];
            $adminId = $row['admin_id'];
            $messageId = $row['message_id'];
        }
        
        $stmt->close();
        
        return [
            'state' => $state,
            'admin_id' => $adminId,
            'message_id' => $messageId
        ];

    } elseif ($action === 'clear') {
        $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    return false;
}

function handleTemporaryData($operation, $userId, $key = null, $value = null) {
    global $botConn;

    if ($operation === 'set') {
        $stmt = $botConn->prepare("INSERT INTO user_temporaries (user_id, `user_key`, `value`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->bind_param("isss", $userId, $key, $value, $value);
        if (!$stmt->execute()) {
            file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - SQL error: " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    } elseif ($operation === 'get') {
        $stmt = $botConn->prepare("SELECT `value` FROM user_temporaries WHERE user_id = ? AND `user_key` = ?");
        $stmt->bind_param("is", $userId, $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $retrievedValue = null;
        if ($row = $result->fetch_assoc()) {
            $retrievedValue = $row['value'];
        }
        $stmt->close();
        return $retrievedValue;
    } elseif ($operation === 'clear') {
        $stmt = $botConn->prepare("DELETE FROM user_temporaries WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
}


function getAdminInfo($adminId) {
    global $vpnConn, $botConn;

    $lang = getLang($adminId);

    $stmtAdmin = $vpnConn->prepare("SELECT username FROM admins WHERE id = ?");
    $stmtAdmin->bind_param("i", $adminId);
    $stmtAdmin->execute();
    $adminResult = $stmtAdmin->get_result();
    if ($adminResult->num_rows === 0) {
        return false;
    }
    $admin = $adminResult->fetch_assoc();
    $adminUsername = $admin['username'];
    $stmtAdmin->close();

    $stmtTraffic = $vpnConn->prepare("
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

    $stmtUserStats = $vpnConn->prepare("
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

    $preventUserCreation = triggerCheck($vpnConn, 'prevent_user_creation', $adminId);
    $preventUserReset = triggerCheck($vpnConn, 'prevent_User_Reset_Usage', $adminId);
    $preventRevokeSubscription = triggerCheck($vpnConn, 'prevent_revoke_subscription', $adminId);
    $preventUnlimitedTraffic = triggerCheck($vpnConn, 'prevent_unlimited_traffic', $adminId);
    $preventUserDelete = triggerCheck($vpnConn, 'admin_delete', $adminId);

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

function getAdminInfoText($adminInfo, $userId) {
    global $botConn;
    $lang = getLang($userId);
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Language retrieved: " . json_encode($lang) . "\n", FILE_APPEND);

    $statusText = ($adminInfo['status'] === 'active') ? $lang['active_status'] : $lang['inactive_status'];
    
    $totalTrafficGB = $adminInfo['totalTraffic'];
    $remainingTrafficGB = $adminInfo['remainingTraffic'];
    
    if (is_numeric($totalTrafficGB)) {
        $trafficText = number_format($totalTrafficGB, 2); 
    } else {
        $trafficText = $lang['unlimited'];
    }
    
    if (is_numeric($remainingTrafficGB)) {
        $remainingText = number_format($remainingTrafficGB, 2); 
    } else {
        $remainingText = $lang['unlimited'];
    }
    
    $daysText = ($adminInfo['daysLeft'] !== $lang['unlimited']) ? "`{$adminInfo['daysLeft']}` {$lang['days']}" : $lang['unlimited'];
    
    $remainingUserLimit = ($adminInfo['remainingUserLimit'] !== $lang['unlimited']) ? "{$adminInfo['remainingUserLimit']}" : $lang['unlimited'];
    
    $stmt = $botConn->prepare("SELECT lang FROM user_states WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $langfa = 'en'; 
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $langfa = $row['lang'];
    }$stmt->close();
    $separator = "➖➖➖➖➖➖➖➖➖➖"; 
    if ($langfa === 'fa') {
        $separator = "‏" . $separator . "‏"; 
    } else {$separator = $separator;}

    $infoText = "🧸 **{$lang['userid']}:** `{$adminInfo['userid']}`\n";
    $infoText .= "🧸 **{$lang['username']}:** `{$adminInfo['username']}` {$statusText}\n";
    $infoText .= $separator . "\n";
    $infoText .= "📊 **{$lang['totalTraffic']}:** `{$trafficText}" . "` {$lang['createAdmin_traffic_gb']}\n";
    $infoText .= "📤 **{$lang['remainingTraffic']}**: `{$remainingText}" . "` {$lang['createAdmin_traffic_gb']}\n";
    $infoText .= "📥 **{$lang['usedTraffic']}:** `" . number_format($adminInfo['usedTraffic'], 2) . "` {$lang['createAdmin_traffic_gb']}\n";
    $infoText .= $separator . "\n"; 
    $infoText .= "👥 **{$lang['adminInfoText_userCreationLimit']}** `{$remainingUserLimit}`\n";
    $infoText .= "⏳ **{$lang['expiryDate']}:** {$daysText} \n";
    $infoText .= $separator . "\n";    

    $userStatsText = "\n**{$lang['adminInfoText_userStatsHeader']}**\n";
    $userStatsText .= "**{$lang['adminInfoText_totalUsers']}** `{$adminInfo['userStats']['total_users']}`\n";
    $userStatsText .= "**{$lang['adminInfoText_activeUsers']}** `{$adminInfo['userStats']['active_users']}`\n";

    $expiredUsers = $adminInfo['userStats']['total_users'] - $adminInfo['userStats']['active_users'];
    $userStatsText .= "**{$lang['adminInfoText_inactiveUsers']}** `{$expiredUsers}`\n";
    $userStatsText .= "**{$lang['adminInfoText_onlineUsers']}** `{$adminInfo['userStats']['online_users']}`";

   
    return $infoText . $userStatsText;
}

function handleCallbackQuery($callback_query) {
    global $botConn, $vpnConn, $allowedUsers;

    $callbackId = $callback_query['id'];
    $userId = $callback_query['from']['id'];
    $data = $callback_query['data'];
    $messageId = $callback_query['message']['message_id'];
    $chatId = $callback_query['message']['chat']['id'];
    $userRole = getUserRole($userId);

    $userState = handleUserState('get', $userId);
    
    $lang = getLang($userId);

    
    if ($userRole === 'unauthorized') {
        sendRequest('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $lang['error_unauthorized'],
            'show_alert' => false
        ]);
        return;
    }

    if (strpos($data, 'show_display_only_') === 0) {
        $responseKey = substr($data, strlen('show_display_only_'));
    
        $callbackResponses = [
            'admin' => $lang['callbackResponse_adminSettings'],
            'users' => $lang['callbackResponse_showDisplayOnlyUsers'],
            'limit' => $lang['callbackResponse_showDisplayOnlyLimit']
        ];
    
        if (array_key_exists($responseKey, $callbackResponses)) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $callbackResponses[$responseKey],
                'show_alert' => true 
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => 'عملیات نامعتبر است.',
                'show_alert' => true 
            ]);
        }
        return;
    }
    
    if (strpos($data, 'protocol_settings:') === 0) {
        $adminId = intval(substr($data, strlen('protocol_settings:')));
    
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $getprotocolsttingskeyboardtext = $lang['callbackResponse_protocolSettings'];
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $getprotocolsttingskeyboardtext,
            'reply_markup' => getprotocolsttingskeyboard($adminId, $userId)
        ]);
    }
    
    if (strpos($data, 'show_restrictions:') === 0) {
        $adminId = intval(substr($data, strlen('show_restrictions:')));
    
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['callbackResponse_showRestrictions'],
            'reply_markup' => getRestrictionsKeyboard(
                $adminId, 
                $adminInfo['preventUserDeletion'], 
                $adminInfo['preventUserCreation'], 
                $adminInfo['preventUserReset'], 
                $adminInfo['preventRevokeSubscription'], 
                $adminInfo['preventUnlimitedTraffic'],
                $userId
            )
        ]);
    }
    
    if (strpos($data, 'toggle_prevent_revoke_subscription:') === 0) {
        $adminId = intval(substr($data, strlen('toggle_prevent_revoke_subscription:')));
    
        $triggerName = 'prevent_revoke_subscription';
    
        $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
        $adminIds = [];
        if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
            $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
            if ($triggerResult && $triggerResult->num_rows > 0) {
                $triggerRow = $triggerResult->fetch_assoc();
                $triggerBody = $triggerRow['SQL Original Statement'];
                if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                    $adminIdsStr = $matches[1];
                    $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                    $adminIds = explode(',', $adminIdsStr);
                }
            }
        }
    
        if (in_array($adminId, $adminIds)) {
            $adminIds = array_diff($adminIds, [$adminId]);
        } else {
            $adminIds[] = $adminId;
        }
    
        if (empty($adminIds)) {
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
        } else {
            $adminIdsStr = implode(', ', $adminIds);
            $triggerBody = "
            CREATE TRIGGER `$triggerName` BEFORE UPDATE ON `users`
            FOR EACH ROW
            BEGIN
                IF OLD.admin_id IN ($adminIdsStr) AND NEW.sub_revoked_at <> OLD.sub_revoked_at THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Revoking subscription is not allowed';
                END IF;
            END;
            ";
    
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
            $vpnConn->query($triggerBody);
        }
    
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['callbackResponse_showRestrictions'],
            'reply_markup' => getRestrictionsKeyboard(
                $adminId, 
                $adminInfo['preventUserDeletion'], 
                $adminInfo['preventUserCreation'], 
                $adminInfo['preventUserReset'], 
                $adminInfo['preventRevokeSubscription'], 
                $adminInfo['preventUnlimitedTraffic'],
                $userId
            )
        ]);
    }
    if (strpos($data, 'set_user_limit:') === 0) {
        $adminId = intval(substr($data, strlen('set_user_limit:')));
    
        handleUserState('set', $userId, 'set_user_limit', $adminId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['createAdmin_maxUserLimit_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        return;
    }
    if (strpos($data, 'reduce_time:') === 0) {
        $adminId = intval(substr($data, strlen('reduce_time:')));
        
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['reduceUserExpiryDays_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        handleUserState('set', $userId, 'reduce_time', $adminId);
    
        return;
    }
    if (strpos($data, 'add_time:') === 0) {
        $adminId = intval(substr($data, strlen('add_time:')));
    
        $response = sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['addUserExpiryDays_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
    
        handleUserState('set', $userId, 'add_time', $adminId);
    
        return;
    }
    if (strpos($data, 'toggle_prevent_user_creation:') === 0) {
        $adminId = intval(substr($data, strlen('toggle_prevent_user_creation:')));
    
        $triggerName = 'prevent_user_creation';
    
        $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
        $adminIds = [];
        if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
            $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
            if ($triggerResult && $triggerResult->num_rows > 0) {
                $triggerRow = $triggerResult->fetch_assoc();
                $triggerBody = $triggerRow['SQL Original Statement'];
                if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                    $adminIdsStr = $matches[1];
                    $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                    $adminIds = explode(',', $adminIdsStr);
                }
            }
        }
    
        if (in_array($adminId, $adminIds)) {
            $adminIds = array_diff($adminIds, [$adminId]);
        } else {
            $adminIds[] = $adminId;
        }
    
        if (empty($adminIds)) {
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
        } else {
            $adminIdsStr = implode(', ', $adminIds);
            $triggerBody = "
            CREATE TRIGGER `$triggerName` BEFORE INSERT ON `users`
            FOR EACH ROW
            BEGIN
                IF NEW.admin_id IN ($adminIdsStr) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User creation not allowed for this admin ID.';
                END IF;
            END;
            ";
    
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
            $vpnConn->query($triggerBody);
        }
    
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['callbackResponse_showRestrictions'],
            'parse_mode' => 'Markdown',
            'reply_markup' => getRestrictionsKeyboard(
                $adminId, 
                $adminInfo['preventUserDeletion'], 
                $adminInfo['preventUserCreation'], 
                $adminInfo['preventUserReset'], 
                $adminInfo['preventRevokeSubscription'], 
                $adminInfo['preventUnlimitedTraffic'],
                $userId
            )
        ]);
    }
    if (strpos($data, 'toggle_prevent_unlimited_traffic:') === 0) {
        $adminId = intval(substr($data, strlen('toggle_prevent_unlimited_traffic:')));
    
        $triggerName = 'prevent_unlimited_traffic';
    
        $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
        $adminIds = [];
        if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
            $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
            if ($triggerResult && $triggerResult->num_rows > 0) {
                $triggerRow = $triggerResult->fetch_assoc();
                $triggerBody = $triggerRow['SQL Original Statement'];
                if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                    $adminIdsStr = $matches[1];
                    $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                    $adminIds = explode(',', $adminIdsStr);
                }
            }
        }
        if (in_array($adminId, $adminIds)) {
            $adminIds = array_diff($adminIds, [$adminId]);
        } else {
            $adminIds[] = $adminId;
        }
        if (empty($adminIds)) {
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
        } else {
            $adminIdsStr = implode(', ', $adminIds);
            $triggerBody = "
            CREATE TRIGGER `$triggerName` BEFORE UPDATE ON `users`
            FOR EACH ROW
            BEGIN
                IF NEW.data_limit IS NULL THEN
                    IF NEW.admin_id IN ($adminIdsStr) THEN 
                        SIGNAL SQLSTATE '45000' 
                        SET MESSAGE_TEXT = 'Admins with these IDs cannot create users with unlimited traffic.';
                    END IF;
                END IF;
            END;
            ";
    
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
            $vpnConn->query($triggerBody);
        }
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['callbackResponse_showRestrictions'],
            'reply_markup' => getRestrictionsKeyboard(
                $adminId, 
                $adminInfo['preventUserDeletion'], 
                $adminInfo['preventUserCreation'], 
                $adminInfo['preventUserReset'], 
                $adminInfo['preventRevokeSubscription'], 
                $adminInfo['preventUnlimitedTraffic'],
                $userId
            )
        ]);
    }
    if ($data === 'manage_admins') {
        if (in_array($userId, $allowedUsers)) {
            $adminsResult = $vpnConn->query("SELECT id, username FROM admins");
        } else {
            $stmt = $vpnConn->prepare("SELECT id, username FROM admins WHERE telegram_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $adminsResult = $stmt->get_result();
        }
    
        $admins = [];
        while ($row = $adminsResult->fetch_assoc()) {
            $admins[] = ['text' => $row['username'], 'callback_data' => 'select_admin:' . $row['id']];
        }
    
        if (empty($admins)) {
            $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $chatId);
            $stmt->execute();
            $stmt->close();
    
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => $lang['back'], 'callback_data' => 'back_to_main']
                    ]
                ]
            ];
    
            sendRequest('editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $lang['no_admins'],
                'reply_markup' => $keyboard
            ]);
            return;
        }
    
        $keyboard = ['inline_keyboard' => array_chunk($admins, 2)];
    
        if (in_array($userId, $allowedUsers)) {
            $keyboard['inline_keyboard'][] = [
                ['text' => $lang['add_admin'], 'callback_data' => 'add_admin'],
                ['text' => $lang['back'], 'callback_data' => 'back_to_main']
            ];
        } else {
            $keyboard['inline_keyboard'][] = [
                ['text' => $lang['back'], 'callback_data' => 'back_to_main']
            ];
        }
    
        handleUserState('clear', $chatId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['select_admin_prompt'],
            'reply_markup' => $keyboard
        ]);
        return;
    }
        if (strpos($data, 'toggle_prevent_user_deletion:') === 0) {
        $adminId = intval(substr($data, strlen('toggle_prevent_user_deletion:')));
    
        $triggerName = 'admin_delete';
    
        $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
        $adminIds = [];
        if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
            $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
            if ($triggerResult && $triggerResult->num_rows > 0) {
                $triggerRow = $triggerResult->fetch_assoc();
                $triggerBody = $triggerRow['SQL Original Statement'];
                if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                    $adminIdsStr = $matches[1];
                    $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                    $adminIds = explode(',', $adminIdsStr);
                }
            }
        }
    
        if (in_array($adminId, $adminIds)) {
            $adminIds = array_diff($adminIds, [$adminId]);
        } else {
            $adminIds[] = $adminId;
        }
    
        if (empty($adminIds)) {
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
        } else {
            $adminIdsStr = implode(', ', $adminIds);
            $triggerBody = "
            CREATE TRIGGER `$triggerName` BEFORE DELETE ON `users`
            FOR EACH ROW
            BEGIN
                IF OLD.admin_id IN ($adminIdsStr) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Deletion not allowed.';
                END IF;
            END
            ";
    
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
            $vpnConn->query($triggerBody);
        }
    
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['callbackResponse_showRestrictions'],
            'parse_mode' => 'Markdown',
            'reply_markup' => getRestrictionsKeyboard(
                $adminId, 
                $adminInfo['preventUserDeletion'], 
                $adminInfo['preventUserCreation'], 
                $adminInfo['preventUserReset'], 
                $adminInfo['preventRevokeSubscription'], 
                $adminInfo['preventUnlimitedTraffic'],
                $userId
            )
                    ]);
    
        return;
    } 
    if ($data === 'back_to_main') {
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['main_menu'],
            'reply_markup' => getMainMenuKeyboard($userId)
        ]);
        return;
    }
    if (strpos($data, 'disable_inbounds:') === 0) {
        $adminId = intval(substr($data, strlen('disable_inbounds:')));
    
        $inboundsResult = $vpnConn->query("SELECT tag FROM inbounds");
        $inbounds = [];
        while ($row = $inboundsResult->fetch_assoc()) {
            $inbounds[] = $row['tag'];
        }
    
        $keyboard = [];
        foreach ($inbounds as $inbound) {
            $keyboard[] = [
                'text' => $inbound,
                'callback_data' => 'disable_inbound_select:' . $adminId . ':' . $inbound
            ];
        }

        $keyboard = array_chunk($keyboard, 2);
        $keyboard[] = [
            ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
        ];
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['selectBindToDisable_prompt'],
            'reply_markup' => ['inline_keyboard' => $keyboard]
        ]);
        return;
    }
    if (strpos($data, 'disable_inbound_select:') === 0) {
        list(, $adminId, $inboundTag) = explode(':', $data, 3);
    
        $stmt = $vpnConn->prepare("SELECT username FROM admins WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $adminResult = $stmt->get_result();
        $stmt->close();
    
        if ($adminResult->num_rows === 0) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['callbackResponse_adminNotFound'],
                'show_alert' => false
            ]);
            return;
        }
        $adminRow = $adminResult->fetch_assoc();
        $adminUsername = $adminRow['username'];
    
        $inboundTagEscaped = $vpnConn->real_escape_string($inboundTag);
        $adminUsernameEscaped = $vpnConn->real_escape_string($adminUsername);
    
        $sql = "
            INSERT INTO exclude_inbounds_association (proxy_id, inbound_tag)
            SELECT proxies.id, '$inboundTagEscaped'
            FROM users
            INNER JOIN admins ON users.admin_id = admins.id
            INNER JOIN proxies ON proxies.user_id = users.id
            WHERE admins.username = '$adminUsernameEscaped'
            AND proxies.id NOT IN (
                SELECT proxy_id FROM exclude_inbounds_association WHERE inbound_tag = '$inboundTagEscaped'
            );
        ";
    
        if ($vpnConn->query($sql) === TRUE) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['inbound_disabled'],
                'show_alert' => false
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['operation_failed'],
                'show_alert' => false
            ]);
        }
    
        $adminInfo = getAdminInfo($adminId, $userId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
    
        return;
    }
    if (strpos($data, 'enable_inbound_select:') === 0) {
        list(, $adminId, $inboundTag) = explode(':', $data, 3);
    
        $stmt = $vpnConn->prepare("SELECT username FROM admins WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $adminResult = $stmt->get_result();
        $stmt->close();
    
        if ($adminResult->num_rows === 0) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['callbackResponse_adminNotFound'],
                'show_alert' => false
            ]);
            return;
        }
        $adminRow = $adminResult->fetch_assoc();
        $adminUsername = $adminRow['username'];
    
        $inboundTagEscaped = $vpnConn->real_escape_string($inboundTag);
        $adminUsernameEscaped = $vpnConn->real_escape_string($adminUsername);
    
        $sql = "
            DELETE FROM exclude_inbounds_association
            WHERE proxy_id IN (
                SELECT proxies.id
                FROM users
                INNER JOIN admins ON users.admin_id = admins.id
                INNER JOIN proxies ON proxies.user_id = users.id
                WHERE admins.username = '$adminUsernameEscaped'
            )
            AND inbound_tag = '$inboundTagEscaped';
        ";
        if ($vpnConn->query($sql) === TRUE) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['inbound_enabled'],
                'show_alert' => false
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['operation_failed'],
                'show_alert' => false
            ]);
        }
    
        $adminInfo = getAdminInfo($adminId, $userId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
        return;
    }
    if (strpos($data, 'enable_inbounds:') === 0) {
        $adminId = intval(substr($data, strlen('enable_inbounds:')));
    
        $inboundsResult = $vpnConn->query("SELECT tag FROM inbounds");
        $inbounds = [];
        while ($row = $inboundsResult->fetch_assoc()) {
            $inbounds[] = $row['tag'];
        }
    
        $keyboard = [];
        foreach ($inbounds as $inbound) {
            $keyboard[] = [
                'text' => $inbound,
                'callback_data' => 'enable_inbound_select:' . $adminId . ':' . $inbound
            ];
        }
    
        $keyboard = array_chunk($keyboard, 2);
        $keyboard[] = [
            ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
        ];
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['add_inbound_prompt'],
            'reply_markup' => ['inline_keyboard' => $keyboard]
        ]);
        return;
    }
    if (strpos($data, 'toggle_disable_inbound:') === 0) {
        $inboundTag = substr($data, strlen('toggle_disable_inbound:'));
    
        $userState = handleUserState('get', $userId);
    
        if ($userState && $userState['state'] === 'disable_inbounds') {
            $selectedInbounds = json_decode($userState['data'], true);
            if (!$selectedInbounds) {
                $selectedInbounds = [];
            }
    
            if (in_array($inboundTag, $selectedInbounds)) {
                $selectedInbounds = array_diff($selectedInbounds, [$inboundTag]);
            } else {
                $selectedInbounds[] = $inboundTag;
            }
    
            $newData = json_encode(array_values($selectedInbounds));
            handleUserState('update', $userId, null, $newData);
    
            $inboundsResult = $vpnConn->query("SELECT tag FROM inbounds");
            $inbounds = [];
            while ($row = $inboundsResult->fetch_assoc()) {
                $inbounds[] = $row['tag'];
            }
    
            $keyboard = [];
            foreach ($inbounds as $inbound) {
                $isSelected = in_array($inbound, $selectedInbounds);
                $emoji = $isSelected ? '✅ ' : '';
                $keyboard[] = [
                    'text' => $emoji . $inbound,
                    'callback_data' => 'toggle_disable_inbound:' . $inbound
                ];
            }
    
            $keyboard = array_chunk($keyboard, 2);
            $keyboard[] = [
                ['text' => $nextStepButton, 'callback_data' => 'confirm_disable_inbounds'],
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $userState['admin_id']]
            ];
    
            sendRequest('editMessageReplyMarkup', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => ['inline_keyboard' => $keyboard]
            ]);
            return;
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['invalid_input'],
                'show_alert' => false
            ]);
            return;
        }
    }
    if ($data === 'confirm_disable_inbounds') {

        $userState = handleUserState('get', $userId);

        if ($userState && $userState['state'] === 'disable_inbounds') {
            $adminId = $userState['admin_id'];
            $selectedInbounds = json_decode($userState['data'], true);
            if (!$selectedInbounds || empty($selectedInbounds)) {
                sendRequest('answerCallbackQuery', [
                    'callback_query_id' => $callbackId,
                    'text' => $lang['selectMinInbound_prompt'],
                    'show_alert' => false
                ]);
                return;
            }
    
            $stmt = $vpnConn->prepare("SELECT username FROM admins WHERE id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $adminResult = $stmt->get_result();
            $stmt->close();
    
            if ($adminResult->num_rows === 0) {
                sendRequest('answerCallbackQuery', [
                    'callback_query_id' => $callbackId,
                    'text' => $lang['callbackResponse_adminNotFound'],
                    'show_alert' => false
                ]);
                return;
            }
            $adminRow = $adminResult->fetch_assoc();
            $adminUsername = $adminRow['username'];
    
            $inboundSelects = array_map(function($inbound) use ($vpnConn) {
                return "SELECT '" . $vpnConn->real_escape_string($inbound) . "' AS inbound_tag";
            }, $selectedInbounds);
            $inboundUnion = implode(" UNION ALL ", $inboundSelects);
    
            $adminUsernameEscaped = $vpnConn->real_escape_string($adminUsername);
    
            $sql = "
                INSERT INTO exclude_inbounds_association (proxy_id, inbound_tag)
                SELECT proxies.id, inbound_tag_mapping.inbound_tag
                FROM users
                INNER JOIN admins ON users.admin_id = admins.id
                INNER JOIN proxies ON proxies.user_id = users.id
                CROSS JOIN (
                    $inboundUnion
                ) AS inbound_tag_mapping
                LEFT JOIN exclude_inbounds_association eia 
                  ON eia.proxy_id = proxies.id AND eia.inbound_tag = inbound_tag_mapping.inbound_tag
                WHERE admins.username = '$adminUsernameEscaped'
                AND eia.proxy_id IS NULL;
            ";
    
            if ($vpnConn->query($sql) === TRUE) {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['inbound_disabled']
                ]);
            } else {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['operation_failed']
                ]);
            }
    
            handleUserState('clear', $userId);

            $adminInfo = getAdminInfo($adminId, $userId);
            $adminInfo['adminId'] = $adminId;
            $infoText = getAdminInfoText($adminInfo, $userId);
    
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'parse_mode' => 'Markdown',
                'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
            ]);
    
            return;
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['operation_failed'],
                'show_alert' => false
            ]);
            return;
        }
    }
    if (strpos($data, 'confirm_inbounds:') === 0) {
        $adminId = intval(substr($data, strlen('confirm_inbounds:')));
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = $lang['inbounds_limited_success'] . "\n" . getAdminInfoText($adminInfo, $userId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
        return;
    }
    if (strpos($data, 'select_admin:') === 0) {
        $adminId = intval(substr($data, strlen('select_admin:')));

        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);

            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
        handleUserState('clear', $chatId);

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);

        return;
    }
    if ($data === 'confirm_enable_inbounds') {

        $userState = handleUserState('get', $userId);

        if ($userState && $userState['state'] === 'enable_inbounds') {
            $adminId = $userState['admin_id'];
            $selectedInbounds = json_decode($userState['data'], true);
            if (!$selectedInbounds || empty($selectedInbounds)) {
                sendRequest('answerCallbackQuery', [
                    'callback_query_id' => $callbackId,
                    'text' => $lang['selectMinInbound_prompt'],
                    'show_alert' => false
                ]);
                return;
            }
    
            $stmt = $vpnConn->prepare("SELECT username FROM admins WHERE id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $adminResult = $stmt->get_result();
            $stmt->close();
    
            if ($adminResult->num_rows === 0) {
                sendRequest('answerCallbackQuery', [
                    'callback_query_id' => $callbackId,
                    'text' => $lang['callbackResponse_adminNotFound'],
                    'show_alert' => false
                ]);
                return;
            }
            $adminRow = $adminResult->fetch_assoc();
            $adminUsername = $adminRow['username'];
    
            $inboundTagsEscaped = array_map(function($inbound) use ($vpnConn) {
                return "'" . $vpnConn->real_escape_string($inbound) . "'";
            }, $selectedInbounds);
            $inboundTagsList = implode(", ", $inboundTagsEscaped);
    
            $adminUsernameEscaped = $vpnConn->real_escape_string($adminUsername);
    
            $sql = "
                DELETE FROM exclude_inbounds_association
                WHERE proxy_id IN (
                    SELECT proxies.id
                    FROM users
                    INNER JOIN admins ON users.admin_id = admins.id
                    INNER JOIN proxies ON proxies.user_id = users.id
                    WHERE admins.username = '$adminUsernameEscaped'
                )
                AND inbound_tag IN ($inboundTagsList);
            ";
    
            if ($vpnConn->query($sql) === TRUE) {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['inbound_enabled']
                ]);
            } else {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['operation_failed']
                ]);
            }
    
            handleUserState('clear', $userId);

            $adminInfo = getAdminInfo($adminId, $userId);
            $adminInfo['adminId'] = $adminId;
            $infoText = getAdminInfoText($adminInfo, $userId);
    
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'parse_mode' => 'Markdown',
                'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
            ]);
    
            return;
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['operation_failed'],
                'show_alert' => false
            ]);
            return;
        }
    }
    if (strpos($data, 'toggle_prevent_user_reset:') === 0) {
        $adminId = intval(substr($data, strlen('toggle_prevent_user_reset:')));
    
        $triggerName = 'prevent_User_Reset_Usage';
    
        $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
        $adminIds = [];
        if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
            $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
            if ($triggerResult && $triggerResult->num_rows > 0) {
                $triggerRow = $triggerResult->fetch_assoc();
                $triggerBody = $triggerRow['SQL Original Statement'];
                if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                    $adminIdsStr = $matches[1];
                    $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                    $adminIds = explode(',', $adminIdsStr);
                }
            }
        }
    
        if (in_array($adminId, $adminIds)) {
            $adminIds = array_diff($adminIds, [$adminId]);
        } else {
            $adminIds[] = $adminId;
        }
    
        if (empty($adminIds)) {
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
        } else {
            $adminIdsStr = implode(', ', $adminIds);
            $triggerBody = "
            CREATE TRIGGER `$triggerName` BEFORE UPDATE ON `users`
            FOR EACH ROW
            BEGIN
                IF NEW.used_traffic <> OLD.used_traffic AND NEW.used_traffic = 0 THEN
                    IF OLD.admin_id IN ($adminIdsStr) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Edit is not allowed.';
                    END IF;    
                END IF;
            END;
            ";
    
            $vpnConn->query("DROP TRIGGER IF EXISTS `$triggerName`");
            $vpnConn->query($triggerBody);
        }
    
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
    
        $infoText = getAdminInfoText($adminInfo, $userId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'parse_mode' => 'Markdown',
            'text' => $lang['callbackResponse_showRestrictions'],
            'reply_markup' => getRestrictionsKeyboard(
                $adminId, 
                $adminInfo['preventUserDeletion'], 
                $adminInfo['preventUserCreation'], 
                $adminInfo['preventUserReset'], 
                $adminInfo['preventRevokeSubscription'], 
                $adminInfo['preventUnlimitedTraffic'],
                $userId
            )
        ]);
    }
    if (strpos($data, 'back_to_admin_management:') === 0) {
        $adminId = intval(substr($data, strlen('back_to_admin_management:')));

        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
        handleUserState('clear', $chatId);

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
        return;
    }
   if (strpos($data, 'set_traffic:') === 0) {
        $adminId = intval(substr($data, strlen('set_traffic:')));
        
        handleUserState('set', $userId, 'set_traffic', $adminId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['setTraffic_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        return;
    }
    if (strpos($data, 'set_expiry:') === 0) {
        $adminId = intval(substr($data, strlen('set_expiry:')));
        
        $response = sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['setExpiryDays_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
    
    handleUserState('set', $userId, 'set_expiry', $adminId);

        return;
    }
    if (strpos($data, 'disable_users:') === 0) {
        $adminId = intval(substr($data, strlen('disable_users:')));

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['delete_users_confirmation'],
            'reply_markup' => getConfirmationKeyboard($adminId, $userId)
        ]);
        return;
    }
    if (strpos($data, 'confirm_disable_yes:') === 0) {
        $adminId = intval(substr($data, strlen('confirm_disable_yes:')));

        $vpnConn->query("UPDATE users SET status = 'disabled' WHERE admin_id = '$adminId' AND status = 'active'");

        $stmt = $botConn->prepare("UPDATE admin_settings SET status = 'disabled' WHERE admin_id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $stmt->close();

        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['users_disabled']
        ]);
        $infoText = getAdminInfoText($adminInfo, $userId);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);

        return;
    }
    if (strpos($data, 'enable_users:') === 0) {
        $adminId = intval(substr($data, strlen('enable_users:')));

        $vpnConn->query("UPDATE users SET status = 'active' WHERE admin_id = '$adminId' AND status = 'disabled'");

        $stmt = $botConn->prepare("UPDATE admin_settings SET status = 'active' WHERE admin_id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $stmt->close();

        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['callbackResponse_adminNotFound']
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['users_enabled']
                ]);
        $infoText = getAdminInfoText($adminInfo, $userId);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);

        return;
    }
    if (strpos($data, 'limit_inbounds:') === 0) {
        $adminId = intval(substr($data, strlen('limit_inbounds:')));
        $adminInfo = getAdminInfo($adminId, $userId);
    
        if (!$adminInfo || !isset($adminInfo['username'])) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId, 
                'text' => $lang['invalid_operation'],
                'show_alert' => false
            ]);
            return;
        }
    
        $inboundsResult = $vpnConn->query("SELECT tag FROM inbounds");
        $inbounds = [];
        while ($row = $inboundsResult->fetch_assoc()) {
            $inbounds[] = $row['tag'];
        }
    
        $eventName = "limit_inbound_for_admin_" . $adminInfo['username']; 
        $selectedInbounds = [];
    
        $eventExistsResult = $vpnConn->query("SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA = DATABASE() AND EVENT_NAME = '$eventName'");
        if ($eventExistsResult && $eventExistsResult->num_rows > 0) {
            $eventResult = $vpnConn->query("SHOW CREATE EVENT `$eventName`");
            if ($eventResult && $eventResult->num_rows > 0) {
                $eventRow = $eventResult->fetch_assoc();
                $eventBody = $eventRow['Create Event'];
                preg_match_all("/SELECT '([^']+)' AS inbound_tag/", $eventBody, $matches);
                if (isset($matches[1])) {
                    $selectedInbounds = $matches[1];
                }
            }
        } else {
            $selectedInbounds = [];
        }
    
        $keyboard = [];
        foreach ($inbounds as $inbound) {
            $isSelected = in_array($inbound, $selectedInbounds);
            $emoji = $isSelected ? '✅' : '';
            $keyboard[] = [
                'text' => $emoji . $inbound,
                'callback_data' => 'toggle_inbound:' . $adminId . ':' . $inbound
            ];
        }
    
        $keyboard = array_chunk($keyboard, 2);
        $keyboard[] = [
            ['text' => $nextStepButton, 'callback_data' => 'confirm_inbounds:' . $adminId],
            ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
        ];
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['limitInbounds_info'],
            'reply_markup' => ['inline_keyboard' => $keyboard]
        ]);
        return;
    }
    if (strpos($data, 'toggle_inbound:') === 0) {
        list(, $adminId, $inboundTag) = explode(':', $data);
    
        $adminInfo = getAdminInfo($adminId, $userId);
        if (!$adminInfo || !isset($adminInfo['username'])) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['invalid_operation'],
                'show_alert' => false
            ]);
            return;
        }
    
        $eventName = "limit_inbound_for_admin_" . $adminInfo['username'];
    
        $eventExistsResult = $vpnConn->query("SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA = DATABASE() AND EVENT_NAME = '$eventName'");
        $selectedInbounds = [];
        if ($eventExistsResult && $eventExistsResult->num_rows > 0) {
            $eventResult = $vpnConn->query("SHOW CREATE EVENT `$eventName`");
            if ($eventResult && $eventResult->num_rows > 0) {
                $eventRow = $eventResult->fetch_assoc();
                $eventBody = $eventRow['Create Event'];
                preg_match_all("/SELECT '([^']+)' AS inbound_tag/", $eventBody, $matches);
                $selectedInbounds = isset($matches[1]) ? $matches[1] : [];
    
                if (in_array($inboundTag, $selectedInbounds)) {
                    $selectedInbounds = array_diff($selectedInbounds, [$inboundTag]);
                } else {
                    $selectedInbounds[] = $inboundTag;
                }
            } else {
                $selectedInbounds = [$inboundTag];
            }
        } else {
            $selectedInbounds = [$inboundTag];
        }
    
        if (empty($selectedInbounds)) {
            $vpnConn->query("DROP EVENT IF EXISTS `$eventName`");
        } else {
            $adminUsername = $vpnConn->real_escape_string($adminInfo['username']);
            $inboundSelects = array_map(function ($tag) {
                return "SELECT '$tag' AS inbound_tag";
            }, $selectedInbounds);
            $inboundUnion = implode(" UNION ALL ", $inboundSelects);
    
            $eventBody = "
                INSERT INTO exclude_inbounds_association (proxy_id, inbound_tag)
                SELECT proxies.id, inbound_tag_mapping.inbound_tag
                FROM users
                INNER JOIN admins ON users.admin_id = admins.id
                INNER JOIN proxies ON proxies.user_id = users.id
                CROSS JOIN (
                    $inboundUnion
                ) AS inbound_tag_mapping
                LEFT JOIN exclude_inbounds_association eia 
                  ON eia.proxy_id = proxies.id AND eia.inbound_tag = inbound_tag_mapping.inbound_tag
                WHERE admins.username = '$adminUsername'
                AND eia.proxy_id IS NULL;
            ";
    
            $vpnConn->query("DROP EVENT IF EXISTS `$eventName`");
    
            $vpnConn->query("
                CREATE EVENT `$eventName`
                ON SCHEDULE EVERY 1 SECOND
                DO
                $eventBody
            ");
        }
    
        $inboundsResult = $vpnConn->query("SELECT tag FROM inbounds");
        $inbounds = [];
        while ($row = $inboundsResult->fetch_assoc()) {
            $inbounds[] = $row['tag'];
        }
    
        $keyboard = [];
        foreach ($inbounds as $inbound) {
            $isSelected = in_array($inbound, $selectedInbounds);
            $emoji = $isSelected ? '✅' : '';
            $keyboard[] = [
                'text' => $emoji . $inbound,
                'callback_data' => 'toggle_inbound:' . $adminId . ':' . $inbound
            ];
        }
    
        $keyboard = array_chunk($keyboard, 2);
        $keyboard[] = [
            ['text' => $nextStepButton, 'callback_data' => 'confirm_inbounds:' . $adminId],
            ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
        ];
    
        sendRequest('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => ['inline_keyboard' => $keyboard]
        ]);
        return;
    }
    if (strpos($data, 'add_protocol:') === 0) {
        $adminId = intval(substr($data, strlen('add_protocol:')));
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['add_protocol_prompt'],
            'reply_markup' => getProtocolSelectionKeyboard($adminId, 'select_add_protocol', $userId)
        ]);
        return;
    }
    if (strpos($data, 'remove_protocol:') === 0) {
        $adminId = intval(substr($data, strlen('remove_protocol:')));
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['remove_protocol_prompt'],
            'reply_markup' => getProtocolSelectionKeyboard($adminId, 'select_remove_protocol', $userId)
        ]);
        return;
    }
    if (strpos($data, 'select_add_protocol:') === 0) {
        list(, $protocol, $adminId) = explode(':', $data);
    
        $stmt = $vpnConn->prepare("SELECT username FROM admins WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $adminResult = $stmt->get_result();
        $stmt->close();
    
        if ($adminResult->num_rows === 0) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['callbackResponse_adminNotFound'],
                'show_alert' => false
            ]);
            return;
        }
    
        $adminRow = $adminResult->fetch_assoc();
        $adminUsername = $vpnConn->real_escape_string($adminRow['username']); 

        $vpnConn->query("SET foreign_key_checks = 0");
    
        $stmt = $vpnConn->prepare("
            INSERT INTO proxies (user_id, type, settings)
            SELECT users.id, ?, CONCAT('{\"id\": \"', CONVERT(UUID(), CHAR), '\"}') 
            FROM users 
            INNER JOIN admins ON users.admin_id = admins.id 
            WHERE admins.username = ? 
            AND NOT EXISTS (
                SELECT 1 FROM proxies 
                WHERE proxies.user_id = users.id 
                AND proxies.type = ?
            );
        ");
        $stmt->bind_param("sss", $protocol, $adminUsername, $protocol);
    
        if ($stmt->execute()) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['protocol_added'],
                'show_alert' => false
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['protocol_add_error'],
                'show_alert' => false
            ]);
        }
        $stmt->close();
    
        $vpnConn->query("SET foreign_key_checks = 1");
    
        $adminInfo = getAdminInfo($adminId, $userId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
    
        return;
    }
    if (strpos($data, 'select_remove_protocol:') === 0) {
        list(, $protocol, $adminId) = explode(':', $data);
    
        $stmt = $vpnConn->prepare("SELECT username FROM admins WHERE id = ?");
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $adminResult = $stmt->get_result();
        $stmt->close();
    
        if ($adminResult->num_rows === 0) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['callbackResponse_adminNotFound'],
                'show_alert' => false
            ]);
            return;
        }
    
        $adminRow = $adminResult->fetch_assoc();
        $adminUsername = $vpnConn->real_escape_string($adminRow['username']); 
        $vpnConn->query("SET foreign_key_checks = 0");

        $stmt = $vpnConn->prepare("
            DELETE FROM proxies
            WHERE type = ? 
              AND user_id IN (
                SELECT users.id
                FROM users
                INNER JOIN admins ON users.admin_id = admins.id
                WHERE admins.username = ?
              );
        ");
        $stmt->bind_param("ss", $protocol, $adminUsername);
    
        if ($stmt->execute()) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['protocol_removed'],
                'show_alert' => false
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => $lang['protocol_remove_error'],
                'show_alert' => false
            ]);
        }
        $stmt->close();
    
        $vpnConn->query("SET foreign_key_checks = 1");
    
        $adminInfo = getAdminInfo($adminId, $userId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
    
        return;
    }
    if (strpos($data, 'add_data_limit:') === 0) {
        $adminId = intval(substr($data, strlen('add_data_limit:')));
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['setTraffic_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        handleUserState('set', $userId, 'add_data_limit', $adminId);
        return;
    }
    if (strpos($data, 'subtract_data_limit:') === 0) {
        $adminId = intval(substr($data, strlen('subtract_data_limit:')));
        
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['reduceVolume_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        handleUserState('set', $userId, 'subtract_data_limit', $adminId);
        return;
    }
    if (strpos($data, 'security:') === 0) {
        $adminId = intval(substr($data, strlen('security:')));
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['security_settings'],
            'reply_markup' => getSecurityKeyboard($adminId, $userId)
        ]);
        return;
    }
    if (strpos($data, 'change_password:') === 0) {
        $adminId = intval(substr($data, strlen('change_password:')));
        handleUserState('set', $userId, 'set_new_password', $adminId);

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['enter_new_password'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        handleUserState('set', $userId, 'set_new_password', $adminId);
        return;
    }
    if (strpos($data, 'change_sudo:') === 0) {
        $adminId = intval(substr($data, strlen('change_sudo:')));
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['sudo_confirmation'],
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => $lang['confirm_yes_button'], 'callback_data' => 'set_sudo_yes:' . $adminId],
                        ['text' => $lang['confirm_no_button'], 'callback_data' => 'set_sudo_no:' . $adminId]
                    ],
                    [
                        ['text' => $lang['back'], 'callback_data' => 'security:' . $adminId]
                    ]
                ]
            ]
        ]);
        return;
    }
    if (strpos($data, 'set_sudo_yes:') === 0) {
        $adminId = intval(substr($data, strlen('set_sudo_yes:')));
        $vpnConn->query("UPDATE admins SET is_sudo = 1 WHERE id = '$adminId'");
        $adminInfo = getAdminInfo($adminId, $userId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['sudo_enabled'],
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
        return;
    }
    if (strpos($data, 'set_sudo_no:') === 0) {
        $adminId = intval(substr($data, strlen('set_sudo_no:')));
        $vpnConn->query("UPDATE admins SET is_sudo = 0 WHERE id = '$adminId'");
        $adminInfo = getAdminInfo($adminId, $userId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo, $userId);
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['sudo_disabled'],
        ]);
        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $infoText,
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);

        return;
    }
    if (strpos($data, 'change_telegram_id:') === 0) {
        $adminId = intval(substr($data, strlen('change_telegram_id:')));
        handleUserState('set', $userId, 'set_new_telegram_id', $adminId);

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['enterNewTelegramId_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        handleUserState('set', $userId, 'set_new_telegram_id', $adminId);
        return;
    }
    if (strpos($data, 'change_username:') === 0) {
        $adminId = intval(substr($data, strlen('change_username:')));
        handleUserState('set', $userId, 'set_new_username', $adminId);

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['username_prompt'],
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        handleUserState('set', $userId, 'set_new_username', $adminId);
        return;
    }
    if ($data === 'add_admin') {
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['add_admin_prompt'],
            'reply_markup' => getbacktoadminselectbutton($userId)
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stateset = 'waiting_for_username';

        handleUserState('set', $userId, $stateset);


        return;
    }
    if ($data === 'generate_random_password') {
        $generatedPassword = generateRandomPassword(12);
        $hashedPassword = password_hash($generatedPassword, PASSWORD_BCRYPT);
        
        handleTemporaryData('set', $userId, 'new_admin_password', $hashedPassword);
        handleTemporaryData('set', $userId, 'new_admin_password_nothashed', $generatedPassword);
        
        $textpass = $lang['sudo_confirmation'] . "\n\n" . $lang['password_generated'] . " `$generatedPassword`";
        
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $textpass,
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => $lang['confirm_yes_button'], 'callback_data' => 'sudo_yes'],
                        ['text' => $lang['confirm_no_button'], 'callback_data' => 'sudo_no']
                    ],
                    [
                        ['text' => $lang['back'], 'callback_data' => 'manage_admins']
                    ]
                ]
            ]
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stateset = 'waiting_for_sudo';

        handleUserState('set', $userId, $stateset);

        return;
    }
    if ($data === 'sudo_yes') {

    handleTemporaryData('set', $userId, 'new_admin_sudo', 1);
        
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $lang['telegram_id_prompt'],
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Skip', 'callback_data' => 'skip_telegram_id']
                    ],
                    [
                        ['text' => $lang['back'], 'callback_data' => 'manage_admins']
                    ]
                ]
            ]
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stateset = 'waiting_for_telegram_id';

        handleUserState('set', $userId, $stateset);

        return;
    }
    if ($data === 'sudo_no') {
        
        handleTemporaryData('set', $userId, 'new_admin_sudo', 0);
        
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
             'text' => $lang['telegram_id_prompt'],
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Skip', 'callback_data' => 'skip_telegram_id']
                    ],
                    [
                        ['text' => $lang['back'], 'callback_data' => 'manage_admins']
                    ]
                ]
            ]
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stateset = 'waiting_for_telegram_id';
        handleUserState('set', $userId, $stateset);
        return;
    }
    if ($data === 'skip_telegram_id') {

        handleTemporaryData('set', $userId, 'new_admin_telegram_id', 0);

        
        createAdmin($userId, $chatId);
        return;
    }
    if (strpos($data, 'set_lang_') === 0) {
            $selectedLang = substr($data, 9); 
            
            $stmt = $botConn->prepare("UPDATE user_states SET lang = ? WHERE user_id = ?");
            $stmt->bind_param("si", $selectedLang, $userId);
            $stmt->execute();
        
            $confirmMessages = [
                'fa' => 'زبان شما با موفقیت تنظیم شد. لطفاً دستور /start را دوباره ارسال کنید.',
                'en' => 'Your language has been successfully set. Please send the /start command again.',
                'ru' => 'Ваш язык успешно установлен. Пожалуйста, отправьте команду /start снова.'
            ];
        
            $confirmationMessage = $confirmMessages[$selectedLang] ?? $confirmMessages['en'];

            $promptMessageId = $userState['message_id'];

            sendRequest('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $promptMessageId
            ]);
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $confirmationMessage
            ]);
            return;
        }
        if ($data === 'account_info') {
            $adminInfo = getAdminInfo($userId); 
            $lang = getLang($userId); 
        
            $stmt = $botConn->prepare("SELECT username, updated_at, lang, message_id FROM user_states WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $username = null;
            $updated_at = null;
            $language = null;
            $promptMessageId = null;
            if ($row = $result->fetch_assoc()) {
                $username = $row['username'];
                $updated_at = $row['updated_at'];
                $language = $row['lang'];
                $promptMessageId = $row['message_id'];
            }
            
            $stmt->close();
            
            sendRequest('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $promptMessageId
            ]);
            
            $infoText = "🧸 **User ID :** `$userId`\n";
            $infoText .= "🧸 **UserName :** @\n"; 
            $infoText .= "📅 **Latest changes :** `$updated_at`\n"; 
            $infoText .= "🌐 **Current language :** `$language`\n"; 
        
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '🔄 change language', 'callback_data' => 'change_language'],
                            ['text' => $lang['back'], 'callback_data' => 'back_to_main']
                        ]
                    ]
                ])
            ]);
        }
        if ($data === 'change_language') {
            
            $stmt = $botConn->prepare("SELECT username, updated_at, lang, message_id FROM user_states WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $username = null;
            $updated_at = null;
            $language = null;
            $promptMessageId = null;
            if ($row = $result->fetch_assoc()) {
                $username = $row['username'];
                $updated_at = $row['updated_at'];
                $language = $row['lang'];
                $promptMessageId = $row['message_id'];
            }
            
            $stmt->close();

            $langSelectionText = "Please select your language:\nПожалуйста, выберите язык:\nلطفاً زبان خود را انتخاب کنید:";

            sendRequest('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $promptMessageId
            ]);
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $langSelectionText,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '🇮🇷 فارسی', 'callback_data' => 'set_lang_fa'],
                            ['text' => '🇬🇧 English', 'callback_data' => 'set_lang_en'],
                            ['text' => '🇷🇺 Русский', 'callback_data' => 'set_lang_ru']
                        ],
                        [
                            ['text' => $lang['back'], 'callback_data' => 'account_info']
                        ]
                    ]
                ])
            ]);
        }
    }
    

    function handleMessage($message) {
        global $botConn, $vpnConn;
    
        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');
        $userId = $message['from']['id'];

        $lang = getLang($userId);

        $userRole = getUserRole($userId);
    
        if ($userRole === 'unauthorized') {
            file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Unauthorized user: $userId\n", FILE_APPEND);
            sendRequest('sendMessage', ['chat_id' => $chatId, 'text' => $lang['error_unauthorized']]);
            exit;
        }
    
        $userState = handleUserState('get', $userId);

        if ($userState) {
            if ($userState['state'] === 'add_data_limit') {
                $dataLimit = floatval($text); 
                if ($dataLimit > 0) {
                    $adminId = $userState['admin_id'];
                    $promptMessageId = $userState['message_id'];
                    $dataLimitBytes = $dataLimit * 1073741824;
    
                    $sql = "UPDATE users SET data_limit = data_limit + $dataLimitBytes WHERE data_limit IS NOT NULL AND admin_id in ($adminId)";
                    if ($vpnConn->query($sql) === TRUE) {

                        sendRequest('deleteMessage', [
                            'chat_id' => $chatId,
                            'message_id' => $promptMessageId
                        ]);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['data_limit_added']
                    ]);
    
                    $adminInfo = getAdminInfo($adminId, $userId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo, $userId);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                handleUserState('clear', $userId);

                }
                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['invalid_input']
                    ]);
                    return;
                }
            }
            if ($userState['state'] === 'subtract_data_limit') {
                $dataLimit = floatval($text); 
                if ($dataLimit > 0) {
                    $dataLimitBytes = $dataLimit * 1073741824;
                    $promptMessageId = $userState['message_id'];
                    $adminId = $userState['admin_id'];

    
                    $sql = "UPDATE users SET data_limit = data_limit - (1073741824 * $dataLimit) WHERE data_limit IS NOT NULL AND admin_id IN ($adminId)";
                    if ($vpnConn->query($sql) === TRUE) {
                    $adminId = $userState['admin_id'];
                    $promptMessageId = $userState['message_id'];

                    sendRequest('deleteMessage', [
                        'chat_id' => $chatId,
                        'message_id' => $promptMessageId
                    ]);
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['data_limit_subtracted']
                    ]);
    
                    $adminInfo = getAdminInfo($adminId, $userId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo, $userId);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    handleUserState('clear', $userId);
          }
            return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['invalid_input']
                    ]);
                    return;
                }
            }
            if ($userState['state'] === 'set_user_limit') {
                $userLimit = intval($text);
                if ($userLimit > 0) {
                    $adminId = $userState['admin_id'];
                    $promptMessageId = $userState['message_id'];

                    $stmt = $botConn->prepare("INSERT INTO admin_settings (admin_id, user_limit) VALUES (?, ?) ON DUPLICATE KEY UPDATE user_limit = ?");
                    $stmt->bind_param("iii", $adminId, $userLimit, $userLimit);
                    $stmt->execute();
                    $stmt->close();
                    $adminInfo = getAdminInfo($adminId, $userId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo, $userId);

                    sendRequest('deleteMessage', [
                        'chat_id' => $chatId,
                        'message_id' => $promptMessageId
                    ]);

                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['setUserLimit_success'],
                    ]);
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);

                    handleUserState('clear', $userId);

                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['invalid_input']
                    ]);
                }
                return;
            }
            if ($userState['state'] === 'add_time') {
                $days = intval($text);
                if ($days > 0) {
                    $adminId = $userState['admin_id'];
                    $secondsToAdd = 86400 * $days;
                    $promptMessageId = $userState['message_id'];

                    $sql = "UPDATE users SET expire = expire + ($secondsToAdd) WHERE expire IS NOT NULL AND admin_id IN ($adminId)";
                    if ($vpnConn->query($sql) === TRUE) {

                        sendRequest('deleteMessage', [
                            'chat_id' => $chatId,
                            'message_id' => $promptMessageId
                        ]);
    
                        sendRequest('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => $lang['setExpiryDays_success']
                        ]);
                    } else {
                        sendRequest('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => $lang['operation_failed'] . $vpnConn->error
                        ]);
                    }
    
                    $adminInfo = getAdminInfo($adminId, $userId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo, $userId);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    handleUserState('clear', $userId);

                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['invalid_input']
                    ]);
                    return;
                }
            }
            if ($userState['state'] === 'reduce_time') {
                $days = intval($text);
                if ($days > 0) {
                    $adminId = $userState['admin_id'];
                    $secondsToReduce = 86400 * $days;
                    $promptMessageId = $userState['message_id'];
    
                    $sql = "UPDATE users SET expire = expire - ($secondsToReduce) WHERE expire IS NOT NULL AND admin_id IN ($adminId)";
                    if ($vpnConn->query($sql) === TRUE) {

                        sendRequest('deleteMessage', [
                            'chat_id' => $chatId,
                            'message_id' => $promptMessageId
                        ]);
    
                        sendRequest('deleteMessage', [
                            'chat_id' => $chatId,
                            'message_id' => $promptMessageId
                        ]);
                        sendRequest('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => $lang['reduceExpiryDays_success']
                        ]);
                    } else {
                        sendRequest('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => $lang['operation_failed'] . $vpnConn->error
                        ]);
                    }
    
                    $adminInfo = getAdminInfo($adminId, $userId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo, $userId);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    handleUserState('clear', $userId);

                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['invalid_input']
                    ]);
                    return;
                }
            }
            if ($userState['state'] === 'set_traffic') {
                $traffic = floatval($text);
                if ($traffic > 0) {
                    $adminId = $userState['admin_id'];
                    $promptMessageId = $userState['message_id']; 
                    $totalTrafficBytes = $traffic * 1073741824;
            
                    $stmt = $botConn->prepare("INSERT INTO admin_settings (admin_id, total_traffic) VALUES (?, ?) ON DUPLICATE KEY UPDATE total_traffic = ?");
                    $stmt->bind_param("iii", $adminId, $totalTrafficBytes, $totalTrafficBytes);
                    $stmt->execute();
                    $stmt->close();
            
                    sendRequest('deleteMessage', [
                        'chat_id' => $chatId,
                        'message_id' => $promptMessageId
                    ]);
            
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['setNewTraffic_success']
                    ]);
            
                    $adminInfo = getAdminInfo($adminId, $userId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo, $userId);
            
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
            
                    handleUserState('clear', $userId);

                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['invalid_input']
                    ]);
                    return;
                }
            }
            if ($userState['state'] === 'set_expiry') {
                $days = intval($text);
                if ($days > 0) {
                    $adminId = $userState['admin_id'];
                    $expiryDate = date('Y-m-d', strtotime("+$days days"));
                    $promptMessageId = $userState['message_id']; 

    
                    $stmt = $botConn->prepare("INSERT INTO admin_settings (admin_id, expiry_date) VALUES (?, ?) ON DUPLICATE KEY UPDATE expiry_date = ?");
                    $stmt->bind_param("iss", $adminId, $expiryDate, $expiryDate);
                    $stmt->execute();
                    $stmt->close();
                    sendRequest('deleteMessage', [
                        'chat_id' => $chatId,
                        'message_id' => $promptMessageId
                    ]);
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['setNewExpiry_success']
                    ]);
    
                    $adminInfo = getAdminInfo($adminId, $userId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo, $userId);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'parse_mode' => 'Markdown',
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    handleUserState('clear', $userId);

                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['invalid_input']
                    ]);
                    return;
                }
            }
        }
        if ($userState['state'] === 'set_new_password') {
            $hashedPassword = password_hash($text, PASSWORD_BCRYPT);
            $adminId = $userState['admin_id'];
            $stmt = $vpnConn->prepare("UPDATE admins SET hashed_password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $adminId);
            $stmt->execute();
            $stmt->close();
            $promptMessageId = $userState['message_id'];

            sendRequest('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $promptMessageId
            ]);

            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['password_changed']
            ]);
            $adminInfo = getAdminInfo($adminId, $userId);
            $adminInfo['adminId'] = $adminId;
            $infoText = getAdminInfoText($adminInfo, $userId);
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'parse_mode' => 'Markdown',
                'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
            ]);
            handleUserState('clear', $userId);
            return;
        }
        if ($userState['state'] === 'set_new_telegram_id') {
            if (is_numeric($text)) {
                $telegramId = intval($text);
                $adminId = $userState['admin_id'];
                $stmt = $vpnConn->prepare("UPDATE admins SET telegram_id = ? WHERE id = ?");
                $stmt->bind_param("ii", $telegramId, $adminId);
                $stmt->execute();
                $stmt->close();
                $promptMessageId = $userState['message_id'];

                sendRequest('deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $promptMessageId
                ]);
    
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['telegram_id_changed']
                ]);
                $adminInfo = getAdminInfo($adminId, $userId);
                $adminInfo['adminId'] = $adminId;
                $infoText = getAdminInfoText($adminInfo, $userId);
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $infoText,
                    'parse_mode' => 'Markdown',
                    'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                ]);
                handleUserState('clear', $userId);

            } else {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['invalid_input']
                ]);
            }
            return;
        }
        if ($userState['state'] === 'set_new_username') {
            $newUsername = $text;
            $adminId = $userState['admin_id'];
            $stmt = $vpnConn->prepare("UPDATE admins SET username = ? WHERE id = ?");
            $stmt->bind_param("si", $newUsername, $adminId);
            $stmt->execute();
            $stmt->close();
            $promptMessageId = $userState['message_id'];

            sendRequest('deleteMessage', [
                'chat_id' => $chatId,
                'message_id' => $promptMessageId
            ]);

            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $lang['username_changed']
            ]);
            $adminInfo = getAdminInfo($adminId, $userId);
            $adminInfo['adminId'] = $adminId;
            $infoText = getAdminInfoText($adminInfo, $userId);
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'parse_mode' => 'Markdown',
                'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
            ]);
            handleUserState('clear', $userId);
            return;
        }
        if ($userState['state'] === 'waiting_for_username') {
            if (preg_match('/^[a-zA-Z0-9]+$/', $text)) {
                $username = $text;
                $adminId = $userState['admin_id'];
                
                $stmt = $vpnConn->prepare("SELECT id FROM admins WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $promptMessageId = $userState['message_id'];

                    sendRequest('deleteMessage', [
                        'chat_id' => $chatId,
                        'message_id' => $promptMessageId
                    ]);
        
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['username_taken'],
                        'reply_markup' => getbacktoadminselectbutton($userId)
                    ]);

                    $stateset = 'waiting_for_username';
                    handleUserState('set', $userId, $stateset);
            
                    return;
                }
                $stmt->close();
                
                handleTemporaryData('set', $userId, 'new_admin_username', $username);
                
                handleUserState('set', $userId, 'waiting_for_password');

                $promptMessageId = $userState['message_id'];

                sendRequest('deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $promptMessageId
                ]);
    
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['password_prompt'],
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                ['text' => 'Generate Random', 'callback_data' => 'generate_random_password']
                            ],
                            [
                                ['text' => $lang['back'], 'callback_data' => 'manage_admins']
                            ]
                        ]
                    ]
                ]);
                $stateset = 'waiting_for_password';
                handleUserState('set', $userId, $stateset);
                return;
            } else {
                $adminId = $userState['admin_id'];
                $promptMessageId = $userState['message_id'];

                sendRequest('deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $promptMessageId
                ]);
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['invalid_username'],
                    'reply_markup' => getbacktoadminselectbutton($userId)
                ]);
                if (isset($response['result']['message_id'])) {
                    $promptMessageId = $response['result']['message_id'];
                } else {
                    $promptMessageId = $messageId;
                }
                $stateset = 'waiting_for_username';
                handleUserState('set', $userId, $stateset);
               
                return;
            }
        }
        
        if ($userState['state'] === 'waiting_for_password') {
            if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $text)) {
                $hashedPassword = password_hash($text, PASSWORD_BCRYPT);

                handleTemporaryData('set', $userId, 'new_admin_password', $hashedPassword);
                
                $promptMessageId = $userState['message_id'];

                sendRequest('deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $promptMessageId
                ]);
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['sudo_confirmation'],
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                ['text' => $lang['confirm_yes_button'], 'callback_data' => 'sudo_yes'],
                                ['text' => $lang['confirm_no_button'], 'callback_data' => 'sudo_no']
                            ],
                            [
                                ['text' => $lang['back'], 'callback_data' => 'manage_admins']
                            ]
                        ]
                    ]
                ]);
                $stateset = 'waiting_for_sudo';
                handleUserState('set', $userId, $stateset);
                return;
            } else {
                $adminId = $userState['admin_id'];
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['invalid_password'],
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                ['text' => 'Generate Random', 'callback_data' => 'generate_random_password']
                            ],
                            [
                                ['text' => $lang['back'], 'callback_data' => 'manage_admins']
                            ]
                        ]
                    ]
                ]);
                $stateset = 'waiting_for_sudo';
                handleUserState('set', $userId, $stateset);
                return;
            }
        }
        if ($userState['state'] === 'waiting_for_sudo') {
            return;
        }
        if ($userState['state'] === 'waiting_for_telegram_id') {
            $adminId = $userState['admin_id'];
            if (is_numeric($text)) {
                $telegramId = intval($text);
                
                handleTemporaryData('set', $userId, 'new_admin_telegram_id', $telegramId);
                
                createAdmin($userId, $chatId);
                return;
            } elseif (strtolower($text) === 'skip') {

                handleTemporaryData('set', $userId, 'new_admin_telegram_id', 0);
                
                createAdmin($userId, $chatId);
                return;
            } else {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['enterValidTelegramId_prompt'],
                    'reply_markup' => getbacktoadminselectbutton($userId)
                ]);
                return;
            }
        }
        if ($text === '/start') {
            $stmt = $botConn->prepare("SELECT lang FROM user_states WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $lang = null;
        
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $lang = $row['lang'];
            } else {
                $stmt = $botConn->prepare("INSERT INTO user_states (user_id, lang, state) VALUES (?, NULL, NULL)");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
            }
        
            $stmt->close();
        
            if (empty($lang)) {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "سلام! خوش آمدید به ربات marzhelp.\nلطفاً زبان خود را انتخاب کنید.\n\nHello! Welcome to marzhelp bot.\nPlease select your language.\n\nПривет! Добро пожаловать в бот marzhelp.\nПожалуйста, выберите ваш язык.",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => '🇮🇷 فارسی', 'callback_data' => 'set_lang_fa'],
                                ['text' => '🇬🇧 English', 'callback_data' => 'set_lang_en'],
                                ['text' => '🇷🇺 Русский', 'callback_data' => 'set_lang_ru']
                            ]
                        ]
                    ])
                ]);
        
                return;
            }
        
            $lang = getLang($userId);
        
            if ($userRole === 'main_admin') {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $lang['main_menu'],
                    'reply_markup' => getMainMenuKeyboard($userId)
                ]);
        
            } elseif ($userRole === 'limited_admin') {

                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $lang['main_menu'],
                        'reply_markup' => getMainMenuKeyboard($userId)
                    ]);
                }
            }
        }
    
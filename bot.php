<?php
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

$vpnConn = new mysqli($vpnDbHost, $vpnDbUser, $vpnDbPass, $vpnDbName, $vpnDbPort);
if ($vpnConn->connect_error) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - VPN DB connection failed: " . $vpnConn->connect_error . "\n", FILE_APPEND);
    exit;
}
$vpnConn->set_charset("utf8");

$mainMenuButton = '🧸 مدیریت ادمین‌ها';
$setTrafficButton = '♾️ حجم ادمین';
$setExpiryButton = '⏳ انقضا ادمین';
$disableUsersButton = '🚫 غیرفعال‌سازی کاربران ';
$enableUsersButton = '✅ فعال‌سازی کاربران';
$confirmYesButton = '👍 بله';
$confirmNoButton = '👎 خیر';
$backButton = '🔙 بازگشت';
$refreshButton = '🔄 رفرش';
$limitInboundsButton = '⛔️ جلوگیری از استفاده اینباند ';
$nextStepButton = '🔪 محدودسازی';
$disableInboundsButton = '➖ حذف اینباند';
$enableInboundsButton = '➕ افزودن اینباند';
$addTimeButton = '➕ افزودن زمان';
$subtractTimeButton = '➖ کم کردن زمان';
$addProtocolButton = '➕ افزودن پروتکل';
$removeProtocolButton = '➖ حذف پروتکل';
$adddatalimitbutton = '➕ افزودن حجم';
$subtractdataButton = '➖ کم کردن حجم';
$setuserlimitButton = '🧸 محدودیت ساخت کاربر';
$GoToLimitsButton = '🛡️ محدودیت‌ها';
$preventUserCreationButton = '🛡️ جلوگیری ساخت کاربر';
$preventUserResetButton = '🛡️ جلوگیری ریست حجم';
$preventRevokeSubscriptionButton = '🛡️ جلوگیری Revoke';
$preventUserDeletionButton = '🛡️ جلوگیری حذف کاربر';
$preventUnlimitedTrafficButton = '🛡️ جلوگیری حجم نامحدود';
$securityButton = '🔒 امنیت';
$changePasswordButton = '🔑 تغییر پسورد';
$changeSudoButton = '🛡️ تغییر دسترسی سودو';
$changeTelegramIdButton = '📱 تغییر ایدی تلگرام';
$changeUsernameButton = '👤 تغییر نام کاربری';
$protocolsettingsbutton = '⚙️ تنظیمات اینباند';


function sendRequest($method, $parameters) {
    global $apiURL;
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
    return json_decode($response, true);
}

function getMainMenuKeyboard() {
    global $mainMenuButton;
    return [
        'inline_keyboard' => [
            [
                ['text' => $mainMenuButton, 'callback_data' => 'manage_admins']
            ]
        ]
    ];
}

function getbacktoadminselectbutton($userId) {
    global $backButton;
    return [
        'inline_keyboard' => [ 
            [
                ['text' => $backButton, 'callback_data' => 'back_to_admin_selection']
            ]
        ]
        ];
}

function getAdminKeyboard($userId, $adminId, $status) {
    global $allowedUsers; 
    
    if (in_array($userId, $allowedUsers)) {
        return getAdminManagementKeyboard($adminId, $status); 
    } else {
        return getLimitedAdminManagementKeyboard($adminId, $status); 
    }
}


function getAdminManagementKeyboard($adminId, $status) {
    global $setTrafficButton, $setExpiryButton, $disableUsersButton, $enableUsersButton, $backButton, $GoToLimitsButton,
    $refreshButton, $limitInboundsButton, $preventUserDeletionButton, $preventUserCreationButton, $preventUserResetButton, 
    $disableInboundsButton, $enableInboundsButton, $addTimeButton, $subtractTimeButton, $addProtocolButton, $removeProtocolButton, 
    $adddatalimitbutton, $subtractdataButton, $setuserlimitButton, $preventRevokeSubscriptionButton, $preventUnlimitedTrafficButton,
    $securityButton, $protocolsettingsbutton;


    return [
        'inline_keyboard' => [
            [
                ['text' => '⬇️ تنظیمات مربوط به مشخصات ادمین ⬇️', 'callback_data' => 'show_display_only']
            ],
            [
                ['text' => $setTrafficButton, 'callback_data' => 'set_traffic:' . $adminId],
                ['text' => $setExpiryButton, 'callback_data' => 'set_expiry:' . $adminId]
            ],
            [
                ['text' => $setuserlimitButton, 'callback_data' => 'set_user_limit:' . $adminId],
                ['text' => $securityButton, 'callback_data' => 'security:' . $adminId]
            ],
            [
                ['text' => '⬇️ تنظیمات مربوط به محدودیت‌های ادمین ⬇️', 'callback_data' => 'show_display_only']
            ],
            [
                ['text' => $limitInboundsButton, 'callback_data' => 'limit_inbounds:' . $adminId],
                ['text' => ($status === 'active') ? $disableUsersButton : $enableUsersButton, 'callback_data' => ($status === 'active') ? 'disable_users:' . $adminId : 'enable_users:' . $adminId]
            ],
            [
                ['text' => $GoToLimitsButton, 'callback_data' => 'show_restrictions:' . $adminId],
                ['text' => $protocolsettingsbutton, 'callback_data' => 'protocol_settings:' . $adminId]
            ],
            [
                ['text' => '⬇️ تنظیمات مربوط به کاربران ⬇️', 'callback_data' => 'show_display_only']
            ],
            [
                ['text' => $addTimeButton, 'callback_data' => 'add_time:' . $adminId],
                ['text' => $subtractTimeButton, 'callback_data' => 'reduce_time:' . $adminId]
            ],
            [
                ['text' => $adddatalimitbutton, 'callback_data' => 'add_data_limit:' . $adminId],
                ['text' => $subtractdataButton, 'callback_data' => 'subtract_data_limit:' . $adminId]
            ],
            [
                ['text' => $backButton, 'callback_data' => 'back_to_admin_selection']
            ]
        ]
    ];
}
function getprotocolsttingskeyboard($adminId) {
global $addProtocolButton, $removeProtocolButton, $enableInboundsButton, $disableInboundsButton, $backButton;
    return [
        'inline_keyboard' => [
            [
                ['text' => $addProtocolButton, 'callback_data' => 'add_protocol:' . $adminId],
                ['text' => $removeProtocolButton, 'callback_data' => 'remove_protocol:' . $adminId]
            ],
            [
                ['text' => $enableInboundsButton, 'callback_data' => 'enable_inbounds:' . $adminId],
                ['text' => $disableInboundsButton, 'callback_data' => 'disable_inbounds:' . $adminId]
            ],
            [
                ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
     ];
}
function getSecurityKeyboard($adminId) {
    global $changePasswordButton, $changeSudoButton, $changeTelegramIdButton, $changeUsernameButton, $backButton;
    return [
        'inline_keyboard' => [
            [
                ['text' => $changePasswordButton, 'callback_data' => 'change_password:' . $adminId],
                ['text' => $changeSudoButton, 'callback_data' => 'change_sudo:' . $adminId]
            ],
            [
                ['text' => $changeTelegramIdButton, 'callback_data' => 'change_telegram_id:' . $adminId],
                ['text' => $changeUsernameButton, 'callback_data' => 'change_username:' . $adminId]
            ],
            [
                ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
}

function getSudoConfirmationKeyboard($adminId) {
    global $confirmYesButton, $confirmNoButton;
    return [
        'inline_keyboard' => [
            [
                ['text' => $confirmYesButton, 'callback_data' => 'confirm_sudo_yes:' . $adminId],
                ['text' => $confirmNoButton, 'callback_data' => 'confirm_sudo_no:' . $adminId]
            ]
        ]
    ];
}

function getLimitedAdminManagementKeyboard($adminId, $status) {
    global $backButton, $addTimeButton, $subtractTimeButton, $adddatalimitbutton, $subtractdataButton;
    
    return [
        'inline_keyboard' => [
            [
                ['text' => $addTimeButton, 'callback_data' => 'add_time:' . $adminId],
                ['text' => $subtractTimeButton, 'callback_data' => 'reduce_time:' . $adminId]
            ],
            [
                ['text' => $adddatalimitbutton, 'callback_data' => 'add_data_limit:' . $adminId],
                ['text' => $subtractdataButton, 'callback_data' => 'subtract_data_limit:' . $adminId]
            ]
        ]
    ];
}

function getConfirmationKeyboard($adminId) {
    global $confirmYesButton, $confirmNoButton;
    return [
        'inline_keyboard' => [
            [
                ['text' => $confirmYesButton, 'callback_data' => 'confirm_disable_yes:' . $adminId],
                ['text' => $confirmNoButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
}

function getBackToAdminManagementKeyboard($adminId, $userId) {
    global $backButton;
    return [
        'inline_keyboard' => [
            [
                ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
}

function getBackToMainKeyboard() {
    global $backButton;
    
    return [
        'inline_keyboard' => [
            [
                ['text' => $backButton, 'callback_data' => 'back_to_main']
            ]
        ]
    ];
}

function getProtocolSelectionKeyboard($adminId, $action) {
    global $backButton;

    return [
        'inline_keyboard' => [
            [
                ['text' => 'VMess', 'callback_data' => $action . ':vmess:' . $adminId],
                ['text' => 'VLess', 'callback_data' => $action . ':vless:' . $adminId]
            ],
            [
                ['text' => 'Trojan', 'callback_data' => $action . ':trojan:' . $adminId],
                ['text' => 'Shadowsocks', 'callback_data' => $action . ':shadowsocks:' . $adminId]
            ],
            [
                ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId] 
            ]
        ]
    ];
}
function getRestrictionsKeyboard($adminId, $preventUserDeletion, $preventUserCreation, $preventUserReset, $preventRevokeSubscription, $preventUnlimitedTraffic) {
    global $preventUserDeletionButton, $preventUserCreationButton, $preventUserResetButton, $preventRevokeSubscriptionButton, $backButton,
     $preventUnlimitedTrafficButton;

    $preventUserDeletionStatus = $preventUserDeletion ? '(فعال✅)' : '(غیرفعال)';
    $preventUserDeletionButtonText = $preventUserDeletionButton . ' ' . $preventUserDeletionStatus;

    $preventUserCreationStatus = $preventUserCreation ? '(فعال✅)' : '(غیرفعال)';
    $preventUserCreationButtonText = $preventUserCreationButton . ' ' . $preventUserCreationStatus;

    $preventUserResetStatus = $preventUserReset ? '(فعال✅)' : '(غیرفعال)';
    $preventUserResetButtonText = $preventUserResetButton . ' ' . $preventUserResetStatus;

    $preventRevokeSubscriptionStatus = $preventRevokeSubscription ? '(فعال✅)' : '(غیرفعال)';
    $preventRevokeSubscriptionButtonText = $preventRevokeSubscriptionButton . ' ' . $preventRevokeSubscriptionStatus;

    $preventUnlimitedTrafficStatus = $preventUnlimitedTraffic ? '(فعال✅)' : '(غیرفعال)';
    $preventUnlimitedTrafficButtonText = $preventUnlimitedTrafficButton . ' ' . $preventUnlimitedTrafficStatus;



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
                ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
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

function getAdminInfo($adminId) {
    global $vpnConn, $botConn;

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
        SELECT 
            (SUM(users.used_traffic) + IFNULL(SUM(user_usage_logs.used_traffic_at_reset), 0)) / 1073741824 AS used_traffic_gb
        FROM admins 
        LEFT JOIN users ON users.admin_id = admins.id 
        LEFT JOIN user_usage_logs ON user_usage_logs.user_id = users.id
        WHERE admins.id = ?
        GROUP BY admins.username
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

    $totalTraffic = isset($settings['total_traffic']) ? round($settings['total_traffic'] / 1073741824, 2) : 'نامحدود';
    $remainingTraffic = ($totalTraffic !== 'نامحدود') ? round($totalTraffic - $usedTraffic, 2) : 'نامحدود';

    $expiryDate = isset($settings['expiry_date']) ? $settings['expiry_date'] : 'نامحدود';
    $daysLeft = ($expiryDate !== 'نامحدود') ? ceil((strtotime($expiryDate) - time()) / 86400) : 'نامحدود';

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

    $userLimit = isset($settings['user_limit']) ? $settings['user_limit'] : 'نامحدود';
    if ($userLimit !== 'نامحدود') {
        $remainingUserLimit = $userLimit - $userStats['active_users'];
    } else {
        $remainingUserLimit = 'نامحدود';
    }
    $triggerName = 'prevent_user_creation';
    $preventUserCreation = false;

    $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
    if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
        $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
        if ($triggerResult && $triggerResult->num_rows > 0) {
            $triggerRow = $triggerResult->fetch_assoc();
            $triggerBody = $triggerRow['SQL Original Statement'];
            if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                $adminIdsStr = $matches[1];
                $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                $adminIds = explode(',', $adminIdsStr);
                if (in_array($adminId, $adminIds)) {
                    $preventUserCreation = true;
                }
            }
        }
    }
    $triggerName = 'prevent_User_Reset_Usage';
    $preventUserReset = false;

    $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
    if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
        $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
        if ($triggerResult && $triggerResult->num_rows > 0) {
            $triggerRow = $triggerResult->fetch_assoc();
            $triggerBody = $triggerRow['SQL Original Statement'];
            if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                $adminIdsStr = $matches[1];
                $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                $adminIds = explode(',', $adminIdsStr);
                if (in_array($adminId, $adminIds)) {
                    $preventUserReset = true;
                }
            }
        }
    }
    $triggerName = 'prevent_revoke_subscription';
    $preventRevokeSubscription = false;

    $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
    if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
        $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
        if ($triggerResult && $triggerResult->num_rows > 0) {
            $triggerRow = $triggerResult->fetch_assoc();
            $triggerBody = $triggerRow['SQL Original Statement'];
            if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                $adminIdsStr = $matches[1];
                $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                $adminIds = explode(',', $adminIdsStr);
                if (in_array($adminId, $adminIds)) {
                    $preventRevokeSubscription = true;
                }
            }
        }
    }
    $triggerName = 'prevent_unlimited_traffic';
    $preventUnlimitedTraffic = false;
    
    $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
    if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
        $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
        if ($triggerResult && $triggerResult->num_rows > 0) {
            $triggerRow = $triggerResult->fetch_assoc();
            $triggerBody = $triggerRow['SQL Original Statement'];
            if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                $adminIdsStr = $matches[1];
                $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                $adminIds = explode(',', $adminIdsStr);
                if (in_array($adminId, $adminIds)) {
                    $preventUnlimitedTraffic = true; 
                }
            }
        }
    }

    $triggerName = 'admin_delete';
    $preventUserDelete = false; 
    
    $triggerExistsResult = $vpnConn->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME = '$triggerName'");
    if ($triggerExistsResult && $triggerExistsResult->num_rows > 0) {
        $triggerResult = $vpnConn->query("SHOW CREATE TRIGGER `$triggerName`");
        if ($triggerResult && $triggerResult->num_rows > 0) {
            $triggerRow = $triggerResult->fetch_assoc();
            $triggerBody = $triggerRow['SQL Original Statement'];
            if (preg_match("/IN\s*\((.*?)\)/", $triggerBody, $matches)) {
                $adminIdsStr = $matches[1];
                $adminIdsStr = str_replace(' ', '', $adminIdsStr);
                $adminIds = explode(',', $adminIdsStr);
                if (in_array($adminId, $adminIds)) {
                    $preventUserDelete = true;
                }
            }
        }
    }

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

    $username = getTemporaryData($userId, 'new_admin_username');
    $hashedPassword = getTemporaryData($userId, 'new_admin_password');
    $isSudo = getTemporaryData($userId, 'new_admin_sudo') ?? 0;
    $telegramId = getTemporaryData($userId, 'new_admin_telegram_id') ?? 0;
    $nothashedpassword = getTemporaryData($userId, 'new_admin_password_nothashed');
    $stmt = $botConn->prepare("SELECT state, admin_id, message_id FROM user_states WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userStateResult = $stmt->get_result();
    $userState = $userStateResult->fetch_assoc();
    $stmt->close();
    if (!$username || !$hashedPassword) {
        sendRequest('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => '❌ اطلاعات کافی برای افزودن ادمین جدید وجود ندارد.'
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

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => "✅ ادمین جدید اضافه شد:\n🧸 یوزرنیم: `$username`\n🔑 پسورد : `$nothashedpassword`\n📱 آیدی تلگرام: `$telegramId`",
            'parse_mode' => 'Markdown',
            'reply_markup' => getAdminKeyboard($chatId, $newAdminId, 'active')
        ]);
    } else {
        $promptMessageId = $userState['message_id'];

        sendRequest('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $promptMessageId
        ]);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => "❌ خطا در افزودن ادمین جدید: " . $stmt->error,
        ]);
    }
    $stmt->close();

    clearUserState($userId);
    clearTemporaryData($userId);
}


function setUserState($userId, $state, $messageId = null, $adminId = null) {
    global $botConn;

    if ($adminId !== null && $messageId !== null) {
        $sql = "INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE state = ?, admin_id = ?, message_id = ?";
        $stmt = $botConn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("isiiisi", $userId, $state, $adminId, $messageId, $state, $adminId, $messageId);
    } elseif ($adminId !== null) {
        $sql = "INSERT INTO user_states (user_id, state, admin_id) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE state = ?, admin_id = ?";
        $stmt = $botConn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("isisi", $userId, $state, $adminId, $state, $adminId);
    } elseif ($messageId !== null) {
        $sql = "INSERT INTO user_states (user_id, state, message_id) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE state = ?, message_id = ?";
        $stmt = $botConn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("isisi", $userId, $state, $messageId, $state, $messageId);
    } else {
        $sql = "INSERT INTO user_states (user_id, state) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE state = ?";
        $stmt = $botConn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("iss", $userId, $state, $state);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
}





function getUserState($userId) {
    global $botConn;
    $stmt = $botConn->prepare("SELECT state FROM user_states WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $state = null;
    if ($row = $result->fetch_assoc()) {
        $state = $row['state'];
    }
    $stmt->close();
    return $state;
}

function setTemporaryData($userId, $key, $value) {
    global $botConn;
    $stmt = $botConn->prepare("INSERT INTO user_temporaries (user_id, `key`, `value`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    $stmt->bind_param("isss", $userId, $key, $value, $value);
    $stmt->execute();
    $stmt->close();
}

function getTemporaryData($userId, $key) {
    global $botConn;
    $stmt = $botConn->prepare("SELECT `value` FROM user_temporaries WHERE user_id = ? AND `key` = ?");
    $stmt->bind_param("is", $userId, $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $value = null;
    if ($row = $result->fetch_assoc()) {
        $value = $row['value'];
    }
    $stmt->close();
    return $value;
}

function clearUserState($userId) {
    global $botConn;
    $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function clearTemporaryData($userId) {
    global $botConn;
    $stmt = $botConn->prepare("DELETE FROM user_temporaries WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}


function getAdminInfoText($adminInfo) {
    $statusText = ($adminInfo['status'] === 'active') ? '🟢 وضعیت : فعال' : '🔴 وضعیت : غیرفعال';
    $trafficText = ($adminInfo['totalTraffic'] !== 'نامحدود') ? "{$adminInfo['totalTraffic']} گیگابایت" : 'نامحدود';
    $remainingText = ($adminInfo['remainingTraffic'] !== 'نامحدود') ? "{$adminInfo['remainingTraffic']} گیگابایت" : 'نامحدود';
    $daysText = ($adminInfo['daysLeft'] !== 'نامحدود') ? "{$adminInfo['daysLeft']} روز" : 'نامحدود';
    $remainingUserLimit = ($adminInfo['remainingUserLimit'] !== 'نامحدود') ? "{$adminInfo['remainingUserLimit']} کاربر" : 'نامحدود';

    $infoText = "🧸 نام کاربری: {$adminInfo['username']}\n🧸 آیدی ادمین: {$adminInfo['userid']}\n{$statusText}\n📥 حجم مصرفی: {$adminInfo['usedTraffic']} گیگابایت\n💾 حجم کل: {$trafficText}\n📤 حجم باقی‌مانده: {$remainingText}\n👥 تعداد مجاز به ساخت کاربر: {$remainingUserLimit}\n⏳ زمان باقی‌مانده: {$daysText}";

    $userStatsText = "\n\n👥 آمار کاربران:\n";
    $userStatsText .= "👤 کل کاربران: {$adminInfo['userStats']['total_users']}\n";
    $userStatsText .= "🟩 فعال: {$adminInfo['userStats']['active_users']}\n";

    $expiredUsers = $adminInfo['userStats']['total_users'] - $adminInfo['userStats']['active_users'];

    $userStatsText .= "🟥 غیرفعال: {$expiredUsers}\n";
    $userStatsText .= "🟢 آنلاین: {$adminInfo['userStats']['online_users']}";

    return $infoText . $userStatsText;
}

$content = file_get_contents("php://input");
file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Received content: " . $content . "\n", FILE_APPEND);
$update = json_decode($content, true);
if (!$update) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - JSON decode failed\n", FILE_APPEND);
    exit;
}

if (isset($update['callback_query'])) {
    handleCallbackQuery($update['callback_query']);
} elseif (isset($update['message'])) {
    handleMessage($update['message']);
}
function isVpnAdmin($chatId, $vpnDb) {
    $stmt = $vpnDb->prepare("SELECT * FROM admins WHERE telegram_id = ?");
    $stmt->bind_param("s", $chatId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
function handleCallbackQuery($callback_query) {
    global $botConn, $vpnConn, $backButton, $mainMenuButton, $userId, $nextStepButton, $setTrafficButton, $setExpiryButton, $disableUsersButton, $enableUsersButton, $refreshButton, $limitInboundsButton, $confirmYesButton, $confirmNoButton, $preventUserDeletionButton, $addTimeButton, $subtractTimeButton;;

    $callbackId = $callback_query['id'];
    $userId = $callback_query['from']['id'];
    $data = $callback_query['data'];
    $messageId = $callback_query['message']['message_id'];
    $chatId = $callback_query['message']['chat']['id'];
    $userRole = getUserRole($userId);

    
    if ($userRole === 'unauthorized') {
        sendRequest('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => '🚫 شما دسترسی ندارید.',
            'show_alert' => false
        ]);
        return;
    }
    if ($data === 'show_display_only') {
        sendRequest('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => 'این دکمه صرفا نمایشی می‌باشد.',
            'show_alert' => true 
        ]);
        
        return;
    }
    if (strpos($data, 'protocol_settings:') === 0) {
        $adminId = intval(substr($data, strlen('protocol_settings:')));
    
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $getprotocolsttingskeyboardtext = 'شما در این بخش میتوانید برای کاربران ادمین انتخاب شده یک اینباند یا یک پروتکل رو فعال یا غیرفعال نمایید.';

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $getprotocolsttingskeyboardtext,
            'reply_markup' => getprotocolsttingskeyboard($adminId)
        ]);
    }


    if (strpos($data, 'show_restrictions:') === 0) {
        $adminId = intval(substr($data, strlen('show_restrictions:')));
    
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'شما در این بخش میتوانید ادمین خود را محدود به انجام یک عملیات در پنل کنید.:',
            'reply_markup' => getRestrictionsKeyboard($adminId, $adminInfo['preventUserDeletion'], $adminInfo['preventUserCreation'], $adminInfo['preventUserReset'], $adminInfo['preventRevokeSubscription'], $adminInfo['preventUnlimitedTraffic'])
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
    
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'شما در این بخش میتوانید ادمین خود را محدود به انجام یک عملیات در پنل کنید.:',
            'reply_markup' => getRestrictionsKeyboard($adminId, $adminInfo['preventUserDeletion'], $adminInfo['preventUserCreation'], $adminInfo['preventUserReset'], $adminInfo['preventRevokeSubscription'], $adminInfo['preventUnlimitedTraffic'])
        ]);
    }
    if (strpos($data, 'set_user_limit:') === 0) {
        $adminId = intval(substr($data, strlen('set_user_limit:')));
    
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'set_user_limit', ?, ?) ON DUPLICATE KEY UPDATE state = 'set_user_limit', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $messageId, $adminId, $messageId);
        $stmt->execute();
        $stmt->close();
    
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'لطفاً حداکثر تعداد کاربرانی که ادمین مجاز به ایجاد آنها است را وارد کنید.',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        return;
    }
    
    if (strpos($data, 'reduce_time:') === 0) {
        $adminId = intval(substr($data, strlen('reduce_time:')));
        
        $response = sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '➖ لطفاً مقدار روزهایی که قصددارید از زمان انقضای کاربران کم کنید را وارد نمایید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
    
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
    
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'reduce_time', ?, ?) ON DUPLICATE KEY UPDATE state = 'reduce_time', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $promptMessageId, $adminId, $promptMessageId);
        $stmt->execute();
        $stmt->close();
    
        return;
    }
    if (strpos($data, 'add_time:') === 0) {
        $adminId = intval(substr($data, strlen('add_time:')));
    
        $response = sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '➕ لطفاً مقدار روزهایی که قصددارید به زمان انقضای کاربران اضافه کنید را وارد نمایید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
    
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
    
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'add_time', ?, ?) ON DUPLICATE KEY UPDATE state = 'add_time', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $promptMessageId, $adminId, $promptMessageId);
        $stmt->execute();
        $stmt->close();
    
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
    
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'شما در این بخش میتوانید ادمین خود را محدود به انجام یک عملیات در پنل کنید.:',
            'reply_markup' => getRestrictionsKeyboard($adminId, $adminInfo['preventUserDeletion'], $adminInfo['preventUserCreation'], $adminInfo['preventUserReset'], $adminInfo['preventRevokeSubscription'], $adminInfo['preventUnlimitedTraffic'])
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
    
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'شما در این بخش میتوانید ادمین خود را محدود به انجام یک عملیات در پنل کنید.:',
            'reply_markup' => getRestrictionsKeyboard($adminId, $adminInfo['preventUserDeletion'], $adminInfo['preventUserCreation'], $adminInfo['preventUserReset'], $adminInfo['preventRevokeSubscription'], $adminInfo['preventUnlimitedTraffic'])
        ]);
    }
    
    
    if ($data === 'manage_admins') {
        $adminsResult = $vpnConn->query("SELECT id, username FROM admins");
        $admins = [];
        while ($row = $adminsResult->fetch_assoc()) {
            $admins[] = ['text' => $row['username'], 'callback_data' => 'select_admin:' . $row['id']];
        }
    
        if (empty($admins)) {
            sendRequest('editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => '🛠️ هیچ ادمینی اضافه نشده است.'
            ]);
            return;
        }
    
        $keyboard = ['inline_keyboard' => array_chunk($admins, 2)];
        $keyboard['inline_keyboard'][] = [
            ['text' => '➕ افزودن ادمین', 'callback_data' => 'add_admin'],
            ['text' => $backButton, 'callback_data' => 'back_to_main']
        ];
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '👇 ادمین مورد نظر خود را انتخاب کنید:',
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
    
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'شما در این بخش میتوانید ادمین خود را محدود به انجام یک عملیات در پنل کنید.:',
            'reply_markup' => getRestrictionsKeyboard($adminId, $adminInfo['preventUserDeletion'], $adminInfo['preventUserCreation'], $adminInfo['preventUserReset'], $adminInfo['preventRevokeSubscription'], $adminInfo['preventUnlimitedTraffic'])
                    ]);
    
        return;
    }
    if ($data === 'back_to_main') {
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '🏠 منوی اصلی',
            'reply_markup' => getMainMenuKeyboard()
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
            ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
        ];
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'لطفاً یک اینباند را برای غیرفعالسازی انتخاب کنید:',
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
                'text' => 'ادمین یافت نشد.',
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
                'text' => 'اینباند غیرفعال شد.',
                'show_alert' => false
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => 'خطا در اجرای عملیات.',
                'show_alert' => false
            ]);
        }
    
        $adminInfo = getAdminInfo($adminId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
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
                'text' => 'ادمین یافت نشد.',
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
                'text' => 'اینباند فعال شد.',
                'show_alert' => false
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => 'خطا در اجرای عملیات.',
                'show_alert' => false
            ]);
        }
    
        $adminInfo = getAdminInfo($adminId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
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
            ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
        ];
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'لطفاً یک اینباند را برای فعالسازی انتخاب کنید:',
            'reply_markup' => ['inline_keyboard' => $keyboard]
        ]);
        return;
    }
    
    if (strpos($data, 'toggle_disable_inbound:') === 0) {
        $inboundTag = substr($data, strlen('toggle_disable_inbound:'));
    
        $stmt = $botConn->prepare("SELECT state, data, admin_id FROM user_states WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userStateResult = $stmt->get_result();
        $userState = $userStateResult->fetch_assoc();
        $stmt->close();
    
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
            $stmt = $botConn->prepare("UPDATE user_states SET data = ? WHERE user_id = ?");
            $stmt->bind_param("si", $newData, $userId);
            $stmt->execute();
            $stmt->close();
    
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
                ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $userState['admin_id']]
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
                'text' => 'عملیات نامعتبر.',
                'show_alert' => false
            ]);
            return;
        }
    }
    if ($data === 'confirm_disable_inbounds') {
        $stmt = $botConn->prepare("SELECT state, admin_id, data FROM user_states WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userStateResult = $stmt->get_result();
        $userState = $userStateResult->fetch_assoc();
        $stmt->close();
    
        if ($userState && $userState['state'] === 'disable_inbounds') {
            $adminId = $userState['admin_id'];
            $selectedInbounds = json_decode($userState['data'], true);
            if (!$selectedInbounds || empty($selectedInbounds)) {
                sendRequest('answerCallbackQuery', [
                    'callback_query_id' => $callbackId,
                    'text' => 'لطفاً حداقل یک اینباند را انتخاب کنید.',
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
                    'text' => 'ادمین یافت نشد.',
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
                    'text' => 'غیرفعالسازی شد.'
                ]);
            } else {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => 'خطا در اجرای عملیات.'
                ]);
            }
    
            $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
    
            $adminInfo = getAdminInfo($adminId);
            $adminInfo['adminId'] = $adminId;
            $infoText = getAdminInfoText($adminInfo);
    
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
            ]);
    
            return;
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => 'عملیات نامعتبر.',
                'show_alert' => false
            ]);
            return;
        }
    }
    
    if (strpos($data, 'confirm_inbounds:') === 0) {
        $adminId = intval(substr($data, strlen('confirm_inbounds:')));
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = '✅ اینباندها با موفقیت محدود شدند' . "\n" . getAdminInfoText($adminInfo);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
        return;
    }
    if ($data === 'back_to_admin_selection') {
        $adminsResult = $vpnConn->query("SELECT id, username FROM admins");
        $admins = [];
        while ($row = $adminsResult->fetch_assoc()) {
            $admins[] = ['text' => $row['username'], 'callback_data' => 'select_admin:' . $row['id']];
        }
    
        if (empty($admins)) {
            $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $chatId);
            $stmt->execute();
            $stmt->close();
    
            sendRequest('editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => '🛠️ هیچ ادمینی اضافه نشده است.'
            ]);
    
            return;
        }
    
        $keyboard = ['inline_keyboard' => array_chunk($admins, 2)];
        $keyboard['inline_keyboard'][] = [['text' => $backButton, 'callback_data' => 'back_to_main']];
        
        $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $stmt->close();
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '👇 ادمین مورد نظر خود را انتخاب کنید:',
            'reply_markup' => $keyboard
        ]);
        return;
    }
    

    if (strpos($data, 'select_admin:') === 0) {
        $adminId = intval(substr($data, strlen('select_admin:')));

        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);

            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
        $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $stmt->close();

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);

        return;
    }
    if ($data === 'confirm_enable_inbounds') {
        $stmt = $botConn->prepare("SELECT state, admin_id, data FROM user_states WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userStateResult = $stmt->get_result();
        $userState = $userStateResult->fetch_assoc();
        $stmt->close();
    
        if ($userState && $userState['state'] === 'enable_inbounds') {
            $adminId = $userState['admin_id'];
            $selectedInbounds = json_decode($userState['data'], true);
            if (!$selectedInbounds || empty($selectedInbounds)) {
                sendRequest('answerCallbackQuery', [
                    'callback_query_id' => $callbackId,
                    'text' => 'لطفاً حداقل یک اینباند را انتخاب کنید.',
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
                    'text' => 'ادمین یافت نشد.',
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
                    'text' => 'فعالسازی شد.'
                ]);
            } else {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => 'خطا در اجرای عملیات.'
                ]);
            }
    
            $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
    
            $adminInfo = getAdminInfo($adminId);
            $adminInfo['adminId'] = $adminId;
            $infoText = getAdminInfoText($adminInfo);
    
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
            ]);
    
            return;
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => 'عملیات نامعتبر.',
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
    
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
    
        $infoText = getAdminInfoText($adminInfo);

    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'شما در این بخش میتوانید ادمین خود را محدود به انجام یک عملیات در پنل کنید.:',
            'reply_markup' => getRestrictionsKeyboard($adminId, $adminInfo['preventUserDeletion'], $adminInfo['preventUserCreation'], $adminInfo['preventUserReset'], $adminInfo['preventRevokeSubscription'], $adminInfo['preventUnlimitedTraffic'])
        ]);
    }
    if (strpos($data, 'back_to_admin_management:') === 0) {
        $adminId = intval(substr($data, strlen('back_to_admin_management:')));

        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
        $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $chatId);
        $stmt->execute();
        $stmt->close();

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
        return;
    }

   if (strpos($data, 'set_traffic:') === 0) {
        $adminId = intval(substr($data, strlen('set_traffic:')));
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'set_traffic', ?, ?) ON DUPLICATE KEY UPDATE state = 'set_traffic', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $messageId, $adminId, $messageId);
        $stmt->execute();
        $stmt->close();
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '♾️ لطفا حجم مورد نظر خودرا بر واحد گیگابایت وارد نمایید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        return;
    }

    if (strpos($data, 'set_expiry:') === 0) {
        $adminId = intval(substr($data, strlen('set_expiry:')));
        
        $response = sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '⏳ لطفا زمان زمان مورد نظر خود را برواحد روز وارد نمایید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
    
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
    
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'set_expiry', ?, ?) ON DUPLICATE KEY UPDATE state = 'set_expiry', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $promptMessageId, $adminId, $promptMessageId);
        $stmt->execute();
        $stmt->close();
    
        return;
    }

    if (strpos($data, 'disable_users:') === 0) {
        $adminId = intval(substr($data, strlen('disable_users:')));

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '🚫 آیا از غیرفعال‌سازی کاربران این ادمین مطمئن هستید؟',
            'reply_markup' => getConfirmationKeyboard($adminId)
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

        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '🚫 کاربران غیرفعال شدند'
        ]);
        $infoText = getAdminInfoText($adminInfo);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $infoText,
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

        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo) {
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => '🛠️ ادمین مورد نظر یافت نشد.'
            ]);
            return;
        }
        $adminInfo['adminId'] = $adminId;

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '✅ کاربران فعال شدند'
                ]);
        $infoText = getAdminInfoText($adminInfo);

        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $infoText,
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);

        return;
    }

    
    if (strpos($data, 'limit_inbounds:') === 0) {
        $adminId = intval(substr($data, strlen('limit_inbounds:')));
        $adminInfo = getAdminInfo($adminId);
    
        if (!$adminInfo || !isset($adminInfo['username'])) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => 'خطا: اطلاعات ادمین یافت نشد.',
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
            ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
        ];
        $limitinboundstext = 'عملکرد این بخش بدینگونه‌ست که هر 1 ثانیه اینباند های انتخابی شما برای ادمین انتخابی غیرفعال میشود.' . "\n" . '⬇️ لطفا اینباند های مدنظرتان را انتخاب کنید سپس روی دکمه‌ی محدودسازی کلیک نمایید.';
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $limitinboundstext,
            'reply_markup' => ['inline_keyboard' => $keyboard]
        ]);
        return;
    }
    
    if (strpos($data, 'toggle_inbound:') === 0) {
        list(, $adminId, $inboundTag) = explode(':', $data);
    
        $adminInfo = getAdminInfo($adminId);
        if (!$adminInfo || !isset($adminInfo['username'])) {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => 'خطا: اطلاعات ادمین یافت نشد.',
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
            ['text' => $backButton, 'callback_data' => 'back_to_admin_management:' . $adminId]
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
            'text' => 'لطفاً یک پروتکل را برای افزودن انتخاب کنید:',
            'reply_markup' => getProtocolSelectionKeyboard($adminId, 'select_add_protocol')
        ]);
        return;
    }

    if (strpos($data, 'remove_protocol:') === 0) {
        $adminId = intval(substr($data, strlen('remove_protocol:')));
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'لطفاً یک پروتکل را برای حذف انتخاب کنید:',
            'reply_markup' => getProtocolSelectionKeyboard($adminId, 'select_remove_protocol')
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
                'text' => 'ادمین یافت نشد.',
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
                'text' => "✅ پروتکل $protocol با موفقیت اضافه شد.",
                'show_alert' => false
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => "❌ خطا در افزودن پروتکل $protocol.",
                'show_alert' => false
            ]);
        }
        $stmt->close();
    
        $vpnConn->query("SET foreign_key_checks = 1");
    
        $adminInfo = getAdminInfo($adminId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
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
                'text' => 'ادمین یافت نشد.',
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
                'text' => "✅ پروتکل $protocol با موفقیت حذف شد.",
                'show_alert' => false
            ]);
        } else {
            sendRequest('answerCallbackQuery', [
                'callback_query_id' => $callbackId,
                'text' => "❌ خطا در حذف پروتکل $protocol.",
                'show_alert' => false
            ]);
        }
        $stmt->close();
    
        $vpnConn->query("SET foreign_key_checks = 1");
    
        $adminInfo = getAdminInfo($adminId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $infoText,
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
    
        return;
    }
    
    if (strpos($data, 'add_data_limit:') === 0) {
        $adminId = intval(substr($data, strlen('add_data_limit:')));
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'لطفاً مقدار حجم مورد نظر خود را بر حسب گیگابایت وارد نمایید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'add_data_limit', ?, ?) ON DUPLICATE KEY UPDATE state = 'add_data_limit', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $promptMessageId, $adminId, $promptMessageId);
        $stmt->execute();
        $stmt->close();
        return;
    }
    
    if (strpos($data, 'subtract_data_limit:') === 0) {
        $adminId = intval(substr($data, strlen('subtract_data_limit:')));
        
    
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => 'لطفاً مقدار حجمی که می‌خواهید کم شود را بر حسب گیگابایت وارد نمایید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'subtract_data_limit', ?, ?) ON DUPLICATE KEY UPDATE state = 'subtract_data_limit', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $promptMessageId, $adminId, $promptMessageId);
                $stmt->execute();
        $stmt->close();
        return;
    }
    if (strpos($data, 'security:') === 0) {
        $adminId = intval(substr($data, strlen('security:')));
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '🔒 تنظیمات امنیتی:',
            'reply_markup' => getSecurityKeyboard($adminId)
        ]);
        return;
    }
    if (strpos($data, 'change_password:') === 0) {
        $adminId = intval(substr($data, strlen('change_password:')));
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id) VALUES (?, 'set_new_password', ?) ON DUPLICATE KEY UPDATE state = 'set_new_password', admin_id = ?");
        $stmt->bind_param("iii", $userId, $adminId, $adminId);
        $stmt->execute();
        $stmt->close();
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '🔑 لطفاً پسورد جدید را وارد کنید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'set_new_password', ?, ?) ON DUPLICATE KEY UPDATE state = 'set_new_password', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $messageId, $adminId, $messageId);
        $stmt->execute();
        $stmt->close();
        return;
    }
    if (strpos($data, 'change_sudo:') === 0) {
        $adminId = intval(substr($data, strlen('change_sudo:')));
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '🛡️ آیا میخواهید دسترسی سودو داشته باشد؟',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'بله', 'callback_data' => 'set_sudo_yes:' . $adminId],
                        ['text' => 'خیر', 'callback_data' => 'set_sudo_no:' . $adminId]
                    ],
                    [
                        ['text' => $backButton, 'callback_data' => 'security:' . $adminId]
                    ]
                ]
            ]
        ]);
        return;
    }
    if (strpos($data, 'set_sudo_yes:') === 0) {
        $adminId = intval(substr($data, strlen('set_sudo_yes:')));
        $vpnConn->query("UPDATE admins SET is_sudo = 1 WHERE id = '$adminId'");
        $adminInfo = getAdminInfo($adminId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '✅ دسترسی سودو فعال شد.',
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);
        return;
    }
    if (strpos($data, 'set_sudo_no:') === 0) {
        $adminId = intval(substr($data, strlen('set_sudo_no:')));
        $vpnConn->query("UPDATE admins SET is_sudo = 0 WHERE id = '$adminId'");
        $adminInfo = getAdminInfo($adminId);
        $adminInfo['adminId'] = $adminId;
        $infoText = getAdminInfoText($adminInfo);
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '❌ دسترسی سودو غیرفعال شد.',
        ]);
        sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => $infoText,
            'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
        ]);

        return;
    }
    if (strpos($data, 'change_telegram_id:') === 0) {
        $adminId = intval(substr($data, strlen('change_telegram_id:')));
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id) VALUES (?, 'set_new_telegram_id', ?) ON DUPLICATE KEY UPDATE state = 'set_new_telegram_id', admin_id = ?");
        $stmt->bind_param("iii", $userId, $adminId, $adminId);
        $stmt->execute();
        $stmt->close();
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '📱 لطفاً آیدی تلگرام جدید را وارد کنید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'set_new_telegram_id', ?, ?) ON DUPLICATE KEY UPDATE state = 'set_new_telegram_id', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $messageId, $adminId, $messageId);
        $stmt->execute();
        $stmt->close();

        return;
    }
    if (strpos($data, 'change_username:') === 0) {
        $adminId = intval(substr($data, strlen('change_username:')));
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id) VALUES (?, 'set_new_username', ?) ON DUPLICATE KEY UPDATE state = 'set_new_username', admin_id = ?");
        $stmt->bind_param("iii", $userId, $adminId, $adminId);
        $stmt->execute();
        $stmt->close();
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '👤 لطفاً نام کاربری جدید را وارد کنید:',
            'reply_markup' => getBackToAdminManagementKeyboard($adminId, $userId)
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stmt = $botConn->prepare("INSERT INTO user_states (user_id, state, admin_id, message_id) VALUES (?, 'set_new_username', ?, ?) ON DUPLICATE KEY UPDATE state = 'set_new_username', admin_id = ?, message_id = ?");
        $stmt->bind_param("iiiii", $userId, $adminId, $messageId, $adminId, $messageId);
        $stmt->execute();
        $stmt->close();
        return;
    }
    if ($data === 'add_admin') {
            

        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '➕ لطفاً یوزرنیم مد نظر خود را وارد کنید (فقط از حروف و اعداد انگلیسی استفاده کنید):',
            'reply_markup' => getbacktoadminselectbutton($userId)
        ]);
        if (isset($response['result']['message_id'])) {
            $promptMessageId = $response['result']['message_id'];
        } else {
            $promptMessageId = $messageId;
        }
        $stateset = 'waiting_for_username';
        setUserState($userId, $stateset, $messageId);

        return;
    }
    if ($data === 'generate_random_password') {
        $generatedPassword = generateRandomPassword(12);
        $hashedPassword = password_hash($generatedPassword, PASSWORD_BCRYPT);
        
        setTemporaryData($userId, 'new_admin_password', $hashedPassword);
        setTemporaryData($userId, 'new_admin_password_nothashed', $generatedPassword);

        
        
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => "🔒 پسورد تصادفی شما: `$generatedPassword`\n\n🛡️ آیا می‌خواهید این ادمین دسترسی سودو داشته باشد؟",
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'بله', 'callback_data' => 'sudo_yes'],
                        ['text' => 'خیر', 'callback_data' => 'sudo_no']
                    ],
                    [
                        ['text' => $backButton, 'callback_data' => 'back_to_admin_selection']
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
        setUserState($userId, $stateset, $messageId);
        return;
    }
    
    if ($data === 'sudo_yes') {
        setTemporaryData($userId, 'new_admin_sudo', 1);
        

        
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '📱 لطفاً آیدی عددی تلگرام را وارد کنید یا کلمه "Skip" را تایپ کنید:',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Skip', 'callback_data' => 'skip_telegram_id']
                    ],
                    [
                        ['text' => $backButton, 'callback_data' => 'back_to_admin_selection']
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
        setUserState($userId, $stateset, $messageId);
        return;
    }
    
    if ($data === 'sudo_no') {
        setTemporaryData($userId, 'new_admin_sudo', 0);
        
        
        sendRequest('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
             'text' => '📱 لطفاً آیدی عددی تلگرام را وارد کنید یا کلمه "Skip" را تایپ کنید:',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => 'Skip', 'callback_data' => 'skip_telegram_id']
                    ],
                    [
                        ['text' => $backButton, 'callback_data' => 'back_to_admin_selection']
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
        setUserState($userId, $stateset, $messageId);
        return;
    }
    if ($data === 'skip_telegram_id') {
        setTemporaryData($userId, 'new_admin_telegram_id', 0);
        
        createAdmin($userId, $chatId);
        return;
    }
    
    
    }



    function handleMessage($message) {
        global $botConn, $vpnConn, $mainMenuButton, $backButton;
    
        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');
        $userId = $message['from']['id'];
    
        $userRole = getUserRole($userId);
    
        if ($userRole === 'unauthorized') {
            file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . " - Unauthorized user: $userId\n", FILE_APPEND);
            sendRequest('sendMessage', ['chat_id' => $chatId, 'text' => '🚫 شما دسترسی ندارید']);
            exit;
        }
    
        $stmt = $botConn->prepare("SELECT state, admin_id, message_id FROM user_states WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userStateResult = $stmt->get_result();
        $userState = $userStateResult->fetch_assoc();
        $stmt->close();
    
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
                        'text' => "✅ $dataLimit گیگابایت حجم به کاربران اضافه شد."
                    ]);
    
                    $adminInfo = getAdminInfo($adminId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
                }
                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '❌ لطفاً یک عدد معتبر وارد کنید.'
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
                        'text' => "✅ $dataLimit گیگابایت حجم از کاربران کم شد."
                    ]);
    
                    $adminInfo = getAdminInfo($adminId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
    }
                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '❌ لطفاً یک عدد معتبر وارد کنید.'
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
                    $adminInfo = getAdminInfo($adminId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo);

                    sendRequest('deleteMessage', [
                        'chat_id' => $chatId,
                        'message_id' => $promptMessageId
                    ]);

                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "✅ تعداد کاربران مجاز برای ساخت به $userLimit تنظیم شد.",
                    ]);
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);

                    $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
                        
                    sendRequest('editMessageText', [
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '❌ لطفاً یک عدد معتبر وارد کنید.'
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
                            'text' => "✅ زمان به مقدار $days روز به انقضا اضافه شد."
                        ]);
                    } else {
                        sendRequest('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => "❌ خطا در اضافه کردن زمان: " . $vpnConn->error
                        ]);
                    }
    
                    $adminInfo = getAdminInfo($adminId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
    
                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '❌ لطفاً یک عدد معتبر وارد کنید.'
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
                            'text' => "✅ زمان به مقدار $days روز از انقضا کم شد."
                        ]);
                    } else {
                        sendRequest('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => "❌ خطا در کم کردن زمان: " . $vpnConn->error
                        ]);
                    }
    
                    $adminInfo = getAdminInfo($adminId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
    
                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '❌ لطفاً یک عدد معتبر وارد کنید.'
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
                        'text' => "✅ $traffic گیگابایت حجم جدید تنظیم شد."
                    ]);
            
                    $adminInfo = getAdminInfo($adminId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo);
            
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
            
                    $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
            
                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '❌ لطفاً یک عدد معتبر وارد کنید.'
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
                        'text' => "✅ تاریخ انقضا جدید برای $days روز تنظیم شد."
                    ]);
    
                    $adminInfo = getAdminInfo($adminId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
    
                    $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
    
                    return;
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '❌ لطفاً یک عدد معتبر وارد کنید.'
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
                'text' => '✅ پسورد با موفقیت تغییر یافت.'
            ]);
            $adminInfo = getAdminInfo($adminId);
            $adminInfo['adminId'] = $adminId;
            $infoText = getAdminInfoText($adminInfo);
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
            ]);
            $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
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
                    'text' => '✅ آیدی تلگرام با موفقیت تغییر یافت.'
                ]);
                $adminInfo = getAdminInfo($adminId);
                $adminInfo['adminId'] = $adminId;
                $infoText = getAdminInfoText($adminInfo);
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $infoText,
                    'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                ]);
                $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
            } else {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => '❌ لطفاً یک آیدی عددی معتبر وارد کنید.'
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
                'text' => '✅ نام کاربری با موفقیت تغییر یافت.'
            ]);
            $adminInfo = getAdminInfo($adminId);
            $adminInfo['adminId'] = $adminId;
            $infoText = getAdminInfoText($adminInfo);
            sendRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $infoText,
                'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
            ]);
            $stmt = $botConn->prepare("UPDATE user_states SET state = NULL WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
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
                        'text' => '❌ این یوزرنیم قبلاً استفاده شده است. لطفاً یوزرنیم دیگری وارد کنید:',
                        'reply_markup' => getbacktoadminselectbutton($userId)
                    ]);
                    if (isset($response['result']['message_id'])) {
                        $promptMessageId = $response['result']['message_id'];
                    } else {
                        $promptMessageId = $messageId;
                    }
                    $stateset = 'waiting_for_username';
                    setUserState($userId, $stateset, $messageId);
                    return;
                }
                $stmt->close();
                
                setTemporaryData($userId, 'new_admin_username', $username);
                
                setUserState($userId, 'waiting_for_password');
                $promptMessageId = $userState['message_id'];

                sendRequest('deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $promptMessageId
                ]);
    
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => '🔑 لطفاً پسورد مورد نظر خود را وارد کنید یا دکمه Generate Random را بزنید:',
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                ['text' => 'Generate Random', 'callback_data' => 'generate_random_password']
                            ],
                            [
                                ['text' => $backButton, 'callback_data' => 'back_to_admin_selection']
                            ]
                        ]
                    ]
                ]);
                if (isset($response['result']['message_id'])) {
                    $promptMessageId = $response['result']['message_id'];
                } else {
                    $promptMessageId = $messageId;
                }
                $stateset = 'waiting_for_password';
                setUserState($userId, $stateset, $messageId);
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
                    'text' => '❌ یوزرنیم نامعتبر است. لطفاً فقط از حروف و اعداد انگلیسی استفاده کنید:',
                    'reply_markup' => getbacktoadminselectbutton($userId)
                ]);
                if (isset($response['result']['message_id'])) {
                    $promptMessageId = $response['result']['message_id'];
                } else {
                    $promptMessageId = $messageId;
                }
                $stateset = 'waiting_for_username';
                setUserState($userId, $stateset, $messageId);
               
                return;
            }
        }
        
        if ($userState['state'] === 'waiting_for_password') {
            if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $text)) {
                $hashedPassword = password_hash($text, PASSWORD_BCRYPT);
                setTemporaryData($userId, 'new_admin_password', $hashedPassword);
                
                $promptMessageId = $userState['message_id'];

                sendRequest('deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $promptMessageId
                ]);
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => '🛡️ آیا می‌خواهید این ادمین دسترسی سودو داشته باشد؟',
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                ['text' => 'بله', 'callback_data' => 'sudo_yes'],
                                ['text' => 'خیر', 'callback_data' => 'sudo_no']
                            ],
                            [
                                ['text' => $backButton, 'callback_data' => 'back_to_admin_selection']
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
                setUserState($userId, $stateset, $messageId);
                return;
            } else {
                $adminId = $userState['admin_id'];
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => '❌ پسورد نامعتبر است. لطفاً پسوردی وارد کنید که شامل حروف بزرگ، کوچک، اعداد و نمادها باشد:',
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                ['text' => 'Generate Random', 'callback_data' => 'generate_random_password']
                            ],
                            [
                                ['text' => $backButton, 'callback_data' => 'back_to_admin_selection']
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
                setUserState($userId, $stateset, $messageId);
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
                
                setTemporaryData($userId, 'new_admin_telegram_id', $telegramId);
                
                createAdmin($userId, $chatId);
                return;
            } elseif (strtolower($text) === 'skip') {
                setTemporaryData($userId, 'new_admin_telegram_id', 0);
                
                createAdmin($userId, $chatId);
                return;
            } else {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => '❌ لطفاً یک آیدی عددی معتبر وارد کنید یا کلمه "Skip" را تایپ کنید:',
                    'reply_markup' => getbacktoadminselectbutton($userId)
                ]);
                return;
            }
        }
    
    
        

        if ($text === '/start') {
            if ($userRole === 'main_admin') {
                sendRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => '🏠 منوی اصلی',
                    'reply_markup' => getMainMenuKeyboard()
                ]);
            } elseif ($userRole === 'limited_admin') {
                $stmt = $vpnConn->prepare("SELECT id FROM admins WHERE telegram_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $admin = $result->fetch_assoc();
                    $adminId = $admin['id'];
                    $adminInfo = getAdminInfo($adminId);
                    $adminInfo['adminId'] = $adminId;
                    $infoText = getAdminInfoText($adminInfo);
    
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $infoText,
                        'reply_markup' => getAdminKeyboard($chatId, $adminId, $adminInfo['status'])
                    ]);
                } else {
                    sendRequest('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => '🛠️ ادمین مورد نظر یافت نشد.'
                    ]);
                }
            }
            return;
        }
    }
    

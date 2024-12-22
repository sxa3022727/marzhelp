<?php
// languages.php
return [
    'fa' => [ // persian
        'main_menu' => '🏠 منوی اصلی',
        'manage_admins' => '🧸 مدیریت ادمین‌ها',
        'add_admin' => '➕ افزودن ادمین',
        'no_admins' => '🛠️ هیچ ادمینی اضافه نشده است.',
        'error_unauthorized' => '🚫 شما دسترسی ندارید.',
        'select_admin_prompt' => '👇 ادمین مورد نظر خود را انتخاب کنید:',
        'back' => '🔙 بازگشت',
        'generate_random_password' => '🔄 تولید پسورد تصادفی',
        'confirm_disable_inbounds' => 'آیا مطمئن هستید که می‌خواهید اینباندها را غیرفعال کنید؟',
        'add_user_success' => '✅ کاربر جدید اضافه شد.',
        'add_user_failure' => '❌ خطا در افزودن کاربر.',
        'delete_users_confirmation' => '🚫 آیا از غیرفعال‌سازی کاربران این ادمین مطمئن هستید؟',
        'users_disabled' => '🚫 کاربران غیرفعال شدند',
        'users_enabled' => '✅ کاربران فعال شدند',
        'security_settings' => '🔒 تنظیمات امنیتی:',
        'enter_new_password' => '🔑 لطفاً پسورد جدید را وارد کنید:',
        'sudo_confirmation' => '🛡️ آیا می‌خواهید این ادمین دسترسی سودو داشته باشد؟',
        'sudo_enabled' => '✅ دسترسی سودو فعال شد.',
        'sudo_disabled' => '❌ دسترسی سودو غیرفعال شد.',
        'username_taken' => '❌ این یوزرنیم قبلاً استفاده شده است. لطفاً یوزرنیم دیگری وارد کنید:',
        'invalid_username' => '❌ یوزرنیم نامعتبر است. لطفاً فقط از حروف و اعداد انگلیسی استفاده کنید:',
        'data_limit_added' => '✅ {dataLimit} گیگابایت حجم به کاربران اضافه شد.',
        'data_limit_subtracted' => '✅ {dataLimit} گیگابایت حجم از کاربران کم شد.',
        'data_limit_set' => '✅ حجم کل به {traffic} گیگابایت تنظیم شد.',
        'expiry_set' => '✅ تاریخ انقضا جدید برای {days} روز تنظیم شد.',
        'password_changed' => '✅ پسورد با موفقیت تغییر یافت.',
        'telegram_id_changed' => '✅ آیدی تلگرام با موفقیت تغییر یافت.',
        'username_changed' => '✅ نام کاربری با موفقیت تغییر یافت.',
        'invalid_password' => '❌ پسورد نامعتبر است. لطفاً پسوردی وارد کنید که شامل حروف بزرگ، کوچک، اعداد و نمادها باشد:',
        'invalid_number' => '❌ لطفاً یک عدد معتبر وارد کنید.',
        'protocol_added' => '✅ پروتکل {protocol} با موفقیت اضافه شد.',
        'protocol_add_error' => '❌ خطا در افزودن پروتکل {protocol}.',
        'protocol_removed' => '✅ پروتکل {protocol} با موفقیت حذف شد.',
        'protocol_remove_error' => '❌ خطا در حذف پروتکل {protocol}.',
        'add_protocol_prompt' => '➕ لطفاً یک پروتکل را برای افزودن انتخاب کنید:',
        'remove_protocol_prompt' => '➖ لطفاً یک پروتکل را برای حذف انتخاب کنید:',
        'add_inbound_prompt' => '➕ لطفاً یک اینباند را برای افزودن انتخاب کنید:',
        'remove_inbound_prompt' => '➖ لطفاً یک اینباند را برای حذف انتخاب کنید:',
        'inbound_disabled' => '✅ اینباند غیرفعال شد.',
        'inbound_enabled' => '✅ اینباند فعال شد.',
        'confirm_disable_inbounds' => 'آیا از غیرفعال‌سازی اینباندها مطمئن هستید؟',
        'inbounds_limited_success' => '✅ اینباندها با موفقیت محدود شدند',
        'invalid_operation' => '❌ عملیات نامعتبر.',
        'add_admin_prompt' => '➕ لطفاً یوزرنیم مد نظر خود را وارد کنید (فقط از حروف و اعداد انگلیسی استفاده کنید):',
        'password_prompt' => '🔑 لطفاً پسورد مورد نظر خود را وارد کنید یا دکمه Generate Random را بزنید:',
        'telegram_id_prompt' => '📱 لطفاً آیدی عددی تلگرام را وارد کنید یا کلمه "skip" را تایپ کنید:',
        'username_prompt' => '👤 لطفاً نام کاربری جدید را وارد کنید:',
        'password_generated' => '🔒 پسورد تصادفی شما:',
        'admin_specifications_settings' => '⬇️ تنظیمات مربوط به مشخصات ادمین ⬇️',
        'admin_limitations_settings' => '⬇️ تنظیمات مربوط به محدودیت‌های ادمین ⬇️',
        'admin_users_settings' => '⬇️ تنظیمات مربوط به کاربران ⬇️',
        'main_menu_button' => '🧸 مدیریت ادمین‌ها',
        'set_traffic_button' => '♾️ حجم ادمین',
        'set_expiry_button' => '⏳ انقضا ادمین',
        'disable_users_button' => '🚫 غیرفعال‌سازی کاربران',
        'enable_users_button' => '✅ فعال‌سازی کاربران',
        'confirm_yes_button' => '👍 بله',
        'confirm_no_button' => '👎 خیر',
        'refresh_button' => '🔄 رفرش',
        'limit_inbounds_button' => '⛔️ جلوگیری از استفاده اینباند',
        'next_step_button' => '🔪 محدودسازی',
        'disable_inbounds_button' => '➖ حذف اینباند',
        'enable_inbounds_button' => '➕ افزودن اینباند',
        'add_time_button' => '➕ افزودن زمان',
        'subtract_time_button' => '➖ کم کردن زمان',
        'add_protocol_button' => '➕ افزودن پروتکل',
        'remove_protocol_button' => '➖ حذف پروتکل',
        'adddatalimitbutton' => '➕ افزودن حجم',
        'subtractdata_button' => '➖ کم کردن حجم',
        'GoToLimitsButton' => '🛡️ محدودیت‌ها',
        'preventUserCreationButton' => '🛡️ جلوگیری ساخت کاربر',
        'preventUserResetButton' => '🛡️ جلوگیری ریست حجم',
        'preventRevokeSubscriptionButton' => '🛡️ جلوگیری Revoke',
        'preventUserDeletionButton' => '🛡️ جلوگیری حذف کاربر',
        'preventUnlimitedTrafficButton' => '🛡️ جلوگیری حجم نامحدود',
        'securityButton' => '🔒 امنیت',
        'changePasswordButton' => '🔑 تغییر پسورد',
        'changeSudoButton' => '🛡️ تغییر دسترسی سودو',
        'changeTelegramIdButton' => '📱 تغییر ایدی تلگرام',
        'changeUsernameButton' => '👤 تغییر نام کاربری',
        'protocolsettingsbutton' => '⚙️ تنظیمات پروتکل',
        'invalid_input' => '❌ ورودی نامعتبر است.',
        'operation_successful' => '✅ عملیات با موفقیت انجام شد.',
        'operation_failed' => '❌ عملیات ناموفق بود.',
        'please_try_again' => 'لطفاً دوباره تلاش کنید.',
        'active_status' => '(فعال✅)',
        'inactive_status' => '(غیرفعال)',
        'unlimited' => '♾️',
        'username' => 'نام کاربری',
        'userid' => 'شناسه کاربر',
        'usedTraffic' => 'ترافیک استفاده شده',
        'totalTraffic' => 'حجم کل',
        'remainingTraffic' => 'حجم باقی‌مانده',
        'expiryDate' => 'تاریخ انقضا',
        'daysLeft' => 'روزهای باقی‌مانده',
        'status' => 'وضعیت',
        'userLimit' => 'محدودیت کاربران',
        'remainingUserLimit' => 'محدودیت کاربران باقی‌مانده',
        'days' => 'روز',
        'adminInfoText_userStatsHeader' => "👥 آمار کاربران:",
        'adminInfoText_totalUsers' => "👤 کل کاربران:",
        'adminInfoText_activeUsers' => "🟩 فعال:",
        'adminInfoText_inactiveUsers' => "🟥 غیرفعال:",
        'adminInfoText_onlineUsers' => "🟢 آنلاین:",
        'adminInfoText_userCreationLimit' => "تعداد مجاز به ساخت کاربر:",
        'createAdmin_error_insufficient_data' => '❌ اطلاعات کافی برای افزودن ادمین جدید وجود ندارد.',
        'createAdmin_success_added' => "✅ ادمین جدید اضافه شد:\n🧸 یوزرنیم: `%s`\n🔑 پسورد : `%s`\n📱 آیدی تلگرام: `%s`",
        'createAdmin_error_adding_failed' => "❌ خطا در افزودن ادمین جدید: %s",
        'createAdmin_traffic_gb' => 'گیگابایت',
        'callbackResponse_adminSettings' => 'این بخش مربوط به تنظیمات ادمین می‌باشد.',
        'callbackResponse_showDisplayOnlyUsers' => 'این بخش مربوط به کاربران شما می‌باشد. بطور مثال اگر افزودن حجم را بزنید و ۲ گیگ اضافه کنید، به تمام کاربران فعال و غیرفعال شما ۲ گیگ حجم افزوده می‌شود.',
        'callbackResponse_showDisplayOnlyLimit' => 'این بخش مربوط به محدودیت‌هایی که می‌توانید برای ادمین اعمال کنید می‌باشد.',
        'callbackResponse_adminNotFound' => '🛠️ ادمین مورد نظر یافت نشد.',
        'callbackResponse_protocolSettings' => 'شما در این بخش می‌توانید برای کاربران ادمین انتخاب شده یک اینباند یا یک پروتکل را فعال یا غیرفعال نمایید.',
        'callbackResponse_showRestrictions' => 'شما در این بخش می‌توانید ادمین خود را محدود به انجام یک عملیات در پنل کنید.',
        'createAdmin_maxUserLimit_prompt' => 'لطفاً حداکثر تعداد کاربرانی که ادمین مجاز به ایجاد آنها است را وارد کنید.',
        'reduceUserExpiryDays_prompt' => '➖ لطفاً مقدار روزهایی که قصددارید از زمان انقضای کاربران کم کنید را وارد نمایید:',
        'addUserExpiryDays_prompt' => '➕ لطفاً مقدار روزهایی که قصددارید به زمان انقضای کاربران اضافه کنید را وارد نمایید:',
        'selectinboundToDisable_prompt' => 'لطفاً یک اینباند را برای غیرفعالسازی انتخاب کنید:',
        'selectMinInbound_prompt' => 'لطفاً حداقل یک اینباند را انتخاب کنید.',
        'setTraffic_prompt' => '♾️ لطفا حجم مورد نظر خود را بر واحد گیگابایت وارد نمایید:',
        'setExpiryDays_prompt' => '⏳ لطفا زمان مورد نظر خود را بر واحد روز وارد نمایید:',
        'limitInbounds_info' => 'عملکرد این بخش بدینگونه‌ست که هر 1 ثانیه اینباند های انتخابی شما برای ادمین انتخابی غیرفعال میشود.' . "\n" . '⬇️ لطفا اینباند های مدنظرتان را انتخاب کنید سپس روی دکمه‌ی محدودسازی کلیک نمایید.',
        'reduceVolume_prompt' => 'لطفاً مقدار حجمی که می‌خواهید کم شود را بر حسب گیگابایت وارد نمایید:',
        'enterNewTelegramId_prompt' => '📱 لطفاً آیدی عددی تلگرام جدید را وارد کنید:',
        'setUserLimit_success' => "✅ تعداد کاربران مجاز برای ساخت به {userLimit} تنظیم شد.",
        'setExpiryDays_success' => "✅ زمان به مقدار {days} روز به انقضا اضافه شد.",
        'reduceExpiryDays_success' => "✅ زمان به مقدار {days} روز از انقضا کم شد.",
        'setNewTraffic_success' => "✅ {traffic} گیگابایت حجم جدید تنظیم شد.",
        'setNewExpiry_success' => "✅ تاریخ انقضا جدید برای {days} روز تنظیم شد.",
        'enterValidTelegramId_prompt' => '❌ لطفاً یک آیدی عددی معتبر وارد کنید یا کلمه "skip" را تایپ کنید:',
        'setuserlimitbutton' => '🧸 محدودیت ساخت کاربر',
        'users' => 'کاربر',
        'account_info' => '👤 حساب‌کاربری',
        'settings' => '⚙️ تنظیمات',
        'update_bot' => '🔄 آپدیت ربات',
        'settings_menu' => '⚙️ منوی تنظیمات',
        'update_in_progress' => '🔄 ربات در حال بروزرسانی است، لطفاً صبر کنید...',
        'update_success' => '✅ ربات با موفقیت بروزرسانی شد!',
        'update_failed' => '❌ بروزرسانی ربات با خطا مواجه شد. لطفاً برای جزئیات به لاگ‌ها مراجعه کنید.',
        'db_update_failed' => '❌ بروزرسانی دیتابیس با خطا مواجه شد. لطفاً برای جزئیات به لاگ‌ها مراجعه کنید.',
        'traffic_settings' => 'درصورت فعالسازی این بخش، اگر ادمین‌های شما یوزری را پاک کنند، حجم دیلیت شده آن یوزر ذخیره می‌شود.',
        'triggers_activated' => '✅ ذخیره حجم دیلیت شده فعال گردید.',
        'triggers_deactivated' => '❌ ذخیره حجم دیلیت شده غیرفعال گردید.',
        'activate' => '🟢 فعالسازی',
        'deactivate' => '🔴 غیرفعالسازی',
        'marzhelp_backup' => '🤖 بکاپ ربات',
        'marzban_backup' => '💾 بکاپ مرزبان',
        'backup_settings' => '⚙️ تنظیمات مربوط به بکاپ',
        'get_backup' => '⬇️ دریافت بکاپ',
        'restore_backup' => '♻️ ایمپورت بکاپ',
        'backup_failed' => '❗️ دریافت بکاپ ناموفق بود',
        'upload_sql_file' => '📂 لطفا فایل بکاپ خود را با فرمت sql ارسال نمایید',
        'marzhelp_backup_options' => '🤖 این بخش مربوط به بکاپ ربات می‌باشد',
        'marzban_backup_options' => '💾 این بخش مربوط به بکاپ دیتابیس مرزبان می‌باشد',
        'restore_success' => '✅ ایمپورت بکاپ موفق بود',
        'restore_failed' => '❌ ایمپورت بکاپ با خطا مواجه شد',
        'save_admin_traffic' => '💾 ذخیره حجم ادمین',
        'backup' => '🔄 بکاپ',
        'backup_not_found' => '❌ بکاپ پیدا نشد',
        'update_marzban' => '🔄 آپدیت مرزبان',
        'restart_marzban' => '🔄 ریستارت مرزبان',
        'marzban_update_success' => '✅ مرزبان با موفقیت آپدیت شد',
        'marzban_update_failed' => '❌ آپدیت مرزبان با شکست مواجه شد',
        'marzban_restart_success' => '✅ مرزبان با موفقیت ریستارت شد',
        'marzban_restart_failed' => '❌ ریستارت مرزبان با شکست مواجه شد',
        'change_template' => '🎨 تغییر تمپلیت',
        'template_applied' => '✅ تمپلیت با موفقیت اعمال شد.',
        'template_error' => '❌ خطایی در اعمال تمپلیت رخ داد.',
        'template_caption' => '📄 تمپلیت %d از %d',
        'prev' => '⬅️ قبلی',
        'next' => '➡️ بعدی',
        'apply_template' => '✅ اعمال تمپلیت',
        'back_to_settings' => '🔙 بازگشت به تنظیمات',
    ],
    
    'en' => [ // english
        'main_menu' => '🏠 Main Menu',
        'manage_admins' => '🧸 Manage Admins',
        'add_admin' => '➕ Add Admin',
        'no_admins' => '🛠️ No admins have been added.',
        'error_unauthorized' => '🚫 You do not have access.',
        'select_admin_prompt' => '👇 Please select your admin:',
        'back' => '🔙 Back',
        'generate_random_password' => '🔄 Generate Random',
        'confirm_disable_inbounds' => 'Are you sure you want to disable these inbounds?',
        'add_user_success' => '✅ New user added.',
        'add_user_failure' => '❌ Error adding user.',
        'delete_users_confirmation' => '🚫 Are you sure you want to disable users for this admin?',
        'users_disabled' => '🚫 Users have been disabled',
        'users_enabled' => '✅ Users have been enabled',
        'security_settings' => '🔒 Security Settings:',
        'enter_new_password' => '🔑 Please enter the new password:',
        'sudo_confirmation' => '🛡️ Do you want this admin to have sudo access?',
        'sudo_enabled' => '✅ Sudo access has been enabled.',
        'sudo_disabled' => '❌ Sudo access has been disabled.',
        'username_taken' => '❌ This username is already taken. Please enter another username:',
        'invalid_username' => '❌ Invalid username. Please use only English letters and numbers:',
        'data_limit_added' => '✅ {dataLimit} GB has been added to users.',
        'data_limit_subtracted' => '✅ {dataLimit} GB has been subtracted from users.',
        'data_limit_set' => '✅ Total data limit set to {traffic} GB.',
        'expiry_set' => '✅ New expiry date set for {days} days.',
        'password_changed' => '✅ Password has been successfully changed.',
        'telegram_id_changed' => '✅ Telegram ID has been successfully changed.',
        'username_changed' => '✅ Username has been successfully changed.',
        'invalid_password' => '❌ Invalid password. Please enter a password that includes uppercase, lowercase, numbers, and symbols:',
        'invalid_number' => '❌ Please enter a valid number.',
        'protocol_added' => '✅ Protocol {protocol} has been successfully added.',
        'protocol_add_error' => '❌ Error adding protocol {protocol}.',
        'protocol_removed' => '✅ Protocol {protocol} has been successfully removed.',
        'protocol_remove_error' => '❌ Error removing protocol {protocol}.',
        'add_protocol_prompt' => '➕ Please select a protocol to add:',
        'remove_protocol_prompt' => '➖ Please select a protocol to remove:',
        'add_inbound_prompt' => '➕ Please select an inbound to add:',
        'remove_inbound_prompt' => '➖ Please select an inbound to remove:',
        'inbound_disabled' => '✅ Inbound has been disabled.',
        'inbound_enabled' => '✅ Inbound has been enabled.',
        'select_protocol_to_add' => '➕ Please select a protocol to add:',
        'select_protocol_to_remove' => '➖ Please select a protocol to remove:',
        'confirm_disable_inbounds' => 'Are you sure you want to disable these inbounds?',
        'inbounds_limited_success' => '✅ Inbounds have been successfully limited.',
        'invalid_operation' => '❌ Invalid operation.',
        'add_admin_prompt' => '➕ Please enter your username (use only English letters and numbers):',
        'password_prompt' => '🔑 Please enter your password or click the Generate Random button:',
        'telegram_id_prompt' => '📱 Please enter the new Telegram ID or type "Skip":',
        'username_prompt' => '👤 Please enter the new username:',
        'password_generated' => '🔒 Your random password:',
        'admin_specifications_settings' => '⬇️ Admin Specifications Settings ⬇️',
        'admin_limitations_settings' => '⬇️ Admin Limitations Settings ⬇️',
        'admin_users_settings' => '⬇️ Admin Users Settings ⬇️',
        'main_menu_button' => '🧸 Manage Admins',
        'set_traffic_button' => '♾️ Admin Traffic',
        'set_expiry_button' => '⏳ Admin Expiry',
        'disable_users_button' => '🚫 Disable Users',
        'enable_users_button' => '✅ Enable Users',
        'confirm_yes_button' => '👍 Yes',
        'confirm_no_button' => '👎 No',
        'refresh_button' => '🔄 Refresh',
        'limit_inbounds_button' => '⛔️ Limit Inbounds',
        'next_step_button' => '🔪 Next Step',
        'disable_inbounds_button' => '➖ Remove Inbound',
        'enable_inbounds_button' => '➕ Add Inbound',
        'add_time_button' => '➕ Add Time',
        'subtract_time_button' => '➖ Subtract Time',
        'add_protocol_button' => '➕ Add Protocol',
        'remove_protocol_button' => '➖ Remove Protocol',
        'adddatalimitbutton' => '➕ Add Data Limit',
        'subtractdata_button' => '➖ Subtract Data',
        'GoToLimitsButton' => '🛡️ Go To Limits',
        'preventUserCreationButton' => '🛡️ Prevent User Creation',
        'preventUserResetButton' => '🛡️ Prevent User Reset',
        'preventRevokeSubscriptionButton' => '🛡️ Prevent Revoke',
        'preventUserDeletionButton' => '🛡️ Prevent User Deletion',
        'preventUnlimitedTrafficButton' => '🛡️ Prevent Unlimited Traffic',
        'securityButton' => '🔒 Security',
        'changePasswordButton' => '🔑 Change Password',
        'changeSudoButton' => '🛡️ Change Sudo Access',
        'changeTelegramIdButton' => '📱 Change Telegram ID',
        'changeUsernameButton' => '👤 Change Username',
        'protocolsettingsbutton' => '⚙️ Protocol Settings',
        'invalid_input' => '❌ Invalid input.',
        'operation_successful' => '✅ Operation was successful.',
        'operation_failed' => '❌ Operation failed.',
        'please_try_again' => 'Please try again.',
        'active_status' => '(Active✅)',
        'inactive_status' => '(Inactive)',
        'unlimited' => '♾️',
        'username' => 'UserName',
        'userid' => 'User ID',
        'usedTraffic' => 'Used Traffic',
        'totalTraffic' => 'Total Traffic',
        'remainingTraffic' => 'Remaining Traffic',
        'expiryDate' => 'Expiry Date',
        'daysLeft' => 'Days Left',
        'status' => 'Status',
        'userLimit' => 'User Limit',
        'remainingUserLimit' => 'Remaining User Limit',
        'days' => 'Days',
        'adminInfoText_userStatsHeader' => "👥 User Statistics:",
        'adminInfoText_totalUsers' => "👤Total Users:",
        'adminInfoText_activeUsers' => "🟩 Active:",
        'adminInfoText_inactiveUsers' => "🟥 Inactive:",
        'adminInfoText_onlineUsers' => "🟢 Online:",
        'adminInfoText_userCreationLimit' => "User Creation Limit:",
        'createAdmin_error_insufficient_data' => '❌ Insufficient data to add a new admin.',
        'createAdmin_success_added' => "✅ New admin added:\n🧸 Username: `%s`\n🔑 Password: `%s`\n📱 Telegram ID: `%s`",
        'createAdmin_error_adding_failed' => "❌ Error adding new admin: %s",
        'createAdmin_traffic_gb' => 'GB',
        'callbackResponse_adminSettings' => 'This section relates to admin settings.',
        'callbackResponse_showDisplayOnlyUsers' => 'This section relates to your users. For example, if you click on add traffic and add 2 GB, 2 GB will be added to all your active and inactive users.',
        'callbackResponse_showDisplayOnlyLimit' => 'This section relates to the restrictions you can apply to admins.',
        'callbackResponse_adminNotFound' => '🛠️ Admin not found.',
        'callbackResponse_protocolSettings' => 'In this section, you can enable or disable a inbound or protocol for the selected admin\'s users.',
        'callbackResponse_showRestrictions' => 'In this section, you can restrict your admin to perform certain operations in the panel.',
        'createAdmin_maxUserLimit_prompt' => 'Please enter the maximum number of users the admin is allowed to create.',
        'reduceUserExpiryDays_prompt' => '➖ Please enter the number of days you wish to reduce from the users\' expiry time:',
        'addUserExpiryDays_prompt' => '➕ Please enter the number of days you wish to add to the users\' expiry time:',
        'selectinboundToDisable_prompt' => 'Please select a inbound to disable:',
        'selectMinInbound_prompt' => 'Please select at least one inbound.',
        'setTraffic_prompt' => '♾️ Please enter your desired volume in gigabytes:',
        'setExpiryDays_prompt' => '⏳ Please enter your desired time in days:',
        'limitInbounds_info' => 'The function of this section is such that every second, the selected inbounds for your chosen admin will be disabled.' . "\n" . '⬇️ Please select the inbounds you want and then click the Limit button.',
        'reduceVolume_prompt' => 'Please enter the amount of volume you want to subtract in gigabytes:',
        'enterNewTelegramId_prompt' => '📱 Please enter the new numeric Telegram ID:',
        'setUserLimit_success' => "✅ The allowed number of users to create has been set to {userLimit}.",
        'setExpiryDays_success' => "✅ The expiration time has been increased by {days} days.",
        'reduceExpiryDays_success' => "✅ The expiration time has been reduced by {days} days.",
        'setNewTraffic_success' => "✅ The new traffic limit has been set to {traffic} GB.",
        'setNewExpiry_success' => "✅ The new expiry date has been set for {days} days.",
        'enterValidTelegramId_prompt' => '❌ Please enter a valid numeric ID or type "Skip":',
        'setuserlimitbutton' => '🧸 user creation limit',
        'users' => 'users',
        'account_info' => '👤 user account',
        'settings' => '⚙️ Settings',
        'update_bot' => '🔄 Update Bot',
        'settings_menu' => '⚙️ Settings Menu',
        'update_in_progress' => '🔄 Updating the bot, please wait...',
        'update_success' => '✅ Bot updated successfully!',
        'update_failed' => '❌ Failed to update the bot. Please check logs for details.',
        'db_update_failed' => '❌ Failed to update the database. Please check logs for details.',
        'traffic_settings' => 'By enabling this section, if your admins delete a user, the deleted user\'s traffic volume will be stored.',
        'triggers_activated' => '✅ Data deletion tracking activated.',
        'triggers_deactivated' => '❌ Data deletion tracking deactivated.',
        'activate' => '🟢 Activate',
        'deactivate' => '🔴 Deactivate',
        'marzhelp_backup' => '🤖 Bot Backup',
        'marzban_backup' => '💾 Marzban Backup',
        'backup_settings' => '⚙️ Backup Settings',
        'get_backup' => '⬇️ Get Backup',
        'restore_backup' => '♻️ Import Backup',
        'backup_failed' => '❗️ Backup failed',
        'upload_sql_file' => '📂 Please upload your backup file in sql format',
        'marzhelp_backup_options' => '🤖 This section is for bot backup',
        'marzban_backup_options' => '💾 This section is for Marzban database backup',
        'restore_success' => '✅ Backup imported successfully',
        'restore_failed' => '❌ Backup import failed',
        'save_admin_traffic' => '💾 Save Admin Traffic',
        'backup' => '🔄 Backup',
        'backup_not_found' => '❌ Backup not found',
        'update_marzban' => '🔄 Update Marzban',
        'restart_marzban' => '🔄 Restart Marzban',
        'marzban_update_success' => '✅ Marzban updated successfully',
        'marzban_update_failed' => '❌ Marzban update failed',
        'marzban_restart_success' => '✅ Marzban restarted successfully',
        'marzban_restart_failed' => '❌ Marzban restart failed',
        'change_template' => '🎨 Change Template',
        'template_applied' => '✅ Template applied successfully.',
        'template_error' => '❌ Failed to apply template.',
        'template_caption' => '📄 Template %d of %d',
        'prev' => '⬅️ Previous',
        'next' => '➡️ Next',
        'apply_template' => '✅ Apply Template',
        'back_to_settings' => '🔙 Back to Settings',
        ],
            'ru' => [ // russia
        'main_menu' => '🏠 Главное меню',
        'manage_admins' => '🧸 Управление админами',
        'add_admin' => '➕ Добавить админа',
        'no_admins' => '🛠️ Админов нет.',
        'error_unauthorized' => '🚫 У вас нет доступа.',
        'select_admin_prompt' => '👇 Пожалуйста, выберите желаемого админа:',
        'back' => '🔙 Назад',
        'generate_random_password' => '🔄 Генерировать случайный пароль',
        'confirm_disable_inbounds' => 'Вы уверены, что хотите отключить эти инбаунды?',
        'add_user_success' => '✅ Новый юзер добавлен.',
        'add_user_failure' => '❌ Ошибка при добавлении юзера.',
        'delete_users_confirmation' => '🚫 Вы уверены, что хотите отключить юзеров этого админа?',
        'users_disabled' => '🚫 Юзеры отключены',
        'users_enabled' => '✅ Юзеры включены',
        'security_settings' => '🔒 Настройки безопасности:',
        'enter_new_password' => '🔑 Пожалуйста, введите новый пароль:',
        'sudo_confirmation' => '🛡️ Вы хотите, чтобы у этого админа был доступ sudo?',
        'sudo_enabled' => '✅ Доступ sudo был включен.',
        'sudo_disabled' => '❌ Доступ sudo был отключен.',
        'username_taken' => '❌ Этот юзернейм уже занят. Пожалуйста, введите другой:',
        'invalid_username' => '❌ Такой юзернейм нельзя использовать. Пожалуйста, используйте только английские буквы и цифры:',
        'data_limit_added' => '✅ {dataLimit} ГБ добавлено юзерам.',
        'data_limit_subtracted' => '✅ {dataLimit} ГБ удалено у юзеров.',
        'data_limit_set' => '✅ Общий лимит данных установлен на {traffic} ГБ.',
        'expiry_set' => '✅ Новая дата истечения установлена на {days} дней.',
        'password_changed' => '✅ Пароль успешно изменен.',
        'telegram_id_changed' => '✅ Telegram ID успешно изменен.',
        'username_changed' => '✅ Имя юзера успешно изменено.',
        'invalid_password' => '❌ Неверный пароль. Пожалуйста, введите пароль, который включает заглавные и строчные буквы, цифры и символы:',
        'invalid_number' => '❌ Пожалуйста, введите действительный номер.',
        'protocol_added' => '✅ Протокол {protocol} успешно добавлен.',
        'protocol_add_error' => '❌ Ошибка при добавлении протокола {protocol}.',
        'protocol_removed' => '✅ Протокол {protocol} успешно удален.',
        'protocol_remove_error' => '❌ Ошибка при удалении протокола {protocol}.',
        'add_protocol_prompt' => '➕ Пожалуйста, выберите протокол для добавления:',
        'remove_protocol_prompt' => '➖ Пожалуйста, выберите протокол для удаления:',
        'add_inbound_prompt' => '➕ Пожалуйста, выберите инбаунд для добавления:',
        'remove_inbound_prompt' => '➖ Пожалуйста, выберите инбаунд для удаления:',
        'inbound_disabled' => '✅ Инбаунд отключен.',
        'inbound_enabled' => '✅ Инбаунд включен.',
        'select_protocol_to_add' => '➕ Пожалуйста, выберите протокол для добавления:',
        'select_protocol_to_remove' => '➖ Пожалуйста, выберите протокол для удаления:',
        'confirm_disable_inbounds' => 'Вы уверены, что хотите отключить эти инбаунды?',
        'inbounds_limited_success' => '✅ Инбаунды успешно ограничены.',
        'invalid_operation' => '❌ Неверная операция.',
        'add_admin_prompt' => '➕ Пожалуйста, введите желаемое имя юзера (используйте только английские буквы и цифры):',
        'password_prompt' => '🔑 Пожалуйста, введите желаемый пароль или нажмите кнопку "Сгенерировать случайный":',
        'telegram_id_prompt' => '📱 Пожалуйста, введите новый Telegram ID или введите "Skip":',
        'username_prompt' => '👤 Пожалуйста, введите новое имя юзера:',
        'password_generated' => '🔒 Ваш случайный пароль:',
        'admin_specifications_settings' => '⬇️ Настройки характеристик админа ⬇️',
        'admin_limitations_settings' => '⬇️ Настройки ограничений админа ⬇️',
        'admin_users_settings' => '⬇️ Настройки пользователей админа ⬇️',
        'main_menu_button' => '🧸 Управление админами',
        'set_traffic_button' => '♾️ Трафик админа',
        'set_expiry_button' => '⏳ Срок действия админа',
        'disable_users_button' => '🚫 Отключить пользователей',
        'enable_users_button' => '✅ Включить пользователей',
        'confirm_yes_button' => '👍 Да',
        'confirm_no_button' => '👎 Нет',
        'refresh_button' => '🔄 Обновить',
        'limit_inbounds_button' => '⛔️ Ограничение инбаундов',
        'next_step_button' => '🔪 Следующий шаг',
        'disable_inbounds_button' => '➖ Удалить инбаунд',
        'enable_inbounds_button' => '➕ Добавить инбаунд',
        'add_time_button' => '➕ Добавить время',
        'subtract_time_button' => '➖ Уменьшить время',
        'add_protocol_button' => '➕ Добавить протокол',
        'remove_protocol_button' => '➖ Удалить протокол',
        'adddatalimitbutton' => '➕ Добавить трафик',
        'subtractdata_button' => '➖ Уменьшить трафик',
        'GoToLimitsButton' => '🛡️ Перейти к ограничениям',
        'preventUserCreationButton' => '🛡️ Предотвратить создание юзера',
        'preventUserResetButton' => '🛡️ Предотвратить сброс объема',
        'preventRevokeSubscriptionButton' => '🛡️ Предотвратить отзыв подписки',
        'preventUserDeletionButton' => '🛡️ Предотвратить удаление юзера',
        'preventUnlimitedTrafficButton' => '🛡️ Предотвратить неограниченный трафик',
        'securityButton' => '🔒 Безопасность',
        'changePasswordButton' => '🔑 Изменить пароль',
        'changeSudoButton' => '🛡️ Изменить доступ sudo',
        'changeTelegramIdButton' => '📱 Изменить Telegram ID',
        'changeUsernameButton' => '👤 Изменить имя юзера',
        'protocolsettingsbutton' => '⚙️ Настройки протокола',
        'invalid_input' => '❌ Неверный ввод.',
        'operation_successful' => '✅ Операция выполнена успешно.',
        'operation_failed' => '❌ Операция не удалась.',
        'please_try_again' => 'Пожалуйста, попробуйте снова.',
        'active_status' => '(Активен✅)',
        'inactive_status' => '(Неактивен)',
        'unlimited' => '♾️',
        'username' => 'Имя юзера',
        'userid' => 'ID Юзера',
        'usedTraffic' => 'Использованный трафик',
        'totalTraffic' => 'Общий трафик',
        'remainingTraffic' => 'Оставшийся трафик',
        'expiryDate' => 'Дата истечения',
        'daysLeft' => 'Дней осталось',
        'status' => 'Статус',
        'userLimit' => 'Лимит пользователей',
        'remainingUserLimit' => 'Оставшийся лимит пользователей',
        'days' => 'Дней',
        'adminInfoText_userStatsHeader' => "👥 Статистика пользователей:",
        'adminInfoText_totalUsers' => "👤 Всего пользователей:",
        'adminInfoText_activeUsers' => "🟩 Активные:",
        'adminInfoText_inactiveUsers' => "🟥 Неактивные:",
        'adminInfoText_onlineUsers' => "🟢 Онлайн:",
        'adminInfoText_userCreationLimit' => "Лимит создания пользователей:",
        'createAdmin_error_insufficient_data' => '❌ Недостаточно данных для добавления нового админа.',
        'createAdmin_success_added' => "✅ Новый администратор добавлен:\n🧸 Юзернейм: `%s`\n🔑 Пароль: `%s`\n📱 Telegram ID: `%s`",
        'createAdmin_error_adding_failed' => "❌ Ошибка при добавлении нового админа: %s",
        'createAdmin_traffic_gb' => 'ГБ',
        'callbackResponse_adminSettings' => 'Этот раздел относится к настройкам админа.',
        'callbackResponse_showDisplayOnlyUsers' => 'Этот раздел относится к вашим юзерам. Например, если вы нажмете «Добавить трафик» и добавите 2 ГБ, всем вашим активным и неактивным юзерам будет добавлено 2 ГБ трафика.',
        'callbackResponse_showDisplayOnlyLimit' => 'Этот раздел относится к ограничениям, которые вы можете применять к админам.',
        'callbackResponse_adminNotFound' => '🛠️ Администратор не найден.',
        'callbackResponse_protocolSettings' => 'В этом разделе вы можете включить или отключить входящий или протокол для пользователей выбранного админа.',        'callbackResponse_showRestrictions' => 'В этом разделе вы можете ограничить вашего админа для выполнения определённых операций в панели.',
        'createAdmin_maxUserLimit_prompt' => 'Пожалуйста, введите максимальное количество пользователей, которых администратор может создать.',
        'reduceUserExpiryDays_prompt' => '➖ Пожалуйста, введите количество дней, на которые вы хотите уменьшить срок действия пользователей:',
        'addUserExpiryDays_prompt' => '➕ Пожалуйста, введите количество дней, которые вы хотите добавить к сроку действия пользователей:',
        'selectBindToDisable_prompt' => 'Пожалуйста, выберите входящий трафик для отключения:',
        'selectMinInbound_prompt' => 'Пожалуйста, выберите как минимум один инбаунд.',
        'setTraffic_prompt' => '♾️ Пожалуйста, введите желаемый трафик в гигабайтах:',
        'setExpiryDays_prompt' => '⏳ Пожалуйста, введите желаемое время в днях:',
        'limitInbounds_info' => 'Функция этого раздела заключается в том, что каждую секунду выбранные вами инбаунды для выбранного админа будут отключаться.' . "\n" . '⬇️ Пожалуйста, выберите желаемые инбаунды и нажмите кнопку Ограничить.',
        'reduceVolume_prompt' => 'Пожалуйста, введите трафик, который вы хотите уменьшить, в гигабайтах:',
        'enterNewTelegramId_prompt' => '📱 Пожалуйста, введите новый Telegram ID (числом):',
        'setUserLimit_success' => "✅ Максимальное количество пользователей для создания установлено на {userLimit}.",
        'setExpiryDays_success' => "✅ Время истечения увеличено на {days} дней.",
        'reduceExpiryDays_success' => "✅ Срок действия уменьшен на {days} дней.",
        'setNewTraffic_success' => "✅ Новый лимит трафика установлен на {traffic} ГБ.",
        'setNewExpiry_success' => "✅ Новая дата истечения установлена на {days} дней.",
        'enterValidTelegramId_prompt' => '❌ Пожалуйста, введите действительный ID числом или введите "Пропустить":',
        'setuserlimitbutton' => '🧸 лимит на создание пользователей',
        'users' => 'пользователи',
        'account_info' => '👤 Учетная запись юзера',
        'settings' => '⚙️ Настройки',
        'update_bot' => '🔄 Обновить бота',
        'settings_menu' => '⚙️ Меню настроек',
        'update_in_progress' => '🔄 Обновление бота, пожалуйста, подождите...',
        'update_success' => '✅ Бот успешно обновлен!',
        'update_failed' => '❌ Ошибка обновления бота. Пожалуйста, проверьте логи для подробностей.',
        'db_update_failed' => '❌ Ошибка обновления базы данных. Пожалуйста, проверьте логи для подробностей.',
        'traffic_settings' => 'При активации этой функции, если ваши администраторы удаляют пользователя, объем удаленного трафика пользователя будет сохранен.',
        'triggers_activated' => '✅ Отслеживание удаления данных активировано.',
        'triggers_deactivated' => '❌ Отслеживание удаления данных деактивировано.',
        'activate' => '🟢 Активировать',
        'deactivate' => '🔴 Деактивировать',
        'marzhelp_backup' => '🤖 Резервное копирование бота',
        'marzban_backup' => '💾 Резервное копирование Марзбан',
        'backup_settings' => '⚙️ Настройки резервного копирования',
        'get_backup' => '⬇️ Получить резервную копию',
        'restore_backup' => '♻️ Импортировать резервную копию',
        'backup_failed' => '❗️ Не удалось создать резервную копию',
        'upload_sql_file' => '📂 Пожалуйста, загрузите файл резервной копии в формате sql',
        'marzhelp_backup_options' => '🤖 Этот раздел для резервного копирования бота',
        'marzban_backup_options' => '💾 Этот раздел для резервного копирования базы данных Марзбан',
        'restore_success' => '✅ Резервная копия успешно импортирована',
        'restore_failed' => '❌ Ошибка импорта резервной копии',
        'save_admin_traffic' => '💾 Сохранить трафик администратора',
        'backup' => '🔄 Резервное копирование',
        'backup_not_found' => '❌ Резервная копия не найдена',
        'update_marzban' => '🔄 Обновить Марзбан',
        'restart_marzban' => '🔄 Перезагрузить Марзбан',
        'marzban_update_success' => '✅ Марзбан успешно обновлен',
        'marzban_update_failed' => '❌ Ошибка обновления Марзбан',
        'marzban_restart_success' => '✅ Марзбан успешно перезагружен',
        'marzban_restart_failed' => '❌ Ошибка перезагрузки Марзбан',
        'change_template' => '🎨 Изменить шаблон',
        'template_applied' => '✅ Шаблон успешно применен.',
        'template_error' => '❌ Не удалось применить шаблон.',
        'template_caption' => '📄 Шаблон %d из %d',
        'prev' => '⬅️ Предыдущий',
        'next' => '➡️ Следующий',
        'apply_template' => '✅ Применить шаблон',
        'back_to_settings' => '🔙 Назад к настройкам',
    ]
];
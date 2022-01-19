<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = new \Symfony\Component\Dotenv\Dotenv();
$dotenv->load(__DIR__ . '/.env');

chdir(__DIR__);
set_time_limit(60);

if (!$_ENV['ENABLED']) {
    return;
}

date_default_timezone_set($_ENV['TIMEZONE']);

$now = new DateTimeImmutable();

$currentDate = $now->format('Y-m-d');
$currentTime = $now->format('H:i');
$workStartTime = $now->setTime(9, 0);
$workEndTime = $now->setTime(18, 0);
$workEndTimeForPreHoliday = $now->setTime(17, 0);
$lunchStartTime = $now->setTime(13, 0);
$lunchEndTime = $now->setTime(14, 0);

try {
    $calendarJson = file_get_contents($_ENV['HOLIDAYS_CALENDAR_URL']);
    $calendarData = json_decode($calendarJson, true);

    $isHoliday = in_array($currentDate, $calendarData['holidays']);
    $isPreHoliday = in_array($currentDate, $calendarData['preholidays']);
} catch (\Throwable $e) {
    sendError($e->getMessage());
}

if ($isHoliday) {
    return;
}

if ($isPreHoliday) {
    $workEndTime = $workEndTimeForPreHoliday;
}

$message = '';
$allowedExecutionInterval = new DateInterval($_ENV['ALLOWED_EXECUTION_INTERVAL']);

if ($now->getTimestamp() >= $workStartTime->getTimestamp() && $now->getTimestamp() < $workStartTime->add($allowedExecutionInterval)->getTimestamp()) {
    $message = 'L w ' . $workStartTime->format('H:i');
}

if ($now->getTimestamp() >= $lunchEndTime->getTimestamp() && $now->getTimestamp() < $lunchEndTime->add($allowedExecutionInterval)->getTimestamp()) {
    $message = 'L w ' . $lunchEndTime->format('H:i');
}

if ($now->getTimestamp() >= $lunchStartTime->getTimestamp() && $now->getTimestamp() < $lunchStartTime->add($allowedExecutionInterval)->getTimestamp()) {
    $message = 'L b ' . $lunchStartTime->format('H:i');
}

if ($now->getTimestamp() >= $workEndTime->getTimestamp() && $now->getTimestamp() < $workEndTime->add($allowedExecutionInterval)->getTimestamp()) {
    $message = 'L h ' . $workEndTime->format('H:i');
}

try {
    if ($message) {
        $settings = [
            'app_info' => [ // Эти данные мы получили после регистрации приложения на https://my.telegram.org
                'api_id' => $_ENV['API_ID'],
                'api_hash' => $_ENV['HASH_ID'],
            ],
        ];

        $MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);
        $MadelineProto->async(false);
        $MadelineProto->start();
        $MadelineProto->messages->sendMessage(['peer' => $_ENV['TELEGRAM_PEER'], 'message' => $message]);
    }
} catch (\Throwable $e) {
    sendError($e->getMessage());
}

function sendError(string $error): void {
    $mail = new \PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'];
    $mail->Password = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = 'SSL';
    $mail->Port = '465';
    $mail->CharSet = 'UTF-8';
    $mail->From = $_ENV['MAIL_USERNAME'];
    $mail->addAddress($_ENV['MAIL_USERNAME']);
    $mail->Subject = $_ENV['MAIL_SUBJECT'];
    $mail->Body = 'Ошибка: ' . $error;

    if (!$mail->send()) {
        echo 'Письмо не может быть отправлено. ';
        echo 'Ошибка: ' . $mail->ErrorInfo;
    }
}

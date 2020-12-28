<?php
$SECRET_KEY = "123456"; // Secret key, lấy ở thông tin API trên RezPay

// Xác nhận signature
$signature = $_SERVER["REZPAY_SIGNATURE"]; // Signature server gửi theo trong header RezPay-Signature
$algoFromHeader = $_SERVER["REZPAY_SIGNATURE_ALGO"]; // Thuật toán HMAC sử dụng, không nên sử dụng trường này để truyền vào HMAC
$algo = "SHA256"; // Hiện tại mặc định là SHA256

$rawPayload = file_get_contents('php://input'); // Lấy payload
$mySignature = hash_hmac($algo, $rawPayload, $SECRET_KEY); // Sinh signature với SECRET KEY

if ($mySignature != $signature) {
    http_response_code(403);
    die('Signature không hợp lệ.');
}

// Handle nội dung
// VD: nội dung REZxxxxxxxxx thì sẽ nạp tiền cho user xxxxxxxxx
$payload = json_decode($rawPayload); // Decode json
if (transactionExists($payload->transaction_id)) { // Kiểm tra nếu giao dịch đã được ghi nhận thì reject callback
    http_response_code(400);
    die('Transaction đã được ghi nhận');
}
// Lấy user ID trong description
// Nên dùng regex vì những service khác nhau có format description khác nhau và tránh người dùng thêm các ký tự không hợp lệ
if (!preg_match('/rez([0-9]+)/i', $payload->description, $matches)) {
    http_response_code(400);
    die('Transaction không hợp lệ');
}
$userId = $matches[1];
$user = getUserByUserId($userId); // Lấy user bằng id

$amount = $payload->amount; // Lấy số tiền
$user->addBalance($amount, $payload->transaction_id); // Ghi nhận giao dịch

http_response_code(200); // Cho RezPay server biết là đã xử lý thành công để không Re-callback
echo 'Giao dịch thành công';

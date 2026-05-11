<?php
/**
 * Email Configuration for LittleLands
 * Supports both Mailjet and Gmail SMTP
 */

// Configuration logic
function getEmailConfig()
{
    return [
        'mailjet' => [
            'api_key' => 'f00773edb6a05fc5eb899227a4fa61f0',
            'api_secret' => '8cb1cf91893d7e85e98911674046470f',
            'sender_email' => 'carriemaejmn@gmail.com',
            'sender_name' => 'LittleLands'
        ]
    ];
}

/**
 * Send OTP using available email method (Mailjet preferred)
 * @param string $to Recipient email
 * @param string $otp OTP code
 * @return bool Success status
 */
function sendOTPEmail($to, $otp)
{
    $config = getEmailConfig();

    // 1. Try Mailjet (Primary)
    if (sendViaMailjet($to, $otp, $config['mailjet'])) {
        return true;
    }

    error_log("Mailjet failed for OTP to $to");
    return false;
}

/**
 * Send email via Mailjet API
 */
function sendViaMailjet($to, $otp, $config)
{
    $subject = 'LittleLands - Password Reset Code';
    $messageText = "Hello,\n\nYou requested a password reset for your LittleLands account.\nYour verification code is: {$otp}\nThis code will expire in 15 minutes.\n\nBest regards,\nLittleLands Team";

    // HTML Template
    $messageHtml = "
    <div style='font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc;'>
        <div style='background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 40px 20px; text-align: center; border-radius: 20px 20px 0 0;'>
            <div style='display: inline-block; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 12px; margin-bottom: 15px;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 32px; font-weight: 800; letter-spacing: -0.5px;'>LittleLands</h1>
            </div>
            <p style='color: #ffffff; margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; opacity: 0.9;'>Playground Booking System</p>
        </div>
        <div style='background-color: #ffffff; padding: 40px; border-radius: 0 0 20px 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center;'>
            <h2 style='color: #1e293b; margin-top: 0; font-size: 24px; font-weight: 700;'>Password Reset Verification</h2>
            <p style='color: #64748b; font-size: 16px; line-height: 1.6;'>Hello,</p>
            <p style='color: #64748b; font-size: 16px; line-height: 1.6;'>You requested a password reset for your LittleLands account. Use the verification code below to continue:</p>
            
            <div style='background-color: #f1f5f9; border: 2px dashed #4facfe; padding: 25px; margin: 30px 0; border-radius: 16px;'>
                <div style='font-size: 42px; font-weight: 800; color: #4facfe; letter-spacing: 12px; margin-left: 12px;'>
                    {$otp}
                </div>
            </div>
            
            <p style='color: #94a3b8; font-size: 14px;'>This code is valid for <b>15 minutes</b>. For security, please do not share this code with anyone.</p>
            
            <div style='margin-top: 40px; padding-top: 25px; border-top: 1px solid #f1f5f9;'>
                <p style='color: #94a3b8; font-size: 12px; line-height: 1.5;'>
                    If you didn't request this code, you can safely ignore this email. Someone may have entered your ID by mistake.
                </p>
            </div>
        </div>
        <div style='text-align: center; margin-top: 30px; padding-bottom: 20px;'>
            <p style='color: #cbd5e1; font-size: 12px;'>&copy; " . date('Y') . " LittleLands &bull; Playground Booking System</p>
        </div>
    </div>
    ";

    $data = [
        'Messages' => [
            [
                'From' => [
                    'Email' => $config['sender_email'],
                    'Name' => $config['sender_name']
                ],
                'To' => [
                    [
                        'Email' => $to
                    ]
                ],
                'Subject' => $subject,
                'TextPart' => $messageText,
                'HTMLPart' => $messageHtml
            ]
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mailjet.com/v3.1/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 seconds connection timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds total timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($config['api_key'] . ':' . $config['api_secret'])
    ]);

    // For local development on XAMPP (Windows), disable SSL verification to avoid certificate errors
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);

    if ($curl_errno) {
        error_log("Mailjet cURL Error ($curl_errno): $curl_error");
        return false; // Fail immediately if connection issue, or fall through to bad http code handling
    }

    if ($http_code === 200 || $http_code === 201) {
        return true;
    } else {
        error_log("Mailjet API Error: HTTP $http_code. Response: $response. Curl Error: $curl_error");
        return false;
    }
}


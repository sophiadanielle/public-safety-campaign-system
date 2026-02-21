<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private array $config;
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../Config/mail_config.php';
        $this->initializeMailer();
    }

    private function initializeMailer(): void
    {
        $this->mailer = new PHPMailer(true);
        
        $this->mailer->isSMTP();
        $this->mailer->Host = $this->config['smtp_host'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $this->config['smtp_username'];
        $this->mailer->Password = $this->config['smtp_password'];
        $this->mailer->SMTPSecure = $this->config['smtp_encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port = $this->config['smtp_port'];
        $this->mailer->CharSet = 'UTF-8';
        
        $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
        $this->mailer->addReplyTo($this->config['reply_to_email'], $this->config['reply_to_name']);
    }

    public function sendOTP(string $email, string $fullname, string $otpCode): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $fullname);
            
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Your Login Verification Code - Alertara QC';
            $this->mailer->Body = $this->getOTPEmailTemplate($fullname, $otpCode);
            $this->mailer->AltBody = "Your verification code is: $otpCode. This code will expire in 5 minutes.";
            
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail sending failed: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    private function getOTPEmailTemplate(string $fullname, string $otpCode): string
    {
        $year = date('Y');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0fdfa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 100%; max-width: 500px; border-collapse: collapse; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px 40px 24px; text-align: center; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); border-radius: 16px 16px 0 0;">
                            <div style="display: inline-block; background: rgba(255, 255, 255, 0.2); padding: 12px 24px; border-radius: 12px; margin-bottom: 16px;">
                                <span style="font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">Alertara QC</span>
                            </div>
                            <h1 style="margin: 0; font-size: 22px; font-weight: 600; color: #ffffff;">Login Verification</h1>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px; font-size: 16px; color: #334155; line-height: 1.6;">
                                Hello <strong style="color: #0f172a;">{$fullname}</strong>,
                            </p>
                            <p style="margin: 0 0 32px; font-size: 16px; color: #334155; line-height: 1.6;">
                                You're attempting to sign in to your Alertara QC account. Please use the verification code below to complete your login:
                            </p>
                            
                            <!-- OTP Code Box -->
                            <div style="text-align: center; margin: 32px 0;">
                                <div style="display: inline-block; background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%); border: 2px solid #0d9488; border-radius: 12px; padding: 20px 40px;">
                                    <span style="font-size: 36px; font-weight: 800; color: #0d9488; letter-spacing: 8px; font-family: 'Courier New', monospace;">{$otpCode}</span>
                                </div>
                            </div>
                            
                            <p style="margin: 32px 0 16px; font-size: 14px; color: #64748b; line-height: 1.6; text-align: center;">
                                <strong style="color: #dc2626;">⏱ This code will expire in 5 minutes</strong>
                            </p>
                            
                            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; border-radius: 0 8px 8px 0; margin: 24px 0;">
                                <p style="margin: 0; font-size: 14px; color: #92400e; line-height: 1.5;">
                                    <strong>🔒 Security Notice:</strong> If you didn't request this code, please ignore this email. Someone may have entered your email address by mistake.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background: #f8fafc; border-radius: 0 0 16px 16px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 8px; font-size: 13px; color: #64748b; text-align: center;">
                                Barangay Public Safety Campaign Management System
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #94a3b8; text-align: center;">
                                © {$year} Alertara QC. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}

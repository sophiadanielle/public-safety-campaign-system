<?php
/**
 * PHPMailer Configuration
 * Email configuration for OTP and notifications
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class MailConfig
{
    private static ?PHPMailer $mailer = null;

    public static function getMailer(): PHPMailer
    {
        if (self::$mailer === null) {
            self::$mailer = new PHPMailer(true);
            self::configure(self::$mailer);
        }
        return self::$mailer;
    }

    private static function configure(PHPMailer $mail): void
    {
        // Load environment variables
        $envPath = __DIR__ . '/../../.env';
        $smtpHost = 'smtp.gmail.com';
        $smtpPort = 587;
        $smtpUser = '';
        $smtpPass = '';
        $smtpFromEmail = '';
        $smtpFromName = 'Alertara QC';

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                
                $pos = strpos($line, '=');
                $key = trim(substr($line, 0, $pos));
                $val = trim(substr($line, $pos + 1));
                
                // Remove quotes if present
                if (strlen($val) >= 2 && (($val[0] === '"' && substr($val, -1) === '"') || ($val[0] === "'" && substr($val, -1) === "'"))) {
                    $val = substr($val, 1, -1);
                }

                switch ($key) {
                    case 'SMTP_HOST':
                        $smtpHost = $val;
                        break;
                    case 'SMTP_PORT':
                        $smtpPort = (int) $val;
                        break;
                    case 'SMTP_USER':
                        $smtpUser = $val;
                        break;
                    case 'SMTP_PASS':
                        $smtpPass = $val;
                        break;
                    case 'SMTP_FROM_EMAIL':
                        $smtpFromEmail = $val;
                        break;
                    case 'SMTP_FROM_NAME':
                        $smtpFromName = $val;
                        break;
                }
            }
        }

        // If from email not set, use SMTP user
        if (empty($smtpFromEmail)) {
            $smtpFromEmail = $smtpUser;
        }

        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;

        // Default sender
        if (!empty($smtpFromEmail)) {
            $mail->setFrom($smtpFromEmail, $smtpFromName);
        }

        // Content settings
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
    }

    /**
     * Send OTP email
     */
    public static function sendOTPEmail(string $toEmail, string $toName, string $otp): bool
    {
        try {
            $mail = new PHPMailer(true);
            self::configure($mail);

            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Your Login Verification Code - Alertara QC';
            $mail->Body = self::getOTPEmailTemplate($otp, $toName);
            $mail->AltBody = "Your verification code is: $otp. This code expires in 5 minutes.";

            $mail->send();
            error_log("OTP email sent successfully to: $toEmail");
            return true;
        } catch (Exception $e) {
            error_log("Failed to send OTP email to $toEmail: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get OTP email HTML template
     */
    private static function getOTPEmailTemplate(string $otp, string $name): string
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
                <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); border-radius: 16px 16px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">Alertara QC</h1>
                            <p style="margin: 8px 0 0; color: rgba(255, 255, 255, 0.9); font-size: 14px;">Public Safety Campaign Management System</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 16px; color: #0f172a; font-size: 24px; font-weight: 600;">Verification Code</h2>
                            <p style="margin: 0 0 24px; color: #475569; font-size: 16px; line-height: 1.6;">
                                Hello <strong>{$name}</strong>,<br><br>
                                You're attempting to sign in to your Alertara QC account. Use the verification code below to complete your login:
                            </p>
                            
                            <!-- OTP Code Box -->
                            <div style="background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 100%); border: 2px solid #0d9488; border-radius: 12px; padding: 24px; text-align: center; margin: 24px 0;">
                                <p style="margin: 0 0 8px; color: #0f766e; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Your Verification Code</p>
                                <p style="margin: 0; color: #0d9488; font-size: 42px; font-weight: 800; letter-spacing: 8px; font-family: 'Courier New', monospace;">{$otp}</p>
                            </div>
                            
                            <p style="margin: 24px 0 0; color: #64748b; font-size: 14px; line-height: 1.6;">
                                <strong>⏱️ This code expires in 5 minutes.</strong><br><br>
                                If you didn't request this code, please ignore this email or contact support if you have concerns about your account security.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; background-color: #f8fafc; border-radius: 0 0 16px 16px; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; color: #94a3b8; font-size: 12px; text-align: center; line-height: 1.6;">
                                This is an automated message from Alertara QC.<br>
                                Please do not reply to this email.<br><br>
                                &copy; {$year} Barangay Public Safety Campaign Management System
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

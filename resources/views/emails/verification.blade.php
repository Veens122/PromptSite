<div
    style="max-width:600px; margin:0 auto; padding:20px; border:1px solid #eee; border-radius:8px; font-family: Arial, sans-serif;">
    <h2 style="text-align:center; color:#333;">Verify Your Email Address</h2>

    <p>Hello {{ $user->name }},</p>

    <p>Thank you for registering with <strong>{{ config('app.name') }}</strong>.</p>
    <p>Please verify your email by clicking the button below or using the verification code.</p>

    <div style="text-align:center; margin:30px 0;">
        <a href="{{ $verificationUrl }}"
            style="display:inline-block; background:#4CAF50; color:#fff; text-decoration:none; padding:12px 25px; border-radius:5px;">
            Verify Email Address
        </a>
    </div>

    <p style="text-align:center; font-size:18px;"><strong>Verification Code:</strong> {{ $verificationCode }}</p>

    <p>This code will expire in <strong>30 minutes</strong>.</p>

    <p>If you did not create an account, you can ignore this email.</p>

    <p>Thanks,<br>The {{ config('app.name') }} Team</p>
</div>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your Account Credentials</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f6f7fb; padding:20px;">
  <div style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:12px; padding:22px; border:1px solid #eee;">
    <h2 style="margin:0 0 10px;">Hello {{ $name }}, 👋</h2>

    <p style="margin:0 0 14px;">
      Your account has been created successfully. Below are your login credentials:
    </p>

    <div style="padding:14px; background:#fafafa; border:1px solid #eee; border-radius:10px;">
      <p style="margin:0 0 8px;"><strong>Role:</strong> {{ $role }}</p>
      <p style="margin:0 0 8px;"><strong>Username:</strong> {{ $username }}</p>
      <p style="margin:0;"><strong>Password:</strong> {{ $password }}</p>
    </div>

    <p style="margin:14px 0 0; color:#666;">
      Please change your password after login for security.
    </p>

    <p style="margin:18px 0 0;">
      Thanks,<br>
      <strong>Royal Crown Team</strong>
    </p>
  </div>
</body>
</html>
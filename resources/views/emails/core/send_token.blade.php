<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
</head>
<body>
    <p>Hello {{ $data["user"]->first_name }},</p>
    <p>Thank you for registering with our website. To complete your registration, please enter the following token:</p>
    <p><strong>{{ $data['token'] }}</strong></p>
    <p>If you didn't request this, please ignore this email.</p>
    <p>Regards,<br>Your Website Team</p>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Updated</title>
</head>
<body>
    <p>Hello {{ $data["admin"]->first_name }},</p>
    <p>Your password has been updated</p>
    <p><strong>password: {{ $data['password'] }}</strong></p>
    <p>to login visit admin.testev.com</p>
    <p>Regards,<br>Your Website Team</p>
</body>
</html>

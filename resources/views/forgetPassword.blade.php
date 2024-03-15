<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Alqanouni-Forget password</title>

    <style>
        .container {
            margin: auto;
            width: 90%;
        }
        .container h3 {
            font-size: 24px;
        }

        .container span{
            font-size: 20px;
            display: block;
        }

        .container p {
            background-color: rgb(228, 85, 85);
            font-size: 18px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h3>Verify Code ▼:</h3>
        <span>{{ $code }}</span>
        <p>If you don't request this email, Just Ignore It.</p>
    </div>
</body>
</html>

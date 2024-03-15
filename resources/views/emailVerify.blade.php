<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Alqanouni-Email verifying</title>

    <style>
        body {
            font-family: "Arial Rounded MT Bold";
        }
        .container {
            margin: auto;
            width: 90%;
        }
        .container h3 {
            font-size: 24px;
        }

        .container span{
            font-size: 18px;
            display: block;
            font-weight: bold;
            font-family: "Agency FB";
        }

        .container p {
            font-size: 20px;
        }
        .container .warning {
            width: 30%;
            background-color: rgb(228, 85, 85);
        }

        /*.container p {*/
        /*    background-color: rgb(228, 85, 85);*/
        /*    font-size: 18px;*/
        /*}*/
    </style>
</head>

<body>
<div class="container">
    <h2>We send this email to verify your email is real email</h2>
    <p>To complete your register write this code in the right field </p>
    <span>The code: {{ $code }}</span>
    <p class="warning">If you don't request this email, Just Ignore It.</p>
</div>
</body>
</html>

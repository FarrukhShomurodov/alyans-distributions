<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Доступ запрещён</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            height: 100vh;
            background: #0f1120;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui;
            color: #fff;
        }

        .block-box {
            background: #171a2e;
            padding: 28px 24px;
            border-radius: 18px;
            text-align: center;
            max-width: 320px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .4);
        }

        .block-box h2 {
            margin: 0 0 12px;
            color: #f87171;
        }

        .block-box p {
            font-size: 14px;
            opacity: .85;
            margin-bottom: 18px;
        }

        .block-box button {
            background: #ef4444;
            border: none;
            color: white;
            padding: 12px 16px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>

    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body>

<div class="block-box">
    <h2>⛔ Доступ запрещён</h2>
    <p>Ваш аккаунт заблокирован администратором.</p>
    <button onclick="Telegram.WebApp.close()">Закрыть</button>
</div>

</body>
</html>

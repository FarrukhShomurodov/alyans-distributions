<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>HRM - Сканирование QR</title>
    <script src="https://telegram.org/js/telegram-web-app.js?1"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #000000);
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            box-sizing: border-box;
        }

        .camera-container {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
        }

        #video {
            width: 100%;
            display: block;
        }

        #canvas {
            display: none;
        }

        .scan-area {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            height: 70%;
            border: 3px solid rgba(46, 173, 220, 0.5);
            border-radius: 8px;
            box-sizing: border-box;
        }

        .status {
            text-align: center;
            margin-bottom: 20px;
            font-size: 16px;
            color: var(--tg-theme-hint-color, #999999);
        }

        .result {
            padding: 15px;
            background-color: var(--tg-theme-secondary-bg-color, #f0f0f0);
            border-radius: 12px;
            margin-top: 20px;
            word-break: break-all;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Сканирование QR-кода</h1>
    <div class="status" id="status">Наведите камеру на QR-код</div>

    <div class="camera-container">
        <video id="video" playsinline></video>
        <canvas id="canvas"></canvas>
        <div class="scan-area"></div>
    </div>

    <div class="result" id="result" style="display: none;"></div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', async (event) => {
        const tg = window.Telegram.WebApp;
        tg.disableVerticalSwipes();
        tg.expand();
        const userData = tg.initDataUnsafe;

        // Проверка пользователя
        if (!userData?.user?.id) {
            window.location.href = 'https://t.me/test_HRM_EMPl_bot';
            return;
        }

        const chatID = userData.user.id;

        try {
            const response = await fetch('/api/has-bot-user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ chat_id: chatID })
            });

            const result = await response.json();

            if (!result.exists) {
                tg.sendData('Пользователь не найден.');
                window.location.href = 'https://t.me/test_HRM_EMPl_bot';
                return;
            }
        } catch (error) {
            console.error('Error:', error);
            window.location.href = 'https://t.me/test_HRM_EMPl_bot';
            return;
        }

        // Инициализация сканера QR
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const canvasContext = canvas.getContext('2d');
        const statusElement = document.getElementById('status');
        const resultElement = document.getElementById('result');

        let scanning = true;

        // Запрос доступа к камере
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: "environment" }
            });
            video.srcObject = stream;
            video.play();

            requestAnimationFrame(tick);
        } catch (err) {
            statusElement.textContent = "Ошибка доступа к камере: " + err.message;
            console.error(err);
        }

        function tick() {
            if (!scanning) return;

            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.height = video.videoHeight;
                canvas.width = video.videoWidth;
                canvasContext.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = canvasContext.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: "dontInvert",
                });

                if (code) {
                    scanning = false;
                    handleQRCode(code.data);
                }
            }

            requestAnimationFrame(tick);
        }

        async function handleQRCode(data) {
            statusElement.textContent = "QR-код распознан!";
            resultElement.textContent = data;
            resultElement.style.display = 'block';

            try {
                // Отправка данных на сервер
                const response = await fetch('/api/process-qr', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        chat_id: chatID,
                        qr_data: data,
                        // start_time: new Date().toISOString(),
                        status: 'accept',
                        type: 'qr_scan',
                    })
                });

                const result = await response.json();

                if (result.success) {
                    tg.showAlert("Данные успешно обработаны!");
                    setTimeout(() => {
                        tg.close();
                    }, 1500);
                } else {
                    tg.showAlert("Ошибка: " + (result.message || "Неизвестная ошибка"));
                    scanning = true;
                    resultElement.style.display = 'none';
                    statusElement.textContent = "Наведите камеру на QR-код";
                }
            } catch (error) {
                console.error('Error:', error);
                tg.showAlert("Ошибка при обработке QR-кода");
                // tg.showAlert(error);
                scanning = true;
                resultElement.style.display = 'none';
                statusElement.textContent = "Наведите камеру на QR-код";
            }
        }
    });
</script>
</body>
</html>

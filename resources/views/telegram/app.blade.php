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
            touch-action: pan-x pan-y;
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
            background-color: #000;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            touch-action: none;
        }

        #video {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
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
            pointer-events: none;
        }

        .zoom-controls {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 10;
        }

        .zoom-button {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: 2px solid white;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            user-select: none;
        }

        .zoom-level {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 14px;
            z-index: 10;
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

        .auto-start-button {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 10000;
            display: block;
        }

        .manual-button {
            padding: 20px 40px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 20px;
            cursor: pointer;
            margin: 20px 0;
            font-weight: bold;
            width: 100%;
            max-width: 300px;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        .instruction {
            text-align: center;
            margin-bottom: 20px;
            color: #666;
            font-size: 16px;
            line-height: 1.5;
        }

        .pinch-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 5;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
<div class="container">
    <h1>Сканирование QR-кода</h1>
    <div class="instruction" id="instruction">
        <span class="loading"></span>Запуск камеры...
    </div>
    <button class="manual-button" id="manualButton" style="display: none;">РАЗРЕШИТЬ КАМЕРУ</button>
    <div class="camera-container" style="display: none;" id="cameraContainer">
        <div class="pinch-overlay" id="pinchOverlay"></div>
        <video id="video" class="video" playsinline></video>
        <canvas id="canvas" style="display: none;"></canvas>
        <div class="scan-area"></div>
        <div class="zoom-level" id="zoomLevel">1x</div>
        <div class="zoom-controls">
            <button class="zoom-button" id="zoomOut">-</button>
            <button class="zoom-button" id="zoomIn">+</button>
        </div>
    </div>
    <div class="result" id="result" style="display: none;"></div>
</div>
<button class="auto-start-button" id="autoStartButton"></button>

<script>
    window.addEventListener('DOMContentLoaded', async (event) => {
        const tg = window.Telegram.WebApp;
        tg.disableVerticalSwipes();
        tg.expand();
        const userData = tg.initDataUnsafe;

        if (!userData?.user?.id) {
            window.location.href = 'https://t.me/test_HRM_EMPl_bot';
            return;
        }

        const chatID = userData.user.id;

        try {
            const response = await fetch('/api/has-bot-user', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
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

        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const canvasContext = canvas.getContext('2d');
        const resultElement = document.getElementById('result');
        const manualButton = document.getElementById('manualButton');
        const cameraContainer = document.getElementById('cameraContainer');
        const instruction = document.getElementById('instruction');
        const autoStartButton = document.getElementById('autoStartButton');
        const zoomInButton = document.getElementById('zoomIn');
        const zoomOutButton = document.getElementById('zoomOut');
        const zoomLevelElement = document.getElementById('zoomLevel');
        const pinchOverlay = document.getElementById('pinchOverlay');

        let scanning = false;
        let stream = null;
        let backCameraId = null;
        let autoStartAttempted = false;
        let currentZoom = 1;
        let minZoom = 1;
        let maxZoom = 4;
        let videoTrack = null;

        function isIOS() {
            return /iPhone|iPad|iPod/i.test(navigator.userAgent);
        }

        async function findBackCamera() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                const cameras = devices.filter(device => device.kind === 'videoinput');
                let backCamera = cameras.find(camera => {
                    const label = camera.label.toLowerCase();
                    return label.includes('back') || label.includes('rear') || label.includes('environment') ||
                        label.includes('main') || label.includes('primary');
                });
                return backCamera ? backCamera.deviceId : cameras[cameras.length - 1]?.deviceId || null;
            } catch (error) {
                console.error('Ошибка при поиске камер:', error);
                return null;
            }
        }

        async function setZoom(zoomValue) {
            if (!videoTrack) return;
            zoomValue = Math.max(minZoom, Math.min(maxZoom, zoomValue));
            try {
                await videoTrack.applyConstraints({ advanced: [{ zoom: zoomValue }] });
                currentZoom = zoomValue;
                zoomLevelElement.textContent = zoomValue.toFixed(1) + 'x';
            } catch (error) {
                console.warn('Не удалось установить зум:', error);
            }
        }

        function initZoomGestures() {
            let initialDistance = 0;
            let initialZoom = currentZoom;
            pinchOverlay.addEventListener('touchstart', (e) => {
                if (e.touches.length === 2) {
                    initialDistance = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                    initialZoom = currentZoom;
                    e.preventDefault();
                }
            });
            pinchOverlay.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2) {
                    const currentDistance = Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                    if (initialDistance > 0) {
                        const zoomFactor = currentDistance / initialDistance;
                        const newZoom = initialZoom * zoomFactor;
                        setZoom(newZoom);
                    }
                    e.preventDefault();
                }
            });
            pinchOverlay.addEventListener('touchend', () => {
                initialDistance = 0;
            });
            zoomInButton.addEventListener('click', () => setZoom(currentZoom + 0.5));
            zoomOutButton.addEventListener('click', () => setZoom(currentZoom - 0.5));
        }

        async function startCamera() {
            try {
                instruction.innerHTML = '<span class="loading"></span>Запрос разрешения...';
                manualButton.style.display = 'none';
                autoStartButton.style.display = 'none';

                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                let constraints = {
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                };

                backCameraId = await findBackCamera();
                if (backCameraId) {
                    constraints.video.deviceId = { exact: backCameraId };
                }

                stream = await navigator.mediaDevices.getUserMedia(constraints);
                videoTrack = stream.getVideoTracks()[0];
                const capabilities = videoTrack.getCapabilities();
                if (capabilities.zoom) {
                    minZoom = capabilities.zoom.min || 1;
                    maxZoom = capabilities.zoom.max || 4;
                }

                video.srcObject = stream;
                cameraContainer.style.display = 'flex';
                video.addEventListener('loadedmetadata', initZoomGestures, { once: true });
                await video.play();

                instruction.textContent = "Основная камера запущена! Наведите на QR-код.";
                scanning = true;
                requestAnimationFrame(tick);
            } catch (err) {
                console.error('Ошибка запуска камеры:', err);
                autoStartButton.style.display = 'block';
                instruction.textContent = err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError'
                    ? "Для сканирования QR-кода разрешите доступ к камере."
                    : "Ошибка при запуске камеры. Коснитесь экрана для повторной попытки.";
            }
        }

        function tick() {
            if (!scanning) return;
            if (video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth > 0) {
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
            if (scanning) requestAnimationFrame(tick);
        }

        async function handleQRCode(data) {
            instruction.textContent = "QR-код распознан!";
            resultElement.textContent = data;
            resultElement.style.display = 'block';

            try {
                // Формируем текущую временную метку в формате datetime
                const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
                const response = await fetch('/api/process-qr', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        chat_id: chatID,
                        qr_data: data,
                        status: 'accept',
                        type: 'qr_scan',
                        start_time: now // Добавляем полную временную метку
                    })
                });

                const result = await response.json();
                if (result.success) {
                    tg.showAlert("Данные успешно обработаны!");
                    setTimeout(() => tg.close(), 1500);
                } else {
                    tg.showAlert("Ошибка: " + (result.message || "Неизвестная ошибка"));
                    resetScanner();
                }
            } catch (error) {
                console.error('Error:', error);
                tg.showAlert("Ошибка при обработке QR-кода");
                resetScanner();
            }
        }

        function resetScanner() {
            scanning = true;
            resultElement.style.display = 'none';
            instruction.textContent = "Наведите камеру на QR-код";
            if (video.srcObject && video.readyState >= video.HAVE_METADATA) {
                requestAnimationFrame(tick);
            }
        }

        autoStartButton.addEventListener('click', startCamera);
        manualButton.addEventListener('click', startCamera);

        instruction.textContent = "Коснитесь экрана для запуска камеры.";
        if (isIOS()) {
            setTimeout(() => {
                if (!autoStartAttempted) {
                    autoStartAttempted = true;
                    startCamera();
                }
            }, 500);
        }

        window.addEventListener('beforeunload', () => {
            scanning = false;
            if (stream) stream.getTracks().forEach(track => track.stop());
        });

        window.addEventListener('orientationchange', () => {
            if (isIOS() && stream) {
                setTimeout(() => startCamera(), 300);
            }
        });
    });
</script>
</body>
</html>

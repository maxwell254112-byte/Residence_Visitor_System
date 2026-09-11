(function () {
    var video = document.getElementById('scanner-video');
    var canvas = document.getElementById('scanner-canvas');
    var cameraSelect = document.getElementById('camera-select');
    var statusBox = document.getElementById('scan-status');
    var resultBox = document.getElementById('scan-result');
    var startBtn = document.getElementById('start-camera');
    var stopBtn = document.getElementById('stop-camera');
    var manualForm = document.getElementById('manual-token-form');
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var scanUrl = document.querySelector('meta[name="scan-url"]')?.getAttribute('content') || '';

    var ctx = canvas ? canvas.getContext('2d', { willReadFrequently: true }) : null;
    var stream = null;
    var ticking = false;
    var lastToken = '';
    var lastAt = 0;
    var busy = false;

    function extractToken(raw) {
        var value = String(raw || '').trim();
        var query = value.match(/[?&]token=([A-Fa-f0-9]{32,128})/i);
        if (query) return query[1];
        var hex = value.match(/\b([A-Fa-f0-9]{32,128})\b/);
        return hex ? hex[1] : value;
    }

    function setStatus(text, type) {
        if (!statusBox) return;
        statusBox.className = 'alert alert-' + (type || 'secondary');
        statusBox.textContent = text;
        statusBox.classList.remove('d-none');
    }

    function showInviteForm(invite) {
        var form = document.getElementById('invite-complete-form');
        var box = document.getElementById('invite-complete');
        var unit = document.getElementById('invite-unit');
        if (!form || !box) return;
        form.querySelector('[name="token"]').value = invite.token || '';
        if (unit) unit.textContent = invite.unit ? ('Unit ' + invite.unit) : 'Invitation found';
        box.classList.remove('d-none');
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideInviteForm() {
        var box = document.getElementById('invite-complete');
        if (box) box.classList.add('d-none');
    }

    function showResult(payload) {
        if (!resultBox) return;
        resultBox.classList.remove('d-none');
        if (payload.ok) {
            var v = payload.visitor || {};
            resultBox.className = 'alert alert-success';
            resultBox.innerHTML = '<strong>' + escapeHtml(payload.message) + '</strong><br>' +
                escapeHtml(v.name || '') + ' visiting ' + escapeHtml(v.host || '') +
                (v.unit ? ' · Unit ' + escapeHtml(v.unit) : '');
            hideInviteForm();
        } else if (payload.code === 'invite_pending') {
            resultBox.classList.add('d-none');
            resultBox.textContent = '';
            if (statusBox) statusBox.classList.add('d-none');
            showInviteForm(payload.invite || {});
        } else {
            resultBox.className = 'alert alert-danger';
            resultBox.textContent = payload.message || 'Check-in was rejected.';
            hideInviteForm();
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    async function submitToken(token, fromManual) {
        token = extractToken(token);
        if (!token) {
            showResult({ ok: false, message: 'Enter a visitor token or invitation link.' });
            return;
        }
        if (!scanUrl) {
            if (fromManual && manualForm) {
                manualForm.submit();
            }
            return;
        }

        var now = Date.now();
        if (busy) return;
        if (!fromManual && token === lastToken && now - lastAt < 4000) return;

        busy = true;
        lastToken = token;
        lastAt = now;
        setStatus('Validating token…', 'info');

        try {
            var res = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ token: token })
            });
            var data = await res.json();
            showResult(data);
            if (data.ok) {
                setStatus('Ready for the next visitor.', 'success');
            } else if (data.code === 'invite_pending') {
                if (statusBox) statusBox.classList.add('d-none');
            } else {
                setStatus('Scan rejected. Ready again.', 'warning');
            }
        } catch (err) {
            showResult({ ok: false, message: 'The scanner could not reach the server.' });
        } finally {
            busy = false;
        }
    }

    function tick() {
        if (!ticking || !video || !ctx || video.readyState !== video.HAVE_ENOUGH_DATA) {
            if (ticking) requestAnimationFrame(tick);
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        var image = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var code = null;
        if (typeof jsQR === 'function') {
            code = jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' });
        }
        if (code && code.data) {
            submitToken(String(code.data).trim(), false);
        }
        requestAnimationFrame(tick);
    }

    async function listCameras() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
        var devices = await navigator.mediaDevices.enumerateDevices();
        var cams = devices.filter(function (d) { return d.kind === 'videoinput'; });
        if (!cameraSelect) return;
        cameraSelect.innerHTML = '';
        cams.forEach(function (cam, i) {
            var opt = document.createElement('option');
            opt.value = cam.deviceId;
            opt.textContent = cam.label || ('Camera ' + (i + 1));
            cameraSelect.appendChild(opt);
        });
    }

    async function startCamera() {
        if (!video || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Camera access is not available. Use manual token entry.', 'warning');
            return;
        }

        stopCamera();
        var constraints = { video: { facingMode: 'environment' }, audio: false };
        if (cameraSelect && cameraSelect.value) {
            constraints = { video: { deviceId: { exact: cameraSelect.value } }, audio: false };
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = stream;
            await video.play();
            await listCameras();
            ticking = true;
            requestAnimationFrame(tick);
            setStatus('Camera ready. Point it at a visitor QR code.', 'success');
        } catch (err) {
            setStatus('Camera permission was denied or unavailable. Use manual token entry.', 'warning');
        }
    }

    function stopCamera() {
        ticking = false;
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
    }

    startBtn?.addEventListener('click', startCamera);
    stopBtn?.addEventListener('click', function () {
        stopCamera();
        setStatus('Camera stopped.', 'secondary');
    });
    cameraSelect?.addEventListener('change', startCamera);

    manualForm?.addEventListener('submit', function (e) {
        var input = manualForm.querySelector('[name="token"]');
        var value = input ? input.value.trim() : '';
        if (!value) return;
        if (!scanUrl || !csrf) return;
        e.preventDefault();
        submitToken(value, true);
        if (input) input.value = '';
    });

    document.getElementById('invite-complete-form')?.addEventListener('submit', async function (e) {
        if (!scanUrl || !csrf) return;
        e.preventDefault();
        var form = e.target;
        var body = {
            action: 'complete_invite',
            token: form.token.value,
            visitor_name: form.visitor_name.value,
            phone: form.phone.value,
            car_plate: form.car_plate.value,
            purpose: form.purpose.value
        };
        setStatus('Saving visitor and checking in…', 'info');
        try {
            var res = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(body)
            });
            var data = await res.json();
            showResult(data);
            if (data.ok) {
                form.reset();
                setStatus('Check-in complete. Ready for the next visitor.', 'success');
            } else {
                setStatus(data.message || 'Could not complete check-in.', 'warning');
            }
        } catch (err) {
            showResult({ ok: false, message: 'The scanner could not reach the server.' });
        }
    });

    window.addEventListener('beforeunload', stopCamera);
})();

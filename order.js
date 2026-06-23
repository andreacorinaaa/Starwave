function showModal(msg, url) {
    document.getElementById('ord-modal-msg').innerText = msg;
    document.getElementById('ord-modal-confirm').href = url;
    document.getElementById('ord-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('ord-modal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('ord-modal');

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    console.log('order.js berhasil dimuat ✓');
});

if (window.QRIS_DATA) {
    const { qrisString, isExpired, isPaid, sudahUpload, expiredAtMs } = window.QRIS_DATA;
    if (!isExpired && !isPaid) {
        QRCode.toCanvas(document.getElementById('qr-canvas'), qrisString, {
            width: 190,
            margin: 1,
            color: { dark: '#1a1a1a', light: '#ffffff' }
        });
        if (!sudahUpload) {
            const timerInterval = setInterval(() => {
                const remaining = Math.max(0, expiredAtMs - Date.now());
                const mins = String(Math.floor(remaining / 60000)).padStart(2, '0');
                const secs = String(Math.floor((remaining % 60000) / 1000)).padStart(2, '0');

                document.getElementById('timer-count').textContent = `${mins}:${secs}`;

                if (remaining <= 0) {
                    clearInterval(timerInterval);
                    location.reload(); // waktu habis -> reload biar status jadi expired
                }
            }, 1000);
        }
    }

    function setupPreview(inputId, previewId, placeholderId) {
        const input = document.getElementById(inputId);
        if (!input) return; 

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.getElementById(previewId);
                img.src = e.target.result;
                img.style.display = 'block';
                document.getElementById(placeholderId).style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }

    setupPreview('bukti_input', 'img-preview', 'placeholder1');
}

function validateSearch(form) {
    const keyword = form.q.value.trim();
    if (keyword === '') {
        return false; 
    }
    return true;
}
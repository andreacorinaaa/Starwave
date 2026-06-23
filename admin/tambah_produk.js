const inpGambar         = document.getElementById('inp-gambar');
const imgPreview        = document.getElementById('img-preview');
const uploadPlaceholder = document.getElementById('upload-placeholder');
const uploadArea        = document.getElementById('upload-area');
const form              = document.getElementById('form-produk');

inpGambar.addEventListener('change', function () {
    const file = this.files[0];

    uploadArea.classList.remove('input-error');

    if (!file) return; 

    const reader = new FileReader();
    reader.onload = function (e) {
        imgPreview.src = e.target.result;          
        imgPreview.style.display = 'block';
        uploadPlaceholder.style.display = 'none';   
    };
    reader.readAsDataURL(file);
});

['dragover', 'dragleave', 'drop'].forEach(function (eventName) {
    uploadArea.addEventListener(eventName, function (e) {
        e.preventDefault();
    });
});  

uploadArea.addEventListener('dragover', function () {
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', function () {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', function (e) {
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        inpGambar.files = dataTransfer.files;
        inpGambar.dispatchEvent(new Event('change'));
    }
});

document.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(function (el) {
    el.addEventListener('input', function () { el.classList.remove('input-error'); });
    el.addEventListener('change', function () { el.classList.remove('input-error'); });
});

form.addEventListener('submit', function (e) {
    let fieldKosongPertama = null;

    const daftarField = [
        { el: document.getElementById('inp-nama'),     cekKosong: v => v.trim() === '' },
        { el: document.getElementById('inp-harga'),     cekKosong: v => v.trim() === '' || Number(v) <= 0 },
        { el: document.getElementById('inp-kategori'),  cekKosong: v => v === '' },
        { el: document.getElementById('inp-deskripsi'), cekKosong: v => v.trim() === '' },
    ];

    daftarField.forEach(function (field) {
        const kosong = field.cekKosong(field.el.value);
        field.el.classList.toggle('input-error', kosong);
        if (kosong && !fieldKosongPertama) {
            fieldKosongPertama = field.el;
        }
    });

    const gambarKosong = inpGambar.files.length === 0;
    uploadArea.classList.toggle('input-error', gambarKosong);
    if (gambarKosong && !fieldKosongPertama) {
        fieldKosongPertama = uploadArea;
    }

    if (fieldKosongPertama) {
        e.preventDefault();
        // Otomatis menggeser halaman ke field yang bermasalah
        fieldKosongPertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (typeof fieldKosongPertama.focus === 'function') {
            fieldKosongPertama.focus();
        }
    }
});
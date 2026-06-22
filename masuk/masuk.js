function togglePw(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function isEmailValid(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Munculin teks error kecil di bawah field tertentu
function showFieldError(input, message) {
    const target = input.closest('.pw-wrap') || input;
    let err = target.nextElementSibling;
    if (!err || !err.classList.contains('field-error')) {
        err = document.createElement('small');
        err.className = 'field-error';
        target.insertAdjacentElement('afterend', err);
    }
    err.textContent = message;
    err.style.display = 'block';
    input.style.borderColor = '#c0392b';
}

function hideFieldError(input) {
    const target = input.closest('.pw-wrap') || input;
    const err = target.nextElementSibling;
    if (err && err.classList.contains('field-error')) {
        err.style.display = 'none';
    }
    input.style.borderColor = '';
}

function validateLogin(form) {
    hideFieldError(form.email);
    hideFieldError(form.password);
    let valid = true;

    const email = form.email.value.trim();
    const password = form.password.value.trim();

    if (email === '') {
        showFieldError(form.email, 'Email tidak boleh kosong!');
        valid = false;
    } else if (!isEmailValid(email)) {
        showFieldError(form.email, 'Format email tidak valid!');
        valid = false;
    }

    if (password === '') {
        showFieldError(form.password, 'Password tidak boleh kosong!');
        valid = false;
    }

    return valid;
}

function validateRegister(form) {
    hideFieldError(form.email);
    hideFieldError(form.password);
    hideFieldError(form.tanggal_lahir);
    let valid = true;

    const email = form.email.value.trim();
    const password = form.password.value.trim();
    const tanggalLahir = form.tanggal_lahir.value.trim();

    if (email === '') {
        showFieldError(form.email, 'Email tidak boleh kosong!');
        valid = false;
    } else if (!isEmailValid(email)) {
        showFieldError(form.email, 'Format email tidak valid!');
        valid = false;
    }

    if (password === '') {
        showFieldError(form.password, 'Password tidak boleh kosong!');
        valid = false;
    } else if (password.length < 8 || !/\d/.test(password)) {
        showFieldError(form.password, 'Password minimal 8 karakter dan harus mengandung angka!');
        valid = false;
    }

    if (tanggalLahir === '') {
        showFieldError(form.tanggal_lahir, 'Tanggal lahir wajib diisi!');
        valid = false;
    }

    return valid;
}

function validateResetPassword(form) {
    hideFieldError(form.password);
    hideFieldError(form.confirm);
    let valid = true;

    const password = form.password.value.trim();
    const confirm = form.confirm.value.trim();

    if (password === '') {
        showFieldError(form.password, 'Password tidak boleh kosong!');
        valid = false;
    } else if (password.length < 8 || !/\d/.test(password)) {
        showFieldError(form.password, 'Password minimal 8 karakter dan harus mengandung angka!');
        valid = false;
    }

    if (confirm === '') {
        showFieldError(form.confirm, 'Konfirmasi password tidak boleh kosong!');
        valid = false;
    } else if (password !== confirm) {
        showFieldError(form.confirm, 'Konfirmasi password tidak sama!');
        valid = false;
    }

    return valid;
}

function validateLupaPassword(form) {
    hideFieldError(form.email);
    let valid = true;

    const email = form.email.value.trim();

    if (email === '') {
        showFieldError(form.email, 'Email tidak boleh kosong!');
        valid = false;
    } else if (!isEmailValid(email)) {
        showFieldError(form.email, 'Format email tidak valid!');
        valid = false;
    }

    return valid;
}
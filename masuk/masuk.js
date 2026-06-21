function togglePw(id) {
    // Ambil elemen input password berdasarkan id-nya
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
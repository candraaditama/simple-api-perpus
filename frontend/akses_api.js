fetch('https://perpus.bantulkab.go.id/api/', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer a1b2c3d4e5f678901234567890abcdef1234567890abcdef1234567890abcdef'
    },
    body: JSON.stringify({
        name: "Budi",
        phone: "085712345678",
        message: "Pendaftaran KTA"
    })
})
.then(r => r.json())
.then(res => {
    if (res.success) {
        alert('Berhasil! KTA Anda: ' + res.kta);
    }
});
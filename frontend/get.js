fetch('https://perpus.bantulkab.go.id/api/', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        name: "Ahmad",
        phone: "08123456789",
        message: "I want to apply"
    })
})
.then(r => r.json())
.then(res => {
    alert('Berhasil! KTA Anda : ' + res.kta);
    console.log('Simpan ya :', res.kta); // → kta42
});
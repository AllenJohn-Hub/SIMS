document.addEventListener('DOMContentLoaded', function() {
    const qrcodeElements = document.querySelectorAll('.qrcode');
    qrcodeElements.forEach(element => {
        const itemId = element.getAttribute('data-item-id');
        const qr = new QRCode(element, {
            text: itemId,
            width: 64,
            height: 64
        });
    });
}); 
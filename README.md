# 🎮 GameZone — Kurulum Rehberi

## Dosya Yapısı
```
gamesite/
├── index.php        ← Ana sayfa (oyun listesi)
├── game.php         ← Oyun detay & oynatma sayfası
├── admin.php        ← Admin paneli (oyun yükleme)
├── .htaccess        ← Güvenlik ayarları
└── games/
    ├── games.json   ← Oyun veritabanı (otomatik oluşur)
    ├── [oyun dosyaları buraya yüklenir]
    └── [kapak görselleri buraya yüklenir]
```

## Kurulum

1. Tüm dosyaları hosting'ine FTP ile yükle
2. `games/` klasörüne **yazma izni (755 veya 777)** ver:
   ```bash
   chmod 755 games/
   ```
3. Siteyi ziyaret et: `https://domain.com/`
4. Admin paneli: `https://domain.com/admin.php`

## Kullanım

### Oyun Yükleme (Admin Panel)
- `/admin.php` adresine git
- Oyun türünü seç: HTML veya EXE
- Başlık ve açıklama yaz
- Dosyayı yükle (isteğe bağlı kapak görseli)
- "Yükle" butonuna bas

### HTML Oyunlar
- Tek `.html` dosyası → direkt yükle
- Birden fazla dosya → `.zip` olarak yükle (ZIP içinde `index.html` olmalı)
- Ziyaretçiler siteden direkt oynayabilir

### EXE Oyunlar
- `.exe` veya `.zip` olarak yükle
- Ziyaretçiler indirip oynayabilir
- İndirme sayısı otomatik takip edilir

## Güvenlik Notu
Admin paneline ekstra koruma için `.htaccess`'e şunu ekleyebilirsin:
```apache
<Files "admin.php">
    AuthType Basic
    AuthName "Admin"
    AuthUserFile /path/to/.htpasswd
    Require valid-user
</Files>
```

## Desteklenen Dosya Türleri
| Tür | Format |
|-----|--------|
| HTML Oyun | .html, .htm, .zip |
| EXE Oyun | .exe, .zip |
| Kapak Görseli | .jpg, .png, .gif, .webp |

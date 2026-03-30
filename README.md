# 🚀 Patch System - দ্রুত রেফারেন্স কার্ড
 
## ⚡ দৈনিক কাজ (৩ স্টেপ)

```bash
# ১. স্ক্যান করুন
php artisan patch:update

# ২. Zip বানান
php artisan patch:wrap --update-timestamp --cleanup

# ৩. Deploy করুন
scp patches/patch_*.zip server:/var/www/
```

---

## 📋 সব কমান্ড

| কমান্ড | কাজ |
|--------|-----|
| `php artisan patch:update` | পরিবর্তিত ফাইল স্ক্যান করুন |
| `php artisan patch:wrap` | Zip তৈরি করুন |
| `php artisan patch:timestamp` | Timestamp ম্যানেজ করুন |

---

## 🎯 সঠিক পদ্ধতি (Best Practice)

```bash
# Production Deploy এর পর একবার
php artisan patch:timestamp --now

# প্রতিদিন development শেষে
php artisan patch:update && \
php artisan patch:wrap --update-timestamp --cleanup
```

---

## ⚙️ কনফিগারেশন

`config/patch.php` ফাইল:

```php
'enabled' => true,                              // চালু/বন্ধ
'last_patch_at' => '2026-03-31 10:00:00',     // এর পরের ফাইল আসবে
'scan_paths' => ['app', 'config', 'routes'],  // কী স্ক্যান করবে
'ignore_paths' => ['vendor', 'node_modules'], // কী skip করবে
```

---

## 📊 উদাহরণ

```bash
$ php artisan patch:update
  ✓ app/Services/NewService.php
  ✓ app/Models/User.php
  Files to store: 2  ← শুধু ২টা ফাইল!

$ php artisan patch:wrap --update-timestamp --cleanup
  Zip created: patch_2026_03_31_153045.zip
  Files: 2
  Size: 8.5 KB  ← ছোট zip!

$ scp patches/patch_*.zip server:/var/www/
$ ssh server "cd /var/www && unzip patch_*.zip"
```

---

## 🔴 সতর্কতা

| করবেন না | কেন |
|-----------|-----|
| `--update-timestamp` না দেওয়া | Duplicate আসবে |
| `--cleanup` না দেওয়া | Database ভরে যাবে |
| Old timestamp রাখা | সব ফাইল আসবে |

---

## 🆘 সমস্যা সমাধান

| সমস্যা | সমাধান |
|---------|---------|
| সব ফাইল আসছে | `php artisan patch:timestamp --now` |
| No patches found | `php artisan patch:update` চালান |
| Permission denied | `chmod 755 patches/` |
| File not found | Re-scan করুন |

---

## 📚 সম্পূর্ণ গাইড

বিস্তারিত জানতে দেখুন: **[PATCH_SYSTEM_GUIDE.md](PATCH_SYSTEM_GUIDE.md)**

---

## 🎯 মনে রাখবেন

> **"Production deploy এর পর `--now`, development এর পর `--update-timestamp`"**

```
Deploy → patch:timestamp --now
Work   → patch:update
Wrap   → patch:wrap --update-timestamp
Repeat → আবার থেকে শুরু ✓
```

---

**Made with ❤️ for easy Laravel deployments**

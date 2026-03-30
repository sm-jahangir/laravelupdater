# 🎯 Laravel Patch System - সম্পূর্ণ গাইড

## 📚 সূচিপত্র
 
1. [পরিচিতি](#পরিচিতি)
2. [কেন এই সিস্টেম প্রয়োজন](#কেন-এই-সিস্টেম-প্রয়োজন)
3. [ইনস্টলেশন](#ইনস্টলেশন)
4. [কনফিগারেশন](#কনফিগারেশন)
5. [কিভাবে কাজ করে](#কিভাবে-কাজ-করে)
6. [কমান্ড সমূহ](#কমান্ড-সমূহ)
7. [সম্পূর্ণ ওয়ার্কফ্লো](#সম্পূর্ণ-ওয়ার্কফ্লো)
8. [ব্যবহারিক উদাহরণ](#ব্যবহারিক-উদাহরণ)
9. [সমস্যা সমাধান](#সমস্যা-সমাধান)
10. [প্রায়শ জিজ্ঞাসিত প্রশ্ন](#প্রায়শ-জিজ্ঞাসিত-প্রশ্ন)

---

## পরিচিতি

**Laravel Patch System** হলো একটি সহজ টুল যা আপনার প্রজেক্টের **শুধুমাত্র পরিবর্তিত ফাইলগুলো** ট্র্যাক করে এবং সেগুলো দিয়ে একটি zip file তৈরি করে। এটি especially বড় প্রজেক্টের জন্য ডিজাইন করা হয়েছে যেখানে প্রতিবার পুরো প্রজেক্ট deploy করা সম্ভব হয় না।

### মূল সুবিধাসমূহ

- ✅ **শুধু পরিবর্তিত ফাইল** - পুরো প্রজেক্ট নয়
- ✅ **Database এ ট্র্যাক রাখে** - কোনো ফাইল বাদ যাবে না
- ✅ **Automatic timestamp tracking** - সর্বশেষ deploy এর পর কী পরিবর্তন হয়েছে
- ✅ **Safe deployment** - শুধু প্রয়োজনীয় ফাইল deploy হবে
- ✅ **Cross-platform** - Windows, Linux, Mac সব জায়গায় কাজ করবে

---

## কেন এই সিস্টেম প্রয়োজন

### সমস্যা ১: বড় প্রজেক্ট Deploy

```
❌ সমস্যা:
- E-commerce প্রজেক্টে 10,000+ ফাইল
- 5টা ফাইল edit করলেন
- পুরো প্রজেক্ট zip করলে 500 MB+
- Deploy করতে সময় লাগবে ঘন্টা

✅ সমাধান:
- শুধু 5টা পরিবর্তিত ফাইল zip করুন
- Zip সাইজ: 50 KB
- Deploy: ২ মিনিটে!
```

### সমস্যা ২: কোন ফাইল পরিবর্তন হয়েছে?

```
❌ সমস্যা:
- মনে নেই কী কী ফাইল edit করেছিলেন
- কোনো ফাইল বাদ পড়ে যেতে পারে
- Production এ bug আসতে পারে

✅ সমাধান:
- Database এ automatic track হয়
- কোনো ফাইল বাদ পড়ে না
- 100% safe deployment
```

---

## ইনস্টলেশন

### ধাপ ১: Migration রান করুন

```bash
php artisan migrate
```

এটি `patches` table তৈরি করবে:

```sql
CREATE TABLE patches (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    file_from VARCHAR(500) UNIQUE,      -- ফাইলের path
    modified_at TIMESTAMP NULL,         -- কখন modify হয়েছে
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### ধাপ ২: কনফিগারেশন চেক করুন

`config/patch.php` ফাইলে যান এবং সেট আপ করুন।

---

## কনফিগারেশন

### `config/patch.php` ফাইল

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Patch System চালু/বন্ধ
    |--------------------------------------------------------------------------
    */
    'enabled' => env('PATCH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | শেষ Patch এর সময়
    |--------------------------------------------------------------------------
    |
    | এই সময়ের পর যত ফাইল modify হবে, শুধু সেগুলোই patch এ যাবে
    |
    */
    'last_patch_at' => env('LAST_PATCH_AT', '2025-01-01 00:00:00'),

    /*
    |--------------------------------------------------------------------------
    | কোন কোন Folder Scan করবে
    |--------------------------------------------------------------------------
    |
    | শুধু এই folder গুলো scan করবে
    |
    */
    'scan_paths' => [
        'app',           // Controllers, Models, Services
        'config',        // Configuration files
        'resources',     // Views, assets
        'routes',        // Web, API routes
        'database',      // Migrations, seeders
        'public',        // Public assets
    ],

    /*
    |--------------------------------------------------------------------------
    | কোন পাথ Ignore করবে
    |--------------------------------------------------------------------------
    |
    | এই folder/ফাইল গুলো skip করবে
    |
    */
    'ignore_paths' => [
        'vendor',            // Third-party packages
        'node_modules',      // NPM packages
        'storage',           // Laravel storage
        'bootstrap/cache',   // Cache files
        '.git',              // Git folder
        '.env',              // Environment files
        '.idea',             // IDE files
        'patches',           // Patch folder itself
    ],

    /*
    |--------------------------------------------------------------------------
    | কোন Extension Ignore করবে
    |--------------------------------------------------------------------------
    */
    'ignore_extensions' => [
        'log',     // Log files
        'cache',   // Cache files
        'pyc',     // Python compiled
        'swp',     // Vim swap files
    ],
];
```

---

## কিভাবে কাজ করে

### Workflow Diagram

```
┌─────────────────────────────────────────────────────────┐
│           1. PRODUCTION DEPLOY সম্পন্ন                  │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│  php artisan patch:timestamp --now                      │
│  "last_patch_at = 2026-03-31 10:00:00" সেট হলো         │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│           2. DEVELOPMENT WORK শুরু                     │
│  - app/Services/NewService.php (নতুন ফাইল)              │
│  - app/Models/User.php (edit করলেন)                    │
│  - config/app.php (পরিবর্তন করলেন)                    │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│  php artisan patch:update                               │
│  "3টা পরিবর্তিত ফাইল খুঁজে পাই"                        │
│  ✅ app/Services/NewService.php                         │
│  ✅ app/Models/User.php                                 │
│  ✅ config/app.php                                      │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│  php artisan patch:wrap --update-timestamp --cleanup   │
│  "patch_2026_03_31_153045.zip তৈরি হলো"                │
│  "last_patch_at auto update হলো"                       │
│  "database clean হলো"                                  │
└─────────────────────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────┐
│           3. ZIP DEPLOY TO PRODUCTION                    │
│  - Upload patch_*.zip to server                         │
│  - Extract in project root                              │
└─────────────────────────────────────────────────────────┘
```

### Timestamp Tracking Explained

```
Timeline Example:

2026-03-25 10:00  ────────► Production Deploy
                        ↓
                patch:timestamp --now
                        ↓
            last_patch_at = 2026-03-25 10:00

2026-03-26 14:30  ────────► You edit app/User.php
2026-03-26 15:45  ────────► You edit config/app.php
2026-03-26 16:20  ────────► You create app/Services/NewService.php

                        ↓
                    patch:update
                        ↓
            ✅ এই ৩টা ফাইল database এ সেভ হবে
            (কারণ এরা 2026-03-25 10:00 এর পরের)

2026-03-27 10:00  ────────► patch:wrap --update-timestamp
                        ↓
            patch_2026_03_27.zip created (3 files)
            last_patch_at = 2026-03-27 10:00 (auto updated)

2026-03-28 11:00  ────────► Next deployment...
                        ↓
            এবার শুধু 2026-03-27 10:00 এর পরের ফাইল আসবে!
```

---

## কমান্ড সমূহ

### ১. patch:update

প্রজেক্ট স্ক্যান করে পরিবর্তিত ফাইল database এ সেভ করে।

```bash
php artisan patch:update
```

**অপশন:**
- `--dry-run` - দেখাবে কী হবে কিন্তু সেভ করবে না

**উদাহরণ:**
```bash
# সাধারণ স্ক্যান
php artisan patch:update

# পরীক্ষা করে দেখুন
php artisan patch:update --dry-run
```

**Output:**
```
╔══════════════════════════════════════════════════════╗
║         Laravel Patch System - File Scanner          ║
╚══════════════════════════════════════════════════════╝

Base path: /var/www/html
Looking for files modified after: 2026-03-25 10:00:00

🔍 Scanning directories...
  📁 Scanning: app
    ✓ app/Services/NewService.php (2026-03-26 16:20:15)
    ✓ app/Models/User.php (2026-03-26 14:30:45)
  📁 Scanning: config
    ✓ config/app.php (2026-03-26 15:45:20)

╔══════════════════════════════════════════════════════╗
║                    Scan Summary                       ║
╚══════════════════════════════════════════════════════╝
  Total files found:  1,547
  Files to store:     3
  Files skipped:      1,544

✨ Success! Run php artisan patch:wrap to create the patch zip.
```

---

### ২. patch:wrap

Database এ থাকা patches দিয়ে zip file তৈরি করে।

```bash
php artisan patch:wrap
```

**অপশন:**
- `--zip-name="name"` - Custom zip নাম (extension ছাড়া)
- `--update-timestamp` - Zip তৈরির পর auto update timestamp
- `-c, --cleanup` - Database থেকে patches ডিলিট করবে

**উদাহরণ:**
```bash
# Default নাম (patch_2026_03_31_153045.zip)
php artisan patch:wrap

# Custom নাম
php artisan patch:wrap --zip-name="hotfix-login-bug"

# Auto update timestamp (সবচেয়ে গুরুত্বপূর্ণ!)
php artisan patch:wrap --update-timestamp

# সব একসাথে (RECOMMENDED)
php artisan patch:wrap --zip-name="feature-user-profile" \
                      --update-timestamp \
                      --cleanup
```

**Output:**
```
╔══════════════════════════════════════════════════════╗
║         Laravel Patch System - Zip Creator           ║
╚══════════════════════════════════════════════════════╝

Found 3 patch(es) in database
Output directory: /var/www/html/patches

📦 Copying files to temporary directory...
 0/3 [░░░░░░░░░░░░░░░░░░░░░░░░░░░░]   0%
 3/3 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

🗜️  Creating zip file: patch_2026_03_31_153045.zip
   Added 3 file(s) to archive

╔══════════════════════════════════════════════════════╗
║                    Zip Summary                        ║
╚══════════════════════════════════════════════════════╝
  Zip location:    patches/patch_2026_03_31_153045.zip
  File size:       12.45 KB
  Files included:  3
  Files failed:    0

✓ Updated last_patch_at to: 2026-03-31 15:30:45
  Next patch will only include files modified after this time.
```

---

### ৩. patch:timestamp

`last_patch_at` timestamp manage করার জন্য।

```bash
php artisan patch:timestamp
```

**অপশন:**
- `--show` - Current timestamp দেখাবে
- `--now` - Current time এ সেট করবে
- `--set="date"` - Specific date সেট করবে

**উদাহরণ:**
```bash
# Current timestamp দেখুন
php artisan patch:timestamp --show

# এখন সেট করুন (deployment এর পর)
php artisan patch:timestamp --now

# Specific date সেট করুন
php artisan patch:timestamp --set="2026-03-25 10:00:00"
```

**Output:**
```
╔══════════════════════════════════════════════════════╗
║         Patch System - Current Timestamp             ║
╚══════════════════════════════════════════════════════╝

Current last_patch_at: 2026-03-25 10:00:00
                  (2 days ago)

Only files modified AFTER this date will be included in patches.
To update this timestamp, use: php artisan patch:timestamp --now
```

---

## সম্পূর্ণ ওয়ার্কফ্লো

### 🎯 সঠিক পদ্ধতি (Best Practice)

```bash
# ──────────────────────────────────────────────
# STEP 1: প্রথমবার Production Deploy এর পর
# ──────────────────────────────────────────────
php artisan patch:timestamp --now

# Output: ✓ Updated last_patch_at to: 2026-03-31 10:00:00


# ──────────────────────────────────────────────
# STEP 2: প্রতিদিন Development Work
# ──────────────────────────────────────────────

# 2.1 আপনার কাজ করুন (ফাইল edit/create)


# 2.2 স্ক্যান করুন
php artisan patch:update


# 2.3 Zip তৈরি করুন (ONE COMMAND!)
php artisan patch:wrap \
    --zip-name="update-$(date +%Y%m%d)" \
    --update-timestamp \
    --cleanup


# ──────────────────────────────────────────────
# STEP 3: Deploy to Production
# ──────────────────────────────────────────────

# 3.1 Upload zip to server
scp patches/update-20260331.zip user@server:/var/www/

# 3.2 SSH করুন
ssh user@server

# 3.3 Backup নিন (optional)
cd /var/www
cp -r html html-backup-$(date +%Y%m%d)

# 3.4 Extract করুন
unzip -o update-20260331.zip

# 3.5 Cache clear করুন
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 3.6 Permissions fix (if needed)
chown -R www-data:www-data storage bootstrap/cache

# 3.7 Test করুন!
# Browser এ গিয়ে দেখুন সব ঠিক আছে কিনা
```

### 🔄 পুনরাবৃত্তি (Repeat)

পরের দিন আবার একই কাজ:

```bash
# Development work...

php artisan patch:update

php artisan patch:wrap --zip-name="update-$(date +%Y%m%d)" \
                      --update-timestamp \
                      --cleanup

# Deploy
```

**মনে রাখবেন:** `--update-timestamp` ব্যবহার করলে পরের patch আগের patch এর পর থেকে শুরু হবে!

---

## ব্যবহারিক উদাহরণ

### উদাহরণ ১: Hotfix Deployment

```bash
# === পরিস্থিতি ===
# Production এ একটা critical bug পেলেন
# User login করতে পারছে না

# === সমাধান ===

# 1. Local এ bug fix করুন
vim app/Http/Controllers/Auth/LoginController.php

# 2. Scan করুন
php artisan patch:update
# Output: Files to store: 1
#         ✓ app/Http/Controllers/Auth/LoginController.php

# 3. Hotfix zip তৈরি করুন
php artisan patch:wrap \
    --zip-name="hotfix-login-issue" \
    --update-timestamp \
    --cleanup
# Output: patch_2026_03_31_153045.zip → hotfix-login-issue.zip
#         Size: 2.1 KB (just 1 file!)

# 4. সাথে সাথে deploy করুন
scp patches/hotfix-login-issue.zip prod-server:/var/www/
ssh prod-server "cd /var/www && unzip -o hotfix-login-issue.zip && php artisan cache:clear"

# 5. Verify করুন
# Browser এ গিয়ে login চেক করুন
```

---

### উদাহরণ ২: New Feature Launch

```bash
# === পরিস্থিতি ===
# নতুন User Dashboard ফিচার তৈরি করলেন
# ৩ দিন ধরে কাজ করলেন
# ১৫টা ফাইল create/edit করলেন

# === Day 1 ===
# কিছু ফাইল edit করলেন
php artisan patch:update
# Files to store: 5

# === Day 2 ===
# আরও কাজ করলেন
php artisan patch:update
# Files to store: 12 (previous 5 + new 7)

# === Day 3 - Feature Complete ===
# Final changes
php artisan patch:update
# Files to store: 15

# Create feature zip
php artisan patch:wrap \
    --zip-name="feature-user-dashboard" \
    --update-timestamp \
    --cleanup
# Output: feature-user-dashboard.zip
#         Files: 15
#         Size: 45.2 KB

# Deploy to staging first
scp patches/feature-user-dashboard.zip staging:/var/www/
# Test thoroughly...

# Then deploy to production
scp patches/feature-user-dashboard.zip production:/var/www/
```

---

### উদাহরণ ৩: Multiple Servers Deployment

```bash
# === পরিস্থিতি ===
# আপনার ৩টা server আছে:
# - web-01.example.com (Main)
# - web-02.example.com (Secondary)
# - web-03.example.com (Secondary)

# === Solution ===

# 1. Create patch ONCE
php artisan patch:update
php artisan patch:wrap --zip-name="global-update-$(date +%Y%m%d)" \
                      --update-timestamp \
                      --cleanup

# 2. Deploy to all servers
for server in web-01 web-02 web-03; do
    echo "Deploying to $server..."
    scp patches/global-update-*.zip $server:/var/www/
    ssh $server "cd /var/www && unzip -o global-update-*.zip && php artisan cache:clear"
done

# 3. Verify all servers
for server in web-01 web-02 web-03; do
    curl -s https://$server.example.com/health | grep OK
done
```

---

### উদাহরণ ৪: Rollback (যদি কিছু ভুল হয়)

```bash
# === পরিস্থিতি ===
# Patch deploy করার পর bug পেলেন
# Rollback করতে হবে

# === Solution ===

# 1. Backup ছিল (আশা করি!)
# আপনি deploy এর আগে backup নিয়েছিলেন:
cd /var/www
cp -r html html-backup-before-deploy

# 2. Rollback
cd /var/www
rm -rf html
mv html-backup-before-deploy html

# 3. Cache clear
php artisan cache:clear
php artisan config:clear

# 4. Patch ফিক্স করুন locally
# Bug fix করুন...

# 5. আবার patch বানান
php artisan patch:update
php artisan patch:wrap --zip-name="hotfix-fixed" --update-timestamp

# 6. আবার deploy (এবার সাবধানে!)
```

---

## সমস্যা সমাধান

### সমস্যা ১: "No patches found in database"

**কারণ:** আপনি `patch:update` চালাননি।

**সমাধান:**
```bash
php artisan patch:update
```

---

### সমস্যা ২: "সব ফাইল আসছে, খুব বেশি!"

**কারণ:** `last_patch_at` বহুত পুরনো।

**সমাধান:**
```bash
# Current timestamp দেখুন
php artisan patch:timestamp --show

# Update করুন
php artisan patch:timestamp --now
```

---

### সমস্যা ৩: "একই ফাইল বারবার আসছে"

**কারণ:** `--update-timestamp` ব্যবহার করছেন না।

**সমাধান:**
```bash
# সবসময় এটা ব্যবহার করুন
php artisan patch:wrap --update-timestamp --cleanup
```

---

### সমস্যা ৪: "File not found" warning

**কারণ:** ফাইল delete হয়ে গেছে `patch:update` এর পর।

**সমাধান:**
```bash
# Re-scan করুন
php artisan patch:update

# অথবা manually ডিলিট করুন
php artisan tinker
>>> App\Models\Patch::where('file_from', 'path/to/deleted/file')->delete();
```

---

### সমস্যা ৫: "Permission denied"

**কারণ:** `patches` folder এ write permission নেই।

**সমাধান:**
```bash
# Linux/Mac
chmod -R 755 patches/
sudo chown -R www-data:www-data patches/

# Windows (যদি WAMP/XAMPP use করেন)
# Right-click patches folder → Properties → Security
# Give Full Control to IUSR/IIS_IUSRS
```

---

### সমস্যা ৬: "Migration error - BLOB/TEXT column"

**কারণ:** Old migration use করছেন।

**সমাধান:**
```bash
# Rollback করুন
php artisan migrate:rollback

# Re-migrate করুন
php artisan migrate
```

---

## প্রায়শ জিজ্ঞাসিত প্রশ্ন

### ❓ patch:update কখন চালাবেন?

**উত্তর:** যখনই কোনো ফাইল edit/create করবেন।

```bash
# Edit করার পর অবিলম্বে চালান
vim app/Models/User.php
php artisan patch:update
```

---

### ❓ patch:wrap কখন চালাবেন?

**উত্তর:** যখন deploy করার জন্য ready হবেন।

```bash
# Development শেষ, deploy এর সময়
php artisan patch:wrap --update-timestamp --cleanup
```

---

### ❓ `--update-timestamp` কেন জরুরি?

**উত্তর:** এটা automatic tracking করে। না দিলে পরের patch এ আগের ফাইলও আসবে।

```bash
# ❌ ভুল (timestamp update হবে না)
php artisan patch:wrap

# ✅ সঠিক
php artisan patch:wrap --update-timestamp
```

---

### ❓ `--cleanup` কি করে?

**উত্তর:** Database থেকে patches ডিলিট করে, যাতে পরের বার fresh start হয়।

```bash
# Patch তৈরি হলে আর DB এ দরকার নেই
php artisan patch:wrap --cleanup
```

---

### ❓ Zip file কোথায় থাকে?

**উত্তর:** `patches/` folder এ।

```
your-project/
├── app/
├── config/
├── patches/              ← এখানে zip থাকবে
│   └── patch_2026_03_31_153045.zip
└── ...
```

---

### ❓ Deploy কিভাবে করবেন?

**উত্তর:** Zip upload করে extract করুন।

```bash
# Local থেকে server এ
scp patches/patch_*.zip user@server:/var/www/

# Server এ
cd /var/www
unzip patch_*.zip
```

---

### ❓ কি কি folder scan হবে?

**উত্তর:** `config/patch.php` এ `scan_paths` দেখুন।

```php
'scan_paths' => [
    'app',        // ← এখানে
    'config',     // ← এখানে
    'resources',  // ← এখানে
    'routes',     // ← এখানে
    'database',   // ← এখানে
    'public',     // ← এখানে
],
```

---

### ❓ Vendor/node_modules কি আসবে?

**উত্তর:** না! `ignore_paths` এ আছে।

```php
'ignore_paths' => [
    'vendor',        // ❌ আসবে না
    'node_modules',  // ❌ আসবে না
    'storage',       // ❌ আসবে না
],
```

---

### ❓ Database তে কী থাকে?

**উত্তর:** শুধু ফাইল path আর modification time।

```sql
| id | file_from                             | modified_at         |
|----|---------------------------------------|---------------------|
| 1  | app/Models/User.php                  | 2026-03-31 14:30:00 |
| 2  | config/app.php                       | 2026-03-31 15:20:00 |
| 3  | app/Services/NewService.php          | 2026-03-31 16:45:00 |
```

---

### ❓ ফাইল size কত হতে পারে?

**উত্তর:** `string(500)` - 500 characters পর্যন্ত path।

```
সাধারণত: app/Services/Very/Long/Path/ToFile.php (50 chars)
Maximum: 500 chars (যথেষ্ট!)
```

---

### ❓ Multiple developers কিভাবে use করবে?

**উত্তর:** প্রত্যেকের নিজস্ব `last_patch_at` থাকা উচিত।

```bash
# Developer 1
php artisan patch:timestamp --now  # তার last deployment

# Developer 2
php artisan patch:timestamp --now  # তার last deployment

# দুইজনের patch আলাদা হবে ✓
```

---

### ❓ Production এ কি এই system থাকতে হবে?

**উত্তর:** না! শুধু local/development এ দরকার।

```bash
# Local development
php artisan patch:update  # ✅ দরকার

# Production server
# শুধু zip extract করবেন  # ❌ system দরকার নেই
```

---

### ❓ Git এর সাথে কি conflict হবে?

**উত্তর:** না! দুইটা আলাদা system।

```bash
# Git = Version control (history, branches, merge)
# Patch System = Deployment tool (only changed files)

# দুইটা একসাথে use করতে পারেন ✓
```

---

### ❓ Rollback করবে কিভাবে?

**উত্তর:** Backup নিয়ে রাখুন।

```bash
# Deploy এর আগে
cp -r html html-backup-$(date +%Y%m%d-%H%M%S)

# যদি কিছু ভুল হয়
rm -rf html
mv html-backup-* html
```

---

### ❓ Patch size কত হতে পারে?

**উত্তর:** কত ফাইল edit করেছেন তার উপর।

```
1 file change    → 2-5 KB
10 files change  → 20-50 KB
100 files change → 200-500 KB

vs
Full project     → 50-500 MB (!!!)
```

---

## 🎯 Quick Reference

### দৈনিক কাজ (Daily Routine)

```bash
# === সকালে (Development start) ===
# কাজ শুরু করুন...

# === বিকেলে (Development done) ===
php artisan patch:update

# === Deploy এর সময় ===
php artisan patch:wrap --zip-name="update-$(date +%Y%m%d)" \
                      --update-timestamp \
                      --cleanup

# === Production deploy ===
scp patches/update-*.zip server:/var/www/
ssh server "cd /var/www && unzip -o update-*.zip && php artisan cache:clear"
```

---

### সব থেকে গুরুত্বপূর্ণ কমান্ড

```bash
# ⭐ ONE COMMAND FOR EVERYTHING ⭐
php artisan patch:update && \
php artisan patch:wrap --update-timestamp --cleanup
```

---

## 📞 সাহায্য লাগলে

### সিস্টেম চেক করুন

```bash
# Commands available?
php artisan list | grep patch

# Config check
php artisan tinker
>>> config('patch.enabled')
=> true
>>> config('patch.scan_paths')
=> [...]
>>> config('patch.last_patch_at')
=> "2026-03-31 10:00:00"

# Database check
php artisan tinker
>>> App\Models\Patch::count()
=> 5
```

---

### Debug Mode

```bash
# Dry run (save হবে না)
php artisan patch:update --dry-run

# Verbose (details দেখাবে)
php artisan patch:update -v

# Database query দেখুন
php artisan tinker
>>> DB::enableQueryLog();
>>> App\Models\Patch::all();
>>> dd(DB::getQueryLog());
```

---

## 🎉 শেষ কথা

এই Patch System use করলে আপনি পাবেন:

✅ **Fast deployment** - মিনিটেই deploy
✅ **Small patches** - KB তে zip, MB নয়
✅ **Safe & reliable** - Database এ track
✅ **Easy to use** - Simple commands
✅ **Team friendly** - Multiple developers পারস্পরিক ব্যবহার করতে পারবে

বড় E-commerce বা Enterprise প্রজেক্টের জন্য **Perfect solution!** 🚀

---

**Happy Patching!** 🎯✨

---

## 📝 সংস্করণ তথ্য

- **Version:** 2.0.0
- **Last Updated:** 2026-03-31
- **Laravel:** 8.x, 9.x, 10.x, 11.x compatible
- **PHP:** 8.0+

---

## 🔗 সংযুক্ত ফাইলসমূহ

- [app/Console/Commands/PatchUpdate.php](app/Console/Commands/PatchUpdate.php)
- [app/Console/Commands/PatchWrap.php](app/Console/Commands/PatchWrap.php)
- [app/Console/Commands/PatchTimestamp.php](app/Console/Commands/PatchTimestamp.php)
- [app/Models/Patch.php](app/Models/Patch.php)
- [config/patch.php](config/patch.php)
- [database/migrations/..._create_patches_table.php](database/migrations/2026_03_30_181646_create_patches_table.php)

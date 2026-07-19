[**فارسی (Persian)**](README.md) | **English**

# Akaunting Jalali Date Module (v2.x)

This module provides full Jalali (Persian) calendar support for Akaunting 3.x. It integrates seamlessly with Akaunting's core and handles date conversions on the client-side for maximum performance.

<p align="center"><img src="https://aramisteam.com/akaunting-git.jpg" /></p>

## ⚠️ The "License Lock" Problem

Since version 3.1.20, Akaunting has introduced a strict licensing mechanism that automatically **uninstalls or deletes** local modules not found in their official App Store.

We reported this breaking change to the Akaunting team, but unfortunately, the issue was deleted without a response. You can view the deleted reference here: [GitHub Issue #3330](https://github.com/akaunting/akaunting/issues/3330).

**To solve this, we provide an automated "Installer & Patcher" to whitelist this module in your core files.**

---

## Automated Installation (Recommended)

The easiest way to install and protect the module from being auto-deleted is using our smart script:

1. **Download** the `installer.php` file from this repository.
2. **Upload** it to your **Akaunting Root Directory** (where your `.env` file is).
3. **Run** the script via terminal:

```bash
   php installer.php
```

**What this script does:**

- Downloads the latest module source.
- Checks and installs Composer dependencies.
- Patches `/app/Traits/Modules.php` to whitelist the module and prevent auto-uninstall.
- Patches `/app/Traits/Plans.php` to bypass plan limit check (work offline & unlimit company).
- Installs and activates the module.

---

## Manual Installation (Advanced)

If you prefer to install manually, follow these steps:

1. **Clone/Download** the module into `modules/JalaliDate`.
2. **Whitelist the module:** Open `app/Traits/Modules.php` and find `if ($alias == 'core') {`. Change it to:

```php
if ($alias == 'core' || $alias == 'jalali-date') {
```

`This change removes by updating akaunting core.`

3. **Install Dependencies:**

```bash
cd modules/JalaliDate && composer install
```

4. **Activate:**

```bash
php artisan module:install JalaliDate 1
php artisan optimize:clear
```

## Bypass plan limit check

Akaunting limitted companies number by checking API key. If the connection to akaunting server failes, It prevent creating new invoice/user. To work akaunting without Internet (offline) and unlimit companies, we bypassed plans checking.

**Bypass plan limits:** Open `app/Traits/Plans.php` and find `$key = 'plans.limits';`. Change it to:

```php
$key = 'plans.limits';

$unlimit = new \stdClass();
$unlimit->action_status = true;
$unlimit->view_status = true;
$unlimit->message = "";

$data = new \stdClass();
$data->user = $unlimit;
$data->company = $unlimit;
$data->invoice = $unlimit;

return Cache::remember($key, Date::now()->addHour(), fn() => $data);
```

`This change removes by updating akaunting core.`


---

## Features

- **Dual Date Picker:** Toggle between Gregorian and Jalali on the fly.
- **Client-side Conversion:** No server-side data manipulation.
- **Persian Invoice Template:** Beautiful invoices with **Vazirmatn** font.
- **Compatibility:** Tested up to Akaunting v3.2.0.

## License

MIT License.

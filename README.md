# Showtime

A WordPress plugin for tour date management. Add, manage, and display concert and event dates with a fully customizable design system.

Built by [more than ads GmbH & Co. KG](https://morethanads.de).

---

## Features

- **Custom Post Type** — `shows` CPT with ACF-powered fields
- **Shortcode** — `[showtime]` with configurable limit and past-show toggle
- **iCal Export** — "Add to Calendar" link on every show row
- **Presale Countdown** — live countdown timer until presale start
- **Design System** — fully tokenized CSS with live preview, presets, and JSON export/import
- **Bandsintown Sync** — manual and automatic daily import via WP-Cron
- **Duplicate Shows** — clone any show as a draft with one click
- **Admin Dashboard** — upcoming shows overview, sync status, and shortcode generator

---

## Requirements

- WordPress 6.0+
- PHP 8.0+
- [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/) — free or Pro

---

## Installation

1. Upload the `showtime` folder to `/wp-content/plugins/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Install and activate **Advanced Custom Fields** if not already present
4. Go to **Showtime → Dashboard**

> **Note:** If you are upgrading from a version that used `functions.php` as the main plugin file, deactivate the old plugin first, then activate `showtime.php`.

---

## Shortcode

```
[showtime]
[showtime limit="10"]
[showtime limit="5" show_past="true"]
```

| Attribute   | Default         | Description                              |
|-------------|-----------------|------------------------------------------|
| `limit`     | Settings value  | Number of shows visible before "All Dates" button |
| `show_past` | `false`         | Include past shows in the list           |

---

## Show Fields

Each show supports the following fields:

| Field          | Type        | Description                                      |
|----------------|-------------|--------------------------------------------------|
| Show Date      | Date Picker | Required. Used for sorting and past-show logic.  |
| Line 1         | Text        | Primary info line (e.g. venue or city name).     |
| Line 2         | Text        | Secondary info line.                             |
| Country        | Text        | 3-letter country code, e.g. `DEU`, `USA`, `GBR` |
| Ticket URL     | URL         | Link for the ticket button.                      |
| Button Label   | Text        | Custom button text. Defaults to `Buy Tickets`.   |
| Hide Button    | Toggle      | Hides the ticket button entirely when enabled.   |
| Status         | Select      | `On Sale` · `Sold Out` · `Cancelled` · `Postponed` |
| Label / Badge  | Text        | Optional badge, e.g. `Special Guest`.            |
| Presale Date   | Date Picker | Shows a live countdown until this date.          |
| Highlight      | Toggle      | Highlights the row in the frontend list.         |

---

## Design System

Navigate to **Showtime → Design** to customize the visual appearance.

### Brand Tokens

| Token            | Description                               |
|------------------|-------------------------------------------|
| Accent Color     | Primary color — buttons, date, badges     |
| Background Color | Page background (used for surface tints)  |
| Text Color       | Primary text color                        |
| Display Font     | CSS font-stack for date and Line 1        |
| UI Font          | CSS font-stack for labels and buttons     |
| Border Radius    | 0 = sharp · 16 = fully rounded            |

### Button Tokens

Background, text color, hover states, border width/color, and padding are all configurable independently.

### Presets

Four built-in presets: **Dark Metal**, **Punk**, **Electronic**, **Classic Rock**.

### Export / Import

Design settings can be exported as a JSON file and re-imported on any installation.

---

## Bandsintown Integration

1. Go to **Showtime → Settings**
2. Enter your **Artist Name** (exact match as on Bandsintown)
3. Enter your **App ID**
4. Optionally enable **Auto-Sync** (daily via WP-Cron)

Sync can also be triggered manually from the Dashboard. The 5 most recent sync runs are logged with timestamps and counts.

---

## File Structure

```
showtime/
├── showtime.php                  # Main plugin file
├── includes/
│   ├── core/
│   │   ├── post-type.php         # CPT registration
│   │   ├── status.php            # Status helpers & ticket button
│   │   └── ical.php              # iCal download endpoint
│   ├── admin/
│   │   ├── columns.php           # List table columns + duplicate action
│   │   ├── dashboard.php         # Admin dashboard page
│   │   └── settings.php          # General & Bandsintown settings
│   ├── frontend/
│   │   └── shortcode.php         # [showtime] shortcode
│   ├── integrations/
│   │   └── bandsintown.php       # Bandsintown API sync
│   └── design/
│       ├── page.php              # Design System admin page
│       └── system.php            # Token computation & CSS output
├── acf/
│   └── group_shows_fields.json   # ACF field group (auto-loaded)
├── assets/
│   ├── admin/
│   │   ├── admin.js
│   │   └── admin.css
│   └── frontend/
│       ├── showtime.js
│       └── showtime.css
└── README.md
```

---

## Changelog

### 1.4.0
- Added duplicate/clone row action for shows
- Added per-show `Button Label` and `Hide Button` fields
- Renamed `Venue` / `City` fields to `Line 1` / `Line 2` for flexible use
- Reorganized file structure into `core/`, `admin/`, `frontend/`, `integrations/`, `design/`
- Renamed main plugin file from `functions.php` to `showtime.php`
- Full English UI (removed all German strings from admin)

### 1.3.2
- Design System with brand tokens, presets, live preview, export/import
- Bandsintown sync with manual trigger and cron schedule
- Presale countdown
- iCal export
- Highlight toggle per show

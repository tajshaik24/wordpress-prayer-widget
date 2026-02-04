# Prayer Times Widget for WordPress

A WordPress plugin that displays Islamic prayer times (Salah) and Iqamah timings using the Masjidi API. Supports single or dual masjid display with a modern, responsive design.

**Version:** 2.0.2
**Author:** Masjidi
**License:** GPLv2+
**Requires WordPress:** 5.0+
**Tested up to:** 6.4

## Features

- **Five Daily Prayers** - Displays Azan (start) and Iqamah (congregation) times for Fajr, Dhuhr, Asr, Maghrib, and Isha
- **Dual Masjid Support** - Show prayer times for two masjids side-by-side
- **Active Prayer Highlighting** - Current/upcoming prayer is highlighted with customizable colors
- **Jumu'ah Times** - Supports up to 3 Friday prayer times with Talk and Prayer labels
- **Gregorian & Hijri Dates** - Shows current date in both calendars
- **Sunrise/Sunset Times** - Displays Shuruq and Maghrib times
- **Iqamah Change Alerts** - Notifies users of upcoming iqamah time changes
- **Monthly Calendar Link** - Direct link to monthly prayer schedule PDF
- **Responsive Design** - Works on desktop, tablet, and mobile devices
- **Timezone Aware** - Automatically respects the masjid's timezone

## Installation

1. Upload the `masjidi` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins** menu in WordPress
3. Navigate to **Settings → Masjidi**
4. Enter your Primary Masjid ID (obtain from MasjidiApp)
5. Optionally configure a Secondary Masjid ID for dual display
6. Configure your API key
7. Customize highlight colors if desired
8. Save settings

## Configuration

Access settings via **Settings → Masjidi** in the WordPress admin panel.

### Settings Options

| Setting | Description | Default |
|---------|-------------|---------|
| Primary Masjid ID | Required - identifies your masjid from Masjidi API | 3443 |
| Primary Display Name | Optional custom name (defaults to API name) | - |
| Secondary Masjid ID | Optional - for dual masjid display | - |
| Secondary Display Name | Optional custom name for secondary masjid | - |
| Masjidi API Key | Authentication key for API access | Test key |
| Highlight Color | Background color for active prayer row | #1e7b34 |
| Highlight Text Color | Text color for active prayer | #ffffff |

### Getting Your Masjid ID

Your Masjid ID can be obtained from the MasjidiApp platform. Contact MasjidiApp support for your production API key.

## Usage

### Shortcode

Add the prayer times widget to any page or post using the shortcode:

```
[masjidi_prayer_times]
```

### Widget

1. Go to **Appearance → Widgets**
2. Find "Prayer Times Widget"
3. Drag it to your desired widget area
4. Optionally set a custom title
5. Save

### Legacy Shortcode

For backwards compatibility:

```
[single_view_calendar]
```

## Customization

### CSS Classes

Override default styles using these CSS classes:

| Class | Description |
|-------|-------------|
| `.mptsi-widget` | Main container |
| `.mptsi-header` | Title and date section |
| `.mptsi-table` | Prayer times table |
| `.mptsi-row` | Prayer row |
| `.mptsi-row.active` | Currently active prayer |
| `.mptsi-row.header` | Header row |
| `.mptsi-name` | Prayer name with icon |
| `.mptsi-time` | Azan time column |
| `.mptsi-iqamah` | Iqamah time column |
| `.mptsi-jumuah` | Jumu'ah section |
| `.mptsi-sun` | Sunrise/sunset section |
| `.mptsi-alert` | Change notification banner |
| `.mptsi-link` | Monthly calendar link |

### Responsive Breakpoints

- **Desktop:** Max-width 420px container
- **Tablet (481-1024px):** Adjusted font sizes
- **Mobile (≤480px):** Full-width, reduced padding

## API Integration

The plugin fetches prayer times from the Masjidi API:

```
https://api.masjidiapp.com/v2/masjids/{masjid_id}
```

### Data Retrieved

- Prayer start times (Fajr, Dhuhr, Asr, Maghrib, Isha)
- Iqamah times for all prayers
- Jumu'ah times (up to 3)
- Sunrise (Shuruq)
- Hijri date
- Masjid timezone
- Upcoming iqamah changes

## File Structure

```
masjidi/
├── masjidi.php                    # Main plugin file
├── uninstall.php                  # Uninstall handler
├── readme.txt                     # Plugin readme
├── LICENSE.txt                    # GPL 2.0 license
├── admin/
│   └── admin_page/
│       └── masjidi_details.php    # Settings page UI
├── includes/
│   ├── classes/                   # PHP classes
│   │   ├── classCweb.php          # Core plugin class
│   │   ├── class-cweb-loader.php  # Hook orchestrator
│   │   ├── class-cweb-public.php  # Public functionality
│   │   └── class-Plugin-admin.php # Admin functionality
│   └── function/
│       └── functions.php          # Helper functions
└── public/
    └── short-codes.php            # Shortcode definitions
```

## Troubleshooting

### Prayer times not showing

- Verify Masjid ID in settings (must be valid ID from MasjidiApp)
- Check API key is correct
- Verify server can reach `api.masjidiapp.com`
- Check WordPress error logs for API errors

### Colors not applying

- Ensure both highlight colors are set in admin settings
- Check for conflicting theme CSS
- The plugin uses `!important` to override theme styles

### Wrong times displayed

- Verify timezone setting in MasjidiApp
- Check server timezone configuration
- Masjid timezone is pulled from the API automatically

### Widget not appearing

- Ensure shortcode is correctly placed: `[masjidi_prayer_times]`
- Check widget is properly registered in Appearance → Widgets
- Verify no PHP errors in error logs

## Requirements

- WordPress 5.0 or higher
- PHP 7.0+
- Server access to `api.masjidiapp.com`

## Changelog

### 2.0.2
- Fixed minor styling issues

### 2.0.1
- Added preview functionality
- Fixed styling issues

### 2.0.0
- Complete rewrite with Masjidi API integration
- Added dual masjid support
- Modern card-based UI design
- Enhanced responsive layout
- Simplified configuration

## License

This plugin is licensed under the GPLv2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## Support

For issues and feature requests, please contact MasjidiApp support or submit an issue to this repository.

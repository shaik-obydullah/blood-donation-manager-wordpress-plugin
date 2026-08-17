# Blood Donation Manager

![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-orange?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-GPL%20v2-green)
![Version](https://img.shields.io/badge/Version-1.0.0-red)

![Status](https://img.shields.io/badge/Status-Active-brightgreen)
![PHPStan](https://img.shields.io/badge/PHPStan-Level%204-yellow)
![WPCS](https://img.shields.io/badge/WordPress-Coding%20Standards-blue)
![Responsive](https://img.shields.io/badge/Mobile-Responsive-teal)
![AJAX](https://img.shields.io/badge/AJAX-Powered-orange)

![Donor Registration](https://img.shields.io/badge/Donor%20Registration-Supported-brightgreen)
![Blood Requests](https://img.shields.io/badge/Blood%20Requests-Supported-brightgreen)
![Blood Banks](https://img.shields.io/badge/Blood%20Banks-Supported-brightgreen)
![Email Notifications](https://img.shields.io/badge/Email%20Notifications-Supported-brightgreen)
![Blood Compatibility](https://img.shields.io/badge/Blood%20Compatibility-Supported-brightgreen)

---

## Overview

A complete WordPress-based blood donation management platform with donor registration, donation requests, blood bank listings, and automatic blood type compatibility matching. Connect donors with those in need and save lives.

## Features

| Feature | Description |
|---------|-------------|
| **Donor Registration** | Complete donor profiles with blood type, medical info, and availability tracking |
| **Blood Requests** | Urgency-based request system (Normal, Urgent, Critical) with status tracking |
| **Blood Bank Directory** | Searchable directory with contact info, hours, and location |
| **Compatibility Engine** | Automatic blood type matching to find compatible donors |
| **Email Notifications** | Welcome emails for donors, request alerts to compatible donors |
| **Admin Dashboard** | Statistics, charts, donor/request management |
| **Shortcodes** | 6 shortcodes for flexible frontend placement |
| **Responsive Design** | Works perfectly on desktop, tablet, and mobile |
| **REST API** | WordPress REST API support for custom post types |
| **Security** | Nonces, prepared statements, input sanitization |

## Screenshots

| Dashboard | Donor Registration | Blood Requests |
|-----------|-------------------|----------------|
| ![Dashboard](https://via.placeholder.com/400x300/c62828/ffffff?text=Dashboard) | ![Donor Form](https://via.placeholder.com/400x300/1565c0/ffffff?text=Donor+Form) | ![Blood+Requests](https://via.placeholder.com/400x300/2e7d32/ffffff?text=Blood+Requests) |

## Installation

### Method 1: Manual Installation

1. Download the plugin and theme files
2. Upload `blood-donation-manager` to `/wp-content/plugins/`
3. Upload `blood-donation-theme` to `/wp-content/themes/`
4. Activate the plugin via **Plugins > Installed Plugins**
5. Activate the theme via **Appearance > Themes**
6. Configure settings at **Blood Donation > Settings**

### Method 2: Docker (Development)

```bash
docker-compose up -d
```

WordPress admin: `http://localhost:8080/wp-admin`

## Shortcodes

| Shortcode | Description | Page |
|-----------|-------------|------|
| `[bdm_donor_registration]` | Donor signup form | Registration Page |
| `[bdm_donation_request]` | Blood request form | Request Page |
| `[bdm_blood_banks]` | Blood bank directory | Blood Banks Page |
| `[bdm_donors_list]` | Available donors list | Donors Page |
| `[bdm_blood_requests]` | Active blood requests | Requests Page |
| `[bdm_dashboard]` | Statistics dashboard | Homepage/Sidebar |

## Configuration

Navigate to **Blood Donation > Settings** to configure:

| Setting | Default | Description |
|---------|---------|-------------|
| Notification Email | admin_email | Admin notification recipient |
| Enable Notifications | Yes | Toggle email notifications |
| Minimum Weight | 50 kg | Minimum donor weight |
| Minimum Age | 18 years | Minimum donor age |
| Min Days Between Donations | 90 days | Cooldown period |
| Custom Message | Empty | Shown on registration form |
| Donor Email Subject | Welcome message | Welcome email subject |
| Request Email Subject | Blood needed | Request notification subject |

## Database Schema

### Donors Table (`{prefix}bdm_donors`)
```
id, user_id, first_name, last_name, email, phone, blood_type,
date_of_birth, gender, weight, address, city, state, zip_code,
country, last_donation_date, medical_conditions, is_available,
created_at, updated_at
```

### Requests Table (`{prefix}bdm_requests`)
```
id, requester_name, requester_email, requester_phone, patient_name,
blood_type_needed, units_needed, hospital_name, hospital_address,
city, urgency, needed_by, additional_info, status,
created_at, updated_at
```

### Blood Banks Table (`{prefix}bdm_blood_banks`)
```
id, name, description, address, city, state, zip_code, country,
phone, email, website, latitude, longitude, operating_hours,
is_active, created_at, updated_at
```

## Blood Type Compatibility

| Recipient | Compatible Donors |
|-----------|-------------------|
| A+ | A+, A-, O+, O- |
| A- | A-, O- |
| B+ | B+, B-, O+, O- |
| B- | B-, O- |
| AB+ | All (Universal Recipient) |
| AB- | A-, B-, AB-, O- |
| O+ | O+, O- |
| O- | O- (Universal Donor) |

## Security

- WordPress nonce verification on all AJAX requests
- Input sanitization using WordPress sanitization functions
- Prepared SQL statements to prevent injection
- Admin pages restricted to `manage_options` capability
- Server-side validation for all form submissions

## Technical Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.0+, WordPress 6.4+ |
| Database | MySQL 5.6+ / MariaDB 10.1+ |
| Frontend | HTML5, CSS3, jQuery |
| Server | Apache 2.4+ / Nginx 1.18+ |
| Dev Environment | Docker + Docker Compose |

## Extending the Plugin

### Add Custom Shortcode
1. Create template: `templates/your-shortcode.php`
2. Register in `__construct()`: `add_shortcode('bdm_your_shortcode', array($this, 'your_method'))`
3. Add class method using `ob_start()` / `ob_get_clean()` pattern

### Add Admin Page
1. Create file: `admin/your-page.php`
2. Register in `admin_menu()`: `add_submenu_page()`
3. Include file in new class method

### Add Email Notification
1. Hook into `ajax-handlers.php`
2. Use `wp_mail()` with HTML headers
3. Make toggleable via `bdm_settings['enable_notifications']`

## File Structure

```
blood-donation-manager/
├── blood-donation-manager.php    # Main plugin file
├── admin/                         # Admin pages
│   ├── dashboard.php
│   ├── donors.php
│   ├── requests.php
│   ├── blood-banks.php
│   └── settings.php
├── templates/                     # Frontend templates
│   ├── donor-registration.php
│   ├── donation-request.php
│   ├── blood-banks-list.php
│   ├── donors-list.php
│   ├── blood-requests-list.php
│   └── dashboard.php
├── includes/
│   └── ajax-handlers.php
└── assets/
    ├── css/
    │   ├── admin.css
    │   └── frontend.css
    └── js/
        ├── admin.js
        └── frontend.js

blood-donation-theme/
├── style.css                      # Theme metadata + CSS
├── functions.php                  # Theme functions
├── header.php
├── footer.php
├── front-page.php
├── page.php
├── single.php
├── index.php
├── archive.php
├── search.php
├── 404.php
├── sidebar.php
└── comments.php
```

## Requirements

- PHP 8.0 or higher
- MySQL 5.6 or higher
- WordPress 6.4 or higher
- Apache 2.4+ or Nginx 1.18+

## Changelog

### 1.0.0 (2024-01-01)
- Initial release
- Donor registration system
- Blood request management
- Blood bank directory
- Email notifications
- Admin dashboard
- 6 shortcodes
- Responsive design

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) file for details.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- **Documentation:** See `AGENTS.md` for codebase overview
- **Issues:** Report bugs via GitHub Issues
- **Email:** support@example.com

## Acknowledgments

- WordPress Community
- Blood donation organizations worldwide
- Open source contributors

---

![Made with WordPress](https://img.shields.io/badge/Made%20with-WordPress-21759b?logo=wordpress&logoColor=white)
![Made with PHP](https://img.shields.io/badge/Made%20with-PHP-777BB4?logo=php&logoColor=white)
![Made with Love](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F-ff69b4)

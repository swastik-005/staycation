# Staycation — Hotel Management Software (HMS)

An end-to-end Hotel Management System hosted on `localhost` via **XAMPP (Apache + MySQL/MariaDB)**, featuring a modern frontend inspired by the [ThreeUI Mara Voss Landing Page](https://threeui.com/landing-pages/mara-voss-landing-page) and a complete administrative operations console.

---

## 🔗 Direct Access Links

- **Staycation Guest Portal**: [http://localhost/hotel_management/](http://localhost/hotel_management/)
- **Guest Folio & Voucher Lookup**: [http://localhost/hotel_management/booking-lookup.php](http://localhost/hotel_management/booking-lookup.php)
- **Staff Operations & Front-Desk Console**: [http://localhost/hotel_management/admin/login.php](http://localhost/hotel_management/admin/login.php)
  - **Username**: `admin`
  - **Password**: `admin123`
- **Database Initializer & Re-seeder**: [http://localhost/hotel_management/setup.php](http://localhost/hotel_management/setup.php)

---

## 🗄️ XAMPP SQL phpMyAdmin Links (View Live Records)

You can view, search, edit, and query every single record registered in the SQL database directly using XAMPP's phpMyAdmin interface:

| SQL Table | Direct phpMyAdmin Link | Description |
|---|---|---|
| **Whole Database** | [http://localhost/phpmyadmin/index.php?route=/database/structure&db=hotel_management_db](http://localhost/phpmyadmin/index.php?route=/database/structure&db=hotel_management_db) | Structure, table count, size, and export tools |
| **`bookings`** | [http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=bookings](http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=bookings) | Every reservation folio, dates, financials, & guest records |
| **`rooms`** | [http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=rooms](http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=rooms) | Room inventory, nightly pricing, status, capacity |
| **`users`** | [http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=users](http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=users) | Staff credentials and administrator roles |
| **`messages`** | [http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=messages](http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=messages) | Inquiries submitted through the guest contact form |
| **`services`** | [http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=services](http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=services) | Curated add-ons (spa, transport, dining) |
| **`reviews`** | [http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=reviews](http://localhost/phpmyadmin/index.php?route=/sql&db=hotel_management_db&table=reviews) | Verified guest ratings and feedback |

---

## 🌟 Software Capabilities

### 1. Front Desk & Operations Dashboard (`/admin/`)
- **Real-Time KPIs**: Total Gross Revenue ($), Total Central Reservations, Room Occupancy Rate (%), and Vacant Room Inventory.
- **Folio Management**: Filter by `Confirmed`, `Checked-in`, `Checked-out`, and `Cancelled`.
- **Walk-in Check-in**: Register front-desk walk-in guests with instant room allocation and payment logging.
- **Room Inventory & Housekeeping**: Add new rooms, update rates, and toggle availability (`Available`, `Occupied`, `Maintenance`).
- **Inquiries Inbox**: Manage inquiries from guests with one-click read/unread status updates.
- **CSV Data Exporter**: Export the entire reservations folio into CSV format.

### 2. Guest-Facing Experience
- **Interactive Three.js 3D WebGL Canvas**: Ambient procedural wave mesh and floating particles.
- **Live Availability Engine**: Filter rooms by check-in, check-out, guest count, and suite categories.
- **Dynamic Pricing Engine**: Automated night count and 12% hospitality tax calculation.
- **Instant Folio Generation**: Generates a unique reference code (`STAY-XXXXXX`) with print/save capabilities.
- **Guest Self-Service Portal**: Guests can view folios and cancel reservations.

---

## 📁 File Locations
- **XAMPP Web Server Directory**: `/Applications/XAMPP/xamppfiles/htdocs/hotel_management/`
- **Workspace Source Directory**: `/Users/banibratadas/Desktop/projects/hotel_management/`



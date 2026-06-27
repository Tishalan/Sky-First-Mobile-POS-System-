<div align="center">

# Sky First Mobile
### Point of Sale & Inventory Management System

**A multi-branch retail management system built for Sky First Mobile shop**
Developed by <a href="https://sky-tec.site/">Sky Tec</a>

<br>

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS5](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)


</div>

---

## Overview

**Sky First Mobile** is a Point of Sale and Inventory Management System built for a mobile phone retail shop operating across two branches. Developed by **Sky Tec**, the system handles day-to-day operations including device sales with IMEI tracking, mobile reload (top-up) management, phone repair billing, credit sales tracking, inventory management, and branch-wise reporting.

> This repository contains the demo version of the system. Sensitive credentials and configurations have been removed.

**Two branch locations served:**

```
Branch 1  —  Main Branch
Branch 2  —  Branch 2
```

---

## System Flow

```
                    [ index.php — Welcome / Landing ]
                                   |
              _____________________|_____________________
             |                                           |
     [ Branch 1 Login ]                        [ Branch 2 Login ]
     login-branch1.php                         login-branch2.php
             |                                           |
    .--------+--------.                       .---------+--------.
    |  billing.php     |                       |  billing_b2.php  |
    |  inventory.php   |                       |  inventory_b2.php|
    |  barcode.php     |                       |  barcode_b2.php  |
    |  reload_mgmt.php |                       |  reload_mgmt_b2  |
    |  reports.php     |                       |  report_b2.php   |
    |  credit_pmts.php |                       |  credit_b2.php   |
    '------------------'                       '------------------'

       Branch 1 Products (branch_id = 1)     Branch 2 Products (branch_id = 2)
       product_id prefix: PM#####            product_id prefix: PB#####
       bill_no prefix: PS-B1-####            bill_no prefix: PS-B2-####
```

---

## User Roles & Access

| Role | Login | Access |
|---|---|---|
| **Branch 1 Admin** | `login-branch1.php` | Full access to Branch 1 — billing, inventory, reload, reports, credit |
| **Branch 2 Admin** | `login-branch2.php` | Full access to Branch 2 — billing, inventory, reload, reports, credit |

Each branch has its own independent session (`$_SESSION['admin_main']` for Branch 1, `$_SESSION['admin2']` for Branch 2), keeping data and access fully isolated.

---

## Key Features

### Login & Branch Selection

The landing page (`index.php`) presents a clean animated entry point with floating particles on a blue-to-dark gradient background. Each branch has its own login portal with separate session management.

```
User visits index.php
       |
       |-- Selects Branch 1  -->  login-branch1.php  -->  billing.php
       |
       |-- Selects Branch 2  -->  login-branch2.php  -->  billing_b2.php
```

---

### POS Billing System

The billing interface is the primary daily-use screen for each branch. Both branches share the same billing logic but operate on completely separate data:

- Live product search by name or barcode with instant dropdown
- Auto-generated bill numbers with branch prefix (`PS-B1-0001` for Branch 1, `PS-B2-0001` for Branch 2)
- **IMEI number capture** per sold device — stored directly against each bill item
- Item-level discount application
- Cash and Card payment method support
- Supports additional billing lines for:
  - **Phone Repair charges** (tracked separately in the bill)
  - **Mobile Reload / Top-up amount** (tracked with profit per bill)
- Balance calculation (overpayment or underpayment tracked)
- Bill print view for receipts

```
Staff logs in
      |
      +--> Search product by name or barcode (live AJAX)
      |         |
      |         +--> Add to bill + set quantity + apply discount
      |         |
      |         +--> Enter IMEI number (for devices)
      |
      +--> Add phone repair charge (if applicable)
      |
      +--> Add reload amount (if applicable)
      |
      +--> Choose payment method (Cash / Card)
      |
      +--> Generate Bill  -->  Auto bill number  -->  Print receipt
```

---

### Mobile Reload / Top-Up Management

A dedicated reload management module tracks mobile network top-up inventory and profit for each branch:

- Add **reload providers** per branch (e.g., Dialog, Airtel, Mobitel)
- Record **reload purchases** from providers — amount, cost, and current balance
- Balance is auto-decremented as reloads are sold via the billing screen
- **Reload profit** is calculated per bill (`reload amount sold` minus `cost`) and stored in the bills table
- Purchases with zero balance are automatically removed from the active list
- Separate reload management for Branch 1 (`reload_management.php`) and Branch 2 (`reload_management_b2.php`)

```
Provider purchase recorded  -->  balance set
           |
           +--> Reload sold via billing.php
           |         |
           |         +--> Balance decremented from provider purchase
           |         |
           |         +--> Reload profit saved to bills.reload_profit
           |
           +--> Zero-balance purchases auto-cleared
```

---

### Inventory Management

Each branch manages its own product catalog independently:

- Add, edit, and delete products per branch
- Auto-generated product IDs (`PM#####` for Branch 1, `PB#####` for Branch 2)
- Fields per product: name, category, original price, sale price, stock quantity, barcode, colour, photo upload
- Stock auto-decrements on each sale
- Product image uploads stored in `/Uploads/`
- Inventory view with stock levels, sold count, and last sold date
- Separate inventory views: `inventoryb1_show.php` (Branch 1 read view), `inventoryb2_show.php` (Branch 2 read view)

---

### Barcode Scanner

Both branches support barcode-based product lookup:

- Staff can scan or type a product barcode to instantly load product details into the billing screen
- Supports standard barcode formats used on mobile accessories and devices
- Branch-specific: `barcode.php` (Branch 1), `barcode_b2.php` (Branch 2)

---

### Credit Sales & Payment Tracking

Credit sales are tracked and managed per branch:

- Bills marked as `Credit` payment show a negative balance until settled
- Credit customers list shows outstanding balances
- Partial payment recording updates running balance
- Branch 1: `credit_payments.php` | Branch 2: `credit_customers_b2.php`
- Combined cross-branch credit view available (filter by Branch 1, Branch 2, or Both)

---

### Reports & Analytics

Sales reports are available per branch with multiple filters:

- Daily, range-based, and overall sales summaries
- Breakdown by: product sales, reload profit, phone repair charges
- Total revenue, total discount, total profit per period
- Credit vs cash vs card payment method breakdown
- Branch 1: `reports.php` | Branch 2: `report_b2.php`

---

## Project Structure

```
Sky First Mobile/
|
|-- index.php                    # Landing page / branch selection
|-- db_connect.php               # Shared database connection
|-- login-branch1.php            # Branch 1 login
|-- login-branch2.php            # Branch 2 login
|-- changepassword.php           # Branch 1 password change
|-- change_password.php          # Branch 2 password change
|
|-- billing.php                  # Branch 1 — POS billing
|-- billing_b2.php               # Branch 2 — POS billing
|-- bill_print_b1.php            # Branch 1 — bill print view
|
|-- inventory.php                # Branch 1 — inventory management (add/edit/delete)
|-- inventory_b2.php             # Branch 2 — inventory management
|-- inventoryb1_show.php         # Branch 1 — inventory read view
|-- inventoryb2_show.php         # Branch 2 — inventory read view
|
|-- barcode.php                  # Branch 1 — barcode scanner
|-- barcode_b2.php               # Branch 2 — barcode scanner
|
|-- reload_management.php        # Branch 1 — reload/top-up management
|-- reload_management_b2.php     # Branch 2 — reload/top-up management
|
|-- credit_payments.php          # Credit tracking (cross-branch view)
|-- credit_customers_b2.php      # Branch 2 — credit customers
|
|-- reports.php                  # Branch 1 — sales reports
|-- report_b2.php                # Branch 2 — sales reports
|
|-- get_product.php              # AJAX — product lookup by ID
|-- get_new_product_id.php       # AJAX — generate next product ID
|
|-- database/
|   |-- sky_mobile.sql           # Full database schema + sample data
|   |-- sky first mobile.sql     # Alternate schema file
|
|-- assets/
|   |-- images/
|   |   |-- logo.jpg             # Sky First Mobile logo
|   |   |-- skylogo.jpg          # Sky Tec logo
|   |-- js/
|       |-- utils.js             # Shared JS utilities
|
|-- Uploads/                     # Product image uploads
    |-- .gitkeep
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 7.4+ (procedural, session-based auth) |
| Database | MySQL via MySQLi — 7 tables, branch_id isolation |
| Frontend | HTML5, CSS3, Vanilla JavaScript, AJAX |
| UI Framework | Bootstrap, Font Awesome 6, Segoe UI / system fonts |
| Theme | Blue to dark gradient, floating particle animation |

---

## Database Schema

Database: `sky_first_mobile` — 7 tables

| Table | Purpose |
|---|---|
| `branches` | Branch registry (Main Branch, Branch 2) |
| `users` | Login credentials per branch (hashed passwords) |
| `products` | Product catalog — shared table, isolated by `branch_id` |
| `bills` | Bill records — includes reload profit, repair charge, payment method |
| `bill_items` | Line items per bill — includes IMEI per device sold |
| `reload_providers` | Mobile network providers per branch (Dialog, Airtel, Mobitel) |
| `reload_purchases` | Reload stock purchased from providers — tracks balance |

**Branch isolation approach:** Unlike Kids Berry (which uses separate tables per branch), Sky First Mobile uses a single shared table for `products`, `bills`, and `bill_items`, isolating data via a `branch_id` column. This keeps the schema lean while still fully separating branch data at the query level.

---

## Local Setup Guide

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- XAMPP / WAMP / LAMP or any local PHP server

### Installation Steps

**1. Clone the repository**
```bash
git clone https://github.com/your-username/sky-first-mobile.git
```

**2. Move to server root**

Copy the `Sky First Mobile` folder into your XAMPP/WAMP `htdocs` or `www` directory.

**3. Import the database**

Open phpMyAdmin, create a new database named `sky_first_mobile`, then import:
```
database/sky_mobile.sql
```

**4. Configure database connection**

Default credentials in `db_connect.php`:
```php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "sky_first_mobile";
```

Update if your local environment differs.

**5. Launch the system**

Navigate to:
```
http://localhost/Sky First Mobile/index.php
```

Select your branch and log in.

**Default credentials (from database):**
```
Branch 1 — username: admin    password: (set in users table)
Branch 2 — username: admin2   password: (set in users table)
```



## Developer

<div align="center">

Developed by <a href="https://sky-tec.site/"> Sky Tec</a>

This is a client-commissioned project built for **Sky First Mobile** shop.
The repository reflects the demo version of the system.
Sensitive credentials and client-specific configurations have been removed.

<br>

Built with dedication by **Sky Tec** for **Sky First Mobile**

</div>

# Maize Supply, Stock, and Order Management System

A web-based platform tailored for managing agricultural supply chains, tracking grain stock, automating buyer order fulfillments, and calculating farmer payouts with business-compliant bonus calculations. The system is designed for the Ampara district agricultural division to streamline maize distribution and farmer financial tracking.

---

## 🚀 Key Features by Roles

### 1. Administrator Dashboard (`admin/`)
* **Supply Management**: Manage maize deliveries submitted by farmers, edit quantities, and track delivery logs.
* **Farmer & Buyer Directory**: Maintain records of registered farmers, land sizes, buyer coordinates, and partner companies.
* **Land Administration**: Manage farmer lands, register sizes, and enforce standard units (typically in Acres).
* **Maize Variety Management**: Define variety types (e.g., *Hybrid*, *Normal*) and manage base prices per kilogram.
* **Stock & Inventory**: Keep an aggregated view of stock per company and variety, with automated synchronization mechanisms (`sync_stock.php`).
* **Order & Approval Workflow**: Approve or reject buyer orders using First-In-First-Out (FIFO) supply allocation logic.
* **Payment Reports & Receipts**:
  * Track basic amounts, bonuses, and net payments.
  * Generate printable payment reports (`payment_report.php`).
  * Issue official payment receipts for individual farmers (`payment_receipt.php`).

### 2. Farmer Portal (`farmer/`)
* **Dashboard Overview**: Access a summary of total land holdings, total maize quantity supplied, and total income generated (basic and bonus calculations).
* **Land Profiles**: View registered lands and parcel details.
* **Supply Log**: View complete chronological supply logs.
* **Income & Payouts**: View approved payment details, basic income, and individual bonus calculations.
* **Bill Viewer**: Download/view detailed billing invoice slips for supplies.

### 3. Buyer Portal (`buyer/`)
* **Stock Status**: Track real-time available stock of different maize varieties.
* **Order Placement**: Submit purchase orders specify required quantities and crop varieties.
* **Order Tracking**: Monitor the status of orders (Pending, Approved, Rejected).

---

## 🛠️ Technology Stack
* **Backend**: PHP 7.4+ (Structured MVC-style vanilla scripts with PDO mysql connection blocks)
* **Database**: MySQL / MariaDB
* **Design & Styling**: Custom Responsive CSS (Agricultural Theme) & Bootstrap 5
* **Security & Reliability**:
  * Session-based Authentication & Access Control
  * Secure Password Hashing (`password_hash` & `password_verify`)
  * Database transactions to ensure financial record integrity

---

## 🗄️ Database Architecture

The system database schema consists of the following primary tables:
* `users`: Credentials and roles (`admin`, `farmer`, `buyer`).
* `farmer`: Personal details and contact info.
* `company`: Private buyers/corporations (e.g., *Ampro Maize Corp*).
* `buyer`: Buyer profile linked to a company.
* `land`: Area of cultivation in Acres linked to farmers.
* `variety`: Types of maize and current buying price per KG.
* `maize`: Daily supplies delivered by farmers.
* `stock`: Aggregated stock tracking available varieties in system.
* `orders`: Order requirements and approval status of buyers.
* `payment`: Financial payout records containing basic payments and calculated bonuses.
* `order_supply`: Correlation table allocating specific maize supplies to approved orders (FIFO tracking).

---

## ⚙️ Installation & Setup

1. **Prerequisites**:
   * Install XAMPP, WAMP, or any Local Web Server running Apache & MySQL.
   * Place the project directory under root folder (e.g., `htdocs/maize_system`).

2. **Database Configuration**:
   * Edit `config/db.php` if you have a custom MySQL host, database name, username, or password:
     ```php
     $host = 'localhost';
     $dbname = 'maize_system';
     $username = 'root';
     $password = '';
     ```

3. **Database Initialization**:
   * Run the CLI setup script or access it in your browser:
     * **CLI Command**:
       ```bash
       php setup_db.php
       ```
     * **Browser Access**: Navigate to `http://localhost/maize_system/setup_db.php`.
   * This automatically creates the `maize_system` database, ensures all tables are set up, creates default varieties (*Hybrid*, *Normal*), and creates the default admin user:
     * **Username**: `admin`
     * **Password**: `admin123`

---

## 💰 Business Payout & Farmer Bonus Rules

When a buyer submits an order and the admin approves it:
1. **FIFO Supply Allocation**: The system traverses individual farmer supplies (`maize` table) matching the ordered variety in ascending chronological order (`supply_date ASC`). It allocates available stock of those supplies to the order.
2. **Order Eligibility**: Payment is only generated for approved orders.
3. **Farmer Bonus Eligibility**: During payment calculation (triggered upon order approval), a farmer gets a **2% bonus** on the basic payment amount under the following business rules:
   * The buyer order must be a **Large Order** (Total quantity required $\ge 30,000$ KG).
   * The farmer's total allocated supply quantity for that specific order must be $\ge 10,000$ KG.
   * The supply must originate from a land parcel that is $\ge 4.0$ Acres.
   * If a farmer allocates supplies from multiple lands to the same order, the 2% bonus is **only computed** on the portion of supplies originating from lands matching the $\ge 4.0$ Acres rule.

---

## 🧬 Diagnostic & Verification Tools

The system includes built-in verification scripts and utilities to enforce calculations and database integrity:

### 1. Automated Bonus Calculation Verification
Run `verify_bonus_calculations.php` to execute the automated test scenarios. It ensures code compliance with FIFO allocations, partial bonuses across multiple lands, duplicate payment checks, and insufficient stock errors:
* **Command**:
  ```bash
  php verify_bonus_calculations.php
  ```
The script runs the following scenarios in a database transaction and rolls back to protect development data:
* **Scenario A**: Full bonus qualification.
* **Scenario B**: No bonus due to small land size (< 4 acres).
* **Scenario C**: No bonus due to small allocated quantity (< 10,000 KG).
* **Scenario D**: No bonus due to order not being a Large Order (< 30,000 KG).
* **Scenario E**: Partial bonus calculation across multiple lands (one large, one small).
* **Scenario F**: Duplicate payment prevention checks.
* **Scenario G**: Order rejection and reversal on insufficient stock limits.

### 2. General Integrity and Migrations
* `sync_stock.php`: Corrects stock inventory levels by aggregating quantities from the `maize` table and deducting quantities from approved `order_supply`.
* `deduplicate_varieties.php`: Clean-up script to remove duplicate entries in case of conflicting variety types.
* `migrate_payments.php`: Migration script to restict payment schemas and calculate proper historical payout records.
* `backup_database.php`: Creates database dump backups for system restoration.

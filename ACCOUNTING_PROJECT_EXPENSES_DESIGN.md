# Project Expense Accounting System Design
## Real Estate ERP - Double Entry Accounting

---

## Table of Contents
1. [Chart of Accounts Overview](#chart-of-accounts-overview)
2. [Account Hierarchy](#account-hierarchy)
3. [Account Purposes & Real-World Examples](#account-purposes--real-world-examples)
4. [Journal Entry Examples](#journal-entry-examples)
5. [Database Design](#database-design)
6. [Project-wise Expense Tracking](#project-wise-expense-tracking)
7. [Report Design & Queries](#report-design--queries)
8. [Best Practices](#best-practices)
9. [Common Mistakes to Avoid](#common-mistakes-to-avoid)

---

## 1. Chart of Accounts Overview

### Complete Expense Structure

```
EXPENSES (1000-1999)
│
└── PROJECT EXPENSES (1100-1199)
    │
    ├── LABOR COST (1110-1119)
    │   ├── Skilled Labor (1111)
    │   ├── Unskilled Labor (1112)
    │   └── Supervisor/Management Labor (1113)
    │
    ├── MATERIAL CONSUMPTION (1120-1129)
    │   ├── Concrete (1121)
    │   ├── Steel (1122)
    │   ├── Brick & Masonry (1123)
    │   ├── Electrical Materials (1124)
    │   ├── Plumbing Materials (1125)
    │   └── Finishing Materials (1126)
    │
    ├── UTILITY BILLS (1130-1139)
    │   ├── Electricity (1131)
    │   ├── Water (1132)
    │   └── Gas (1133)
    │
    ├── EQUIPMENT RENT (1140-1149)
    │   ├── Machinery Rent (1141)
    │   ├── Vehicle Rent (1142)
    │   └── Tool & Equipment Rent (1143)
    │
    ├── TRANSPORTATION (1150-1159)
    │   ├── Material Transport (1151)
    │   ├── Worker Transport (1152)
    │   └── Equipment Transport (1153)
    │
    └── OTHER EXPENSE (1160-1169)
        ├── Permits & Licenses (1161)
        ├── Site Maintenance (1162)
        ├── Safety Equipment (1163)
        └── Miscellaneous (1164)
```

---

## 2. Account Hierarchy

### Hierarchical Structure

```
Level 1: GROUP (Expense Class)
├── Code: 1000
├── Name: EXPENSES
└── Type: Expense

    Level 2: PARENT ACCOUNT (Category)
    ├── Code: 1100
    ├── Name: PROJECT EXPENSES
    ├── Parent: 1000 (EXPENSES)
    └── Type: Category Header

        Level 3: CHILD ACCOUNT (Detailed Account)
        ├── Code: 1110
        ├── Name: LABOR COST
        ├── Parent: 1100 (PROJECT EXPENSES)
        └── Type: Detail Account (transactable)

            Level 4: SUB-CHILD ACCOUNT (Optional)
            ├── Code: 1111
            ├── Name: Skilled Labor
            ├── Parent: 1110 (LABOR COST)
            └── Type: Leaf Account (transactable)
```

### Database Representation

```
accounts table:
id | code  | name            | type      | parent_id | account_type | is_active
1  | 1000  | EXPENSES        | GROUP     | NULL      | EXPENSE      | true
2  | 1100  | PROJECT EXPENSES| PARENT    | 1         | EXPENSE      | true
3  | 1110  | LABOR COST      | CHILD     | 2         | EXPENSE      | true
4  | 1111  | Skilled Labor   | SUB_CHILD | 3         | EXPENSE      | true
```

---

## 3. Account Purposes & Real-World Examples

### 3.1 LABOR COST (Account Code: 1110)

**Purpose:**
- Track all employee and contractor wages paid for project work
- Includes salaries, daily wages, overtime, and contractor fees
- Essential for project profitability analysis and cost control

**When to Use:**
- Recording wages for construction workers
- Contractor payments for specialized work (plumbing, electrical)
- Supervisor and site manager salaries allocated to project
- Overtime and shift allowances
- Subcontractor labor charges

**When NOT to Use:**
- General office administration staff salaries (use overhead accounts)
- Training and development costs
- Employee benefits and insurance (separate account if significant)
- Vehicle driver wages (sometimes goes to Equipment Rent if part of rental)

**Real-World Example 1: Green City Project - Skilled Labor Payment**

Transaction: Paying ₹100,000 to masons for brick laying work on July 5, 2026

```
Date: 2026-07-05
Reference: Invoice #INV-2026-001 from Construx Labor Services
Description: Skilled mason labor for brick laying - Week 1

Debit (Expense):
    Account: 1111 - Skilled Labor
    Amount: 100,000
    Project: Green City (project_id: 5)
    Description: 10 masons × ₹10,000/day × 1 day
    Dimension: Project = Green City

Credit (Liability/Asset):
    Account: 2010 - Accounts Payable (OR 1000 - Cash)
    Amount: 100,000
    Description: Payment to skilled laborers
```

Journal Entry:
```
Dr. 1111 Skilled Labor                           100,000
    Project = Green City
    Cr. 2010 Accounts Payable                              100,000
```

**Real-World Example 2: Lake View Project - Unskilled Labor**

Transaction: Paying ₹50,000 for general labor (carrying, cleaning, site work)

```
Date: 2026-07-10
Reference: Payroll Sheet #PS-2026-006
Description: General site labor for material handling

Debit (Expense):
    Account: 1112 - Unskilled Labor
    Amount: 50,000
    Project: Lake View (project_id: 8)
    Description: 5 general workers × ₹10,000/day

Credit:
    Account: 1000 - Cash in Hand
    Amount: 50,000
```

---

### 3.2 MATERIAL CONSUMPTION (Account Code: 1120)

**Purpose:**
- Record cost of raw materials consumed in construction
- Represents materials that become part of the project
- Tracks material usage by type for cost analysis
- Supports inventory-to-expense flow

**When to Use:**
- Concrete used in foundation and structure
- Steel reinforcement used in construction
- Bricks, blocks, mortar consumed in walls
- Electrical wires, cables, switches used in wiring
- Plumbing pipes, fittings used in plumbing work
- Paint, tiles, and finishing materials applied to project

**When NOT to Use:**
- Raw materials still in inventory (use Inventory Asset account)
- Office supplies consumed
- Equipment maintenance materials (use Equipment Rent or Repairs)
- Materials for demonstration or training (use separate account)

**Real-World Example 1: Green City - Concrete Consumption**

Transaction: Consuming 50 cubic meters of concrete (valued at ₹25,000/meter) for foundation

```
Date: 2026-07-15
Reference: Material Consumption Report #MCR-001
Description: Ready-mix concrete for foundation work - Phase 1

Debit (Expense):
    Account: 1121 - Concrete
    Amount: 1,250,000 (50m³ × ₹25,000)
    Project: Green City (project_id: 5)
    Description: Concrete for RCC beam construction
    Dimension: Project = Green City, Material Type = Concrete

Credit:
    Account: 1000 - Raw Material Inventory (Asset)
    Amount: 1,250,000
    Description: Concrete consumed from inventory
```

This assumes inventory was already purchased and in stock. The journal entry moves the cost from inventory asset to project expense.

**Real-World Example 2: Lake View - Steel Reinforcement**

Transaction: Consuming 10 tons of steel rebar from inventory for structure

```
Date: 2026-07-20
Reference: Material Requisition #MR-2026-045
Description: Steel rebar for structural columns

Debit (Expense):
    Account: 1122 - Steel Reinforcement
    Amount: 800,000 (10 tons × ₹80,000/ton)
    Project: Lake View (project_id: 8)
    Description: Steel rebar for main structural frame
    Dimension: Project = Lake View

Credit:
    Account: 1000 - Materials Inventory
    Amount: 800,000
    Description: Steel consumed from warehouse stock
```

---

### 3.3 UTILITY BILLS (Account Code: 1130)

**Purpose:**
- Record costs of utilities consumed at project site
- Includes electricity, water, and gas used during construction
- Necessary for accurate project cost tracking
- Supports environmental impact and cost control analysis

**When to Use:**
- Monthly electricity bills for site (equipment power, lighting, etc.)
- Water consumed for construction work and worker use
- Gas used if project has gas-dependent operations
- Temporary utility connections for construction phase

**When NOT to Use:**
- Utilities for office headquarters
- Utilities for permanent buildings after completion
- Equipment fuel (use Equipment Rent or separate fuel account)
- Emergency generator fuel (use Equipment Rent)

**Real-World Example: Green City - Electricity Bill**

Transaction: Monthly electricity bill for construction site

```
Date: 2026-07-31
Reference: Utility Bill #ELEC-2026-07 from State Electricity Board
Description: Electricity consumption - June 2026 for construction site
Amount: ₹45,000 (1500 units × ₹30/unit)

Debit (Expense):
    Account: 1131 - Electricity
    Amount: 45,000
    Project: Green City (project_id: 5)
    Description: Site electricity for June 2026
    Dimension: Project = Green City, Utility Type = Electricity

Credit:
    Account: 2050 - Utility Bills Payable (OR 1000 - Cash)
    Amount: 45,000
    Description: Monthly electricity charge for construction site
```

---

### 3.4 EQUIPMENT RENT (Account Code: 1140)

**Purpose:**
- Record rental costs of machinery and equipment needed for construction
- Includes temporary equipment like cranes, excavators, scaffolding, etc.
- More cost-effective than purchasing for short-term needs

**When to Use:**
- Renting tower crane for lifting operations
- Renting concrete pump for concrete laying
- Renting bulldozer/excavator for earth work
- Renting scaffolding for multi-story construction
- Renting power tools and equipment
- Renting vehicles for material transportation

**When NOT to Use:**
- Owned equipment depreciation (use Depreciation Expense)
- Equipment maintenance (use Repairs & Maintenance)
- Equipment insurance (separate account)
- Fuel for equipment (can be part of rent or separate)

**Real-World Example: Lake View - Crane Rental**

Transaction: Monthly rental of tower crane for lifting operations

```
Date: 2026-07-31
Reference: Rental Agreement #RA-2026-LC-05, Invoice #INV-CRANE-001
Description: Tower crane rental - July 2026 (High capacity, 50-ton)
Rental Period: July 1-31, 2026
Daily Rate: ₹15,000
Days Used: 25 days
Total: ₹375,000

Debit (Expense):
    Account: 1141 - Machinery Rent
    Amount: 375,000
    Project: Lake View (project_id: 8)
    Description: Tower crane rental - July 2026
    Dimension: Project = Lake View, Equipment Type = Crane

Credit:
    Account: 2010 - Accounts Payable (OR 1000 - Cash)
    Amount: 375,000
    Description: Payment for tower crane rental
```

**Contractor Rental Example:**

```
Date: 2026-07-25
Reference: Rental Invoice #INV-EQ-2026-012
Description: Scaffolding rental for external work
Duration: 15 days @ ₹5,000/day = ₹75,000

Debit:
    Account: 1143 - Tool & Equipment Rent
    Amount: 75,000
    Project: Green City (project_id: 5)
    Description: Temporary scaffolding for facade work

Credit:
    Account: 2010 - Accounts Payable
    Amount: 75,000
```

---

### 3.5 TRANSPORTATION (Account Code: 1150)

**Purpose:**
- Track costs of moving materials, equipment, and workers to/from project site
- Essential component of project cost structure
- Impacts project timeline and cost control

**When to Use:**
- Transportation of raw materials from supplier to site
- Transporting finished materials from factory to site
- Transporting equipment to project site
- Worker transport to construction site
- Material and equipment transport between project locations

**When NOT to Use:**
- Office vehicle operations
- Client/visitor transportation
- General company transportation
- Equipment ownership (use Equipment Rent)

**Real-World Example 1: Green City - Material Transport**

Transaction: Truck transport of 50 tons of cement from factory to site

```
Date: 2026-07-22
Reference: Transport Invoice #TRP-2026-0145
Description: Truck transport - Cement from supplier to Green City site
Quantity: 50 tons
Distance: 120 km
Rate: ₹200/ton = ₹10,000/trip + Loading/Unloading ₹5,000
Total: ₹15,000

Debit (Expense):
    Account: 1151 - Material Transport
    Amount: 15,000
    Project: Green City (project_id: 5)
    Description: Cement transportation - 50 tons from supplier
    Dimension: Project = Green City, Material = Cement

Credit:
    Account: 2010 - Accounts Payable (OR 1000 - Cash)
    Amount: 15,000
```

**Real-World Example 2: Lake View - Worker Transport**

Transaction: Daily worker transport to construction site

```
Date: 2026-07-31
Reference: Transport Summary #TS-2026-202607
Description: Worker transport for July 2026 (25 working days)
Workers: 20 workers × ₹100/day × 25 days = ₹50,000

Debit (Expense):
    Account: 1152 - Worker Transport
    Amount: 50,000
    Project: Lake View (project_id: 8)
    Description: Daily worker transport - July 2026
    Dimension: Project = Lake View

Credit:
    Account: 1000 - Cash in Hand
    Amount: 50,000
```

---

### 3.6 OTHER EXPENSE (Account Code: 1160)

**Purpose:**
- Catch-all for miscellaneous project expenses that don't fit other categories
- Includes permits, safety equipment, site maintenance, etc.
- Provides flexibility for unforeseen project costs

**When to Use:**
- Building permits and municipal approvals
- Site temporary fencing and hoarding
- First aid and safety equipment
- Site office setup and maintenance
- Project insurance premiums
- Inspection and testing fees
- Environmental compliance costs

**When NOT to Use:**
- Items that belong to other main categories (use specific account)
- Capitalized expenses (use Asset accounts)
- General office expenses

**Real-World Example 1: Green City - Building Permit**

Transaction: Municipal building permit and clearance

```
Date: 2026-06-15
Reference: Municipal Receipt #MUN-2026-GC-001
Description: Building permit and approval certificate

Debit (Expense):
    Account: 1161 - Permits & Licenses
    Amount: 50,000
    Project: Green City (project_id: 5)
    Description: Building permit - Municipal Corporation
    Dimension: Project = Green City

Credit:
    Account: 1000 - Cash in Hand
    Amount: 50,000
```

**Real-World Example 2: Lake View - Safety Equipment**

Transaction: Purchase and setup of safety equipment at site

```
Date: 2026-07-05
Reference: Quotation #Q-2026-SAFETY-08, Invoice #INV-SAFETY-001
Description: Safety equipment - helmets, harnesses, first aid kits
Items:
  - 100 safety helmets @ ₹500 = ₹50,000
  - 50 safety harnesses @ ₹2,000 = ₹100,000
  - First aid kits and supplies = ₹20,000
Total: ₹170,000

Debit (Expense):
    Account: 1163 - Safety Equipment
    Amount: 170,000
    Project: Lake View (project_id: 8)
    Description: Safety equipment for construction site setup
    Dimension: Project = Lake View

Credit:
    Account: 1000 - Cash in Hand
    Amount: 170,000
```

---

## 4. Journal Entry Examples

### Complete Transaction Workflow

#### Scenario: Paying for Steel Reinforcement to Vendor

**Situation:** 
- Lake View Project needs 15 tons of steel rebar
- Supplier: Steel Industries Limited
- Rate: ₹85,000/ton
- Total: ₹1,275,000
- Payment terms: 50% on order, 50% on delivery

---

### Step 1: Purchase Order (Not a Journal Entry - Just Reference)
```
PO #PO-2026-LC-001
Date: 2026-07-01
Vendor: Steel Industries Limited
Item: Steel Rebar (Fe-500)
Quantity: 15 tons
Rate: ₹85,000/ton
Total: ₹1,275,000
```

---

### Step 2: Advance Payment (50% - when order is placed)

**Journal Entry 1: Recording Payment to Supplier**

```
Date: 2026-07-01
Reference: Cheque #CHQ-001245, PO #PO-2026-LC-001
Description: Advance payment for steel rebar purchase

Debit:
    Account: 3010 - Supplier Advance (Asset)
    Amount: 637,500
    Description: Advance paid to Steel Industries Limited

Credit:
    Account: 1000 - Cash in Bank
    Amount: 637,500
    Description: Cheque issued to Steel Industries Limited
```

---

### Step 3: Receipt of Material (When delivered to site)

**Journal Entry 2: Receiving Material into Inventory**

```
Date: 2026-07-15
Reference: Gate Pass #GP-2026-001, GRN #GRN-2026-LC-001
Description: Receipt of 15 tons steel rebar at Lake View site

Debit:
    Account: 1500 - Raw Material Inventory (Asset)
    Sub-account: Steel Inventory
    Amount: 1,275,000
    Dimension: Material = Steel, Project = Lake View
    Description: 15 tons Fe-500 rebar received

Credit:
    Account: 3010 - Supplier Advance
    Amount: 637,500
    Description: Adjusting advance payment

Credit:
    Account: 2010 - Accounts Payable
    Amount: 637,500
    Description: Balance 50% due on delivery
```

**Note:** Total debit to inventory = ₹1,275,000, which equals the full cost. The credit side balances with advance + payable.

---

### Step 4: Final Payment

**Journal Entry 3: Paying Balance Amount**

```
Date: 2026-07-15
Reference: Cheque #CHQ-001246
Description: Final payment for steel rebar delivery

Debit:
    Account: 2010 - Accounts Payable
    Amount: 637,500
    Description: Balance payment to Steel Industries Limited

Credit:
    Account: 1000 - Cash in Bank
    Amount: 637,500
    Description: Cheque issued for final settlement
```

---

### Step 5: Material Consumption (Using Material at Project Site)

**Journal Entry 4: Recording Material Consumption to Project**

```
Date: 2026-07-25
Reference: Material Requisition #MR-2026-LC-045, Consumption Report #CR-001
Description: Steel rebar consumed for structural columns - Lake View

Consumed: 10 tons out of 15 tons purchased
Rate: ₹85,000/ton
Amount: ₹850,000

Debit:
    Account: 1122 - Steel Reinforcement (Expense)
    Amount: 850,000
    Project: Lake View (project_id: 8)
    Dimension: Project = Lake View, Material = Steel
    Description: 10 tons Fe-500 rebar used in structural frame

Credit:
    Account: 1500 - Raw Material Inventory (Asset)
    Amount: 850,000
    Description: Steel consumed from inventory
```

**Result:**
- Inventory Balance: ₹425,000 (5 tons remaining)
- Project Expense: ₹850,000
- This tracks project cost accurately

---

### Complete Double-Entry Example: Monthly Project Expense Summary

**Month: July 2026**
**Project: Green City Development**

#### Transaction 1: Labor Payment

```
Date: 2026-07-05
Description: Weekly labor payment

Debit:
    Account: 1110 - Labor Cost                           ₹100,000
    Project: Green City

Credit:
    Account: 1000 - Cash in Hand                         ₹100,000
```

#### Transaction 2: Electricity Bill

```
Date: 2026-07-31
Description: July 2026 electricity charges

Debit:
    Account: 1131 - Electricity                          ₹45,000
    Project: Green City

Credit:
    Account: 2050 - Utility Bills Payable               ₹45,000
```

#### Transaction 3: Material Consumption

```
Date: 2026-07-20
Description: Concrete used for foundation

Debit:
    Account: 1121 - Concrete                             ₹1,250,000
    Project: Green City

Credit:
    Account: 1500 - Material Inventory                   ₹1,250,000
```

#### Transaction 4: Equipment Rent

```
Date: 2026-07-31
Description: Crane rental - July 2026

Debit:
    Account: 1141 - Machinery Rent                       ₹375,000
    Project: Green City

Credit:
    Account: 2010 - Accounts Payable                     ₹375,000
```

#### Transaction 5: Transportation

```
Date: 2026-07-22
Description: Material transport costs

Debit:
    Account: 1151 - Material Transport                   ₹50,000
    Project: Green City

Credit:
    Account: 1000 - Cash in Hand                         ₹50,000
```

---

## 5. Database Design

### 5.1 Core Tables Structure

#### accounts Table
```sql
CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,           -- 1111, 1112, etc.
    name VARCHAR(100) NOT NULL,                 -- Skilled Labor, Concrete, etc.
    description TEXT,
    
    -- Hierarchy
    parent_id INT NULL,                         -- NULL for top-level accounts
    hierarchy_level INT,                        -- 1=Group, 2=Parent, 3=Child, 4=Sub-child
    
    -- Classification
    account_type ENUM('ASSET', 'LIABILITY', 
                      'EQUITY', 'INCOME', 
                      'EXPENSE') NOT NULL,      -- Always EXPENSE for project expenses
    
    -- Status & Tracking
    is_active BOOLEAN DEFAULT TRUE,
    is_transactable BOOLEAN DEFAULT TRUE,       -- Can this account receive transactions?
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_account_type (account_type),
    INDEX idx_parent_id (parent_id),
    INDEX idx_active (is_active)
);
```

**Sample Data:**
```sql
INSERT INTO accounts VALUES
(1, '1000', 'EXPENSES', 'All company expenses', NULL, 1, 'EXPENSE', TRUE, FALSE),
(2, '1100', 'PROJECT EXPENSES', 'Direct project costs', 1, 2, 'EXPENSE', TRUE, FALSE),
(3, '1110', 'LABOR COST', 'Project labor costs', 2, 3, 'EXPENSE', TRUE, FALSE),
(4, '1111', 'Skilled Labor', 'Skilled workers wages', 3, 4, 'EXPENSE', TRUE, TRUE),
(5, '1112', 'Unskilled Labor', 'General workers wages', 3, 4, 'EXPENSE', TRUE, TRUE),
(6, '1120', 'MATERIAL CONSUMPTION', 'Materials used in project', 2, 3, 'EXPENSE', TRUE, FALSE),
(7, '1121', 'Concrete', 'Ready-mix concrete used', 6, 4, 'EXPENSE', TRUE, TRUE),
(8, '1122', 'Steel Reinforcement', 'Rebar and steel used', 6, 4, 'EXPENSE', TRUE, TRUE),
(9, '1130', 'UTILITY BILLS', 'Site utilities expenses', 2, 3, 'EXPENSE', TRUE, FALSE),
(10, '1131', 'Electricity', 'Site electricity charges', 9, 4, 'EXPENSE', TRUE, TRUE),
(11, '1140', 'EQUIPMENT RENT', 'Equipment rental costs', 2, 3, 'EXPENSE', TRUE, FALSE),
(12, '1141', 'Machinery Rent', 'Heavy machinery rental', 11, 4, 'EXPENSE', TRUE, TRUE),
(13, '1150', 'TRANSPORTATION', 'Transport and logistics', 2, 3, 'EXPENSE', TRUE, FALSE),
(14, '1151', 'Material Transport', 'Material movement costs', 13, 4, 'EXPENSE', TRUE, TRUE),
(15, '1160', 'OTHER EXPENSE', 'Miscellaneous project costs', 2, 3, 'EXPENSE', TRUE, FALSE),
(16, '1161', 'Permits & Licenses', 'Municipal and regulatory', 15, 4, 'EXPENSE', TRUE, TRUE);
```

---

#### transactions Table (Enhanced for Project Tracking)

```sql
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Transaction Identity
    transaction_number VARCHAR(50) UNIQUE NOT NULL,  -- TXN-2026-001, etc.
    transaction_type ENUM('JOURNAL_ENTRY', 'PAYMENT', 
                          'RECEIPT', 'TRANSFER') NOT NULL,
    
    -- Date & Timing
    transaction_date DATE NOT NULL,
    posting_date DATE,                          -- When posted to ledger
    
    -- Description & Reference
    description TEXT NOT NULL,
    reference_number VARCHAR(100),              -- Invoice #, PO #, Check #
    
    -- Project Dimension (CRITICAL FOR PROJECT EXPENSE TRACKING)
    project_id INT NOT NULL,                    -- Links to projects table
    
    -- Status & Approval
    status ENUM('DRAFT', 'APPROVED', 
                'POSTED', 'REVERSED') DEFAULT 'DRAFT',
    
    -- Amounts
    total_debit DECIMAL(15, 2),
    total_credit DECIMAL(15, 2),
    
    -- Audit Trail
    created_by INT,
    approved_by INT,
    posted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    
    INDEX idx_project_id (project_id),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_status (status),
    UNIQUE KEY unique_txn (transaction_number)
);
```

---

#### transaction_lines Table

```sql
CREATE TABLE transaction_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Link to Transaction
    transaction_id INT NOT NULL,
    line_number INT,                            -- 1, 2, 3, etc.
    
    -- Account & Amount
    account_id INT NOT NULL,
    debit_amount DECIMAL(15, 2) DEFAULT 0,
    credit_amount DECIMAL(15, 2) DEFAULT 0,
    
    -- Additional Dimensions
    dimension_1_name VARCHAR(50),               -- e.g., 'Material Type'
    dimension_1_value VARCHAR(100),             -- e.g., 'Steel', 'Concrete'
    
    dimension_2_name VARCHAR(50),               -- e.g., 'Labor Type'
    dimension_2_value VARCHAR(100),             -- e.g., 'Skilled', 'Unskilled'
    
    dimension_3_name VARCHAR(50),               -- Future use
    dimension_3_value VARCHAR(100),
    
    -- Description
    description TEXT,
    
    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_account_id (account_id),
    INDEX idx_dimension_1 (dimension_1_value),
    INDEX idx_dimension_2 (dimension_2_value)
);
```

---

#### account_balances Table (For Efficient Reporting)

```sql
CREATE TABLE account_balances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Account & Period
    account_id INT NOT NULL,
    project_id INT NOT NULL,
    period_year INT,                            -- 2026
    period_month INT,                           -- 1-12
    
    -- Balances
    opening_balance DECIMAL(15, 2) DEFAULT 0,
    debit_amount DECIMAL(15, 2) DEFAULT 0,
    credit_amount DECIMAL(15, 2) DEFAULT 0,
    closing_balance DECIMAL(15, 2) DEFAULT 0,
    
    -- Tracking
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    
    UNIQUE KEY unique_balance (account_id, project_id, period_year, period_month),
    INDEX idx_project_period (project_id, period_year, period_month)
);
```

---

#### project_expenses_summary Table (Pre-calculated for Fast Reporting)

```sql
CREATE TABLE project_expenses_summary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Project & Period
    project_id INT NOT NULL,
    calculation_date DATE,
    
    -- Expense Categories
    labor_cost DECIMAL(15, 2) DEFAULT 0,           -- Sum of 1110 child accounts
    material_consumption DECIMAL(15, 2) DEFAULT 0, -- Sum of 1120 child accounts
    utility_bills DECIMAL(15, 2) DEFAULT 0,        -- Sum of 1130 child accounts
    equipment_rent DECIMAL(15, 2) DEFAULT 0,       -- Sum of 1140 child accounts
    transportation DECIMAL(15, 2) DEFAULT 0,       -- Sum of 1150 child accounts
    other_expense DECIMAL(15, 2) DEFAULT 0,        -- Sum of 1160 child accounts
    
    -- Total
    total_project_expenses DECIMAL(15, 2) DEFAULT 0,
    
    -- Metadata
    is_final BOOLEAN DEFAULT FALSE,                -- Is this a month-end finalized figure?
    last_recalculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id),
    
    INDEX idx_project_id (project_id),
    INDEX idx_calculation_date (calculation_date)
);
```

---

### 5.2 Relationship Diagram

```
projects (id, name, status, start_date, end_date, budget, ...)
    ↓
transactions (id, project_id, transaction_date, ...)
    ↓
transaction_lines (id, transaction_id, account_id, debit_amount, credit_amount, ...)
    ↓
accounts (id, code, name, account_type, parent_id, ...)

account_balances (account_id, project_id, period_year, period_month, ...)
    ↓
account_id ← accounts(id)
project_id ← projects(id)

project_expenses_summary (project_id, labor_cost, material_consumption, ...)
    ↓
project_id ← projects(id)
```

---

## 6. Project-wise Expense Tracking

### 6.1 Recording Project Expenses with Project ID Dimension

**Key Principle:** The same chart of accounts is reused across all projects. Each transaction line includes `project_id` to associate the expense with a specific project.

#### Example: Labor Payment for Multiple Projects

**Scenario:** Payment to contractor who worked on multiple projects

```
Date: 2026-07-31
Reference: Invoice #INV-LAB-2026-001
Description: Wages for workers - July 2026

Contractor worked on:
- Green City: 8 days (16 workers)
- Lake View: 5 days (12 workers)

Daily rate: ₹5,000 per worker

Amount Calculation:
- Green City: 16 workers × 5,000 × 8 days = ₹640,000
- Lake View: 12 workers × 5,000 × 5 days = ₹300,000
- Total: ₹940,000
```

**Journal Entries (Split by Project):**

```
Transaction: TXN-2026-0850
Date: 2026-07-31

Line 1:
  Debit:  Account 1111 (Skilled Labor)
          Amount: ₹640,000
          Project: Green City (project_id: 5)
          Description: 16 workers × ₹5,000/day × 8 days
          
Line 2:
  Debit:  Account 1111 (Skilled Labor)
          Amount: ₹300,000
          Project: Lake View (project_id: 8)
          Description: 12 workers × ₹5,000/day × 5 days
          
Line 3:
  Credit: Account 2010 (Accounts Payable)
          Amount: ₹940,000
          Description: Wages payable to contractors
```

**Database Record:**

```
transactions:
id: 1001
transaction_number: TXN-2026-0850
transaction_date: 2026-07-31
description: Contractor wages - July 2026
reference_number: INV-LAB-2026-001
status: POSTED
total_debit: 940,000
total_credit: 940,000

transaction_lines:
Line 1:
  id: 4501, transaction_id: 1001, account_id: 4 (1111),
  debit_amount: 640,000, credit_amount: 0,
  project_id: 5 (Green City),
  description: Green City workers

Line 2:
  id: 4502, transaction_id: 1001, account_id: 4 (1111),
  debit_amount: 300,000, credit_amount: 0,
  project_id: 8 (Lake View),
  description: Lake View workers

Line 3:
  id: 4503, transaction_id: 1001, account_id: 11 (2010),
  debit_amount: 0, credit_amount: 940,000,
  project_id: NULL,
  description: Accounts payable - wage expense
```

---

### 6.2 Querying Project-wise Expenses

**Query: Get Total Labor Cost for Green City**

```sql
SELECT 
    a.id,
    a.code,
    a.name,
    SUM(tl.debit_amount) AS labor_cost
FROM transaction_lines tl
JOIN transactions t ON tl.transaction_id = t.id
JOIN accounts a ON tl.account_id = a.id
JOIN projects p ON t.project_id = p.id
WHERE p.id = 5                              -- Green City
  AND a.code = '1111'                       -- Skilled Labor
  AND t.status = 'POSTED'
  AND t.transaction_date >= '2026-01-01'
  AND t.transaction_date <= '2026-12-31'
GROUP BY a.id, a.code, a.name;

Result:
id | code | name          | labor_cost
4  | 1111 | Skilled Labor | 640,000
```

**Query: Get All Project Expenses by Category**

```sql
SELECT 
    p.id,
    p.name AS project_name,
    
    -- All Categories
    SUM(CASE WHEN a.code LIKE '111%' THEN tl.debit_amount ELSE 0 END) AS labor_cost,
    SUM(CASE WHEN a.code LIKE '112%' THEN tl.debit_amount ELSE 0 END) AS material_consumption,
    SUM(CASE WHEN a.code LIKE '113%' THEN tl.debit_amount ELSE 0 END) AS utility_bills,
    SUM(CASE WHEN a.code LIKE '114%' THEN tl.debit_amount ELSE 0 END) AS equipment_rent,
    SUM(CASE WHEN a.code LIKE '115%' THEN tl.debit_amount ELSE 0 END) AS transportation,
    SUM(CASE WHEN a.code LIKE '116%' THEN tl.debit_amount ELSE 0 END) AS other_expense,
    
    -- Total
    SUM(tl.debit_amount) AS total_project_expenses
    
FROM transaction_lines tl
JOIN transactions t ON tl.transaction_id = t.id
JOIN accounts a ON tl.account_id = a.id
JOIN projects p ON t.project_id = p.id
WHERE a.code >= '1100' AND a.code < '1200'  -- Project Expenses range
  AND t.status = 'POSTED'
  AND t.transaction_date >= '2026-01-01'
  AND t.transaction_date <= '2026-12-31'
GROUP BY p.id, p.name
ORDER BY p.name;

Result:
project_id | project_name    | labor_cost | material_consumption | utility_bills | equipment_rent | transportation | other_expense | total_project_expenses
5          | Green City      | 1,000,000  | 1,500,000            | 200,000       | 0              | 0              | 100,000       | 2,800,000
8          | Lake View       | 1,000,000  | 1,000,000            | 100,000       | 0              | 0              | 100,000       | 2,200,000
```

---

### 6.3 Monthly Project Expense Report

**Query: Project Expense Summary by Month**

```sql
SELECT 
    p.name AS project_name,
    YEAR(t.transaction_date) AS year,
    MONTH(t.transaction_date) AS month,
    
    SUM(CASE WHEN a.code LIKE '111%' THEN tl.debit_amount ELSE 0 END) AS labor_cost,
    SUM(CASE WHEN a.code LIKE '112%' THEN tl.debit_amount ELSE 0 END) AS material_consumption,
    SUM(CASE WHEN a.code LIKE '113%' THEN tl.debit_amount ELSE 0 END) AS utility_bills,
    SUM(CASE WHEN a.code LIKE '114%' THEN tl.debit_amount ELSE 0 END) AS equipment_rent,
    SUM(CASE WHEN a.code LIKE '115%' THEN tl.debit_amount ELSE 0 END) AS transportation,
    SUM(CASE WHEN a.code LIKE '116%' THEN tl.debit_amount ELSE 0 END) AS other_expense,
    
    SUM(tl.debit_amount) AS total_monthly_expenses
    
FROM transaction_lines tl
JOIN transactions t ON tl.transaction_id = t.id
JOIN accounts a ON tl.account_id = a.id
JOIN projects p ON t.project_id = p.id
WHERE a.code >= '1100' AND a.code < '1200'
  AND t.status = 'POSTED'
GROUP BY p.id, p.name, YEAR(t.transaction_date), MONTH(t.transaction_date)
ORDER BY p.name, year, month;
```

---

## 7. Report Design & Queries

### 7.1 PROJECT EXPENSE SUMMARY Report

**Report Purpose:** Show total expenses by category across all projects

```
═══════════════════════════════════════════════════════════════
                 PROJECT EXPENSE SUMMARY
                  As of: 31 July 2026
═══════════════════════════════════════════════════════════════

Expense Category                    Amount          % of Total
───────────────────────────────────────────────────────────────
Labor Cost                      2,000,000            40.0%
Material Consumption            2,500,000            50.0%
Utility Bills                     300,000             6.0%
Equipment Rent                          0             0.0%
Transportation                         0             0.0%
Other Expense                     200,000             4.0%
───────────────────────────────────────────────────────────────
TOTAL PROJECT EXPENSES          5,000,000           100.0%
═══════════════════════════════════════════════════════════════
```

**SQL Query:**

```sql
SELECT 
    'Labor Cost' AS category,
    SUM(CASE WHEN a.code LIKE '111%' THEN tl.debit_amount ELSE 0 END) AS amount
FROM transaction_lines tl
JOIN transactions t ON tl.transaction_id = t.id
JOIN accounts a ON tl.account_id = a.id
WHERE a.code >= '1100' AND a.code < '1200'
  AND t.status = 'POSTED'
  
UNION ALL

SELECT 
    'Material Consumption' AS category,
    SUM(CASE WHEN a.code LIKE '112%' THEN tl.debit_amount ELSE 0 END) AS amount
-- ... etc for all categories
```

---

### 7.2 PROJECT-WISE DETAILED REPORT

**Report Purpose:** Show expenses for each project separately

```
═══════════════════════════════════════════════════════════════
             PROJECT: GREEN CITY DEVELOPMENT
                  As of: 31 July 2026
═══════════════════════════════════════════════════════════════

Account Code    Account Name              Amount          YTD Total
───────────────────────────────────────────────────────────────
1111            Skilled Labor             100,000        1,000,000
1112            Unskilled Labor                0          100,000
1113            Management Labor          150,000          500,000
                └─ Labor Cost Subtotal                   1,600,000

1121            Concrete                  500,000        1,500,000
1122            Steel Reinforcement       250,000          800,000
1123            Brick & Masonry            50,000          100,000
1124            Electrical Materials       25,000           50,000
1125            Plumbing Materials         15,000           30,000
1126            Finishing Materials        10,000           20,000
                └─ Material Consumption Total           2,500,000

1131            Electricity                45,000          200,000
1132            Water                      10,000           30,000
                └─ Utility Bills Total                    230,000

1141            Machinery Rent           375,000          375,000
1142            Vehicle Rent                   0            0
                └─ Equipment Rent Total                  375,000

1151            Material Transport         50,000          100,000
1152            Worker Transport          15,000           50,000
                └─ Transportation Total                  150,000

1161            Permits & Licenses            0           50,000
1162            Site Maintenance           10,000           25,000
1163            Safety Equipment            5,000           10,000
                └─ Other Expense Total                    85,000

───────────────────────────────────────────────────────────────
TOTAL PROJECT COST                                    5,095,000
═══════════════════════════════════════════════════════════════

Budget Variance:
  Approved Budget:    ₹5,000,000
  Actual Expenses:    ₹5,095,000
  Variance:           ₹95,000 (Over budget by 1.9%)
```

---

### 7.3 PROJECT PROFITABILITY ANALYSIS

**Report Purpose:** Show project profitability with revenue vs. expenses

```
═══════════════════════════════════════════════════════════════
          PROJECT PROFITABILITY ANALYSIS
            Green City Development
              As of: 31 July 2026
═══════════════════════════════════════════════════════════════

REVENUE SECTION:
─────────────────────────────────────────────────────────────
  Contract Value                            ₹6,500,000
  Approved Variations                       ₹  150,000
  Total Revenue                             ₹6,650,000

EXPENSES SECTION:
─────────────────────────────────────────────────────────────
  Labor Cost                ₹1,600,000
  Material Consumption      ₹2,500,000
  Utility Bills             ₹  230,000
  Equipment Rent            ₹  375,000
  Transportation            ₹  150,000
  Other Expense             ₹   85,000
  ─────────────────────────────────────
  Total Project Expenses                    ₹4,940,000

Project Profit                              ₹1,710,000

PROFITABILITY METRICS:
─────────────────────────────────────────────────────────────
  Profit Margin (%)         = (1,710,000 / 6,650,000) × 100
                            = 25.7%
  
  Cost Performance          = Actual Cost / Budget Cost
                            = 4,940,000 / 5,000,000
                            = 0.988 (98.8% - Good)
  
  Expenditure vs Revenue    = 4,940,000 / 6,650,000
                            = 74.3% (Expenses are 74.3% of revenue)
═══════════════════════════════════════════════════════════════
```

**SQL Query:**

```sql
SELECT 
    p.id,
    p.name,
    p.contract_value,
    p.contract_value + COALESCE(p.approved_variations, 0) AS total_revenue,
    
    (SELECT SUM(tl.debit_amount) 
     FROM transaction_lines tl
     JOIN transactions t ON tl.transaction_id = t.id
     JOIN accounts a ON tl.account_id = a.id
     WHERE t.project_id = p.id 
       AND a.code >= '1100' AND a.code < '1200'
       AND t.status = 'POSTED') AS total_expenses,
    
    (p.contract_value + COALESCE(p.approved_variations, 0) -
     (SELECT SUM(tl.debit_amount) 
      FROM transaction_lines tl
      JOIN transactions t ON tl.transaction_id = t.id
      JOIN accounts a ON tl.account_id = a.id
      WHERE t.project_id = p.id 
        AND a.code >= '1100' AND a.code < '1200'
        AND t.status = 'POSTED')) AS profit
    
FROM projects p
WHERE p.status = 'ACTIVE'
ORDER BY p.id;
```

---

### 7.4 VARIANCE ANALYSIS Report

**Report Purpose:** Compare budgeted vs. actual expenses

```
═══════════════════════════════════════════════════════════════
          PROJECT VARIANCE ANALYSIS
            All Projects - July 2026
═══════════════════════════════════════════════════════════════

Project         Category              Budget    Actual    Variance   %
─────────────────────────────────────────────────────────────────────
Green City      Labor Cost            800,000   900,000  (100,000) -12.5%
                Material Consumption 1,500,000 1,400,000  100,000   6.7%
                Utility Bills         250,000   230,000   20,000    8.0%
                Equipment Rent        200,000   375,000  (175,000) -87.5%
                Transportation         80,000   150,000  (70,000)  -87.5%
                Other Expense          50,000    85,000  (35,000)  -70.0%
                ─────────────────────────────────────────────────
                Subtotal            2,880,000 3,140,000 (260,000)  -9.0%

Lake View       Labor Cost            600,000   500,000  100,000   16.7%
                Material Consumption 1,200,000 1,000,000  200,000   16.7%
                Utility Bills         150,000   100,000   50,000    33.3%
                Other Expense         100,000   100,000        0    0.0%
                ─────────────────────────────────────────────────
                Subtotal            2,050,000 1,700,000  350,000   17.1%

───────────────────────────────────────────────────────────────────
TOTAL          All Categories       4,930,000 4,840,000   90,000    1.8%
═════════════════════════════════════════════════════════════════════

Legend: (Amount) in parentheses = Over budget (unfavorable)
        Amount without parentheses = Under budget (favorable)
```

---

## 8. Best Practices

### 8.1 Chart of Accounts Best Practices

1. **Consistent Numbering System**
   - Use systematic code structure: 1XXX for expenses
   - Follow accounting standards (Chart of Accounts per IFRS/IND-AS)
   - Leave gaps for future account additions
   
2. **Clear Account Hierarchy**
   - Maximum 4 levels (Group → Parent → Child → Sub-child)
   - Only leaf accounts should be transactable
   - Parent accounts should be summary accounts (non-transactable)
   
3. **Meaningful Account Names**
   - Clear, descriptive names that don't change
   - Avoid abbreviations unless standardized
   - Include units where applicable (e.g., "Electricity (per kWh)")
   
4. **Account Segregation by Project**
   - Never create project-specific accounts (e.g., "Green City Labor")
   - Use project_id dimension in transactions instead
   - This allows account reuse and cleaner accounting structure

5. **Regular Account Review**
   - Review accounts quarterly for usage and relevance
   - Consolidate unused accounts
   - Update descriptions based on actual usage

---

### 8.2 Transaction Recording Best Practices

1. **Always Use Project Dimension**
   ```sql
   -- CORRECT: Project ID in transaction line
   INSERT INTO transaction_lines (transaction_id, account_id, debit_amount, project_id)
   VALUES (1001, 4, 640000, 5);  -- Green City project
   
   -- WRONG: Creating project-specific accounts
   INSERT INTO accounts (code, name, parent_id)
   VALUES ('1111-GC', 'Skilled Labor - Green City', 3);
   ```

2. **Detailed Documentation**
   - Always fill in reference_number field
   - Include invoice/PO/check numbers
   - Use consistent naming conventions
   - Include units of measurement in line descriptions
   
3. **Complete Double-Entry**
   - Never record only debits or only credits
   - Ensure total_debit = total_credit at transaction level
   - Validate before posting

4. **Approval Workflow**
   - Implement approval before posting
   - Track approver and approval date
   - Keep audit trail of all changes

5. **Timely Recording**
   - Record transactions close to actual date
   - Use consistent posting date
   - Avoid batch posting from long periods

---

### 8.3 Project Expense Tracking Best Practices

1. **Allocation Methodology**
   - For shared expenses, allocate based on:
     - Labor hours worked on project
     - Material quantity used on project
     - Square footage of project
     - Equipment usage hours
   - Document allocation basis in transaction description

2. **Inventory Movement**
   - Always record through inventory accounts first
   - Only expense when consumed at project site
   - Maintain inventory ledger for reconciliation

3. **Periodic Reconciliation**
   ```sql
   -- Reconcile expenses with supporting documents
   SELECT 
       a.name,
       SUM(tl.debit_amount) AS total_expense,
       COUNT(DISTINCT t.id) AS transaction_count
   FROM transaction_lines tl
   JOIN transactions t ON tl.transaction_id = t.id
   JOIN accounts a ON tl.account_id = a.id
   WHERE t.project_id = 5  -- Green City
     AND t.status = 'POSTED'
     AND MONTH(t.transaction_date) = 7
   GROUP BY a.id, a.name
   HAVING total_expense > 0
   ```

4. **Month-end Close Process**
   - Post all accruals (utilities, rent, etc.)
   - Reconcile accounts payable
   - Calculate project_expenses_summary
   - Review for unusual transactions
   - Lock posting date for that period

---

### 8.4 Reporting Best Practices

1. **Standardize Report Format**
   - Use consistent headers and footers
   - Include report date and currency
   - Add footnotes for significant variances
   - Always include comparison periods

2. **Data Validation**
   - Always validate totals before publishing
   - Cross-check project totals with project budget
   - Reconcile with source documents

3. **Timeliness**
   - Generate reports within 5 days of month-end
   - Provide reports to project managers and finance team
   - Use consistent reporting calendar

---

## 9. Common Mistakes to Avoid

### 9.1 Chart of Accounts Mistakes

**❌ MISTAKE 1: Creating Project-Specific Accounts**
```sql
-- WRONG: This creates account bloat and limits reusability
INSERT INTO accounts VALUES
(100, '1111-GC', 'Skilled Labor - Green City', ...),
(101, '1111-LV', 'Skilled Labor - Lake View', ...),
(102, '1112-GC', 'Unskilled Labor - Green City', ...);

-- This results in:
-- - Hundreds of accounts for multiple projects
-- - Difficulty in consolidated reporting
-- - Breakdown of CoA structure
-- - Maintenance nightmare
```

**✅ CORRECT: Use Dimension in Transaction**
```sql
-- CORRECT: Single account, multiple projects
INSERT INTO accounts VALUES
(4, '1111', 'Skilled Labor', NULL, 3, 4, 'EXPENSE', TRUE, TRUE, ...);

-- Use same account for all projects:
INSERT INTO transaction_lines (account_id, debit_amount, project_id) 
VALUES (4, 640000, 5);    -- Green City

INSERT INTO transaction_lines (account_id, debit_amount, project_id)
VALUES (4, 300000, 8);    -- Lake View
```

---

**❌ MISTAKE 2: Mixing Projects in Same Transaction Line**
```sql
-- WRONG: Combining two projects in one line
INSERT INTO transaction_lines 
  (transaction_id, account_id, debit_amount, project_id)
VALUES 
  (1001, 4, 940000, NULL);  -- No project specified!
  
-- This makes it impossible to:
-- - Track expenses by project
-- - Generate project-wise reports
-- - Reconcile project budgets
-- - Analyze project profitability
```

**✅ CORRECT: Split by Project**
```sql
-- CORRECT: Separate lines for each project
INSERT INTO transaction_lines (transaction_id, account_id, debit_amount, project_id)
VALUES 
  (1001, 4, 640000, 5),   -- Green City
  (1001, 4, 300000, 8);   -- Lake View
```

---

**❌ MISTAKE 3: Inconsistent Account Levels for Transactions**
```sql
-- WRONG: Recording on different hierarchy levels
INSERT INTO transaction_lines (transaction_id, account_id, debit_amount)
VALUES 
  (1001, 3, 100000),   -- Transacted on 1110 (Labor Cost - parent level)
  (1002, 4, 200000);   -- Transacted on 1111 (Skilled Labor - child level)

-- Problems:
-- - Inconsistent recording
-- - Difficult to reconcile
-- - Reports become unreliable
-- - Audit trail unclear
```

**✅ CORRECT: Always Transaction on Leaf Accounts**
```sql
-- CORRECT: All transactions on child/sub-child level
INSERT INTO accounts SET is_transactable = FALSE WHERE id = 3;
-- Prevents transactions on parent accounts

INSERT INTO transaction_lines (transaction_id, account_id, debit_amount)
VALUES 
  (1001, 4, 100000),    -- 1111 (Skilled Labor)
  (1002, 5, 200000);    -- 1112 (Unskilled Labor)
```

---

### 9.2 Transaction Recording Mistakes

**❌ MISTAKE 4: Unequal Debits and Credits**
```sql
-- WRONG: Debit > Credit (journal won't balance)
INSERT INTO transaction_lines (transaction_id, account_id, debit_amount, credit_amount)
VALUES 
  (1005, 4, 1000, 0),      -- Labor Cost debit
  (1005, 11, 800, 0);      -- AP credit (missing!)

-- Results in:
-- - Unbalanced journal
-- - Accounting equation breaks down
-- - Report totals are wrong
-- - Audit failures
```

**✅ CORRECT: Equal Debits and Credits**
```sql
-- CORRECT: Transaction balances
INSERT INTO transaction_lines (transaction_id, account_id, debit_amount, credit_amount)
VALUES 
  (1005, 4, 1000, 0),      -- Labor Cost debit
  (1005, 11, 0, 1000);     -- AP credit
-- Total Debit = 1000, Total Credit = 1000 ✓
```

---

**❌ MISTAKE 5: Recording Partial Amounts or Forgetting Allocations**
```sql
-- WRONG: Only recording one part of multi-project expense
Date: 2026-07-31
Material used across 3 projects
Project A: 1000 units
Project B: 500 units
Project C: 300 units
Total: 1800 units

Recorded:
Dr. Material Consumption (Project A)  1000
Cr. Inventory                               1000

-- Missing Project B and C entries!
-- Material is short by 800 units in inventory
-- Projects B and C are under-expensed
```

**✅ CORRECT: Complete Allocation**
```sql
-- CORRECT: All projects included
Dr. Material Consumption (Project A)     1000 units
Dr. Material Consumption (Project B)     500 units
Dr. Material Consumption (Project C)     300 units
Cr. Inventory                                    1800 units
```

---

### 9.3 Project Tracking Mistakes

**❌ MISTAKE 6: Losing Project Information in Batch Processing**
```sql
-- WRONG: Batch importing without project context
INSERT INTO transaction_lines SELECT 
  ROW_NUMBER() OVER (ORDER BY id) AS line_number,
  account_id,
  amount AS debit_amount,
  NULL AS project_id  -- ← Lost project information!
FROM import_file;

-- Result:
-- - All transactions marked as NULL project
-- - Can't attribute expenses to specific projects
-- - Reports show total expenses only
-- - Can't answer "How much did Green City cost?"
```

**✅ CORRECT: Maintain Project Context**
```sql
-- CORRECT: Keep project_id throughout
INSERT INTO transaction_lines SELECT 
  ROW_NUMBER() OVER (ORDER BY id) AS line_number,
  account_id,
  amount AS debit_amount,
  import_file.project_id  -- ← Preserved!
FROM import_file
JOIN projects ON import_file.project_code = projects.code;
```

---

**❌ MISTAKE 7: Mixing Overhead with Project Expenses**
```sql
-- WRONG: Recording office overhead as project expense
Dr. 1111 Skilled Labor (Project A)         50,000
Dr. 1000 Cash in Hand (office salary)      30,000
Cr. Accounts Payable                                80,000

-- Problem:
-- - Office salary incorrectly attributed to Project A
-- - Project A cost is inflated
-- - True project cost is hidden
-- - Profitability analysis is wrong
```

**✅ CORRECT: Separate Project vs. Overhead**
```sql
-- CORRECT: Use different account for overhead
Dr. 1111 Skilled Labor (Project A)         50,000    -- Project expense
Dr. 2000 Office Administration Expense     30,000    -- Company overhead
Cr. Accounts Payable                                 80,000

-- Now:
-- - Project A correctly shows ₹50,000 cost
-- - Overhead separately tracked
-- - Profitability analysis is accurate
```

---

### 9.4 Reporting Mistakes

**❌ MISTAKE 8: Reporting Only Summary, Not Details**
```
PROJECT EXPENSE SUMMARY
Project Expenses:  ₹5,000,000

-- This is useless because:
-- - No breakdown by category
-- - Can't identify problem areas
-- - Can't manage budget variances
-- - Can't make informed decisions
```

**✅ CORRECT: Detailed Breakdown**
```
PROJECT EXPENSE SUMMARY

Labor Cost:                   ₹2,000,000  (40%)
Material Consumption:         ₹2,500,000  (50%)
Utility Bills:                ₹  300,000  (6%)
Equipment Rent:               ₹        0  (0%)
Transportation:               ₹        0  (0%)
Other Expense:                ₹  200,000  (4%)
─────────────────────────────────────────────
TOTAL:                        ₹5,000,000  (100%)

-- Now stakeholders can:
-- - See where money is going
-- - Identify over-budget categories
-- - Make corrective decisions
```

---

**❌ MISTAKE 9: Not Reconciling to Budget**
```
Monthly Report: ₹850,000 spent

-- No comparison to budget
-- Is this good or bad?
-- Is project on track?
-- No one knows!
```

**✅ CORRECT: Always Show Variance**
```
Expense Category          Budget    Actual    Variance    %
Labor Cost               500,000   550,000   (50,000)   -10.0%
Material Consumption     300,000   250,000    50,000     16.7%
Equipment Rent           100,000   150,000   (50,000)   -50.0%
Other                     50,000    50,000         0      0.0%
─────────────────────────────────────────────────────────
TOTAL                    950,000   850,000   100,000     10.5%

-- Budget Status: ✓ ON TRACK (Under budget by 10.5%)
-- Action: Equipment rent needs review (over by 50%)
```

---

**❌ MISTAKE 10: Not Tracking Project Profitability**
```
Project Expenses:  ₹5,000,000
Project Revenue:   ₹6,000,000

-- Without deeper analysis:
-- - Can't see which projects are profitable
-- - Can't identify problem projects early
-- - Can't make go/no-go decisions
-- - Can't optimize resources
```

**✅ CORRECT: Complete Profitability Analysis**
```
PROJECT PROFITABILITY REPORT

Project        Revenue    Expenses   Profit   Margin    Status
Green City    6,500,000  5,095,000 1,405,000 21.6%     ✓ Good
Lake View     5,200,000  4,950,000   250,000  4.8%     ⚠ Low
Sunset Tower  4,000,000  4,250,000  (250,000) -6.3%    ✗ Loss

Key Insights:
- Green City: Healthy margin, on track
- Lake View: Margin below target, monitor costs
- Sunset Tower: Project is unprofitable, review scope/change orders
```

---

### 9.5 System Design Mistakes

**❌ MISTAKE 11: Inadequate Audit Trail**
```sql
-- WRONG: No audit information
INSERT INTO transactions (description, amount) 
VALUES ('Labor payment', 100000);

-- Can't answer:
-- - Who recorded this?
-- - When was it posted?
-- - Was it approved?
-- - What was the original journal entry?
```

**✅ CORRECT: Complete Audit Trail**
```sql
-- CORRECT: Full audit information
INSERT INTO transactions 
  (transaction_number, description, transaction_date, 
   posting_date, created_by, approved_by, status)
VALUES 
  ('TXN-2026-0850', 'Labor payment', 
   '2026-07-31', '2026-08-02', 
   5, 7, 'POSTED');
   
-- Now can track:
-- - Who created and when
-- - Who approved and when
-- - When posted to ledger
-- - Complete history
```

---

**❌ MISTAKE 12: No Data Validation at Entry**
```sql
-- WRONG: Accepting any data
INSERT INTO transaction_lines 
  (account_id, debit_amount, credit_amount)
VALUES 
  (999, 100000, 200000);  -- Account doesn't exist, unequal D/C!

-- Results in:
-- - Invalid data in database
-- - Reports show garbage
-- - Audit fails
-- - Cleanup nightmare
```

**✅ CORRECT: Validation Before Insert**
```sql
-- CORRECT: Validate before accepting
START TRANSACTION;

-- Check account exists
IF NOT EXISTS (SELECT id FROM accounts WHERE id = 4) 
  THEN THROW ERROR;

-- Check amounts are positive
IF debit_amount < 0 OR credit_amount < 0 
  THEN THROW ERROR;

-- Check debit = credit for transaction
IF (SELECT SUM(debit_amount) FROM new_lines) 
   != (SELECT SUM(credit_amount) FROM new_lines)
  THEN THROW ERROR;

-- If all checks pass, insert
INSERT INTO transaction_lines VALUES (...);

COMMIT;
```

---

## Summary

### Key Principles for Project Expense Accounting

1. **One Chart of Accounts for All Projects**
   - Create accounts once, use across all projects
   - Use project_id dimension in transactions
   - Avoid project-specific account proliferation

2. **Strict Double-Entry Discipline**
   - Every transaction must balance
   - Always credit something when debiting expense
   - Maintain accounting equation: Assets = Liabilities + Equity

3. **Complete Project Dimension Tracking**
   - Never lose project context
   - Every project expense must have project_id
   - Use this for project-wise reporting and profitability

4. **Hierarchy and Consistency**
   - Follow 4-level hierarchy strictly
   - Only leaf accounts are transactable
   - Parent accounts are summary only

5. **Robust Reconciliation**
   - Monthly reconciliation of all accounts
   - Variance analysis against budget
   - Inventory-to-expense flow tracking

6. **Comprehensive Reporting**
   - Show both summary and detail
   - Always include budget variance
   - Track profitability by project
   - Identify problem areas early

---

## Implementation Checklist

- [ ] Create accounts table with hierarchy structure
- [ ] Set up transactions and transaction_lines tables
- [ ] Add project_id field to transactions/transaction_lines
- [ ] Create account_balances and project_expenses_summary tables
- [ ] Set is_transactable = FALSE for parent accounts
- [ ] Build transaction recording forms with project selection
- [ ] Implement approval workflow
- [ ] Create standard reports (expense summary, project-wise, profitability)
- [ ] Build variance analysis report
- [ ] Set up month-end close procedure
- [ ] Create audit trail functionality
- [ ] Implement data validation rules
- [ ] Train users on CoA structure and project tracking
- [ ] Schedule monthly reconciliation process

---

End of Document

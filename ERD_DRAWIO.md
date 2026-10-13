# Entity Relationship Diagram (ERD) - RetroCam Inventory Management System

Dokumen ini menyediakan diagram ERD dan kode XML/PlantUML/Mermaid untuk diimpor ke **draw.io** / Diagrams.net.

---

## 1. Visualisasi ERD (Mermaid)

```mermaid
erDiagram
    USERS ||--|| USER_PROFILES : "has (1:1)"
    CATEGORIES ||--|{ PRODUCTS : "contains (1:N)"
    PRODUCTS ||--|{ PRODUCT_SUPPLIERS : "supplied_via (1:N)"
    SUPPLIERS ||--|{ PRODUCT_SUPPLIERS : "supplies (1:N)"
    PRODUCTS ||--|{ STOCK_MOVEMENTS : "tracks (1:N)"
    USERS ||--|{ STOCK_MOVEMENTS : "records (1:N)"

    USERS {
        int id PK "AUTO_INCREMENT"
        varchar username UK "UNIQUE"
        varchar password_hash "Bcrypt hash"
        enum role "admin, staff"
        timestamp created_at
    }

    USER_PROFILES {
        int id PK "AUTO_INCREMENT"
        int user_id FK,UK "One-to-One"
        varchar full_name
        varchar email
        varchar phone
    }

    CATEGORIES {
        int id PK "AUTO_INCREMENT"
        varchar name UK "UNIQUE"
        text description
        timestamp created_at
    }

    PRODUCTS {
        int id PK "AUTO_INCREMENT"
        int category_id FK "Categories(id)"
        varchar sku UK "UNIQUE"
        varchar name
        decimal price "12,2"
        int stock "Default 0"
        int minimum_stock "Default 5"
        timestamp created_at
    }

    SUPPLIERS {
        int id PK "AUTO_INCREMENT"
        varchar name
        varchar contact_person
        varchar phone
        text address
        timestamp created_at
    }

    PRODUCT_SUPPLIERS {
        int product_id PK,FK "Products(id)"
        int supplier_id PK,FK "Suppliers(id)"
        decimal supplier_price "12,2"
    }

    STOCK_MOVEMENTS {
        int id PK "AUTO_INCREMENT"
        int product_id FK "Products(id)"
        int user_id FK "Users(id)"
        enum type "IN, OUT"
        int quantity
        text notes
        timestamp created_at
    }
```

---

## 2. Cara Impor ke Draw.io (Metode Tercepat: PlantUML)

1. Buka [draw.io](https://app.diagrams.net/).
2. Buat diagram baru atau buka kanvas kosong.
3. Di menu bar atas, pilih: **Arrange &rarr; Insert &rarr; Advanced &rarr; PlantUML...**
4. Salin kode berikut dan paste ke dalam kotak, lalu klik **Insert**:

```plantuml
@startuml
skinparam roundcorner 5
skinparam linetype ortho
skinparam shadowing false
skinparam handwritten false
skinparam class {
    BackgroundColor #1E293B
    ArrowColor #38BDF8
    BorderColor #475569
    FontColor #F8FAFC
    HeaderBackgroundColor #0F172A
}

entity "users" as users {
    * id : INT (PK)
    --
    * username : VARCHAR(50) (UQ)
    * password_hash : VARCHAR(255)
    * role : ENUM('admin','staff')
    created_at : TIMESTAMP
}

entity "user_profiles" as user_profiles {
    * id : INT (PK)
    --
    * user_id : INT (FK, UQ)
    * full_name : VARCHAR(100)
    email : VARCHAR(100)
    phone : VARCHAR(20)
}

entity "categories" as categories {
    * id : INT (PK)
    --
    * name : VARCHAR(100) (UQ)
    description : TEXT
    created_at : TIMESTAMP
}

entity "products" as products {
    * id : INT (PK)
    --
    * category_id : INT (FK)
    * sku : VARCHAR(50) (UQ)
    * name : VARCHAR(150)
    * price : DECIMAL(12,2)
    * stock : INT
    * minimum_stock : INT
    created_at : TIMESTAMP
}

entity "suppliers" as suppliers {
    * id : INT (PK)
    --
    * name : VARCHAR(150)
    contact_person : VARCHAR(100)
    phone : VARCHAR(30)
    address : TEXT
    created_at : TIMESTAMP
}

entity "product_suppliers" as product_suppliers {
    * product_id : INT (PK, FK)
    * supplier_id : INT (PK, FK)
    --
    supplier_price : DECIMAL(12,2)
}

entity "stock_movements" as stock_movements {
    * id : INT (PK)
    --
    * product_id : INT (FK)
    * user_id : INT (FK)
    * type : ENUM('IN','OUT')
    * quantity : INT
    notes : TEXT
    created_at : TIMESTAMP
}

users ||--|| user_profiles : "1 : 1 (has profile)"
categories ||--|{ products : "1 : N (categorizes)"
products ||--|{ product_suppliers : "1 : N (junction)"
suppliers ||--|{ product_suppliers : "1 : N (junction)"
products ||--|{ stock_movements : "1 : N (mutation log)"
users ||--|{ stock_movements : "1 : N (recorded by)"

@enduml
```

---

## 3. Metode Impor via SQL DDL (Draw.io Native)

Draw.io juga mendukung fitur membuat ERD otomatis langsung dari SQL DDL:

1. Di menu bar draw.io, klik: **Arrange &rarr; Insert &rarr; Advanced &rarr; SQL...**
2. Salin query berikut dan klik **Insert**:

```sql
CREATE TABLE users (
    id INT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    role VARCHAR(20),
    created_at TIMESTAMP
);

CREATE TABLE user_profiles (
    id INT PRIMARY KEY,
    user_id INT UNIQUE,
    full_name VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE categories (
    id INT PRIMARY KEY,
    name VARCHAR(100) UNIQUE,
    description TEXT,
    created_at TIMESTAMP
);

CREATE TABLE products (
    id INT PRIMARY KEY,
    category_id INT,
    sku VARCHAR(50) UNIQUE,
    name VARCHAR(150),
    price DECIMAL(12,2),
    stock INT,
    minimum_stock INT,
    created_at TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE suppliers (
    id INT PRIMARY KEY,
    name VARCHAR(150),
    contact_person VARCHAR(100),
    phone VARCHAR(30),
    address TEXT,
    created_at TIMESTAMP
);

CREATE TABLE product_suppliers (
    product_id INT,
    supplier_id INT,
    supplier_price DECIMAL(12,2),
    PRIMARY KEY (product_id, supplier_id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE stock_movements (
    id INT PRIMARY KEY,
    product_id INT,
    user_id INT,
    type VARCHAR(10),
    quantity INT,
    notes TEXT,
    created_at TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

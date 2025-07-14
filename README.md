# SIMS: Development of Sales and Inventory Management System Integrating E-Commerce for Small-Scale Clothing Enterprises in Lian Public Makret

Mar-Jay C. Alegar  
Allen John Y. Desacola  
Hanna Joyce M. Ilao  

Capstone Adviser: Asst. Prof. Benjie R. Samonte  


## Introduction/Summary
### Purpose

This project aims to help small Ready-to-Wear (RTW) clothing store owners in Lian Public Market improve how they manage their sales and inventory. Many of them still use manual methods, which often lead to errors and slow down daily operations. By introducing a digital system, the project will automate sales tracking, inventory monitoring, and restocking alerts. The goal is to make store management easier, more accurate, and more efficient so business owners can focus on growing their shops and making better decisions based on real-time data.


### Scope 

This project focuses on building a Sales and Inventory Management System (SIMS) with an added e-commerce feature for small clothing stores in Lian Public Market. The system will help store owners track inventory and sales in real-time while allowing customers to view items online and reserve them through a **click-and-collect** setup.

Store owners will be alerted when stock is low, and the system will use QR code scanning to speed up checkout and keep inventory accurate. A **web-based platform** will be used for managing the store, while a **mobile app** will support staff in handling transactions and inventory updates. One store in Lian will be used for testing to check how well the system works in real business operations.



### Definitions, acronyms and abbreviations

  The following terms are used in this study "SIMS: Sales and Inventory
Management System Integrating E-Commerce for Small-Scale Clothing Enterprises in Lian Public". The terms
are operationally and conceptually defined to establish a common understanding of
terms that may have different interpretations in other contexts.

- **Automated Alerts** – System-generated notifications that inform store owners and staff about critical updates, such as low stock levels, sales performance summaries, or discrepancies in inventory records.

- **Barcode-Based Product Identification** – The use of barcodes and scanners to uniquely identify and track products, improving sales efficiency and reducing human errors in manual inventory recording.

- **Business Intelligence (BI)** – The application of business analysis tools to asses the real-time monitoring, sales reports, system's decision making, and data performance.

- **Cloud-Based Backup** – – It is a cloud platform that stores the system data for secure and large-scale data protection to prevent data loss and enable remote access to sales and inventory records.

- **Data Accuracy** – Refers to the reliability and correctness of stored and processed data within the system, minimizing inconsistencies and ensuring accurate financial and inventory records.

- **Digital Sales Reporting** – The automated generation of reports which gives an overview of sales performance, revenue insights, and inventory status, enabling store owners in strategic decision-making.

- **Inventory Tracking** – An organized method for tracking and recording stock levels, incoming shipments, and outgoing sales to keep inventory records accurate and minimize stock errors.

- **Point of Sale (POS) System** – This refers to digital platform designed to execute sales transactions, record purchases, and process payments, commonly associated with inventory management for real-time stock updates.

- **QR Code Scanner** – A digital system or software-based tool that scans QR codes in every product to quickly retrieve product information, assist sales transactions, and update inventory databases in real-time.

- **Ready-to-Wear (RTW) Clothing Store** – Refers to a retail business that sells ready-made clothing in standard sizes, offering ready-to-wear apparel to customers without requiring further tailoring or customization.
  
- **Revenue Trends** – Analyzed trends in sales data over a specific period to evaluate financial performance, determine periods of highest sales, and forecast future revenue growth.

- **Sales and Inventory Management System (SIMS)** – Digital system designed to automate tracking of sales transactions and inventory levels in real-time, improving efficiency, accuracy, and business decision-making for store owners.
  
- **Stock Replenishment** – A product restocking procedure when inventory levels drop to a predetermined threshold to ensure continuous availability of goods for customers.

- **System Usability** – Refers to the user-friendliness and accessibility of the SIMS interface, designed to be user-friendly for store owners and staff with minimal technical expertise.

- **Transaction Logging** – A computerized record keeping for sales and inventory changes within the system, maintaining a recordable history of business activities for future reference and auditing.
  
- **User Authentication** – Security measures implemented within the system to restrict access to authorized personnel, ensuring that only store owners and designated staff can manage sales and inventory data.



## Overall Description
### Discuss system architecture

<img width="872" height="567" alt="image" src="https://github.com/user-attachments/assets/9eaf920c-737e-418c-84c1-8e6661bb7751" />


  The system is designed to help store owners and staff manage sales and inventory through a web-based platform. 
  Admins and staff use a dashboard and sales interface to access the system, which handles tasks like tracking 
  inventory, recording sales, and generating reports.
  All data—such as sales history and inventory records—is stored in a central database. The system also includes 
  a QR scanner to quickly read product info and a QR printer to create labels for new items. A recommender feature 
  suggests products to restock or promote, while a reporting tool helps store owners track business performance.
  Overall, this setup makes daily operations faster, more accurate, and easier to manage.

### Software perspective and functions
  
  #### Software Needed in the Development

| *Software*             | *Description*                                     | *Specification*                               |
|--------------------------|-----------------------------------------------------|--------------------------------------------------|
| Operating System         | Development & testing environment                  | Windows 10                                       |
| JavaScript               | Main language for mobile app development           | ECMAScript 2023                                  |
| Visual Studio Code       | Platform for coding and debugging                  | Version 1.75                                     |
| MySQL                    | Stores system data                                 | Version 8.0                                      |
| React.js                 | Used for developing the web-based system           | Latest Version                                   |
| React Native             | Mobile application interface                       | Version 0.72                                     |
| Node.js with Express.js  | Handles server-side logic and API requests         | Node.js 23                                       |
| QR Code Scanner          | Enables inventory updates via QR scanning          | React Native Vision Camera, QR Code Scanner      |
| JWT & Encrypted Storage  | Manages user access and secures data               | Version 0.12.1                                   |


The system is built using tools that make it efficient, secure, and easy to use. Windows 10 is used as the main 
operating system to ensure a stable development environment. JavaScript is the main programming language, 
supported by Visual Studio Code for writing and debugging code.
For the front end, React.js is used for the web version and React Native for the mobile app, making the system 
accessible on different devices. The back end uses Node.js with Express.js to handle system logic and communication 
with the database. MySQL stores important data like sales records, inventory, and user info.
To make sales and inventory updates faster, the system includes a QR scanner using React Native Vision Camera. 
Security is handled by JWT for user login and encrypted storage to protect sensitive data. All these tools work 
together to support small clothing store owners with a reliable and modern system.

### Add use case characteristics and other diagrams

#### Use Case Diagram

<img width="899" height="898" alt="image" src="https://github.com/user-attachments/assets/02e2e9fe-8352-448c-b7d8-61303e9dd63b" />

The Use Case Diagram gives a clear view of how users interact with the Sales and Inventory Management System. 
It highlights two main users: the Store Owner and the Staff. Each of them has access to specific tasks within the system.
The diagram groups all system functions inside a blue boundary, showing they are part of the system’s main features. 
Tasks like managing inventory, handling sales transactions, and viewing reports are shown as ovals, representing the 
key actions users can perform. Arrows between the users and these tasks indicate what each role can do.
Overall, this diagram helps explain how the system supports day-to-day business activities by showing who does what in a simple, visual way.


#### Requirements Analysis

<img width="747" height="456" alt="image" src="https://github.com/user-attachments/assets/de03c2ae-18d0-4477-aa03-d40f1991285f" />

The system is designed to handle key business operations efficiently and securely. It includes secure user login, inventory management, 
and sales processing. It can generate QR codes for new products, track sales automatically, and provide real-time sales and inventory reports.
To support better decision-making, the system offers restocking suggestions based on sales trends and sends alerts when stock is low. 
All data is regularly synced and backed up, ensuring both security and easy access when needed.


#### Context Diagram

<img width="951" height="387" alt="image" src="https://github.com/user-attachments/assets/89b2d0b1-5393-4627-a8a6-34bed322e86c" />

The context diagram shows how the Sales and Inventory Management System (SIMS) connects and exchanges data with its two main users: Admin and Staff. 
The Admin manages product information and receives important reports like sales trends, stock lists, and overall sales summaries to help with decision-making. 
The Staff enters product data and checks the stock list to keep track of inventory.
SIMS serves as the central system, handling all data input and output to make sure sales and inventory information stays accurate and up-to-date.


#### Data Flow Diagram

<img width="955" height="563" alt="image" src="https://github.com/user-attachments/assets/629b250f-e5d3-4994-b4d4-a6061d3da2e1" />

The Data Flow Diagram (DFD) shows how data moves through the system and how different parts work together. 
It maps out the key processes involved in managing products, inventory, and sales records.
The diagram also shows where data is stored and how it flows between the system and users like the Admin and Staff. 
It helps explain how information is collected, updated, and used to generate reports—making sure everything is accurate and organized for smooth business operations.



### Constraints, limitation and dependencies

  While the system offers helpful features for sales and inventory management, it also has some limitations. 
  It is designed specifically for clothing retailers, so it may not work well for other business types without changes. 
  The system only supports basic financial reports and does not include advanced accounting, payroll, or supplier management, which store owners must handle separately.
  QR code generation is available, but the system does not allow direct input of QR tag details, and it does not automate 
  BIR ledger or official receipts due to compliance rules. The mobile app is limited to processing transactions and does not support full business management features.
  Since the system depends on internet connectivity for real-time updates, it may not function properly in areas with unstable connections. 
  Despite these limitations, the project is focused on helping RTW store owners in Lian Public Market improve their daily operations. 
  Future updates could include features for broader business needs like supplier tracking, multi-store management, and more detailed financial tools.


## Specific Requirements (at least 50%)

#### Dashboard Features

<img width="836" height="395" alt="image" src="https://github.com/user-attachments/assets/263eff28-e271-4e72-8c33-5d9fc60ae56f" />

#### Inventory Features

<img width="831" height="395" alt="image" src="https://github.com/user-attachments/assets/3af61949-7e4b-4af5-9295-bff699cc5b72" />

#### Sales Report Features

<img width="830" height="395" alt="image" src="https://github.com/user-attachments/assets/8310139b-2d61-4662-926b-0ab71422d569" />

#### QR Printing Features

<img width="829" height="395" alt="image" src="https://github.com/user-attachments/assets/a03dddf1-84a8-404e-9ed1-243b42d26f02" />

#### Scan QR Features

<img width="832" height="395" alt="image" src="https://github.com/user-attachments/assets/f2f9dc8d-5f2a-491a-b4c4-5feeb6ce0451" />


#### 📁 File Structure

```
CAPSTONE/
├── assets/                 # Static assets
├── bootstrap5/            # Bootstrap framework
├── css/                   # Custom stylesheets
│   ├── dashboard.css      # Dashboard-specific styles
│   ├── inventory.css      # Inventory page styles
│   └── style.css          # Global styles
├── js/                    # JavaScript files
├── libraries/             # Third-party libraries
├── qr_codes/              # Generated QR code images
├── config.php             # Database configuration
├── dashboard.php          # Enhanced dashboard
├── inventory.php          # Inventory management
├── sidebar.php            # Main layout template
└── database_schema.sql    # Database structure
```


# SIMS: Development of Sales and Inventory Management System Integrating E-Commerce for Small-Scale Clothing Enterprises in Lian Public Makret

Mar-Jay C. Alegar  
Allen John Y. Desacola  
Hanna Joyce M. Ilao  

Capstone Adviser: Asst. Prof. Benjie R. Samonte  


## Introduction/Summary
### Purpose

  This study aims to help small-scale clothing enterprises in Lian Public Market improve how they manage their businesses. 
  Many of these store owners still rely on manual methods for tracking sales and inventory, which often lead to errors, delays, 
  and difficulties in keeping up with daily operations. To solve this, the researchers and developer will develop a digital system 
  that will automate sales recording , inventory tracking, and restocking alerts. One local store has been chosen to test the system, 
  with the goal of showing how it can make business operations more efficient, accurate, and easier to manage, especially for small
  business owners who want to grow and make better decisions using clear and updated data.

### Scope 

  This project focuses on building a Sales and Inventory management System (SIMS) specifically for small clothing enterprises in Lian
  public Market. IOt aims to help store owners track their sales and inventory more easily and accuratelty. The systemincludes a web 
  platform for full inventory and sales management, and mobile app for store staff to handle transactions and update inventory real-time.
  Features like QR code scanning, automatic low-stock alerts, and daily to monthly sales reports are included to make operations smoother 
  and more efficient. One local store will be used for testing to see how well the system works in a real setting.


###
  
### Enhanced Dashboard
- **Real-time Statistics**: Dynamic data showing total products, inventory value, low stock items, and out-of-stock items
- **Interactive Charts**: Category distribution chart using Chart.js
- **Quick Actions**: Easy access to common tasks like adding items, printing QR codes, and viewing reports
- **Recent Activity**: Shows recently added items with timestamps
- **Top Items**: Displays highest value items in inventory
- **Live Clock**: Real-time date and time display
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices

### Inventory Management
- Add, edit, and delete inventory items
- Automatic QR code generation for each item
- Category-based organization
- Stock level tracking
- Price management
- Search and filter functionality

### QR Code System
- Automatic QR code generation for inventory items
- QR code printing functionality
- QR code scanning capability
- Download QR codes as images

## 🛠️ Installation

1. **Setup Database**
   ```sql
   -- Import the database schema
   mysql -u root -p < database_schema.sql
   ```

2. **Configure Database Connection**
   - Edit `config.php` with your database credentials
   - Default database name: `sims`

3. **Web Server Setup**
   - Place files in your web server directory (e.g., `htdocs` for XAMPP)
   - Ensure PHP and MySQL are running

4. **Default Login**
   - Username: `admin`
   - Password: `admin123`

## 📊 Dashboard Features

### Statistics Cards
- **Total Products**: Count of all inventory items
- **Total Inventory Value**: Sum of (stock × price) for all items
- **Low Stock Items**: Items with stock less than 10
- **Out of Stock**: Items with zero stock

### Interactive Elements
- **Category Chart**: Doughnut chart showing product distribution by category
- **Quick Actions**: One-click access to common functions
- **Recent Items**: Latest 5 items added to inventory
- **Top Items**: Highest value items based on (stock × price)

### Real-time Updates
- Live clock display
- Dynamic data loading
- Smooth animations and transitions

## 🎨 Design Features

### Modern UI/UX
- Gradient backgrounds and modern card designs
- Smooth hover effects and transitions
- Responsive grid layout
- Custom scrollbars
- Bootstrap 5 components

### Color Scheme
- Primary: Purple gradient (#667eea to #764ba2)
- Success: Green (#28a745)
- Warning: Yellow/Orange (#ffc107)
- Danger: Red (#dc3545)
- Neutral: Gray tones

## 📱 Responsive Design

The dashboard is fully responsive and optimized for:
- **Desktop**: Full feature set with side-by-side layouts
- **Tablet**: Adjusted spacing and card layouts
- **Mobile**: Stacked layout with touch-friendly buttons

## 🔧 Technical Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5
- **Charts**: Chart.js
- **Icons**: Bootstrap Icons
- **QR Codes**: External API (qrserver.com)

## 📁 File Structure

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

## 🚀 Getting Started

1. Start your web server (XAMPP, WAMP, etc.)
2. Navigate to the project URL
3. Login with default credentials
4. Start adding inventory items
5. Explore the dashboard features

## 🔄 Recent Updates

### Dashboard Improvements
- ✅ Added real-time statistics from database
- ✅ Implemented interactive category chart
- ✅ Created quick action buttons
- ✅ Added recent activity feed
- ✅ Enhanced visual design with modern UI
- ✅ Improved responsive layout
- ✅ Added live clock functionality
- ✅ Implemented smooth animations

## 📞 Support

For questions or issues, please check:
1. Database connection settings in `config.php`
2. PHP error logs for debugging
3. Browser console for JavaScript errors
4. Ensure all required files are present

## 🔮 Future Enhancements

- Sales tracking and reporting
- User management system
- Advanced analytics
- Export functionality
- Email notifications
- Mobile app integration 

J.K BAGS — PHP + MYSQL E-COMMERCE WEBSITE
========================================

WHAT HAS BEEN FIXED
- Twelve distinct product photos are bundled locally and assigned to sample product cards.
- Product photos no longer depend on an external image site; all demo catalogue photos are local files.
- Admin login/setup is more reliable: missing tables are created and missing columns in older versions of the jkbags schema are migrated. The default demo admin can also be restored when missing.
- Admin dashboard includes Products, Orders, Customers and Messages sections.
- Admin can add/edit/delete products, upload JPG/PNG/WEBP product photos (max 5 MB), update order status and view customer messages.
- The store uses a responsive plum, blush-pink, lavender and cream design.
- Search, category filters, price sorting, login/register, cart, wishlist, checkout and order history are included.

RUN ON XAMPP (LOCAL)
1. Extract the ZIP.
2. Copy the JK-BAGS folder into: C:\xampp\htdocs\JK-BAGS\
3. Open XAMPP Control Panel and start Apache and MySQL.
4. Open: http://localhost/JK-BAGS/
5. The first request tries to create the jkbags database and required tables automatically (XAMPP root user with no password is the default). If your MySQL setup doesn't allow database creation, open phpMyAdmin, import JK-BAGS/database.sql, then refresh the site. Existing older project tables will be checked for missing columns and upgraded on the next page load.
6. Admin panel: http://localhost/JK-BAGS/admin/login.php

DEFAULT LOCAL ADMIN LOGIN
Email: admin@jkbags.com
Password: admin123
Change these demo credentials before hosting the website online.

PRODUCT PICTURES
Twelve individual sample bag photographs are included in assets/images/products/ as JPG files and work offline. From Admin > Add Product, upload your own JPG, PNG or WEBP photo to replace a sample image. Upload limit is 5 MB; your PHP upload_max_filesize setting must also permit the selected size.

DATABASE SETTINGS
Default local settings are: host=localhost, user=root, password blank, database=jkbags. For another environment, set DB_HOST, DB_USER, DB_PASS and DB_NAME environment variables, or edit the settings at the top of includes/db.php. Hosting users usually need to create a database through their hosting control panel and import database.sql, because shared hosting often disallows CREATE DATABASE from PHP.

ONLINE DEPLOYMENT
- Use PHP 8.1+ and MySQL/MariaDB with the mysqli extension enabled (the project includes a fallback for PHP builds without mysqlnd).
- Upload all project files and folders to your site's document root.
- Set the correct database credentials, force HTTPS, change the demo admin password, and remove unused demo products/prices.
- Test registration, login, cart, order placement, product photo upload, order updates and messages before launch.

LIMITATIONS
- Checkout saves a customer order request; online payment gateway, email/SMS notifications, password recovery and courier integration are not connected.
- The local demo credentials are for development only and must be changed before public launch.
- This build was PHP syntax-checked. The current build environment doesn't have mysqli/MySQL enabled, so a full live database/browser test can't be performed here; use XAMPP to verify the live database workflow.

TROUBLESHOOTING
- If the site says mysqli is not enabled, open XAMPP's php.ini, enable extension=mysqli, save, and restart Apache.
- If admin login loops back to the login page, confirm MySQL is running and use the exact URL shown above; the project creates/restores the local demo admin record during setup.
- If you see an SQL permission error, import database.sql from phpMyAdmin while using the jkbags database, then refresh the page.

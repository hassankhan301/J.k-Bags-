CREATE DATABASE IF NOT EXISTS jkbags CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jkbags;
CREATE TABLE IF NOT EXISTS users(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,email VARCHAR(150) UNIQUE NOT NULL,password VARCHAR(255) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS admins(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100) NOT NULL,email VARCHAR(150) UNIQUE NOT NULL,password VARCHAR(255) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS products(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(150) NOT NULL,category VARCHAR(80) NOT NULL,price DECIMAL(10,2) NOT NULL,stock INT DEFAULT 0,image TEXT NOT NULL,description TEXT,badge VARCHAR(40),featured TINYINT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS orders(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NULL,customer_name VARCHAR(100),phone VARCHAR(40),address TEXT,total DECIMAL(10,2),status VARCHAR(30) DEFAULT 'Pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL);
CREATE TABLE IF NOT EXISTS order_items(id INT AUTO_INCREMENT PRIMARY KEY,order_id INT NOT NULL,product_id INT NOT NULL,quantity INT NOT NULL,price DECIMAL(10,2) NOT NULL,FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,FOREIGN KEY(product_id) REFERENCES products(id));
CREATE TABLE IF NOT EXISTS messages(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(100),email VARCHAR(150),subject VARCHAR(200),message TEXT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
INSERT IGNORE INTO products(id,name,category,price,stock,image,description,badge,featured) VALUES
(1,'Luna Classic Bag','Hand Bag',8999,20,'assets/images/products/luna-classic.jpg','Elegant everyday handbag with a refined silhouette.','NEW',1),
(2,'Sophia Shoulder Bag','Shoulder Bag',7499,15,'assets/images/products/sophia-shoulder.jpg','A polished shoulder bag for day-to-evening style.','BEST SELLER',1),
(3,'Amelia Crossbody','Crossbody Bag',6999,18,'assets/images/products/amelia-crossbody.jpg','Compact crossbody design for effortless everyday use.','NEW',1),
(4,'Grace Evening Clutch','Clutch',5499,12,'assets/images/products/grace-evening-clutch.jpg','A sophisticated clutch for evenings and celebrations.','LIMITED',1),
(5,'Bella Mini Bag','Hand Bag',6299,25,'assets/images/products/bella-mini.jpg','Chic mini bag with a versatile modern look.','',0),
(6,'Victoria Tote','Tote Bag',9999,10,'assets/images/products/victoria-tote.jpg','Spacious premium tote for work and travel.','PREMIUM',1),
(7,'Elena Shoulder Bag','Shoulder Bag',7999,14,'assets/images/products/elena-shoulder.jpg','Elegant shoulder style with a timeless finish.','',0),
(8,'Rose Party Clutch','Clutch',4799,10,'assets/images/products/rose-party-clutch.jpg','A statement clutch for special occasions.','SALE',0),
(9,'Emma Luxury Bag','Hand Bag',10999,8,'assets/images/products/emma-luxury.jpg','Luxury-inspired statement handbag.','LUXURY',1),
(10,'Mia Crossbody','Crossbody Bag',5899,16,'assets/images/products/mia-crossbody.jpg','Lightweight crossbody for daily styling.','',0),
(11,'Charlotte Tote','Tote Bag',9499,9,'assets/images/products/charlotte-tote.jpg','Classic tote with a modern premium finish.','NEW',1),
(12,'Isabella Mini','Crossbody Bag',6799,13,'assets/images/products/isabella-mini.jpg','Minimal mini crossbody for effortless style.','',0);
-- Default admin password: admin123
INSERT INTO admins(id,name,email,password) VALUES(1,'J.K Admin','admin@jkbags.com','$2y$12$U2/15HaoEJjCdz8WUg0KtuRryVrgd5FjzwIELfu/0hTeazUZp3ERm') ON DUPLICATE KEY UPDATE name=VALUES(name),password=VALUES(password);

# StayC

StayC is a web platform connecting students with nearby dormitories, bedspaces, boarding houses and apartments around campus.

> Status: Completed & Deployed.

## Description

StayC is a web application that helps students find an apartment, dormitory, boarding house, or bedspace near their school. Students often depend on social media posts and word of mouth, which takes time, provides outdated information, and risks exposure to unverified listings. In StayC, owners post their listings with photos, price, available slots, location on the map, and detailed property information. Students can search, filter, save listings to favorites, and send inquiries in one place, while an administrator approves listings to keep the platform safe and authentic.

## Features

**Students**
- Register, log in, and log out
- Search and filter listings by price, location, and available slots
- View listings on an interactive map and find dorms near their location
- Save favorite listings
- Send inquiries or reservation requests to owners
- Receive in-system notifications, with a live badge and pop-up that update every 30 seconds
- Sort listings by nearest using the browser's location
- Edit profile details and upload an avatar with image validation

**Owners**
- Add, edit, and delete their own listings
- Upload listing photos with file validation
- Place the map pin from the typed address (forward geocoding), or fill in the address from the pin or GPS (reverse geocoding)
- View and respond to student inquiries
- Live dashboard with charts that refresh every 30 seconds
- Edit profile details and upload an avatar with image validation

**Administrators**
- Approve or reject new listings
- Manage users and remove inappropriate content
- Receive user inquiries and support reports via the admin contact page
- Edit profile details and upload an avatar with image validation

**General**
- Responsive design for desktop, tablet, and mobile
- Role-based access control (Student, Owner, Admin)
- Secure password hashing, sessions, and prepared SQL statements
- Dark mode saved with localStorage and synced across tabs
- Installable web app (PWA): web app manifest, service worker, offline page, and app icons
- Regex-based amenity engine that turns typed amenities ("Free Wi-Fi", "Aircon") into icons

## Technologies

- HTML5
- CSS3
- JavaScript ES6+
- Bootstrap 5
- Leaflet (OpenStreetMap) and Nominatim geocoding
- Chart.js
- PHP
- MySQL / MariaDB
- Fetch API, Geolocation API, Web Storage API, Service Worker API
- Git / GitHub

## Installation Instructions

Requirements: [XAMPP](https://www.apachefriends.org/) (Apache, PHP, MySQL) and [Git](https://git-scm.com/).

1. Start XAMPP and run Apache and MySQL.
2. Clone the repository into the XAMPP `htdocs` folder:
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/chelseabianes28-hue/dormfinder.git
   ```
3. Go to the `includes` folder, copy `config.example.php`, and rename the copy to `config.php`.
4. Open `config.php` and set your database values. The XAMPP defaults (user `root`, empty password) already work.
5. Set up the database (see below).
6. Open `http://localhost/dormfinder/` in your browser.

## Database Setup

1. Open phpMyAdmin at `http://localhost/phpmyadmin`.
2. Click the **Import** tab.
3. Choose the file `database/database.sql` and click **Import**.
4. A database named `dormfinder` with six tables is created: `users`, `listings`, `listing_photos`, `inquiries`, `favorites`, and `notifications`.

Alternatively, import from the command line:
```
mysql -u root < database/database.sql
```

Already have an older database? Run `database/add_avatar_column.sql` and `database/add_apartment_type.sql` once in the SQL tab.

## Screenshots

<img width="1365" height="720" alt="image" src="https://github.com/user-attachments/assets/de7d6613-7693-4c0f-a064-7a68d476e0b4" />
<img width="1365" height="721" alt="image" src="https://github.com/user-attachments/assets/a26a8f1f-5c07-47d6-a521-4e90fedf70fb" />
<img width="1365" height="718" alt="image" src="https://github.com/user-attachments/assets/b4b4e2ee-1d25-4c6e-af07-8f9f5ae973a1" />
<img width="1365" height="716" alt="image" src="https://github.com/user-attachments/assets/e368158c-da40-41f3-8f6b-fb33e2757acb" />
<img width="1365" height="713" alt="image" src="https://github.com/user-attachments/assets/26711dd0-d78f-4e1e-a724-292f73fa0b93" />
<img width="1365" height="715" alt="image" src="https://github.com/user-attachments/assets/a822e912-387d-4e01-81f1-e585ff0d13d9" />
<img width="1365" height="719" alt="image" src="https://github.com/user-attachments/assets/9ca5d1c5-71c7-4be0-95af-916beb120205" />
<img width="1364" height="717" alt="image" src="https://github.com/user-attachments/assets/a7e47ad8-870a-46b9-9e4d-b425f2c5b283" />
<img width="1365" height="717" alt="image" src="https://github.com/user-attachments/assets/616c25fd-7a17-4f70-b926-ee9d61c71ab4" />
<img width="1365" height="720" alt="image" src="https://github.com/user-attachments/assets/6c1bf37c-7a72-4d12-bc98-a927dc91628c" />
<img width="1365" height="716" alt="image" src="https://github.com/user-attachments/assets/7d38393a-e096-498e-bbf1-de1b51408774" />
<img width="1365" height="721" alt="image" src="https://github.com/user-attachments/assets/764cb549-503f-47a0-853a-70f4522bb750" />
<img width="1365" height="719" alt="image" src="https://github.com/user-attachments/assets/98ac8e20-48e0-456f-847f-a129a1c1c434" />
<img width="1363" height="718" alt="image" src="https://github.com/user-attachments/assets/854e72c0-251c-4817-8b1f-8aedd332a4ea" />
<img width="1364" height="720" alt="image" src="https://github.com/user-attachments/assets/f53f7d20-501b-4ad2-99e1-c1b2580ef945" />
<img width="1365" height="718" alt="image" src="https://github.com/user-attachments/assets/e4703c6a-86bc-40af-8de5-35f5001143f0" />
<img width="1354" height="717" alt="image" src="https://github.com/user-attachments/assets/720970a2-0335-4916-91fa-a5a22797e1a6" />
<img width="1365" height="716" alt="image" src="https://github.com/user-attachments/assets/5cf98e6c-b263-416f-8b68-5659628c3722" />
<img width="1363" height="717" alt="image" src="https://github.com/user-attachments/assets/bf5a15b9-e110-4a66-9464-6648b3b81a05" />
<img width="1365" height="719" alt="image" src="https://github.com/user-attachments/assets/15686dd4-f839-4e1c-a3e4-cf6e407a713b" />
<img width="1365" height="716" alt="image" src="https://github.com/user-attachments/assets/e77fd0a5-2c16-4fc0-8ff7-8462baf92938" />
<img width="1364" height="718" alt="image" src="https://github.com/user-attachments/assets/080086fd-206d-45be-b57a-1e1150c6e41e" />
<img width="1365" height="721" alt="image" src="https://github.com/user-attachments/assets/0dab2bb5-2da9-47dd-91f7-4209847b4547" />
<img width="1365" height="717" alt="image" src="https://github.com/user-attachments/assets/d45fe9ec-4161-4b6a-97e9-371cbf796451" />
<img width="1365" height="717" alt="image" src="https://github.com/user-attachments/assets/f559556b-59f8-4fcc-aa01-582db8256cad" />
<img width="1365" height="717" alt="image" src="https://github.com/user-attachments/assets/90dd453e-eeee-40b2-ad4d-02a3dc593a4b" />
<img width="1365" height="717" alt="image" src="https://github.com/user-attachments/assets/d5713d5b-81c3-40fa-985d-1b8619d03790" />
<img width="1363" height="717" alt="image" src="https://github.com/user-attachments/assets/6157fdad-0ad6-4ca9-b42a-bf65b7e466ca" />
<img width="1358" height="717" alt="image" src="https://github.com/user-attachments/assets/94139660-685a-484c-a603-118692452bfa" />
<img width="1365" height="718" alt="image" src="https://github.com/user-attachments/assets/f0701b80-465e-42f7-b239-5bcfb39ba1eb" />
<img width="1364" height="714" alt="image" src="https://github.com/user-attachments/assets/f938fa7b-4928-4478-824c-786508e7fcb2" />
<img width="1365" height="717" alt="image" src="https://github.com/user-attachments/assets/65c97601-43cd-47fa-9110-126f93cfd8e0" />
<img width="1365" height="714" alt="image" src="https://github.com/user-attachments/assets/6ddab350-2030-41a2-8e5b-2c633a8ee272" />

## Live Demo

Production Web Application: [https://dormfinder.free.je](https://dormfinder.free.je)

## Developers

- Chelsea Enya Sophia A. Bianes: sole developer (design, front-end, back-end, database, and deployment)

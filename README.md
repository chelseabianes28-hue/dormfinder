# DormFinder

Dormitory and Bedspace Finder for Students

> Status: in development. Features are added section by section, and the commit history shows the progress.

## Description

DormFinder is a web application that helps students find an affordable dormitory or bedspace near their school. Students often depend on Facebook posts and word of mouth, which takes time and gives outdated information. In DormFinder, owners post their listings with photos, price, available slots, and a location on the map. Students search, filter, and send inquiries in one place, while an administrator approves listings and keeps the platform safe.

## Features

**Students**
- Register, log in, and log out
- Search and filter listings by price, location, and available slots
- View listings on an interactive map and find dorms near their location
- Save favorite listings
- Send inquiries or reservation requests to owners
- Receive in-system notifications

**Owners**
- Add, edit, and delete their own listings
- Upload listing photos with file validation
- View and respond to student inquiries
- Dashboard with charts

**Administrators**
- Approve or reject new listings
- Manage users and remove inappropriate content

**General**
- Responsive design for desktop, tablet, and mobile
- Role-based access control (Student, Owner, Admin)
- Secure password hashing, sessions, and prepared SQL statements
- Dark mode and installable web app (PWA)

## Technologies

- HTML5
- CSS3
- JavaScript ES6+
- Bootstrap 5
- Leaflet (OpenStreetMap)
- Chart.js
- PHP
- MySQL / MariaDB
- Fetch API
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

## Screenshots

(To be added.)

## Live Demo

(Deployed URL to be added.)

## Developers

- ces bianes: sole developer (design, front-end, back-end, database, and deployment)

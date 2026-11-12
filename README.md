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
- Receive in-system notifications

**Owners**
- Add, edit, and delete their own listings
- Upload listing photos with file validation
- View and respond to student inquiries
- Dashboard with charts

**Administrators**
- Approve or reject new listings
- Manage users and remove inappropriate content
- Receive user inquiries and support reports via the admin contact page

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

<img width="964" height="507" alt="image" src="https://github.com/user-attachments/assets/11036537-65cf-4ebe-9215-233d465e64cb" />
<img width="963" height="506" alt="image" src="https://github.com/user-attachments/assets/6bea0a43-4c03-4a6f-ade5-a4c4307244d5" />
<img width="981" height="510" alt="image" src="https://github.com/user-attachments/assets/fecb61e2-b948-4579-a8f7-2f4cf28e425f" />
<img width="1012" height="535" alt="image" src="https://github.com/user-attachments/assets/d158c28c-e534-46ed-93d3-9ef44e1dcb4f" />
<img width="996" height="521" alt="image" src="https://github.com/user-attachments/assets/8aa95f20-cec5-4aa5-983b-f90e98cdf99f" />
<img width="1002" height="524" alt="image" src="https://github.com/user-attachments/assets/03cb8b44-b539-4083-8997-2989f9040c43" />
<img width="1001" height="516" alt="image" src="https://github.com/user-attachments/assets/4aa48d88-a962-48b6-bf35-0db97992957c" />
<img width="1010" height="531" alt="image" src="https://github.com/user-attachments/assets/bd08160a-7496-4256-abd8-673b7ac63ab0" />
<img width="1012" height="518" alt="image" src="https://github.com/user-attachments/assets/94bf9c51-1979-481c-935d-e39894977737" />
<img width="1012" height="529" alt="image" src="https://github.com/user-attachments/assets/877b9759-298a-4474-a7a5-40b6a26fcb32" />
<img width="993" height="525" alt="image" src="https://github.com/user-attachments/assets/a21076ec-3a19-4aec-a296-804f7eb82046" />
<img width="993" height="518" alt="image" src="https://github.com/user-attachments/assets/f6362272-789f-407e-9ad6-bf51c00005b6" />
<img width="988" height="515" alt="image" src="https://github.com/user-attachments/assets/729508cd-019a-4e84-9677-e998b610e355" />
<img width="984" height="517" alt="image" src="https://github.com/user-attachments/assets/19fa10fe-bf78-46b5-9fdc-018f3b2fb98e" />
<img width="958" height="501" alt="image" src="https://github.com/user-attachments/assets/45c90f2d-eb27-494b-b314-68d78a95aa2d" />
<img width="957" height="493" alt="image" src="https://github.com/user-attachments/assets/35773b95-116b-4252-9f04-6880f8892615" />
<img width="963" height="505" alt="image" src="https://github.com/user-attachments/assets/69a886be-a66e-4dcc-bd21-20d7aaa8d345" />
<img width="953" height="495" alt="image" src="https://github.com/user-attachments/assets/450fcfea-bd2e-4cd4-8222-ef29a9b7faff" />
<img width="968" height="509" alt="image" src="https://github.com/user-attachments/assets/5bf3c379-4a8f-42fe-83f1-13ca9d773ac5" />
<img width="1364" height="716" alt="image" src="https://github.com/user-attachments/assets/89144e6b-019e-48eb-a73f-93d54d283eb5" />
<img width="1365" height="715" alt="image" src="https://github.com/user-attachments/assets/06e43c4f-cb38-4476-8857-6122b3c3033e" />
<img width="968" height="515" alt="image" src="https://github.com/user-attachments/assets/e2fedbf0-3bc3-4499-96d1-9ca5422d4541" />
<img width="962" height="506" alt="image" src="https://github.com/user-attachments/assets/1c1a1f0c-2aca-4463-891d-49b03eec3d5b" />
<img width="983" height="506" alt="image" src="https://github.com/user-attachments/assets/526cef22-dd42-4675-b57e-03ee2df58277" />
<img width="989" height="512" alt="image" src="https://github.com/user-attachments/assets/92cb5f93-e462-451c-8ef1-8042b89942f4" />
<img width="973" height="508" alt="image" src="https://github.com/user-attachments/assets/a5e8a688-0d3c-4717-bd2d-807483c271ba" />
<img width="979" height="504" alt="image" src="https://github.com/user-attachments/assets/ee72232f-00da-464f-b152-2ee68dd8ecf3" />
<img width="987" height="514" alt="image" src="https://github.com/user-attachments/assets/2fe32b2a-dfbd-4c05-a05e-2734eef04554" />
<img width="999" height="501" alt="image" src="https://github.com/user-attachments/assets/6dfe52dc-640b-42f2-9108-1edcd86b83d5" />
<img width="1002" height="527" alt="image" src="https://github.com/user-attachments/assets/bf609f99-e50f-4a29-9103-e43ba24eda82" />
<img width="1022" height="529" alt="image" src="https://github.com/user-attachments/assets/387b06c4-11cb-4a4b-a966-4848c944d58e" />



## Live Demo

Production Web Application: [https://dormfinder.free.je](https://dormfinder.free.je)

## Developers

- Chelsea Enya Sophia A. Bianes: sole developer (design, front-end, back-end, database, and deployment)

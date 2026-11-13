# Deployment guide: DormFinder on free PHP and MySQL hosting

DormFinder uses PHP and MySQL, so it needs a host that runs both. Static hosts such as GitHub Pages,
Cloudflare Pages, Netlify, and Vercel cannot run PHP or MySQL. The host chosen for this project is
InfinityFree (free plan). Tell your instructor which host you use.

## What the host must provide
- PHP 8 or newer, with the fileinfo, mbstring, and gd extensions (gd is optional)
- A MySQL or MariaDB database with phpMyAdmin
- Apache with .htaccess support
- A free subdomain with HTTPS

## Limits of the free plan to keep in mind
- About 50,000 hits per day. Every page, image, CSS, and JS file counts as a hit. If the limit is reached, the account goes offline for 24 hours.
- Accounts can be deactivated if nobody visits for several weeks, so open the site a few days before the defense.
- Remote MySQL connections are not allowed, so import the database with phpMyAdmin on the host.
- The address search on the Add listing form calls OpenStreetMap from the server. Some free hosts block outgoing requests. If it fails, the owner clicks the map to place the pin, which already works.

## Steps
1. Create a hosting account and a free subdomain. Note the control panel address, the FTP details, and the final website address.
2. In the control panel, open MySQL Databases and create a database. Note the database name, user name, password, and the MySQL host name. The host name is not "localhost" on most free hosts.
3. Open phpMyAdmin from the control panel, click your database, click Import, and import `database/database_hosting.sql`. Six tables should appear. If the database already existed before the "Apartment" type was added, also run `database/add_apartment_type.sql` in the SQL tab.
4. Upload the project files with FTP (for example FileZilla) or the online file manager into the website folder (usually `htdocs`). Upload everything except the `database` folder and the `.git` folder. Make sure `manifest.json`, `sw.js`, and `offline.html` are in the same folder as `index.php` (they make the site installable).
5. On the host, create `includes/config.php` by copying `includes/config.example.php` and filling in the host values:
   - `db_host`: the MySQL host name from step 2
   - `db_name`, `db_user`, `db_pass`: from step 2
   - `admin_email`: the email shown on the Contact page
6. Open the website address. The home page should load.
7. Register an account, then in phpMyAdmin open the `users` table and change that account's `role` to `admin`. Log in again.
8. Test everything online (see the checklist below).
9. Add the live address to the README (Live Demo) and to the submission `README.txt`.

## Online testing checklist
- Home page, Find a dorm, and a listing page load with CSS and images
- Register, log in, and log out work for a student, an owner, and the admin
- Owner can add, edit, and delete a listing with photos (uploaded files appear)
- Search and filters on Find a dorm work (Fetch API)
- Student can send an inquiry and save a favorite; owner can accept or decline
- Admin can approve or reject listings and disable users
- Dashboards and charts load
- Dark mode stays after refresh
- The browser offers to install the app (address-bar install icon, or Add to Home Screen on a phone)
- Dashboard shows "Live · updates every 30 seconds"; a new inquiry makes the owner's badge go up without reloading
- On the Add listing form, clicking the map with an empty address fills in the suggested address
- Pages look right at 1366 x 768, 768 x 1024, and 375 x 667

## Troubleshooting
- White page or "500 error": check that PHP is version 8 or newer and that the `.htaccess` files were uploaded in full.
- "Unable to connect to the database": check `includes/config.php` (host name, database name, user, password).
- Photos do not show: check that the `uploads` folder exists and is writable on the host.
- Pages show without styling: check that the `assets` folder was uploaded.

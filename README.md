**while upload to cpanel if you face live issue- then check these first
you must check the  New Document Root - apidropship.biswasandbrothers.com/public  -- like i added public in the path in apidropship then i get the response.
***cgi bin and .wellknown is defaukt
****image is not showing in live site. Run the following Artisan command to create a symbolic link from the storage folder to the public folder:
 php artisan storage:link
** when new project for api setup go to bootsrap folder -> app.php and add
 api: __DIR__.'/../routes/api.php',

 

-use this token
-prefix_67e12b036e3f06.63889147


php artisan storage:link


mysql -u root -p
DB_PASSWORD=mir0188_2024
SHOW DATABASES;
USE hrm_api;
SHOW TABLES;
DELETE FROM chat_messages;
DROP TABLE prospect_log_activities;
DESCRIBE project_phases;
php artisan make:model UserFeaturePermission -mc  
php artisan make:migration MainCategories
php artisan route:list
php artisan route:clear
php artisan make:controller ProductVarientController --resource
php artisan make:model FacebookLeads -m //table and model
// change a parameter type 
ALTER TABLE tasks MODIFY COLUMN total_duration INT; //INT,VARCHAR(255), DOUBLE
<!-- example of adding a timestamp column ----- 
ALTER TABLE prospects ADD COLUMN last_activity TIMESTAMP NULL DEFAULT NULL; -->

ALTER TABLE users ADD start_min INT NULL; // add new column
ALTER TABLE donation_project_models DROP COLUMN project_id; // remove a column

01980911466
01810102520
//check
php artisan migrate:status
php artisan migrate:reset  ====it will reset all your data in DB
php artisan migrate:fresh --seed  ====Warning: migrate:fresh will delete all existing tables and recreate them.

{
    if nothing to migrate shows 
then try --- 
php artisan migrate:rollback 
then
php artisan migrate 
again
}




{----
    pabbly - create workflow 

1. Facebook lead ads
2. mysql  (get host name from whm from the top bar of dashboard , then make permission of the third party port to remote access database  from cpanel )
3. create database for facebook_leads

-----}
<!-- if you want to add a list of rows from mysql database -- select phpmyadmin - select a database - go sql tab and paste like 

INSERT INTO designations (designation_name, isActive, created_at, updated_at) VALUES
('CEO', true, NOW(), NOW()),
('Managing Director', true, NOW(), NOW()),
('Director', true, NOW(), NOW()),
('General Manager', true, NOW(), NOW()),
('Deputy General Manager', true, NOW(), NOW()),
('Assistant General Manager', true, NOW(), NOW()), -->


hostinger
ls -la
# railwayplanner

## Setup

This server uses Apache2, [MySQL 8.0](https://www.itsupportwale.com/blog/how-to-install-mysql-8-on-ubuntu-18-04/)
and PHP 7.2

1. Add 
    ```[client]
    default-character-set = utf8mb4
    local_infile=1

    [mysql]
    default-character-set = utf8mb4

    [mysqld]
    character-set-client-handshake = FALSE
    character-set-server = utf8mb4
    collation-server = utf8mb4_unicode_ci
    local_infile=1
    ```
    to your my.cnf file (usually under /etc/mysql/my.cnf). This ensures the databases uses utf8 encoding to store text
2. The data is sourced from the 2020 timetable for the Swiss public transport network in GTFS format, available at [the Swiss Transportation Open Data Platform](https://opentransportdata.swiss/en/dataset/timetable-2020-gtfs). Download the gtfs .zip file [here](https://opentransportdata.swiss/dataset/6f55f96d-7644-4901-b927-e9cf05a8c7f0/resource/d1c6b09b-52f5-49b2-861a-322840c9dc37/download/gtfsfp20202020-01-29.zip)
3. In the projet root (the same level as the "sql/" and "html/" directories), create a new folder called "data". In the "data/" folder, create a new folder called "clean", a folder named "config", and a folder named "log".
4. Extract the gtfs .zip files into the "data/" directory.
5. Run all python scripts in the "scipts/" folder, these will clean the data, and create .csv files in "data/clean/ that correspond to the database schema. A command that might execute this for you is `for f in scripts/*.py; do python "$f"; done`. It might take a while.
6. Create a mysql database and connect to it via the mysql console (something like `sudo mysql <name of database>` or `mysql -u <username> -p <name of database`>)
7. In the console, run the table creation script like so: `SOURCE <path to root>/sql/create_tables.sql`
8. Then insert the csv data into the database with `SOURCE <path to root>/sql/insert_data.sql`. You will have to change the paths to the data files in insert_data.sql. Depending on the dataset used, this will take quite a while to complete (it took ~10 minutes on my machine.
9. Run the uniquify_routes.sql script, which will modify routes so that every trip on a specific route has the exact same stops. This is needed for the RAPTOR pathfinding algorithm.
10. Inside "config/", create a file named "db_cfg.ini" with content: 
    ```
    [database_credentials]
    host = "<database host, usually localhost>"
    user = "<database user>"
    password = "<database user password>"
    database = "<name of database>"
    ```
11. On linux, run command `chmod -R a+rw log` to make the log folder writable to other users
12. Install composer using `sudo apt install composer` then in the command line, navigate to the project root
13. In the project root, run `composer require monolog/monolog` to install monolog


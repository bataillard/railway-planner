# railwayplanner

## Setup

This server uses Apache2, MySQL 14 and PHP 7.2

1. Add 
    ```[client]
    default-character-set = utf8mb4

    [mysql]
    default-character-set = utf8mb4

    [mysqld]
    character-set-client-handshake = FALSE
    character-set-server = utf8mb4
    collation-server = utf8mb4_unicode_ci```
    to your my.cnf file (usually under /etc/mysql/my.cnf). This ensures the databases uses utf8 encoding to store text
2. The data is sourced from the 2020 timetable for the Swiss public transport network in GTFS format, available at [the Swiss Transportation Open Data Platform](https://opentransportdata.swiss/en/dataset/timetable-2020-gtfs). Download the gtfs .zip file
3. In the projet root (the same level as the "sql/" and "html/" directories), create a new folder called "data". In the "data/" folder, create a new folder called "clean". 
4. Extract the gtfs .zip files into the "data/" directory.
5. Run all python scripts in the "scipts/" folder, these will clean the data, and create .csv files in "data/clean/ that correspond to the database schema. A command that might execute this for you is `for f in scripts/*.py; do python "$f"; done`. It might take a while.
6. Create a mysql database and connect to it via the mysql console (something like `sudo mysql <name of database>` or `mysql -u <username> -p <name of database`>)
7. In the console, run the table creation script like so: `SOURCE <path to root>/sql/create_tables.sql`
8. Then insert the csv data into the database with `SOURCE <path to root>/sql/insert_data.sql`. Depending on the dataset used, this will take quite a while to complete.
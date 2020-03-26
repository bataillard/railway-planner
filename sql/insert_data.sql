LOAD DATA LOCAL INFILE "/home/luca/Coding/CPSC304/railwayplanner/data/clean/agency.csv"
INTO TABLE Agency
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS;

LOAD DATA LOCAL INFILE "/home/luca/Coding/CPSC304/railwayplanner/data/clean/stops.csv"
INTO TABLE Stop
FIELDS TERMINATED BY ','
ENCLOSED BY '"' 
LINES TERMINATED BY '\n'
IGNORE 1 ROWS;

LOAD DATA LOCAL INFILE "/home/luca/Coding/CPSC304/railwayplanner/data/clean/service.csv"
INTO TABLE Service
FIELDS TERMINATED BY ','
ENCLOSED BY '"' 
LINES TERMINATED BY '\n'
IGNORE 1 ROWS;


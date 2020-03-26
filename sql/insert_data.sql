LOAD DATA LOCAL INFILE "/home/luca/Coding/CPSC304/railwayplanner/data/clean/agency.csv"
INTO TABLE Agency
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE "/home/luca/Coding/CPSC304/railwayplanner/data/clean/stop.csv"
INTO TABLE Stop
FIELDS TERMINATED BY ','
ENCLOSED BY '"' 
LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE "/home/luca/Coding/CPSC304/railwayplanner/data/clean/track.csv"
INTO TABLE Track
FIELDS TERMINATED BY ','
ENCLOSED BY '"' 
LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE "/home/luca/Coding/CPSC304/railwayplanner/data/clean/service.csv"
INTO TABLE Service
FIELDS TERMINATED BY ','
ENCLOSED BY '"' 
LINES TERMINATED BY '\n';

LOAD DATA LOCAL INFILE "/home/luca/Coding/CPSC304/railwayplanner/data/clean/route.csv"
INTO TABLE Route
FIELDS TERMINATED BY ','
ENCLOSED BY '"' 
LINES TERMINATED BY '\n';




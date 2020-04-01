WITH IC5Trips AS (
	SELECT trip_id
	FROM Route NATURAL JOIN Trip
	WHERE route_type LIKE "Intercity" AND route_name LIKE "5"),
TripStopTime AS (
	SELECT ST.*
	FROM IC5Trips T NATURAL JOIN StopTime ST),
TripMinMaxSeq AS (
	SELECT MIN(ST1.stop_sequence) AS min_stop, 
		   MAX(ST2.stop_sequence) AS max_stop, 
		   T.trip_id 
	FROM IC5Trips T NATURAL JOIN TripStopTime ST1 NATURAL JOIN TripStopTime ST2
    GROUP BY T.trip_id),
TripStartEndTime AS (
	SELECT T.trip_id, ST1.departure_time, ST2.arrival_time
    FROM TripMinMaxSeq T
		JOIN TripStopTime ST1 ON T.trip_id = ST1.trip_id
        JOIN TripStopTime ST2 ON T.trip_id = ST2.trip_id
	WHERE T.min_stop = ST1.stop_sequence AND T.max_stop = ST2.stop_sequence),
RouteTrips AS (
    SELECT *
    FROM TripStartEndTime T NATURAL JOIN TripStartEndTime TSET
    ORDER BY TSET.departure_time
)

SELECT * FROM RouteTrips;
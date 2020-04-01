-- Alter Routes so that each trip on the route has the same stops

-- For each route, create a new route for each different number of stops on that route
INSERT INTO Route
WITH RouteTripStops AS (
    SELECT DISTINCT R.route_id, T.trip_id, CONCAT(JSON_ARRAYAGG(CONCAT(ST.stop_id, "T", ST.track))) AS stops
    FROM Route R NATURAL JOIN Trip T, StopTime ST
    WHERE T.trip_id = ST.trip_id
    GROUP BY R.route_id, T.trip_id
), RouteStops AS (
    SELECT DISTINCT route_id, stops
    FROM RouteTripStops
), RouteStopsVariant AS (
    SELECT route_id, ROW_NUMBER() OVER (ORDER BY stops) AS variant, stops
    FROM RouteStops)
SELECT CONCAT(R.route_id, '-VAR-', RSV.variant) AS route_id, R.agency_id, R.route_type, R.route_name
FROM Route R NATURAL JOIN RouteStopsVariant RSV;

-- Modify each trip to point to the route variant with the same stops
WITH RouteTripStops AS (
    SELECT DISTINCT R.route_id, T.trip_id, CONCAT(JSON_ARRAYAGG(CONCAT(ST.stop_id, "T", ST.track))) AS stops
    FROM Route R NATURAL JOIN Trip T, StopTime ST
    WHERE T.trip_id = ST.trip_id
    GROUP BY R.route_id, T.trip_id
), RouteStops AS (
    SELECT DISTINCT route_id, stops
    FROM RouteTripStops
), RouteStopsVariant AS (
    SELECT route_id, ROW_NUMBER() OVER (ORDER BY stops) AS variant, stops
    FROM RouteStops
), RouteTripVariant AS (
    SELECT DISTINCT RTS.route_id, RTS.trip_id, RSV.variant
    FROM RouteStopsVariant RSV
             INNER JOIN RouteTripStops RTS ON RTS.route_id = RSV.route_id AND RSV.stops = RTS.stops)
UPDATE Trip TMain INNER JOIN RouteTripVariant RTV
    ON TMain.trip_id = RTV.trip_id
SET TMain.route_id = CONCAT(TMain.route_id,  "-VAR-", RTV.variant)
WHERE 1;

-- Delete the old routes
DELETE FROM Route WHERE route_id NOT LIKE "%-VAR-%";

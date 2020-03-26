
-- Agency and employee management

CREATE TABLE Agency (
    agency_id VARCHAR(50) PRIMARY KEY,
    agency_name VARCHAR(100)
);

-- Train infrastucture tables

CREATE TABLE Stop (
    stop_id VARCHAR(50) PRIMARY KEY,
    stop_lat DECIMAL(9,6),
    stop_long DECIMAL(9,6),
    stop_name VARCHAR(100)
);

CREATE TABLE Track (
    stop_id VARCHAR(50),
    track VARCHAR(50),

    PRIMARY KEY (stop_id, track),
    FOREIGN KEY (stop_id) REFERENCES Stop(stop_id)
);

CREATE TABLE Transfer (
    from_stop_id VARCHAR(50),
    from_track VARCHAR(50),
    to_stop_id VARCHAR(50),
    to_track VARCHAR(50),

    transfer_time INTEGER,

    PRIMARY KEY (from_stop_id, from_track, to_stop_id, to_track),
    FOREIGN KEY (from_stop_id, from_track) REFERENCES Track(stop_id, track),
    FOREIGN KEY (to_stop_id, to_track) REFERENCES Track(stop_id, track)
);

CREATE TABLE Route (
    route_id VARCHAR(50) PRIMARY KEY,
    agency_id VARCHAR(50) NOT NULL,
    route_type VARCHAR(50),
    route_name VARCHAR(50),

    FOREIGN KEY (agency_id) REFERENCES Agency(agency_id)
);

CREATE TABLE Service (
    service_id VARCHAR(50) PRIMARY KEY,

    runs_monday BOOLEAN,    
    runs_tuesday BOOLEAN,
    runs_wednesday BOOLEAN,
    runs_thursday BOOLEAN,
    runs_friday BOOLEAN,
    runs_saturday BOOLEAN,
    runs_sunday BOOLEAN,

    start_date DATE,
    end_date DATE
);

-- Train trip scheduling tables

CREATE TABLE TripName (
    trip_name VARCHAR(50) PRIMARY KEY,
    headsign VARCHAR(100)
);

CREATE TABLE Trip (
    trip_id VARCHAR(50) PRIMARY KEY,
    service_id VARCHAR(50) NOT NULL,
    route_id VARCHAR(50) NOT NULL,

    trip_name VARCHAR(50),
    headsign VARCHAR(100),
    wheelchair_accessible BOOLEAN,
    bikes_allowed BOOLEAN,

    FOREIGN KEY (service_id) REFERENCES Service(service_id),
    FOREIGN KEY (route_id) REFERENCES Route(route_id),
    FOREIGN KEY (trip_name) REFERENCES TripName(trip_name)
);

-- Actors definition

CREATE TABLE Passenger (
    passenger_id INTEGER PRIMARY KEY AUTO_INCREMENT,
    passenger_name VARCHAR(200),
    passenger_password VARCHAR(255)
);

CREATE TABLE TrainGuard (
    employee_id INTEGER PRIMARY KEY AUTO_INCREMENT,
    agency_id VARCHAR(50) NOT NULL,
    employee_name VARCHAR(200),
    employee_password VARCHAR(255)
);

-- Itinerary management

CREATE TABLE Itinerary (
    itinerary_id INTEGER PRIMARY KEY AUTO_INCREMENT,
    starting_stop VARCHAR(50),
    end_stop VARCHAR(50)
);

CREATE TABLE PassengerItinerary (
    passenger_id INTEGER,
    itinerary_id INTEGER,

    PRIMARY KEY (passenger_id, itinerary_id),
    FOREIGN KEY (passenger_id) REFERENCES Passenger(passenger_id),
    FOREIGN KEY (itinerary_id) REFERENCES Itinerary(itinerary_id)
);

CREATE TABLE VehicleChange (
    itinerary_id INTEGER,
    trip_id VARCHAR(50),
    stop_id VARCHAR(50),
    track VARCHAR(50),
    board BOOLEAN,

    PRIMARY KEY (itinerary_id, trip_id, stop_id, track, board),
    FOREIGN KEY (itinerary_id) REFERENCES Itinerary(itinerary_id),
    FOREIGN KEY (trip_id) REFERENCES Trip(trip_id),
    FOREIGN KEY (stop_id, track) REFERENCES Track(stop_id, track)
);

-- Passenger and employee actions

CREATE TABLE CyclistReservation (
    passenger_id INTEGER,
    itinerary_id INTEGER,

    PRIMARY KEY (passenger_id, itinerary_id),
    FOREIGN KEY (passenger_id, itinerary_id) REFERENCES PassengerItinerary(passenger_id, itinerary_id)
);

CREATE TABLE Patrols (
    employee_id INTEGER,
    trip_id VARCHAR(50),

    PRIMARY KEY (employee_id, trip_id),
    FOREIGN KEY (employee_id) REFERENCES TrainGuard(employee_id),
    FOREIGN KEY (trip_id) REFERENCES Trip(trip_id)
);

CREATE TABLE TicketCheck (
    itinerary_id INTEGER,
    passenger_id INTEGER,
    employee_id INTEGER,
    valid_ticket BOOLEAN,
    check_time DATETIME,

    PRIMARY KEY (passenger_id, itinerary_id, employee_id),
    FOREIGN KEY (passenger_id, itinerary_id) REFERENCES PassengerItinerary(passenger_id, itinerary_id),
    FOREIGN KEY (employee_id) REFERENCES TrainGuard(employee_id)
);



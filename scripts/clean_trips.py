import re
import csv

from constants import HOME_PATH
from time import strftime, strptime

FIELDNAMES = ["trip_id", "service_id", "route_id", "trip_name", "wheelchair_accessible", "bikes_allowed"]

def clean(data):
    return {
        "trip_id": data["trip_id"],
        "service_id": data["service_id"],
        "route_id": data["route_id"],
        "trip_name": data["trip_short_name"],
        "wheelchair_accessible": 0,
        "bikes_allowed": 0
    }

with open(HOME_PATH + "/data/trips.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/trip.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen = set()

        for row in reader:
            cleaned = clean(row)

            prim_key = cleaned["trip_id"]

            if prim_key not in seen:
                writer.writerow(cleaned)
                seen.add(prim_key)


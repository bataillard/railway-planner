import re
import csv

from constants import HOME_PATH
from time import strftime, strptime

FIELDNAMES = ["trip_name", "headsign"]

def clean(data):
    return {
        "trip_name": data["trip_short_name"],
        "headsign": data["trip_headsign"]
    }

with open(HOME_PATH + "/data/trips.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/tripname.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen = set()

        for row in reader:
            cleaned = clean(row)

            prim_key = cleaned["trip_name"]
            
            if prim_key not in seen:
                writer.writerow(cleaned)
                seen.add(prim_key)


import re
import csv

from constants import HOME_PATH
from time import strftime, strptime

FIELDNAMES = ["route_id", "agency_id", "route_type", "route_name"]

def clean(data):
    return {
        "route_id": data["route_id"],
        "agency_id": data["agency_id"],
        "route_type": data["route_desc"],
        "route_name": data["route_short_name"]
    }

with open(HOME_PATH + "/data/routes.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/route.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen = set()

        for row in reader:
            cleaned = clean(row)

            if cleaned["route_id"] not in seen:
                writer.writerow(cleaned)
                seen.add(cleaned["route_id"])


import re
import csv

from constants import HOME_PATH
from time import strftime, strptime

FIELDNAMES = ["trip_id", "stop_id", "track", "stop_sequence", "arrival_time", "departure_time"]

def clean(data):
    match = re.search(r"(\w+)(:(\w+):(\w+))?", data["stop_id"])

    return {
        "trip_id": data["trip_id"],
        "stop_id": match.group(1),
        "track": match.group(4).upper() if match.group(4) else "0",
        "stop_sequence": data["stop_sequence"],
        "arrival_time": data["arrival_time"],
        "departure_time": data["departure_time"]
    }

with open(HOME_PATH + "/data/stop_times.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/stoptime.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen = set()

        for row in reader:
            cleaned = clean(row)

            prim_key = cleaned["trip_id"] + cleaned["stop_id"] + cleaned["track"]

            if prim_key not in seen and "P" not in cleaned["stop_id"]:
                writer.writerow(cleaned)
                seen.add(prim_key)
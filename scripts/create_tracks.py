import re
import csv

from constants import HOME_PATH
from time import strftime, strptime

FIELDNAMES = ["stop_id", "track"]

def clean(data):
    match = re.search(r"(\w+)(:(\w+):(\w+))?", data["stop_id"])

    return {
        "stop_id": match.group(1),
        "track": match.group(4) if match.group(4) else "0",
    }

with open(HOME_PATH + "/data/stops.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/track.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen = []

        for row in reader:
            cleaned = clean(row)

            if cleaned["stop_id"] not in seen and "P" not in cleaned["stop_id"]:
                writer.writerow(cleaned)
                seen.append(cleaned["stop_id"])


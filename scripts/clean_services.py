import re
import csv

from constants import HOME_PATH
from time import strftime, strptime

FIELDNAMES = ["service_id", "runs_monday", "runs_tuesday", "runs_wednesday", \
    "runs_thursday", "runs_friday", "runs_saturday", "runs_sunday", "start_date", "end_date"]

def clean(data):
    return {
        "service_id": data["service_id"],
        "runs_monday": data["monday"],
        "runs_tuesday": data["tuesday"],
        "runs_wednesday": data["wednesday"],
        "runs_thursday": data["thursday"],
        "runs_friday": data["friday"],
        "runs_saturday": data["saturday"],
        "runs_sunday": data["sunday"],
        "start_date": strftime("%Y-%m-%d", strptime(data["start_date"], "%Y%m%d")),
        "end_date": strftime("%Y-%m-%d", strptime(data["end_date"], "%Y%m%d"))
    }

with open(HOME_PATH + "/data/calendar.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/service.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen = set()

        for row in reader:
            cleaned = clean(row)

            if cleaned["service_id"] not in seen:
                writer.writerow(cleaned)
                seen.add(cleaned["service_id"])


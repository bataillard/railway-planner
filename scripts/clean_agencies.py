import re
import csv

from constants import HOME_PATH

FIELDNAMES = ["agency_id", "agency_name"]

def clean_agency(data):
    return {
        "agency_id": data["agency_id"],
        "agency_name": data["agency_name"]
    }

with open(HOME_PATH + "/data/agency.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/agency.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen = []

        for row in reader:
            agency = clean_agency(row)

            if agency["agency_id"] not in seen:
                writer.writerow(agency)
                seen.append(agency["agency_id"])



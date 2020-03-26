import re
import csv

from constants import HOME_PATH

FIELDNAMES = ["stop_id", "stop_lat", "stop_long", "stop_name"]

def clean_stop(stop_dict):
    id = re.search(r"(\w+)(:(\w+):(\w+))?", stop_dict["stop_id"]).group(1)
    lat = float(stop_dict["stop_lat"])
    lon = float(stop_dict["stop_lon"])

    return {
        "stop_id": id,
        "stop_lat": lat,
        "stop_long": lon,
        "stop_name": stop_dict["stop_name"]
    }

with open(HOME_PATH + "/data/stops.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/stop.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen_stops = []

        for row in reader:
            stop = clean_stop(row)

            if stop["stop_id"] not in seen_stops and "P" not in stop["stop_id"]:
                writer.writerow(stop)
                seen_stops.append(stop["stop_id"])





import re
import csv

from constants import HOME_PATH
from time import strftime, strptime

FIELDNAMES = ["from_stop_id", "from_track", "to_stop_id", "to_track", "transfer_time"]

def clean(data):
    from_match = re.search(r"(\w+)(:(\w+):(\w+))?", data["from_stop_id"])
    to_match = re.search(r"(\w+)(:(\w+):(\w+))?", data["to_stop_id"])

    return {
        "from_stop_id": from_match.group(1) ,
        "from_track": from_match.group(4).upper() if from_match.group(4) else "0",
        "to_stop_id": to_match.group(1),
        "to_track": to_match.group(4).upper() if to_match.group(4) else "0",
        "transfer_time": data["min_transfer_time"]
    }

with open(HOME_PATH + "/data/transfers.txt", newline="", encoding="utf-8-sig") as csvfile:
    with open(HOME_PATH + "/data/clean/transfer.csv", "w", newline="\n") as outputcsv:
        reader = csv.DictReader(csvfile)
        writer = csv.DictWriter(outputcsv, fieldnames=FIELDNAMES, quoting=csv.QUOTE_ALL, lineterminator="\n")
        
        seen = set()

        for row in reader:
            cleaned = clean(row)

            prim_key = cleaned["from_stop_id"] + cleaned["from_track"] + cleaned["to_stop_id"] + cleaned["to_track"]
            
            if prim_key not in seen and "P" not in cleaned["from_stop_id"] and "P" not in cleaned["to_stop_id"]:
                writer.writerow(cleaned)
                seen.add(prim_key)


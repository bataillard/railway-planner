import re

from constants import HOME_PATH

def clean_line(line):
    data = line.split(",")
    no_quotes = [x.replace("\"", "") for x in data]
    
    return [x.strip() for x in no_quotes]

def convert_tuple(tup):
    return tup

with open(HOME_PATH + "/data/stops.txt") as f:
    f.readline()  # Ignore headers

    for count, line in enumerate(f):
        clean_tuple = clean_line(line)

        print(clean_tuple)


            

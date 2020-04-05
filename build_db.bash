#!/bin/bash

for f in scripts/*.py
do
  echo "Running $f"
  python3 "$f"
done

for s in sql/*
do
  echo "Building $s"
  mysql --host=localhost --user=<db-user> --password=<db-password> "railwayplanner" < "$s"
done
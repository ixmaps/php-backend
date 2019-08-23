#!/bin/bash
for file in application/model/*.php; do
  php -l "$file"
done

for file in application/controller/*.php; do
  php -l "$file"
done
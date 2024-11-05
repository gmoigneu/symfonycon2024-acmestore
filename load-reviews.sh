#!/bin/bash

# Loop through letters from 'b' to 's'
for letter in {b..s}; do
    # Execute the command with the constructed parameter
    php -d memory_limit=28000M bin/console app:load-test-review-data "/Users/nls/psh/acme-store/data/xa$letter"
done

#!/bin/bash

# Temporary script to update all remaining importers

FILES=(
    "ForumImporter.php"
    "GroupImporter.php"
    "OrderImporter.php"
    "PostImporter.php"
    "ProductImporter.php"
    "SubscriptionImporter.php"
    "TopicImporter.php"
    "UserSubscriptionImporter.php"
)

for FILE in "${FILES[@]}"; do
    echo "Processing $FILE..."
done

echo "Script created. Will manually update files now."
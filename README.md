# wikidata-extractor

# Get Wikipedia pages
Download Wikipedia pages for people in JSON file in ./wikidata_people

Set which JSON file in get_wikipedia_pages_single_file.py and run ```python3 get_wikipedia_pages_single_file.py``` - this will loop through all the people in the specified JSON file and get their Wikipedia page (and all pages linking to that page) and create a JSON file of all data for that person in ./wikipedia

# Create Training Data

Create CSV file run ```php create_entities_asset_file.php``` in ./transform

Create JSONL file run ```php create_jsonl_asset_file.php > {filename}.jsonl``` in ./transform, the data for this needs to be piped out
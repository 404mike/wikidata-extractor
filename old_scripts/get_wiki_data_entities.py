import sys
import time
import json
import os.path
from SPARQLWrapper import SPARQLWrapper, JSON

import os
proxy_host = 'cache.llgc.org.uk:80'
os.environ['HTTP_PROXY'] = proxy_host
os.environ['HTTPS_PROXY'] = proxy_host

# WIKIDATA API endpoint
endpoint_url = "https://query.wikidata.org/sparql"

def main():
  ''' Get number of results for query
  '''

  query = """SELECT (COUNT(*) AS ?count)
  WHERE 
  {
    ?item wdt:P27 wd:Q174193.
    SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
  }"""

  results = get_results(endpoint_url, query)
  num_items = results["results"]["bindings"][0]["count"]["value"]
  print("Found {num} items".format(num=num_items))

  loop_number_of_records(num_items)

def loop_number_of_records(num):
  ''' Function to loop through number of results 
      and create a SPARQL query for each offset
  '''

  # how many results per query
  limit = 1000
  total_num = f'{int(num):,}'

  # loop through each number of items, limit each loop
  for i in range(0, int(num), limit): 

    # place to store items
    wiki_data = []

    if not os.path.isfile("wikidata/{}.json".format(str(i))):

      results = get_wiki_data_pages(i, total_num, limit)

      # if there are no results print data to JSON file
      if not results["results"]["bindings"]:
        print("nothing left")
        final_print(i, wiki_data)

      # Loop through each of the results for this loop
      for item in results["results"]["bindings"]:
        wiki_id = item["item"]["value"]
        itemLabel = item["itemLabel"]["value"]

        # check to see if itemDescription exists in the result
        if "itemDescription" in item:
          itemDescription = item["itemDescription"]["value"]
        else:
          itemDescription = ""
        
        # check to see if wikipedia article exists
        if "article" in item:
          wikipedia_url = item["article"]["value"]
        else:
          wikipedia_url = ""

        # add items to a dictionary
        wiki_data_item = {"wiki_id": wiki_id,"itemLabel":itemLabel,"itemDescription":itemDescription,"wikipedia_url":wikipedia_url}
        # append the item to the wiki data array
        wiki_data.append(wiki_data_item)
    
      final_print(i, wiki_data)

      print("Waiting 3 seconds")
      time.sleep(3)

def get_wiki_data_pages(i, total_num, limit):
    temp_i = f'{int(i):,}'
    print("Getting page {}, out of {}".format(str(temp_i),str(total_num)))
    query = """PREFIX schema: <http://schema.org/>
    SELECT ?item ?itemLabel ?itemDescription ?article
    WHERE 
    {
      ?item wdt:P27 wd:Q174193.
      SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
      OPTIONAL {
          ?article schema:about ?item .
          ?article schema:inLanguage "en" .
          ?article schema:isPartOf <https://en.wikipedia.org/> .
        }
    }
    LIMIT {LIMIT}
    OFFSET {OFFSET}"""

    query = query.replace('{LIMIT}', str(limit))
    query = query.replace('{OFFSET}',str(i))
  
    results = get_results(endpoint_url, query)

    return results

def get_results(endpoint_url, query):
  ''' Perform SPARQL query on wikidata API
  '''
  user_agent = "WDQS-example Python/%s.%s" % (sys.version_info[0], sys.version_info[1])
  # TODO adjust user agent; see https://w.wiki/CX6
  sparql = SPARQLWrapper(endpoint_url, agent=user_agent)
  sparql.setQuery(query)
  sparql.setReturnFormat(JSON)
  return sparql.query().convert()

def final_print(filename, data):
  ''' Print data to JSON file
  '''
  jsonString = json.dumps(data)
  jsonFile = open("wikidata/{}.json".format(str(filename)), "w")
  jsonFile.write(jsonString)
  jsonFile.close()
  print("Written {}.json".format(str(filename)))

if __name__ == "__main__":
    main()
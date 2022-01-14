import time
import urllib
import os.path
import json
from glob import glob
from wikitextparser import remove_markup, parse
from urllib import request as urlrequest

import os
proxy_host = 'cache.llgc.org.uk:80'
os.environ['HTTP_PROXY'] = proxy_host
os.environ['HTTPS_PROXY'] = proxy_host


def main():

  files = []
  with open('wikidata_people/wikidata_people_wales.json', encoding='utf-8') as data_file:    
    data = json.load(data_file)
    # print(data)
    for v in data:
        files.append(v)
  parse_json_file(files, len(files))


def parse_json_file(files,number_of_items):
  '''Parse JSON file
    Loop through all the records in this JSON file
    Then parse out the contents of each record in parse_json_obj()
  '''
  number_of_loops = 0
  for item in files:
    # for item in json_obj:
    number_of_loops += 1 

    percent_remaining = (int(number_of_loops) / int(number_of_items)) * 100
    percent_remaining = round(percent_remaining, 3)
    print("Getting item {} out of {} - {}%".format(number_of_loops, number_of_items, percent_remaining))     
    parse_json_obj(item)
    print("")

def parse_json_obj(item):
  '''Extract data from JSON
  '''

  # extract data from JSON object
  wiki_id = item["item"]
  itemLabel = item["itemLabel"]
  
  if "itemDescription" in item:
    itemDescription = item["itemDescription"]
  else:
    itemDescription = ''

  if "article" in item:
    wikipedia_url = item["article"]
  else:
    wikipedia_url = ''

  # create new filename
  filename = wiki_id.replace('http://www.wikidata.org/entity/','')

  print("Checking {}".format(wiki_id))
  # Check to see if we already have downloaded this file
  if not os.path.isfile("wikipedia/{}.json".format(filename)):

    # temp - just testing on a single record
    # if wiki_id == "http://www.wikidata.org/entity/Q6450928":
    
    # Get main wikipedia page and all pages that link to that page
    wikipedia_page = get_wikipedia_page(wikipedia_url, itemLabel)

    # Object of all the data
    person = {"wiki_id": wiki_id, "itemLabel": itemLabel,
              "itemDescription": itemDescription, "wikipedia_url": wikipedia_url,
              "wikipedia_page": wikipedia_page}
    
    # create JSON file
    final_print(filename, person)
    
    # Pause so that we don't get throttled by the API
    print("Waiting 2 second")
    time.sleep(2)

def get_wikipedia_page(wikipedia_url, itemLabel):
  '''Get the summary of the main Wikipedia page for this person
  '''

  print("Getting main Wikipedia page for {}".format(itemLabel))

  # format Wikipedia URL
  wikipedia_url = wikipedia_url.replace('https://en.wikipedia.org/wiki/','')

  # if no wikipedia URL return
  if not wikipedia_url:
    return ""

  # Wiikipedia API URL format
  wikipedia_api_url = "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro&explaintext&redirects=1&titles=" + wikipedia_url

  # API rrequest
  req = urlrequest.Request(wikipedia_api_url)
  req.set_proxy(proxy_host, 'https')

  response = urlrequest.urlopen(req)
  data = response.read().decode('utf8')
  # exit()

  output = json.loads(data)

  # get wikipedia ID 
  key = list(output["query"]["pages"].keys())
  key = key[0]

  if "extract" in output["query"]["pages"][key]:
    main_wiki_extract = output["query"]["pages"][key]["extract"]  
  else:
    main_wiki_extract = ""

  # get pages that link to the main article for this person
  link_mentions = get_pages_linking_to_page(wikipedia_url, itemLabel)
  # link_mentions = {}

  return {"main": main_wiki_extract, "link_mentions": link_mentions}

def get_pages_linking_to_page(wikipedia_url, itemLabel):
  '''Get a list of pages that link to the main article of ther person
  '''

  print("Getting page that links to page main article for {}".format(itemLabel))

  # save all mentions to the person we're looking for
  link_mentions = []

  # format Wikipedia API URL
  wikipedia_api_url = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=backlinks&bllimit=50&bltitle=" + wikipedia_url

  # API request
  data = urllib.request.urlopen(wikipedia_api_url).read()
  output = json.loads(data)

  # list of page types to ignore
  ignore_pages = ["Talk:","User talk:", "Wikipedia:", "User:", "Portal:", 
                  "File:", "Category:", "Wikipedia talk:", "Template:",
                  "Draft:"]
  # loop through each of the page links
  for links in output["query"]["backlinks"]:
    link = links["title"]
    if any(x in link for x in ignore_pages):
      continue
    else:
      # get paragraph that mentions this person from this link
      response = parse_data_from_wiki_link_page(links["title"], itemLabel)
      if response:
        link_mentions.append({"page": links["title"], "response": response})
  
  return link_mentions

def parse_data_from_wiki_link_page(wikipedia_url, itemLabel):
  ''' Get a paragraph, that contains the name of the person
      we're looking for from a page that links to the main article
  '''

  print("\tPage: {}".format(wikipedia_url))

  # make the URL safe
  wikipedia_url = urllib.parse.quote_plus(wikipedia_url)

  # format Wikipedia API URL
  wikipedia_api_url = "https://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvslots=%2A&rvprop=content&formatversion=2&format=json&titles=" + wikipedia_url

  # API request
  data = urllib.request.urlopen(wikipedia_api_url).read()
  response = json.loads(data)

  # get element
  s = (response["query"]["pages"][0]["revisions"][0]["slots"]["main"]["content"])
  # remove mark-up
  try:
    s = remove_markup(s)
  except:
    s = ''

  # transform to plain text
  s = parse(s).plain_text()

  # list of all mentions
  p = []

  # loop through each line in the response
  # if that line contains the name of the person
  # save to p 
  for line in s.splitlines():
      if itemLabel in line:
        p.append(line)

  return p

def final_print(filename, data):
  ''' Print data to JSON file
  '''
  jsonString = json.dumps(data)
  jsonFile = open("wikipedia/{}.json".format(filename), "w")
  jsonFile.write(jsonString)
  jsonFile.close()
  print("Written {}.json".format(str(filename)))

if __name__ == "__main__":
    main()
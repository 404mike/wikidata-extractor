from glob import glob
import json,urllib.request
import time
import urllib
from wikitextparser import remove_markup, parse

def main():
  files = get_all_json_files()
  parse_json_file(files)
  
def get_all_json_files():
  '''Loop through all the JSON files in ./wikidata '''
  jsonlst = []
  for fname in glob("wikidata/*.json"):
    with open(fname, 'r') as f:
      jsonlst.append(json.load(f))
  return jsonlst

def parse_json_file(files):
  '''Parse JSON file
    Loop through all the records in this JSON file
    Then parse out the contents of each record in parse_json_obj()
  '''
  for json_obj in files:
    for item in json_obj:
      parse_json_obj(item)

def parse_json_obj(item):
  '''Extract data from JSON
  '''
  wiki_id = item["wiki_id"]
  itemLabel = item["itemLabel"]
  itemDescription = item["itemDescription"]
  wikipedia_url = item["wikipedia_url"]

  # temp - just testing on a single record
  if wiki_id == "http://www.wikidata.org/entity/Q6450928":
    # print(itemLabel)
    wikipedia_page = get_wikipedia_page(wikipedia_url, itemLabel)
    print(wikipedia_page)
  # print(wiki_id)
  # print("Waiting 3 seconds")
  # time.sleep(3)

    person = {"wiki_id": wiki_id, "itemLabel": itemLabel,
              "itemDescription": itemDescription, "wikipedia_url": wikipedia_url,
              "wikipedia_page": wikipedia_page}
    
    final_print("person", person)
    exit()

def get_wikipedia_page(wikipedia_url, itemLabel):
  '''Get the summary of the main Wikipedia page for this person
  '''

  # format Wikipedia URL
  wikipedia_url = wikipedia_url.replace('https://en.wikipedia.org/wiki/','')

  # if no wikipedia URL return
  if not wikipedia_url:
    return ""

  # Wiikipedia API URL format
  wikipedia_api_url = "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro&explaintext&redirects=1&titles=" + wikipedia_url

  # API rrequest
  data = urllib.request.urlopen(wikipedia_api_url).read()
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

  return {"main": main_wiki_extract, "link_mentions": link_mentions}

def get_pages_linking_to_page(wikipedia_url, itemLabel):
  '''Get a list of pages that link to the main article of ther person
  '''
  # save all mentions to the person we're looking for
  link_mentions = []

  # format Wikipedia API URL
  wikipedia_api_url = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=backlinks&bllimit=50&bltitle=" + wikipedia_url

  # API request
  data = urllib.request.urlopen(wikipedia_api_url).read()
  output = json.loads(data)

  # list of page types to ignore
  ignore_pages = ["Talk:","User talk:", "Wikipedia:", "User:"]
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
  s = remove_markup(s)

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
  jsonFile = open("wikipedia/{}.json".format(str(filename)), "w")
  jsonFile.write(jsonString)
  jsonFile.close()
  print("Written {}.json".format(str(filename)))

if __name__ == "__main__":
    main()
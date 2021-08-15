from glob import glob
import json,urllib.request
import time
import urllib
from wikitextparser import remove_markup, parse

def main():
  files = get_all_json_files()
  loop_json_files(files)
  
def get_all_json_files():
  jsonlst = []
  for fname in glob("wikidata/*.json"):
    with open(fname, 'r') as f:
      jsonlst.append(json.load(f))
  return jsonlst

def loop_json_files(files):
  for json_obj in files:
    for item in json_obj:
      parse_json_obj(item)


def parse_json_obj(item):
  wiki_id = item["wiki_id"]
  itemLabel = item["itemLabel"]
  itemDescription = item["itemDescription"]
  wikipedia_url = item["wikipedia_url"]

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
 
  wikipedia_url = wikipedia_url.replace('https://en.wikipedia.org/wiki/','')

  # if no wikipedia URL return
  if not wikipedia_url:
    return ""

  wikipedia_api_url = "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro&explaintext&redirects=1&titles=" + wikipedia_url

  # wikipedia_api_url = "https://en.wikipedia.org/w/api.php?format=json&action=query&prop=extracts&exintro&explaintext&redirects=1&titles=Walter_Moore_(footballer,_born_1899)"
  # print(wikipedia_api_url) 

  data = urllib.request.urlopen(wikipedia_api_url).read()
  output = json.loads(data)

  # get wikipedia ID 
  key = list(output["query"]["pages"].keys())
  key = key[0]

  if "extract" in output["query"]["pages"][key]:
    main_wiki_extract = output["query"]["pages"][key]["extract"]  
  else:
    main_wiki_extract = ""

  link_mentions = get_pages_linking_to_page(wikipedia_url, itemLabel)
  # print(link_mentions)

  return {"main": main_wiki_extract, "link_mentions": link_mentions}

def get_pages_linking_to_page(wikipedia_url, itemLabel):
 
  link_mentions = []

  wikipedia_api_url = "https://en.wikipedia.org/w/api.php?action=query&format=json&list=backlinks&bllimit=50&bltitle=" + wikipedia_url

  data = urllib.request.urlopen(wikipedia_api_url).read()
  output = json.loads(data)

  ignore_pages = ["Talk:","User talk:", "Wikipedia:", "User:"]
  for links in output["query"]["backlinks"]:
    link = links["title"]
    if any(x in link for x in ignore_pages):
      continue
    else:
      response = parse_data_from_wiki_link_page(links["title"], itemLabel)
      if response:
        link_mentions.append({"page": links["title"], "response": response})
  
  return link_mentions

def parse_data_from_wiki_link_page(wikipedia_url, itemLabel):

  wikipedia_url = urllib.parse.quote_plus(wikipedia_url)

  wikipedia_api_url = "https://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvslots=%2A&rvprop=content&formatversion=2&format=json&titles=" + wikipedia_url

  data = urllib.request.urlopen(wikipedia_api_url).read()
  response = json.loads(data)

  s = (response["query"]["pages"][0]["revisions"][0]["slots"]["main"]["content"])
  s = remove_markup(s)

  s = parse(s).plain_text()
  p = []
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
import os
import re
import requests
from bs4 import BeautifulSoup

# Unique & trackers
unique_corporations = {}
unique_pagination = {}

# Find existing corporations from File System
for corporation in os.listdir('corporations'):
    unique_corporations[corporation.replace('.html', '')] = True

def safe_name(name):
    # Remove any french or illegal characters
    # for before, after in [['/', '-'], ['é', 'e'], ['à', 'a'], ['É', 'E'], ['è', 'e'], ['.', '']]:
    for before, after in [['/', '-']]:
        name = name.replace(before, after)
    return name

# Parse HTML
def parse_page(s, content):
    errors = False
    soup = BeautifulSoup(content, "html.parser")
    links = soup.find("ul", "list-group list-group-hover").find_all("a", href=re.compile("nvgt.do?"))
    pagination = soup.find("ul", "pagination pagination-sm hidden-print").find_all("a", text="Next")
    print('links:', len(links))

    # Find all available links
    for link in links:
        name = link.get_text().strip()
        name = safe_name(name)

        if (unique_corporations.get(name) != True):
            request_corporation = s.get("https://www.ic.gc.ca/app/ccc/srch/" + link["href"])
            details = BeautifulSoup(request_corporation.content, "html.parser")
            title = details.find('title').get_text()

            if (re.findall('Error', title)):
                print("Found Error:", name)
                errors = True
                break
            else:
                print("Saving HTML:", name)
                with open("corporations/" + name + ".html", "w") as f:
                    unique_corporations[name] = True
                    f.write(details.prettify())
        else:
            print('Skipped:', name)

    # Restart Script
    if (errors):
        print('Restarting...')
        return parse_main()

    for link in pagination:
        if (unique_pagination.get(link['href']) != True and link.get_text() != 'Previous'):
            print('Next pagination')
            unique_pagination[link['href']] = True
            request_pagination = s.get("https://www.ic.gc.ca/app/ccc/srch/" + link['href'])
            parse_page(s, request_pagination.content)

def parse_main():
    # Log in session
    session = requests.session()
    session.get("https://www.ic.gc.ca/app/ccc/srch/")

    # Find all corporations
    payload = {
        "searchCriteriaBean.textField": "*",
        "searchCriteriaBean.column": "nm",
        "prtl": 1,
        "searchCriteriaBean.hitsPerPage": 500,
        "searchCriteriaBean.sortSpec": "title asc",
        "searchCriteriaBean.isSummaryOn": "N"
    }
    print('Starting scraper...')
    r = session.post("https://www.ic.gc.ca/app/ccc/srch/bscSrch.do", data=payload)
    parse_page(session, r.content)

if __name__ == '__main__':
    parse_main()


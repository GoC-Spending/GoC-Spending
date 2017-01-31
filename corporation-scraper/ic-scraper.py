import mechanicalsoup
import urllib

browser = mechanicalsoup.Browser()

search_page = browser.get("https://www.ic.gc.ca/app/ccc/srch/")

search_form = search_page.soup.select("#searchForm")[0]

search_form.find("input", {"name": "searchCriteriaBean.textField"})["value"] = "IBM Canada Ltd."
search_form.find("input", {"name": "searchCriteriaBean.column", "id": "company"})["checked"] = ""

company_list_page = browser.submit(search_form, search_page.url)


def is_link_to_company_details(href):
    return (
        (href.find('nvgt.do') is not -1) and
        (href.find('V_SEARCH.command=navigate') is not -1) and
        (href.find('estblmntNo') is not -1)
    )


company_details_link = company_list_page.soup.find_all("a", href=is_link_to_company_details)

parsed_url = urllib.parse.urljoin(company_list_page.url, company_details_link[0]['href'])

company_details_page = browser.get(parsed_url)

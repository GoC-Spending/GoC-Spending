import mechanicalsoup
import urllib


class IcScraper:
    def __init__(self, company_name):
        self.company_name = company_name
        self.browser = mechanicalsoup.Browser()

        self.is_canadian_company = None

        self.naics_code = None
        self.naics_text = None

        self.start_process(self.browser.get("https://www.ic.gc.ca/app/ccc/srch/"))

    def start_process(self, search_page):
        search_form = search_page.soup.select("#searchForm")[0]

        search_form.find("input", {"name": "searchCriteriaBean.textField"})["value"] = self.company_name
        search_form.find("input", {"name": "searchCriteriaBean.column", "id": "company"})["checked"] = ""

        self.find_and_process_corporation_page(self.browser.submit(search_form, search_page.url))

    def find_and_process_corporation_page(self, list_page):
        company_details_link = list_page.soup.find_all("a", href=self.is_link_to_company_details)

        parsed_url = urllib.parse.urljoin(list_page.url, company_details_link[0]['href'])

        company_details_page = self.browser.get(parsed_url)

        self.extract_row_information(company_details_page, 'Country of Ownership:', self.process_country_ownership)
        self.extract_row_information(company_details_page, 'Primary Industry (NAICS):', self.process_naics)

    def process_country_ownership(self, country_ownership_string):
        self.is_canadian_company = country_ownership_string is not 'Foreign'

    def process_naics(self, naics_string):
        split_string = naics_string.split(' - ')

        self.naics_code = split_string[0]
        self.naics_text = split_string[1]

    @staticmethod
    def extract_row_information(details_page, identifier_string, processor_function):
        def contains_identifier(string):
            return string.find(identifier_string) is not -1

        identifier_label = details_page.soup.find_all("strong", string=contains_identifier)[0]

        for parent in identifier_label.parents:
            if parent.get('class') is not None:
                if 'row' in parent['class']:
                    processor_function(parent.find_all("div")[1].string.strip())
                    break

    @staticmethod
    def is_link_to_company_details(href):
        return (
            (href.find('nvgt.do') is not -1) and
            (href.find('V_SEARCH.command=navigate') is not -1) and
            (href.find('estblmntNo') is not -1)
        )


ibm_data = IcScraper("IBM Canada Ltd.")

print(ibm_data.is_canadian_company)
print(ibm_data.naics_code)
print(ibm_data.naics_text)
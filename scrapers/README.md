# Documentation for Scrapers

> Useful HTTP logs for URL requests

## Log in for sessions

### HTTP Get

https://www.ic.gc.ca/app/ccc/srch/

## Search for Federal Corporations

https://www.ic.gc.ca/app/scr/cc/CorporationsCanada/fdrlCrpSrch.html

![image](https://cloud.githubusercontent.com/assets/550895/22557729/659bdbb8-e939-11e6-9bdc-a1f0400d7be2.png)

## Search with Open Corporates

https://opencorporates.com/

![image](https://cloud.githubusercontent.com/assets/550895/22557819/a8627cea-e939-11e6-9209-5dc8fe0a9492.png)

## Search by company name

![image](https://cloud.githubusercontent.com/assets/550895/22494202/87033b02-e803-11e6-8832-60449a1aa347.png)

### HTTP Post

https://www.ic.gc.ca/app/ccc/srch/bscSrch.do

**Payload**

```json
{
    "searchCriteriaBean.textField": "*",
    "searchCriteriaBean.column": "nm",
    "prtl": 1,
    "V_SEARCH.docsStart": 10,
    "searchCriteriaBean.hitsPerPage": 25,
    "searchCriteriaBean.sortSpec": "title asc",
    "searchCriteriaBean.isSummaryOn": "N"
}
```

> You can use `*` for `textField` to query the entire database.

## Get company details

> This query only works when using HTTP sessions.

![image](https://cloud.githubusercontent.com/assets/550895/22494273/17bef56e-e804-11e6-8cf9-acbcddbcece7.png)

### HTTP GET

**Request Headers**

```http
GET /app/ccc/srch/nvgt.do?V_SEARCH.docsCount=3&V_SEARCH.docsStart=1&V_DOCUMENT.docRank=1&lang=eng&prtl=1&profile=cmpltPrfl&V_TOKEN=1485918271008&V_SEARCH.command=navigate&V_SEARCH.resultsJSP=/prfl.do&estblmntNo=234567029712&profileId= HTTP/1.1
Host: www.ic.gc.ca
Upgrade-Insecure-Requests: 1
Referer: https://www.ic.gc.ca/app/ccc/srch/bscSrch.do
```

**Query String Params**

```http
V_SEARCH.docsCount:3
V_SEARCH.docsStart:1
V_DOCUMENT.docRank:1
lang:eng
prtl:1
profile:cmpltPrfl
V_TOKEN:1485918271008
V_SEARCH.command:navigate
V_SEARCH.resultsJSP:/prfl.do
estblmntNo:234567029712
profileId:
```

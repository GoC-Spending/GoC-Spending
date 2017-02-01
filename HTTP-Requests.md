> Useful HTTP logs for URL requests

## Search by company name

![image](https://cloud.githubusercontent.com/assets/550895/22494202/87033b02-e803-11e6-8832-60449a1aa347.png)

### HTTP Post

https://www.ic.gc.ca/app/ccc/srch/bscSrch.do

**Request Headers**

```http
POST /app/ccc/srch/bscSrch.do HTTP/1.1
Host: www.ic.gc.ca
Origin: https://www.ic.gc.ca
Upgrade-Insecure-Requests: 1
Referer: https://www.ic.gc.ca/app/ccc/srch/srch.do;jsessionid=0000bDkI5m4D_gbvZy1H1qfErA-:17e5e02re?lang=eng&profileId=&prtl=1&searchCriteriaBean.portal=1&V_SEARCH.documentJSP=%2FretrieveEstablishmentIdFromVerity.do&searchPage=%252Fapp%252Fccc%252Fsrch%252FcccBscSrch.do%253Flang%253Deng%2526amp%253Bprtl%253D1%2526amp%253Btagid%253D&searchCriteriaBean.resultJsp=%2Fresults.do&exportCsvLang=&V_SEARCH.scopeCategory=CCC.Root&V_SEARCH.depth=1&V_SEARCH.showStricts=false&searchCriteriaBean.textField=IBM+Canada+Ltd.&sbmtBtn=&searchCriteriaBean.conceptOperator=&searchCriteriaBean.column=nm&searchCriteriaBean.companyName=&searchCriteriaBean.province=&searchCriteriaBean.city=&searchCriteriaBean.postalCode=&searchCriteriaBean.companyProfile=&searchCriteriaBean.naicsCodeText=&searchCriteriaBean.product=&searchCriteriaBean.primaryBusinessActivity=&searchCriteriaBean.numberOfEmployees=&searchCriteriaBean.totalSales=&searchCriteriaBean.exportSales=&searchCriteriaBean.isExporter=&searchCriteriaBean.isExportingOrInterested=exportingActively&searchCriteriaBean.exportingCountry=&searchCriteriaBean.exportingState=&searchCriteriaBean.nsnCode=&searchCriteriaBean.fscCode=&searchCriteriaBean.niinCode=&searchCriteriaBean.marketInterest=&searchCriteriaBean.cageCode=&searchCriteriaBean.jcoCode=&searchCriteriaBean.dunNumber=&searchCriteriaBean.dunSuffix=&searchCriteriaBean.hitsPerPage=10&searchCriteriaBean.sortSpec=title+asc&searchCriteriaBean.isSummaryOn=Y
```

**Payload**

```
lang:eng
profileId:
prtl:1
searchCriteriaBean.hitsPerPage:10
searchPage:%2Fapp%2Fccc%2Fsrch%2FcccBscSrch.do%3Flang%3Deng%26amp%3Bprtl%3D1%26amp%3Btagid%3D
V_SEARCH.scopeCategory:CCC.Root
V_SEARCH.depth:1
V_SEARCH.showStricts:false
searchCriteriaBean.sortSpec:title asc
searchCriteriaBean.textField:IBM Canada Ltd.
buttonFind:
```

## Get company details

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

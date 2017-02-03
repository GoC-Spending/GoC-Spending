"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : new P(function (resolve) { resolve(result.value); }).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t;
    return { next: verb(0), "throw": verb(1), "return": verb(2) };
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (_) try {
            if (f = 1, y && (t = y[op[0] & 2 ? "return" : op[0] ? "throw" : "next"]) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [0, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var fs = require("fs");
var path = require("path");
var request = require("request-promise");
var cheerio = require("cheerio");
var d3 = require("d3-queue");
var unidecode = require('unidecode');
// Create Folder
if (!fs.existsSync('corporations')) {
    fs.mkdirSync('corporations');
}
function main() {
    return __awaiter(this, void 0, void 0, function () {
        var _this = this;
        var corporations, jar, login, formData, search, $, links, q, start, count;
        return __generator(this, function (_a) {
            switch (_a.label) {
                case 0:
                    corporations = {};
                    fs.readdirSync(path.join(__dirname, 'corporations')).map(function (filename) {
                        var name = filename.replace('.html', '');
                        corporations[name] = true;
                    });
                    jar = request.jar();
                    return [4 /*yield*/, request.get('https://www.ic.gc.ca/app/ccc/srch/', { jar: jar })];
                case 1:
                    login = _a.sent();
                    // Get list of corporation names
                    console.log('Start search...');
                    formData = {
                        'searchCriteriaBean.textField': '*',
                        'searchCriteriaBean.column': 'nm',
                        'prtl': 1,
                        'searchCriteriaBean.hitsPerPage': 1000,
                        'searchCriteriaBean.sortSpec': 'title asc',
                        'searchCriteriaBean.isSummaryOn': 'N'
                    };
                    return [4 /*yield*/, request.post('https://www.ic.gc.ca/app/ccc/srch/srch.do', { formData: formData, jar: jar })];
                case 2:
                    search = _a.sent();
                    $ = cheerio.load(search);
                    links = $('ul.list-group.list-group-hover').find('a');
                    q = d3.queue(5);
                    start = new Date().getTime();
                    count = 0;
                    // Iterate over available links
                    links.map(function (index, element) { return __awaiter(_this, void 0, void 0, function () {
                        var _this = this;
                        var name_1, href_1, V_TOKEN, offset_V_TOKEN_1;
                        return __generator(this, function (_a) {
                            if (element.children.length) {
                                name_1 = element.children[0].data.trim();
                                name_1 = name_1.replace('/', '-').replace('.', '');
                                name_1 = unidecode(name_1);
                                name_1 = name_1.toUpperCase();
                                href_1 = element.attribs.href;
                                if (href_1.match(/nvgt.do/)) {
                                    V_TOKEN = Number(href_1.match(/V_TOKEN=(\d*)/)[1]);
                                    offset_V_TOKEN_1 = new Date().getTime() - V_TOKEN;
                                    // Create new request for details
                                    if (fs.existsSync(path.join(__dirname, 'corporations', name_1 + '.html'))) {
                                    }
                                    else {
                                        q.defer(function (callback) { return __awaiter(_this, void 0, void 0, function () {
                                            var fake_href, baseUrl, details, title;
                                            return __generator(this, function (_a) {
                                                switch (_a.label) {
                                                    case 0:
                                                        fake_href = href_1.replace(/V_TOKEN=\d*/, "V_TOKEN=" + (new Date().getTime() - offset_V_TOKEN_1));
                                                        baseUrl = 'https://www.ic.gc.ca/app/ccc/srch/';
                                                        return [4 /*yield*/, request.get(baseUrl + href_1, { jar: jar })];
                                                    case 1:
                                                        details = _a.sent();
                                                        title = cheerio.load(details)('title').text().trim();
                                                        if (title.match(/Error/i)) {
                                                            console.log('Restarting...');
                                                            main();
                                                        }
                                                        else {
                                                            // console.log('Count:', count, 'Time:', new Date().getTime() - start)
                                                            // count++
                                                            console.log('Saving HTML:', "-" + name_1 + "-");
                                                            fs.writeFileSync(path.join(__dirname, 'corporations', name_1 + '.html'), details);
                                                            callback(null);
                                                        }
                                                        return [2 /*return*/];
                                                }
                                            });
                                        }); });
                                    }
                                }
                            }
                            return [2 /*return*/];
                        });
                    }); });
                    q.awaitAll(function (error) {
                        console.log('done');
                    });
                    return [2 /*return*/];
            }
        });
    });
}
main();

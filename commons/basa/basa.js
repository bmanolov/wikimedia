var Report = function(rows) {
	this.rows = rows || [];
	this.rowsByFile = {};
	this.rows.forEach(function(row){
		this.rowsByFile[row.file] = row;
	}, this);

	this.toString = function() {
		this.sort();
		var s = '';
		$.each(this.rows, function(i, row){
			s += ' '+ row.date +' ; '+ $.trim(row.file +' ; '+ row.articles.join(' ; ')) +'\n';
		});
		return s;
	};

	this.sort = function() {
		this.rows.sort(function(row1, row2){
			return row1.date > row2.date;
		});
	};

	this.hasFile = function(file) {
		return this.rowsByFile[Report.normalizeFileName(file)];
	};
};
Report.fromString = function(string) {
	var rows = [];
	string.split("\n").forEach(function(rawRow){
		var parts = rawRow.split(" ; ");
		var row = {
			date: $.trim(parts[0]),
			file: $.trim(parts[1])
		};
		row.articles = parts.slice(2) || {};
		rows.push(row);
	});
	return new Report(rows);
};
Report.fromFiles = function(files) {
	var rows = [];
	$.each(files, function(file, data){
		var row = {
			date: data.timestamp.replace(/T.+/, ''),
			file: Report.normalizeFileName(file),
			articles: data.articles
		};
		rows.push(row);
	});
	return new Report(rows);
};
Report.normalizeFileName = function(file) {
	return file.replace(/^File:/, '');
};


$.ajaxSetup({async:false});

function getFromApi(params, totalResults) {
	var url = '/w/api.php';
	params = params || {};
	$.extend(params, {
		action: "query",
		format: "json"
	});
	var key = params.list || params.prop;
	$.getJSON(url, params, function(response) {
		var results = response.query[key] || response.query.pages;
		if ($.isArray(results)) {
			totalResults = totalResults || [];
			$.merge(totalResults, results);
		} else {
			totalResults = totalResults || {};
			$.extend(totalResults, results);
		}
		if (response["query-continue"]) {
			getFromApi($.extend(params, response["query-continue"][key]), totalResults);
		}
	});
	return totalResults;
}

function fetchPages() {
	var params = {
		list: "categorymembers",
		cmtitle: "Category:Images from the Bulgarian Archives State Agency",
		cmnamespace: 6,
		cmlimit: 500,
		cmprop: "timestamp|title|ids"
	};
	return getFromApi(params);
}

function fetchUsages(titles) {
	return getFromApi({
		prop: "globalusage",
		gulimit: 500,
		guprop: "namespace",
		gufilterlocal: 1,
		titles: titles.join("|")
	});
}

function fetchImageinfo(titles) {
	return getFromApi({
		prop: "imageinfo",
		iilimit: 15, // get last 15 revisions
		titles: titles.join("|")
	});
}

function flipUploadsByTitle(uploads) {
	var normalized = {};
	$.each(uploads, function(i, upload){
		if (!normalized[upload.title]) {
			normalized[upload.title] = upload;
		}
	});
	return normalized;
}

function chunkifyPages(uploads, chunkSize) {
	chunkSize = chunkSize || 5;
	var titleChunks = [];
	var titles = [];
	$.each(uploads, function(i, upload) {
		if (titles.length >= chunkSize) {
			titleChunks.push(titles);
			titles = [];
		}
		titles.push(upload.title);
	});
	if (titles.length) {
		titleChunks.push(titles);
	}
	return titleChunks;
}

function getInterwikisFromPages(pages) {
	var interwikis = [];
	pages.forEach(function(page) {
		if (page.ns == "0" && /wikipedia/.test(page.wiki)) { // allow only wikipeda articles
			var title = page.title.replace(/_/g, ' ');
			var wiki = page.wiki.replace(/\.wikipedia\.org/, '');
			interwikis.push(wiki + ":" + title);
		}
	});
	return interwikis;
}

function enhanceFilePagesWithUsagesAndTimestamps(uploads, newlyCreatedArticles) {
	var uploadsByTitle = flipUploadsByTitle(uploads);
	var arrayChunks = chunkifyPages(uploads, 50);
	arrayChunks.forEach(function(titles) {
		$.each(fetchUsages(titles), function(pageid, page) {
			var interwikis = getInterwikisFromPages(page.globalusage);
			interwikis.forEach(function(interwiki, idx) {
				if (newlyCreatedArticles.indexOf(interwiki) !== -1) {
					interwikis[idx] += '*';
				}
			});
			uploadsByTitle[page.title]['articles'] = interwikis;
		});
	});
	arrayChunks.forEach(function(titles) {
		$.each(fetchImageinfo(titles), function(pageid, page) {
			var info;
			do {
				info = page.imageinfo.pop();
				if (/^BASA/.test(info.user)) {
					break;
				}
			} while (page.imageinfo.length);
			uploadsByTitle[page.title]['timestamp'] = info.timestamp || '';
		});
	});
	return uploadsByTitle;
}

function extractNewlyCreatedPages(content) {
	var pages = [];
	$.each(content.split("\n"), function(i, line) {
		var parts = line.split(';');
		$.each(parts, function(pi, part) {
			part = $.trim(part);
			var matches = part.match(/(.+)\*$/);
			if (matches) {
				pages.push(matches[1]);
			}
		});
	});
	return pages;
}

function getOldReport() {
	var page = 'Commons:Bulgarian Archives State Agency/Uploads';
	var pageUrl = mw.util.getUrl(page, { action: 'raw' });
	var oldReport;
	$.get(pageUrl)
	.done(function(response) {
		oldReport = response;
	})
	.fail(function() {
		alert('Error by fetching of old report');
	});
	return oldReport;
}

function chunkifyPages(pages, chunkSize) {
	chunkSize = chunkSize || 5;
	var chunks = [];
	var currentChunk = [];
	$.each(pages, function(i, page) {
		if (currentChunk.length >= chunkSize) {
			chunks.push(currentChunk);
			currentChunk = [];
		}
		currentChunk.push(page.title);
	});
	if (currentChunk.length) {
		chunks.push(currentChunk);
	}
	return chunks;
}

var pages = fetchPages();
pages = enhanceFilePagesWithUsagesAndTimestamps(pages, extractNewlyCreatedPages(getOldReport()));

var report = Report.fromFiles(pages);
$('<textarea rows="20"/>')
	.val(report.toString())
	.on('focus', function() { $(this).select() })
	.appendTo('body');

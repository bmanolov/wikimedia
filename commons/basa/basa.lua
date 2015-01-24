local messagesBg = {
	No = "№",
	Date = "Дата",
	Uploaded_image = "Качено изображение",
	Illustrated_article = "Илюстрирана(и) статия(и)",
	Nb = "Бр.",
	NbFiles = "Брой качени файлове: ",
	NbBgArticles = "Брой илюстрирани статии на български: ",
	NbNonBgArticles = "Брой илюстрирани статии на други езици: ",
	NewArticles = "Брой новосъздадени статии: ",
	NewArticle = "нова статия",
	source = "изходни данни",
}
local messagesEn = {
	No = "No",
	Date = "Date",
	Uploaded_image = "Uploaded image",
	Illustrated_article = "Illustrated article(s)",
	Nb = "#Art",
	NbFiles = "Number of uploads: ",
	NbBgArticles = "Number of Bulgarian articles illustrated: ",
	NbNonBgArticles = "Number of non-Bulgarian articles illustrated: ",
	NewArticles = "Number of new articles created: ",
	NewArticle = "new article",
	source = "source data",
}
local messages = {
	bg = messagesBg,
	en = messagesEn
}
local module = {}

local function table_size(tbl)
	local size = 0
	for k, v in pairs(tbl) do
		size = size + 1
	end
	return size
end

local function links_from_keys(articles)
	local links = {}
	for article, v in pairs(articles) do
		table.insert(links, '[[:' .. article .. ']]')
	end
	return table.concat(links, ', ')
end

local function string_split(str, delimiter)
	local chunks = {}
	local from = 1
	local delim_from, delim_to = string.find(str, delimiter, from)
	while delim_from do
		table.insert(chunks, string.sub(str, from , delim_from-1))
		from = delim_to + 1
		delim_from, delim_to = string.find(str, delimiter, from)
	end
	table.insert(chunks, string.sub(str, from))
	return chunks
end

function module.report_table(frame)
	local m = messages[frame.args.lang]
	local head = '{| width="100%" class="wikitable sortable"\n'
		.. '! ' .. m.No .. '\n'
		.. '! ' .. m.Date .. '\n'
		.. '! ' .. m.Uploaded_image .. '\n'
		.. '! ' .. m.Illustrated_article .. '\n'
		.. '! ' .. m.Nb .. '\n'
		.. '|-\n'
	local tables = {}
	local rows = {}
	local counter = 0
	local bg_articles = {}
	local nonbg_articles = {}
	local new_articles = {}
	local years = {}
	local chunks, row, arg, year, file, link, is_new_article
	local data_template = frame.args.data_template

	local lines = string_split(frame:expandTemplate{ title = data_template }, '\n')

	for counter = 1, #lines, 1 do

		chunks = string_split(mw.text.trim(lines[counter], " ;"), ';')
		file = mw.text.trim(chunks[2])
		if not string.find(file, '[[', 1, true) then
			file = '[[:File:' .. file .. '|' .. file .. ']]'
		end
		row = '|' .. counter .. '||' .. chunks[1] .. '||' .. file .. '||'
		if #chunks > 2 then
			for i = 3, #chunks, 1 do
				link = mw.text.trim(chunks[i])
				is_new_article = link:sub(-1, -1) == '*'
				if is_new_article then
					link = mw.text.trim(link, '*')
					new_articles[link] = 1
				end
				link = '[[:' .. link .. ']]'
				if is_new_article then
					link = link .. '<sup title="'..m.NewArticle..'">☆</sup>'
				end
				row = row .. (i > 3 and ', ' or '') .. link
				if string.find(chunks[i], 'bg:', 1, true) then
					bg_articles[link] = 1
				else
					nonbg_articles[link] = 1
				end
			end
		end
		row = row .. ' || ' .. (#chunks > 2 and (#chunks - 2) or '')
		year = string.sub(chunks[1], 0, 4)
		if tables[year] == nil then
			tables[year] = {}
			table.insert(years, year)
		end
		table.insert(tables[year], row)

	end

	local new_articles_count = table_size(new_articles)
	local output = '<div style="float:right; font-size:90%">([['..data_template..'|'..m.source..']])</div>\n'
		.. '*' .. m.NbFiles .. #lines .. '\n'
		.. '*' .. m.NbBgArticles .. table_size(bg_articles) .. '\n'
		.. '*' .. m.NbNonBgArticles .. table_size(nonbg_articles) .. '\n'
		.. '*' .. m.NewArticles .. new_articles_count .. '\n'
	if new_articles_count > 0 then
		output = output .. '*: ☆ ' .. links_from_keys(new_articles) .. '\n'
	end

	table.sort(years)
	for i = 1, #years do
		local rows = tables[years[i]]
		output = output
			.. '\n== ' .. years[i] .. ' <small>(' .. #rows .. ')</small> ==\n'
			.. head .. table.concat(rows, '\n|-\n') .. '\n|}\n'
	end

	return output
end

return module

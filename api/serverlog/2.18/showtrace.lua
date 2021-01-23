function shell(cmd, quite)
	if not quite then print("", "shell", cmd) end
	local f = io.popen(cmd)
	local ret = f:read("*all")
	f:close()
	return ret
end

function split(s, delim)
	assert (type (delim) == "string" and string.len (delim) > 0,"bad delimiter")
	local start = 1  local t = {}
	while true do
		local pos = string.find (s, delim, start, true) -- plain find
		if not pos then
			break
		end
		table.insert (t, string.sub (s, start, pos - 1))
		start = pos + string.len (delim)
	end
	table.insert (t, string.sub (s, start))
	return t
end

base64 = {}

local b='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
-- encoding
base64.enc = function(data)
    return ((data:gsub('.', function(x)
        local r,b='',x:byte()
        for i=8,1,-1 do r=r..(b%2^i-b%2^(i-1)>0 and '1' or '0') end
        return r;
    end)..'0000'):gsub('%d%d%d?%d?%d?%d?', function(x)
        if (#x < 6) then return '' end
        local c=0
        for i=1,6 do c=c+(x:sub(i,i)=='1' and 2^(6-i) or 0) end
        return b:sub(c+1,c+1)
    end)..({ '', '==', '=' })[#data%3+1])
end
-- decoding
base64.dec = function(data)
    data = string.gsub(data, '[^'..b..'=]', '')
    return (data:gsub('.', function(x)
        if (x == '=') then return '' end
        local r,f='',(b:find(x)-1)
        for i=6,1,-1 do r=r..(f%2^i-f%2^(i-1)>0 and '1' or '0') end
        return r;
    end):gsub('%d%d%d?%d?%d?%d?%d?%d?', function(x)
        if (#x ~= 8) then return '' end
        local c=0
        for i=1,8 do c=c+(x:sub(i,i)=='1' and 2^(8-i) or 0) end
        return string.char(c)
    end))
end


local text = base64.dec(arg[1])

local text1 = [[
<2018/06/27 04:07:54.104> [a0075_battle_ground:48098][error] ./zone/zonebattleAirdrop.lua:230: attempt to index local 'dropInfo' (a nil value)
<2018/06/27 04:07:54.104> stack traceback:
	[string " function print(...) ..."]:1: in function '__index'
	./zone/zonebattleAirdrop.lua:230: in function 'callback'
	./zone/zonecmd.lua:50: in function <./zone/zonecmd.lua:36>
	./zone/zoneclientlib.lua:48: in function <./zone/zoneclientlib.lua:44>
	[C]: in function 'xpcall'
	./zone/zoneclientlib.lua:44: in function 'callback'
	./zone/zonecmd.lua:70: in function <./zone/zonecmd.lua:69>
	./zone/zonecmd.lua:213: in function <./zone/zonecmd.lua:213>
	[C]: in function 'xpcall'
	./zone/zonecmd.lua:213: in function <./zone/zonecmd.lua:199>
	[C]: in function 'xpcall'
	./common/netlib.lua:338: in function 'onRecv'
	./zone/zoneentry.lua:113: in function <./zone/zoneentry.lua:112>
	[C]: in function 'xpcall'
	./zone/zoneentry.lua:112: in function <./zone/zoneentry.lua:111>
]]

local stdout = {}
local branch, rev = text:match("%[([%w_]+):(%d+)%]%[")
if not branch or not rev then
	stdout[#stdout + 1] = "<pre>" .. text .. "</pre>"
	print("[" ..base64.enc(table.concat(stdout)).. "]")
	return
end


local ret = {}

local format = function(text, color)
	color = color or "black"
	local ret = text
		:gsub("\t", "    ")
		:gsub("&", "&amp;")
		:gsub(" ", "&nbsp;")
		:gsub("<", "&lt;")
		:gsub(">", "&gt;")
		:gsub("\"", "&quot;")
	return  "<div style='white-space:nowrap;color:"..color.."'>" .. ret .. "</div>"
end

local showlog = function(rev)
	local svnurl = "http://svn.funova.com/svn/gunsoul_x/"
	local xml = shell("svn log " .. svnurl .. " -r " .. rev .. " --xml")
	local log = xml:match("<msg>(.*)</msg>") or ""
	--local author = xml:match("<author>(.*)</author>")
	return "<div style='background-color:#555;color:yellow'>rev:" .. rev .. " msg:" .. log .. "</div>"
end

local cache = {}
local showcode = function(filename, line)
	if cache[filename..line] then
		return ""--cache[filename..line]
	end
	local ret = {}
	local svnurl = "http://svn.funova.com/svn/gunsoul_x/branches/autobranch/" .. branch .. "/newserver" .. filename
	if branch == "trunk" then
		svnurl = "http://svn.funova.com/svn/gunsoul_x/trunk/project/newserver" .. filename
	end
	local list = split(shell("svn blame " .. svnurl .. " -g -v -r " .. rev), "\n")
	local linerev, lineauthor
	for i = line - 15, line + 5 do
		if list[i] then
			local txt = list[i]
			txt = txt:gsub("%+0800%s*%([^%)]*%)", "")
			if i == tonumber(line) then
				linerev, lineauthor = txt:match("%s+(%d+)%s+(%w+)")
				txt =  format(string.format("% 4d", i) .. txt, "red")
				ret[#ret + 1] = showlog(linerev)
			else
				txt =  format(string.format("% 4d", i) .. txt, "black")
			end
			ret[#ret + 1] = txt
		end
	end
	cache[filename..line] = table.concat(ret)
	return cache[filename..line], linerev or "0", lineauthor or ""
end

local skip = 0
text:gsub("\r", ""):gsub("[^\n]+", function(line)
	stdout[#stdout + 1] = format(line)
	if skip == 1 then
		return
	end
	if line:find("in function 'xpcall'") then
		skip = 1
	end
	local filename, linenum = line:match("([^%s]+%.lua):(%d+)")
	if filename and linenum and not line:find("in function 'LogError'") then
		filename = filename:gsub("^%.", "")
		local code, linerev, lineauthor = showcode(filename, linenum)
		stdout[#stdout + 1] = string.format("<div style='margin-left:50px;background-color:#DDD;display:inline-block;'>%s</div>", code)
		skip = 1
	end
end)

--print(table.concat(stdout))
print("[" ..base64.enc(table.concat(stdout)).. "]")

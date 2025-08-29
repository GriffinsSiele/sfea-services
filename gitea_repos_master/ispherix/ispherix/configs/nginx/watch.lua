local curl = require("cURL")

local function exec(query)
    local addr = os.getenv("CLICKHOUSE_TCP_ADDR")
    if not addr then
        error("CLICKHOUSE_ADDR environment variable is not set")
    end

    curl.easy()
        :setopt_url("http://" .. addr)
        :setopt_writefunction(io.stderr)
        :setopt_httppost(curl.form()
                             :add_content("query", query)
    )
        :perform()
        :close()
end

local function quote(str)
    if str == nil then
        return "NULL"
    end
    return "'" .. str:gsub("'", "''") .. "'"
end

-- language=clickhouse
local query = string.format([[
insert into ispherix.nginx_logs (
        client_ip,
        client_port,
        proxy_ip,
        proxy_port,
        username,
        request_method,
        request_uri,
        request_protocol,
        referer,
        user_agent
    )
    values (
        %s, -- client_ip
        %d, -- client_port
        %s, -- proxy_ip
        %d, -- proxy_port
        %s, -- username
        %s, -- request_method
        %s, -- request_uri
        %s, -- request_protocol
        %s, -- referer
        %s  -- user_agent
    );
]],
        quote(ngx.var.remote_addr),
        ngx.var.remote_port,
        quote(ngx.var.proxy_protocol_server_addr),
        ngx.var.proxy_protocol_server_port,
        quote(ngx.req.get_uri_args()["u"]),
        quote(ngx.req.get_method()),
        quote(ngx.var.uri),
        quote(ngx.var.server_protocol),
        quote(ngx.req.get_headers()["Referer"]),
        quote(ngx.req.get_headers()["User-Agent"])
)

exec(query)

local filename = "/app/assets/images/pixel.png"
local file = io.open(filename, "rb")
if not file then
    error("file " .. filename .. " not available for reading")
end

ngx.header["Cache-Control"] = "no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0";
ngx.header["Content-Type"] = "image/png"
ngx.header["Last-Modified"] = ""

local file_content = file:read("*a")

ngx.print(file_content)

file:close()

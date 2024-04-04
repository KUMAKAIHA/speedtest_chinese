A docker version of LibreSpeed is available here: [GitHub Packages](https://github.com/librespeed/speedtest/pkgs/container/speedtest)

## Downloading docker image
To download LibreSpeed from the docker repo, use this command:

```
docker pull ghcr.io/librespeed/speedtest
```

You will now have a new docker image called `librespeed/speedtest`.


## Docker Compose
To start the container using [docker compose](https://docs.docker.com/compose/) the following configuration can be used:

```yml
version: '3.7'
services:
  speedtest:
    container_name: speedtest
    image: ghcr.io/librespeed/speedtest:latest
    restart: always
    environment:
      MODE: standalone
      #TITLE: "LibreSpeed"
      #TELEMETRY: "false"
      #ENABLE_ID_OBFUSCATION: "false"
      #REDACT_IP_ADDRESSES: "false"
      #PASSWORD:
      #EMAIL:
      #DISABLE_IPINFO: "false"
      #DISTANCE: "km"
      #WEBPORT: 80
    ports:
      - "80:80" # webport mapping (host:container)
```

Please adjust the environment variables according to the intended operating mode.

## Standalone mode
If you want to install LibreSpeed on a single server, you need to configure it in standalone mode. To do this, set the `MODE` environment variable to `standalone`.

The test can be accessed on port 80.

Here's a list of additional environment variables available in this mode:
* __`TITLE`__: Title of your speed test. Default value: `LibreSpeed`
* __`TELEMETRY`__: Whether to enable telemetry or not. If enabled, you maybe want your data to be persisted. See below. Default value: `false`
* __`ENABLE_ID_OBFUSCATION`__: When set to true with telemetry enabled, test IDs are obfuscated, to avoid exposing the database internal sequential IDs. Default value: `false`
* __`REDACT_IP_ADDRESSES`__: When set to true with telemetry enabled, IP addresses and hostnames are redacted from the collected telemetry, for better privacy. Default value: `false`
* __`PASSWORD`__: Password to access the stats page. If not set, stats page will not allow accesses.
* __`EMAIL`__: Email address for GDPR requests. Must be specified when telemetry is enabled.
* __`IPINFO_APIKEY`__: API key for ipinfo.io. Optional, but required if you expect to serve a large number of tests
* __`DISABLE_IPINFO`__: If set to true, ISP info and distance will not be fetched from ipinfo.io. Default: value: `false`
* __`DISTANCE`__: When `DISABLE_IPINFO` is set to false, this specifies how the distance from the server is measured. Can be either `km` for kilometers, `mi` for miles, or an empty string to disable distance measurement. Default value: `km`
* __`WEBPORT`__: Allows choosing a custom port for the included web server. Default value: `80`. Note that you will have to expose it through docker with the -p argument

If telemetry is enabled, a stats page will be available at `http://your.server/results/stats.php`, but a password must be specified.

### Persist sqlite database

Default DB driver is sqlite. The DB file is written to `/database/db.sql`.

So if you want your data to be persisted over image updates, you have to mount a volume with `-v $PWD/db-dir:/database`.


###### Example
This command starts LibreSpeed in standalone mode, with the default settings, on port 80:

```
docker run -e MODE=standalone -p 80:80 -it ghcr.io/librespeed/speedtest
```

This command starts LibreSpeed in standalone mode, with telemetry, ID obfuscation and a stats password, on port 86:

```
docker run -e MODE=standalone -e TELEMETRY=true -e ENABLE_ID_OBFUSCATION=true -e PASSWORD="yourPasswordHere" -e WEBPORT=86 -p 86:86 -v $PWD/db-dir/:/database -it ghcr.io/librespeed/speedtest
```

## Multiple Points of Test
For multiple servers, you need to set up 1+ LibreSpeed backends, and 1 LibreSpeed frontend.

### Backend mode
In backend mode, LibreSpeed provides only a test point with no UI. To do this, set the `MODE` environment variable to `backend`.

The following backend files can be accessed on port 80: `garbage.php`, `empty.php`, `getIP.php`

Here's a list of additional environment variables available in this mode:
* __`IPINFO_APIKEY`__: API key for ipinfo.io. Optional, but required if you expect to serve a large number of tests

###### Example:
This command starts LibreSpeed in backend mode, with the default settings, on port 80:
```
docker run -e MODE=backend -p 80:80 -it ghcr.io/librespeed/speedtest
```

### Frontend mode
In frontend mode, LibreSpeed serves clients the Web UI and a list of servers. To do this:
* Set the `MODE` environment variable to `frontend`
* Create a servers.json file with your test points. The syntax is the following:
    ```
    [
        {
            "name": "Friendly name for Server 1",
            "server" :"//server1.mydomain.com/",
            "dlURL" :"garbage.php",
            "ulURL" :"empty.php",
            "pingURL" :"empty.php",
            "getIpURL" :"getIP.php"
        },
        {
            "name": "Friendly name for Server 2",
            "server" :"https://server2.mydomain.com/",
            "dlURL" :"garbage.php",
            "ulURL" :"empty.php",
            "pingURL" :"empty.php",
            "getIpURL" :"getIP.php"
        },
        ...more servers...
    ]
    ```
    Note: if a server only supports HTTP or HTTPS, specify the protocol in the server field. If it supports both, just use `//`.
* Mount this file to `/servers.json` in the container (example at the end of this file)

The test can be accessed on port 80.

Here's a list of additional environment variables available in this mode:
* __`TITLE`__: Title of your speedtest. Default value: `LibreSpeed`
* __`TELEMETRY`__: Whether to enable telemetry or not. Default value: `false`
* __`ENABLE_ID_OBFUSCATION`__: When set to true with telemetry enabled, test IDs are obfuscated, to avoid exposing the database internal sequential IDs. Default value: `false`
* __`REDACT_IP_ADDRESSES`__: When set to true with telemetry enabled, IP addresses and hostnames are redacted from the collected telemetry, for better privacy. Default value: `false`
* __`PASSWORD`__: Password to access the stats page. If not set, stats page will not allow accesses.
* __`EMAIL`__: Email address for GDPR requests. Must be specified when telemetry is enabled.
* __`DISABLE_IPINFO`__: If set to true, ISP info and distance will not be fetched from ipinfo.io. Default: value: `false`
* __`DISTANCE`__: When `DISABLE_IPINFO` is set to false, this specifies how the distance from the server is measured. Can be either `km` for kilometers, `mi` for miles, or an empty string to disable distance measurement. Default value: `km`
* __`WEBPORT`__: Allows choosing a custom port for the included web server. Default value: `80`

###### Example
This command starts LibreSpeed in frontend mode, with a given `servers.json` file, and with telemetry, ID obfuscation, and a stats password:
```
docker run -e MODE=frontend -e TELEMETRY=true -e ENABLE_ID_OBFUSCATION=true -e PASSWORD="yourPasswordHere" -v $(pwd)/servers.json:/servers.json -p 80:80 -it ghcr.io/librespeed/speedtest
```

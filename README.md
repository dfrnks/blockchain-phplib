# blockchain-phplib

This project is a blockchain with full PHP.

It connects with other peers via the websocket.

The blockchain will mine another block every 5 seconds on average.

To start just run

```shell script
php service start
```

Tp change the port and number of process running

```shell script
php service start 8080:4 2220:4
```
    
php service start [Port http] [Port WebSocket]

##API REST HTTP

GET **/chain** - Return the chain

GET **/peers** - Return the peers connected

PUT **/block** - Add a block to the chain, send the data in the body

GET **/lastblock** - Return the last block
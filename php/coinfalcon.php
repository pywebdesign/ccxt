<?php

namespace ccxt;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception as Exception; // a common import

class coinfalcon extends Exchange {

    public function describe () {
        return array_replace_recursive (parent::describe (), array (
            'id' => 'coinfalcon',
            'name' => 'CoinFalcon',
            'countries' => array ( 'GB' ),
            'rateLimit' => 1000,
            'version' => 'v1',
            'has' => array (
                'fetchTickers' => true,
                'fetchOpenOrders' => true,
            ),
            'urls' => array (
                'logo' => 'https://user-images.githubusercontent.com/1294454/41822275-ed982188-77f5-11e8-92bb-496bcd14ca52.jpg',
                'api' => 'https://coinfalcon.com',
                'www' => 'https://coinfalcon.com',
                'doc' => 'https://docs.coinfalcon.com',
                'fees' => 'https://coinfalcon.com/fees',
                'referral' => 'https://coinfalcon.com/?ref=CFJSVGTUPASB',
            ),
            'api' => array (
                'public' => array (
                    'get' => array (
                        'markets',
                        'markets/{market}/orders',
                        'markets/{market}/trades',
                    ),
                ),
                'private' => array (
                    'get' => array (
                        'user/accounts',
                        'user/orders/{id}',
                        'user/trades',
                    ),
                    'post' => array (
                        'user/orders',
                    ),
                    'delete' => array (
                        'user/orders/{id}',
                    ),
                ),
            ),
            'fees' => array (
                'trading' => array (
                    'maker' => 0.0025,
                    'taker' => 0.0025,
                ),
            ),
            'precision' => array (
                'amount' => 8,
                'price' => 8,
            ),
        ));
    }

    public function fetch_markets () {
        $response = $this->publicGetMarkets ();
        $markets = $response['data'];
        $result = array ();
        for ($i = 0; $i < count ($markets); $i++) {
            $market = $markets[$i];
            list ($baseId, $quoteId) = explode ('-', $market['name']);
            $base = $this->common_currency_code($baseId);
            $quote = $this->common_currency_code($quoteId);
            $symbol = $base . '/' . $quote;
            $precision = array (
                'amount' => $this->safe_integer($market, 'size_precision'),
                'price' => $this->safe_integer($market, 'price_precision'),
            );
            $result[] = array (
                'id' => $market['name'],
                'symbol' => $symbol,
                'base' => $base,
                'quote' => $quote,
                'baseId' => $baseId,
                'quoteId' => $quoteId,
                'active' => true,
                'precision' => $precision,
                'limits' => array (
                    'amount' => array (
                        'min' => pow (10, -$precision['amount']),
                        'max' => null,
                    ),
                    'price' => array (
                        'min' => pow (10, -$precision['price']),
                        'max' => null,
                    ),
                    'cost' => array (
                        'min' => null,
                        'max' => null,
                    ),
                ),
                'info' => $market,
            );
        }
        return $result;
    }

    public function parse_ticker ($ticker, $market = null) {
        if ($market === null) {
            $marketId = $ticker['name'];
            $market = $this->marketsById[$marketId];
        }
        $symbol = $market['symbol'];
        $timestamp = $this->milliseconds ();
        $last = floatval ($ticker['last_price']);
        return array (
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'high' => null,
            'low' => null,
            'bid' => null,
            'bidVolume' => null,
            'ask' => null,
            'askVolume' => null,
            'vwap' => null,
            'open' => null,
            'close' => $last,
            'last' => $last,
            'previousClose' => null,
            'change' => floatval ($ticker['change_in_24h']),
            'percentage' => null,
            'average' => null,
            'baseVolume' => floatval ($ticker['volume']),
            'quoteVolume' => null,
            'info' => $ticker,
        );
    }

    public function fetch_ticker ($symbol, $params = array ()) {
        $this->load_markets();
        $tickers = $this->fetch_tickers($params);
        return $tickers[$symbol];
    }

    public function fetch_tickers ($symbols = null, $params = array ()) {
        $response = $this->publicGetMarkets ();
        $tickers = $response['data'];
        $result = array ();
        for ($i = 0; $i < count ($tickers); $i++) {
            $ticker = $this->parse_ticker($tickers[$i]);
            $symbol = $ticker['symbol'];
            $result[$symbol] = $ticker;
        }
        return $result;
    }

    public function fetch_order_book ($symbol, $limit = null, $params = array ()) {
        $this->load_markets();
        $response = $this->publicGetMarketsMarketOrders (array_merge (array (
            'market' => $this->market_id($symbol),
            'level' => '3',
        ), $params));
        return $this->parse_order_book($response['data'], null, 'bids', 'asks', 'price', 'size');
    }

    public function parse_trade ($trade, $market = null) {
        $timestamp = $this->parse8601 ($trade['created_at']);
        $price = floatval ($trade['price']);
        $amount = floatval ($trade['size']);
        $symbol = $market['symbol'];
        $cost = floatval ($this->cost_to_precision($symbol, $price * $amount));
        return array (
            'info' => $trade,
            'timestamp' => $timestamp,
            'datetime' => $this->iso8601 ($timestamp),
            'symbol' => $symbol,
            'id' => null,
            'order' => null,
            'type' => null,
            'side' => null,
            'price' => $price,
            'amount' => $amount,
            'cost' => $cost,
            'fee' => null,
        );
    }

    public function fetch_trades ($symbol, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        $request = array (
            'market' => $market['id'],
        );
        if ($since !== null) {
            $request['since'] = $this->iso8601 ($since);
        }
        $response = $this->publicGetMarketsMarketTrades (array_merge ($request, $params));
        return $this->parse_trades($response['data'], $market, $since, $limit);
    }

    public function fetch_balance ($params = array ()) {
        $this->load_markets();
        $response = $this->privateGetUserAccounts ($params);
        $result = array ( 'info' => $response );
        $balances = $response['data'];
        for ($i = 0; $i < count ($balances); $i++) {
            $balance = $balances[$i];
            $currencyId = $this->safe_string($balance, 'currency_code');
            $uppercase = strtoupper ($currencyId);
            $code = $this->common_currency_code($uppercase);
            if (is_array ($this->currencies_by_id) && array_key_exists ($uppercase, $this->currencies_by_id)) {
                $code = $this->currencies_by_id[$uppercase]['code'];
            }
            $account = array (
                'free' => floatval ($balance['available_balance']),
                'used' => floatval ($balance['hold_balance']),
                'total' => floatval ($balance['balance']),
            );
            $result[$code] = $account;
        }
        return $this->parse_balance($result);
    }

    public function parse_order ($order, $market = null) {
        if ($market === null) {
            $market = $this->marketsById[$order['market']];
        }
        $symbol = $market['symbol'];
        $timestamp = $this->parse8601 ($order['created_at']);
        $price = floatval ($order['price']);
        $amount = $this->safe_float($order, 'size');
        $filled = $this->safe_float($order, 'size_filled');
        $remaining = $this->amount_to_precision($symbol, $amount - $filled);
        $cost = $this->price_to_precision($symbol, $amount * $price);
        // pending, open, partially_filled, fullfilled, canceled
        $status = $order['status'];
        if ($status === 'fulfilled') {
            $status = 'closed';
        } else if ($status === 'canceled') {
            $status = 'canceled';
        } else {
            $status = 'open';
        }
        $type = explode ('_', $order['operation_type']);
        return array (
            'id' => $this->safe_string($order, 'id'),
            'datetime' => $this->iso8601 ($timestamp),
            'timestamp' => $timestamp,
            'status' => $status,
            'symbol' => $symbol,
            'type' => $type[0],
            'side' => $order['order_type'],
            'price' => $price,
            'cost' => $cost,
            'amount' => $amount,
            'filled' => $filled,
            'remaining' => $remaining,
            'trades' => null,
            'fee' => null,
            'info' => $order,
        );
    }

    public function create_order ($symbol, $type, $side, $amount, $price = null, $params = array ()) {
        $this->load_markets();
        $market = $this->market ($symbol);
        // $price/size must be string
        $amount = $this->amount_to_precision($symbol, floatval ($amount));
        $request = array (
            'market' => $market['id'],
            'size' => (string) $amount,
            'order_type' => $side,
        );
        if ($type === 'limit') {
            $price = $this->price_to_precision($symbol, floatval ($price));
            $request['price'] = (string) $price;
        }
        $request['operation_type'] = $type . '_order';
        $response = $this->privatePostUserOrders (array_merge ($request, $params));
        $order = $this->parse_order($response['data'], $market);
        $id = $order['id'];
        $this->orders[$id] = $order;
        return $order;
    }

    public function cancel_order ($id, $symbol = null, $params = array ()) {
        $this->load_markets();
        $response = $this->privateDeleteUserOrders (array_merge (array (
            'id' => $id,
        ), $params));
        $market = $this->market ($symbol);
        return $this->parse_order($response['data'], $market);
    }

    public function fetch_open_orders ($symbol = null, $since = null, $limit = null, $params = array ()) {
        $this->load_markets();
        $request = array ();
        if ($symbol !== null) {
            $request['market'] = $this->market_id($symbol);
        }
        if ($since !== null) {
            $request['since_time'] = $this->iso8601 ($this->milliseconds ());
        }
        // TODO => test status=all if it works for closed orders too
        $response = $this->privateGetUserOrders (array_merge ($request, $params));
        return $this->parse_orders($response['data']);
    }

    public function nonce () {
        return $this->milliseconds ();
    }

    public function sign ($path, $api = 'public', $method = 'GET', $params = array (), $headers = null, $body = null) {
        $request = '/' . 'api/' . $this->version . '/' . $this->implode_params($path, $params);
        $url = $this->urls['api'] . $request;
        $query = $this->omit ($params, $this->extract_params($path));
        if ($api === 'public') {
            if ($query)
                $url .= '?' . $this->urlencode ($query);
        } else {
            $this->check_required_credentials();
            if ($method === 'GET') {
                if ($query)
                    $url .= '?' . $this->urlencode ($query);
            } else {
                $body = $this->json ($query);
            }
            $seconds = (string) $this->seconds ();
            $payload = implode ('|', array ($seconds, $method, $request));
            if ($body) {
                $payload .= '|' . $body;
            }
            $signature = $this->hmac ($this->encode ($payload), $this->encode ($this->secret));
            $headers = array (
                'CF-API-KEY' => $this->apiKey,
                'CF-API-TIMESTAMP' => $seconds,
                'CF-API-SIGNATURE' => $signature,
                'Content-Type' => 'application/json',
            );
        }
        return array ( 'url' => $url, 'method' => $method, 'body' => $body, 'headers' => $headers );
    }

    public function handle_errors ($code, $reason, $url, $method, $headers, $body) {
        if ($code < 400) {
            return;
        }
        $ErrorClass = $this->safe_value(array (
            '401' => '\\ccxt\\AuthenticationError',
            '429' => '\\ccxt\\DDoSProtection',
        ), $code, '\\ccxt\\ExchangeError');
        throw new $ErrorClass ($body);
    }
}

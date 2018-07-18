<?php

namespace App\Action;

use GuzzleHttp\Client;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class SendAction implements ServerMiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {

        $body = $request->getParsedBody();

        $headers = $this->parseHeaders($body['headers']);
        $refreshList = false;
        try {
            /** @var \SQLite3 $con */
            $con = $request->getAttribute("con");

            $sql = "SELECT count(*) AS num_rows FROM request 
                    WHERE method='" . $body['method'] . "' AND url='" . $body['url'] . "' 
                    AND headers='" . $body['headers'] . "' AND body='" . $body['body'] . "' AND created_at BETWEEN DATETIME('now','-1 day') AND  DATETIME('now');";

            $res = $con->query($sql)->fetchArray(SQLITE3_ASSOC);

            if ($res['num_rows'] == 0) {
                $sql = "INSERT INTO request('method', url, headers, body, created_at) VALUES('" . $body['method'] . "','" . $body['url'] . "','" . $body['headers'] . "','" . $body['body'] . "', DATETIME('now'))";

                $con->query($sql);
                $refreshList = true;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }

        if (!strstr($body['url'], "http")) {
            $body['url'] = "http://" . $body['url'];
        }

        $client = new Client([
            'base_uri' => $body['url'],
            "headers" => $headers,
            'http_errors' => false,
        ]);


        $microtime = microtime(true);
        try {


            switch ($body['method']) {
                case "GET":
                    $response = $client->get("");
                    break;
                case "POST";

                    $response = $client->post("", [
                        "json" => json_decode($body['body'], 1)
                    ]);

                    break;
                case "PUT":
                    $response = $client->put("", [
                        "json" => json_decode($body['body'], 1)
                    ]);
                    break;
                case "DELETE":
                    $response = $client->delete("");
                    break;
            }
            $microtimeAfter = microtime(true);
            $time = $microtimeAfter - $microtime;

            $responseBody = $response->getBody()->getContents();
            $contentType = $response->getHeaders()['Content-type'][0];
            $contentType = explode(";", $contentType)[0];
            switch ($contentType) {
                case "text/html":
                case "text/xhtml":
                case "text/xml":
                    $dom = new \DOMDocument();
                    $dom->preserveWhiteSpace = true;
                    $dom->loadXML($responseBody);
                    $dom->formatOutput = true;
                    $responseBody = $dom->saveXML();
                    break;
            }

            if (!mb_check_encoding($responseBody, 'UTF-8')) {
                $responseBody = utf8_encode($responseBody);
            }
            $statusCode = $response->getStatusCode();
        } catch (\Exception $e) {
            $responseBody = "NOT FOUND: " . $e->getMessage();
            $statusCode = 404;
        }

        $headersResponse = "";
        $headers = $response->getHeaders();
        foreach ($headers as $idx => $header) {
            $headersResponse .= $idx . ": " . implode($header, "; ") . "<br>";

        }

        return new JsonResponse(["body" => $responseBody, "headers" => $headersResponse, "contentType" => $contentType, "statusCode" => $statusCode, "refreshList" => $refreshList, "time" => round($time, 3)]);
    }

    public function parseHeaders($headers)
    {
        $finalHeaders = [];
        if (trim($headers) != "") {
            $headers = explode("\n", $headers);
            foreach ($headers as $header) {
                $header = explode(":", $header);
                if (count($header) != 2) {
                    throw new \Exception("Header incorreto");
                }
                $finalHeaders[trim($header[0])] = trim($header[1]);
            }
        }

        return $finalHeaders;
    }


}

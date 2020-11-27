<?php

namespace App\Connectors;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use App\Models\Token;
use App\Models\GoEngine;
use Illuminate\Support\Str;

class GenericConnector
{
    public $server;
    public $user;
    private $engine;
    private $client;

    public function __construct(\App\Models\Server $server = null, $user = null)
    {
        $this->server = $server;
        $this->user = $user;
        $this->client = new Client([
            "base_uri" => getProxyServer(),
            "verify" => false
        ]);
    }

    public function execute($command)
    {
        return trim(
            $this->request('runCommand', [
                "command" => $command,
            ])
        );
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        return trim(
            $this->request('putFile', [
                "localPath" => $localPath,
                "remotePath" => $remotePath,
            ])
        );
    }

    public function receiveFile($localPath, $remotePath)
    {
        return trim(
            $this->request('getFile', [
                "localPath" => $localPath,
                "remotePath" => $remotePath,
            ])
        );
    }

    public function runScript($script, $parameters, $runAsRoot = false)
    {
        return trim(
            $this->request('getFile', [
                "script" => $script,
                "parameters" => $parameters,
                "runAsRoot" => $runAsRoot,
            ])
        );
        $remotePath = "/tmp/" . Str::random();

        $this->sendFile($script, $remotePath);
        $output = $this->execute("[ -f '$remotePath' ] && echo 1 || echo 0");
        if ($output != "1") {
            abort(504, "Betik gönderilemedi");
        }
        $this->execute("chmod +x " . $remotePath);

        // Run Part Of The Script
        $query = $runAsRoot ? sudo() : '';
        $query = $query . $remotePath . " " . $parameters . " 2>&1";
        $output = $this->execute($query);

        return $output;
    }

    public function verify($ip_address, $username, $password, $port, $type)
    {
        return trim(
            $this->request('verify', [
                "ip_address" => $ip_address,
                "username" => $username,
                "password" => $password,
                "port" => $port,
                "keyType" => $type,
            ])
        );
    }

    public function create(
        \App\Models\Server $server,
        $username,
        $password,
        $user_id,
        $key,
        $port = null
    ) {
    }

    public function request($url, $params, $retry = 3)
    {
        if ($this->server != null) {
            $params["server_id"] = $this->server->id;
        }

        if ($this->user == null) {
            $params["token"] = Token::create(user()->id);
        } else {
            $params["token"] = Token::create($this->user->id);
        }
        
        try {
            $response = $this->client->request(
                'POST',
                "/$url",
                [
                    "form_params" => $params,
                ]
            );
            return $response->getBody()->getContents();
        } catch (\Exception $exception) {
            abort(504, "Liman Go Servisinde bir sorun oluştu, lütfen yöneticinizle iletişime geçin." . $exception->getMessage());
            return null;
        }
    }
}

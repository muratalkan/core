<?php

namespace App\Connectors;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use App\Models\Token;
use App\Models\GoEngine;

class GenericConnector
{
    public $server;
    public $user;
    private $engine;
    private $client;

    public function __construct(\App\Models\Server $server = null, $user = null)
    {
        $engines = GoEngine::where("enabled", true)->get();
        if ($engines->count() == 0) {
            abort(504, "Şu anda kullanılabilecek hiçbir liman-go sunucusu yok.");
        }
        foreach ($engines as $engine) {
            $status = @fsockopen(
                $engine->ip_address,
                $engine->port,
                $errno,
                $errstr,
                3
            );
            if (!is_resource($status)) {
                $engine->update([
                    "enabled" => false
                ]);
                continue;
            }
            $this->engine = $engine;
            break;
        }
        if ($this->engine == null) {
            abort(504, "Şu anda kullanılabilecek hiçbir liman-go sunucusu yok.");
        }
        $this->server = $server;
        $this->user = $user;
        $this->client = new Client([
            "base_uri" => "https://" . $this->engine->ip_address . ":" . $this->engine->port,
            "verify" => false
        ]);
    }

    public function execute($command)
    {
        return trim(
            self::request('runCommand', [
                "command" => $command,
            ])
        );
    }

    public function sendFile($localPath, $remotePath, $permissions = 0644)
    {
        return trim(
            self::request('putFile', [
                "localPath" => $localPath,
                "remotePath" => $remotePath,
            ])
        );
    }

    public function receiveFile($localPath, $remotePath)
    {
        return trim(
            self::request('getFile', [
                "localPath" => $localPath,
                "remotePath" => $remotePath,
            ])
        );
    }

    public function runScript($script, $parameters, $runAsRoot = false)
    {
        return trim(
            self::request('getFile', [
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
            self::request('verify', [
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

<?php

namespace App\System;

use GuzzleHttp\Client;
use App\Models\GoEngine;

class Helper
{
    private $engine;
    public function __construct()
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
        $this->client = new Client([
            "base_uri" => "https://" . $this->engine->ip_address . ":" . $this->engine->port,
            "verify" => false
        ]);
        $this->client->setDefaultOption('headers', array('liman-system' => $this->engine->token));
    }

    public function userAdd($extension_id)
    {
        try {
            $this->client->request(
                'POST',
                "/userAdd",
                [
                    "form_params" => [
                        "extension_id" => $extension_id
                    ],
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function userRemove($extension_id)
    {
        try {
            $this->client->request(
                'POST',
                "/userRemove",
                [
                    "form_params" => [
                        "extension_id" => cleanDash($extension_id)
                    ],
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function dnsUpdate($server1, $server2, $server3)
    {
        try {
            $this->client->request(
                'POST',
                "/dns",
                [
                    "form_params" => [
                        "server1" => $server1,
                        "server2" => $server2,
                        "server3" => $server3,
                    ],
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function addCertificate($tmpPath, $targetName)
    {
        try {
            $this->client->request(
                'POST',
                "/certificateAdd",
                [
                    "form_params" => [
                        "tmpPath" => $tmpPath,
                        "targetName" => $targetName
                    ],
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function removeCertificate($targetName)
    {
        try {
            $this->client->request(
                'POST',
                "/certificateAdd",
                [
                    "form_params" => [
                        "targetName" => $targetName
                    ],
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function fixExtensionPermissions($extension_id, $extension_name)
    {
        try {
            $this->client->request(
                'POST',
                "/fixPermissions",
                [
                    "form_params" => [
                        "extension_id" => cleanDash($extension_id),
                        "extension_name" => $extension_name
                    ],
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function runCommand($command)
    {
        try {
            $response = $this->client->request(
                'POST',
                "/fixPermissions",
                [
                    "form_params" => [
                        "command" => $command
                    ],
                ]
            );
        } catch (\Exception $e) {
            return __("Liman Sistem Servisine Erişilemiyor!");
        }
        return $response->getBody()->getContents();
    }
}
